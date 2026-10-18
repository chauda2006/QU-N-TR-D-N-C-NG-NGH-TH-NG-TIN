<?php
// Nhúng file kết nối cơ sở dữ liệu (nếu cần)
require_once 'database.php';

// Nhúng thanh header dùng chung
include_once 'header.php';
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Trang Sự Kiện</title>
    <!-- Nhúng CSS header và CSS giao diện trang index/sự kiện -->
    <link rel="stylesheet" href="css/header.css">
    <link rel="stylesheet" href="css/index.css">
</head>
<body>

    <main class="container my-4">
        <h1 class="text-center mb-4">DANH SÁCH SỰ KIỆN</h1>
        
        <!-- Khung chứa danh sách các sự kiện -->
        <div id="event-list" class="row">
            <p class="text-center">Đang tải danh sách sự kiện...</p>
        </div>
    </main>

    <!-- JavaScript lấy dữ liệu từ API sự kiện và hiển thị -->
    <script>
        document.addEventListener("DOMContentLoaded", function () {
            // Gọi API lấy danh sách sự kiện
            fetch('api_su_kien.php')
                .then(response => response.json())
                .then(data => {
                    const eventListDiv = document.getElementById('event-list');
                    eventListDiv.innerHTML = ''; // Xóa dòng chữ đang tải

                    if (data.length === 0) {
                        eventListDiv.innerHTML = '<p class="text-center">Hiện chưa có sự kiện nào.</p>';
                        return;
                    }

                    // Duyệt qua danh sách sự kiện và đổ dữ liệu ra HTML
                    data.forEach(event => {
                        const eventCard = `
                            <div class="col-md-4 mb-4">
                                <div class="card h-100 shadow-sm">
                                    <img src="${event.hinh_anh || 'images/default.jpg'}" class="card-img-top" alt="${event.ten_su_kien}">
                                    <div class="card-body">
                                        <h5 class="card-title">${event.ten_su_kien}</h5>
                                        <p class="card-text">${event.mo_ta || 'Không có mô tả.'}</p>
                                        <p class="text-muted"><small>Ngày diễn ra: ${event.ngay_to_chuc || 'Chưa cập nhật'}</small></p>
                                    </div>
                                </div>
                            </div>
                        `;
                        eventListDiv.innerHTML += eventCard;
                    });
                })
                .catch(error => {
                    console.error('Lỗi khi tải dữ liệu sự kiện:', error);
                    document.getElementById('event-list').innerHTML = '<p class="text-center text-danger">Không thể tải dữ liệu sự kiện.</p>';
                });
        });
    </script>
</body>
</html>