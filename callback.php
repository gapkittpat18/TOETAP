<?php
require __DIR__.'/../auth/bootstrap.php';
require __DIR__.'/functions.php';

$userId=currentUserId();
if(!$userId){
 $next=$_SERVER['REQUEST_URI']??'/tapsole/strava/callback.php';
 header('Location:../login.php?next='.rawurlencode($next));exit;
}
$cfg=stravaConfig();
$oauth=$_SESSION['strava_oauth']??null;
if(isset($_GET['error'])) exit('Strava authorization denied: '.htmlspecialchars($_GET['error']));
if(!is_array($oauth) || empty($oauth['state']) || empty($oauth['user_id'])) exit('OAuth session expired. Please connect Strava again.');
if((int)$oauth['user_id']!==$userId) exit('OAuth user mismatch.');
if(time()-(int)($oauth['created_at']??0)>900) exit('OAuth session expired. Please connect Strava again.');
if(!isset($_GET['state']) || !hash_equals((string)$oauth['state'],(string)$_GET['state'])) exit('Invalid OAuth state.');

$code=$_GET['code']??'';
if(!$code) exit('Missing authorization code.');

try{
 $r=stravaHttp('POST','https://www.strava.com/oauth/token',[
  'client_id'=>$cfg['client_id'],
  'client_secret'=>$cfg['client_secret'],
  'code'=>$code,
  'grant_type'=>'authorization_code'
 ]);
 $ath=$r['athlete']??[];
 $athleteId=(string)($ath['id']??'');
 if($athleteId==='') throw new Exception('Strava did not return an athlete ID.');

 // One Strava athlete may belong to only one TOETAP account.
 $owner=$pdo->prepare("SELECT user_id FROM strava_connections WHERE athlete_id=? AND user_id<>? LIMIT 1");
 $owner->execute([$athleteId,$userId]);
 if($owner->fetchColumn()) throw new Exception('This Strava account is already connected to another TOETAP account.');

 $s=$pdo->prepare("INSERT INTO strava_connections(user_id,athlete_id,access_token,refresh_token,token_expires_at)
 VALUES(?,?,?,?,?)
 ON DUPLICATE KEY UPDATE athlete_id=VALUES(athlete_id),access_token=VALUES(access_token),
 refresh_token=VALUES(refresh_token),token_expires_at=VALUES(token_expires_at)");
 $s->execute([$userId,$athleteId,$r['access_token'],$r['refresh_token'],date('Y-m-d H:i:s',$r['expires_at'])]);

 unset($_SESSION['strava_oauth']);
 header('Location: ../strava_v1.php?connected=1'); exit;
}catch(Throwable $e){
 unset($_SESSION['strava_oauth']);
 exit('OAuth failed: '.htmlspecialchars($e->getMessage()));
}
