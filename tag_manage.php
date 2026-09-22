<?php
require __DIR__.'/auth/bootstrap.php';require __DIR__.'/v1_nav.php';$userId=requireUser();$id=(int)($_GET['id']??$_POST['id']??0);
$q=$pdo->prepare("SELECT * FROM tags WHERE id=? AND owner_user_id=?");$q->execute([$id,$userId]);$tag=$q->fetch();if(!$tag)exit('Tag not found.');$msg='';$err='';
if($_SERVER['REQUEST_METHOD']==='POST'){
 requireCsrf();
 $action=(string)($_POST['action']??'change_shoe');
 if($action==='release'){
  try{
   $pdo->beginTransaction();
   // Lock and re-check ownership so only the current owner can release this physical tag.
   $lock=$pdo->prepare("SELECT id,user_shoe_id FROM tags WHERE id=? AND owner_user_id=? AND issued_by_tapsole=1 FOR UPDATE");
   $lock->execute([$id,$userId]);$owned=$lock->fetch();
   if(!$owned) throw new Exception('Tag not found or no longer owned by this account.');

   // Supersede any still-pending selection made by this tag before ownership is removed.
   $old=$pdo->prepare("UPDATE shoe_selections SET used=1 WHERE tag_id=? AND user_id=? AND used=0");
   $old->execute([$id,$userId]);

   // Keep tag_code + public_token unchanged: the physical NFC never needs rewriting.
   $rel=$pdo->prepare("UPDATE tags SET owner_user_id=NULL,user_shoe_id=NULL,activation_status='NEW',activated_at=NULL WHERE id=? AND owner_user_id=?");
   $rel->execute([$id,$userId]);
   if($rel->rowCount()!==1) throw new Exception('Tag could not be released.');

   $pdo->commit();
   header('Location: tags.php?released=1');exit;
  }catch(Throwable $e){
   if($pdo->inTransaction())$pdo->rollBack();
   $err=$e->getMessage();
  }
 }else{
  $sid=(int)($_POST['shoe_id']??0);
  $o=$pdo->prepare("SELECT id FROM user_shoes WHERE id=? AND user_id=? AND active=1");$o->execute([$sid,$userId]);
  if(!$o->fetchColumn())$err='Invalid shoe.';
  else{
   $u=$pdo->prepare("UPDATE tags SET user_shoe_id=?,activation_status='ACTIVE',activated_at=UTC_TIMESTAMP() WHERE id=? AND owner_user_id=?");
   $u->execute([$sid,$id,$userId]);$tag['user_shoe_id']=$sid;$msg='Tag now selects the new shoe.';
  }
 }
}
$sq=$pdo->prepare("SELECT id,nickname,custom_brand,custom_model FROM user_shoes WHERE user_id=? AND active=1 ORDER BY id DESC");$sq->execute([$userId]);$shoes=$sq->fetchAll();
?><!doctype html><html><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Manage Tag — TOETAP</title><link rel="stylesheet" href="assets/v1.css"></head><body><main class="app"><div class="top"><a class="back" href="tags.php">← Tags</a><div class="brand">TOETAP<i></i></div></div><div class="kicker"><?=htmlspecialchars($tag['tag_code'])?></div><h1 class="pageTitle">Change shoe.</h1><?php if($msg):?><div class="notice">✓ <?=$msg?></div><?php endif;?><?php if($err):?><div class="notice warn"><?=$err?></div><?php endif;?>
<form method="post" class="v1form"><?=csrfField()?><input type="hidden" name="id" value="<?=$id?>"><input type="hidden" name="action" value="change_shoe"><label>THIS TAG SELECTS</label><select name="shoe_id"><?php foreach($shoes as $s):$n=$s['nickname']?:trim($s['custom_brand'].' '.$s['custom_model']);?><option value="<?=$s['id']?>" <?=$tag['user_shoe_id']==$s['id']?'selected':''?>><?=htmlspecialchars($n)?></option><?php endforeach;?></select><button class="btn green">SAVE TAG SHOE</button></form>
<div class="v1panel"><div class="tiny">OPAQUE NFC URL</div><p style="word-break:break-all">https://app.toetap.run/tapsole/tap.php?t=<?=htmlspecialchars($tag['public_token'])?></p></div>
<div class="v1panel">
 <div class="kicker">TRANSFER / SECOND-HAND</div>
 <p class="muted">Release this physical tag before selling or giving it to someone else. Your shoe and run history stay in your account. The NFC URL stays the same.</p>
 <form method="post" onsubmit="return confirm('Release this TOETAP tag? It will become unclaimed and the next owner can activate it.')">
  <?=csrfField()?><input type="hidden" name="id" value="<?=$id?>"><input type="hidden" name="action" value="release">
  <button class="btn" type="submit">RELEASE TAG</button>
 </form>
</div><?php v1nav('tags');?></main></body></html>