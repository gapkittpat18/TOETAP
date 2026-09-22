<?php
require __DIR__.'/auth/bootstrap.php'; require __DIR__.'/v1_nav.php';
$id=(int)($_GET['shoe']??0);$tag=trim($_GET['tag']??'');
$s=$pdo->prepare("SELECT * FROM user_shoes WHERE id=?");$s->execute([$id]);$shoe=$s->fetch();if(!$shoe)exit('Shoe not found.');
$name=$shoe['nickname']?:trim($shoe['custom_brand'].' '.$shoe['custom_model']);
?>
<!doctype html><html><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Ready — TOETAP</title><link rel="stylesheet" href="assets/v1.css"></head><body>
<main class="app"><div class="top"><div class="brand">TOETAP<i></i></div></div>
<div class="stepbar"><i class="on"></i><i class="on"></i><i class="on"></i></div>
<div class="v1panel" style="text-align:center;margin-top:28px"><div class="success">✓</div><div class="kicker">SHOE ACTIVATED</div><h1 style="font-size:34px;margin:8px 0"><?=htmlspecialchars($name)?></h1>
<div class="heroShoeV1"><img src="assets/sneaker.png" alt="Sneaker"></div><p class="muted"><b><?=htmlspecialchars($tag)?></b> is ready. From now on, tap this NFC before your run. No confirmation screen needed.</p>
<?php if($shoe['strava_gear_name']):?><div class="notice">Connected to Strava · <?=htmlspecialchars($shoe['strava_gear_name'])?></div><?php endif;?>
<a class="btn" href="tap.php?tag=<?=urlencode($tag)?>">TEST TAP</a><a class="btn secondary" href="shoe.php?id=<?=$id?>">VIEW SHOE</a></div>
<?php v1nav('shoes');?></main></body></html>