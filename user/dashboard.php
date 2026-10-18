<?php
// Bật hiển thị lỗi để dễ dàng kiểm tra khi gặp sự cố
ini_set('display_errors', 1);
error_reporting(E_ALL);

require_once __DIR__ . '/check_user.php';
require_once __DIR__ . '/../database.php';

/** @var PDO $conn */

$message = "";
$error = "";

$ma_sv = $_SESSION['ma_sv'] ?? '';

// 1. Xử lý khi người dùng gửi yêu cầu nâng cấp quyền kèm lý do & chức vụ
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'request_upgrade') {
    $chuc_vu = trim($_POST['chuc_vu_mong_muon'] ?? '');
    $ly_do   = trim($_POST['ly_do_nang_cap'] ?? '');

    if (!empty($chuc_vu) && !empty($ly_do)) {
        try {
            $stmt_update = $conn->prepare("
                UPDATE users 
                SET yeu_cau_phan_quyen = 'pending', 
                    chuc_vu_mong_muon = ?, 
                    ly_do_nang_cap = ? 
                WHERE ma_sv = ?
            ");
            $stmt_update->execute([$chuc_vu, $ly_do, $ma_sv]);
            $message = "Đã gửi yêu cầu nâng cấp thành công! Vui lòng chờ Admin duyệt.";
        } catch (PDOException $e) {
            $error = "Lỗi hệ thống: " . $e->getMessage();
        }
    } else {
        $error = "Vui lòng chọn chức vụ mong muốn và nhập lý do yêu cầu!";
    }
}

// 2. Truy vấn lại thông tin người dùng mới nhất từ CSDL
$stmt = $conn->prepare("SELECT * FROM users WHERE ma_sv = ?");
$stmt->execute([$ma_sv]);
$user = $stmt->fetch();

$vai_tro    = $user['vai_tro'] ?? 'sinh_vien';
$yeu_cau    = $user['yeu_cau_phan_quyen'] ?? 'none';
$chuc_vu_cu = $user['chuc_vu_mong_muon'] ?? '';
$ly_do_cu   = $user['ly_do_nang_cap'] ?? '';

// Kiểm tra xem có thông báo mới từ Admin hay không (approved hoặc rejected)
$has_notification = ($yeu_cau === 'approved' || $yeu_cau === 'rejected');
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>Dashboard - <?php echo htmlspecialchars($user['ho_ten'] ?? ''); ?></title>
    <style>
        body { font-family: Arial, sans-serif; background: #f8f9fa; margin: 0; padding: 20px; }
        .container { max-width: 800px; margin: 0 auto; background: #fff; padding: 25px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        .header { display: flex; justify-content: space-between; align-items: center; border-bottom: 2px solid #eee; padding-bottom: 15px; margin-bottom: 20px; flex-wrap: wrap; gap: 10px; }
        .header h2 { color: #333; margin: 0; }
        .header-nav { display: flex; gap: 8px; align-items: center; }
        
        /* Style chuông thông báo */
        .notification-bell {
            position: relative;
            display: inline-block;
            background: #ffc107;
            color: #000;
            padding: 8px 12px;
            border-radius: 20px;
            font-weight: bold;
            text-decoration: none;
            font-size: 14px;
        }
        .notification-bell:hover { background: #e0a800; }
        .notification-badge {
            position: absolute;
            top: -6px;
            right: -6px;
            background: #dc3545;
            color: white;
            border-radius: 50%;
            padding: 2px 6px;
            font-size: 11px;
            font-weight: bold;
            border: 2px solid #fff;
            animation: pulse 1.5s infinite;
        }
        @keyframes pulse {
            0% { transform: scale(1); }
            50% { transform: scale(1.15); }
            100% { transform: scale(1); }
        }

        .btn-home { background-color: #0d6efd; color: white; padding: 8px 12px; text-decoration: none; border-radius: 4px; font-weight: bold; font-size: 14px; }
        .btn-home:hover { background-color: #0b5ed7; }
        .btn-logout { background: #dc3545; color: white; padding: 8px 12px; text-decoration: none; border-radius: 4px; font-weight: bold; font-size: 14px; }
        .btn-logout:hover { background: #bb2d3b; }
        
        .info-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 15px; }
        p { color: #555; margin: 8px 0; }
        strong { color: #222; }

        .role-section { margin-top: 25px; padding: 20px; background: #f8f9fa; border-radius: 8px; border: 1px solid #e9ecef; }
        .role-section h3 { margin-top: 0; color: #333; font-size: 18px; }
        .alert-success { background: #d4edda; color: #155724; padding: 10px; border-radius: 4px; margin-bottom: 15px; font-size: 14px; }
        .alert-danger { background: #f8d7da; color: #721c24; padding: 10px; border-radius: 4px; margin-bottom: 15px; font-size: 14px; }
        .form-group { margin-bottom: 12px; }
        label { display: block; font-weight: bold; margin-bottom: 5px; color: #495057; font-size: 14px; }
        select, textarea { width: 100%; padding: 8px; border: 1px solid #ced4da; border-radius: 4px; box-sizing: border-box; font-size: 14px; }
        .btn-request { background-color: #198754; color: white; padding: 10px 18px; border: none; border-radius: 4px; font-weight: bold; cursor: pointer; font-size: 14px; }
        .btn-request:hover { background-color: #157347; }
        .btn-manager { background-color: #0dcaf0; color: #000; padding: 8px 15px; text-decoration: none; border-radius: 4px; font-weight: bold; display: inline-block; margin-top: 5px; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h2>👋 Chào mừng, <?php echo htmlspecialchars($user['ho_ten'] ?? ''); ?></h2>
            <div class="header-nav">
                <!-- 🔔 CHUÔNG THÔNG BÁO -->
                <a href="#role-section" class="notification-bell" title="Thông báo hệ thống">
                    🔔 Thông báo
                    <?php if ($has_notification): ?>
                        <span class="notification-badge">1</span>
                    <?php endif; ?>
                </a>

                <a href="../index.php" class="btn-home">Trang chủ</a>
                <a href="logout.php" class="btn-logout">Đăng xuất</a>
            </div>
        </div>
        
        <h3>👤 Thông tin tài khoản</h3>
        <div class="info-grid">
            <div>
                <p><strong>Mã số / Mã SV:</strong> <?php echo htmlspecialchars($user['ma_sv'] ?? ''); ?></p>
                <p><strong>Họ và tên:</strong> <?php echo htmlspecialchars($user['ho_ten'] ?? ''); ?></p>
                <p><strong>Ngày sinh:</strong> <?php echo htmlspecialchars($user['ngay_sinh'] ?? 'Chưa cập nhật'); ?></p>
                <p><strong>Giới tính:</strong> <?php echo htmlspecialchars($user['gioi_tinh'] ?? 'Chưa cập nhật'); ?></p>
            </div>
            <div>
                <p><strong>Số điện thoại:</strong> <?php echo htmlspecialchars($user['sdt'] ?? 'Chưa cập nhật'); ?></p>
                <p><strong>Email:</strong> <?php echo htmlspecialchars($user['email'] ?? 'Chưa cập nhật'); ?></p>
                <p><strong>Trạng thái:</strong> <?php echo htmlspecialchars($user['trang_thai'] ?? 'Đang học / Đang công tác'); ?></p>
            </div>
        </div>

        <!-- 🚀 KHU VỰC YÊU CẦU NÂNG CẤP QUYỀN & THÔNG BÁO -->
        <div id="role-section" class="role-section">
            <h3>🎗️ Đăng ký & Trạng thái Quản lý sự kiện</h3>

            <?php if (!empty($message)): ?>
                <div class="alert-success"><?php echo htmlspecialchars($message); ?></div>
            <?php endif; ?>

            <?php if (!empty($error)): ?>
                <div class="alert-danger"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>

            <!-- Thông báo phản hồi từ Admin -->
            <?php if ($yeu_cau === 'approved'): ?>
                <div style="background: #d4edda; border: 1px solid #c3e6cb; padding: 15px; border-radius: 6px; margin-bottom: 15px;">
                    <h4 style="color: #155724; margin-top: 0;">🎉 Chúc mừng! Yêu cầu của bạn đã được Admin phê duyệt.</h4>
                    <p style="margin: 0; color: #155724;">Bạn đã chính thức trở thành <strong>Quản lý sự kiện</strong>.</p>
                </div>
            <?php elseif ($yeu_cau === 'rejected'): ?>
                <div style="background: #f8d7da; border: 1px solid #f5c6cb; padding: 15px; border-radius: 6px; margin-bottom: 15px;">
                    <h4 style="color: #721c24; margin-top: 0;">❌ Rất tiếc! Yêu cầu nâng cấp của bạn đã bị từ chối.</h4>
                    <p style="margin: 0; color: #721c24;">Bạn có thể chỉnh sửa thông tin lý do và gửi lại yêu cầu mới bên dưới.</p>
                </div>
            <?php endif; ?>

            <p>Vai trò hiện tại: <strong>
                <?php 
                    if ($vai_tro === 'admin') echo 'Quản trị viên (Admin)';
                    elseif ($vai_tro === 'quan_ly_su_kien') echo 'Quản lý sự kiện';
                    else echo 'Sinh viên';
                ?>
            </strong></p>

            <?php if ($vai_tro === 'sinh_vien'): ?>
                <?php if ($yeu_cau === 'pending'): ?>
                    <div style="background: #fff3cd; padding: 12px; border-radius: 4px; border: 1px solid #ffeeba; margin-top: 10px;">
                        <p style="margin: 0; color: #856404; font-weight: bold;">⏳ Yêu cầu của bạn đang chờ Admin duyệt.</p>
                        <p style="margin: 5px 0 0 0; font-size: 13px;">Chức vụ đăng ký: <strong><?php echo htmlspecialchars($chuc_vu_cu); ?></strong></p>
                        <p style="margin: 2px 0 0 0; font-size: 13px;">Lý do: <em><?php echo htmlspecialchars($ly_do_cu); ?></em></p>
                    </div>
                <?php else: ?>
                    <form method="POST" style="margin-top: 15px;">
                        <input type="hidden" name="action" value="request_upgrade">
                        
                        <div class="form-group">
                            <label>Bạn là ai / Chức vụ mong muốn (*)</label>
                            <select name="chuc_vu_mong_muon" required>
                                <option value="">-- Chọn chức vụ / vai trò --</option>
                                <option value="Chủ nhiệm CLB">Chủ nhiệm CLB</option>
                                <option value="Phó chủ nhiệm CLB">Phó chủ nhiệm CLB</option>
                                <option value="Nhân viên ban tổ chức">Nhân viên ban tổ chức</option>
                                <option value="Cán bộ lớp / Đoàn thanh niên">Cán bộ lớp / Đoàn thanh niên</option>
                            </select>
                        </div>

                        <div class="form-group">
                            <label>Lý do xin nâng cấp quyền (*)</label>
                            <textarea name="ly_do_nang_cap" rows="3" required placeholder="Ví dụ: Tôi là Chủ nhiệm CLB Tin học, cần tài khoản để tạo và quản lý sự kiện..."></textarea>
                        </div>

                        <button type="submit" class="btn-request">
                            🚀 Gửi yêu cầu xét duyệt
                        </button>
                    </form>
                <?php endif; ?>
            <?php else: ?>
                <p style="color: #198754; font-weight: bold; margin-bottom: 10px;">
                    ✅ Tài khoản của bạn đã có quyền quản lý sự kiện!
                </p>
                <a href="../su-kien.php" class="btn-manager">🎪 Đi tới Trang quản lý sự kiện</a>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>