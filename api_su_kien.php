<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

// Đã sửa thành database.php cho đúng với dự án
include_once 'database.php';

$db = $pdo; // Sử dụng trực tiếp biến $pdo từ file database.php
$method = $_SERVER['REQUEST_METHOD'];

switch($method) {
    case 'GET':
        if(isset($_GET['ma_sukien'])) {
            $query = "SELECT sk.*, dd.ten_diadiem, dd.suc_chua, dd.vi_tri 
                      FROM su_kien sk 
                      LEFT JOIN dia_diem dd ON sk.ma_diadiem = dd.ma_diadiem 
                      WHERE sk.ma_sukien = :ma_sukien LIMIT 1";
            $stmt = $db->prepare($query);
            $stmt->bindParam(':ma_sukien', $_GET['ma_sukien']);
            $stmt->execute();

            $data = $stmt->fetch(PDO::FETCH_ASSOC);
            if($data) {
                echo json_encode($data);
            } else {
                http_response_code(404);
                echo json_encode(["message" => "Không tìm thấy sự kiện."]);
            }
        } else {
            $query = "SELECT sk.*, dd.ten_diadiem, dd.vi_tri 
                      FROM su_kien sk 
                      LEFT JOIN dia_diem dd ON sk.ma_diadiem = dd.ma_diadiem 
                      ORDER BY sk.thoi_gian_bat_dau ASC";
            $stmt = $db->prepare($query);
            $stmt->execute();

            $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
            echo json_encode($result);
        }
        break;

    case 'POST':
        $data = json_decode(file_get_contents("php://input"));
        if(!empty($data->ten_sukien) && !empty($data->thoi_gian_bat_dau) && !empty($data->thoi_gian_ket_thuc)) {
            $query = "INSERT INTO su_kien (ten_sukien, mo_ta, thoi_gian_bat_dau, thoi_gian_ket_thuc, ma_diadiem, file_name, file_path_key, file_type, file_size, upload_time) 
                      VALUES (:ten_sukien, :mo_ta, :thoi_gian_bat_dau, :thoi_gian_ket_thuc, :ma_diadiem, :file_name, :file_path_key, :file_type, :file_size, :upload_time)";
            $stmt = $db->prepare($query);

            $stmt->bindParam(':ten_sukien', $data->ten_sukien);
            $stmt->bindParam(':mo_ta', $data->mo_ta);
            $stmt->bindParam(':thoi_gian_bat_dau', $data->thoi_gian_bat_dau);
            $stmt->bindParam(':thoi_gian_ket_thuc', $data->thoi_gian_ket_thuc);
            $stmt->bindParam(':ma_diadiem', $data->ma_diadiem);

            $file_name = $data->file_name ?? null;
            $file_path_key = $data->file_path_key ?? null;
            $file_type = $data->file_type ?? null;
            $file_size = $data->file_size ?? null;
            $upload_time = $data->upload_time ?? null;

            $stmt->bindParam(':file_name', $file_name);
            $stmt->bindParam(':file_path_key', $file_path_key);
            $stmt->bindParam(':file_type', $file_type);
            $stmt->bindParam(':file_size', $file_size);
            $stmt->bindParam(':upload_time', $upload_time);

            if($stmt->execute()) {
                http_response_code(201);
                echo json_encode(["message" => "Tạo sự kiện thành công."]);
            } else {
                http_response_code(500);
                echo json_encode(["message" => "Không thể tạo sự kiện."]);
            }
        } else {
            http_response_code(400);
            echo json_encode(["message" => "Thông tin sự kiện không hợp lệ."]);
        }
        break;

    case 'PUT':
        $data = json_decode(file_get_contents("php://input"));
        if(!empty($data->ma_sukien) && !empty($data->ten_sukien) && !empty($data->thoi_gian_bat_dau) && !empty($data->thoi_gian_ket_thuc)) {
            $query = "UPDATE su_kien SET 
                        ten_sukien = :ten_sukien,
                        mo_ta = :mo_ta,
                        thoi_gian_bat_dau = :thoi_gian_bat_dau,
                        thoi_gian_ket_thuc = :thoi_gian_ket_thuc,
                        ma_diadiem = :ma_diadiem,
                        file_name = :file_name,
                        file_path_key = :file_path_key,
                        file_type = :file_type,
                        file_size = :file_size,
                        upload_time = :upload_time
                      WHERE ma_sukien = :ma_sukien";
            $stmt = $db->prepare($query);

            $stmt->bindParam(':ma_sukien', $data->ma_sukien);
            $stmt->bindParam(':ten_sukien', $data->ten_sukien);
            $stmt->bindParam(':mo_ta', $data->mo_ta);
            $stmt->bindParam(':thoi_gian_bat_dau', $data->thoi_gian_bat_dau);
            $stmt->bindParam(':thoi_gian_ket_thuc', $data->thoi_gian_ket_thuc);
            $stmt->bindParam(':ma_diadiem', $data->ma_diadiem);

            $file_name = $data->file_name ?? null;
            $file_path_key = $data->file_path_key ?? null;
            $file_type = $data->file_type ?? null;
            $file_size = $data->file_size ?? null;
            $upload_time = $data->upload_time ?? null;

            $stmt->bindParam(':file_name', $file_name);
            $stmt->bindParam(':file_path_key', $file_path_key);
            $stmt->bindParam(':file_type', $file_type);
            $stmt->bindParam(':file_size', $file_size);
            $stmt->bindParam(':upload_time', $upload_time);

            if($stmt->execute()) {
                echo json_encode(["message" => "Cập nhật sự kiện thành công."]);
            } else {
                http_response_code(500);
                echo json_encode(["message" => "Không thể cập nhật sự kiện."]);
            }
        } else {
            http_response_code(400);
            echo json_encode(["message" => "Dữ liệu cập nhật thiếu thông tin."]);
        }
        break;

    case 'DELETE':
        if(isset($_GET['ma_sukien'])) {
            $query = "DELETE FROM su_kien WHERE ma_sukien = :ma_sukien";
            $stmt = $db->prepare($query);
            $stmt->bindParam(':ma_sukien', $_GET['ma_sukien']);

            if($stmt->execute()) {
                echo json_encode(["message" => "Xóa sự kiện thành công."]);
            } else {
                http_response_code(500);
                echo json_encode(["message" => "Không thể xóa sự kiện."]);
            }
        } else {
            http_response_code(400);
            echo json_encode(["message" => "Thiếu mã sự kiện cần xóa."]);
        }
        break;
}
?>