<?php
require __DIR__.'/auth/bootstrap.php';
require __DIR__.'/v1_nav.php';
$userId=requireAdmin();

$status=trim((string)($_GET['status']??'ALL'));
$allowed=['ALL','NEW','ACTIVE'];
if(!in_array($status,$allowed,true)) $status='ALL';

$sql="SELECT t.id,t.tag_code,t.public_token,t.activation_status,t.owner_user_id,t.user_shoe_id,t.activated_at,
             us.user_id AS shoe_owner_user_id,us.nickname,us.custom_brand,us.custom_model,u.email
      FROM tags t
      LEFT JOIN user_shoes us ON us.id=t.user_shoe_id
      LEFT JOIN users u ON u.id=t.owner_user_id
      WHERE t.issued_by_tapsole=1";
$args=[];
if($status!=='ALL'){ $sql.=" AND t.activation_status=?"; $args[]=$status; }
$sql.=" ORDER BY t.id DESC";
$q=$pdo->prepare($sql);$q->execute($args);$rows=$q->fetchAll();

$countAll=(int)$pdo->query("SELECT COUNT(*) FROM tags WHERE issued_by_tapsole=1")->fetchColumn();
$countNew=(int)$pdo->query("SELECT COUNT(*) FROM tags WHERE issued_by_tapsole=1 AND activation_status='NEW'")->fetchColumn();
$countActive=(int)$pdo->query("SELECT COUNT(*) FROM tags WHERE issued_by_tapsole=1 AND activation_status='ACTIVE'")->fetchColumn();
?><!doctype html><html><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>Tag Inventory — TOETAP</title><link rel="stylesheet" href="assets/v1.css">
<style>
.invStats{display:grid;grid-template-columns:repeat(3,1fr);gap:8px;margin:12px 0 16px}.invStats a{background:#111;color:#fff;border-radius:15px;padding:12px;text-decoration:none}.invStats small,.invStats b{display:block}.invStats small{font-size:8px;color:#999}.invStats b{font-size:22px;margin-top:3px}.invStats a.on b{color:#c9ff54}
.invCard{background:#fff;border:1px solid #e7e7e2;border-radius:17px;padding:13px;margin:9px 0}.invTop{display:flex;justify-content:space-between;gap:10px;align-items:center}.invCode{font-weight:900}.invStatus{font-size:9px;font-weight:900}.invStatus.new{color:#777}.invStatus.issue{color:#b42318;background:#fee4e2;border-radius:999px;padding:5px 8px}.invStatus.active{color:#111;background:#c9ff54;border-radius:999px;padding:5px 8px}.invMeta{font-size:10px;line-height:1.5;color:#777;margin-top:8px;word-break:break-word}.invUrl{font-size:9px;color:#777;margin-top:7px;word-break:break-all}
.adminActions{display:flex;gap:8px;margin:10px 0 14px}.adminActions a{font-size:9px;font-weight:900;text-decoration:none;background:#111;color:#c9ff54;border-radius:999px;padding:9px 11px}
</style></head><body><main class="app">
<div class="top"><a class="back" href="tags.php">← Tags</a><div class="brand">TOETAP<i></i></div></div>
<div class="kicker">ADMIN · HARDWARE</div><h1 class="pageTitle">Tag inventory.</h1>
<div class="adminActions"><a href="tag_new.php">＋ ISSUE TAG</a></div>
<div class="invStats">
<a class="<?=$status==='ALL'?'on':''?>" href="admin_tags.php"><small>ISSUED</small><b><?=$countAll?></b></a>
<a class="<?=$status==='NEW'?'on':''?>" href="admin_tags.php?status=NEW"><small>UNCLAIMED</small><b><?=$countNew?></b></a>
<a class="<?=$status==='ACTIVE'?'on':''?>" href="admin_tags.php?status=ACTIVE"><small>CLAIMED</small><b><?=$countActive?></b></a>
</div>
<?php foreach($rows as $t):
$nm=$t['nickname']?:trim(($t['custom_brand']??'').' '.($t['custom_model']??''));
$url=!empty($t['public_token'])?'https://app.toetap.run/tapsole/tap.php?t='.rawurlencode($t['public_token']):'';
$ownershipIssue=($t['activation_status']==='ACTIVE' && (
    empty($t['owner_user_id']) || empty($t['user_shoe_id']) || empty($t['shoe_owner_user_id']) ||
    (int)$t['owner_user_id']!==(int)$t['shoe_owner_user_id']
));
?>
<div class="invCard">
 <div class="invTop"><div class="invCode"><?=htmlspecialchars($t['tag_code'])?></div><div class="invStatus <?=$ownershipIssue?'issue':strtolower($t['activation_status'])?>"><?=htmlspecialchars($ownershipIssue?'CHECK OWNER':($t['activation_status']==='NEW'?'UNCLAIMED':$t['activation_status']))?></div></div>
 <div class="invMeta"><?php if($t['owner_user_id']):?>Owner: <?=htmlspecialchars($t['email']?:('User #'.$t['owner_user_id']))?><br><?php else:?>Owner: —<br><?php endif;?>
 Shoe: <?=htmlspecialchars($nm?:'—')?><?php if($t['user_shoe_id']):?> · #<?=(int)$t['user_shoe_id']?><?php endif;?>
 <?php if($t['activated_at']):?><br>Activated: <?=htmlspecialchars($t['activated_at'])?> UTC<?php endif;?></div>
 <?php if($url):?><div class="invUrl"><?=htmlspecialchars($url)?></div><?php endif;?>
</div>
<?php endforeach;?>
<?php if(!$rows):?><div class="v1panel"><div class="empty">No issued tags in this view.</div></div><?php endif;?>
<?php v1nav('tags');?></main></body></html>