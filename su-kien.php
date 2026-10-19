<?php
ob_start(); // Bật bộ đệm đầu ra để tránh lỗi "headers already sent" khi gọi session_start()
require_once 'database.php';
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Danh sách sự kiện</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <link rel="stylesheet" href="css/header.css">
    <link rel="stylesheet" href="css/index.css">

    <style>
        body {
            background: #0b0e14;
            color: #fff;
        }

        .event-container {
            padding: 30px 35px 50px;
            min-height: 80vh;
        }

        .event-heading {
            color: #fff;
            font-weight: 800;
            margin-bottom: 30px;
        }

        .search-box-wrapper {
            position: relative;
            max-width: 700px;
            margin: 0 auto 35px;
        }

        .search-icon-inside {
            position: absolute;
            left: 18px;
            top: 50%;
            transform: translateY(-50%);
            color: #ec4899;
            z-index: 5;
        }

        .custom-search-input {
            background: #151922 !important;
            border: 1px solid rgba(236, 72, 153, .4) !important;
            color: #fff !important;
            border-radius: 30px !important;
            padding: 12px 20px 12px 45px !important;
            min-width: 0;
        }

        .custom-search-input::placeholder {
            color: #9ca3af;
        }

        .custom-search-input:focus {
            border-color: #10b981 !important;
            box-shadow: 0 0 12px rgba(16, 185, 129, .3) !important;
        }

        .btn-custom-search {
            background: linear-gradient(135deg, #ec4899, #10b981);
            border: none;
            color: #fff;
            font-weight: 600;
            border-radius: 30px !important;
            padding: 10px 22px;
            margin-left: 8px !important;
            white-space: nowrap;
        }

        .btn-custom-search:hover {
            color: #fff;
            opacity: .9;
        }

        #event-list {
            row-gap: 8px;
        }

        .event-column {
            display: flex;
        }

        .event-card {
            width: 100%;
            height: 100%;
            padding: 18px;
            background: #12151b;
            border: 1px solid rgba(255, 255, 255, .09);
            border-radius: 20px;
            overflow: hidden;
            transition: all .3s ease;
        }

        .event-card:hover {
            transform: translateY(-5px);
            border-color: rgba(16, 185, 129, .5);
            box-shadow: 0 10px 25px rgba(0, 0, 0, .35);
        }

        .event-image {
            display: block;
            width: 100%;
            height: 180px;
            object-fit: cover;
            margin-bottom: 16px;
            border-radius: 8px;
            background: #1b1f27;
        }

        .event-title {
            color: #34d399;
            font-size: 1.15rem;
            font-weight: 700;
            line-height: 1.5;
            margin-bottom: 12px;
            overflow-wrap: anywhere;
        }

        .event-description {
            color: #c4c4c4;
            line-height: 1.6;
            margin-bottom: 18px;
            overflow-wrap: anywhere;
        }

        .event-info {
            color: #d1d5db;
            font-size: .9rem;
            line-height: 1.7;
            margin-bottom: 8px;
            overflow-wrap: anywhere;
        }

        .event-info i {
            color: #ec4899;
            margin-right: 7px;
        }

        .event-date {
            color: #ec4899;
        }

        .event-actions {
            display: flex;
            gap: 10px;
            margin-top: 18px;
            padding-top: 15px;
            border-top: 1px solid rgba(255, 255, 255, .1);
        }

        .btn-event-detail,
        .btn-event-register {
            flex: 1;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 6px;
            padding: 10px 8px;
            border-radius: 10px;
            font-size: 14px;
            font-weight: 600;
            text-decoration: none;
            transition: all .25s ease;
            cursor: pointer;
        }

        .btn-event-detail {
            color: #34d399;
            background: transparent;
            border: 1px solid #34d399;
        }

        .btn-event-detail:hover {
            color: #0b0e14;
            background: #34d399;
        }

        .btn-event-detail:disabled {
            opacity: .5;
            cursor: not-allowed;
        }

        .btn-event-register {
            color: #fff;
            background: linear-gradient(135deg, #ec4899, #10b981);
            border: 1px solid transparent;
        }

        .btn-event-register:hover {
            color: #fff;
            opacity: .85;
            transform: translateY(-2px);
        }

        .event-message {
            text-align: center;
            color: #c4c4c4;
            padding: 35px 10px;
            width: 100%;
        }

        /* Cửa sổ chi tiết nhỏ gọn, không có ảnh */
        #eventDetailModal .modal-dialog {
            max-width: 520px;
        }

        #eventDetailModal .modal-content {
            background: #12151b;
            color: #fff;
            border: 1px solid #34d399;
            border-radius: 16px;
        }

        #eventDetailModal .modal-header,
        #eventDetailModal .modal-footer {
            border-color: #333;
        }

        #eventDetailModal .modal-title {
            color: #34d399;
            font-weight: 700;
            overflow-wrap: anywhere;
        }

        #eventDetailModal .detail-info {
            display: flex;
            align-items: flex-start;
            gap: 10px;
            margin-bottom: 14px;
            line-height: 1.6;
            overflow-wrap: anywhere;
        }

        #eventDetailModal .detail-info i {
            color: #ec4899;
            margin-top: 3px;
        }

        #eventDetailModal .detail-label {
            color: #d1d5db;
            font-weight: 600;
        }

        #detailDescription {
            white-space: pre-wrap;
            overflow-wrap: anywhere;
            line-height: 1.7;
            color: #d1d5db;
        }

        @media (max-width: 991.98px) {
            .event-container {
                padding-left: 20px;
                padding-right: 20px;
            }

            .event-image {
                height: 190px;
            }
        }

        @media (max-width: 767.98px) {
            .event-container {
                padding: 25px 15px 40px;
            }

            .event-card {
                padding: 20px;
            }

            .event-image {
                height: 200px;
            }

            .event-heading {
                font-size: 1.5rem;
            }

            #eventDetailModal .modal-dialog {
                margin: 12px;
            }
        }

        @media (max-width: 380px) {
            .event-actions {
                flex-direction: column;
            }

            .btn-event-detail,
            .btn-event-register {
                width: 100%;
            }
        }
    </style>
</head>

<body>

<?php include_once 'header.php'; ?>

<main class="container-fluid event-container">

    <h1 class="text-center text-uppercase event-heading">
        Danh sách sự kiện
    </h1>

    <div class="search-box-wrapper">
        <i class="bi bi-search search-icon-inside"></i>

        <div class="input-group">
            <input
                type="text"
                id="searchInput"
                class="form-control custom-search-input"
                placeholder="Tìm kiếm sự kiện..."
                autocomplete="off"
            >

            <button class="btn btn-custom-search" type="button" id="btnSearch">
                Tìm kiếm
            </button>
        </div>
    </div>

    <div id="event-list" class="row">
        <p class="event-message">
            <i class="bi bi-arrow-repeat me-2"></i>
            Đang tải danh sách sự kiện...
        </p>
    </div>

</main>

<!-- Cửa sổ xem chi tiết: không có hình ảnh -->
<div class="modal fade" id="eventDetailModal" tabindex="-1"
     aria-labelledby="detailTitle" aria-hidden="true">

    <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable">

        <div class="modal-content">

            <div class="modal-header">
                <h5 class="modal-title" id="detailTitle">
                    Chi tiết sự kiện
                </h5>

                <button type="button"
                        class="btn-close btn-close-white"
                        data-bs-dismiss="modal"
                        aria-label="Đóng">
                </button>
            </div>

            <div class="modal-body">

                <div class="detail-info">
                    <i class="bi bi-upc-scan"></i>
                    <div>
                        <span class="detail-label">Mã sự kiện:</span>
                        <span id="detailId"></span>
                    </div>
                </div>

                <div class="detail-info">
                    <i class="bi bi-calendar-event"></i>
                    <div>
                        <span class="detail-label">Thời gian bắt đầu:</span>
                        <span id="detailStart"></span>
                    </div>
                </div>

                <div class="detail-info">
                    <i class="bi bi-calendar-check"></i>
                    <div>
                        <span class="detail-label">Thời gian kết thúc:</span>
                        <span id="detailEnd"></span>
                    </div>
                </div>

                <div class="detail-info">
                    <i class="bi bi-geo-alt"></i>
                    <div>
                        <span class="detail-label">Địa điểm:</span>
                        <span id="detailLocation"></span>
                    </div>
                </div>

                <div class="detail-info">
                    <i class="bi bi-pin-map"></i>
                    <div>
                        <span class="detail-label">Vị trí:</span>
                        <span id="detailAddress"></span>
                    </div>
                </div>

                <div class="detail-info">
                    <i class="bi bi-people"></i>
                    <div>
                        <span class="detail-label">Đối tượng tham gia:</span>
                        <span id="detailAudience"></span>
                    </div>
                </div>

                <div class="detail-info">
                    <i class="bi bi-person-check"></i>
                    <div>
                        <span class="detail-label">Sức chứa:</span>
                        <span id="detailCapacity"></span>
                    </div>
                </div>

                <hr style="border-color:#444;">

                <h6 style="color:#34d399;">Mô tả và nội dung sự kiện</h6>
                <p id="detailDescription" class="mb-0"></p>

            </div>

            <div class="modal-footer">

                <a id="detailRegister" href="#" class="btn-event-register">
                    <i class="bi bi-pencil-square"></i>
                    Đăng ký
                </a>

                <button type="button"
                        class="btn btn-secondary"
                        data-bs-dismiss="modal">
                    Đóng
                </button>

            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>

<script>
document.addEventListener('DOMContentLoaded', function () {

    let allEvents = [];
    let searchTimeout = null;

    const eventList = document.getElementById('event-list');
    const searchInput = document.getElementById('searchInput');
    const btnSearch = document.getElementById('btnSearch');

    // Lấy dữ liệu theo nhiều tên trường
    function getProp(obj, ...keys) {
        for (const key of keys) {
            if (
                obj &&
                obj[key] !== undefined &&
                obj[key] !== null &&
                String(obj[key]).trim() !== ''
            ) {
                return obj[key];
            }
        }

        return '';
    }

    // Chuẩn hóa chữ để tìm kiếm
    function normalizeText(value) {
        return String(value || '')
            .normalize('NFC')
            .toLocaleLowerCase('vi')
            .trim();
    }

    // Định dạng ngày giờ từ MySQL
    function formatDate(value) {
        if (!value) {
            return 'Chưa cập nhật';
        }

        const text = String(value).trim();

        const match = text.match(
            /^(\d{4})-(\d{2})-(\d{2})(?:[ T](\d{2}):(\d{2})(?::\d{2})?)?$/
        );

        if (!match) {
            return text;
        }

        const date = `${match[3]}/${match[2]}/${match[1]}`;

        return match[4] !== undefined
            ? `${date} ${match[4]}:${match[5]}`
            : date;
    }

    // Lấy tên sự kiện
    function getEventTitle(event) {
        return getProp(
            event,
            'ten_sukien',
            'ten_su_kien',
            'tensukien',
            'title',
            'name',
            'ten_sk',
            'ten'
        ) || 'Sự kiện chưa có tên';
    }

    // Lấy mã sự kiện
    function getEventId(event) {
        return getProp(
            event,
            'ma_sukien',
            'ma_su_kien',
            'id'
        );
    }

    // Kiểm tra mã sự kiện hợp lệ
    function isValidEventId(eventId) {
        return (
            eventId !== '' &&
            Number.isInteger(Number(eventId)) &&
            Number(eventId) > 0
        );
    }

    // Hiển thị danh sách sự kiện
    function renderEvents(events) {
        eventList.innerHTML = '';

        if (!Array.isArray(events) || events.length === 0) {
            eventList.innerHTML = `
                <div class="event-message">
                    <i class="bi bi-calendar-x fs-2 d-block mb-2"></i>
                    Hiện chưa có sự kiện phù hợp.
                </div>
            `;
            return;
        }

        events.forEach(function (event) {

            const title = getEventTitle(event);
            const eventId = getEventId(event);

            const description = getProp(
                event,
                'mo_ta',
                'mota',
                'description',
                'noi_dung'
            ) || 'Không có mô tả.';

            const startDate = getProp(
                event,
                'thoi_gian_bat_dau',
                'ngay_to_chuc',
                'ngaytochuc',
                'date',
                'ngay_dien_ra',
                'ngay'
            );

            const location = getProp(
                event,
                'ten_diadiem',
                'dia_diem_chi_tiet',
                'dia_diem',
                'vi_tri'
            ) || 'Chưa cập nhật';

            const validId = isValidEventId(eventId);

            const card = document.createElement('div');
            card.className = 'col-12 col-md-6 col-lg-4 mb-4 event-column';

            const detailButton = validId
                ? `
                    <button
                        type="button"
                        class="btn-event-detail"
                        data-event-id="${String(eventId).replace(/&/g, '&amp;').replace(/"/g, '&quot;')}">
                        <i class="bi bi-eye"></i>
                        Xem chi tiết
                    </button>
                `
                : `
                    <button type="button" class="btn-event-detail" disabled>
                        <i class="bi bi-eye"></i>
                        Xem chi tiết
                    </button>
                `;

            const registerButton = validId
                ? `
                    <a
                        href="dang_ky_su_kien.php?id=${encodeURIComponent(eventId)}"
                        class="btn-event-register">
                        <i class="bi bi-pencil-square"></i>
                        Đăng ký
                    </a>
                `
                : `
                    <button type="button" class="btn-event-register" disabled>
                        <i class="bi bi-pencil-square"></i>
                        Đăng ký
                    </button>
                `;

            card.innerHTML = `
                <div class="card event-card text-white">

                    <img class="event-image" alt="" loading="lazy">

                    <div class="card-body p-0">

                        <h5 class="event-title"></h5>

                        <p class="event-description"></p>

                        <p class="event-info">
                            <i class="bi bi-calendar-event"></i>
                            <span class="event-date"></span>
                        </p>

                        <p class="event-info">
                            <i class="bi bi-geo-alt"></i>
                            <span class="event-location"></span>
                        </p>

                        <div class="event-actions">
                            ${detailButton}
                            ${registerButton}
                        </div>

                    </div>
                </div>
            `;

            // Hiển thị nội dung an toàn
            card.querySelector('.event-title').textContent = title;
            card.querySelector('.event-description').textContent = description;
            card.querySelector('.event-date').textContent =
                'Bắt đầu: ' + formatDate(startDate);
            card.querySelector('.event-location').textContent =
                'Địa điểm: ' + location;

            // Hiển thị ảnh trên thẻ sự kiện
            const imageValue = getProp(
                event,
                'hinh_anh',
                'hinhanh',
                'image',
                'img',
                'hinh',
                'file_path_key',
                'file_name'
            );

            let imagePath = imageValue ? String(imageValue).trim() : '';

            if (
                imagePath &&
                !/^https?:\/\//i.test(imagePath) &&
                /^[a-z][a-z0-9+.-]*:/i.test(imagePath)
            ) {
                imagePath = '';
            }

            if (!imagePath) {
                const normalizedTitle = normalizeText(title);

                if (normalizedTitle.includes('chào tân sinh viên')) {
                    imagePath = 'images/sukien1.jpg';
                } else if (normalizedTitle.includes('workshop')) {
                    imagePath = 'images/sukien2.jpg';
                } else if (
                    normalizedTitle.includes('hội thảo') ||
                    normalizedTitle.includes('trí tuệ nhân tạo')
                ) {
                    imagePath = 'images/sukien3.jpg';
                } else {
                    imagePath = 'images/default.jpg';
                }
            }

            const img = card.querySelector('.event-image');

            img.alt = title;
            img.onerror = function () {
                this.onerror = null;
                this.src = 'images/default.jpg';
            };
            img.src = imagePath;

            eventList.appendChild(card);
        });
    }

    // Hiển thị cửa sổ chi tiết, không có ảnh
    function showEventDetails(event) {
        const eventId = getEventId(event);

        const startDate = getProp(
            event,
            'thoi_gian_bat_dau',
            'ngay_to_chuc',
            'ngaytochuc',
            'date',
            'ngay_dien_ra',
            'ngay'
        );

        const endDate = getProp(
            event,
            'thoi_gian_ket_thuc',
            'ngay_ket_thuc'
        );

        const location = getProp(
            event,
            'ten_diadiem',
            'dia_diem_chi_tiet',
            'dia_diem',
            'vi_tri'
        ) || 'Chưa cập nhật';

        const address = getProp(
            event,
            'vi_tri',
            'dia_chi',
            'dia_diem_chi_tiet'
        ) || 'Chưa cập nhật';

        const audience = getProp(
            event,
            'doi_tuong',
            'doi_tuong_tham_gia'
        ) || 'Tất cả sinh viên';

        const capacity = getProp(
            event,
            'suc_chua',
            'so_luong_tham_gia'
        );

        const description = getProp(
            event,
            'noi_dung_chi_tiet',
            'noi_dung',
            'mo_ta',
            'mota',
            'description'
        ) || 'Chưa có nội dung chi tiết.';

        document.getElementById('detailTitle').textContent =
            getEventTitle(event);

        document.getElementById('detailId').textContent =
            eventId || 'Chưa cập nhật';

        document.getElementById('detailStart').textContent =
            formatDate(startDate);

        document.getElementById('detailEnd').textContent =
            formatDate(endDate);

        document.getElementById('detailLocation').textContent = location;
        document.getElementById('detailAddress').textContent = address;
        document.getElementById('detailAudience').textContent = audience;

        document.getElementById('detailCapacity').textContent =
            capacity !== '' ? capacity + ' người' : 'Chưa cập nhật';

        document.getElementById('detailDescription').textContent =
            description;

        const registerLink = document.getElementById('detailRegister');

        if (isValidEventId(eventId)) {
            registerLink.href =
                'dang_ky_su_kien.php?id=' + encodeURIComponent(eventId);
            registerLink.style.display = 'inline-flex';
        } else {
            registerLink.style.display = 'none';
        }

        const modalElement = document.getElementById('eventDetailModal');
        const modal = bootstrap.Modal.getOrCreateInstance(modalElement);

        modal.show();
    }

    // Bắt sự kiện nhấn nút Xem chi tiết
    eventList.addEventListener('click', function (e) {
        const button = e.target.closest('.btn-event-detail');

        if (!button || button.disabled) {
            return;
        }

        const eventId = button.dataset.eventId;

        const selectedEvent = allEvents.find(function (event) {
            return String(getEventId(event)) === String(eventId);
        });

        if (selectedEvent) {
            showEventDetails(selectedEvent);
        }
    });

    // Tìm kiếm sự kiện
    function filterEvents() {
        const keyword = normalizeText(searchInput.value);

        const filtered = allEvents.filter(function (event) {
            const searchable = [
                getEventTitle(event),
                getProp(event, 'mo_ta', 'mota', 'description', 'noi_dung'),
                getProp(event, 'ten_diadiem', 'dia_diem', 'vi_tri'),
                getProp(event, 'doi_tuong', 'doi_tuong_tham_gia')
            ].join(' ');

            return normalizeText(searchable).includes(keyword);
        });

        renderEvents(filtered);
    }

    // Tải sự kiện từ API
    fetch('api_su_kien.php')
        .then(function (response) {
            if (!response.ok) {
                throw new Error('Lỗi HTTP ' + response.status);
            }

            return response.json();
        })
        .then(function (data) {
            if (Array.isArray(data)) {
                allEvents = data;
            } else if (data && Array.isArray(data.data)) {
                allEvents = data.data;
            } else {
                throw new Error('Dữ liệu API không đúng định dạng.');
            }

            renderEvents(allEvents);
        })
        .catch(function (error) {
            console.error('Lỗi tải sự kiện:', error);

            eventList.innerHTML = `
                <div class="event-message text-danger">
                    <i class="bi bi-exclamation-triangle me-2"></i>
                    Không thể tải dữ liệu sự kiện.
                    Vui lòng kiểm tra api_su_kien.php và kết nối cơ sở dữ liệu.
                </div>
            `;
        });

    // Sự kiện tìm kiếm
    btnSearch.addEventListener('click', filterEvents);

    searchInput.addEventListener('input', function () {
        clearTimeout(searchTimeout);
        searchTimeout = setTimeout(filterEvents, 250);
    });

    searchInput.addEventListener('keydown', function (event) {
        if (event.key === 'Enter') {
            event.preventDefault();
            clearTimeout(searchTimeout);
            filterEvents();
        }
    });

});
</script>

</body>
</html>
