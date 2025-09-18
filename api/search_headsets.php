<?php
require_once '../../includes/config.php';
require_once '../../includes/functions.php';

header('Content-Type: application/json');

if (!isset($_GET['query']) || empty($_GET['query'])) {
    echo json_encode(['success' => false, 'message' => 'Query parameter is required']);
    exit;
}

$query = '%' . $_GET['query'] . '%';

try {
    $stmt = $pdo->prepare("SELECT * FROM headset_inventory WHERE c_no LIKE :query OR yjack_serial_no LIKE :query OR headset_serial_no LIKE :query LIMIT 10");
    $stmt->bindParam(':query', $query);
    $stmt->execute();
    $headsets = $stmt->fetchAll();
    
    echo json_encode(['success' => true, 'headsets' => $headsets]);
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}