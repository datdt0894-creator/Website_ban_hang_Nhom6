<?php
require_once __DIR__ . '/includes/functions.php';
// Giữ lại giỏ hàng, chỉ đăng xuất user
unset($_SESSION['user']);
$_SESSION['flash'] = 'Bạn đã đăng xuất.';
redirect('/shop/login.php');
