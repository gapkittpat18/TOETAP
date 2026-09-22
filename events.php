<?php
require __DIR__.'/../auth/bootstrap.php'; requireUser();
$rows=$pdo->query("SELECT * FROM webhook_events ORDER BY id DESC LIMIT 50")->fetchAll();
?><!doctype html><html><head><meta charset="utf-8"><meta http-equiv="refresh" content="10"><title>Webhook Events — TOETAP</title>
<style>body{background:#0b0b0d;color:#fff;font-family:Arial;padding:24px}table{width:100%;border-collapse:collapse;background:#17171b}th,td{padding:10px;border-bottom:1px solid #333;text-align:left;font-size:13px}.APPLIED{color:#9effb2}.ERROR{color:#ff9da6}.RECEIVED,.PROCESSING{color:#ffe89a}a{color:#fff}.pill{padding:4px 8px;border:1px solid #444;border-radius:20px}</style></head>
<body><h1>Webhook Events <span class="pill">AUTO</span></h1>
<p>Refreshes every 10 seconds. Real Strava CREATE events are processed automatically.</p>
<p><a href="process.php">PROCESS PENDING MANUALLY</a> · <a href="dev_simulator.php">DEV SIMULATOR</a> · <a href="setup.php">Setup</a></p>
<table><tr><th>ID</th><th>Provider</th><th>Received UTC</th><th>Type</th><th>Activity</th><th>Status</th><th>Message</th></tr>
<?php foreach($rows as $r):?><tr><td><?=$r['id']?></td><td><?=htmlspecialchars($r['provider'])?></td><td><?=htmlspecialchars($r['received_at'])?></td><td><?=htmlspecialchars($r['aspect_type'].' '.$r['object_type'])?></td><td><?=htmlspecialchars((string)$r['object_id'])?></td><td class="<?=htmlspecialchars($r['status'])?>"><?=htmlspecialchars($r['status'])?></td><td><?=htmlspecialchars($r['message']??'')?></td></tr><?php endforeach;?></table></body></html>
