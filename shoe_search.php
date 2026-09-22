<?php
require __DIR__.'/auth/bootstrap.php';
header('Content-Type: application/json; charset=utf-8');
$q=trim($_GET['q']??'');
if(mb_strlen($q)<1){echo json_encode([]);exit;}
$s=$pdo->prepare("SELECT id,brand,model,category,default_target_km FROM shoe_library
 WHERE active=1 AND (brand LIKE ? OR model LIKE ? OR CONCAT(brand,' ',model) LIKE ?)
 ORDER BY CASE WHEN model LIKE ? THEN 0 ELSE 1 END, brand, model LIMIT 8");
$like='%'.$q.'%';$start=$q.'%';$s->execute([$like,$like,$like,$start]);
echo json_encode($s->fetchAll(),JSON_UNESCAPED_UNICODE);
