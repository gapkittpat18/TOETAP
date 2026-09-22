<?php
require __DIR__.'/auth/bootstrap.php';require __DIR__.'/strava/functions.php';require __DIR__.'/v1_nav.php';
$userId=requireUser();
// First-use product onboarding. Strava is recommended, never required.
if(empty($_COOKIE['toetap_onboarded'])){
 try{
  $oc=$pdo->prepare("SELECT COUNT(*) FROM strava_connections WHERE user_id=?");
  $oc->execute([$userId]);
  if((int)$oc->fetchColumn()===0){header('Location:onboarding.php');exit;}
  setcookie('toetap_onboarded','1',time()+31536000,'/');
 }catch(Throwable $e){}
}

$q=$pdo->prepare("SELECT ss.selected_at,us.* FROM shoe_selections ss JOIN user_shoes us ON us.id=ss.user_shoe_id WHERE ss.user_id=? AND us.active=1 ORDER BY ss.selected_at DESC,ss.id DESC LIMIT 1");$q->execute([$userId]);$shoe=$q->fetch();
$recent=[];$w7=0;$d30=0;$n30=0;$gearMap=[];$token=null;$dataSource='MANUAL';
try{
 $token=validToken($pdo,$userId);
 $dataSource='STRAVA';
 $gm=$pdo->prepare("SELECT strava_gear_id,nickname,custom_brand,custom_model FROM user_shoes WHERE user_id=? AND active=1");
 $gm->execute([$userId]);
 foreach($gm->fetchAll() as $g) if($g['strava_gear_id']) $gearMap[$g['strava_gear_id']]=$g['nickname']?:trim($g['custom_brand'].' '.$g['custom_model']);
 $acts=stravaHttp('GET','https://www.strava.com/api/v3/athlete/activities?per_page=100&page=1',[],$token);
 $now=new DateTimeImmutable('now',new DateTimeZone('UTC'));
 foreach($acts as $a){
  $type=$a['sport_type']??($a['type']??'');if(!in_array($type,['Run','TrailRun','VirtualRun'],true))continue;
  $dt=new DateTimeImmutable($a['start_date']);$kmA=(float)$a['distance']/1000;
  if($dt>=$now->modify('-7 days'))$w7+=$kmA;
  if($dt>=$now->modify('-30 days')){$d30+=$kmA;$n30++;}
  if(count($recent)<3){$gid=$a['gear_id']??'';$recent[]=['name'=>$a['name']??'Run','km'=>$kmA,'date'=>substr($a['start_date_local']??$a['start_date'],0,10),'shoe'=>$gearMap[$gid]??'Unassigned shoe'];}
 }
}catch(Throwable $e){
 // No usable Strava connection: TOETAP remains fully usable with local/manual runs.
 $dataSource='MANUAL';$token=null;
 $now=new DateTimeImmutable('now',new DateTimeZone('UTC'));
 $l=$pdo->prepare("SELECT a.*,COALESCE(NULLIF(us.nickname,''),CONCAT_WS(' ',us.custom_brand,us.custom_model),'Unassigned shoe') shoe_name
 FROM activities a LEFT JOIN user_shoes us ON us.id=a.user_shoe_id
 WHERE a.user_id=? AND (a.source='MANUAL' OR a.source IS NULL) AND a.activity_type IN ('Run','TrailRun','VirtualRun')
 ORDER BY a.start_date DESC LIMIT 100");
 $l->execute([$userId]);
 foreach($l->fetchAll() as $a){
  $dt=new DateTimeImmutable($a['start_date'],new DateTimeZone('UTC'));$kmA=(float)$a['distance_m']/1000;
  if($dt>=$now->modify('-7 days'))$w7+=$kmA;
  if($dt>=$now->modify('-30 days')){$d30+=$kmA;$n30++;}
  if(count($recent)<3)$recent[]=['name'=>'Run','km'=>$kmA,'date'=>substr($a['start_date'],0,10),'shoe'=>$a['shoe_name']];
 }
}
$km=$shoe?(float)$shoe['initial_km']:0;
if($shoe){
 if($dataSource==='STRAVA' && $shoe['strava_gear_id'] && $token){
  try{$g=stravaHttp('GET','https://www.strava.com/api/v3/gear/'.rawurlencode($shoe['strava_gear_id']),[],$token);$km=(float)$g['distance']/1000;}catch(Throwable $e){}
 }elseif($dataSource==='MANUAL'){
  $mq=$pdo->prepare("SELECT COALESCE(SUM(distance_m),0) FROM activities WHERE user_id=? AND user_shoe_id=? AND (source='MANUAL' OR source IS NULL)");
  $mq->execute([$userId,$shoe['id']]);$km+=(float)$mq->fetchColumn()/1000;
 }
}
$pct=$shoe&&$shoe['target_km']?min(100,$km/(float)$shoe['target_km']*100):0;
?><!doctype html><html><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>TOETAP V1</title><link rel="stylesheet" href="assets/v1.css">
<style>
/* V2.2.27 custom shoe presentation */
.hero .shoe.custom-photo-hero{position:relative;isolation:isolate;min-height:235px;display:flex;align-items:center;justify-content:center}
.hero .shoe.custom-photo-hero::before{
 content:"";position:absolute;z-index:-1;left:50%;top:50%;transform:translate(-50%,-50%);
 width:min(86vw,430px);height:185px;border-radius:50%;
 background:radial-gradient(ellipse at center,rgba(255,255,255,.20) 0%,rgba(255,255,255,.105) 35%,rgba(255,255,255,0) 72%);
 filter:blur(7px);pointer-events:none
}
.hero .shoe.custom-photo-hero .shoeShape{width:100%!important;max-width:440px!important;height:220px!important;display:flex!important;align-items:center!important;justify-content:center!important;filter:none!important}
.hero .shoe.custom-photo-hero img.custom-shoe-photo{width:94%!important;max-width:430px!important;height:215px!important;object-fit:contain!important;filter:none!important;-webkit-filter:none!important;opacity:1!important;mix-blend-mode:normal!important;transform:none!important}
@media(max-width:380px){
 .hero .shoe.custom-photo-hero{min-height:210px}
 .hero .shoe.custom-photo-hero .shoeShape{height:200px!important}
 .hero .shoe.custom-photo-hero img.custom-shoe-photo{height:195px!important}
}
</style></head><body><main class="app">
<div class="top"><div class="brand">TOETAP<i></i></div><a class="avatar" href="settings.php">G</a></div>
<div class="kicker">YOUR NEXT RUN</div><h1 class="headline">Tap. Run.<br>Done.</h1>
<?php if($shoe):$nm=$shoe['nickname']?:trim($shoe['custom_brand'].' '.$shoe['custom_model']);?>
<section class="hero"><div class="ready"><span class="dot"></span> READY TO RUN</div><div class="shoe<?=!empty($shoe['custom_photo_path'])?' custom-photo-hero':''?>"><div class="shoeShape"><img class="shoe-outline<?=!empty($shoe['custom_photo_path'])?' custom-shoe-photo':''?>" src="<?=htmlspecialchars(!empty($shoe['custom_photo_path'])?$shoe['custom_photo_path']:'assets/sneaker.png')?>" alt="Sneaker"<?=!empty($shoe['custom_photo_path'])?' style="filter:none!important;-webkit-filter:none!important;opacity:1!important;mix-blend-mode:normal!important"':''?>></div></div><div class="kicker" style="color:#8d918d"><?=htmlspecialchars($shoe['custom_brand'])?></div><h2><?=htmlspecialchars($nm)?></h2><div class="sub"><?=htmlspecialchars($shoe['custom_model'])?></div><div class="meterTop"><span><?=number_format($km,1)?> km</span><span><?=number_format((float)$shoe['target_km'],0)?> km target</span></div><div class="meter"><b style="width:<?=$pct?>%"></b></div><div class="taprow"><span>Latest NFC tap</span><b><span class="local-time" data-utc="<?=htmlspecialchars(str_replace(' ','T',$shoe['selected_at']).'Z')?>"><?=htmlspecialchars($shoe['selected_at'])?></span></b></div></section>
<?php else:?><section class="hero"><div class="shoe"><div class="shoeShape">◇</div></div><h2>Tap your shoe</h2><div class="sub">Your latest NFC tap selects the shoe for your next run.</div></section><?php endif;?>
<div class="tiny" style="margin:2px 2px 10px">DATA SOURCE · <?=htmlspecialchars($dataSource==='STRAVA'?'STRAVA':'TOETAP')?></div><div class="stats"><div class="stat"><small>7 DAYS</small><b><?=number_format($w7,1)?> km</b></div><div class="stat"><small>30 DAYS</small><b><?=number_format($d30,1)?> km</b></div><div class="stat"><small>RUNS</small><b><?=$n30?></b></div></div>
<div class="section"><h3>Recent runs</h3><a href="analytics.php">See all</a></div><div class="card"><?php foreach($recent as $r):?><div class="run"><div class="runIcon">↗</div><div><b><?=htmlspecialchars($r['name'])?></b><span><?=htmlspecialchars($r['shoe'])?> · <?=$r['date']?></span></div><div class="distance"><?=number_format($r['km'],2)?><small>KM</small></div></div><?php endforeach;if(!$recent):?><div class="empty">No recent runs</div><?php endif;?></div>
<a class="insight" href="analytics.php"><div><div class="kicker">TOETAP INSIGHTS</div><h3>Understand your running.</h3><span style="font-size:12px">Volume, rotation and shoe-level trends.</span></div><div class="arrow">→</div></a>
<a class="fab" href="tag_new.php">＋</a><a class="btn secondary" href="add_run.php">+ ADD RUN MANUALLY</a><?php v1nav('home');?></main><script>
document.querySelectorAll('.local-time[data-utc]').forEach(function(el){
 const d=new Date(el.dataset.utc);
 if(!isNaN(d)){
  el.textContent=new Intl.DateTimeFormat(undefined,{month:'short',day:'numeric',hour:'2-digit',minute:'2-digit'}).format(d);
  el.title=Intl.DateTimeFormat().resolvedOptions().timeZone;
 }
});
</script><script>
document.addEventListener('DOMContentLoaded',function(){
  document.querySelectorAll('.shoeShape img.custom-shoe-photo').forEach(function(img){
    const wrap=img.closest('.shoeShape');
    if(wrap){wrap.style.setProperty('filter','none','important');}
    img.style.setProperty('filter','none','important');
    img.style.setProperty('-webkit-filter','none','important');
  });
});
</script>
</body></html>