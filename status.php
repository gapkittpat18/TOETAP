<?php
require __DIR__.'/../auth/bootstrap.php';
require __DIR__.'/functions.php';
$userId=requireUser();$error='';$athlete=null;$activities=[];
try{$token=validToken($pdo,$userId);$athlete=stravaHttp('GET','https://www.strava.com/api/v3/athlete',[],$token);$activities=stravaHttp('GET','https://www.strava.com/api/v3/athlete/activities?per_page=10&page=1',[],$token);}
catch(Throwable $e){$error=$e->getMessage();}
?><!doctype html><html><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Strava — TOETAP</title><link rel="stylesheet" href="../assets/v1.css"></head><body><main class="app"><div class="top"><a class="back" href="../index.php">← Home</a><div class="brand">TOETAP<i></i></div></div><div class="kicker">STRAVA</div><h1 class="pageTitle">Connection.</h1>
<?php if($error):?><div class="notice warn"><?=htmlspecialchars($error)?></div><a class="btn green" href="connect.php">CONNECT STRAVA</a>
<?php else:?><div class="v1panel"><b>✓ STRAVA CONNECTED</b><p><?=htmlspecialchars(trim(($athlete['firstname']??'').' '.($athlete['lastname']??'')))?> · Athlete <?=htmlspecialchars((string)($athlete['id']??''))?></p></div>
<div class="section"><h3>Latest activities</h3></div><div class="v1panel"><?php foreach($activities as $a):?><div class="rank"><div><b><?=htmlspecialchars($a['name']??'Activity')?></b><div class="tiny"><?=htmlspecialchars($a['sport_type']??$a['type']??'')?> · <?=number_format(($a['distance']??0)/1000,2)?> km · Gear <?=htmlspecialchars($a['gear_id']??'None')?></div></div></div><?php endforeach;?></div><?php endif;?>
</main></body></html>