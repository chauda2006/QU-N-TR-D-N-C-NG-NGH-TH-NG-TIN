<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/../database.php';

/** @var PDO $conn */

$error = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $tai_khoan = trim($_POST['tai_khoan'] ?? '');
    $mat_khau = trim($_POST['mat_khau'] ?? '');

    if (!empty($tai_khoan) && !empty($mat_khau)) {
        try {
            $stmt = $conn->prepare("SELECT * FROM users WHERE (ma_sv = ? OR email = ?) AND vai_tro = 'admin'");
            $stmt->execute([$tai_khoan, $tai_khoan]);
            $admin = $stmt->fetch();

            if ($admin) {
                $is_valid = ($mat_khau === $admin['mat_khau']) || password_verify($mat_khau, $admin['mat_khau']);

                if ($is_valid) {
                    $_SESSION['user_id'] = $admin['id'];
                    $_SESSION['ma_sv'] = $admin['ma_sv'];
                    $_SESSION['ho_ten'] = $admin['ho_ten'];
                    $_SESSION['vai_tro'] = 'admin';

                    header("Location: dashboard.php");
                    exit();
                } else {
                    $error = "Mật khẩu quản trị không chính xác!";
                }
            } else {
                $error = "Tài khoản không tồn tại hoặc không có quyền Quản trị viên!";
            }
        } catch (PDOException $e) {
            $error = "Lỗi hệ thống: " . $e->getMessage();
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
    <title>Đăng nhập Quản trị viên</title>
    <style>
        body { font-family: Arial, sans-serif; background: #f4f6f9; display: flex; justify-content: center; align-items: center; height: 100vh; margin: 0; }
        .login-box { background: #fff; padding: 30px; border-radius: 8px; box-shadow: 0 4px 10px rgba(0,0,0,0.1); width: 100%; max-width: 380px; }
        h2 { text-align: center; color: #333; margin-bottom: 20px; }
        .error { background: #f8d7da; color: #721c24; padding: 10px; border-radius: 4px; margin-bottom: 15px; font-size: 14px; }
        .form-group { margin-bottom: 15px; }
        label { display: block; margin-bottom: 5px; font-weight: bold; color: #555; }
        input { width: 100%; padding: 10px; border: 1px solid #ccc; border-radius: 4px; box-sizing: border-box; }
        button { width: 100%; padding: 10px; background: #dc3545; border: none; color: white; font-weight: bold; border-radius: 4px; cursor: pointer; }
        button:hover { background: #bb2d3b; }
    </style>
</head>
<body>
    <div class="login-box">
        <h2>Đăng nhập Admin</h2>
        <?php if (!empty($error)): ?>
            <div class="error"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>
        <form method="POST">
            <div class="form-group">
                <label>Tài khoản</label>
                <input type="text" name="tai_khoan" required placeholder="Nhập tài khoản admin">
            </div>
            <div class="form-group">
                <label>Mật khẩu</label>
                <input type="password" name="mat_khau" required placeholder="Nhập mật khẩu">
            </div>
            <button type="submit">Đăng nhập</button>
        </form>
    </div>
</body>
</html>