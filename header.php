<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$current_page = basename($_SERVER['PHP_SELF']);
$isAdmin = (isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true) || 
           (isset($_SESSION['vai_tro']) && ($_SESSION['vai_tro'] === 'admin' || $_SESSION['vai_tro'] === 'quan_ly_su_kien')) ||
           (isset($_SESSION['user']) && is_array($_SESSION['user']) && isset($_SESSION['user']['role']) && $_SESSION['user']['role'] === 'admin');
$isLoggedIn = isset($_SESSION['user_id']) || isset($_SESSION['username']);

// Phân hướng trang chat tùy theo phân quyền người dùng
$chat_link = $isAdmin ? '/QUAN-LY-SU-KIEN-SINH-VIEN/admin/quan_ly_tin_nhan.php' : '/QUAN-LY-SU-KIEN-SINH-VIEN/user/chat.php';
?>

<!-- Nhúng Bootstrap Icons -->
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">

<header class="site-header">
    <div class="header-container">
        <!-- Khối Logo và Tên Website -->
        <a href="/QUAN-LY-SU-KIEN-SINH-VIEN/index.php" class="brand-area">
            <img src="./images/logo.png" alt="Logo" class="main-logo" onerror="this.style.display='none'">
            <span class="site-title">Student Event Management</span>
        </a>

        <!-- Khối Menu và Phân quyền bên phải -->
        <div class="right-header-content">
            <nav class="main-menu">
                <ul>
                    <li>
                        <a href="/QU-N-TR-D-N-C-NG-NGH-TH-NG-TIN/index.php">Trang chủ</a>
                    </li>
                    <li>
                      <a href="/QU-N-TR-D-N-C-NG-NGH-TH-NG-TIN/su-kien.php">Sự kiện</a>
                    </li>
                    <li>
                       <a href="/QU-N-TR-D-N-C-NG-NGH-TH-NG-TIN/lich-su.php">Lịch sử</a>
                    </li>
                    <li>
                      <a href="/QU-N-TR-D-N-C-NG-NGH-TH-NG-TIN/dang-ky.php">Đăng ký</a>
                    </li>
                    
                </ul>
            </nav>

            <!-- KHU VỰC CÁC NÚT: Cấu trúc xếp dọc hoàn toàn (Flex-direction: column) -->
            <div class="header-actions-vertical">
                <?php 
                // Lấy tên người dùng từ session
                $ten_nguoi_dung = $_SESSION['username'] ?? $_SESSION['ho_ten'] ?? 'Tài khoản';
                // Lấy chữ cái đầu tiên của tên (xử lý Unicode tiếng Việt chuẩn)
                $chu_cai_dau = mb_strtoupper(mb_substr(trim($ten_nguoi_dung), 0, 1, 'UTF-8'), 'UTF-8');
                ?>

                <!-- 1. Nút Đăng nhập / Tài khoản (Tích hợp Avatar Chữ cái & Tên cùng một hàng ngang) -->
                <?php if ($isLoggedIn): ?>
                    <a href="/QUAN-LY-SU-KIEN-SINH-VIEN/user/dashboard.php" class="btn-custom-3d btn-user-login btn-user-account-custom">
                        <div class="user-avatar-circle">
                            <span><?php echo $chu_cai_dau; ?></span>
                        </div>
                        <span class="user-name-text"><?php echo htmlspecialchars($ten_nguoi_dung); ?></span>
                    </a>
                <?php else: ?>
                    <a href="/QUAN-LY-SU-KIEN-SINH-VIEN/user/login.php" class="btn-custom-3d btn-user-login">👤 Đăng nhập</a>
                <?php endif; ?>

                <!-- 2. Nút Admin -->
                <?php if ($isAdmin): ?>
                    <a href="/QUAN-LY-SU-KIEN-SINH-VIEN/admin/dashboard.php" class="btn-custom-3d btn-admin-nav">⚙ Quản Trị</a>
                <?php else: ?>
                    <a href="/QUAN-LY-SU-KIEN-SINH-VIEN/admin/dashboard.php" class="btn-custom-3d btn-admin-login">⚙ Quản Trị</a>
                <?php endif; ?>

                <!-- 3. Nút Đăng xuất (Nằm ở dưới cùng khi người dùng đã đăng nhập) -->
                <?php if ($isLoggedIn): ?>
                    <a href="/QUAN-LY-SU-KIEN-SINH-VIEN/user/logout.php" class="btn-custom-3d btn-user-logout">Đăng xuất</a>
                <?php endif; ?>
            </div>

        </div>
    </div>
</header>

<!-- CSS CHUẨN XÁC 100%: TÔNG ĐỎ & XANH LỤC NEON, KHẮC PHỤC TRIỆT ĐỂ RỚT DÒNG -->
<style>
/* Nút Tin nhắn Phản hồi trên thanh Menu */
.nav-chat-btn {
    display: inline-flex !important;
    align-items: center !important;
    gap: 6px !important;
    background: linear-gradient(135deg, rgba(255, 42, 109, 0.2), rgba(5, 255, 161, 0.2)) !important;
    border: 1.5px solid rgba(5, 255, 161, 0.5) !important;
    border-radius: 50rem !important;
    padding: 6px 14px !important;
    color: #ffffff !important;
    font-weight: 700 !important;
    position: relative !important;
    transition: all 0.3s ease !important;
    box-shadow: 0 0 12px rgba(5, 255, 161, 0.25) !important;
    text-decoration: none !important;
}

.nav-chat-btn:hover, .nav-chat-btn.active {
    background: linear-gradient(135deg, rgba(255, 42, 109, 0.85), rgba(5, 255, 161, 0.45)) !important;
    border-color: #05ffa1 !important;
    box-shadow: 0 0 20px rgba(5, 255, 161, 0.7), 0 0 10px rgba(255, 42, 109, 0.5) !important;
    transform: translateY(-2px);
}

.chat-icon-neon {
    color: #05ffa1 !important;
    font-size: 15px !important;
    filter: drop-shadow(0 0 5px rgba(5, 255, 161, 0.8));
}

.chat-pulse-dot {
    width: 8px;
    height: 8px;
    background-color: #ff2a6d;
    border-radius: 50%;
    box-shadow: 0 0 8px #ff2a6d, 0 0 12px #ff2a6d;
    animation: pulseNeonDot 1.5s infinite;
}

@keyframes pulseNeonDot {
    0% { transform: scale(0.9); opacity: 1; }
    50% { transform: scale(1.3); opacity: 0.7; }
    100% { transform: scale(0.9); opacity: 1; }
}

/* Ép toàn bộ khối chứa nút xếp thành 1 cột dọc */
.header-actions-vertical {
  display: flex !important;
  flex-direction: column !important;
  align-items: stretch !important;
  gap: 8px !important;
  min-width: 170px !important;
}

/* Kiểu dáng 3D chung cho các nút */
.btn-custom-3d {
  position: relative;
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 8px 16px;
  font-family: 'Segoe UI', system-ui, -apple-system, sans-serif;
  font-size: 13px;
  font-weight: 700;
  text-decoration: none;
  text-transform: uppercase;
  letter-spacing: 0.8px;
  color: #ffffff !important;
  border: none;
  border-radius: 10px;
  cursor: pointer;
  user-select: none;
  overflow: hidden;
  transition: all 0.15s cubic-bezier(0.4, 0, 0.2, 1);
  white-space: nowrap;
  text-align: center;
}

/* Tùy chỉnh riêng nút tài khoản: Ép nằm ngang hàng tuyệt đối */
.btn-user-account-custom {
  display: flex !important;
  flex-direction: row !important;
  align-items: center !important;
  justify-content: flex-start !important;
  gap: 10px !important;
  padding: 6px 14px 6px 8px !important;
  min-width: 170px !important;
  text-transform: none !important;
}

/* Vòng tròn Avatar chứa chữ cái đầu tiên (Xanh Lục Neon) */
.user-avatar-circle {
  width: 30px !important;
  height: 30px !important;
  min-width: 30px !important;
  min-height: 30px !important;
  background: radial-gradient(circle, #05ffa1 0%, #047857 100%) !important;
  border: 1.5px solid #ffffff !important;
  border-radius: 50% !important;
  display: flex !important;
  align-items: center !important;
  justify-content: center !important;
  box-shadow: 0 0 12px rgba(5, 255, 161, 0.9) !important;
  flex-shrink: 0 !important;
  margin: 0 !important;
}

.user-avatar-circle span {
  color: #030008 !important;
  font-size: 0.9rem !important;
  font-weight: 900 !important;
  line-height: 1 !important;
  text-shadow: none !important;
}

/* Tên người dùng hiển thị gọn gàng bên cạnh avatar */
.user-name-text {
  font-size: 13.5px !important;
  font-weight: 800 !important;
  white-space: nowrap !important;
  overflow: hidden !important;
  text-overflow: ellipsis !important;
  max-width: 120px !important;
  text-align: left !important;
  line-height: 1.2 !important;
}

/* Nút Đăng nhập / Tài khoản / Admin (Màu Đỏ Hồng - Xanh Lục Neon 3D) */
.btn-user-login, .btn-admin-nav, .btn-admin-login {
  background: linear-gradient(135deg, #ff2a6d 0%, #059669 100%);
  box-shadow: 0 4px 0 #047857, 0 6px 12px rgba(5, 255, 161, 0.3), inset 0 2px 0 rgba(255, 255, 255, 0.35);
}
.btn-user-login:hover, .btn-admin-nav:hover, .btn-admin-login:hover {
  transform: translateY(-2px);
  box-shadow: 0 6px 0 #047857, 0 10px 16px rgba(5, 255, 161, 0.45), inset 0 2px 0 rgba(255, 255, 255, 0.45);
}
.btn-user-login:active, .btn-admin-nav:active, .btn-admin-login:active {
  transform: translateY(2px);
  box-shadow: 0 2px 0 #047857, 0 3px 6px rgba(5, 255, 161, 0.3), inset 0 1px 0 rgba(0, 0, 0, 0.2);
}

/* Nút Đăng xuất (Màu Đỏ tươi 3D nằm dưới cùng) */
.btn-user-logout {
  background: linear-gradient(180deg, #ff2a6d 0%, #dc2626 100%);
  box-shadow: 0 4px 0 #991b1b, 0 6px 12px rgba(255, 42, 109, 0.4), inset 0 2px 0 rgba(255, 255, 255, 0.35);
}
.btn-user-logout:hover {
  transform: translateY(-2px);
  background: linear-gradient(180deg, #ff4d85 0%, #ef4444 100%);
  box-shadow: 0 6px 0 #991b1b, 0 10px 16px rgba(255, 42, 109, 0.55), inset 0 2px 0 rgba(255, 255, 255, 0.45);
}
.btn-user-logout:active {
  transform: translateY(2px);
  box-shadow: 0 2px 0 #991b1b, 0 3px 6px rgba(220, 38, 38, 0.3), inset 0 1px 0 rgba(0, 0, 0, 0.2);
}

/* Hiệu ứng lóe sáng khi di chuột qua nút */
.btn-custom-3d::before {
  content: '';
  position: absolute;
  top: 0;
  left: -100%;
  width: 100%;
  height: 100%;
  background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.3), transparent);
  transition: left 0.6s ease;
}
.btn-custom-3d:hover::before {
  left: 100%;
}
</style>

<script>
    document.addEventListener("DOMContentLoaded", function () {
        const currentPath = window.location.pathname.split("/").pop().split("?")[0];
        const menuLinks = document.querySelectorAll(".main-menu a");

        menuLinks.forEach(link => {
            const href = link.getAttribute("href");
            if (href) {
                const linkFileName = href.split("/").pop().split("?")[0];
                if (linkFileName === currentPath) {
                    link.classList.add("active");
                }
            }
        });
    });
</script>
<script src="/QUAN-LY-SU-KIEN-SINH-VIEN/js/heder.js"></script>