<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/functions.php';

header('Content-Type: application/json');

if (!isLoggedIn() || !isAdmin()) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
}

$action = $_POST['action'] ?? '';
$recordIds = json_decode($_POST['record_ids'] ?? '[]', true);
$type = $_POST['type'] ?? 'absenteeism';

if (empty($recordIds)) {
    echo json_encode(['success' => false, 'message' => 'No records selected']);
    exit;
}

try {
    $table = ($type === 'tardiness') ? 'tardiness' : ($type === 'vto' ? 'vto_tracker' : 'absenteeism');
    
    // Prepare the SQL query based on action
    $placeholders = implode(',', array_fill(0, count($recordIds), '?'));
    
    if ($action === 'no_need_email') {
        // Mark as no need email
        $sql = "UPDATE $table SET email_sent = 1, email_sent_at = 'BYPASS' WHERE id IN ($placeholders)";
    } elseif ($action === 're_track_email') {
        // Reset email status for re-tracking
        $sql = "UPDATE $table SET email_sent = 0, email_sent_at = NULL WHERE id IN ($placeholders)";
    } else {
        echo json_encode(['success' => false, 'message' => 'Invalid action']);
        exit;
    }
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute($recordIds);
    
    $updatedCount = $stmt->rowCount();
    
    echo json_encode([
        'success' => true, 
        'updated' => $updatedCount,
        'message' => "Updated $updatedCount record(s) successfully"
    ]);
    
} catch (PDOException $e) {
    error_log("Database error in update_attendance: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
?>