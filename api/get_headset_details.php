<?php
require_once '../includes/config.php';
require_once '../includes/functions.php';

header('Content-Type: application/json');

if (!isset($_GET['c_no']) || empty($_GET['c_no'])) {
    echo json_encode(['success' => false, 'message' => 'C No is required']);
    exit;
}

$c_no = strtoupper(trim($_GET['c_no']));

try {
    $stmt = $pdo->prepare("SELECT c_no, brand, yjack_serial_no FROM headset_inventory WHERE c_no = ?");
    $stmt->execute([$c_no]);
    $headset = $stmt->fetch();
    
    if ($headset) {
        echo json_encode(['success' => true, 'headset' => $headset]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Headset not found']);
    }
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}