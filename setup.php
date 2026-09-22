<?php
require __DIR__.'/../auth/bootstrap.php';
$userId=requireAdmin();
$cfg=require __DIR__.'/../config/strava.php';

$callback='https://app.toetap.run/tapsole/webhook/callback.php';
$msg='';$err='';$subscriptions=[];

function stravaSubRequest(string $method,string $url,array $data=[]): array{
 global $cfg;
 $auth=[
   'client_id'=>(string)$cfg['client_id'],
   'client_secret'=>(string)$cfg['client_secret']
 ];
 $method=strtoupper($method);

 if($method==='GET' || $method==='DELETE'){
   // Push-subscription GET/DELETE authenticate with application credentials
   // in the query string. DELETE previously dropped these credentials.
   $url.=(str_contains($url,'?')?'&':'?').http_build_query($auth);
 }

 $ch=curl_init($url);
 $opts=[
   CURLOPT_RETURNTRANSFER=>true,
   CURLOPT_TIMEOUT=>20,
   CURLOPT_HTTPHEADER=>['Accept: application/json']
 ];

 if($method==='POST'){
   $opts[CURLOPT_POST]=true;
   $opts[CURLOPT_POSTFIELDS]=http_build_query(array_merge($auth,$data));
   $opts[CURLOPT_HTTPHEADER]=[
     'Accept: application/json',
     'Content-Type: application/x-www-form-urlencoded'
   ];
 }elseif($method==='DELETE'){
   $opts[CURLOPT_CUSTOMREQUEST]='DELETE';
 }

 curl_setopt_array($ch,$opts);
 $body=curl_exec($ch);
 $code=curl_getinfo($ch,CURLINFO_HTTP_CODE);
 $ce=curl_error($ch);
 curl_close($ch);

 if($ce) throw new Exception('cURL: '.$ce);
 $json=json_decode((string)$body,true);
 if($code<200||$code>=300){
   $detail=is_array($json)?json_encode($json,JSON_UNESCAPED_SLASHES):(string)$body;
   throw new Exception('Strava HTTP '.$code.': '.$detail);
 }
 return is_array($json)?$json:[];
}

try{
 if($_SERVER['REQUEST_METHOD']==='POST'){
   requireCsrf();
   $action=$_POST['action']??'';
   if($action==='migrate'){
     $verify=trim($_POST['verify_token']??'');
     if(strlen($verify)<24) throw new Exception('Verify token must be at least 24 characters.');

     // Strava allows one push subscription per application. Remove existing subscription(s) first.
     $existing=stravaSubRequest('GET','https://www.strava.com/api/v3/push_subscriptions');
     foreach($existing as $sub){
       if(!empty($sub['id'])){
         stravaSubRequest('DELETE','https://www.strava.com/api/v3/push_subscriptions/'.rawurlencode((string)$sub['id']));
       }
     }

     // Store token before creation because Strava immediately validates callback with GET challenge.
     $pdo->prepare("UPDATE webhook_settings SET callback_url=?,verify_token=?,subscription_id=NULL WHERE id=1")
         ->execute([$callback,$verify]);

     $created=stravaSubRequest('POST','https://www.strava.com/api/v3/push_subscriptions',[
       'callback_url'=>$callback,'verify_token'=>$verify
     ]);
     if(empty($created['id'])) throw new Exception('Strava did not return a subscription ID.');
     $pdo->prepare("UPDATE webhook_settings SET subscription_id=? WHERE id=1")->execute([$created['id']]);
     $msg='Stable webhook active. Subscription ID '.$created['id'];
   }
 }
 $subscriptions=stravaSubRequest('GET','https://www.strava.com/api/v3/push_subscriptions');
}catch(Throwable $e){$err=$e->getMessage();}

$set=$pdo->query("SELECT * FROM webhook_settings WHERE id=1")->fetch();
$generated=bin2hex(random_bytes(24));
?><!doctype html><html><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Webhook — TOETAP</title><link rel="stylesheet" href="../assets/v1.css"></head><body><main class="app">
<div class="top"><a class="back" href="../settings.php">← Settings</a><div class="brand">TOETAP<i></i></div></div>
<div class="kicker">STRAVA WEBHOOK</div><h1 class="pageTitle">Stable endpoint.</h1>
<?php if($msg):?><div class="notice">✓ <?=htmlspecialchars($msg)?></div><?php endif;?>
<?php if($err):?><div class="notice warn"><?=htmlspecialchars($err)?></div><?php endif;?>
<div class="v1panel"><div class="tiny">PERMANENT CALLBACK</div><p style="word-break:break-all;font-weight:800"><?=htmlspecialchars($callback)?></p>
<div class="tiny">LOCAL DB SUBSCRIPTION</div><p><?=htmlspecialchars((string)($set['subscription_id']??'None'))?></p>
<div class="tiny">STRAVA CURRENT SUBSCRIPTION</div><p><?=htmlspecialchars(!empty($subscriptions)?implode(', ',array_map(fn($x)=>(string)($x['id']??'?'),$subscriptions)):'None')?></p></div>
<form method="post" class="v1panel"><?=csrfField()?><input type="hidden" name="action" value="migrate">
<label class="tiny">VERIFY TOKEN</label>
<input name="verify_token" value="<?=htmlspecialchars($generated)?>" minlength="24" required>
<p class="muted">This replaces the old Quick Tunnel subscription with app.toetap.run. Existing activity history is not changed.</p>
<button class="btn green" type="submit">MIGRATE WEBHOOK TO APP.TOETAP.RUN</button></form>
<a class="btn" href="events.php">VIEW WEBHOOK EVENTS</a>
</main></body></html>