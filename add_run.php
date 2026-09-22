<?php
require __DIR__.'/auth/bootstrap.php';
require __DIR__.'/v1_nav.php';
$userId=requireUser();
$ok='';$err='';

$q=$pdo->prepare("SELECT us.id,COALESCE(NULLIF(us.nickname,''),CONCAT_WS(' ',us.custom_brand,us.custom_model)) name
FROM user_shoes us WHERE us.user_id=? AND us.active=1 ORDER BY us.id DESC");
$q->execute([$userId]);$shoes=$q->fetchAll();

$latest=$pdo->prepare("SELECT user_shoe_id FROM shoe_selections WHERE user_id=? ORDER BY selected_at DESC,id DESC LIMIT 1");
$latest->execute([$userId]);$defaultShoe=(int)($latest->fetchColumn()?:0);

if($_SERVER['REQUEST_METHOD']==='POST'){
 try{
  requireCsrf();
  $shoe=(int)($_POST['shoe_id']??0);
  $km=(float)($_POST['distance_km']??0);
  $mins=(int)($_POST['duration_min']??0);
  $avgHr=(float)($_POST['avg_hr']??0);
  $local=trim($_POST['start_local']??'');
  if(!$shoe||$km<=0||$local==='') throw new Exception('Please enter shoe, distance and run time.');
  $own=$pdo->prepare("SELECT COUNT(*) FROM user_shoes WHERE id=? AND user_id=? AND active=1");$own->execute([$shoe,$userId]);
  if(!$own->fetchColumn()) throw new Exception('Invalid shoe.');
  // Browser sends local wall time + timezone offset. Store UTC like the Strava pipeline.
  $offset=(int)($_POST['tz_offset']??0); // JS getTimezoneOffset(): UTC - local, minutes
  $dt=new DateTimeImmutable($local,new DateTimeZone('UTC'));
  $utc=$dt->modify(($offset>=0?'+':'').$offset.' minutes')->format('Y-m-d H:i:s');
  $ins=$pdo->prepare("INSERT INTO activities
   (user_id,user_shoe_id,source,external_activity_id,strava_activity_id,activity_type,distance_m,moving_time_s,start_date,assigned_at)
   VALUES(?,?,'MANUAL',NULL,NULL,'Run',?,?,?,UTC_TIMESTAMP())");
  $ins->execute([$userId,$shoe,$km*1000,max(0,$mins*60),$utc]);
  $activityId=(int)$pdo->lastInsertId();
  if($avgHr>0){
    $m=$pdo->prepare("INSERT INTO activity_metrics(activity_id,avg_hr) VALUES(?,?)");
    $m->execute([$activityId,$avgHr]);
  }
  $ok='Run added.';
 }catch(Throwable $e){$err=$e->getMessage();}
}
$now=date('Y-m-d\TH:i');
?><!doctype html><html><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Add Run — TOETAP</title><link rel="stylesheet" href="assets/v1.css"></head><body><main class="app">
<div class="top"><a class="back" href="index.php">← Home</a><div class="brand">TOETAP<i></i></div></div>
<div class="kicker">NO STRAVA NEEDED</div><h1 class="pageTitle">Add run</h1><p class="muted">Log a run directly in TOETAP. Your latest tapped shoe is preselected when available.</p>
<?php if($ok):?><div class="notice"><?=$ok?></div><?php endif;?><?php if($err):?><div class="notice warn"><?=htmlspecialchars($err)?></div><?php endif;?>
<form method="post" class="v1form" id="runForm"><?=csrfField()?>
<label>SHOE</label><select name="shoe_id" required><option value="">Choose shoe</option><?php foreach($shoes as $s):?><option value="<?=$s['id']?>" <?=$defaultShoe===$s['id']?'selected':''?>><?=htmlspecialchars($s['name'])?></option><?php endforeach;?></select>
<div class="formrow"><div><label>DISTANCE (KM)</label><input type="number" step="0.01" min="0.01" name="distance_km" required placeholder="5.00"></div><div><label>DURATION (MIN)</label><input type="number" min="0" name="duration_min" placeholder="30"></div></div>
<label>AVERAGE HR <span class="tiny">OPTIONAL</span></label><input type="number" min="1" max="250" name="avg_hr" placeholder="155">
<label>START TIME</label><input type="datetime-local" name="start_local" id="startLocal" required>
<input type="hidden" name="tz_offset" id="tzOffset">
<button class="btn green" type="submit">SAVE RUN</button></form>
<?php v1nav('home');?></main>
<script>
const n=new Date(), pad=x=>String(x).padStart(2,'0');
document.getElementById('startLocal').value=`${n.getFullYear()}-${pad(n.getMonth()+1)}-${pad(n.getDate())}T${pad(n.getHours())}:${pad(n.getMinutes())}`;
document.getElementById('tzOffset').value=n.getTimezoneOffset();
</script></body></html>