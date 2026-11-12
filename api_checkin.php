<?php
header("Content-Type: application/json; charset=UTF-8");
session_start();
require_once 'database.php';
require_once __DIR__ . '/attendance_date.php';

// Sử dụng $conn hoặc $pdo tùy theo cấu hình file database.php của bạn
$db = $conn ?? $pdo ?? null;

$ma_sv = $_SESSION['ma_sv'] ?? null;
if (!$ma_sv) {
    echo json_encode(["status" => "error", "message" => "Vui lòng đăng nhập tài khoản sinh viên để điểm danh."]);
    exit;
}

$data = json_decode(file_get_contents("php://input"), true);
$eventId = $data['event_id'] ?? null;
$inputPasscode = trim($data['passcode'] ?? '');
$userLat = $data['latitude'] ?? null;
$userLng = $data['longitude'] ?? null;

if (!$eventId || !$inputPasscode) {
    echo json_encode(["status" => "error", "message" => "Vui lòng nhập đầy đủ mã Passcode."]);
    exit;
}

if (
    !is_numeric($userLat) || !is_numeric($userLng) ||
    (float)$userLat < -90 || (float)$userLat > 90 ||
    (float)$userLng < -180 || (float)$userLng > 180
) {
    echo json_encode(["status" => "error", "message" => "Không thể xác định vị trí hợp lệ. Vui lòng bật quyền truy cập vị trí và thử lại."]);
    exit;
}

$schoolLat = 9.185607178374637;
$schoolLng = 105.16695532108882;
$maxDistance = 150;
$earthRadius = 6371000;
$lat1 = deg2rad($schoolLat);
$lat2 = deg2rad((float)$userLat);
$deltaLat = $lat2 - $lat1;
$deltaLng = deg2rad((float)$userLng - $schoolLng);
$a = sin($deltaLat / 2) ** 2
    + cos($lat1) * cos($lat2) * sin($deltaLng / 2) ** 2;
$distance = 2 * $earthRadius * asin(min(1, sqrt($a)));
$attendanceLocation = $distance <= $maxDistance ? 'BDU.Cm' : 'Khác';

try {
    // 1. Lấy user_id và thông tin sinh viên từ mã sinh viên
    $stmtUser = $db->prepare("SELECT id FROM users WHERE ma_sv = ?");
    $stmtUser->execute([$ma_sv]);
    $user = $stmtUser->fetch(PDO::FETCH_ASSOC);

    if (!$user) {
        echo json_encode(["status" => "error", "message" => "Không tìm thấy thông tin tài khoản sinh viên."]);
        exit;
    }
    $userId = $user['id'];

    // 2. CẬP NHẬT SỬA LỖI: Đổi 'id' thành 'su_kien_id' cho đúng với cấu trúc bảng su_kien
    $stmt = $db->prepare("SELECT * FROM su_kien WHERE ma_sukien = ? OR su_kien_id = ?");
    $stmt->execute([$eventId, $eventId]);
    $event = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$event || ($event['is_attendance_open'] ?? 0) != 1) {
        echo json_encode(["status" => "error", "message" => "Sự kiện chưa mở điểm danh hoặc đã kết thúc."]);
        exit;
    }
    if (!isAttendanceDateAllowed($event['thoi_gian_bat_dau'] ?? null)) {
        echo json_encode(["status" => "error", "message" => "Không thể điểm danh cho sự kiện đã diễn ra quá ngày."]);
        exit;
    }

    // Kiểm tra thời gian hết hạn passcode
    if (!empty($event['passcode_expired_at']) && strtotime($event['passcode_expired_at']) < time()) {
        echo json_encode(["status" => "error", "message" => "Mã Passcode đã hết hạn! Vui lòng liên hệ Ban tổ chức."]);
        exit;
    }

    // Kiểm tra tính chính xác của Passcode
    if (trim($event['passcode'] ?? '') !== $inputPasscode) {
        echo json_encode(["status" => "error", "message" => "Mã Passcode không chính xác."]);
        exit;
    }

    $targetEventId = $event['ma_sukien'] ?? $eventId;
    $diemThuong = $event['diem_thuong'] ?? 5; 

    // 3. Kiểm tra xem sinh viên đã có bản ghi trong bảng dang_ky_su_kien chưa
    $stmtReg = $db->prepare("SELECT * FROM dang_ky_su_kien WHERE su_kien_id = ? AND user_id = ?");
    $stmtReg->execute([$targetEventId, $userId]);
    $registration = $stmtReg->fetch(PDO::FETCH_ASSOC);

    // Nếu đã có bản ghi và trạng thái là đã tham gia rồi thì chặn
    if ($registration) {
        $currentStatus = $registration['trang_thai'] ?? '';
        if ($currentStatus === 'da_tham_gia' || $currentStatus === 'present') {
            echo json_encode(["status" => "warning", "message" => "Bạn đã điểm danh sự kiện này rồi!"]);
            exit;
        }
    }

    // Keep the attendance location available to the admin attendance list.
    $locationColumn = $db->query("SHOW COLUMNS FROM dang_ky_su_kien LIKE 'vi_tri_diem_danh'")->fetch(PDO::FETCH_ASSOC);
    if (!$locationColumn) {
        $db->exec("ALTER TABLE dang_ky_su_kien ADD COLUMN vi_tri_diem_danh VARCHAR(20) DEFAULT NULL");
    }

    // Bắt đầu Transaction 
    $db->beginTransaction();

if ($registration) {
        $updateStmt = $db->prepare("UPDATE dang_ky_su_kien SET trang_thai = 'da_tham_gia', thoi_gian_diem_danh = NOW(), vi_tri_diem_danh = ? WHERE id = ?");
        $updateStmt->execute([$attendanceLocation, $registration['id']]);
    } else {
        $insertStmt = $db->prepare("INSERT INTO dang_ky_su_kien (user_id, su_kien_id, trang_thai, thoi_gian_diem_danh, vi_tri_diem_danh) VALUES (?, ?, 'da_tham_gia', NOW(), ?)");
        $insertStmt->execute([$userId, $targetEventId, $attendanceLocation]);
    }

    // 4. Tự động cộng điểm rèn luyện cho tài khoản sinh viên
    $updateDiem = $db->prepare("
        UPDATE users 
        SET diem_ren_luyen = diem_ren_luyen + ? 
        WHERE id = ?
    ");
    $updateDiem->execute([$diemThuong, $userId]);

    $db->commit();

    echo json_encode([
        "status" => "success", 
        "message" => "Điểm danh thành công và được cộng " . $diemThuong . " điểm rèn luyện!"
    ]);

} catch (PDOException $e) {
    if ($db && $db->inTransaction()) {
        $db->rollBack();
    }
    echo json_encode(["status" => "error", "message" => "Lỗi hệ thống cơ sở dữ liệu: " . $e->getMessage()]);
}
?>