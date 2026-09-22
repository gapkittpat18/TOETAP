<?php
require __DIR__.'/../auth/bootstrap.php';
require __DIR__.'/functions.php';
$userId=requireUser();

$token=validToken($pdo,$userId);
$msg=''; $err='';

if($_SERVER['REQUEST_METHOD']==='POST'){
    requireCsrf();
    $shoeId=(int)($_POST['shoe_id']??0);
    $gearId=trim($_POST['gear_id']??'');
    $gearName=trim($_POST['gear_name']??'');
    if($shoeId && $gearId){
        $s=$pdo->prepare("UPDATE user_shoes SET strava_gear_id=?,strava_gear_name=? WHERE id=? AND user_id=?");
        $s->execute([$gearId,$gearName ?: $gearId,$shoeId,$userId]);
        $msg='Gear linked.';
    }
}

$sq=$pdo->prepare("SELECT * FROM user_shoes WHERE user_id=? AND active=1 ORDER BY id");$sq->execute([$userId]);$shoes=$sq->fetchAll();

$athlete=stravaHttp('GET','https://www.strava.com/api/v3/athlete',[],$token);
$gears=[];

if(!isset($athlete['shoes']) || !is_array($athlete['shoes'])){
    $err='Strava returned only a SummaryAthlete. Reconnect once with profile:read_all permission.';
} else {
    foreach($athlete['shoes'] as $g){
        if(empty($g['id'])) continue;
        $gears[]=[
            'id'=>(string)$g['id'],
            'name'=>$g['name']??(string)$g['id'],
            'distance'=>(float)($g['distance']??0)
        ];
    }
    usort($gears,function($a,$b){ return strcasecmp($a['name'],$b['name']); });
}
?>
<!doctype html><html><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Link Gear — TOETAP</title>
<style>body{background:#0b0b0d;color:#fff;font-family:Arial;margin:0;padding:24px}.w{max-width:850px;margin:auto}.card{background:#17171b;border:1px solid #29292f;border-radius:20px;padding:22px;margin:14px 0}select,button{padding:13px;border-radius:10px;font-size:15px}select{width:100%;margin:7px 0 16px;background:#222;color:#fff;border:1px solid #444}button{background:#fff;color:#000;border:0;font-weight:bold}.muted{color:#999}.ok{color:#9effb2}a{color:#fff}</style></head>
<body><div class="w"><h1>Link Strava Gear</h1><p class="muted">Map each physical TOETAP shoe to its Strava gear.</p>
<?php if($msg):?><p class="ok">✓ <?=htmlspecialchars($msg)?></p><?php endif;?>
<?php if($err):?><p style="color:#ffe28a">⚠ <?=htmlspecialchars($err)?> <a href="connect.php"><b>RECONNECT STRAVA</b></a></p><?php endif;?>

<div class="card">
<a href="https://www.strava.com/settings/gear" target="_blank" rel="noopener" class="stravaCreateGear"><button type="button">+ CREATE NEW GEAR ON STRAVA</button></a>
<button type="button" id="refreshGear" style="margin-left:8px">↻ I'VE ADDED IT — REFRESH GEAR</button>
<p class="muted" id="gearRefreshMsg">Create the shoe in Strava, return here, then refresh.</p>
</div>

<?php foreach($shoes as $shoe):?><div class="card"><h2><?=htmlspecialchars($shoe['nickname'] ?: trim($shoe['custom_brand'].' '.$shoe['custom_model']))?></h2>
<p class="muted">Current: <?=htmlspecialchars($shoe['strava_gear_name'] ?: 'Not linked')?> <?=htmlspecialchars($shoe['strava_gear_id'] ?: '')?></p>
<form method="post"><?=csrfField()?><input type="hidden" name="shoe_id" value="<?=$shoe['id']?>">
<select name="gear_id" class="gearSelect" required><option value="">Choose Strava gear...</option>
<?php foreach($gears as $g):?><option value="<?=htmlspecialchars($g['id'])?>" <?=$shoe['strava_gear_id']===$g['id']?'selected':''?>><?=htmlspecialchars($g['name'])?> — <?=htmlspecialchars($g['id'])?> (<?=number_format($g['distance']/1000,1)?> km)</option><?php endforeach;?>
</select><input type="hidden" name="gear_name" id="gn<?=$shoe['id']?>"><button onclick="var s=this.form.gear_id;this.form.gear_name.value=s.options[s.selectedIndex].text.split(' — ')[0]">LINK GEAR</button></form></div><?php endforeach;?>
<p><a href="status.php">← Strava</a> · <a href="../index.php">TOETAP Home</a></p></div>
<script>
document.getElementById('refreshGear').addEventListener('click',async function(){
 const msg=document.getElementById('gearRefreshMsg');this.disabled=true;msg.textContent='Refreshing Strava gear…';
 try{
  const r=await fetch('gear_list.php',{credentials:'same-origin',cache:'no-store'}),j=await r.json();
  if(!r.ok||!j.ok)throw new Error(j.error||'Could not refresh.');
  document.querySelectorAll('.gearSelect').forEach(sel=>{
   const old=sel.value;
   sel.innerHTML='<option value="">Choose Strava gear...</option>'+j.gears.map(g=>`<option value="${eh(g.id)}">${eh(g.name)} — ${eh(g.id)} (${Number(g.distance_km).toFixed(1)} km)</option>`).join('');
   if(old&&[...sel.options].some(o=>o.value===old))sel.value=old;
  });
  msg.textContent='✓ Refreshed — '+j.gears.length+' Strava shoes found.';
 }catch(e){msg.textContent='⚠ '+e.message;}finally{this.disabled=false;}
});
function eh(v){return String(v).replace(/[&<>"']/g,c=>({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#039;'}[c]));}
</script>

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
