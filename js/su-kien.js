document.addEventListener('DOMContentLoaded', function () {

    let allEvents = [];
    let searchTimeout = null;

    const eventList = document.getElementById('event-list');
    const searchInput = document.getElementById('searchInput');
    const btnSearch = document.getElementById('btnSearch');

    loadEventData();

    function loadEventData() {
        fetch('api_su_kien.php')
            .then(response => {
                if (!response.ok) throw new Error('Không thể tải dữ liệu');
                return response.json();
            })
            .then(data => {
                allEvents = data;
                renderEvents(allEvents);
            })
            .catch(error => {
                console.error('Lỗi khi tải danh sách sự kiện:', error);
                if (eventList) {
                    eventList.innerHTML = `<div class="event-message text-danger"><i class="bi bi-exclamation-triangle fs-2 d-block mb-2"></i>Không thể tải danh sách sự kiện từ máy chủ.</div>`;
                }
            });
    }

    // Hàm quét thuộc tính thông minh tự động thích ứng với mọi cấu trúc CSDL
    function getProp(obj, ...keys) {
        if (!obj) return '';
        // 1. Kiểm tra các key chỉ định trước
        for (const key of keys) {
            if (obj[key] !== undefined && obj[key] !== null && String(obj[key]).trim() !== '') {
                return obj[key];
            }
        }
        // 2. Quét toàn bộ khóa trong object nếu không tìm thấy chính xác
        const lowerKeys = keys.map(k => k.toLowerCase());
        for (const propName in obj) {
            const lowerProp = propName.toLowerCase();
            if (lowerKeys.some(k => lowerProp.includes(k))) {
                if (obj[propName] !== undefined && obj[propName] !== null && String(obj[propName]).trim() !== '') {
                    return obj[propName];
                }
            }
        }
        return '';
    }

    function normalizeText(value) {
        return String(value || '').normalize('NFC').toLocaleLowerCase('vi').trim();
    }

    function formatDate(value) {
        if (!value) return 'Chưa cập nhật';
        const text = String(value).trim();
        const match = text.match(/^(\d{4})-(\d{2})-(\d{2})(?:[ T](\d{2}):(\d{2})(?::\d{2})?)?$/);
        if (!match) return text;
        const date = `${match[3]}/${match[2]}/${match[1]}`;
        return match[4] !== undefined ? `${date} ${match[4]}:${match[5]}` : date;
    }

    function getEventTitle(event) {
        return getProp(event, 'ten_su_kien', 'tensukien', 'tieu_de', 'tieude', 'ten_sk', 'tieu_de_su_kien', 'title', 'name', 'ten') || 'Sự kiện chưa có tên';
    }

    function getEventId(event) {
        return getProp(event, 'ma_sukien', 'masukien', 'id_su_kien', 'id_sukien', 'ma_su_kien', 'id');
    }

    function isValidEventId(eventId) {
        return eventId !== '' && eventId !== null && eventId !== undefined;
    }

    function renderEvents(events) {
        if (!eventList) return;
        eventList.innerHTML = '';

        if (!Array.isArray(events) || events.length === 0) {
            eventList.innerHTML = `<div class="event-message text-muted text-center py-5"><i class="bi bi-calendar-x fs-2 d-block mb-2"></i>Hiện chưa có sự kiện phù hợp.</div>`;
            return;
        }

        events.forEach(function (event) {
            const title = getEventTitle(event);
            const eventId = getEventId(event);
            const description = getProp(event, 'mo_ta', 'mota', 'chitiet', 'description') || 'Không có mô tả.';
            const startDate = getProp(event, 'thoi_gian_bat_dau', 'batdau', 'ngay_to_chuc', 'ngay', 'date');
            const location = getProp(event, 'ten_diadiem', 'diadiem', 'dia_diem', 'vi_tri', 'diachi') || 'Chưa cập nhật';
            const cardIndex = allEvents.indexOf(event);

            const detailButton = isValidEventId(eventId)
                ? `<button type="button" class="btn btn-outline-success btn-sm btn-open-detail fw-bold w-100" data-index="${cardIndex}" style="border-color: #00f2c2; color: #00f2c2;"><i class="bi bi-eye me-1"></i> Xem chi tiết & Đăng ký</button>`
                : `<button type="button" class="btn btn-outline-secondary btn-sm" disabled><i class="bi bi-eye me-1"></i> Xem chi tiết</button>`;

            const card = document.createElement('div');
            card.className = 'col-md-6 col-lg-4 mb-4';
            card.innerHTML = `
                <div class="card event-card h-100" style="border: 1px solid rgba(0, 242, 194, 0.3); border-radius: 16px; overflow: hidden; background: rgba(3, 2, 62, 0.67);">
                    <div style="height: 200px; width: 100%; overflow: hidden; background: #03022e; position: relative;">
                        <img class="event-image card-img-top" alt="${title}" loading="lazy" style="width: 100%; height: 100%; object-fit: cover;">
                    </div>
                    <div class="card-body d-flex flex-column" style="padding: 20px;">
                        <h5 class="event-title font-weight-bold" style="color: #00f2c2 !important; font-size: 18px; margin-bottom: 12px;"></h5>
                        <p class="event-description text-muted small flex-grow-1" style="color: #cbd5e1 !important; line-height: 1.5; display: -webkit-box; -webkit-line-clamp: 3; -webkit-box-orient: vertical; overflow: hidden;"></p>
                        <p class="event-date text-light small mb-2"><i class="bi bi-clock text-success me-1"></i><span></span></p>
                        <p class="event-location text-light small mb-3"><i class="bi bi-geo-alt text-success me-1"></i><span></span></p>
                        <div class="event-actions mt-auto">${detailButton}</div>
                    </div>
                </div>
            `;

            card.querySelector('.event-title').textContent = title;
            card.querySelector('.event-description').textContent = description;
<<<<<<< HEAD
            card.querySelector('.event-date').textContent = 'Bắt đầu: ' + formatDate(startDate);
            card.querySelector('.event-location').textContent = 'Địa điểm: ' + location;
          
            const imageValue = getProp(event, 'hinh_anh', 'hinhanh', 'image');
            let imagePath = imageValue ? String(imageValue).trim() : '';

            if (!imagePath) {
                const normalizedTitle = normalizeText(title);

                if (normalizedTitle.includes('chào tân sinh viên')) {imagePath = 'images/sukien01.jpg';} 
                else if (normalizedTitle.includes('hội thao')) {imagePath = 'images/sukien02.jpg';} 
                else if (normalizedTitle.includes('workshop')) { imagePath = 'images/sukien03.jpg';} 
                else if (normalizedTitle.includes('hiến máu')) { imagePath = 'images/sukien04.jpg';} 
                else if (normalizedTitle.includes('giao lưu')) {imagePath = 'images/sukien05.jpg';}
                else if (normalizedTitle.includes('hội thảo')) {imagePath = 'images/sukien06.jpg';} 
                else {imagePath = 'images/sukien01.jpg';}
    
=======
            card.querySelector('.event-date span').textContent = formatDate(startDate);
            card.querySelector('.event-location span').textContent = location;

            // Xử lý đường dẫn ảnh chính xác cho từng sự kiện
            const imageValue = getProp(event, 'anh_hien_thi', 'anh_bia', 'hinh_anh', 'anh', 'file_name', 'image');
            let imagePath = imageValue ? String(imageValue).trim() : '';
            
            if (imagePath && !imagePath.startsWith('http://') && !imagePath.startsWith('https://') && !imagePath.startsWith('/') && !imagePath.startsWith('images/')) {
                imagePath = 'images/' + imagePath;
            }
            if (!imagePath) {
                imagePath = 'images/sukien1.jpg';
>>>>>>> 49efca014943327e2c5fe5e0d02e81d254f75cf4
            }

            const img = card.querySelector('.event-image');
            img.src = imagePath;
<<<<<<< HEAD

            img.onerror = function () {
                this.onerror = null;
                this.src = 'images/sukien01.jpg';
    };

=======
            img.onerror = function () {
                this.onerror = null;
                this.src = 'images/sukien1.jpg';
            };
>>>>>>> 49efca014943327e2c5fe5e0d02e81d254f75cf4

            if (isValidEventId(eventId)) {
                const detailBtnEl = card.querySelector('.btn-open-detail');
                if (detailBtnEl) {
                    detailBtnEl.addEventListener('click', function () {
                        showEventDetails(allEvents[this.getAttribute('data-index')], 'view');
                    });
                }
            }

            eventList.appendChild(card);
        });
    }

    function showEventDetails(event, mode) {
        const eventId = getEventId(event);
        const title = getEventTitle(event);
        const description = getProp(event, 'mo_ta', 'mota', 'description') || 'Không có mô tả.';
        const startDate = getProp(event, 'thoi_gian_bat_dau', 'ngay_to_chuc', 'date');
        const endDate = getProp(event, 'thoi_gian_ket_thuc');
        const location = getProp(event, 'ten_diadiem', 'dia_diem', 'vi_tri') || 'Chưa cập nhật';
        const address = getProp(event, 'vi_tri', 'dia_chi') || 'Chưa cập nhật';
        const audience = getProp(event, 'doi_tuong_tham_gia', 'doi_tuong') || 'Toàn trường';
        const capacity = getProp(event, 'suc_chua', 'gioi_han') || 'Không giới hạn';

        if (document.getElementById('detailTitle')) document.getElementById('detailTitle').innerText = title;
        if (document.getElementById('detailId')) document.getElementById('detailId').innerText = eventId;
        if (document.getElementById('detailStart')) document.getElementById('detailStart').innerText = formatDate(startDate);
        if (document.getElementById('detailEnd')) document.getElementById('detailEnd').innerText = formatDate(endDate);
        if (document.getElementById('detailLocation')) document.getElementById('detailLocation').innerText = location;
        if (document.getElementById('detailAddress')) document.getElementById('detailAddress').innerText = address;
        if (document.getElementById('detailAudience')) document.getElementById('detailAudience').innerText = audience;
        if (document.getElementById('detailCapacity')) document.getElementById('detailCapacity').innerText = capacity;
        if (document.getElementById('detailDescription')) document.getElementById('detailDescription').innerText = description;
        if (document.getElementById('formEventId')) document.getElementById('formEventId').value = eventId;

        const infoSection = document.getElementById('modalEventInfo');
        const registerSection = document.getElementById('modalRegisterSection');

        if (infoSection) infoSection.style.display = 'block';
        if (registerSection) registerSection.style.display = 'block';

        const modalEl = document.getElementById('eventDetailModal');
        if (modalEl && typeof bootstrap !== 'undefined') {
            const myModal = new bootstrap.Modal(modalEl);
            myModal.show();
        }
    }

    // Bộ lọc tìm kiếm
    if (btnSearch && searchInput) {
        btnSearch.addEventListener('click', executeSearch);
        searchInput.addEventListener('input', function () {
            clearTimeout(searchTimeout);
            searchTimeout = setTimeout(executeSearch, 400);
        });
    }

    function executeSearch() {
        if (!searchInput) return;
        const keyword = normalizeText(searchInput.value);
        if (!keyword) {
            renderEvents(allEvents);
            return;
        }
        const filtered = allEvents.filter(event => {
            const title = normalizeText(getEventTitle(event));
            const loc = normalizeText(getProp(event, 'ten_diadiem', 'dia_diem', 'vi_tri'));
            return title.includes(keyword) || loc.includes(keyword);
        });
        renderEvents(filtered);
    }
<<<<<<< HEAD
});
// Xử lý khi nhấn nút "Chọn vị trí ghế ngồi" để chuyển trang
const btnGoToSeatMap = document.getElementById("btnGoToSeatMap");
if (btnGoToSeatMap) {
    btnGoToSeatMap.addEventListener("click", function() {
        const eventId = document.getElementById("formEventId").value;
        const fullname = encodeURIComponent(document.getElementById("fullname").value.trim());
        const email = encodeURIComponent(document.getElementById("email").value.trim());

        if (!eventId) {
            alert("Không tìm thấy mã sự kiện!");
            return;
        }

        // Chuyển hướng sang trang chon-ghe.php kèm theo các thông tin người dùng đang nhập dở
        window.location.href = `chon-ghe.php?id_su_kien=${eventId}&name=${fullname}&email=${email}`;
    });
}
// XỬ LÝ SỰ KIỆN CLICK NÚT CHỌN VỊ TRÍ GHẾ NGỒI ĐỂ CHUYỂN SANG TRANG CHON-GHE.PHP
btnGoToSeatMap = document.getElementById("btnGoToSeatMap");

if (btnGoToSeatMap) {
    btnGoToSeatMap.addEventListener("click", function() {
        // Lấy mã sự kiện đang được chọn trong input ẩn
        const eventId = document.getElementById("formEventId").value;
        
        // Lấy thông tin họ tên và email sinh viên đang nhập dở
        const fullname = encodeURIComponent(document.getElementById("fullname").value.trim());
        const email = encodeURIComponent(document.getElementById("email").value.trim());

        if (!eventId) {
            alert("Vui lòng đợi hệ thống tải thông tin mã sự kiện!");
            return;
        }

        // Tự động chuyển hướng sang trang sơ đồ 200 ghế độc lập kèm theo dữ liệu qua URL GET
        window.location.href = "chon-ghe.php?id_su_kien=" + eventId + "&name=" + fullname + "&email=" + email;
    });
}
=======
});
>>>>>>> 49efca014943327e2c5fe5e0d02e81d254f75cf4
