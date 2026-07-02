
CREATE DATABASE IF NOT EXISTS technest_shop
  CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE technest_shop;

-- Xoá bảng cũ (cho phép import lại nhiều lần)
DROP TABLE IF EXISTS order_returns;
DROP TABLE IF EXISTS order_items;
DROP TABLE IF EXISTS orders;
DROP TABLE IF EXISTS products;
DROP TABLE IF EXISTS categories;
DROP TABLE IF EXISTS users;

-- =====================================================
--  Bảng users (người dùng + phân quyền)
-- =====================================================
CREATE TABLE users (
  id          INT AUTO_INCREMENT PRIMARY KEY,
  full_name   VARCHAR(100) NOT NULL,
  email       VARCHAR(150) NOT NULL UNIQUE,
  password    VARCHAR(255) NOT NULL,          -- lưu bằng password_hash()
  phone       VARCHAR(20)  DEFAULT NULL,
  address     VARCHAR(255) DEFAULT NULL,
  role        ENUM('admin','customer') NOT NULL DEFAULT 'customer',
  created_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- =====================================================
--  Bảng categories (danh mục)
-- =====================================================
CREATE TABLE categories (
  id          INT AUTO_INCREMENT PRIMARY KEY,
  name        VARCHAR(100) NOT NULL,
  slug        VARCHAR(120) NOT NULL,
  created_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- =====================================================
--  Bảng products (sản phẩm)
-- =====================================================
CREATE TABLE products (
  id           INT AUTO_INCREMENT PRIMARY KEY,
  code         VARCHAR(20) DEFAULT NULL UNIQUE,
  category_id  INT DEFAULT NULL,
  name         VARCHAR(200) NOT NULL,
  description  TEXT,
  price        DECIMAL(12,0) NOT NULL DEFAULT 0,
  stock        INT NOT NULL DEFAULT 0,
  image        VARCHAR(255) DEFAULT NULL,
  is_featured  TINYINT(1) NOT NULL DEFAULT 0,
  created_at   TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- =====================================================
--  Bảng orders (đơn hàng)
-- =====================================================
CREATE TABLE orders (
  id            INT AUTO_INCREMENT PRIMARY KEY,
  user_id       INT DEFAULT NULL,
  customer_name VARCHAR(100) NOT NULL,
  phone         VARCHAR(20)  NOT NULL,
  address       VARCHAR(255) NOT NULL,
  note          VARCHAR(255) DEFAULT NULL,
  total         DECIMAL(12,0) NOT NULL DEFAULT 0,
  status        ENUM('pending','confirmed','shipping','completed','cancelled','return_requested','returned')
                NOT NULL DEFAULT 'pending',
  estimated_delivery DATE DEFAULT NULL,         -- ngày giao dự kiến
  cancel_reason VARCHAR(255) DEFAULT NULL,       -- lý do khách huỷ đơn
  created_at    TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- =====================================================
--  Bảng order_returns (yêu cầu hoàn hàng / hoàn tiền)
-- =====================================================
CREATE TABLE order_returns (
  id            INT AUTO_INCREMENT PRIMARY KEY,
  order_id      INT NOT NULL,
  reason        VARCHAR(500) NOT NULL,                       -- lý do hoàn
  refund_method VARCHAR(100) NOT NULL,                       -- phương thức hoàn tiền
  image         VARCHAR(255) DEFAULT NULL,                   -- ảnh đơn hàng/sản phẩm
  status        ENUM('pending','approved','rejected') NOT NULL DEFAULT 'pending',
  admin_note    VARCHAR(255) DEFAULT NULL,                   -- ghi chú khi duyệt
  created_at    TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- =====================================================
--  Bảng order_items (chi tiết đơn hàng)
-- =====================================================
CREATE TABLE order_items (
  id           INT AUTO_INCREMENT PRIMARY KEY,
  order_id     INT NOT NULL,
  product_id   INT DEFAULT NULL,
  product_name VARCHAR(200) NOT NULL,   -- lưu lại tên tại thời điểm mua
  price        DECIMAL(12,0) NOT NULL,
  quantity     INT NOT NULL,
  FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
  FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- =====================================================
--  DỮ LIỆU MẪU
-- =====================================================

-- Tài khoản (mật khẩu của cả 2: 123456)
-- Hash dưới đây là password_hash('123456', PASSWORD_DEFAULT)
INSERT INTO users (full_name, email, password, phone, address, role) VALUES
('Quản trị viên', 'admin@technest.vn',
 '$2y$10$B0u9rZzt1NHkku26jMJ6FeuP5RpIF5/paEsPl635A5FxweT/9zWuq',
 '0900000000', 'Hà Nội', 'admin'),
('Nguyễn Văn A', 'user@technest.vn',
 '$2y$10$B0u9rZzt1NHkku26jMJ6FeuP5RpIF5/paEsPl635A5FxweT/9zWuq',
 '0911111111', 'TP. Hồ Chí Minh', 'customer');

-- Danh mục
INSERT INTO categories (name, slug) VALUES
('Điện thoại', 'dien-thoai'),
('Laptop', 'laptop'),
('Phụ kiện', 'phu-kien'),
('Đồng hồ', 'dong-ho');



INSERT INTO products
(id, code,category_id, name, description, price, stock, image, is_featured)
VALUES
(1, 'TN0001', 1, 'iPhone 15 Pro Max',
 'Chip A17 Pro, khung Titan, camera 48MP. Bản 256GB.', 
 31990000, 24, 'ip15.jpg', 1),
(2, 'TN0002', 1, 'Samsung Galaxy S24 Ultra',
 'Màn 6.8 inch QHD+, bút S-Pen, camera 200MP.', 28990000, 17, '2.jpg', 1),
(3, 'TN0003', 1, 'Xiaomi 14', 'Snapdragon 8 Gen 3, camera Leica, sạc nhanh 90W.', 
18990000, 29, '3.jpg', 0),
(4, 'TN0004', 2, 'MacBook Air M3',
 'Chip M3, màn 13.6 inch, 8GB RAM, 256GB SSD.', 
 27490000, 12, '4.jpg', 1),
(5, 'TN0005', 2, 'Dell XPS 13',
 'Intel Core Ultra 7, 16GB RAM, màn InfinityEdge.',
 32990000, 8, '5.jpg', 0),
(6, 'TN0006', 2, 'ASUS ROG Strix G16', 
'Laptop gaming RTX 4060, Core i7, 16GB RAM.',
 35990000, 10, '6.jpg', 1),
(7, 'TN0007', 3, 'Tai nghe AirPods Pro 2',
 'Chống ồn chủ động, USB-C, âm thanh không gian.',
 5490000, 50, '8.jpg', 0),
(8, 'TN0008', 3, 'Bàn phím cơ Keychron K2',
 'Bluetooth, hot-swap, switch Brown.',
 1990000,40, '9.jpg', 0),
(9, 'TN0009', 3, 'Sạc nhanh Anker 65W',
 'Củ sạc GaN 3 cổng, gọn nhẹ.',
 790000, 100, '10.jpg', 0),
(10, 'TN0010', 4, 'Apple Watch Series 9',
 'GPS 45mm, đo SpO2, theo dõi sức khoẻ.',
 9990000, 21, 'Apple Watch Series 9.jpg', 1),
(11, 'TN0011', 4, 'Garmin Venu 3',
 'Đồng hồ thể thao, GPS, pin 14 ngày.',
 12490000, 15, 'GarminVenu3.jpg', 0),
(12, 'TN0012', 1, 'iPhone 15',
 'Chip A16 Bionic, Dynamic Island, camera 48MP. Bản 128GB.',
 21990000, 35, 'ip15t.jpg', 0),
(13, 'TN0013', 1, 'Samsung Galaxy A55',
 'Màn Super AMOLED 120Hz, chống nước IP67.',
 9490000, 40, '13.jpg', 0),
(14, 'TN0014', 1, 'OPPO Reno12',
 'Camera chân dung AI, sạc nhanh 80W.',
 11990000, 28, '14.jpg', 0),
(15, 'TN0015', 1, 'Google Pixel 8',
 'Chip Tensor G3, camera tính toán, Android gốc.',
 16990000, 20, '15.jpg', 1),
(16, 'TN0016', 1, 'Realme 12 Pro+',
 'Camera tele 64MP, thiết kế lịch lãm.',
 9990000, 33, '16.jpg', 0),
(17, 'TN0017', 2, 'MacBook Pro 14 M3',
 'Chip M3 Pro, màn Liquid Retina XDR, 18GB RAM.',
 49990000, 7, '17.jpg', 1),
(18, 'TN0018', 2, 'Lenovo ThinkPad X1',
 'Doanh nhân, nhẹ 1.1kg, bảo mật vân tay.',
 38990000, 9, '18.jpg', 0),
(19, 'TN0019', 2, 'HP Pavilion 15',
 'Core i5, 16GB RAM, 512GB SSD, học tập & văn phòng.',
 16990000, 18, '19.jpg', 0),
(20, 'TN0020', 2, 'Acer Nitro 5',
 'Gaming RTX 3050, màn 144Hz, tản nhiệt tốt.',
 22990000, 14, '20.jpg', 0),
(21, 'TN0021', 2, 'MacBook Air M2',
 'Mỏng nhẹ, chip M2, pin cả ngày, bản 256GB.',
 24990000, 16, '21.jpg', 0),
(22, 'TN0022', 3, 'Chuột Logitech MX Master 3S', 
'Chuột không dây cao cấp, cuộn MagSpeed.', 
2490000, 45, '22.jpg', 0),
(23, 'TN0023', 3, 'Tai nghe Sony WH-1000XM5',
 'Chống ồn hàng đầu, pin 30 giờ.',
 7990000, 25, '23.jpg', 1),
(24, 'TN0024', 3, 'Loa JBL Flip 6',
 'Loa bluetooth chống nước IP67, bass mạnh.',
 2390000, 38, '24.jpg', 0),
(25, 'TN0025', 3, 'Ốp lưng MagSafe',
 'Ốp chống sốc, hỗ trợ sạc MagSafe.',
 390000, 120, '25.jpg', 0),
(26, 'TN0026', 3, 'Pin dự phòng Anker 20000mAh',
 'Sạc nhanh PD 20W, 2 cổng ra.',
 990000, 70, '26.jpg', 0),
(27, 'TN0027', 3, 'Cáp sạc USB-C 2m',
 'Cáp bện dù, truyền nhanh, bền bỉ.',
 250000, 150, '27.jpg', 0),
(28, 'TN0028', 4, 'Samsung Galaxy Watch 6',
 'Theo dõi sức khoẻ, màn Super AMOLED.',
 7490000, 26, '28.jpg', 0),
(29, 'TN0029', 4, 'Apple Watch Ultra 2',
 'Viền Titan, GPS chính xác, pin 36 giờ.',
 21990000, 12, '29.jpg', 1),
(30, 'TN0030', 4, 'Xiaomi Smart Band 8',
 'Vòng đeo theo dõi vận động, giá tốt.',
 790000, 90, '30.jpg', 0),
(31, 'TN0031', 4, 'Garmin Forerunner 265',
 'Đồng hồ chạy bộ, màn AMOLED, GPS đa băng tần.', 
 12990000, 11, '31.jpg', 0);
 
UPDATE products
SET code = CONCAT('TN', LPAD(id,4,'0'));
