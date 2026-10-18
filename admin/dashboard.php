<?php
// Bật hiển thị lỗi để dễ debug
ini_set('display_errors', 1);
error_reporting(E_ALL);

require_once __DIR__ . '/check_admin.php'; // Kiểm tra quyền admin
require_once __DIR__ . '/../database.php';   // Kết nối CSDL

/** @var PDO $conn */

// 1. Lọc theo lớp nếu có yêu cầu (mặc định là '242101TH001')
$selected_class = $_GET['ma_lop'] ?? '242101TH001';

// 2. Truy vấn lấy tổng số sinh viên trong lớp được chọn
$stmt_count = $conn->prepare("SELECT COUNT(*) as total FROM users WHERE ma_lop = ? AND vai_tro = 'sinh_vien'");
$stmt_count->execute([$selected_class]);
$total_students = $stmt_count->fetch()['total'];

// 3. Lấy danh sách sinh viên kèm thống kê sự kiện trong tháng này và tổng điểm rèn luyện
$first_day_of_month = date('Y-m-01 00:00:00');
$last_day_of_month  = date('Y-m-t 23:59:59');

$sql_students = "
    SELECT u.*, 
        -- Thống kê số sự kiện tham gia trong tháng này
        (
            SELECT COUNT(DISTINCT dk.su_kien_id) 
            FROM dang_ky_su_kien dk 
            JOIN su_kien sk ON dk.su_kien_id = sk.id 
            WHERE dk.user_id = u.id 
              AND dk.trang_thai = 'da_tham_gia'
              AND sk.ngay_to_chuc BETWEEN :first_day AND :last_day
        ) AS so_su_kien_thang,
        
        -- Tính tổng điểm rèn luyện được cộng từ trước tới nay
        (
            SELECT COALESCE(SUM(sk.diem_ren_luyen), 0) 
            FROM dang_ky_su_kien dk 
            JOIN su_kien sk ON dk.su_kien_id = sk.id 
            WHERE dk.user_id = u.id 
              AND dk.trang_thai = 'da_tham_gia'
        ) AS tong_diem_ren_luyen
    FROM users u 
    WHERE u.ma_lop = :ma_lop AND u.vai_tro = 'sinh_vien'
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
    // Nếu chưa có cấu trúc bảng dang_ky_su_kien phức tạp, fallback về lấy dữ liệu đơn giản
    $stmt_students = $conn->prepare("SELECT *, 0 AS so_su_kien_thang, 0 AS tong_diem_ren_luyen FROM users WHERE ma_lop = ? AND vai_tro = 'sinh_vien'");
    $stmt_students->execute([$selected_class]);
    $students = $stmt_students->fetchAll();
}

// 4. Lấy danh sách tất cả các lớp có trong hệ thống để làm bộ lọc
$stmt_classes = $conn->query("SELECT DISTINCT ma_lop FROM users WHERE ma_lop IS NOT NULL AND ma_lop != ''");
$classes = $stmt_classes->fetchAll();
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>Admin Dashboard - Quản lý Lớp & Điểm Rèn Luyện</title>
    <style>
        body { font-family: Arial, sans-serif; background: #f4f6f9; margin: 0; padding: 20px; }
        .container { max-width: 1100px; margin: 0 auto; background: #fff; padding: 25px; border-radius: 8px; box-shadow: 0 4px 12px rgba(0,0,0,0.1); }
        .header { display: flex; justify-content: space-between; align-items: center; border-bottom: 2px solid #eee; padding-bottom: 15px; margin-bottom: 20px; flex-wrap: wrap; gap: 10px; }
        .header h2 { color: #333; margin: 0; }
        .header-nav { display: flex; gap: 8px; align-items: center; }
        .btn-home { background-color: #0d6efd; color: white; padding: 8px 12px; text-decoration: none; border-radius: 4px; font-weight: bold; font-size: 14px; }
        .btn-home:hover { background-color: #0b5ed7; }
        .btn-events { background-color: #198754; color: white; padding: 8px 12px; text-decoration: none; border-radius: 4px; font-weight: bold; font-size: 14px; }
        .btn-events:hover { background-color: #157347; }
        .btn-logout { background: #dc3545; color: white; padding: 8px 12px; text-decoration: none; border-radius: 4px; font-weight: bold; font-size: 14px; }
        .btn-logout:hover { background: #bb2d3b; }
        .stat-card { background: #e7f1ff; border-left: 5px solid #0d6efd; padding: 15px; border-radius: 4px; margin-bottom: 20px; }
        .stat-card h3 { margin: 0 0 5px 0; color: #0d6efd; }
        .stat-card p { margin: 0; font-size: 18px; font-weight: bold; color: #333; }
        .filter-form { margin-bottom: 20px; display: flex; gap: 10px; align-items: center; }
        .filter-form select { padding: 8px 12px; border-radius: 4px; border: 1px solid #ccc; font-size: 14px; }
        table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        th, td { border: 1px solid #ddd; padding: 10px; text-align: left; font-size: 14px; }
        th { background-color: #f8f9fa; color: #333; }
        tr:nth-child(even) { background-color: #f9f9f9; }
        .badge-event { background-color: #0dcaf0; color: #000; padding: 3px 8px; border-radius: 12px; font-weight: bold; font-size: 13px; }
        .badge-score { background-color: #ffc107; color: #000; padding: 3px 8px; border-radius: 12px; font-weight: bold; font-size: 13px; }
    </style>
</head>
<body>

<div class="container">
    <div class="header">
        <h2>🛠️ Trang Quản Trị Hệ Thống (Admin)</h2>
        <div class="header-nav">
            <a href="../index.php" class="btn-home">Trang chủ</a>
            <a href="../su-kien.php" class="btn-events">Quản lý sự kiện</a>
            <a href="logout.php" class="btn-logout">Đăng xuất</a>
        </div>
    </div>

    <!-- Form chọn lớp -->
    <form method="GET" class="filter-form">
        <label for="ma_lop"><strong>Chọn lớp học:</strong></label>
        <select name="ma_lop" id="ma_lop" onchange="this.form.submit()">
            <?php foreach ($classes as $cls): ?>
                <option value="<?php echo htmlspecialchars($cls['ma_lop']); ?>" <?php echo ($selected_class === $cls['ma_lop']) ? 'selected' : ''; ?>>
                    <?php echo htmlspecialchars($cls['ma_lop']); ?>
                </option>
            <?php endforeach; ?>
        </select>
    </form>

    <!-- Thống kê chung -->
    <div class="stat-card">
        <h3>📊 Thống kê lớp: <?php echo htmlspecialchars($selected_class); ?></h3>
        <p>Tổng số sinh viên: <span><?php echo $total_students; ?></span> người</p>
    </div>

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
                        <td>
                            <span class="badge-event"><?php echo (int)($stu['so_su_kien_thang'] ?? 0); ?> sự kiện</span>
                        </td>
                        <td>
                            <span class="badge-score">+<?php echo (int)($stu['tong_diem_ren_luyen'] ?? 0); ?> điểm</span>
                        </td>
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