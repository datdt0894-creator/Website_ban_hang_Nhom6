<?php
require_once __DIR__ . '/../includes/functions.php';
require_admin('/shop/login.php');

$by = $_GET['by'] ?? 'month';
if (!in_array($by, ['day', 'month', 'year'], true)) $by = 'month';

// Nhóm doanh thu theo kỳ (chỉ tính đơn đã hoàn thành)
$fmt = [
    'day'   => ['%Y-%m-%d', 'Theo ngày (30 gần nhất)', 30],
    'month' => ['%Y-%m',    'Theo tháng (12 gần nhất)', 12],
    'year'  => ['%Y',       'Theo năm',                 6],
][$by];

$stmt = $pdo->prepare(
    "SELECT DATE_FORMAT(created_at, ?) AS period,
            COUNT(*) AS orders,
            COALESCE(SUM(total),0) AS revenue
     FROM orders
     WHERE status = 'completed'
     GROUP BY period
     ORDER BY period DESC
     LIMIT ?"
);
$stmt->bindValue(1, $fmt[0]);
$stmt->bindValue(2, $fmt[2], PDO::PARAM_INT);
$stmt->execute();
$rows = array_reverse($stmt->fetchAll());  // tăng dần để vẽ biểu đồ

$maxRev = 0; $sumRev = 0; $sumOrd = 0;
foreach ($rows as $r) { $maxRev = max($maxRev, (int)$r['revenue']); $sumRev += (int)$r['revenue']; $sumOrd += (int)$r['orders']; }

// So sánh kỳ mới nhất với kỳ trước đó
$cur = $rows[count($rows)-1] ?? null;
$prev = $rows[count($rows)-2] ?? null;
$delta = null;
if ($cur && $prev && (int)$prev['revenue'] > 0) {
    $delta = ((int)$cur['revenue'] - (int)$prev['revenue']) / (int)$prev['revenue'] * 100;
}

$title  = 'Báo cáo doanh thu';
$active = 'revenue';
include __DIR__ . '/includes/header.php';
?>

<div class="pills" style="margin-bottom:18px">
  <a href="?by=day"   class="<?= $by==='day'?'active':'' ?>">Theo ngày</a>
  <a href="?by=month" class="<?= $by==='month'?'active':'' ?>">Theo tháng</a>
  <a href="?by=year"  class="<?= $by==='year'?'active':'' ?>">Theo năm</a>
</div>

<div class="stats" style="margin-bottom:18px">
  <div class="stat c3"><div class="label">Tổng doanh thu (kỳ hiển thị)</div><div class="num" style="font-size:1.25rem"><?= money($sumRev) ?></div></div>
  <div class="stat c2"><div class="label">Tổng đơn hoàn thành</div><div class="num"><?= $sumOrd ?></div></div>
  <div class="stat c1"><div class="label">Kỳ mới nhất</div><div class="num" style="font-size:1.1rem"><?= $cur ? money($cur['revenue']) : '—' ?></div></div>
  <div class="stat c4">
    <div class="label">So với kỳ trước</div>
    <div class="num" style="font-size:1.3rem;color:<?= $delta===null?'#6B7280':($delta>=0?'#16A34A':'#d56a63') ?>">
      <?= $delta===null ? '—' : (($delta>=0?'▲ +':'▼ ').round($delta,1).'%') ?>
    </div>
  </div>
</div>

<div class="panel">
  <div class="panel-head"><h3><?= e($fmt[1]) ?></h3></div>
  <?php if (!$rows): ?>
    <div class="empty-state"><div class="ico">📈</div><h3>Chưa có doanh thu</h3><p>Doanh thu được tính từ các đơn ở trạng thái “Hoàn thành”.</p></div>
  <?php else: ?>
    <!-- Biểu đồ cột -->
    <div style="display:flex;align-items:flex-end;gap:12px;height:240px;padding:24px 24px 8px;overflow-x:auto">
      <?php foreach ($rows as $r):
        $h = $maxRev > 0 ? max(4, round((int)$r['revenue'] / $maxRev * 190)) : 4; ?>
        <div style="flex:1;min-width:46px;display:flex;flex-direction:column;align-items:center;gap:6px">
          <div style="font-size:.72rem;color:var(--muted);white-space:nowrap"><?= number_format($r['revenue']/1000000,1) ?>tr</div>
          <div title="<?= money($r['revenue']) ?>" style="width:100%;max-width:60px;height:<?= $h ?>px;border-radius:8px 8px 0 0;background:linear-gradient(180deg,#4d86d0,#3a6cb0)"></div>
          <div style="font-size:.72rem;color:var(--ink);white-space:nowrap;transform:rotate(-30deg);transform-origin:center"><?= e($r['period']) ?></div>
        </div>
      <?php endforeach; ?>
    </div>
    <!-- Bảng chi tiết -->
    <?php
      $asc = $rows; // tăng dần
      for ($i = 0; $i < count($asc); $i++) {
          $asc[$i]['delta'] = ($i > 0 && (int)$asc[$i-1]['revenue'] > 0)
              ? (((int)$asc[$i]['revenue'] - (int)$asc[$i-1]['revenue']) / (int)$asc[$i-1]['revenue'] * 100)
              : null;
      }
      $tableRows = array_reverse($asc); // mới nhất lên đầu
    ?>
    <table class="table" style="margin-top:10px">
      <thead><tr><th>Kỳ</th><th>Số đơn</th><th>Doanh thu</th><th>So với kỳ trước</th></tr></thead>
      <tbody>
        <?php foreach ($tableRows as $r): $d = $r['delta']; ?>
          <tr>
            <td><strong><?= e($r['period']) ?></strong></td>
            <td><?= $r['orders'] ?></td>
            <td><?= money($r['revenue']) ?></td>
            <td style="color:<?= $d===null?'#6B7280':($d>=0?'#16A34A':'#d56a63') ?>">
              <?= $d===null ? '—' : (($d>=0?'+':'').round($d,1).'%') ?>
            </td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  <?php endif; ?>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>
