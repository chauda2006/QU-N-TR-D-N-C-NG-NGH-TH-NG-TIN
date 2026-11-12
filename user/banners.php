<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Kết nối cơ sở dữ liệu từ file database.php ở thư mục gốc hoặc cấu hình trực tiếp
require_once '../database.php';
$pdo = $pdo ?? $conn ?? null;

if (!$pdo) {
    $host = 'localhost';
    $db   = 'do_an_cuoi_ky'; 
    $user = 'root';
    $pass = '';
    $charset = 'utf8mb4';
    try {
        $dsn = "mysql:host=$host;dbname=$db;charset=$charset";
        $pdo = new PDO($dsn, $user, $pass, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
        ]);
    } catch (\PDOException $e) {
        die("Lỗi kết nối CSDL: " . $e->getMessage());
    }
}

// 🔒 KIỂM TRA PHÂN QUYỀN BẢO MẬT: CHỈ ADMIN HOẶC QUẢN LÝ SỰ KIỆN MỚI ĐƯỢC TRUY CẬP
$vai_tro = $_SESSION['vai_tro'] ?? ($_SESSION['user']['role'] ?? 'sinh_vien');
$isAdminOrManager = ($vai_tro === 'admin' || $vai_tro === 'quan_ly_su_kien');

if (!$isAdminOrManager) {
    // Nếu không có quyền, đá văng về trang chủ hoặc dashboard sinh viên
    header("Location: ../index.php");
    exit();
}

$msg = '';
$editData = null;

// Xử lý Thêm mới hoặc Cập nhật (Sửa)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = trim($_POST['title']);
    $edit_id = isset($_POST['edit_id']) ? intval($_POST['edit_id']) : 0;

    // Kiểm tra nếu có upload ảnh mới
    $imagePath = null;
    if (isset($_FILES['banner_image']) && $_FILES['banner_image']['error'] === 0) {
        $allowed = ['jpg', 'jpeg', 'png', 'webp'];
        $filename = $_FILES['banner_image']['name'];
        $filetmp = $_FILES['banner_image']['tmp_name'];
        $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));

        if (in_array($ext, $allowed)) {
            $uploadDir = '../uploads/banners/';
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0777, true);
            }
            
            $newFilename = time() . '_' . basename($filename);
            $destination = $uploadDir . $newFilename;

            if (move_uploaded_file($filetmp, $destination)) {
                $imagePath = 'uploads/banners/' . $newFilename;
            }
        }
    }

    if ($edit_id > 0) {
        // --- CHỨC NĂNG SỬA BANNER ---
        if ($imagePath) {
            $stmt = $pdo->prepare("UPDATE banners SET title = ?, image_url = ? WHERE id = ?");
            $stmt->execute([$title, $imagePath, $edit_id]);
        } else {
            $stmt = $pdo->prepare("UPDATE banners SET title = ? WHERE id = ?");
            $stmt->execute([$title, $edit_id]);
        }
        $msg = "Cập nhật banner thành công!";
    } else {
        // --- CHỨC NĂNG THÊM BANNER ---
        if ($imagePath) {
            $stmt = $pdo->prepare("INSERT INTO banners (title, image_url) VALUES (?, ?)");
            $stmt->execute([$title, $imagePath]);
            $msg = "Thêm banner thành công!";
        } else {
            $msg = "Vui lòng chọn hình ảnh banner hợp lệ!";
        }
    }
}

// Xử lý Xóa banner
if (isset($_GET['delete'])) {
    $id = intval($_GET['delete']);
    $stmt = $pdo->prepare("SELECT image_url FROM banners WHERE id = ?");
    $stmt->execute([$id]);
    $banner = $stmt->fetch();
    if ($banner && file_exists('../' . $banner['image_url'])) {
        unlink('../' . $banner['image_url']);
    }

    $delStmt = $pdo->prepare("DELETE FROM banners WHERE id = ?");
    $delStmt->execute([$id]);
    header("Location: banners.php");
    exit;
}

// Nếu bấm nút Sửa, lấy thông tin cũ đổ lên form
if (isset($_GET['edit'])) {
    $editId = intval($_GET['edit']);
    $stmtEdit = $pdo->prepare("SELECT * FROM banners WHERE id = ?");
    $stmtEdit->execute([$editId]);
    $editData = $stmtEdit->fetch(PDO::FETCH_ASSOC);
}

$banners = $pdo->query("SELECT * FROM banners ORDER BY id DESC")->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quản lý Banner Slide Trang Chủ</title>
    <!-- Bootstrap 5 & Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;600;700;800&display=swap" rel="stylesheet">
    
    <style>
        *, *::before, *::after { box-sizing: border-box !important; font-family: 'Plus Jakarta Sans', sans-serif !important; }
        
        html, body {
            margin: 0 !important; padding: 0 !important;
            background-color: #030008 !important;
            color: #ffffff !important;
            min-height: 100vh !important;
            overflow-x: hidden !important;
        }
        
        body::before {
            content: '' !important;
            position: fixed !important;
            top: 0; left: 0; width: 100vw; height: 100vh;
            background: radial-gradient(circle at 15% 20%, rgba(255, 42, 109, 0.25) 0%, transparent 48%),
                        radial-gradient(circle at 85% 80%, rgba(5, 255, 161, 0.22) 0%, transparent 48%),
                        linear-gradient(135deg, #090314 0%, #030008 100%) !important;
            z-index: -999;
            pointer-events: none;
        }

        .container {
            max-width: 1050px !important;
            margin-top: 35px !important;
            margin-bottom: 50px !important;
        }

        h2 {
            color: #05ffa1 !important;
            font-weight: 800 !important;
            text-shadow: 0 0 15px rgba(5, 255, 161, 0.5);
        }

        /* 🧊 Khung Glassmorphism 3D */
        .card-custom {
            background: rgba(12, 8, 32, 0.9) !important;
            backdrop-filter: blur(25px) !important;
            -webkit-backdrop-filter: blur(25px) !important;
            border: 2px solid rgba(5, 255, 161, 0.35) !important;
            border-radius: 20px !important;
            box-shadow: 0 20px 50px rgba(0,0,0,0.8), inset 0 0 15px rgba(5, 255, 161, 0.08) !important;
            overflow: hidden;
            color: #fff !important;
            margin-bottom: 25px;
        }

        .card-header-custom {
            background: linear-gradient(135deg, rgba(255, 42, 109, 0.6) 0%, rgba(5, 255, 161, 0.3) 100%) !important;
            border-bottom: 2px solid rgba(5, 255, 161, 0.3) !important;
            padding: 16px 20px !important;
            font-weight: 800 !important;
            color: #ffffff !important;
        }

        .form-label {
            font-weight: 700;
            color: #05ffa1;
        }

        .form-control {
            background: rgba(8, 5, 22, 0.95) !important;
            border: 2px solid rgba(5, 255, 161, 0.35) !important;
            color: #fff !important;
            border-radius: 12px !important;
            padding: 12px 16px !important;
            font-weight: 600 !important;
        }

        .form-control:focus {
            border-color: #05ffa1 !important;
            box-shadow: 0 0 20px rgba(5, 255, 161, 0.5) !important;
        }

        /* Bảng dữ liệu danh sách */
        .table {
            color: #fff !important;
            vertical-align: middle;
        }
        .table th {
            background: rgba(5, 255, 161, 0.15) !important;
            color: #05ffa1 !important;
            border-color: rgba(5, 255, 161, 0.2) !important;
        }
        .table td {
            background: transparent !important;
            border-color: rgba(255, 255, 255, 0.08) !important;
            color: #fff !important;
        }

        .btn-success {
            background: linear-gradient(135deg, #05ffa1 0%, #047857 100%) !important;
            border: none !important;
            color: #030008 !important;
            font-weight: 800 !important;
            border-radius: 12px !important;
            padding: 10px 24px !important;
            box-shadow: 0 0 15px rgba(5, 255, 161, 0.4);
        }
        .btn-success:hover {
            box-shadow: 0 0 25px rgba(5, 255, 161, 0.8);
            transform: translateY(-2px);
        }

        .btn-secondary {
            background: rgba(255, 255, 255, 0.1) !important;
            border: 1px solid rgba(255, 255, 255, 0.3) !important;
            color: #fff !important;
            border-radius: 12px !important;
            font-weight: 700 !important;
        }
        .btn-secondary:hover {
            background: rgba(255, 255, 255, 0.2) !important;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2>Quản lý Banner Slide Trang Chủ</h2>
            <a href="dashboard.php" class="btn btn-secondary">
                <i class="bi bi-arrow-left me-1"></i> Quay lại Dashboard
            </a>
        </div>

        <?php if (!empty($msg)): ?>
            <div class="alert alert-success border-2 border-success fw-bold" style="background: rgba(5, 255, 161, 0.2); color: #05ffa1; border-radius: 12px;">
                <i class="bi bi-check-circle-fill me-2"></i> <?= htmlspecialchars($msg) ?>
            </div>
        <?php endif; ?>

        <!-- Form Thêm / Sửa Banner -->
        <div class="card-custom">
            <div class="card-header-custom">
                <i class="bi bi-image-fill me-2"></i>
                <?= $editData ? 'Chỉnh sửa Banner (ID: ' . $editData['id'] . ')' : 'Thêm Banner Mới (Kích thước gợi ý: 1000 x 400 px)' ?>
            </div>
            <div class="card-body p-4">
                <form action="" method="POST" enctype="multipart/form-data">
                    <?php if ($editData): ?>
                        <input type="hidden" name="edit_id" value="<?= $editData['id'] ?>">
                    <?php endif; ?>

                    <div class="mb-3">
                        <label class="form-label">Tiêu đề Banner:</label>
                        <input type="text" name="title" class="form-control" required placeholder="Nhập tiêu đề..." value="<?= $editData ? htmlspecialchars($editData['title']) : '' ?>">
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Hình ảnh Banner (Tỷ lệ chuẩn 1000x400px):</label>
                        <?php if ($editData && !empty($editData['image_url'])): ?>
                            <div class="mb-2">
                                <img src="../<?= htmlspecialchars($editData['image_url']) ?>" alt="Current Banner" style="width: 200px; height: 80px; object-fit: cover;" class="rounded border border-success">
                                <br><small class="text-warning">Ảnh hiện tại (Chọn file mới bên dưới nếu muốn thay đổi)</small>
                            </div>
                        <?php endif; ?>
                        <input type="file" name="banner_image" class="form-control" accept="image/*" <?= $editData ? '' : 'required' ?>>
                    </div>

                    <button type="submit" class="btn btn-success">
                        <i class="bi bi-save me-1"></i> <?= $editData ? 'Cập nhật Banner' : 'Đăng Banner' ?>
                    </button>
                    <?php if ($editData): ?>
                        <a href="banners.php" class="btn btn-secondary ms-2">Hủy bỏ</a>
                    <?php endif; ?>
                </form>
            </div>
        </div>

        <!-- Danh sách Banner hiện tại -->
        <div class="card-custom">
            <div class="card-header-custom">
                <i class="bi bi-list-nested me-2"></i> Danh sách Banner hiện tại
            </div>
            <div class="card-body p-4">
                <div class="table-responsive">
                    <table class="table table-bordered align-middle">
                        <thead>
                            <tr>
                                <th style="width: 80px;">ID</th>
                                <th style="width: 220px;">Hình ảnh</th>
                                <th>Tiêu đề</th>
                                <th style="width: 150px;" class="text-center">Thao tác</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (count($banners) > 0): ?>
                                <?php foreach ($banners as $b): ?>
                                <tr>
                                    <td><strong>#<?= $b['id'] ?></strong></td>
                                    <td><img src="../<?= htmlspecialchars($b['image_url']) ?>" alt="Banner" style="width: 180px; height: 70px; object-fit: cover;" class="rounded border border-secondary shadow-sm"></td>
                                    <td><strong style="color: #05ffa1;"><?= htmlspecialchars($b['title']) ?></strong></td>
                                    <td class="text-center">
                                        <a href="banners.php?edit=<?= $b['id'] ?>" class="btn btn-warning btn-sm text-dark fw-bold px-3 me-1">Sửa</a>
                                        <a href="banners.php?delete=<?= $b['id'] ?>" class="btn btn-danger btn-sm fw-bold px-2" onclick="return confirm('Bạn có chắc chắn muốn xóa banner này?')">Xóa</a>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr><td colspan="4" class="text-center text-muted py-4">Chưa có banner nào trong hệ thống.</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</body>
</html>