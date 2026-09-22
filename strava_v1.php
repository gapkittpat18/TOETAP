<?php
require __DIR__.'/auth/bootstrap.php';require __DIR__.'/v1_nav.php';require_once __DIR__.'/strava/functions.php';
$userId=requireUser();
$q=$pdo->prepare("SELECT * FROM strava_connections WHERE user_id=? LIMIT 1");$q->execute([$userId]);$c=$q->fetch();$ath=null;$err='';
if($c){try{$token=validToken($pdo,$userId);$ath=stravaHttp('GET','https://www.strava.com/api/v3/athlete',[],$token);}catch(Throwable $e){$err=$e->getMessage();}}
?><!doctype html><html><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Strava — TOETAP</title><link rel="stylesheet" href="assets/v1.css"></head><body><main class="app">
<div class="top"><a class="back" href="settings.php">← Settings</a><div class="brand">TOETAP<i></i></div></div><div class="kicker">INTEGRATION</div><h1 class="pageTitle">Strava</h1>
<section class="hero"><div class="stravaMark">STRAVA</div><div style="margin-top:14px"><?php if($c):?><span class="statusGood"><i></i> CONNECTED</span><?php else:?><span class="muted">Not connected</span><?php endif;?></div>
<?php if($ath):?><h2><?=htmlspecialchars(trim(($ath['firstname']??'').' '.($ath['lastname']??'')))?></h2><div class="sub">Athlete <?=htmlspecialchars((string)($ath['id']??$c['athlete_id']))?></div><?php endif;?></section>
<div class="v1panel"><div class="kv"><span>Activity source</span><b>Strava</b></div><div class="kv"><span>Shoe assignment</span><b>Automatic after matching</b></div><div class="kv"><span>Matching</span><b>Latest Tap Wins</b></div></div>
<?php if($err):?><div class="notice warn"><?=htmlspecialchars($err)?></div><?php endif;?>
<a class="btn green" href="strava/connect.php"><?=$c?'REAUTHORIZE STRAVA':'CONNECT STRAVA'?></a>
<?php v1nav('home');?></main></body></html>