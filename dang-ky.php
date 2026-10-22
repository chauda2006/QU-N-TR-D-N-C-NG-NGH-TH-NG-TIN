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
            // Lưu dữ liệu vào bảng dang_ky_su_kien
            $sql_insert = "INSERT INTO dang_ky_su_kien (ho_ten, email, mssv, su_kien_id, ngay_dang_ky) 
                           VALUES (:ho_ten, :email, :mssv, :su_kien_id, NOW())";
            $stmt_insert = $conn->prepare($sql_insert);
            $check = $stmt_insert->execute([
                ':ho_ten'     => $ho_ten,
                ':email'      => $email,
                ':mssv'       => $mssv,
                ':su_kien_id' => $su_kien_id
            ]);

            if ($check) {
                $message = "Đăng ký tham gia sự kiện thành công!";
                $message_type = "success";
            }
        } catch (PDOException $e) {
            $message = "Lỗi khi đăng ký: " . $e->getMessage();
            $message_type = "danger";
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

    <!-- Video background -->
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
                        <option value="">-- Đang tải danh sách sự kiện... --</option>
                    </select>
                </div>

                <button type="submit" class="btn btn-success w-100 fw-bold py-2">
                    <i class="bi bi-send-check me-2"></i>Xác nhận đăng ký
                </button>
            </form>
        </div>
    </main>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>

    <script>
        document.addEventListener("DOMContentLoaded", function() {
            const select = document.getElementById('su_kien_id');

            // Lấy event_id từ tham số URL nếu người dùng nhấn từ trang su-kien.php[cite: 6]
            const urlParams = new URLSearchParams(window.location.search);
            const selectedEventId = urlParams.get('event_id') || urlParams.get('id');

            // Gọi API lấy danh sách sự kiện
            fetch('api_su_kien.php')
                .then(response => response.json())
                .then(data => {
                    select.innerHTML = '<option value="">-- Chọn sự kiện --</option>';

                    let events = Array.isArray(data) ? data : (data.data || data.result || data.events || []);

                    if (events.length > 0) {
                        events.forEach(item => {
                            let id = item.id || item.ma_su_kien || item.id_su_kien || item.event_id;
                            let title = item.ten_su_kien || item.tieu_de || item.ten || item.title || item.name;

                            if (id && title) {
                                let option = document.createElement('option');
                                option.value = id;
                                option.textContent = title;

                                // Tự động chọn sự kiện nếu truyền ID từ trang su-kien.php sang[cite: 6]
                                if (selectedEventId && id == selectedEventId) {
                                    option.selected = true;
                                }

                                select.appendChild(option);
                            }
                        });
                    } else {
                        select.innerHTML = '<option value="">Không có sự kiện nào sẵn có</option>';
                    }
                })
                .catch(error => {
                    console.error('Lỗi nạp dữ liệu:', error);
                    select.innerHTML = '<option value="">Không thể tải danh sách sự kiện</option>';
                });
        });
    </script>
</body>
</html>