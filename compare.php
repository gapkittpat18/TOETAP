<?php
require __DIR__.'/auth/bootstrap.php';
require __DIR__.'/v1_nav.php';
require_once __DIR__.'/strava/functions.php';
$userId=requireUser();

function esc($x){return htmlspecialchars((string)$x,ENT_QUOTES,'UTF-8');}
function paceFmt2($x){if(!$x)return '—';$m=floor($x/60);$ss=(int)round($x-$m*60);if($ss===60){$m++;$ss=0;}return $m.':'.str_pad((string)$ss,2,'0',STR_PAD_LEFT);}
function timeFmt2($x){if(!$x)return '—';$x=(int)round($x);$h=intdiv($x,3600);$m=intdiv($x%3600,60);$ss=$x%60;return ($h?$h.':':'').str_pad((string)$m,2,'0',STR_PAD_LEFT).':'.str_pad((string)$ss,2,'0',STR_PAD_LEFT);}
function distLabel2($m){if($m>=4950&&$m<=5050)return'5K';if($m>=9950&&$m<=10050)return'10K';if($m>=21000&&$m<=21200)return'21K';if($m>=42100&&$m<=42300)return'42K';return null;}

$q=$pdo->prepare("SELECT us.*,
 COALESCE(
   NULLIF(TRIM(us.nickname),''),
   NULLIF(TRIM(CONCAT_WS(' ',us.custom_brand,us.custom_model)),''),
   NULLIF(TRIM(CONCAT_WS(' ',sl.brand,sl.model)),''),
   CONCAT('Shoe #',us.id)
 ) display_name
 FROM user_shoes us
 LEFT JOIN shoe_library sl ON sl.id=us.shoe_library_id
 WHERE us.user_id=?
 ORDER BY (us.retired_at IS NULL) DESC, us.id DESC");
$q->execute([$userId]);$shoes=$q->fetchAll(PDO::FETCH_ASSOC);
$byId=[];
foreach($shoes as &$sh){
 $name=(string)$sh['display_name'];
 $meta=[];
 if(!empty($sh['initial_km'])) $meta[]=number_format((float)$sh['initial_km'],0).' km';
 if(!empty($sh['retired_at'])) $meta[]='RETIRED';
 // Always append physical shoe ID so duplicate models remain unambiguous.
 $meta[]='#'.(int)$sh['id'];
 $sh['picker_name']=$name.' · '.implode(' · ',$meta);
 $byId[(int)$sh['id']]=$sh;
}
unset($sh);
$a=(int)($_GET['a']??($shoes[0]['id']??0));$b=(int)($_GET['b']??($shoes[1]['id']??0));
if($a===$b && count($shoes)>1){foreach($shoes as $sh){if((int)$sh['id']!==$a){$b=(int)$sh['id'];break;}}}

function shoeStats($pdo,$userId,$shoe){
 $out=['runs'=>0,'km'=>0.0,'paceSum'=>0.0,'paceN'=>0,'hrSum'=>0.0,'hrN'=>0,'bests'=>[],'dist'=>[],'source'=>'TOETAP'];
 if(!$shoe)return $out;
 $gid=(string)($shoe['strava_gear_id']??'');
 if($gid){
  try{
   $token=validToken($pdo,$userId);$out['source']='STRAVA';
   $ck='toetap_compare_v217_'.$userId.'_'.(int)$shoe['id'];$c=$_SESSION[$ck]??null;
   if(is_array($c)&&isset($c['at'],$c['data'])&&time()-(int)$c['at']<21600)return $c['data'];
   $cands=[];
   for($page=1;$page<=5;$page++){
    $acts=stravaHttp('GET','https://www.strava.com/api/v3/athlete/activities?per_page=200&page='.$page,[],$token);
    foreach($acts as $x){
     $type=$x['sport_type']??($x['type']??'');if(!in_array($type,['Run','TrailRun','VirtualRun'],true)||(string)($x['gear_id']??'')!==$gid)continue;
     $km=(float)($x['distance']??0)/1000;$sec=(float)($x['moving_time']??0);$pace=$km>0?$sec/$km:0;$hr=(float)($x['average_heartrate']??0);
     $out['runs']++;$out['km']+=$km;if($pace){$out['paceSum']+=$pace;$out['paceN']++;}if($hr){$out['hrSum']+=$hr;$out['hrN']++;}
     $lab=null;if($km>=4.75&&$km<=5.25)$lab='5K';elseif($km>=9.5&&$km<=10.5)$lab='10K';elseif($km>=20&&$km<=22.2)$lab='21K';elseif($km>=40&&$km<=44.5)$lab='42K';
     if($lab){if(!isset($out['dist'][$lab]))$out['dist'][$lab]=['runs'=>0,'paceSum'=>0.0,'paceN'=>0];$d=&$out['dist'][$lab];$d['runs']++;if($pace){$d['paceSum']+=$pace;$d['paceN']++;}unset($d);}
     // Any run long enough can contain a 5K/10K/21K/42K Best Effort.
     // pr_count only means the activity created a new all-time PR; it must not gate shoe best-effort discovery.
     if(!empty($x['id']) && (float)($x['distance']??0) >= 4950) $cands[]=(string)$x['id'];
    }
    if(count($acts)<200)break;
   }
   foreach(array_slice(array_values(array_unique($cands)),0,100) as $aid){
    try{$det=stravaHttp('GET','https://www.strava.com/api/v3/activities/'.rawurlencode($aid),[],$token);
     foreach(($det['best_efforts']??[]) as $e){
      $lab=distLabel2((float)($e['distance']??0));
      $sec=(int)($e['elapsed_time']??$e['moving_time']??0);
      if($lab&&$sec>0&&(!isset($out['bests'][$lab])||$sec<$out['bests'][$lab]['time'])){
       $out['bests'][$lab]=[
        'time'=>$sec,
        // This indicates the source activity created at least one Strava PR.
        // Exact all-time PR equality is checked separately below when available.
        'activity_pr_count'=>(int)($det['pr_count']??0)
       ];
      }
     }
    }catch(Throwable $ignore){}
   }
   $_SESSION[$ck]=['at'=>time(),'data'=>$out];return $out;
  }catch(Throwable $ignore){}
 }
 $q=$pdo->prepare("SELECT distance_km,moving_time_sec FROM activities WHERE user_id=? AND user_shoe_id=? AND activity_type IN ('Run','TrailRun','VirtualRun') ORDER BY start_date DESC LIMIT 1000");
 $q->execute([$userId,(int)$shoe['id']]);
 foreach($q as $x){$km=(float)$x['distance_km'];$sec=(float)$x['moving_time_sec'];$pace=$km>0&&$sec>0?$sec/$km:0;$out['runs']++;$out['km']+=$km;if($pace){$out['paceSum']+=$pace;$out['paceN']++;}}
 return $out;
}
$sa=shoeStats($pdo,$userId,$byId[$a]??null);$sb=shoeStats($pdo,$userId,$byId[$b]??null);
// Reuse the all-time PR cache from Insights when available.
// Never label a shoe effort PR merely because its activity had pr_count > 0.
$globalPrTimes=[];
foreach($_SESSION as $k=>$v){
 if(strpos((string)$k,'toetap_strava_pr_v208_'.$userId)===0 && is_array($v) && isset($v['data'])){
  $pd=$v['data'];
  foreach(['5K','10K','21K','42K'] as $lab){
   if(isset($pd[$lab])){
    if(is_array($pd[$lab])) $t=(int)($pd[$lab]['time']??$pd[$lab]['elapsed_time']??0);
    else $t=(int)$pd[$lab];
    if($t>0)$globalPrTimes[$lab]=$t;
   }
  }
 }
}
function effortCell($stats,$lab,$globalPrTimes){
 if(empty($stats['bests'][$lab]['time'])) return ['time'=>'—','pr'=>false];
 $t=(int)$stats['bests'][$lab]['time'];
 return ['time'=>timeFmt2($t),'pr'=>isset($globalPrTimes[$lab]) && abs((int)$globalPrTimes[$lab]-$t)<=1];
}

function valueUnlocked($shoe,$km){$price=(float)($shoe['purchase_price']??0);$target=(float)($shoe['target_km']??0);if(!$price||!$target)return null;return ['v'=>$price*min(1,$km/$target),'p'=>$price,'pct'=>min(100,$km/$target*100)];}
$va=valueUnlocked($byId[$a]??[],$sa['km']);$vb=valueUnlocked($byId[$b]??[],$sb['km']);
?>
<!doctype html><html><head><meta name="viewport" content="width=device-width,initial-scale=1"><title>Shoe Comparison · TOETAP</title><link rel="stylesheet" href="assets/v1.css">
<style>
body{margin:0;background:#f5f5ef;color:#111;font-family:Arial,sans-serif}.comparePage .wrap{margin:0;padding:0}.comparePage h1{font-size:32px;margin:6px 0 4px}.comparePage .sub{font-size:11px;color:#777}.pickCards{display:grid;grid-template-columns:minmax(0,1fr) 28px minmax(0,1fr);gap:7px;align-items:start;margin:18px 0}.vs{text-align:center;font-size:9px;font-weight:900;padding-top:23px}.shoePicker{position:relative;min-width:0}.shoePicker summary{list-style:none;background:#fff;border:1px solid #e4e4dd;border-radius:16px;padding:10px 30px 10px 11px;min-height:38px;position:relative;cursor:pointer}.shoePicker summary::-webkit-details-marker{display:none}.pickLabel{display:block;color:#999;font-size:7px;font-weight:900;margin-bottom:4px}.shoePicker summary b{display:block;font-size:10px;line-height:1.2;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}.chev{position:absolute;right:11px;top:19px}.shoeMenu{position:absolute;z-index:30;top:calc(100% + 5px);left:0;width:max(100%,220px);max-width:min(310px,85vw);max-height:300px;overflow-y:auto;background:#fff;border:1px solid #e4e4dd;border-radius:15px;padding:5px;box-shadow:0 14px 35px rgba(0,0,0,.13)}.shoePicker:last-child .shoeMenu{right:0;left:auto}.shoeMenu a{display:block;background:#fff;color:#111!important;text-decoration:none;padding:11px 10px;border-radius:10px;font-size:10px;font-weight:800;line-height:1.25;border-bottom:1px solid #f0f0ea}.shoeMenu a:last-child{border-bottom:0}.shoeMenu a.active{background:#c9ff54;color:#111!important}.comparePage .card{background:#111;color:#fff;border-radius:22px;padding:16px;margin:10px 0}.comparePage .names,.comparePage .row{display:grid;grid-template-columns:1fr 90px 1fr;gap:8px;align-items:center}.comparePage .names b:last-child,.comparePage .row .r{text-align:right}.comparePage .names{padding-bottom:13px;border-bottom:1px solid #2c2e2a}.comparePage .names b{font-size:14px}.comparePage .names small{font-size:8px;color:#777}.comparePage .row{padding:12px 0;border-bottom:1px solid #252724}.comparePage .row:last-child{border:0}.comparePage .row .label{text-align:center;font-size:8px;color:#888;font-weight:900}.comparePage .row strong{font-size:16px}.comparePage .lime{color:#c9ff54}.comparePage .section{font-size:11px;font-weight:900;margin:22px 2px 8px}.comparePage .note{font-size:9px;color:#888;line-height:1.45;margin-top:10px}.comparePage .empty{background:#fff;border-radius:18px;padding:20px;font-size:12px}
.comparePage .prBadge{font-style:normal;font-size:7px;background:#c9ff54;color:#111;border-radius:999px;padding:3px 5px;margin-left:3px;vertical-align:2px}
</style></head><body><main class="app comparePage">
<div class="top"><div class="brand">TOETAP<i></i></div><a class="avatar" href="settings.php">G</a></div>
<div class="wrap">
<div class="sub">TOETAP · SHOE INTELLIGENCE</div><h1>Compare shoes.</h1><div class="sub">Your data. Your rotation. No review scores.</div>
<?php if(count($shoes)<2):?><div class="empty">Add at least two active shoes to compare them.</div>
<?php else:?>
<div class="pickCards">
 <details class="shoePicker">
  <summary><span class="pickLabel">SHOE A</span><b><?=esc($byId[$a]['picker_name']??'Choose shoe')?></b><span class="chev">⌄</span></summary>
  <div class="shoeMenu"><?php foreach($shoes as $sh): if((int)$sh['id']===$b) continue; ?>
   <a class="<?=$a==(int)$sh['id']?'active':''?>" href="?a=<?=$sh['id']?>&b=<?=$b?>"><?=esc($sh['picker_name'])?></a>
  <?php endforeach;?></div>
 </details>
 <div class="vs">VS</div>
 <details class="shoePicker">
  <summary><span class="pickLabel">SHOE B</span><b><?=esc($byId[$b]['picker_name']??'Choose shoe')?></b><span class="chev">⌄</span></summary>
  <div class="shoeMenu"><?php foreach($shoes as $sh): if((int)$sh['id']===$a) continue; ?>
   <a class="<?=$b==(int)$sh['id']?'active':''?>" href="?a=<?=$a?>&b=<?=$sh['id']?>"><?=esc($sh['picker_name'])?></a>
  <?php endforeach;?></div>
 </details>
</div>
<div class="card">
 <div class="names"><b><?=esc($byId[$a]['display_name']??'Shoe A')?> <small>#<?=$a?></small></b><span></span><b><?=esc($byId[$b]['display_name']??'Shoe B')?> <small>#<?=$b?></small></b></div>
 <div class="row"><strong><?=number_format($sa['runs'])?></strong><div class="label">RUNS</div><strong class="r"><?=number_format($sb['runs'])?></strong></div>
 <div class="row"><strong><?=number_format($sa['km'],0)?> km</strong><div class="label">DISTANCE</div><strong class="r"><?=number_format($sb['km'],0)?> km</strong></div>
 <div class="row"><strong><?=paceFmt2($sa['paceN']?$sa['paceSum']/$sa['paceN']:0)?></strong><div class="label">AVG PACE</div><strong class="r"><?=paceFmt2($sb['paceN']?$sb['paceSum']/$sb['paceN']:0)?></strong></div>
 <div class="row"><strong><?=$sa['hrN']?number_format($sa['hrSum']/$sa['hrN'],0):'—'?></strong><div class="label">AVG HR</div><strong class="r"><?=$sb['hrN']?number_format($sb['hrSum']/$sb['hrN'],0):'—'?></strong></div>
</div>
<div class="section">BEST EFFORT ON EACH SHOE</div>
<div class="card">
<?php foreach(['5K','10K','21K','42K'] as $lab): $ea=effortCell($sa,$lab,$globalPrTimes); $eb=effortCell($sb,$lab,$globalPrTimes); ?>
 <div class="row">
  <strong class="lime"><?=$ea['time']?><?=$ea['pr']?' <em class="prBadge">PR</em>':''?></strong>
  <div class="label"><?=$lab?><br>BEST EFFORT</div>
  <strong class="r lime"><?=$eb['time']?><?=$eb['pr']?' <em class="prBadge">PR</em>':''?></strong>
 </div>
<?php endforeach;?>
</div>
<div class="section">USAGE & VALUE</div>
<div class="card">
 <div class="row"><strong><?=number_format((float)($byId[$a]['target_km']??0),0)?> km</strong><div class="label">TARGET LIFE</div><strong class="r"><?=number_format((float)($byId[$b]['target_km']??0),0)?> km</strong></div>
 <div class="row"><strong><?=$va?number_format($va['pct'],0).'%':'—'?></strong><div class="label">VALUE UNLOCKED</div><strong class="r"><?=$vb?number_format($vb['pct'],0).'%':'—'?></strong></div>
</div>
<div class="note">Best Effort uses Strava Best Efforts from runs made with that shoe. A PR badge is shown only when the effort matches the cached all-time Strava PR from Insights. Pace, HR and usage are descriptive personal data; they do not prove one shoe caused better performance.</div>
<?php endif;?>
</div></main><?php v1nav('shoes');?></body></html>