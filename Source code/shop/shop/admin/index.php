<?php
require_once __DIR__ . '/../includes/functions.php';
require_admin('/shop/login.php');

$totalProducts = $pdo->query('SELECT COUNT(*) FROM products')->fetchColumn();
$totalOrders   = $pdo->query('SELECT COUNT(*) FROM orders')->fetchColumn();
$totalUsers    = $pdo->query('SELECT COUNT(*) FROM users WHERE role="customer"')->fetchColumn();
$revenue       = $pdo->query('SELECT COALESCE(SUM(total),0) FROM orders WHERE status="completed"')->fetchColumn();
$pending       = $pdo->query('SELECT COUNT(*) FROM orders WHERE status="pending"')->fetchColumn();

$recent = $pdo->query(
    'SELECT * FROM orders ORDER BY created_at DESC LIMIT 6'
)->fetchAll();

$lowStock = $pdo->query(
    'SELECT * FROM products WHERE stock <= 10 ORDER BY stock ASC LIMIT 5'
)->fetchAll();

$title  = 'Tổng quan';
$active = 'dashboard';
include __DIR__ . '/includes/header.php';
?>

<div class="stats">
  <a class="stat c1" href="/shop/admin/products.php"><div class="label">Sản phẩm</div><div class="num"><?= $totalProducts ?></div></a>
  <a class="stat c2" href="/shop/admin/orders.php"><div class="label">Đơn hàng</div><div class="num"><?= $totalOrders ?></div></a>
  <a class="stat c3" href="/shop/admin/revenue.php"><div class="label">Doanh thu (hoàn thành)</div><div class="num" style="font-size:1.3rem"><?= money($revenue) ?></div></a>
  <a class="stat c4" href="/shop/admin/orders.php?status=pending"><div class="label">Đơn chờ xử lý</div><div class="num"><?= $pending ?></div></a>
</div>

<div style="display:grid;grid-template-columns:1.6fr 1fr;gap:18px">
  <div class="panel">
    <div class="panel-head"><h3>Đơn hàng gần đây</h3><a class="btn btn-ghost btn-sm" href="/shop/admin/orders.php">Xem tất cả</a></div>
    <table class="table">
      <thead><tr><th>Mã</th><th>Khách hàng</th><th>Tổng</th><th>Trạng thái</th></tr></thead>
      <tbody>
        <?php if (!$recent): ?>
          <tr><td colspan="4" style="text-align:center;color:var(--muted)">Chưa có đơn hàng</td></tr>
        <?php else: foreach ($recent as $o): ?>
          <tr>
            <td><a href="/shop/admin/orders.php?view=<?= $o['id'] ?>"><strong>#<?= $o['id'] ?></strong></a></td>
            <td><?= e($o['customer_name']) ?></td>
            <td><?= money($o['total']) ?></td>
            <td><span class="tag tag-<?= e($o['status']) ?>"><?= order_status_label($o['status']) ?></span></td>
          </tr>
        <?php endforeach; endif; ?>
      </tbody>
    </table>
  </div>

  <div class="panel">
    <div class="panel-head"><h3>Sắp hết hàng</h3></div>
    <table class="table">
      <thead><tr><th>Mã</th><th>Sản phẩm</th><th>Tồn</th></tr></thead>
      <tbody>
        <?php if (!$lowStock): ?>
          <tr><td colspan="3" style="text-align:center;color:var(--muted)">Tồn kho ổn định</td></tr>
        <?php else: foreach ($lowStock as $p): ?>
          <tr>
            <td><code><?= e($p['code'] ?? '—') ?></code></td>
            <td><?= e($p['name']) ?></td>
            <td><span class="<?= $p['stock']==0?'stock-out':'' ?>"><?= $p['stock'] ?></span></td>
          </tr>
        <?php endforeach; endif; ?>
      </tbody>
    </table>
    <div style="padding:14px 16px;border-top:1px solid var(--line)">
      <h4 style="margin-bottom:8px;color:var(--primary-d)">⊕ Nhập thêm hàng nhanh</h4>
      <p style="color:var(--muted);font-size:.82rem;margin-bottom:10px">Nhập mã sản phẩm và số lượng cần thêm vào kho.</p>
      <form method="post" action="/shop/admin/products.php" style="display:flex;gap:8px;flex-wrap:wrap">
        <input type="hidden" name="restock" value="1">
        <input type="text" name="code" placeholder="Mã SP (vd: TN0007)" required
               style="flex:1;min-width:130px;padding:9px 12px;border:1px solid var(--line);border-radius:10px">
        <input type="number" name="qty" placeholder="SL" min="1" value="10" required
               style="width:80px;padding:9px 12px;border:1px solid var(--line);border-radius:10px">
        <button class="btn btn-primary btn-sm">Nhập kho</button>
      </form>
    </div>
  </div>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>
