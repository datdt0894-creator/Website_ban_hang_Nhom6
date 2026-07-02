<?php
/**
 * Hàm tiện ích dùng chung cho toàn bộ website.
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../config/database.php';

/** Chống XSS khi in dữ liệu ra HTML */
function e($value)
{
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

/** Định dạng tiền Việt Nam, ví dụ: 31.990.000đ */
function money($number)
{
    return number_format((float)$number, 0, ',', '.') . 'đ';
}

/** Chuyển hướng nhanh */
function redirect($url)
{
    header('Location: ' . $url);
    exit;
}

/** Đã đăng nhập chưa? */
function is_logged_in()
{
    return isset($_SESSION['user']);
}

/** Có phải admin không? */
function is_admin()
{
    return is_logged_in() && $_SESSION['user']['role'] === 'admin';
}

/** Lấy user hiện tại */
function current_user()
{
    return $_SESSION['user'] ?? null;
}

/** Bắt buộc phải đăng nhập, nếu chưa thì đẩy ra trang login */
function require_login($redirect = '/shop/login.php')
{
    if (!is_logged_in()) {
        $_SESSION['flash'] = 'Vui lòng đăng nhập để tiếp tục.';
        redirect($redirect);
    }
}

/** Ghi nhớ đơn vừa đặt của khách vãng lai (lưu theo phiên làm việc) */
function remember_guest_order($orderId)
{
    if (!isset($_SESSION['guest_orders'])) {
        $_SESSION['guest_orders'] = [];
    }
    $orderId = (int)$orderId;
    if (!in_array($orderId, $_SESSION['guest_orders'], true)) {
        $_SESSION['guest_orders'][] = $orderId;
    }
}

/** Danh sách mã đơn khách vãng lai đã đặt trong phiên này */
function guest_order_ids()
{
    return $_SESSION['guest_orders'] ?? [];
}

function can_view_order($order)
{
    if (!$order) {
        return false;
    }
    $u = current_user();
    if ($u && (int)$order['user_id'] === (int)$u['id']) {
        return true;
    }
    return in_array((int)$order['id'], guest_order_ids(), true);
}

/** Bắt buộc phải là admin, nếu không thì chặn */
function require_admin($redirect = '/shop/login.php')
{
    if (!is_admin()) {
        $_SESSION['flash'] = 'Bạn không có quyền truy cập khu vực quản trị.';
        redirect($redirect);
    }
}

/** Tổng số lượng sản phẩm trong giỏ (hiển thị badge) */
function cart_count()
{
    if (empty($_SESSION['cart'])) {
        return 0;
    }
    return array_sum(array_column($_SESSION['cart'], 'quantity'));
}

/** Tên trạng thái đơn hàng bằng tiếng Việt */
function order_status_label($status)
{
    $map = [
        'pending'          => 'Chờ xác nhận',
        'confirmed'        => 'Đã xác nhận',
        'shipping'         => 'Đang giao',
        'completed'        => 'Hoàn thành',
        'cancelled'        => 'Đã huỷ',
        'return_requested' => 'Yêu cầu hoàn hàng',
        'returned'         => 'Đã hoàn hàng',
    ];
    return $map[$status] ?? $status;
}

/** Tên trạng thái yêu cầu hoàn hàng */
function return_status_label($status)
{
    $map = [
        'pending'  => 'Chờ duyệt',
        'approved' => 'Đã duyệt (hoàn tất)',
        'rejected' => 'Từ chối',
    ];
    return $map[$status] ?? $status;
}

/** Các phương thức hoàn tiền cho khách chọn */
function refund_methods()
{
    return [
        'Chuyển khoản ngân hàng',
        'Ví điện tử (Momo / ZaloPay)',
        'Hoàn vào số dư tài khoản cửa hàng',
    ];
}

/** Khách có được phép huỷ đơn không? (chỉ khi đang chờ xác nhận) */
function can_cancel_order($status)
{
    return $status === 'pending';
}

/** Khách có được yêu cầu hoàn hàng không? (đã nhận hàng) */
function can_return_order($status)
{
    return $status === 'completed';
}


/** Ảnh sản phẩm, nếu chưa có thì dùng ảnh placeholder */
function product_image($image)
{
    if ($image && file_exists(__DIR__ . '/../uploads/' . $image)) {
        return '/shop/uploads/' . $image;
    }
    // Ảnh SVG placeholder inline cho gọn (không cần file)
    return 'data:image/svg+xml;utf8,' . rawurlencode(
        '<svg xmlns="http://www.w3.org/2000/svg" width="400" height="300">'
        . '<rect width="100%" height="100%" fill="#eef1f6"/>'
        . '<text x="50%" y="50%" font-size="20" fill="#9aa4b2" '
        . 'text-anchor="middle" dy=".3em" font-family="sans-serif">No Image</text></svg>'
    );
}
