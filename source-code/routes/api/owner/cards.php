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
    case 'updateMembership':
        handleUpdateMembership();
        break;
    case 'delete':
        handleDeleteCard();
        break;
    default:
        http_response_code(400);
        echo json_encode(['error' => 'Invalid action']);
        break;
}

function handleUpdateMembership() {
    global $conn;
    
    $cardId = $_POST['card_id'] ?? null;
    $planId = $_POST['plan_id'] ?? null;
    $membership = $_POST['membership'] ?? null;
    $expiryDate = $_POST['expiry_date'] ?? null;
    
    if (!$cardId || !$membership || !$expiryDate) {
        http_response_code(400);
        echo json_encode(['error' => 'All fields are required']);
        exit;
    }
    
    try {
        // Verify card exists
        $stmt = $conn->prepare("SELECT id FROM review_cards WHERE id = ?");
        $stmt->execute([$cardId]);
        if (!$stmt->fetch()) {
            http_response_code(404);
            echo json_encode(['error' => 'Card not found']);
            exit;
        }
        
        $stmt = $conn->prepare("UPDATE review_cards SET membership = ?, expiry_date = ? WHERE id = ?");
        $stmt->execute([$membership, $expiryDate, $cardId]);
        
        echo json_encode(['success' => true, 'message' => 'Membership updated successfully']);
        
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
    }
}

function handleDeleteCard() {
    global $conn;
    
    $cardId = $_POST['id'] ?? null;
    
    if (!$cardId) {
        http_response_code(400);
        echo json_encode(['error' => 'Card ID is required']);
        exit;
    }
    
    try {
        // Get card details to delete associated images (logos)
        $stmt = $conn->prepare("SELECT details FROM review_cards WHERE id = ?");
        $stmt->execute([$cardId]);
        $card = $stmt->fetch();
        
        if ($card) {
            $details = json_decode($card['details'] ?? '{}', true);
            if (!empty($details['logo'])) {
                $projectRoot = dirname(dirname(dirname(__FILE__))) . '/';
                $imagePath = $projectRoot . $details['logo'];
                if (file_exists($imagePath)) {
                    @unlink($imagePath);
                }
            }
        }
        
        // Delete card (cascading will delete reviews and feedbacks)
        $stmt = $conn->prepare("DELETE FROM review_cards WHERE id = ?");
        $stmt->execute([$cardId]);
        
        echo json_encode(['success' => true, 'message' => 'Card deleted successfully']);
        
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
    }
}
?>