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

                if (normalizedTitle.includes('chào tân sinh viên')) {imagePath = 'images/sukien01.jpg';} 
                else if (normalizedTitle.includes('hội thao')) {imagePath = 'images/sukien02.jpg';} 
                else if (normalizedTitle.includes('workshop')) { imagePath = 'images/sukien03.jpg';} 
                else if (normalizedTitle.includes('hiến máu')) { imagePath = 'images/sukien04.jpg';} 
                else if (normalizedTitle.includes('giao lưu')) {imagePath = 'images/sukien05.jpg';}
                else if (normalizedTitle.includes('hội thảo')) {imagePath = 'images/sukien06.jpg';} 
                else {imagePath = 'images/sukien01.jpg';}
    
            }

            const img = card.querySelector('.event-image');
            img.alt = title;
            img.src = imagePath;

            img.onerror = function () {
                this.onerror = null;
                this.src = 'images/sukien01.jpg';
    };


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
const deleteBtnEl = card.querySelector('.btn-delete-event');
if (deleteBtnEl) {
    deleteBtnEl.addEventListener('click', function () {
        const id = this.getAttribute('data-id');
        if (confirm(`Bạn có chắc chắn muốn xóa sự kiện này không?`)) {
            // Đã chỉnh đường dẫn vào đúng thư mục user/
            window.location.href = `user/delete.php?ma_sukien=${id}`;
        }
    });
}
events.forEach(function (event) {
            const title = getEventTitle(event);
            const eventId = getEventId(event);
            const description = getProp(event, 'mo_ta', 'mota', 'chitiet', 'description') || 'Không có mô tả.';
            const startDate = getProp(event, 'thoi_gian_bat_dau', 'batdau', 'ngay_to_chuc', 'ngay', 'date');
            const location = getProp(event, 'ten_diadiem', 'diadiem', 'dia_diem', 'vi_tri', 'diachi') || 'Chưa cập nhật';
            const cardIndex = allEvents.indexOf(event);

            // Nút Xem chi tiết
            const detailButton = isValidEventId(eventId)
                ? `<button type="button" class="btn btn-outline-success btn-sm btn-open-detail fw-bold me-1" data-index="${cardIndex}" style="border-color: #00f2c2; color: #00f2c2;"><i class="bi bi-eye me-1"></i> Chi tiết</button>`
                : `<button type="button" class="btn btn-outline-secondary btn-sm me-1" disabled><i class="bi bi-eye me-1"></i> Chi tiết</button>`;

            // NÚT SỬA VÀ XÓA XUẤT HIỆN TRỰC TIẾP TRÊN MỖI CARD SỰ KIỆN
            const adminActionButtons = isValidEventId(eventId)
                ? `<button type="button" class="btn btn-warning btn-sm btn-edit-event me-1" data-id="${eventId}" data-index="${cardIndex}" title="Sửa sự kiện"><i class="bi bi-pencil-square"></i> Sửa</button>
                   <button type="button" class="btn btn-danger btn-sm btn-delete-event" data-id="${eventId}" title="Xóa sự kiện"><i class="bi bi-trash"></i> Xóa</button>`
                : '';

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
                        <div class="event-actions mt-auto d-flex justify-content-between align-items-center">
                            ${detailButton}
                            <div class="d-flex align-items-center">${adminActionButtons}</div>
                        </div>
                    </div>
                </div>
            `;

            card.querySelector('.event-title').textContent = title;
            card.querySelector('.event-description').textContent = description;
            card.querySelector('.event-date span').textContent = formatDate(startDate);
            card.querySelector('.event-location span').textContent = location;

            // Xử lý đường dẫn ảnh
            const imageValue = getProp(event, 'anh_hien_thi', 'anh_bia', 'hinh_anh', 'anh', 'file_name', 'image');
            let imagePath = imageValue ? String(imageValue).trim() : '';
            if (imagePath && !imagePath.startsWith('http://') && !imagePath.startsWith('https://') && !imagePath.startsWith('/') && !imagePath.startsWith('images/')) {
                imagePath = 'images/' + imagePath;
            }
            if (!imagePath) imagePath = 'images/sukien1.jpg';

            const img = card.querySelector('.event-image');
            img.src = imagePath;
            img.onerror = function () { this.onerror = null; this.src = 'images/sukien1.jpg'; };

            // Gắn sự kiện Xem chi tiết
            if (isValidEventId(eventId)) {
                const detailBtnEl = card.querySelector('.btn-open-detail');
                if (detailBtnEl) {
                    detailBtnEl.addEventListener('click', function () {
                        showEventDetails(allEvents[this.getAttribute('data-index')], 'view');
                    });
                }

                // GẮN SỰ KIỆN NÚT XÓA -> Gọi file user/delete.php
                const deleteBtnEl = card.querySelector('.btn-delete-event');
                if (deleteBtnEl) {
                    deleteBtnEl.addEventListener('click', function () {
                        const id = this.getAttribute('data-id');
                        if (confirm(`Bạn có chắc chắn muốn xóa sự kiện "${title}" không?`)) {
                            window.location.href = `user/delete.php?ma_sukien=${id}`;
                        }
                    });
                }

                // GẮN SỰ KIỆN NÚT SỬA -> Nhập tên mới để sửa nhanh
                const editBtnEl = card.querySelector('.btn-edit-event');
                if (editBtnEl) {
                    editBtnEl.addEventListener('click', function () {
                        const id = this.getAttribute('data-id');
                        const newTitle = prompt("Nhập tên sự kiện mới:", title);
                        if (newTitle !== null && newTitle.trim() !== "") {
                            fetch('api_su_kien.php', {
                                method: 'PUT',
                                headers: { 'Content-Type': 'application/json' },
                                body: JSON.stringify({
                                    ma_sukien: id,
                                    ten_su_kien: newTitle.trim(),
                                    thoi_gian_bat_dau: startDate,
                                    dia_diem: location,
                                    mo_ta: description
                                })
                            })
                            .then(res => res.json())
                            .then(data => {
                                alert(data.message || 'Cập nhật thành công!');
                                loadEventData();
                            });
                        }
                    });
                }
            }

            eventList.appendChild(card);
        });
        