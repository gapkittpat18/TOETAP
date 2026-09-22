<?php
require __DIR__.'/auth/bootstrap.php';require __DIR__.'/v1_nav.php';
$userId=requireUser();$id=(int)($_GET['id']??$_POST['id']??0);
$q=$pdo->prepare("SELECT id,nickname,custom_brand,custom_model,custom_photo_path,original_photo_path FROM user_shoes WHERE id=? AND user_id=?");
$q->execute([$id,$userId]);$shoe=$q->fetch();if(!$shoe)exit('Shoe not found.');
$err='';
function ttPhotoUrl($p){$p=(string)$p;return ($p!==''&&preg_match('~^uploads/shoes/[A-Za-z0-9._-]+$~',$p))?$p:'';}
function ttDeletePhotoFile($p){$p=ttPhotoUrl($p);if($p==='')return;$root=realpath(__DIR__.'/uploads/shoes');$f=realpath(__DIR__.'/'.$p);if($root&&$f&&str_starts_with($f,$root.DIRECTORY_SEPARATOR)&&is_file($f))@unlink($f);}
if($_SERVER['REQUEST_METHOD']==='POST'){
 requireCsrf();$action=$_POST['action']??'upload';
 if($action==='remove'){
  $pdo->prepare("UPDATE user_shoes SET custom_photo_path=NULL, original_photo_path=NULL WHERE id=? AND user_id=?")->execute([$id,$userId]);
  ttDeletePhotoFile($shoe['custom_photo_path']??'');ttDeletePhotoFile($shoe['original_photo_path']??'');header('Location: shoe_photo.php?id='.$id.'&removed=1');exit;
 }
 if(!isset($_FILES['shoe_photo'])||$_FILES['shoe_photo']['error']!==UPLOAD_ERR_OK)$err='Choose or take a shoe photo first.';
 elseif($_FILES['shoe_photo']['size']>10*1024*1024)$err='Photo is too large. Maximum 10 MB.';
 else{
  $tmp=$_FILES['shoe_photo']['tmp_name'];$info=@getimagesize($tmp);$mime=$info['mime']??'';
  $allowed=['image/jpeg'=>'jpg','image/png'=>'png','image/webp'=>'webp'];
  if(!$info||!isset($allowed[$mime]))$err='Use a JPG, PNG or WebP image.';
  elseif(($info[0]??0)<100||($info[1]??0)<100||($info[0]??0)>12000||($info[1]??0)>12000)$err='Photo dimensions are not supported.';
  else{
   $dir=__DIR__.'/uploads/shoes';if(!is_dir($dir)&&!mkdir($dir,0755,true))$err='Could not create photo storage.';
   if($err===''){
    $name='shoe_'.$userId.'_'.$id.'_'.bin2hex(random_bytes(8)).'.'.$allowed[$mime];$dest=$dir.'/'.$name;
    if(!move_uploaded_file($tmp,$dest))$err='Could not save the photo.';
    else{
     $path='uploads/shoes/'.$name;$originalPath=null;
     if(isset($_FILES['shoe_photo_original'])&&$_FILES['shoe_photo_original']['error']===UPLOAD_ERR_OK&&$_FILES['shoe_photo_original']['size']<=10*1024*1024){
      $otmp=$_FILES['shoe_photo_original']['tmp_name'];$oi=@getimagesize($otmp);$om=$oi['mime']??'';
      if($oi&&isset($allowed[$om])){
       $on='shoe_original_'.$userId.'_'.$id.'_'.bin2hex(random_bytes(8)).'.'.$allowed[$om];
       if(move_uploaded_file($otmp,$dir.'/'.$on))$originalPath='uploads/shoes/'.$on;
      }
     }
     if(!$originalPath)$originalPath=$path;
     $pdo->prepare("UPDATE user_shoes SET custom_photo_path=?, original_photo_path=? WHERE id=? AND user_id=?")->execute([$path,$originalPath,$id,$userId]);
     ttDeletePhotoFile($shoe['custom_photo_path']??'');
     if(($shoe['original_photo_path']??'')!==($shoe['custom_photo_path']??''))ttDeletePhotoFile($shoe['original_photo_path']??'');
     header('Location: shoe_photo.php?id='.$id.'&saved=1');exit;
    }
   }
  }
 }
}
$photo=ttPhotoUrl($shoe['custom_photo_path']??'');$name=$shoe['nickname']?:trim(($shoe['custom_brand']??'').' '.($shoe['custom_model']??''));
?><!doctype html><html><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Shoe Photo — TOETAP</title><link rel="stylesheet" href="assets/v1.css">
<style>
.photoStage{background:#111;border-radius:26px;min-height:290px;display:flex;align-items:center;justify-content:center;overflow:hidden;margin:12px 0 16px;padding:20px}.photoStage img{width:100%;height:260px;object-fit:contain}.photoEmpty{text-align:center;color:#fff}.photoEmpty b{display:block;font-size:48px;margin-bottom:10px}.photoEmpty span{font-size:11px;color:#999}.photoPick{display:grid;grid-template-columns:1fr 1fr;gap:10px;margin:12px 0}.photoPick label{display:flex;align-items:center;justify-content:center;min-height:52px;border-radius:15px;background:#111;color:#fff;font-size:11px;font-weight:900;text-align:center;cursor:pointer;padding:10px}.photoPick label:last-child{background:#c9ff54;color:#111}.photoPick input{position:absolute;opacity:0;pointer-events:none}.previewNote{font-size:10px;line-height:1.5;color:#777;margin:10px 2px 18px}.removePhoto{width:100%;border:0;background:transparent;color:#a22;font-weight:900;font-size:10px;padding:14px}
.aiBox{display:none;margin:12px 0;padding:14px;border-radius:16px;background:#eef0e9}.aiBox.show{display:block}.aiTop{display:flex;justify-content:space-between;font-size:10px;font-weight:900}.aiBar{height:6px;background:#d8dbd2;border-radius:99px;overflow:hidden;margin-top:9px}.aiBar i{display:block;height:100%;width:5%;background:#111;border-radius:99px;transition:width .2s}.choice{display:none;grid-template-columns:1fr 1fr;gap:8px;margin:10px 0}.choice.show{display:grid}.choice button{border:1px solid #ccc;background:#fff;border-radius:12px;padding:11px;font-size:10px;font-weight:900}.choice button.active{background:#111;color:#fff;border-color:#111}
</style></head><body><main class="app"><div class="top"><a class="back" href="shoe.php?id=<?=$id?>">← Shoe</a><div class="brand">TOETAP<i></i></div></div><div class="kicker">CUSTOM SHOE PHOTO</div><h1 class="pageTitle">Make it yours.</h1><div class="muted"><?=htmlspecialchars($name)?></div>
<?php if(isset($_GET['saved'])):?><div class="notice">✓ Shoe photo updated.</div><?php endif;?><?php if(isset($_GET['removed'])):?><div class="notice">Photo removed.</div><?php endif;?><?php if($err):?><div class="notice warn"><?=htmlspecialchars($err)?></div><?php endif;?>
<div class="photoStage"><?php if($photo):?><img id="photoPreview" src="<?=htmlspecialchars($photo)?>?v=<?=time()?>" alt="Your shoe"><?php else:?><div class="photoEmpty" id="photoEmpty"><b>＋</b><span>TAKE OR UPLOAD A SHOE PHOTO</span></div><img id="photoPreview" style="display:none" alt="Preview"><?php endif;?></div>
<form method="post" enctype="multipart/form-data" id="photoForm"><?=csrfField()?><input type="hidden" name="id" value="<?=$id?>"><input type="hidden" name="action" value="upload">
<div class="photoPick"><label>PHOTO LIBRARY<input id="libraryInput" type="file" name="shoe_photo" accept="image/jpeg,image/png,image/webp"></label><label>TAKE PHOTO<input id="cameraInput" type="file" accept="image/*" capture="environment"></label></div>
<input id="originalInput" type="file" name="shoe_photo_original" style="display:none">
<div class="aiBox" id="aiBox"><div class="aiTop"><span id="aiText">REMOVING BACKGROUND</span><span id="aiPct">0%</span></div><div class="aiBar"><i id="aiBar"></i></div></div>
<div class="choice" id="choice"><button type="button" id="processedBtn" class="active">BACKGROUND REMOVED</button><button type="button" id="originalBtn">ORIGINAL</button></div>
<button class="btn green" id="savePhoto" type="button" disabled>USE THIS PHOTO</button></form>
<?php if($photo):?><form method="post" onsubmit="return confirm('Remove this custom shoe photo?')"><?=csrfField()?><input type="hidden" name="id" value="<?=$id?>"><input type="hidden" name="action" value="remove"><button class="removePhoto">REMOVE PHOTO</button></form><?php endif;?>
<p class="previewNote">JPG, PNG or WebP · max 10 MB. Background removal runs on your device; your original is kept so you can switch back if needed.</p><?php v1nav('shoes');?></main>
<script type="module">
const form=document.getElementById('photoForm'),lib=document.getElementById('libraryInput'),cam=document.getElementById('cameraInput'),preview=document.getElementById('photoPreview'),empty=document.getElementById('photoEmpty'),save=document.getElementById('savePhoto');
const originalInput=document.getElementById('originalInput'),aiBox=document.getElementById('aiBox'),aiText=document.getElementById('aiText'),aiPct=document.getElementById('aiPct'),aiBar=document.getElementById('aiBar'),choice=document.getElementById('choice'),processedBtn=document.getElementById('processedBtn'),originalBtn=document.getElementById('originalBtn');
let original=null,processed=null,selected=null,previewUrl=null;
function setFile(input,file){const dt=new DataTransfer();dt.items.add(file);input.files=dt.files;}
function showFile(file){if(previewUrl)URL.revokeObjectURL(previewUrl);previewUrl=URL.createObjectURL(file);preview.src=previewUrl;preview.style.display='block';if(empty)empty.style.display='none';}
function select(which){
 selected=(which==='processed'&&processed)?processed:original;
 showFile(selected);processedBtn.classList.toggle('active',selected===processed);originalBtn.classList.toggle('active',selected===original);save.disabled=!selected;
}
async function cropTransparentPng(blob,padRatio=0.045){
 const bmp=await createImageBitmap(blob);
 const c=document.createElement('canvas');c.width=bmp.width;c.height=bmp.height;
 const ctx=c.getContext('2d',{willReadFrequently:true});ctx.drawImage(bmp,0,0);
 const d=ctx.getImageData(0,0,c.width,c.height).data;
 let minX=c.width,minY=c.height,maxX=-1,maxY=-1;
 for(let y=0;y<c.height;y++){for(let x=0;x<c.width;x++){
   if(d[(y*c.width+x)*4+3]>12){if(x<minX)minX=x;if(x>maxX)maxX=x;if(y<minY)minY=y;if(y>maxY)maxY=y;}
 }}
 if(maxX<minX||maxY<minY)return blob;
 const w=maxX-minX+1,h=maxY-minY+1,pad=Math.round(Math.max(w,h)*padRatio);
 const sx=Math.max(0,minX-pad),sy=Math.max(0,minY-pad),ex=Math.min(c.width,maxX+pad+1),ey=Math.min(c.height,maxY+pad+1);
 const out=document.createElement('canvas');out.width=ex-sx;out.height=ey-sy;
 out.getContext('2d').drawImage(c,sx,sy,out.width,out.height,0,0,out.width,out.height);
 return await new Promise((resolve,reject)=>out.toBlob(b=>b?resolve(b):reject(new Error('Crop failed')),'image/png',1));
}
async function process(input){
 if(!input.files||!input.files[0])return;
 original=input.files[0];processed=null;selected=original;setFile(originalInput,original);showFile(original);
 save.disabled=true;choice.classList.remove('show');aiBox.classList.add('show');aiText.textContent='PREPARING AI';aiPct.textContent='0%';aiBar.style.width='5%';
 try{
   const mod=await import('https://esm.sh/@imgly/background-removal@1.5.6');
   aiText.textContent='REMOVING BACKGROUND';
   const blob=await mod.removeBackground(original,{
     model:'isnet_quint8',
     output:{format:'image/png',quality:1},
     progress:(key,current,total)=>{
       const pct=Math.max(5,Math.min(99,Math.round((current/Math.max(total,1))*100)));
       aiPct.textContent=pct+'%';aiBar.style.width=pct+'%';
     }
   });
   // Trim transparent margin left by the segmentation model, then add a small safe pad.
   const croppedBlob=await cropTransparentPng(blob,0.045);
   processed=new File([croppedBlob],'toetap-shoe.png',{type:'image/png'});
   aiPct.textContent='100%';aiBar.style.width='100%';aiText.textContent='BACKGROUND REMOVED + CROPPED';
   choice.classList.add('show');select('processed');
   setTimeout(()=>aiBox.classList.remove('show'),650);
 }catch(err){
   console.error('TOETAP background removal:',err);
   aiText.textContent='AI UNAVAILABLE — ORIGINAL READY';aiPct.textContent='';aiBar.style.width='100%';
   choice.classList.add('show');processedBtn.disabled=true;select('original');
 }
}
lib.addEventListener('change',()=>process(lib));cam.addEventListener('change',()=>process(cam));
processedBtn.addEventListener('click',()=>select('processed'));originalBtn.addEventListener('click',()=>select('original'));
save.addEventListener('click',()=>{
 if(!selected)return;
 setFile(lib,selected);cam.disabled=true;
 form.submit();
});
</script></body></html>
