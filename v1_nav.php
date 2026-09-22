<?php
function toetapLoadingUi(){ static $done=false;if($done)return;$done=true; ?>
<style id="toetapLoaderCritical">
.toetapLoader{position:fixed!important;inset:0!important;z-index:2147483647!important;background:rgba(244,244,238,.96)!important;display:flex!important;align-items:center!important;justify-content:center!important;padding:24px!important;opacity:0;visibility:hidden;pointer-events:none;transition:opacity .12s ease,visibility .12s ease}
.toetapLoader.show{opacity:1!important;visibility:visible!important;pointer-events:auto!important}
.toetapLoaderCard{text-align:center;min-width:200px;color:#111}.toetapLoaderBrand{font-size:18px;font-weight:900;letter-spacing:4px}.toetapLoaderBrand i{display:inline-block;width:7px;height:7px;border-radius:50%;background:#c9ff54;margin-left:3px}
.toetapLoaderRing{width:48px;height:48px;border:5px solid #d7d7d0;border-top-color:#111;border-radius:50%;margin:25px auto 16px;animation:toetapSpin .72s linear infinite}
.toetapLoaderCard b,.toetapLoaderCard span{display:block}.toetapLoaderCard b{font-size:11px;letter-spacing:1.6px}.toetapLoaderCard span{font-size:10px;color:#777;margin-top:6px}
@keyframes toetapSpin{to{transform:rotate(360deg)}}
</style>
<div id="toetapLoader" class="toetapLoader" aria-hidden="true">
 <div class="toetapLoaderCard">
  <div class="toetapLoaderBrand">TOETAP<i></i></div>
  <div class="toetapLoaderRing"></div>
  <b id="toetapLoaderText">LOADING</b>
  <span>Getting things ready…</span>
 </div>
</div>
<script>
(function(){
 const el=document.getElementById('toetapLoader'),txt=document.getElementById('toetapLoaderText');
 if(!el)return;
 let timer=null;
 function show(label){
   clearTimeout(timer);
   if(label)txt.textContent=label;
   // Only show if the action is still waiting after 350 ms.
   // Fast pages finish before this, so users never see a pointless flash.
   timer=setTimeout(()=>{
     el.classList.add('show');
     el.setAttribute('aria-hidden','false');
   },350);
 }
 function hide(){clearTimeout(timer);el.classList.remove('show');el.setAttribute('aria-hidden','true');txt.textContent='LOADING';}
 document.addEventListener('submit',e=>{
   const f=e.target;if(!(f instanceof HTMLFormElement))return;
   if(f.dataset.noLoader==='1')return;
   show((f.dataset.loadingText||'SAVING').toUpperCase());
 });
 document.addEventListener('click',e=>{
   const a=e.target.closest('a[href]');if(!a)return;
   if(a.dataset.noLoader==='1'||a.target==='_blank'||a.hasAttribute('download'))return;
   const href=a.getAttribute('href')||'';
   if(!href||href[0]==='#'||href.startsWith('javascript:')||href.startsWith('mailto:')||href.startsWith('tel:'))return;
   try{
     const u=new URL(a.href,location.href);
     if(u.origin!==location.origin)return;
   }catch(_){return;}
   // Do not delay navigation just to display a loader.
   // Fast pages navigate normally; the loader only appears if the browser is still here after 350 ms.
   show((a.dataset.loadingText||'LOADING').toUpperCase());
 });
 window.addEventListener('pageshow',hide);

 window.toetapLoading={show,hide};
})();
</script>
<?php }
function v1nav($active='home'){toetapLoadingUi();?><nav class="nav">
<a class="<?=$active==='home'?'active':''?>" href="index.php"><b>⌂</b>HOME</a>
<a class="<?=$active==='shoes'?'active':''?>" href="shoes.php"><b>◒</b>SHOES</a>
<a class="<?=$active==='insights'?'active':''?>" href="analytics.php"><b>⌁</b>INSIGHTS</a>
<a class="<?=$active==='tags'?'active':''?>" href="tags.php"><b>◇</b>TAGS</a>
</nav><?php }?>