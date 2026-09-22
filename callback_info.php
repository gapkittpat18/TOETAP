<?php
require __DIR__.'/../auth/bootstrap.php';
requireUser();
$cfg=require __DIR__.'/../config/strava.php';
$uri=$cfg['redirect_uri'];
$host=parse_url($uri,PHP_URL_HOST)?:'app.toetap.run';
?><!doctype html><html><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Strava Callback — TOETAP</title><link rel="stylesheet" href="../assets/v1.css"></head><body><main class="app"><div class="brand">TOETAP<i></i></div><div class="kicker">STRAVA SETUP</div><h1 class="pageTitle">Stable callback.</h1><div class="v1panel"><div class="tiny">REDIRECT URI</div><p style="word-break:break-all;font-weight:800"><?=htmlspecialchars($uri)?></p><div class="tiny">CALLBACK DOMAIN</div><p style="word-break:break-all;font-weight:800"><?=htmlspecialchars($host)?></p></div><a class="btn green" href="connect.php">CONNECT / REAUTHORIZE STRAVA</a></main></body></html>