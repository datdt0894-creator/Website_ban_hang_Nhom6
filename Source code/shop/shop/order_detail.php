<?php
require_once __DIR__ . '/includes/functions.php';

$id = (int)($_GET['id'] ?? 0);

// Lấy đơn theo mã, sau đó kiểm tra quyền xem (chủ tài khoản hoặc khách đã đặt trong phiên)
$stmt = $pdo->prepare('SELECT * FROM orders WHERE id = ?');
$stmt->execute([$id]);
$order = $stmt->fetch();
if (!can_view_order($order)) {
    $_SESSION['flash'] = 'Không tìm thấy đơn hàng.';
    redirect('/shop/orders.php');
}

$errors = [];

// ---------- Xử lý hành động ----------
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    // ===== Huỷ đơn (không cần admin duyệt) =====
    if ($action === 'cancel') {
        $reason = trim($_POST['cancel_reason'] ?? '');
        if (!can_cancel_order($order['status'])) {
            $errors[] = 'Đơn hàng này không thể huỷ (đã giao hoặc đã xử lý).';
        } elseif ($reason === '') {
            $errors[] = 'Vui lòng nhập lý do huỷ đơn.';
        } else {
            try {
                $pdo->beginTransaction();
                // Hoàn lại tồn kho
                $its = $pdo->prepare('SELECT product_id, quantity FROM order_items WHERE order_id = ?');
                $its->execute([$order['id']]);
                $restore = $pdo->prepare('UPDATE products SET stock = stock + ? WHERE id = ?');
                foreach ($its->fetchAll() as $it) {
                    if ($it['product_id']) {
                        $restore->execute([$it['quantity'], $it['product_id']]);
                    }
                }
                $upd = $pdo->prepare('UPDATE orders SET status = "cancelled", cancel_reason = ? WHERE id = ?');
                $upd->execute([$reason, $order['id']]);
                $pdo->commit();
                $_SESSION['flash'] = 'Đã huỷ đơn hàng #' . $order['id'] . '.';
                redirect('/shop/order_detail.php?id=' . $order['id']);
            } catch (Exception $ex) {
                $pdo->rollBack();
                $errors[] = 'Có lỗi khi huỷ đơn, vui lòng thử lại.';
            }
        }
    }

    // ===== Yêu cầu hoàn hàng (chờ admin duyệt) =====
    if ($action === 'return') {
        $reason = trim($_POST['return_reason'] ?? '');
        $method = trim($_POST['refund_method'] ?? '');

        // Đã có yêu cầu đang chờ / đã duyệt chưa?
        $chk = $pdo->prepare("SELECT COUNT(*) FROM order_returns WHERE order_id = ? AND status IN ('pending','approved')");
        $chk->execute([$order['id']]);
        $hasReturn = (int)$chk->fetchColumn() > 0;

        if (!can_return_order($order['status'])) {
            $errors[] = 'Chỉ có thể yêu cầu hoàn hàng với đơn đã hoàn thành.';
        } elseif ($hasReturn) {
            $errors[] = 'Đơn này đã có yêu cầu hoàn hàng.';
        } elseif ($reason === '') {
            $errors[] = 'Vui lòng nhập lý do hoàn hàng.';
        } elseif (!in_array($method, refund_methods(), true)) {
            $errors[] = 'Vui lòng chọn phương thức hoàn tiền.';
        } else {
            // Upload ảnh (bắt buộc)
            $image = null;
            if (!empty($_FILES['image']['name']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
                $ext = strtolower(pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION));
                if (in_array($ext, ['jpg', 'jpeg', 'png', 'webp', 'gif'], true)) {
                    $image = 'return_' . time() . '_' . mt_rand(1000, 9999) . '.' . $ext;
                    move_uploaded_file($_FILES['image']['tmp_name'], __DIR__ . '/uploads/' . $image);
                } else {
                    $errors[] = 'Ảnh không hợp lệ (chỉ chấp nhận jpg, png, webp, gif).';
                }
            } else {
                $errors[] = 'Vui lòng đính kèm ảnh đơn hàng / sản phẩm.';
            }

            if (!$errors) {
                $ins = $pdo->prepare(
                    'INSERT INTO order_returns (order_id, reason, refund_method, image, status)
                     VALUES (?, ?, ?, ?, "pending")'
                );
                $ins->execute([$order['id'], $reason, $method, $image]);
                $pdo->prepare('UPDATE orders SET status = "return_requested" WHERE id = ?')->execute([$order['id']]);
                $_SESSION['flash'] = 'Đã gửi yêu cầu hoàn hàng. Vui lòng chờ quản trị viên duyệt.';
                redirect('/shop/order_detail.php?id=' . $order['id']);
            }
        }
    }
}

// Lấy lại đơn + chi tiết + yêu cầu hoàn (sau xử lý)
$stmt->execute([$id]);
$order = $stmt->fetch();

$items = $pdo->prepare('SELECT * FROM order_items WHERE order_id = ?');
$items->execute([$id]);
$items = $items->fetchAll();

$ret = $pdo->prepare('SELECT * FROM order_returns WHERE order_id = ? ORDER BY created_at DESC LIMIT 1');
$ret->execute([$id]);
$return = $ret->fetch();

$title = 'Đơn hàng #' . $order['id'];
$page  = 'orders';
include __DIR__ . '/includes/header.php';
?>

<p style="margin:18px 0;color:var(--muted)">
  <a href="/shop/orders.php">← Đơn hàng của tôi</a>
</p>

<?php if ($errors): ?>
  <div class="alert alert-error"><?= implode('<br>', array_map('e', $errors)) ?></div>
<?php endif; ?>

<div class="cart-layout">
  <div>
    <div class="panel">
      <div class="panel-head">
        <h3>Đơn hàng #<?= $order['id'] ?></h3>
        <span class="tag tag-<?= e($order['status']) ?>"><?= order_status_label($order['status']) ?></span>
      </div>
      <table class="cart-table">
        <thead><tr><th>Sản phẩm</th><th>Đơn giá</th><th>SL</th><th>Thành tiền</th></tr></thead>
        <tbody>
          <?php foreach ($items as $it): ?>
          <tr>
            <td><?= e($it['product_name']) ?></td>
            <td><?= money($it['price']) ?></td>
            <td><?= $it['quantity'] ?></td>
            <td><strong><?= money($it['price'] * $it['quantity']) ?></strong></td>
          </tr>
          <?php endforeach; ?>
          <tr><td colspan="3"><strong>Tổng cộng</strong></td><td><strong><?= money($order['total']) ?></strong></td></tr>
        </tbody>
      </table>
    </div>

    <?php if ($order['status'] === 'cancelled' && $order['cancel_reason']): ?>
      <div class="alert alert-error" style="margin-top:16px"><strong>Đã huỷ.</strong> Lý do: <?= e($order['cancel_reason']) ?></div>
    <?php endif; ?>

    <?php if ($return): ?>
      <div class="panel" style="margin-top:16px">
        <div class="panel-head">
          <h3>Yêu cầu hoàn hàng</h3>
          <span class="tag tag-<?= e($return['status']) ?>"><?= return_status_label($return['status']) ?></span>
        </div>
        <div style="padding:18px;line-height:1.9">
          <strong>Lý do:</strong> <?= e($return['reason']) ?><br>
          <strong>Phương thức hoàn tiền:</strong> <?= e($return['refund_method']) ?><br>
          <strong>Ngày gửi:</strong> <?= date('d/m/Y H:i', strtotime($return['created_at'])) ?>
          <?php if ($return['admin_note']): ?><br><strong>Phản hồi của cửa hàng:</strong> <?= e($return['admin_note']) ?><?php endif; ?>
          <?php if ($return['image']): ?>
            <div style="margin-top:10px"><img src="<?= e(product_image($return['image'])) ?>" alt="Ảnh hoàn hàng" style="max-width:240px;border-radius:10px;border:1px solid var(--line)"></div>
          <?php endif; ?>
          <?php if ($return['status'] === 'pending'): ?>
            <p style="margin-top:10px;color:var(--warn)">⏳ Đang chờ quản trị viên kiểm duyệt.</p>
          <?php elseif ($return['status'] === 'approved'): ?>
            <p style="margin-top:10px;color:var(--ok)">✅ Yêu cầu đã được duyệt — hoàn hàng hoàn tất.</p>
          <?php else: ?>
            <p style="margin-top:10px;color:var(--danger)">❌ Yêu cầu bị từ chối.</p>
          <?php endif; ?>
        </div>
      </div>
    <?php endif; ?>
  </div>

  <aside>
    <div class="cart-summary">
      <h3 style="margin-bottom:10px">Thông tin giao hàng</h3>
      <div style="line-height:2;color:var(--ink)">
        <strong><?= e($order['customer_name']) ?></strong><br>
        📞 <?= e($order['phone']) ?><br>
        📍 <?= e($order['address']) ?><br>
        🕒 Đặt: <?= date('d/m/Y H:i', strtotime($order['created_at'])) ?><br>
        🚚 Giao dự kiến: <strong><?= $order['estimated_delivery'] ? date('d/m/Y', strtotime($order['estimated_delivery'])) : 'Đang cập nhật' ?></strong>
      </div>
    </div>

    <?php if (can_cancel_order($order['status'])): ?>
      <div class="cart-summary" style="margin-top:16px">
        <h3 style="margin-bottom:10px">Huỷ đơn hàng</h3>
        <p style="color:var(--muted);font-size:.9rem;margin-bottom:10px">Đơn đang chờ xác nhận — bạn có thể huỷ ngay, không cần chờ duyệt.</p>
        <form method="post">
          <input type="hidden" name="action" value="cancel">
          <div class="field">
            <textarea name="cancel_reason" placeholder="Lý do huỷ đơn..." required></textarea>
          </div>
          <button class="btn btn-danger btn-block" data-confirm="Xác nhận huỷ đơn hàng này?">Huỷ đơn hàng</button>
        </form>
      </div>
    <?php endif; ?>

    <?php if (can_return_order($order['status']) && !$return): ?>
      <div class="cart-summary" style="margin-top:16px">
        <h3 style="margin-bottom:10px">Yêu cầu hoàn hàng</h3>
        <p style="color:var(--muted);font-size:.9rem;margin-bottom:10px">Gửi kèm ảnh, lý do và phương thức hoàn tiền. Quản trị viên sẽ kiểm duyệt.</p>
        <form method="post" enctype="multipart/form-data">
          <input type="hidden" name="action" value="return">
          <div class="field">
            <label>Lý do hoàn hàng *</label>
            <textarea name="return_reason" placeholder="Sản phẩm lỗi, giao sai..." required></textarea>
          </div>
          <div class="field">
            <label>Phương thức hoàn tiền *</label>
            <select name="refund_method" required>
              <option value="">— Chọn —</option>
              <?php foreach (refund_methods() as $m): ?>
                <option value="<?= e($m) ?>"><?= e($m) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="field">
            <label>Ảnh đơn hàng / sản phẩm *</label>
            <input type="file" name="image" accept="image/*" required>
          </div>
          <button class="btn btn-accent btn-block">Gửi yêu cầu hoàn hàng</button>
        </form>
      </div>
    <?php endif; ?>
  </aside>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>
