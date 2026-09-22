<?php
require __DIR__.'/auth/bootstrap.php';require __DIR__.'/v1_nav.php';
$userId=requireAdmin();
$msg='';$err='';
if($_SERVER['REQUEST_METHOD']==='POST'){
 requireCsrf();
 try{
  $max=$pdo->query("SELECT MAX(CAST(SUBSTRING(tag_code,3) AS UNSIGNED)) FROM tags WHERE tag_code LIKE 'TS%'")->fetchColumn();
  $code='TS'.str_pad((string)(((int)$max)+1),6,'0',STR_PAD_LEFT);
  $token=bin2hex(random_bytes(24));
  $q=$pdo->prepare("INSERT INTO tags(tag_code,owner_user_id,public_token,user_shoe_id,activation_status,issued_by_tapsole,activated_at) VALUES(?,?,?,?,?,?,?)");
  $q->execute([$code,null,$token,null,'NEW',1,null]);
  $url='https://app.toetap.run/tapsole/tap.php?t='.rawurlencode($token);
  $msg=$code.'|'.$url;
 }catch(Throwable $e){$err=$e->getMessage();}
}
?><!doctype html><html><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Add Tag — TOETAP</title><link rel="stylesheet" href="assets/v1.css"></head><body><main class="app">
<div class="top"><a class="back" href="tags.php">← Tags</a><div class="brand">TOETAP<i></i></div></div><div class="kicker">ADMIN · TAG PROVISIONING</div><h1 class="pageTitle">Issue NFC Tag</h1>
<p class="muted">Create a factory-issued READY / UNCLAIMED TOETAP tag. Customers claim it later by tapping the permanent NFC URL.</p>
<?php if($err):?><div class="notice warn"><?=htmlspecialchars($err)?></div><?php endif;?>
<?php if($msg):[$code,$url]=explode('|',$msg,2);?><div class="hero"><div class="ready"><span class="dot"></span> TAG CREATED</div><h2><?=htmlspecialchars($code)?></h2><div class="sub">Write this URL to the NFC as a URL/URI record:</div><div class="notice" style="word-break:break-all;background:#252729;color:#fff"><?=htmlspecialchars($url)?></div></div><?php endif;?>
<form class="v1form" method="post"><?=csrfField()?>
<div class="notice"><b>FACTORY TAG · NO OWNER</b><br>Generating this URL does not claim the tag to your admin account. Owner and shoe stay empty until the customer taps and activates it.</div>
<button class="btn green" type="submit">ISSUE NEW TAG</button>
</form><?php v1nav('tags');?></main></body></html>