// Toggle menu điều hướng trên mobile
function toggleMenu() {
  var nav = document.getElementById('navLinks');
  if (nav) nav.classList.toggle('open');
}

// Toggle sidebar admin trên mobile
function toggleSidebar() {
  var sb = document.getElementById('sidebar');
  if (sb) sb.classList.toggle('open');
}

// Xác nhận trước khi xoá
document.addEventListener('click', function (e) {
  var el = e.target.closest('[data-confirm]');
  if (el && !confirm(el.getAttribute('data-confirm'))) {
    e.preventDefault();
  }
});

// Tăng/giảm số lượng ở trang chi tiết sản phẩm
function changeQty(delta) {
  var input = document.getElementById('qty');
  if (!input) return;
  var v = parseInt(input.value || '1', 10) + delta;
  if (v < 1) v = 1;
  var max = parseInt(input.getAttribute('max') || '999', 10);
  if (v > max) v = max;
  input.value = v;
}

// ----- Toast nhỏ góc màn hình -----
var _toastTimer;
function showToast(msg) {
  var t = document.getElementById('toast');
  if (!t) { alert(msg); return; }
  t.textContent = msg;
  t.classList.add('show');
  clearTimeout(_toastTimer);
  _toastTimer = setTimeout(function () { t.classList.remove('show'); }, 2200);
}

// ----- Cập nhật số lượng trên badge giỏ hàng -----
function updateCartBadge(count) {
  var b = document.getElementById('cartCount');
  if (!b) return;
  b.textContent = count;
  b.style.display = count > 0 ? '' : 'none';
}

// ----- Thêm vào giỏ KHÔNG chuyển trang (AJAX) -----
// Nút "Mua ngay" (name=buy_now) vẫn submit bình thường để sang thẳng thanh toán.
document.addEventListener('submit', function (e) {
  var form = e.target.closest('.add-form');
  if (!form) return;
  if (e.submitter && e.submitter.name === 'buy_now') return; // để chuyển sang thanh toán
  e.preventDefault();

  var id = (form.querySelector('[name=id]') || {}).value;
  var qtyEl = form.querySelector('[name=qty]');
  var qty = qtyEl ? qtyEl.value : 1;

  fetch('/shop/cart.php', {
    method: 'POST',
    headers: { 'X-Requested-With': 'XMLHttpRequest', 'Content-Type': 'application/x-www-form-urlencoded' },
    body: 'action=add&ajax=1&id=' + encodeURIComponent(id) + '&qty=' + encodeURIComponent(qty)
  })
    .then(function (r) { return r.json(); })
    .then(function (d) {
      if (d && d.ok) {
        updateCartBadge(d.count);
        showToast('🛒 Đã thêm: ' + d.name);
      } else {
        showToast('Sản phẩm tạm hết hàng');
      }
    })
    .catch(function () { showToast('Có lỗi, vui lòng thử lại'); });
});

// ----- Trang giỏ hàng: chọn sản phẩm + tính tổng đã chọn -----
function money(n) { return new Intl.NumberFormat('vi-VN').format(Math.round(n)) + 'đ'; }
function recalcCart() {
  var items = document.querySelectorAll('.sel-item');
  if (!items.length) return;
  var total = 0, count = 0;
  items.forEach(function (cb) {
    if (cb.checked) {
      var price = parseFloat(cb.getAttribute('data-price')) || 0;
      var row = cb.closest('tr');
      var qEl = row ? row.querySelector('.qty-item') : null;
      var qty = qEl ? (parseInt(qEl.value, 10) || 0) : 1;
      total += price * qty;
      count += 1;
    }
  });
  var st = document.getElementById('selTotal');
  var sc = document.getElementById('selCount');
  if (st) st.textContent = money(total);
  if (sc) sc.textContent = count + ' sản phẩm';
}
document.addEventListener('change', function (e) {
  if (e.target.id === 'selAll') {
    document.querySelectorAll('.sel-item').forEach(function (cb) { cb.checked = e.target.checked; });
    recalcCart();
  } else if (e.target.classList.contains('sel-item') || e.target.classList.contains('qty-item')) {
    recalcCart();
  }
});
document.addEventListener('input', function (e) {
  if (e.target.classList.contains('qty-item')) recalcCart();
});
document.addEventListener('DOMContentLoaded', recalcCart);
