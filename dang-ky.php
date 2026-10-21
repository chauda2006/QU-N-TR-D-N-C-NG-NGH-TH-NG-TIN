<?php
ob_start();
require_once 'database.php'; 

$message = "";
$message_type = "";

// Xử lý lưu thông tin khi sinh viên bấm gửi form Đăng ký
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $ho_ten     = isset($_POST['ho_ten']) ? trim($_POST['ho_ten']) : '';
    $email      = isset($_POST['email']) ? trim($_POST['email']) : '';
    $mssv       = isset($_POST['mssv']) ? trim($_POST['mssv']) : '';
    $su_kien_id = isset($_POST['su_kien_id']) ? intval($_POST['su_kien_id']) : 0;

    if (!empty($ho_ten) && !empty($email) && !empty($mssv) && $su_kien_id > 0) {
        try {
            // 1. Kiểm tra sinh viên đã có trong hệ thống chưa (tránh lỗi khóa ngoại)
            $sql_check_sv = "SELECT ma_sv FROM sinh_vien WHERE ma_sv = :mssv";
            $stmt_check = $conn->prepare($sql_check_sv);
            $stmt_check->execute([':mssv' => $mssv]);
            
            if ($stmt_check->rowCount() == 0) {
                // Nếu là sinh viên mới, tự động tạo hồ sơ vào bảng sinh_vien
                $sql_add_sv = "INSERT INTO sinh_vien (ma_sv, ho_ten, email, mat_khau) VALUES (:mssv, :ho_ten, :email, '123456')";
                $stmt_add = $conn->prepare($sql_add_sv);
                $stmt_add->execute([
                    ':mssv' => $mssv,
                    ':ho_ten' => $ho_ten,
                    ':email' => $email
                ]);
            }

            // 2. Lưu dữ liệu vào bảng dang_ky (Chuẩn theo cấu trúc CSDL của nhóm)
            $sql_insert = "INSERT INTO dang_ky (ma_sv, ma_sukien) VALUES (:mssv, :su_kien_id)";
            $stmt_insert = $conn->prepare($sql_insert);
            $check = $stmt_insert->execute([
                ':mssv' => $mssv,
                ':su_kien_id' => $su_kien_id
            ]);

            if ($check) {
                $message = "Đăng ký tham gia sự kiện thành công!";
                $message_type = "success";
            }
        } catch (PDOException $e) {
            // Xử lý báo lỗi nếu sinh viên đăng ký 1 sự kiện 2 lần (Lỗi Unique Key)
            if ($e->getCode() == 23000) {
                $message = "Bạn đã đăng ký sự kiện này rồi!";
                $message_type = "warning";
            } else {
                $message = "Lỗi hệ thống: " . $e->getMessage();
                $message_type = "danger";
            }
        }
    } else {
        $message = "Vui lòng điền đầy đủ thông tin và chọn sự kiện!";
        $message_type = "warning";
    }
}
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Đăng ký sự kiện</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <link rel="stylesheet" href="css/header.css">
    <link rel="stylesheet" href="css/su-kien.css">

    <style>
        .video-background {
            position: fixed;
            top: 50%;
            left: 50%;
            width: 100vw;
            height: 100vh;
            transform: translate(-50%, -50%);
            object-fit: cover;
            z-index: -100;
            filter: contrast(1.1) brightness(1.05) !important;
            will-change: transform;
        }
        .main-register-container {
            min-height: 85vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 30px 15px;
        }
        .register-card {
            background: rgba(26, 29, 41, 0.88) !important;
            backdrop-filter: blur(8px);
            border: 1px solid #2d3142;
            border-radius: 12px;
            color: #e2e8f0;
            width: 100%;
            max-width: 520px;
            padding: 30px;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.4);
        }
    </style>
</head>
<body>

    <video autoplay loop muted playsinline preload="auto" class="video-background">
        <source src="videos/video01.mp4" type="video/mp4">
    </video>
    <div class="video-overlay"></div>

    <?php include_once 'header.php'; ?>

    <main class="container main-register-container">
        <div class="register-card">
            <h2 class="text-center text-uppercase fw-bold text-success mb-4">
                <i class="bi bi-pencil-square me-2"></i>Đăng ký sự kiện
            </h2>

            <?php if (!empty($message)): ?>
                <div class="alert alert-<?php echo $message_type; ?> text-center">
                    <?php echo $message; ?>
                </div>
            <?php endif; ?>

            <form action="dang-ky.php" method="POST">
                <div class="mb-3">
                    <label for="ho_ten" class="form-label small text-muted">Họ và tên người đăng ký <span class="text-danger">*</span></label>
                    <input type="text" class="form-control" id="ho_ten" name="ho_ten" required placeholder="Nhập họ và tên..." style="background-color: #0f111a; border-color: #2d3142; color: #fff;">
                </div>

                <div class="mb-3">
                    <label for="mssv" class="form-label small text-muted">Mã số sinh viên (MSSV) <span class="text-danger">*</span></label>
                    <input type="text" class="form-control" id="mssv" name="mssv" required placeholder="Nhập MSSV..." style="background-color: #0f111a; border-color: #2d3142; color: #fff;">
                </div>

                <div class="mb-3">
                    <label for="email" class="form-label small text-muted">Địa chỉ Email liên hệ <span class="text-danger">*</span></label>
                    <input type="email" class="form-control" id="email" name="email" required placeholder="Nhập email..." style="background-color: #0f111a; border-color: #2d3142; color: #fff;">
                </div>

                <div class="mb-4">
                    <label for="su_kien_id" class="form-label small text-muted">Chọn sự kiện tham gia <span class="text-danger">*</span></label>
                    <select class="form-select" id="su_kien_id" name="su_kien_id" required style="background-color: #0f111a; border-color: #2d3142; color: #fff;">
                        <option value="">-- Chọn sự kiện --</option>
                        <?php
                        // Tải danh sách sự kiện trực tiếp bằng PHP
                        try {
                            $sql_sk = "SELECT ma_sukien, ten_sukien FROM su_kien";
                            $stmt_sk = $conn->query($sql_sk);
                            
                            // Lấy ID sự kiện từ URL nếu sinh viên click từ trang danh sách
                            $selected_id = isset($_GET['event_id']) ? intval($_GET['event_id']) : (isset($_GET['id']) ? intval($_GET['id']) : 0);
                            
                            while ($row = $stmt_sk->fetch(PDO::FETCH_ASSOC)) {
                                $selected = ($row['ma_sukien'] == $selected_id) ? 'selected' : '';
                                echo '<option value="' . $row['ma_sukien'] . '" ' . $selected . '>' . htmlspecialchars($row['ten_sukien']) . '</option>';
                            }
                        } catch (PDOException $e) {
                            echo '<option value="">Lỗi tải danh sách sự kiện</option>';
                        }
                        ?>
                    </select>
                </div>

                <button type="submit" class="btn btn-success w-100 fw-bold py-2">
                    <i class="bi bi-send-check me-2"></i>Xác nhận đăng ký
                </button>
            </form>
        </div>
    </main>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <!-- Đã xóa bỏ đoạn mã JavaScript gây lỗi fetch API -->
</body>
</html>