<?php
require_once __DIR__ . '/../includes/functions.php';
require_admin('/shop/login.php');

// Duyệt / từ chối yêu cầu hoàn hàng
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $rid    = (int)($_POST['id'] ?? 0);
    $action = $_POST['action'] ?? '';
    $note   = trim($_POST['admin_note'] ?? '');

    $stmt = $pdo->prepare('SELECT * FROM order_returns WHERE id = ?');
    $stmt->execute([$rid]);
    $r = $stmt->fetch();

    if ($r && $r['status'] === 'pending' && in_array($action, ['approve', 'reject'], true)) {
        try {
            $pdo->beginTransaction();
            if ($action === 'approve') {
                // Duyệt: hoàn hàng hoàn tất, hoàn lại tồn kho
                $pdo->prepare('UPDATE order_returns SET status = "approved", admin_note = ? WHERE id = ?')
                    ->execute([$note, $rid]);
                $pdo->prepare('UPDATE orders SET status = "returned" WHERE id = ?')->execute([$r['order_id']]);

                $its = $pdo->prepare('SELECT product_id, quantity FROM order_items WHERE order_id = ?');
                $its->execute([$r['order_id']]);
                $restore = $pdo->prepare('UPDATE products SET stock = stock + ? WHERE id = ?');
                foreach ($its->fetchAll() as $it) {
                    if ($it['product_id']) $restore->execute([$it['quantity'], $it['product_id']]);
                }
                $_SESSION['flash'] = 'Đã duyệt hoàn hàng cho đơn #' . $r['order_id'] . '.';
            } else {
                // Từ chối: trả đơn về trạng thái hoàn thành
                $pdo->prepare('UPDATE order_returns SET status = "rejected", admin_note = ? WHERE id = ?')
                    ->execute([$note, $rid]);
                $pdo->prepare('UPDATE orders SET status = "completed" WHERE id = ?')->execute([$r['order_id']]);
                $_SESSION['flash'] = 'Đã từ chối yêu cầu hoàn hàng đơn #' . $r['order_id'] . '.';
            }
            $pdo->commit();
        } catch (Exception $ex) {
            $pdo->rollBack();
            $_SESSION['flash'] = 'Có lỗi khi xử lý, vui lòng thử lại.';
        }
    }
    redirect('/shop/admin/returns.php');
}

// Lọc theo trạng thái
$filter = $_GET['status'] ?? '';
$sql = 'SELECT r.*, o.customer_name, o.total, o.user_id
        FROM order_returns r JOIN orders o ON o.id = r.order_id';
$params = [];
if (in_array($filter, ['pending', 'approved', 'rejected'], true)) {
    $sql .= ' WHERE r.status = ?';
    $params[] = $filter;
}
$sql .= ' ORDER BY (r.status = "pending") DESC, r.created_at DESC';
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$returns = $stmt->fetchAll();

$title  = 'Duyệt hoàn hàng';
$active = 'returns';
include __DIR__ . '/includes/header.php';
?>

<div class="pills" style="margin-bottom:16px">
  <a href="/shop/admin/returns.php" class="<?= $filter===''?'active':'' ?>">Tất cả</a>
  <a href="?status=pending"  class="<?= $filter==='pending'?'active':'' ?>">Chờ duyệt</a>
  <a href="?status=approved" class="<?= $filter==='approved'?'active':'' ?>">Đã duyệt</a>
  <a href="?status=rejected" class="<?= $filter==='rejected'?'active':'' ?>">Từ chối</a>
</div>

<?php if (!$returns): ?>
  <div class="panel"><div class="empty-state"><div class="ico">↩️</div><h3>Chưa có yêu cầu hoàn hàng</h3></div></div>
<?php else: ?>
  <div class="grid" style="grid-template-columns:repeat(auto-fill,minmax(340px,1fr))">
    <?php foreach ($returns as $r): ?>
    <div class="panel">
      <div class="panel-head">
        <h3>Đơn #<?= $r['order_id'] ?></h3>
        <span class="tag tag-<?= e($r['status']) ?>"><?= return_status_label($r['status']) ?></span>
      </div>
      <div style="padding:16px 18px;line-height:1.8">
        <strong><?= e($r['customer_name']) ?></strong> · <?= money($r['total']) ?><br>
        <strong>Lý do:</strong> <?= e($r['reason']) ?><br>
        <strong>Hoàn tiền qua:</strong> <?= e($r['refund_method']) ?><br>
        <strong>Ngày gửi:</strong> <?= date('d/m/Y H:i', strtotime($r['created_at'])) ?>
        <?php if ($r['image']): ?>
          <div style="margin-top:10px"><img src="<?= e(product_image($r['image'])) ?>" alt="" style="max-width:100%;border-radius:10px;border:1px solid var(--line)"></div>
        <?php endif; ?>

        <?php if ($r['status'] === 'pending'): ?>
          <form method="post" style="margin-top:14px">
            <input type="hidden" name="id" value="<?= $r['id'] ?>">
            <div class="field" style="margin-bottom:10px">
              <input type="text" name="admin_note" placeholder="Ghi chú phản hồi (tuỳ chọn)">
            </div>
            <div style="display:flex;gap:8px">
              <button class="btn btn-primary btn-sm" name="action" value="approve" data-confirm="Duyệt hoàn hàng đơn #<?= $r['order_id'] ?>? Tồn kho sẽ được cộng lại.">✓ Duyệt</button>
              <button class="btn btn-danger btn-sm" name="action" value="reject" data-confirm="Từ chối yêu cầu hoàn hàng đơn #<?= $r['order_id'] ?>?">✕ Từ chối</button>
            </div>
          </form>
        <?php elseif ($r['admin_note']): ?>
          <div style="margin-top:10px;color:var(--muted)"><strong>Ghi chú:</strong> <?= e($r['admin_note']) ?></div>
        <?php endif; ?>
      </div>
    </div>
    <?php endforeach; ?>
  </div>
<?php endif; ?>

<?php include __DIR__ . '/includes/footer.php'; ?>
