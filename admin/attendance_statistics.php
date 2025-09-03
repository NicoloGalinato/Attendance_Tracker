<?php
require_once '../includes/config.php';
require_once '../includes/functions.php';

if (!isLoggedIn() || !isAdmin()) {
    redirect(BASE_URL);
}

updateLastActivity();

// Check if it's an AJAX request
$isAjax = isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
$isEmployeeStatsRequest = isset($_GET['employee_id']);

if ($isAjax && $isEmployeeStatsRequest) {
    // Handling employee statistics AJAX request
    $employeeId = $_GET['employee_id'];
    $startDate = isset($_GET['start_date']) ? $_GET['start_date'] : '2000-01-01';
    $endDate = isset($_GET['end_date']) ? $_GET['end_date'] : date('Y-m-d');
    
    $response = [
        'employee' => [],
        'absenteeism' => [],
        'tardiness' => [],
        'stats' => [
            'total_absences' => 0,
            'total_tardiness' => 0,
        ]
    ];
    
    try {
        // Get employee details
        $stmt = $pdo->prepare("SELECT DISTINCT full_name, employee_id, department, operation_manager FROM absenteeism WHERE employee_id = ? UNION SELECT DISTINCT full_name, employee_id, department, operation_manager FROM tardiness WHERE employee_id = ? LIMIT 1");
        $stmt->execute([$employeeId, $employeeId]);
        $employee = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($employee) {
            $response['employee'] = $employee;
        }

        // Get absenteeism history
        $stmt = $pdo->prepare("SELECT date_of_absent, reason FROM absenteeism WHERE employee_id = ? AND date_of_absent BETWEEN ? AND ? ORDER BY date_of_absent DESC");
        $stmt->execute([$employeeId, $startDate, $endDate]);
        $response['absenteeism'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Get tardiness history
        $stmt = $pdo->prepare("SELECT date_of_incident, minutes_late FROM tardiness WHERE employee_id = ? AND date_of_incident BETWEEN ? AND ? ORDER BY date_of_incident DESC");
        $stmt->execute([$employeeId, $startDate, $endDate]);
        $response['tardiness'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Calculate totals
        $response['stats']['total_absences'] = count($response['absenteeism']);
        $response['stats']['total_tardiness'] = count($response['tardiness']);
        
        echo json_encode($response);
        exit;
        
    } catch (PDOException $e) {
        error_log("Database error fetching employee stats: " . $e->getMessage());
        echo json_encode(['error' => 'Database error']);
        exit;
    }
}

// Get filter parameters
$period = isset($_GET['period']) ? $_GET['period'] : 'monthly';
$department = isset($_GET['department']) ? $_GET['department'] : '';
$operationManagerTab = isset($_GET['om_tab']) ? $_GET['om_tab'] : 'overall';
$limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 5;

// Calculate date ranges based on period
$dateRange = [];
$currentDate = new DateTime();

switch ($period) {
    case 'weekly':
        $startDate = clone $currentDate;
        $startDate->modify('this week');
        $endDate = clone $startDate;
        $endDate->modify('+6 days');
        $dateRange['start'] = $startDate->format('Y-m-d');
        $dateRange['end'] = $endDate->format('Y-m-d');
        break;
    case 'monthly':
        $startDate = clone $currentDate;
        $startDate->modify('first day of this month');
        $endDate = clone $currentDate;
        $endDate->modify('last day of this month');
        $dateRange['start'] = $startDate->format('Y-m-d');
        $dateRange['end'] = $endDate->format('Y-m-d');
        break;
    default: // overall
        $dateRange['start'] = '2000-01-01'; // A very early date
        $dateRange['end'] = $currentDate->format('Y-m-d');
        break;
}

// Get statistics data
$stats = [
    'absenteeism' => [],
    'tardiness' => [],
    'departments' => [],
    'operation_managers' => [],
    'om_stats' => []
];

try {
    // Get departments for filter
    $stmt = $pdo->query("SELECT DISTINCT department FROM absenteeism UNION SELECT DISTINCT department FROM tardiness ORDER BY department");
    $stats['departments'] = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    // Get operation managers for tabs
    $stmt = $pdo->query("SELECT DISTINCT operation_manager FROM absenteeism WHERE operation_manager != '' ORDER BY operation_manager");
    $stats['operation_managers'] = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    // Get operation manager statistics for charts
    $omQuery = "SELECT 
        operation_manager,
        COUNT(*) as total_absences,
        (SELECT COUNT(*) FROM tardiness t 
         WHERE t.operation_manager = a.operation_manager 
         AND t.date_of_incident BETWEEN :start_date AND :end_date) as total_tardiness
    FROM absenteeism a
    WHERE a.operation_manager != '' AND a.date_of_absent BETWEEN :start_date AND :end_date";
    
    if (!empty($department)) {
        $omQuery .= " AND a.department = :department";
    }
    
    $omQuery .= " GROUP BY operation_manager
                 ORDER BY total_absences DESC, total_tardiness DESC";
    
    $stmt = $pdo->prepare($omQuery);
    $stmt->bindValue(':start_date', $dateRange['start']);
    $stmt->bindValue(':end_date', $dateRange['end']);
    
    if (!empty($department)) {
        $stmt->bindValue(':department', $department);
    }
    
    $stmt->execute();
    $stats['om_stats'] = $stmt->fetchAll();
    
    // Get top absenteeism
    $absentQuery = "SELECT 
        a.employee_id, 
        a.full_name, 
        a.department, 
        a.operation_manager,
        COUNT(*) as absence_count
    FROM absenteeism a
    WHERE a.date_of_absent BETWEEN :start_date AND :end_date";
    
    $params = [
        ':start_date' => $dateRange['start'],
        ':end_date' => $dateRange['end']
    ];
    
    if (!empty($department)) {
        $absentQuery .= " AND a.department = :department";
        $params[':department'] = $department;
    }
    
    if ($operationManagerTab !== 'overall') {
        $absentQuery .= " AND a.operation_manager = :operation_manager";
        $params[':operation_manager'] = $operationManagerTab;
    }
    
    $absentQuery .= " GROUP BY a.employee_id, a.full_name, a.department, a.operation_manager
                     ORDER BY absence_count DESC
                     LIMIT :limit";
    
    $stmt = $pdo->prepare($absentQuery);
    foreach ($params as $key => $value) {
        $stmt->bindValue($key, $value);
    }
    $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
    $stmt->execute();
    $stats['absenteeism'] = $stmt->fetchAll();
    
    // Get top tardiness
    $tardyQuery = "SELECT 
        t.employee_id, 
        t.full_name, 
        t.department, 
        t.operation_manager,
        COUNT(*) as tardiness_count,
        SUM(t.minutes_late) as total_minutes_late
    FROM tardiness t
    WHERE t.date_of_incident BETWEEN :start_date AND :end_date";
    
    $params = [
        ':start_date' => $dateRange['start'],
        ':end_date' => $dateRange['end']
    ];
    
    if (!empty($department)) {
        $tardyQuery .= " AND t.department = :department";
        $params[':department'] = $department;
    }
    
    if ($operationManagerTab !== 'overall') {
        $tardyQuery .= " AND t.operation_manager = :operation_manager";
        $params[':operation_manager'] = $operationManagerTab;
    }
    
    $tardyQuery .= " GROUP BY t.employee_id, t.full_name, t.department, t.operation_manager
                    ORDER BY tardiness_count DESC, total_minutes_late DESC
                    LIMIT :limit";
    
    $stmt = $pdo->prepare($tardyQuery);
    foreach ($params as $key => $value) {
        $stmt->bindValue($key, $value);
    }
    $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
    $stmt->execute();
    $stats['tardiness'] = $stmt->fetchAll();
    
    // Get department statistics
    $deptQuery = "SELECT 
        department,
        COUNT(*) as total_absences,
        (SELECT COUNT(*) FROM tardiness t 
         WHERE t.department = a.department 
         AND t.date_of_incident BETWEEN :start_date AND :end_date) as total_tardiness
    FROM absenteeism a
    WHERE a.date_of_absent BETWEEN :start_date AND :end_date";
    
    if (!empty($department)) {
        $deptQuery .= " AND a.department = :department";
    }
    
    if ($operationManagerTab !== 'overall') {
        $deptQuery .= " AND a.operation_manager = :operation_manager";
    }
    
    $deptQuery .= " GROUP BY department
    ORDER BY total_absences DESC, total_tardiness DESC";
    
    $stmt = $pdo->prepare($deptQuery);
    $stmt->bindValue(':start_date', $dateRange['start']);
    $stmt->bindValue(':end_date', $dateRange['end']);
    
    if (!empty($department)) {
        $stmt->bindValue(':department', $department);
    }
    
    if ($operationManagerTab !== 'overall') {
        $stmt->bindValue(':operation_manager', $operationManagerTab);
    }
    
    $stmt->execute();
    $deptStats = $stmt->fetchAll();
    
} catch (PDOException $e) {
    error_log("Database error in statistics: " . $e->getMessage());
    if ($isAjax) {
        echo json_encode(['error' => 'Database error']);
        exit;
    } else {
        $_SESSION['error'] = "Error loading statistics data";
    }
}

// If it's an AJAX request, return JSON data
if ($isAjax) {
    $response = [
        'absenteeism' => $stats['absenteeism'],
        'tardiness' => $stats['tardiness'],
        'om_stats' => $stats['om_stats'],
        'deptStats' => $deptStats,
        'period' => $period,
        'department' => $department,
        'limit' => $limit,
        'om_tab' => $operationManagerTab
    ];
    
    header('Content-Type: application/json');
    echo json_encode($response);
    exit;
}

// Regular page rendering for non-AJAX requests
require_once '../components/layout.php';
renderHead('Attendance Statistics');
renderNavbar();
renderSidebar('attendance_statistics');
?>

<div class="pt-2 min-h-screen">
    <main class="p-6">
        <div class="flex justify-between items-center mb-6">
            <h1 class="text-2xl font-bold">Attendance Statistics</h1>
            <div class="flex items-center gap-2">
                <a href="attendance.php" class="bg-gray-700 hover:bg-gray-600 text-white px-4 py-2 rounded-lg flex items-center">
                    <i class="fas fa-arrow-left mr-2"></i> Back to Tracker
                </a>
            </div>
        </div>
        
        <?php renderAlert(); ?>
        
        <div class="border-b border-gray-700 mb-6">
            <nav class="-mb-px flex space-x-8 overflow-x-auto" id="om-tabs">
                <button type="button" data-tab="overall" 
                   class="<?= $operationManagerTab === 'overall' ? 'border-primary-500 text-primary-400' : 'border-transparent text-gray-400 hover:text-gray-300' ?> whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm om-tab">
                    Overall
                </button>
                <?php foreach ($stats['operation_managers'] as $manager): ?>
                    <button type="button" data-tab="<?= htmlspecialchars($manager) ?>" 
                       class="<?= $operationManagerTab === $manager ? 'border-primary-500 text-primary-400' : 'border-transparent text-gray-400 hover:text-gray-300' ?> whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm om-tab">
                        <?= htmlspecialchars($manager) ?>
                    </button>
                <?php endforeach; ?>
            </nav>
        </div>
        
        <div class="bg-gray-800 rounded-xl border border-gray-700 p-6 mb-6">
            <h2 class="text-lg font-semibold mb-4">Filter Statistics</h2>
            <form id="filter-form" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                <input type="hidden" name="om_tab" id="om-tab-input" value="<?= $operationManagerTab ?>">
                
                <div>
                    <label for="period" class="block text-sm font-medium text-gray-300 mb-1">Time Period</label>
                    <select id="period" name="period" class="w-full px-4 py-2 bg-gray-700 border border-gray-600 rounded-lg text-gray-200">
                        <option value="weekly" <?= $period === 'weekly' ? 'selected' : '' ?>>Weekly</option>
                        <option value="monthly" <?= $period === 'monthly' ? 'selected' : '' ?>>Monthly</option>
                        <option value="overall" <?= $period === 'overall' ? 'selected' : '' ?>>Overall</option>
                    </select>
                </div>
                
                <div>
                    <label for="department" class="block text-sm font-medium text-gray-300 mb-1">Department</label>
                    <select id="department" name="department" class="w-full px-4 py-2 bg-gray-700 border border-gray-600 rounded-lg text-gray-200">
                        <option value="">All Departments</option>
                        <?php foreach ($stats['departments'] as $dept): ?>
                            <option value="<?= htmlspecialchars($dept) ?>" <?= $department === $dept ? 'selected' : '' ?>>
                                <?= htmlspecialchars($dept) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div>
                    <label for="limit" class="block text-sm font-medium text-gray-300 mb-1">Top Results</label>
                    <select id="limit" name="limit" class="w-full px-4 py-2 bg-gray-700 border border-gray-600 rounded-lg text-gray-200">
                        <option value="5" <?= $limit === 5 ? 'selected' : '' ?>>Top 5</option>
                        <option value="10" <?= $limit === 10 ? 'selected' : '' ?>>Top 10</option>
                        <option value="20" <?= $limit === 20 ? 'selected' : '' ?>>Top 20</option>
                        <option value="50" <?= $limit === 50 ? 'selected' : '' ?>>Top 50</option>
                    </select>
                </div>
            </form>
        </div>
        
        <div id="om-charts-container">
            <?php if ($operationManagerTab === 'overall' && !empty($stats['om_stats'])): ?>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-8">
                <div class="bg-gray-800 rounded-xl border border-gray-700 overflow-hidden shadow">
                    <div class="bg-gray-700 px-6 py-4 border-b border-gray-600">
                        <h2 class="text-lg font-semibold text-gray-100 flex items-center">
                            <i class="fas fa-chart-bar mr-2 text-red-400"></i>
                            Absences by Operation Manager
                        </h2>
                    </div>
                    <div class="p-6">
                        <canvas id="absencesByOmChart" width="400" height="250"></canvas>
                    </div>
                </div>
                
                <div class="bg-gray-800 rounded-xl border border-gray-700 overflow-hidden shadow">
                    <div class="bg-gray-700 px-6 py-4 border-b border-gray-600">
                        <h2 class="text-lg font-semibold text-gray-100 flex items-center">
                            <i class="fas fa-chart-bar mr-2 text-yellow-400"></i>
                            Tardiness by Operation Manager
                        </h2>
                    </div>
                    <div class="p-6">
                        <canvas id="tardinessByOmChart" width="400" height="250"></canvas>
                    </div>
                </div>
            </div>
            <?php endif; ?>
        </div>
        
        <div id="stats-cards-container" class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-8">
            <div class="bg-gray-800 rounded-xl border border-gray-700 overflow-hidden shadow">
                <div class="bg-gray-700 px-6 py-4 border-b border-gray-600">
                    <h2 class="text-lg font-semibold text-gray-100 flex items-center">
                        <i class="fas fa-user-times mr-2 text-red-400"></i>
                        Top <?= $limit ?> Absences
                        <span class="ml-auto text-sm text-gray-400 font-normal">
                            <?= 
                                $period === 'weekly' ? date('M j', strtotime($dateRange['start'])) . ' - ' . date('M j, Y', strtotime($dateRange['end'])) : 
                                ($period === 'monthly' ? date('F Y', strtotime($dateRange['start'])) : 'Overall') 
                            ?> 
                            <?= !empty($department) ? '• ' . htmlspecialchars($department) : '' ?>
                            <?= $operationManagerTab !== 'overall' ? '• ' . htmlspecialchars($operationManagerTab) : '' ?>
                        </span>
                    </h2>
                </div>
                <div class="p-6">
                    <?php if (!empty($stats['absenteeism'])): ?>
                        <div class="space-y-4">
                            <?php foreach ($stats['absenteeism'] as $index => $record): ?>
                                <div class="flex items-center justify-between p-3 bg-gray-700/50 rounded-lg">
                                    <div class="flex items-center">
                                        <div class="w-8 h-8 rounded-full bg-primary-600 flex items-center justify-center text-white font-bold text-sm mr-3">
                                            <?= $index + 1 ?>
                                        </div>
                                        <div>
                                            <button onclick="showEmployeeDetails('<?= htmlspecialchars($record['employee_id']) ?>')" class="font-medium text-gray-100 hover:text-primary-400 cursor-pointer text-left">
                                                <?= htmlspecialchars($record['full_name']) ?>
                                            </button>
                                            <div class="text-sm text-gray-400"><?= htmlspecialchars($record['employee_id']) ?></div>
                                            <div class="text-xs text-gray-500"><?= htmlspecialchars($record['department']) ?></div>
                                        </div>
                                    </div>
                                    <div class="text-right">
                                        <div class="text-xl font-bold text-red-400"><?= $record['absence_count'] ?></div>
                                        <div class="text-xs text-gray-400">absences</div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <div class="text-center py-8 text-gray-400">
                            <i class="fas fa-inbox text-4xl mb-3"></i>
                            <p>No absenteeism records found</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
            
            <div class="bg-gray-800 rounded-xl border border-gray-700 overflow-hidden shadow">
                <div class="bg-gray-700 px-6 py-4 border-b border-gray-600">
                    <h2 class="text-lg font-semibold text-gray-100 flex items-center">
                        <i class="fas fa-clock mr-2 text-yellow-400"></i>
                        Top <?= $limit ?> Tardiness
                        <span class="ml-auto text-sm text-gray-400 font-normal">
                            <?= 
                                $period === 'weekly' ? date('M j', strtotime($dateRange['start'])) . ' - ' . date('M j, Y', strtotime($dateRange['end'])) : 
                                ($period === 'monthly' ? date('F Y', strtotime($dateRange['start'])) : 'Overall') 
                            ?> 
                            <?= !empty($department) ? '• ' . htmlspecialchars($department) : '' ?>
                            <?= $operationManagerTab !== 'overall' ? '• ' . htmlspecialchars($operationManagerTab) : '' ?>
                        </span>
                    </h2>
                </div>
                <div class="p-6">
                    <?php if (!empty($stats['tardiness'])): ?>
                        <div class="space-y-4">
                            <?php foreach ($stats['tardiness'] as $index => $record): ?>
                                <div class="flex items-center justify-between p-3 bg-gray-700/50 rounded-lg">
                                    <div class="flex items-center">
                                        <div class="w-8 h-8 rounded-full bg-primary-600 flex items-center justify-center text-white font-bold text-sm mr-3">
                                            <?= $index + 1 ?>
                                        </div>
                                        <div>
                                            <button onclick="showEmployeeDetails('<?= htmlspecialchars($record['employee_id']) ?>')" class="font-medium text-gray-100 hover:text-primary-400 cursor-pointer text-left">
                                                <?= htmlspecialchars($record['full_name']) ?>
                                            </button>
                                            <div class="text-sm text-gray-400"><?= htmlspecialchars($record['employee_id']) ?></div>
                                            <div class="text-xs text-gray-500"><?= htmlspecialchars($record['department']) ?></div>
                                        </div>
                                    </div>
                                    <div class="text-right">
                                        <div class="text-xl font-bold text-yellow-400"><?= $record['tardiness_count'] ?></div>
                                        <div class="text-xs text-gray-400">
                                            <?= $record['total_minutes_late'] ?> total minutes
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <div class="text-center py-8 text-gray-400">
                            <i class="fas fa-inbox text-4xl mb-3"></i>
                            <p>No tardiness records found</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        
        <div id="dept-stats-container">
            <div class="bg-gray-800 rounded-xl border border-gray-700 overflow-hidden shadow mb-8">
                <div class="bg-gray-700 px-6 py-4 border-b border-gray-600">
                    <h2 class="text-lg font-semibold text-gray-100 flex items-center">
                        <i class="fas fa-building mr-2 text-blue-400"></i>
                        Department Statistics
                    </h2>
                </div>
                <div class="p-6">
                    <?php if (!empty($deptStats)): ?>
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-700">
                                <thead class="bg-gray-700">
                                    <tr>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-300 uppercase tracking-wider">Department</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-300 uppercase tracking-wider">Absences</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-300 uppercase tracking-wider">Tardiness</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-300 uppercase tracking-wider">Total Incidents</th>
                                    </tr>
                                </thead>
                                <tbody class="bg-gray-800 divide-y divide-gray-700">
                                    <?php foreach ($deptStats as $dept): ?>
                                        <tr>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-100">
                                                <?= htmlspecialchars($dept['department']) ?>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-red-400">
                                                <?= $dept['total_absences'] ?>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-yellow-400">
                                                <?= $dept['total_tardiness'] ?>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-300">
                                                <?= $dept['total_absences'] + $dept['total_tardiness'] ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <div class="text-center py-8 text-gray-400">
                            <i class="fas fa-inbox text-4xl mb-3"></i>
                            <p>No department statistics available</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </main>
</div>

<div id="employee-modal" class="fixed inset-0 bg-gray-900 bg-opacity-75 z-50 hidden flex items-center justify-center p-4">
    <div class="bg-gray-800 rounded-xl border border-gray-700 w-full max-w-4xl max-h-[90vh] overflow-hidden flex flex-col shadow-lg">
        <div class="flex justify-between items-center p-6 border-b border-gray-700">
            <h2 class="text-xl font-bold text-gray-100" id="modal-title">Employee Details</h2>
            <button id="close-modal-btn" class="text-gray-400 hover:text-gray-200">
                <i class="fas fa-times text-2xl"></i>
            </button>
        </div>
        <div class="p-6 overflow-y-auto hide-scrollbar">
            <div id="modal-loader" class="text-center py-12 text-gray-400 hidden">
                <i class="fas fa-spinner fa-spin text-4xl mb-3"></i>
                <p>Loading employee data...</p>
            </div>
            <div id="modal-content" class="hidden">
                <div class="mb-6 pb-4 border-b border-gray-700">
                    <h3 class="text-lg font-semibold text-gray-200" id="employee-name"></h3>
                    <p class="text-sm text-gray-400" id="employee-id-dept"></p>
                    <p class="text-sm text-gray-400 mt-1" id="employee-om"></p>
                </div>
                
                <div class="bg-gray-700/50 p-4 rounded-lg mb-6 flex flex-col md:flex-row items-center gap-4">
                    <div class="flex-1 w-full">
                        <label for="modal-start-date" class="block text-sm font-medium text-gray-300 mb-1">Start Date</label>
                        <input type="date" id="modal-start-date" class="w-full px-4 py-2 bg-gray-700 border border-gray-600 rounded-lg text-gray-200">
                    </div>
                    <div class="flex-1 w-full">
                        <label for="modal-end-date" class="block text-sm font-medium text-gray-300 mb-1">End Date</label>
                        <input type="date" id="modal-end-date" class="w-full px-4 py-2 bg-gray-700 border border-gray-600 rounded-lg text-gray-200">
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                    <div>
                        <h4 class="text-md font-semibold text-gray-200 mb-2">Absence & Tardiness Breakdown</h4>
                        <div class="bg-gray-700/50 p-4 rounded-lg flex items-center justify-center">
                            <canvas id="employee-stats-chart" class="w-full max-w-xs"></canvas>
                        </div>
                    </div>
                    <div class="flex flex-col justify-center">
                        <div class="bg-gray-700/50 p-4 rounded-lg mb-4">
                            <h5 class="text-sm font-medium text-gray-300">Total Absences</h5>
                            <p class="text-3xl font-bold text-red-400" id="total-absences-count">0</p>
                        </div>
                        <div class="bg-gray-700/50 p-4 rounded-lg">
                            <h5 class="text-sm font-medium text-gray-300">Total Tardiness</h5>
                            <p class="text-3xl font-bold text-yellow-400" id="total-tardiness-count">0</p>
                        </div>
                    </div>
                </div>
                
                <div>
                    <h4 class="text-md font-semibold text-gray-200 mb-2">Incident History</h4>
                    <div class="bg-gray-700/50 rounded-lg overflow-hidden">
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-700">
                                <thead class="bg-gray-700">
                                    <tr>
                                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-300 uppercase tracking-wider">Date</th>
                                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-300 uppercase tracking-wider">Type</th>
                                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-300 uppercase tracking-wider">Details</th>
                                    </tr>
                                </thead>
                                <tbody id="incident-history-table" class="bg-gray-800 divide-y divide-gray-700">
                                    </tbody>
                            </table>
                        </div>
                        <div id="no-history-message" class="p-6 text-center text-gray-400 hidden">
                            <i class="fas fa-inbox text-4xl mb-3"></i>
                            <p>No records found for this period.</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<script>
// Global variables for charts
let absencesChart = null;
let tardinessChart = null;
let employeeChart = null;
let currentEmployeeId = null;

// Function to update UI with new data
function updateUI(data) {
    // Update absenteeism list
    const absenteeismContainer = document.querySelector('#stats-cards-container > div:first-child .p-6');
    if (data.absenteeism.length > 0) {
        let html = '<div class="space-y-4">';
        data.absenteeism.forEach((record, index) => {
            html += `
                <div class="flex items-center justify-between p-3 bg-gray-700/50 rounded-lg">
                    <div class="flex items-center">
                        <div class="w-8 h-8 rounded-full bg-primary-600 flex items-center justify-center text-white font-bold text-sm mr-3">
                            ${index + 1}
                        </div>
                        <div>
                            <button onclick="showEmployeeDetails('${escapeHtml(record.employee_id)}')" class="font-medium text-gray-100 hover:text-primary-400 cursor-pointer text-left">
                                ${escapeHtml(record.full_name)}
                            </button>
                            <div class="text-sm text-gray-400">${escapeHtml(record.employee_id)}</div>
                            <div class="text-xs text-gray-500">${escapeHtml(record.department)}</div>
                        </div>
                    </div>
                    <div class="text-right">
                        <div class="text-xl font-bold text-red-400">${record.absence_count}</div>
                        <div class="text-xs text-gray-400">absences</div>
                    </div>
                </div>
            `;
        });
        html += '</div>';
        absenteeismContainer.innerHTML = html;
    } else {
        absenteeismContainer.innerHTML = `
            <div class="text-center py-8 text-gray-400">
                <i class="fas fa-inbox text-4xl mb-3"></i>
                <p>No absenteeism records found</p>
            </div>
        `;
    }
    
    // Update tardiness list
    const tardinessContainer = document.querySelector('#stats-cards-container > div:last-child .p-6');
    if (data.tardiness.length > 0) {
        let html = '<div class="space-y-4">';
        data.tardiness.forEach((record, index) => {
            html += `
                <div class="flex items-center justify-between p-3 bg-gray-700/50 rounded-lg">
                    <div class="flex items-center">
                        <div class="w-8 h-8 rounded-full bg-primary-600 flex items-center justify-center text-white font-bold text-sm mr-3">
                            ${index + 1}
                        </div>
                        <div>
                            <button onclick="showEmployeeDetails('${escapeHtml(record.employee_id)}')" class="font-medium text-gray-100 hover:text-primary-400 cursor-pointer text-left">
                                ${escapeHtml(record.full_name)}
                            </button>
                            <div class="text-sm text-gray-400">${escapeHtml(record.employee_id)}</div>
                            <div class="text-xs text-gray-500">${escapeHtml(record.department)}</div>
                        </div>
                    </div>
                    <div class="text-right">
                        <div class="text-xl font-bold text-yellow-400">${record.tardiness_count}</div>
                        <div class="text-xs text-gray-400">
                            ${record.total_minutes_late} total minutes
                        </div>
                    </div>
                </div>
            `;
        });
        html += '</div>';
        tardinessContainer.innerHTML = html;
    } else {
        tardinessContainer.innerHTML = `
            <div class="text-center py-8 text-gray-400">
                <i class="fas fa-inbox text-4xl mb-3"></i>
                <p>No tardiness records found</p>
            </div>
        `;
    }
    
    // Update department statistics
    const deptStatsContainer = document.querySelector('#dept-stats-container > div .p-6');
    if (data.deptStats.length > 0) {
        let html = `
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-700">
                    <thead class="bg-gray-700">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-300 uppercase tracking-wider">Department</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-300 uppercase tracking-wider">Absences</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-300 uppercase tracking-wider">Tardiness</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-300 uppercase tracking-wider">Total Incidents</th>
                        </tr>
                    </thead>
                    <tbody class="bg-gray-800 divide-y divide-gray-700">
        `;
        
        data.deptStats.forEach(dept => {
            html += `
                <tr>
                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-100">
                        ${escapeHtml(dept.department)}
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-red-400">
                        ${dept.total_absences}
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-yellow-400">
                        ${dept.total_tardiness}
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-300">
                        ${dept.total_absences + dept.total_tardiness}
                    </td>
                </tr>
            `;
        });
        
        html += `
                    </tbody>
                </table>
            </div>
        `;
        
        deptStatsContainer.innerHTML = html;
    } else {
        deptStatsContainer.innerHTML = `
            <div class="text-center py-8 text-gray-400">
                <i class="fas fa-inbox text-4xl mb-3"></i>
                <p>No department statistics available</p>
            </div>
        `;
    }
    
    // Update the card titles with current filters
    // This part of the code is not changing and works as intended
    
    // Update operation manager charts if we're in overall view
    const omChartsContainer = document.getElementById('om-charts-container');
    if (data.om_tab === 'overall' && data.om_stats && data.om_stats.length > 0) {
        // Prepare data for charts
        const omLabels = data.om_stats.map(stat => stat.operation_manager);
        const absencesData = data.om_stats.map(stat => stat.total_absences);
        const tardinessData = data.om_stats.map(stat => stat.total_tardiness);
        
        let chartsHTML = `
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-8">
                <div class="bg-gray-800 rounded-xl border border-gray-700 overflow-hidden shadow">
                    <div class="bg-gray-700 px-6 py-4 border-b border-gray-600">
                        <h2 class="text-lg font-semibold text-gray-100 flex items-center">
                            <i class="fas fa-chart-bar mr-2 text-red-400"></i>
                            Absences by Operation Manager
                        </h2>
                    </div>
                    <div class="p-6">
                        <canvas id="absencesByOmChart" width="400" height="250"></canvas>
                    </div>
                </div>
                
                <div class="bg-gray-800 rounded-xl border border-gray-700 overflow-hidden shadow">
                    <div class="bg-gray-700 px-6 py-4 border-b border-gray-600">
                        <h2 class="text-lg font-semibold text-gray-100 flex items-center">
                            <i class="fas fa-chart-bar mr-2 text-yellow-400"></i>
                            Tardiness by Operation Manager
                        </h2>
                    </div>
                    <div class="p-6">
                        <canvas id="tardinessByOmChart" width="400" height="250"></canvas>
                    </div>
                </div>
            </div>
        `;
        
        omChartsContainer.innerHTML = chartsHTML;
        
        // Destroy existing charts if they exist
        if (absencesChart) {
            absencesChart.destroy();
        }
        if (tardinessChart) {
            tardinessChart.destroy();
        }
        
        // Create new charts after a small delay to allow DOM to update
        setTimeout(() => {
            // Absences by Operation Manager Chart
            const absencesCtx = document.getElementById('absencesByOmChart').getContext('2d');
            absencesChart = new Chart(absencesCtx, {
                type: 'bar',
                data: {
                    labels: omLabels,
                    datasets: [{
                        label: 'Absences',
                        data: absencesData,
                        backgroundColor: 'rgba(239, 68, 68, 0.7)',
                        borderColor: 'rgba(239, 68, 68, 1)',
                        borderWidth: 1
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    scales: {
                        y: {
                            beginAtZero: true,
                            ticks: {
                                color: '#9ca3af'
                            },
                            grid: {
                                color: 'rgba(156, 163, 175, 0.2)'
                            }
                        },
                        x: {
                            ticks: {
                                color: '#9ca3af',
                                maxRotation: 45,
                                minRotation: 45
                            },
                            grid: {
                                display: false
                            }
                        }
                    },
                    plugins: {
                        legend: {
                            labels: {
                                color: '#9ca3af'
                            }
                        }
                    }
                }
            });
            
            // Tardiness by Operation Manager Chart
            const tardinessCtx = document.getElementById('tardinessByOmChart').getContext('2d');
            tardinessChart = new Chart(tardinessCtx, {
                type: 'bar',
                data: {
                    labels: omLabels,
                    datasets: [{
                        label: 'Tardiness',
                        data: tardinessData,
                        backgroundColor: 'rgba(234, 179, 8, 0.7)',
                        borderColor: 'rgba(234, 179, 8, 1)',
                        borderWidth: 1
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    scales: {
                        y: {
                            beginAtZero: true,
                            ticks: {
                                color: '#9ca3af'
                            },
                            grid: {
                                color: 'rgba(156, 163, 175, 0.2)'
                            }
                        },
                        x: {
                            ticks: {
                                color: '#9ca3af',
                                maxRotation: 45,
                                minRotation: 45
                            },
                            grid: {
                                display: false
                            }
                        }
                    },
                    plugins: {
                        legend: {
                            labels: {
                                color: '#9ca3af'
                            }
                        }
                    }
                }
            });
        }, 100);
    } else {
        omChartsContainer.innerHTML = '';
        // Destroy charts if they exist
        if (absencesChart) {
            absencesChart.destroy();
            absencesChart = null;
        }
        if (tardinessChart) {
            tardinessChart.destroy();
            tardinessChart = null;
        }
    }
}

// Function to escape HTML to prevent XSS
function escapeHtml(unsafe) {
    if (unsafe === null || unsafe === undefined) return '';
    return String(unsafe)
        .replace(/&/g, "&amp;")
        .replace(/</g, "&lt;")
        .replace(/>/g, "&gt;")
        .replace(/"/g, "&quot;")
        .replace(/'/g, "&#039;");
}

// Function to fetch data via AJAX
function fetchData() {
    const formData = new FormData(document.getElementById('filter-form'));
    const params = new URLSearchParams(formData);
    
    // Show loading indicator
    document.getElementById('stats-cards-container').style.opacity = '0.7';
    document.getElementById('dept-stats-container').style.opacity = '0.7';
    
    fetch('attendance_statistics.php?' + params.toString(), {
        headers: {
            'X-Requested-With': 'XMLHttpRequest'
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.error) {
            alert('Error: ' + data.error);
            return;
        }
        
        updateUI(data);
    })
    .catch(error => {
        console.error('Error:', error);
        alert('An error occurred while fetching data.');
    })
    .finally(() => {
        // Hide loading indicator
        document.getElementById('stats-cards-container').style.opacity = '1';
        document.getElementById('dept-stats-container').style.opacity = '1';
    });
}

// --- Employee Modal Functions ---

function showEmployeeDetails(employeeId) {
    currentEmployeeId = employeeId;
    
    const modal = document.getElementById('employee-modal');
    const loader = document.getElementById('modal-loader');
    const content = document.getElementById('modal-content');
    
    // Show modal and loader, hide content
    modal.classList.remove('hidden');
    loader.classList.remove('hidden');
    content.classList.add('hidden');
    
    // Set default date range to "Overall" (e.g., last 3 months or overall)
    const today = new Date();
    const threeMonthsAgo = new Date();
    threeMonthsAgo.setMonth(today.getMonth() - 3);
    
    const startDateInput = document.getElementById('modal-start-date');
    const endDateInput = document.getElementById('modal-end-date');
    
    startDateInput.value = threeMonthsAgo.toISOString().split('T')[0];
    endDateInput.value = today.toISOString().split('T')[0];
    
    // Fetch initial data
    fetchEmployeeStats(employeeId, startDateInput.value, endDateInput.value);
}

function fetchEmployeeStats(employeeId, startDate, endDate) {
    const params = new URLSearchParams({
        employee_id: employeeId,
        start_date: startDate,
        end_date: endDate
    });
    
    const loader = document.getElementById('modal-loader');
    const content = document.getElementById('modal-content');

    loader.classList.remove('hidden');
    content.classList.add('hidden');
    
    fetch('attendance_statistics.php?' + params.toString(), {
        headers: {
            'X-Requested-With': 'XMLHttpRequest'
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.error) {
            alert('Error: ' + data.error);
            return;
        }
        
        updateModalContent(data);
    })
    .catch(error => {
        console.error('Error:', error);
        alert('An error occurred while fetching employee data.');
    })
    .finally(() => {
        loader.classList.add('hidden');
        content.classList.remove('hidden');
    });
}

function updateModalContent(data) {
    // Update employee info
    document.getElementById('employee-name').textContent = escapeHtml(data.employee.full_name);
    document.getElementById('employee-id-dept').textContent = `ID: ${escapeHtml(data.employee.employee_id)} • Dept: ${escapeHtml(data.employee.department)}`;
    document.getElementById('employee-om').textContent = `Operation Manager: ${escapeHtml(data.employee.operation_manager) || 'N/A'}`;
    
    // Update total counts
    const totalAbsences = data.stats.total_absences;
    const totalTardiness = data.stats.total_tardiness;
    
    document.getElementById('total-absences-count').textContent = totalAbsences;
    document.getElementById('total-tardiness-count').textContent = totalTardiness;

    // Update charts
    if (employeeChart) {
        employeeChart.destroy();
    }
    
    const totalIncidents = totalAbsences + totalTardiness;
    if (totalIncidents > 0) {
        document.getElementById('employee-stats-chart').style.display = 'block';
        const ctx = document.getElementById('employee-stats-chart').getContext('2d');
        employeeChart = new Chart(ctx, {
            type: 'doughnut',
            data: {
                labels: ['Absences', 'Tardiness'],
                datasets: [{
                    data: [totalAbsences, totalTardiness],
                    backgroundColor: ['rgba(239, 68, 68, 0.7)', 'rgba(234, 179, 8, 0.7)'],
                    hoverBackgroundColor: ['rgba(239, 68, 68, 1)', 'rgba(234, 179, 8, 1)'],
                    borderColor: '#1f2937', // border color to match background
                    borderWidth: 2
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: {
                        position: 'bottom',
                        labels: {
                            color: '#e5e7eb'
                        }
                    },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                let label = context.label || '';
                                if (label) {
                                    label += ': ';
                                }
                                label += context.raw;
                                return label;
                            }
                        }
                    }
                }
            }
        });
    } else {
        document.getElementById('employee-stats-chart').style.display = 'none';
    }
    
    // Update incident history table
    const historyTable = document.getElementById('incident-history-table');
    const noHistoryMessage = document.getElementById('no-history-message');
    historyTable.innerHTML = '';
    
    if (totalIncidents === 0) {
        noHistoryMessage.classList.remove('hidden');
    } else {
        noHistoryMessage.classList.add('hidden');
        
        const allIncidents = [
            ...data.absenteeism.map(record => ({ date: record.date_of_absent, type: 'Absent', details: `Reason: ${escapeHtml(record.reason) || 'N/A'}` })),
            ...data.tardiness.map(record => ({ date: record.date_of_incident, type: 'Tardy', details: `${record.minutes_late} minutes late` }))
        ].sort((a, b) => new Date(b.date) - new Date(a.date));

        allIncidents.forEach(incident => {
            const row = document.createElement('tr');
            row.innerHTML = `
                <td class="px-4 py-2 whitespace-nowrap text-sm text-gray-300">${incident.date}</td>
                <td class="px-4 py-2 whitespace-nowrap text-sm font-medium ${incident.type === 'Absent' ? 'text-red-400' : 'text-yellow-400'}">
                    ${incident.type}
                </td>
                <td class="px-4 py-2 whitespace-nowrap text-sm text-gray-400">${incident.details}</td>
            `;
            historyTable.appendChild(row);
        });
    }
}

// Event listeners
document.addEventListener('DOMContentLoaded', function() {
    // Tab switching
    document.querySelectorAll('.om-tab').forEach(tab => {
        tab.addEventListener('click', function() {
            const tabName = this.getAttribute('data-tab');
            document.getElementById('om-tab-input').value = tabName;
            
            // Update active tab styling
            document.querySelectorAll('.om-tab').forEach(t => {
                t.classList.remove('border-primary-500', 'text-primary-400');
                t.classList.add('border-transparent', 'text-gray-400');
            });
            this.classList.remove('border-transparent', 'text-gray-400');
            this.classList.add('border-primary-500', 'text-primary-400');
            
            // Fetch new data
            fetchData();
        });
    });
    
    // Filter form changes
    document.getElementById('period').addEventListener('change', fetchData);
    document.getElementById('department').addEventListener('change', fetchData);
    document.getElementById('limit').addEventListener('change', fetchData);
    
    // Employee modal date range changes
    document.getElementById('modal-start-date').addEventListener('change', function() {
        if (currentEmployeeId) {
            fetchEmployeeStats(currentEmployeeId, this.value, document.getElementById('modal-end-date').value);
        }
    });
    document.getElementById('modal-end-date').addEventListener('change', function() {
        if (currentEmployeeId) {
            fetchEmployeeStats(currentEmployeeId, document.getElementById('modal-start-date').value, this.value);
        }
    });
    
    // Close modal button
    document.getElementById('close-modal-btn').addEventListener('click', function() {
        document.getElementById('employee-modal').classList.add('hidden');
        if (employeeChart) {
            employeeChart.destroy();
            employeeChart = null;
        }
    });

    // Close modal when clicking outside
    document.getElementById('employee-modal').addEventListener('click', function(e) {
        if (e.target.id === 'employee-modal') {
            this.classList.add('hidden');
            if (employeeChart) {
                employeeChart.destroy();
                employeeChart = null;
            }
        }
    });
    
    // Initialize charts on first load if in overall view
    <?php if ($operationManagerTab === 'overall' && !empty($stats['om_stats'])): ?>
    const omLabels = <?= json_encode(array_map(function($stat) { return $stat['operation_manager']; }, $stats['om_stats'])) ?>;
    const absencesData = <?= json_encode(array_map(function($stat) { return $stat['total_absences']; }, $stats['om_stats'])) ?>;
    const tardinessData = <?= json_encode(array_map(function($stat) { return $stat['total_tardiness']; }, $stats['om_stats'])) ?>;
    
    // Absences by Operation Manager Chart
    const absencesCtx = document.getElementById('absencesByOmChart').getContext('2d');
    absencesChart = new Chart(absencesCtx, {
        type: 'bar',
        data: {
            labels: omLabels,
            datasets: [{
                label: 'Absences',
                data: absencesData,
                backgroundColor: 'rgba(239, 68, 68, 0.7)',
                borderColor: 'rgba(239, 68, 68, 1)',
                borderWidth: 1
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        color: '#9ca3af'
                    },
                    grid: {
                        color: 'rgba(156, 163, 175, 0.2)'
                    }
                },
                x: {
                    ticks: {
                        color: '#9ca3af',
                        maxRotation: 45,
                        minRotation: 45
                    },
                    grid: {
                        display: false
                    }
                }
            },
            plugins: {
                legend: {
                    labels: {
                        color: '#9ca3af'
                    }
                }
            }
        }
    });
    
    // Tardiness by Operation Manager Chart
    const tardinessCtx = document.getElementById('tardinessByOmChart').getContext('2d');
    tardinessChart = new Chart(tardinessCtx, {
        type: 'bar',
        data: {
            labels: omLabels,
            datasets: [{
                label: 'Tardiness',
                data: tardinessData,
                backgroundColor: 'rgba(234, 179, 8, 0.7)',
                borderColor: 'rgba(234, 179, 8, 1)',
                borderWidth: 1
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        color: '#9ca3af'
                    },
                    grid: {
                        color: 'rgba(156, 163, 175, 0.2)'
                    }
                },
                x: {
                    ticks: {
                        color: '#9ca3af',
                        maxRotation: 45,
                        minRotation: 45
                    },
                    grid: {
                        display: false
                    }
                }
            },
            plugins: {
                legend: {
                    labels: {
                        color: '#9ca3af'
                    }
                }
            }
        }
    });
    <?php endif; ?>
});

</script>


<?php
renderFooter();
?>