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
        handleSaveCategory();
        break;
    case 'update':
        handleUpdateCategory();
        break;
    case 'delete':
        handleDeleteCategory();
        break;
    case 'reorder':
        handleReorderCategories();
        break;
    default:
        http_response_code(400);
        echo json_encode(['error' => 'Invalid action']);
        break;
}

function handleSaveCategory() {
    global $conn;
    
    $restaurantId = $_POST['restaurant_id'] ?? null;
    $name = trim($_POST['name'] ?? '');
    $categoryId = $_POST['id'] ?? null;
    $sortOrder = $_POST['sort_order'] ?? 0;
    
    if (empty($name)) {
        http_response_code(400);
        echo json_encode(['error' => 'Category name is required']);
        exit;
    }
    
    // Verify restaurant ownership
    $stmt = $conn->prepare("SELECT id FROM restaurants WHERE id = ? AND user_id = ?");
    $stmt->execute([$restaurantId, $_SESSION['user_id']]);
    if (!$stmt->fetch()) {
        http_response_code(403);
        echo json_encode(['error' => 'Unauthorized']);
        exit;
    }
    
    try {
        if ($categoryId) {
            // Update existing category
            $stmt = $conn->prepare("UPDATE categories SET name = ?, sort_order = ? WHERE id = ? AND restaurant_id = ?");
            $stmt->execute([$name, $sortOrder, $categoryId, $restaurantId]);
            echo json_encode(['success' => true, 'id' => $categoryId]);
        } else {
            // Check if category with same name exists
            $stmt = $conn->prepare("SELECT id FROM categories WHERE restaurant_id = ? AND name = ?");
            $stmt->execute([$restaurantId, $name]);
            if ($existingCategory = $stmt->fetch()) {
                echo json_encode(['success' => true, 'id' => $existingCategory['id'], 'message' => 'Category already exists']);
                exit;
            }
            
            // Insert new category
            $stmt = $conn->prepare("INSERT INTO categories (restaurant_id, name, sort_order) VALUES (?, ?, ?)");
            $stmt->execute([$restaurantId, $name, $sortOrder]);
            $newId = $conn->lastInsertId();
            echo json_encode(['success' => true, 'id' => $newId]);
        }
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
    }
}

function handleReorderCategories() {
    global $conn;
    
    $categories = $_POST['categories'] ?? [];
    
    if (empty($categories)) {
        http_response_code(400);
        echo json_encode(['error' => 'No categories provided']);
        exit;
    }
    
    try {
        $conn->beginTransaction();
        
        foreach ($categories as $category) {
            $categoryId = $category['id'] ?? null;
            $sortOrder = $category['sort_order'] ?? 0;
            
            if ($categoryId) {
                // Verify ownership
                $stmt = $conn->prepare("
                    SELECT c.id FROM categories c 
                    JOIN restaurants r ON c.restaurant_id = r.id 
                    WHERE c.id = ? AND r.user_id = ?
                ");
                $stmt->execute([$categoryId, $_SESSION['user_id']]);
                
                if ($stmt->fetch()) {
                    $stmt = $conn->prepare("UPDATE categories SET sort_order = ? WHERE id = ?");
                    $stmt->execute([$sortOrder, $categoryId]);
                }
            }
        }
        
        $conn->commit();
        echo json_encode(['success' => true, 'message' => 'Order saved successfully']);
        
    } catch (PDOException $e) {
        $conn->rollBack();
        http_response_code(500);
        echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
    }
}

function handleUpdateCategory() {
    global $conn;
    
    $categoryId = $_POST['id'] ?? null;
    $name = trim($_POST['name'] ?? '');
    $sortOrder = $_POST['sort_order'] ?? null;
    
    if (empty($categoryId) || empty($name)) {
        http_response_code(400);
        echo json_encode(['error' => 'Category ID and name are required']);
        exit;
    }
    
    $stmt = $conn->prepare("
        SELECT c.restaurant_id FROM categories c 
        JOIN restaurants r ON c.restaurant_id = r.id 
        WHERE c.id = ? AND r.user_id = ?
    ");
    $stmt->execute([$categoryId, $_SESSION['user_id']]);
    $category = $stmt->fetch();
    
    if (!$category) {
        http_response_code(403);
        echo json_encode(['error' => 'Unauthorized']);
        exit;
    }
    
    try {
        if ($sortOrder !== null) {
            $stmt = $conn->prepare("UPDATE categories SET name = ?, sort_order = ? WHERE id = ? AND restaurant_id = ?");
            $stmt->execute([$name, $sortOrder, $categoryId, $category['restaurant_id']]);
        } else {
            $stmt = $conn->prepare("UPDATE categories SET name = ? WHERE id = ? AND restaurant_id = ?");
            $stmt->execute([$name, $categoryId, $category['restaurant_id']]);
        }
        
        echo json_encode(['success' => true, 'id' => $categoryId]);
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
    }
}

function handleDeleteCategory() {
    global $conn;
    
    $categoryId = $_POST['id'] ?? null;
    
    if (empty($categoryId)) {
        http_response_code(400);
        echo json_encode(['error' => 'Category ID is required']);
        exit;
    }
    
    $stmt = $conn->prepare("
        SELECT c.id FROM categories c 
        JOIN restaurants r ON c.restaurant_id = r.id 
        WHERE c.id = ? AND r.user_id = ?
    ");
    $stmt->execute([$categoryId, $_SESSION['user_id']]);
    if (!$stmt->fetch()) {
        http_response_code(403);
        echo json_encode(['error' => 'Unauthorized']);
        exit;
    }
    
    try {
        $stmt = $conn->prepare("UPDATE menu_items SET category_id = NULL WHERE category_id = ?");
        $stmt->execute([$categoryId]);
        
        $stmt = $conn->prepare("DELETE FROM categories WHERE id = ?");
        $stmt->execute([$categoryId]);
        
        echo json_encode(['success' => true]);
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
    }
}
?>