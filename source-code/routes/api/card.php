<?php

// Add this function at the very top after the opening PHP tag
function getProjectRoot() {
    return dirname(__FILE__, 3) . '/';
}

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
    case 'create':
        handleCreateCard();
        break;
    case 'update':
        handleUpdateCard();
        break;
    case 'updateSettings':
        handleUpdateSettings();
        break;
    case 'delete':
        handleDeleteCard();
        break;
    case 'reactivateTrial':
        handleReactivateTrial();
        break;
    default:
        http_response_code(400);
        echo json_encode(['error' => 'Invalid action']);
        break;
}


function handleCreateCard()
{
    global $conn;

    $user_id = $_SESSION['user_id'];
    $name = trim($_POST['name'] ?? '');

    // Validation
    if (empty($name)) {
        $_SESSION['onboard_error'] = 'Business name is required.';
        header('Location: ' . url('/onboard'));
        exit;
    }

    if (strlen($name) > 255) {
        $_SESSION['onboard_error'] = 'Business name must be less than 255 characters.';
        header('Location: ' . url('/onboard'));
        exit;
    }

    // Generate slug from name
    $slug = strtolower($name);
    $slug = preg_replace('/[^a-z0-9\s-]/', '', $slug);
    $slug = preg_replace('/\s+/', '-', $slug);
    $slug = preg_replace('/-+/', '-', $slug);
    $slug = trim($slug, '-');

    // If slug is empty after sanitization, use a fallback
    if (empty($slug)) {
        $slug = 'business-' . time();
    }

    // Check if slug exists and append random digits if needed
    $originalSlug = $slug;
    $attempts = 0;

    while ($attempts < 100) {
        $stmt = $conn->prepare("SELECT id FROM review_cards WHERE slug = ?");
        $stmt->execute([$slug]);
        
        if (!$stmt->fetch()) {
            break; // Slug is unique
        }

        // Slug exists, add 2 random digits
        $randomDigits = str_pad(rand(0, 99), 2, '0', STR_PAD_LEFT);
        $slug = $originalSlug . $randomDigits;
        $attempts++;
    }

    if ($attempts >= 100) {
        $_SESSION['onboard_error'] = 'Unable to generate unique URL. Please try a different name.';
        header('Location: ' . url('/onboard'));
        exit;
    }

    // Calculate trial expiry date (3 days from now)
    $trialExpiryDate = date('Y-m-d H:i:s', strtotime('+3 days'));

    // Insert review card with 3-day trial
    try {
        $stmt = $conn->prepare("
            INSERT INTO review_cards (user_id, name, slug, membership, expiry_date) 
            VALUES (?, ?, ?, 'trial', ?)
        ");
        $stmt->execute([$user_id, $name, $slug, $trialExpiryDate]);

        $card_id = $conn->lastInsertId();

        // Set success message and redirect
        $_SESSION['onboard_success'] = 'Review card created successfully! You have a 3-day free trial.';
        header('Location: ' . url('/dashboard?card=' . $card_id));
        exit;
        
    } catch (PDOException $e) {
        $_SESSION['onboard_error'] = 'An error occurred. Please try again.';
        header('Location: ' . url('/onboard'));
        exit;
    }
}

function handleUpdateCard()
{
    global $conn;

    $cardId = $_POST['card_id'] ?? null;
    $name = trim($_POST['name'] ?? '');
    $details = $_POST['details'] ?? null;

    if (empty($name)) {
        http_response_code(400);
        echo json_encode(['error' => 'Card name is required']);
        exit;
    }

    // Verify ownership
    $stmt = $conn->prepare("SELECT * FROM review_cards WHERE id = ? AND user_id = ?");
    $stmt->execute([$cardId, $_SESSION['user_id']]);
    $card = $stmt->fetch();

    if (!$card) {
        http_response_code(403);
        echo json_encode(['error' => 'Unauthorized']);
        exit;
    }

    // Build details JSON if provided
    $detailsJson = $card['details'];
    if ($details !== null) {
        $decodedDetails = json_decode($details, true);
        if (json_last_error() === JSON_ERROR_NONE) {
            $detailsJson = $details;
        }
    }

    try {
        $stmt = $conn->prepare("UPDATE review_cards SET name = ?, details = ? WHERE id = ?");
        $stmt->execute([$name, $detailsJson, $cardId]);

        echo json_encode(['success' => true, 'message' => 'Card updated successfully']);
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
    }
}

function handleDeleteCard()
{
    global $conn;

    $slug = $_POST['slug'] ?? '';

    if (empty($slug)) {
        http_response_code(400);
        echo json_encode(['error' => 'Card slug is required']);
        exit;
    }

    // Verify ownership
    $stmt = $conn->prepare("SELECT * FROM review_cards WHERE slug = ? AND user_id = ?");
    $stmt->execute([$slug, $_SESSION['user_id']]);
    $card = $stmt->fetch();

    if (!$card) {
        http_response_code(403);
        echo json_encode(['error' => 'Unauthorized']);
        exit;
    }

    try {
        // Delete card (cascading will delete reviews and feedbacks)
        $stmt = $conn->prepare("DELETE FROM review_cards WHERE id = ? AND user_id = ?");
        $stmt->execute([$card['id'], $_SESSION['user_id']]);

        echo json_encode(['success' => true, 'message' => 'Card deleted successfully']);
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
    }
}

function handleReactivateTrial()
{
    global $conn;

    $cardId = $_POST['card_id'] ?? null;

    if (!$cardId) {
        http_response_code(400);
        echo json_encode(['error' => 'Card ID is required']);
        exit;
    }

    // Verify ownership
    $stmt = $conn->prepare("SELECT id FROM review_cards WHERE id = ? AND user_id = ?");
    $stmt->execute([$cardId, $_SESSION['user_id']]);
    if (!$stmt->fetch()) {
        http_response_code(403);
        echo json_encode(['error' => 'Unauthorized']);
        exit;
    }

    // Calculate new trial expiry (3 days from now)
    $trialExpiryDate = date('Y-m-d H:i:s', strtotime('+3 days'));

    try {
        $stmt = $conn->prepare("UPDATE review_cards SET membership = 'trial', expiry_date = ? WHERE id = ?");
        $stmt->execute([$trialExpiryDate, $cardId]);

        echo json_encode(['success' => true, 'message' => 'Trial reactivated successfully']);
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
    }
}


function handleUpdateSettings()
{
    global $conn;
    
    $cardId = $_POST['card_id'] ?? null;
    $slug = $_POST['slug'] ?? '';
    $name = trim($_POST['name'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $whatsapp = trim($_POST['whatsapp'] ?? '');
    $facebook = trim($_POST['facebook'] ?? '');
    $instagram = trim($_POST['instagram'] ?? '');
    $website = trim($_POST['website'] ?? '');
    $google_review_url = trim($_POST['google_review_url'] ?? '');
    $theme = $_POST['theme'] ?? 'purple';
    $enable_keyword_selection = isset($_POST['enable_keyword_selection']) && $_POST['enable_keyword_selection'] == 1;
    $business_keywords = array_map('trim', explode(',', $_POST['business_keywords'] ?? ''));
    $removeLogo = isset($_POST['remove_logo']);
    
    // Verify ownership
    $stmt = $conn->prepare("SELECT * FROM review_cards WHERE id = ? AND user_id = ?");
    $stmt->execute([$cardId, $_SESSION['user_id']]);
    $card = $stmt->fetch();
    
    if (!$card) {
        $_SESSION['settings_error'] = 'Unauthorized access.';
        header('Location: ' . url('/edit/' . $slug . '/settings'));
        exit;
    }
    
    // Parse existing details
    $details = json_decode($card['details'] ?? '{}', true);
    
    // Handle logo upload
    $projectRoot = getProjectRoot();
    $logoUrl = $details['logo'] ?? null;
    
    if ($removeLogo) {
        // Delete existing logo file
        if ($logoUrl) {
            $imagePath = $projectRoot . $logoUrl;
            if (file_exists($imagePath)) {
                @unlink($imagePath);
            }
        }
        $logoUrl = null;
    }
    
    // Handle new logo upload
    if (isset($_FILES['logo']) && $_FILES['logo']['error'] === UPLOAD_ERR_OK) {
        $uploadDir = $projectRoot . 'uploads/';
        
        // Create uploads directory if not exists
        if (!is_dir($uploadDir)) {
            if (!mkdir($uploadDir, 0777, true)) {
                $_SESSION['settings_error'] = 'Failed to create upload directory.';
                header('Location: ' . url('/edit/' . $slug . '/settings'));
                exit;
            }
        }
        
        // Validate file type
        $allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp', 'image/svg+xml'];
        $fileType = $_FILES['logo']['type'];
        
        if (!in_array($fileType, $allowedTypes)) {
            $_SESSION['settings_error'] = 'Invalid file type. Allowed: JPG, PNG, GIF, WebP, SVG';
            header('Location: ' . url('/edit/' . $slug . '/settings'));
            exit;
        }
        
        // Validate file size (2MB max)
        if ($_FILES['logo']['size'] > 2 * 1024 * 1024) {
            $_SESSION['settings_error'] = 'File too large. Maximum size: 2MB';
            header('Location: ' . url('/edit/' . $slug . '/settings'));
            exit;
        }
        
        // Generate unique filename
        $ext = strtolower(pathinfo($_FILES['logo']['name'], PATHINFO_EXTENSION));
        if (empty($ext)) {
            $ext = 'jpg';
        }
        
        $filename = uniqid('logo_') . '.' . $ext;
        $uploadPath = $uploadDir . $filename;
        
        if (move_uploaded_file($_FILES['logo']['tmp_name'], $uploadPath)) {
            // Delete old logo if exists
            if ($logoUrl) {
                $oldImagePath = $projectRoot . $logoUrl;
                if (file_exists($oldImagePath)) {
                    @unlink($oldImagePath);
                }
            }
            $logoUrl = 'uploads/' . $filename;
        } else {
            $_SESSION['settings_error'] = 'Failed to upload logo.';
            header('Location: ' . url('/edit/' . $slug . '/settings'));
            exit;
        }
    }
    
    // Update details with all fields
    $details['logo'] = $logoUrl;
    $details['description'] = $description;
    $details['whatsapp'] = $whatsapp;
    $details['facebook'] = $facebook;
    $details['instagram'] = $instagram;
    $details['website'] = $website;
    $details['google_review_url'] = $google_review_url;
    $details['theme'] = $theme;
    $details['enable_keyword_selection'] = $enable_keyword_selection;
    $details['business_keywords'] = array_filter($business_keywords);
    
    // If keyword selection is disabled, reset selected keywords
    if (!$enable_keyword_selection) {
        $details['business_keywords'] = array_filter($business_keywords);
    }
    
    // Update card
    try {
        $stmt = $conn->prepare("
            UPDATE review_cards 
            SET name = ?, details = ? 
            WHERE id = ? AND user_id = ?
        ");
        $stmt->execute([
            $name,
            json_encode($details),
            $cardId,
            $_SESSION['user_id']
        ]);
        
        $_SESSION['settings_success'] = 'Settings updated successfully!';
        header('Location: ' . url('/edit/' . $slug . '/settings'));
        exit;
        
    } catch (PDOException $e) {
        $_SESSION['settings_error'] = 'Database error: ' . $e->getMessage();
        header('Location: ' . url('/edit/' . $slug . '/settings'));
        exit;
    }
}