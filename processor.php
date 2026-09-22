<?php
require_once __DIR__.'/../strava/functions.php';
require_once __DIR__.'/../strava/time.php';

function toetapFinishWebhook(PDO $pdo,int $id,string $status,string $msg): void {
    $s=$pdo->prepare("UPDATE webhook_events SET status=?,message=?,processed_at=UTC_TIMESTAMP() WHERE id=?");
    $s->execute([$status,substr($msg,0,500),$id]);
}

function toetapProcessWebhookEvent(PDO $pdo,int $eventId): array {
    $claim=$pdo->prepare("UPDATE webhook_events SET status='PROCESSING' WHERE id=? AND status='RECEIVED'");
    $claim->execute([$eventId]);
    if($claim->rowCount()!==1) return ['id'=>$eventId,'status'=>'SKIPPED','message'=>'Event already claimed/processed.'];

    $q=$pdo->prepare("SELECT * FROM webhook_events WHERE id=? LIMIT 1");
    $q->execute([$eventId]); $ev=$q->fetch();
    if(!$ev) return ['id'=>$eventId,'status'=>'ERROR','message'=>'Event missing.'];

    try{
        if($ev['object_type']!=='activity' || $ev['aspect_type']!=='create'){
            toetapFinishWebhook($pdo,$eventId,'IGNORED','Only activity create events are auto-matched.');
            return ['id'=>$eventId,'status'=>'IGNORED'];
        }

        $conn=$pdo->prepare("SELECT user_id FROM strava_connections WHERE athlete_id=? LIMIT 1");
        $conn->execute([$ev['owner_id']]); $userId=(int)$conn->fetchColumn();
        if(!$userId){
            toetapFinishWebhook($pdo,$eventId,'IGNORED','No TOETAP user mapped to this Strava athlete.');
            return ['id'=>$eventId,'status'=>'IGNORED'];
        }

        $token=validToken($pdo,$userId);
        $activity=stravaHttp('GET','https://www.strava.com/api/v3/activities/'.rawurlencode((string)$ev['object_id']),[],$token);
        $sport=$activity['sport_type']??$activity['type']??'';
        if(!in_array($sport,['Run','TrailRun','VirtualRun'],true)){
            toetapFinishWebhook($pdo,$eventId,'IGNORED','Not a running activity.');
            return ['id'=>$eventId,'status'=>'IGNORED'];
        }

        $runUtc=stravaUtcToDb($activity['start_date']);
        $window=(new DateTimeImmutable($runUtc,new DateTimeZone('UTC')))->modify('-6 hours')->format('Y-m-d H:i:s');

        // V0.5 — Latest Tap Wins: choose only the newest unused tap in the 6-hour window.
        $selQ=$pdo->prepare("SELECT ss.*,us.strava_gear_id,us.strava_gear_name
          FROM shoe_selections ss JOIN user_shoes us ON us.id=ss.user_shoe_id
          WHERE ss.user_id=? AND ss.used=0 AND us.strava_gear_id IS NOT NULL
            AND ss.selected_at<=? AND ss.selected_at>=?
          ORDER BY ss.selected_at DESC, ss.id DESC
          LIMIT 1");
        $selQ->execute([$userId,$runUtc,$window]); $sel=$selQ->fetch();

        if(!$sel){
            toetapFinishWebhook($pdo,$eventId,'IGNORED','No eligible shoe selection in the 6-hour window.');
            return ['id'=>$eventId,'status'=>'IGNORED','matches'=>0];
        }

        $gear=$sel['strava_gear_id']; $original=$activity['gear_id']??null;

        $dupe=$pdo->prepare("SELECT id FROM activities WHERE user_id=? AND (strava_activity_id=? OR (source='STRAVA' AND external_activity_id=?)) LIMIT 1");
        $dupe->execute([$userId,(string)$activity['id'],(string)$activity['id']]);
        if($dupe->fetchColumn()){
            toetapFinishWebhook($pdo,$eventId,'IGNORED','Activity already processed.');
            return ['id'=>$eventId,'status'=>'IGNORED'];
        }

        // External side effect first. If DB write fails, event becomes ERROR and is visible.
        stravaHttp('PUT','https://www.strava.com/api/v3/activities/'.rawurlencode((string)$activity['id']),['gear_id'=>$gear],$token);

        $pdo->beginTransaction();
        $ins=$pdo->prepare("INSERT INTO activities
          (user_id,user_shoe_id,source,external_activity_id,strava_activity_id,activity_type,distance_m,moving_time_s,start_date,assigned_at,match_status,shoe_selection_id,original_gear_id,proposed_gear_id)
          VALUES(?,?,'STRAVA',?,?,?,?,?,?,UTC_TIMESTAMP(),'APPLIED',?,?,?)");
        $ins->execute([$userId,$sel['user_shoe_id'],(string)$activity['id'],(string)$activity['id'],$sport,$activity['distance']??0,
          $activity['moving_time']??0,$runUtc,$sel['id'],$original,$gear]);
        $used=$pdo->prepare("UPDATE shoe_selections SET used=1 WHERE id=? AND used=0");
        $used->execute([$sel['id']]);
        if($used->rowCount()!==1) throw new Exception('Selection was already consumed.');
        $pdo->commit();

        toetapFinishWebhook($pdo,$eventId,'APPLIED','Gear '.$gear.' applied automatically.');
        return ['id'=>$eventId,'status'=>'APPLIED','activity'=>(string)$activity['id'],'gear'=>$gear];
    }catch(Throwable $e){
        if($pdo->inTransaction()) $pdo->rollBack();
        toetapFinishWebhook($pdo,$eventId,'ERROR',$e->getMessage());
        return ['id'=>$eventId,'status'=>'ERROR','message'=>$e->getMessage()];
    }
}

if(!function_exists('tapsoleProcessWebhookEvent')){ function tapsoleProcessWebhookEvent(PDO $pdo,int $eventId): array { return toetapProcessWebhookEvent($pdo,$eventId); } }
