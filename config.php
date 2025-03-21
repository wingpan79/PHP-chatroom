<?php
// Security check: prevent direct access to PHP file
defined('IN_CHAT') or exit('Access Denied');

// Start session
session_start();

// Set timezone
date_default_timezone_set('Europe/London');

// Database configuration
$db_config = [
    'host' => 'localhost',
    'username' => 'root',
    'password' => '',
    'database' => 'chat'
];

// Site configuration
$site_config = [
    'name' => 'Online Chat Room',
    'url' => (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://localhost",
    'upload_path' => __DIR__ . '/uploads',
    'avatar_path' => __DIR__ . '/uploads/avatars',
    'max_upload_size' => 5 * 1024 * 1024, // 5MB
    'allowed_extensions' => ['jpg', 'jpeg', 'png', 'gif'],
    'online_timeout' => 300, // 5 minutes of inactivity considered offline
    'messages_per_page' => 50,
    'secret_key' => '' // Secret key for encryption operations
];

// Error reporting settings
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/error.log');

// Database connection
try {
    $pdo = new PDO(
        "mysql:host={$db_config['host']};dbname={$db_config['database']};charset=utf8mb4",
        $db_config['username'],
        $db_config['password'],
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4"
        ]
    );
} catch(PDOException $e) {
    error_log("Database connection failed: " . $e->getMessage());
    die("System maintenance, please try again later...");
}


function is_ajax_request() {
    return isset($_SERVER['HTTP_X_REQUESTED_WITH']) && 
           strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest';
}

function get_client_ip() {
    $ip = '';
    if (isset($_SERVER['HTTP_CLIENT_IP'])) {
        $ip = $_SERVER['HTTP_CLIENT_IP'];
    } elseif (isset($_SERVER['HTTP_X_FORWARDED_FOR'])) {
        $ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
    } elseif (isset($_SERVER['REMOTE_ADDR'])) {
        $ip = $_SERVER['REMOTE_ADDR'];
    }
    return $ip;
}

function check_login() {
    if (!isset($_SESSION['user_id'])) {
        if (is_ajax_request()) {
            header('Content-Type: application/json');
            die(json_encode(['error' => '未登录']));
        } else {
            header('Location: login.php');
            exit;
        }
    }
}

// Define constants
define('IN_CHAT', true);
define('UPLOAD_PATH', $site_config['upload_path']);
define('AVATAR_PATH', $site_config['avatar_path']);