<?php
$host = "localhost";
$user = "root";
$pass = "";
$db   = "quan_ly_phong_tro";

$conn = new mysqli($host, $user, $pass, $db);
if ($conn->connect_error) {
    die("Kết nối cơ sở dữ liệu thất bại: " . $conn->connect_error);
}
$conn->set_charset("utf8mb4");
?>