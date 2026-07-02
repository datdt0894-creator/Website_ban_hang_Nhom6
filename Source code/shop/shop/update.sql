-- =====================================================
--  CẬP NHẬT CSDL — thêm tính năng mới
--  Dùng cho người ĐÃ import database.sql trước đó (không mất dữ liệu).
--  Cách dùng: phpMyAdmin -> chọn database technest_shop -> tab SQL -> dán file này -> Go
--  (Nếu cài mới hoàn toàn thì chỉ cần import database.sql, KHÔNG cần file này.)
-- =====================================================
USE technest_shop;

-- 1) Thêm trạng thái hoàn hàng vào đơn hàng
ALTER TABLE orders
  MODIFY COLUMN status
  ENUM('pending','confirmed','shipping','completed','cancelled','return_requested','returned')
  NOT NULL DEFAULT 'pending';

-- 2) Thêm ngày giao dự kiến + lý do huỷ
ALTER TABLE orders
  ADD COLUMN estimated_delivery DATE DEFAULT NULL AFTER status,
  ADD COLUMN cancel_reason VARCHAR(255) DEFAULT NULL AFTER estimated_delivery;

-- 3) Bảng yêu cầu hoàn hàng
CREATE TABLE IF NOT EXISTS order_returns (
  id            INT AUTO_INCREMENT PRIMARY KEY,
  order_id      INT NOT NULL,
  reason        VARCHAR(500) NOT NULL,
  refund_method VARCHAR(100) NOT NULL,
  image         VARCHAR(255) DEFAULT NULL,
  status        ENUM('pending','approved','rejected') NOT NULL DEFAULT 'pending',
  admin_note    VARCHAR(255) DEFAULT NULL,
  created_at    TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- =====================================================
--  Cập nhật đợt 2: mã sản phẩm (SKU)
-- =====================================================
ALTER TABLE products ADD COLUMN code VARCHAR(20) DEFAULT NULL UNIQUE AFTER id;
UPDATE products SET code = CONCAT('TN', LPAD(id, 4, '0')) WHERE code IS NULL;
