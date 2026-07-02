<?php
require_once __DIR__ . '/includes/functions.php';

if (is_logged_in()) redirect('/shop/index.php');

$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name     = trim($_POST['full_name'] ?? '');
    $email    = trim($_POST['email'] ?? '');
    $phone    = trim($_POST['phone'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm  = $_POST['confirm'] ?? '';

    if ($name === '')                       $errors[] = 'Vui lòng nhập họ tên.';
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'Email không hợp lệ.';
    if (strlen($password) < 6)              $errors[] = 'Mật khẩu tối thiểu 6 ký tự.';
    if ($password !== $confirm)             $errors[] = 'Mật khẩu nhập lại không khớp.';

    if (!$errors) {
        $chk = $pdo->prepare('SELECT id FROM users WHERE email = ?');
        $chk->execute([$email]);
        if ($chk->fetch()) {
            $errors[] = 'Email này đã được đăng ký.';
        }
    }

    if (!$errors) {
        $hash = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $pdo->prepare(
            'INSERT INTO users (full_name, email, phone, password, role) VALUES (?, ?, ?, ?, "customer")'
        );
        $stmt->execute([$name, $email, $phone, $hash]);

        $_SESSION['user'] = [
            'id'        => (int)$pdo->lastInsertId(),
            'full_name' => $name,
            'email'     => $email,
            'phone'     => $phone,
            'address'   => null,
            'role'      => 'customer',
        ];
        $_SESSION['flash'] = 'Đăng ký thành công! Chào mừng bạn đến TechNest.';
        redirect('/shop/index.php');
    }
}

$title = 'Đăng ký';
include __DIR__ . '/includes/header.php';
?>

<div class="auth-wrap">
  <div class="form-card">
    <h1 style="margin-bottom:6px">Tạo tài khoản</h1>
    <p style="color:var(--muted);margin-bottom:18px">Tham gia TechNest chỉ trong 1 phút.</p>

    <?php if ($errors): ?><div class="alert alert-error"><?= implode('<br>', array_map('e', $errors)) ?></div><?php endif; ?>

    <form method="post">
      <div class="field">
        <label>Họ và tên</label>
        <input type="text" name="full_name" value="<?= e($_POST['full_name'] ?? '') ?>" required autofocus>
      </div>
      <div class="field">
        <label>Email</label>
        <input type="email" name="email" value="<?= e($_POST['email'] ?? '') ?>" required>
      </div>
      <div class="field">
        <label>Số điện thoại</label>
        <input type="text" name="phone" value="<?= e($_POST['phone'] ?? '') ?>">
      </div>
      <div class="field">
        <label>Mật khẩu</label>
        <input type="password" name="password" required>
      </div>
      <div class="field">
        <label>Nhập lại mật khẩu</label>
        <input type="password" name="confirm" required>
      </div>
      <button class="btn btn-primary btn-block">Đăng ký</button>
    </form>

    <p style="text-align:center;margin-top:16px;color:var(--muted)">
      Đã có tài khoản? <a href="/shop/login.php" style="color:var(--primary);font-weight:600">Đăng nhập</a>
    </p>
  </div>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>
