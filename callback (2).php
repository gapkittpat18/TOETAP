<?php
require __DIR__.'/../config/database.php';
require __DIR__.'/processor.php';

if($_SERVER['REQUEST_METHOD']==='GET'){
    $expected=(string)$pdo->query("SELECT verify_token FROM webhook_settings WHERE id=1")->fetchColumn();
    $mode=$_GET['hub_mode'] ?? $_GET['hub.mode'] ?? '';
    $token=$_GET['hub_verify_token'] ?? $_GET['hub.verify_token'] ?? '';
    $challenge=$_GET['hub_challenge'] ?? $_GET['hub.challenge'] ?? '';
    if($mode==='subscribe' && $expected!=='' && hash_equals($expected,$token)){
        header('Content-Type: application/json');
        echo json_encode(['hub.challenge'=>$challenge]); exit;
    }
    http_response_code(403); echo 'Forbidden'; exit;
}

if($_SERVER['REQUEST_METHOD']==='POST'){
    $raw=file_get_contents('php://input');
    $e=json_decode($raw,true);
    if(!is_array($e)){ http_response_code(200); echo 'OK'; exit; }

    $stmt=$pdo->prepare("INSERT IGNORE INTO webhook_events
      (provider,subscription_id,object_type,object_id,aspect_type,owner_id,event_time,payload,status,received_at)
      VALUES('strava',?,?,?,?,?,?,?,'RECEIVED',UTC_TIMESTAMP())");
    $stmt->execute([$e['subscription_id']??null,$e['object_type']??'',$e['object_id']??0,$e['aspect_type']??'',
                    $e['owner_id']??null,$e['event_time']??null,$raw]);

    $eventId=(int)$pdo->lastInsertId();

    // Send the required response to the client as early as PHP/XAMPP permits.
    http_response_code(200);
    header('Content-Type: text/plain');
    echo 'OK';
    if(function_exists('fastcgi_finish_request')) fastcgi_finish_request();

    // Real event: process immediately after receipt.
    // Duplicate deliveries are protected by INSERT IGNORE + event claim + activity idempotency.
    if($eventId>0) tapsoleProcessWebhookEvent($pdo,$eventId);
    exit;
}
http_response_code(405);
