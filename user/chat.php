<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Kiểm tra xem sinh viên/người dùng đã đăng nhập chưa
if (!isset($_SESSION['user_id']) && !isset($_SESSION['username']) && !isset($_SESSION['ma_sv'])) {
    header("Location: login.php");
    exit();
}

$current_user_id = $_SESSION['user_id'] ?? ($_SESSION['id'] ?? 1); 
$current_username = $_SESSION['username'] ?? ($_SESSION['ho_ten'] ?? 'Sinh viên');
$chu_cai_dau = mb_strtoupper(mb_substr(trim($current_username), 0, 1, 'UTF-8'), 'UTF-8');
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Hệ thống Hỗ trợ & Trò chuyện Trực tuyến</title>
    <!-- Bootstrap 5 & Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <!-- Google Font -->
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:ital,wght@0,300;0,400;0,500;0,600;0,700;0,800;1,400;1,700&display=swap" rel="stylesheet">
    
    <style>
        /* ==========================================================================
           🌌 COSMIC CHAT SYSTEM - 3D NEON RED & GREEN CINEMATIC STYLING
           ========================================================================== */
        *, *::before, *::after {
            box-sizing: border-box !important;
            font-family: 'Plus Jakarta Sans', system-ui, -apple-system, sans-serif !important;
            -webkit-font-smoothing: antialiased !important;
        }

        html, body {
            margin: 0 !important;
            padding: 0 !important;
            background-color: #030008 !important;
            color: #ffffff !important;
            overflow-x: hidden !important;
            min-height: 100vh !important;
        }

        /* Lớp nền vũ trụ động đa tầng */
        body::before {
            content: '' !important;
            position: fixed !important;
            top: 0 !important; left: 0 !important;
            width: 100vw !important; height: 100vh !important;
            background: radial-gradient(circle at 15% 20%, rgba(255, 42, 109, 0.28) 0%, transparent 48%),
                        radial-gradient(circle at 85% 80%, rgba(5, 255, 161, 0.25) 0%, transparent 48%),
                        linear-gradient(135deg, #090314 0%, #030008 100%) !important;
            z-index: -999 !important;
            pointer-events: none !important;
        }

        .chat-page-wrapper {
            max-width: 1000px;
            margin: 40px auto;
            padding: 0 15px;
        }

        /* Tiêu đề trang */
        .chat-main-title {
            color: #ffffff !important;
            text-shadow: 0 0 20px rgba(5, 255, 161, 0.6), 0 0 10px rgba(255, 42, 109, 0.5) !important;
            font-weight: 800 !important;
            letter-spacing: 0.5px;
        }

        /* Khung chat chính Glassmorphism 3D */
        .chat-container {
            background: rgba(12, 8, 32, 0.95) !important;
            backdrop-filter: blur(30px) !important;
            -webkit-backdrop-filter: blur(30px) !important;
            border: 2px solid rgba(5, 255, 161, 0.45) !important;
            border-radius: 26px !important;
            box-shadow: 0 40px 90px rgba(0, 0, 0, 0.95), 
                        0 0 50px rgba(5, 255, 161, 0.2), 
                        inset 0 0 35px rgba(255, 42, 109, 0.15) !important;
            overflow: hidden;
            display: flex;
            flex-direction: column;
            height: 75vh;
            margin-top: 20px;
        }

        /* Header khung chat */
        .chat-header {
            background: linear-gradient(135deg, rgba(255, 42, 109, 0.85) 0%, rgba(5, 255, 161, 0.35) 100%) !important;
            padding: 20px 25px;
            font-size: 1.15rem;
            font-weight: 800;
            border-bottom: 2px solid rgba(5, 255, 161, 0.5);
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 5px 20px rgba(0,0,0,0.5);
        }

        /* Vùng nội dung tin nhắn - Khắc phục hoàn toàn lỗi giật lag khi cuộn */
        .chat-box {
            flex: 1;
            padding: 25px;
            overflow-y: auto;
            display: flex;
            flex-direction: column;
            gap: 16px;
            background: rgba(5, 3, 15, 0.65);
            scroll-behavior: smooth !important;
        }

        /* Tùy chỉnh thanh cuộn */
        .chat-box::-webkit-scrollbar {
            width: 8px;
        }
        .chat-box::-webkit-scrollbar-track {
            background: rgba(5, 3, 15, 0.8);
        }
        .chat-box::-webkit-scrollbar-thumb {
            background: rgba(5, 255, 161, 0.4);
            border-radius: 10px;
        }
        .chat-box::-webkit-scrollbar-thumb:hover {
            background: rgba(5, 255, 161, 0.8);
        }

        /* Bong bóng tin nhắn */
        .message-item {
            max-width: 70%;
            padding: 14px 18px;
            border-radius: 18px;
            font-size: 0.98rem;
            font-weight: 600;
            word-break: break-word;
            line-height: 1.5;
            position: relative;
            animation: fadeInMsg 0.3s ease-out;
        }

        @keyframes fadeInMsg {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }

        /* Tin nhắn gửi đi (Sinh viên) - Nằm bên phải (Đỏ Hồng Neon) */
        .message-item.sent {
            background: linear-gradient(135deg, #ff2a6d 0%, #be123c 100%) !important;
            color: #ffffff !important;
            align-self: flex-end;
            border-bottom-right-radius: 4px;
            box-shadow: 0 10px 25px rgba(255, 42, 109, 0.4), inset 0 2px 0 rgba(255,255,255,0.3);
        }

        /* Tin nhắn nhận về (Quản lý / Admin) - Nằm bên trái (Xanh Lục Neon) */
        .message-item.received {
            background: linear-gradient(135deg, rgba(5, 255, 161, 0.85) 0%, rgba(4, 120, 87, 0.9) 100%) !important;
            color: #030008 !important;
            align-self: flex-start;
            border-bottom-left-radius: 4px;
            box-shadow: 0 10px 25px rgba(5, 255, 161, 0.35), inset 0 2px 0 rgba(255,255,255,0.4);
            font-weight: 700;
        }

        .message-item.received .msg-time-label {
            color: rgba(3, 0, 8, 0.75) !important;
        }

        .message-item.sent .msg-time-label {
            color: rgba(255, 255, 255, 0.8) !important;
        }

        /* Khu vực nhập liệu khung chat */
        .chat-input-area {
            padding: 18px 25px;
            background: rgba(12, 8, 32, 0.98);
            border-top: 2px solid rgba(5, 255, 161, 0.35);
            display: flex;
            gap: 15px;
            align-items: center;
        }

        .chat-input {
            flex: 1;
            background: rgba(8, 5, 22, 0.98) !important;
            border: 2px solid rgba(5, 255, 161, 0.45) !important;
            color: #ffffff !important;
            border-radius: 14px !important;
            padding: 14px 20px !important;
            font-weight: 600 !important;
            outline: none !important;
            box-shadow: inset 0 2px 8px rgba(0,0,0,0.8) !important;
            transition: all 0.3s ease !important;
        }

        .chat-input:focus {
            border-color: #05ffa1 !important;
            box-shadow: 0 0 30px rgba(5, 255, 161, 0.7), inset 0 0 10px rgba(5, 255, 161, 0.3) !important;
        }

        /* Nút gửi tin nhắn 3D Neon */
        .btn-send-neon {
            background: linear-gradient(135deg, #05ffa1 0%, #047857 100%) !important;
            border: none !important;
            color: #030008 !important;
            font-weight: 900 !important;
            padding: 14px 28px !important;
            border-radius: 14px !important;
            box-shadow: 0 8px 25px rgba(5, 255, 161, 0.5) !important;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1) !important;
            cursor: pointer;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .btn-send-neon:hover {
            transform: translateY(-3px) scale(1.02);
            box-shadow: 0 12px 35px rgba(5, 255, 161, 0.9) !important;
            background: linear-gradient(135deg, #4ade80 0%, #059669 100%) !important;
        }

        .btn-send-neon:active {
            transform: translateY(-1px) scale(0.99);
        }

        /* Nút quay lại dashboard chuẩn 3D */
        .btn-back-dash {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            background: linear-gradient(135deg, rgba(255, 42, 109, 0.2), rgba(5, 255, 161, 0.2));
            border: 1.5px solid rgba(5, 255, 161, 0.5);
            border-radius: 12px;
            padding: 8px 18px;
            color: #ffffff !important;
            font-weight: 700;
            text-decoration: none;
            transition: 0.3s ease;
        }
        .btn-back-dash:hover {
            background: linear-gradient(135deg, rgba(255, 42, 109, 0.8), rgba(5, 255, 161, 0.4));
            border-color: #05ffa1;
            box-shadow: 0 0 20px rgba(5, 255, 161, 0.5);
            transform: translateY(-2px);
        }
    </style>
</head>
<body>

    <div class="chat-page-wrapper">
        <!-- Header tiêu đề & Nút quay lại -->
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h3 class="chat-main-title text-uppercase m-0">
                <i class="bi bi-chat-heart-fill me-2 text-danger"></i> Trò chuyện & Hỗ trợ trực tuyến
            </h3>
            <a href="../su-kien.php" class="btn-back-dash">
                <i class="bi bi-arrow-left"></i> Quay lại Dashboard
            </a>
        </div>

        <!-- Khung chat chính -->
        <div class="chat-container">
            <!-- Tiêu đề hộp chat -->
            <div class="chat-header">
                <div class="d-flex align-items-center gap-3">
                    <div style="width: 36px; height: 36px; background: radial-gradient(circle, #05ffa1 0%, #047857 100%); border-radius: 50%; display: flex; align-items: center; justify-content: center; font-weight: 900; color: #030008; border: 1.5px solid #fff;">
                        <?php echo $chu_cai_dau; ?>
                    </div>
                    <div>
                        <span class="d-block" style="font-size: 0.95rem; opacity: 0.85;">Kênh trao đổi của: <strong><?php echo htmlspecialchars($current_username); ?></strong></span>
                        <span class="text-warning fw-bold" style="font-size: 0.85rem;"><i class="bi bi-shield-check me-1"></i> Kết nối trực tiếp với Quản lý Sự kiện & Admin</span>
                    </div>
                </div>
                <span class="badge bg-success px-3 py-2" style="border-radius: 20px; font-weight: 700; box-shadow: 0 0 12px rgba(5,255,161,0.6);">
                    <i class="bi bi-circle-fill text-white me-1" style="font-size: 8px;"></i> Trực tuyến
                </span>
            </div>

            <!-- Vùng hiển thị danh sách tin nhắn (Load qua AJAX) -->
            <div class="chat-box" id="chatBox">
                <!-- Nội dung chat tự động cập nhật tại đây -->
            </div>

            <!-- Khu vực nhập tin nhắn -->
            <div class="chat-input-area">
                <input type="text" id="messageInput" class="chat-input" placeholder="Nhập nội dung cần hỗ trợ hoặc phản hồi tới Quản lý / Admin..." autocomplete="off">
                <button type="button" id="sendBtn" class="btn-send-neon">
                    <i class="bi bi-send-fill me-1"></i> Gửi đi
                </button>
            </div>
        </div>
    </div>

    <!-- Thư viện jQuery hỗ trợ AJAX mượt mà -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script>
        const currentUserId = <?php echo $current_user_id; ?>;
        let isUserScrolling = false;
        let lastHtmlContent = ""; // Biến lưu trạng thái nội dung cũ để tránh làm mới lại DOM gây chớp màn hình

        // Theo dõi hành động cuộn chuột của người dùng
        $('#chatBox').on('scroll', function() {
            const chatBox = this;
            if (chatBox.scrollHeight - chatBox.scrollTop - chatBox.clientHeight > 80) {
                isUserScrolling = true;
            } else {
                isUserScrolling = false;
            }
        });

        // Hàm tải tin nhắn từ server một cách thông minh (chỉ cập nhật DOM khi có tin nhắn thay đổi)
        function loadMessages(forceScroll = false) {
            $.ajax({
                url: 'fetch_messages.php',
                method: 'GET',
                success: function(data) {
                    // Kiểm tra nếu nội dung có thay đổi mới render lại để triệt tiêu hoàn toàn hiện tượng giật hình
                    if (data !== lastHtmlContent) {
                        lastHtmlContent = data;
                        $('#chatBox').html(data);
                        
                        if (forceScroll || !isUserScrolling) {
                            const chatBox = document.getElementById('chatBox');
                            chatBox.scrollTo({
                                top: chatBox.scrollHeight,
                                behavior: 'smooth'
                            });
                        }
                    }
                }
            });
        }

        // Sự kiện click nút gửi
        $('#sendBtn').click(function() {
            sendMessage();
        });

        // Sự kiện nhấn phím Enter để gửi nhanh
        $('#messageInput').keypress(function(e) {
            if (e.which === 13) {
                sendMessage();
            }
        });

        // Hàm thực thi gửi tin nhắn bằng AJAX POST
        function sendMessage() {
            const message = $('#messageInput').val().trim();
            if (message === '') return;

            $('#messageInput').prop('disabled', true);
            $('#sendBtn').prop('disabled', true);

            $.ajax({
                url: 'send_message.php',
                method: 'POST',
                data: { message: message },
                success: function(response) {
                    $('#messageInput').val('').prop('disabled', false).focus();
                    $('#sendBtn').prop('disabled', false);
                    isUserScrolling = false; 
                    loadMessages(true); // Ép cuộn mượt xuống đáy sau khi gửi tin nhắn thành công
                },
                error: function() {
                    $('#messageInput').prop('disabled', false);
                    $('#sendBtn').prop('disabled', false);
                    alert('Lỗi kết nối khi gửi tin nhắn. Vui lòng thử lại!');
                }
            });
        }

        // Tự động làm mới tin nhắn mỗi 2 giây mà không bị chớp màn hình
        setInterval(function() {
            loadMessages(false);
        }, 2000);
        
        // Load ngay lập tức khi vừa mở trang và ép cuộn xuống đáy
        loadMessages(true);
    </script>
</body>
</html>