<?php
require __DIR__.'/../auth/bootstrap.php';
require __DIR__.'/functions.php';
$userId=requireUser();
require __DIR__.'/time.php';

$token=validToken($pdo,$userId); $message=''; $error='';

if($_SERVER['REQUEST_METHOD']==='POST' && isset($_POST['apply'])){
    requireCsrf();
    $activityId=(string)$_POST['activity_id'];
    $selectionId=(int)$_POST['selection_id'];
    $gearId=trim($_POST['gear_id']);
    try{
        // Validate ownership before any external Strava mutation.
        $sel=$pdo->prepare("SELECT ss.*,us.id AS shoe_id FROM shoe_selections ss JOIN user_shoes us ON us.id=ss.user_shoe_id WHERE ss.id=? AND ss.user_id=? AND us.user_id=?");
        $sel->execute([$selectionId,$userId,$userId]); $s=$sel->fetch();
        if(!$s) throw new Exception('Selection not found.');
        if((string)($s['strava_gear_id']??'')!==$gearId) throw new Exception('Invalid gear for this selection.');
        $result=stravaHttp('PUT','https://www.strava.com/api/v3/activities/'.rawurlencode($activityId),['gear_id'=>$gearId],$token);
        $up=$pdo->prepare("INSERT INTO activities(user_id,user_shoe_id,strava_activity_id,activity_type,distance_m,moving_time_s,start_date,assigned_at,match_status,shoe_selection_id,original_gear_id,proposed_gear_id)
        VALUES(?,?,?,?,?,?, ?,NOW(),'APPLIED',?,?,?)
        ON DUPLICATE KEY UPDATE user_shoe_id=VALUES(user_shoe_id),assigned_at=NOW(),match_status='APPLIED',shoe_selection_id=VALUES(shoe_selection_id),original_gear_id=VALUES(original_gear_id),proposed_gear_id=VALUES(proposed_gear_id)");
        $up->execute([
            $userId,
            $s['user_shoe_id'],
            $activityId,
            $_POST['sport_type'],
            (float)$_POST['distance_m'],
            (int)$_POST['moving_time_s'],
            $_POST['start_date_db'],
            $selectionId,
            $_POST['original_gear_id'] ?: null,
            $gearId
        ]);
        $pdo->prepare("UPDATE shoe_selections SET used=1 WHERE id=? AND user_id=?")->execute([$selectionId,$userId]);
        $message='Applied to Strava successfully.';
    }catch(Throwable $e){$error=$e->getMessage();}
}

$acts=stravaHttp('GET','https://www.strava.com/api/v3/athlete/activities?per_page=50&page=1',[],$token);

// Eligible runs sorted oldest -> newest so a tap can only be consumed by the first run after it.
$runs=[];
foreach($acts as $act){
    $sport=$act['sport_type']??$act['type']??'';
    if(!in_array($sport,['Run','TrailRun','VirtualRun'],true) || empty($act['start_date'])) continue;
    $act['_start_utc']=stravaUtcToDb($act['start_date']);
    $runs[]=$act;
}
usort($runs,fn($x,$y)=>strcmp($x['_start_utc'],$y['_start_utc']));

$selQ=$pdo->prepare("SELECT ss.*,us.nickname,us.custom_brand,us.custom_model,us.strava_gear_id,us.strava_gear_name
  FROM shoe_selections ss JOIN user_shoes us ON us.id=ss.user_shoe_id
  WHERE ss.user_id=? AND ss.used=0 AND us.strava_gear_id IS NOT NULL
  ORDER BY ss.selected_at ASC");
$selQ->execute([$userId]);
$selections=$selQ->fetchAll();

$candidates=[]; $consumed=[];
foreach($selections as $s){
    $tapTs=strtotime($s['selected_at'].' UTC');
    foreach($runs as $act){
        $aid=(string)$act['id'];
        if(isset($consumed[$aid])) continue;
        $runTs=strtotime($act['_start_utc'].' UTC');
        $gap=$runTs-$tapTs;
        if($gap < 0) continue;              // run happened before tap
        if($gap > 7200) break;              // later runs will only be farther away
        // This is the FIRST eligible run after this tap. Consume both sides.
        $candidates[]=['a'=>$act,'s'=>$s,'delta'=>(int)round($gap/60)];
        $consumed[$aid]=true;
        break;
    }
}
?>
<!doctype html><html><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Match Run — TapSole</title>
<style>body{background:#0b0b0d;color:#fff;font-family:Arial;margin:0;padding:24px}.w{max-width:900px;margin:auto}.card{background:#17171b;border:1px solid #29292f;border-radius:20px;padding:22px;margin:14px 0}.muted{color:#999}.ok{color:#9effb2}.err{color:#ff9da6}button{background:#fff;color:#000;border:0;border-radius:12px;padding:14px 18px;font-weight:bold}a{color:#fff}.arrow{font-size:24px;margin:10px 0}</style></head>
<body><div class="w"><h1>Safe Run Match</h1><p class="muted">Preview only until you press APPLY.</p>
<?php if($message):?><p class="ok">✓ <?=htmlspecialchars($message)?></p><?php endif;?><?php if($error):?><p class="err"><?=htmlspecialchars($error)?></p><?php endif;?>
<?php if(!$candidates):?><div class="card">No unambiguous run found within 2 hours after an unused TapSole selection.</div><?php endif;?>
<?php foreach($candidates as $c): $a=$c['a'];$s=$c['s'];?>
<div class="card"><h2><?=htmlspecialchars($a['name']??'Run')?> · <?=number_format(($a['distance']??0)/1000,2)?> km</h2>
<p class="muted">Run start: <?=htmlspecialchars($a['start_date_local']??$a['start_date'])?><br>Tap: <?=htmlspecialchars($s['selected_at'])?> · <?=$c['delta']?> min before run<br>Current Strava gear: <?=htmlspecialchars($a['gear_id']??'None')?></p>
<div class="arrow">↓</div><strong>Proposed: <?=htmlspecialchars($s['nickname'] ?: trim($s['custom_brand'].' '.$s['custom_model']))?></strong><br><span class="muted"><?=htmlspecialchars($s['strava_gear_name'])?> · <?=htmlspecialchars($s['strava_gear_id'])?></span>
<form method="post" style="margin-top:18px"><?=csrfField()?>
<input type="hidden" name="activity_id" value="<?=htmlspecialchars((string)$a['id'])?>">
<input type="hidden" name="selection_id" value="<?=$s['id']?>"><input type="hidden" name="gear_id" value="<?=htmlspecialchars($s['strava_gear_id'])?>">
<input type="hidden" name="original_gear_id" value="<?=htmlspecialchars($a['gear_id']??'')?>"><input type="hidden" name="sport_type" value="<?=htmlspecialchars($a['sport_type']??$a['type']??'Run')?>">
<input type="hidden" name="distance_m" value="<?=htmlspecialchars((string)($a['distance']??0))?>"><input type="hidden" name="moving_time_s" value="<?=htmlspecialchars((string)($a['moving_time']??0))?>">
<input type="hidden" name="start_date_db" value="<?=stravaUtcToDb($a['start_date'])?>"><button name="apply" value="1">APPLY TO STRAVA</button></form></div>
<?php endforeach;?><p><a href="gear_link.php">Link Gear</a> · <a href="status.php">Strava</a> · <a href="../index.php">Home</a></p></div></body></html>
