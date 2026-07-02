<?php
require_once __DIR__ . '/includes/functions.php';
require_login();

$u = current_user();
$errors = [];
$ok = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    // ===== Cập nhật thông tin =====
    if ($action === 'update') {
        $name    = trim($_POST['full_name'] ?? '');
        $email   = trim($_POST['email'] ?? '');
        $phone   = trim($_POST['phone'] ?? '');
        $address = trim($_POST['address'] ?? '');

        if ($name === '')                                 $errors[] = 'Vui lòng nhập họ tên.';
        if (!filter_var($email, FILTER_VALIDATE_EMAIL))   $errors[] = 'Email không hợp lệ.';

        // email trùng người khác?
        if (!$errors) {
            $chk = $pdo->prepare('SELECT id FROM users WHERE email = ? AND id <> ?');
            $chk->execute([$email, $u['id']]);
            if ($chk->fetch()) $errors[] = 'Email này đã được người khác sử dụng.';
        }

        if (!$errors) {
            $stmt = $pdo->prepare('UPDATE users SET full_name=?, email=?, phone=?, address=? WHERE id=?');
            $stmt->execute([$name, $email, $phone, $address, $u['id']]);
            // cập nhật session
            $_SESSION['user']['full_name'] = $name;
            $_SESSION['user']['email']     = $email;
            $_SESSION['user']['phone']     = $phone;
            $_SESSION['user']['address']   = $address;
            $u = current_user();
            $ok = 'Đã cập nhật thông tin tài khoản.';
        }
    }

    // ===== Đổi mật khẩu =====
    if ($action === 'password') {
        $cur = $_POST['current'] ?? '';
        $new = $_POST['new'] ?? '';
        $cf  = $_POST['confirm'] ?? '';

        $row = $pdo->prepare('SELECT password FROM users WHERE id = ?');
        $row->execute([$u['id']]);
        $hash = $row->fetchColumn();

        if (!password_verify($cur, $hash))  $errors[] = 'Mật khẩu hiện tại không đúng.';
        elseif (strlen($new) < 6)           $errors[] = 'Mật khẩu mới tối thiểu 6 ký tự.';
        elseif ($new !== $cf)               $errors[] = 'Mật khẩu nhập lại không khớp.';

        if (!$errors) {
            $pdo->prepare('UPDATE users SET password = ? WHERE id = ?')
                ->execute([password_hash($new, PASSWORD_DEFAULT), $u['id']]);
            $ok = 'Đã đổi mật khẩu thành công.';
        }
    }

    // ===== Xoá tài khoản =====
    if ($action === 'delete') {
        $pdo->prepare('DELETE FROM users WHERE id = ?')->execute([$u['id']]);
        session_destroy();
        // dùng session mới cho flash
        session_start();
        $_SESSION['flash'] = 'Tài khoản của bạn đã được xoá.';
        redirect('/shop/index.php');
    }
}

$title = 'Tài khoản của tôi';
include __DIR__ . '/includes/header.php';
?>

<div class="section-head"><h2><span class="eyebrow">Tài khoản</span>Thông tin của tôi</h2></div>

<?php if ($ok): ?><div class="alert alert-ok"><?= e($ok) ?></div><?php endif; ?>
<?php if ($errors): ?><div class="alert alert-error"><?= implode('<br>', array_map('e', $errors)) ?></div><?php endif; ?>

<div class="cart-layout">
  <div>
    <!-- Thông tin tài khoản -->
    <form class="form-card" method="post">
      <input type="hidden" name="action" value="update">
      <h3 style="margin-bottom:16px">Hồ sơ</h3>
      <div class="field">
        <label>Họ và tên</label>
        <input type="text" name="full_name" value="<?= e($u['full_name']) ?>" required>
      </div>
      <div class="field">
        <label>Email</label>
        <input type="email" name="email" value="<?= e($u['email']) ?>" required>
      </div>
      <div class="field">
        <label>Số điện thoại</label>
        <input type="text" name="phone" value="<?= e($u['phone'] ?? '') ?>">
      </div>
      <div class="field">
        <label>Địa chỉ</label>
        <input type="text" name="address" value="<?= e($u['address'] ?? '') ?>">
      </div>
      <button class="btn btn-primary">💾 Lưu thay đổi</button>
    </form>

    <!-- Đổi mật khẩu -->
    <form class="form-card" method="post" style="margin-top:18px">
      <input type="hidden" name="action" value="password">
      <h3 style="margin-bottom:16px">Đổi mật khẩu</h3>
      <div class="field">
        <label>Mật khẩu hiện tại</label>
        <input type="password" name="current" required>
      </div>
      <div style="display:grid;grid-template-columns:1fr 1fr;gap:14px">
        <div class="field">
          <label>Mật khẩu mới</label>
          <input type="password" name="new" required>
        </div>
        <div class="field">
          <label>Nhập lại mật khẩu mới</label>
          <input type="password" name="confirm" required>
        </div>
      </div>
      <button class="btn btn-primary">Đổi mật khẩu</button>
    </form>
  </div>

  <aside>
    <div class="cart-summary">
      <div style="display:flex;align-items:center;gap:12px;margin-bottom:14px">
        <span class="av" style="width:48px;height:48px;font-size:1.2rem;border-radius:50%;background:var(--primary);color:#fff;display:flex;align-items:center;justify-content:center;font-weight:700">
          <?= e(mb_strtoupper(mb_substr($u['full_name'],0,1))) ?>
        </span>
        <div>
          <strong><?= e($u['full_name']) ?></strong><br>
          <span style="color:var(--muted);font-size:.88rem"><?= e($u['email']) ?></span>
        </div>
      </div>
      <span class="tag tag-<?= $u['role']==='admin'?'admin':'customer' ?>">
        <?= $u['role']==='admin' ? 'Quản trị viên' : 'Khách hàng' ?>
      </span>

      <div style="margin-top:20px;display:flex;flex-direction:column;gap:10px">
        <a class="btn btn-ghost btn-block" href="/shop/orders.php">📦 Đơn hàng của tôi</a>
        <a class="btn btn-ghost btn-block" href="/shop/logout.php">🚪 Đăng xuất</a>
      </div>
    </div>

    <div class="cart-summary" style="margin-top:16px;border:1px solid #f3d4d4">
      <h3 style="margin-bottom:8px;color:var(--danger)">Vùng nguy hiểm</h3>
      <p style="color:var(--muted);font-size:.88rem;margin-bottom:12px">Xoá tài khoản là vĩnh viễn và không thể khôi phục.</p>
      <form method="post">
        <input type="hidden" name="action" value="delete">
        <button class="btn btn-danger btn-block"
                data-confirm="Bạn chắc chắn muốn XOÁ tài khoản? Hành động này không thể hoàn tác.">
          🗑️ Xoá tài khoản
        </button>
      </form>
    </div>
  </aside>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>
