<?php
require_once '../../includes/config.php';
require_once '../../includes/functions.php';

header('Content-Type: application/json');

if (!isset($_GET['c_no']) || empty($_GET['c_no'])) {
    echo json_encode(['success' => false, 'message' => 'C No parameter is required']);
    exit;
}

$cNo = $_GET['c_no'];

try {
    $stmt = $pdo->prepare("SELECT * FROM headset_inventory WHERE c_no = :c_no");
    $stmt->bindParam(':c_no', $cNo);
    $stmt->execute();
    $headset = $stmt->fetch();
    
    if ($headset) {
        echo json_encode(['success' => true, 'headset' => $headset]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Headset not found']);
    }
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}