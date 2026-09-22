<?php
require __DIR__.'/auth/bootstrap.php';
require __DIR__.'/v1_nav.php';
require_once __DIR__.'/strava/functions.php';

$tagCode=trim($_GET['tag']??$_POST['tag']??'');
$publicToken=trim($_GET['t']??$_POST['t']??'');
if($tagCode==='' && $publicToken==='') exit('Missing TOETAP Tag ID.');
if($publicToken!==''){
  $stmt=$pdo->prepare("SELECT * FROM tags WHERE public_token=? AND issued_by_tapsole=1 LIMIT 1");
  $stmt->execute([$publicToken]);
}else{
  $stmt=$pdo->prepare("SELECT * FROM tags WHERE tag_code=? AND issued_by_tapsole=1 LIMIT 1");
  $stmt->execute([$tagCode]);
}
$tag=$stmt->fetch();
if($tag) $tagCode=(string)$tag['tag_code'];
if(!$tag) exit('TOETAP tag not found.');
if($tag['activation_status']!=='NEW'){header('Location: tap.php?tag='.urlencode($tagCode));exit;}

// Factory NEW tags must be claimable by the customer who physically has the NFC.
// Do not block activation because an older/prototype record contains stale owner/shoe fields.
// The atomic claim below transfers this NEW tag to the activating customer.
$userId=requireUser();
$gears=[];$stravaMsg='';
try{
  $token=validToken($pdo,$userId);
  $ath=stravaHttp('GET','https://www.strava.com/api/v3/athlete',[],$token);
  foreach(($ath['shoes']??[]) as $g){
    $gears[]=['id'=>$g['id'],'name'=>$g['name']??$g['id'],'distance'=>(float)($g['distance']??0)];
  }
}catch(Throwable $e){$stravaMsg='Strava gear is unavailable right now. You can link it later.';}

$error='';
if($_SERVER['REQUEST_METHOD']==='POST'){
  requireCsrf();
  $libId=(int)($_POST['library_id']??0);
  $brand=trim($_POST['brand']??''); $model=trim($_POST['model']??'');
  $nickname=trim($_POST['nickname']??''); $size=trim($_POST['size']??''); $color=trim($_POST['color']??'');
  $gearId=trim($_POST['gear_id']??''); $gearName=''; $gearKm=0.0;
  $targetKm=max(1,(float)($_POST['target_km']??500));
  $purchasePrice=trim($_POST['purchase_price']??''); $purchasePrice=$purchasePrice===''?null:max(0,(float)$purchasePrice);

  if($libId){
    $l=$pdo->prepare("SELECT * FROM shoe_library WHERE id=? AND active=1");$l->execute([$libId]);$lib=$l->fetch();
    if($lib){$brand=$lib['brand'];$model=$lib['model'];if(empty($_POST['target_km']))$targetKm=(float)$lib['default_target_km'];}
  }
  foreach($gears as $g) if($g['id']===$gearId){$gearName=$g['name'];$gearKm=$g['distance']/1000;break;}
  $initialKm=$gearId ? $gearKm : max(0,(float)($_POST['initial_km']??0));

  if($brand===''||$model==='') $error='Please choose a shoe or enter brand and model.';
  else try{
    $pdo->beginTransaction();
    $ins=$pdo->prepare("INSERT INTO user_shoes(user_id,shoe_library_id,custom_brand,custom_model,nickname,size,color,purchase_price,purchase_currency,initial_km,target_km,strava_gear_id,strava_gear_name)
                        VALUES(?,?,?,?,?,?,?,?,?,?,?,?,?)");
    $ins->execute([$userId,$libId?:null,$brand,$model,$nickname?:null,$size?:null,$color?:null,$purchasePrice,'THB',$initialKm,$targetKm,$gearId?:null,$gearName?:null]);
    $shoeId=(int)$pdo->lastInsertId();
    $up=$pdo->prepare("UPDATE tags
        SET owner_user_id=?, user_shoe_id=?, activation_status='ACTIVE', activated_at=UTC_TIMESTAMP()
        WHERE id=? AND activation_status='NEW'");
    $up->execute([$userId,$shoeId,(int)$tag['id']]);
    if($up->rowCount()!==1) throw new Exception('Tag could not be activated.');
    $pdo->commit();
    header('Location: shoe_ready.php?shoe='.$shoeId.'&tag='.urlencode($tagCode));exit;
  }catch(Throwable $e){if($pdo->inTransaction())$pdo->rollBack();$error=$e->getMessage();}
}
?>
<!doctype html><html><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Add Shoe — TOETAP</title><link rel="stylesheet" href="assets/v1.css"></head>
<body><main class="app">
<div class="top"><a class="back" href="index.php">← Home</a><div class="brand">TOETAP<i></i></div></div>
<div class="stepbar"><i class="on"></i><i class="on"></i><i></i></div>
<section class="hero"><div class="kicker" style="color:#a9aca9"><?=htmlspecialchars($tagCode)?> · NEW TAG</div><h2 style="font-size:34px">Which shoe<br>is this?</h2><p>Search the TOETAP library. If it isn't there yet, just type it manually.</p></section>
<?php if($error):?><div class="notice warn"><?=htmlspecialchars($error)?></div><?php endif;?>
<form method="post" class="v1form" id="shoeForm"><?=csrfField()?>
<input type="hidden" name="tag" value="<?=htmlspecialchars($tagCode)?>"><input type="hidden" name="t" value="<?=htmlspecialchars($publicToken)?>">
<input type="hidden" name="library_id" id="library_id" value="">
<label>SEARCH SHOE</label>
<div class="searchbox"><input id="shoeSearch" autocomplete="off" placeholder="Try: Adios Pro 4 or Superblast"></div>
<div id="suggestions" class="suggestions"></div>

<div id="manualFields">
<label>BRAND</label><input id="brand" name="brand" placeholder="adidas">
<label>MODEL</label><input id="model" name="model" placeholder="Adizero Adios Pro 4">
</div>

<label>NICKNAME <span class="tiny">OPTIONAL</span></label><input name="nickname" placeholder="Race shoe">
<div class="formrow"><div><label>SIZE</label><input name="size" placeholder="US 8.5"></div><div><label>COLOR</label><input name="color" placeholder="White"></div></div>

<label>LINK STRAVA SHOE <span class="tiny">RECOMMENDED</span></label>
<select name="gear_id" id="gear">
<option value="">Not now</option>
<?php foreach($gears as $g):?><option value="<?=htmlspecialchars($g['id'])?>"><?=htmlspecialchars($g['name'])?> · <?=number_format($g['distance']/1000,1)?> km</option><?php endforeach;?>
</select>

<div class="gearTools">
  <a class="btn secondary" href="https://www.strava.com/settings/gear" target="_blank" rel="noopener" class="stravaCreateGear">+ CREATE NEW GEAR ON STRAVA</a>
  <button class="btn secondary" type="button" id="refreshGear">↻ I'VE ADDED IT — REFRESH GEAR</button>
  <div class="tiny" id="gearRefreshMsg">Create the shoe in Strava, return here, then refresh. Your TOETAP shoe details stay on this page.</div>
</div>

<?php if($stravaMsg):?><div class="tiny" style="margin-top:7px"><?=htmlspecialchars($stravaMsg)?></div><?php else:?><div class="tiny" style="margin-top:7px">If selected, TOETAP imports the current mileage from Strava automatically.</div><?php endif;?>

<label>PURCHASE PRICE <span class="tiny">OPTIONAL · THB</span></label><input type="number" min="0" step=".01" name="purchase_price" placeholder="6990">
<div class="formrow"><div><label>CURRENT KM</label><input type="number" min="0" step=".1" name="initial_km" value="0"></div><div><label>TARGET KM</label><input id="targetKm" type="number" min="1" name="target_km" value="500"></div></div>
<button class="btn green" type="submit">ACTIVATE SHOE</button>
</form>
<script>
const q=document.getElementById('shoeSearch'), box=document.getElementById('suggestions');
let timer;
q.addEventListener('input',()=>{clearTimeout(timer);document.getElementById('library_id').value='';timer=setTimeout(async()=>{
 const v=q.value.trim(); if(!v){box.innerHTML='';return;}
 const rows=await fetch('shoe_search.php?q='+encodeURIComponent(v)).then(r=>r.json());
 box.innerHTML=rows.map(x=>`<div class="suggest" data-x='${JSON.stringify(x).replace(/'/g,"&#39;")}'>
 <div class="brandmark">${x.brand.substring(0,2).toUpperCase()}</div><div><b>${x.brand} ${x.model}</b><span>${x.category||'Running shoe'} · target ${Number(x.default_target_km).toFixed(0)} km</span></div></div>`).join('');
 document.querySelectorAll('.suggest').forEach(el=>el.onclick=()=>{const x=JSON.parse(el.dataset.x);document.getElementById('library_id').value=x.id;document.getElementById('brand').value=x.brand;document.getElementById('model').value=x.model;document.getElementById('targetKm').value=x.default_target_km;q.value=x.brand+' '+x.model;box.innerHTML='';});
},220)});
</script>

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

</body></html>