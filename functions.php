<?php
function stravaConfig(): array {
    static $cfg;
    if (!$cfg) $cfg = require __DIR__ . '/../config/strava.php';
    return $cfg;
}
function stravaHttp(string $method,string $url,array $data=[],?string $token=null): array {
    $ch=curl_init();
    $headers=['Accept: application/json'];
    if($token) $headers[]='Authorization: Bearer '.$token;
    $opts=[
        CURLOPT_URL=>$url,
        CURLOPT_RETURNTRANSFER=>true,
        CURLOPT_CUSTOMREQUEST=>$method,
        CURLOPT_HTTPHEADER=>$headers,
        CURLOPT_TIMEOUT=>30,
    ];
    if($data){
        $opts[CURLOPT_POSTFIELDS]=http_build_query($data);
        $headers[]='Content-Type: application/x-www-form-urlencoded';
        $opts[CURLOPT_HTTPHEADER]=$headers;
    }
    curl_setopt_array($ch,$opts);
    $body=curl_exec($ch);
    if($body===false) throw new Exception('cURL: '.curl_error($ch));
    $status=curl_getinfo($ch,CURLINFO_HTTP_CODE);
    curl_close($ch);
    $json=json_decode($body,true);
    if($status<200||$status>=300) throw new Exception("Strava HTTP $status: ".$body);
    return is_array($json)?$json:[];
}
function getConnection(PDO $pdo,int $userId): ?array {
    $s=$pdo->prepare("SELECT * FROM strava_connections WHERE user_id=? LIMIT 1");
    $s->execute([$userId]); return $s->fetch() ?: null;
}
function validToken(PDO $pdo,int $userId): string {
    $c=getConnection($pdo,$userId);
    if(!$c) throw new Exception('Strava is not connected.');
    if(strtotime((string)$c['token_expires_at']) > time()+120) return $c['access_token'];
    $cfg=stravaConfig();
    $r=stravaHttp('POST','https://www.strava.com/oauth/token',[
        'client_id'=>$cfg['client_id'],'client_secret'=>$cfg['client_secret'],
        'grant_type'=>'refresh_token','refresh_token'=>$c['refresh_token']
    ]);
    $u=$pdo->prepare("UPDATE strava_connections SET access_token=?,refresh_token=?,token_expires_at=? WHERE user_id=?");
    $u->execute([$r['access_token'],$r['refresh_token'],date('Y-m-d H:i:s',$r['expires_at']),$userId]);
    return $r['access_token'];
}
