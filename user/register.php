<?php
session_start();
require_once '../database.php';

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username']);
    $password = trim($_POST['password']);
    $confirm_password = trim($_POST['confirm_password']);

    // 1. Kiểm tra rỗng
    if (empty($username) || empty($password) || empty($confirm_password)) {
        $error = 'Vui lòng nhập đầy đủ thông tin!';
    } 
    // 2. Kiểm tra trùng khớp mật khẩu
    elseif ($password !== $confirm_password) {
        $error = 'Mật khẩu nhập lại không trùng khớp!';
    } 
    // 3. Kiểm tra độ dài mật khẩu (tùy chọn)
    elseif (strlen($password) < 6) {
        $error = 'Mật khẩu phải có ít nhất 6 ký tự!';
    } 
    else {
        // Kiểm tra tên đăng nhập đã tồn tại chưa
        $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ?");
        $stmt->execute([$username]);

        if ($stmt->fetch()) {
            $error = 'Tên đăng nhập đã tồn tại!';
        } else {
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            $stmt_insert = $pdo->prepare("INSERT INTO users (username, password) VALUES (?, ?)");

            if ($stmt_insert->execute([$username, $hashed_password])) {
                $success = 'Đăng ký thành công! Đang chuyển hướng sang trang đăng nhập...';
                header("refresh:2;url=login.php");
            } else {
                $error = 'Có lỗi xảy ra, vui lòng thử lại!';
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Đăng ký Tài khoản</title>
    <!-- Tái sử dụng chung 1 file CSS với trang đăng nhập -->
    <link rel="stylesheet" href="../css/user-login.css">
</head>
<body>

    <div class="login-wrapper">
        <div class="login-box">
            <div class="login-header">
                <h2>ĐĂNG KÝ</h2>
                <p>Tạo tài khoản người dùng mới</p>
            </div>

            <!-- Khung thông báo lỗi -->
            <?php if ($error): ?>
                <div class="error-msg"><?= $error ?></div>
            <?php endif; ?>

            <!-- Khung thông báo thành công -->
            <?php if ($success): ?>
                <div class="success-msg"><?= $success ?></div>
            <?php endif; ?>

          

            <div class="divider">
                <span>hoặc đăng ký bằng tài khoản</span>
            </div>

            <!-- FORM ĐĂNG KÝ TRUYỀN THỐNG -->
            <form method="POST" action="register.php" class="login-form">
                <div class="input-group">
                    <label for="username">Tên đăng nhập</label>
                    <input type="text" id="username" name="username" placeholder="Nhập tên đăng nhập mới..." required autocomplete="off">
                </div>

                <div class="input-group">
                    <label for="password">Mật khẩu</label>
                    <input type="password" id="password" name="password" placeholder="Tạo mật khẩu..." required>
                </div>

                <div class="input-group">
                    <label for="confirm_password">Xác nhận mật khẩu</label>
                    <input type="password" id="confirm_password" name="confirm_password" placeholder="Nhập lại mật khẩu..." required>
                </div>

                <button type="submit" class="btn-submit">ĐĂNG KÝ NGAY</button>
            </form>

            <div class="login-footer">
                <p>Đã có tài khoản? <a href="login.php">Đăng nhập</a></p>
            </div>
        </div>
    </div>

</body>
</html>