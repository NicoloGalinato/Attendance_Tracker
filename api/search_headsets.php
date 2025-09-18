<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/functions.php';

if (!isLoggedIn() || !isAdmin()) {
    header('HTTP/1.1 403 Forbidden');
    exit;
}

header('Content-Type: application/json');

if (!isset($_GET['query']) || empty($_GET['query'])) {
    echo json_encode(['success' => false, 'message' => 'Query is required']);
    exit;
}

if (!isset($_GET['type']) || !in_array($_GET['type'], ['c_no', 'yjack_serial_no'])) {
    echo json_encode(['success' => false, 'message' => 'Valid search type is required']);
    exit;
}

$query = strtoupper(trim($_GET['query']));
$type = $_GET['type'];

try {
    $sql = "SELECT c_no, yjack_serial_no, brand FROM headset_inventory WHERE $type LIKE ? ORDER BY c_no LIMIT 10";
    $stmt = $pdo->prepare($sql);
    $stmt->execute(["%$query%"]);
    $headsets = $stmt->fetchAll();
    
    echo json_encode(['success' => true, 'headsets' => $headsets]);
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}