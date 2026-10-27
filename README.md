# ĐỒ ÁN CUỐI KỲ: QUẢN LÝ SỰ KIỆN SINH VIÊN (DA03)
**Học phần:** Điện toán đám mây - Nhóm 03

### 1. Tên đề tài và thành viên
- Đề tài: Quản lý sự kiện sinh viên (DA03)
- Thành viên nhóm:
  1. Châu Tiến Đạt (Nhóm trưởng)
  2. Võ Khánh Băng
  3. Nguyễn Thanh Nhàn
  4. Trần Vĩnh Thịnh
  5. Đặng Công Danh

### 2. Công nghệ sử dụng
- Ngôn ngữ & Giao diện: PHP 8.2, Apache, Bootstrap, HTML, CSS, JavaScript
- Cơ sở dữ liệu: MySQL 8.0
- Lưu trữ tệp: MinIO Object Storage
- Triển khai ảo hóa: Docker Desktop, Docker Compose

### 3. Yêu cầu môi trường
- Docker Desktop hoặc Docker Engines
- Docker Compose v2 trở lên

### 4. Hướng dẫn tạo file .env từ .env.example
- Chạy lệnh sao chép:
  `cp .env.example .env`
- Điều chỉnh lại các tham số mật khẩu hoặc cổng nếu cần thiết.

### 5. Lệnh chạy hệ thống
`docker compose up -d --build`

### 6. URL truy cập giao diện web
- Web App: http://localhost:8080

### 7. URL API / Hướng dẫn dùng Postman
- Mở Postman, import file JSON collection nằm trong thư mục `postman/`.

### 8. URL MinIO Console
- MinIO Console: http://localhost:9000
- Tài khoản: Xem các giá trị `MINIO_ROOT_USER` và `MINIO_ROOT_PASSWORD` trong file `.env`.

### 9. Tài khoản demo
- Admin: admin
- Sinh viên: 123456

### 10. Lệnh dừng hệ thống
`docker compose down`

### 11. Lệnh xóa toàn bộ dữ liệu demo
`docker compose down -v`