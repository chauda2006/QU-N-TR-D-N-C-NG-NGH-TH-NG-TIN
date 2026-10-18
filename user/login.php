<?php
session_start();
require_once __DIR__ . '/../database.php';

/** @var PDO $conn */ // Khai báo để VS Code nhận biết biến $conn và không báo lỗi gạch đỏ

$error = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $tai_khoan = trim($_POST['tai_khoan'] ?? '');
    $mat_khau = trim($_POST['mat_khau'] ?? '');

    if (!empty($tai_khoan) && !empty($mat_khau)) {
        try {
            // Truy vấn tìm sinh viên theo mã số sinh viên (ma_sv)
            $stmt = $conn->prepare("SELECT * FROM users WHERE ma_sv = ?");
            $stmt->execute([$tai_khoan]);
            $user = $stmt->fetch();

            if ($user) {
                // Kiểm tra mật khẩu (hỗ trợ cả mật khẩu thường hoặc đã mã hóa password_hash)
                $is_valid = ($mat_khau === $user['mat_khau']) || password_verify($mat_khau, $user['mat_khau']);

                if ($is_valid) {
                    $_SESSION['user_id'] = $user['id'];
                    $_SESSION['ma_sv'] = $user['ma_sv'];
                    $_SESSION['ho_ten'] = $user['ho_ten'];
                    $_SESSION['vai_tro'] = $user['vai_tro'];

                    header("Location: dashboard.php");
                    exit();
                } else {
                    $error = "Mật khẩu không chính xác!";
                }
            } else {
                $error = "Mã số sinh viên không tồn tại!";
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
    <title>Đăng nhập Sinh viên</title>
    <style>
        body { font-family: Arial, sans-serif; background: #f4f6f9; display: flex; justify-content: center; align-items: center; height: 100vh; margin: 0; }
        .login-box { background: #fff; padding: 30px; border-radius: 8px; box-shadow: 0 4px 10px rgba(0,0,0,0.1); width: 100%; max-width: 380px; }
        h2 { text-align: center; color: #333; margin-bottom: 20px; }
        .error { background: #f8d7da; color: #721c24; padding: 10px; border-radius: 4px; margin-bottom: 15px; font-size: 14px; }
        .form-group { margin-bottom: 15px; }
        label { display: block; margin-bottom: 5px; font-weight: bold; color: #555; }
        input { width: 100%; padding: 10px; border: 1px solid #ccc; border-radius: 4px; box-sizing: border-box; }
        button { width: 100%; padding: 10px; background: #0d6efd; border: none; color: white; font-weight: bold; border-radius: 4px; cursor: pointer; }
        button:hover { background: #0b5ed7; }
    </style>
</head>
<body>
    <div class="login-box">
        <h2>Đăng nhập Sinh viên</h2>
        <?php if (!empty($error)): ?>
            <div class="error"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>
        <form method="POST">
            <div class="form-group">
                <label>Mã số sinh viên</label>
                <input type="text" name="tai_khoan" required placeholder="Nhập mã số sinh viên">
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