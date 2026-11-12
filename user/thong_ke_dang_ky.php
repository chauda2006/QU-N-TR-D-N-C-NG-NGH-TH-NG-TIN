<?php
// Bắt đầu session và kiểm tra quyền truy cập (Chỉ Admin hoặc Quản lý sự kiện mới được xem)
session_start();
include '../database.php'; 
$conn = $conn ?? $pdo ?? null;

$vai_tro = $_SESSION['vai_tro'] ?? '';
if ($vai_tro !== 'admin' && $vai_tro !== 'quan_ly_su_kien') {
    echo "<script>alert('Bạn không có quyền truy cập trang này!'); window.location.href = '../index.php';</script>";
    exit();
}

// Lấy danh sách tất cả sự kiện để đưa vào ô chọn (dropdown)
$stmt_events = $conn->query("SELECT ma_sukien, ten_sukien FROM su_kien ORDER BY ma_sukien DESC");
$events_list = $stmt_events->fetchAll(PDO::FETCH_ASSOC);

// Lấy mã sự kiện được chọn từ URL (mặc định lấy sự kiện đầu tiên nếu có)
$selected_event_id = $_GET['ma_sukien'] ?? ($events_list[0]['ma_sukien'] ?? 0);
$filter_status = $_GET['status'] ?? 'all';

// Lấy thông tin tên sự kiện đang chọn
$current_event_name = "Chưa chọn sự kiện";
foreach ($events_list as $ev) {
    if ($ev['ma_sukien'] == $selected_event_id) {
        $current_event_name = $ev['ten_sukien'];
        break;
    }
}

// 📥 XỬ LÝ XUẤT FILE EXCEL KHI NGƯỜI DÙNG BẤM NÚT EXPORT
if (isset($_GET['export']) && $_GET['export'] == 'excel') {
    // Truy vấn toàn bộ dữ liệu không phân trang/lọc để xuất file đầy đủ
    $stmt_export = $conn->prepare("
        SELECT u.ma_sv, u.ho_ten, u.email, u.sdt, u.ma_lop, dk.ngay_dang_ky, dk.trang_thai
        FROM dang_ky_su_kien dk
        JOIN users u ON dk.user_id = u.id
        WHERE dk.su_kien_id = ?
        ORDER BY dk.ngay_dang_ky DESC
    ");
    $stmt_export->execute([$selected_event_id]);
    $export_data = $stmt_export->fetchAll(PDO::FETCH_ASSOC);

    // Đặt tiêu đề HTTP để trình duyệt hiểu đây là file Excel (.xls)
    header("Content-Type: application/vnd.ms-excel; charset=utf-8");
    header("Content-Disposition: attachment; filename=ThongKe_DangKy_SuKien_" . $selected_event_id . "_" . date('Ymd_His') . ".xls");
    header("Pragma: no-cache");
    header("Expires: 0");

    // In bảng HTML dưới dạng Excel Table
    echo '<meta charset="UTF-8">';
    echo '<table border="1">';
    echo '<tr><th colspan="7" style="background-color: #10b981; color: white; font-size: 16px;">DANH SÁCH THÔNG KÊ ĐĂNG KÝ SỰ KIỆN: ' . htmlspecialchars($current_event_name) . '</th></tr>';
    echo '<tr style="background-color: #f2f2f2; font-weight: bold;">';
    echo '<th>STT</th><th>Mã Sinh Viên (MSSV)</th><th>Họ và Tên</th><th>Lớp</th><th>Email</th><th>Số Điện Thoại</th><th>Trạng Thái</th>';
    echo '</tr>';

    $i = 1;
    foreach ($export_data as $row) {
        $trang_thai_text = 'Đã đăng ký';
        if ($row['trang_thai'] === 'da_tham_gia' || $row['trang_thai'] === 'present') {
            $trang_thai_text = 'Đã điểm danh';
        } elseif ($row['trang_thai'] === 'da_huy' || $row['trang_thai'] === 'cancelled') {
            $trang_thai_text = 'Đã hủy ghế';
        }

        echo '<tr>';
        echo '<td>' . $i++ . '</td>';
        echo '<td>`' . htmlspecialchars($row['ma_sv']) . '</td>'; // Thêm dấu ` để Excel không nuốt số 0 đầu MSSV
        echo '<td>' . htmlspecialchars($row['ho_ten']) . '</td>';
        echo '<td>' . htmlspecialchars($row['ma_lop'] ?? 'N/A') . '</td>';
        echo '<td>' . htmlspecialchars($row['email']) . '</td>';
        echo '<td>`' . htmlspecialchars($row['sdt'] ?? 'N/A') . '</td>';
        echo '<td>' . $trang_thai_text . '</td>';
        echo '</tr>';
    }
    echo '</table>';
    exit();
}

// 📊 TRUY VẤN THỐNG KÊ SỐ LƯỢNG TỔNG QUAN CHO SỰ KIỆN NÀY
$stmt_stats = $conn->prepare("
    SELECT 
        COUNT(*) as total,
        SUM(CASE WHEN trang_thai = 'da_tham_gia' OR trang_thai = 'present' THEN 1 ELSE 0 END) as checked_in,
        SUM(CASE WHEN trang_thai = 'da_huy' OR trang_thai = 'cancelled' THEN 1 ELSE 0 END) as cancelled,
        SUM(CASE WHEN trang_thai != 'da_tham_gia' AND trang_thai != 'present' AND trang_thai != 'da_huy' AND trang_thai != 'cancelled' THEN 1 ELSE 0 END) as registered_only
    FROM dang_ky_su_kien 
    WHERE su_kien_id = ?
");
$stmt_stats->execute([$selected_event_id]);
$stats = $stmt_stats->fetch(PDO::FETCH_ASSOC) ?: ['total' => 0, 'checked_in' => 0, 'cancelled' => 0, 'registered_only' => 0];

// 📋 TRUY VẤN DANH SÁCH CHI TIẾT KÈM BỘ LỌC TRẠNG THÁI
$sql_query = "
    SELECT u.ma_sv, u.ho_ten, u.email, u.sdt, u.ma_lop, dk.ngay_dang_ky, dk.trang_thai
    FROM dang_ky_su_kien dk
    JOIN users u ON dk.user_id = u.id
    WHERE dk.su_kien_id = ?
";

$params = [$selected_event_id];

if ($filter_status === 'checked_in') {
    $sql_query .= " AND (dk.trang_thai = 'da_tham_gia' OR dk.trang_thai = 'present')";
} elseif ($filter_status === 'cancelled') {
    $sql_query .= " AND (dk.trang_thai = 'da_huy' OR dk.trang_thai = 'cancelled')";
} elseif ($filter_status === 'registered') {
    $sql_query .= " AND (dk.trang_thai != 'da_tham_gia' AND dk.trang_thai != 'present' AND dk.trang_thai != 'da_huy' AND dk.trang_thai != 'cancelled')";
}

$sql_query .= " ORDER BY dk.ngay_dang_ky DESC";

$stmt_registrations = $conn->prepare($sql_query);
$stmt_registrations->execute($params);
$registrations = $stmt_registrations->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Thống Kê Người Đăng Ký Sự Kiện</title>
    <!-- Bootstrap 5 & Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">

   <link rel="stylesheet" href="../css/thong-ke.css?v=<?php echo time(); ?>">
    <style>
    
       /* ==========================================================================
   ✨ NÂNG CẤP NÚT ĐIỀU HƯỚNG 3D NEON (QUẢN LÝ SỰ KIỆN & TRANG CHỦ)
   ========================================================================== */
.d-flex.justify-content-between.align-items-center.mb-4 div a.btn,
.d-flex.justify-content-between.align-items-center.mb-4 a.btn {
    background: linear-gradient(135deg, rgba(15, 12, 35, 0.8), rgba(8, 5, 25, 0.95)) !important;
    border: 2px solid rgba(5, 255, 225, 0.4) !important;
    color: #ffffff !important;
    border-radius: 14px !important;
    padding: 10px 20px !important;
    font-weight: 800 !important;
    font-family: 'Plus Jakarta Sans', sans-serif !important;
    letter-spacing: 0.5px !important;
    box-shadow: 0 8px 25px rgba(0, 0, 0, 0.5), inset 0 0 10px rgba(5, 255, 225, 0.1) !important;
    transition: all 0.35s cubic-bezier(0.4, 0, 0.2, 1) !important;
    position: relative !important;
    overflow: hidden !important;
}

/* Hiệu ứng ánh sáng quét qua nút (Shimmer effect) */
.d-flex.justify-content-between.align-items-center.mb-4 div a.btn::before,
.d-flex.justify-content-between.align-items-center.mb-4 a.btn::before {
    content: '' !important;
    position: absolute !important;
    top: 0 !important; left: -100% !important;
    width: 100% !important; height: 100% !important;
    background: linear-gradient(90deg, transparent, rgba(5, 255, 225, 0.3), transparent) !important;
    transition: 0.5s ease !important;
}

.d-flex.justify-content-between.align-items-center.mb-4 div a.btn:hover::before,
.d-flex.justify-content-between.align-items-center.mb-4 a.btn:hover::before {
    left: 100% !important;
}

/* Hiệu ứng khi rê chuột vào (Hover State) - Nổi khối 3D & Phát sáng Neon Xanh/Đỏ */
.d-flex.justify-content-between.align-items-center.mb-4 div a.btn:hover,
.d-flex.justify-content-between.align-items-center.mb-4 a.btn:hover {
    transform: translateY(-3px) scale(1.03) !important;
    border-color: #05ffe1 !important;
    color: #05ffe1 !important;
    background: linear-gradient(135deg, rgba(20, 15, 45, 0.95), rgba(10, 6, 30, 1)) !important;
    box-shadow: 0 12px 35px rgba(5, 255, 225, 0.4), 0 0 20px rgba(5, 255, 225, 0.3), inset 0 0 15px rgba(5, 255, 225, 0.2) !important;
}
    </style>
</head>
<body>

    <div class="container py-5">
        <!-- Header điều hướng -->
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2><i class="bi bi-file-earmark-bar-graph me-2 text-success"></i>Thống Kê Người Đăng Ký & Điểm Danh Sự Kiện</h2>
            <div>
                <a href="../su-kien.php" class="btn btn-outline-secondary btn-sm">→ Trang sự kiện</a>
               <a href="../index.php" class="btn btn-outline-secondary btn-sm">→ Trang chủ</a>
            </div>
        </div>

        <!-- Khung thống kê chính -->
        <div class="stat-container">
            <!-- Form chọn sự kiện thống kê & Nút Xuất Excel -->
            <form method="GET" class="row align-items-end mb-4 g-3">
                <input type="hidden" name="status" value="<?php echo htmlspecialchars($filter_status); ?>">
                <div class="col-md-8">
                    <label class="form-label fw-bold text-info"><i class="bi bi-calendar-event me-1"></i> Chọn sự kiện cần xem thống kê:</label>
                    <select name="ma_sukien" class="form-select bg-dark text-white border-secondary" onchange="this.form.submit()">
                        <?php foreach ($events_list as $ev): ?>
                            <option value="<?php echo $ev['ma_sukien']; ?>" <?php echo ($ev['ma_sukien'] == $selected_event_id) ? 'selected' : ''; ?>>
                                [ID: <?php echo $ev['ma_sukien']; ?>] <?php echo htmlspecialchars($ev['ten_sukien']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-4 text-md-end">
                    <!-- Nút Xuất Excel Pro -->
                    <a href="thong_ke_dang_ky.php?ma_sukien=<?php echo $selected_event_id; ?>&status=<?php echo $filter_status; ?>&export=excel" class="btn btn-success fw-bold px-4 py-2 w-100 shadow">
                        <i class="bi bi-file-earmark-excel-fill me-2"></i> Xuất File Excel
                    </a>
                </div>
            </form>

            <h4 class="text-success mb-3">Sự kiện: <span class="text-white"><?php echo htmlspecialchars($current_event_name); ?></span></h4>

            <!-- 📊 CÁC THẺ THỐNG KÊ NHANH (MINI CARDS) -->
            <div class="row g-3 mb-4">
                <div class="col-md-3">
                    <div class="mini-card border-info">
                        <span class="text-muted small">Tổng đăng ký</span>
                        <h3 class="text-info fw-bold mb-0 mt-1"><?php echo $stats['total']; ?></h3>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="mini-card border-warning">
                        <span class="text-muted small">Đã đăng ký (Chưa quét)</span>
                        <h3 class="text-warning fw-bold mb-0 mt-1"><?php echo $stats['registered_only']; ?></h3>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="mini-card border-success">
                        <span class="text-muted small">Đã điểm danh (Tham gia)</span>
                        <h3 class="text-success fw-bold mb-0 mt-1"><?php echo $stats['checked_in']; ?></h3>
                    </div>
                </div>
            </div>

            <!-- 🎛️ BỘ LỌC NHANH TRẠNG THÁI -->
            <div class="d-flex gap-2 mb-3 flex-wrap">
                <a href="thong_ke_dang_ky.php?ma_sukien=<?php echo $selected_event_id; ?>&status=all" class="btn btn-sm <?php echo ($filter_status === 'all') ? 'btn-primary fw-bold' : 'btn-outline-secondary'; ?>">
                    Tất cả (<?php echo $stats['total']; ?>)
                </a>
                <a href="thong_ke_dang_ky.php?ma_sukien=<?php echo $selected_event_id; ?>&status=registered" class="btn btn-sm <?php echo ($filter_status === 'registered') ? 'btn-warning text-dark fw-bold' : 'btn-outline-secondary'; ?>">
                    Đã đăng ký (<?php echo $stats['registered_only']; ?>)
                </a>
                <a href="thong_ke_dang_ky.php?ma_sukien=<?php echo $selected_event_id; ?>&status=checked_in" class="btn btn-sm <?php echo ($filter_status === 'checked_in') ? 'btn-success fw-bold' : 'btn-outline-secondary'; ?>">
                    Đã điểm danh (<?php echo $stats['checked_in']; ?>)
                </a>
                <a href="thong_ke_dang_ky.php?ma_sukien=<?php echo $selected_event_id; ?>&status=cancelled" class="btn btn-sm <?php echo ($filter_status === 'cancelled') ? 'btn-danger fw-bold' : 'btn-outline-secondary'; ?>">
                    Đã hủy ghế (<?php echo $stats['cancelled']; ?>)
                </a>
            </div>

            <!-- Bảng danh sách chi tiết -->
            <div class="table-responsive">
                <table class="table table-hover align-middle text-center">
                    <thead>
                        <tr>
                            <th>STT</th>
                            <th>Mã Sinh Viên (MSSV)</th>
                            <th>Họ và Tên</th>
                            <th>Lớp</th>
                            <th>Email</th>
                            <th>Số Điện Thoại</th>
                            <th>Thời Gian Đăng Ký</th>
                            <th>Trạng Thái</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (count($registrations) > 0): ?>
                            <?php foreach ($registrations as $index => $reg): ?>
                                <tr>
                                    <td><?php echo $index + 1; ?></td>
                                    <td><strong class="text-warning"><?php echo htmlspecialchars($reg['ma_sv']); ?></strong></td>
                                    <td class="text-start"><?php echo htmlspecialchars($reg['ho_ten']); ?></td>
                                    <td><span class="badge bg-secondary"><?php echo htmlspecialchars($reg['ma_lop'] ?? 'Chưa cập nhật'); ?></span></td>
                                    <td><?php echo htmlspecialchars($reg['email']); ?></td>
                                    <td><?php echo htmlspecialchars($reg['sdt'] ?? 'Chưa cập nhật'); ?></td>
                                    <td class="small text-muted"><?php echo !empty($reg['ngay_dang_ky']) ? date('d/m/Y H:i', strtotime($reg['ngay_dang_ky'])) : 'N/A'; ?></td>
                                    <td>
                                        <?php if ($reg['trang_thai'] === 'da_tham_gia' || $reg['trang_thai'] === 'present'): ?>
                                            <span class="badge bg-success px-3 py-2"><i class="bi bi-check-circle-fill me-1"></i> Đã điểm danh</span>
                                        <?php elseif ($reg['trang_thai'] === 'da_huy' || $reg['trang_thai'] === 'cancelled'): ?>
                                            <span class="badge bg-danger px-3 py-2"><i class="bi bi-x-circle-fill me-1"></i> Đã hủy ghế</span>
                                        <?php else: ?>
                                            <span class="badge bg-info text-dark px-3 py-2"><i class="bi bi-clock-fill me-1"></i> Đã đăng ký</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="8" class="text-center py-4 text-muted">Không có sinh viên nào phù hợp với trạng thái này.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>