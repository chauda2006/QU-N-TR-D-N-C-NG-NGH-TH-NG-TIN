<?php
header("Content-Type: application/json; charset=UTF-8");
session_start();
require_once 'database.php';

$ma_sv = $_SESSION['ma_sv'] ?? null;
if (!$ma_sv) {
    echo json_encode(["status" => "error", "message" => "Vui lòng đăng nhập tài khoản sinh viên để điểm danh."]);
    exit;
}

$data = json_decode(file_get_contents("php://input"));
$eventId = $data->event_id ?? null;
$inputPasscode = trim($data->passcode ?? '');

if (!$eventId || !$inputPasscode) {
    echo json_encode(["status" => "error", "message" => "Vui lòng nhập đầy đủ mã Passcode."]);
    exit;
}

try {
    // 1. Lấy user_id từ mã sinh viên
    $stmtUser = $pdo->prepare("SELECT id FROM users WHERE ma_sv = ?");
    $stmtUser->execute([$ma_sv]);
    $user = $stmtUser->fetch(PDO::FETCH_ASSOC);

    if (!$user) {
        echo json_encode(["status" => "error", "message" => "Không tìm thấy thông tin tài khoản sinh viên."]);
        exit;
    }
    $userId = $user['id'];

    // 2. Kiểm tra thông tin sự kiện và mã passcode
    $stmt = $pdo->prepare("SELECT trang_thai_su_kien, is_attendance_open, passcode, passcode_expired_at FROM su_kien WHERE id = ?");
    $stmt->execute([$eventId]);
    $event = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$event || $event['trang_thai_su_kien'] !== 'ongoing' || $event['is_attendance_open'] != 1) {
        echo json_encode(["status" => "error", "message" => "Sự kiện chưa mở điểm danh hoặc đã kết thúc."]);
        exit;
    }

    // Kiểm tra thời gian hết hạn (5 phút)
    if (strtotime($event['passcode_expired_at']) < time()) {
        echo json_encode(["status" => "error", "message" => "Mã Passcode đã hết hạn! Vui lòng liên hệ Ban tổ chức."]);
        exit;
    }

    if ($event['passcode'] !== $inputPasscode) {
        echo json_encode(["status" => "error", "message" => "Mã Passcode không chính xác."]);
        exit;
    }

    // 3. Kiểm tra xem sinh viên đã đăng ký tham gia sự kiện này chưa
    $stmtReg = $pdo->prepare("SELECT id, status_attendance FROM dang_ky WHERE su_kien_id = ? AND user_id = ?");
    $stmtReg->execute([$eventId, $userId]);
    $registration = $stmtReg->fetch(PDO::FETCH_ASSOC);

    if (!$registration) {
        echo json_encode(["status" => "error", "message" => "Bạn chưa đăng ký tham gia sự kiện này."]);
        exit;
    }

    if ($registration['status_attendance'] === 'present') {
        echo json_encode(["status" => "warning", "message" => "Bạn đã điểm danh sự kiện này rồi!"]);
        exit;
    }

    // 4. Cập nhật trạng thái điểm danh thành công kèm thời gian hiện tại
    $updateStmt = $pdo->prepare("
        UPDATE dang_ky 
        SET status_attendance = 'present', attended_at = NOW(), trang_thai = 'da_tham_gia' 
        WHERE id = ?
    ");
    $updateStmt->execute([$registration['id']]);

    echo json_encode(["status" => "success", "message" => "Điểm danh thành công!"]);

} catch (PDOException $e) {
    echo json_encode(["status" => "error", "message" => "Lỗi hệ thống: " . $e->getMessage()]);
}
?>