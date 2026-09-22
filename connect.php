<?php
require __DIR__.'/../auth/bootstrap.php';
$userId=requireUser();
$cfg=require __DIR__.'/../config/strava.php';

$dynamicRedirect=$cfg['redirect_uri'];

if(str_contains($cfg['client_id'],'PUT_')||str_contains($cfg['client_secret'],'PUT_')) exit('Edit config/strava.php first.');

$state=bin2hex(random_bytes(32));
$_SESSION['strava_oauth']=[
    'state'=>$state,
    'user_id'=>$userId,
    'created_at'=>time(),
    'redirect_uri'=>$dynamicRedirect
];

$q=http_build_query([
 'client_id'=>$cfg['client_id'],
 'redirect_uri'=>$dynamicRedirect,
 'response_type'=>'code',
 'approval_prompt'=>'force',
 'scope'=>'read,profile:read_all,activity:read_all,activity:write',
 'state'=>$state
]);
header('Location: https://www.strava.com/oauth/authorize?'.$q); exit;
