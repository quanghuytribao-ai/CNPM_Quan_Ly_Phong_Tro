<?php
if (session_status() === PHP_SESSION_NONE) session_start();
if (!isset($_SESSION['user'])) { header('Location: login.php'); exit; }
function role_label($role) { return ['landlord'=>'Chủ trọ','staff'=>'Nhân viên','tenant'=>'Người thuê'][$role] ?? 'Không xác định'; }
function require_roles($roles) {
    $roles = (array)$roles;
    if (!in_array($_SESSION['user']['role'] ?? '', $roles, true)) {
        http_response_code(403);
        echo '<!doctype html><html lang="vi"><meta charset="utf-8"><title>Không có quyền</title><body style="font-family:Arial;padding:50px;text-align:center"><h1>⛔ Không có quyền truy cập</h1><p>Tài khoản của bạn không được phép sử dụng chức năng này.</p><a href="'.(strpos($_SERVER['PHP_SELF'],'/')!==false && basename(dirname($_SERVER['PHP_SELF']))!=='quan-ly-phong-tro' ? '../dashboard.php':'dashboard.php').'">← Về tổng quan</a></body></html>';
        exit;
    }
}
function sidebar($base='', $active='') {
    $r=$_SESSION['user']['role'];
    $items=[
      'dashboard'=>['📊','Tổng quan','dashboard.php','all'],
      'phong'=>['🚪','Quản lý phòng','phong/index.php','landlord,staff,tenant'],
      'tenant'=>['👤','Người thuê','nguoi-thue/index.php','landlord,staff,tenant'],
      'contract'=>['📄','Hợp đồng','hop-dong/index.php','landlord,staff,tenant'],
      'utility'=>['⚡','Điện & nước','dien-nuoc/index.php','landlord,staff,tenant'],
      'invoice'=>['💰','Hóa đơn','hoa-don/index.php','landlord,staff,tenant'],
      'users'=>['🔐','Quản lý người dùng','nguoi-dung/index.php','landlord'],
    ];
    echo '<aside class="sidebar"><div class="logo">🏠 <span>TRỌ MANAGER</span></div><nav>';
    foreach($items as $k=>$it){ if($it[3]!=='all' && !in_array($r,explode(',',$it[3]),true)) continue; echo '<a class="'.($active===$k?'active':''). '" href="'.$base.$it[2].'">'.$it[0].' '.$it[1].'</a>'; }
    echo '</nav><div class="sidebar-role">👤 '.htmlspecialchars(role_label($r)).'</div><a class="logout" href="'.$base.'logout.php">↪ Đăng xuất</a></aside>';
}
?>
