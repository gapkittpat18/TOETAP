<?php
require __DIR__.'/auth/bootstrap.php';
$userId=requireUser();
if(isset($_GET['skip'])){
 setcookie('toetap_onboarded','1',time()+31536000,'/');
 header('Location:index.php');exit;
}
?><!doctype html><html><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>Welcome — TOETAP</title><link rel="stylesheet" href="assets/v1.css"></head><body><main class="app onboarding">
<div class="brand" style="margin-top:18px">TOETAP<i></i></div>
<div class="onHero"><div class="kicker">TAP · RUN · DONE</div><h1>Run with your shoes.<br>Not your settings.</h1>
<p>TOETAP remembers the shoe you tap before a run. Connect Strava for automatic activity sync, or use TOETAP without it.</p></div>

<div class="choice primaryChoice">
<div class="choiceTop"><span class="pill">RECOMMENDED</span><b>Connect Strava</b></div>
<p>Automatic runs, pace, distance, shoe mileage, Insights and automatic gear matching.</p>
<a class="btn green" href="strava/connect.php">CONNECT STRAVA</a>
</div>

<div class="choice">
<div class="choiceTop"><b>Use without Strava</b></div>
<p>Add shoes, register NFC tags, tap to select a shoe and log runs manually. You can connect Strava later anytime.</p>
<a class="btn secondary" href="onboarding.php?skip=1">CONTINUE WITHOUT STRAVA</a>
</div>
<div class="tiny" style="text-align:center;margin-top:18px">Strava is optional. TOETAP does not require a Strava account.</div>
</main></body></html>