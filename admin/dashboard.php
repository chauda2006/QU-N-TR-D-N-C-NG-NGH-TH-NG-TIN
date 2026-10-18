<?php
// Bật hiển thị lỗi để dễ kiểm tra khi lập trình
ini_set('display_errors', 1);
error_reporting(E_ALL);

require_once __DIR__ . '/check_admin.php'; // Kiểm tra quyền admin
require_once __DIR__ . '/../database.php';   // Kết nối CSDL

/** @var PDO $conn */

// Lấy thông tin tài khoản Admin đang đăng nhập từ Session
$admin_ho_ten = $_SESSION['ho_ten'] ?? 'Admin';
$admin_ma_sv  = $_SESSION['ma_sv'] ?? '';

// 1. Xử lý khi Admin bấm Duyệt hoặc Từ chối yêu cầu phân quyền của sinh viên
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action_admin'])) {
    $target_user_id = $_POST['target_user_id'] ?? '';
    $admin_action   = $_POST['action_admin'];

    if ($target_user_id) {
        if ($admin_action === 'approve') {
            // Duyệt: Nâng cấp vai trò thành quan_ly_su_kien và cập nhật trạng thái
            $stmt_approve = $conn->prepare("UPDATE users SET vai_tro = 'quan_ly_su_kien', yeu_cau_phan_quyen = 'approved' WHERE id = ?");
            $stmt_approve->execute([$target_user_id]);
        } elseif ($admin_action === 'reject') {
            // Từ chối: Cập nhật trạng thái thành rejected
            $stmt_reject = $conn->prepare("UPDATE users SET yeu_cau_phan_quyen = 'rejected' WHERE id = ?");
            $stmt_reject->execute([$target_user_id]);
        }
    }
}

// 2. Lấy danh sách sinh viên đang chờ duyệt quyền (pending)
$stmt_pending = $conn->query("SELECT * FROM users WHERE yeu_cau_phan_quyen = 'pending'");
$pending_requests = $stmt_pending->fetchAll();

// 3. Lựa chọn lớp học theo bộ lọc (mặc định '242101TH001')
$selected_class = $_GET['ma_lop'] ?? '242101TH001';

// 4. Truy vấn lấy tổng số sinh viên thuộc lớp được chọn
$stmt_count = $conn->prepare("SELECT COUNT(*) as total FROM users WHERE ma_lop = ? AND (vai_tro = 'sinh_vien' OR vai_tro IS NULL OR vai_tro = '')");
$stmt_count->execute([$selected_class]);
$total_students = $stmt_count->fetch()['total'] ?? 0;

// 5. Truy vấn danh sách và số lượng tất cả Nhân viên / Quản lý sự kiện
$stmt_staff_list = $conn->query("SELECT ma_sv, ho_ten, email, sdt, chuc_vu_mong_muon FROM users WHERE vai_tro = 'quan_ly_su_kien' ORDER BY ma_sv ASC");
$staff_members = $stmt_staff_list->fetchAll();
$total_staff = count($staff_members);

// 6. Truy vấn danh sách sinh viên thuộc lớp kèm thống kê sự kiện & điểm rèn luyện
$first_day_of_month = date('Y-m-01 00:00:00');
$last_day_of_month  = date('Y-m-t 23:59:59');

$sql_students = "
    SELECT u.*,
        (
            SELECT COUNT(DISTINCT dk.su_kien_id)
            FROM dang_ky_su_kien dk
            JOIN su_kien sk ON dk.su_kien_id = sk.id
            WHERE dk.user_id = u.id
              AND dk.trang_thai = 'da_tham_gia'
              AND sk.ngay_to_chuc BETWEEN :first_day AND :last_day
        ) AS so_su_kien_thang,
        (
            SELECT COALESCE(SUM(sk.diem_ren_luyen), 0)
            FROM dang_ky_su_kien dk
            JOIN su_kien sk ON dk.su_kien_id = sk.id
            WHERE dk.user_id = u.id
              AND dk.trang_thai = 'da_tham_gia'
        ) AS tong_diem_ren_luyen
    FROM users u
    WHERE u.ma_lop = :ma_lop AND (u.vai_tro = 'sinh_vien' OR u.vai_tro IS NULL OR u.vai_tro = '')
";

try {
    $stmt_students = $conn->prepare($sql_students);
    $stmt_students->execute([
        ':first_day' => $first_day_of_month,
        ':last_day'  => $last_day_of_month,
        ':ma_lop'    => $selected_class
    ]);
    $students = $stmt_students->fetchAll();
} catch (PDOException $e) {
    $stmt_students = $conn->prepare("SELECT *, 0 AS so_su_kien_thang, 0 AS tong_diem_ren_luyen FROM users WHERE ma_lop = ? AND (vai_tro = 'sinh_vien' OR vai_tro IS NULL OR vai_tro = '')");
    $stmt_students->execute([$selected_class]);
    $students = $stmt_students->fetchAll();
}

// 7. Lấy danh sách tất cả các lớp học có trong hệ thống để đổ vào menu thả xuống
$stmt_classes = $conn->query("SELECT DISTINCT ma_lop FROM users WHERE ma_lop IS NOT NULL AND ma_lop != '' ORDER BY ma_lop ASC");
$classes = $stmt_classes->fetchAll();
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>Admin Dashboard - Quản lý Lớp & Phân Quyền</title>
    <!-- Liên kết đường dẫn CSS chuẩn -->
    <link rel="stylesheet" href="../css/dashboard.css">
</head>
<body>
    <div class="container">
        <!-- HEADER -->
        <div class="header">
            <h2>🛠️ Trang Quản Trị Hệ Thống (Admin)</h2>
            <div class="header-nav">
                <!-- 🔔 CHUÔNG THÔNG BÁO -->
                <a href="#requests-section" class="notification-bell" title="Yêu cầu nâng cấp quyền">
                    🔔 Thông báo
                    <?php if (count($pending_requests) > 0): ?>
                        <span class="notification-badge"><?php echo count($pending_requests); ?></span>
                    <?php endif; ?>
                </a>

                <a href="../index.php" class="btn-home">Trang chủ</a>
                <a href="../su-kien.php" class="btn-events">Quản lý sự kiện</a>
                <a href="logout.php" class="btn-logout">Đăng xuất</a>
            </div>
        </div>

        <!-- 🔔 KHU VỰC DUYỆT YÊU CẦU NÂNG CẤP QUYỀN -->
        <?php if (count($pending_requests) > 0): ?>
            <div id="requests-section" class="approval-box">
                <h3>🔔 Danh sách yêu cầu nâng cấp quyền (<?php echo count($pending_requests); ?>)</h3>
                <table>
                    <thead>
                        <tr style="background: #ffe8a1;">
                            <th>Mã SV</th>
                            <th>Họ tên</th>
                            <th>Lớp</th>
                            <th>Chức vụ đăng ký</th>
                            <th>Lý do xin nâng cấp</th>
                            <th style="text-align: center;">Thao tác</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($pending_requests as $req): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($req['ma_sv']); ?></td>
                                <td><strong><?php echo htmlspecialchars($req['ho_ten']); ?></strong></td>
                                <td><?php echo htmlspecialchars($req['ma_lop'] ?? ''); ?></td>
                                <td><span style="color: #0d6efd; font-weight: bold;"><?php echo htmlspecialchars($req['chuc_vu_mong_muon'] ?? 'Chưa rõ'); ?></span></td>
                                <td><span style="font-style: italic; color: #555;"><?php echo htmlspecialchars($req['ly_do_nang_cap'] ?? 'Không có lý do'); ?></span></td>
                                <td style="text-align: center;">
                                    <form method="POST" style="display: inline-block;">
                                        <input type="hidden" name="target_user_id" value="<?php echo $req['id']; ?>">
                                        <button type="submit" name="action_admin" value="approve" style="background: #198754; color: white; border: none; padding: 6px 12px; border-radius: 4px; cursor: pointer; font-weight: bold;">Duyệt</button>
                                        <button type="submit" name="action_admin" value="reject" style="background: #dc3545; color: white; border: none; padding: 6px 12px; border-radius: 4px; cursor: pointer; font-weight: bold;">Từ chối</button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>

        <!-- BỘ LỌC CHỌN LỚP HỌC -->
        <div class="filter-form">
            <label><strong>Chọn lớp học:</strong></label>
            <select onchange="location = 'dashboard.php?ma_lop=' + this.value;">
                <?php foreach ($classes as $cls): ?>
                    <option value="<?php echo htmlspecialchars($cls['ma_lop']); ?>" <?php echo ($cls['ma_lop'] === $selected_class) ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($cls['ma_lop']); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <!-- 📊 THỐNG KÊ GRID (SINH VIÊN & NHÂN VIÊN) -->
        <div class="stats-grid">
            <!-- Thống kê lớp -->
            <div class="stat-card">
                <h3>📊 Thống kê lớp: <?php echo htmlspecialchars($selected_class); ?></h3>
                <p>Tổng số sinh viên: <span><?php echo $total_students; ?></span> người</p>
            </div>

            <!-- Thống kê nhân sự + Danh sách chi tiết -->
            <div class="stat-card-staff">
                <h3>👔 Thống kê Nhân sự / Ban tổ chức</h3>
                <p>Tổng số Quản lý sự kiện: <span><?php echo $total_staff; ?></span> người</p>

                <div class="staff-list-box">
                    <div class="staff-list-title">📌 Danh sách Quản lý sự kiện hiện tại:</div>
                    <?php if ($total_staff > 0): ?>
                        <ul class="staff-ul">
                            <?php foreach ($staff_members as $staff): ?>
                                <?php $is_current = ($staff['ma_sv'] === $admin_ma_sv); ?>
                                <li class="<?php echo $is_current ? 'current-logged-in' : ''; ?>">
                                    <strong><?php echo htmlspecialchars($staff['ho_ten']); ?></strong> 
                                    (<?php echo htmlspecialchars($staff['ma_sv']); ?>)
                                    <?php if (!empty($staff['chuc_vu_mong_muon'])): ?>
                                        - <em><?php echo htmlspecialchars($staff['chuc_vu_mong_muon']); ?></em>
                                    <?php endif; ?>

                                    <?php if ($is_current): ?>
                                        <span class="badge-current-user">🟢 Đang đăng nhập</span>
                                    <?php endif; ?>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    <?php else: ?>
                        <p style="font-size: 13px; color: #777; margin-top: 5px;">Chưa có quản lý sự kiện nào.</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- DANH SÁCH SINH VIÊN -->
        <h3>📋 Danh sách sinh viên & Điểm rèn luyện</h3>
        <table>
            <thead>
                <tr>
                    <th>STT</th>
                    <th>Mã SV</th>
                    <th>Họ và tên</th>
                    <th>Email</th>
                    <th>Sự kiện tháng này</th>
                    <th>Điểm rèn luyện cộng</th>
                    <th>Trạng thái</th>
                </tr>
            </thead>
            <tbody>
                <?php if (count($students) > 0): ?>
                    <?php $i = 1; foreach ($students as $stu): ?>
                        <tr>
                            <td><?php echo $i++; ?></td>
                            <td><?php echo htmlspecialchars($stu['ma_sv']); ?></td>
                            <td><?php echo htmlspecialchars($stu['ho_ten']); ?></td>
                            <td><?php echo htmlspecialchars($stu['email'] ?? 'Chưa cập nhật'); ?></td>
                            <td><span class="badge-event"><?php echo (int)($stu['so_su_kien_thang'] ?? 0); ?> sự kiện</span></td>
                            <td><span class="badge-score">+<?php echo (int)($stu['tong_diem_ren_luyen'] ?? 0); ?> điểm</span></td>
                            <td><?php echo htmlspecialchars($stu['trang_thai'] ?? 'Đang học'); ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="7" style="text-align: center; color: #777;">Không có sinh viên nào trong lớp này.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</body>
</html>