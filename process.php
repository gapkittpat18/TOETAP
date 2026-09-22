<?php
require __DIR__.'/../config/database.php';
require __DIR__.'/processor.php';
header('Content-Type: application/json');

$events=$pdo->query("SELECT id FROM webhook_events WHERE status='RECEIVED' ORDER BY id ASC LIMIT 20")->fetchAll();
$out=[];
foreach($events as $e) $out[]=tapsoleProcessWebhookEvent($pdo,(int)$e['id']);
echo json_encode(['processed'=>$out],JSON_PRETTY_PRINT);
