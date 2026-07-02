<?php /* Biến $p là 1 sản phẩm. Dùng chung cho lưới sản phẩm. */ ?>
<article class="card">
  <a href="/shop/product.php?id=<?= $p['id'] ?>" class="thumb">
    <?php if (!empty($p['sold'])): ?><span class="badge-feat">🔥 Bán chạy · <?= (int)$p['sold'] ?></span>
    <?php elseif (!empty($p['is_featured'])): ?><span class="badge-feat">HOT</span><?php endif; ?>
    <img src="<?= e(product_image($p['image'])) ?>" alt="<?= e($p['name']) ?>">
  </a>
  <div class="body">
    <span class="cat"><?= e($p['cat_name'] ?? 'Sản phẩm') ?></span>
    <a class="title" href="/shop/product.php?id=<?= $p['id'] ?>"><?= e($p['name']) ?></a>
    <div class="price"><?= money($p['price']) ?></div>
    <div class="card-foot">
      <?php if (is_admin()): ?>
        <span class="stock-out" style="color:var(--muted)">Chế độ quản trị</span>
      <?php elseif ((int)$p['stock'] > 0): ?>
        <form class="add-form" method="post" action="/shop/cart.php">
          <input type="hidden" name="action" value="add">
          <input type="hidden" name="id" value="<?= $p['id'] ?>">
          <div style="display:flex;gap:6px">
            <button class="btn btn-primary btn-sm" type="submit" style="flex:1">Thêm vào giỏ</button>
            <button class="btn btn-accent btn-sm" type="submit" name="buy_now" value="1" style="flex:1">Mua ngay</button>
          </div>
        </form>
      <?php else: ?>
        <span class="stock-out">Tạm hết hàng</span>
      <?php endif; ?>
    </div>
  </div>
</article>
