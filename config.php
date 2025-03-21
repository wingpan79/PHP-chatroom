<?php
// 安全检查：防止直接访问PHP文件
defined('IN_CHAT') or exit('Access Denied');

// 开启session
session_start();

// 设置时区
date_default_timezone_set('Asia/Shanghai');

// 数据库配置
$db_config = [
    'host' => 'localhost',
    'username' => 'root',
    'password' => '',
    'database' => 'chat'
];

// 站点配置
$site_config = [
    'name' => '在线聊天室',
    'url' => (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://localhost",
    'upload_path' => __DIR__ . '/uploads',
    'avatar_path' => __DIR__ . '/uploads/avatars',
    'max_upload_size' => 5 * 1024 * 1024, // 5MB
    'allowed_extensions' => ['jpg', 'jpeg', 'png', 'gif'],
    'online_timeout' => 300, // 5分钟未活动视为离线
    'messages_per_page' => 50,
    'secret_key' => '0f19996e7e3bfe9bbd21ab811fe4b0402b1ae3f553e1795ba56c730b25748168' // 用于加密等操作的密钥
];

// 错误报告设置
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/error.log');

// 数据库连接
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
    error_log("数据库连接失败: " . $e->getMessage());
    die("系统维护中，请稍后再试...");
}

// 辅助函数
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

// 定义常量
define('IN_CHAT', true);
define('UPLOAD_PATH', $site_config['upload_path']);
define('AVATAR_PATH', $site_config['avatar_path']);