<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Gọi file kết nối cơ sở dữ liệu từ thư mục gốc (lùi lại 1 cấp từ thư mục user/)
require_once dirname(__DIR__) . '/database.php';

// Chuẩn hóa biến kết nối an toàn
if (!isset($conn) && isset($pdo)) { 
    $conn = $pdo; 
}

// Lấy ID người dùng hiện tại từ session
$current_user_id = $_SESSION['user_id'] ?? ($_SESSION['user']['id'] ?? 1);

try {
    if (isset($conn)) {
        // Truy vấn lấy toàn bộ lịch sử tin nhắn giữa sinh viên và admin/quản lý
        $stmt = $conn->prepare("SELECT * FROM messages WHERE (sender_id = ? AND sender_role = 'student') OR (receiver_id = ? AND receiver_role = 'student') ORDER BY created_at ASC");
        $stmt->execute([$current_user_id, $current_user_id]);
        $messages = $stmt->fetchAll(PDO::FETCH_ASSOC);

        if (empty($messages)) {
            echo '<div class="text-center text-muted my-auto" style="opacity: 0.7; padding: 40px 0;">';
            echo '<i class="bi bi-chat-square-dots fs-1 d-block mb-2 text-success"></i>';
            echo 'Chưa có cuộc trò chuyện nào.<br>Hãy gửi tin nhắn để bắt đầu kết nối với Quản lý & Admin!';
            echo '</div>';
        } else {
            foreach ($messages as $m) {
                // Xác định tin nhắn do sinh viên gửi (sent) hay admin phản hồi (received)
                $is_sent = ($m['sender_role'] === 'student');
                $bubble_class = $is_sent ? 'sent' : 'received';
                $role_label = $is_sent ? 'Bạn (Sinh viên)' : 'Quản lý / Admin';
                
                echo '<div class="message-item ' . $bubble_class . '">';
                echo '<div>' . htmlspecialchars($m['message']) . '</div>';
                echo '<div class="d-flex justify-content-between align-items-center mt-2" style="font-size: 0.72rem; opacity: 0.85;">';
                echo '<span><i class="bi bi-person-fill me-1"></i>' . $role_label . '</span>';
                echo '<span class="msg-time-label">' . date('H:i - d/m/Y', strtotime($m['created_at'])) . '</span>';
                echo '</div>';
                echo '</div>';
            }
        }
    } else {
        echo '<div class="text-danger text-center">Lỗi kết nối cơ sở dữ liệu trong file fetch_messages.php.</div>';
    }
} catch (PDOException $e) {
    echo '<div class="text-danger text-center">Lỗi truy vấn CSDL: ' . htmlspecialchars($e->getMessage()) . '</div>';
}
?>