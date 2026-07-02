<?php
require_once __DIR__ . '/includes/functions.php';

if (is_logged_in()) redirect('/shop/index.php');

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email    = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    $stmt = $pdo->prepare('SELECT * FROM users WHERE email = ?');
    $stmt->execute([$email]);
    $user = $stmt->fetch();

    if ($user && password_verify($password, $user['password'])) {
        // Lưu thông tin (không lưu mật khẩu) vào session
        $_SESSION['user'] = [
            'id'        => (int)$user['id'],
            'full_name' => $user['full_name'],
            'email'     => $user['email'],
            'phone'     => $user['phone'],
            'address'   => $user['address'],
            'role'      => $user['role'],
        ];
        // Phân quyền điều hướng
        if ($user['role'] === 'admin') {
            redirect('/shop/admin/index.php');
        }
        redirect('/shop/index.php');
    } else {
        $error = 'Email hoặc mật khẩu không đúng.';
    }
}

$title = 'Đăng nhập';
include __DIR__ . '/includes/header.php';
?>

<div class="auth-wrap">
  <div class="form-card">
    <h1 style="margin-bottom:6px">Đăng nhập</h1>
    <p style="color:var(--muted);margin-bottom:18px">Chào mừng bạn quay lại TechNest 👋</p>

    <?php if ($error): ?><div class="alert alert-error"><?= e($error) ?></div><?php endif; ?>

    <form method="post">
      <div class="field">
        <label>Email</label>
        <input type="email" name="email" value="<?= e($_POST['email'] ?? '') ?>" required autofocus>
      </div>
      <div class="field">
        <label>Mật khẩu</label>
        <input type="password" name="password" required>
      </div>
      <button class="btn btn-primary btn-block">Đăng nhập</button>
    </form>

    <p style="text-align:center;margin-top:16px;color:var(--muted)">
      Chưa có tài khoản? <a href="/shop/register.php" style="color:var(--primary);font-weight:600">Đăng ký</a>
    </p>

    <div class="alert alert-info" style="font-size:.85rem;margin-top:18px">
      <strong>Tài khoản demo:</strong><br>
      Admin: admin@technest.vn / 123456<br>
      Khách: user@technest.vn / 123456
    </div>
  </div>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>
