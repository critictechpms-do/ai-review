<?php

if (ob_get_level() > 0) {
    ob_clean();
}
header('Content-Type: application/json');

$action = $_GET['action'] ?? '';

switch ($action) {
    case 'signup':
        handleSignup();
        break;
    case 'login':
        handleLogin();
        break;
    case 'logout':
        handleLogout();
        break;
    case 'resetPassword':
        handleResetPassword();
        break;
    default:
        http_response_code(400);
        echo json_encode(['error' => 'Invalid action']);
        break;
}

function handleSignup()
{
    global $conn;

    if (!isset($conn) || $conn === null) {
        $conn = $GLOBALS['conn'] ?? null;
        if (!$conn) {
            die("Database connection not available");
        }
    }

    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($email) || empty($password)) {
        $_SESSION['auth_error'] = 'Email and password are required.';
        header('Location: ' . url('/signup'));
        exit;
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $_SESSION['auth_error'] = 'Please enter a valid email address.';
        header('Location: ' . url('/signup'));
        exit;
    }

    if (strlen($password) < 8) {
        $_SESSION['auth_error'] = 'Password must be at least 8 characters.';
        header('Location: ' . url('/signup'));
        exit;
    }

    $stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
    $stmt->execute([$email]);

    if ($stmt->fetch()) {
        $_SESSION['auth_error'] = 'Email already registered. Please login instead.';
        header('Location: ' . url('/signup'));
        exit;
    }

    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

    try {
        $stmt = $conn->prepare("INSERT INTO users (email, password) VALUES (?, ?)");
        $stmt->execute([$email, $hashedPassword]);

        $userId = $conn->lastInsertId();

        $_SESSION['user_id'] = $userId;
        $_SESSION['user_email'] = $email;

        if (!isOwner()) {
            header('Location: ' . url('/dashboard'));
            exit;
        } else {
            header('Location: ' . url('/owner'));
            exit;
        }
        exit;
    } catch (PDOException $e) {
        $_SESSION['auth_error'] = 'Registration failed. Please try again.';
        header('Location: ' . url('/signup'));
        exit;
    }
}

function handleLogin()
{
    global $conn;

    if (!isset($conn) || $conn === null) {
        $conn = $GLOBALS['conn'] ?? null;
    }

    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($email) || empty($password)) {
        $_SESSION['auth_error'] = 'Email and password are required.';
        header('Location: ' . url('/login'));
        exit;
    }

    $stmt = $conn->prepare("SELECT id, email, password FROM users WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch();

    if (!$user) {
        $_SESSION['auth_error'] = 'User not found';
        header('Location: ' . url('/login'));
        exit;
    }

    if (!password_verify($password, $user['password'])) {
        $_SESSION['auth_error'] = 'Incorrect password.';
        header('Location: ' . url('/login'));
        exit;
    }

    $_SESSION['user_id'] = $user['id'];
    $_SESSION['user_email'] = $user['email'];

    if (!isOwner()) {
        header('Location: ' . url('/dashboard'));
        exit;
    } else {
        header('Location: ' . url('/owner'));
        exit;
    }
    exit;
}

function handleResetPassword()
{
    global $conn;

    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';

    // Validation
    if (empty($email) || empty($password) || empty($confirmPassword)) {
        $_SESSION['auth_error'] = 'All fields are required.';
        header('Location: ' . url('/forgot-password'));
        exit;
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $_SESSION['auth_error'] = 'Please enter a valid email address.';
        header('Location: ' . url('/forgot-password'));
        exit;
    }

    if (strlen($password) < 8) {
        $_SESSION['auth_error'] = 'Password must be at least 8 characters.';
        header('Location: ' . url('/forgot-password'));
        exit;
    }

    if ($password !== $confirmPassword) {
        $_SESSION['auth_error'] = 'Passwords do not match.';
        header('Location: ' . url('/forgot-password'));
        exit;
    }

    // Check if user exists
    $stmt = $conn->prepare("SELECT id, email FROM users WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch();

    if (!$user) {
        $_SESSION['auth_error'] = 'No account found with this email address.';
        header('Location: ' . url('/forgot-password'));
        exit;
    }

    // Hash new password and update
    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

    try {
        $stmt = $conn->prepare("UPDATE users SET password = ? WHERE id = ?");
        $stmt->execute([$hashedPassword, $user['id']]);

        $_SESSION['auth_success'] = 'Password reset successfully! You can now login with your new password.';
        header('Location: ' . url('/login'));
        exit;

    } catch (PDOException $e) {
        $_SESSION['auth_error'] = 'Failed to reset password. Please try again.';
        header('Location: ' . url('/forgot-password'));
        exit;
    }
}

function handleLogout()
{
    session_destroy();
    header('Location: ' . url('/login'));
    exit;
}
?>