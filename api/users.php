<?php
define('IN_CHAT', true);
require_once '../config.php';
header('Content-Type: application/json');

// check if user is logged in
if (!isset($_SESSION['user_id'])) {
    die(json_encode(['error' => 'Not logged in']));
}

// handle  request
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action']) && $_POST['action'] == 'heartbeat') {
    $stmt = $pdo->prepare("UPDATE users SET last_login = NOW() WHERE id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    echo json_encode(['success' => true]);
    exit;
}

// get online users list
if ($_SERVER['REQUEST_METHOD'] == 'GET' && !isset($_GET['user_id'])) {
    $stmt = $pdo->prepare("
        SELECT u.id, u.username, u.nickname, u.avatar, u.signature, u.is_admin,
               CASE WHEN u.is_admin = 1 THEN u.custom_location 
                    ELSE (SELECT city FROM user_locations WHERE user_id = u.id ORDER BY id DESC LIMIT 1)
               END as location
        FROM users u 
        WHERE u.last_login >= DATE_SUB(NOW(), INTERVAL 5 MINUTE)
        AND u.status = 1
        ORDER BY u.is_admin DESC, u.nickname, u.username
    ");
    $stmt->execute();
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode(['users' => $users]);
    exit;
}

// get user detailed information (for business card display)
if (isset($_GET['user_id'])) {
    $user_id = (int)$_GET['user_id'];
    $stmt = $pdo->prepare("
        SELECT u.id, u.username, u.nickname, u.avatar, u.signature, u.created_at, u.is_admin,
               CASE WHEN u.is_admin = 1 THEN u.custom_location 
                    ELSE (SELECT city FROM user_locations WHERE user_id = u.id ORDER BY id DESC LIMIT 1)
               END as location
        FROM users u 
        WHERE u.id = ? AND u.status = 1
    ");
    $stmt->execute([$user_id]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($user) {
        echo json_encode(['user' => $user]);
    } else {
        echo json_encode(['error' => 'User does not exist']);
    }
    exit;
} 