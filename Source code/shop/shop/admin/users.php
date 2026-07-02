<?php
require_once __DIR__ . '/../includes/functions.php';
require_admin('/shop/login.php');

$me = current_user();

// Đổi quyền
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id   = (int)($_POST['id'] ?? 0);
    $role = $_POST['role'] ?? '';
    if (in_array($role, ['admin','customer'], true) && $id !== (int)$me['id']) {
        $pdo->prepare('UPDATE users SET role = ? WHERE id = ?')->execute([$role, $id]);
        $_SESSION['flash'] = 'Đã cập nhật phân quyền người dùng.';
    } elseif ($id === (int)$me['id']) {
        $_SESSION['flash'] = 'Không thể tự đổi quyền của chính mình.';
    }
    redirect('/shop/admin/users.php');
}

// Xoá user (không cho tự xoá)
if (($_GET['action'] ?? '') === 'delete') {
    $id = (int)($_GET['id'] ?? 0);
    if ($id !== (int)$me['id']) {
        $pdo->prepare('DELETE FROM users WHERE id = ?')->execute([$id]);
        $_SESSION['flash'] = 'Đã xoá người dùng.';
    }
    redirect('/shop/admin/users.php');
}

$users = $pdo->query(
    'SELECT u.*, (SELECT COUNT(*) FROM orders o WHERE o.user_id=u.id) AS order_count
     FROM users u ORDER BY u.created_at DESC'
)->fetchAll();

$title  = 'Quản lý người dùng';
$active = 'users';
include __DIR__ . '/includes/header.php';
?>

<div class="panel">
  <div class="panel-head"><h3>Người dùng (<?= count($users) ?>)</h3></div>
  <table class="table">
    <thead>
      <tr><th>Họ tên</th><th>Email</th><th>SĐT</th><th>Đơn hàng</th><th>Vai trò</th><th>Thao tác</th></tr>
    </thead>
    <tbody>
      <?php foreach ($users as $u): ?>
      <tr>
        <td><strong><?= e($u['full_name']) ?></strong>
            <?= (int)$u['id']===(int)$me['id'] ? '<span class="tag tag-admin" style="margin-left:6px">Bạn</span>' : '' ?></td>
        <td><?= e($u['email']) ?></td>
        <td><?= e($u['phone'] ?: '—') ?></td>
        <td><?= (int)$u['order_count'] ?></td>
        <td><span class="tag tag-<?= $u['role'] ?>"><?= $u['role']==='admin'?'Quản trị':'Khách hàng' ?></span></td>
        <td>
          <?php if ((int)$u['id'] !== (int)$me['id']): ?>
          <div class="actions">
            <form method="post" style="display:inline-flex;gap:6px">
              <input type="hidden" name="id" value="<?= $u['id'] ?>">
              <select name="role" style="padding:6px 10px;border:1px solid var(--line);border-radius:8px">
                <option value="customer" <?= $u['role']==='customer'?'selected':'' ?>>Khách hàng</option>
                <option value="admin"    <?= $u['role']==='admin'?'selected':'' ?>>Quản trị</option>
              </select>
              <button class="btn btn-ghost btn-sm">Lưu</button>
            </form>
            <a class="btn btn-danger btn-sm" href="?action=delete&id=<?= $u['id'] ?>"
               data-confirm="Xoá người dùng “<?= e($u['full_name']) ?>”?">Xoá</a>
          </div>
          <?php else: ?>
            <span style="color:var(--muted)">—</span>
          <?php endif; ?>
        </td>
      </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>
