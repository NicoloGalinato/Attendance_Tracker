<?php
require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../../includes/functions.php';

// Get all filter parameters with proper fallbacks
$search = isset($_POST['search']) ? trim($_POST['search']) : '';
$page = isset($_POST['page']) ? (int)$_POST['page'] : (isset($_GET['page']) ? (int)$_GET['page'] : 1);
$type = isset($_POST['type']) ? $_POST['type'] : (isset($_GET['tab']) ? $_GET['tab'] : 'headset');
$dateFrom = isset($_POST['date_from']) ? $_POST['date_from'] : (isset($_GET['from']) ? $_GET['from'] : '');
$dateTo = isset($_POST['date_to']) ? $_POST['date_to'] : (isset($_GET['to']) ? $_GET['to'] : '');
$statusFilter = isset($_POST['status']) ? $_POST['status'] : (isset($_GET['status']) ? $_GET['status'] : '');

// Ensure we have the table and type
if (!isset($type)) $type = 'headset';
$table = ($type === 'peripherals') ? 'request_peripherals' : 'headset_tracker';
$perPage = 15;

// Initialize where clauses and parameters
$whereClauses = [];
$params = [];

// Add search filter
if (!empty($search)) {
    if ($type === 'headset') {
        $whereClauses[] = "(employee_id LIKE :search OR full_name LIKE :search)";
    } else {
        $whereClauses[] = "(stn_no LIKE :search OR serial_no LIKE :search)";
    }
    $params[':search'] = "%$search%";
}

// Add date filter
if (!empty($dateFrom)) {
    $dateField = ($type === 'peripherals') ? 'request_date' : 'date_issued';
    $whereClauses[] = "$dateField >= :date_from";
    $params[':date_from'] = $dateFrom;
}

if (!empty($dateTo)) {
    $dateField = ($type === 'peripherals') ? 'request_date' : 'date_issued';
    $whereClauses[] = "$dateField <= :date_to";
    $params[':date_to'] = $dateTo;
}

// Add status filter
if (!empty($statusFilter)) {
    if ($type === 'headset') {
        $whereClauses[] = "equipment_status = :status";
        $params[':status'] = $statusFilter;
    } else {
        if ($statusFilter === 'RESOLVED') {
            $whereClauses[] = "resolved = 'YES'";
        } else if ($statusFilter === 'PENDING') {
            $whereClauses[] = "resolved = 'NO'";
        }
    }
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
}

$totalPages = ceil($totalRecords / $perPage);
$page = min($page, $totalPages);
$offset = ($page - 1) * $perPage;

try {
    $query = "SELECT * FROM $table $searchQuery ORDER BY created_at DESC LIMIT :limit OFFSET :offset";
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
                        <th scope="col" class="px-4 py-3 text-left text-xs font-medium text-gray-300 uppercase tracking-wider w-32">Department</th>
                        <th scope="col" class="px-4 py-3 text-left text-xs font-medium text-gray-300 uppercase tracking-wider w-48">Operation Manager</th>
                        <th scope="col" class="px-4 py-3 text-left text-xs font-medium text-gray-300 uppercase tracking-wider w-32">Brand/Model</th>
                        <th scope="col" class="px-4 py-3 text-left text-xs font-medium text-gray-300 uppercase tracking-wider w-32">C No</th>
                        <th scope="col" class="px-4 py-3 text-left text-xs font-medium text-gray-300 uppercase tracking-wider w-32">YJack Serial</th>
                        <th scope="col" class="px-4 py-3 text-left text-xs font-medium text-gray-300 uppercase tracking-wider w-32">Extra Foam</th>
                        <th scope="col" class="px-4 py-3 text-left text-xs font-medium text-gray-300 uppercase tracking-wider w-32">Condition</th>
                        <th scope="col" class="px-4 py-3 text-left text-xs font-medium text-gray-300 uppercase tracking-wider w-32">Released By</th>
                        <th scope="col" class="px-4 py-3 text-left text-xs font-medium text-gray-300 uppercase tracking-wider w-32">Release Time</th>
                        <th scope="col" class="px-4 py-3 text-left text-xs font-medium text-gray-300 uppercase tracking-wider w-32">Received By</th>
                        <th scope="col" class="px-4 py-3 text-left text-xs font-medium text-gray-300 uppercase tracking-wider w-32">Return Date/Time</th>
                        <th scope="col" class="px-4 py-3 text-left text-xs font-medium text-gray-300 uppercase tracking-wider w-32">Status</th>
                        <th scope="col" class="px-4 py-3 text-left text-xs font-medium text-gray-300 uppercase tracking-wider w-32">Remarks</th>
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
                    <?php endif; ?>
                    <th scope="col" class="px-4 py-3 text-right text-xs font-medium text-gray-300 uppercase tracking-wider">Actions</th>
                </tr>
            </thead>
            <tbody class="bg-gray-800 divide-y divide-gray-700">
                <?php if (empty($records)): ?>
                    <tr>
                        <td colspan="<?= $type === 'headset' ? 15 : 12 ?>" class="px-6 py-4 text-center text-gray-400">
                            No records found
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($records as $record): ?>
                    <tr class="hover:bg-gray-700/50">
                        <?php if ($type === 'headset'): ?>
                            <td class="px-4 py-4 whitespace-nowrap overflow-hidden text-ellipsis max-w-xs">
                                <div class="text-sm text-gray-300"><?= date('M d, Y', strtotime($record['date_issued'])) ?></div>
                            </td>
                            <td class="px-4 py-4 whitespace-nowrap overflow-hidden text-ellipsis max-w-xs">
                                <div class="text-sm font-medium text-gray-100"><?= htmlspecialchars($record['employee_id']) ?></div>
                            </td>
                            <td class="px-4 py-4 whitespace-nowrap overflow-hidden text-ellipsis max-w-xs">
                                <div class="text-sm text-gray-300"><?= htmlspecialchars($record['full_name']) ?></div>
                            </td>
                            <td class="px-4 py-4 whitespace-nowrap overflow-hidden text-ellipsis max-w-xs">
                                <div class="text-sm text-gray-300"><?= htmlspecialchars($record['department']) ?></div>
                            </td>
                            <td class="px-4 py-4 whitespace-nowrap overflow-hidden text-ellipsis max-w-xs">
                                <div class="text-sm text-gray-300"><?= htmlspecialchars($record['operation_manager']) ?></div>
                            </td>
                            <td class="px-4 py-4 whitespace-nowrap overflow-hidden text-ellipsis max-w-xs">
                                <div class="text-sm text-gray-300"><?= htmlspecialchars($record['brand_model_no']) ?></div>
                            </td>
                            <td class="px-4 py-4 whitespace-nowrap overflow-hidden text-ellipsis max-w-xs">
                                <div class="text-sm text-gray-300"><?= htmlspecialchars($record['c_no'] ?? 'N/A') ?></div>
                            </td>
                            <td class="px-4 py-4 whitespace-nowrap overflow-hidden text-ellipsis max-w-xs">
                                <div class="text-sm text-gray-300"><?= htmlspecialchars($record['yjack_serial_no'] ?? 'N/A') ?></div>
                            </td>
                            <td class="px-4 py-4 whitespace-nowrap overflow-hidden text-ellipsis max-w-xs">
                                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full <?= $record['w_xtra_foam'] === 'YES' ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-800' ?>">
                                    <?= $record['w_xtra_foam'] ?>
                                </span>
                            </td>
                            <td class="px-4 py-4 whitespace-nowrap overflow-hidden text-ellipsis max-w-xs">
                                <div class="text-sm text-gray-300"><?= htmlspecialchars($record['_condition']) ?></div>
                            </td>
                            <td class="px-4 py-4 whitespace-nowrap overflow-hidden text-ellipsis max-w-xs">
                                <div class="text-sm text-gray-300"><?= htmlspecialchars($record['release_by']) ?></div>
                            </td>
                            <td class="px-4 py-4 whitespace-nowrap overflow-hidden text-ellipsis max-w-xs">
                                <div class="text-sm text-gray-300"><?= date('g:i A', strtotime($record['release_time'])) ?></div>
                            </td>
                            <td class="px-4 py-4 whitespace-nowrap overflow-hidden text-ellipsis max-w-xs">
                                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full <?= $record['received_by'] === 'PENDING' ? 'bg-red-100 text-red-800' : '' ?>">
                                    <?= $record['received_by'] ?>
                                </span>
                            </td>
                            <td class="px-4 py-4 whitespace-nowrap overflow-hidden text-ellipsis max-w-xs">
                                <div class="text-sm text-gray-300">
                                    <?php if ($record['return_date']): ?>
                                        <?= date('M d, Y', strtotime($record['return_date'])) ?> at <?= date('g:i A', strtotime($record['return_time'])) ?>
                                    <?php else: ?>
                                        N/A
                                    <?php endif; ?>
                                </div>
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
                                <div class="text-sm text-gray-300"><?= htmlspecialchars($record['remarks'] ?? 'N/A') ?></div>
                            </td>
                        <?php else: ?>
                            <td class="px-4 py-4 whitespace-nowrap overflow-hidden text-ellipsis max-w-xs">
                                <div class="text-sm text-gray-300"><?= date('M d, Y', strtotime($record['request_date'])) ?></div>
                            </td>
                            <td class="px-4 py-4 whitespace-nowrap overflow-hidden text-ellipsis max-w-xs">
                                <div class="text-sm font-medium text-gray-100"><?= htmlspecialchars($record['stn_no']) ?></div>
                            </td>
                            <td class="px-4 py-4 whitespace-nowrap overflow-hidden text-ellipsis max-w-xs">
                                <div class="text-sm text-gray-300"><?= htmlspecialchars($record['peripheral_type']) ?></div>
                            </td>
                            <td class="px-4 py-4 whitespace-nowrap overflow-hidden text-ellipsis max-w-xs">
                                <div class="text-sm text-gray-300"><?= htmlspecialchars($record['serial_no'] ?? 'N/A') ?></div>
                            </td>
                            <td class="px-4 py-4 whitespace-nowrap overflow-hidden text-ellipsis max-w-xs">
                                <div class="text-sm text-gray-300"><?= htmlspecialchars($record['issue']) ?></div>
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
                                <div class="text-sm text-gray-300"><?= htmlspecialchars($record['remarks'] ?? 'N/A') ?></div>
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
                                <div class="text-sm text-gray-300"><?= htmlspecialchars($record['slt']) ?></div>
                            </td>
                        <?php endif; ?>
                        <td class="px-4 py-4 whitespace-nowrap text-right text-sm font-medium">
                            <?php if ($type === 'headset'): ?>
                                <a href="headset_form.php?id=<?= $record['id'] ?>" title="Edit record" class="text-primary-500 hover:text-primary-400 mr-3">
                                    <i class="fas fa-edit"></i>
                                </a>
                                <?php if ($record['equipment_status'] !== 'RETURNED'): ?>
                                    <a href="#" onclick="event.preventDefault(); returnEquipment(<?= $record['id'] ?>)" title="Mark as Returned" class="text-green-500 hover:text-green-400 mr-3">
                                        <i class="fas fa-undo"></i>
                                    </a>
                                <?php endif; ?>
                            <?php else: ?>
                                <a href="peripherals_form.php?id=<?= $record['id'] ?>" title="Edit record" class="text-primary-500 hover:text-primary-400 mr-3">
                                    <i class="fas fa-edit"></i>
                                </a>
                                <?php if ($record['resolved'] !== 'YES'): ?>
                                    <a href="#" onclick="event.preventDefault(); resolveRequest(<?= $record['id'] ?>)" title="Mark as Resolved" class="text-green-500 hover:text-green-400 mr-3">
                                        <i class="fas fa-check"></i>
                                    </a>
                                <?php endif; ?>
                            <?php endif; ?>
                            <a href="#" onclick="event.preventDefault(); showDeleteModal(<?= $record['id'] ?>, '<?= $type === 'headset' ? 'headset' : 'peripherals' ?>')" class="text-red-500 hover:text-red-400" title="Delete record">
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