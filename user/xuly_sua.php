<?php
session_start();
require_once '../database.php';

// Tự động nhận diện biến kết nối PDO ($conn hoặc $pdo)
$db = isset($conn) ? $conn : (isset($pdo) ? $pdo : null);

if (!$db) {
    die("Lỗi: Không tìm thấy kết nối cơ sở dữ liệu.");
}

// Kiểm tra quyền quản trị
$vai_tro = $_SESSION['vai_tro'] ?? '';
$isManager = in_array(strtolower(trim($vai_tro)), ['admin', 'quan_ly_su_kien', 'quan_ly', 'management']);

if (!$isManager) {
    header("Location: login.php");
    exit();
}

// Hàm lấy giá trị cột linh hoạt
function getColValue($row, ...$keys) {
    if (!$row) return '';
    foreach ($keys as $k) {
        if (isset($row[$k]) && $row[$k] !== '') {
            return $row[$k];
        }
    }
    return '';
}

// Lấy danh sách toàn bộ cột thực tế trong bảng su_kien
$stmtCols = $db->query("SHOW COLUMNS FROM su_kien");
$existingCols = $stmtCols->fetchAll(PDO::FETCH_COLUMN);

// Hàm tìm tên cột chính xác tồn tại trong CSDL
function findRealCol($existingCols, $candidates, $default) {
    foreach ($candidates as $c) {
        if (in_array($c, $existingCols)) {
            return $c;
        }
    }
    return $default;
}

// Tự động phát hiện các cột thực tế
$colPk       = findRealCol($existingCols, ['ma_sukien', 'id', 'ma_sk', 'id_sukien'], 'ma_sukien');
$colTitle    = findRealCol($existingCols, ['ten_su_kien', 'ten_sukien', 'tieu_de', 'ten'], 'ten_su_kien');
$colStart    = findRealCol($existingCols, ['thoi_gian_bat_dau', 'bat_dau', 'ngay_to_chuc'], 'thoi_gian_bat_dau');
$colEnd      = findRealCol($existingCols, ['thoi_gian_ket_thuc', 'ket_thuc'], 'thoi_gian_ket_thuc');
$colLocation = findRealCol($existingCols, ['ten_diadiem', 'dia_diem', 'diadiem', 'dia_chi'], 'ten_diadiem');
$colPosition = findRealCol($existingCols, ['vi_tri', 'vi_tri_cu_the', 'dia_chi_chi_tiet'], 'vi_tri');
$colTarget   = findRealCol($existingCols, ['doi_tuong', 'doi_tuong_tham_gia', 'doi_tuong_ap_dung'], 'doi_tuong');
$colCapacity = findRealCol($existingCols, ['suc_chua', 'so_luong', 'so_nguoi_toi_da'], 'suc_chua');
$colDesc     = findRealCol($existingCols, ['mo_ta', 'mota', 'chi_tiet', 'description'], 'mo_ta');

$id = $_GET['ma_sukien'] ?? $_GET['id'] ?? $_GET['ma_sk'] ?? null;

$event = null;
if ($id) {
    try {
        $stmt = $db->prepare("SELECT * FROM su_kien WHERE {$colPk} = ?");
        $stmt->execute([$id]);
        $event = $stmt->fetch(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        $event = null;
    }
}

$error_msg = "";
$save_success = false; // Cờ đánh dấu đã lưu thành công

// XỬ LÝ LƯU DỮ LIỆU KHI SUBMIT FORM
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update') {
    $post_id     = $_POST['ma_sukien'] ?? null;
    $ten_su_kien = trim($_POST['ten_su_kien'] ?? '');
    $bat_dau     = $_POST['thoi_gian_bat_dau'] ?? null;
    $ket_thuc    = $_POST['thoi_gian_ket_thuc'] ?? null;
    $dia_diem    = trim($_POST['dia_diem'] ?? '');
    $vi_tri      = trim($_POST['vi_tri'] ?? '');
    $doi_tuong   = trim($_POST['doi_tuong'] ?? '');
    $suc_chua    = trim($_POST['suc_chua'] ?? '');
    $mo_ta       = trim($_POST['mo_ta'] ?? '');

    if ($post_id) {
        try {
            $updateFields = [];
            $params = [':id' => $post_id];

            $updateFields[] = "{$colTitle} = :ten";
            $params[':ten'] = $ten_su_kien;

            if (in_array($colStart, $existingCols)) {
                $updateFields[] = "{$colStart} = :bat_dau";
                $params[':bat_dau'] = $bat_dau;
            }
            if (in_array($colEnd, $existingCols)) {
                $updateFields[] = "{$colEnd} = :ket_thuc";
                $params[':ket_thuc'] = $ket_thuc;
            }
            if (in_array($colLocation, $existingCols)) {
                $updateFields[] = "{$colLocation} = :dia_diem";
                $params[':dia_diem'] = $dia_diem;
            }
            if (in_array($colPosition, $existingCols)) {
                $updateFields[] = "{$colPosition} = :vi_tri";
                $params[':vi_tri'] = $vi_tri;
            }
            if (in_array($colTarget, $existingCols)) {
                $updateFields[] = "{$colTarget} = :doi_tuong";
                $params[':doi_tuong'] = $doi_tuong;
            }
            if (in_array($colCapacity, $existingCols)) {
                $updateFields[] = "{$colCapacity} = :suc_chua";
                $params[':suc_chua'] = $suc_chua;
            }
            if (in_array($colDesc, $existingCols)) {
                $updateFields[] = "{$colDesc} = :mo_ta";
                $params[':mo_ta'] = $mo_ta;
            }

            $sql = "UPDATE su_kien SET " . implode(", ", $updateFields) . " WHERE {$colPk} = :id";
            $updateStmt = $db->prepare($sql);
            $updateStmt->execute($params);

            // Bật cờ lưu thành công
            $save_success = true;
        } catch (Exception $e) {
            $error_msg = "Lỗi khi lưu dữ liệu: " . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>Chỉnh sửa sự kiện</title>

    <!-- Bootstrap 5 & Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">

    <!-- DÒNG LIÊN KẾT FILE CSS VŨ TRỤ 3D ĐÂY NHÉ: -->
    <link rel="stylesheet" href="../css/user-sua.css">

    <!-- SweetAlert2 -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>
<body>
    <!-- LỚP HẠT SAO VÀ BÁNH RĂNG ĐỘNG CƠ CÔNG NGHỆ 3D -->
    <div class="starfield-layer-1"></div>
    <div class="starfield-layer-2"></div>
    <div class="bg-space-dust"></div>
    <div class="gear-engine-left"></div>
    <div class="gear-engine-right"></div>

    <!-- CONTAINER CHÍNH -->
    <div class="container" style="max-width: 800px;">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h3 class="text-warning mb-0"><i class="bi bi-pencil-square me-2"></i>Chỉnh Sửa Thông Tin Sự Kiện</h3>
            <a href="../su-kien.php" class="btn btn-outline-light btn-sm"><i class="bi bi-arrow-left me-1"></i> Trở về danh sách</a>
        </div>

        <?php if (!empty($error_msg)): ?>
            <div class="alert alert-danger"><?php echo htmlspecialchars($error_msg); ?></div>
        <?php endif; ?>

        <?php if (!$event): ?>
            <!-- GIAO DIỆN CHỌN SỰ KIỆN -->
            <div class="card p-4 shadow">
                <h5 class="text-warning mb-3"><i class="bi bi-list-check me-2"></i>Chọn sự kiện bạn muốn chỉnh sửa:</h5>
                <?php
                try {
                    $list = $db->query("SELECT * FROM su_kien ORDER BY {$colStart} DESC")->fetchAll(PDO::FETCH_ASSOC);
                    if (empty($list)) {
                        echo "<p class='text-muted mb-0'>Chưa có sự kiện nào trong hệ thống.</p>";
                    } else {
                        echo "<div class='list-group'>";
                        foreach ($list as $item) {
                            $item_id   = getColValue($item, $colPk, 'ma_sukien', 'id');
                            $item_name = getColValue($item, $colTitle, 'ten_su_kien', 'ten_sukien', 'tieu_de');
                            $item_time = getColValue($item, $colStart, 'thoi_gian_bat_dau');

                            echo "<a href='xuly_sua.php?ma_sukien={$item_id}' class='list-group-item list-group-item-action mb-2'>
                                    <span><i class='bi bi-calendar-event me-2 text-info'></i><strong>" . htmlspecialchars($item_name) . "</strong></span>
                                    <small class='text-light opacity-75'>{$item_time}</small>
                                  </a>";
                        }
                        echo "</div>";
                    }
                } catch (Exception $e) {
                    echo "<p class='text-danger'>Lỗi kết nối CSDL: " . $e->getMessage() . "</p>";
                }
                ?>
            </div>
        <?php else: ?>
            <!-- FORM NHẬP LIỆU SỬA DỮ LIỆU -->
            <?php
                $val_id       = getColValue($event, $colPk, 'ma_sukien', 'id');
                $val_title    = getColValue($event, $colTitle, 'ten_su_kien', 'ten_sukien', 'tieu_de');
                $val_start    = getColValue($event, $colStart, 'thoi_gian_bat_dau');
                $val_end      = getColValue($event, $colEnd, 'thoi_gian_ket_thuc');
                $val_location = getColValue($event, $colLocation, 'ten_diadiem', 'dia_diem', 'diadiem');
                $val_position = getColValue($event, $colPosition, 'vi_tri');
                $val_target   = getColValue($event, $colTarget, 'doi_tuong');
                $val_capacity = getColValue($event, $colCapacity, 'suc_chua');
                $val_desc     = getColValue($event, $colDesc, 'mo_ta', 'mota');
            ?>
            <form method="POST" class="p-4 shadow">
                <input type="hidden" name="action" value="update">
                <input type="hidden" name="ma_sukien" value="<?php echo htmlspecialchars($val_id); ?>">

                <!-- Tên sự kiện -->
                <div class="mb-3">
                    <label class="form-label">Tên sự kiện (*):</label>
                    <input type="text" name="ten_su_kien" class="form-control" value="<?php echo htmlspecialchars($val_title); ?>" required>
                </div>

                <!-- Thời gian bắt đầu & kết thúc -->
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Thời gian bắt đầu (*):</label>
                        <input type="datetime-local" name="thoi_gian_bat_dau" class="form-control" value="<?php echo !empty($val_start) ? date('Y-m-d\TH:i', strtotime($val_start)) : ''; ?>" required>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Thời gian kết thúc:</label>
                        <input type="datetime-local" name="thoi_gian_ket_thuc" class="form-control" value="<?php echo !empty($val_end) ? date('Y-m-d\TH:i', strtotime($val_end)) : ''; ?>">
                    </div>
                </div>

                <!-- Địa điểm & Vị trí cụ thể -->
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Địa điểm tổ chức:</label>
                        <input type="text" name="dia_diem" class="form-control" value="<?php echo htmlspecialchars($val_location); ?>" placeholder="Ví dụ: Hội trường A, Sân bóng...">
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Vị trí cụ thể:</label>
                        <input type="text" name="vi_tri" class="form-control" value="<?php echo htmlspecialchars($val_position); ?>" placeholder="Ví dụ: Tầng 2 - Tòa nhà trung tâm">
                    </div>
                </div>

                <!-- Đối tượng tham gia & Sức chứa -->
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Đối tượng tham gia:</label>
                        <input type="text" name="doi_tuong" class="form-control" value="<?php echo htmlspecialchars($val_target); ?>" placeholder="Ví dụ: Sinh viên toàn trường, CLB...">
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Sức chứa (Số người tối đa):</label>
                        <input type="text" name="suc_chua" class="form-control" value="<?php echo htmlspecialchars($val_capacity); ?>" placeholder="Ví dụ: 200 hoặc Không giới hạn">
                    </div>
                </div>

                <!-- Mô tả chi tiết -->
                <div class="mb-3">
                    <label class="form-label">Mô tả và nội dung sự kiện:</label>
                    <textarea name="mo_ta" class="form-control" rows="5" placeholder="Nhập chi tiết mô tả sự kiện..."><?php echo htmlspecialchars($val_desc); ?></textarea>
                </div>

                <div class="d-flex justify-content-end gap-2 mt-4">
                    <a href="../su-kien.php" class="btn btn-secondary">Hủy bỏ</a>
                    <button type="submit" class="btn btn-warning"><i class="bi bi-save me-1"></i> Lưu thay đổi</button>
                </div>
            </form>
        <?php endif; ?>
    </div>

    <!-- THÔNG BÁO POPUP VŨ TRỤ 3D KHI CẬP NHẬT THÀNH CÔNG -->
    <?php if ($save_success): ?>
    <script>
        Swal.fire({
            title: '🎉 THÀNH CÔNG!',
            text: 'Cập nhật thông tin sự kiện thành công!',
            icon: 'success',
            background: '#0a0826',
            color: '#ffffff',
            confirmButtonText: 'ĐỒNG Ý',
            confirmButtonColor: '#ff1e56',
            customClass: {
                popup: 'swal-space-popup'
            }
        }).then((result) => {
            if (result.isConfirmed || result.isDismissed) {
                window.location.href = "../su-kien.php";
            }
        });
    </script>
    <?php endif; ?>
</body>
</html>