<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Gọi file kết nối cơ sở dữ liệu từ thư mục gốc
require_once dirname(__DIR__) . '/database.php';

// Chuẩn hóa biến kết nối an toàn
if (!isset($conn) && isset($pdo)) { 
    $conn = $pdo; 
}

// Kiểm tra xem người dùng đã đăng nhập chưa
$current_user_id = $_SESSION['user_id'] ?? ($_SESSION['user']['id'] ?? 0);

if ($current_user_id > 0 && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $message = trim($_POST['message'] ?? '');
    
    if (!empty($message) && isset($conn)) {
        try {
            // Câu lệnh INSERT chuẩn vào bảng messages
            $stmt = $conn->prepare("INSERT INTO messages (sender_id, sender_role, receiver_id, receiver_role, message) VALUES (?, 'student', 1, 'admin', ?)");
            $stmt->execute([$current_user_id, $message]);
            
            echo "success";
            exit();
        } catch (PDOException $e) {
            http_response_code(500);
            echo "error: " . $e->getMessage();
            exit();
        }
    }
}

http_response_code(400);
echo "invalid_request";
?>