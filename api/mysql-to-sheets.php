<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

// 1. Database Configuration
$db_host = "localhost";
$db_user = "u865665685_cxi_database";
$db_pass = "Wea_dayaday05";
$db_name = "u865665685_cxi_database";

// 2. Connect to MySQL
$conn = new mysqli($db_host, $db_user, $db_pass, $db_name);

if ($conn->connect_error) {
    die(json_encode(["error" => "Connection failed: " . $conn->connect_error]));
}

// 3. Sanitize input and set defaults
$allowed_tables = ['absenteeism', 'employees']; // Added both tables that might be needed
$table = $conn->real_escape_string($_GET['table'] ?? 'absenteeism'); // Default to absenteeism
$mode = $_GET['mode'] ?? 'incremental';
$limit = min((int)($_GET['limit'] ?? 100), 500);
$lastId = max((int)($_GET['last_id'] ?? 0), 0);
$lastUpdate = $conn->real_escape_string($_GET['last_update'] ?? '');

// 4. Validate table name
if (!in_array($table, $allowed_tables)) {
    die(json_encode(["error" => "Invalid table specified"]));
}

// 5. Handle full sync mode
if ($mode === 'full') {
    // Get all current IDs in the database
    $idsQuery = "SELECT id FROM $table ORDER BY id ASC";
    $idsResult = $conn->query($idsQuery);
    
    if ($idsResult === false) {
        die(json_encode(["error" => "ID query failed: " . $conn->error]));
    }
    
    $currentIds = array_column($idsResult->fetch_all(MYSQLI_ASSOC), 'id');
    
    // Get the data with optional last_update filter
    $dataQuery = "SELECT * FROM $table WHERE id > ?";
    if (!empty($lastUpdate)) {
        $dataQuery .= " OR created_at >= ?";
    }
    $dataQuery .= " LIMIT ?";
    
    $stmt = $conn->prepare($dataQuery);
    if ($stmt === false) {
        die(json_encode(["error" => "Prepare failed: " . $conn->error]));
    }
    
    if (!empty($lastUpdate)) {
        $stmt->bind_param("isi", $lastId, $lastUpdate, $limit);
    } else {
        $stmt->bind_param("ii", $lastId, $limit);
    }
    
    $stmt->execute();
    $result = $stmt->get_result();
    
    $data = [];
    while ($row = $result->fetch_assoc()) {
        $data[] = $row;
    }
    
    echo json_encode([
        'mode' => 'full',
        'all_ids' => $currentIds,
        'data' => $data,
        'last_update' => $lastUpdate
    ]);
    
    $stmt->close();
    $conn->close();
    exit();
}

// 6. Handle incremental sync with last_update support
$query = "SELECT * FROM $table WHERE id > ?";
if (!empty($lastUpdate)) {
    $query .= " OR created_at >= ?";
}
$query .= " LIMIT ?";

$stmt = $conn->prepare($query);
if ($stmt === false) {
    die(json_encode(["error" => "Prepare failed: " . $conn->error]));
}

if (!empty($lastUpdate)) {
    $stmt->bind_param("isi", $lastId, $lastUpdate, $limit);
} else {
    $stmt->bind_param("ii", $lastId, $limit);
}

$stmt->execute();
$result = $stmt->get_result();

$data = [];
while ($row = $result->fetch_assoc()) {
    $data[] = $row;
}

echo json_encode([
    'mode' => 'incremental',
    'data' => $data,
    'last_update' => $lastUpdate
]);

// 7. Close connections
$stmt->close();
$conn->close();
?>