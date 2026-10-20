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
                    eventList.innerHTML = `<div class="event-message text-danger"><i class="bi bi-exclamation-triangle fs-2 d-block mb-2"></i> Không thể tải danh sách sự kiện lúc này.</div>`;
                }
            });
    }

    function getProp(obj, ...keys) {
        for (const key of keys) {
            if (obj && obj[key] !== undefined && obj[key] !== null && String(obj[key]).trim() !== '') {
                return obj[key];
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
        return getProp(event, 'ten_sukien', 'ten_su_kien', 'tensukien', 'title', 'name') || 'Sự kiện chưa có tên';
    }

    function getEventId(event) {
        return getProp(event, 'id_su_kien', 'id_sukien', 'ma_sukien', 'ma_su_kien', 'id');
    }

    function isValidEventId(eventId) {
        return eventId !== '' && Number.isInteger(Number(eventId)) && Number(eventId) > 0;
    }

    function renderEvents(events) {
        if (!eventList) return;
        eventList.innerHTML = '';

        if (!Array.isArray(events) || events.length === 0) {
            eventList.innerHTML = `<div class="event-message"><i class="bi bi-calendar-x fs-2 d-block mb-2"></i>Hiện chưa có sự kiện phù hợp.</div>`;
            return;
        }

        events.forEach(function (event) {
            const title = getEventTitle(event);
            const eventId = getEventId(event);
            const description = getProp(event, 'mo_ta', 'mota', 'description') || 'Không có mô tả.';
            const startDate = getProp(event, 'thoi_gian_bat_dau', 'ngay_to_chuc', 'date');
            const location = getProp(event, 'ten_diadiem', 'dia_diem') || 'Chưa cập nhật';
            const cardIndex = allEvents.indexOf(event);

            const detailButton = isValidEventId(eventId)
                ? `<button type="button" class="btn-event-detail btn btn-primary btn-sm btn-open-detail" data-index="${cardIndex}"><i class="bi bi-eye"></i> Xem chi tiết</button>`
                : `<button type="button" class="btn-event-detail btn btn-primary btn-sm" disabled><i class="bi bi-eye"></i> Xem chi tiết</button>`;

            const registerButton = isValidEventId(eventId)
                ? `<button type="button" class="btn-event-register btn-open-register" data-index="${cardIndex}"><i class="bi bi-pencil-square"></i> Đăng ký</button>`
                : `<button type="button" class="btn-event-register" disabled><i class="bi bi-pencil-square"></i> Đăng ký</button>`;

            const card = document.createElement('div');
            card.className = 'col-12 col-md-6 col-lg-4 mb-4 event-column';
            card.innerHTML = `
                <div class="card event-card text-white bg-dark">
                    <img class="event-image" alt="" loading="lazy">
                    <div class="card-body">
                        <h5 class="event-title"></h5>
                        <p class="event-description"></p>
                        <p class="event-info"><i class="bi bi-calendar-event"></i> <span class="event-date"></span></p>
                        <p class="event-info"><i class="bi bi-geo-alt"></i> <span class="event-location"></span></p>
                        <div class="event-actions">${detailButton}${registerButton}</div>
                    </div>
                </div>
            `;

            card.querySelector('.event-title').textContent = title;
            card.querySelector('.event-description').textContent = description;
            card.querySelector('.event-date').textContent = 'Bắt đầu: ' + formatDate(startDate);
            card.querySelector('.event-location').textContent = 'Địa điểm: ' + location;

            const imageValue = getProp(event, 'hinh_anh', 'hinhanh', 'image');
            let imagePath = imageValue ? String(imageValue).trim() : '';
            if (!imagePath) {
                const normalizedTitle = normalizeText(title);
                if (normalizedTitle.includes('chào tân sinh viên')) imagePath = 'images/sukien1.jpg';
                else if (normalizedTitle.includes('workshop')) imagePath = 'images/sukien2.jpg';
                else if (normalizedTitle.includes('hội thảo')) imagePath = 'images/sukien3.jpg';
                else imagePath = 'images/default.jpg';
            }

            const img = card.querySelector('.event-image');
            img.alt = title;
            img.src = imagePath;
            img.onerror = function () { this.src = 'images/default.jpg'; };

            if (isValidEventId(eventId)) {
                // Hành vi 1: Bấm "Xem chi tiết" ngoài Card
                card.querySelector('.btn-open-detail').addEventListener('click', function() {
                    showEventDetails(allEvents[this.getAttribute('data-index')], 'view');
                });
                
                // Hành vi 2: Bấm "Đăng ký" ngoài Card
                card.querySelector('.btn-open-register').addEventListener('click', function() {
                    showEventDetails(allEvents[this.getAttribute('data-index')], 'register');
                });
            }

            eventList.appendChild(card);
        });
    }

    // Quản lý hiển thị tách biệt nội dung Modal dựa vào chế độ Mode ('view' hoặc 'register')
    function showEventDetails(event, mode) {
        const eventId = getEventId(event);
        const title = getEventTitle(event);
        
        const description = getProp(event, 'mo_ta', 'mota', 'description') || 'Không có mô tả.';
        const startDate = getProp(event, 'thoi_gian_bat_dau', 'ngay_to_chuc', 'date');
        const endDate = getProp(event, 'thoi_gian_ket_thuc', 'ngay_ket_thuc');
        const location = getProp(event, 'ten_diadiem', 'dia_diem', 'vi_tri') || 'Chưa cập nhật';
        const address = getProp(event, 'vi_tri', 'dia_chi') || 'Chưa cập nhật';
        const audience = getProp(event, 'doi_tuong_tham_gia', 'doi_tuong') || 'Toàn trường';
        const capacity = getProp(event, 'suc_chua', 'gioi_han') || 'Không giới hạn';

        // Khôi phục trạng thái form
        const modalForm = document.getElementById('modalRegisterForm');
        if (modalForm) modalForm.reset();
        const alertBox = document.getElementById('registerAlert');
        if (alertBox) alertBox.className = "alert d-none";

        // Đổ dữ liệu vào Modal HTML
        document.getElementById("detailTitle").innerText = title;
        document.getElementById("detailId").innerText = eventId;
        document.getElementById("detailStart").innerText = formatDate(startDate);
        document.getElementById("detailEnd").innerText = formatDate(endDate);
        document.getElementById("detailLocation").innerText = location;
        document.getElementById("detailAddress").innerText = address;
        document.getElementById("detailAudience").innerText = audience;
        document.getElementById("detailCapacity").innerText = capacity;
        document.getElementById("detailDescription").innerText = description;

        document.getElementById("formEventId").value = eventId;

        // PHÂN CHIA HIỂN THỊ CHỐNG LẶP GIAO DIỆN
        const infoSection = document.getElementById("modalEventInfo");
        const registerSection = document.getElementById("modalRegisterSection");
        const registerDivider = document.getElementById("modalRegisterDivider");

        if (mode === 'view') {
            // Chế độ xem: Hiện thông tin, ẩn Form đăng ký hoàn toàn
            infoSection.style.display = "block";
            registerSection.style.display = "none";
        } else if (mode === 'register') {
            // Chế độ đăng ký trực tiếp: Ẩn thông tin, chỉ hiện duy nhất Form nhập liệu lên đầu
            infoSection.style.display = "none";
            registerSection.style.display = "block";
            if (registerDivider) registerDivider.style.display = "none"; // Ẩn vạch kẻ ngang ngăn cách
            
            setTimeout(() => { 
                const nameInput = document.getElementById('fullname');
                if (nameInput) nameInput.focus(); 
            }, 300);
        }

        const myModal = new bootstrap.Modal(document.getElementById('eventDetailModal'));
        myModal.show();
    }

    // Xử lý gửi Form đăng ký ngầm qua AJAX
    const registerForm = document.getElementById('modalRegisterForm');
    if (registerForm) {
        registerForm.addEventListener('submit', function (e) {
            e.preventDefault();

            const eventId = document.getElementById('formEventId').value;
            const fullname = document.getElementById('fullname').value;
            const email = document.getElementById('email').value;
            const alertBox = document.getElementById('registerAlert');

            const formData = new FormData();
            formData.append('ajax_register', '1');
            formData.append('event_id', eventId);
            formData.append('fullname', fullname);
            formData.append('email', email);

            fetch('xu_ly_dang_ky_ajax.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(result => {
                if (alertBox) {
                    alertBox.classList.remove('d-none', 'alert-success', 'alert-danger');
                    if (result.status === 'success') {
                        alertBox.classList.add('alert-success');
                        // SỬA LỖI CÚ PHÁP: Đã bọc lại bằng dấu huyền `` để nhận diện biến \${result.message}
                        alertBox.innerHTML = `<i class="bi bi-check-circle-fill me-2"></i> ${result.message}`;
                        registerForm.reset();
                    } else {
                        alertBox.classList.add('alert-danger');
                        alertBox.innerHTML = `<i class="bi bi-exclamation-triangle-fill me-2"></i> ${result.message}`;
                    }
                }
            })
            .catch(error => {
                console.error('Lỗi:', error);
                if (alertBox) {
                    alertBox.classList.remove('d-none', 'alert-success');
                    alertBox.classList.add('alert-danger');
                    alertBox.innerHTML = `<i class="bi bi-exclamation-triangle-fill me-2"></i> Có lỗi xảy ra khi kết nối đến server!`;
                }
            });
        });
    }

    // Bộ lọc tìm kiếm
    if (btnSearch && searchInput) {
        btnSearch.addEventListener('click', executeSearch);
        searchInput.addEventListener('input', function() {
            clearTimeout(searchTimeout);
            searchTimeout = setTimeout(executeSearch, 400);
        });
    }

    function executeSearch() {
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
