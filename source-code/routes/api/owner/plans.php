<?php


ob_clean();
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

// Check if user is owner
if ($_SESSION['user_email'] !== env('OWNER_EMAIL')) {
    http_response_code(403);
    echo json_encode(['error' => 'Access denied. Owner only.']);
    exit;
}

$action = $_GET['action'] ?? '';

switch ($action) {
    case 'save':
        handleSavePlan();
        break;
    case 'delete':
        handleDeletePlan();
        break;
    default:
        handleObj();
        break;
}

function handleSavePlan()
{
    global $conn;

    $planId = $_POST['id'] ?? null;
    $name = trim($_POST['name'] ?? '');
    $price = floatval($_POST['price'] ?? 0);
    $salePrice = !empty($_POST['sale_price']) ? floatval($_POST['sale_price']) : null;
    $description = trim($_POST['description'] ?? '');
    $cta = trim($_POST['cta'] ?? '');
    $duration = intval($_POST['duration'] ?? 30);
    $features = $_POST['features'] ?? [];

    // Filter empty features
    $features = array_filter($features, function ($f) {
        return !empty(trim($f));
    });
    $features = array_values($features); // Re-index array

    if (empty($name)) {
        http_response_code(400);
        echo json_encode(['error' => 'Plan name is required']);
        exit;
    }

    try {
        if ($planId) {
            // Update existing plan
            $stmt = $conn->prepare("
                UPDATE plans 
                SET name = ?, price = ?, sale_price = ?, description = ?, features = ?, cta = ?, duration = ?
                WHERE id = ?
            ");
            $stmt->execute([
                $name,
                $price,
                $salePrice,
                $description,
                json_encode($features),
                $cta,
                $duration,
                $planId
            ]);
        } else {
            // Insert new plan
            $stmt = $conn->prepare("
                INSERT INTO plans (name, price, sale_price, description, features, cta, duration)
                VALUES (?, ?, ?, ?, ?, ?, ?)
            ");
            $stmt->execute([
                $name,
                $price,
                $salePrice,
                $description,
                json_encode($features),
                $cta,
                $duration
            ]);
        }

        echo json_encode(['success' => true]);
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
    }
}

function handleDeletePlan()
{
    global $conn;

    $planId = $_POST['id'] ?? null;

    if (empty($planId)) {
        http_response_code(400);
        echo json_encode(['error' => 'Plan ID is required']);
        exit;
    }

    try {
        $stmt = $conn->prepare("DELETE FROM plans WHERE id = ?");
        $stmt->execute([$planId]);

        echo json_encode(['success' => true]);
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
    }
}

function handleObj()
{
    echo json_encode(['Owned & Developed by Nizam' => 'Owned & Developed by Nizam']);
}
