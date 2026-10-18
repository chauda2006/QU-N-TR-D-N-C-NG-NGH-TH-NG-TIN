<?php
require_once __DIR__ . '/check_user.php';
require_once __DIR__ . '/../database.php';

/** @var PDO $conn */

// Lấy thông tin sinh viên từ DB dựa vào mã số trong Session
$ma_sv = $_SESSION['ma_sv'];
$stmt = $conn->prepare("SELECT * FROM users WHERE ma_sv = ?");
$stmt->execute([$ma_sv]);
$user = $stmt->fetch();
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>Dashboard Sinh viên - <?php echo htmlspecialchars($user['ho_ten'] ?? ''); ?></title>
    <style>
        body { font-family: Arial, sans-serif; background: #f8f9fa; margin: 0; padding: 20px; }
        .container { max-width: 800px; margin: 0 auto; background: #fff; padding: 25px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        .header { display: flex; justify-content: space-between; align-items: center; border-bottom: 2px solid #eee; padding-bottom: 15px; margin-bottom: 20px; }
        .header h2 { color: #333; margin: 0; }
        .btn-home { background-color: #0d6efd; color: white; padding: 8px 15px; text-decoration: none; border-radius: 4px; font-weight: bold; margin-right: 8px; }
        .btn-home:hover { background-color: #0b5ed7; }
        .btn-logout { background: #dc3545; color: white; padding: 8px 15px; text-decoration: none; border-radius: 4px; font-weight: bold; }
        .btn-logout:hover { background: #bb2d3b; }
        .info-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 15px; }
        p { color: #555; margin: 8px 0; }
        strong { color: #222; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h2>👋 Chào mừng, <?php echo htmlspecialchars($user['ho_ten'] ?? ''); ?></h2>
            <div>
                <!-- Nút chuyển về trang chủ / danh sách sự kiện -->
                <a href="../index.php" class="btn-home">Trang chủ</a>
                <!-- Nút Đăng xuất -->
                <a href="logout.php" class="btn-logout">Đăng xuất</a>
            </div>
        </div>
        
        <h3>👤 Thông tin cá nhân sinh viên</h3>
        <div class="info-grid">
            <div>
                <p><strong>Mã SV:</strong> <?php echo htmlspecialchars($user['ma_sv'] ?? ''); ?></p>
                <p><strong>Họ và tên:</strong> <?php echo htmlspecialchars($user['ho_ten'] ?? ''); ?></p>
                <p><strong>Ngày sinh:</strong> <?php echo htmlspecialchars($user['ngay_sinh'] ?? 'Chưa cập nhật'); ?></p>
                <p><strong>Giới tính:</strong> <?php echo htmlspecialchars($user['gioi_tinh'] ?? 'Chưa cập nhật'); ?></p>
            </div>
            <div>
                <p><strong>Số điện thoại:</strong> <?php echo htmlspecialchars($user['sdt'] ?? 'Chưa cập nhật'); ?></p>
                <p><strong>Email:</strong> <?php echo htmlspecialchars($user['email'] ?? 'Chưa cập nhật'); ?></p>
                <p><strong>Trạng thái:</strong> <?php echo htmlspecialchars($user['trang_thai'] ?? 'Đang học'); ?></p>
            </div>
        </div>
    </div>
</body>
</html>