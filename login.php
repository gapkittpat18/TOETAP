<?php
require __DIR__.'/auth/bootstrap.php';
if(currentUserId()){header('Location:index.php');exit;}
$err='';$next=safeNext($_GET['next']??$_POST['next']??'index.php');
if($_SERVER['REQUEST_METHOD']==='POST'){
 requireCsrf();
 $email=strtolower(trim($_POST['email']??''));$pw=$_POST['password']??'';
 $q=$pdo->prepare("SELECT id,password_hash FROM users WHERE LOWER(email)=? LIMIT 1");$q->execute([$email]);$u=$q->fetch();
 if($u && !empty($u['password_hash']) && password_verify($pw,$u['password_hash'])){loginUser((int)$u['id']);header('Location:'.$next);exit;}
 $err='Email or password is incorrect.';
}
?><!doctype html><html><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Sign in — TOETAP</title><link rel="stylesheet" href="assets/v1.css"></head><body><main class="app authPage">
<div class="brand" style="margin-top:22px">TOETAP<i></i></div><div class="onHero"><div class="kicker">WELCOME BACK</div><h1>Sign in.</h1><p>Your shoes, taps and runs stay tied to your TOETAP account.</p></div>
<?php if($err):?><div class="notice warn"><?=htmlspecialchars($err)?></div><?php endif;?>
<form method="post" class="v1form"><?=csrfField()?><input type="hidden" name="next" value="<?=htmlspecialchars($next)?>">
<label>EMAIL</label><input type="email" name="email" autocomplete="email" required>
<label>PASSWORD</label><input type="password" name="password" autocomplete="current-password" required>
<button class="btn green" type="submit">SIGN IN</button></form>
<p class="muted" style="text-align:center">New to TOETAP? <a href="register.php?next=<?=rawurlencode($next)?>">Create account</a></p></main></body></html>