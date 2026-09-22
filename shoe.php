<?php
require __DIR__.'/auth/bootstrap.php';require __DIR__.'/v1_nav.php';require_once __DIR__.'/strava/functions.php';
require_once __DIR__.'/rotation_intelligence.php';
$id=(int)($_GET['id']??0);$userId=requireUser();
$toetapRoleProfile=null;

if($_SERVER['REQUEST_METHOD']==='POST' && ($_POST['action']??'')==='retire'){
 requireCsrf();
 $x=$pdo->prepare("UPDATE user_shoes SET active=0,retired_at=UTC_TIMESTAMP() WHERE id=? AND user_id=?");$x->execute([$id,$userId]);header('Location: shoes.php');exit;
}
$q=$pdo->prepare("SELECT us.*,GROUP_CONCAT(t.tag_code ORDER BY t.id SEPARATOR ', ') tags FROM user_shoes us LEFT JOIN tags t ON t.user_shoe_id=us.id AND t.activation_status='ACTIVE' WHERE us.id=? AND us.user_id=? GROUP BY us.id");$q->execute([$id,$userId]);$s=$q->fetch();if(!$s)exit('Shoe not found.');
$name=$s['nickname']?:trim($s['custom_brand'].' '.$s['custom_model']);
$run=$pdo->prepare("SELECT COUNT(*) runs,COALESCE(SUM(distance_m),0)/1000 km FROM activities WHERE user_shoe_id=? AND user_id=?");$run->execute([$id,$userId]);$stat=$run->fetch();
$current=(float)$s['initial_km']+(float)$stat['km'];
$pct=$s['target_km']>0?min(100,$current/$s['target_km']*100):0;
$remaining=max(0,(float)$s['target_km']-$current);
$price=isset($s['purchase_price'])?(float)$s['purchase_price']:0;
$valueUnlocked=($price>0 && (float)$s['target_km']>0)?$price*min(1,$current/(float)$s['target_km']):null;
$valuePct=min(100,$current/max(1,(float)$s['target_km'])*100);
$perf=['runs'=>0,'km'=>0.0,'paceSum'=>0.0,'paceN'=>0,'hrSum'=>0.0,'hrN'=>0,'dist'=>[],'prs'=>[],'roleRuns'=>[]];
$perfSource='TOETAP';
if(!empty($s['strava_gear_id'])){
 try{
  $token=validToken($pdo,$userId);$perfSource='STRAVA';$gearId=(string)$s['strava_gear_id'];
  $cacheKey='toetap_shoe_perf_v2110_'.$userId.'_'.$id;
  $cached=$_SESSION[$cacheKey]??null;
  if(is_array($cached)&&isset($cached['at'],$cached['perf'])&&(time()-(int)$cached['at'])<21600){
   $perf=$cached['perf'];
  }else{
   $candidates=[];
   for($page=1;$page<=5;$page++){
    $acts=stravaHttp('GET','https://www.strava.com/api/v3/athlete/activities?per_page=200&page='.$page,[],$token);
    foreach($acts as $a){
     $type=$a['sport_type']??($a['type']??'');
     if(!in_array($type,['Run','TrailRun','VirtualRun'],true)||(string)($a['gear_id']??'')!==$gearId)continue;
     $km=(float)($a['distance']??0)/1000;$sec=(float)($a['moving_time']??0);$pace=$km>0?$sec/$km:0;$hr=(float)($a['average_heartrate']??0);
     $perf['roleRuns'][]=['km'=>$km,'pace'=>$pace,'pr_count'=>(int)($a['pr_count']??0)];
     $perf['runs']++;$perf['km']+=$km;if($pace>0){$perf['paceSum']+=$pace;$perf['paceN']++;}if($hr>0){$perf['hrSum']+=$hr;$perf['hrN']++;}
     $lab=null;if($km>=4.75&&$km<=5.25)$lab='5K';elseif($km>=9.5&&$km<=10.5)$lab='10K';elseif($km>=20&&$km<=22.2)$lab='21K';elseif($km>=40&&$km<=44.5)$lab='42K';
     if($lab){
      if(!isset($perf['dist'][$lab]))$perf['dist'][$lab]=['runs'=>0,'paceSum'=>0.0,'paceN'=>0,'bestSec'=>null];
      $d=&$perf['dist'][$lab];$d['runs']++;if($pace>0){$d['paceSum']+=$pace;$d['paceN']++;}if($sec>0&&($d['bestSec']===null||$sec<$d['bestSec']))$d['bestSec']=$sec;unset($d);
     }
     if((int)($a['pr_count']??0)>0&&!empty($a['id']))$candidates[]=(string)$a['id'];
    }
    if(count($acts)<200)break;
   }
   foreach(array_slice(array_values(array_unique($candidates)),0,50) as $aid){
    try{$detail=stravaHttp('GET','https://www.strava.com/api/v3/activities/'.rawurlencode($aid),[],$token);
     foreach(($detail['best_efforts']??[]) as $eff){
      $m=(float)($eff['distance']??0);$lab=null;
      if($m>=4950&&$m<=5050)$lab='5K';elseif($m>=9950&&$m<=10050)$lab='10K';elseif($m>=21000&&$m<=21200)$lab='21K';elseif($m>=42100&&$m<=42300)$lab='42K';
      $sec=(int)($eff['elapsed_time']??$eff['moving_time']??0);
      if($lab&&$sec>0&&(!isset($perf['prs'][$lab])||$sec<$perf['prs'][$lab]['sec']))$perf['prs'][$lab]=['sec'=>$sec,'date'=>$eff['start_date']??($detail['start_date']??'')];
     }
    }catch(Throwable $ignore){}
   }
   $_SESSION[$cacheKey]=['at'=>time(),'perf'=>$perf];
  }
 }catch(Throwable $ignore){}
}
function shoePaceFmt($x){if(!$x)return '—';$m=floor($x/60);$ss=(int)round($x-$m*60);if($ss===60){$m++;$ss=0;}return $m.':'.str_pad((string)$ss,2,'0',STR_PAD_LEFT);}
function shoeTimeFmt($x){if(!$x)return '—';$x=(int)round($x);$h=intdiv($x,3600);$m=intdiv($x%3600,60);$ss=$x%60;return ($h?$h.':':'').str_pad((string)$m,2,'0',STR_PAD_LEFT).':'.str_pad((string)$ss,2,'0',STR_PAD_LEFT);}
?><!doctype html><html><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title><?=htmlspecialchars($name)?> — TOETAP</title><link rel="stylesheet" href="assets/v1.css"><style>
.valueUnlock{background:#111;color:#fff;border-radius:18px;padding:14px;margin:10px 0 16px}
.valueUnlockTop{display:flex;justify-content:space-between;align-items:center;gap:10px;font-size:11px}.valueUnlockTop b{color:#c9ff54}.valueUnlockTop span{color:#aaa}
.valueUnlockBar{height:9px;background:#30322f;border-radius:999px;overflow:hidden;margin-top:10px}.valueUnlockBar i{display:block;height:100%;background:#c9ff54;border-radius:999px}
.shoePerf{background:#111;color:#fff;border-radius:22px;padding:16px;margin:14px 0}.shoePerfHead{display:flex;justify-content:space-between;gap:12px;align-items:flex-end}.shoePerfHead small{color:#c9ff54;font-size:9px;font-weight:900}.shoePerfHead h3{margin:3px 0 0}.shoePerfSummary{display:grid;grid-template-columns:repeat(3,1fr);gap:7px;margin:13px 0}.shoePerfSummary div{background:#20221f;border-radius:14px;padding:10px}.shoePerfSummary small,.shoePerfSummary b{display:block}.shoePerfSummary small{font-size:8px;color:#999}.shoePerfSummary b{font-size:15px;margin-top:4px}.shoeDist{display:grid;grid-template-columns:repeat(2,1fr);gap:7px}.shoeDist>div{background:#20221f;border-radius:14px;padding:11px}.shoeDist small,.shoeDist b,.shoeDist span{display:block}.shoeDist small{color:#c9ff54;font-size:9px;font-weight:900}.shoeDist b{font-size:18px;margin:4px 0}.shoeDist span{font-size:9px;color:#999}.shoePerfNote{font-size:9px;color:#999;margin-top:10px}
.shoeAgeLink{margin-top:12px;border-top:1px solid #30322e;padding-top:12px}.shoeAgeLink a{color:#c9ff54;text-decoration:none;font-size:10px;font-weight:900;letter-spacing:.03em}
.personalRole{background:#111;color:#fff;border-radius:20px;padding:15px;margin:12px 0}.roleEyebrow{font-size:8px;color:#c9ff54;font-weight:900;letter-spacing:.08em}.roleTags{display:flex;gap:6px;flex-wrap:wrap;margin:9px 0}.roleTags span{border:1px solid #3b3d38;border-radius:999px;padding:6px 9px;font-size:9px;font-weight:900}.roleReason{font-size:8px;color:#888;line-height:1.4}
.timelineItem b{font-size:10px}.timelineItem small{font-size:8px;color:#777;margin-top:2px}

.shoeJourney{background:#111;color:#fff;border-radius:0;padding:18px 20px;margin:18px -20px;overflow:hidden;width:calc(100% + 40px);max-width:none;box-sizing:border-box}
.journeyTop{display:flex;align-items:flex-end;justify-content:space-between;gap:12px}
.journeyEyebrow{font-size:8px;font-weight:900;letter-spacing:.12em;color:#c9ff54}
.journeyKm{font-size:15px;margin-top:3px}.journeyKm b{font-size:25px}
.journeyPct{font-size:8px;font-weight:900;letter-spacing:.06em;color:#aaa;padding-bottom:3px}
.journeyBar{height:5px;background:#292929;border-radius:99px;margin:12px 0 15px;overflow:hidden}
.journeyBar i{display:block;height:100%;background:#c9ff54;border-radius:99px}
.journeySteps{display:flex;justify-content:space-between;gap:4px;position:relative}
.journeySteps:before{content:"";position:absolute;left:10px;right:10px;top:9px;height:1px;background:#343434}
.journeyStep{position:relative;z-index:1;min-width:0;text-align:center;flex:1;color:#6f6f6f}
.journeyStep span{width:19px;height:19px;margin:0 auto 6px;border-radius:50%;display:grid;place-items:center;background:#292929;border:1px solid #454545;font-size:8px}
.journeyStep.done{color:#fff}.journeyStep.done span{background:#c9ff54;border-color:#c9ff54;color:#111;font-weight:900}
.journeyStep b,.journeyStep small{display:block;white-space:nowrap}
.journeyStep b{font-size:8px}.journeyStep small{font-size:6px;margin-top:2px;color:#686868}
.journeyStep.done small{color:#929292}
.journeyAchievement{margin-top:15px;padding-top:12px;border-top:1px solid #292929;display:flex;justify-content:space-between;align-items:center;gap:10px}
.journeyAchievement small,.journeyAchievement b{display:block}.journeyAchievement small{font-size:6px;color:#777;letter-spacing:.1em}.journeyAchievement b{font-size:11px;margin-top:2px}
.journeyChips{display:flex;gap:5px;flex-wrap:wrap;justify-content:flex-end}.journeyChips span{background:#c9ff54;color:#111;border-radius:99px;padding:5px 8px;font-size:7px;font-weight:900}

@media (min-width:720px){
 .shoeJourney{border-radius:22px;margin:18px 0;width:100%}
}

/* V2.2.3 — full-bleed classic journey */
.shoeJourneyClassic{
 width:100%;
 max-width:100%;
 margin:18px 0;
 background:#f1f1eb;
 color:#111;
 box-sizing:border-box;
 overflow:hidden;
 border-radius:20px;
}
.sjInner{width:100%;padding:18px 20px 20px;margin:0;box-sizing:border-box}
.sjEyebrow{font-size:8px;font-weight:900;letter-spacing:.12em}
.shoeJourneyClassic h3{font-size:19px;line-height:1;margin:5px 0 12px}
.sjList{position:relative}
.sjItem{display:grid;grid-template-columns:28px 1fr;gap:9px;align-items:center;padding:9px 0;position:relative}
.sjItem:not(:last-child):after{content:"";position:absolute;left:10px;top:30px;width:2px;height:18px;background:#d4d4cd}
.sjDot{width:21px;height:21px;border-radius:50%;display:grid;place-items:center;background:#deded7;font-size:9px;font-weight:900;z-index:1}
.sjItem.done .sjDot{background:#c9ff54}
.sjItem.future{color:#777}
.sjItem>div{min-width:0;text-align:left}
.sjItem b,.sjItem small{display:block;width:100%;margin-left:0;padding-left:0;text-align:left}
.sjItem b{font-size:10px;line-height:1.25;letter-spacing:.01em}
.sjItem small{font-size:8px;line-height:1.25;color:#777;margin-top:3px}
.sjAchievement{margin-top:10px;padding-top:13px;border-top:1px solid #d8d8d1;display:flex;align-items:center;justify-content:space-between;gap:12px}
.sjAchievement small,.sjAchievement b{display:block}
.sjAchievement small{font-size:6px;color:#777;letter-spacing:.12em}
.sjAchievement b{font-size:11px;margin-top:2px}
.sjPrs{display:flex;gap:5px;flex-wrap:wrap;justify-content:flex-end}
.sjPrs span{background:#111;color:#fff;border-radius:999px;padding:5px 8px;font-size:7px;font-weight:900}


</style></head><body><main class="app">
<div class="top"><a class="back" href="shoes.php">← Shoes</a><div class="brand">TOETAP<i></i></div></div>
<div class="kicker"><?=htmlspecialchars($s['custom_brand'])?></div><?php if(($_GET['saved']??'')==='1'):?><div class="notice">✓ Shoe updated.</div><?php endif;?><h1 class="pageTitle"><?=htmlspecialchars($name)?></h1>
<div class="heroShoeV1"><img src="<?=htmlspecialchars(!empty($s['custom_photo_path'])?$s['custom_photo_path']:'assets/sneaker.png')?>" alt="Sneaker"></div><div style="text-align:center;margin:-4px 0 12px"><a href="shoe_photo.php?id=<?=$id?>" style="font-size:10px;font-weight:900;color:#111;text-decoration:none;border-bottom:1px solid #111"><?=!empty($s['custom_photo_path'])?'CHANGE PHOTO':'＋ ADD SHOE PHOTO'?></a></div><div class="muted"><?=htmlspecialchars($s['custom_model'])?></div>
<div class="metricGrid"><div class="metric"><small>MILEAGE</small><b><?=number_format($current,1)?> km</b></div><div class="metric"><small>LIFESPAN USED</small><b><?=number_format($pct,0)?>%</b></div><div class="metric"><small>REMAINING</small><b><?=number_format($remaining,0)?> km</b></div></div>
<div class="metricGrid"><div class="metric"><small>PURCHASE PRICE</small><b><?=$price>0?'฿'.number_format($price,0):'—'?></b></div><div class="metric"><small>VALUE UNLOCKED</small><b><?=$valueUnlocked!==null?'฿'.number_format($valueUnlocked,0).' / ฿'.number_format($price,0):'Add price'?></b></div><div class="metric"><small>TOETAP RUNS</small><b><?=number_format((int)$stat['runs'])?></b></div></div>
<?php if($price>0):?><div class="valueUnlock"><div class="valueUnlockTop"><b><?=number_format($valuePct,1)?>% UNLOCKED</b><span><?=$valuePct>=100?'FULLY UNLOCKED':number_format(max(0,(float)$s['target_km']-$current),1).' km to full value'?></span></div><div class="valueUnlockBar"><i style="width:<?=max(1,$valuePct)?>%"></i></div></div><?php endif;?>

<?php
// Use the exact same matched Strava activities as SHOE PERFORMANCE PROFILE.

if(!empty($perf['roleRuns'])) $toetapRoleProfile=toetapInferRoles($perf['roleRuns']);

// V2.2.1 Compact Shoe Journey.
$timelineKm=(float)($currentKm??($mileage??($s['initial_km']??0)));
$timelineTarget=(float)($s['target_km']??500);
if($timelineTarget<=0) $timelineTarget=500;
$timelinePct=min(100,max(0,($timelineKm/$timelineTarget)*100));
$timelineActivated=$s['created_at']??($s['activated_at']??null);

$timelineMarks=[50,100,250];
foreach($timelineMarks as $mk){
    if($mk >= $timelineTarget) continue;
}
$timelineFinal=(int)round($timelineTarget);

$timelinePrs=[];
foreach(['5K','10K','21K','42K'] as $tlab){
    if(!empty($perf['prs'][$tlab])) $timelinePrs[]=$tlab;
}
?>
<?php if($toetapRoleProfile): ?>
<div class="personalRole">
 <div class="roleEyebrow">PERSONAL SHOE PROFILE</div>
 <div class="roleTags"><?php foreach($toetapRoleProfile['roles'] as $role): ?><span><?=htmlspecialchars($role,ENT_QUOTES,'UTF-8')?></span><?php endforeach;?></div>
 <div class="roleReason"><?=htmlspecialchars($toetapRoleProfile['reason'],ENT_QUOTES,'UTF-8')?></div>
</div>
<?php endif; ?>
<div class="shoePerf">
 <div class="shoePerfHead"><div><small>SHOE PERFORMANCE PROFILE</small><h3><?=htmlspecialchars($name)?></h3></div><span class="tiny"><?=$perfSource==='STRAVA'?'STRAVA':'TOETAP'?></span></div>
 <div class="shoePerfSummary">
  <div><small>RUNS</small><b><?=number_format((int)$perf['runs'])?></b></div>
  <div><small>DISTANCE</small><b><?=number_format((float)$perf['km'],0)?> km</b></div>
  <div><small>AVG PACE</small><b><?=shoePaceFmt($perf['paceN']?$perf['paceSum']/$perf['paceN']:0)?></b></div>
 </div>
 <div class="shoeDist">
 <?php foreach(['5K','10K','21K','42K'] as $lab):$d=$perf['dist'][$lab]??null;$pr=$perf['prs'][$lab]??null;?>
  <div><small><?=$lab?></small><b><?=$pr?'PR '.shoeTimeFmt($pr['sec']):($d?'BEST '.shoeTimeFmt($d['bestSec']):'—')?></b><span><?=$d?number_format($d['runs']).' matching runs · avg '.shoePaceFmt($d['paceN']?$d['paceSum']/$d['paceN']:0).'/km':'No matching runs'?></span></div>
 <?php endforeach;?>
 </div>
 <div class="shoePerfNote"><?=$perfSource==='STRAVA'?'PR uses Strava Best Efforts for this shoe. Other stats use up to 1,000 recent Strava activities.':'Link a Strava gear to unlock shoe PR and richer performance data.'?></div>
 <div class="shoeAgeLink"><a href="analytics.php#shoe-aging">VIEW PERFORMANCE VS SHOE AGE →</a></div>
</div>
<div class="v1panel"><div class="kv">
<section class="shoeJourneyClassic">
 <div class="sjInner">
  <div class="sjEyebrow">SHOE JOURNEY</div>
  <h3>MILESTONES</h3>

  <div class="sjList">
   <div class="sjItem done">
    <span class="sjDot">✓</span>
    <div><b>ACTIVATED</b><small><?=$timelineActivated?date('M j, Y',strtotime($timelineActivated)):'TOETAP shoe'?></small></div>
   </div>

   <?php foreach([50,100,250] as $mk): if($mk >= $timelineTarget) continue; $done=$timelineKm >= $mk; ?>
   <div class="sjItem <?=$done?'done':'future'?>">
    <span class="sjDot"><?=$done?'✓':'·'?></span>
    <div><b><?=$mk?> KM</b><small><?=$done?'Unlocked':number_format(max(0,$mk-$timelineKm),0).' km to go'?></small></div>
   </div>
   <?php endforeach; ?>

   <?php $finalDone=$timelineKm >= $timelineTarget; ?>
   <div class="sjItem <?=$finalDone?'done':'future'?>">
    <span class="sjDot"><?=$finalDone?'✓':'·'?></span>
    <div>
     <b><?=number_format($timelineFinal,0)?> KM · FULL VALUE</b>
     <small><?=$finalDone?'Fully unlocked':number_format(max(0,$timelineTarget-$timelineKm),0).' km to go'?></small>
    </div>
   </div>
  </div>

  <?php if($timelinePrs): ?>
  <div class="sjAchievement">
   <div><small>ACHIEVEMENT</small><b>PR SHOE</b></div>
   <div class="sjPrs"><?php foreach($timelinePrs as $pr): ?><span><?=$pr?> PR</span><?php endforeach; ?></div>
  </div>
  <?php endif; ?>
 </div>
</section>
<span>NFC</span><b><?=htmlspecialchars($s['tags']?:'Not linked')?></b></div><div class="kv"><span>Strava</span><b><?=htmlspecialchars($s['strava_gear_name']?:'Not linked')?></b></div><div class="kv"><span>Size</span><b><?=htmlspecialchars($s['size']?:'—')?></b></div><div class="kv"><span>Color</span><b><?=htmlspecialchars($s['color']?:'—')?></b></div><div class="progress"><div class="fill" style="width:<?=$pct?>%"></div></div><div class="tiny" style="margin-top:8px"><?=number_format($pct,0)?>% of <?=number_format((float)$s['target_km'],0)?> km target used · <?=number_format($remaining,0)?> km remaining</div></div>
<a class="btn green" href="shoe_edit.php?id=<?=$id?>">EDIT SHOE / STRAVA GEAR</a><a class="btn secondary" href="strava_v1.php">STRAVA CONNECTION</a>
<form method="post" onsubmit="return confirm('Retire this shoe? It will leave your active rotation.');"><?=csrfField()?><input type="hidden" name="action" value="retire"><button class="btn secondary danger" type="submit">RETIRE SHOE</button></form>
<?php v1nav('shoes');?></main></body></html>