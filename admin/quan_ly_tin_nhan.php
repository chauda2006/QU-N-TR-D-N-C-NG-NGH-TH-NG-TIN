<?php
session_start();
require_once dirname(__DIR__) . '/database.php'; 

if (!isset($conn) && isset($pdo)) { $conn = $pdo; }

// Kiểm tra quyền Admin hoặc Quản lý sự kiện
$isAdmin = (isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true) || 
           (isset($_SESSION['vai_tro']) && ($_SESSION['vai_tro'] === 'admin' || $_SESSION['vai_tro'] === 'quan_ly_su_kien')) ||
           (isset($_SESSION['user']) && is_array($_SESSION['user']) && isset($_SESSION['user']['role']) && $_SESSION['user']['role'] === 'admin');

if (!$isAdmin) {
    header("Location: ../user/login.php");
    exit();
}

$selected_student_id = $_GET['student_id'] ?? 0;
$admin_role = $_SESSION['vai_tro'] ?? 'admin';
$admin_id = $_SESSION['user_id'] ?? ($_SESSION['user']['id'] ?? 1);

// Xử lý khi Admin gửi phản hồi cho sinh viên
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty(trim($_POST['reply_message'])) && $selected_student_id > 0 && isset($conn)) {
    $reply = trim($_POST['reply_message']);
    try {
        $stmt_insert = $conn->prepare("INSERT INTO messages (sender_id, sender_role, receiver_id, receiver_role, message) VALUES (?, ?, ?, 'student', ?)");
        $stmt_insert->execute([$admin_id, $admin_role, $selected_student_id, $reply]);
    } catch (PDOException $e) {}
    
    header("Location: quan_ly_tin_nhan.php?student_id=" . $selected_student_id);
    exit();
}

// Lấy danh sách sinh viên kèm thông tin chính xác từ cột ho_ten, ma_sv và ma_lop trong bảng users
$students = [];
$db_error = "";
if (isset($conn)) {
    try {
        $check_table = $conn->query("SHOW TABLES LIKE 'messages'");
        if ($check_table->rowCount() > 0) {
            $sql_users = "SELECT DISTINCT m.sender_id, u.ho_ten, u.ma_sv, u.ma_lop 
                          FROM messages m 
                          LEFT JOIN users u ON m.sender_id = u.id 
                          WHERE m.sender_role = 'student'";
            
            $stmt_users = $conn->query($sql_users);
            $students = $stmt_users->fetchAll(PDO::FETCH_ASSOC);
        }
    } catch (PDOException $e) {
        $db_error = "Lỗi CSDL: " . $e->getMessage();
    }
}

// Nếu chưa chọn sinh viên nào nhưng có danh sách sinh viên -> mặc định chọn sinh viên đầu tiên
if ($selected_student_id == 0 && !empty($students)) {
    $selected_student_id = $students[0]['sender_id'];
}

// Xác định thông tin chi tiết của sinh viên đang chọn để hiển thị tiêu đề
$selected_student_name = "Sinh viên #" . $selected_student_id;
$selected_student_info = "";
foreach($students as $st) {
    if ($st['sender_id'] == $selected_student_id) {
        $selected_student_name = !empty($st['ho_ten']) ? $st['ho_ten'] : "Sinh viên #" . $st['sender_id'];
        $mssv_str = !empty($st['ma_sv']) ? " - MSSV: " . $st['ma_sv'] : "";
        $lop_str = !empty($st['ma_lop']) ? " - Lớp: " . $st['ma_lop'] : "";
        $selected_student_info = $mssv_str . $lop_str;
        break;
    }
}

// Lấy toàn bộ lịch sử hội thoại với sinh viên đang được chọn
$messages = [];
if ($selected_student_id > 0 && isset($conn)) {
    try {
        $stmt_msg = $conn->prepare("SELECT * FROM messages WHERE (sender_id = ? AND sender_role = 'student') OR (receiver_id = ? AND receiver_role = 'student') ORDER BY created_at ASC");
        $stmt_msg->execute([$selected_student_id, $selected_student_id]);
        $messages = $stmt_msg->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        $messages = [];
    }
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Trung tâm Phản hồi & Trò chuyện Sinh viên</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;600;700;800&display=swap" rel="stylesheet">
    <style>
        *, *::before, *::after { box-sizing: border-box !important; font-family: 'Plus Jakarta Sans', system-ui, -apple-system, sans-serif !important; }
        
        html, body {
            margin: 0 !important; padding: 0 !important;
            background-color: #030008 !important;
            color: #ffffff !important;
            min-height: 100vh !important;
            overflow-x: hidden !important;
        }
        
        body::before {
            content: '' !important;
            position: fixed !important;
            top: 0; left: 0; width: 100vw; height: 100vh;
            background: radial-gradient(circle at 15% 20%, rgba(255, 42, 109, 0.25) 0%, transparent 48%),
                        radial-gradient(circle at 85% 80%, rgba(5, 255, 161, 0.22) 0%, transparent 48%),
                        linear-gradient(135deg, #090314 0%, #030008 100%) !important;
            z-index: -999;
            pointer-events: none;
        }

        .container {
            max-width: 1150px !important;
            margin-top: 30px !important;
        }

        .admin-chat-wrapper {
            max-width: 100%;
            margin: 20px auto;
            display: flex;
            gap: 20px;
            height: 78vh;
        }

        .student-list {
            width: 360px;
            background: rgba(12, 8, 32, 0.95);
            backdrop-filter: blur(25px);
            -webkit-backdrop-filter: blur(25px);
            border: 2px solid rgba(5, 255, 161, 0.4);
            border-radius: 20px;
            padding: 20px;
            overflow-y: auto;
            box-shadow: 0 20px 50px rgba(0,0,0,0.8);
        }

        .student-list::-webkit-scrollbar { width: 6px; }
        .student-list::-webkit-scrollbar-thumb { background: rgba(5,255,161,0.3); border-radius: 10px; }

        .student-item {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 14px 16px;
            margin-bottom: 10px;
            border-radius: 14px;
            color: #fff;
            text-decoration: none;
            background: rgba(255, 255, 255, 0.04);
            border: 1px solid rgba(5, 255, 161, 0.2);
            font-weight: 700;
            transition: all 0.3s ease;
            position: relative;
        }

        .student-item:hover, .student-item.active {
            background: linear-gradient(135deg, rgba(255, 42, 109, 0.75), rgba(5, 255, 161, 0.35));
            border-color: #05ffa1;
            box-shadow: 0 0 20px rgba(5, 255, 161, 0.4);
            transform: translateX(4px);
        }

        /* 🔴 CHẤM ĐỎ PHÁT SÁNG NEON BÁO TIN NHẮN CHƯA ĐỌC */
        .notification-dot {
            position: absolute;
            top: 12px;
            right: 14px;
            width: 12px;
            height: 12px;
            background: linear-gradient(135deg, #ff2a6d 0%, #ff0055 100%);
            border-radius: 50%;
            box-shadow: 0 0 10px #ff2a6d, 0 0 20px #ff2a6d;
            animation: pulseDot 1.5s infinite;
        }

        @keyframes pulseDot {
            0% { transform: scale(0.95); opacity: 1; }
            50% { transform: scale(1.35); opacity: 0.7; }
            100% { transform: scale(0.95); opacity: 1; }
        }

        .chat-panel {
            flex: 1;
            background: rgba(12, 8, 32, 0.95);
            backdrop-filter: blur(25px);
            -webkit-backdrop-filter: blur(25px);
            border: 2px solid rgba(5, 255, 161, 0.4);
            border-radius: 20px;
            display: flex;
            flex-direction: column;
            overflow: hidden;
            box-shadow: 0 20px 50px rgba(0,0,0,0.8);
        }

        .chat-messages {
            flex: 1;
            padding: 20px;
            overflow-y: auto;
            display: flex;
            flex-direction: column;
            gap: 15px;
            background: rgba(5, 3, 15, 0.6);
        }

        .chat-messages::-webkit-scrollbar { width: 8px; }
        .chat-messages::-webkit-scrollbar-thumb { background: rgba(5,255,161,0.4); border-radius: 10px; }

        .msg-bubble {
            max-width: 70%;
            padding: 14px 18px;
            border-radius: 16px;
            font-weight: 600;
            font-size: 0.95rem;
            line-height: 1.5;
            word-break: break-word;
        }

        .msg-student {
            background: rgba(255, 255, 255, 0.1);
            align-self: flex-start;
            border-left: 4px solid #ff2a6d;
            color: #ffffff;
        }

        .msg-admin {
            background: linear-gradient(135deg, #05ffa1 0%, #047857 100%);
            align-self: flex-end;
            color: #030008;
            font-weight: 700;
            box-shadow: 0 0 20px rgba(5, 255, 161, 0.4);
        }

        .chat-input-area {
            padding: 15px 20px;
            background: rgba(12, 8, 32, 0.98);
            border-top: 2px solid rgba(5, 255, 161, 0.3);
            display: flex;
            gap: 12px;
        }

        .chat-input {
            flex: 1;
            background: rgba(8, 5, 22, 0.98) !important;
            border: 2px solid rgba(5, 255, 161, 0.45) !important;
            color: #fff !important;
            border-radius: 12px !important;
            padding: 12px 16px !important;
            font-weight: 600 !important;
            outline: none !important;
            box-shadow: inset 0 2px 8px rgba(0,0,0,0.8) !important;
        }

        .chat-input:focus {
            border-color: #05ffa1 !important;
            box-shadow: 0 0 20px rgba(5, 255, 161, 0.6) !important;
        }

        .btn-send-reply {
            background: linear-gradient(135deg, #05ffa1 0%, #047857 100%) !important;
            border: none !important;
            color: #030008 !important;
            font-weight: 800 !important;
            padding: 0 25px !important;
            border-radius: 12px !important;
            cursor: pointer;
        }

        .btn-send-reply:hover {
            transform: translateY(-2px);
            box-shadow: 0 0 30px rgba(5, 255, 161, 0.9) !important;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h3 class="fw-bold m-0" style="color: #05ffa1; text-shadow: 0 0 15px rgba(5,255,161,0.5);">
                <i class="bi bi-headset me-2"></i> Trung tâm Hỗ trợ & Phản hồi Sinh viên
            </h3>
            <a href="../su-kien.php" class="btn btn-outline-light btn-sm fw-bold px-3 py-2" style="border-radius: 10px;">
                <i class="bi bi-arrow-left me-1"></i> Về trang Sự kiện
            </a>
        </div>

        <?php if (!empty($db_error)): ?>
            <div class="alert alert-danger border-2 border-danger fw-bold mb-4" style="background: rgba(255, 42, 109, 0.2); color: #ff2a6d !important; border-radius: 12px;">
                <i class="bi bi-exclamation-triangle-fill me-2"></i> <?= htmlspecialchars($db_error); ?>
            </div>
        <?php endif; ?>

        <div class="admin-chat-wrapper">
            <!-- Cột danh sách sinh viên -->
            <div class="student-list">
                <h6 class="text-uppercase fw-bold mb-3" style="color: #ff2a6d; letter-spacing: 0.5px;">Danh sách Sinh viên</h6>
                <?php if (empty($students)): ?>
                    <p class="text-muted small">Chưa có sinh viên nào gửi tin nhắn hệ thống.</p>
                <?php else: ?>
                    <?php foreach ($students as $st): ?>
                        <?php 
                            $name_display = !empty($st['ho_ten']) ? $st['ho_ten'] : "Sinh viên #" . $st['sender_id'];
                            $mssv_display = !empty($st['ma_sv']) ? $st['ma_sv'] : "---";
                            $malop_display = !empty($st['ma_lop']) ? $st['ma_lop'] : "Chưa cập nhật";

                            // Logic kiểm tra chấm đỏ thông minh: Tự động biến mất khi Admin bấm vào xem hoặc đã phản hồi tin nhắn mới nhất
                            $has_unread = false;
                            if (isset($conn) && $selected_student_id != $st['sender_id']) {
                                $stmt_st_time = $conn->prepare("SELECT MAX(created_at) as max_time FROM messages WHERE sender_id = ? AND sender_role = 'student'");
                                $stmt_st_time->execute([$st['sender_id']]);
                                $st_latest = $stmt_st_time->fetch(PDO::FETCH_ASSOC);

                                $stmt_ad_time = $conn->prepare("SELECT MAX(created_at) as max_time FROM messages WHERE receiver_id = ? AND sender_role != 'student'");
                                $stmt_ad_time->execute([$st['sender_id']]);
                                $ad_latest = $stmt_ad_time->fetch(PDO::FETCH_ASSOC);

                                $st_time = $st_latest['max_time'] ?? '';
                                $ad_time = $ad_latest['max_time'] ?? '';

                                if (!empty($st_time) && (empty($ad_time) || $st_time > $ad_time)) {
                                    $has_unread = true;
                                }
                            }
                        ?>
                        <a href="?student_id=<?= $st['sender_id'] ?>" class="student-item <?= ($selected_student_id == $st['sender_id']) ? 'active' : '' ?>">
                            <i class="bi bi-person-circle fs-4 text-info"></i>
                            <div style="overflow: hidden; width: 100%;">
                                <div style="font-size: 0.92rem; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; font-weight: 700;"><?= htmlspecialchars($name_display) ?></div>
                                <div class="d-flex justify-content-between" style="font-size: 0.72rem; opacity: 0.8; margin-top: 2px;">
                                    <span>MSSV: <?= htmlspecialchars($mssv_display) ?></span>
                                    <span>Lớp: <?= htmlspecialchars($malop_display) ?></span>
                                </div>
                            </div>
                            <?php if ($has_unread): ?>
                                <span class="notification-dot" title="Có tin nhắn mới từ sinh viên"></span>
                            <?php endif; ?>
                        </a>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>

            <!-- Cột nội dung chat & Khung trả lời -->
            <div class="chat-panel">
                <div class="p-3 border-bottom border-secondary d-flex justify-content-between align-items-center" style="background: rgba(5, 255, 161, 0.1);">
                    <span class="fw-bold">Đang hội thoại với: <span class="text-warning"><?= htmlspecialchars($selected_student_name) . htmlspecialchars($selected_student_info) ?></span></span>
                    <span class="badge bg-success" style="border-radius: 20px;">Hệ thống Quản lý</span>
                </div>

                <div class="chat-messages" id="adminChatBox">
                    <?php if (empty($messages)): ?>
                        <div class="text-center text-muted my-auto">Chọn một sinh viên ở danh sách bên trái để xem nội dung trao đổi.</div>
                    <?php else: ?>
                        <?php foreach ($messages as $m): ?>
                            <?php $is_admin_msg = ($m['sender_role'] !== 'student'); ?>
                            <div class="msg-bubble <?= $is_admin_msg ? 'msg-admin' : 'msg-student' ?>">
                                <div><?= htmlspecialchars($m['message']) ?></div>
                                <div class="d-flex justify-content-between align-items-center mt-1" style="font-size: 0.7rem; opacity: 0.85;">
                                    <span><?= $is_admin_msg ? 'Phản hồi từ Admin' : htmlspecialchars($selected_student_name) ?></span>
                                    <span><?= date('H:i d/m', strtotime($m['created_at'])) ?></span>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>

                <?php if ($selected_student_id > 0): ?>
                <form method="POST" class="chat-input-area">
                    <input type="text" name="reply_message" class="chat-input" placeholder="Nhập câu trả lời gửi đến sinh viên..." required autocomplete="off">
                    <button type="submit" class="btn-send-reply">
                        <i class="bi bi-send-fill me-1"></i> Phản hồi
                    </button>
                </form>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script>
        const chatBox = document.getElementById('adminChatBox');
        if (chatBox) {
            chatBox.scrollTop = chatBox.scrollHeight;
        }
    </script>
</body>
</html>