<?php
require __DIR__.'/auth/bootstrap.php';
requireAdmin();

try {
    $pdo->beginTransaction();

    $tag = $pdo->prepare("SELECT user_shoe_id FROM tags WHERE tag_code='TS000001' LIMIT 1");
    $tag->execute();
    $shoeId = $tag->fetchColumn();

    $pdo->exec("DELETE FROM shoe_selections");
    $pdo->exec("DELETE FROM activities");

    $stmt = $pdo->prepare("
        UPDATE tags
        SET user_shoe_id=NULL, activation_status='NEW', activated_at=NULL
        WHERE tag_code='TS000001'
    ");
    $stmt->execute();

    if ($shoeId) {
        $del = $pdo->prepare("DELETE FROM user_shoes WHERE id=?");
        $del->execute([$shoeId]);
    }

    $pdo->commit();
    header('Location: tap.php?tag=TS000001');
} catch (Throwable $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    exit('Reset failed: ' . htmlspecialchars($e->getMessage()));
}
