<?php
require_once '../includes/config.php';
require_once '../includes/functions.php';

if (!isLoggedIn() || !isAdmin()) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $recordId = $_POST['record_id'] ?? 0;
    
    try {
        if ($action === 'return_equipment') {
            $currentDateTime = date('Y-m-d H:i:s');
            
            $stmt = $pdo->prepare("UPDATE headset_tracker SET return_date = CURDATE(), return_time = CURTIME(), equipment_status = 'RETURNED' WHERE id = ?");
            $stmt->execute([$recordId]);
            
            if ($stmt->rowCount() > 0) {
                echo json_encode(['success' => true, 'message' => 'Equipment returned successfully']);
            } else {
                echo json_encode(['success' => false, 'message' => 'Record not found']);
            }
        } 
        elseif ($action === 'resolve_request') {
            $stmt = $pdo->prepare("UPDATE request_peripherals SET resolved = 'YES', date_resolved = CURDATE() WHERE id = ?");
            $stmt->execute([$recordId]);
            
            if ($stmt->rowCount() > 0) {
                echo json_encode(['success' => true, 'message' => 'Request resolved successfully']);
            } else {
                echo json_encode(['success' => false, 'message' => 'Record not found']);
            }
        }
        else {
            echo json_encode(['success' => false, 'message' => 'Invalid action']);
        }
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
}