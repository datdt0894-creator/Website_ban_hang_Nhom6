<?php
require_once __DIR__ . '/functions.php';
$page = $page ?? '';
$u = current_user();
?>
<!DOCTYPE html>
<html lang="vi">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= isset($title) ? e($title) . ' — TechNest' : 'TechNest — Cửa hàng công nghệ' ?></title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Space+Grotesk:wght@500;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="/shop/assets/css/style.css">
</head>
<body>
<header class="navbar">
  <div class="container inner">
    <a class="brand" href="/shop/index.php"><span class="dot"></span>TechNest</a>
    <nav class="nav-links" id="navLinks">
      <a href="/shop/index.php" class="<?= $page==='home'?'active':'' ?>">Trang chủ</a>
      <a href="/shop/index.php#products" class="<?= $page==='products'?'active':'' ?>">Sản phẩm</a>
      <?php if (is_logged_in() || !empty($_SESSION['guest_orders'])): ?>
        <a href="/shop/orders.php" class="<?= $page==='orders'?'active':'' ?>">Đơn hàng</a>
      <?php endif; ?>
      <?php if (is_admin()): ?>
        <a href="/shop/admin/index.php">Quản trị</a>
      <?php endif; ?>
    </nav>
    <form class="nav-search" method="get" action="/shop/index.php">
      <span class="nav-search-ico">🔍</span>
      <input type="text" name="q" placeholder="Tìm sản phẩm..."
             value="<?= e($_GET['q'] ?? '') ?>" aria-label="Tìm sản phẩm">
      <button class="nav-search-btn" type="submit">Tìm</button>
    </form>
    <div class="nav-right">
      <a class="cart-link" href="/shop/cart.php" title="Giỏ hàng">
        🛒
        <span class="cart-badge" id="cartCount" <?= cart_count() > 0 ? '' : 'style="display:none"' ?>><?= cart_count() ?></span>
      </a>
      <?php if ($u): ?>
        <a class="user-chip" href="/shop/account.php" title="Tài khoản của tôi">
          <span class="av"><?= e(mb_strtoupper(mb_substr($u['full_name'],0,1))) ?></span>
          <span class="name"><?= e($u['full_name']) ?></span>
        </a>
        <a class="btn btn-ghost btn-sm" href="/shop/logout.php">Đăng xuất</a>
      <?php else: ?>
        <a class="btn btn-ghost btn-sm" href="/shop/login.php">Đăng nhập</a>
        <a class="btn btn-primary btn-sm" href="/shop/register.php">Đăng ký</a>
      <?php endif; ?>
      <button class="menu-toggle" onclick="toggleMenu()" aria-label="Menu">☰</button>
    </div>
  </div>
</header>
<main class="container">
<div id="toast" class="toast"></div>
<?php if (!empty($_SESSION['flash'])): ?>
  <div class="alert alert-info"><?= e($_SESSION['flash']) ?></div>
  <?php unset($_SESSION['flash']); ?>
<?php endif; ?>
