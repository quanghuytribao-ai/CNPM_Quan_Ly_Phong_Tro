<?php
session_start(); require_once 'connect.php';
if(isset($_SESSION['user'])) { header('Location: dashboard.php'); exit; }
$error='';
if($_SERVER['REQUEST_METHOD']==='POST'){
 $username=trim($_POST['username']??''); $password=$_POST['password']??'';
 $s=$conn->prepare('SELECT id,username,password,full_name,role FROM users WHERE username=? LIMIT 1'); $s->bind_param('s',$username); $s->execute(); $u=$s->get_result()->fetch_assoc();
 if($u && password_verify($password,$u['password'])){ $_SESSION['user']=['id'=>$u['id'],'username'=>$u['username'],'full_name'=>$u['full_name'],'role'=>$u['role']]; header('Location: dashboard.php'); exit; }
 $error='Tên đăng nhập hoặc mật khẩu không đúng!';
}
?>
<!doctype html><html lang="vi"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Đăng nhập - Trọ Manager</title><link rel="stylesheet" href="assets/css/style.css"></head><body class="login-page"><div class="login-box"><div class="brand">🏠</div><h1>QUẢN LÝ PHÒNG TRỌ</h1><p class="subtitle">Đăng nhập hệ thống</p><?php if($error):?><div class="alert error"><?=htmlspecialchars($error)?></div><?php endif;?><form method="post"><label>Tên đăng nhập</label><input name="username" required autofocus placeholder="Nhập tên đăng nhập"><label>Mật khẩu</label><input type="password" name="password" required placeholder="Nhập mật khẩu"><button class="btn primary full" type="submit">ĐĂNG NHẬP</button></form><div class="auth-link">Chưa có tài khoản? <a href="register.php">Đăng ký tài khoản</a></div></div></body></html>
