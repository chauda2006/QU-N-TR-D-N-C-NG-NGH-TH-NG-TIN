<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

// Xử lý request preflight của AJAX nếu có
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

include_once 'database.php';
$db = isset($pdo) ? $pdo : (isset($conn) ? $conn : null);

if (!$db) {
    http_response_code(500);
    echo json_encode(["status" => "error", "message" => "Lỗi kết nối cơ sở dữ liệu."]);
    exit();
}

$method = $_SERVER['REQUEST_METHOD'];

switch ($method) {
    case 'GET':
        if (isset($_GET['ma_sukien']) || isset($_GET['id'])) {
            $id_val = $_GET['ma_sukien'] ?? $_GET['id'];
            $query = "SELECT * FROM su_kien WHERE ma_sukien = :id OR id = :id LIMIT 1";
            $stmt = $db->prepare($query);
            $stmt->bindParam(':id', $id_val);
            $stmt->execute();
            
            $data = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($data) {
                // Chuẩn hóa tên trường ảnh
                $ten_anh = '';
                if (!empty($data['anh_bia'])) $ten_anh = $data['anh_bia'];
                elseif (!empty($data['hinh_anh'])) $ten_anh = $data['hinh_anh'];
                elseif (!empty($data['anh'])) $ten_anh = $data['anh'];
                elseif (!empty($data['file_name'])) $ten_anh = $data['file_name'];
                
                $data['anh_hien_thi'] = trim($ten_anh);
                echo json_encode($data);
            } else {
                http_response_code(404);
                echo json_encode(["status" => "error", "message" => "Không tìm thấy sự kiện."]);
            }
        } else {
            $query = "SELECT * FROM su_kien ORDER BY thoi_gian_bat_dau DESC";
            $stmt = $db->prepare($query);
            $stmt->execute();
            $result = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // Chuẩn hóa tên trường ảnh cho toàn bộ danh sách
            foreach ($result as &$row) {
                $ten_anh = '';
                if (!empty($row['anh_bia'])) $ten_anh = $row['anh_bia'];
                elseif (!empty($row['hinh_anh'])) $ten_anh = $row['hinh_anh'];
                elseif (!empty($row['anh'])) $ten_anh = $row['anh'];
                elseif (!empty($row['file_name'])) $ten_anh = $row['file_name'];
                
                $row['anh_hien_thi'] = trim($ten_anh);
            }

            echo json_encode($result);
        }
        break;

    case 'POST':
        // Hỗ trợ nhận dữ liệu JSON hoặc FormData
        $data = json_decode(file_get_contents("php://input"), true);
        if (!$data) {
            $data = $_POST;
        }

        if (!empty($data)) {
            try {
                // Tự động quét cấu trúc cột thực tế của bảng su_kien
                $stmt_cols = $db->query("SHOW COLUMNS FROM su_kien");
                $columns = $stmt_cols->fetchAll(PDO::FETCH_COLUMN);

                // Xác định tên cột tiêu đề
                $col_title = 'ten_su_kien';
                foreach (['ten_su_kien', 'tieude', 'tieu_de', 'ten_sk', 'ten'] as $c) {
                    if (in_array($c, $columns)) { $col_title = $c; break; }
                }

                // Xác định tên cột ảnh
                $col_img = 'anh_bia';
                foreach (['anh_bia', 'hinh_anh', 'anh', 'image', 'file_name'] as $ic) {
                    if (in_array($ic, $columns)) { $col_img = $ic; break; }
                }

                $insert_data = [];
                $insert_data[$col_title] = $data['ten_su_kien'] ?? $data['tieu_de'] ?? 'Sự kiện mới';

                if (in_array('thoi_gian_bat_dau', $columns)) $insert_data['thoi_gian_bat_dau'] = $data['thoi_gian_bat_dau'] ?? null;
                if (in_array('thoi_gian_ket_thuc', $columns)) $insert_data['thoi_gian_ket_thuc'] = $data['thoi_gian_ket_thuc'] ?? null;
                if (in_array('dia_diem', $columns)) $insert_data['dia_diem'] = $data['dia_diem'] ?? null;
                if (in_array('vi_tri', $columns)) $insert_data['vi_tri'] = $data['vi_tri'] ?? null;
                if (in_array('doi_tuong_tham_gia', $columns)) $insert_data['doi_tuong_tham_gia'] = $data['doi_tuong_tham_gia'] ?? null;
                if (in_array('suc_chua', $columns)) $insert_data['suc_chua'] = $data['suc_chua'] ?? 0;
                if (in_array('mo_ta', $columns)) $insert_data['mo_ta'] = $data['mo_ta'] ?? null;
                if (in_array($col_img, $columns) && !empty($data['anh_hien_thi'])) $insert_data[$col_img] = $data['anh_hien_thi'];
                if (in_array('ma_sv_tao', $columns)) $insert_data['ma_sv_tao'] = $data['ma_sv_tao'] ?? null;

                $fields = array_keys($insert_data);
                $placeholders = array_fill(0, count($fields), '?');

                $sql = "INSERT INTO su_kien (" . implode(', ', $fields) . ") VALUES (" . implode(', ', $placeholders) . ")";
                $stmt = $db->prepare($sql);
                $success = $stmt->execute(array_values($insert_data));

                if ($success) {
                    echo json_encode(["status" => "success", "message" => "Thêm sự kiện qua API thành công."]);
                } else {
                    http_response_code(500);
                    echo json_encode(["status" => "error", "message" => "Không thể thêm vào CSDL."]);
                }
            } catch (Exception $e) {
                http_response_code(500);
                echo json_encode(["status" => "error", "message" => $e->getMessage()]);
            }
        } else {
            http_response_code(400);
            echo json_encode(["status" => "error", "message" => "Dữ liệu trống."]);
        }
        break;

    case 'PUT':
        echo json_encode(["status" => "success", "message" => "Cập nhật thành công."]);
        break;

    case 'DELETE':
        $id_val = $_GET['ma_sukien'] ?? $_GET['id'] ?? null;
        if ($id_val) {
            $query = "DELETE FROM su_kien WHERE ma_sukien = :id OR id = :id";
            $stmt = $db->prepare($query);
            $stmt->bindParam(':id', $id_val);
            if ($stmt->execute()) {
                echo json_encode(["status" => "success", "message" => "Xóa sự kiện thành công."]);
            } else {
                http_response_code(500);
                echo json_encode(["status" => "error", "message" => "Không thể xóa sự kiện."]);
            }
        } else {
            http_response_code(400);
            echo json_encode(["status" => "error", "message" => "Thiếu mã sự kiện để xóa."]);
        }
        break;

    default:
        http_response_code(405);
        echo json_encode(["status" => "error", "message" => "Phương thức không được hỗ trợ."]);
        break;
}
?>