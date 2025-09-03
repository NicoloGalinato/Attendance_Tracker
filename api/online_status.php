<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/functions.php';

if (!isLoggedIn() || !isAdmin()) {
    header('HTTP/1.1 403 Forbidden');
    exit;
}

header('Content-Type: application/json');
header('Cache-Control: no-cache, must-revalidate'); // Prevent caching

try {
    // Create DateTime object for threshold in UTC
    $threshold = new DateTime('now', new DateTimeZone(DB_TIMEZONE));
    $threshold->modify('-5000 seconds'); // Increased to 5 seconds for better reliability
    
    $stmt = $pdo->prepare("SELECT id FROM users WHERE last_activity IS NOT NULL AND last_activity > ?");
    $stmt->execute([$threshold->format('Y-m-d H:i:s')]);
    $onlineUsers = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    echo json_encode([
        'success' => true,
        'onlineUsers' => $onlineUsers,
        'timestamp' => time() // Add timestamp for debugging
    ]);
} catch (PDOException $e) {
    error_log("Online status error: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'error' => 'Database error'
    ]);
}