<?php
require_once __DIR__ . '/includes/functions.php';

$catId  = isset($_GET['cat']) ? (int)$_GET['cat'] : 0;
$search = trim($_GET['q'] ?? '');

// Lấy danh mục
$categories = $pdo->query('SELECT * FROM categories ORDER BY name')->fetchAll();

// Sản phẩm nổi bật = bán chạy nhất (tự đánh giá theo số lượng đã bán)
$featured = [];
if (!$catId && $search === '') {
    $featured = $pdo->query(
        "SELECT p.*, c.name AS cat_name, COALESCE(SUM(oi.quantity),0) AS sold
         FROM products p
         LEFT JOIN categories c ON c.id = p.category_id
         LEFT JOIN order_items oi ON oi.product_id = p.id
         LEFT JOIN orders o ON o.id = oi.order_id AND o.status <> 'cancelled'
         GROUP BY p.id
         HAVING sold > 0
         ORDER BY sold DESC, p.created_at DESC
         LIMIT 4"
    )->fetchAll();
    // Chưa có lượt bán nào -> tạm dùng sản phẩm được đánh dấu nổi bật / mới nhất
    if (!$featured) {
        $featured = $pdo->query(
            'SELECT p.*, c.name AS cat_name, 0 AS sold FROM products p
             LEFT JOIN categories c ON c.id = p.category_id
             ORDER BY p.is_featured DESC, p.created_at DESC LIMIT 4'
        )->fetchAll();
    }
}

// Truy vấn sản phẩm có lọc + tìm kiếm
$sql = 'SELECT p.*, c.name AS cat_name FROM products p
        LEFT JOIN categories c ON c.id = p.category_id WHERE 1=1';
$params = [];
if ($catId)        { $sql .= ' AND p.category_id = ?'; $params[] = $catId; }
if ($search !== '') { $sql .= ' AND p.name LIKE ?';     $params[] = '%' . $search . '%'; }
$sql .= ' ORDER BY p.created_at DESC';

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$products = $stmt->fetchAll();

$title = 'Cửa hàng công nghệ';
$page  = 'home';
include __DIR__ . '/includes/header.php';
?>

<?php if (!$catId && $search === ''): ?>
<section class="hero">
  <h1>Công nghệ chính hãng, giá tốt mỗi ngày</h1>
  <p>Điện thoại, laptop, phụ kiện và đồng hồ thông minh từ các thương hiệu hàng đầu. Giao nhanh, bảo hành đầy đủ.</p>
  <a href="#products" class="btn btn-accent">Mua sắm ngay →</a>
</section>
<?php endif; ?>

<?php if ($featured): ?>
<div class="section-head">
  <h2><span class="eyebrow">Được yêu thích</span>Sản phẩm nổi bật</h2>
</div>
<div class="grid">
  <?php foreach ($featured as $p): include __DIR__ . '/includes/_product_card.php'; endforeach; ?>
</div>
<?php endif; ?>

<div class="section-head" id="products">
  <h2><span class="eyebrow">Cửa hàng</span><?= $search !== '' ? 'Kết quả cho “'.e($search).'”' : 'Tất cả sản phẩm' ?></h2>
  <?php if ($search !== ''): ?>
    <a class="btn btn-ghost btn-sm" href="/shop/index.php">✕ Xoá tìm kiếm</a>
  <?php endif; ?>
</div>

<div class="pills">
  <a href="/shop/index.php" class="<?= !$catId ? 'active':'' ?>">Tất cả</a>
  <?php foreach ($categories as $c): ?>
    <a href="?cat=<?= $c['id'] ?>" class="<?= $catId===(int)$c['id']?'active':'' ?>"><?= e($c['name']) ?></a>
  <?php endforeach; ?>
</div>

<?php if ($products): ?>
  <div class="grid" style="margin-top:18px">
    <?php foreach ($products as $p): include __DIR__ . '/includes/_product_card.php'; endforeach; ?>
  </div>
<?php else: ?>
  <div class="empty-state">
    <div class="ico">🔍</div>
    <h3>Không tìm thấy sản phẩm</h3>
    <p>Thử từ khoá khác hoặc xem tất cả sản phẩm.</p>
  </div>
<?php endif; ?>

<?php include __DIR__ . '/includes/footer.php'; ?>
