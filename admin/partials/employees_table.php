<?php
require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../../includes/functions.php';

// Unified parameter handling
$search = '';
$department = '';
$supervisor = '';
$operationManager = '';
$status = '';
$page = 1;
$selectedEmployees = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $search = $_POST['search'] ?? '';
    $department = $_POST['department'] ?? '';
    $supervisor = $_POST['supervisor'] ?? '';
    $operationManager = $_POST['operation_manager'] ?? '';
    $status = $_POST['status'] ?? '';
    $page = isset($_POST['page']) ? (int)$_POST['page'] : 1;
    
    if (!empty($_POST['selected_employees_json'])) {
        $selectedEmployees = json_decode($_POST['selected_employees_json'], true) ?? [];
    }
} else {
    $search = $_GET['search'] ?? '';
    $department = $_GET['department'] ?? '';
    $supervisor = $_GET['supervisor'] ?? '';
    $operationManager = $_GET['operation_manager'] ?? '';
    $status = $_GET['status'] ?? '';
    $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
}

// Build query efficiently
$whereConditions = [];
$params = [];

if (!empty($search)) {
    $whereConditions[] = "(employee_id LIKE ? OR full_name LIKE ?)";
    $searchTerm = "%$search%";
    $params[] = $searchTerm;
    $params[] = $searchTerm;
}

if (!empty($department)) {
    $whereConditions[] = "department = ?";
    $params[] = $department;
}

if (!empty($supervisor)) {
    $whereConditions[] = "supervisor = ?";
    $params[] = $supervisor;
}

if (!empty($operationManager)) {
    $whereConditions[] = "operation_manager = ?";
    $params[] = $operationManager;
}

if ($status !== '') {
    $whereConditions[] = "is_active = ?";
    $params[] = $status;
}

$whereClause = empty($whereConditions) ? '' : 'WHERE ' . implode(' AND ', $whereConditions);
$page = max($page, 1);
$perPage = 10;
$offset = ($page - 1) * $perPage;

// Optimized count query
try {
    $countSql = "SELECT COUNT(*) FROM employees $whereClause";
    $countStmt = $pdo->prepare($countSql);
    $countStmt->execute($params);
    $totalEmployees = $countStmt->fetchColumn();
} catch (PDOException $e) {
    $totalEmployees = 0;
}

$totalPages = ceil($totalEmployees / $perPage);
$page = min($page, max($totalPages, 1));

// Optimized data query
try {
    $sql = "SELECT id, employee_id, full_name, department, supervisor, operation_manager, email, created_at, is_active 
            FROM employees 
            $whereClause 
            ORDER BY created_at DESC 
            LIMIT ? OFFSET ?";
    
    $stmt = $pdo->prepare($sql);
    
    // Bind all parameters
    $paramIndex = 1;
    foreach ($params as $param) {
        $stmt->bindValue($paramIndex, $param);
        $paramIndex++;
    }
    
    $stmt->bindValue($paramIndex, $perPage, PDO::PARAM_INT);
    $stmt->bindValue($paramIndex + 1, $offset, PDO::PARAM_INT);
    
    $stmt->execute();
    $employees = $stmt->fetchAll();
} catch (PDOException $e) {
    $employees = [];
    error_log("Database error: " . $e->getMessage());
}
?>

<div class="bg-gray-800 rounded-xl border border-gray-700 overflow-hidden shadow">
    <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-700 w-full" style="zoom:85%">
            <thead class="bg-gray-700">
                <tr>
                    <th scope="col" class="px-4 py-3 text-left text-xs font-medium text-gray-300 uppercase tracking-wider">
                        <input type="checkbox" id="selectAll" onchange="selectAllEmployees(this)" class="rounded border-gray-600 bg-gray-700 text-primary-600 focus:ring-primary-500">
                    </th>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-300 uppercase tracking-wider">CXI Number</th>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-300 uppercase tracking-wider">Full Name</th>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-300 uppercase tracking-wider">Department</th>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-300 uppercase tracking-wider">Supervisor</th>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-300 uppercase tracking-wider">Operations Manager</th>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-300 uppercase tracking-wider">Email</th>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-300 uppercase tracking-wider">Created At</th>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-300 uppercase tracking-wider">Status</th>
                    <th scope="col" class="px-6 py-3 text-right text-xs font-medium text-gray-300 uppercase tracking-wider">Actions</th>
                </tr>
            </thead>
            <tbody class="bg-gray-800 divide-y divide-gray-700">
                <?php if (empty($employees)): ?>
                    <tr>
                        <td colspan="10" class="px-6 py-4 text-center text-gray-400">
                            No agents found
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($employees as $employee): ?>
                    <tr class="hover:bg-gray-700/50">
                        <td class="px-4 py-4 whitespace-nowrap">
                            <input type="checkbox" name="selected_employees[]" value="<?= $employee['id'] ?>" 
                                   class="employee-checkbox rounded border-gray-600 bg-gray-700 text-primary-600 focus:ring-primary-500"
                                   onchange="toggleEditTeamButton()"
                                   <?= in_array($employee['id'], $selectedEmployees) ? 'checked' : '' ?>>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="text-sm font-medium text-gray-100" style="text-transform: uppercase;"><?= htmlspecialchars($employee['employee_id']) ?></div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="text-sm text-gray-300"><?= htmlspecialchars($employee['full_name']) ?></div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="text-sm text-gray-300" style="text-transform: uppercase;"><?= htmlspecialchars($employee['department']) ?></div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="text-sm text-gray-300" style="text-transform: uppercase;"><?= htmlspecialchars($employee['supervisor']) ?></div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="text-sm text-gray-300" style="text-transform: uppercase;"><?= htmlspecialchars($employee['operation_manager']) ?></div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="text-sm text-gray-300" style="text-transform: uppercase;"><?= htmlspecialchars($employee['email'] ?? '') ?></div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                           <div class="text-sm text-gray-300" style="text-transform: uppercase;"><?= date('M j, Y g:i A', strtotime($employee['created_at'])) ?></div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full <?= $employee['is_active'] ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' ?>">
                                <?= $employee['is_active'] ? 'Active' : 'Inactive' ?>
                            </span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                            <a href="employee.php?id=<?= $employee['id'] ?>" title="Edit record" class="text-primary-500 hover:text-primary-400 mr-3">
                                <i class="fas fa-edit"></i>
                            </a>
                            <a href="employees.php?toggle_status=<?= $employee['id'] ?>" class="text-yellow-500 hover:text-yellow-400 mr-3" title="Status update" onclick="return confirm('Are you sure you want to <?= $employee['is_active'] ? 'deactivate' : 'activate' ?> this agent?')">
                                <i class="fas fa-<?= $employee['is_active'] ? 'times' : 'check' ?>"></i>
                            </a>
                            <a href="#" onclick="event.preventDefault(); showDeleteModal(<?= $employee['id'] ?>)" class="text-red-500 hover:text-red-400" title="Delete record">
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
        Showing <?= ($offset + 1) ?> to <?= min($offset + $perPage, $totalEmployees) ?> of <?= $totalEmployees ?> agents
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