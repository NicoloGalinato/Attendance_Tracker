<?php
require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../../includes/functions.php';

// In inventory_table.php, in the return functionality section
if (isset($_GET['return_id']) && isLoggedIn() && isAdmin()) {
    $return_id = (int)$_GET['return_id'];
    
    // Get current user's sub_name
    $userStmt = $pdo->prepare("SELECT sub_name FROM users WHERE id = ?");
    $userStmt->execute([$_SESSION['user_id']]);
    $user = $userStmt->fetch();
    $sub_name = $user['sub_name'];
    
    try {
        // First get the C_NO before updating
        $getCNo = $pdo->prepare("SELECT c_no FROM headset_tracker WHERE id = ?");
        $getCNo->execute([$return_id]);
        $headsetRecord = $getCNo->fetch();
        $c_no = $headsetRecord['c_no'];
        
        // Update the tracker record
        $stmt = $pdo->prepare("UPDATE headset_tracker SET received_by = ?, return_time = CURTIME(), return_date = CURDATE(), status = 'RETURNED' WHERE id = ?");
        $stmt->execute([$sub_name, $return_id]);
        
        // NEW: Update headset inventory status to AVAILABLE
        if (!empty($c_no)) {
            $updateInventory = $pdo->prepare("UPDATE headset_inventory SET status = 'AVAILABLE' WHERE c_no = ?");
            $updateInventory->execute([$c_no]);
        }
        
        $_SESSION['success'] = "Headset marked as returned successfully!";
        redirect('inventory_tracker.php?tab=headset');
    } catch (PDOException $e) {
        $_SESSION['error'] = "Error updating record: " . $e->getMessage();
        redirect('inventory_tracker.php?tab=headset');
    }
}

// Get all filter parameters with proper fallbacks
$search = isset($_POST['search']) ? trim($_POST['search']) : (isset($_GET['search']) ? trim($_GET['search']) : '');
$page = isset($_POST['page']) ? (int)$_POST['page'] : (isset($_GET['page']) ? (int)$_GET['page'] : 1);
$type = isset($_POST['type']) ? $_POST['type'] : (isset($_GET['tab']) ? $_GET['tab'] : 'headset');
$dateFrom = isset($_POST['date_from']) ? $_POST['date_from'] : (isset($_GET['from']) ? $_GET['from'] : '');
$dateTo = isset($_POST['date_to']) ? $_POST['date_to'] : (isset($_GET['to']) ? $_GET['to'] : '');
$statusFilter = isset($_POST['status']) ? $_POST['status'] : (isset($_GET['status']) ? $_GET['status'] : '');

// Ensure we have the table and type
if (!isset($type)) $type = 'headset';

// Determine which table to use
if ($type === 'peripherals') {
    $table = 'request_peripherals';
} elseif ($type === 'headset_inventory') {
    $table = 'headset_inventory';
} else {
    $table = 'headset_tracker';
}

$perPage = 15;

// Initialize where clauses and parameters
$whereClauses = [];
$params = [];

// Add search filter
if (!empty($search)) {
    if ($type === 'headset') {
        $whereClauses[] = "(employee_id LIKE :search OR full_name LIKE :search)";
    } elseif ($type === 'headset_inventory') {
        $whereClauses[] = "(yjack_serial_no LIKE :search OR headset_serial_no LIKE :search)";
    } else {
        $whereClauses[] = "(stn_no LIKE :search OR serial_no LIKE :search)";
    }
    $params[':search'] = "%$search%";
}

// Add date filter (only for headset and peripherals tabs)
if (!empty($dateFrom) && $type !== 'headset_inventory') {
    $dateField = ($type === 'peripherals') ? 'request_date' : 'date_issued';
    $whereClauses[] = "$dateField >= :date_from";
    $params[':date_from'] = $dateFrom;
}

if (!empty($dateTo) && $type !== 'headset_inventory') {
    $dateField = ($type === 'peripherals') ? 'request_date' : 'date_issued';
    $whereClauses[] = "$dateField <= :date_to";
    $params[':date_to'] = $dateTo;
}

// Add status filter
if (!empty($statusFilter)) {
    $whereClauses[] = "status = :status";
    $params[':status'] = $statusFilter;
}

$searchQuery = empty($whereClauses) ? '' : 'WHERE ' . implode(' AND ', $whereClauses);

try {
    $countStmt = $pdo->prepare("SELECT COUNT(*) FROM $table $searchQuery");
    foreach ($params as $key => $value) {
        $countStmt->bindValue($key, $value);
    }
    $countStmt->execute();
    $totalRecords = $countStmt->fetchColumn();
} catch (PDOException $e) {
    $totalRecords = 0;
    error_log("Count error: " . $e->getMessage());
}

$totalPages = ceil($totalRecords / $perPage);
$page = min($page, $totalPages);
$offset = ($page - 1) * $perPage;

try {
    // Determine order by clause based on table
    $orderBy = ($type === 'headset_inventory') ? 'id DESC' : 'created_at DESC';
    $query = "SELECT * FROM $table $searchQuery ORDER BY $orderBy LIMIT :limit OFFSET :offset";
    $stmt = $pdo->prepare($query);
    
    foreach ($params as $key => $value) {
        $stmt->bindValue($key, $value);
    }
    
    $stmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
    $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
    $stmt->execute();
    $records = $stmt->fetchAll();
} catch (PDOException $e) {
    $records = [];
    error_log("Fetch error: " . $e->getMessage());
}
?>

<div class="bg-gray-800 rounded-xl border border-gray-700 overflow-hidden shadow">
    <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-700 w-full" style="zoom:85%">
            <thead class="bg-gray-700">
                <tr>
                    <?php if ($type === 'headset'): ?>
                        <th scope="col" class="px-4 py-3 text-left text-xs font-medium text-gray-300 uppercase tracking-wider w-32">Date Issued</th>
                        <th scope="col" class="px-4 py-3 text-left text-xs font-medium text-gray-300 uppercase tracking-wider w-48">Employee ID</th>
                        <th scope="col" class="px-4 py-3 text-left text-xs font-medium text-gray-300 uppercase tracking-wider w-48">Full Name</th>
                        <th scope="col" class="px-4 py-3 text-left text-xs font-medium text-gray-300 uppercase tracking-wider w-40">Department</th>
                        <th scope="col" class="px-4 py-3 text-left text-xs font-medium text-gray-300 uppercase tracking-wider w-48">Operation Manager</th>
                        <th scope="col" class="px-4 py-3 text-left text-xs font-medium text-gray-300 uppercase tracking-wider w-40">Brand/Model</th>
                        <th scope="col" class="px-4 py-3 text-left text-xs font-medium text-gray-300 uppercase tracking-wider w-24">C No</th>
                        <th scope="col" class="px-4 py-3 text-left text-xs font-medium text-gray-300 uppercase tracking-wider w-40">YJack Serial</th>
                        <th scope="col" class="px-4 py-3 text-left text-xs font-medium text-gray-300 uppercase tracking-wider w-32">Extra Foam</th>
                        <th scope="col" class="px-4 py-3 text-left text-xs font-medium text-gray-300 uppercase tracking-wider w-32">Condition</th>
                        <th scope="col" class="px-4 py-3 text-left text-xs font-medium text-gray-300 uppercase tracking-wider w-40">Released By</th>
                        <th scope="col" class="px-4 py-3 text-left text-xs font-medium text-gray-300 uppercase tracking-wider w-32">Release Time</th>
                        <th scope="col" class="px-4 py-3 text-left text-xs font-medium text-gray-300 uppercase tracking-wider w-48">Equipment Status</th>
                        <th scope="col" class="px-4 py-3 text-left text-xs font-medium text-gray-300 uppercase tracking-wider w-40">Received By</th>
                        <th scope="col" class="px-4 py-3 text-left text-xs font-medium text-gray-300 uppercase tracking-wider w-32">Return Time</th>
                        <th scope="col" class="px-4 py-3 text-left text-xs font-medium text-gray-300 uppercase tracking-wider w-32">Return Date</th>
                        <th scope="col" class="px-4 py-3 text-left text-xs font-medium text-gray-300 uppercase tracking-wider w-48">Remarks</th>
                        <th scope="col" class="px-4 py-3 text-left text-xs font-medium text-gray-300 uppercase tracking-wider w-48">Status</th>
                    <?php elseif ($type === 'headset_inventory'): ?>
                        <th scope="col" class="px-4 py-3 text-left text-xs font-medium text-gray-300 uppercase tracking-wider">C No</th>
                        <th scope="col" class="px-4 py-3 text-left text-xs font-medium text-gray-300 uppercase tracking-wider">Brand/Model No</th>
                        <th scope="col" class="px-4 py-3 text-left text-xs font-medium text-gray-300 uppercase tracking-wider">YJack Serial No</th>
                        <th scope="col" class="px-4 py-3 text-left text-xs font-medium text-gray-300 uppercase tracking-wider">Headset Serial No</th>
                        <th scope="col" class="px-4 py-3 text-left text-xs font-medium text-gray-300 uppercase tracking-wider">Status</th>
                        <th scope="col" class="px-4 py-3 text-left text-xs font-medium text-gray-300 uppercase tracking-wider">Remarks</th>
                        <th scope="col" class="px-4 py-3 text-left text-xs font-medium text-gray-300 uppercase tracking-wider">Date Created</th>
                    <?php else: ?>
                        <th scope="col" class="px-4 py-3 text-left text-xs font-medium text-gray-300 uppercase tracking-wider">Date</th>
                        <th scope="col" class="px-4 py-3 text-left text-xs font-medium text-gray-300 uppercase tracking-wider">Station No</th>
                        <th scope="col" class="px-4 py-3 text-left text-xs font-medium text-gray-300 uppercase tracking-wider">Peripheral</th>
                        <th scope="col" class="px-4 py-3 text-left text-xs font-medium text-gray-300 uppercase tracking-wider">Serial No</th>
                        <th scope="col" class="px-4 py-3 text-left text-xs font-medium text-gray-300 uppercase tracking-wider">Issue</th>
                        <th scope="col" class="px-4 py-3 text-left text-xs font-medium text-gray-300 uppercase tracking-wider">Request Form</th>
                        <th scope="col" class="px-4 py-3 text-left text-xs font-medium text-gray-300 uppercase tracking-wider">Email</th>
                        <th scope="col" class="px-4 py-3 text-left text-xs font-medium text-gray-300 uppercase tracking-wider">Remarks</th>
                        <th scope="col" class="px-4 py-3 text-left text-xs font-medium text-gray-300 uppercase tracking-wider">Resolved</th>
                        <th scope="col" class="px-4 py-3 text-left text-xs font-medium text-gray-300 uppercase tracking-wider">Date Resolved</th>
                        <th scope="col" class="px-4 py-3 text-left text-xs font-medium text-gray-300 uppercase tracking-wider">SLT</th>
                        <th scope="col" class="px-4 py-3 text-left text-xs font-medium text-gray-300 uppercase tracking-wider">Status</th>
                    <?php endif; ?>
                    <th scope="col" class="px-4 py-3 text-right text-xs font-medium text-gray-300 uppercase tracking-wider">Actions</th>
                </tr>
            </thead>
            <tbody class="bg-gray-800 divide-y divide-gray-700">
                <?php if (empty($records)): ?>
                    <tr>
                        <td colspan="<?= $type === 'headset' ? 18 : ($type === 'headset_inventory' ? 7 : 12) ?>" class="px-6 py-4 text-center text-gray-400">
                            No records found
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($records as $record): ?>
                    <tr class="hover:bg-gray-700/50">
                        <?php if ($type === 'headset'): ?>
                            <!-- Headset Tracker Table Content (same as before) -->
                            <td class="px-4 py-4 whitespace-nowrap overflow-hidden text-ellipsis max-w-xs">
                                <div class="text-sm text-gray-300" ><?= date('M d, Y', strtotime($record['date_issued'])) ?></div>
                            </td>
                            <td class="px-4 py-4 whitespace-nowrap overflow-hidden text-ellipsis max-w-xs">
                                <div class="text-sm font-medium text-gray-100"  title="<?= htmlspecialchars($record['employee_id'] ?? '') ?>"><?= htmlspecialchars($record['employee_id']) ?></div>
                            </td>
                            <td class="px-4 py-4 whitespace-nowrap overflow-hidden text-ellipsis max-w-xs">
                                <div class="text-sm text-gray-300"  title="<?= htmlspecialchars($record['full_name'] ?? '') ?>"><?= htmlspecialchars($record['full_name']) ?></div>
                            </td>
                            <td class="px-4 py-4 whitespace-nowrap overflow-hidden text-ellipsis max-w-xs">
                                <div class="text-sm text-gray-300"  title="<?= htmlspecialchars($record['department'] ?? '') ?>"><?= htmlspecialchars($record['department']) ?></div>
                            </td>
                            <td class="px-4 py-4 whitespace-nowrap overflow-hidden text-ellipsis max-w-xs">
                                <div class="text-sm text-gray-300"  title="<?= htmlspecialchars($record['operation_manager'] ?? '') ?>"><?= htmlspecialchars($record['operation_manager']) ?></div>
                            </td>
                            <td class="px-4 py-4 whitespace-nowrap overflow-hidden text-ellipsis max-w-xs">
                                <div class="text-sm text-gray-300" title="<?= htmlspecialchars($record['brand_model_no'] ?? '') ?>"><?= htmlspecialchars($record['brand_model_no']) ?></div>
                            </td>
                            <td class="px-4 py-4 whitespace-nowrap overflow-hidden text-ellipsis max-w-xs">
                                <div class="text-sm text-gray-300" title="<?= htmlspecialchars($record['c_no'] ?? 'N/A') ?>"><?= htmlspecialchars($record['c_no'] ?? 'N/A') ?></div>
                            </td>
                            <td class="px-4 py-4 whitespace-nowrap overflow-hidden text-ellipsis max-w-xs">
                                <div class="text-sm text-gray-300" title="<?= htmlspecialchars($record['yjack_serial_no'] ?? 'N/A') ?>"><?= htmlspecialchars($record['yjack_serial_no'] ?? 'N/A') ?></div>
                            </td>
                            <td class="px-4 py-4 whitespace-nowrap overflow-hidden text-ellipsis max-w-xs">
                                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full <?= $record['w_xtra_foam'] === 'YES' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' ?>">
                                    <?= $record['w_xtra_foam'] ?>
                                </span>
                            </td>
                            <td class="px-4 py-4 whitespace-nowrap overflow-hidden text-ellipsis max-w-xs">
                                <div class="text-sm text-gray-300" title="<?= htmlspecialchars($record['yjack_serial_no'] ?? 'N/A') ?>"><?= htmlspecialchars($record['_condition']) ?></div>
                            </td>
                            <td class="px-4 py-4 whitespace-nowrap overflow-hidden text-ellipsis max-w-xs">
                                <div class="text-sm text-gray-300" title="<?= htmlspecialchars($record['release_by'] ?? '') ?>"><?= htmlspecialchars($record['release_by']) ?></div>
                            </td>
                            <td class="px-4 py-4 whitespace-nowrap overflow-hidden text-ellipsis max-w-xs">
                                <div class="text-sm text-gray-300"><?= date('g:i A', strtotime($record['release_time'])) ?></div>
                            </td>
                            <td class="px-4 py-4 whitespace-nowrap overflow-hidden text-ellipsis max-w-xs">
                                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full 
                                    <?= $record['equipment_status'] === 'WORKING ALL ITEMS' ? 'bg-green-100 text-green-800' : 
                                       ($record['equipment_status'] === 'NOT WORKING - HEADSET' ? 'bg-red-100 text-red-800' : 
                                       ($record['equipment_status'] === 'NOT WORKING - YJACK' ? 'bg-red-100 text-red-800' : 
                                       ($record['equipment_status'] === 'WITH ISSUE' ? 'bg-red-100 text-red-800' : 'bg-red-100 text-red-800'))) ?>">
                                    <?= $record['equipment_status'] ?>
                                </span>
                            </td>
                            <td class="px-4 py-4 whitespace-nowrap overflow-hidden text-ellipsis max-w-xs">
                                <div class="text-sm text-gray-300" title="<?= htmlspecialchars($record['received_by'] ?? 'PENDING') ?>"><?= $record['received_by'] ? htmlspecialchars($record['received_by']) : 'PENDING' ?></div>
                            </td>
                            <td class="px-4 py-4 whitespace-nowrap overflow-hidden text-ellipsis max-w-xs">
                                <div class="text-sm text-gray-300" title="<?= htmlspecialchars($record['return_time'] ?? 'PENDING') ?>">
                                    <?= $record['return_time'] ? date('g:i A', strtotime($record['return_time'])) : 'PENDING' ?>
                                </div>
                            </td>
                            <td class="px-4 py-4 whitespace-nowrap overflow-hidden text-ellipsis max-w-xs">
                                <div class="text-sm text-gray-300" title="<?= htmlspecialchars($record['return_date'] ?? 'PENDING') ?>"><?= $record['return_date'] ? date('M d, Y', strtotime($record['return_date'])) : 'PENDING' ?></div>
                            </td>
                            <td class="px-4 py-4 whitespace-nowrap overflow-hidden text-ellipsis max-w-xs">
                                <div class="text-sm text-gray-300" title="<?= htmlspecialchars($record['remarks'] ?? 'PENDING') ?>"><?= htmlspecialchars($record['remarks'] ?? 'PENDING') ?></div>
                            </td>
                            <td class="px-4 py-4 whitespace-nowrap overflow-hidden text-ellipsis max-w-xs">
                                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full <?= $record['status'] === 'RETURNED' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' ?>">
                                    <?= $record['status'] ?>
                                </span>
                            </td>
                            
                        <?php elseif ($type === 'headset_inventory'): ?>
                            <!-- Headset Inventory Table Content -->
                            <td class="px-4 py-4 whitespace-nowrap overflow-hidden text-ellipsis max-w-xs">
                                <div class="text-sm font-medium text-gray-100"><?= htmlspecialchars($record['c_no']) ?></div>
                            </td>
                            <td class="px-4 py-4 whitespace-nowrap overflow-hidden text-ellipsis max-w-xs">
                                <div class="text-sm text-gray-300"><?= htmlspecialchars($record['brand']) ?></div>
                            </td>
                            <td class="px-4 py-4 whitespace-nowrap overflow-hidden text-ellipsis max-w-xs">
                                <div class="text-sm text-gray-300" title="<?= htmlspecialchars($record['yjack_serial_no'] ?? '') ?>"><?= htmlspecialchars($record['yjack_serial_no']) ?></div>
                            </td>
                            <td class="px-4 py-4 whitespace-nowrap overflow-hidden text-ellipsis max-w-xs">
                                <div class="text-sm text-gray-300" title="<?= htmlspecialchars($record['headset_serial_no'] ?? '') ?>"><?= htmlspecialchars($record['headset_serial_no']) ?></div>
                            </td>
                            <td class="px-4 py-4 whitespace-nowrap overflow-hidden text-ellipsis max-w-xs">
                                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full 
                                    <?= $record['status'] === 'AVAILABLE' ? 'bg-green-100 text-green-800' : 
                                       ($record['status'] === 'IN USE' ? 'bg-yellow-100 text-yellow-800' : 
                                       ($record['status'] === 'DEFECTIVE' ? 'bg-red-100 text-red-800' : 
                                       ($record['status'] === 'MAINTENANCE' ? 'bg-orange-100 text-orange-800' : 'bg-gray-100 text-gray-800'))) ?>">
                                    <?= $record['status'] ?>
                                </span>
                            </td>
                            <td class="px-4 py-4 whitespace-nowrap overflow-hidden text-ellipsis max-w-xs">
                                <div class="text-sm text-gray-300" title="<?= htmlspecialchars($record['remarks'] ?? 'N/A') ?>"><?= htmlspecialchars($record['remarks'] ?? 'N/A') ?></div>
                            </td>
                            <td class="px-4 py-4 whitespace-nowrap overflow-hidden text-ellipsis max-w-xs">
                                <div class="text-sm text-gray-300"><?= date('M d, Y', strtotime($record['date_created'])) ?></div>
                            </td>
                            
                        <?php else: ?>
                            <!-- Peripherals Table Content (same as before) -->
                            <td class="px-4 py-4 whitespace-nowrap overflow-hidden text-ellipsis max-w-xs">
                                <div class="text-sm text-gray-300"><?= date('M d, Y', strtotime($record['request_date'])) ?></div>
                            </td>
                            <td class="px-4 py-4 whitespace-nowrap overflow-hidden text-ellipsis max-w-xs">
                                <div class="text-sm font-medium text-gray-100" title="<?= htmlspecialchars($record['stn_no'] ?? '') ?>"><?= htmlspecialchars($record['stn_no']) ?></div>
                            </td>
                            <td class="px-4 py-4 whitespace-nowrap overflow-hidden text-ellipsis max-w-xs">
                                <div class="text-sm text-gray-300" title="<?= htmlspecialchars($record['peripheral_type'] ?? '') ?>"><?= htmlspecialchars($record['peripheral_type']) ?></div>
                            </td>
                            <td class="px-4 py-4 whitespace-nowrap overflow-hidden text-ellipsis max-w-xs">
                                <div class="text-sm text-gray-300" title="<?= htmlspecialchars($record['serial_no'] ?? 'N/A') ?>"><?= htmlspecialchars($record['serial_no'] ?? 'N/A') ?></div>
                            </td>
                            <td class="px-4 py-4 whitespace-nowrap overflow-hidden text-ellipsis max-w-xs">
                                <div class="text-sm text-gray-300" title="<?= htmlspecialchars($record['issue'] ?? '') ?>"><?= htmlspecialchars($record['issue']) ?></div>
                            </td>
                            <td class="px-4 py-4 whitespace-nowrap overflow-hidden text-ellipsis max-w-xs">
                                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full <?= $record['request_form'] === 'YES' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' ?>">
                                    <?= $record['request_form'] ?>
                                </span>
                            </td>
                            <td class="px-4 py-4 whitespace-nowrap overflow-hidden text-ellipsis max-w-xs">
                                <?php if (!$record['email_sent']): ?>
                                    <a href="send_peripheral_email.php?send_email=<?= $record['id'] ?>" title="Send Email" class="text-blue-500 hover:text-blue-400 mr-3">
                                        <i class="fas fa-envelope"></i>
                                    </a>
                                <?php else: ?>
                                    <span title="Email sent on <?= date('M d, Y g:i A', strtotime($record['email_sent_at'])) ?>" class="text-green-500 mr-3">
                                        <i class="fas fa-check-circle"></i>
                                    </span>
                                <?php endif; ?>
                            </td>
                            <td class="px-4 py-4 whitespace-nowrap overflow-hidden text-ellipsis max-w-xs">
                                <div class="text-sm text-gray-300" title="<?= htmlspecialchars($record['remarks'] ?? 'N/A') ?>"><?= htmlspecialchars($record['remarks'] ?? 'N/A') ?></div>
                            </td>
                            <td class="px-4 py-4 whitespace-nowrap overflow-hidden text-ellipsis max-w-xs">
                                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full <?= $record['resolved'] === 'YES' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' ?>">
                                    <?= $record['resolved'] ?>
                                </span>
                            </td>
                            <td class="px-4 py-4 whitespace-nowrap overflow-hidden text-ellipsis max-w-xs">
                                <div class="text-sm text-gray-300">
                                    <?= $record['date_resolved'] ? date('M d, Y', strtotime($record['date_resolved'])) : 'N/A' ?>
                                </div>
                            </td>
                            <td class="px-4 py-4 whitespace-nowrap overflow-hidden text-ellipsis max-w-xs">
                                <div class="text-sm text-gray-300" title="<?= htmlspecialchars($record['slt'] ?? '') ?>"><?= htmlspecialchars($record['slt']) ?></div>
                            </td>
                            <td class="px-4 py-4 whitespace-nowrap overflow-hidden text-ellipsis max-w-xs">
                                <div class="text-sm text-gray-300" title="<?= htmlspecialchars($record['status'] ?? '') ?>"><?= htmlspecialchars($record['status']) ?></div>
                            </td>
                        <?php endif; ?>
                        <td class="px-4 py-4 whitespace-nowrap text-right text-sm font-medium">
                            <?php if ($type === 'headset'): ?>
                                <a href="headset_form.php?id=<?= $record['id'] ?>" title="Edit record" class="text-primary-500 hover:text-primary-400 mr-3">
                                    <i class="fas fa-edit"></i>
                                </a>
                                <?php if ($record['status'] === 'RETURNED'): ?>
                                    <span title="Returned on <?= $record['return_date'] ? date('M d, Y', strtotime($record['return_date'])) : '' ?> at <?= $record['return_time'] ? date('g:i A', strtotime($record['return_time'])) : '' ?>" class="text-green-500 mr-3">
                                        <i class="fas fa-check-circle"></i>
                                    </span>
                                <?php else: ?>
                                    <a href="#" onclick="event.preventDefault(); showReturnModal(<?= $record['id'] ?>)" 
                                    title="Mark as Returned" 
                                    class="text-purple-500 hover:text-purple-400 mr-3">
                                        <i class="fas fa-undo"></i>
                                    </a>
                                <?php endif; ?>
                            <?php elseif ($type === 'headset_inventory'): ?>
                                <a href="headset_inventory_form.php?id=<?= $record['id'] ?>" title="Edit record" class="text-primary-500 hover:text-primary-400 mr-3">
                                    <i class="fas fa-edit"></i>
                                </a>
                            <?php else: ?>
                                <!-- peripheral actions -->
                            <?php endif; ?>
                            <a href="#" onclick="event.preventDefault(); showDeleteModal(<?= $record['id'] ?>, '<?= $type ?>')" class="text-red-500 hover:text-red-400" title="Delete record">
                                <i class="fas fa-trash"></i>
                            </a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Pagination -->
<?php if ($totalPages > 1): ?>
<div class="mt-6 flex items-center justify-between">
    <div class="text-sm text-gray-400">
        Showing <?= ($offset + 1) ?> to <?= min($offset + $perPage, $totalRecords) ?> of <?= $totalRecords ?> records
    </div>
    <div class="flex gap-1">
        <?php if ($page > 1): ?>
            <a href="#" data-page="1" class="pagination-link px-3 py-1 rounded-lg border border-gray-600 text-gray-300 hover:bg-gray-700">
                <i class="fas fa-angle-double-left"></i>
            </a>
            <a href="#" data-page="<?= $page - 1 ?>" class="pagination-link px-3 py-1 rounded-lg border border-gray-600 text-gray-300 hover:bg-gray-700">
                <i class="fas fa-angle-left"></i>
            </a>
        <?php endif; ?>

        <?php 
        $startPage = max(1, $page - 2);
        $endPage = min($totalPages, $page + 2);
        
        for ($i = $startPage; $i <= $endPage; $i++): ?>
            <a href="#" data-page="<?= $i ?>" class="pagination-link px-3 py-1 rounded-lg border <?= $i == $page ? 'bg-primary-600 border-primary-600 text-white' : 'border-gray-600 text-gray-300 hover:bg-gray-700' ?>">
                <?= $i ?>
            </a>
        <?php endfor; ?>

        <?php if ($page < $totalPages): ?>
            <a href="#" data-page="<?= $page + 1 ?>" class="pagination-link px-3 py-1 rounded-lg border border-gray-600 text-gray-300 hover:bg-gray-700">
                <i class="fas fa-angle-right"></i>
            </a>
            <a href="#" data-page="<?= $totalPages ?>" class="pagination-link px-3 py-1 rounded-lg border border-gray-600 text-gray-300 hover:bg-gray-700">
                <i class="fas fa-angle-double-right"></i>
            </a>
        <?php endif; ?>
    </div>
</div>
<?php endif; ?>