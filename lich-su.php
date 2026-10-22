<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once 'database.php';

$message = "";
$message_type = "";

// 1. KIỂM TRA ĐĂNG NHẬP (Đã đồng bộ với login.php)
if (!isset($_SESSION['ma_sv']) || (!isset($_SESSION['user_id']) && !isset($_SESSION['id']))) {
    header("Location: login.php");
    exit();
}

$ma_sv_hien_tai = $_SESSION['ma_sv'];
// Ưu tiên lấy user_id từ login.php, nếu không có thì lấy id
$user_id_hien_tai = $_SESSION['user_id'] ?? $_SESSION['id'];

// 2. XỬ LÝ HỦY ĐĂNG KÝ SỰ KIỆN
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['action']) && $_POST['action'] == 'cancel') {
    $su_kien_id_huy = intval($_POST['su_kien_id']);
    
    if ($su_kien_id_huy > 0) {
        try {
            $conn->beginTransaction();

            // Chuyển trạng thái đăng ký thành "Đã hủy"
            $stmt_huy_dk = $conn->prepare("UPDATE dang_ky SET trang_thai = 'Đã hủy' WHERE ma_sv = :ma_sv AND ma_sukien = :su_kien_id");
            $stmt_huy_dk->execute([':ma_sv' => $ma_sv_hien_tai, ':su_kien_id' => $su_kien_id_huy]);

            // Chuyển trạng thái ghế thành "DA_HUY" để nhường chỗ cho sinh viên khác
            $stmt_huy_ghe = $conn->prepare("UPDATE dat_ghe SET trang_thai = 'DA_HUY' WHERE user_id = :user_id AND su_kien_id = :su_kien_id");
            $stmt_huy_ghe->execute([':user_id' => $user_id_hien_tai, ':su_kien_id' => $su_kien_id_huy]);

            $conn->commit();
            $message = "Đã hủy vé thành công. Hẹn gặp lại bạn ở các sự kiện khác!";
            $message_type = "success";
        } catch (Exception $e) {
            $conn->rollBack();
            $message = "Lỗi hệ thống khi hủy vé: " . $e->getMessage();
            $message_type = "danger";
        }
    }
}

// 3. LẤY DANH SÁCH LỊCH SỬ ĐĂNG KÝ
$sql_history = "
    SELECT dk.thoi_gian_dang_ky, dk.trang_thai as trang_thai_dk,
           sk.ma_sukien, sk.ten_sukien, sk.thoi_gian_bat_dau,
           dd.ten_diadiem,
           g.hang, g.so_ghe
    FROM dang_ky dk
    JOIN su_kien sk ON dk.ma_sukien = sk.ma_sukien
    LEFT JOIN dia_diem dd ON sk.ma_diadiem = dd.ma_diadiem
    -- Chỉ lấy ghế đang được đặt hiện tại (nếu đã hủy thì không hiện mã ghế cũ)
    LEFT JOIN dat_ghe dg ON dg.su_kien_id = sk.ma_sukien AND dg.user_id = :user_id AND dg.trang_thai = 'DA_DAT'
    LEFT JOIN ghe g ON dg.ghe_id = g.id
    WHERE dk.ma_sv = :ma_sv
    ORDER BY dk.thoi_gian_dang_ky DESC
";
$stmt_history = $conn->prepare($sql_history);
$stmt_history->execute([':user_id' => $user_id_hien_tai, ':ma_sv' => $ma_sv_hien_tai]);
$history_list = $stmt_history->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Lịch sử sự kiện</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <link rel="stylesheet" href="css/header.css">
    <link rel="stylesheet" href="css/su-kien.css">

    <style>
        .video-background {
            position: fixed; top: 50%; left: 50%; width: 100vw; height: 100vh;
            transform: translate(-50%, -50%); object-fit: cover; z-index: -100;
            filter: contrast(1.1) brightness(1.05) !important;
        }
        .main-history-container {
            min-height: 85vh; padding: 40px 15px;
        }
        .history-card {
            background: rgba(45, 53, 74, 0.92) !important; 
            backdrop-filter: blur(12px);
            border: 1px solid rgba(255, 255, 255, 0.15); 
            border-radius: 12px; color: #ffffff;
            padding: 30px; box-shadow: 0 8px 32px rgba(0, 0, 0, 0.5);
        }
        /* Style cho bảng (Table) */
        .table-custom {
            color: #fff; border-collapse: separate; border-spacing: 0; border-radius: 8px; overflow: hidden;
        }
        .table-custom thead th {
            background-color: rgba(25, 135, 84, 0.8); border: none; padding: 15px; font-weight: bold;
        }
        .table-custom tbody tr {
            background-color: rgba(0, 0, 0, 0.2); transition: 0.3s;
        }
        .table-custom tbody tr:hover {
            background-color: rgba(255, 255, 255, 0.1);
        }
        .table-custom td {
            border-bottom: 1px solid rgba(255, 255, 255, 0.1); padding: 15px; vertical-align: middle;
        }
        .badge-status { font-size: 13px; padding: 6px 10px; }
    </style>
</head>
<body>

    <video autoplay loop muted playsinline preload="auto" class="video-background">
        <source src="videos/video01.mp4" type="video/mp4">
    </video>
    <div class="video-overlay"></div>

    <?php include_once 'header.php'; ?>

    <main class="container main-history-container">
        <div class="history-card">
            <h2 class="text-center text-uppercase fw-bold text-success mb-4">
                <i class="bi bi-clock-history me-2"></i>Lịch sử tham gia sự kiện
            </h2>

            <?php if (!empty($message)): ?>
                <div class="alert alert-<?php echo $message_type; ?> text-center fw-bold border-0">
                    <?php echo $message; ?>
                </div>
            <?php endif; ?>

            <div class="table-responsive">
                <table class="table table-custom">
                    <thead>
                        <tr>
                            <th>Tên sự kiện</th>
                            <th>Thời gian bắt đầu</th>
                            <th>Địa điểm</th>
                            <th class="text-center">Vị trí ghế</th>
                            <th class="text-center">Trạng thái</th>
                            <th class="text-center">Thao tác</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (count($history_list) > 0): ?>
                            <?php foreach ($history_list as $row): 
                                $is_cancelled = ($row['trang_thai_dk'] == 'Đã hủy');
                                // Kiểm tra sự kiện đã diễn ra chưa
                                $is_past = (strtotime($row['thoi_gian_bat_dau']) < time());
                            ?>
                            <tr>
                                <td class="fw-bold text-warning"><?php echo htmlspecialchars($row['ten_sukien']); ?></td>
                                <td><?php echo date('d/m/Y - H:i', strtotime($row['thoi_gian_bat_dau'])); ?></td>
                                <td><?php echo htmlspecialchars($row['ten_diadiem'] ?? 'Chưa cập nhật'); ?></td>
                                
                                <td class="text-center fw-bold text-info">
                                    <?php 
                                        if ($is_cancelled) {
                                            echo '<span class="text-muted fst-italic">Đã trả ghế</span>';
                                        } else {
                                            echo ($row['hang'] && $row['so_ghe']) ? $row['hang'].$row['so_ghe'] : 'Đang xếp';
                                        }
                                    ?>
                                </td>
                                
                                <td class="text-center">
                                    <?php if ($is_cancelled): ?>
                                        <span class="badge bg-secondary badge-status">Đã hủy vé</span>
                                    <?php elseif ($is_past): ?>
                                        <span class="badge bg-primary badge-status">Đã kết thúc</span>
                                    <?php else: ?>
                                        <span class="badge bg-success badge-status">Đã đăng ký</span>
                                    <?php endif; ?>
                                </td>
                                
                                <td class="text-center">
                                    <?php if (!$is_cancelled && !$is_past): ?>
                                        <form method="POST" onsubmit="return confirm('Bạn có chắc chắn muốn hủy tham gia sự kiện này? Ghế ngồi của bạn sẽ được nhường cho người khác.');">
                                            <input type="hidden" name="action" value="cancel">
                                            <input type="hidden" name="su_kien_id" value="<?php echo $row['ma_sukien']; ?>">
                                            <button type="submit" class="btn btn-outline-danger btn-sm fw-bold">
                                                <i class="bi bi-x-circle me-1"></i>Hủy vé
                                            </button>
                                        </form>
                                    <?php else: ?>
                                        <button class="btn btn-outline-secondary btn-sm fw-bold" disabled>Không khả dụng</button>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="6" class="text-center text-muted py-4 fst-italic">
                                    Bạn chưa đăng ký tham gia sự kiện nào. 
                                    <br><a href="su-kien.php" class="btn btn-success btn-sm mt-2">Xem sự kiện ngay</a>
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </main>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>