<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

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
            // Nếu có đổi ảnh mới thì cập nhật cả tiêu đề lẫn ảnh
            $stmt = $pdo->prepare("UPDATE banners SET title = ?, image_url = ? WHERE id = ?");
            $stmt->execute([$title, $imagePath, $edit_id]);
        } else {
            // Nếu không đổi ảnh thì chỉ cập nhật tiêu đề
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
    <title>Quản lý Banner Slide Trang Chủ</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
    <div class="container mt-4 mb-5">
        <h2>Quản lý Banner Slide Trang Chủ</h2>
        <a href="dashboard.php" class="btn btn-secondary mb-3">Quay lại Dashboard</a>

        <?php if (!empty($msg)): ?>
            <div class="alert alert-info"><?= htmlspecialchars($msg) ?></div>
        <?php endif; ?>

        <!-- Form Thêm / Sửa Banner -->
        <div class="card mb-4 shadow-sm">
            <div class="card-header bg-primary text-white">
                <?= $editData ? 'Chỉnh sửa Banner (ID: ' . $editData['id'] . ')' : 'Thêm Banner Mới (Kích thước gợi ý: 1000 x 400 px)' ?>
            </div>
            <div class="card-body">
                <form action="" method="POST" enctype="multipart/form-data">
                    <!-- Input ẩn lưu ID khi đang ở chế độ sửa -->
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
                                <img src="../<?= htmlspecialchars($editData['image_url']) ?>" alt="Current Banner" style="width: 200px; height: 80px; object-fit: cover;" class="rounded border">
                                <br><small class="text-muted">Ảnh hiện tại (Chọn file mới bên dưới nếu muốn thay đổi)</small>
                            </div>
                        <?php endif; ?>
                        <input type="file" name="banner_image" class="form-control" accept="image/*" <?= $editData ? '' : 'required' ?>>
                    </div>

                    <button type="submit" class="btn btn-success"><?= $editData ? 'Cập nhật Banner' : 'Đăng Banner' ?></button>
                    <?php if ($editData): ?>
                        <a href="banners.php" class="btn btn-secondary">Hủy bỏ</a>
                    <?php endif; ?>
                </form>
            </div>
        </div>

        <!-- Danh sách Banner hiện tại -->
        <div class="card shadow-sm">
            <div class="card-header bg-secondary text-white">Danh sách Banner hiện tại</div>
            <div class="card-body">
                <table class="table table-bordered align-middle">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Hình ảnh</th>
                            <th>Tiêu đề</th>
                            <th>Thao tác</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (count($banners) > 0): ?>
                            <?php foreach ($banners as $b): ?>
                            <tr>
                                <td><?= $b['id'] ?></td>
                                <td><img src="../<?= htmlspecialchars($b['image_url']) ?>" alt="Banner" style="width: 150px; height: 60px; object-fit: cover;" class="rounded"></td>
                                <td><strong><?= htmlspecialchars($b['title']) ?></strong></td>
                                <td>
                                    <a href="banners.php?edit=<?= $b['id'] ?>" class="btn btn-warning btn-sm text-white">Sửa</a>
                                    <a href="banners.php?delete=<?= $b['id'] ?>" class="btn btn-danger btn-sm" onclick="return confirm('Bạn có chắc chắn muốn xóa?')">Xóa</a>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr><td colspan="4" class="text-center">Chưa có banner nào.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</body>
</html>