<?php
require __DIR__.'/../auth/bootstrap.php';
require __DIR__.'/functions.php';
header('Content-Type: application/json; charset=utf-8');
$userId=requireUser();
try{
    $token=validToken($pdo,$userId);
    $ath=stravaHttp('GET','https://www.strava.com/api/v3/athlete',[],$token);
    if(!isset($ath['shoes']) || !is_array($ath['shoes'])){
        throw new Exception('Strava did not return shoes. Reauthorize with profile:read_all.');
    }
    $gears=[];
    foreach($ath['shoes'] as $g){
        if(empty($g['id'])) continue;
        $gears[]=[
            'id'=>(string)$g['id'],
            'name'=>(string)($g['name']??$g['id']),
            'distance'=>(float)($g['distance']??0),
            'distance_km'=>round(((float)($g['distance']??0))/1000,1)
        ];
    }
    usort($gears,fn($a,$b)=>strcasecmp($a['name'],$b['name']));
    echo json_encode(['ok'=>true,'gears'=>$gears],JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE);
}catch(Throwable $e){
    http_response_code(400);
    echo json_encode(['ok'=>false,'error'=>$e->getMessage()],JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE);
}
