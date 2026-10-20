<?php
session_start();
require_once __DIR__ . '/../database.php';

$error = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $tai_khoan = trim($_POST['tai_khoan'] ?? $_POST['ma_sv'] ?? '');
    $mat_khau  = trim($_POST['mat_khau'] ?? '');

    if (!empty($tai_khoan) && !empty($mat_khau)) {
        try {
            // Đã đổi thành bảng 'users'
            $stmt = $conn->prepare("SELECT * FROM users WHERE ma_sv = ? OR email = ?");
            $stmt->execute([$tai_khoan, $tai_khoan]);
            $user = $stmt->fetch();

            if ($user) {
                $is_valid = ($mat_khau === $user['mat_khau']) || password_verify($mat_khau, $user['mat_khau']);

                if ($is_valid) {
                    $_SESSION['user_id'] = $user['id'];
                    $_SESSION['ma_sv'] = $user['ma_sv'];
                    $_SESSION['ho_ten'] = $user['ho_ten'];
                    $_SESSION['vai_tro'] = $user['vai_tro']; 

                    if ($user['vai_tro'] === 'admin') {
                        header("Location: ../admin/dashboard.php");
                    } else {
                        header("Location: dashboard.php");
                    }
                    exit();
                } else {
                    $error = "Mật khẩu không chính xác!";
                }
            } else {
                $error = "Tài khoản không tồn tại!";
            }
        } catch (PDOException $e) {
            $error = "Lỗi SQL: " . $e->getMessage();
        }
    } else {
        $error = "Vui lòng nhập đầy đủ thông tin!";
    }
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>Đăng nhập Hệ thống</title>
    <style>
        body { font-family: Arial, sans-serif; background: #f4f6f9; display: flex; justify-content: center; align-items: center; height: 100vh; margin: 0; }
        .login-box { background: #fff; padding: 30px; border-radius: 8px; box-shadow: 0 4px 10px rgba(0,0,0,0.1); width: 100%; max-width: 380px; }
        h2 { text-align: center; color: #333; margin-bottom: 20px; }
        .error { background: #f8d7da; color: #721c24; padding: 10px; border-radius: 4px; margin-bottom: 15px; font-size: 14px; text-align: center; }
        .form-group { margin-bottom: 15px; }
        label { display: block; margin-bottom: 5px; font-weight: bold; color: #555; }
        input { width: 100%; padding: 10px; border: 1px solid #ccc; border-radius: 4px; box-sizing: border-box; }
        button { width: 100%; padding: 10px; background: #0d6efd; border: none; color: white; font-weight: bold; border-radius: 4px; cursor: pointer; }
        button:hover { background: #0b5ed7; }
    </style>
</head>
<body>
    <div class="login-box">
        <h2>Đăng nhập Hệ thống</h2>
        <?php if (!empty($error)): ?>
            <div class="error"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>
        <form method="POST">
            <div class="form-group">
                <label>Mã SV / Tài khoản / Email</label>
                <input type="text" name="tai_khoan" required placeholder="Nhập mã SV hoặc email...">
            </div>
            <div class="form-group">
                <label>Mật khẩu</label>
                <input type="password" name="mat_khau" required placeholder="Nhập mật khẩu...">
            </div>
            <button type="submit">Đăng nhập</button>
        </form>
    </div>
</body>
</html>