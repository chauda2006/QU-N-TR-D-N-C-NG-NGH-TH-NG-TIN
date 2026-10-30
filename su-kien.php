<?php
session_start();
// Hiển thị nút Đăng sự kiện nếu là Quản lý sự kiện hoặc Admin
if (isset($_SESSION['vai_tro']) && ($_SESSION['vai_tro'] === 'quan_ly_su_kien' || $_SESSION['vai_tro'] === 'admin')): 
?>
<div style="text-align: right; margin: 20px 20px 0 0;">
    <a href="user/quan_ly_su_kien.php" class="btn-space-manager">
        <i class="bi bi-plus-circle-fill"></i> Đăng & Bổ Sung Sự Kiện Mới
    </a>
    <!-- Nút Xóa sự kiện -->
    <a href="user/delete.php" class="btn-space-danger">
        <i class="bi bi-gear-fill"></i> Xóa
    </a>
    <!-- Nút Sửa sự kiện -->
    <a href="user/xuly_sua.php" class="btn-space-warning">
        <i class="bi bi-gear-fill"></i> Sửa
    </a>
</div>
<?php endif; ?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Danh sách sự kiện</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <link rel="stylesheet" href="css/header.css">
    <link rel="stylesheet" type="text/css" href="./css/nut-su-kien.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" type="text/css" href="./css/model-su-kien.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" type="text/css" href="./css/su-kien.css?v=<?php echo time(); ?>">

    <!-- CSS TINH CHỈNH 3D SCI-FI CHO THANH TÌM KIẾM & NÚT LỌC -->
    <style>
        /* Tiêu đề vũ trụ */
        .event-heading {
            color: #ffffff !important;
            text-align: center !important;
            font-size: 2.6rem !important;
            font-weight: 900 !important;
            letter-spacing: 4px !important;
            margin-top: 15px !important;
            margin-bottom: 30px !important;
            text-transform: uppercase !important;
            background: linear-gradient(180deg, #ffffff 0%, #34d399 50%, #00f3ff 100%) !important;
            -webkit-background-clip: text !important;
            -webkit-text-fill-color: transparent !important;
            filter: drop-shadow(0 4px 15px rgba(0, 0, 0, 0.9)) drop-shadow(0 0 25px rgba(0, 243, 255, 0.6)) !important;
        }

        /* Thanh tìm kiếm 3D Glassmorphism */
        .search-box-wrapper {
            position: relative !important;
            max-width: 740px !important;
            margin: 10px auto 30px auto !important;
            background: linear-gradient(135deg, rgba(11, 16, 32, 0.92) 0%, rgba(5, 8, 18, 0.96) 100%) !important;
            backdrop-filter: blur(25px) !important;
            -webkit-backdrop-filter: blur(25px) !important;
            border: 1.8px solid rgba(0, 243, 255, 0.4) !important;
            border-radius: 50px !important;
            padding: 7px 9px 7px 18px !important;
            display: flex !important;
            align-items: center !important;
            box-shadow: 0 20px 45px rgba(0, 0, 0, 0.85), inset 0 1px 2px rgba(255, 255, 255, 0.25), inset 0 -2px 10px rgba(0, 243, 255, 0.15) !important;
            transition: all 0.4s cubic-bezier(0.16, 1, 0.3, 1) !important;
        }

        .search-box-wrapper:hover,
        .search-box-wrapper:focus-within {
            border-color: #00f3ff !important;
            box-shadow: 0 30px 60px rgba(0, 0, 0, 0.95), 0 0 40px rgba(0, 243, 255, 0.6), inset 0 0 15px rgba(0, 243, 255, 0.2) !important;
            transform: translateY(-4px) !important;
        }

        .search-icon-inside {
            position: absolute !important;
            left: 24px !important;
            top: 50% !important;
            transform: translateY(-50%) !important;
            color: #00f3ff !important;
            font-size: 1.35rem !important;
            pointer-events: none !important;
            filter: drop-shadow(0 0 10px rgba(0, 243, 255, 0.9)) !important;
            z-index: 5 !important;
        }

        .search-box-wrapper .input-group {
            display: flex !important;
            align-items: center !important;
            width: 100% !important;
            background: transparent !important;
            border: none !important;
            margin: 0 !important;
            padding: 0 !important;
        }

        .custom-search-input {
            background: transparent !important;
            border: none !important;
            outline: none !important;
            box-shadow: none !important;
            color: #ffffff !important;
            font-size: 16px !important;
            font-weight: 500 !important;
            padding: 12px 18px 12px 48px !important;
            flex: 1 !important;
            width: auto !important;
        }

        .custom-search-input:focus {
            background: transparent !important;
            border: none !important;
            box-shadow: none !important;
            color: #ffffff !important;
        }

        .custom-search-input::placeholder {
            color: #64748b !important;
        }

        .btn-custom-search {
            background: linear-gradient(135deg, #f43f5e 0%, #059669 100%) !important;
            color: #ffffff !important;
            border: 1px solid rgba(255, 255, 255, 0.25) !important;
            border-radius: 40px !important;
            padding: 12px 36px !important;
            font-weight: 800 !important;
            font-size: 15px !important;
            cursor: pointer !important;
            white-space: nowrap !important;
            flex-shrink: 0 !important;
            box-shadow: 0 6px 20px rgba(244, 63, 94, 0.45) !important;
            transition: all 0.3s ease !important;
        }

        .btn-custom-search:hover {
            background: linear-gradient(135deg, #00f3ff 0%, #10b981 100%) !important;
            color: #03040b !important;
            box-shadow: 0 0 30px rgba(0, 243, 255, 0.85) !important;
            transform: scale(1.03) !important;
        }

        /* Cụm nút lọc sự kiện */
        .filter-box-wrapper {
            display: flex !important;
            justify-content: center !important;
            align-items: center !important;
            gap: 15px !important;
            margin: 0 auto 35px auto !important;
            flex-wrap: wrap !important;
        }

        .btn-filter-custom {
            background: rgba(11, 15, 28, 0.75) !important;
            backdrop-filter: blur(15px) !important;
            -webkit-backdrop-filter: blur(15px) !important;
            border: 1.5px solid rgba(52, 211, 153, 0.3) !important;
            color: #94a3b8 !important;
            padding: 10px 24px !important;
            border-radius: 30px !important;
            font-size: 14.5px !important;
            font-weight: 700 !important;
            cursor: pointer !important;
            transition: all 0.3s ease !important;
            box-shadow: 0 8px 20px rgba(0, 0, 0, 0.5) !important;
            display: inline-flex !important;
            align-items: center !important;
        }

        .btn-filter-custom:hover {
            color: #00f3ff !important;
            border-color: #00f3ff !important;
            box-shadow: 0 0 20px rgba(0, 243, 255, 0.4) !important;
            transform: translateY(-2px) !important;
        }

        .btn-filter-custom.active {
            background: linear-gradient(135deg, rgba(244, 63, 94, 0.9) 0%, rgba(16, 185, 129, 0.9) 100%) !important;
            color: #ffffff !important;
            border-color: #ffffff !important;
            box-shadow: 0 0 25px rgba(0, 243, 255, 0.6), 0 6px 15px rgba(244, 63, 94, 0.4) !important;
            transform: scale(1.03) !important;
        }
    </style>
</head>
<body>

    <!-- Video Background -->
    <video autoplay loop muted playsinline preload="auto" class="video-background">
        <source src="videos/video01.mp4" type="video/mp4">
        Trình duyệt của bạn không hỗ trợ video background.
    </video>
    
    <!-- Lớp phủ tối -->
    <div class="video-overlay"></div>

    <?php include_once 'header.php'; ?>

    <main class="container-fluid event-container">
        <h1 class="text-center text-uppercase event-heading">
            Danh sách sự kiện
        </h1>
        
        <!-- Thanh tìm kiếm 3D -->
        <div class="search-box-wrapper">
            <i class="bi bi-search search-icon-inside"></i>
            <div class="input-group">
                <input type="text" id="searchInput" class="form-control custom-search-input" placeholder="Tìm kiếm sự kiện..." autocomplete="off">
                <button class="btn btn-custom-search" type="button" id="btnSearch">
                    Tìm kiếm
                </button>
            </div>
        </div>

        <!-- Cụm nút lọc sự kiện mới & sắp diễn ra -->
        <div class="filter-box-wrapper">
            <button class="btn-filter-custom active" data-filter="all">
                <i class="bi bi-grid-fill me-2"></i>Tất cả
            </button>
            <button class="btn-filter-custom" data-filter="newest">
                <i class="bi bi-stars me-2"></i>Sự kiện mới
            </button>
            <button class="btn-filter-custom" data-filter="upcoming">
                <i class="bi bi-hourglass-split me-2"></i>Sắp diễn ra
            </button>
        </div>

        <div id="event-list" class="row">
            <p class="event-message">
                <i class="bi bi-arrow-repeat me-2"></i> Đang tải danh sách sự kiện.
            </p>
        </div>
    </main>

    <!-- Modal Xem chi tiết và Đăng ký -->
    <div class="modal fade" id="eventDetailModal" tabindex="-1" aria-labelledby="detailTitle" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable">
            <div class="modal-content" style="background-color: #1a1d29; color: #e2e8f0; border: 1px solid #2d3142;">
                <div class="modal-header" style="border-bottom: 1px solid #2d3142;">
                    <h5 class="modal-title text-success fw-bold" id="detailTitle">
                        Chi tiết sự kiện
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Đóng"></button>
                </div>
                <div class="modal-body">
                    <div id="modalEventInfo">
                        <div class="detail-info mb-2">
                            <i class="bi bi-upc-scan text-success me-2"></i>
                            <div>
                                <span class="detail-label">Mã sự kiện:</span>
                                <span id="detailId" class="fw-bold"></span>
                            </div>
                        </div>
                        <div class="detail-info mb-2">
                            <i class="bi bi-calendar-event text-success me-2"></i>
                            <div>
                                <span class="detail-label">Thời gian bắt đầu:</span>
                                <span id="detailStart"></span>
                            </div>
                        </div>
                        <div class="detail-info mb-2">
                            <i class="bi bi-calendar-check text-success me-2"></i>
                            <div>
                                <span class="detail-label">Thời gian kết thúc:</span>
                                <span id="detailEnd"></span>
                            </div>
                        </div>
                        <div class="detail-info mb-2">
                            <i class="bi bi-geo-alt text-success me-2"></i>
                            <div>
                                <span class="detail-label">Địa điểm:</span>
                                <span id="detailLocation"></span>
                            </div>
                        </div>
                        <div class="detail-info mb-2">
                            <i class="bi bi-pin-map text-success me-2"></i>
                            <div>
                                <span class="detail-label">Vị trí:</span>
                                <span id="detailAddress"></span>
                            </div>
                        </div>
                        <div class="detail-info mb-2">
                            <i class="bi bi-people text-success me-2"></i>
                            <div>
                                <span class="detail-label">Đối tượng tham gia:</span>
                                <span id="detailAudience"></span>
                            </div>
                        </div>
                        <div class="detail-info mb-3">
                            <i class="bi bi-person-check text-success me-2"></i>
                            <div>
                                <span class="detail-label">Sức chứa:</span>
                                <span id="detailCapacity"></span>
                            </div>
                        </div>
                        <hr style="border-color:#444;">
                        <h6 style="color:#34d399;" class="fw-bold">Mô tả và nội dung sự kiện</h6>
                        <p id="detailDescription" class="mb-3 text-muted small"></p>
                    </div>

                    <div id="modalRegisterSection">
                        <hr id="modalRegisterDivider" style="border-color:#444;">
                        <h6 style="color:#10b981;" class="fw-bold mb-3">
                            <i class="bi bi-pencil-square me-2"></i>BẠN CHẮC CHẮN MUỐN THAM GIA CHỨ?
                        </h6>
                        <div id="registerAlert" class="alert d-none mb-3" style="font-size: 14px; padding: 10px;"></div>
                        <form id="modalRegisterForm">
                            <input type="hidden" id="formEventId">
                            <div class="text-center mt-3">
                                <a id="btnRegisterModal" href="dang-ky.php" class="btn btn-danger w-100 fw-bold py-2">
                                    <i class="bi bi-pencil-square me-2"></i>Đăng ký tham gia
                                </a>
                            </div>
                        </form>
                    </div>
                </div>
                <div class="modal-footer" style="border-top: 1px solid #2d3142;">
                    <button type="button" class="btn btn-secondary w-100" data-bs-dismiss="modal">
                        Đóng
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        const canManageEvents = <?php echo (isset($_SESSION['vai_tro']) && ($_SESSION['vai_tro'] === 'quan_ly_su_kien' || $_SESSION['vai_tro'] === 'admin')) ? 'true' : 'false'; ?>;
    </script>
    
    <!-- Script xử lý chuyển trạng thái nút lọc và lọc thời gian bắt đầu/kết thúc -->
    <script>
        document.addEventListener("DOMContentLoaded", function () {
            const filterButtons = document.querySelectorAll(".btn-filter-custom");

            filterButtons.forEach(btn => {
                btn.addEventListener("click", function () {
                    filterButtons.forEach(b => b.classList.remove("active"));
                    this.classList.add("active");

                    const filterType = this.getAttribute("data-filter");
                    if (typeof filterEvents === "function") {
                        filterEvents(filterType);
                    }
                });
            });
        });

        // Hàm logic lọc sự kiện theo thời gian bắt đầu và kết thúc
        function filterEvents(filterType) {
            const eventColumns = document.querySelectorAll('.event-column');
            const now = new Date();

            eventColumns.forEach(col => {
                // Quét thông tin thời gian bắt đầu/kết thúc hiển thị trong card
                const textContent = col.innerText || '';
                
                // Tìm kiếm chuỗi định dạng ngày giờ dạng DD/MM/YYYY HH:MM
                const matches = textContent.match(/(\d{2}\/\d{2}\/\d{4}(?:\s+\d{2}:\d{2})?)/g);
                
                let startDate = null;
                let endDate = null;

                if (matches && matches.length > 0) {
                    // Lấy mốc thời gian bắt đầu đầu tiên tìm thấy
                    let startParts = matches[0].split(' ');
                    let dateParts = startParts[0].split('/');
                    let timeParts = startParts[1] ? startParts[1].split(':') : [0, 0];
                    startDate = new Date(dateParts[2], dateParts[1] - 1, dateParts[0], timeParts[0], timeParts[1]);

                    // Nếu có mốc thời gian thứ hai (thời gian kết thúc)
                    if (matches.length > 1) {
                        let endParts = matches[1].split(' ');
                        let endDateParts = endParts[0].split('/');
                        let endTimeParts = endParts[1] ? endParts[1].split(':') : [0, 0];
                        endDate = new Date(endDateParts[2], endDateParts[1] - 1, endDateParts[0], endTimeParts[0], endTimeParts[1]);
                    }
                }

                // Thực hiện ẩn/hiện card theo nút lọc
                if (filterType === 'all') {
                    col.style.display = 'block';
                } 
                else if (filterType === 'upcoming') {
                    // Sắp diễn ra: Thời gian bắt đầu lớn hơn hiện tại
                    if (startDate && startDate > now) {
                        col.style.display = 'block';
                    } else {
                        col.style.display = 'none';
                    }
                } 
                else if (filterType === 'newest') {
                    // Sự kiện mới: Sự kiện vừa mới bắt đầu hoặc diễn ra trong thời gian gần đây
                    if (startDate && startDate >= now) {
                        col.style.display = 'block';
                    } else {
                        col.style.display = 'none';
                    }
                }
            });
        }
    </script>

    <!-- Gọi file JS chính của bạn -->
    <script src="js/su-kien.js"></script>
</body>
</html>