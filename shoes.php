<?php
require __DIR__.'/auth/bootstrap.php';
require_once __DIR__.'/strava/functions.php';
require_once __DIR__.'/rotation_intelligence.php';require __DIR__.'/v1_nav.php';
$uid=requireUser();
$q=$pdo->prepare("SELECT us.*,GROUP_CONCAT(t.tag_code SEPARATOR ', ') tags,
 COALESCE((SELECT SUM(a.distance_m)/1000 FROM activities a WHERE a.user_shoe_id=us.id AND a.user_id=us.user_id),0) local_km
 FROM user_shoes us
 LEFT JOIN tags t ON t.user_shoe_id=us.id AND t.activation_status='ACTIVE'
 WHERE us.user_id=? AND us.active=1 GROUP BY us.id ORDER BY us.id DESC");
$q->execute([$uid]);$rows=$q->fetchAll();

// V2.2.7 — Strava-first mileage for the Shoes page.
// Cache all linked gear mileage for 6h so normal page loads do not repeatedly hit Strava.
$stravaMileage=[];
$stravaMileageReady=false;
$gearCacheKey='toetap_shoes_gear_v227_'.$uid;
$gearCached=$_SESSION[$gearCacheKey]??null;
if(is_array($gearCached) && isset($gearCached['at'],$gearCached['km']) && (time()-(int)$gearCached['at'])<21600){
    $stravaMileage=is_array($gearCached['km'])?$gearCached['km']:[];
    $stravaMileageReady=true;
}else{
    $linkedGear=[];
    foreach($rows as $row){
        if(!empty($row['strava_gear_id'])) $linkedGear[(string)$row['strava_gear_id']]=true;
    }
    if($linkedGear){
        try{
            $token=validToken($pdo,$uid);
            foreach(array_keys($linkedGear) as $gearId){
                try{
                    $g=stravaHttp('GET','https://www.strava.com/api/v3/gear/'.rawurlencode($gearId),[],$token);
                    if(array_key_exists('distance',$g)){
                        $stravaMileage[$gearId]=max(0,(float)$g['distance']/1000);
                    }
                }catch(Throwable $ignoreGear){}
            }
            $_SESSION[$gearCacheKey]=['at'=>time(),'km'=>$stravaMileage];
            $stravaMileageReady=true;
        }catch(Throwable $ignoreStrava){}
    }
}
?><!doctype html><html><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Shoes — TOETAP</title><link rel="stylesheet" href="assets/v1.css">
<style>
.rotationCard{display:block;text-decoration:none;color:inherit;background:#111;border-radius:24px;padding:18px;margin:0 0 14px;color:#fff}
.rotationTop{display:flex;justify-content:space-between;gap:14px;align-items:flex-start}.rotationIdentity{display:flex;gap:12px;align-items:center}.rotationThumb{width:78px;height:58px;object-fit:contain;flex:0 0 auto}.rotationTop h3{font-size:23px;line-height:1.05;margin:3px 0 7px}.rotationTop .lifePct{font-size:25px;color:#c9ff54;font-weight:900}
.lifeBar{height:8px;background:#30322f;border-radius:999px;overflow:hidden;margin:15px 0}.lifeBar i{display:block;height:100%;background:#c9ff54;border-radius:999px}
.lifeStats{display:grid;grid-template-columns:repeat(3,1fr);gap:8px}.lifeStats>div{background:#20221f;border-radius:14px;padding:10px 9px}
.lifeStats small{display:block;font-size:9px;color:#9ea19b;letter-spacing:.08em;margin-bottom:5px}.lifeStats b{font-size:13px}
.cardActions{display:flex;justify-content:space-between;align-items:center;margin-top:12px;gap:10px}.cardActions .tiny{color:#a9aca9}
.priceAction{color:#c9ff54;font-size:11px;font-weight:900;text-decoration:none;border-bottom:1px solid #c9ff54;padding-bottom:2px}
.compareLink{display:inline-block;text-decoration:none;background:#111;color:#c9ff54;border-radius:999px;padding:9px 12px;font-size:10px;font-weight:900;margin:4px 0 12px}
.compareRow{display:flex;justify-content:flex-start;margin:8px 0 14px}.compareRow .compareLink{margin:0}
.rotationBox{background:#111;color:#fff;border-radius:22px;padding:16px;margin:12px 0 18px}.rotationEyebrow{font-size:8px;font-weight:900;color:#c9ff54;letter-spacing:.08em}.rotationBox h2{font-size:20px;margin:5px 0 10px}.rotationItem{display:flex;justify-content:space-between;align-items:center;color:#fff;text-decoration:none;padding:11px 0;border-top:1px solid #292b28}.rotationItem b,.rotationItem small{display:block}.rotationItem b{font-size:11px}.rotationItem small{font-size:8px;color:#999;margin-top:4px}.rotationItem strong{color:#c9ff54}.rotationEmpty{font-size:9px;color:#999;padding-top:8px}
</style></head><body><main class="app">
<div class="top"><div class="brand">TOETAP<i></i></div><a class="avatar" href="settings.php">G</a></div>
<div class="kicker">YOUR ROTATION</div><h1 class="pageTitle">Running shoes.</h1><div class="compareRow"><a class="compareLink" href="compare.php">COMPARE SHOES ↔</a></div>
<?php foreach($rows as $s):
 $nm=$s['nickname']?:trim($s['custom_brand'].' '.$s['custom_model']);
 $localCurrent=(float)$s['initial_km']+(float)$s['local_km'];
 $gearKey=(string)($s['strava_gear_id']??'');
 $usingStrava=($gearKey!=='' && array_key_exists($gearKey,$stravaMileage));
 $current=$usingStrava?(float)$stravaMileage[$gearKey]:$localCurrent;
 $target=max(1,(float)$s['target_km']);$used=min(100,$current/$target*100);$left=max(0,$target-$current);
 $price=(float)($s['purchase_price']??0);$valueUnlocked=($price>0&&$target>0)?$price*min(1,$current/$target):null;?>
<div class="rotationCard">
 <a href="shoe.php?id=<?=$s['id']?>" style="display:block;text-decoration:none;color:inherit">
  <div class="rotationTop"><div class="rotationIdentity"><img class="rotationThumb" src="<?=htmlspecialchars(!empty($s['custom_photo_path'])?$s['custom_photo_path']:'assets/sneaker.png')?>" alt=""><div><div class="kicker"><?=htmlspecialchars($s['custom_brand'])?></div><h3><?=htmlspecialchars($nm)?></h3><span class="pill"><?=htmlspecialchars($s['tags']?:'NO NFC')?></span></div></div><div class="lifePct"><?=number_format($used,1)?>%</div></div>
  <div class="lifeBar"><i style="width:<?=max(1,min(100,$used))?>%"></i></div>
  <div class="lifeStats"><div><small>RUN<?=$usingStrava?' · STRAVA':''?></small><b><?=number_format($current,1)?> / <?=number_format($target,0)?> km</b></div><div style="grid-column:span 2"><small>VALUE UNLOCKED</small><b><?=$valueUnlocked!==null?'฿'.number_format($valueUnlocked,0).' / ฿'.number_format($price,0):'Add purchase price'?></b></div></div>
 </a>
 <div class="cardActions"><span class="tiny"><?=$used>=100?'FULLY UNLOCKED':number_format($used,1).'% unlocked · '.number_format($left,1).' km to full value'?></span>
 <?php if($price<=0):?><a class="priceAction" href="shoe_edit.php?id=<?=$s['id']?>&focus=price">+ ADD PRICE</a><?php else:?><a class="priceAction" href="shoe_edit.php?id=<?=$s['id']?>&focus=price">EDIT PRICE</a><?php endif;?></div>
</div>
<?php endforeach;?>
<?php if(!$rows):?><div class="v1panel"><div class="empty">No active shoes yet.</div></div><?php endif;?>
<?php if(currentUserIsAdmin()):?><a class="fab" href="tag_new.php" title="Admin: issue NFC tag">＋</a><?php endif;?>
<?php v1nav('shoes');?></main></body></html>