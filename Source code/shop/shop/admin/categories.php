<?php
require_once __DIR__ . '/../includes/functions.php';
require_admin('/shop/login.php');

function slugify($text)
{
    $text = mb_strtolower(trim($text), 'UTF-8');
    $map = ['à','á','ạ','ả','ã','â','ầ','ấ','ậ','ẩ','ẫ','ă','ằ','ắ','ặ','ẳ','ẵ',
            'è','é','ẹ','ẻ','ẽ','ê','ề','ế','ệ','ể','ễ','ì','í','ị','ỉ','ĩ',
            'ò','ó','ọ','ỏ','õ','ô','ồ','ố','ộ','ổ','ỗ','ơ','ờ','ớ','ợ','ở','ỡ',
            'ù','ú','ụ','ủ','ũ','ư','ừ','ứ','ự','ử','ữ','ỳ','ý','ỵ','ỷ','ỹ','đ'];
    $rep = ['a','a','a','a','a','a','a','a','a','a','a','a','a','a','a','a','a',
            'e','e','e','e','e','e','e','e','e','e','e','i','i','i','i','i',
            'o','o','o','o','o','o','o','o','o','o','o','o','o','o','o','o','o',
            'u','u','u','u','u','u','u','u','u','u','u','y','y','y','y','y','d'];
    $text = str_replace($map, $rep, $text);
    $text = preg_replace('/[^a-z0-9]+/', '-', $text);
    return trim($text, '-');
}

// Thêm / Sửa
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id   = (int)($_POST['id'] ?? 0);
    $name = trim($_POST['name'] ?? '');
    if ($name !== '') {
        $slug = slugify($name);
        if ($id > 0) {
            $pdo->prepare('UPDATE categories SET name=?, slug=? WHERE id=?')->execute([$name, $slug, $id]);
            $_SESSION['flash'] = 'Đã cập nhật danh mục.';
        } else {
            $pdo->prepare('INSERT INTO categories (name, slug) VALUES (?, ?)')->execute([$name, $slug]);
            $_SESSION['flash'] = 'Đã thêm danh mục.';
        }
    }
    redirect('/shop/admin/categories.php');
}

// Xoá
if (($_GET['action'] ?? '') === 'delete') {
    $id = (int)($_GET['id'] ?? 0);
    $pdo->prepare('DELETE FROM categories WHERE id = ?')->execute([$id]);
    $_SESSION['flash'] = 'Đã xoá danh mục.';
    redirect('/shop/admin/categories.php');
}

// Dữ liệu sửa
$edit = null;
if (($_GET['action'] ?? '') === 'edit') {
    $stmt = $pdo->prepare('SELECT * FROM categories WHERE id = ?');
    $stmt->execute([(int)($_GET['id'] ?? 0)]);
    $edit = $stmt->fetch();
}

$rows = $pdo->query(
    'SELECT c.*, (SELECT COUNT(*) FROM products p WHERE p.category_id=c.id) AS product_count
     FROM categories c ORDER BY c.name'
)->fetchAll();

$title  = 'Quản lý danh mục';
$active = 'categories';
include __DIR__ . '/includes/header.php';
?>

<div style="display:grid;grid-template-columns:1fr 320px;gap:18px;align-items:start">
  <div class="panel">
    <div class="panel-head"><h3>Danh mục (<?= count($rows) ?>)</h3></div>
    <table class="table">
      <thead><tr><th>Tên</th><th>Slug</th><th>Số SP</th><th>Thao tác</th></tr></thead>
      <tbody>
        <?php if (!$rows): ?>
          <tr><td colspan="4" style="text-align:center;color:var(--muted)">Chưa có danh mục</td></tr>
        <?php else: foreach ($rows as $c): ?>
          <tr>
            <td><strong><?= e($c['name']) ?></strong></td>
            <td style="color:var(--muted)"><?= e($c['slug']) ?></td>
            <td><?= $c['product_count'] ?></td>
            <td>
              <div class="actions">
                <a class="btn btn-ghost btn-sm" href="?action=edit&id=<?= $c['id'] ?>">Sửa</a>
                <a class="btn btn-danger btn-sm" href="?action=delete&id=<?= $c['id'] ?>"
                   data-confirm="Xoá danh mục “<?= e($c['name']) ?>”? Sản phẩm sẽ bị gỡ khỏi danh mục.">Xoá</a>
              </div>
            </td>
          </tr>
        <?php endforeach; endif; ?>
      </tbody>
    </table>
  </div>

  <div class="panel">
    <div class="panel-head"><h3><?= $edit ? 'Sửa danh mục' : 'Thêm danh mục' ?></h3></div>
    <form method="post" style="padding:20px">
      <input type="hidden" name="id" value="<?= $edit ? (int)$edit['id'] : 0 ?>">
      <div class="field">
        <label>Tên danh mục</label>
        <input type="text" name="name" value="<?= e($edit['name'] ?? '') ?>" required autofocus>
      </div>
      <button class="btn btn-primary btn-block"><?= $edit ? 'Cập nhật' : 'Thêm mới' ?></button>
      <?php if ($edit): ?>
        <a class="btn btn-ghost btn-block" href="/shop/admin/categories.php" style="margin-top:8px">Huỷ</a>
      <?php endif; ?>
    </form>
  </div>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>
