<?php
require __DIR__.'/../auth/bootstrap.php';
require __DIR__.'/functions.php';
$userId=requireUser();

header('Content-Type: text/html; charset=utf-8');

function h($v){ return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); }

try {
    $token = validToken($pdo, $userId);

    // 1) Authenticated athlete: inspect exactly what Strava returns in shoes[]
    $athlete = stravaHttp('GET', 'https://www.strava.com/api/v3/athlete', [], $token);

    // 2) Recent activities: useful comparison with gear IDs actually seen on runs
    $activities = stravaHttp(
        'GET',
        'https://www.strava.com/api/v3/athlete/activities?per_page=100&page=1',
        [],
        $token
    );

    $activityGears = [];
    foreach ($activities as $a) {
        if (!empty($a['gear_id'])) {
            $activityGears[(string)$a['gear_id']] = [
                'id' => (string)$a['gear_id'],
                'activity_name' => $a['name'] ?? '',
                'start_date' => $a['start_date'] ?? ''
            ];
        }
    }

    // 3) Current TapSole mappings
    $tapsoleShoes = $pdo->query("
        SELECT id, nickname, custom_brand, custom_model,
               strava_gear_id, strava_gear_name
        FROM user_shoes
        WHERE user_id=? AND active=1
        ORDER BY id
    ")->fetchAll(PDO::FETCH_ASSOC);

} catch (Throwable $e) {
    http_response_code(500);
    echo '<pre>'.h($e->getMessage()).'</pre>';
    exit;
}

$shoes = $athlete['shoes'] ?? null;
?><!doctype html>
<html>
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>TapSole — Strava Gear Debug</title>
<style>
body{background:#0b0b0d;color:#fff;font-family:Arial,sans-serif;margin:0;padding:24px}
.w{max-width:1100px;margin:auto}.card{background:#17171b;border:1px solid #303038;border-radius:18px;padding:20px;margin:16px 0}
table{width:100%;border-collapse:collapse}th,td{padding:10px;border-bottom:1px solid #333;text-align:left}
pre{white-space:pre-wrap;word-break:break-word;background:#09090b;padding:16px;border-radius:12px;overflow:auto}
.ok{color:#9effb2}.warn{color:#ffe28a}.bad{color:#ff9da6}.muted{color:#aaa}
a{color:#fff}.pill{border:1px solid #555;border-radius:20px;padding:4px 9px}
</style>
</head>
<body><div class="w">
<h1>Strava Gear Debug</h1>
<p class="muted">Read-only debug. This page does not change Strava or TapSole data.</p>

<div class="card">
<h2>1. Athlete response</h2>
<p>Athlete ID: <b><?=h($athlete['id'] ?? 'missing')?></b></p>
<p><code>shoes</code> key:
<?php if(!array_key_exists('shoes',$athlete)): ?>
<span class="bad">MISSING</span>
<?php elseif(!is_array($shoes)): ?>
<span class="bad">NOT ARRAY</span>
<?php else: ?>
<span class="ok">FOUND — <?=count($shoes)?> item(s)</span>
<?php endif; ?>
</p>

<?php if(is_array($shoes)): ?>
<table><tr><th>#</th><th>ID</th><th>Name</th><th>Distance</th><th>Primary</th></tr>
<?php foreach($shoes as $i=>$g): ?>
<tr>
<td><?=($i+1)?></td>
<td><?=h($g['id'] ?? '')?></td>
<td><?=h($g['name'] ?? '')?></td>
<td><?=isset($g['distance']) ? number_format(((float)$g['distance'])/1000,1).' km' : ''?></td>
<td><?=!empty($g['primary'])?'YES':''?></td>
</tr>
<?php endforeach; ?>
</table>
<?php endif; ?>
</div>

<div class="card">
<h2>2. Gear IDs seen in latest 100 activities</h2>
<?php if(!$activityGears): ?>
<p class="warn">No gear IDs found.</p>
<?php else: ?>
<table><tr><th>Gear ID</th><th>Example activity</th><th>Start UTC</th></tr>
<?php foreach($activityGears as $g): ?>
<tr><td><?=h($g['id'])?></td><td><?=h($g['activity_name'])?></td><td><?=h($g['start_date'])?></td></tr>
<?php endforeach; ?></table>
<?php endif; ?>
</div>

<div class="card">
<h2>3. Current TapSole shoe mappings</h2>
<table><tr><th>ID</th><th>TapSole Shoe</th><th>Strava Gear</th><th>Gear ID</th></tr>
<?php foreach($tapsoleShoes as $s):
$name=$s['nickname'] ?: trim(($s['custom_brand']??'').' '.($s['custom_model']??'')); ?>
<tr><td><?=h($s['id'])?></td><td><?=h($name)?></td><td><?=h($s['strava_gear_name']??'')?></td><td><?=h($s['strava_gear_id']??'')?></td></tr>
<?php endforeach; ?></table>
</div>

<div class="card">
<h2>4. Raw athlete JSON</h2>
<pre><?=h(json_encode($athlete, JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE))?></pre>
</div>

<p><a href="gear_link.php">← Gear Link</a> · <a href="status.php">Strava Status</a></p>
</div></body></html>
