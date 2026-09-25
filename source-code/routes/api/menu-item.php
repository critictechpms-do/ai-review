<?php

ob_clean();
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

$action = $_GET['action'] ?? '';

switch ($action) {
    case 'save':
        handleSaveMenuItem();
        break;
    case 'delete':
        handleDeleteMenuItem();
        break;
    case 'toggle':
        handleToggleAvailability();
        break;
    default:
        http_response_code(400);
        echo json_encode(['error' => 'Invalid action']);
        break;
}

function getProjectRoot() {
    return dirname(__FILE__, 3) . '/';
}

function handleToggleAvailability() {
    global $conn;
    
    $itemId = $_POST['id'] ?? null;
    $status = $_POST['status'] ?? null;
    
    if (!$itemId || $status === null) {
        http_response_code(400);
        echo json_encode(['error' => 'Item ID and status are required']);
        exit;
    }
    
    // Verify ownership through restaurant
    $stmt = $conn->prepare("
        SELECT mi.id FROM menu_items mi 
        JOIN restaurants r ON mi.restaurant_id = r.id 
        WHERE mi.id = ? AND r.user_id = ?
    ");
    $stmt->execute([$itemId, $_SESSION['user_id']]);
    
    if (!$stmt->fetch()) {
        http_response_code(403);
        echo json_encode(['error' => 'Unauthorized']);
        exit;
    }
    
    try {
        $stmt = $conn->prepare("UPDATE menu_items SET is_available = ? WHERE id = ?");
        $stmt->execute([$status, $itemId]);
        
        echo json_encode(['success' => true, 'status' => $status ? 'available' : 'unavailable']);
        
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
    }
}

function handleSaveMenuItem()
{
    global $conn;

    $itemId = $_POST['item_id'] ?? null;
    $restaurantId = $_POST['restaurant_id'] ?? null;
    $categoryId = $_POST['category_id'] ?: null;
    $name = trim($_POST['name'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $price = $_POST['price'] ?? 0;
    $salePrice = $_POST['sale_price'] ?: null;
    $removeImage = $_POST['remove_image'] ?? false;

    // Verify restaurant ownership
    $stmt = $conn->prepare("SELECT id FROM restaurants WHERE id = ? AND user_id = ?");
    $stmt->execute([$restaurantId, $_SESSION['user_id']]);
    if (!$stmt->fetch()) {
        http_response_code(403);
        echo json_encode(['error' => 'Unauthorized']);
        exit;
    }

    // Handle variants and addons
    $variants = [];
    $addons = [];

    if (isset($_POST['variants'])) {
        foreach ($_POST['variants'] as $variant) {
            if (!empty($variant['name'])) {
                $variants[] = [
                    'name' => $variant['name'],
                    'price' => floatval($variant['price'] ?? 0)
                ];
            }
        }
    }

    if (isset($_POST['addons'])) {
        foreach ($_POST['addons'] as $addon) {
            if (!empty($addon['name'])) {
                $addons[] = [
                    'name' => $addon['name'],
                    'price' => floatval($addon['price'] ?? 0)
                ];
            }
        }
    }

    $details = json_encode([
        'variants' => $variants,
        'addons' => $addons
    ]);

    // Handle image upload
    $imageUrl = null;
    $projectRoot = getProjectRoot();

    // Get existing image if editing
    if ($itemId && !$removeImage) {
        $stmt = $conn->prepare("SELECT image_url FROM menu_items WHERE id = ? AND restaurant_id = ?");
        $stmt->execute([$itemId, $restaurantId]);
        $existingImage = $stmt->fetchColumn();
        if ($existingImage) {
            $imageUrl = $existingImage;
        }
    }

    // Handle new image upload
    if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {

        $uploadDir = $projectRoot . 'uploads/';

        // Create uploads directory if not exists
        if (!is_dir($uploadDir)) {
            if (!mkdir($uploadDir, 0777, true)) {
                http_response_code(500);
                echo json_encode(['error' => 'Failed to create upload directory at: ' . $uploadDir]);
                exit;
            }
        }

        // Validate file type
        $allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
        $fileType = $_FILES['image']['type'];

        if (!in_array($fileType, $allowedTypes)) {
            http_response_code(400);
            echo json_encode(['error' => 'Invalid file type. Allowed: JPG, PNG, GIF, WebP']);
            exit;
        }

        // Validate file size (2MB max)
        if ($_FILES['image']['size'] > 2 * 1024 * 1024) {
            http_response_code(400);
            echo json_encode(['error' => 'File too large. Maximum size: 2MB']);
            exit;
        }

        // Generate unique filename
        $ext = strtolower(pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION));
        if (empty($ext)) {
            $ext = 'jpg';
        }

        $filename = uniqid('item_') . '.' . $ext;
        $uploadPath = $uploadDir . $filename;

        if (move_uploaded_file($_FILES['image']['tmp_name'], $uploadPath)) {
            // Delete old image if exists
            if ($imageUrl) {
                $oldImagePath = $projectRoot . $imageUrl;
                if (file_exists($oldImagePath)) {
                    @unlink($oldImagePath);
                }
            }
            // Store relative path from project root
            $imageUrl = 'uploads/' . $filename;
        } else {
            http_response_code(500);
            echo json_encode(['error' => 'Failed to upload file to: ' . $uploadPath]);
            exit;
        }
    } elseif ($removeImage) {
        // Remove existing image file if requested
        if ($imageUrl) {
            $imagePath = $projectRoot . $imageUrl;
            if (file_exists($imagePath)) {
                @unlink($imagePath);
            }
        }
        $imageUrl = null;
    }

    try {
        if ($itemId) {
            // Update existing item
            $stmt = $conn->prepare("
                UPDATE menu_items 
                SET category_id = ?, name = ?, description = ?, image_url = ?, 
                    price = ?, sale_price = ?, details = ?
                WHERE id = ? AND restaurant_id = ?
            ");
            $stmt->execute([
                $categoryId,
                $name,
                $description,
                $imageUrl,
                $price,
                $salePrice,
                $details,
                $itemId,
                $restaurantId
            ]);
        } else {
            // Insert new item
            $stmt = $conn->prepare("
                INSERT INTO menu_items (restaurant_id, category_id, name, description, image_url, price, sale_price, details)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?)
            ");
            $stmt->execute([
                $restaurantId,
                $categoryId,
                $name,
                $description,
                $imageUrl,
                $price,
                $salePrice,
                $details
            ]);
        }

        echo json_encode(['success' => true, 'image_url' => url('/' . $imageUrl)]);
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
    }
}

function handleDeleteMenuItem()
{
    global $conn;

    $itemId = $_POST['id'] ?? null;

    // Verify ownership through restaurant
    $stmt = $conn->prepare("
        SELECT mi.image_url FROM menu_items mi 
        JOIN restaurants r ON mi.restaurant_id = r.id 
        WHERE mi.id = ? AND r.user_id = ?
    ");
    $stmt->execute([$itemId, $_SESSION['user_id']]);
    $item = $stmt->fetch();

    if (!$item) {
        http_response_code(403);
        echo json_encode(['error' => 'Unauthorized']);
        exit;
    }

    // Delete image file if exists
    if ($item['image_url']) {
        $projectRoot = getProjectRoot();
        $imagePath = $projectRoot . $item['image_url'];
        if (file_exists($imagePath)) {
            @unlink($imagePath);
        }
    }

    $stmt = $conn->prepare("DELETE FROM menu_items WHERE id = ?");
    $stmt->execute([$itemId]);

    echo json_encode(['success' => true]);
}
?>