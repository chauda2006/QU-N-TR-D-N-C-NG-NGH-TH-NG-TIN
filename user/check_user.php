<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Kiểm tra xem đã đăng nhập và đúng vai trò là sinh viên chưa
if (!isset($_SESSION['vai_tro']) || $_SESSION['vai_tro'] !== 'sinh_vien') {
    header("Location: login.php");
    exit();
}
?>