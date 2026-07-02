<?php
require_once __DIR__ . '/includes/functions.php';

$id = (int)($_GET['id'] ?? 0);
$stmt = $pdo->prepare(
    'SELECT p.*, c.name AS cat_name FROM products p
     LEFT JOIN categories c ON c.id = p.category_id WHERE p.id = ?'
);
$stmt->execute([$id]);
$p = $stmt->fetch();

if (!$p) {
    http_response_code(404);
    $title = 'Không tìm thấy';
    include __DIR__ . '/includes/header.php';
    echo '<div class="empty-state"><div class="ico">😕</div><h3>Không tìm thấy sản phẩm</h3>'
       . '<a class="btn btn-primary" href="/shop/index.php">Về trang chủ</a></div>';
    include __DIR__ . '/includes/footer.php';
    exit;
}

// Sản phẩm liên quan
$rel = $pdo->prepare(
    'SELECT p.*, c.name AS cat_name FROM products p
     LEFT JOIN categories c ON c.id = p.category_id
     WHERE p.category_id = ? AND p.id <> ? ORDER BY RAND() LIMIT 4'
);
$rel->execute([$p['category_id'], $p['id']]);
$related = $rel->fetchAll();

$title = $p['name'];
$page  = 'products';
include __DIR__ . '/includes/header.php';
?>

<p style="margin:18px 0;color:var(--muted)">
  <a href="/shop/index.php">Trang chủ</a> ›
  <a href="/shop/index.php?cat=<?= $p['category_id'] ?>"><?= e($p['cat_name']) ?></a> ›
  <?= e($p['name']) ?>
</p>

<div class="detail">
  <div class="gallery">
    <img src="<?= e(product_image($p['image'])) ?>" alt="<?= e($p['name']) ?>">
  </div>
  <div>
    <span class="cat" style="color:var(--accent-d);font-weight:700;text-transform:uppercase;font-size:.8rem"><?= e($p['cat_name']) ?></span>
    <h1><?= e($p['name']) ?></h1>
    <div class="price"><?= money($p['price']) ?></div>
    <div class="meta">
      <span>📦 Còn <strong><?= (int)$p['stock'] ?></strong> sản phẩm</span>
      <span>🚚 Giao hàng toàn quốc</span>
    </div>
    <p class="desc"><?= nl2br(e($p['description'])) ?></p>

    <?php if (is_admin()): ?>
      <p class="stock-out" style="font-size:1.05rem;color:var(--muted)">Bạn đang ở chế độ quản trị — không thể đặt hàng.</p>
    <?php elseif ((int)$p['stock'] > 0): ?>
      <form class="add-form" method="post" action="/shop/cart.php" style="display:flex;gap:14px;align-items:center;flex-wrap:wrap">
        <input type="hidden" name="action" value="add">
        <input type="hidden" name="id" value="<?= $p['id'] ?>">
        <div class="qty">
          <button type="button" onclick="changeQty(-1)">−</button>
          <input type="number" id="qty" name="qty" value="1" min="1" max="<?= (int)$p['stock'] ?>">
          <button type="button" onclick="changeQty(1)">+</button>
        </div>
        <button class="btn btn-primary">🛒 Thêm vào giỏ</button>
        <button class="btn btn-accent" name="buy_now" value="1">Mua ngay</button>
      </form>
    <?php else: ?>
      <p class="stock-out" style="font-size:1.1rem">Sản phẩm tạm hết hàng</p>
    <?php endif; ?>
  </div>
</div>

<?php if ($related): ?>
<div class="section-head"><h2>Sản phẩm liên quan</h2></div>
<div class="grid">
  <?php foreach ($related as $p): include __DIR__ . '/includes/_product_card.php'; endforeach; ?>
</div>
<?php endif; ?>

<?php include __DIR__ . '/includes/footer.php'; ?>
