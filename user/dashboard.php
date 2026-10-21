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
    $ly_do = trim($_POST['ly_do_nang_cap'] ?? '');

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

$vai_tro = $user['vai_tro'] ?? 'sinh_vien';
$yeu_cau = $user['yeu_cau_phan_quyen'] ?? 'none';
$chuc_vu_cu = $user['chuc_vu_mong_muon'] ?? '';
$ly_do_cu = $user['ly_do_nang_cap'] ?? '';

// Kiểm tra xem có thông báo mới từ Admin hay không (approved hoặc rejected)
$has_notification = ($yeu_cau === 'approved' || $yeu_cau === 'rejected');
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>Dashboard - <?php echo htmlspecialchars($user['ho_ten'] ?? ''); ?></title>
    <!-- Đường dẫn CSS chuẩn lùi 1 cấp thư mục về css/user-dashboard.css -->
    <link rel="stylesheet" type="text/css" href="../css/user-dashboard.css?v=<?php echo time(); ?>">
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

        <!-- 🔑 KHU VỰC YÊU CẦU NÂNG CẤP QUYỀN & THÔNG BÁO -->
        <div id="role-section" class="role-section">
            <h3>🔐 Đăng ký & Trạng thái Quản lý sự kiện</h3>

            <?php if (!empty($message)): ?>
                <div class="alert-success"><?php echo htmlspecialchars($message); ?></div>
            <?php endif; ?>

            <?php if (!empty($error)): ?>
                <div class="alert-danger"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>

            <!-- Thông báo phản hồi từ Admin -->
            <?php if ($yeu_cau === 'approved'): ?>
                <div style="background: #d4edda; border: 1px solid #c3e6cb; padding: 15px; border-radius: 6px; margin-bottom: 15px;">
                    <p style="color: #155724; font-weight: bold; margin: 0;">🎉 Chúc mừng! Yêu cầu của bạn đã được Admin phê duyệt.</p>
                    <p style="color: #155724; margin: 5px 0 0 0;">Bạn đã chính thức trở thành <b>Quản lý sự kiện</b>.</p>
                </div>
            <?php elseif ($yeu_cau === 'rejected'): ?>
                <div style="background: #f8d7da; border: 1px solid #f5c6cb; padding: 15px; border-radius: 6px; margin-bottom: 15px;">
                    <p style="color: #721c24; font-weight: bold; margin: 0;">❌ Rất tiếc! Yêu cầu nâng cấp quyền của bạn đã bị từ chối.</p>
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
                <!-- Đã có quyền quản lý sự kiện hoặc admin -->
                <p style="color: #198754; font-weight: bold; margin-bottom: 10px;">
                    ✅ Tài khoản của bạn đã có quyền quản lý sự kiện!
                </p>
                <a href="quan_ly_su_kien.php" class="btn-manager">
                    🎪 Đi tới Trang đăng & quản lý sự kiện
                </a>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>