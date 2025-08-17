<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/functions.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);
$employeeId = $data['employee_id'] ?? '';
$newStatus = strtoupper(trim($data['new_status'] ?? ''));

if (empty($employeeId) || empty($newStatus)) {
    echo json_encode(['success' => false, 'message' => 'Missing required fields']);
    exit;
}

try {
    // Get employee name for logging
    $stmt = $pdo->prepare("SELECT full_name FROM employees WHERE employee_id = ?");
    $stmt->execute([$employeeId]);
    $employee = $stmt->fetch();
    $employeeName = $employee ? $employee['full_name'] : 'Unknown Employee';

    $pdo->beginTransaction();
    
    // Update all pending IRs for this employee
    $stmt = $pdo->prepare("UPDATE absenteeism 
                          SET ir_form = ? 
                          WHERE employee_id = ? AND ir_form LIKE 'PENDING%'");
    $stmt->execute([$newStatus, $employeeId]);
    $updatedCount = $stmt->rowCount();
    
    // Log this activity for each updated record
    if ($updatedCount > 0) {
        // Get the updated records for logging
        $stmt = $pdo->prepare("SELECT id FROM absenteeism 
                              WHERE employee_id = ? AND ir_form = ?");
        $stmt->execute([$employeeId, $newStatus]);
        $updatedRecords = $stmt->fetchAll();
        
        foreach ($updatedRecords as $record) {
            logActivity(
                "Updated all pending IR forms to '$newStatus' for $employeeName",
                $record['id'],
                'absenteeism'
            );
        }
    }
    
    $pdo->commit();
    
    echo json_encode([
        'success' => true,
        'updated' => $updatedCount,
        'message' => 'Records updated successfully'
    ]);
    
} catch (PDOException $e) {
    $pdo->rollBack();
    echo json_encode([
        'success' => false,
        'message' => 'Database error: ' . $e->getMessage()
    ]);
}