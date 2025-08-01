<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/functions.php';

if (!isLoggedIn() || !isAdmin()) {
    header('HTTP/1.1 403 Forbidden');
    exit;
}

header('Content-Type: application/json');

try {
    // Create DateTime object for threshold in UTC
    $threshold = new DateTime('now', new DateTimeZone(DB_TIMEZONE));
    $threshold->modify('-300 seconds');
    
    $stmt = $pdo->prepare("SELECT id FROM users WHERE last_activity IS NOT NULL AND last_activity > ?");
    $stmt->execute([$threshold->format('Y-m-d H:i:s')]);
    $onlineUsers = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    echo json_encode([
        'success' => true,
        'onlineUsers' => $onlineUsers
    ]);
} catch (PDOException $e) {
    echo json_encode([
        'success' => false,
        'error' => 'Database error'
    ]);
}