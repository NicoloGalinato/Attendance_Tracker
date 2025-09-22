<?php
require_once '../includes/config.php';
require_once '../includes/functions.php';

if (!isLoggedIn() || !isAdmin()) {
    redirect(BASE_URL);
}

updateLastActivity();

// Get statistics
$stats = [
    
    'pending_emails' => 0,
    'pending_ir' => 0,
    'uncovered_shift' => 0, 
    'pending_coverage' => 0, 
    'absent_today' => 0,
    'absent_week' => 0,
    'absent_month' => 0,
    'absent_year' => 0,
    'late_today' => 0,
    'late_week' => 0,
    'late_month' => 0,
    'late_year' => 0
];

try {
    $currentTab = isset($_GET['tab']) ? $_GET['tab'] : 'absenteeism';

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
        // For other tabs or when no tab is selected, show both
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
        // For other tabs or when no tab is selected, show both
        $stmt = $pdo->query("SELECT COUNT(*) FROM absenteeism WHERE ir_form NOT REGEXP '^(YES|NO NEED)'");
        $stats['pending_ir'] += $stmt->fetchColumn();

        $stmt = $pdo->query("SELECT COUNT(*) FROM tardiness WHERE ir_form NOT REGEXP '^(YES|FOR ACCUMULATION|NO NEED|EXPIRED)'");
        $stats['pending_ir'] += $stmt->fetchColumn();
    }

    // Pending Coverage
    $stmt = $pdo->query("SELECT COUNT(*) FROM absenteeism WHERE coverage = 'PENDING'"); 
    $stats['pending_coverage'] += $stmt->fetchColumn();



    $todayDate = date('Y-m-d');
    // Pending Uncovered Shift
    $todayDate = date('Y-m-d');
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM absenteeism WHERE coverage = 'UNCOVERED' AND date_of_absent = ?");
    $stmt->execute([$todayDate]);
    $stats['uncovered_shift'] += $stmt->fetchColumn();
    
} catch (PDOException $e) {
    // If there's an error, we'll just use the default 0 values
}

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

<div class=" pt-2 min-h-screen">
    <main class="p-6">
        <div class="flex justify-between items-center mb-6">
            <h1 class="text-2xl font-bold">Attendance Tracker</h1>
            <div class="flex items-center gap-2">
                <!-- No Need Email Button (initially hidden) -->
                <button id="noNeedEmailBtn" class="hidden bg-yellow-600 hover:bg-yellow-700 text-white px-4 py-2 rounded-lg flex items-center">
                    <i class="fas fa-envelope mr-2"></i> No Need Email
                </button>
                <a href="<?= $currentTab === 'vto' ? 'vto_form.php' : 'attendance_form.php' ?>?action=create&type=<?= $currentTab ?>" class="bg-primary-600 hover:bg-primary-700 text-white px-4 py-2 rounded-lg flex items-center">
                    <i class="fas fa-plus mr-2"></i> Add New
                </a>
            </div>
        </div>
        
        <!-- Stats Cards -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
            <!-- Pending Emails -->
            <button type="button" name="filter" value="pending_emails" 
                class="filter-button bg-gray-800 rounded-xl border border-gray-700 p-6 shadow hover:border-blue-500 transition-colors duration-200 text-left 
                    <?= ($currentTab === 'vto') ? 'opacity-50 cursor-not-allowed' : '' ?>" 
                <?= ($currentTab === 'vto') ? 'disabled' : '' ?>>
                <div class="flex items-center justify-between">
                    <div>
                        <h3 class="text-gray-400 text-sm font-medium">Pending Emails</h3>
                        <p class="text-3xl font-bold mt-2 text-gray-100"><?= $stats['pending_emails'] ?></p>
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
                        <p class="text-3xl font-bold mt-2 text-gray-100"><?= $stats['pending_ir'] ?></p>
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
                        <p class="text-3xl font-bold mt-2 text-gray-100"><?= $stats['pending_coverage'] ?></p>
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
                        <p class="text-3xl font-bold mt-2 text-gray-100"><?= $stats['uncovered_shift'] ?></p>
                        <p class="text-xs text-gray-400 mt-1">Today's uncovered shift</p>
                    </div>
                    <div class="bg-red-500/20 p-3 rounded-full">
                        <i class="fas fa-book text-red-500 text-xl"></i>
                    </div>
                </div>
            </button>
        </div>
        <!-- Wrap your stats cards in a form that preserves all filters -->
        <!-- Replace the existing stats cards form with this -->
        <form method="get" action="attendance.php" id="mainFilterForm">
            <input type="hidden" name="tab" value="<?= $currentTab ?>">
            <!-- Preserve existing filters -->
            <?php if (!empty($_GET['search'])): ?>
                <input type="hidden" name="search" value="<?= htmlspecialchars($_GET['search']) ?>">
            <?php endif; ?>
            <?php if (!empty($_GET['from'])): ?>
                <input type="hidden" name="from" value="<?= htmlspecialchars($_GET['from']) ?>">
            <?php endif; ?>
            <?php if (!empty($_GET['to'])): ?>
                <input type="hidden" name="to" value="<?= htmlspecialchars($_GET['to']) ?>">
            <?php endif; ?>
            <?php if (!empty($_GET['dept'])): ?>
                <input type="hidden" name="dept" value="<?= htmlspecialchars($_GET['dept']) ?>">
            <?php endif; ?>
            <?php if (!empty($_GET['cov'])): ?>
                <input type="hidden" name="cov" value="<?= htmlspecialchars($_GET['cov']) ?>">
            <?php endif; ?>
            <?php if (!empty($_GET['ir'])): ?>
                <input type="hidden" name="ir" value="<?= htmlspecialchars($_GET['ir']) ?>">
            <?php endif; ?>
        </form>

        <!-- Tabs -->
        <div class="border-b border-gray-700 mb-6">
            <nav class="-mb-px flex space-x-8">
                <a href="?tab=absenteeism" class="<?= $currentTab === 'absenteeism' ? 'border-primary-500 text-primary-400' : 'border-transparent text-gray-400 hover:text-gray-300' ?> whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm">
                    Absenteeism
                </a>
                <a href="?tab=tardiness" class="<?= $currentTab === 'tardiness' ? 'border-primary-500 text-primary-400' : 'border-transparent text-gray-400 hover:text-gray-300' ?> whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm">
                    Tardiness
                </a>
                <a href="?tab=vto" class="<?= $currentTab === 'vto' ? 'border-primary-500 text-primary-400' : 'border-transparent text-gray-400 hover:text-gray-300' ?> whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm">
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

        <div id="attendanceTableContainer">
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

// Simple checkbox functionality that works with dynamic content
document.addEventListener('DOMContentLoaded', function() {
    // Use event delegation for checkboxes since they're loaded dynamically
    document.addEventListener('change', function(e) {
        // Handle select all checkbox
        if (e.target.id === 'selectAllCheckbox') {
            const isChecked = e.target.checked;
            document.querySelectorAll('.record-checkbox').forEach(checkbox => {
                checkbox.checked = isChecked;
            });
            toggleNoNeedEmailButton();
        }
        
        // Handle individual record checkboxes
        if (e.target.classList.contains('record-checkbox')) {
            toggleNoNeedEmailButton();
        }
    });
    
    // No Need Email button functionality
    const noNeedEmailBtn = document.getElementById('noNeedEmailBtn');
    if (noNeedEmailBtn) {
        noNeedEmailBtn.addEventListener('click', function() {
            const selectedIds = Array.from(document.querySelectorAll('.record-checkbox'))
                .filter(checkbox => checkbox.checked)
                .map(checkbox => checkbox.getAttribute('data-id'));
                
            if (selectedIds.length === 0) {
                alert('Please select at least one record.');
                return;
            }
            
            if (!confirm(`Are you sure you want to mark ${selectedIds.length} record(s) as "No Need Email"?`)) {
                return;
            }
            
            // Send AJAX request to update records
            const formData = new FormData();
            formData.append('action', 'no_need_email');
            formData.append('record_ids', JSON.stringify(selectedIds));
            formData.append('type', '<?= $currentTab ?>');
            
            fetch('../includes/update_attendance.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert(`Successfully updated ${data.updated} record(s).`);
                    location.reload(); // Reload to reflect changes
                } else {
                    alert('Error: ' + data.message);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('An error occurred while updating records.');
            });
        });
    }
    
    // Modify the toggleNoNeedEmailButton function
    function toggleNoNeedEmailButton() {
        // Don't show the button for VTO tab
        if ('<?= $currentTab ?>' === 'vto') return;
        
        const anyChecked = Array.from(document.querySelectorAll('.record-checkbox')).some(checkbox => checkbox.checked);
        const noNeedEmailBtn = document.getElementById('noNeedEmailBtn');
        
        if (anyChecked && noNeedEmailBtn) {
            noNeedEmailBtn.classList.remove('hidden');
        } else if (noNeedEmailBtn) {
            noNeedEmailBtn.classList.add('hidden');
        }
    }

    // Also hide the "No Need Email" button when in VTO tab
    if ('<?= $currentTab ?>' === 'vto') {
        const noNeedEmailBtn = document.getElementById('noNeedEmailBtn');
        if (noNeedEmailBtn) {
            noNeedEmailBtn.classList.add('hidden');
        }
    }
    
    // Initialize filter functionality
    const searchInput = document.getElementById('searchInput');
    const dateFrom = document.getElementById('dateFrom');
    const dateTo = document.getElementById('dateTo');
    const departmentFilter = document.getElementById('departmentFilter');
    const coverageFilter = document.getElementById('coverageFilter');
    const irFilter = document.getElementById('irFilter');
    let searchTimeout;
    let currentFilter = '';

    // Function to load data with current filters
    function loadFilteredData(page = 1) {
        const urlParams = new URLSearchParams(window.location.search);
        const formData = new FormData();
        
        // Always include search
        formData.append('search', searchInput.value);
        
        // Apply current filter type
        if (currentFilter) {
            formData.append('filter', currentFilter);
            if (currentFilter !== 'pending_coverage') {
                coverageFilter.value = '';
            }
        } else {
            formData.append('department', departmentFilter.value);
            formData.append('coverage', coverageFilter.value);
            formData.append('ir_filter', irFilter.value);
        }

        // Add other filters
        formData.append('date_from', dateFrom.value);
        formData.append('date_to', dateTo.value);
        formData.append('type', urlParams.get('tab') || 'absenteeism');
        formData.append('page', page);
        
        fetch('partials/attendance_table.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.text())
        .then(data => {
            document.getElementById('attendanceTableContainer').innerHTML = data;
            setupPaginationLinks();
        });
    }

    function setupPaginationLinks() {
        document.querySelectorAll('.pagination-link').forEach(link => {
            link.addEventListener('click', function(e) {
                e.preventDefault();
                const page = this.getAttribute('data-page');
                loadFilteredData(page);
                
                // Update URL with new page parameter
                const urlParams = new URLSearchParams(window.location.search);
                urlParams.set('page', page);
                history.pushState(null, '', '?' + urlParams.toString());
            });
        });
    }
            
    // Card filter buttons handler
    function handleCardFilter(filterValue) {
        const urlParams = new URLSearchParams(window.location.search);
        
        // Reset all filter params
        urlParams.delete('dept');
        urlParams.delete('cov');
        urlParams.delete('filter');
        
        // Set the new filter
        currentFilter = filterValue;
        urlParams.set('filter', filterValue);
        
        // Update UI
        document.querySelectorAll('.filter-button').forEach(btn => {
            btn.classList.remove('active');
        });
        event.target.classList.add('active');
        
        history.pushState(null, '', '?' + urlParams.toString());
        loadFilteredData();
    }

    // Dropdown filter handler
    function handleDropdownFilter() {
        const urlParams = new URLSearchParams(window.location.search);
        
        // Clear card filter
        currentFilter = '';
        urlParams.delete('filter');
        
        // Set dropdown filters
        if (departmentFilter.value) {
            urlParams.set('dept', departmentFilter.value);
        } else {
            urlParams.delete('dept');
        }
        
        if (coverageFilter.value) {
            urlParams.set('cov', coverageFilter.value);
        } else {
            urlParams.delete('cov');
        }
        
        // Update UI
        document.querySelectorAll('.filter-button').forEach(btn => {
            btn.classList.remove('active');
        });
        
        history.pushState(null, '', '?' + urlParams.toString());
        loadFilteredData();
    }

    // Initialize event listeners
    document.querySelectorAll('.filter-button').forEach(button => {
        button.addEventListener('click', function() {
            handleCardFilter(this.value);
        });
    });

    departmentFilter.addEventListener('change', handleDropdownFilter);
    coverageFilter.addEventListener('change', handleDropdownFilter);
    irFilter.addEventListener('change', handleDropdownFilter);
    searchInput.addEventListener('input', () => {
        clearTimeout(searchTimeout);
        searchTimeout = setTimeout(() => {
            const urlParams = new URLSearchParams(window.location.search);
            
            // Preserve current filters
            if (currentFilter) {
                urlParams.set('filter', currentFilter);
            } else {
                // Preserve dropdown filters
                if (departmentFilter.value) urlParams.set('dept', departmentFilter.value);
                if (coverageFilter.value) urlParams.set('cov', coverageFilter.value);
            }
            
            // Update search parameter
            if (searchInput.value) {
                urlParams.set('search', searchInput.value);
            } else {
                urlParams.delete('search');
            }
            
            history.pushState(null, '', '?' + urlParams.toString());
            loadFilteredData();
        }, 300);
    });
    dateFrom.addEventListener('change', handleDropdownFilter);
    dateTo.addEventListener('change', handleDropdownFilter);

    // Initial load
    const urlParams = new URLSearchParams(window.location.search);
    if (urlParams.has('filter')) {
        currentFilter = urlParams.get('filter');
        document.querySelector(`.filter-button[value="${currentFilter}"]`)?.classList.add('active');
    }
    const initialPage = urlParams.get('page') || 1;
    loadFilteredData(initialPage);
});
// // // // // 


</script>

<script>
// // // // // 
// Delete confirmation modal
// // // // // 
function showDeleteModal(recordId, recordType = '') {
    const modal = `
        <div id="deleteModal" class="fixed inset-0 bg-gray-900 bg-opacity-50 flex items-center justify-center z-50">
            <div class="bg-gray-800 rounded-lg border border-gray-700 shadow-xl w-full max-w-md">
                <div class="px-6 py-6">
                    <h3 class="text-lg font-bold text-gray-100 mb-4">Confirm Deletion</h3>
                    <p class="text-gray-300 mb-4">Are you sure you want to delete this record?</p>
                    <form method="post" action="${window.location.pathname}?delete=${recordId}${recordType ? '&type=' + recordType : ''}" class="space-y-4">
                        <div>
                            <label for="delete_password" class="block text-sm font-medium text-gray-300 mb-1">To confirm please enter the KEY:</label>
                            <input type="password" name="delete_password" id="delete_password" 
                                   class="w-full px-3 py-2 bg-gray-700 border border-gray-600 rounded-md text-gray-200" required>
                        </div>
                        <div class="flex justify-end space-x-3">
                            <button type="button" onclick="closeDeleteModal()" 
                                    class="px-4 py-2 bg-gray-600 text-gray-100 rounded-md hover:bg-gray-500">
                                Cancel
                            </button>
                            <button type="submit" 
                                    class="px-4 py-2 bg-red-600 text-white rounded-md hover:bg-red-500">
                                Delete
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    `;
    
    document.body.insertAdjacentHTML('beforeend', modal);
}

function closeDeleteModal() {
    const modal = document.getElementById('deleteModal');
    if (modal) {
        modal.remove();
    }
}
// // // // // 

// // // // // 
// History modal function JS 
// // // // // 
function showHistoryModal(recordId, recordType) {
    const modal = document.getElementById('historyModal');
    const loader = `
        <div class="relative">
            <div class="absolute -left-2.5 top-0 h-5 w-5 rounded-full bg-gray-500 border-4 border-gray-800"></div>
            <div class="ml-4">
                <p class="text-sm text-gray-400">Loading history...</p>
            </div>
        </div>
    `;
    document.getElementById('historyTableBody').innerHTML = loader;
    modal.classList.remove('hidden');
    
    fetch(`get_history.php?record_id=${recordId}&type=${recordType}`)
        .then(response => {
            if (!response.ok) throw new Error('Network response was not ok');
            return response.json();
        })
        .then(data => {
            const tbody = document.getElementById('historyTableBody');
            tbody.innerHTML = '';
            
            if (data.length > 0) {
                data.forEach(activity => {
                    const initials = activity.sub_name.split(' ').map(n => n[0]).join('').toUpperCase();
                    const item = `
                        <div class="bg-gray-800 rounded-lg p-4 shadow-md border border-gray-700 mb-4 ">
                            <!-- User & Activity Info -->
                            <div class="flex items-start">
                                <div class="flex-shrink-0 h-10 w-10 rounded-full bg-blue-600 flex items-center justify-center text-white font-bold">
                                    ${initials.substring(0, 2)}
                                </div>
                                <div class="ml-4">
                                    <h4 class="text-base font-semibold text-gray-100">${activity.sub_name}</h4>
                                    <p class="text-sm text-gray-300 mt-1">${activity.activity_description}</p>
                                </div>
                            </div>
                            
                            <!-- Timeline Indicator & Timestamp -->
                            <div class="relative ml-10 mt-3">
                                <div class="absolute -left-2.5 top-0 h-5 w-5 rounded-full bg-blue-600 border-4 border-gray-800"></div>
                                <div class="ml-4">
                                    <p class="text-xs text-gray-400">
                                        ${new Date(activity.activity_time).toLocaleString('en-US', { 
                                            month: 'short', 
                                            day: 'numeric', 
                                            year: 'numeric',
                                            hour: '2-digit',
                                            minute: '2-digit'
                                        })}
                                    </p>
                                </div>
                            </div>
                        </div>
                    `;
                    tbody.insertAdjacentHTML('beforeend', item);
                });
            } else {
                tbody.innerHTML = `
                    <div class="relative">
                        <div class="absolute -left-2.5 top-0 h-5 w-5 rounded-full bg-gray-500 border-4 border-gray-800"></div>
                        <div class="ml-4">
                            <p class="text-sm text-gray-400">No history found for this record</p>
                        </div>
                    </div>
                `;
            }
        })
        .catch(error => {
            console.error('Error:', error);
            const tbody = document.getElementById('historyTableBody');
            tbody.innerHTML = `
                <div class="relative">
                    <div class="absolute -left-2.5 top-0 h-5 w-5 rounded-full bg-red-600 border-4 border-gray-800"></div>
                    <div class="ml-4">
                        <p class="text-sm text-red-400">Error loading history: ${error.message}</p>
                    </div>
                </div>
            `;
        });
}

function closeHistoryModal() {
    document.getElementById('historyModal').classList.add('hidden');
}
// // // // // 



// // // // // 
// Close modal when clicking outside or pressing Escape
// // // // // 
document.addEventListener('click', function(e) {
    if (e.target === document.getElementById('historyModal')) {
        closeHistoryModal();
    }
});

document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape' && !document.getElementById('historyModal').classList.contains('hidden')) {
        closeHistoryModal();
    }
});

// // // / // 
</script>
<?php renderFooter(); ?>