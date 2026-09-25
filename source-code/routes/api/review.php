<?php
ob_clean();
header('Content-Type: application/json');

global $conn;

$action = $_GET['action'] ?? '';

switch ($action) {
    case 'generate':
        handleGenerateAIReviews();
        break;
    case 'submit':
        handleSubmitReview();
        break;
    case 'feedback':
        handleSubmitFeedback();
        break;
    case 'get':
        handleGetReview();
        break;
    case 'delete':
        handleDeleteReview();
        break;
    case 'list':
        handleListReviews();
        break;
    case 'stats':
        handleGetStats();
        break;
    default:
        http_response_code(400);
        echo json_encode(['error' => 'Invalid action']);
        break;
}

function handleGenerateAIReviews()
{
    global $conn;

    $slug = $_POST['slug'] ?? '';
    $customerName = trim($_POST['customer_name'] ?? '');

    if (empty($slug)) {
        http_response_code(400);
        echo json_encode(['error' => 'Slug is required']);
        exit;
    }

    try {
        // Get card details
        $stmt = $conn->prepare("SELECT * FROM review_cards WHERE slug = ?");
        $stmt->execute([$slug]);
        $card = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$card) {
            http_response_code(404);
            echo json_encode(['error' => 'Review card not found']);
            exit;
        }

        // Get business details
        $details = json_decode($card['details'] ?? '{}', true);
        $keywords = $details['business_keywords'] ?? ['professional', 'quality', 'excellent service'];
        $googleReviewUrl = $details['google_review_url'] ?? 'https://www.google.com/search?q=review';

        // Generate AI reviews
        $reviews = generateAIReviews($card['name'], $keywords, $customerName);

        echo json_encode([
            'success' => true,
            'reviews' => $reviews,
            'google_review_url' => $googleReviewUrl
        ]);
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['error' => $e->getMessage()]);
    }
}

function handleSubmitReview()
{
    global $conn;

    $slug = $_POST['slug'] ?? '';
    $rating = (int)$_POST['rating'];
    $customerName = trim($_POST['customer_name'] ?? 'Anonymous');
    $body = trim($_POST['review_body'] ?? '');

    if (empty($slug)) {
        http_response_code(400);
        echo json_encode(['error' => 'Slug is required']);
        exit;
    }

    try {
        // Get card
        $stmt = $conn->prepare("SELECT id FROM review_cards WHERE slug = ?");
        $stmt->execute([$slug]);
        $card = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$card) {
            http_response_code(404);
            echo json_encode(['error' => 'Review card not found']);
            exit;
        }

        // Save review
        $stmt = $conn->prepare("
            INSERT INTO reviews (card_id, customer_name, rating, body, details) 
            VALUES (?, ?, ?, ?, ?)
        ");
        $detailsJson = json_encode(['ai_generated' => true]);
        $stmt->execute([$card['id'], $customerName, $rating, $body, $detailsJson]);

        echo json_encode([
            'success' => true,
            'message' => 'Review saved successfully'
        ]);
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
    }
}

function handleSubmitFeedback()
{
    global $conn;

    $slug = $_POST['slug'] ?? '';
    $rating = (int)$_POST['rating'];
    $customerName = trim($_POST['customer_name'] ?? 'Anonymous');
    $phone = trim($_POST['phone'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $message = trim($_POST['message'] ?? '');

    if (empty($slug)) {
        http_response_code(400);
        echo json_encode(['error' => 'Slug is required']);
        exit;
    }

    try {
        // Get card
        $stmt = $conn->prepare("SELECT id FROM review_cards WHERE slug = ?");
        $stmt->execute([$slug]);
        $card = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$card) {
            http_response_code(404);
            echo json_encode(['error' => 'Review card not found']);
            exit;
        }

        // Save feedback
        $stmt = $conn->prepare("
            INSERT INTO feedbacks (card_id, customer_name, phone, email, message, rating) 
            VALUES (?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([$card['id'], $customerName, $phone, $email, $message, $rating]);

        echo json_encode([
            'success' => true,
            'message' => 'Feedback submitted successfully'
        ]);
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
    }
}

function generateAIReviews($businessName, $keywords, $customerName = '')
{
    $apiKey = env("GROQ_API_KEY");

    // If no API key, return fallback reviews
    if (empty($apiKey)) {
        $fallback = [
            "I had an amazing experience at {$businessName}! The " . implode(' and ', array_slice($keywords, 0, 2)) . " were outstanding. Highly recommend!",
            "Absolutely love {$businessName}! The " . implode(', ', array_slice($keywords, 0, 3)) . " exceeded my expectations. Will definitely return!",
            "{$businessName} is simply the best! The " . implode(' and ', array_slice($keywords, 0, 2)) . " made my experience unforgettable. 5 stars!"
        ];
        return $fallback;
    }

    try {
        $prompt = "Write 3 distinct, short Google reviews for a business named \"{$businessName}\". 
        Include these keywords naturally: " . implode(", ", $keywords) . ". 
        " . (!empty($customerName) ? "The customer's name is {$customerName}. Use their name in the reviews." : "Don't use a specific customer name.") . "
        Make them sound like real customers. 
        Return the result as a JSON object with a key named \"reviews\" containing an array of strings.";

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, "https://api.groq.com/openai/v1/chat/completions");
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            "Content-Type: application/json",
            "Authorization: Bearer " . $apiKey
        ]);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode([
            "model" => "openai/gpt-oss-20b",
            "messages" => [
                [
                    "role" => "system",
                    "content" => "You are a helpful assistant that only outputs valid JSON. Always return an object with a 'reviews' key."
                ],
                [
                    "role" => "user",
                    "content" => $prompt
                ]
            ],
            "response_format" => ["type" => "json_object"],
            "temperature" => 0.7
        ]));
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        curl_close($ch);

        if ($curlError) {
            throw new Exception('CURL Error: ' . $curlError);
        }

        if ($httpCode === 200) {
            $data = json_decode($response, true);
            if (isset($data['choices'][0]['message']['content'])) {
                $content = $data['choices'][0]['message']['content'];
                $parsed = json_decode($content, true);

                if (isset($parsed['reviews']) && is_array($parsed['reviews'])) {
                    return $parsed['reviews'];
                }

                // Try to extract reviews from response
                if (is_array($parsed)) {
                    foreach ($parsed as $value) {
                        if (is_array($value) && count($value) >= 3) {
                            return array_slice($value, 0, 3);
                        }
                    }
                }
            }
        }

        // Return fallback if API fails
        return [
            "I had an amazing experience at {$businessName}! The " . implode(' and ', array_slice($keywords, 0, 2)) . " were outstanding. Highly recommend!",
            "Absolutely love {$businessName}! The " . implode(', ', array_slice($keywords, 0, 3)) . " exceeded my expectations. Will definitely return!",
            "{$businessName} is simply the best! The " . implode(' and ', array_slice($keywords, 0, 2)) . " made my experience unforgettable. 5 stars!"
        ];
    } catch (Exception $e) {
        // Return fallback reviews on error
        return [
            "I had an amazing experience at {$businessName}! The " . implode(' and ', array_slice($keywords, 0, 2)) . " were outstanding. Highly recommend!",
            "Absolutely love {$businessName}! The " . implode(', ', array_slice($keywords, 0, 3)) . " exceeded my expectations. Will definitely return!",
            "{$businessName} is simply the best! The " . implode(' and ', array_slice($keywords, 0, 2)) . " made my experience unforgettable. 5 stars!"
        ];
    }
}


function handleGetReview()
{
    global $conn;

    $reviewId = $_GET['review_id'] ?? null;
    if (!$reviewId) {
        http_response_code(400);
        echo json_encode(['error' => 'Review ID required']);
        exit;
    }

    try {
        $stmt = $conn->prepare("
            SELECT r.*, rc.name as card_name, rc.slug 
            FROM reviews r 
            JOIN review_cards rc ON r.card_id = rc.id 
            WHERE r.id = ? AND rc.user_id = ?
        ");
        $stmt->execute([$reviewId, $_SESSION['user_id']]);
        $review = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$review) {
            http_response_code(403);
            echo json_encode(['error' => 'Unauthorized or review not found']);
            exit;
        }

        echo json_encode(['success' => true, 'data' => $review]);
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
    }
}

function handleDeleteReview()
{
    global $conn;

    $reviewId = $_POST['review_id'] ?? null;
    if (!$reviewId) {
        http_response_code(400);
        echo json_encode(['error' => 'Review ID required']);
        exit;
    }

    try {
        // Verify ownership
        $stmt = $conn->prepare("
            SELECT r.id 
            FROM reviews r 
            JOIN review_cards rc ON r.card_id = rc.id 
            WHERE r.id = ? AND rc.user_id = ?
        ");
        $stmt->execute([$reviewId, $_SESSION['user_id']]);
        if (!$stmt->fetch()) {
            http_response_code(403);
            echo json_encode(['error' => 'Unauthorized']);
            exit;
        }

        $stmt = $conn->prepare("DELETE FROM reviews WHERE id = ?");
        $stmt->execute([$reviewId]);

        echo json_encode(['success' => true, 'message' => 'Review deleted successfully']);
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
    }
}

function handleListReviews()
{
    global $conn;

    $slug = $_GET['slug'] ?? null;
    $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 20;
    $offset = isset($_GET['offset']) ? (int)$_GET['offset'] : 0;
    $search = $_GET['search'] ?? '';

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

        if (!empty($search)) {
            $whereClause .= " AND (customer_name LIKE ? OR body LIKE ?)";
            $params[] = '%' . $search . '%';
            $params[] = '%' . $search . '%';
        }

        // Get total count
        $countStmt = $conn->prepare("SELECT COUNT(*) as total FROM reviews WHERE $whereClause");
        $countStmt->execute($params);
        $total = $countStmt->fetch()['total'];

        // Get reviews
        $stmt = $conn->prepare("
            SELECT * FROM reviews 
            WHERE $whereClause 
            ORDER BY created_at DESC 
            LIMIT ? OFFSET ?
        ");
        $stmt->execute(array_merge($params, [$limit, $offset]));
        $reviews = $stmt->fetchAll(PDO::FETCH_ASSOC);

        echo json_encode([
            'success' => true,
            'data' => $reviews,
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

function handleGetStats()
{
    global $conn;

    $slug = $_GET['slug'] ?? null;

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

        // Get review stats
        $stmt = $conn->prepare("
            SELECT 
                COUNT(*) as total,
                SUM(CASE WHEN rating >= 4 THEN 1 ELSE 0 END) as high,
                SUM(CASE WHEN rating <= 3 THEN 1 ELSE 0 END) as low,
                AVG(rating) as avg
            FROM reviews WHERE card_id = ?
        ");
        $stmt->execute([$card['id']]);
        $reviewStats = $stmt->fetch(PDO::FETCH_ASSOC);

        // Get feedback count
        $stmt = $conn->prepare("SELECT COUNT(*) as count FROM feedbacks WHERE card_id = ?");
        $stmt->execute([$card['id']]);
        $feedbackCount = $stmt->fetch()['count'];

        echo json_encode([
            'success' => true,
            'data' => [
                'total_reviews' => (int)$reviewStats['total'],
                'high' => (int)$reviewStats['high'],
                'low' => (int)$reviewStats['low'],
                'avg' => round($reviewStats['avg'] ?? 0, 1),
                'feedbacks' => (int)$feedbackCount
            ]
        ]);
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
    }
}
