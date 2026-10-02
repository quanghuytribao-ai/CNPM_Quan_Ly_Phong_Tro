<?php
require_once 'auth.php';
require_once 'connect.php';
$role = $_SESSION['user']['role'] ?? '';
$userId = (int)($_SESSION['user']['id'] ?? 0);

function count_value(mysqli $conn, string $sql): int {
    $result = $conn->query($sql);
    return $result ? (int)($result->fetch_assoc()['total'] ?? 0) : 0;
}
function money_value(mysqli $conn, string $sql): float {
    $result = $conn->query($sql);
    return $result ? (float)($result->fetch_assoc()['total'] ?? 0) : 0;
}

if ($role === 'tenant') {
    $stmt = $conn->prepare("SELECT t.id,t.full_name,t.phone,t.start_date,r.room_number,r.price,r.status FROM tenants t LEFT JOIN rooms r ON r.id=t.room_id WHERE t.user_id=? LIMIT 1");
    $stmt->bind_param('i', $userId);
    $stmt->execute();
    $own = $stmt->get_result()->fetch_assoc();
    $unpaid = 0;
    $paid = 0;
    if ($own) {
        $stmt = $conn->prepare("SELECT COUNT(*) AS total FROM invoices WHERE tenant_id=? AND status='unpaid'");
        $stmt->bind_param('i', $own['id']); $stmt->execute();
        $unpaid = (int)$stmt->get_result()->fetch_assoc()['total'];
        $stmt = $conn->prepare("SELECT COALESCE(SUM(total),0) AS total FROM invoices WHERE tenant_id=? AND status='paid'");
        $stmt->bind_param('i', $own['id']); $stmt->execute();
        $paid = (float)$stmt->get_result()->fetch_assoc()['total'];
    }
} else {
    $totalRooms = count_value($conn, 'SELECT COUNT(*) AS total FROM rooms');
    $occupied = count_value($conn, "SELECT COUNT(*) AS total FROM rooms WHERE status='occupied'");
    $empty = count_value($conn, "SELECT COUNT(*) AS total FROM rooms WHERE status='empty'");
    $repair = count_value($conn, "SELECT COUNT(*) AS total FROM rooms WHERE status='repair'");
    $tenantCount = count_value($conn, 'SELECT COUNT(*) AS total FROM tenants');
    $unpaidCount = count_value($conn, "SELECT COUNT(*) AS total FROM invoices WHERE status='unpaid'");
    $unpaidAmount = money_value($conn, "SELECT COALESCE(SUM(total),0) AS total FROM invoices WHERE status='unpaid'");
    $revenue = money_value($conn, "SELECT COALESCE(SUM(total),0) AS total FROM invoices WHERE status='paid'");
    $rooms = $conn->query('SELECT room_number,price,status FROM rooms ORDER BY room_number LIMIT 8');
}
?>
<!doctype html>
<html lang="vi">
<head>
<meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>Tổng quan | Trọ Manager</title><link rel="stylesheet" href="assets/css/style.css">
<style>
.dashboard-welcome{display:flex;justify-content:space-between;align-items:center;gap:16px;flex-wrap:wrap;margin-bottom:22px}
.dashboard-welcome h2{margin:0 0 6px}.dashboard-welcome p{margin:0;color:#64748b}
.stat-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(185px,1fr));gap:16px;margin-bottom:22px}
.stat-card{background:#fff;border:1px solid #e8edf5;border-radius:14px;padding:20px;box-shadow:0 5px 18px rgba(20,40,80,.04)}
.stat-top{display:flex;align-items:center;justify-content:space-between;color:#64748b;font-size:14px}.stat-icon{font-size:22px;background:#eff6ff;padding:10px;border-radius:10px}
.stat-number{font-size:27px;font-weight:750;margin-top:14px;color:#172d58}.stat-note{font-size:12px;color:#8490a3;margin-top:5px}
.dash-columns{display:grid;grid-template-columns:minmax(0,1.5fr) minmax(260px,1fr);gap:18px}
.dash-panel{background:#fff;border:1px solid #e8edf5;border-radius:14px;padding:20px;min-width:0}.dash-panel h3{margin:0 0 16px}
.dash-room{display:grid;grid-template-columns:1fr 1.2fr 1fr;gap:10px;align-items:center;padding:12px 0;border-bottom:1px solid #edf0f5;font-size:14px}.dash-room:last-child{border-bottom:0}
.dash-status{font-style:normal;font-size:12px;padding:5px 9px;border-radius:20px;text-align:center;white-space:nowrap}.dash-status.empty{background:#dcfce7;color:#166534}.dash-status.occupied{background:#dbeafe;color:#1d4ed8}.dash-status.repair{background:#fef3c7;color:#92400e}
.dash-money{font-size:25px;font-weight:750;color:#15803d;margin:8px 0}.dash-links{display:grid;grid-template-columns:repeat(auto-fit,minmax(145px,1fr));gap:12px;margin-top:20px}.dash-link{display:flex;gap:10px;align-items:center;text-decoration:none;color:#243047;background:#f8fafc;border:1px solid #e8edf5;border-radius:10px;padding:14px}.dash-link:hover{background:#eff6ff;border-color:#bfdbfe}.dash-link span{font-size:20px}
.tenant-info{display:grid;grid-template-columns:repeat(auto-fit,minmax(160px,1fr));gap:16px}.tenant-info div{background:#f8fafc;padding:15px;border-radius:10px}.tenant-info small{display:block;color:#64748b;margin-bottom:7px}.tenant-info strong{font-size:15px}
@media(max-width:850px){.dash-columns{grid-template-columns:1fr}.dash-room{grid-template-columns:1fr 1fr}.dash-room .dash-status{grid-column:1/-1;justify-self:start}}
</style></head>
<body><div class="layout"><?php sidebar('','dashboard'); ?><main class="main">
<header class="dashboard-welcome"><div><h2><?= $role==='tenant'?'Khu vực người thuê':'Tổng quan hệ thống' ?></h2><p>Xin chào, <?=htmlspecialchars($_SESSION['user']['full_name'] ?? 'Người dùng')?>. Chúc bạn một ngày làm việc hiệu quả!</p></div><div class="user">👤 <?=htmlspecialchars(role_label($role))?></div></header>
<?php if ($role === 'tenant'): ?>
  <?php if ($own): ?>
  <section class="dash-panel" style="margin-bottom:18px"><h3>🏠 Thông tin thuê phòng</h3><div class="tenant-info">
    <div><small>Phòng đang thuê</small><strong><?=htmlspecialchars($own['room_number'] ?? 'Chưa được gán')?></strong></div>
    <div><small>Giá thuê mỗi tháng</small><strong><?=number_format((float)($own['price'] ?? 0),0,',','.')?> đ</strong></div>
    <div><small>Số điện thoại</small><strong><?=htmlspecialchars($own['phone'] ?: 'Chưa cập nhật')?></strong></div>
    <div><small>Ngày bắt đầu</small><strong><?=htmlspecialchars($own['start_date'] ?: 'Chưa cập nhật')?></strong></div>
  </div></section>
  <?php else: ?><section class="dash-panel" style="margin-bottom:18px"><h3>🏠 Thông tin thuê phòng</h3><p>Tài khoản của bạn chưa được liên kết với hồ sơ người thuê. Vui lòng liên hệ Chủ trọ.</p></section><?php endif; ?>
  <section class="stat-grid">
    <div class="stat-card"><div class="stat-top">Hóa đơn chưa thanh toán <span class="stat-icon">🧾</span></div><div class="stat-number"><?=$unpaid?></div><div class="stat-note">Số hóa đơn còn cần thanh toán</div></div>
    <div class="stat-card"><div class="stat-top">Đã thanh toán <span class="stat-icon">✅</span></div><div class="stat-number" style="font-size:22px"><?=number_format($paid,0,',','.')?> đ</div><div class="stat-note">Tổng tiền hóa đơn đã thanh toán</div></div>
  </section>
  <section class="dash-panel"><h3>Truy cập nhanh</h3><div class="dash-links"><a class="dash-link" href="hop-dong/index.php"><span>📄</span>Hợp đồng của tôi</a><a class="dash-link" href="dien-nuoc/index.php"><span>⚡</span>Điện & nước</a><a class="dash-link" href="hoa-don/index.php"><span>💰</span>Hóa đơn của tôi</a><a class="dash-link" href="phong/index.php"><span>🚪</span>Thông tin phòng</a></div></section>
<?php else: ?>
  <section class="stat-grid">
    <div class="stat-card"><div class="stat-top">Tổng số phòng <span class="stat-icon">🏠</span></div><div class="stat-number"><?=$totalRooms?></div><div class="stat-note">Tất cả phòng trong hệ thống</div></div>
    <div class="stat-card"><div class="stat-top">Đang cho thuê <span class="stat-icon">🔑</span></div><div class="stat-number"><?=$occupied?></div><div class="stat-note">Phòng có người thuê</div></div>
    <div class="stat-card"><div class="stat-top">Phòng trống <span class="stat-icon">🟢</span></div><div class="stat-number"><?=$empty?></div><div class="stat-note">Có thể sắp xếp cho thuê</div></div>
    <div class="stat-card"><div class="stat-top">Người thuê <span class="stat-icon">👥</span></div><div class="stat-number"><?=$tenantCount?></div><div class="stat-note">Hồ sơ người thuê hiện có</div></div>
    <div class="stat-card"><div class="stat-top">Hóa đơn chưa thu <span class="stat-icon">🧾</span></div><div class="stat-number"><?=$unpaidCount?></div><div class="stat-note">Tổng tiền: <?=number_format($unpaidAmount,0,',','.')?> đ</div></div>
  </section>
  <section class="dash-columns">
    <div class="dash-panel"><h3>🚪 Tình trạng phòng</h3>
      <?php if ($rooms && $rooms->num_rows): while($room=$rooms->fetch_assoc()): ?>
      <div class="dash-room"><strong><?=htmlspecialchars($room['room_number'])?></strong><span><?=number_format((float)$room['price'],0,',','.')?> đ/tháng</span><em class="dash-status <?=htmlspecialchars($room['status'])?>"><?=['empty'=>'Trống','occupied'=>'Đang thuê','repair'=>'Đang sửa'][$room['status']] ?? 'Khác'?></em></div>
      <?php endwhile; else: ?><p>Chưa có dữ liệu phòng.</p><?php endif; ?>
      <p style="margin:14px 0 0;color:#64748b;font-size:13px">Đang sửa: <strong><?=$repair?></strong> phòng · <a href="phong/index.php">Quản lý phòng →</a></p>
    </div>
    <div class="dash-panel"><h3>💰 Doanh thu đã thu</h3><div class="dash-money"><?=number_format($revenue,0,',','.')?> đ</div><p style="color:#64748b;font-size:13px">Tổng giá trị các hóa đơn có trạng thái đã thanh toán.</p><a class="btn primary" href="hoa-don/index.php">Xem hóa đơn →</a></div>
  </section>
  <section class="dash-panel" style="margin-top:18px"><h3>⚡ Thao tác nhanh</h3><div class="dash-links"><a class="dash-link" href="phong/index.php"><span>➕</span>Quản lý phòng</a><a class="dash-link" href="nguoi-thue/index.php"><span>👤</span>Quản lý người thuê</a><a class="dash-link" href="hop-dong/index.php"><span>📄</span>Hợp đồng</a><a class="dash-link" href="dien-nuoc/index.php"><span>⚡</span>Nhập điện nước</a><a class="dash-link" href="hoa-don/index.php"><span>🧾</span>Tạo hóa đơn</a><?php if($role==='landlord'): ?><a class="dash-link" href="nguoi-dung/index.php"><span>🔐</span>Quản lý tài khoản</a><?php endif; ?></div></section>
<?php endif; ?>
</main></div></body></html>
