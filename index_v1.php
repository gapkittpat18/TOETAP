<?php
require __DIR__.'/config/database.php';require __DIR__.'/strava/functions.php';require __DIR__.'/v1_nav.php';
$userId=(int)$pdo->query("SELECT id FROM users ORDER BY id LIMIT 1")->fetchColumn();
$q=$pdo->prepare("SELECT ss.selected_at,us.* FROM shoe_selections ss JOIN user_shoes us ON us.id=ss.user_shoe_id WHERE ss.user_id=? AND us.active=1 ORDER BY ss.selected_at DESC,ss.id DESC LIMIT 1");$q->execute([$userId]);$shoe=$q->fetch();
$recent=[];$w7=0;$d30=0;$n30=0;$gearMap=[];$token=null;
try{$token=validToken($pdo,$userId);$gm=$pdo->prepare("SELECT strava_gear_id,nickname,custom_brand,custom_model FROM user_shoes WHERE user_id=? AND active=1");$gm->execute([$userId]);foreach($gm->fetchAll() as $g)if($g['strava_gear_id'])$gearMap[$g['strava_gear_id']]=$g['nickname']?:trim($g['custom_brand'].' '.$g['custom_model']);
$acts=stravaHttp('GET','https://www.strava.com/api/v3/athlete/activities?per_page=100&page=1',[],$token);$now=new DateTimeImmutable('now',new DateTimeZone('UTC'));
foreach($acts as $a){$type=$a['sport_type']??($a['type']??'');if(!in_array($type,['Run','TrailRun','VirtualRun'],true))continue;$dt=new DateTimeImmutable($a['start_date']);$km=(float)$a['distance']/1000;if($dt>=$now->modify('-7 days'))$w7+=$km;if($dt>=$now->modify('-30 days')){$d30+=$km;$n30++;}if(count($recent)<3){$gid=$a['gear_id']??'';$recent[]=['name'=>$a['name']??'Run','km'=>$km,'date'=>substr($a['start_date_local']??$a['start_date'],0,10),'shoe'=>$gearMap[$gid]??'Unassigned shoe'];}}}catch(Throwable $e){}
$km=$shoe?(float)$shoe['initial_km']:0;if($shoe&&$shoe['strava_gear_id']&&$token){try{$g=stravaHttp('GET','https://www.strava.com/api/v3/gear/'.rawurlencode($shoe['strava_gear_id']),[],$token);$km=(float)$g['distance']/1000;}catch(Throwable $e){}}
$pct=$shoe&&$shoe['target_km']?min(100,$km/(float)$shoe['target_km']*100):0;
?><!doctype html><html><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>TapSole V1</title><link rel="stylesheet" href="assets/v1.css"></head><body><main class="app">
<div class="top"><div class="brand">TAPSOLE<i></i></div><a class="avatar" href="settings.php">G</a></div>
<div class="kicker">YOUR NEXT RUN</div><h1 class="headline">Tap. Run.<br>Done.</h1>
<?php if($shoe):$nm=$shoe['nickname']?:trim($shoe['custom_brand'].' '.$shoe['custom_model']);?>
<section class="hero"><div class="ready"><span class="dot"></span> READY TO RUN</div><div class="shoe"><div class="shoeShape"><img class="shoe-outline" src="assets/sneaker.png" alt="Sneaker"></div></div><div class="kicker" style="color:#8d918d"><?=htmlspecialchars($shoe['custom_brand'])?></div><h2><?=htmlspecialchars($nm)?></h2><div class="sub"><?=htmlspecialchars($shoe['custom_model'])?></div><div class="meterTop"><span><?=number_format($km,1)?> km</span><span><?=number_format((float)$shoe['target_km'],0)?> km target</span></div><div class="meter"><b style="width:<?=$pct?>%"></b></div><div class="taprow"><span>Latest NFC tap</span><b><span class="local-time" data-utc="<?=htmlspecialchars(str_replace(' ','T',$shoe['selected_at']).'Z')?>"><?=htmlspecialchars($shoe['selected_at'])?></span></b></div></section>
<?php else:?><section class="hero"><div class="shoe"><div class="shoeShape">◇</div></div><h2>Tap your shoe</h2><div class="sub">Your latest NFC tap selects the shoe for your next run.</div></section><?php endif;?>
<div class="stats"><div class="stat"><small>7 DAYS</small><b><?=number_format($w7,1)?> km</b></div><div class="stat"><small>30 DAYS</small><b><?=number_format($d30,1)?> km</b></div><div class="stat"><small>RUNS</small><b><?=$n30?></b></div></div>
<div class="section"><h3>Recent runs</h3><a href="analytics.php">See all</a></div><div class="card"><?php foreach($recent as $r):?><div class="run"><div class="runIcon">↗</div><div><b><?=htmlspecialchars($r['name'])?></b><span><?=htmlspecialchars($r['shoe'])?> · <?=$r['date']?></span></div><div class="distance"><?=number_format($r['km'],2)?><small>KM</small></div></div><?php endforeach;if(!$recent):?><div class="empty">No recent runs</div><?php endif;?></div>
<a class="insight" href="analytics.php"><div><div class="kicker">TAPSOLE INSIGHTS</div><h3>Understand your running.</h3><span style="font-size:12px">Volume, rotation and shoe-level trends.</span></div><div class="arrow">→</div></a>
<a class="fab" href="tag_new.php">＋</a><?php v1nav('home');?></main><script>
document.querySelectorAll('.local-time[data-utc]').forEach(function(el){
 const d=new Date(el.dataset.utc);
 if(!isNaN(d)){
  el.textContent=new Intl.DateTimeFormat(undefined,{month:'short',day:'numeric',hour:'2-digit',minute:'2-digit'}).format(d);
  el.title=Intl.DateTimeFormat().resolvedOptions().timeZone;
 }
});
</script></body></html>