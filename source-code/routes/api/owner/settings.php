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
        handleSaveSettings();
        break;
    default:
        http_response_code(400);
        echo json_encode(['error' => 'Invalid action']);
        break;
}

function handleSaveSettings() {
    global $conn;
    
    $whatsapp = trim($_POST['whatsapp'] ?? '');
    
    // Validate WhatsApp number
    if (empty($whatsapp)) {
        http_response_code(400);
        echo json_encode(['error' => 'WhatsApp number is required']);
        exit;
    }
    
    // Remove any non-numeric characters except +
    $whatsapp = preg_replace('/[^0-9+]/', '', $whatsapp);
    
    // Basic validation for WhatsApp number
    if (strlen($whatsapp) < 10) {
        http_response_code(400);
        echo json_encode(['error' => 'Please enter a valid WhatsApp number']);
        exit;
    }
    
    try {
        // Check if settings row exists
        $stmt = $conn->query("SELECT whatsapp FROM platform_settings LIMIT 1");
        $existingSettings = $stmt->fetch();
        
        if ($existingSettings) {
            // Update existing settings
            $stmt = $conn->prepare("UPDATE platform_settings SET whatsapp = ?");
            $stmt->execute([$whatsapp]);
        } else {
            // Insert new settings row
            $stmt = $conn->prepare("INSERT INTO platform_settings (whatsapp, details) VALUES (?, '{}')");
            $stmt->execute([$whatsapp]);
        }
        
        echo json_encode(['success' => true, 'message' => 'Settings saved successfully']);
        
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
    }
}
?>