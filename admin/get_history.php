<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/functions.php';

if (!isLoggedIn()) {
    header('HTTP/1.1 401 Unauthorized');
    exit();
}

$recordId = isset($_GET['record_id']) ? (int)$_GET['record_id'] : 0;
$recordType = isset($_GET['type']) ? $_GET['type'] : 'absenteeism';

try {
    $stmt = $pdo->prepare("
        SELECT ah.*, u.sub_name 
        FROM activity_history ah
        JOIN users u ON ah.user_id = u.id
        WHERE ah.record_id = ? AND ah.record_type = ?
        ORDER BY ah.activity_time DESC
    ");
    $stmt->execute([$recordId, $recordType]);
    $history = $stmt->fetchAll();
    
    header('Content-Type: application/json');
    echo json_encode($history);
} catch (PDOException $e) {
    header('HTTP/1.1 500 Internal Server Error');
    echo json_encode(['error' => $e->getMessage()]);
}

