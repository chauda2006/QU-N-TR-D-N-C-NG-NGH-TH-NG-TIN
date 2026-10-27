<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

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
            
            // LẤY CHI TIẾT 1 SỰ KIỆN KÈM LEFT JOIN ĐỊA ĐIỂM
            $query = "SELECT sk.*, dd.ten_diadiem, dd.vi_tri, dd.suc_chua as suc_chua_diadiem 
                      FROM su_kien sk 
                      LEFT JOIN dia_diem dd ON sk.ma_diadiem = dd.ma_diadiem 
                      WHERE sk.ma_sukien = :id OR sk.id = :id LIMIT 1";
            $stmt = $db->prepare($query);
            $stmt->bindParam(':id', $id_val);
            $stmt->execute();

            $data = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($data) {
                $ten_anh = '';
                if (!empty($data['anh_bia'])) $ten_anh = $data['anh_bia'];
                elseif (!empty($data['hinh_anh'])) $ten_anh = $data['hinh_anh'];
                elseif (!empty($data['anh'])) $ten_anh = $data['anh'];
                elseif (!empty($data['file_name'])) $ten_anh = $data['file_name'];

                $data['anh_hien_thị'] = trim($ten_anh);
                echo json_encode($data);
            } else {
                http_response_code(404);
                echo json_encode(["status" => "error", "message" => "Không tìm thấy sự kiện."]);
            }
        } else {
            // LẤY DANH SÁCH TẤT CẢ SỰ KIỆN KÈM LEFT JOIN ĐỊA ĐIỂM
            $query = "SELECT sk.*, dd.ten_diadiem, dd.vi_tri, dd.suc_chua as suc_chua_diadiem 
                      FROM su_kien sk 
                      LEFT JOIN dia_diem dd ON sk.ma_diadiem = dd.ma_diadiem 
                      ORDER BY sk.thoi_gian_bat_dau DESC";
            $stmt = $db->prepare($query);
            $stmt->execute();
            $result = $stmt->fetchAll(PDO::FETCH_ASSOC);

            foreach ($result as &$row) {
                $ten_anh = '';
                if (!empty($row['anh_bia'])) $ten_anh = $row['anh_bia'];
                elseif (!empty($row['hinh_anh'])) $ten_anh = $row['hinh_anh'];
                elseif (!empty($row['anh'])) $ten_anh = $row['anh'];
                elseif (!empty($row['file_name'])) $ten_anh = $row['file_name'];

                $row['anh_hien_thị'] = trim($ten_anh);
            }
            unset($row);

            echo json_encode($result);
        }
        break;

case 'POST':
        // 1. Nhận dữ liệu text từ form hoặc JSON
        $data = json_decode(file_get_contents("php://input"), true);
        if (!$data) {
            $data = $_POST;
        }

        if (!empty($data)) {
            try {
                // 2. KHỞI TẠO KẾT NỐI MINIO QUA DOCKER SERVICE
                // (Đảm bảo project đã cài aws-sdk-php hoặc nạp autoload)
                if (file_exists('vendor/autoload.php')) {
                    require_once 'vendor/autoload.php';
                }

                $s3 = null;
                if (class_exists('Aws\S3\S3Client')) {
                    $s3 = new Aws\S3\S3Client([
                        'version'                 => 'latest',
                        'region'                  => 'us-east-1',
                        'endpoint'                => 'http://minio:9000', // Tên service nội bộ trong compose.yaml
                        'use_path_style_endpoint' => true,
                        'credentials'             => [
                            'key'    => getenv('MINIO_ROOT_USER') ?: 'minioadmin',
                            'secret' => getenv('MINIO_ROOT_PASSWORD') ?: 'change_this_password',
                        ],
                    ]);
                }

                // 3. XỬ LÝ UPLOAD TỆP TIN POSTER NẾU CÓ
                $uploaded_filename = null;
                $object_key = null;
                $file_type = null;
                $file_size = 0;

                if (isset($_FILES['hinh_anh']) && $_FILES['hinh_anh']['error'] === UPLOAD_ERR_OK) {
                    $fileTmp  = $_FILES['hinh_anh']['tmp_name'];
                    $fileName = basename($_FILES['hinh_anh']['name']);
                    $fileSize = $_FILES['hinh_anh']['size'];
                    $fileType = $_FILES['hinh_anh']['type'];

                    // Quy tắc đặt object key: uploads/YYYY/MM/uuid_filename
                    $objectKey = 'uploads/' . date('Y/m') . '/' . uniqid() . '_' . preg_replace('/[^a-zA-Z0-9._-]/', '', $fileName);
                    $bucketName = getenv('MINIO_BUCKET_NAME') ?: 'event-posters';

                    if ($s3) {
                        // Đẩy file trực tiếp vào MinIO
                        $s3->putObject([
                            'Bucket'      => $bucketName,
                            'Key'         => $objectKey,
                            'SourceFile'  => $fileTmp,
                            'ContentType' => $fileType,
                        ]);
                    }

                    $uploaded_filename = $fileName;
                    $object_key = $objectKey;
                    $file_type = $fileType;
                    $file_size = $fileSize;
                }

                // 4. LẤY CÁC CỘT HIỆN CÓ CỦA BẢNG su_kien
                $stmt_cols = $db->query("SHOW COLUMNS FROM su_kien");
                $columns = $stmt_cols->fetchAll(PDO::FETCH_COLUMN);

                $col_title = 'ten_su_kien';
                foreach (['ten_su_kien', 'tieude', 'tieu_de', 'ten_sk', 'ten'] as $c) {
                    if (in_array($c, $columns)) { $col_title = $c; break; }
                }

                $col_img = 'anh_bia';
                foreach (['anh_bia', 'hinh_anh', 'anh', 'image', 'file_name'] as $ic) {
                    if (in_array($ic, $columns)) { $col_img = $ic; break; }
                }

                $insert_data = [];
                $insert_data[$col_title] = $data['ten_su_kien'] ?? $data['tieu_de'] ?? 'Sự kiện mới';

                if (in_array('thoi_gian_bat_dau', $columns)) $insert_data['thoi_gian_bat_dau'] = $data['thoi_gian_bat_dau'] ?? null;
                if (in_array('thoi_gian_ket_thuc', $columns)) $insert_data['thoi_gian_ket_thuc'] = $data['thoi_gian_ket_thuc'] ?? null;
                if (in_array('ma_diadiem', $columns)) $insert_data['ma_diadiem'] = $data['ma_diadiem'] ?? null;
                if (in_array('vi_tri', $columns)) $insert_data['vi_tri'] = $data['vi_tri'] ?? null;
                if (in_array('doi_tuong_tham_gia', $columns)) $insert_data['doi_tuong_tham_gia'] = $data['doi_tuong_tham_gia'] ?? null;
                if (in_array('suc_chua', $columns)) $insert_data['suc_chua'] = $data['suc_chua'] ?? 0;
                if (in_array('mo_ta', $columns)) $insert_data['mo_ta'] = $data['mo_ta'] ?? null;

                // Gán tên file hoặc object key vào cột ảnh
                $final_img = $object_key ?? $uploaded_filename ?? $data['anh_hien_thị'] ?? null;
                if (in_array($col_img, $columns) && !empty($final_img)) {
                    $insert_data[$col_img] = $final_img;
                }

                // Lưu thêm các cột metadata tệp nếu có trong bảng su_kien
                if (in_array('file_name', $columns) && $uploaded_filename) $insert_data['file_name'] = $uploaded_filename;
                if (in_array('file_path_key', $columns) && $object_key) $insert_data['file_path_key'] = $object_key;
                if (in_array('file_size', $columns) && $file_size) $insert_data['file_size'] = $file_size;

                $fields = array_keys($insert_data);
                $placeholders = array_fill(0, count($fields), '?');

                $sql = "INSERT INTO su_kien (" . implode(', ', $fields) . ") VALUES (" . implode(', ', $placeholders) . ")";
                $stmt = $db->prepare($sql);
                $success = $stmt->execute(array_values($insert_data));

                if ($success) {
                    http_response_code(201); // 201 Created theo chuẩn REST API đồ án
                    echo json_encode(["status" => "success", "message" => "Thêm sự kiện và upload poster thành công."]);
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
}
?>