<?php
require_once __DIR__ . '/../includes/functions.php';
require_admin('/shop/login.php');

$action = $_GET['action'] ?? 'list';
$categories = $pdo->query('SELECT * FROM categories ORDER BY name')->fetchAll();

/** Upload ảnh, trả về tên file hoặc null */
function handle_upload($field)
{
    if (empty($_FILES[$field]['name']) || $_FILES[$field]['error'] !== UPLOAD_ERR_OK) {
        return null;
    }
    $allowed = ['jpg', 'jpeg', 'png', 'webp', 'gif'];
    $ext = strtolower(pathinfo($_FILES[$field]['name'], PATHINFO_EXTENSION));
    if (!in_array($ext, $allowed)) {
        return null;
    }
    $name = 'p_' . time() . '_' . mt_rand(1000, 9999) . '.' . $ext;
    $dest = __DIR__ . '/../uploads/' . $name;
    if (move_uploaded_file($_FILES[$field]['tmp_name'], $dest)) {
        return $name;
    }
    return null;
}

// ---- Nhập thêm hàng theo mã sản phẩm ----
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_POST['restock'])) {
    $code = trim($_POST['code'] ?? '');
    $qty  = (int)($_POST['qty'] ?? 0);
    $stmt = $pdo->prepare('SELECT id, name, stock FROM products WHERE code = ?');
    $stmt->execute([$code]);
    $prod = $stmt->fetch();
    if (!$prod) {
        $_SESSION['flash'] = 'Không tìm thấy sản phẩm có mã “' . $code . '”.';
    } elseif ($qty < 1) {
        $_SESSION['flash'] = 'Số lượng nhập phải lớn hơn 0.';
    } else {
        $pdo->prepare('UPDATE products SET stock = stock + ? WHERE id = ?')->execute([$qty, $prod['id']]);
        $_SESSION['flash'] = 'Đã nhập thêm ' . $qty . ' vào “' . $prod['name'] . '” (mã ' . $code . '). Tồn mới: ' . ($prod['stock'] + $qty) . '.';
    }
    redirect('/shop/admin/products.php');
}

// ---- Thêm / Sửa (POST) ----
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id          = (int)($_POST['id'] ?? 0);
    $name        = trim($_POST['name'] ?? '');
    $category_id = (int)($_POST['category_id'] ?? 0) ?: null;
    $price       = (float)($_POST['price'] ?? 0);
    $stock       = (int)($_POST['stock'] ?? 0);
    $desc        = trim($_POST['description'] ?? '');
    $featured    = isset($_POST['is_featured']) ? 1 : 0;
    $image       = handle_upload('image');

    if ($name !== '') {
        if ($id > 0) {
            // Cập nhật
            if ($image) {
                $stmt = $pdo->prepare(
                    'UPDATE products SET name=?, category_id=?, price=?, stock=?, description=?, is_featured=?, image=? WHERE id=?'
                );
                $stmt->execute([$name, $category_id, $price, $stock, $desc, $featured, $image, $id]);
            } else {
                $stmt = $pdo->prepare(
                    'UPDATE products SET name=?, category_id=?, price=?, stock=?, description=?, is_featured=? WHERE id=?'
                );
                $stmt->execute([$name, $category_id, $price, $stock, $desc, $featured, $id]);
            }
            $_SESSION['flash'] = 'Đã cập nhật sản phẩm.';
        } else {
            // Thêm mới
            $stmt = $pdo->prepare(
                'INSERT INTO products (name, category_id, price, stock, description, is_featured, image)
                 VALUES (?, ?, ?, ?, ?, ?, ?)'
            );
            $stmt->execute([$name, $category_id, $price, $stock, $desc, $featured, $image]);
            $newId = (int)$pdo->lastInsertId();
            // mã sản phẩm tự động (mỗi sản phẩm 1 mã riêng)
            $pdo->prepare("UPDATE products SET code = CONCAT('TN', LPAD(id,4,'0')) WHERE id = ? AND (code IS NULL OR code = '')")->execute([$newId]);
            $_SESSION['flash'] = 'Đã thêm sản phẩm mới.';
        }
    }
    redirect('/shop/admin/products.php');
}

// ---- Xoá ----
if ($action === 'delete') {
    $id = (int)($_GET['id'] ?? 0);
    $pdo->prepare('DELETE FROM products WHERE id = ?')->execute([$id]);
    $_SESSION['flash'] = 'Đã xoá sản phẩm.';
    redirect('/shop/admin/products.php');
}

// ---- Form thêm/sửa ----
if ($action === 'new' || $action === 'edit') {
    $product = ['id'=>0,'name'=>'','category_id'=>'','price'=>'','stock'=>'','description'=>'','is_featured'=>0,'image'=>null];
    if ($action === 'edit') {
        $stmt = $pdo->prepare('SELECT * FROM products WHERE id = ?');
        $stmt->execute([(int)($_GET['id'] ?? 0)]);
        $product = $stmt->fetch() ?: $product;
    }
    $title  = $action === 'edit' ? 'Sửa sản phẩm' : 'Thêm sản phẩm';
    $active = 'products';
    include __DIR__ . '/includes/header.php';
    ?>
    <a href="/shop/admin/products.php" style="color:var(--muted)">← Quay lại danh sách</a>
    <div class="panel" style="margin-top:14px;max-width:720px">
      <div class="panel-head"><h3><?= e($title) ?></h3></div>
      <form method="post" enctype="multipart/form-data" style="padding:22px">
        <input type="hidden" name="id" value="<?= (int)$product['id'] ?>">
        <div class="field">
          <label>Tên sản phẩm *</label>
          <input type="text" name="name" value="<?= e($product['name']) ?>" required>
        </div>
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:14px">
          <div class="field">
            <label>Danh mục</label>
            <select name="category_id">
              <option value="">— Chọn —</option>
              <?php foreach ($categories as $c): ?>
                <option value="<?= $c['id'] ?>" <?= (int)$product['category_id']===(int)$c['id']?'selected':'' ?>>
                  <?= e($c['name']) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="field">
            <label>Giá (VNĐ) *</label>
            <input type="number" name="price" value="<?= e($product['price']) ?>" min="0" required>
          </div>
        </div>
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:14px">
          <div class="field">
            <label>Tồn kho</label>
            <input type="number" name="stock" value="<?= e($product['stock']) ?>" min="0">
          </div>
          <div class="field">
            <label>Ảnh sản phẩm</label>
            <input type="file" name="image" accept="image/*">
            <?php if ($product['image']): ?>
              <small style="color:var(--muted)">Ảnh hiện tại: <?= e($product['image']) ?> (để trống nếu giữ nguyên)</small>
            <?php endif; ?>
          </div>
        </div>
        <div class="field">
          <label>Mô tả</label>
          <textarea name="description"><?= e($product['description']) ?></textarea>
        </div>
        <div class="field">
          <label style="display:flex;align-items:center;gap:8px;font-weight:500">
            <input type="checkbox" name="is_featured" value="1" style="width:auto" <?= !empty($product['is_featured'])?'checked':'' ?>>
            Đánh dấu là sản phẩm nổi bật
          </label>
        </div>
        <button class="btn btn-primary">💾 Lưu sản phẩm</button>
      </form>
    </div>
    <?php
    include __DIR__ . '/includes/footer.php';
    exit;
}

// ---- Danh sách ----
$products = $pdo->query(
    'SELECT p.*, c.name AS cat_name FROM products p
     LEFT JOIN categories c ON c.id = p.category_id ORDER BY p.created_at DESC'
)->fetchAll();

$title  = 'Quản lý sản phẩm';
$active = 'products';
include __DIR__ . '/includes/header.php';
?>

<div class="panel">
  <div class="panel-head">
    <h3>Sản phẩm (<?= count($products) ?>)</h3>
    <a class="btn btn-primary btn-sm" href="?action=new">+ Thêm sản phẩm</a>
  </div>
  <table class="table">
    <thead>
      <tr><th>Mã</th><th>Ảnh</th><th>Tên</th><th>Danh mục</th><th>Giá</th><th>Tồn</th><th>Nổi bật</th><th>Thao tác</th></tr>
    </thead>
    <tbody>
      <?php if (!$products): ?>
        <tr><td colspan="8" style="text-align:center;color:var(--muted)">Chưa có sản phẩm</td></tr>
      <?php else: foreach ($products as $p): ?>
        <tr>
          <td><code><?= e($p['code'] ?? '—') ?></code></td>
          <td><img class="thumb-sm" src="<?= e(product_image($p['image'])) ?>" alt=""></td>
          <td><?= e($p['name']) ?></td>
          <td><?= e($p['cat_name'] ?? '—') ?></td>
          <td><?= money($p['price']) ?></td>
          <td><span class="<?= $p['stock']==0?'stock-out':'' ?>"><?= $p['stock'] ?></span></td>
          <td><?= $p['is_featured'] ? '⭐' : '—' ?></td>
          <td>
            <div class="actions">
              <a class="btn btn-ghost btn-sm" href="?action=edit&id=<?= $p['id'] ?>">Sửa</a>
              <a class="btn btn-danger btn-sm" href="?action=delete&id=<?= $p['id'] ?>"
                 data-confirm="Xoá sản phẩm “<?= e($p['name']) ?>”?">Xoá</a>
            </div>
          </td>
        </tr>
      <?php endforeach; endif; ?>
    </tbody>
  </table>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>
