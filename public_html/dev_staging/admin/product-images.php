<?php
/**
 * ZIN Fashion - Product Images Manager
 * Location: /public_html/dev_staging/admin/product-images.php
 */

session_start();
require_once '../includes/config.php';
require_once 'includes/translations.php';

// Check if admin is logged in
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: login.php');
    exit;
}

$currentLang = getAdminLanguage();
$pdo = getDBConnection();

// Get product ID
$productId = isset($_GET['id']) ? intval($_GET['id']) : 0;

if (!$productId) {
    header('Location: products.php');
    exit;
}

// Get product details
$sql = "SELECT product_id, product_name, sku FROM products WHERE product_id = :id";
$stmt = $pdo->prepare($sql);
$stmt->execute(['id' => $productId]);
$product = $stmt->fetch();

if (!$product) {
    header('Location: products.php');
    exit;
}

// Handle image upload
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['images'])) {
    $uploadDir = '../uploads/products/';
    
    // Create directory structure if it doesn't exist
    if (!file_exists($uploadDir)) {
        mkdir($uploadDir, 0755, true);
    }
    
    // Create product-specific folder
    $productDir = $uploadDir . $productId . '/';
    if (!file_exists($productDir)) {
        mkdir($productDir, 0755, true);
    }
    
    $uploadedCount = 0;
    $errors = [];
    
    // Handle multiple file uploads
    $files = $_FILES['images'];
    $fileCount = count($files['name']);
    
    for ($i = 0; $i < $fileCount; $i++) {
        if ($files['error'][$i] === UPLOAD_ERR_OK) {
            $tmpName = $files['tmp_name'][$i];
            $originalName = $files['name'][$i];
            $fileSize = $files['size'][$i];
            $fileType = $files['type'][$i];
            
            // Validate file type
            $allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/webp'];
            if (!in_array($fileType, $allowedTypes)) {
                $errors[] = "$originalName: Invalid file type";
                continue;
            }
            
            // Validate file size (5MB max)
            if ($fileSize > 5242880) {
                $errors[] = "$originalName: File too large (max 5MB)";
                continue;
            }
            
            // Generate unique filename
            $extension = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));
            $newFileName = uniqid() . '_' . time() . '.' . $extension;
            $uploadPath = $productDir . $newFileName;
            
            // Process and optimize image
            if (processAndSaveImage($tmpName, $uploadPath, $fileType)) {
                // Save to database
                $imageUrl = '/uploads/products/' . $productId . '/' . $newFileName;
                
                // Check if this is the first image
                $checkSql = "SELECT COUNT(*) FROM product_images WHERE product_id = :product_id";
                $checkStmt = $pdo->prepare($checkSql);
                $checkStmt->execute(['product_id' => $productId]);
                $imageCount = $checkStmt->fetchColumn();
                
                // Insert image record
                $insertSql = "INSERT INTO product_images (product_id, image_url, is_primary, display_order) 
                             VALUES (:product_id, :image_url, :is_primary, :display_order)";
                $insertStmt = $pdo->prepare($insertSql);
                $insertStmt->execute([
                    'product_id' => $productId,
                    'image_url' => $imageUrl,
                    'is_primary' => $imageCount == 0 ? 1 : 0, // First image is primary
                    'display_order' => $imageCount
                ]);
                
                $uploadedCount++;
                
                // Create thumbnail
                createThumbnail($uploadPath, $productDir . 'thumb_' . $newFileName, 300, 300);
            } else {
                $errors[] = "$originalName: Failed to process image";
            }
        }
    }
    
    $message = $uploadedCount > 0 ? "$uploadedCount image(s) uploaded successfully" : '';
    if (count($errors) > 0) {
        $message .= ' Errors: ' . implode(', ', $errors);
    }
}

// Handle primary image selection
if (isset($_POST['set_primary'])) {
    $imageId = intval($_POST['image_id']);
    
    // Reset all images to non-primary
    $resetSql = "UPDATE product_images SET is_primary = 0 WHERE product_id = :product_id";
    $resetStmt = $pdo->prepare($resetSql);
    $resetStmt->execute(['product_id' => $productId]);
    
    // Set new primary
    $setPrimarySql = "UPDATE product_images SET is_primary = 1 WHERE image_id = :image_id AND product_id = :product_id";
    $setPrimaryStmt = $pdo->prepare($setPrimarySql);
    $setPrimaryStmt->execute(['image_id' => $imageId, 'product_id' => $productId]);
    
    $message = "Primary image updated";
}

// Handle image deletion
if (isset($_GET['delete'])) {
    $imageId = intval($_GET['delete']);
    
    // Get image details
    $imageSql = "SELECT image_url FROM product_images WHERE image_id = :image_id AND product_id = :product_id";
    $imageStmt = $pdo->prepare($imageSql);
    $imageStmt->execute(['image_id' => $imageId, 'product_id' => $productId]);
    $image = $imageStmt->fetch();
    
    if ($image) {
        // Delete physical file
        $filePath = '../' . ltrim($image['image_url'], '/');
        if (file_exists($filePath)) {
            unlink($filePath);
        }
        
        // Delete thumbnail if exists
        $thumbPath = str_replace('/', '/thumb_', $filePath);
        if (file_exists($thumbPath)) {
            unlink($thumbPath);
        }
        
        // Delete from database
        $deleteSql = "DELETE FROM product_images WHERE image_id = :image_id";
        $deleteStmt = $pdo->prepare($deleteSql);
        $deleteStmt->execute(['image_id' => $imageId]);
        
        // Reorder remaining images
        reorderImages($pdo, $productId);
        
        header("Location: product-images.php?id=$productId&msg=deleted");
        exit;
    }
}

// Get all images for this product
$imagesSql = "SELECT * FROM product_images WHERE product_id = :product_id ORDER BY display_order, image_id";
$imagesStmt = $pdo->prepare($imagesSql);
$imagesStmt->execute(['product_id' => $productId]);
$images = $imagesStmt->fetchAll();

/**
 * Process and save image with optimization
 */
function processAndSaveImage($source, $destination, $mimeType) {
    // Create image resource based on type
    switch ($mimeType) {
        case 'image/jpeg':
        case 'image/jpg':
            $image = imagecreatefromjpeg($source);
            break;
        case 'image/png':
            $image = imagecreatefrompng($source);
            break;
        case 'image/webp':
            $image = imagecreatefromwebp($source);
            break;
        default:
            return false;
    }
    
    if (!$image) {
        return false;
    }
    
    // Get original dimensions
    $width = imagesx($image);
    $height = imagesy($image);
    
    // Resize if larger than 1500px
    $maxSize = 1500;
    if ($width > $maxSize || $height > $maxSize) {
        $ratio = min($maxSize / $width, $maxSize / $height);
        $newWidth = round($width * $ratio);
        $newHeight = round($height * $ratio);
        
        $resized = imagecreatetruecolor($newWidth, $newHeight);
        
        // Preserve transparency for PNG
        if ($mimeType == 'image/png') {
            imagecolortransparent($resized, imagecolorallocatealpha($resized, 0, 0, 0, 127));
            imagealphablending($resized, false);
            imagesavealpha($resized, true);
        }
        
        imagecopyresampled($resized, $image, 0, 0, 0, 0, $newWidth, $newHeight, $width, $height);
        imagedestroy($image);
        $image = $resized;
    }
    
    // Save optimized image
    $quality = 85; // Adjust quality for file size
    $success = false;
    
    switch ($mimeType) {
        case 'image/jpeg':
        case 'image/jpg':
            $success = imagejpeg($image, $destination, $quality);
            break;
        case 'image/png':
            $success = imagepng($image, $destination, 8); // PNG compression level 0-9
            break;
        case 'image/webp':
            $success = imagewebp($image, $destination, $quality);
            break;
    }
    
    imagedestroy($image);
    return $success;
}

/**
 * Create thumbnail
 */
function createThumbnail($source, $destination, $thumbWidth, $thumbHeight) {
    if (!file_exists($source)) {
        return false;
    }
    
    $info = getimagesize($source);
    $mimeType = $info['mime'];
    
    // Create image resource
    switch ($mimeType) {
        case 'image/jpeg':
        case 'image/jpg':
            $image = imagecreatefromjpeg($source);
            break;
        case 'image/png':
            $image = imagecreatefrompng($source);
            break;
        case 'image/webp':
            $image = imagecreatefromwebp($source);
            break;
        default:
            return false;
    }
    
    $width = imagesx($image);
    $height = imagesy($image);
    
    // Calculate aspect ratio
    $originalAspect = $width / $height;
    $thumbAspect = $thumbWidth / $thumbHeight;
    
    if ($originalAspect >= $thumbAspect) {
        $newHeight = $thumbHeight;
        $newWidth = $width / ($height / $thumbHeight);
    } else {
        $newWidth = $thumbWidth;
        $newHeight = $height / ($width / $thumbWidth);
    }
    
    $thumb = imagecreatetruecolor($thumbWidth, $thumbHeight);
    
    // Center the image
    imagecopyresampled(
        $thumb,
        $image,
        0 - ($newWidth - $thumbWidth) / 2,
        0 - ($newHeight - $thumbHeight) / 2,
        0, 0,
        $newWidth, $newHeight,
        $width, $height
    );
    
    // Save thumbnail
    imagejpeg($thumb, $destination, 90);
    
    imagedestroy($image);
    imagedestroy($thumb);
    
    return true;
}

/**
 * Reorder images after deletion
 */
function reorderImages($pdo, $productId) {
    $sql = "SELECT image_id FROM product_images WHERE product_id = :product_id ORDER BY display_order";
    $stmt = $pdo->prepare($sql);
    $stmt->execute(['product_id' => $productId]);
    $images = $stmt->fetchAll();
    
    $order = 0;
    foreach ($images as $image) {
        $updateSql = "UPDATE product_images SET display_order = :order WHERE image_id = :image_id";
        $updateStmt = $pdo->prepare($updateSql);
        $updateStmt->execute(['order' => $order++, 'image_id' => $image['image_id']]);
    }
}
?>
<!DOCTYPE html>
<html lang="<?= $currentLang ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Product Images - <?= htmlspecialchars($product['product_name']) ?> - ZIN Fashion Admin</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="stylesheet" href="css/admin.css">
    <style>
        .images-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
            padding: 20px;
            background: white;
            border-radius: 5px;
        }
        
        .upload-section {
            background: white;
            padding: 30px;
            border-radius: 5px;
            margin-bottom: 30px;
        }
        
        .upload-area {
            border: 2px dashed #ddd;
            border-radius: 5px;
            padding: 40px;
            text-align: center;
            transition: all 0.3s;
            cursor: pointer;
        }
        
        .upload-area:hover,
        .upload-area.dragover {
            border-color: #3498db;
            background: #f0f8ff;
        }
        
        .upload-icon {
            font-size: 48px;
            color: #ddd;
            margin-bottom: 20px;
        }
        
        .images-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
            gap: 20px;
            padding: 20px;
            background: white;
            border-radius: 5px;
        }
        
        .image-card {
            position: relative;
            border: 1px solid #ddd;
            border-radius: 5px;
            overflow: hidden;
            background: #f9f9f9;
        }
        
        .image-card.primary {
            border: 2px solid #27ae60;
        }
        
        .image-card.primary::before {
            content: 'PRIMARY';
            position: absolute;
            top: 10px;
            left: 10px;
            background: #27ae60;
            color: white;
            padding: 3px 10px;
            border-radius: 3px;
            font-size: 11px;
            font-weight: bold;
            z-index: 1;
        }
        
        .image-preview {
            width: 100%;
            height: 200px;
            object-fit: cover;
        }
        
        .image-actions {
            padding: 10px;
            display: flex;
            justify-content: space-between;
            gap: 5px;
        }
        
        .btn-sm {
            padding: 5px 10px;
            font-size: 12px;
        }
        
        .alert {
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 5px;
        }
        
        .alert-success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        
        .alert-warning {
            background: #fff3cd;
            color: #856404;
            border: 1px solid #ffeeba;
        }
        
        .upload-progress {
            display: none;
            margin-top: 20px;
        }
        
        .progress-bar {
            width: 100%;
            height: 20px;
            background: #f0f0f0;
            border-radius: 10px;
            overflow: hidden;
        }
        
        .progress-fill {
            height: 100%;
            background: #3498db;
            transition: width 0.3s;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 12px;
        }
        
        .image-order {
            position: absolute;
            top: 10px;
            right: 10px;
            background: rgba(0,0,0,0.7);
            color: white;
            padding: 5px 10px;
            border-radius: 3px;
            font-size: 12px;
        }
    </style>
</head>
<body>
    <!-- Sidebar -->
    <div class="sidebar" id="sidebar">
        <div class="logo">
            <img src="../assets/images/logo.svg" alt="ZIN Fashion">
            <h2><?= __('admin_panel') ?></h2>
        </div>
        <ul class="sidebar-menu">
            <li><a href="index.php"><i class="fas fa-home"></i> <?= __('dashboard') ?></a></li>
            <li><a href="products.php" class="active"><i class="fas fa-box"></i> <?= __('products') ?></a></li>
            <li><a href="categories.php"><i class="fas fa-list"></i> <?= __('categories') ?></a></li>
            <li><a href="orders.php"><i class="fas fa-shopping-cart"></i> <?= __('orders') ?></a></li>
            <li><a href="customers.php"><i class="fas fa-users"></i> <?= __('customers') ?></a></li>
            <li><a href="newsletter.php"><i class="fas fa-envelope"></i> <?= __('newsletter') ?></a></li>
            <li><a href="settings.php"><i class="fas fa-cog"></i> <?= __('settings') ?></a></li>
            <li><a href="change-password.php"><i class="fas fa-key"></i> <?= __('change_password') ?></a></li>
        </ul>
    </div>

    <!-- Main Content -->
    <div class="main-content">
        <!-- Header -->
        <div class="header">
            <div>
                <button class="menu-toggle" onclick="toggleSidebar()">
                    <i class="fas fa-bars"></i>
                </button>
                <h1>Product Images</h1>
            </div>
            <div class="header-right">
                <div class="user-info">
                    <i class="fas fa-user-circle"></i>
                    <span><?= __('welcome') ?>, <?= htmlspecialchars($_SESSION['admin_name']) ?></span>
                </div>
                <a href="logout.php" class="btn-logout">
                    <i class="fas fa-sign-out-alt"></i> <?= __('logout') ?>
                </a>
            </div>
        </div>

        <?php if (isset($message)): ?>
            <div class="alert alert-success">
                <?= htmlspecialchars($message) ?>
            </div>
        <?php endif; ?>

        <?php if (isset($_GET['msg']) && $_GET['msg'] == 'deleted'): ?>
            <div class="alert alert-warning">
                Image deleted successfully
            </div>
        <?php endif; ?>

        <!-- Product Info Header -->
        <div class="images-header">
            <div>
                <h2><?= htmlspecialchars($product['product_name']) ?></h2>
                <p>SKU: <?= htmlspecialchars($product['sku']) ?></p>
            </div>
            <a href="products.php" class="btn btn-secondary">
                <i class="fas fa-arrow-left"></i> Back to Products
            </a>
        </div>

        <!-- Upload Section -->
        <div class="upload-section">
            <h3>Upload Images</h3>
            <form method="POST" enctype="multipart/form-data" id="uploadForm">
                <div class="upload-area" onclick="document.getElementById('imageInput').click();" 
                     ondrop="handleDrop(event)" ondragover="handleDragOver(event)" ondragleave="handleDragLeave(event)">
                    <i class="fas fa-cloud-upload-alt upload-icon"></i>
                    <h4>Click to upload or drag and drop</h4>
                    <p>JPG, PNG, WebP (Max 5MB per file)</p>
                    <input type="file" id="imageInput" name="images[]" multiple accept="image/*" style="display: none;" onchange="handleFiles(this.files)">
                </div>
                <div class="upload-progress">
                    <div class="progress-bar">
                        <div class="progress-fill" id="progressFill">0%</div>
                    </div>
                </div>
                <div id="fileList" style="margin-top: 20px;"></div>
                <button type="submit" class="btn btn-primary" style="margin-top: 20px; display: none;" id="uploadBtn">
                    <i class="fas fa-upload"></i> Upload Images
                </button>
            </form>
        </div>

        <!-- Images Grid -->
        <div class="images-grid">
            <?php if (count($images) > 0): ?>
                <?php foreach ($images as $index => $image): ?>
                    <div class="image-card <?= $image['is_primary'] ? 'primary' : '' ?>">
                        <div class="image-order">#<?= $index + 1 ?></div>
                        <img src="<?= htmlspecialchars($image['image_url']) ?>" alt="Product Image" class="image-preview">
                        <div class="image-actions">
                            <?php if (!$image['is_primary']): ?>
                                <form method="POST" style="display: inline;">
                                    <input type="hidden" name="image_id" value="<?= $image['image_id'] ?>">
                                    <button type="submit" name="set_primary" class="btn btn-success btn-sm" title="Set as Primary">
                                        <i class="fas fa-star"></i>
                                    </button>
                                </form>
                            <?php endif; ?>
                            <a href="?id=<?= $productId ?>&delete=<?= $image['image_id'] ?>" 
                               onclick="return confirm('Are you sure you want to delete this image?')" 
                               class="btn btn-danger btn-sm" title="Delete">
                                <i class="fas fa-trash"></i>
                            </a>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div style="grid-column: 1 / -1; text-align: center; padding: 40px; color: #999;">
                    <i class="fas fa-images" style="font-size: 48px; margin-bottom: 20px;"></i>
                    <p>No images uploaded yet. Upload your first image above.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <script>
        function toggleSidebar() {
            const sidebar = document.getElementById('sidebar');
            const mainContent = document.querySelector('.main-content');
            sidebar.classList.toggle('active');
            mainContent.classList.toggle('expanded');
        }

        // Drag and drop functionality
        function handleDragOver(e) {
            e.preventDefault();
            e.currentTarget.classList.add('dragover');
        }

        function handleDragLeave(e) {
            e.currentTarget.classList.remove('dragover');
        }

        function handleDrop(e) {
            e.preventDefault();
            e.currentTarget.classList.remove('dragover');
            handleFiles(e.dataTransfer.files);
        }

        function handleFiles(files) {
            const fileList = document.getElementById('fileList');
            const uploadBtn = document.getElementById('uploadBtn');
            
            fileList.innerHTML = '<h4>Selected Files:</h4>';
            
            for (let i = 0; i < files.length; i++) {
                const file = files[i];
                const fileSize = (file.size / 1024 / 1024).toFixed(2); // Convert to MB
                
                const fileItem = document.createElement('div');
                fileItem.style.padding = '5px';
                fileItem.innerHTML = `
                    <i class="fas fa-image"></i> ${file.name} 
                    <span style="color: #999;">(${fileSize} MB)</span>
                `;
                
                fileList.appendChild(fileItem);
            }
            
            if (files.length > 0) {
                uploadBtn.style.display = 'inline-block';
            }
        }
    </script>
</body>
</html>
