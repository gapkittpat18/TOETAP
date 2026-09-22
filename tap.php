<?php
require_once __DIR__.'/auth/bootstrap.php';
require __DIR__ . '/config/database.php';

$tagCode=trim($_GET['tag']??'');$publicToken=trim($_GET['t']??'');
if($tagCode===''&&$publicToken===''){
    render('Missing Tag', 'No TOETAP tag ID was provided.', false);
    exit;
}

$stmt = $pdo->prepare("
    SELECT t.id AS tag_id, t.tag_code, t.activation_status, t.issued_by_tapsole, t.user_shoe_id, t.owner_user_id, t.public_token,
           us.user_id, us.nickname, us.custom_brand, us.custom_model, us.initial_km, us.target_km,
           sb.name AS master_brand, sm.model_name AS master_model
    FROM tags t
    LEFT JOIN user_shoes us ON us.id = t.user_shoe_id
    LEFT JOIN shoe_models sm ON sm.id = us.shoe_model_id
    LEFT JOIN shoe_brands sb ON sb.id = sm.brand_id
    WHERE ".($publicToken!==''?"t.public_token = ?":"t.tag_code = ?")."
    LIMIT 1
");
$stmt->execute([$publicToken!==''?$publicToken:$tagCode]);
$tag = $stmt->fetch();
$canEditTag=false;
if($tag && !empty($tag['owner_user_id']) && function_exists('currentUserId')){
    $canEditTag=((int)currentUserId()===(int)$tag['owner_user_id']);
}


if (!$tag || (int)$tag['issued_by_tapsole'] !== 1) {
    http_response_code(404);
    render('Not a TOETAP', 'This tag is not recognized by TOETAP.', false);
    exit;
}

if ($tag['activation_status'] === 'DISABLED') {
    render('Tag Disabled', 'This TOETAP tag is currently disabled.', false);
    exit;
}

if ($tag['activation_status'] === 'NEW' || empty($tag['user_shoe_id'])) {
    newTag((string)$tag['tag_code'], (string)($tag['public_token'] ?? ''));
    exit;
}

// A claimed tag must belong to the same user as its linked physical shoe.
// Fail closed instead of creating a selection from inconsistent ownership data.
if (empty($tag['owner_user_id']) || empty($tag['user_id']) || (int)$tag['owner_user_id'] !== (int)$tag['user_id']) {
    http_response_code(409);
    render('Tag Link Error', 'This TOETAP tag needs an ownership check before it can select a shoe.', false);
    exit;
}

try {
    // V0.5 — Tap confirmed · Latest Tap Wins. Any older unused selection for this user is superseded.
    $pdo->beginTransaction();

    $supersede = $pdo->prepare("
        UPDATE shoe_selections
        SET used = 1
        WHERE user_id = ? AND used = 0
    ");
    $supersede->execute([(int)$tag['user_id']]);

    $insert = $pdo->prepare("
        INSERT INTO shoe_selections (user_id, user_shoe_id, tag_id, selected_at, used)
        VALUES (?, ?, ?, UTC_TIMESTAMP(), 0)
    ");
    $insert->execute([(int)$tag['user_id'], (int)$tag['user_shoe_id'], (int)$tag['tag_id']]);

    $pdo->commit();
} catch (Throwable $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    http_response_code(500);
    render('Selection Failed', 'TOETAP could not select this shoe.', false);
    exit;
}

$brand = $tag['master_brand'] ?: $tag['custom_brand'] ?: 'Shoe';
$model = $tag['master_model'] ?: $tag['custom_model'] ?: '';
$fullName = trim($brand . ' ' . $model);
$displayName = $tag['nickname'] ?: $fullName;

ready($displayName, $fullName, (float)$tag['initial_km'], (float)$tag['target_km'], (int)$tag['tag_id'], $canEditTag);

function shellStart($title) { ?>
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title><?= htmlspecialchars($title) ?> — TOETAP</title>
<style>
*{box-sizing:border-box}body{margin:0;min-height:100vh;background:linear-gradient(155deg,#09090b,#18181d);color:#fff;font-family:Arial,sans-serif;display:flex;align-items:center;justify-content:center;padding:22px}
.app{width:100%;max-width:430px}.brand{text-align:center;font-weight:900;letter-spacing:5px;margin-bottom:25px}.card{background:#17171b;border:1px solid #29292f;border-radius:28px;padding:32px 25px;text-align:center;box-shadow:0 25px 80px #0007}
.icon{width:82px;height:82px;border-radius:50%;background:#26262c;display:flex;align-items:center;justify-content:center;margin:0 auto 23px;font-size:38px;font-weight:800}
.icon.ok{background:#fff;color:#000}h1{font-size:32px;margin:0}.sub{color:#999;line-height:1.5;margin-top:10px}.shoe{font-size:23px;font-weight:800;margin-top:27px}.model{color:#888;margin-top:5px}
.box{background:#222228;border-radius:17px;padding:17px;margin-top:25px}.label{font-size:12px;color:#888;letter-spacing:2px;font-weight:800}.ready{font-size:18px;font-weight:800;margin-top:6px}
.btn{display:block;background:#fff;color:#000;text-decoration:none;font-weight:900;border-radius:15px;padding:16px;margin-top:26px}.small{color:#666;font-size:12px;margin-top:18px}
.progress{height:7px;background:#29292f;border-radius:10px;overflow:hidden;margin-top:10px}.fill{height:100%;background:#fff}.kms{display:flex;justify-content:space-between;color:#888;font-size:12px;margin-top:22px}
.tapLoader{position:fixed;inset:0;z-index:9999;background:#09090beF;display:none;place-items:center}.tapLoader.show{display:grid}.tapSpin{width:48px;height:48px;border:5px solid #333;border-top-color:#fff;border-radius:50%;animation:tapSpin .72s linear infinite}@keyframes tapSpin{to{transform:rotate(360deg)}}
</style></head><body><div id="tapLoader" class="tapLoader"><div><div class="tapSpin"></div><div class="label" style="text-align:center;margin-top:14px">LOADING</div></div></div><main class="app"><div class="brand">TOETAP</div><?php if(($_GET['changed']??'')==='1'):?><div class="box" style="margin:0 0 18px;text-align:center"><div class="ready">✓ Shoe corrected</div><div class="sub">This is now your current shoe.</div></div><?php endif;?>
<?php }
function shellEnd(){ ?></main><script>
let tapLoaderTimer=null;
document.addEventListener('click',function(e){
 const a=e.target.closest('a[href]');if(!a||a.target==='_blank')return;
 const h=a.getAttribute('href')||'';if(!h||h[0]==='#')return;
 clearTimeout(tapLoaderTimer);
 tapLoaderTimer=setTimeout(()=>document.getElementById('tapLoader')?.classList.add('show'),350);
});
window.addEventListener('pageshow',()=>{clearTimeout(tapLoaderTimer);document.getElementById('tapLoader')?.classList.remove('show');});
</script></body></html><?php }

function newTag($code, $publicToken='') {
    shellStart('New TOETAP'); ?>
    <section class="card">
      <div class="icon">+</div>
      <h1>New TOETAP</h1>
      <div class="sub">This tag hasn't been activated yet.<br>Set up your shoe once, then just tap and run.</div>
      <a class="btn" href="shoe_add.php?<?= $publicToken!=='' ? 't='.urlencode($publicToken) : 'tag='.urlencode($code) ?>">ADD YOUR SHOE</a>
      <div class="small"><?= htmlspecialchars($code) ?></div>
    </section>
    <?php shellEnd();
}

function ready($display, $full, $km, $target, $tagId, $canEditTag=false) {
    $pct = $target > 0 ? min(100, ($km/$target)*100) : 0;
    shellStart('Current Shoe'); ?>
    <section class="card">
      <div class="icon ok">✓</div>
      <div class="label">CURRENT SHOE</div><h1>Ready to Run</h1>
      <div class="shoe"><?= htmlspecialchars($display) ?></div>
      <?php if ($display !== $full): ?><div class="model"><?= htmlspecialchars($full) ?></div><?php endif; ?>
      <div class="box"><div class="label">SELECTED</div><div class="ready">Next run will use this shoe.</div></div>
      <div class="kms"><span><?= number_format($km,1) ?> km</span><span><?= number_format($target,0) ?> km</span></div>
      <div class="progress"><div class="fill" style="width:<?= number_format($pct,1,'.','') ?>%"></div></div>
      <?php if($canEditTag):?><a class="btn" href="tag_change_shoe.php?id=<?= (int)$tagId ?>">CHANGE SHOE</a><?php endif;?>
<a class="btn" href="index.php">MY SHOES</a>
    </section>
    <?php shellEnd();
}

function render($title, $message, $ok=true) {
    shellStart($title); ?>
    <section class="card">
      <div class="icon <?= $ok?'ok':'' ?>"><?= $ok?'✓':'!' ?></div>
      <h1><?= htmlspecialchars($title) ?></h1>
      <div class="sub"><?= htmlspecialchars($message) ?></div>
      <a class="btn" href="index.php">HOME</a>
    </section>
    <?php shellEnd();
}
