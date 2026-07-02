# 🛒 TechNest — Website bán hàng (PHP + MySQL)

Đồ án cuối kỳ môn **Lập trình Web** — Chủ đề 1: *Website bán hàng cơ bản*.
Website thương mại điện tử bán đồ công nghệ, gồm **trang người dùng** và **trang quản trị (Admin)**, đầy đủ CRUD và phân quyền.

---

## 1. Công nghệ sử dụng
- **PHP** thuần (PDO) — không dùng framework để dễ chấm và dễ hiểu.
- **MySQL** (MariaDB) — cơ sở dữ liệu.
- **HTML5 + CSS3** thuần — giao diện responsive, hiện đại (không dùng Bootstrap).
- Một ít **JavaScript** cho menu mobile và nút tăng/giảm số lượng.

---

## 2. Cách cài đặt & chạy (dùng XAMPP)

1. **Cài XAMPP** (hoặc Laragon/WAMP). Bật **Apache** và **MySQL**.
2. **Chép mã nguồn**: copy cả thư mục `shop/` vào:
   - XAMPP: `C:\xampp\htdocs\shop`
   - Laragon: `C:\laragon\www\shop`
3. **Tạo cơ sở dữ liệu**:
   - Mở `http://localhost/phpmyadmin`
   - Tab **Import** → chọn file `shop/database.sql` → **Go**.
   - (Tự động tạo database `technest_shop` cùng dữ liệu mẫu.)
   - ⚠️ **Nếu bạn đã import bản cũ trước đây**: chạy thêm file `update.sql`
     (phpMyAdmin → chọn `technest_shop` → tab SQL → dán nội dung `update.sql` → Go)
     để thêm các cột/bảng mới mà không mất dữ liệu.
   - 🆕 Muốn có thêm 20 sản phẩm mẫu mà không cài lại: chạy file `add_products.sql` (chạy 1 lần).
4. **Kiểm tra cấu hình** trong `shop/config/database.php`
   (mặc định khớp XAMPP: user `root`, mật khẩu rỗng). Sửa nếu cần.
5. **Mở trình duyệt**: `http://localhost/shop/index.php`

> Nếu đặt thư mục với tên khác `shop`, hãy đổi các đường dẫn `/shop/...`
> trong code, hoặc giữ nguyên tên thư mục là `shop` cho nhanh.

---

## 3. Tài khoản demo

| Vai trò      | Email                | Mật khẩu |
|--------------|----------------------|----------|
| 👑 Quản trị  | `admin@technest.vn`  | `123456` |
| 🙍 Khách hàng| `user@technest.vn`   | `123456` |

> Admin đăng nhập sẽ được chuyển thẳng vào trang quản trị `/shop/admin/`.

---

## 4. Chức năng

### Trang người dùng
- Trang chủ: banner, sản phẩm nổi bật, lọc theo **danh mục**, **tìm kiếm**.
- Trang chi tiết sản phẩm + sản phẩm liên quan.
- **Giỏ hàng**: nhấn "Thêm vào giỏ" sản phẩm được thêm ngay tại chỗ (không nhảy trang), có thông báo nhỏ và cập nhật số trên giỏ; tiếp tục mua sắm thoải mái.
- Vào **giỏ hàng** mới xem lại, **tick chọn** đúng sản phẩm muốn mua rồi mới thanh toán (sản phẩm không chọn vẫn nằm trong giỏ).
- **Mua ngay**: nút đặt thẳng một sản phẩm, không cần thêm vào giỏ.
- **Đặt hàng không cần đăng nhập (mua với tư cách khách)**: khách có thể thêm giỏ và thanh toán mà **không bắt buộc đăng nhập tài khoản**; ở bước thanh toán chỉ cần nhập **thông tin nhận hàng** (họ tên, số điện thoại, địa chỉ, ghi chú). Đơn của khách vãng lai được lưu với `user_id = NULL` và **ghi nhớ theo phiên** để khách xem lại, huỷ hoặc yêu cầu hoàn hàng ngay trong phiên đó. Đăng nhập chỉ cần khi muốn lưu lịch sử đơn lâu dài theo tài khoản.
- **Trang tài khoản**: bấm vào tên ở thanh trên cùng để xem/sửa hồ sơ, đổi mật khẩu, đăng xuất hoặc xoá tài khoản. Khách chỉ huỷ được đơn khi đơn đang ở trạng thái "Chờ xác nhận". Tài khoản **admin không thể đặt hàng**.
- **Thanh toán** (COD): tạo đơn hàng, tự động **trừ tồn kho** (dùng transaction).
- **Đăng ký / Đăng nhập / Đăng xuất**, mật khẩu mã hoá bằng `password_hash()`.
- Xem lịch sử **đơn hàng của tôi** và trạng thái đơn (khách đăng nhập xem theo tài khoản; khách vãng lai xem các đơn đã đặt trong phiên hiện tại).
- **Tìm kiếm sản phẩm ngay trên thanh điều hướng** (đầu trang).
- **Ngày giao dự kiến** hiển thị trên mỗi đơn hàng.
- **Tự huỷ đơn** kèm lý do khi đơn chưa giao (không cần admin duyệt; tự hoàn tồn kho).
- **Yêu cầu hoàn hàng** với đơn đã hoàn thành: đính kèm ảnh, lý do và phương thức hoàn tiền; chờ admin kiểm duyệt mới hoàn tất.

### Trang quản trị (Admin)
- **Dashboard**: thống kê sản phẩm, đơn hàng, doanh thu, đơn chờ xử lý; cảnh báo sắp hết hàng.
- **Sản phẩm**: CRUD đầy đủ + **upload ảnh**.
- **Danh mục**: CRUD đầy đủ (tự sinh slug tiếng Việt).
- **Đơn hàng**: xem chi tiết, **cập nhật trạng thái**, lọc theo trạng thái, xoá.
- **Duyệt hoàn hàng**: xem yêu cầu hoàn hàng của khách (ảnh, lý do, phương thức hoàn tiền), **duyệt** (hoàn tất + cộng lại tồn kho) hoặc **từ chối**.
- **Báo cáo doanh thu**: so sánh doanh thu theo ngày / tháng / năm (biểu đồ cột + % tăng giảm so với kỳ trước).
- **Bảng điều khiển bấm được**: các ô Sản phẩm / Đơn hàng / Doanh thu / Đơn chờ xử lý liên kết thẳng tới trang tương ứng.
- **Mã sản phẩm (SKU)**: mỗi sản phẩm có 1 mã riêng (TN0001...); **nhập thêm hàng nhanh** chỉ bằng mã + số lượng.
- **Sản phẩm nổi bật tự động**: hệ thống tự xếp hạng theo số lượng đã bán (badge "Bán chạy").
- **Người dùng**: xem, **đổi phân quyền** (admin/khách), xoá.

### Bảo mật / kỹ thuật
- **Phân quyền** bằng session + hàm `require_login()`, `require_admin()` (áp dụng cho khu vực tài khoản và quản trị; **trang đặt hàng/thanh toán không yêu cầu đăng nhập**).
- Chống **SQL Injection**: tất cả truy vấn dùng **PDO Prepared Statements**.
- Chống **XSS**: dữ liệu in ra HTML đều qua hàm `e()` (`htmlspecialchars`).
- Mật khẩu **không lưu dạng thường** — dùng `password_hash` / `password_verify`.

---

## 5. Cấu trúc thư mục

```
shop/
├── config/
│   └── database.php        # Kết nối CSDL (PDO)
├── includes/
│   ├── functions.php       # Hàm dùng chung (auth, format, helpers)
│   ├── header.php          # Header trang người dùng
│   ├── footer.php          # Footer trang người dùng
│   └── _product_card.php   # Component thẻ sản phẩm
├── admin/
│   ├── includes/           # Layout admin (sidebar)
│   ├── index.php           # Dashboard
│   ├── products.php        # CRUD sản phẩm
│   ├── categories.php      # CRUD danh mục
│   ├── orders.php          # Quản lý đơn hàng
│   └── users.php           # Quản lý người dùng
├── assets/
│   ├── css/style.css       # Toàn bộ giao diện
│   └── js/main.js          # JS phụ trợ
├── uploads/                # Ảnh sản phẩm tải lên
├── index.php               # Trang chủ
├── product.php             # Chi tiết sản phẩm
├── cart.php                # Giỏ hàng
├── checkout.php            # Thanh toán
├── order_success.php       # Xác nhận đặt hàng
├── orders.php              # Đơn hàng của tôi
├── login.php / register.php / logout.php
└── database.sql            # File cơ sở dữ liệu (import file này)
```

---

## 6. Sơ đồ cơ sở dữ liệu (5 bảng)

- **users** (id, full_name, email, password, phone, address, role, created_at)
- **categories** (id, name, slug, created_at)
- **products** (id, category_id→categories, name, description, price, stock, image, is_featured, created_at)
- **orders** (id, user_id→users, customer_name, phone, address, note, total, status, created_at)
- **order_items** (id, order_id→orders, product_id→products, product_name, price, quantity)

Quan hệ: 1 danh mục có nhiều sản phẩm; 1 đơn hàng có nhiều dòng `order_items`;
1 người dùng có nhiều đơn hàng.

---

## 7. Gợi ý khi demo / thuyết trình
1. Mở trang chủ → tìm kiếm, lọc danh mục.
2. **Đặt hàng với tư cách khách (không đăng nhập)**: thêm sản phẩm vào giỏ → thanh toán → chỉ nhập thông tin nhận hàng → đặt thành công.
3. (Tuỳ chọn) Đăng ký / đăng nhập tài khoản rồi đặt hàng để đơn được lưu vào lịch sử tài khoản.
4. Vào *Đơn hàng của tôi* xem đơn vừa đặt (khách vãng lai xem được đơn của phiên hiện tại).
5. Đăng nhập admin → Dashboard → thêm/sửa/xoá sản phẩm → đổi trạng thái đơn vừa tạo.
6. Chứng minh phân quyền: tài khoản khách không vào được `/shop/admin/`.
