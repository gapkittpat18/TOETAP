<?php
require __DIR__.'/auth/bootstrap.php';
if(currentUserId()){header('Location:index.php');exit;}
$err='';$next=safeNext($_GET['next']??$_POST['next']??'');
if($_SERVER['REQUEST_METHOD']==='POST'){
 requireCsrf();
 $name=trim($_POST['name']??'');$email=strtolower(trim($_POST['email']??''));$pw=$_POST['password']??'';
 try{
  if($name===''||!filter_var($email,FILTER_VALIDATE_EMAIL)||strlen($pw)<8) throw new Exception('Enter your name, a valid email and a password of at least 8 characters.');
  $q=$pdo->prepare("SELECT COUNT(*) FROM users WHERE LOWER(email)=?");$q->execute([$email]);if($q->fetchColumn())throw new Exception('This email is already registered.');
  $hash=password_hash($pw,PASSWORD_DEFAULT);
  $q=$pdo->prepare("INSERT INTO users(name,email,password_hash,created_at) VALUES(?,?,?,UTC_TIMESTAMP())");$q->execute([$name,$email,$hash]);
  loginUser((int)$pdo->lastInsertId());header('Location:'.($next!==''?$next:'onboarding.php'));exit;
 }catch(Throwable $e){$err=$e->getMessage();}
}
?><!doctype html><html><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Create account — TOETAP</title><link rel="stylesheet" href="assets/v1.css"></head><body><main class="app authPage">
<div class="brand" style="margin-top:22px">TOETAP<i></i></div><div class="onHero"><div class="kicker">START RUNNING</div><h1>Create account.</h1><p>One account owns your shoes, NFC tags, selections and activity history.</p></div>
<?php if($err):?><div class="notice warn"><?=htmlspecialchars($err)?></div><?php endif;?>
<form method="post" class="v1form"><?=csrfField()?><input type="hidden" name="next" value="<?=htmlspecialchars($next)?>"><label>NAME</label><input name="name" autocomplete="name" required>
<label>EMAIL</label><input type="email" name="email" autocomplete="email" required>
<label>PASSWORD</label><input type="password" name="password" minlength="8" autocomplete="new-password" required>
<button class="btn green" type="submit">CREATE ACCOUNT</button></form>
<p class="muted" style="text-align:center">Already have an account? <a href="login.php<?= $next!=='' ? '?next='.rawurlencode($next) : '' ?>">Sign in</a></p></main></body></html>