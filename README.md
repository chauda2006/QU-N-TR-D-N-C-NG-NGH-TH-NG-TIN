# SEM – Website Quản lý Sự kiện Sinh viên BDU

> **Student Event Management (SEM)** – hệ thống web hỗ trợ sinh viên đăng ký sự kiện, Ban tổ chức quản lý sự kiện và điểm danh bằng **Passcode** có thời hạn, tự động ghi nhận **điểm rèn luyện (ĐRL)**.
>
> Đồ án môn **Quản trị dự án công nghệ thông tin** – Nhóm 6 – Trường Đại học Bình Dương, Phân hiệu Cà Mau.
> Giảng viên hướng dẫn: ThS. Lê Thanh Thoại.

---

## Mục lục

1. [Giới thiệu](#1-giới-thiệu)
2. [Chức năng chính](#2-chức-năng-chính)
3. [Công nghệ sử dụng](#3-công-nghệ-sử-dụng)
4. [Yêu cầu hệ thống](#4-yêu-cầu-hệ-thống)
5. [Cấu trúc thư mục](#5-cấu-trúc-thư-mục)
6. [Hướng dẫn cài đặt trên XAMPP](#6-hướng-dẫn-cài-đặt-trên-xampp)
7. [Cấu hình](#7-cấu-hình)
8. [Hướng dẫn sử dụng](#8-hướng-dẫn-sử-dụng)
9. [Cơ sở dữ liệu](#9-cơ-sở-dữ-liệu)
10. [Kiểm thử](#10-kiểm-thử)
11. [Bảo mật](#11-bảo-mật)
12. [Sao lưu và khôi phục dữ liệu](#12-sao-lưu-và-khôi-phục-dữ-liệu)
13. [Xử lý sự cố thường gặp](#13-xử-lý-sự-cố-thường-gặp)
14. [Hạn chế đã biết và hướng phát triển](#14-hạn-chế-đã-biết-và-hướng-phát-triển)
15. [Quy ước làm việc với Git](#15-quy-ước-làm-việc-với-git)
16. [Thành viên nhóm](#16-thành-viên-nhóm)
17. [Hồ sơ đi kèm](#17-hồ-sơ-đi-kèm)

---

## 1. Giới thiệu

Tại Phân hiệu Cà Mau, mỗi năm có khoảng 15–20 sự kiện, mỗi sự kiện thu hút 150–300 sinh viên. Việc thông báo, đăng ký, điểm danh và tổng hợp ĐRL hiện làm thủ công (nhóm trò chuyện, biểu mẫu, Excel), dẫn đến:

- Thông báo dễ bị trôi tin, đăng ký sai hoặc trùng MSSV.
- Điểm danh bằng ảnh chụp gây ùn tắc 15–30 phút.
- Đối chiếu danh sách và tổng hợp điểm rèn luyện mất thời gian, dễ nhầm lẫn.

**SEM** tập trung hóa toàn bộ quy trình trên một website chạy bằng **XAMPP** (Apache + MySQL + PHP), không cần Docker và không phát sinh chi phí phần mềm.

> **Lưu ý:** Các chỉ số lợi ích nêu trong hồ sơ (giảm khoảng 90% thời gian chờ, giảm 100% thao tác tổng hợp thủ công, điểm danh dưới 3 giây) là **giá trị kỳ vọng**, chưa được đo trong sự kiện thực tế.

## 2. Chức năng chính

| Nhóm | Chức năng |
|---|---|
| **Tài khoản** | Đăng ký tài khoản, đăng nhập/đăng xuất, hồ sơ sinh viên, hết phiên sau 30 phút không hoạt động |
| **Phân quyền** | Hai vai trò: **Sinh viên** và **Admin**; trang trái quyền bị chặn |
| **Sinh viên** | Xem và tìm kiếm sự kiện, đăng ký/hủy đăng ký, xem lịch sử tham gia, nhập Passcode điểm danh, xem ĐRL |
| **Admin** | Dashboard thống kê, CRUD địa điểm, CRUD sự kiện, tải ảnh poster, xem và duyệt danh sách đăng ký |
| **Điểm danh** | Admin bật/tắt Passcode ngẫu nhiên 6 số, hiệu lực 5 phút; sinh viên nhập mã để điểm danh; tự cộng ĐRL, chống cộng điểm lặp |
| **Ràng buộc** | Khóa đăng ký khi đủ chỗ; chống đăng ký trùng; chặn xóa dữ liệu đang được tham chiếu |

## 3. Công nghệ sử dụng

| Thành phần | Công nghệ |
|---|---|
| Máy chủ web | Apache (trong XAMPP) |
| Ngôn ngữ phía máy chủ | PHP, truy cập dữ liệu bằng **PDO** (prepared statement) |
| Cơ sở dữ liệu | MySQL / MariaDB, bộ ký tự `utf8mb4_unicode_ci` |
| Giao diện | HTML, CSS, JavaScript, **Bootstrap 5** (Responsive) |
| Quản lý mã nguồn | Git, GitHub |
| Lập lịch dự án | Microsoft Project |

## 4. Yêu cầu hệ thống

| Thành phần | Yêu cầu |
|---|---|
| Hệ điều hành | Windows 10/11 hoặc tương đương |
| XAMPP | Bản có Apache, MySQL/MariaDB và PHP kèm theo (PDO MySQL đã bật) |
| Trình duyệt | Chrome, Edge hoặc Firefox bản mới |
| Phần cứng | RAM từ 4GB, trống ít nhất 1GB ổ đĩa |
| Cổng mạng | Cổng 80 (Apache) và 3306 (MySQL) chưa bị chiếm |

> Phiên bản PHP và MySQL/MariaDB là phiên bản đi kèm gói XAMPP nhóm đã dùng để kiểm thử: **<điền số phiên bản>**.

## 5. Cấu trúc thư mục

```text
sem/                              Thư mục dự án (đặt trong htdocs; tên thư mục gốc trên máy có thể khác)
├── admin/                        Khu vực quản trị viên
│   ├── check_admin.php           Kiểm tra phiên và quyền Admin
│   ├── chi_tiet_diem_danh.php    Chi tiết danh sách điểm danh theo sự kiện
│   ├── dashboard.php             Bảng điều khiển quản trị
│   ├── login.php                 Đăng nhập Admin
│   └── logout.php                Đăng xuất Admin
├── user/                         Khu vực sinh viên / quản lý sự kiện
│   ├── banners.php               Banner giao diện
│   ├── check_user.php            Kiểm tra phiên và quyền người dùng
│   ├── dashboard.php             Trang tài khoản / bảng điều khiển người dùng
│   ├── delete.php                Xóa dữ liệu (sự kiện)
│   ├── diem-ren-luyen.php        Xem điểm rèn luyện
│   ├── login.php                 Đăng nhập người dùng
│   ├── logout.php                Đăng xuất người dùng
│   ├── quan_ly_su_kien.php       Quản lý sự kiện
│   ├── register.php              Đăng ký tài khoản
│   ├── thong_ke_dang_ky.php      Thống kê đăng ký
│   └── xuly_sua.php              Xử lý sửa sự kiện
├── css/                          Tệp giao diện (CSS)
├── js/
│   ├── heder.js                  Script phần đầu trang
│   └── su-kien.js                Script trang sự kiện
├── images/                       Hình ảnh giao diện
├── videos/                       Tệp video
├── uploads/                      Ảnh poster sự kiện do người dùng tải lên
├── api_checkin.php               API điểm danh bằng Passcode
├── api_dia_diem.php              API địa điểm
├── api_su_kien.php               API sự kiện
├── api_toggle_attendance.php     API bật/tắt điểm danh (sinh và đóng Passcode)
├── attendance_date.php           Xử lý thời gian điểm danh
├── dang-ky.php                   Đăng ký sự kiện
├── database.php                  Kết nối PDO, múi giờ và bộ ký tự dùng chung
├── do_an_cuoi_ky.sql             Tập tin tạo CSDL và nạp dữ liệu mẫu
├── header.php                    Phần đầu trang dùng chung
├── index.php                     Trang chủ
├── lich-su.php                   Lịch sử sự kiện cá nhân của sinh viên
├── style.php                     Khai báo kiểu giao diện dùng chung
├── su-kien.php                   Danh sách sự kiện
├── tiêu đề.php                   Tệp tiêu đề trang
└── README.md                     Tài liệu này
```

> **Lưu ý:** Mô tả từng tệp được suy ra từ tên tệp; chỉnh lại nếu chức năng thực tế khác. Tên tệp `heder.js` và `tiêu đề.php` giữ nguyên như trong mã nguồn (tên `tiêu đề.php` có dấu tiếng Việt và khoảng trắng, nên đổi sang tên không dấu như `tieu-de.php` nếu gặp lỗi khi chuyển giữa các hệ điều hành).

## 6. Hướng dẫn cài đặt trên XAMPP

**Bước 1 – Cài XAMPP.** Tải từ trang chủ Apache Friends (<https://www.apachefriends.org/>), cài đặt, mở **XAMPP Control Panel** và bấm **Start** cho hai dịch vụ **Apache** và **MySQL**.

**Bước 2 – Chép mã nguồn.** Chép thư mục dự án vào thư mục `htdocs` của XAMPP, ví dụ:

```text
C:\xampp\htdocs\sem
```

**Bước 3 – Tạo cơ sở dữ liệu và nhập dữ liệu.**

1. Mở trình duyệt, vào `http://localhost/phpmyadmin`.
2. Chọn **New** (Mới) → đặt tên CSDL, ví dụ `do_an_cuoi_ky` → bộ ký tự **`utf8mb4_unicode_ci`** → **Create**.
3. Chọn CSDL vừa tạo → tab **Import** → chọn tệp `do_an_cuoi_ky.sql` → **Go**.
4. Kiểm tra CSDL có đủ **5 bảng** và dữ liệu mẫu.

**Bước 4 – Cấu hình kết nối.** Mở `database.php`, cập nhật tên CSDL, tài khoản, mật khẩu (xem [mục 7](#7-cấu-hình)).

**Bước 5 – Cấp quyền ghi cho thư mục `uploads/`** để lưu poster (trên Windows, bảo đảm tài khoản đang chạy Apache có quyền ghi).

**Bước 6 – Truy cập hệ thống.**

- Trang chủ và khu vực sinh viên: `http://localhost/sem/` (hoặc `http://localhost/sem/user/login.php` để đăng nhập).
- Khu vực quản trị: `http://localhost/sem/admin/login.php`.

Đăng nhập bằng tài khoản mẫu (xem [mục 8](#8-hướng-dẫn-sử-dụng)).

## 7. Cấu hình

Các cấu hình dùng chung nằm trong `database.php`:

| Thông số | Giá trị khuyến nghị | Ghi chú |
|---|---|---|
| Host | `localhost` | |
| Tên CSDL | `do_an_cuoi_ky` (hoặc tên bạn đã tạo) | Phải khớp với CSDL đã nhập |
| Tài khoản / mật khẩu | `root` / (rỗng) theo mặc định XAMPP | Đổi nếu đã đặt mật khẩu |
| Bộ ký tự | `utf8mb4` | Hiển thị đúng tiếng Việt có dấu |
| Múi giờ | `Asia/Ho_Chi_Minh` | Bắt buộc để mốc hết hạn Passcode 5 phút chính xác |
| Chế độ lỗi PDO | `ERRMODE_EXCEPTION` | |

Ví dụ minh họa nguyên lý (tên biến có thể khác trong mã thực tế):

```php
date_default_timezone_set('Asia/Ho_Chi_Minh');
$pdo = new PDO('mysql:host=localhost;dbname=do_an_cuoi_ky;charset=utf8mb4', $user, $pass);
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
```

## 8. Hướng dẫn sử dụng

### 8.1. Tài khoản mẫu

| Vai trò | Tên đăng nhập | Mật khẩu |
|---|---|---|
| Admin | `<điền>` | `<điền>` |
| Sinh viên | `<điền>` | `<điền>` |

> Tài khoản và dữ liệu mẫu chỉ phục vụ kiểm thử. **Đổi mật khẩu** trước khi dùng thật.

### 8.2. Dành cho Sinh viên

1. Đăng ký tài khoản (nếu chưa có) và đăng nhập.
2. Vào **Danh sách sự kiện**, dùng ô tìm kiếm để lọc theo từ khóa.
3. Chọn sự kiện → **Đăng ký** (chọn chỗ ngồi nếu có). Sự kiện đủ chỗ sẽ bị khóa đăng ký.
4. Xem **Lịch sử** (`lich-su.php`) để theo dõi trạng thái; có thể **Hủy đăng ký**.
5. Tại sự kiện, khi Admin mở điểm danh, nhập **Passcode 6 số** trong vòng 5 phút.
6. Xem **điểm rèn luyện** tích lũy trong trang tài khoản.

### 8.3. Dành cho Quản trị viên (Admin)

1. Đăng nhập tài khoản Admin → vào **Dashboard** xem thống kê tổng.
2. **Quản lý địa điểm:** thêm, sửa, xóa (địa điểm còn sự kiện tham chiếu sẽ không xóa được).
3. **Quản lý sự kiện:** thêm sự kiện (nhập thông tin, chọn địa điểm, sức chứa, điểm cộng, tải **poster** JPG/PNG không quá 5MB), sửa, xóa, tìm kiếm.
4. **Duyệt đăng ký:** xem danh sách sinh viên đã đăng ký từng sự kiện.
5. **Điểm danh:** bấm **Mở điểm danh** để sinh Passcode, đọc mã cho sinh viên; hết 5 phút mã tự vô hiệu, hoặc bấm **Đóng điểm danh** để tắt sớm. Xem danh sách sinh viên **Đã điểm danh**.

### 8.4. Quy tắc điểm danh

- Mã gồm 6 chữ số ngẫu nhiên, hiệu lực **5 phút** kể từ lúc tạo.
- Chỉ sinh viên **đã đăng ký** sự kiện mới điểm danh được.
- Mỗi sinh viên chỉ được cộng điểm **một lần** cho mỗi sự kiện (bấm F5 hay gửi lặp không cộng thêm).

## 9. Cơ sở dữ liệu

Cơ sở dữ liệu gồm **5 bảng**:

| Bảng | Vai trò |
|---|---|
| `users` | Tài khoản, vai trò, điểm rèn luyện tích lũy |
| `dia_diem` | Hội trường, phòng học và sức chứa tối đa |
| `su_kien` | Thông tin sự kiện, sức chứa, điểm cộng, poster, Passcode và thời điểm hết hạn |
| `dang_ky` | Đăng ký của sinh viên; cờ `is_checked_in`; ràng buộc duy nhất `(user_id, su_kien_id)` |
| `diem_ren_luyen` | Nhật ký cộng điểm theo sự kiện |

Quan hệ chính: một địa điểm có nhiều sự kiện; một sinh viên và một sự kiện có nhiều đăng ký. Các khóa ngoại dùng chế độ hạn chế xóa (RESTRICT).

> Tên bảng, tên cột nêu trên theo thiết kế logic trong báo cáo; đối chiếu với `do_an_cuoi_ky.sql` thực tế nếu có khác biệt.

## 10. Kiểm thử

Bộ kiểm thử gồm **12 ca (TC-01 đến TC-12)**, trong đó TC-09 và TC-10 được chia thành các tình huống nhỏ:

| Nhóm | Nội dung |
|---|---|
| TC-01, TC-02 | Đăng ký, đăng nhập đúng/sai |
| TC-03 đến TC-05 | CRUD địa điểm, sự kiện, poster, tìm kiếm |
| TC-06 đến TC-08 | Đăng ký, đủ chỗ/trùng, hủy đăng ký |
| TC-09 | Passcode: đúng, sai, hết hạn, gửi lặp, chưa đăng ký |
| TC-10 | Bảo mật: tiêm mã SQL, hết phiên 30 phút, truy cập trái quyền, tải tệp độc hại |
| TC-11 | Độ bền dữ liệu khi khởi động lại Apache/MySQL |
| TC-12 | Hiệu năng (≤ 3 giây) và giao diện Responsive |

Chi tiết và trạng thái từng ca nằm trong tệp `Nhom6_TestCase_SEM.xlsx` và Chương 8 của báo cáo.

## 11. Bảo mật

| Mối đe dọa | Biện pháp |
|---|---|
| Truy cập trái quyền | `admin/check_admin.php` và `user/check_user.php` kiểm tra phiên và vai trò ở các trang tương ứng |
| Tiêm mã SQL | PDO với prepared statement, không nối chuỗi vào truy vấn |
| Quản lý phiên | Hết phiên sau 30 phút không hoạt động; mật khẩu lưu dạng băm |
| Tải tệp độc hại | Lọc MIME, giới hạn 5MB, đổi tên tệp bằng `time()` kèm `uniqid()` |
| Gian lận điểm danh | Passcode hết hạn sau 5 phút; kiểm tra `is_checked_in` trong một Transaction |

> Hệ thống thiết kế để chạy **cục bộ**. Nếu đưa lên Internet, cần bổ sung HTTPS, đổi mật khẩu CSDL, tắt hiển thị lỗi PHP và rà soát lại bảo mật.

## 12. Sao lưu và khôi phục dữ liệu

**Sao lưu:** phpMyAdmin → chọn CSDL → tab **Export** → định dạng SQL → **Go**. Sao lưu thêm thư mục `uploads/`.

**Khôi phục:** tạo CSDL trống (`utf8mb4_unicode_ci`) → tab **Import** → chọn tệp `.sql` đã sao lưu. Chép lại thư mục `uploads/`.

Khuyến nghị sao lưu sau mỗi sự kiện và trước khi cập nhật mã nguồn.

## 13. Xử lý sự cố thường gặp

| Hiện tượng | Nguyên nhân thường gặp | Cách xử lý |
|---|---|---|
| Apache không khởi động | Cổng 80 bị chiếm (Skype, IIS, ứng dụng khác) | Tắt ứng dụng chiếm cổng hoặc đổi cổng Apache trong XAMPP |
| MySQL không khởi động | Cổng 3306 bị chiếm hoặc đang có MySQL khác chạy | Dừng dịch vụ MySQL khác hoặc đổi cổng và cập nhật `database.php` |
| Lỗi "Access denied" / không kết nối được CSDL | Sai tài khoản, mật khẩu hoặc tên CSDL | Kiểm tra lại `database.php` |
| Tiếng Việt bị lỗi font | CSDL hoặc kết nối không dùng `utf8mb4` | Tạo lại CSDL với `utf8mb4_unicode_ci` và đặt `charset=utf8mb4` |
| Không tải được ảnh poster | Thư mục `uploads/` chưa có quyền ghi, ảnh quá 5MB hoặc sai định dạng | Cấp quyền ghi; dùng JPG/PNG không quá 5MB |
| Passcode báo hết hạn quá sớm hoặc muộn | Lệch múi giờ PHP và MySQL | Bảo đảm `date_default_timezone_set('Asia/Ho_Chi_Minh')` trong `database.php` và kiểm tra giờ hệ thống |
| Không xóa được sự kiện/địa điểm | Còn dữ liệu tham chiếu (ràng buộc khóa ngoại) | Xóa hoặc hủy dữ liệu liên quan trước |
| Trang trắng hoặc lỗi PHP | Lỗi cấu hình/mã nguồn | Xem log tại `C:\xampp\apache\logs\error.log` |

## 14. Hạn chế đã biết và hướng phát triển

**Hạn chế**

- Chỉ chạy cục bộ trên XAMPP, chưa có địa chỉ truy cập công khai.
- Chưa tích hợp trực tiếp với hệ thống điểm rèn luyện chính thức của Nhà trường.
- Passcode là mã dùng chung nên có thể bị chia sẻ cho người không có mặt; thời hạn 5 phút giảm thiểu nhưng chưa loại bỏ hoàn toàn rủi ro.
- Một phần ca kiểm thử còn chờ thực hiện; chưa kiểm thử tải với 150–300 người dùng đồng thời.

**Hướng phát triển**

- Hoàn tất kiểm thử 12 ca và thử nghiệm tại một sự kiện thực tế để đo các chỉ số.
- Nâng cấp cơ chế điểm danh (mã động theo từng sinh viên, xác minh vị trí).
- Triển khai lên máy chủ trong mạng nhà trường, bổ sung sao lưu tự động và tích hợp hệ thống ĐRL khi được cấp quyền.

## 15. Quy ước làm việc với Git

| Nội dung | Quy ước |
|---|---|
| Nhánh chính | `main` chỉ chứa mã đã kiểm thử; hợp nhất qua yêu cầu hợp nhất đã review |
| Nhánh tính năng | Đặt theo mã hoạt động, ví dụ `feature/t-10-crud-su-kien` |
| Thông điệp commit | Bắt đầu bằng mã hoạt động, ví dụ `T-14: khóa dòng khi đăng ký` |
| Code Freeze | Từ mốc M-03 chỉ sửa lỗi, không thêm tính năng; gắn thẻ phiên bản khi bàn giao |

## 16. Thành viên nhóm

| Họ và tên | MSSV | Vai trò |
|---|---|---|
| Võ Khánh Băng | 24210501052 | Quản lý dự án (Nhóm trưởng) |
| Châu Tiến Đạt | 24210501011 | Lập trình viên (giao diện, CRUD) |
| Nguyễn Thanh Nhàn | 24210501026 | Lập trình viên (nghiệp vụ, backend) |
| Đặng Công Danh | 24210501002 | Phân tích nghiệp vụ (BA) |
| Trần Vĩnh Thịnh | 24210501042 | Kiểm thử (Tester/QA) |

## 17. Hồ sơ đi kèm

- Báo cáo tổng kết: `Nhom6_QuanTriDuAn_QuanLySuKienSinhVien_2026.docx` / `.pdf`
- Ca kiểm thử: `Nhom6_TestCase_SEM.xlsx`
- Lịch dự án: `Nhom06_COMP1305_Lich.mpp`
- Liên kết kho mã nguồn GitHub:(https://github.com/chauda2006/QU-N-TR-D-N-C-NG-NGH-TH-NG-TIN.git)
- Liên kết video demo: https://www.figma.com/design/MyCUq7sPKBmDRXKuTXwLJX/Untitled?node-id=0-1&p=f

---

*Dự án phục vụ mục đích học tập. Mọi số liệu về lợi ích là kỳ vọng cho đến khi được kiểm chứng bằng dữ liệu thực tế.*
