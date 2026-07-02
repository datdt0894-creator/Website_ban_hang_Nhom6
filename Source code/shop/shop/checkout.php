<?php
require_once __DIR__ . '/includes/functions.php';

if (is_admin()) {
    $_SESSION['flash'] = 'Tài khoản quản trị không thể đặt hàng.';
    redirect('/shop/admin/index.php');
}

$cart = $_SESSION['cart'] ?? [];
if (!$cart) {
    redirect('/shop/cart.php');
}

$action = $_POST['action'] ?? '';

// ---- Nhận lựa chọn từ trang giỏ hàng (PRG: lưu rồi chuyển hướng) ----
if ($action === 'select') {
    $sel = array_map('intval', (array)($_POST['selected'] ?? []));
    $sel = array_values(array_filter($sel, fn($id) => isset($cart[$id])));
    if (!$sel) {
        $_SESSION['flash'] = 'Vui lòng chọn ít nhất một sản phẩm để thanh toán.';
        redirect('/shop/cart.php');
    }
    $_SESSION['checkout_items'] = $sel;
    redirect('/shop/checkout.php');
}

// ---- Xác định sản phẩm sẽ thanh toán (mặc định: cả giỏ) ----
$ids = $_SESSION['checkout_items'] ?? array_keys($cart);
$ids = array_values(array_filter($ids, fn($id) => isset($cart[$id])));
if (!$ids) {
    redirect('/shop/cart.php');
}
$_SESSION['checkout_items'] = $ids;

$items = [];
$total = 0;
foreach ($ids as $id) {
    $items[$id] = $cart[$id];
    $total += $cart[$id]['price'] * $cart[$id]['quantity'];
}

$u = current_user();
$errors = [];

// ---- Đặt hàng (chỉ với các sản phẩm đã chọn) ----
if ($action === 'place') {
    $name    = trim($_POST['customer_name'] ?? '');
    $phone   = trim($_POST['phone'] ?? '');
    $address = trim($_POST['address'] ?? '');
    $note    = trim($_POST['note'] ?? '');

    if ($name === '')    $errors[] = 'Vui lòng nhập họ tên.';
    if ($phone === '')   $errors[] = 'Vui lòng nhập số điện thoại.';
    if ($address === '') $errors[] = 'Vui lòng nhập địa chỉ giao hàng.';

    if (!$errors) {
        try {
            $pdo->beginTransaction();
            $eta = date('Y-m-d', strtotime('+4 days'));
            $stmt = $pdo->prepare(
                'INSERT INTO orders (user_id, customer_name, phone, address, note, total, status, estimated_delivery)
                 VALUES (?, ?, ?, ?, ?, ?, "pending", ?)'
            );
            $stmt->execute([$u['id'] ?? null, $name, $phone, $address, $note, $total, $eta]);
            $orderId = (int)$pdo->lastInsertId();

            $insItem  = $pdo->prepare(
                'INSERT INTO order_items (order_id, product_id, product_name, price, quantity)
                 VALUES (?, ?, ?, ?, ?)'
            );
            $decStock = $pdo->prepare('UPDATE products SET stock = stock - ? WHERE id = ? AND stock >= ?');

            foreach ($items as $item) {
                $insItem->execute([$orderId, $item['id'], $item['name'], $item['price'], $item['quantity']]);
                $decStock->execute([$item['quantity'], $item['id'], $item['quantity']]);
                // chỉ xoá khỏi giỏ những sản phẩm đã đặt
                unset($_SESSION['cart'][$item['id']]);
            }

            $pdo->commit();
            unset($_SESSION['checkout_items']);
            // Ghi nhớ đơn để khách (kể cả khách vãng lai) xem lại được
            remember_guest_order($orderId);
            $_SESSION['flash'] = 'Đặt hàng thành công! Mã đơn #' . $orderId;
            redirect('/shop/order_success.php?id=' . $orderId);

        } catch (Exception $ex) {
            $pdo->rollBack();
            $errors[] = 'Có lỗi khi tạo đơn hàng, vui lòng thử lại.';
        }
    }
}

$title = 'Thanh toán';
include __DIR__ . '/includes/header.php';
?>

<div class="section-head"><h2><span class="eyebrow">Bước 2/2</span>Thông tin giao hàng</h2></div>

<?php if ($errors): ?>
  <div class="alert alert-error"><?= implode('<br>', array_map('e', $errors)) ?></div>
<?php endif; ?>

<div class="cart-layout">
  <form class="form-card" method="post">
    <input type="hidden" name="action" value="place">
    <div class="field">
      <label>Họ và tên *</label>
      <input type="text" name="customer_name" value="<?= e($_POST['customer_name'] ?? ($u['full_name'] ?? '')) ?>" required>
    </div>
    <div class="field">
      <label>Số điện thoại *</label>
      <input type="text" name="phone" value="<?= e($_POST['phone'] ?? ($u['phone'] ?? '')) ?>" required>
    </div>
    <div class="field">
      <label>Địa chỉ giao hàng *</label>
      <input type="text" name="address" value="<?= e($_POST['address'] ?? ($u['address'] ?? '')) ?>" required>
    </div>
    <div class="field">
      <label>Ghi chú (tuỳ chọn)</label>
      <textarea name="note" placeholder="Giao giờ hành chính, gọi trước khi đến..."><?= e($_POST['note'] ?? '') ?></textarea>
    </div>
    <p style="color:var(--muted);font-size:.9rem;margin-bottom:14px">💳 Hình thức: Thanh toán khi nhận hàng (COD)</p>
    <button class="btn btn-accent btn-block">Đặt hàng — <?= money($total) ?></button>
    <a href="/shop/cart.php" style="display:block;text-align:center;margin-top:12px;color:var(--muted)">← Quay lại giỏ hàng</a>
  </form>

  <aside class="cart-summary">
    <h3 style="margin-bottom:12px">Đơn hàng (<?= count($items) ?> sản phẩm)</h3>
    <?php foreach ($items as $item): ?>
      <div class="row" style="font-size:.92rem">
        <span><?= e($item['name']) ?> × <?= $item['quantity'] ?></span>
        <span><?= money($item['price'] * $item['quantity']) ?></span>
      </div>
    <?php endforeach; ?>
    <div class="row total"><span>Tổng cộng</span><span><?= money($total) ?></span></div>
  </aside>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>
