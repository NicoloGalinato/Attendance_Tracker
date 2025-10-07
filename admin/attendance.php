<?php
require_once '../includes/config.php';
require_once '../includes/functions.php';

if (!isLoggedIn() || !isAdmin()) {
    redirect(BASE_URL);
}

updateLastActivity();

// Get statistics - will be updated via AJAX
$stats = [
    'pending_emails' => 0,
    'pending_ir' => 0,
    'uncovered_shift' => 0, 
    'pending_coverage' => 0
];

// Handle AJAX request for stats
if (isset($_POST['ajax']) && $_POST['ajax'] === 'get_stats') {
    $tab = $_POST['tab'] ?? 'absenteeism';
    $stats = getStatsForTab($tab);
    header('Content-Type: application/json');
    echo json_encode($stats);
    exit;
}

function getStatsForTab($currentTab) {
    global $pdo;
    
    $stats = [
        'pending_emails' => 0,
        'pending_ir' => 0,
        'uncovered_shift' => 0, 
        'pending_coverage' => 0
    ];

    try {
        // Pending emails (not sent)
        if ($currentTab === 'absenteeism' || $currentTab === 'tardiness') {
            if ($currentTab === 'absenteeism') {
                $stmt = $pdo->query("SELECT COUNT(*) FROM absenteeism WHERE email_sent = 0");
                $stats['pending_emails'] = $stmt->fetchColumn();
            } else {
                $stmt = $pdo->query("SELECT COUNT(*) FROM tardiness WHERE email_sent = 0");
                $stats['pending_emails'] = $stmt->fetchColumn();
            }
        } else {
            // For VTO tab, show both
            $stmt = $pdo->query("SELECT COUNT(*) FROM absenteeism WHERE email_sent = 0");
            $stats['pending_emails'] += $stmt->fetchColumn();
            
            $stmt = $pdo->query("SELECT COUNT(*) FROM tardiness WHERE email_sent = 0");
            $stats['pending_emails'] += $stmt->fetchColumn();
        }
        
        // Pending IR forms
        if ($currentTab === 'absenteeism' || $currentTab === 'tardiness') {
            if ($currentTab === 'absenteeism') {
                $stmt = $pdo->query("SELECT COUNT(*) FROM absenteeism WHERE ir_form NOT REGEXP '^(YES|NO NEED)'");
                $stats['pending_ir'] = $stmt->fetchColumn();
            } else {
                $stmt = $pdo->query("SELECT COUNT(*) FROM tardiness WHERE ir_form NOT REGEXP '^(YES|FOR ACCUMULATION|NO NEED|EXPIRED)'");
                $stats['pending_ir'] += $stmt->fetchColumn();
            }
        } else {
            // For VTO tab, show both
            $stmt = $pdo->query("SELECT COUNT(*) FROM absenteeism WHERE ir_form NOT REGEXP '^(YES|NO NEED)'");
            $stats['pending_ir'] += $stmt->fetchColumn();

            $stmt = $pdo->query("SELECT COUNT(*) FROM tardiness WHERE ir_form NOT REGEXP '^(YES|FOR ACCUMULATION|NO NEED|EXPIRED)'");
            $stats['pending_ir'] += $stmt->fetchColumn();
        }

        // Pending Coverage - only for absenteeism
        if ($currentTab === 'absenteeism') {
            $stmt = $pdo->query("SELECT COUNT(*) FROM absenteeism WHERE coverage = 'PENDING'"); 
            $stats['pending_coverage'] = $stmt->fetchColumn();
        }

        // Pending Uncovered Shift - only for absenteeism
        if ($currentTab === 'absenteeism') {
            $todayDate = date('Y-m-d');
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM absenteeism WHERE coverage = 'UNCOVERED' AND date_of_absent = ?");
            $stmt->execute([$todayDate]);
            $stats['uncovered_shift'] = $stmt->fetchColumn();
        }
        
    } catch (PDOException $e) {
        // If there's an error, we'll just use the default 0 values
        error_log("Error getting stats: " . $e->getMessage());
    }
    
    return $stats;
}

// Get initial stats for current tab
$currentTab = isset($_GET['tab']) ? $_GET['tab'] : 'absenteeism';
$stats = getStatsForTab($currentTab);

// Get data for charts (last 12 months)
$chartData = [
    'months' => [],
    'absenteeism' => [],
    'tardiness' => [],
    'absenteeism_percentage' => [],
    'tardiness_percentage' => [],
    'vto_percentage' => []
];

try {
    // Get total number of active agents for percentage calculation
    $stmt = $pdo->query("SELECT COUNT(*) FROM employees WHERE is_active = 1");
    $totalActiveAgents = $stmt->fetchColumn();
    $totalActiveAgents = max($totalActiveAgents, 1); // Ensure we don't divide by zero

    // Get the first day of the current month
    $currentDate = new DateTime('first day of this month');
    
    for ($i = 11; $i >= 0; $i--) {
        // Create a copy of the current date
        $monthDate = clone $currentDate;
        
        // Subtract the appropriate number of months
        $monthDate->sub(new DateInterval("P{$i}M"));
        
        // Get the month info
        $month = $monthDate->format('Y-m');
        $startDate = $monthDate->format('Y-m-01');
        $endDate = $monthDate->format('Y-m-t'); // Last day of month
        
        $chartData['months'][] = $monthDate->format('M Y');
        
        // Absenteeism
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM absenteeism WHERE date_of_absent BETWEEN ? AND ?");
        $stmt->execute([$startDate, $endDate]);
        $absentCount = $stmt->fetchColumn();
        $chartData['absenteeism'][] = $absentCount;
        $chartData['absenteeism_percentage'][] = round(($absentCount / $totalActiveAgents) * 100, 2);
        
        // Tardiness
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM tardiness WHERE date_of_incident BETWEEN ? AND ?");
        $stmt->execute([$startDate, $endDate]);
        $tardyCount = $stmt->fetchColumn();
        $chartData['tardiness'][] = $tardyCount;
        $chartData['tardiness_percentage'][] = round(($tardyCount / $totalActiveAgents) * 100, 2);

        // VTO
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM vto_tracker WHERE shift_date BETWEEN ? AND ?");
        $stmt->execute([$startDate, $endDate]);
        $vtoCount = $stmt->fetchColumn();
        $chartData['vto'][] = $vtoCount;
        $chartData['vto_percentage'][] = round(($vtoCount / $totalActiveAgents) * 100, 2);
    }
} catch (PDOException $e) {
    // Log error if needed
    error_log("Database error in dashboard chart data: " . $e->getMessage());
}


// Handle deletion
if (isset($_GET['delete'])) {
    // Start session if not already started
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    
    $id = (int)$_GET['delete'];
    $type = isset($_GET['type']) ? $_GET['type'] : 'absenteeism';
    
    // Check if password is provided and correct
    $requiredPassword = "SLT@2025"; // Change this to your actual password
    $providedPassword = $_POST['delete_password'] ?? '';
    
    if (empty($providedPassword) || $providedPassword !== $requiredPassword) {
        $_SESSION['error'] = "Incorrect or missing password for deletion";
        redirect('attendance.php?tab=' . $type);
        exit();
    }
    
    try {
        $table = ($type === 'tardiness') ? 'tardiness' : ($type === 'vto' ? 'vto_tracker' : 'absenteeism');
        $stmt = $pdo->prepare("DELETE FROM $table WHERE id = ?");
        $stmt->execute([$id]);
        
        if ($stmt->rowCount() > 0) {
            $_SESSION['success'] = "Record deleted successfully!";
        } else {
            $_SESSION['error'] = "Record not found";
        }
    } catch (PDOException $e) {
        $_SESSION['error'] = "Error deleting record: " . $e->getMessage();
    }
    
    redirect('attendance.php?tab=' . $type);
}

// Handle email sending
if (isset($_GET['send_email'])) {
    $id = (int)$_GET['send_email'];
    $type = isset($_GET['type']) ? $_GET['type'] : 'absenteeism';
    
    try {
        $table = ($type === 'tardiness') ? 'tardiness' : 'absenteeism';
        
        // Get record data
        $stmt = $pdo->prepare("SELECT * FROM $table WHERE id = ?");
        $stmt->execute([$id]);
        $record = $stmt->fetch();
        
        if ($record) {
            // Here you would implement your email sending logic
            // For now, we'll just update the record to mark as sent
            
            $updateStmt = $pdo->prepare("UPDATE $table SET email_sent = 1, email_sent_at = CONVERT_TZ(NOW(), 'SYSTEM', 'Asia/Manila') WHERE id = ?");
            $updateStmt->execute([$id]);
            
            $_SESSION['success'] = "Email sent successfully!";
        } else {
            $_SESSION['error'] = "Record not found";
        }
    } catch (PDOException $e) {
        $_SESSION['error'] = "Error sending email: " . $e->getMessage();
    }
    
    redirect('attendance.php?tab=' . $type);
}

if (isset($_SESSION['check_pending_ir'])) {
    $employeeId = $_SESSION['check_pending_ir'];
    unset($_SESSION['check_pending_ir']);
    
    // Get all pending IRs for this employee
    try {
        $stmt = $pdo->prepare("SELECT id, employee_id, full_name, date_of_absent, ir_form FROM absenteeism 
                      WHERE employee_id = ? AND ir_form LIKE 'PENDING%' 
                      ORDER BY date_of_absent");
        $stmt->execute([$employeeId]);
        $pendingIRs = $stmt->fetchAll();
        
        if (count($pendingIRs) > 0) {
            $showPendingIRModal = true;
            $pendingIRData = $pendingIRs;
            
            // Also get employee details for the modal
            $stmt = $pdo->prepare("SELECT full_name FROM employees WHERE employee_id = ?");
            $stmt->execute([$employeeId]);
            $employee = $stmt->fetch();
            $employeeName = $employee ? $employee['full_name'] : '';
        }
    } catch (PDOException $e) {
        error_log("Error checking pending IRs: " . $e->getMessage());
    }
}

require_once '../components/layout.php';
renderHead('Attendance Tracker');
renderNavbar();
renderSidebar('attendance');

$currentTab = isset($_GET['tab']) ? $_GET['tab'] : 'absenteeism';
?>

<!-- Minimal Loading Screen - Only for initial page load -->
<div id="initialLoading" class="fixed inset-0 bg-gray-900 flex items-center justify-center z-50 transition-opacity duration-300">
    <div class="text-center">
        <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-blue-500 mx-auto mb-4"></div>
        <p class="text-white text-lg">Loading Attendance Tracker...</p>
    </div>
</div>

<div class=" pt-2 min-h-screen">
    <main class="p-6">
        <div class="flex justify-between items-center mb-6">
            <h1 class="text-2xl font-bold">Attendance Tracker</h1>
            <div class="flex items-center gap-2">
                <!-- No Need Email Button (initially hidden) -->
                <button id="noNeedEmailBtn" class="hidden bg-yellow-600 hover:bg-yellow-700 text-white px-4 py-2 rounded-lg flex items-center">
                    <i class="fas fa-envelope mr-2"></i> No Need Email
                </button>
                <!-- Re-track Email Button (initially hidden) -->
                <button id="reTrackEmailBtn" class="hidden bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg flex items-center">
                    <i class="fas fa-redo-alt mr-2"></i> Re-track Email
                </button>
                <a href="<?= $currentTab === 'vto' ? 'vto_form.php' : 'attendance_form.php' ?>?action=create&type=<?= $currentTab ?>" class="bg-primary-600 hover:bg-primary-700 text-white px-4 py-2 rounded-lg flex items-center">
                    <i class="fas fa-plus mr-2"></i> Add New
                </a>
            </div>
        </div>
        
        <!-- Stats Cards -->
        <div id="statsCardsContainer" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
            <!-- Pending Emails -->
            <button type="button" name="filter" value="pending_emails" 
                class="filter-button bg-gray-800 rounded-xl border border-gray-700 p-6 shadow hover:border-blue-500 transition-colors duration-200 text-left 
                    <?= ($currentTab === 'vto') ? 'opacity-50 cursor-not-allowed' : '' ?>" 
                <?= ($currentTab === 'vto') ? 'disabled' : '' ?>>
                <div class="flex items-center justify-between">
                    <div>
                        <h3 class="text-gray-400 text-sm font-medium">Pending Emails</h3>
                        <p class="text-3xl font-bold mt-2 text-gray-100" id="pendingEmailsCount"><?= $stats['pending_emails'] ?></p>
                        <p class="text-xs text-gray-400 mt-1">Emails not yet sent</p>
                    </div>
                    <div class="bg-primary-500/20 p-3 rounded-full">
                        <i class="fas fa-envelope text-primary-500 text-xl"></i>
                    </div>
                </div>
            </button>
        
            <!-- Pending IR Forms -->
            <button type="button" name="filter" value="pending_ir" 
                class="filter-button bg-gray-800 rounded-xl border border-gray-700 p-6 shadow hover:border-blue-500 transition-colors duration-200 text-left 
                    <?= ($currentTab === 'vto') ? 'opacity-50 cursor-not-allowed' : '' ?>" 
                <?= ($currentTab === 'vto') ? 'disabled' : '' ?>>
                <div class="flex items-center justify-between">
                    <div>
                        <h3 class="text-gray-400 text-sm font-medium">Pending IR Forms</h3>
                        <p class="text-3xl font-bold mt-2 text-gray-100" id="pendingIrCount"><?= $stats['pending_ir'] ?></p>
                        <p class="text-xs text-gray-400 mt-1">Forms not yet submitted</p>
                    </div>
                    <div class="bg-yellow-500/20 p-3 rounded-full">
                        <i class="fas fa-file-alt text-yellow-500 text-xl"></i>
                    </div>
                </div>
            </button>
        
            <!-- Pending Coverage -->
            <button type="button" name="filter" value="pending_coverage" 
                class="filter-button bg-gray-800 rounded-xl border border-gray-700 p-6 shadow hover:border-blue-500 transition-colors duration-200 text-left 
                    <?= ($currentTab === 'tardiness' || $currentTab === 'vto') ? 'opacity-50 cursor-not-allowed' : '' ?>" 
                <?= ($currentTab === 'tardiness' || $currentTab === 'vto') ? 'disabled' : '' ?>>
                <div class="flex items-center justify-between">
                    <div>
                        <h3 class="text-gray-400 text-sm font-medium">Pending Coverage</h3>
                        <p class="text-3xl font-bold mt-2 text-gray-100" id="pendingCoverageCount"><?= $stats['pending_coverage'] ?></p>
                        <p class="text-xs text-gray-400 mt-1">Shift not yet covered</p>
                    </div>
                    <div class="bg-orange-500/20 p-3 rounded-full">
                        <i class="fas fa-clock text-orange-500 text-xl"></i>
                    </div>
                </div>
            </button>
        
            <!-- Uncovered Shift -->
            <button type="button" name="filter" value="uncovered_shift" 
                class="filter-button bg-gray-800 rounded-xl border border-gray-700 p-6 shadow hover:border-blue-500 transition-colors duration-200 text-left 
                    <?= ($currentTab === 'tardiness' || $currentTab === 'vto') ? 'opacity-50 cursor-not-allowed' : '' ?>" 
                <?= ($currentTab === 'tardiness' || $currentTab === 'vto') ? 'disabled' : '' ?>>
                <div class="flex items-center justify-between">
                    <div>
                        <h3 class="text-gray-400 text-sm font-medium">Uncovered Shift</h3>
                        <p class="text-3xl font-bold mt-2 text-gray-100" id="uncoveredShiftCount"><?= $stats['uncovered_shift'] ?></p>
                        <p class="text-xs text-gray-400 mt-1">Today's uncovered shift</p>
                    </div>
                    <div class="bg-red-500/20 p-3 rounded-full">
                        <i class="fas fa-book text-red-500 text-xl"></i>
                    </div>
                </div>
            </button>
        </div>

        <!-- Tabs -->
        <div class="border-b border-gray-700 mb-6">
            <nav class="-mb-px flex space-x-8">
                <a href="#" data-tab="absenteeism" class="tab-link <?= $currentTab === 'absenteeism' ? 'border-primary-500 text-primary-400' : 'border-transparent text-gray-400 hover:text-gray-300' ?> whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm">
                    Absenteeism
                </a>
                <a href="#" data-tab="tardiness" class="tab-link <?= $currentTab === 'tardiness' ? 'border-primary-500 text-primary-400' : 'border-transparent text-gray-400 hover:text-gray-300' ?> whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm">
                    Tardiness
                </a>
                <a href="#" data-tab="vto" class="tab-link <?= $currentTab === 'vto' ? 'border-primary-500 text-primary-400' : 'border-transparent text-gray-400 hover:text-gray-300' ?> whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm">
                    VTO Tracker
                </a>
            </nav>
        </div>

        <?php renderAlert(); ?>
        
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-6 gap-4 mb-6">   
            <!-- Search Input -->
            <div class="relative sm:col-span-2 lg:col-span-1">
                <input type="text" id="searchInput" 
                    class="w-full pl-10 pr-4 py-2 bg-gray-700/50 border border-gray-600/50 rounded-lg text-gray-200 shadow hover:border-blue-500 transition-colors duration-200 text-left"
                    placeholder="Search by employee ID or name..."
                    value="<?= isset($_GET['search']) ? htmlspecialchars($_GET['search']) : '' ?>">
                <div class="absolute left-3 top-2.5 text-gray-400">
                    <i class="fas fa-search"></i>
                </div>
            </div>
            
            <!-- Date Range Filter -->
            <div class="flex gap-2 sm:col-span-2 lg:col-span-1">
                <div class="relative flex-grow">
                    <input type="date" id="dateFrom" 
                        class="w-full px-4 py-2 bg-gray-700/50 border border-gray-600/50 rounded-lg text-gray-200 shadow hover:border-blue-500 transition-colors duration-200 text-left"
                        value="<?= isset($_GET['from']) ? htmlspecialchars($_GET['from']) : '' ?>">
                </div>
            </div>
            <div class="flex gap-2 sm:col-span-2 lg:col-span-1">
                <div class="relative flex-grow">
                    <input type="date" id="dateTo" 
                        class="w-full px-4 py-2 bg-gray-700/50 border border-gray-600/50 rounded-lg text-gray-200 shadow hover:border-blue-500 transition-colors duration-200 text-left"
                        value="<?= isset($_GET['to']) ? htmlspecialchars($_GET['to']) : '' ?>">
                </div>
            </div>
            
            <!-- Department Filter -->
            <div class="relative sm:col-span-1 lg:col-span-1">
                <select id="departmentFilter" 
                        class="w-full px-4 py-2 bg-gray-700/50 border border-gray-600/50 rounded-lg text-gray-200 appearance-none shadow hover:border-blue-500 transition-colors duration-200 text-left">
                    <option value="">ALL DEPARTMENTS</option>
                    <?php
                    $stmt = $pdo->query("SELECT DISTINCT department FROM absenteeism UNION SELECT DISTINCT department FROM tardiness ORDER BY department");
                    while ($row = $stmt->fetch()) {
                        $selected = (isset($_GET['dept']) && $_GET['dept'] === $row['department']) ? 'selected' : '';
                        echo '<option value="'.htmlspecialchars($row['department']).'" '.$selected.'>'.htmlspecialchars($row['department']).'</option>';
                    }
                    ?>
                </select>
                <div class="absolute right-3 top-2.5 text-gray-400 pointer-events-none">
                    <i class="fas fa-chevron-down"></i>
                </div>
            </div>
            
            <!-- Coverage Filter -->
            <div class="relative sm:col-span-1 lg:col-span-1">
                <select id="coverageFilter" 
                        class="w-full px-4 py-2 bg-gray-700/50 border border-gray-600/50 rounded-lg text-gray-200 appearance-none shadow hover:border-blue-500 transition-colors duration-200 text-left 
                    <?= ($currentTab === 'tardiness' || $currentTab === 'vto') ? 'opacity-50 cursor-not-allowed' : '' ?>" 
                <?= ($currentTab === 'tardiness' || $currentTab === 'vto') ? 'disabled' : '' ?>>
                    <option value="">ALL COVERAGE</option>
                    <?php
                    // Define the specific values we want to show in the filter
                    $filterValues = ['UNCOVERED', 'PENDING', 'NO NEED', 'N/A', '-'];
                    
                    foreach ($filterValues as $value) {
                        $selected = (isset($_GET['cov']) && $_GET['cov'] === $value) ? 'selected' : '';
                        echo '<option value="'.htmlspecialchars($value).'" '.$selected.'>'.htmlspecialchars($value).'</option>';
                    }
                    ?>
                </select>
                <div class="absolute right-3 top-2.5 text-gray-400 pointer-events-none">
                    <i class="fas fa-chevron-down"></i>
                </div>
            </div>

            <!-- IR Filter -->
            <div class="relative sm:col-span-1 lg:col-span-1">
                <select id="irFilter" class="w-full px-4 py-2 bg-gray-700/50 border border-gray-600/50 rounded-lg text-gray-200 appearance-none shadow hover:border-blue-500 transition-colors duration-200 text-left 
                    <?= ($currentTab === 'vto') ? 'opacity-50 cursor-not-allowed' : '' ?>" 
                <?= ($currentTab === 'vto') ? 'disabled' : '' ?>>
                    <option value="">ALL INCIDENT REPORTS</option>
                    <?php
                    // Get the current tab to determine which options to show
                    $currentTab = isset($_GET['tab']) ? $_GET['tab'] : 'absenteeism';
                    
                    if ($currentTab === 'tardiness') {
                        // Tardiness-specific options - auto-populate from database
                        $standardOptions = [];
                        $pendingOptions = [];
                        
                        // Get distinct IR form values from tardiness table
                        $stmt = $pdo->query("SELECT DISTINCT ir_form FROM tardiness WHERE ir_form IS NOT NULL AND ir_form != ''");
                        while ($row = $stmt->fetch()) {
                            $irForm = $row['ir_form'];
                            
                            // Categorize the IR forms
                            if ($irForm === 'FOR IR' || $irForm === 'FOR ACCUMULATION') {
                                $standardOptions[$irForm] = $irForm;
                            } 
                            elseif ($irForm === 'PENDING') {
                                $standardOptions[$irForm] = $irForm;
                            }
                            // Handle pending dates
                            elseif (preg_match('/PENDING \/ ([A-Z]{3,4} [0-9]{1,2})/', $irForm, $matches)) {
                                $dateOnly = "PENDING / " . $matches[1];
                                $pendingOptions[$dateOnly] = $dateOnly;
                            }
                        }
                        
                        // Sort pending options in descending order
                        krsort($pendingOptions);
                        
                        // Display standard options first (FOR IR, FOR ACCUMULATION, PENDING)
                        foreach ($standardOptions as $value => $label) {
                            $selected = (isset($_GET['ir']) && $_GET['ir'] === $value) ? 'selected' : '';
                            echo '<option value="'.htmlspecialchars($value).'" '.$selected.'>'.htmlspecialchars($label).'</option>';
                        }
                        
                        // Display pending dates (in descending order)
                        foreach ($pendingOptions as $value => $label) {
                            $selected = (isset($_GET['ir']) && $_GET['ir'] === $value) ? 'selected' : '';
                            echo '<option value="'.htmlspecialchars($value).'" '.$selected.'>'.htmlspecialchars($label).'</option>';
                        }
                        
                        // If no specific options found, still show the standard ones
                        if (empty($standardOptions) && empty($pendingOptions)) {
                            $defaultOptions = ['FOR IR', 'FOR ACCUMULATION', 'PENDING'];
                            foreach ($defaultOptions as $option) {
                                $selected = (isset($_GET['ir']) && $_GET['ir'] === $option) ? 'selected' : '';
                                echo '<option value="'.htmlspecialchars($option).'" '.$selected.'>'.htmlspecialchars($option).'</option>';
                            }
                        }
                    } else {
                        // Absenteeism options - auto-populate from database
                        $standardOptions = [];
                        $pendingOptions = [];
                        
                        // Get distinct IR form values from absenteeism table
                        $stmt = $pdo->query("SELECT DISTINCT ir_form FROM absenteeism WHERE ir_form IS NOT NULL AND ir_form != ''");
                        while ($row = $stmt->fetch()) {
                            $irForm = $row['ir_form'];
                            
                            // Categorize the IR forms
                            if ($irForm === 'FOR IR' || $irForm === 'NO NEED') {
                                $standardOptions[$irForm] = $irForm;
                            } 
                            // Handle pending dates
                            elseif (preg_match('/PENDING \/ ([A-Z]{3,4} [0-9]{1,2})/', $irForm, $matches)) {
                                $dateOnly = "PENDING / " . $matches[1];
                                $pendingOptions[$dateOnly] = $dateOnly;
                            }
                        }
                        
                        // Sort pending options in descending order
                        krsort($pendingOptions);
                        
                        // Display standard options first (FOR IR, NO NEED)
                        foreach ($standardOptions as $value => $label) {
                            $selected = (isset($_GET['ir']) && $_GET['ir'] === $value) ? 'selected' : '';
                            echo '<option value="'.htmlspecialchars($value).'" '.$selected.'>'.htmlspecialchars($label).'</option>';
                        }
                        
                        // Display pending dates (in descending order)
                        foreach ($pendingOptions as $value => $label) {
                            $selected = (isset($_GET['ir']) && $_GET['ir'] === $value) ? 'selected' : '';
                            echo '<option value="'.htmlspecialchars($value).'" '.$selected.'>'.htmlspecialchars($label).'</option>';
                        }
                        
                        // If no specific options found, still show the standard ones
                        if (empty($standardOptions) && empty($pendingOptions)) {
                            $defaultOptions = ['FOR IR', 'NO NEED'];
                            foreach ($defaultOptions as $option) {
                                $selected = (isset($_GET['ir']) && $_GET['ir'] === $option) ? 'selected' : '';
                                echo '<option value="'.htmlspecialchars($option).'" '.$selected.'>'.htmlspecialchars($option).'</option>';
                            }
                        }
                    }
                    ?>
                </select>
                <div class="absolute right-3 top-2.5 text-gray-400 pointer-events-none">
                    <i class="fas fa-chevron-down"></i>
                </div>
            </div>
        </div>

        <div id="attendanceTableContainer" class="transition-opacity duration-200">
            <?php include 'partials/attendance_table.php'; ?>
        </div>


        <?php if (isset($showPendingIRModal) && $showPendingIRModal): ?>
        <div id="pendingIRModal" class="fixed inset-0 bg-gray-900 bg-opacity-50 flex items-center justify-center z-50">
            <div class="bg-gray-800 rounded-lg border border-gray-700 shadow-xl w-full max-w-4xl">
                <div class="px-6 py-6">
                    <h3 class="text-lg font-bold text-gray-100 mb-4">Pending IR Forms Found for <?= htmlspecialchars($employeeName) ?></h3>
                    <p class="text-gray-300 mb-4">This agent has <?= count($pendingIRData) ?> pending IR form(s). Would you like to update them all?</p>
                    
                    <div class="mb-4">
                        <label for="irFormUpdate" class="block text-sm font-medium text-gray-300 mb-2">New IR Form Status:</label>
                        <input type="text" id="irFormUpdate" class="w-full px-4 py-2 bg-gray-700 border border-gray-600 rounded-md text-gray-200" 
                            placeholder="Enter the latest IR form current status (e.g., PENDING / Current Status)">
                    </div>
                    
                    <div class="overflow-x-auto mb-6">
                        <table class="min-w-full divide-y divide-gray-700">
                            <thead class="bg-gray-700">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-300 uppercase">Employee ID</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-300 uppercase">Full Name</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-300 uppercase">Date</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-300 uppercase">Current Status</th>
                                </tr>
                            </thead>
                            <tbody class="bg-gray-800 divide-y divide-gray-700">
                                <?php foreach ($pendingIRData as $ir): ?>
                                <tr>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-100">
                                        <?= htmlspecialchars($ir['employee_id']) ?>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-300">
                                        <?= htmlspecialchars($ir['full_name']) ?>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-300">
                                        <?= date('M d, Y', strtotime($ir['date_of_absent'])) ?>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-300">
                                        <?= htmlspecialchars($ir['ir_form']) ?>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    
                    <div class="flex justify-end space-x-3">
                        <button onclick="closePendingIRModal()" class="px-4 py-2 bg-gray-600 text-gray-100 rounded-md hover:bg-gray-500">
                            Skip
                        </button>
                        <button onclick="updateAllPendingIRs()" class="px-4 py-2 bg-primary-600 text-white rounded-md hover:bg-primary-500">
                            Update All
                        </button>
                    </div>
                </div>
            </div>
        </div>

<script>
function closePendingIRModal() {
    document.getElementById('pendingIRModal').remove();
}

function updateAllPendingIRs() {
    const newStatus = document.getElementById('irFormUpdate').value;
    if (!newStatus.trim()) {
        alert('Please enter a valid IR form status');
        return;
    }
    
    fetch('../includes/update_pending_irs.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({
            employee_id: '<?= $employeeId ?>',
            new_status: newStatus
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert('Successfully updated ' + data.updated + ' records');
            closePendingIRModal();
            
            // Only refresh if there were pending forms
            if (data.has_pending) {
                document.getElementById('mainFilterForm').submit();
            }
        } else {
            alert('Error: ' + data.message);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('An error occurred while updating records');
    });
}
</script>
<?php endif; ?>
    </main>
</div>

<script>
// Simple Loading Manager - Only for initial load
class SimpleLoadingManager {
    constructor() {
        this.initialLoading = document.getElementById('initialLoading');
        this.init();
    }

    init() {
        // Hide loading when page is ready
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', () => this.hideInitialLoading());
        } else {
            this.hideInitialLoading();
        }
    }

    hideInitialLoading() {
        setTimeout(() => {
            if (this.initialLoading) {
                this.initialLoading.style.opacity = '0';
                setTimeout(() => {
                    this.initialLoading.remove();
                }, 100);
            }
        }, 300);
    }
}

// Initialize loading manager
const loadingManager = new SimpleLoadingManager();

// Optimized Attendance Manager
class AttendanceManager {
    constructor() {
        this.searchInput = document.getElementById('searchInput');
        this.dateFrom = document.getElementById('dateFrom');
        this.dateTo = document.getElementById('dateTo');
        this.departmentFilter = document.getElementById('departmentFilter');
        this.coverageFilter = document.getElementById('coverageFilter');
        this.irFilter = document.getElementById('irFilter');
        this.filterButtons = document.querySelectorAll('.filter-button');
        this.tabLinks = document.querySelectorAll('.tab-link');
        this.tableContainer = document.getElementById('attendanceTableContainer');
        this.statsCardsContainer = document.getElementById('statsCardsContainer');
        
        this.searchTimeout = null;
        this.isLoading = false;
        this.pendingRequest = null;
        this.currentTab = '<?= $currentTab ?>';
        this.currentPage = 1;
        this.filters = {
            search: '',
            from: '',
            to: '',
            dept: '',
            cov: '',
            ir: '',
            filter: ''
        };
        
        this.init();
    }

    init() {
        this.bindEvents();
        this.loadInitialData();
    }

    bindEvents() {
        // Search with debouncing
        this.searchInput?.addEventListener('input', (e) => {
            this.filters.search = e.target.value;
            clearTimeout(this.searchTimeout);
            this.searchTimeout = setTimeout(() => {
                this.loadTable(1);
            }, 300);
        });

        // Other filters
        [this.dateFrom, this.dateTo, this.departmentFilter, this.coverageFilter, this.irFilter].forEach(element => {
            element?.addEventListener('change', () => {
                this.updateFilters();
                this.loadTable(1);
            });
        });

        // Filter buttons
        this.filterButtons.forEach(button => {
            button.addEventListener('click', (e) => {
                if (!button.disabled) {
                    const filterValue = button.getAttribute('value');
                    this.filters.filter = filterValue;
                    this.loadTable(1);
                }
            });
        });

        // Tab links
        this.tabLinks.forEach(link => {
            link.addEventListener('click', (e) => {
                e.preventDefault();
                const tab = link.getAttribute('data-tab');
                this.switchTab(tab);
            });
        });
    }

    updateFilters() {
        this.filters = {
            search: this.searchInput.value,
            from: this.dateFrom.value,
            to: this.dateTo.value,
            dept: this.departmentFilter.value,
            cov: this.coverageFilter.value,
            ir: this.irFilter.value,
            filter: this.filters.filter
        };
    }

    switchTab(tab) {
        this.currentTab = tab;
        this.currentPage = 1;
        this.filters.filter = '';
        
        // Update active tab UI
        this.tabLinks.forEach(link => {
            if (link.getAttribute('data-tab') === tab) {
                link.classList.add('border-primary-500', 'text-primary-400');
                link.classList.remove('border-transparent', 'text-gray-400');
            } else {
                link.classList.remove('border-primary-500', 'text-primary-400');
                link.classList.add('border-transparent', 'text-gray-400');
            }
        });

        // Update stats cards for the new tab
        this.updateStatsCards(tab);
        
        // Update URL without reload
        this.updateUrl();
        this.loadTable(1);
    }

    async updateStatsCards(tab) {
        try {
            const formData = new FormData();
            formData.append('ajax', 'get_stats');
            formData.append('tab', tab);

            const response = await fetch('attendance.php', {
                method: 'POST',
                body: formData,
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            });

            if (!response.ok) throw new Error('Network response was not ok');

            const stats = await response.json();
            
            // Update the stats numbers
            document.getElementById('pendingEmailsCount').textContent = stats.pending_emails;
            document.getElementById('pendingIrCount').textContent = stats.pending_ir;
            document.getElementById('pendingCoverageCount').textContent = stats.pending_coverage;
            document.getElementById('uncoveredShiftCount').textContent = stats.uncovered_shift;

            // Update button states based on tab
            this.updateCardButtonsState(tab);

        } catch (error) {
            console.error('Error updating stats:', error);
        }
    }

    updateCardButtonsState(tab) {
        const pendingEmailsBtn = document.querySelector('button[value="pending_emails"]');
        const pendingIrBtn = document.querySelector('button[value="pending_ir"]');
        const pendingCoverageBtn = document.querySelector('button[value="pending_coverage"]');
        const uncoveredShiftBtn = document.querySelector('button[value="uncovered_shift"]');

        // Reset all buttons first
        [pendingEmailsBtn, pendingIrBtn, pendingCoverageBtn, uncoveredShiftBtn].forEach(btn => {
            if (btn) {
                btn.classList.remove('opacity-50', 'cursor-not-allowed');
                btn.disabled = false;
            }
        });

        // Disable appropriate buttons based on tab
        if (tab === 'vto') {
            // Disable all filter buttons for VTO tab
            [pendingEmailsBtn, pendingIrBtn, pendingCoverageBtn, uncoveredShiftBtn].forEach(btn => {
                if (btn) {
                    btn.classList.add('opacity-50', 'cursor-not-allowed');
                    btn.disabled = true;
                }
            });
        } else if (tab === 'tardiness') {
            // Disable coverage-related buttons for tardiness
            if (pendingCoverageBtn) {
                pendingCoverageBtn.classList.add('opacity-50', 'cursor-not-allowed');
                pendingCoverageBtn.disabled = true;
            }
            if (uncoveredShiftBtn) {
                uncoveredShiftBtn.classList.add('opacity-50', 'cursor-not-allowed');
                uncoveredShiftBtn.disabled = true;
            }
        }
        // For absenteeism tab, all buttons are enabled (default state)
    }

    async loadTable(page = 1) {
        if (this.isLoading && this.pendingRequest) {
            this.pendingRequest.abort();
        }

        this.currentPage = page;
        this.isLoading = true;
        
        // Show loading state
        this.tableContainer.style.opacity = '0.7';

        try {
            const formData = new FormData();
            formData.append('tab', this.currentTab);
            formData.append('page', page);
            
            // Add all filters
            Object.entries(this.filters).forEach(([key, value]) => {
                if (value) formData.append(key, value);
            });

            const controller = new AbortController();
            this.pendingRequest = controller;

            const response = await fetch('partials/attendance_table.php', {
                method: 'POST',
                body: formData,
                signal: controller.signal,
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            });

            if (!response.ok) throw new Error('Network response was not ok');

            const html = await response.text();
            this.tableContainer.innerHTML = html;
            this.isLoading = false;
            this.pendingRequest = null;
            this.tableContainer.style.opacity = '1';
            
            this.rebindTableEvents();
            this.updateUrl();

        } catch (error) {
            if (error.name !== 'AbortError') {
                console.error('Error loading table:', error);
                this.isLoading = false;
                this.pendingRequest = null;
                this.tableContainer.style.opacity = '1';
                this.showError('Error loading data. Please try again.');
            }
        }
    }

    rebindTableEvents() {
        // Rebind pagination events
        document.querySelectorAll('.pagination-link').forEach(link => {
            link.addEventListener('click', (e) => {
                e.preventDefault();
                const page = parseInt(link.getAttribute('data-page'));
                this.loadTable(page);
            });
        });

        // Rebind checkbox events
        document.querySelectorAll('.record-checkbox').forEach(checkbox => {
            checkbox.addEventListener('change', () => this.toggleActionButtons());
        });

        // Rebind select all checkbox
        const selectAll = document.getElementById('selectAllCheckbox');
        if (selectAll) {
            selectAll.addEventListener('change', (e) => {
                const isChecked = e.target.checked;
                document.querySelectorAll('.record-checkbox').forEach(checkbox => {
                    checkbox.checked = isChecked;
                });
                this.toggleActionButtons();
            });
        }
    }

    toggleActionButtons() {
        const anyChecked = Array.from(document.querySelectorAll('.record-checkbox')).some(checkbox => checkbox.checked);
        const noNeedEmailBtn = document.getElementById('noNeedEmailBtn');
        const reTrackEmailBtn = document.getElementById('reTrackEmailBtn');
        
        if (this.currentTab === 'vto') {
            if (noNeedEmailBtn) noNeedEmailBtn.classList.add('hidden');
            if (reTrackEmailBtn) reTrackEmailBtn.classList.add('hidden');
            return;
        }
        
        if (anyChecked) {
            if (noNeedEmailBtn) noNeedEmailBtn.classList.remove('hidden');
            if (reTrackEmailBtn) reTrackEmailBtn.classList.remove('hidden');
        } else {
            if (noNeedEmailBtn) noNeedEmailBtn.classList.add('hidden');
            if (reTrackEmailBtn) reTrackEmailBtn.classList.add('hidden');
        }
    }

    updateUrl() {
        const params = new URLSearchParams();
        params.append('tab', this.currentTab);
        
        // Add all filters
        Object.entries(this.filters).forEach(([key, value]) => {
            if (value) params.append(key, value);
        });
        
        if (this.currentPage > 1) params.append('page', this.currentPage);
        
        const queryString = params.toString();
        const newUrl = queryString ? `?${queryString}` : window.location.pathname;
        history.replaceState(null, '', newUrl);
    }

    loadInitialData() {
        const urlParams = new URLSearchParams(window.location.search);
        
        // Set current tab
        const initialTab = urlParams.get('tab') || 'absenteeism';
        this.currentTab = initialTab;
        
        // Set filters from URL
        this.filters = {
            search: urlParams.get('search') || '',
            from: urlParams.get('from') || '',
            to: urlParams.get('to') || '',
            dept: urlParams.get('dept') || '',
            cov: urlParams.get('cov') || '',
            ir: urlParams.get('ir') || '',
            filter: urlParams.get('filter') || ''
        };
        
        // Set filter values in UI
        this.searchInput.value = this.filters.search;
        this.dateFrom.value = this.filters.from;
        this.dateTo.value = this.filters.to;
        this.departmentFilter.value = this.filters.dept;
        this.coverageFilter.value = this.filters.cov;
        this.irFilter.value = this.filters.ir;
        
        // Set initial page
        this.currentPage = parseInt(urlParams.get('page')) || 1;
        
        // Update card buttons state for initial tab
        this.updateCardButtonsState(this.currentTab);
        
        // Load initial data
        this.loadTable(this.currentPage);
    }

    showError(message) {
        this.tableContainer.innerHTML = `
            <div class="bg-red-900/20 border border-red-700 rounded-lg p-6 text-center">
                <i class="fas fa-exclamation-triangle text-red-400 text-2xl mb-3"></i>
                <p class="text-red-300 mb-4">${message}</p>
                <button onclick="attendanceManager.loadTable(1)" 
                        class="bg-red-600 hover:bg-red-500 text-white px-4 py-2 rounded-lg transition-colors duration-200">
                    <i class="fas fa-redo mr-2"></i>Try Again
                </button>
            </div>
        `;
    }
}

// Initialize attendance manager when DOM is ready
document.addEventListener('DOMContentLoaded', () => {
    window.attendanceManager = new AttendanceManager();
});

// Global functions for modals and other interactions
function showDeleteModal(recordId, recordType = '') {
    const modal = document.createElement('div');
    modal.id = 'deleteModal';
    modal.className = 'fixed inset-0 bg-gray-900/50 backdrop-blur-sm flex items-center justify-center z-50 transition-opacity duration-200';
    modal.innerHTML = `
        <div class="bg-gray-800 rounded-lg border border-gray-700 shadow-xl w-full max-w-md transform transition-transform duration-200 scale-95">
            <div class="px-6 py-6">
                <h3 class="text-lg font-bold text-gray-100 mb-4">Confirm Deletion</h3>
                <p class="text-gray-300 mb-4">Are you sure you want to delete this record?</p>
                <form method="post" action="attendance.php?delete=${recordId}&type=${recordType}" class="space-y-4">
                    <div>
                        <label for="delete_password" class="block text-sm font-medium text-gray-300 mb-1">To confirm please enter the KEY:</label>
                        <input type="password" name="delete_password" id="delete_password" 
                               class="w-full px-3 py-2 bg-gray-700 border border-gray-600 rounded-md text-gray-200 focus:border-red-500 focus:ring-1 focus:ring-red-500 transition-colors duration-200" 
                               required autocomplete="off">
                    </div>
                    <div class="flex justify-end space-x-3">
                        <button type="button" onclick="closeDeleteModal()" 
                                class="px-4 py-2 bg-gray-600 text-gray-100 rounded-md hover:bg-gray-500 transition-colors duration-200">
                            Cancel
                        </button>
                        <button type="submit" 
                                class="px-4 py-2 bg-red-600 text-white rounded-md hover:bg-red-500 transition-colors duration-200">
                            Delete
                        </button>
                    </div>
                </form>
            </div>
        </div>
    `;
    
    document.body.appendChild(modal);
    
    setTimeout(() => {
        modal.querySelector('div').classList.remove('scale-95');
    }, 10);
    
    modal.querySelector('input').focus();
}

function closeDeleteModal() {
    const modal = document.getElementById('deleteModal');
    if (modal) {
        modal.querySelector('div').classList.add('scale-95');
        setTimeout(() => modal.remove(), 150);
    }
}

// Global event listeners for modals
document.addEventListener('click', function(e) {
    if (e.target === document.getElementById('deleteModal')) closeDeleteModal();
});

document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        if (document.getElementById('deleteModal')) closeDeleteModal();
    }
});
</script>

<?php
renderFooter();
?>