<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/functions.php';

header('Content-Type: application/json');

// MANUAL DATA - Copy from your Google Sheets and paste here
$manualData = [
    // FORMAT: ['Date', 'Total Present']
    // Copy the exact data from your Google Sheets "TOTAL AGENT PRESENT" sheet
    // Example:
    ['September 29, 2025', '298'],
    ['September 30, 2025', '316'],
    ['October 1, 2025', '325'],
    ['October 2, 2025', '328'],
    ['October 3, 2025', '333'],
    ['October 4, 2025', '219'],
    ['October 5, 2025', '196']
    // ADD MORE ROWS HERE...
];

try {
    if (empty($manualData)) {
        throw new Exception("Please add manual data in the PHP file");
    }
    
    $result = insertToTotalPresentTable($manualData);
    
    echo json_encode([
        'success' => true, 
        'message' => 'Manual data imported successfully!',
        'records_processed' => $result['processed'],
        'records_inserted' => $result['inserted'],
        'duplicates_skipped' => $result['duplicates'],
        'next_steps' => 'You can now setup automated sync later'
    ]);
    
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}

function insertToTotalPresentTable($sheetData) {
    global $pdo;
    
    $processed = 0;
    $inserted = 0;
    $duplicates = 0;
    
    // REMOVED TRUNCATE - No longer clearing existing data
    
    // Prepare INSERT statement with ON DUPLICATE KEY UPDATE
    $stmt = $pdo->prepare("
        INSERT INTO total_present (date, total_present) 
        VALUES (:date, :total_present)
        ON DUPLICATE KEY UPDATE 
            total_present = VALUES(total_present),
            updated_at = CURRENT_TIMESTAMP
    ");
    
    // Alternative: If you want to skip duplicates completely, use this instead:
    /*
    $stmt = $pdo->prepare("
        INSERT IGNORE INTO total_present (date, total_present) 
        VALUES (:date, :total_present)
    ");
    */
    
    foreach ($sheetData as $row) {
        if (count($row) < 2) continue;
        
        $date = trim($row[0]);
        $totalPresent = trim($row[1]);
        
        // Skip empty rows
        if (empty($date) || empty($totalPresent)) continue;
        
        // Convert date to proper format
        $formattedDate = formatDate($date);
        
        $processed++;
        
        try {
            $result = $stmt->execute([
                ':date' => $formattedDate,
                ':total_present' => $totalPresent
            ]);
            
            $rowCount = $stmt->rowCount();
            
            if ($rowCount > 0) {
                if ($rowCount === 1) {
                    // New record inserted
                    $inserted++;
                    echo "Inserted: $formattedDate - $totalPresent\n";
                } else if ($rowCount === 2) {
                    // Record updated (ON DUPLICATE KEY UPDATE)
                    $inserted++;
                    echo "Updated: $formattedDate - $totalPresent\n";
                }
            } else {
                // No rows affected (duplicate skipped with INSERT IGNORE)
                $duplicates++;
                echo "Skipped (duplicate): $formattedDate - $totalPresent\n";
            }
            
        } catch (PDOException $e) {
            // Check if it's a duplicate entry error
            if ($e->getCode() == 23000) { // MySQL duplicate entry error code
                $duplicates++;
                echo "Skipped (duplicate): $formattedDate - $totalPresent\n";
            } else {
                error_log("MySQL Insert Error: " . $e->getMessage());
                echo "Error: " . $e->getMessage() . "\n";
            }
        }
    }
    
    return [
        'processed' => $processed, 
        'inserted' => $inserted, 
        'duplicates' => $duplicates
    ];
}

function formatDate($dateString) {
    // Convert various date formats to YYYY-MM-DD
    $timestamp = strtotime($dateString);
    if ($timestamp !== false) {
        return date('Y-m-d', $timestamp);
    }
    return $dateString; // Return as is if can't convert
}
?>