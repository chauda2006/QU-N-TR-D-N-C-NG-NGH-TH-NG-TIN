<?php
session_start();
ob_start();
require_once 'database.php';

// ==============================================================================
// 1. API TRẢ VỀ SƠ ĐỒ GHẾ NGỒI
// ==============================================================================
if (isset($_GET['action']) && $_GET['action'] == 'get_seats') {
    header('Content-Type: application/json');
    $su_kien_id = isset($_GET['su_kien_id']) ? intval($_GET['su_kien_id']) : 0;
    
    if ($su_kien_id > 0) {
        $sql_seats = "
            SELECT g.id, g.hang, g.so_ghe, g.loai_ghe,
                   IF(dg.id IS NULL, 0, 1) as is_booked
            FROM ghe g
            LEFT JOIN dat_ghe dg ON g.id = dg.ghe_id AND dg.su_kien_id = :su_kien_id AND dg.trang_thai = 'DA_DAT'
            WHERE g.phong_id = 1
            ORDER BY g.hang, g.so_ghe
        ";
        $stmt_seats = $conn->prepare($sql_seats);
        $stmt_seats->execute([':su_kien_id' => $su_kien_id]);
        echo json_encode($stmt_seats->fetchAll(PDO::FETCH_ASSOC));
    } else {
        echo json_encode([]);
    }
    exit;
}

// ==============================================================================
// 2. XỬ LÝ GIAO DIỆN VÀ LƯU FORM ĐĂNG KÝ
// ==============================================================================
$message = "";
$message_type = "";

// Lấy thông tin tài khoản đang đăng nhập[cite: 12]
$ho_ten_hien_tai  = $_SESSION['ho_ten'] ?? '';
$mssv_hien_tai    = $_SESSION['ma_sv'] ?? '';
$user_id_hien_tai = $_SESSION['user_id'] ?? $_SESSION['id'] ?? 4; 
$email_hien_tai   = $_SESSION['email'] ?? '';

// Tự động kéo Email từ bảng users nếu session chưa lưu[cite: 12]
if (empty($email_hien_tai) && !empty($mssv_hien_tai)) {
    try {
        $stmt_email = $conn->prepare("SELECT email FROM users WHERE ma_sv = :mssv LIMIT 1");
        $stmt_email->execute([':mssv' => $mssv_hien_tai]);
        if ($row = $stmt_email->fetch(PDO::FETCH_ASSOC)) {
            $email_hien_tai = $row['email'];
        }
    } catch (PDOException $e) {}
}

// Khi người dùng bấm nút Xác nhận[cite: 12]
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $ho_ten     = isset($_POST['ho_ten']) ? trim($_POST['ho_ten']) : '';
    $email      = isset($_POST['email']) ? trim($_POST['email']) : '';
    $mssv       = isset($_POST['mssv']) ? trim($_POST['mssv']) : '';
    $su_kien_id = isset($_POST['su_kien_id']) ? intval($_POST['su_kien_id']) : 0;
    $ghe_id     = isset($_POST['ghe_id']) ? intval($_POST['ghe_id']) : 0;

    if (!empty($mssv) && $su_kien_id > 0 && $ghe_id > 0) {
        try {
            $conn->beginTransaction();

            // 1. Kiểm tra ghế trống xem có ai nhanh tay đặt trước không[cite: 12]
            $check_ghe = $conn->prepare("SELECT id FROM dat_ghe WHERE su_kien_id = ? AND ghe_id = ? AND trang_thai = 'DA_DAT'");
            $check_ghe->execute([$su_kien_id, $ghe_id]);
            if ($check_ghe->rowCount() > 0) {
                throw new Exception("Rất tiếc, ghế này vừa bị người khác đặt mất. Vui lòng chọn ghế khác!");
            }

            // 2. Xử lý thông tin sinh viên mới nếu chưa có trong DB[cite: 12]
            $stmt_check = $conn->prepare("SELECT ma_sv FROM sinh_vien WHERE ma_sv = :mssv");
            $stmt_check->execute([':mssv' => $mssv]);
            if ($stmt_check->rowCount() == 0) {
                $stmt_add = $conn->prepare("INSERT INTO sinh_vien (ma_sv, ho_ten, email, mat_khau) VALUES (:mssv, :ho_ten, :email, '123456')");
                $stmt_add->execute([':mssv' => $mssv, ':ho_ten' => $ho_ten, ':email' => $email]);
            }

            // 3. Xử lý lưu đăng ký sự kiện theo đúng cấu trúc bảng dang_ky[cite: 11, 12]
            $stmt_check_dk = $conn->prepare("SELECT trang_thai FROM dang_ky WHERE user_id = :user_id AND su_kien_id = :su_kien_id");
            $stmt_check_dk->execute([':user_id' => $user_id_hien_tai, ':su_kien_id' => $su_kien_id]);
            $dk_exist = $stmt_check_dk->fetch(PDO::FETCH_ASSOC);

            if ($dk_exist) {
                if ($dk_exist['trang_thai'] == 'Đã hủy') {
                    // Nếu đã hủy -> Cập nhật lại thành Đã đăng ký[cite: 12]
                    $stmt_update_dk = $conn->prepare("UPDATE dang_ky SET trang_thai = 'Đã đăng ký' WHERE user_id = :user_id AND su_kien_id = :su_kien_id");
                    $stmt_update_dk->execute([':user_id' => $user_id_hien_tai, ':su_kien_id' => $su_kien_id]);
                } else {
                    // Nếu đang ở trạng thái 'Đã đăng ký' thì chặn lại[cite: 12]
                    throw new Exception("Bạn đã đăng ký tham gia sự kiện này rồi!");
                }
            } else {
                // Nếu chưa từng đăng ký -> Thêm mới[cite: 11, 12]
                $stmt_insert = $conn->prepare("INSERT INTO dang_ky (user_id, su_kien_id, trang_thai) VALUES (:user_id, :su_kien_id, 'Đã đăng ký')");
                $stmt_insert->execute([':user_id' => $user_id_hien_tai, ':su_kien_id' => $su_kien_id]);
            }

            // 4. Chốt ghế ngồi (Dùng ON DUPLICATE KEY để ghi đè lên các ghế cũ bị hủy)[cite: 12]
            $stmt_dat = $conn->prepare("
                INSERT INTO dat_ghe (su_kien_id, ghe_id, user_id, trang_thai, ngay_dat) 
                VALUES (:su_kien_id, :ghe_id, :user_id, 'DA_DAT', current_timestamp())
                ON DUPLICATE KEY UPDATE user_id = :user_id2, trang_thai = 'DA_DAT', ngay_dat = current_timestamp()
            ");
            $stmt_dat->execute([
                ':su_kien_id' => $su_kien_id,
                ':ghe_id'     => $ghe_id,
                ':user_id'    => $user_id_hien_tai,
                ':user_id2'   => $user_id_hien_tai
            ]);
$conn->commit();
            $message = "Đăng ký thành công! Chỗ ngồi của bạn đã được xác nhận.";
            $message_type = "success";

        } catch (Exception $e) {
            $conn->rollBack();
            $message = $e->getMessage();
            $message_type = "warning";
        }
    } else {
        $message = "Vui lòng điền đầy đủ thông tin và chọn vị trí ghế ngồi!";
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
            position: fixed; top: 50%; left: 50%; width: 100vw; height: 100vh;
            transform: translate(-50%, -50%); object-fit: cover; z-index: -100;
            filter: contrast(1.1) brightness(1.05) !important;
        }
        .main-register-container {
            min-height: 85vh; display: flex; align-items: center; justify-content: center; padding: 30px 15px;
        }
        .register-card {
            background: rgba(45, 53, 74, 0.92) !important; 
            backdrop-filter: blur(12px);
            border: 1px solid rgba(255, 255, 255, 0.15); 
            border-radius: 12px; color: #ffffff;
            width: 100%; max-width: 650px; padding: 30px; 
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.5);
        }
        .input-readonly {
            background-color: rgba(0, 0, 0, 0.25) !important; 
            border-color: rgba(255, 255, 255, 0.1) !important; 
            color: #adb5bd !important; cursor: not-allowed;
        }
        .form-control:not(.input-readonly), .form-select {
            background-color: rgba(255, 255, 255, 0.08) !important; 
            border-color: rgba(255, 255, 255, 0.3) !important;
            color: #fff !important;
        }
        .form-control:focus, .form-select:focus {
            background-color: rgba(255, 255, 255, 0.15) !important;
            border-color: #198754 !important;
            box-shadow: 0 0 0 0.25rem rgba(25, 135, 84, 0.25) !important;
        }
        
        .seat-map-container {
            margin-top: 15px; padding: 15px; background: rgba(0, 0, 0, 0.2); 
            border: 1px solid rgba(255, 255, 255, 0.15);
            border-radius: 8px; max-height: 280px; overflow-y: auto; display: none;
        }
        .seat-grid { display: flex; flex-wrap: wrap; gap: 8px; justify-content: center; }
        .seat {
            width: 40px; height: 40px; border-radius: 6px; border: 1px solid #4a4e69; 
            background: #1a1d29; color: #fff; display: flex; align-items: center; justify-content: center; 
            font-size: 12px; font-weight: bold; cursor: pointer; transition: 0.2s;
        }
        .seat:hover { background: #198754; border-color: #198754; }
        .seat.vip { border-bottom: 4px solid #ffc107; } 
        .seat.booked { background: #dc3545; border-color: #dc3545; color: #fff; cursor: not-allowed; opacity: 0.6; }
        .seat.selected { background: #0d6efd; border-color: #0d6efd; box-shadow: 0 0 10px #0d6efd; }
        
        .seat-legend { display: flex; justify-content: center; gap: 15px; margin-top: 15px; font-size: 13px; }
        .legend-item { display: flex; align-items: center; gap: 5px; }
        .legend-box { width: 15px; height: 15px; border-radius: 3px; }
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
                <i class="bi bi-calendar2-check me-2"></i>Đăng ký sự kiện
            </h2>

            <?php if (!empty($message)): ?>
                <div class="alert alert-<?php echo $message_type; ?> text-center fw-bold border-0">
                    <?php echo $message; ?>
                </div>
            <?php endif; ?>

            <form action="dang-ky.php" method="POST" id="registerForm">
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label fw-bold text-white">Họ và tên <span class="text-danger">*</span></label>
                        <input type="text" class="form-control <?php echo !empty($ho_ten_hien_tai) ? 'input-readonly' : ''; ?>" id="ho_ten" name="ho_ten" value="<?php echo htmlspecialchars($ho_ten_hien_tai); ?>" <?php echo !empty($ho_ten_hien_tai) ? 'readonly' : ''; ?> required>
                    </div>
                    
                    <div class="col-md-6 mb-3">
                        <label class="form-label fw-bold text-white">MSSV <span class="text-danger">*</span></label>
                        <input type="text" class="form-control <?php echo !empty($mssv_hien_tai) ? 'input-readonly' : ''; ?>" id="mssv" name="mssv" value="<?php echo htmlspecialchars($mssv_hien_tai); ?>" <?php echo !empty($mssv_hien_tai) ? 'readonly' : ''; ?> required>
                    </div>
                </div>

                <div class="mb-3">
                    <label class="form-label fw-bold text-white">Email liên hệ <span class="text-danger">*</span></label>
                    <input type="email" class="form-control <?php echo !empty($email_hien_tai) ? 'input-readonly' : ''; ?>" id="email" name="email" value="<?php echo htmlspecialchars($email_hien_tai); ?>" placeholder="Nhập email của bạn..." <?php echo !empty($email_hien_tai) ? 'readonly' : ''; ?> required>
                </div>

                <div class="mb-3">
                    <label class="form-label fw-bold text-white">Sự kiện tham gia <span class="text-danger">*</span></label>
                    <select class="form-select fw-bold text-success" id="su_kien_id" name="su_kien_id" required>
                        <option value="" class="text-white">-- Click để chọn sự kiện --</option>
                        <?php
                        try {
                            $stmt_sk = $conn->query("SELECT ma_sukien, ten_sukien FROM su_kien WHERE thoi_gian_bat_dau >= NOW() ORDER BY thoi_gian_bat_dau ASC");
                            $selected_id = isset($_GET['event_id']) ? intval($_GET['event_id']) : 0;
                            while ($row = $stmt_sk->fetch(PDO::FETCH_ASSOC)) {
                                $selected = ($row['ma_sukien'] == $selected_id) ? 'selected' : '';
                                echo '<option class="text-dark" value="' . $row['ma_sukien'] . '" ' . $selected . '>' . htmlspecialchars($row['ten_sukien']) . '</option>';
                            }
                        } catch (Exception $e) {}
                        ?>
                    </select>
                </div>

                <div class="mb-4">
                    <input type="hidden" name="ghe_id" id="ghe_id" required>
                    <div id="seat_map_area" class="seat-map-container">
                        <div class="text-center mb-3 fw-bold text-warning" id="stage_area" style="border-bottom: 2px dashed rgba(255,255,255,0.2); padding-bottom: 10px;">KHU VỰC SÂN KHẤU</div>
                        <div class="seat-grid" id="seat_grid">
                        </div>
                        
                        <div class="seat-legend">
                            <div class="legend-item"><div class="legend-box" style="background: #1a1d29; border: 1px solid #4a4e69;"></div> Ghế trống</div>
                            <div class="legend-item"><div class="legend-box" style="background: #1a1d29; border-bottom: 3px solid #ffc107;"></div> Ghế VIP</div>
                            <div class="legend-item"><div class="legend-box" style="background: #dc3545;"></div> Đã đặt</div>
                            <div class="legend-item"><div class="legend-box" style="background: #0d6efd;"></div> Đang chọn</div>
                        </div>
                    </div>
                    <div id="seat_instruction" class="text-light small mt-2 fst-italic">Vui lòng chọn một sự kiện để hiển thị sơ đồ ghế.</div>
                </div>

                <button type="submit" class="btn btn-success w-100 fw-bold py-2 fs-5 shadow-sm">
                    <i class="bi bi-ticket-perforated me-2"></i>ĐẶT VÉ VÀ NHẬN CHỖ
                </button>
            </form>
        </div>
    </main>

    <script>
        document.addEventListener("DOMContentLoaded", function() {
            const selectEvent = document.getElementById('su_kien_id');
            const seatMapArea = document.getElementById('seat_map_area');
            const seatGrid = document.getElementById('seat_grid');
            const seatInstruction = document.getElementById('seat_instruction');
            const hiddenGheId = document.getElementById('ghe_id');
            const registerForm = document.getElementById('registerForm');

            function loadSeats(eventId) {
                if (!eventId) {
                    seatMapArea.style.display = 'none';
                    seatInstruction.style.display = 'block';
                    hiddenGheId.value = '';
                    return;
                }

                seatInstruction.innerHTML = '<span class="spinner-border spinner-border-sm text-success"></span> Đang tải sơ đồ ghế...';
                
                fetch(`dang-ky.php?action=get_seats&su_kien_id=${eventId}`)
                    .then(response => response.json())
                    .then(seats => {
                        seatGrid.innerHTML = '';
                        hiddenGheId.value = ''; 
                        
                        if (seats.length === 0) {
                            seatInstruction.innerHTML = '<span class="text-danger">Không tìm thấy sơ đồ ghế cho sự kiện này.</span>';
                            seatMapArea.style.display = 'none';
                            return;
                        }

                        seats.forEach(seat => {
                            const seatDiv = document.createElement('div');
                            seatDiv.className = `seat ${seat.loai_ghe === 'VIP' ? 'vip' : ''} ${seat.is_booked == 1 ? 'booked' : 'available'}`;
                            seatDiv.textContent = `${seat.hang}${seat.so_ghe}`;
                            seatDiv.title = `Ghế ${seat.hang}${seat.so_ghe} - ${seat.loai_ghe}`;
                            
                            if (seat.is_booked == 0) {
                                seatDiv.addEventListener('click', function() {
                                    document.querySelectorAll('.seat.selected').forEach(el => el.classList.remove('selected'));
                                    this.classList.add('selected');
                                    hiddenGheId.value = seat.id;
                                });
                            }
                            seatGrid.appendChild(seatDiv);
                        });

                        seatInstruction.style.display = 'none';
                        seatMapArea.style.display = 'block';
                    })
                    .catch(error => {
                        seatInstruction.innerHTML = '<span class="text-danger">Lỗi kết nối khi tải sơ đồ ghế.</span>';
                    });
            }

            selectEvent.addEventListener('change', function() {
                loadSeats(this.value);
            });

            if (selectEvent.value) {
                loadSeats(selectEvent.value);
            }

            registerForm.addEventListener('submit', function(e) {
                if (hiddenGheId.value === '') {
                    e.preventDefault();
                    alert("Vui lòng click chọn 1 vị trí ghế trên sơ đồ trước khi đặt vé!");
                }
            });
        });
    </script>
</body>
</html>