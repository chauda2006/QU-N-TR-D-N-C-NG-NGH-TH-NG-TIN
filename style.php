<?php
// 1. Khai báo file này trả về định dạng CSS
header("Content-type: text/css; charset: UTF-8");

// 2. Lấy tham số trang từ URL (mặc định là 'home' nếu không có)
$page = isset($_GET['page']) ? $_GET['page'] : 'home';

// 3. Định nghĩa style di chuyển chuột (hover) cho từng trang
switch ($page) {
    case 'about':
        $hover_color = '#ff5722';      // Màu hover trang About (Đỏ cam)
        $cursor_icon = 'pointer';       // Kiểu con trỏ
        $scale_size  = '1.1';           // Phóng to nhẹ
        break;

    case 'services':
        $hover_color = '#4caf50';      // Màu hover trang Services (Xanh lá)
        $cursor_icon = 'crosshair';     // Con trỏ dạng tâm ngắm
        $scale_size  = '1.15';
        break;

    case 'contact':
        $hover_color = '#9c27b0';      // Màu hover trang Contact (Tím)
        $cursor_icon = 'help';          // Con trỏ dạng dấu hỏi
        $scale_size  = '1.05';
        break;

    case 'home':
    default:
        $hover_color = '#2196f3';      // Màu hover trang Home (Xanh dương)
        $cursor_icon = 'pointer';
        $scale_size  = '1.1';
        break;
}
?>

/* ========================================================
   CSS ĐƯỢC PHÁT SINH TỰ ĐỘNG BỞI PHP DỰA TRÊN TRANG HIỆN TẠI
   ======================================================== */

/* 1. Đổi icon con trỏ chuột trên toàn trang */
body {
    cursor: <?php echo $cursor_icon; ?>;
}

/* 2. Hiệu ứng hover cho các nút bấm (button, link, card) */
.btn-hover, 
a:hover, 
.card:hover {
    color: <?php echo $hover_color; ?> !important;
    border-color: <?php echo $hover_color; ?> !important;
    transform: translateY(-4px) scale(<?php echo $scale_size; ?>);
    transition: all 0.3s ease;
}

/* 3. Hiệu ứng bóng (Glow Effect) khi di chuột qua */
.glow-effect:hover {
    box-shadow: 0 0 15px <?php echo $hover_color; ?>;
    transition: box-shadow 0.3s ease-in-out;
}