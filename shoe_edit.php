<?php
require __DIR__.'/auth/bootstrap.php';require __DIR__.'/v1_nav.php';require_once __DIR__.'/strava/functions.php';
$userId=requireUser();$id=(int)($_GET['id']??$_POST['id']??0);
$q=$pdo->prepare("SELECT * FROM user_shoes WHERE id=? AND user_id=?");$q->execute([$id,$userId]);$shoe=$q->fetch();if(!$shoe)exit('Shoe not found.');
$gears=[];$se='';try{$token=validToken($pdo,$userId);$ath=stravaHttp('GET','https://www.strava.com/api/v3/athlete',[],$token);foreach(($ath['shoes']??[]) as $g)$gears[]=['id'=>(string)$g['id'],'name'=>$g['name']??$g['id'],'distance'=>(float)($g['distance']??0)];}catch(Throwable $e){$se='Strava unavailable. Reauthorize if needed.';}
$msg='';$err='';
if($_SERVER['REQUEST_METHOD']==='POST'){requireCsrf();$brand=trim($_POST['brand']??'');$model=trim($_POST['model']??'');$nick=trim($_POST['nickname']??'');$size=trim($_POST['size']??'');$color=trim($_POST['color']??'');$target=max(1,(float)($_POST['target_km']??500));$purchasePrice=trim($_POST['purchase_price']??'');$purchasePrice=$purchasePrice===''?null:max(0,(float)$purchasePrice);$gid=trim($_POST['gear_id']??'');$gname='';$initial=(float)$shoe['initial_km'];
 foreach($gears as $g)if($g['id']===$gid){$gname=$g['name'];if(isset($_POST['resync']))$initial=$g['distance']/1000;break;}
 if($brand===''||$model==='')$err='Brand and model are required.';else{$u=$pdo->prepare("UPDATE user_shoes SET custom_brand=?,custom_model=?,nickname=?,size=?,color=?,purchase_price=?,purchase_currency=?,target_km=?,strava_gear_id=?,strava_gear_name=?,initial_km=? WHERE id=? AND user_id=?");$u->execute([$brand,$model,$nick?:null,$size?:null,$color?:null,$purchasePrice,'THB',$target,$gid?:null,$gname?:null,$initial,$id,$userId]);
header('Location: shoe.php?id='.$id.'&saved=1');exit;}
}
?><!doctype html><html><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Edit Shoe — TOETAP</title><link rel="stylesheet" href="assets/v1.css"></head><body><main class="app"><div class="top"><a class="back" href="shoe.php?id=<?=$id?>">← Shoe</a><div class="brand">TOETAP<i></i></div></div><div class="kicker">SHOE SETTINGS</div><h1 class="pageTitle">Edit shoe.</h1>
<?php if($msg):?><div class="notice">✓ <?=htmlspecialchars($msg)?></div><?php endif;?><?php if($err):?><div class="notice warn"><?=htmlspecialchars($err)?></div><?php endif;?>
<div style="margin:0 0 14px"><a class="btn secondary" href="shoe_photo.php?id=<?=$id?>"><?=!empty($shoe['custom_photo_path'])?'CHANGE SHOE PHOTO':'＋ ADD SHOE PHOTO'?></a></div><form method="post" class="v1form"><?=csrfField()?><input type="hidden" name="id" value="<?=$id?>"><label>BRAND</label><input name="brand" value="<?=htmlspecialchars($shoe['custom_brand']??'')?>" required><label>MODEL</label><input name="model" value="<?=htmlspecialchars($shoe['custom_model']??'')?>" required><label>NICKNAME</label><input name="nickname" value="<?=htmlspecialchars($shoe['nickname']??'')?>"><div class="formrow"><div><label>SIZE</label><input name="size" value="<?=htmlspecialchars($shoe['size']??'')?>"></div><div><label>COLOR</label><input name="color" value="<?=htmlspecialchars($shoe['color']??'')?>"></div></div><div id="priceField"><label>PURCHASE PRICE <span class="tiny">OPTIONAL · THB</span></label><input id="purchasePrice" type="number" min="0" step=".01" name="purchase_price" value="<?=htmlspecialchars($shoe['purchase_price']??'')?>" placeholder="6990"></div>
<label>TARGET KM</label><input type="number" min="1" name="target_km" value="<?=htmlspecialchars($shoe['target_km'])?>">
<label>STRAVA GEAR</label><select name="gear_id" id="gear"><option value="">Not linked</option><?php foreach($gears as $g):?><option value="<?=htmlspecialchars($g['id'])?>" <?=$shoe['strava_gear_id']===$g['id']?'selected':''?>><?=htmlspecialchars($g['name'])?> · <?=number_format($g['distance']/1000,1)?> km</option><?php endforeach;?></select>

<div class="gearTools">
  <a class="btn secondary" href="https://www.strava.com/settings/gear" target="_blank" rel="noopener" class="stravaCreateGear">+ CREATE NEW GEAR ON STRAVA</a>
  <button class="btn secondary" type="button" id="refreshGear">↻ I'VE ADDED IT — REFRESH GEAR</button>
  <div class="tiny" id="gearRefreshMsg">Create the shoe in Strava, return here, then refresh. Your TOETAP shoe details stay on this page.</div>
</div>

<?php if($se):?><div class="tiny"><?=htmlspecialchars($se)?></div><?php else:?><label><input style="width:auto" type="checkbox" name="resync" value="1"> Re-sync current mileage from selected Strava gear</label><?php endif;?><button class="btn green">SAVE SHOE</button></form>
<script>
(function(){
 const select=document.getElementById('gear');
 const btn=document.getElementById('refreshGear');
 const msg=document.getElementById('gearRefreshMsg');
 if(!select||!btn)return;
 btn.addEventListener('click',async()=>{
   const previous=select.value;
   btn.disabled=true; msg.textContent='Refreshing Strava gear…';
   try{
     const r=await fetch('strava/gear_list.php',{credentials:'same-origin',cache:'no-store'});
     const j=await r.json();
     if(!r.ok||!j.ok)throw new Error(j.error||'Could not refresh Strava gear.');
     select.innerHTML='<option value="">Not now</option>'+j.gears.map(g =>
       `<option value="${escapeHtml(g.id)}">${escapeHtml(g.name)} · ${Number(g.distance_km).toFixed(1)} km</option>`
     ).join('');
     if(previous && [...select.options].some(o=>o.value===previous)) select.value=previous;
     msg.textContent=`✓ Refreshed — ${j.gears.length} Strava shoe${j.gears.length===1?'':'s'} found.`;
   }catch(e){msg.textContent='⚠ '+e.message;}
   finally{btn.disabled=false;}
 });
 function escapeHtml(v){return String(v).replace(/[&<>"']/g,c=>({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#039;'}[c]));}
})();
</script>
<?php v1nav('shoes');?></main>
<script>
(function(){
 const KEY='toetapAwaitingStravaGear';
 const links=document.querySelectorAll('.stravaCreateGear');
 const refreshBtn=document.getElementById('refreshGear');
 let returning=false;
 links.forEach(a=>a.addEventListener('click',()=>{try{sessionStorage.setItem(KEY,'1')}catch(e){}}));
 function refreshOnReturn(){
   let waiting=false;
   try{waiting=sessionStorage.getItem(KEY)==='1'}catch(e){}
   if(!waiting||returning||!refreshBtn)return;
   returning=true;
   try{sessionStorage.removeItem(KEY)}catch(e){}
   refreshBtn.click();
   setTimeout(()=>returning=false,1200);
 }
 document.addEventListener('visibilitychange',()=>{if(document.visibilityState==='visible')setTimeout(refreshOnReturn,350)});
 window.addEventListener('focus',()=>setTimeout(refreshOnReturn,350));
 window.addEventListener('pageshow',()=>setTimeout(refreshOnReturn,350));
})();
</script>

<?php if(($_GET['focus']??'')==='price'):?>
<script>
window.addEventListener('DOMContentLoaded',()=>{
 const f=document.getElementById('priceField'), i=document.getElementById('purchasePrice');
 if(f){f.scrollIntoView({behavior:'smooth',block:'center'});f.style.padding='12px';f.style.border='2px solid #c9ff54';f.style.borderRadius='14px';}
 if(i){setTimeout(()=>i.focus(),350);}
});
</script>
<?php endif;?></body></html>