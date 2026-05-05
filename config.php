<?php
/**
 * My Blog - 数据库配置
 */

define('DB_HOST', 'localhost');
define('DB_NAME', 'blog_db');
define('DB_PASS', 'YOUR_DB_PASSWORD');
define('DB_USER', 'blog_db');

define('API_KEY_HEADER', 'X-API-Key');
define('SESSION_LIFETIME', 3600 * 24);
define('SITE_NAME', 'My Blog');
define('SITE_URL', '');
define('POSTS_PER_PAGE', 10);
date_default_timezone_set('Asia/Shanghai');

function getDB() {
    static $pdo = null;
    if ($pdo === null) {
        try {
            $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4";
            $pdo = new PDO($dsn, DB_USER, DB_PASS, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ]);
        } catch (PDOException $e) {
            die('数据库连接失败: ' . $e->getMessage());
        }
    }
    return $pdo;
}

function cleanInput($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data, ENT_QUOTES, 'UTF-8');
    return $data;
}

function generateSlug($string) {
    $slug = strtolower(trim($string));
    $slug = preg_replace('/[^a-z0-9\x{4e00}-\x{9fa5}\-]/u', '-', $slug);
    $slug = preg_replace('/-+/', '-', $slug);
    $slug = trim($slug, '-');
    return $slug ?: 'post-' . time();
}

function isLoggedIn() {
    return isset($_SESSION['admin_id']);
}

function isApiRequest() {
    return isset($_GET['api']) || str_starts_with($_SERVER['REQUEST_URI'], '/api/');
}

function jsonResponse($data, $status = 200) {
    http_response_code($status);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

function e($string) {
    return htmlspecialchars($string ?? '', ENT_QUOTES, 'UTF-8');
}

function getSettings() {
    static $settings = null;
    if ($settings === null) {
        $pdo = getDB();
        $stmt = $pdo->query("SELECT setting_key, value FROM settings");
        $rows = $stmt->fetchAll();
        $settings = ['site_name' => SITE_NAME];
        foreach ($rows as $row) {
            $settings[$row['setting_key']] = $row['value'];
        }
    }
    return $settings;
}
