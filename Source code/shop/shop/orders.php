<?php
require_once __DIR__ . '/includes/functions.php';

$u = current_user();
if ($u) {
    // Khách đã đăng nhập: hiện toàn bộ đơn của tài khoản
    $stmt = $pdo->prepare('SELECT * FROM orders WHERE user_id = ? ORDER BY created_at DESC');
    $stmt->execute([$u['id']]);
    $orders = $stmt->fetchAll();
} else {
    // Khách vãng lai: chỉ hiện các đơn đã đặt trong phiên hiện tại
    $ids = guest_order_ids();
    if ($ids) {
        $in   = implode(',', array_fill(0, count($ids), '?'));
        $stmt = $pdo->prepare("SELECT * FROM orders WHERE id IN ($in) ORDER BY created_at DESC");
        $stmt->execute($ids);
        $orders = $stmt->fetchAll();
    } else {
        $orders = [];
    }
}

$title = 'Đơn hàng của tôi';
$page  = 'orders';
include __DIR__ . '/includes/header.php';
?>

<div class="section-head"><h2><span class="eyebrow">Tài khoản</span>Đơn hàng của tôi</h2></div>

<?php if (!$orders): ?>
  <div class="empty-state">
    <div class="ico">📦</div>
    <h3>Bạn chưa có đơn hàng nào</h3>
    <a class="btn btn-primary" href="/shop/index.php">Mua sắm ngay</a>
  </div>
<?php else: ?>
  <div class="panel">
    <table class="table">
      <thead>
        <tr><th>Mã đơn</th><th>Ngày đặt</th><th>SL</th><th>Tổng tiền</th><th>Giao dự kiến</th><th>Trạng thái</th><th></th></tr>
      </thead>
      <tbody>
        <?php foreach ($orders as $o):
          $cnt = $pdo->prepare('SELECT SUM(quantity) FROM order_items WHERE order_id = ?');
          $cnt->execute([$o['id']]);
          $qty = (int)$cnt->fetchColumn();
        ?>
        <tr>
          <td><strong>#<?= $o['id'] ?></strong></td>
          <td><?= date('d/m/Y H:i', strtotime($o['created_at'])) ?></td>
          <td><?= $qty ?></td>
          <td><strong><?= money($o['total']) ?></strong></td>
          <td><?= $o['estimated_delivery'] ? date('d/m/Y', strtotime($o['estimated_delivery'])) : '—' ?></td>
          <td><span class="tag tag-<?= e($o['status']) ?>"><?= order_status_label($o['status']) ?></span></td>
          <td><a class="btn btn-ghost btn-sm" href="/shop/order_detail.php?id=<?= $o['id'] ?>">Chi tiết</a></td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
<?php endif; ?>

<?php include __DIR__ . '/includes/footer.php'; ?>
