<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/functions.php';

header('Content-Type: application/json');
header("Cache-Control: max-age=5"); // 5 second browser cache
header("X-Accel-Expires: 5"); // For Nginx servers

// Get the requested data type (absenteeism or tardiness)
$type = isset($_GET['type']) ? $_GET['type'] : 'absenteeism';

try {
    if ($type === 'absenteeism') {
        $stmt = $pdo->prepare("SELECT * FROM absenteeism ORDER BY created_at ASC");
        $stmt->execute();
        $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } elseif ($type === 'tardiness') {
        $stmt = $pdo->prepare("SELECT * FROM tardiness ORDER BY created_at ASC");
        $stmt->execute();
        $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } elseif ($type === 'vto_tracker') {
        $stmt = $pdo->prepare("SELECT * FROM vto_tracker ORDER BY created_at ASC");
        $stmt->execute();
        $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } else {
        throw new Exception("Invalid data type requested. Valid types are: absenteeism, tardiness, vto, vto_tracker");
    }
    
    echo json_encode(['success' => true, 'data' => $data]);
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'error' => 'Database error: ' . $e->getMessage()]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
?>