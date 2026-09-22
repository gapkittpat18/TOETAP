<?php
require __DIR__.'/auth/bootstrap.php';require __DIR__.'/v1_nav.php';
$userId=requireUser();
$tagId=(int)($_GET['id']??$_POST['id']??0);
$q=$pdo->prepare("SELECT t.* FROM tags t WHERE t.id=? AND t.owner_user_id=? AND t.issued_by_tapsole=1 LIMIT 1");
$q->execute([$tagId,$userId]);$tag=$q->fetch();if(!$tag)exit('Tag not found or not owned by this account.');
$err='';
if($_SERVER['REQUEST_METHOD']==='POST'){
 requireCsrf();$shoeId=(int)($_POST['shoe_id']??0);
 $o=$pdo->prepare("SELECT id FROM user_shoes WHERE id=? AND user_id=? AND active=1 LIMIT 1");$o->execute([$shoeId,$userId]);
 if(!$o->fetchColumn())$err='Please choose one of your active shoes.';
 else{
  $u=$pdo->prepare("UPDATE tags SET user_shoe_id=?,activation_status='ACTIVE',activated_at=UTC_TIMESTAMP() WHERE id=? AND owner_user_id=?");
  $u->execute([$shoeId,$tagId,$userId]);
  // Make the corrected shoe the current Latest Tap selection immediately.
  $old=$pdo->prepare("UPDATE shoe_selections SET used=1 WHERE user_id=? AND used=0");$old->execute([$userId]);
  $sel=$pdo->prepare("INSERT INTO shoe_selections(user_id,user_shoe_id,tag_id,selected_at,used) VALUES(?,?,?,UTC_TIMESTAMP(),0)");
  $sel->execute([$userId,$shoeId,$tagId]);
  $target=!empty($tag['public_token'])?'tap.php?t='.rawurlencode($tag['public_token']):'tap.php?tag='.rawurlencode($tag['tag_code']);
  header('Location: '.$target.'&changed=1');exit;
 }
}
$sq=$pdo->prepare("SELECT id,nickname,custom_brand,custom_model FROM user_shoes WHERE user_id=? AND active=1 ORDER BY id DESC");$sq->execute([$userId]);$shoes=$sq->fetchAll();
?><!doctype html><html><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Change Shoe — TOETAP</title><link rel="stylesheet" href="assets/v1.css"></head><body><main class="app"><div class="top"><a class="back" href="javascript:history.back()">← Back</a><div class="brand">TOETAP<i></i></div></div><div class="kicker">TAG <?=htmlspecialchars($tag['tag_code'])?></div><h1 class="pageTitle">Change shoe.</h1><p>Choose the shoe this NFC tag should select.</p>
<?php if($err):?><div class="notice warn"><?=htmlspecialchars($err)?></div><?php endif;?>
<form method="post" class="v1form"><?=csrfField()?><input type="hidden" name="id" value="<?=$tagId?>"><label>SHOE</label><select name="shoe_id" required><?php foreach($shoes as $s):$n=$s['nickname']?:trim(($s['custom_brand']??'').' '.($s['custom_model']??''));?><option value="<?=$s['id']?>" <?=$tag['user_shoe_id']==$s['id']?'selected':''?>><?=htmlspecialchars($n)?></option><?php endforeach;?></select><button class="btn green">SAVE & SELECT SHOE</button></form><?php v1nav('shoes');?></main></body></html>