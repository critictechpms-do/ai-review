<?php
ob_clean();
header('Content-Type: application/json');

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

$action = $_GET['action'] ?? '';

switch ($action) {
    case 'get':
        handleGetFeedback();
        break;
    case 'delete':
        handleDeleteFeedback();
        break;
    case 'markRead':
        handleMarkRead();
        break;
    case 'markResolved':
        handleMarkResolved();
        break;
    case 'list':
        handleListFeedbacks();
        break;
    default:
        http_response_code(400);
        echo json_encode(['error' => 'Invalid action']);
        break;
}

function handleGetFeedback() {
    global $conn;
    
    $feedbackId = $_GET['feedback_id'] ?? null;
    if (!$feedbackId) {
        http_response_code(400);
        echo json_encode(['error' => 'Feedback ID required']);
        exit;
    }
    
    try {
        $stmt = $conn->prepare("
            SELECT f.*, rc.name as card_name, rc.slug 
            FROM feedbacks f 
            JOIN review_cards rc ON f.card_id = rc.id 
            WHERE f.id = ? AND rc.user_id = ?
        ");
        $stmt->execute([$feedbackId, $_SESSION['user_id']]);
        $feedback = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$feedback) {
            http_response_code(403);
            echo json_encode(['error' => 'Unauthorized or feedback not found']);
            exit;
        }
        
        echo json_encode(['success' => true, 'data' => $feedback]);
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
    }
}

function handleDeleteFeedback() {
    global $conn;
    
    $feedbackId = $_POST['feedback_id'] ?? null;
    if (!$feedbackId) {
        http_response_code(400);
        echo json_encode(['error' => 'Feedback ID required']);
        exit;
    }
    
    try {
        // Verify ownership
        $stmt = $conn->prepare("
            SELECT f.id 
            FROM feedbacks f 
            JOIN review_cards rc ON f.card_id = rc.id 
            WHERE f.id = ? AND rc.user_id = ?
        ");
        $stmt->execute([$feedbackId, $_SESSION['user_id']]);
        if (!$stmt->fetch()) {
            http_response_code(403);
            echo json_encode(['error' => 'Unauthorized']);
            exit;
        }
        
        $stmt = $conn->prepare("DELETE FROM feedbacks WHERE id = ?");
        $stmt->execute([$feedbackId]);
        
        echo json_encode(['success' => true, 'message' => 'Feedback deleted successfully']);
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
    }
}

function handleMarkRead() {
    global $conn;
    
    $feedbackId = $_POST['feedback_id'] ?? null;
    if (!$feedbackId) {
        http_response_code(400);
        echo json_encode(['error' => 'Feedback ID required']);
        exit;
    }
    
    try {
        // Verify ownership
        $stmt = $conn->prepare("
            SELECT f.id 
            FROM feedbacks f 
            JOIN review_cards rc ON f.card_id = rc.id 
            WHERE f.id = ? AND rc.user_id = ?
        ");
        $stmt->execute([$feedbackId, $_SESSION['user_id']]);
        if (!$stmt->fetch()) {
            http_response_code(403);
            echo json_encode(['error' => 'Unauthorized']);
            exit;
        }
        
        $stmt = $conn->prepare("UPDATE feedbacks SET status = 'read' WHERE id = ?");
        $stmt->execute([$feedbackId]);
        
        echo json_encode(['success' => true, 'message' => 'Feedback marked as read']);
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
    }
}

function handleMarkResolved() {
    global $conn;
    
    $feedbackId = $_POST['feedback_id'] ?? null;
    if (!$feedbackId) {
        http_response_code(400);
        echo json_encode(['error' => 'Feedback ID required']);
        exit;
    }
    
    try {
        // Verify ownership
        $stmt = $conn->prepare("
            SELECT f.id 
            FROM feedbacks f 
            JOIN review_cards rc ON f.card_id = rc.id 
            WHERE f.id = ? AND rc.user_id = ?
        ");
        $stmt->execute([$feedbackId, $_SESSION['user_id']]);
        if (!$stmt->fetch()) {
            http_response_code(403);
            echo json_encode(['error' => 'Unauthorized']);
            exit;
        }
        
        $stmt = $conn->prepare("UPDATE feedbacks SET status = 'resolved' WHERE id = ?");
        $stmt->execute([$feedbackId]);
        
        echo json_encode(['success' => true, 'message' => 'Feedback marked as resolved']);
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
    }
}

function handleListFeedbacks() {
    global $conn;
    
    $slug = $_GET['slug'] ?? null;
    $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 20;
    $offset = isset($_GET['offset']) ? (int)$_GET['offset'] : 0;
    $status = $_GET['status'] ?? 'all'; // all, new, read, resolved
    
    if (!$slug) {
        http_response_code(400);
        echo json_encode(['error' => 'Slug required']);
        exit;
    }
    
    try {
        // Get card ID
        $stmt = $conn->prepare("SELECT id FROM review_cards WHERE slug = ? AND user_id = ?");
        $stmt->execute([$slug, $_SESSION['user_id']]);
        $card = $stmt->fetch();
        
        if (!$card) {
            http_response_code(403);
            echo json_encode(['error' => 'Unauthorized']);
            exit;
        }
        
        $whereClause = "card_id = ?";
        $params = [$card['id']];
        
        if ($status !== 'all') {
            $whereClause .= " AND status = ?";
            $params[] = $status;
        }
        
        // Get total count
        $countStmt = $conn->prepare("SELECT COUNT(*) as total FROM feedbacks WHERE $whereClause");
        $countStmt->execute($params);
        $total = $countStmt->fetch()['total'];
        
        // Get feedbacks
        $stmt = $conn->prepare("
            SELECT * FROM feedbacks 
            WHERE $whereClause 
            ORDER BY 
                CASE status 
                    WHEN 'new' THEN 1 
                    WHEN 'read' THEN 2 
                    WHEN 'resolved' THEN 3 
                END,
                created_at DESC 
            LIMIT ? OFFSET ?
        ");
        $stmt->execute(array_merge($params, [$limit, $offset]));
        $feedbacks = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo json_encode([
            'success' => true,
            'data' => $feedbacks,
            'pagination' => [
                'total' => (int)$total,
                'limit' => $limit,
                'offset' => $offset
            ]
        ]);
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
    }
}
?>