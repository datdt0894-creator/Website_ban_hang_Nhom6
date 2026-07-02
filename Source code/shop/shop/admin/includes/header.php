<?php
require_once __DIR__ . '/../../includes/functions.php';
require_admin('/shop/login.php');
$active = $active ?? '';
?>
<!DOCTYPE html>
<html lang="vi">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= isset($title) ? e($title) : 'Quản trị' ?> — TechNest Admin</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Space+Grotesk:wght@500;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="/shop/assets/css/style.css">
</head>
<body>
<div class="admin-body">
  <aside class="sidebar" id="sidebar">
    <div class="brand">⚙️ TechNest Admin</div>
    <nav>
      <a href="/shop/admin/index.php"      class="<?= $active==='dashboard'?'active':'' ?>">📊 Tổng quan</a>
      <a href="/shop/admin/products.php"   class="<?= $active==='products'?'active':'' ?>">📦 Sản phẩm</a>
      <a href="/shop/admin/categories.php" class="<?= $active==='categories'?'active':'' ?>">🏷️ Danh mục</a>
      <a href="/shop/admin/orders.php"     class="<?= $active==='orders'?'active':'' ?>">🧾 Đơn hàng</a>
      <a href="/shop/admin/revenue.php"    class="<?= $active==='revenue'?'active':'' ?>">📈 Báo cáo doanh thu</a>
      <a href="/shop/admin/returns.php"    class="<?= $active==='returns'?'active':'' ?>">↩️ Duyệt hoàn hàng<?php
        $pend = (int)$pdo->query('SELECT COUNT(*) FROM order_returns WHERE status="pending"')->fetchColumn();
        if ($pend > 0) echo ' <span style="background:#F97362;color:#fff;border-radius:10px;padding:1px 8px;font-size:.72rem;font-weight:700">'.$pend.'</span>';
      ?></a>
      <a href="/shop/admin/users.php"      class="<?= $active==='users'?'active':'' ?>">👥 Người dùng</a>
      <div class="sep">Khác</div>
      <a href="/shop/index.php">🏠 Về cửa hàng</a>
      <a href="/shop/logout.php">🚪 Đăng xuất</a>
    </nav>
  </aside>
  <div class="admin-main">
    <div class="admin-top">
      <div style="display:flex;align-items:center;gap:12px">
        <button class="menu-toggle" onclick="toggleSidebar()">☰</button>
        <h1><?= isset($title) ? e($title) : 'Quản trị' ?></h1>
      </div>
      <a class="user-chip" href="/shop/account.php" title="Tài khoản của tôi">
        <span class="av"><?= e(mb_strtoupper(mb_substr(current_user()['full_name'],0,1))) ?></span>
        <span class="name"><?= e(current_user()['full_name']) ?></span>
      </a>
    </div>
    <div class="admin-content">
    <?php if (!empty($_SESSION['flash'])): ?>
      <div class="alert alert-ok"><?= e($_SESSION['flash']) ?></div>
      <?php unset($_SESSION['flash']); ?>
    <?php endif; ?>
