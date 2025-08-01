<?php
require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../../includes/functions.php';

$search = isset($_POST['search']) ? trim($_POST['search']) : (isset($_GET['search']) ? trim($_GET['search']) : '');
$page = isset($_POST['page']) ? (int)$_POST['page'] : (isset($_GET['page']) ? (int)$_GET['page'] : 1);
$type = isset($_POST['type']) ? $_POST['type'] : (isset($_GET['tab']) ? $_GET['tab'] : 'absenteeism');
$page = max($page, 1);

$table = ($type === 'tardiness') ? 'tardiness' : 'absenteeism';
$perPage = 10;
$searchQuery = '';
$params = [];

if (!empty($search)) {
    $searchQuery = "WHERE (employee_id LIKE :search OR full_name LIKE :search)";
    $params[':search'] = "%$search%";
}

try {
    $countStmt = $pdo->prepare("SELECT COUNT(*) FROM $table $searchQuery");
    if (!empty($search)) {
        foreach ($params as $key => $value) {
            $countStmt->bindValue($key, $value);
        }
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
    
    if (!empty($search)) {
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }
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
        <table class="min-w-full divide-y divide-gray-700">
            <thead class="bg-gray-700">
                <tr>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-300 uppercase tracking-wider">Employee ID</th>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-300 uppercase tracking-wider">Full Name</th>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-300 uppercase tracking-wider">Department</th>
                    <?php if ($type === 'absenteeism'): ?>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-300 uppercase tracking-wider">Date of Absence</th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-300 uppercase tracking-wider">Shift</th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-300 uppercase tracking-wider">Followed Procedure</th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-300 uppercase tracking-wider">Coverage</th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-300 uppercase tracking-wider">Coverage Type</th>
                    <?php else: ?>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-300 uppercase tracking-wider">Date of Tardiness</th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-300 uppercase tracking-wider">Type</th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-300 uppercase tracking-wider">Minutes</th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-300 uppercase tracking-wider">Shift</th>
                    <?php endif; ?>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-300 uppercase tracking-wider">Incident Report</th>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-300 uppercase tracking-wider">Reported By</th>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-300 uppercase tracking-wider">Time Reported</th>
                    <th scope="col" class="px-6 py-3 text-right text-xs font-medium text-gray-300 uppercase tracking-wider">Actions</th>
                </tr>
            </thead>
            <tbody class="bg-gray-800 divide-y divide-gray-700">
                <?php if (empty($records)): ?>
                    <tr>
                        <td colspan="<?= $type === 'absenteeism' ? 9 : 8 ?>" class="px-6 py-4 text-center text-gray-400">
                            No records found
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($records as $record): ?>
                    <tr class="hover:bg-gray-700/50">

                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="text-sm font-medium text-gray-100" style="text-transform: uppercase;"><?= htmlspecialchars($record['employee_id']) ?></div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="text-sm text-gray-300" style="text-transform: uppercase;"><?= htmlspecialchars($record['full_name']) ?></div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="text-sm text-gray-300" style="text-transform: uppercase;"><?= htmlspecialchars($record['department']) ?></div>
                        </td>


                        <?php if ($type === 'absenteeism'): ?>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm text-gray-300"><?= date('M d, Y', strtotime($record['date_of_absent'])) ?></div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm text-gray-300"><?= htmlspecialchars($record['shift']) ?></div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full <?= $record['follow_call_in_procedure'] === 'NO' ? 'bg-red-100 text-red-800' : 'bg-green-100 text-green-800' ?>" >
                                    <?= $record['follow_call_in_procedure'] ?>
                                </span>
                            </td>
                            <td class="px-6 py-4">
                                <div class="text-sm text-gray-300" style="text-transform: uppercase;"><?= htmlspecialchars($record['coverage']) ?></div>
                            </td>
                            <td class="px-6 py-4">
                                <div class="text-sm text-gray-300"><?= htmlspecialchars($record['coverage_type']) ?></div>
                            </td>
                        <?php else: ?>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm text-gray-300"><?= date('M d, Y', strtotime($record['date_of_incident'])) ?></div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full <?= $record['types'] === 'Late' ? 'bg-yellow-100 text-yellow-800' : 'bg-red-100 text-red-800' ?> ">
                                    <?= $record['types'] ?>
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm text-gray-300"><?= $record['minutes_late'] ?></div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm text-gray-300"><?= $record['shift'] ?></div>
                            </td>
                        <?php endif; ?>
                        
                        
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="text-sm text-gray-300" style="text-transform: uppercase;"><?= $record['ir_form'] ?></div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="text-sm text-gray-300"><?= htmlspecialchars($record['sub_name']) ?></div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="text-sm text-gray-300"><?= date('g:i A', strtotime($record['timestamp'])) ?></div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                            <?php if (!$record['email_sent']): ?>
                                <a href="send_email.php?send_email=<?= $record['id'] ?>&type=<?= $type ?>" title="Send Email" class="text-blue-500 hover:text-blue-400 mr-3">
                                    <i class="fas fa-envelope"></i>
                                </a>
                            <?php else: ?>
                                <span title="Email sent on <?= date('M d, Y g:i A', strtotime($record['email_sent_at'])) ?>" class="text-green-500 mr-3">
                                    <i class="fas fa-check-circle"></i>
                                </span>
                            <?php endif; ?>
                            <a href="attendance_form.php?id=<?= $record['id'] ?>&type=<?= $type ?>" title="Edit record" class="text-primary-500 hover:text-primary-400 mr-3">
                                <i class="fas fa-edit"></i>
                            </a>
                            <a href="#" onclick="event.preventDefault(); showHistoryModal(<?= $record['id'] ?>, '<?= $type ?>')" title="View History" class="text-purple-500 hover:text-purple-400 mr-3">
                                <i class="fas fa-history"></i>
                            </a>
                            <a href="attendance.php?delete=<?= $record['id'] ?>&type=<?= $type ?>" class="text-red-500 hover:text-red-400" title="Delete record" onclick="return confirm('Are you sure you want to delete this record?')">
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

<div id="historyModal" class="hidden fixed inset-0 bg-gray-900/80 overflow-y-auto h-full w-full z-50">
    <div class="relative top-20 mx-auto p-5 w-11/12 md:w-3/4 lg:w-1/2">
        <div class="bg-gray-800 rounded-lg shadow-xl overflow-hidden border border-gray-700">
            <!-- Modal Header -->
            <div class="bg-gray-700 px-6 py-4 border-b border-gray-600">
                <h3 class="text-lg font-semibold text-gray-100">Assignment History</h3>
            </div>
            
            <!-- Modal Content -->
            <div class="p-6">
                <!-- Timeline Container with Scroll -->
                <div class="border-l-2 border-gray-600 pl-6 pb-6 max-h-[500px] overflow-y-auto">
                    <!-- Scrollable History Items -->
                    <div id="historyTableBody" class="space-y-6">
                        <!-- History data will be loaded here -->
                    </div>
                </div>
            </div>
            
            <!-- Modal Footer -->
            <div class="bg-gray-700 px-6 py-4 border-t border-gray-600 flex justify-end">
                <button onclick="closeHistoryModal()" class="px-4 py-2 bg-gray-600 text-gray-100 rounded-md hover:bg-gray-500 focus:outline-none focus:ring-2 focus:ring-gray-500">
                    Close
                </button>
            </div>
        </div>
    </div>
</div>