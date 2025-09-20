<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/functions.php';

header('Content-Type: application/json');

if (!isset($_GET['c_no']) || empty($_GET['c_no'])) {
    echo json_encode(['success' => false, 'message' => 'C No is required']);
    exit;
}

$c_no = strtoupper(trim($_GET['c_no']));

try {
    $stmt = $pdo->prepare("SELECT status FROM headset_inventory WHERE c_no = ?");
    $stmt->execute([$c_no]);
    $headset = $stmt->fetch();
    
    if ($headset) {
        echo json_encode(['success' => true, 'status' => $headset['status']]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Headset not found']);
    }
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}