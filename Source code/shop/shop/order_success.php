<?php
require_once __DIR__ . '/includes/functions.php';

$id = (int)($_GET['id'] ?? 0);

$stmt = $pdo->prepare('SELECT * FROM orders WHERE id = ?');
$stmt->execute([$id]);
$order = $stmt->fetch();
if (!can_view_order($order)) redirect('/shop/index.php');

$items = $pdo->prepare('SELECT * FROM order_items WHERE order_id = ?');
$items->execute([$id]);
$items = $items->fetchAll();

$title = 'Đặt hàng thành công';
include __DIR__ . '/includes/header.php';
?>

<div class="form-card" style="max-width:620px;margin:40px auto;text-align:center">
  <div style="font-size:3.4rem">✅</div>
  <h1 style="margin:10px 0">Cảm ơn bạn đã đặt hàng!</h1>
  <p style="color:var(--muted)">Mã đơn hàng của bạn là <strong>#<?= $order['id'] ?></strong>.
     Chúng tôi sẽ liên hệ xác nhận trong thời gian sớm nhất.</p>

  <table class="cart-table" style="margin:22px 0;text-align:left">
    <thead><tr><th>Sản phẩm</th><th>SL</th><th>Thành tiền</th></tr></thead>
    <tbody>
      <?php foreach ($items as $it): ?>
      <tr>
        <td><?= e($it['product_name']) ?></td>
        <td><?= $it['quantity'] ?></td>
        <td><?= money($it['price'] * $it['quantity']) ?></td>
      </tr>
      <?php endforeach; ?>
      <tr><td colspan="2"><strong>Tổng cộng</strong></td><td><strong><?= money($order['total']) ?></strong></td></tr>
    </tbody>
  </table>

  <p style="text-align:left;color:var(--muted)">
    <strong>Người nhận:</strong> <?= e($order['customer_name']) ?><br>
    <strong>Điện thoại:</strong> <?= e($order['phone']) ?><br>
    <strong>Địa chỉ:</strong> <?= e($order['address']) ?>
  </p>

  <div style="display:flex;gap:10px;justify-content:center;margin-top:20px">
    <a class="btn btn-ghost" href="/shop/orders.php">Xem đơn của tôi</a>
    <a class="btn btn-primary" href="/shop/index.php">Tiếp tục mua sắm</a>
  </div>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>
