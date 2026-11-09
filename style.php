<?php
// Bắt buộc khai báo header CSS để trình duyệt hiểu đây là mã CSS
header("Content-Type: text/css; charset=UTF-8");

// Khai báo biến màu sắc qua PHP
$primary_gradient  = "linear-gradient(135deg, #e11d48 0%, #059669 100%)";
$logout_gradient   = "linear-gradient(180deg, #ff4b4b 0%, #dc2626 100%)";
$logout_hover_grad = "linear-gradient(180deg, #ff5c5c 0%, #ef4444 100%)";
$menu_active_color = "#059669";
$accent_border     = "#e11d48";
?>

/* ==========================================================
   1. BỐ CỤC KHỐI NÚT VÀ NÚT BẤM 3D (HEADER ACTIONS)
   ========================================================== */
.header-actions-vertical {
  display: flex !important;
  flex-direction: column !important;
  align-items: stretch !important;
  gap: 8px !important;
  min-width: 140px;
}

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
  transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
  white-space: nowrap;
  text-align: center;
}

/* Nút Đăng nhập / Admin */
.btn-user-login, 
.btn-admin-nav, 
.btn-admin-login {
  background: <?= $primary_gradient ?>;
  box-shadow: 0 4px 0 #047857, 0 6px 12px rgba(5, 150, 105, 0.3), inset 0 2px 0 rgba(255, 255, 255, 0.35);
}

.btn-user-login:hover, 
.btn-admin-nav:hover, 
.btn-admin-login:hover {
  transform: translateY(-3px) scale(1.02);
  box-shadow: 0 7px 0 #047857, 0 12px 20px rgba(5, 150, 105, 0.45), inset 0 2px 0 rgba(255, 255, 255, 0.45);
}

.btn-user-login:active, 
.btn-admin-nav:active, 
.btn-admin-login:active {
  transform: translateY(2px) scale(0.98);
  box-shadow: 0 2px 0 #047857, 0 3px 6px rgba(5, 150, 105, 0.3), inset 0 1px 0 rgba(0, 0, 0, 0.2);
}

/* Nút Đăng xuất */
.btn-user-logout {
  background: <?= $logout_gradient ?>;
  box-shadow: 0 4px 0 #991b1b, 0 6px 12px rgba(220, 38, 38, 0.4), inset 0 2px 0 rgba(255, 255, 255, 0.35);
}

.btn-user-logout:hover {
  transform: translateY(-3px) scale(1.02);
  background: <?= $logout_hover_grad ?>;
  box-shadow: 0 7px 0 #991b1b, 0 12px 20px rgba(239, 68, 68, 0.5), inset 0 2px 0 rgba(255, 255, 255, 0.45);
}

.btn-user-logout:active {
  transform: translateY(2px) scale(0.98);
  box-shadow: 0 2px 0 #991b1b, 0 3px 6px rgba(220, 38, 38, 0.3), inset 0 1px 0 rgba(0, 0, 0, 0.2);
}

/* Hiệu ứng ánh sáng lướt qua khi hover */
.btn-custom-3d::before {
  content: '';
  position: absolute;
  top: 0;
  left: -100%;
  width: 100%;
  height: 100%;
  background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.4), transparent);
  transition: left 0.5s ease-in-out;
}

.btn-custom-3d:hover::before {
  left: 100%;
}


/* ==========================================================
   2. HIỆU ỨNG GẠCH CHÂN MENU TRƯỢT
   ========================================================== */
.main-menu a {
  position: relative;
  text-decoration: none;
  transition: color 0.3s ease;
  display: inline-block;
}

.main-menu a::after {
  content: '';
  position: absolute;
  bottom: -4px;
  left: 0;
  width: 0%;
  height: 3px;
  background: <?= $primary_gradient ?>;
  border-radius: 4px;
  transition: width 0.3s ease-in-out;
}

.main-menu a:hover,
.main-menu a.active {
  color: <?= $menu_active_color ?>;
}

.main-menu a:hover::after,
.main-menu a.active::after {
  width: 100%;
}


/* ==========================================================
   3. VÒNG TRÒN CON TRỎ CHUỘT (CURSOR TRAIL)
   ========================================================== */
.cursor-follower {
  position: fixed;
  top: 0;
  left: 0;
  width: 20px;
  height: 20px;
  background-color: rgba(5, 150, 105, 0.35);
  border: 1.5px solid <?= $accent_border ?>;
  border-radius: 50%;
  pointer-events: none;
  transform: translate(-50%, -50%);
  transition: transform 0.08s ease-out, width 0.2s ease, height 0.2s ease, background-color 0.2s ease;
  z-index: 99999;
  box-shadow: 0 0 10px rgba(225, 29, 72, 0.3);
}

.cursor-follower.active {
  width: 38px;
  height: 38px;
  background-color: rgba(225, 29, 72, 0.2);
  border-color: <?= $menu_active_color ?>;
  box-shadow: 0 0 15px rgba(5, 150, 105, 0.5);
}