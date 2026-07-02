<?php
require_once __DIR__ . '/includes/functions.php';

if (!isset($_SESSION['cart'])) {
  $_SESSION['cart'] = [];
}

// ---- Xoá 1 sản phẩm (GET) ----
if (($_GET['action'] ?? '') === 'remove') {
  $id = (int)($_GET['id'] ?? 0);
  unset($_SESSION['cart'][$id]);
  redirect('/shop/cart.php');
}

// ---- Xử lý các hành động (POST) ----
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $action = $_POST['action'] ?? '';

  // Tài khoản quản trị KHÔNG được đặt hàng / dùng giỏ hàng
  if (is_admin()) {
    if (!empty($_POST['ajax'])) {
      header('Content-Type: application/json; charset=utf-8');
      echo json_encode(['ok' => false, 'count' => 0, 'name' => '', 'msg' => 'admin']);
      exit;
    }
    $_SESSION['flash'] = 'Tài khoản quản trị không thể đặt hàng.';
    redirect('/shop/admin/index.php');
  }

  if ($action === 'add') {
    $id   = (int)($_POST['id'] ?? 0);
    $qty  = max(1, (int)($_POST['qty'] ?? 1));
    $ajax = !empty($_POST['ajax']);
    $stmt = $pdo->prepare('SELECT * FROM products WHERE id = ?');
    $stmt->execute([$id]);
    $prod = $stmt->fetch();

    $ok = false;
    if ($prod && (int)$prod['stock'] > 0) {
      $current = $_SESSION['cart'][$id]['quantity'] ?? 0;
      $newQty  = min($current + $qty, (int)$prod['stock']);
      $_SESSION['cart'][$id] = [
        'id'       => (int)$prod['id'],
        'name'     => $prod['name'],
        'price'    => (float)$prod['price'],
        'image'    => $prod['image'],
        'stock'    => (int)$prod['stock'],
        'quantity' => $newQty,
      ];
      $ok = true;
    }

    // Mua ngay -> chọn đúng sản phẩm này rồi sang thẳng thanh toán
    if (!empty($_POST['buy_now'])) {
      if ($ok) $_SESSION['checkout_items'] = [$id];
      redirect('/shop/checkout.php');
    }

    // Thêm vào giỏ qua AJAX -> trả JSON, KHÔNG chuyển trang
    if ($ajax) {
      header('Content-Type: application/json; charset=utf-8');
      echo json_encode([
        'ok'    => $ok,
        'count' => cart_count(),
        'name'  => $prod['name'] ?? '',
      ]);
      exit;
    }

    // Không có JS: ở lại trang trước đó (không nhảy sang giỏ hàng)
    if ($ok) $_SESSION['flash'] = 'Đã thêm “' . $prod['name'] . '” vào giỏ hàng.';
    $back = $_SERVER['HTTP_REFERER'] ?? '/shop/index.php';
    redirect($back);
  }

  if ($action === 'update') {
    foreach (($_POST['qty'] ?? []) as $id => $q) {
      $id = (int)$id;
      $q = (int)$q;
      if (isset($_SESSION['cart'][$id])) {
        if ($q < 1) {
          unset($_SESSION['cart'][$id]);
        } else {
          $max = $_SESSION['cart'][$id]['stock'] ?? 999;
          $_SESSION['cart'][$id]['quantity'] = min($q, $max);
        }
      }
    }
    $_SESSION['flash'] = 'Đã cập nhật giỏ hàng.';
    redirect('/shop/cart.php');
  }

  if ($action === 'remove') {
    $id = (int)($_POST['id'] ?? 0);
    unset($_SESSION['cart'][$id]);
    redirect('/shop/cart.php');
  }

  if ($action === 'clear') {
    $_SESSION['cart'] = [];
    redirect('/shop/cart.php');
  }
}

$cart  = $_SESSION['cart'];
$total = 0;
foreach ($cart as $item) {
  $total += $item['price'] * $item['quantity'];
}

$title = 'Giỏ hàng';
include __DIR__ . '/includes/header.php';
?>

<div class="section-head">
  <h2><span class="eyebrow">Bước 1/2</span>Giỏ hàng của bạn</h2>
</div>

<?php if (!$cart): ?>
  <div class="empty-state">
    <div class="ico">🛒</div>
    <h3>Giỏ hàng đang trống</h3>
    <p>Hãy chọn vài sản phẩm yêu thích nhé!</p>
    <a class="btn btn-primary" href="/shop/index.php">Tiếp tục mua sắm</a>
  </div>
<?php else: ?>
  <form method="post" action="/shop/checkout.php" id="cartForm">
    <div class="cart-layout">
      <div>
        <table class="cart-table">
          <thead>
            <tr>
              <th style="width:40px"><input type="checkbox" id="selAll" checked title="Chọn tất cả"></th>
              <th>Sản phẩm</th>
              <th>Giá</th>
              <th>Số lượng</th>
              <th>Tạm tính</th>
              <th></th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($cart as $item): ?>
              <tr>
                <td style="text-align:center">
                  <input type="checkbox" class="sel-item" name="selected[]" value="<?= $item['id'] ?>"
                    data-price="<?= $item['price'] ?>" checked>
                </td>
                <td>
                  <div class="cart-item">
                    <img src="<?= e(product_image($item['image'])) ?>" alt="">
                    <a href="/shop/product.php?id=<?= $item['id'] ?>"><?= e($item['name']) ?></a>
                  </div>
                </td>
                <td><?= money($item['price']) ?></td>
                <td>
                  <input type="number" class="qty-item" data-id="<?= $item['id'] ?>" name="qty[<?= $item['id'] ?>]" value="<?= $item['quantity'] ?>"
                    min="1" max="<?= $item['stock'] ?>" style="width:70px;padding:8px;border:1px solid var(--line);border-radius:8px">
                </td>
                <td><strong><?= money($item['price'] * $item['quantity']) ?></strong></td>
                <td>
                  <a class="btn btn-danger btn-sm" href="/shop/cart.php?action=remove&id=<?= $item['id'] ?>"
                    data-confirm="Xoá sản phẩm này khỏi giỏ?">✕</a>
                </td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
        <div style="display:flex;gap:10px;margin-top:14px;flex-wrap:wrap">
          <button class="btn btn-ghost" type="submit" formaction="/shop/cart.php" name="action" value="update">Cập nhật giỏ</button>
          <a class="btn btn-ghost" href="/shop/index.php">← Tiếp tục mua</a>
        </div>
      </div>

      <aside class="cart-summary">
        <h3 style="margin-bottom:10px">Tóm tắt đơn</h3>
        <div class="row"><span>Đã chọn</span><span id="selCount"><?= count($cart) ?> sản phẩm</span></div>
        <div class="row"><span>Phí vận chuyển</span><span>Miễn phí</span></div>
        <div class="row total"><span>Tổng cộng</span><span id="selTotal"><?= money($total) ?></span></div>
        <button class="btn btn-accent btn-block" type="submit" name="action" value="select" style="margin-top:16px">
          Tiến hành thanh toán
        </button>
        <p style="color:var(--muted);font-size:.82rem;margin-top:10px;text-align:center">Chỉ những sản phẩm được tick mới được đặt.</p>
      </aside>
    </div>
  </form>

  <form method="post" style="margin-top:4px">
    <input type="hidden" name="action" value="clear">
    <button class="btn btn-ghost btn-sm" data-confirm="Xoá toàn bộ giỏ hàng?">Xoá tất cả</button>
  </form>
<?php endif; ?>

<?php include __DIR__ . '/includes/footer.php'; ?>