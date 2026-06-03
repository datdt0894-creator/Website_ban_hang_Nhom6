# Website Bán Hàng Cơ Bản - Nhóm 6

## Giới thiệu

Website Bán Hàng Cơ Bản là dự án cuối kỳ môn Lập trình Web, được xây dựng bằng PHP và MySQL nhằm hỗ trợ người dùng tìm kiếm, xem sản phẩm, thêm vào giỏ hàng và đặt hàng trực tuyến.

Hệ thống bao gồm hai phần:

* Người dùng (User)
* Quản trị viên (Admin)

## Công nghệ sử dụng

* PHP
* MySQL (thiết kế và quản lý cơ sở dữ liệu)
* HTML5
* CSS3
* Git & GitHub
* Figma (thiết kế giao diện)
* InfinityFree (triển khai website)
* Microsoft Word (viết báo cáo)
* Microsoft PowerPoint (thiết kế slide thuyết trình)

## Chức năng chính

### Người dùng

* Đăng ký tài khoản
* Đăng nhập / Đăng xuất
* Xem danh sách sản phẩm
* Xem chi tiết sản phẩm
* Thêm sản phẩm vào giỏ hàng
* Cập nhật số lượng sản phẩm trong giỏ hàng
* Xóa sản phẩm khỏi giỏ hàng
* Đặt hàng

### Quản trị viên

#### Quản lý sản phẩm

* Thêm sản phẩm
* Hiển thị sản phẩm
* Cập nhật sản phẩm
* Xóa sản phẩm

#### Quản lý danh mục

* Thêm danh mục
* Cập nhật danh mục
* Xóa danh mục

#### Quản lý người dùng

* Hiển thị danh sách người dùng
* Cập nhật thông tin người dùng
* Xóa người dùng

#### Quản lý đơn hàng

* Hiển thị danh sách đơn hàng
* Cập nhật trạng thái đơn hàng

## Cơ sở dữ liệu

Các bảng chính:

* Users
* Categories
* Products
* Orders
* Order_Details

---

# HƯỚNG DẪN CÀI ĐẶT

## Yêu cầu hệ thống

* PHP 8.0 trở lên
* MySQL 5.7 trở lên
* Apache Server
* Git
* Visual Studio Code hoặc Visual Studio
* XAMPP hoặc Laragon

## Bước 1: Clone dự án

Mở Terminal hoặc Git Bash và chạy lệnh:

```bash
git clone https://github.com/datdt0894-creator/Website_ban_hang_Nhom6.git
```

Di chuyển vào thư mục dự án:

```bash
cd Website_ban_hang_Nhom6
```

## Bước 2: Tạo cơ sở dữ liệu

Mở MySQL hoặc phpMyAdmin và thực hiện:

```sql
CREATE DATABASE website_ban_hang;
```

Sau khi dự án hoàn thiện, import file cơ sở dữ liệu (`.sql`) đi kèm source code.

## Bước 3: Cấu hình kết nối cơ sở dữ liệu

Mở file cấu hình của dự án và chỉnh sửa:

```php
$host = "localhost";
$user = "root";
$password = "";
$database = "website_ban_hang";
```

## Bước 4: Khởi động hệ thống

Nếu sử dụng XAMPP:

* Khởi động Apache
* Khởi động MySQL

Nếu sử dụng Laragon:

* Chọn Start All

## Bước 5: Chạy dự án

Đưa thư mục dự án vào:

**XAMPP**

```text
C:\xampp\htdocs\
```

**Laragon**

```text
C:\laragon\www\
```

Truy cập trình duyệt:

```text
http://localhost/Website_ban_hang_Nhom6/
```

# Triển khai (Deploy)

Sau khi hoàn thành dự án:

1. Upload source code lên InfinityFree.
2. Tạo cơ sở dữ liệu MySQL.
3. Import database.
4. Cấu hình lại kết nối cơ sở dữ liệu.
5. Kiểm tra hoạt động của hệ thống.
6. Chuẩn bị tài khoản demo và link demo.

# Phân công thành viên

| Thành viên | Nhiệm vụ                            |
| ---------- | ----------------------------------- |
| TV1        | Backend, Authentication và Database |
| TV2        | Thiết kế giao diện User             |
| TV3        | Trang Admin và CRUD                 |
| TV4        | Giỏ hàng và Đặt hàng                |
| TV5        | Phân tích hệ thống và Báo cáo       |
| TV6        | GitHub, Kiểm thử và Triển khai      |

