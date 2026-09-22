<?php require __DIR__.'/auth/bootstrap.php';require __DIR__.'/v1_nav.php';$userId=requireUser();
$u=$pdo->prepare("SELECT email,is_admin FROM users WHERE id=? LIMIT 1");$u->execute([$userId]);$account=$u->fetch()?:[];
$c=$pdo->prepare("SELECT athlete_id FROM strava_connections WHERE user_id=? LIMIT 1");$c->execute([$userId]);$ath=$c->fetchColumn();?><!doctype html><html><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Settings — TOETAP</title><link rel="stylesheet" href="assets/v1.css"><style>
.logoutBtn{margin-top:10px;background:transparent!important;color:#111!important;border:1px solid #d8d8d2!important}
</style></head><body><main class="app">
<div class="top"><a class="back" href="index.php">← Home</a><div class="brand">TOETAP<i></i></div></div><div class="kicker">ACCOUNT</div><h1 class="pageTitle">Settings</h1><div class="v1panel"><div class="kv"><span>Email</span><b><?=htmlspecialchars($account['email']??'')?></b></div><div class="kv"><span>Account</span><b><?=!empty($account['is_admin'])?'ADMIN':'USER'?></b></div></div>
<div class="v1panel"><div class="kv"><span>Strava</span><b><?=$ath?'Connected':'Optional · Not connected'?></b></div><div class="kv"><span>Matching</span><b>Latest Tap Wins</b></div><div class="kv"><span>Tap window</span><b>6 hours</b></div><div class="kv"><span>Activities</span><b>Run / Trail / Virtual</b></div></div>
<div class="v1panel"><div class="kicker">ABOUT THE TAG</div><p class="muted">TOETAP NFC selects a shoe. It has no GPS, battery or location tracking. Your activity comes from Strava.</p></div>
<a class="btn" href="strava_v1.php">STRAVA (OPTIONAL)</a>
<form method="post" action="logout.php" style="margin:0"><?=csrfField()?><button class="btn logoutBtn" type="submit">LOG OUT</button></form>
<?php v1nav('home');?></main></body></html>