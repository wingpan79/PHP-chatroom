<?php
define('IN_CHAT', true);
require_once '../config.php';

// set online timeout (seconds)
define('ONLINE_TIMEOUT', 300); // 5 minutes

// clean offline users
function cleanOfflineUsers($pdo) {
    $stmt = $pdo->prepare("DELETE FROM online_users WHERE last_active < DATE_SUB(NOW(), INTERVAL ? SECOND)");
    $stmt->execute([ONLINE_TIMEOUT]);
}

// update or add online user
function updateOnlineUser($pdo, $userId, $page = '') {
    $stmt = $pdo->prepare("INSERT INTO online_users (user_id, last_active, current_page) 
                          VALUES (?, NOW(), ?) 
                          ON DUPLICATE KEY UPDATE last_active = NOW(), current_page = ?");
    $stmt->execute([$userId, $page, $page]);
}

// get online users list
function getOnlineUsers($pdo) {
    cleanOfflineUsers($pdo);
    
    $stmt = $pdo->prepare("SELECT u.id, u.username, u.nickname, u.avatar, o.current_page, u.is_admin 
                          FROM online_users o 
                          JOIN users u ON o.user_id = u.id 
                          ORDER BY u.is_admin DESC, u.username ASC");
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// API route processing
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_SESSION['user_id'])) {
        http_response_code(401);
        die(json_encode(['error' => 'Not logged in']));
    }
    
    $page = isset($_POST['page']) ? $_POST['page'] : '';
    updateOnlineUser($pdo, $_SESSION['user_id'], $page);
    echo json_encode(['success' => true]);
}

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $users = getOnlineUsers($pdo);
    echo json_encode([
        'success' => true,
        'users' => $users,
        'total' => count($users)
    ]);
} 