<?php
require_once __DIR__ . '/../includes/functions.php';
require_admin('/shop/login.php');

$statuses = ['pending', 'confirmed', 'shipping', 'completed', 'cancelled', 'return_requested', 'returned'];

$allowedTransitions = [
  'pending'    => ['confirmed', 'cancelled'],
  'confirmed'  => ['shipping'],
  'shipping'   => ['completed'],
  'completed'  => [],
  'return_requested' => ['returned', 'completed'],
  'returned'   => [],
  'cancelled'  => [],
];

// Cập nhật trạng thái
// if ($_SERVER['REQUEST_METHOD'] === 'POST') {
//     $id     = (int)($_POST['id'] ?? 0);
//     $status = $_POST['status'] ?? '';
//     if (in_array($status, $statuses, true)) {
//         $pdo->prepare('UPDATE orders SET status = ? WHERE id = ?')->execute([$status, $id]);
//         $_SESSION['flash'] = 'Đã cập nhật trạng thái đơn #' . $id . '.';
//     }
//     redirect('/shop/admin/orders.php' . ($id ? '?view=' . $id : ''));
// }

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $id     = (int)($_POST['id'] ?? 0);
  $status = $_POST['status'] ?? '';

  if (in_array($status, $statuses, true)) {
    // Lấy trạng thái hiện tại của đơn hàng
    $stmt = $pdo->prepare('SELECT status FROM orders WHERE id = ?');
    $stmt->execute([$id]);
    $currentStatus = $stmt->fetchColumn();

    // k doi status thi k can update 
    if ($status === $currentStatus) {
      $_SESSION['flash'] = ' Trạng thái không đổi';
    }

    // Kiểm tra xem có thể chuyển trạng thái hay không
    elseif (
      isset($allowedTransitions[$currentStatus])
      && in_array($status, $allowedTransitions[$currentStatus], true)
    ) {
      $pdo->prepare('UPDATE orders SET status = ? WHERE id = ?')->execute([$status, $id]);
      $_SESSION['flash'] = 'Đã cập nhật trạng thái đơn #' . $id . '.';
    } else {
      $_SESSION['flash'] = 'Không thể chuyển trạng thái từ "' . order_status_label($currentStatus) . '" sang "' . order_status_label($status) . '".';
    }
  }
  redirect('/shop/admin/orders.php' . ($id ? '?view=' . $id : ''));
}

// Xoá đơn
if (($_GET['action'] ?? '') === 'delete') {
  $id = (int)($_GET['id'] ?? 0);
  $pdo->prepare('DELETE FROM orders WHERE id = ?')->execute([$id]);
  $_SESSION['flash'] = 'Đã xoá đơn hàng #' . $id . '.';
  redirect('/shop/admin/orders.php');
}

$title  = 'Quản lý đơn hàng';
$active = 'orders';

// ---- Xem chi tiết 1 đơn ----
if (isset($_GET['view'])) {
  $vid = (int)$_GET['view'];
  $stmt = $pdo->prepare('SELECT * FROM orders WHERE id = ?');
  $stmt->execute([$vid]);
  $order = $stmt->fetch();

  if ($order) {
    $items = $pdo->prepare('SELECT * FROM order_items WHERE order_id = ?');
    $items->execute([$vid]);
    $items = $items->fetchAll();

    $rq = $pdo->prepare('SELECT * FROM order_returns WHERE order_id = ? ORDER BY created_at DESC LIMIT 1');
    $rq->execute([$vid]);
    $orderReturn = $rq->fetch();

    include __DIR__ . '/includes/header.php';
?>
    <a href="/shop/admin/orders.php" style="color:var(--muted)">← Quay lại danh sách đơn</a>
    <div style="display:grid;grid-template-columns:1.6fr 1fr;gap:18px;margin-top:14px;align-items:start">
      <div class="panel">
        <div class="panel-head">
          <h3>Đơn hàng #<?= $order['id'] ?></h3>
          <span class="tag tag-<?= e($order['status']) ?>"><?= order_status_label($order['status']) ?></span>
        </div>
        <table class="table">
          <thead>
            <tr>
              <th>Sản phẩm</th>
              <th>Đơn giá</th>
              <th>SL</th>
              <th>Thành tiền</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($items as $it): ?>
              <tr>
                <td><?= e($it['product_name']) ?></td>
                <td><?= money($it['price']) ?></td>
                <td><?= $it['quantity'] ?></td>
                <td><?= money($it['price'] * $it['quantity']) ?></td>
              </tr>
            <?php endforeach; ?>
            <tr>
              <td colspan="3"><strong>Tổng cộng</strong></td>
              <td><strong><?= money($order['total']) ?></strong></td>
            </tr>
          </tbody>
        </table>
      </div>

      <div style="display:flex;flex-direction:column;gap:18px">
        <div class="panel">
          <div class="panel-head">
            <h3>Khách hàng</h3>
          </div>
          <div style="padding:18px;line-height:2">
            <strong><?= e($order['customer_name']) ?></strong><br>
            📞 <?= e($order['phone']) ?><br>
            📍 <?= e($order['address']) ?><br>
            🕒 <?= date('d/m/Y H:i', strtotime($order['created_at'])) ?><br>
            🚚 Giao dự kiến: <strong><?= $order['estimated_delivery'] ? date('d/m/Y', strtotime($order['estimated_delivery'])) : 'Đang cập nhật' ?></strong>
            <?php if ($order['note']): ?><br>📝 <?= e($order['note']) ?><?php endif; ?>
              <?php if ($order['status'] === 'cancelled' && $order['cancel_reason']): ?>
                <br><span style="color:var(--danger)">✖ Lý do huỷ: <?= e($order['cancel_reason']) ?></span>
              <?php endif; ?>
          </div>
        </div>
        <div class="panel">
          <div class="panel-head">
            <h3>Cập nhật trạng thái</h3>
          </div>
          <form method="post" style="padding:18px">
            <input type="hidden" name="id" value="<?= $order['id'] ?>">
            <div class="field">
              <select name="status">
                <!-- Trang thai hien tai -->
                <option value="<?= $order['status'] ?>" selected>
                  <?= order_status_label($order['status']) ?>
                </option>

                <?php foreach ($allowedTransitions[$order['status']] as $s) : ?>
                  <option value="<?= $s ?>">
                    <?= order_status_label($s) ?>
                  </option>
                <?php
                endforeach;
                ?>
              </select>
            </div>
            <?php
            $canChange = !empty($allowedTransitions[$order['status']]);
            ?>

            <button class="btn btn-primary btn-block"
              <?= !$canChange ? 'disabled' : '' ?>>Lưu trạng thái</button>
          </form>
        </div>
        <?php if ($orderReturn): ?>
          <div class="panel">
            <div class="panel-head">
              <h3>Yêu cầu hoàn hàng</h3>
              <span class="tag tag-<?= e($orderReturn['status']) ?>"><?= return_status_label($orderReturn['status']) ?></span>
            </div>
            <div style="padding:18px;line-height:1.8">
              <strong>Lý do:</strong> <?= e($orderReturn['reason']) ?><br>
              <strong>Hoàn tiền qua:</strong> <?= e($orderReturn['refund_method']) ?>
              <?php if ($orderReturn['image']): ?>
                <div style="margin-top:8px"><img src="<?= e(product_image($orderReturn['image'])) ?>" style="max-width:180px;border-radius:8px;border:1px solid var(--line)"></div>
              <?php endif; ?>
              <a class="btn btn-accent btn-sm" style="margin-top:10px" href="/shop/admin/returns.php">Tới trang duyệt hoàn hàng →</a>
            </div>
          </div>
        <?php endif; ?>
      </div>
    </div>
<?php
    include __DIR__ . '/includes/footer.php';
    exit;
  }
}

// ---- Danh sách + lọc theo trạng thái ----
$filter = $_GET['status'] ?? '';
$sql = 'SELECT o.*, (SELECT SUM(quantity) FROM order_items oi WHERE oi.order_id=o.id) AS items
        FROM orders o';
$params = [];
if (in_array($filter, $statuses, true)) {
  $sql .= ' WHERE o.status = ?';
  $params[] = $filter;
}
$sql .= ' ORDER BY o.created_at DESC';
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$orders = $stmt->fetchAll();

include __DIR__ . '/includes/header.php';
?>

<div class="pills" style="margin-bottom:16px">
  <a href="/shop/admin/orders.php" class="<?= $filter === '' ? 'active' : '' ?>">Tất cả</a>
  <?php foreach ($statuses as $s): ?>
    <a href="?status=<?= $s ?>" class="<?= $filter === $s ? 'active' : '' ?>"><?= order_status_label($s) ?></a>
  <?php endforeach; ?>
</div>

<div class="panel">
  <div class="panel-head">
    <h3>Đơn hàng (<?= count($orders) ?>)</h3>
  </div>
  <table class="table">
    <thead>
      <tr>
        <th>Mã</th>
        <th>Khách hàng</th>
        <th>SĐT</th>
        <th>SL</th>
        <th>Tổng</th>
        <th>Ngày</th>
        <th>Trạng thái</th>
        <th>Thao tác</th>
      </tr>
    </thead>
    <tbody>
      <?php if (!$orders): ?>
        <tr>
          <td colspan="8" style="text-align:center;color:var(--muted)">Chưa có đơn hàng</td>
        </tr>
        <?php else: foreach ($orders as $o): ?>
          <tr>
            <td><strong>#<?= $o['id'] ?></strong></td>
            <td><?= e($o['customer_name']) ?></td>
            <td><?= e($o['phone']) ?></td>
            <td><?= (int)$o['items'] ?></td>
            <td><?= money($o['total']) ?></td>
            <td><?= date('d/m/Y', strtotime($o['created_at'])) ?></td>
            <td><span class="tag tag-<?= e($o['status']) ?>"><?= order_status_label($o['status']) ?></span></td>
            <td>
              <div class="actions">
                <a class="btn btn-ghost btn-sm" href="?view=<?= $o['id'] ?>">Xem</a>
                <a class="btn btn-danger btn-sm" href="?action=delete&id=<?= $o['id'] ?>"
                  data-confirm="Xoá đơn hàng #<?= $o['id'] ?>?">Xoá</a>
              </div>
            </td>
          </tr>
      <?php endforeach;
      endif; ?>
    </tbody>
  </table>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>