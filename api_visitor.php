<?php
/**
 * YOUR_NAME - 访客计数 API
 */
require_once 'config.php';

header('Content-Type: application/json; charset=utf-8');

// 只允许 GET
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    jsonResponse(['error' => 'Method not allowed'], 405);
}

// 获取访客 IP
function getVisitorIP() {
    $ip = $_SERVER['HTTP_X_FORWARDED_FOR'] ?? $_SERVER['HTTP_X_REAL_IP'] ?? $_SERVER['REMOTE_ADDR'] ?? '';
    // 如果是多个 IP，取第一个
    $ips = array_map('trim', explode(',', $ip));
    return $ips[0] ?? 'unknown';
}

$ip = getVisitorIP();
$countFile = __DIR__ . '/.visitor_count.txt';
$ipLogFile = __DIR__ . '/.visitor_ips.txt';

// 读取当前计数
$count = file_exists($countFile) ? (int)file_get_contents($countFile) : 0;

// 读取已记录 IP
$loggedIPs = [];
if (file_exists($ipLogFile)) {
    $loggedIPs = array_filter(array_map('trim', file($ipLogFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES)));
}

// IP 去重：新 IP 才计数
if (!in_array($ip, $loggedIPs)) {
    $count++;
    file_put_contents($countFile, $count, LOCK_EX);
    file_put_contents($ipLogFile, implode("\n", array_merge($loggedIPs, [$ip])) . "\n", LOCK_EX);
}

// 限制日志文件大小（保留最近 10000 个 IP）
if (filesize($ipLogFile) > 500000) {
    $lines = array_slice($loggedIPs, -10000);
    file_put_contents($ipLogFile, implode("\n", $lines) . "\n", LOCK_EX);
}

jsonResponse([
    'count' => $count,
    'ip' => $ip
]);
