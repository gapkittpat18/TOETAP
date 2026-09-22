<?php
require __DIR__.'/auth/bootstrap.php';require __DIR__.'/v1_nav.php';require_once __DIR__.'/strava/functions.php';
$userId=requireUser();
$period=strtoupper((string)($_GET['period']??'1Y'));
$allowedPeriods=['3M','6M','1Y','ALL'];
if(!in_array($period,$allowedPeriods,true))$period='1Y';
$periodMonths=['3M'=>3,'6M'=>6,'1Y'=>12];

function paceFmt($s){if(!$s)return '—';$m=floor($s/60);$sec=(int)round($s-$m*60);if($sec===60){$m++;$sec=0;}return $m.':'.str_pad((string)$sec,2,'0',STR_PAD_LEFT);}
function timeFmt($s){if(!$s)return '—';$s=(int)round($s);$h=intdiv($s,3600);$m=intdiv($s%3600,60);$x=$s%60;return ($h?$h.':':'').str_pad((string)$m,2,'0',STR_PAD_LEFT).':'.str_pad((string)$x,2,'0',STR_PAD_LEFT);}
function distanceBucket(float $km): ?string {
 if($km>=4.75 && $km<=5.25)return '5K';
 if($km>=9.50 && $km<=10.50)return '10K';
 if($km>=20.0 && $km<=22.2)return '21K';
 if($km>=40.0 && $km<=44.5)return '42K';
 return null;
}
function efficiency(float $pace,float $hr): ?float {
 if($pace<=0||$hr<=0)return null;
 $speedMMin=1000/$pace;
 return $speedMMin/$hr; // metres/minute per bpm; higher = more speed for each heartbeat
}

function prDistanceLabel(float $m): ?string {
 if($m>=4950 && $m<=5050)return '5K';
 if($m>=9950 && $m<=10050)return '10K';
 if($m>=21000 && $m<=21200)return '21K';
 if($m>=42100 && $m<=42300)return '42K';
 return null;
}

$shoeRows=[];$q=$pdo->prepare("SELECT id,strava_gear_id,nickname,custom_brand,custom_model,initial_km,target_km,purchase_price,purchase_currency FROM user_shoes WHERE user_id=?");
$q->execute([$userId]);
foreach($q->fetchAll() as $s){
 $s['display_name']=$s['nickname']?:trim($s['custom_brand'].' '.$s['custom_model']);
 $shoeRows[(int)$s['id']]=$s;
}
$gearMap=[];foreach($shoeRows as $s)if($s['strava_gear_id'])$gearMap[$s['strava_gear_id']]=(int)$s['id'];

$runs=[];$dataSource='STRAVA';$sourceNote='';$stravaPr=['5K'=>null,'10K'=>null,'21K'=>null,'42K'=>null];$prSyncNote='';
try{
 $token=validToken($pdo,$userId);
 $periodCutoff=null;
 if(isset($periodMonths[$period]))$periodCutoff=(new DateTimeImmutable('now',new DateTimeZone('UTC')))->modify('-'.$periodMonths[$period].' months');
 // Fetch enough history for the selected period. ALL is capped at 2,000 activities to protect API quota.
 for($page=1;$page<=10;$page++){
  $acts=stravaHttp('GET','https://www.strava.com/api/v3/athlete/activities?per_page=200&page='.$page,[],$token);
  $pageReachedCutoff=false;
  foreach($acts as $a){
   $rawDate=$a['start_date']??'';
   if($periodCutoff && $rawDate){
    try{$activityDt=new DateTimeImmutable($rawDate);if($activityDt<$periodCutoff){$pageReachedCutoff=true;continue;}}catch(Throwable $e){}
   }
   $type=$a['sport_type']??($a['type']??'');if(!in_array($type,['Run','TrailRun','VirtualRun'],true))continue;
   $gid=(string)($a['gear_id']??'');$sid=$gearMap[$gid]??0;$dist=(float)($a['distance']??0);$time=(float)($a['moving_time']??0);
   $runs[]=['id'=>(string)($a['id']??''),'date'=>$a['start_date']??'','km'=>$dist/1000,'sec'=>$time,'pace'=>$dist>0?$time/($dist/1000):0,
     'hr'=>(float)($a['average_heartrate']??0),'shoe_id'=>$sid,'shoe'=>$sid?($shoeRows[$sid]['display_name']):($gid?'Unlinked shoe':'No shoe'),'gear'=>$gid];
  }
  if(count($acts)<200||$pageReachedCutoff)break;
 }

 // Exact-distance PRs come from Strava DetailedActivity.best_efforts, not whole-activity bucket times.
 // To protect API quota, cache the result in the PHP session for 6 hours.
 $cacheKey='toetap_strava_pr_v208_'.$userId;
 $cached=$_SESSION[$cacheKey]??null;
 if(is_array($cached) && isset($cached['at'],$cached['prs']) && (time()-(int)$cached['at'])<21600){
  $stravaPr=$cached['prs'];$prSyncNote='Strava PR cache · refreshed within 6h';
 }else{
  $prCandidates=[];
  // Scan summary history (max 2,000). Only activities that Strava marks with PR achievements need a detail request.
  for($pp=1;$pp<=10;$pp++){
   $pageActs=stravaHttp('GET','https://www.strava.com/api/v3/athlete/activities?per_page=200&page='.$pp,[],$token);
   foreach($pageActs as $pa){
    $pt=$pa['sport_type']??($pa['type']??'');
    if(in_array($pt,['Run','TrailRun','VirtualRun'],true) && (int)($pa['pr_count']??0)>0 && !empty($pa['id']))$prCandidates[(string)$pa['id']]=true;
   }
   if(count($pageActs)<200)break;
  }
  // Safety cap: avoid exhausting the read-rate limit on one page load.
  $candidateIds=array_slice(array_keys($prCandidates),0,70);
  foreach($candidateIds as $aid){
   try{
    $detail=stravaHttp('GET','https://www.strava.com/api/v3/activities/'.rawurlencode($aid),[],$token);
    foreach(($detail['best_efforts']??[]) as $eff){
     $lab=prDistanceLabel((float)($eff['distance']??0));$sec=(int)($eff['elapsed_time']??$eff['moving_time']??0);
     if(!$lab||$sec<=0)continue;
     if($stravaPr[$lab]===null || $sec<$stravaPr[$lab]['sec']){
      $prGear=(string)($detail['gear_id']??'');$prSid=$gearMap[$prGear]??0;
      $stravaPr[$lab]=[
       'sec'=>$sec,'activity_id'=>$aid,'date'=>$eff['start_date']??($detail['start_date']??''),
       'distance_m'=>(float)($eff['distance']??0),'gear_id'=>$prGear,'shoe_id'=>$prSid,
       'shoe'=>$prSid?($shoeRows[$prSid]['display_name']):($prGear?'Unlinked Strava shoe':'Not recorded')
      ];
     }
    }
   }catch(Throwable $ignore){}
  }
  $_SESSION[$cacheKey]=['at'=>time(),'prs'=>$stravaPr];
  $prSyncNote=count($prCandidates)>70?'Strava PR data · safety-capped this sync':'Strava Best Efforts · all-time scan';
 }

 $sourceNote='Live Strava data · Performance period '.$period;
}catch(Throwable $e){
 $dataSource='TOETAP';$sourceNote='Local/manual TOETAP history';
 $q=$pdo->prepare("SELECT a.*,am.avg_hr,COALESCE(NULLIF(us.nickname,''),CONCAT_WS(' ',us.custom_brand,us.custom_model),'No shoe') shoe_name
 FROM activities a LEFT JOIN user_shoes us ON us.id=a.user_shoe_id LEFT JOIN activity_metrics am ON am.activity_id=a.id
 WHERE a.user_id=? AND (a.source='MANUAL' OR a.source IS NULL) AND a.activity_type IN ('Run','TrailRun','VirtualRun')
 ".(isset($periodMonths[$period])?"AND a.start_date >= DATE_SUB(UTC_TIMESTAMP(), INTERVAL ".(int)$periodMonths[$period]." MONTH) ":"")."
 ORDER BY a.start_date DESC LIMIT 2000");
 $q->execute([$userId]);
 foreach($q->fetchAll() as $a){$dist=(float)$a['distance_m'];$time=(float)$a['moving_time_s'];
  $runs[]=['id'=>'local:'.$a['id'],'date'=>$a['start_date'],'km'=>$dist/1000,'sec'=>$time,'pace'=>$dist>0?$time/($dist/1000):0,
   'hr'=>(float)($a['avg_hr']??0),'shoe_id'=>(int)($a['user_shoe_id']??0),'shoe'=>$a['shoe_name'],'gear'=>''];
 }
}

usort($runs,fn($a,$b)=>strcmp($b['date'],$a['date']));
$now=new DateTimeImmutable('now',new DateTimeZone('UTC'));$d30=$now->modify('-30 days');$d60=$now->modify('-60 days');
$km30=$kmPrev=0;$cnt30=0;$paceSum=0;$paceN=0;$weeks=array_fill(0,8,0.0);$shoeStats=[];$distStats=[];
foreach($runs as $r){
 try{$dt=new DateTimeImmutable($r['date']?:'now');}catch(Throwable $e){$dt=$now;}
 if($dt>=$d30){$km30+=$r['km'];$cnt30++;if($r['pace']>0){$paceSum+=$r['pace'];$paceN++;}}
 elseif($dt>=$d60)$kmPrev+=$r['km'];
 if($dt>=$now->modify('-56 days')){$days=(int)$dt->diff($now)->format('%a');$idx=7-min(7,intdiv($days,7));$weeks[$idx]+=$r['km'];}
 if($r['shoe_id']){
  $sid=$r['shoe_id'];if(!isset($shoeStats[$sid]))$shoeStats[$sid]=['name'=>$r['shoe'],'km'=>0,'runs'=>0,'paceSum'=>0,'paceN'=>0];
  $shoeStats[$sid]['km']+=$r['km'];$shoeStats[$sid]['runs']++;if($r['pace']>0){$shoeStats[$sid]['paceSum']+=$r['pace'];$shoeStats[$sid]['paceN']++;}
 }
 $b=distanceBucket($r['km']);
 if($b){
  if(!isset($distStats[$b]))$distStats[$b]=['runs'=>0,'bestSec'=>null,'bestPace'=>null,'bestKm'=>null,'paceSum'=>0,'paceN'=>0,'hrSum'=>0,'hrN'=>0,'effSum'=>0,'effN'=>0,'bestShoe'=>'—'];
  $d=&$distStats[$b];$d['runs']++;if($r['pace']>0){$d['paceSum']+=$r['pace'];$d['paceN']++;}
  if($r['hr']>0){$d['hrSum']+=$r['hr'];$d['hrN']++;$ef=efficiency($r['pace'],$r['hr']);if($ef){$d['effSum']+=$ef;$d['effN']++;}}
  if($r['sec']>0 && ($d['bestSec']===null||$r['sec']<$d['bestSec'])){$d['bestSec']=$r['sec'];$d['bestPace']=$r['pace'];$d['bestKm']=$r['km'];$d['bestShoe']=$r['shoe'];}
  unset($d);
 }
}
$avgPace=$paceN?$paceSum/$paceN:0;$change=$kmPrev>0?(($km30-$kmPrev)/$kmPrev*100):null;$maxWeek=max(1,max($weeks));
uasort($shoeStats,fn($a,$b)=>$b['km']<=>$a['km']);

// Shoe aging analysis: reconstruct loaded-history mileage chronologically per shoe.
$aging=[];
$chron=array_reverse($runs);$cum=[];
foreach($chron as $r){
 $sid=$r['shoe_id'];if(!$sid||!isset($shoeRows[$sid]))continue;
 if(!isset($cum[$sid]))$cum[$sid]=(float)$shoeRows[$sid]['initial_km'];
 $mid=$cum[$sid]+$r['km']/2;$cum[$sid]+=$r['km'];
 if($mid<100)$bucket='0–100';elseif($mid<250)$bucket='100–250';elseif($mid<400)$bucket='250–400';else $bucket='400+';
 if(!isset($aging[$sid][$bucket]))$aging[$sid][$bucket]=['paceSum'=>0,'paceN'=>0,'effSum'=>0,'effN'=>0,'runs'=>0];
 $x=&$aging[$sid][$bucket];$x['runs']++;if($r['pace']>0){$x['paceSum']+=$r['pace'];$x['paceN']++;}$ef=efficiency($r['pace'],$r['hr']);if($ef){$x['effSum']+=$ef;$x['effN']++;}unset($x);
}
?>
<!doctype html><html><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Insights — TOETAP</title><link rel="stylesheet" href="assets/v1.css"><style>
.periodTabs{display:grid;grid-template-columns:repeat(4,1fr);gap:7px;margin:8px 0 6px}.periodTabs a{text-decoration:none;text-align:center;background:#ecece6;color:#111;border-radius:999px;padding:9px 4px;font-size:11px;font-weight:900}.periodTabs a.active{background:#111;color:#c9ff54}.periodNote{margin:0 0 12px;color:#777}
.distanceTabs{display:grid;grid-template-columns:repeat(4,1fr);gap:7px;margin:10px 0 12px}
.distanceTab{appearance:none;border:0;background:#ecece6;border-radius:16px;padding:11px 5px;color:#111;text-align:left;min-width:0}
.distanceTab span,.distanceTab b,.distanceTab small{display:block}.distanceTab span{font-size:11px;font-weight:900}.distanceTab b{font-size:15px;margin-top:8px;white-space:nowrap}.distanceTab small{font-size:9px;color:#777;margin-top:2px}
.distanceTab.active{background:#111;color:#fff}.distanceTab.active span{color:#c9ff54}.distanceTab.active small{color:#aaa}
.distancePanel{display:none;background:#111;color:#fff;border-radius:24px;padding:18px;margin-bottom:25px}.distancePanel.active{display:block}
.prHero{background:#c9ff54;color:#111;border-radius:18px;padding:15px;margin-bottom:9px}.prHero small,.prHero strong,.prHero span{display:block}.prHero small{font-size:9px;font-weight:900;letter-spacing:.08em}.prHero strong{font-size:34px;line-height:1;margin:6px 0 4px}.prHero span{font-size:10px;color:#4b4b45}
.distanceHero{display:grid;grid-template-columns:1.15fr 1fr;gap:9px}.distanceHero>div{background:#20221f;border-radius:16px;padding:13px}
.distanceHero small{display:block;color:#999;font-size:9px;letter-spacing:.08em;margin-bottom:5px}.distanceHero strong{font-size:26px;line-height:1}.distanceHero em{font-style:normal;font-size:11px;color:#aaa}
.distanceSummary{display:flex;gap:14px;margin:13px 2px 0;color:#aaa;font-size:10px;font-weight:800}
.detailsToggle{width:100%;margin-top:13px;border:0;border-top:1px solid #30322f;background:transparent;color:#c9ff54;padding:13px 0 0;display:flex;justify-content:space-between;font-size:10px;font-weight:900}
.distanceDetails{display:none;padding-top:8px}.distanceDetails.open{display:block}.distancePanel .kv{border-color:#30322f}.distancePanel .kv span{color:#aaa}
.prHistory{padding-top:4px;padding-bottom:4px}.prHistoryRow{display:grid;grid-template-columns:88px 1fr;gap:12px;align-items:center;padding:13px 0;border-bottom:1px solid #e6e6df}.prHistoryRow:last-child{border-bottom:0}.prHistoryRow small,.prHistoryRow b,.prHistoryRow span{display:block}.prHistoryRow small{font-size:9px;color:#777;font-weight:900}.prHistoryRow>div:first-child>b{font-size:20px}.prHistoryShoe{text-align:right}.prHistoryShoe b{font-size:12px}.prHistoryShoe span{font-size:9px;color:#777;margin-top:3px}
.agingIntro{background:#111;color:#fff;border-radius:18px;padding:14px 15px;margin:8px 0 12px}.agingIntro b,.agingIntro span{display:block}.agingIntro b{color:#c9ff54;font-size:11px;letter-spacing:.04em}.agingIntro span{color:#aaa;font-size:9px;line-height:1.45;margin-top:5px}
</style></head><body><main class="app">
<div class="top"><a class="back" href="index.php">← Home</a><div class="brand">TOETAP<i></i></div></div>
<div class="kicker">SHOE INTELLIGENCE</div><h1 class="pageTitle">Insights</h1>
<p class="muted"><?=$dataSource==='STRAVA'?'Strava is the primary run-data source.':'No Strava connection — using runs stored directly in TOETAP.'?></p>
<div class="tiny" style="margin:0 0 12px">DATA SOURCE · <?=htmlspecialchars($sourceNote)?></div>

<div class="metricGrid"><div class="metric"><small>30 DAYS</small><b><?=number_format($km30,1)?> km</b></div><div class="metric"><small>RUNS</small><b><?=$cnt30?></b></div><div class="metric"><small>AVG PACE</small><b><?=paceFmt($avgPace)?></b></div></div>

<div class="section"><h3>Performance by distance</h3><span class="tiny">Default · last 1 year</span></div>
<div class="periodTabs" aria-label="Performance period">
<?php foreach(['3M','6M','1Y','ALL'] as $opt):?>
<a class="<?=$period===$opt?'active':''?>" href="?period=<?=$opt?>"><?=$opt?></a>
<?php endforeach;?>
</div>
<div class="tiny periodNote">Period affects activity analytics only. PR is always all-time from Strava Best Efforts.<?= $prSyncNote?' · '.htmlspecialchars($prSyncNote):'' ?></div>
<div class="distanceTabs" role="tablist" aria-label="Performance distance">
<?php foreach(['5K','10K','21K','42K'] as $i=>$label):$d=$distStats[$label]??null;?>
<button class="distanceTab <?=$i===0?'active':''?>" type="button" role="tab" aria-selected="<?=$i===0?'true':'false'?>" data-target="dist-<?=$label?>">
 <span><?=$label?></span>
 <?php $pr=$stravaPr[$label]??null;?>
 <b><?=$pr?'PR '.timeFmt($pr['sec']):'PR —'?></b>
 <small><?=$d?number_format($d['runs']).' runs':'No matching runs'?></small>
</button>
<?php endforeach;?>
</div>

<?php foreach(['5K','10K','21K','42K'] as $i=>$label):$d=$distStats[$label]??null;?>
<section id="dist-<?=$label?>" class="distancePanel <?=$i===0?'active':''?>" role="tabpanel">
 <?php $pr=$stravaPr[$label]??null;if($d||$pr):?>
 <div class="prHero">
   <small>ALL-TIME PR · STRAVA BEST EFFORT</small>
   <strong><?=$pr?timeFmt($pr['sec']):'—'?></strong>
   <span><?=$pr&&!empty($pr['date'])?htmlspecialchars(date('j M Y',strtotime($pr['date']))):($dataSource==='STRAVA'?'No Strava PR found in synced history':'Requires Strava Best Efforts')?></span>
 </div>
 <?php if($d):?>
 <div class="distanceHero">
   <div><small>BEST ACTIVITY · <?=$period?></small><strong><?=timeFmt($d['bestSec'])?></strong></div>
   <div><small>ACTIVITY PACE</small><strong><?=paceFmt($d['bestPace'])?> <em>/km</em></strong></div>
 </div>
 <div class="distanceSummary">
   <span><?=number_format($d['runs'])?> MATCHING RUNS</span>
   <span>AVG <?=paceFmt($d['paceN']?$d['paceSum']/$d['paceN']:0)?> /KM</span>
 </div>
 <button class="detailsToggle" type="button" aria-expanded="false">VIEW DETAILS <span>＋</span></button>
 <div class="distanceDetails">
   <div class="kv"><span>Best matching activity distance</span><b><?=number_format((float)$d['bestKm'],2)?> km</b></div>
   <div class="kv"><span>Best matching activity shoe</span><b><?=htmlspecialchars($d['bestShoe']==='No shoe'?'Not recorded':$d['bestShoe'])?></b></div>
   <div class="kv"><span>Average HR</span><b><?=$d['hrN']?number_format($d['hrSum']/$d['hrN'],0).' bpm':'No HR data'?></b></div>
   <div class="kv"><span>HR / pace efficiency</span><b><?=$d['effN']?number_format($d['effSum']/$d['effN'],3).' m/min/bpm':'No HR data'?></b></div>
 </div>
 <?php else:?><div class="empty">No matching <?=$label?> activities in <?=$period?>.</div><?php endif;?>
 <?php else:?><div class="empty">No <?=$label?> PR or matching activities found.</div><?php endif;?>
</section>
<?php endforeach;?>


<div class="section"><h3>PR shoe history</h3><span class="tiny">All-time · Strava Best Efforts</span></div>
<div class="v1panel prHistory">
<?php $hasPrHistory=false;foreach(['5K','10K','21K','42K'] as $label):$pr=$stravaPr[$label]??null;if(!$pr)continue;$hasPrHistory=true;?>
<div class="prHistoryRow">
 <div><small><?=$label?> PR</small><b><?=timeFmt($pr['sec'])?></b></div>
 <div class="prHistoryShoe"><b><?=htmlspecialchars($pr['shoe']??'Not recorded')?></b><span><?=!empty($pr['date'])?htmlspecialchars(date('j M Y',strtotime($pr['date']))):'Date unavailable'?></span></div>
</div>
<?php endforeach;if(!$hasPrHistory):?><div class="empty"><?=$dataSource==='STRAVA'?'No Strava Best Effort PRs found in synced history.':'Connect Strava to build PR shoe history.'?></div><?php endif;?>
</div>

<div id="shoe-aging" class="section"><h3>Performance vs shoe age</h3><span class="tiny">Trend · not causation</span></div>
<div class="agingIntro">
 <b>DOES YOUR SHOE STILL FEEL FAST?</b>
 <span>Compare pace and HR efficiency across mileage stages. Changes can also come from fitness, route, weather and workout type.</span>
</div>
<?php foreach($aging as $sid=>$buckets):?>
<div class="v1panel" style="margin-bottom:10px"><h3><?=htmlspecialchars($shoeRows[$sid]['display_name']??'Shoe')?></h3>
<?php foreach(['0–100','100–250','250–400','400+'] as $b):if(empty($buckets[$b]))continue;$x=$buckets[$b];?>
<div class="kv"><span><?=$b?> km · <?=$x['runs']?> runs</span><b><?=paceFmt($x['paceN']?$x['paceSum']/$x['paceN']:0)?> /km<?php if($x['effN']):?> · <?=number_format($x['effSum']/$x['effN'],3)?> eff.<?php endif;?></b></div>
<?php endforeach;?></div>
<?php endforeach;if(!$aging):?><div class="v1panel"><div class="empty">More shoe-linked run history is needed.</div></div><?php endif;?>

<div class="section"><h3>8-week distance</h3><span class="tiny">km / week</span></div><div class="v1panel"><div class="chart"><?php foreach($weeks as $v):?><div class="barwrap"><div class="bar" style="height:<?=max(3,($v/$maxWeek)*110)?>px"></div><div class="barlabel"><?=number_format($v,0)?></div></div><?php endforeach;?></div><div style="display:flex;justify-content:space-between"><span class="tiny">8 weeks ago</span><span class="tiny">This week</span></div></div>

<div class="section"><h3>What changed?</h3></div><div class="v1panel">
<div class="insightRow"><div class="insightIcon">↗</div><div><b>30-day volume</b><span><?=$change===null?'More history is needed for comparison.':'You ran '.abs(round($change)).'% '.($change>=0?'more':'less').' distance than the previous 30 days.'?></span></div></div>
<div class="insightRow"><div class="insightIcon">◎</div><div><b>Efficiency definition</b><span>HR / pace efficiency is running speed in metres per minute divided by average heart rate. Higher means more speed per heartbeat. It appears only when HR data exists.</span></div></div>
</div>
<a class="btn secondary" href="add_run.php">+ ADD RUN MANUALLY</a><?php v1nav('insights');?></main><script>
document.querySelectorAll('.distanceTab').forEach(tab=>{
 tab.addEventListener('click',()=>{
  document.querySelectorAll('.distanceTab').forEach(x=>{x.classList.remove('active');x.setAttribute('aria-selected','false')});
  document.querySelectorAll('.distancePanel').forEach(x=>x.classList.remove('active'));
  tab.classList.add('active');tab.setAttribute('aria-selected','true');
  const panel=document.getElementById(tab.dataset.target);if(panel)panel.classList.add('active');
 });
});
document.querySelectorAll('.detailsToggle').forEach(btn=>{
 btn.addEventListener('click',()=>{
  const d=btn.nextElementSibling,open=d.classList.toggle('open');
  btn.setAttribute('aria-expanded',open?'true':'false');
  btn.firstChild.nodeValue=open?'HIDE DETAILS ':'VIEW DETAILS ';
  btn.querySelector('span').textContent=open?'−':'＋';
 });
});
</script></body></html>
