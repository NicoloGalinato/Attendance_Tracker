<?php
require_once '../includes/config.php';
require_once '../includes/functions.php';
require_once '../components/layout.php';

// It's good practice to ensure the user is logged in and is an admin
if (!isLoggedIn()) {
    redirect(BASE_URL);
}

updateLastActivity();

// Assume connection.php is in the same directory as this file
include '../connection.php';

// Define pagination parameters
$ticketsPerPage = 10;
$currentPage = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($currentPage - 1) * $ticketsPerPage;

// Function to get a list of months with ticket data
function getMonthsWithData($con)
{
    $query = "SELECT DISTINCT DATE_FORMAT(STR_TO_DATE(Timestamp, '%m/%d/%Y %H:%i:%s'), '%Y-%m') AS month FROM ticket ORDER BY month DESC";
    $result = mysqli_query($con, $query);
    return mysqli_fetch_all($result, MYSQLI_ASSOC);
}

// Function to get all tickets with pagination
function getAllTickets($con, $limit, $offset)
{
    $query = "SELECT * FROM ticket ORDER BY STR_TO_DATE(Timestamp, '%m/%d/%Y %H:%i:%s') DESC LIMIT ? OFFSET ?";
    $stmt = mysqli_prepare($con, $query);
    mysqli_stmt_bind_param($stmt, "ii", $limit, $offset);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    return mysqli_fetch_all($result, MYSQLI_ASSOC);
}

// Function to get total ticket count
function getTotalTicketsCount($con) {
    $query = "SELECT COUNT(*) as total FROM ticket";
    $result = mysqli_query($con, $query);
    $row = mysqli_fetch_assoc($result);
    return $row['total'];
}

// Function to get resolved tickets count
function getResolvedTicketsCount($con) {
    $query = "SELECT COUNT(*) as total FROM ticket WHERE Status = 'RESOLVED'";
    $result = mysqli_query($con, $query);
    $row = mysqli_fetch_assoc($result);
    return $row['total'];
}

// Function to get pending tickets count
function getPendingTicketsCount($con) {
    $query = "SELECT COUNT(*) as total FROM ticket WHERE Status = 'PENDING'";
    $result = mysqli_query($con, $query);
    $row = mysqli_fetch_assoc($result);
    return $row['total'];
}

// Function to get top ticket submitters
function getTopSubmitters($con, $selectedMonth = null)
{
    if (!$selectedMonth) {
        $selectedMonth = date('Y-m');
    }
    $startDate = $selectedMonth . '-01';
    $endDate = date('Y-m-01', strtotime($startDate . ' +1 month'));
    $query = "
        SELECT Employee_name, COUNT(*) as ticket_count 
        FROM ticket 
        WHERE STR_TO_DATE(Timestamp, '%m/%d/%Y %H:%i:%s') >= '$startDate'
        AND STR_TO_DATE(Timestamp, '%m/%d/%Y %H:%i:%s') < '$endDate'
        GROUP BY Employee_name 
        ORDER BY ticket_count DESC 
        LIMIT 5
    ";
    $result = mysqli_query($con, $query);
    return mysqli_fetch_all($result, MYSQLI_ASSOC);
}

// Function to get top OMs
function getTopOMs($con, $selectedMonth = null)
{
    if (!$selectedMonth) {
        $selectedMonth = date('Y-m');
    }
    $startDate = $selectedMonth . '-01';
    $endDate = date('Y-m-01', strtotime($startDate . ' +1 month'));
    $query = "
        SELECT OM, COUNT(*) as om_count 
        FROM ticket 
        WHERE STR_TO_DATE(Timestamp, '%m/%d/%Y %H:%i:%s') >= '$startDate'
        AND STR_TO_DATE(Timestamp, '%m/%d/%Y %H:%i:%s') < '$endDate'
        GROUP BY OM 
        ORDER BY om_count DESC 
        LIMIT 5
    ";
    $result = mysqli_query($con, $query);
    return mysqli_fetch_all($result, MYSQLI_ASSOC);
}

// Modified function to get the overall tickets per OM per month
function getOverallOMsByMonth($con)
{
    $query = "SELECT DATE_FORMAT(STR_TO_DATE(Timestamp, '%m/%d/%Y %H:%i:%s'), '%Y-%m') as month,
              OM,
              COUNT(*) as om_count
              FROM ticket
              GROUP BY month, OM
              ORDER BY month ASC, OM ASC";
    $result = mysqli_query($con, $query);
    $raw_data = mysqli_fetch_all($result, MYSQLI_ASSOC);
    $datasets = [];
    $labels = [];
    $oms = [];
    foreach ($raw_data as $row) {
        if (!in_array($row['OM'], $oms)) {
            $oms[] = $row['OM'];
        }
        if (!in_array($row['month'], $labels)) {
            $labels[] = $row['month'];
        }
    }
    sort($labels);
    foreach ($oms as $om) {
        $data = [];
        foreach ($labels as $month) {
            $count = 0;
            foreach ($raw_data as $row) {
                if ($row['OM'] === $om && $row['month'] === $month) {
                    $count = $row['om_count'];
                    break;
                }
            }
            $data[] = $count;
        }
        $datasets[] = [
            'label' => $om,
            'data' => $data,
        ];
    }
    return ['labels' => $labels, 'datasets' => $datasets];
}

// Function to get weekly ticket counts
function getWeeklyTickets($con)
{
    $query = "SELECT Week_Beginning, COUNT(*) as ticket_count 
              FROM ticket 
              GROUP BY Week_Beginning 
              ORDER BY Week_Beginning DESC 
              LIMIT 8";
    $result = mysqli_query($con, $query);
    return mysqli_fetch_all($result, MYSQLI_ASSOC);
}

// Function to get monthly ticket counts
function getMonthlyTickets($con)
{
    $query = "SELECT DATE_FORMAT(STR_TO_DATE(Timestamp, '%m/%d/%Y %H:%i:%s'), '%Y-%m') as month, 
              COUNT(*) as ticket_count 
              FROM ticket 
              GROUP BY month 
              ORDER BY month DESC";
    $result = mysqli_query($con, $query);
    return mysqli_fetch_all($result, MYSQLI_ASSOC);
}

// Function to get yearly ticket counts
function getYearlyTickets($con)
{
    $query = "SELECT YEAR(STR_TO_DATE(Timestamp, '%m/%d/%Y %H:%i:%s')) as year, 
              COUNT(*) as ticket_count 
              FROM ticket 
              GROUP BY year 
              ORDER BY year DESC";
    $result = mysqli_query($con, $query);
    return mysqli_fetch_all($result, MYSQLI_ASSOC);
}

// Function to get issue breakdown
function getIssueBreakdown($con)
{
    $query = "SELECT Issues_Concerning, COUNT(*) as issue_count 
              FROM ticket 
              GROUP BY Issues_Concerning 
              ORDER BY issue_count DESC";
    $result = mysqli_query($con, $query);
    return mysqli_fetch_all($result, MYSQLI_ASSOC);
}

// Function to get OM breakdown
function getOMBreakdown($con)
{
    $query = "SELECT OM, COUNT(*) as om_count 
              FROM ticket 
              GROUP BY OM 
              ORDER BY om_count DESC";
    $result = mysqli_query($con, $query);
    return mysqli_fetch_all($result, MYSQLI_ASSOC);
}

// Functions for filtered tickets with pagination
function getFilteredTickets($con, $filterType, $filterValue, $limit, $offset) {
    $whereClause = "1=1";
    $bindParams = "";
    $bindValues = [];
    if ($filterType === 'employee') {
        $whereClause = "Employee_name = ?";
        $bindParams = "s";
        $bindValues = [$filterValue];
    } elseif ($filterType === 'om') {
        $whereClause = "OM = ?";
        $bindParams = "s";
        $bindValues = [$filterValue];
    } elseif ($filterType === 'issue') {
        $whereClause = "Issues_Concerning = ?";
        $bindParams = "s";
        $bindValues = [$filterValue];
    } elseif ($filterType === 'status') {
        if ($filterValue === 'resolved') {
            $whereClause = "Status = 'RESOLVED'";
        } elseif ($filterValue === 'pending') {
            $whereClause = "Status = 'PENDING'";
        }
    }
    $query = "SELECT * FROM ticket WHERE " . $whereClause . " ORDER BY STR_TO_DATE(Timestamp, '%m/%d/%Y %H:%i:%s') DESC LIMIT ? OFFSET ?";
    $stmt = mysqli_prepare($con, $query);
    if ($filterType === 'status' && ($filterValue === 'resolved' || $filterValue === 'pending')) {
         mysqli_stmt_bind_param($stmt, "ii", $limit, $offset);
    } else {
        $bindParams .= "ii";
        $bindValues[] = $limit;
        $bindValues[] = $offset;
        mysqli_stmt_bind_param($stmt, $bindParams, ...$bindValues);
    }
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    return mysqli_fetch_all($result, MYSQLI_ASSOC);
}

function getFilteredTicketsCount($con, $filterType, $filterValue) {
    $whereClause = "1=1";
    $bindParams = "";
    $bindValues = [];
    if ($filterType === 'employee') {
        $whereClause = "Employee_name = ?";
        $bindParams = "s";
        $bindValues = [$filterValue];
    } elseif ($filterType === 'om') {
        $whereClause = "OM = ?";
        $bindParams = "s";
        $bindValues = [$filterValue];
    } elseif ($filterType === 'issue') {
        $whereClause = "Issues_Concerning = ?";
        $bindParams = "s";
        $bindValues = [$filterValue];
    } elseif ($filterType === 'status') {
        if ($filterValue === 'resolved') {
            $whereClause = "Status = 'RESOLVED'";
        } elseif ($filterValue === 'pending') {
            $whereClause = "Status = 'PENDING'";
        }
    }
    $query = "SELECT COUNT(*) as total FROM ticket WHERE " . $whereClause;
    if ($filterType === 'status' && ($filterValue === 'resolved' || $filterValue === 'pending')) {
        $result = mysqli_query($con, $query);
    } else {
        $stmt = mysqli_prepare($con, $query);
        mysqli_stmt_bind_param($stmt, $bindParams, ...$bindValues);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
    }
    $row = mysqli_fetch_assoc($result);
    return $row['total'];
}


// Handle AJAX requests
if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
    $response = [];
    if (isset($_GET['data_type'])) {
        $data_type = $_GET['data_type'];
        $month = $_GET['month'] ?? date('Y-m');
        if ($data_type === 'employee') {
            $response['topSubmitters'] = getTopSubmitters($con, $month);
        } elseif ($data_type === 'om') {
            $response['topOMs'] = getTopOMs($con, $month);
            $response['overallOMsByMonth'] = getOverallOMsByMonth($con);
        } elseif ($data_type === 'issue') {
            $response['issueBreakdown'] = getIssueBreakdown($con);
            $response['monthlyTickets'] = getMonthlyTickets($con);
        }
    } elseif (isset($_GET['action'])) {
        $action = $_GET['action'];
        $page = (int)($_GET['page'] ?? 1);
        $offset = ($page - 1) * $ticketsPerPage;
        $filterType = $_GET['filter_type'] ?? null;
        $filterValue = $_GET['filter_value'] ?? null;
        if ($action === 'getTickets') {
            if ($filterType && $filterValue) {
                $tickets = getFilteredTickets($con, $filterType, $filterValue, $ticketsPerPage, $offset);
                $totalTickets = getFilteredTicketsCount($con, $filterType, $filterValue);
            } else {
                $tickets = getAllTickets($con, $ticketsPerPage, $offset);
                $totalTickets = getTotalTicketsCount($con);
            }
            $response['tickets'] = $tickets;
            $response['totalPages'] = ceil($totalTickets / $ticketsPerPage);
            $response['currentPage'] = $page;
            $response['totalTicketsCount'] = $totalTickets;
        } elseif ($action === 'getTicketsByStatus') {
            $status = $_GET['status'] ?? 'total';
            $tickets = [];
            $totalTickets = 0;
            if ($status === 'resolved') {
                $tickets = getFilteredTickets($con, 'status', 'resolved', $ticketsPerPage, $offset);
                $totalTickets = getResolvedTicketsCount($con);
            } elseif ($status === 'pending') {
                $tickets = getFilteredTickets($con, 'status', 'pending', $ticketsPerPage, $offset);
                $totalTickets = getPendingTicketsCount($con);
            } else {
                $tickets = getAllTickets($con, $ticketsPerPage, $offset);
                $totalTickets = getTotalTicketsCount($con);
            }
            $response['tickets'] = $tickets;
            $response['totalPages'] = ceil($totalTickets / $ticketsPerPage);
            $response['currentPage'] = $page;
            $response['totalTicketsCount'] = $totalTickets;
        }
    }
    header('Content-Type: application/json');
    echo json_encode($response);
    exit;
}

// Initial page load data
$month = isset($_GET['month']) ? $_GET['month'] : null;
$topSubmitters = getTopSubmitters($con, $month);
$topOMs = getTopOMs($con, $month);
$totalTicketsCount = getTotalTicketsCount($con);
$resolvedTicketsCount = getResolvedTicketsCount($con);
$pendingTicketsCount = getPendingTicketsCount($con);
$tickets = getAllTickets($con, $ticketsPerPage, $offset);
$weeklyTickets = getWeeklyTickets($con);
$monthlyTickets = getMonthlyTickets($con);
$yearlyTickets = getYearlyTickets($con);
$issueBreakdown = getIssueBreakdown($con);
$omBreakdown = getOMBreakdown($con);
$overallOMsByMonth = getOverallOMsByMonth($con);
$monthsWithData = getMonthsWithData($con);

renderHead('Statistics');
renderNavbar();
renderSidebar('statistics');
?>

<div class="pt-2 min-h-screen text-white">
    <main class="p-6">
        <div class="flex justify-between items-center mb-6">
            <h1 class="text-2xl font-bold">Dashboard</h1>
            <div class="text-sm text-gray-400 text-right">
                <div><?= date('F j, Y') ?></div>
                <div id="realtime-clock" class="text-s text-gray-500"></div>
            </div>
        </div>  

        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
            <div class="bg-gray-800 rounded-xl border border-gray-700 p-6 shadow ticket-count-card" data-status-filter="total">
                <div class="flex items-center justify-between">
                    <div>
                        <h3 class="text-gray-400 text-sm font-medium">Total Tickets</h3>
                        <p class="text-3xl font-bold mt-2" id="total-tickets-count"><?= $totalTicketsCount ?></p>
                    </div>
                    <div class="bg-primary-500/20 p-3 rounded-full">
                        <i class="fas fa-ticket-alt text-primary-500 text-xl"></i>
                    </div>
                </div>
            </div>

            <div class="bg-gray-800 rounded-xl border border-gray-700 p-6 shadow ticket-count-card" data-status-filter="resolved">
                <div class="flex items-center justify-between">
                    <div>
                        <h3 class="text-gray-400 text-sm font-medium">Resolved Tickets</h3>
                        <p class="text-3xl font-bold mt-2" id="resolved-tickets-count"><?= $resolvedTicketsCount ?></p>
                    </div>
                    <div class="bg-green-500/20 p-3 rounded-full">
                        <i class="fas fa-check-circle text-green-500 text-xl"></i>
                    </div>
                </div>
            </div>

            <div class="bg-gray-800 rounded-xl border border-gray-700 p-6 shadow ticket-count-card" data-status-filter="pending">
                <div class="flex items-center justify-between">
                    <div>
                        <h3 class="text-gray-400 text-sm font-medium">Pending Tickets</h3>
                        <p class="text-3xl font-bold mt-2" id="pending-tickets-count"><?= $pendingTicketsCount ?></p>
                    </div>
                    <div class="bg-yellow-500/20 p-3 rounded-full">
                        <i class="fas fa-hourglass-half text-yellow-500 text-xl"></i>
                    </div>
                </div>
            </div>
        </div>

        <div class="flex border-b border-gray-700 mb-6">
            <button class="tab-button py-2 px-4 font-medium text-gray-400 hover:text-white transition-colors active" data-tab="ticket-reports">Ticket Reports</button>
            <button class="tab-button py-2 px-4 font-medium text-gray-400 hover:text-white transition-colors" data-tab="employee-reports">Employee Reports</button>
            <button class="tab-button py-2 px-4 font-medium text-gray-400 hover:text-white transition-colors" data-tab="om-reports">OM Reports</button>
            <button class="tab-button py-2 px-4 font-medium text-gray-400 hover:text-white transition-colors" data-tab="issue-breakdown">Issue Breakdown</button>
        </div>

        <div id="ticket-reports" class="tab-content active">
            <div class="bg-gray-800 rounded-xl border border-gray-700 p-6 shadow">
                <h2 class="text-xl font-semibold mb-4">Overall Ticket Trends</h2>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-6">
                    <div class="bg-gray-700 p-4 rounded-lg shadow">
                        <h3 class="text-lg font-medium text-gray-300 mb-3">Weekly Tickets</h3>
                        <div class="h-64">
                            <canvas id="weeklyTicketsChart"></canvas>
                        </div>
                    </div>
                    <div class="bg-gray-700 p-4 rounded-lg shadow">
                        <h3 class="text-lg font-medium text-gray-300 mb-3">Monthly Tickets</h3>
                        <div class="h-64">
                            <canvas id="monthlyTicketsChart"></canvas>
                        </div>
                    </div>
                    <div class="bg-gray-700 p-4 rounded-lg shadow">
                        <h3 class="text-lg font-medium text-gray-300 mb-3">Yearly Tickets</h3>
                        <div class="h-64">
                            <canvas id="yearlyTicketsChart"></canvas>
                        </div>
                    </div>
                </div>

                <div class="mt-6">
                    <h3 class="text-lg font-semibold text-gray-200 mb-3">
                        <span id="ticket-table-title">All Tickets</span>
                        <button id="show-all-tickets-btn" class="ml-4 px-3 py-1 text-sm text-white bg-blue-600 rounded hover:bg-blue-700 hidden">Show All Tickets</button>
                    </h3>
                    <div class="overflow-x-auto bg-gray-700 rounded-lg">
                        <table class="min-w-full text-gray-200">
                            <thead>
                                <tr class="bg-gray-600 text-gray-300 uppercase text-sm leading-normal" id="filtered-tickets-table-head">
                                    <th class="py-3 px-6 text-left">ID</th>
                                    <th class="py-3 px-6 text-left">Employee</th>
                                    <th class="py-3 px-6 text-left">Department</th>
                                    <th class="py-3 px-6 text-left">Issue</th>
                                    <th class="py-3 px-6 text-left">Status</th>
                                    <th class="py-3 px-6 text-left">Urgency</th>
                                    <th class="py-3 px-6 text-left">Date</th>
                                </tr>
                            </thead>
                            <tbody class="text-gray-200 text-sm font-light" id="filtered-tickets-table-body">
                                <?php foreach($tickets as $ticket): ?>
                                <tr class="border-b border-gray-600 hover:bg-gray-600">
                                    <td class="py-3 px-6 text-left whitespace-nowrap"><?= htmlspecialchars($ticket['id']) ?></td>
                                    <td class="py-3 px-6 text-left"><?= htmlspecialchars($ticket['Employee_name']) ?></td>
                                    <td class="py-3 px-6 text-left"><?= htmlspecialchars($ticket['LOB']) ?></td>
                                    <td class="py-3 px-6 text-left"><?= htmlspecialchars($ticket['Issues_Concerning']) ?></td>
                                    <td class="py-3 px-6 text-left">
                                        <span class="<?= $ticket['Status'] == 'RESOLVED' ? 'bg-green-700' : ($ticket['Status'] == 'PENDING' ? 'bg-yellow-700' : 'bg-red-700') ?> text-white py-1 px-3 rounded-full text-xs">
                                            <?= htmlspecialchars($ticket['Status']) ?>
                                        </span>
                                    </td>
                                    <td class="py-3 px-6 text-left">
                                        <span class="<?= $ticket['Urgency'] == 'High' ? 'bg-red-700' : ($ticket['Urgency'] == 'Medium' ? 'bg-yellow-700' : 'bg-blue-700') ?> text-white py-1 px-3 rounded-full text-xs">
                                            <?= htmlspecialchars($ticket['Urgency'] ?: 'Not specified') ?>
                                        </span>
                                    </td>
                                    <td class="py-3 px-6 text-left"><?= htmlspecialchars($ticket['Timestamp']) ?></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    <div id="filtered-pagination-controls" class="flex justify-center mt-4 space-x-2"></div>
                </div>
            </div>
        </div>

        <div id="employee-reports" class="tab-content hidden">
            <div class="bg-gray-800 rounded-xl border border-gray-700 p-6 shadow">
                <h2 class="text-xl font-semibold text-gray-200 mb-4">Employee Ticket Statistics</h2>
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div class="bg-gray-700 p-4 rounded-lg shadow">
                        <div class="flex justify-between items-center mb-3">
                            <h3 class="text-lg font-medium text-gray-300">Top Ticket Submitters</h3>
                            <div>
                                <select name="month" id="employee-month-select" class="bg-gray-600 border border-gray-500 text-gray-200 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 p-1">
                                    <?php
                                    foreach ($monthsWithData as $month) {
                                        $value = $month['month'];
                                        $label = date('F Y', strtotime($month['month'] . '-01'));
                                        $selected = ($value == ($month ?? date('Y-m'))) ? 'selected' : '';
                                        echo "<option value=\"$value\" $selected>$label</option>";
                                    }
                                    ?>
                                </select>
                            </div>
                        </div>
                        <div class="h-64">
                            <canvas id="topSubmittersChart"></canvas>
                        </div>
                        <p id="employee-no-data" class="text-center text-gray-400 mt-4 hidden">No data available for the selected month.</p>
                    </div>
                    
                    <div class="bg-gray-700 p-4 rounded-lg shadow">
                        <h3 class="text-lg font-medium text-gray-300 mb-3">Recent Tickets by Employee</h3>
                        <div class="overflow-x-auto">
                            <table class="min-w-full text-gray-200">
                                <thead>
                                    <tr class="bg-gray-600 text-gray-300 uppercase text-sm leading-normal">
                                        <th class="py-3 px-6 text-left">Employee</th>
                                        <th class="py-3 px-6 text-left">Issue</th>
                                        <th class="py-3 px-6 text-left">Status</th>
                                        <th class="py-3 px-6 text-left">Date</th>
                                    </tr>
                                </thead>
                                <tbody class="text-gray-200 text-sm font-light" id="employee-recent-tickets-tbody">
                                    <?php foreach(array_slice($tickets, 0, 5) as $ticket): ?>
                                    <tr class="border-b border-gray-600 hover:bg-gray-600 cursor-pointer" data-filter-type="employee" data-filter-value="<?= htmlspecialchars($ticket['Employee_name']) ?>">
                                        <td class="py-3 px-6 text-left whitespace-nowrap"><?= htmlspecialchars($ticket['Employee_name']) ?></td>
                                        <td class="py-3 px-6 text-left"><?= htmlspecialchars($ticket['Issues_Concerning']) ?></td>
                                        <td class="py-3 px-6 text-left">
                                            <span class="<?= $ticket['Status'] == 'RESOLVED' ? 'bg-green-700' : 'bg-yellow-700' ?> text-white py-1 px-3 rounded-full text-xs">
                                                <?= htmlspecialchars($ticket['Status']) ?>
                                            </span>
                                        </td>
                                        <td class="py-3 px-6 text-left"><?= htmlspecialchars($ticket['Timestamp']) ?></td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
                
                <div class="mt-6">
                    <h3 class="text-lg font-semibold text-gray-200 mb-3">
                        All Tickets
                        <button id="show-all-tickets" class="ml-4 px-3 py-1 text-sm text-white bg-blue-600 rounded hover:bg-blue-700 hidden">Show All Tickets</button>
                    </h3>
                    <div class="overflow-x-auto bg-gray-700 rounded-lg">
                        <table class="min-w-full text-gray-200">
                            <thead>
                                <tr class="bg-gray-600 text-gray-300 uppercase text-sm leading-normal" id="all-tickets-table-head">
                                    <th class="py-3 px-6 text-left">ID</th>
                                    <th class="py-3 px-6 text-left">Employee</th>
                                    <th class="py-3 px-6 text-left">Department</th>
                                    <th class="py-3 px-6 text-left">Issue</th>
                                    <th class="py-3 px-6 text-left">Status</th>
                                    <th class="py-3 px-6 text-left">Urgency</th>
                                    <th class="py-3 px-6 text-left">Date</th>
                                </tr>
                            </thead>
                            <tbody class="text-gray-200 text-sm font-light" id="all-tickets-table-body">
                                <?php foreach($tickets as $ticket): ?>
                                <tr class="border-b border-gray-600 hover:bg-gray-600">
                                    <td class="py-3 px-6 text-left whitespace-nowrap"><?= htmlspecialchars($ticket['id']) ?></td>
                                    <td class="py-3 px-6 text-left"><?= htmlspecialchars($ticket['Employee_name']) ?></td>
                                    <td class="py-3 px-6 text-left"><?= htmlspecialchars($ticket['LOB']) ?></td>
                                    <td class="py-3 px-6 text-left"><?= htmlspecialchars($ticket['Issues_Concerning']) ?></td>
                                    <td class="py-3 px-6 text-left">
                                        <span class="<?= $ticket['Status'] == 'RESOLVED' ? 'bg-green-700' : ($ticket['Status'] == 'PENDING' ? 'bg-yellow-700' : 'bg-red-700') ?> text-white py-1 px-3 rounded-full text-xs">
                                            <?= htmlspecialchars($ticket['Status']) ?>
                                        </span>
                                    </td>
                                    <td class="py-3 px-6 text-left">
                                        <span class="<?= $ticket['Urgency'] == 'High' ? 'bg-red-700' : ($ticket['Urgency'] == 'Medium' ? 'bg-yellow-700' : 'bg-blue-700') ?> text-white py-1 px-3 rounded-full text-xs">
                                            <?= htmlspecialchars($ticket['Urgency'] ?: 'Not specified') ?>
                                        </span>
                                    </td>
                                    <td class="py-3 px-6 text-left"><?= htmlspecialchars($ticket['Timestamp']) ?></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    <div id="pagination-controls" class="flex justify-center mt-4 space-x-2"></div>
                </div>
            </div>
        </div>

        <div id="om-reports" class="tab-content hidden">
            <div class="bg-gray-800 rounded-xl border border-gray-700 p-6 shadow">
                <h2 class="text-xl font-semibold text-gray-200 mb-4">OM Reports</h2>
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div class="bg-gray-700 p-4 rounded-lg shadow">
                        <div class="flex justify-between items-center mb-3">
                            <h3 class="text-lg font-medium text-gray-300">Top OMs</h3>
                            <div>
                                <select name="month" id="om-month-select" class="bg-gray-600 border border-gray-500 text-gray-200 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 p-1">
                                    <?php
                                    foreach ($monthsWithData as $month) {
                                        $value = $month['month'];
                                        $label = date('F Y', strtotime($month['month'] . '-01'));
                                        $selected = ($value == ($month ?? date('Y-m'))) ? 'selected' : '';
                                        echo "<option value=\"$value\" $selected>$label</option>";
                                    }
                                    ?>
                                </select>
                            </div>
                        </div>
                        <div class="h-64">
                            <canvas id="topOMsChart"></canvas>
                        </div>
                        <p id="om-no-data" class="text-center text-gray-400 mt-4 hidden">No data available for the selected month.</p>
                    </div>
                    
                    <div class="bg-gray-700 p-4 rounded-lg shadow">
                        <h3 class="text-lg font-medium text-gray-300 mb-3">Recent Tickets by OM</h3>
                        <div class="overflow-x-auto">
                            <table class="min-w-full text-gray-200">
                                <thead>
                                    <tr class="bg-gray-600 text-gray-300 uppercase text-sm leading-normal">
                                        <th class="py-3 px-6 text-left">OM</th>
                                        <th class="py-3 px-6 text-left">Issue</th>
                                        <th class="py-3 px-6 text-left">Employee</th>
                                        <th class="py-3 px-6 text-left">Status</th>
                                    </tr>
                                </thead>
                                <tbody class="text-gray-200 text-sm font-light" id="om-recent-tickets-tbody">
                                    <?php foreach(array_slice($tickets, 0, 5) as $ticket): ?>
                                    <tr class="border-b border-gray-600 hover:bg-gray-600 cursor-pointer" data-filter-type="om" data-filter-value="<?= htmlspecialchars($ticket['OM']) ?>">
                                        <td class="py-3 px-6 text-left whitespace-nowrap"><?= htmlspecialchars($ticket['OM']) ?></td>
                                        <td class="py-3 px-6 text-left"><?= htmlspecialchars($ticket['Issues_Concerning']) ?></td>
                                        <td class="py-3 px-6 text-left"><?= htmlspecialchars($ticket['Employee_name']) ?></td>
                                        <td class="py-3 px-6 text-left">
                                            <span class="<?= $ticket['Status'] == 'RESOLVED' ? 'bg-green-700' : 'bg-yellow-700' ?> text-white py-1 px-3 rounded-full text-xs">
                                                <?= htmlspecialchars($ticket['Status']) ?>
                                            </span>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <div class="bg-gray-700 rounded-lg shadow p-4 mt-6">
                    <h3 class="text-lg font-medium text-gray-300 mb-3">Overall Tickets per OM by Month</h3>
                    <div class="h-64">
                        <canvas id="uniqueOMsChart"></canvas>
                    </div>
                </div>
            </div>
        </div>
        
        <div id="issue-breakdown" class="tab-content hidden">
            <div class="bg-gray-800 rounded-xl border border-gray-700 p-6 shadow">
                <h2 class="text-xl font-semibold text-gray-200 mb-4">Issue Breakdown</h2>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                    <div class="bg-gray-700 p-4 rounded-lg shadow">
                        <h3 class="text-lg font-medium text-gray-300 mb-3">Top 5 Issues</h3>
                        <div class="h-64">
                            <canvas id="topIssuesChart"></canvas>
                        </div>
                    </div>

                    <div class="bg-gray-700 p-4 rounded-lg shadow">
                        <h3 class="text-lg font-medium text-gray-300 mb-3">Monthly Ticket Trend</h3>
                        <div class="h-64">
                            <canvas id="issueMonthlyTrendChart"></canvas>
                        </div>
                    </div>
                </div>

                <div>
                    <h3 class="text-lg font-semibold text-gray-200 mb-3">All Tickets by Issue Type</h3>
                    <div class="overflow-x-auto bg-gray-700 rounded-lg" id="all-issues-table-container">
                        <table class="min-w-full text-gray-200">
                            <thead>
                                <tr class="bg-gray-600 text-gray-300 uppercase text-sm leading-normal" id="issue-table-head">
                                    <th class="py-3 px-6 text-left">Issue</th>
                                    <th class="py-3 px-6 text-left">Tickets</th>
                                    <th class="py-3 px-6 text-left">Action</th>
                                </tr>
                            </thead>
                            <tbody class="text-gray-200 text-sm font-light" id="issue-table-body">
                                <?php foreach($issueBreakdown as $issue): ?>
                                <tr class="border-b border-gray-600 hover:bg-gray-600 cursor-pointer">
                                    <td class="py-3 px-6 text-left whitespace-nowrap"><?= htmlspecialchars($issue['Issues_Concerning']) ?></td>
                                    <td class="py-3 px-6 text-left"><?= htmlspecialchars($issue['issue_count']) ?></td>
                                    <td class="py-3 px-6 text-left">
                                        <button class="px-3 py-1 text-sm text-white bg-blue-600 rounded hover:bg-blue-700 show-issue-tickets" data-issue-name="<?= htmlspecialchars($issue['Issues_Concerning']) ?>">
                                            Show Tickets
                                        </button>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                                
                    <div id="issue-tickets-container" class="hidden mt-6">
                        <h4 class="text-lg font-semibold text-gray-200 mb-3">Tickets for: <span id="issue-tickets-title" class="text-blue-400"></span></h4>
                        <div class="overflow-x-auto bg-gray-700 rounded-lg">
                            <table class="min-w-full text-gray-200">
                                <thead>
                                    <tr class="bg-gray-600 text-gray-300 uppercase text-sm leading-normal">
                                        <th class="py-3 px-6 text-left">ID</th>
                                        <th class="py-3 px-6 text-left">Employee</th>
                                        <th class="py-3 px-6 text-left">Department</th>
                                        <th class="py-3 px-6 text-left">Status</th>
                                        <th class="py-3 px-6 text-left">Urgency</th>
                                        <th class="py-3 px-6 text-left">Date</th>
                                        <th class="py-3 px-6 text-left">Issue Details</th>
                                    </tr>
                                </thead>
                                <tbody class="text-gray-200 text-sm font-light" id="issue-tickets-table-body">
                                </tbody>
                            </table>
                        </div>
    
                        <div id="issue-pagination-controls" class="flex justify-center mt-4 space-x-2"></div>
                        <div class="flex justify-center mt-4">
                            <button id="back-to-issues" class="px-4 py-2 text-sm text-white bg-red-600 rounded hover:bg-red-700">Back to All Issues</button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </main>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    // Simple real-time Manila clock
    function updateManilaClock() {
        const now = new Date();
        const manilaTime = now.toLocaleTimeString('en-US', {
            timeZone: 'Asia/Manila',
            hour12: true,
            hour: '2-digit',
            minute: '2-digit',
            second: '2-digit'
        });
        document.getElementById('realtime-clock').textContent = manilaTime ;
    }

    updateManilaClock();
    setInterval(updateManilaClock, 1000);


    let topSubmittersChart = null;
    let topOMsChart = null;
    let overallOMsChart = null;
    let topIssuesChart = null;
    let issueMonthlyTrendChart = null;
    let currentFilter = { type: null, value: null };

    document.addEventListener('DOMContentLoaded', () => {
        // Initial load for the Ticket Reports tab table
        fetchTicketsByStatus(1, 'total');
        attachRecentTicketListeners();
        fetchTicketsByPage(1, null, null);
        document.getElementById('show-all-tickets').classList.add('hidden');
        initCharts();
        attachIssueBreakdownListeners();

        document.querySelectorAll('.ticket-count-card').forEach(card => {
            card.addEventListener('click', () => {
                const status = card.getAttribute('data-status-filter');
                if (status) {
                    fetchTicketsByStatus(1, status);
                }
            });
        });

        document.getElementById('show-all-tickets-btn').addEventListener('click', () => {
            fetchTicketsByStatus(1, 'total');
        });

        const initialTab = document.querySelector('.tab-button.active');
        if (initialTab) {
            const tabId = initialTab.getAttribute('data-tab');
            document.getElementById(tabId).classList.remove('hidden');
        }
    });

    // All chart initialization logic moved here to avoid repetition.
    function initCharts() {
        // Top Submitters Chart
        const topSubmittersCtx = document.getElementById('topSubmittersChart').getContext('2d');
        topSubmittersChart = new Chart(topSubmittersCtx, {
            type: 'bar',
            data: {
                labels: <?= json_encode(array_column($topSubmitters, 'Employee_name')) ?>,
                datasets: [{
                    label: 'Number of Tickets',
                    data: <?= json_encode(array_column($topSubmitters, 'ticket_count')) ?>,
                    backgroundColor: 'rgba(59, 130, 246, 0.7)',
                    borderColor: 'rgba(59, 130, 246, 1)',
                    borderWidth: 1
                }]
            },
            options: getChartOptions('bar')
        });

        // Top OMs Chart
        const topOMsCtx = document.getElementById('topOMsChart').getContext('2d');
        topOMsChart = new Chart(topOMsCtx, {
            type: 'bar',
            data: {
                labels: <?= json_encode(array_column($topOMs, 'OM')) ?>,
                datasets: [{
                    label: 'Number of Tickets',
                    data: <?= json_encode(array_column($topOMs, 'om_count')) ?>,
                    backgroundColor: 'rgba(139, 92, 246, 0.7)',
                    borderColor: 'rgba(139, 92, 246, 1)',
                    borderWidth: 1
                }]
            },
            options: getChartOptions('bar')
        });

        // Overall Tickets per OM per Month Chart
        const overallOMsByMonthData = <?= json_encode($overallOMsByMonth) ?>;
        overallOMsByMonthData.datasets.forEach(dataset => {
            dataset.borderColor = getRandomColor();
            dataset.backgroundColor = dataset.borderColor.replace('0.7', '0.2');
            dataset.fill = false;
            dataset.borderWidth = 2;
            dataset.tension = 0.1;
        });
        const overallOMsCtx = document.getElementById('uniqueOMsChart').getContext('2d');
        overallOMsChart = new Chart(overallOMsCtx, {
            type: 'line',
            data: {
                labels: overallOMsByMonthData.labels,
                datasets: overallOMsByMonthData.datasets
            },
            options: getChartOptions('line')
        });

        // Weekly Tickets Chart
        const weeklyTicketsCtx = document.getElementById('weeklyTicketsChart').getContext('2d');
        new Chart(weeklyTicketsCtx, {
            type: 'line',
            data: {
                labels: <?= json_encode(array_column($weeklyTickets, 'Week_Beginning')) ?>,
                datasets: [{
                    label: 'Tickets per Week',
                    data: <?= json_encode(array_column($weeklyTickets, 'ticket_count')) ?>,
                    backgroundColor: 'rgba(59, 130, 246, 0.2)',
                    borderColor: 'rgba(59, 130, 246, 1)',
                    borderWidth: 2,
                    tension: 0.1,
                    fill: true
                }]
            },
            options: getChartOptions('line')
        });

        // Monthly Tickets Chart
        const monthlyTicketsCtx = document.getElementById('monthlyTicketsChart').getContext('2d');
        new Chart(monthlyTicketsCtx, {
            type: 'bar',
            data: {
                labels: <?= json_encode(array_column($monthlyTickets, 'month')) ?>,
                datasets: [{
                    label: 'Tickets per Month',
                    data: <?= json_encode(array_column($monthlyTickets, 'ticket_count')) ?>,
                    backgroundColor: 'rgba(16, 185, 129, 0.7)',
                    borderColor: 'rgba(16, 185, 129, 1)',
                    borderWidth: 1
                }]
            },
            options: getChartOptions('bar')
        });

        // Yearly Tickets Chart
        const yearlyTicketsCtx = document.getElementById('yearlyTicketsChart').getContext('2d');
        new Chart(yearlyTicketsCtx, {
            type: 'doughnut',
            data: {
                labels: <?= json_encode(array_column($yearlyTickets, 'year')) ?>,
                datasets: [{
                    label: 'Tickets per Year',
                    data: <?= json_encode(array_column($yearlyTickets, 'ticket_count')) ?>,
                    backgroundColor: ['rgba(59, 130, 246, 0.7)', 'rgba(16, 185, 129, 0.7)', 'rgba(245, 158, 11, 0.7)'],
                    borderColor: ['rgba(59, 130, 246, 1)', 'rgba(16, 185, 129, 1)', 'rgba(245, 158, 11, 1)'],
                    borderWidth: 1
                }]
            },
            options: getChartOptions('doughnut')
        });
        
        // Top Issues Chart (Horizontal Bar)
        const issueBreakdownCtx = document.getElementById('topIssuesChart').getContext('2d');
        topIssuesChart = new Chart(issueBreakdownCtx, {
            type: 'bar',
            data: {
                labels: <?= json_encode(array_column($issueBreakdown, 'Issues_Concerning')) ?>,
                datasets: [{
                    label: 'Number of Tickets',
                    data: <?= json_encode(array_column($issueBreakdown, 'issue_count')) ?>,
                    backgroundColor: 'rgba(59, 130, 246, 0.7)',
                    borderColor: 'rgba(59, 130, 246, 1)',
                    borderWidth: 1
                }]
            },
            options: getChartOptions('horizontalBar')
        });

        // Issue Monthly Trend Chart
        const monthlyTicketsData = <?= json_encode($monthlyTickets) ?>;
        const monthlyLabels = monthlyTicketsData.map(item => item.month).reverse();
        const monthlyData = monthlyTicketsData.map(item => item.ticket_count).reverse();
        const issueMonthlyTrendCtx = document.getElementById('issueMonthlyTrendChart').getContext('2d');
        issueMonthlyTrendChart = new Chart(issueMonthlyTrendCtx, {
            type: 'line',
            data: {
                labels: monthlyLabels,
                datasets: [{
                    label: 'Tickets per Month',
                    data: monthlyData,
                    backgroundColor: 'rgba(255, 99, 132, 0.2)',
                    borderColor: 'rgba(255, 99, 132, 1)',
                    borderWidth: 2,
                    tension: 0.4,
                    fill: true
                }]
            },
            options: getChartOptions('line')
        });
    }

    // Chart options function to keep things DRY and consistent with the dashboard style
    function getChartOptions(chartType) {
        const baseOptions = {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    labels: {
                        color: '#9CA3AF'
                    }
                }
            }
        };

        if (chartType === 'doughnut') {
            return baseOptions;
        }

        const scales = {
            x: {
                grid: {
                    color: 'rgba(55, 65, 81, 0.5)',
                    display: chartType !== 'horizontalBar'
                },
                ticks: {
                    color: '#9CA3AF'
                }
            },
            y: {
                beginAtZero: true,
                grid: {
                    color: 'rgba(55, 65, 81, 0.5)'
                },
                ticks: {
                    color: '#9CA3AF',
                    stepSize: 1
                }
            }
        };

        if (chartType === 'horizontalBar') {
            scales.x.grid.display = true;
            scales.y.grid.display = false;
            scales.x.ticks.stepSize = 1;
        }
        
        return {
            ...baseOptions,
            scales: scales,
            indexAxis: chartType === 'horizontalBar' ? 'y' : 'x',
        };
    }

    function getRandomColor() {
        const r = Math.floor(Math.random() * 255);
        const g = Math.floor(Math.random() * 255);
        const b = Math.floor(Math.random() * 255);
        return `rgba(${r}, ${g}, ${b}, 0.7)`;
    }

    async function fetchDataAndUpdate(selectedMonth, dataType) {
        const url = `statistics.php?month=${selectedMonth}&data_type=${dataType}`;
        try {
            const response = await fetch(url, {
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            });
            const data = await response.json();
            
            if (dataType === 'employee') {
                const chartData = data.topSubmitters;
                const labels = chartData.map(item => item.Employee_name);
                const counts = chartData.map(item => item.ticket_count);
                const chartElement = document.getElementById('topSubmittersChart');
                const noDataElement = document.getElementById('employee-no-data');
                if (chartData.length > 0) {
                    if (topSubmittersChart) {
                        topSubmittersChart.data.labels = labels;
                        topSubmittersChart.data.datasets[0].data = counts;
                        topSubmittersChart.update();
                    }
                    chartElement.style.display = 'block';
                    noDataElement.classList.add('hidden');
                } else {
                    if (topSubmittersChart) {
                        topSubmittersChart.destroy();
                        topSubmittersChart = null;
                    }
                    chartElement.style.display = 'none';
                    noDataElement.classList.remove('hidden');
                }
            } else if (dataType === 'om') {
                const topOMsData = data.topOMs;
                const topOMsLabels = topOMsData.map(item => item.OM);
                const topOMsCounts = topOMsData.map(item => item.om_count);
                const topOMsChartElement = document.getElementById('topOMsChart');
                const topOMsNoDataElement = document.getElementById('om-no-data');

                if (topOMsData.length > 0) {
                    if (topOMsChart) {
                        topOMsChart.data.labels = topOMsLabels;
                        topOMsChart.data.datasets[0].data = topOMsCounts;
                        topOMsChart.update();
                    }
                    topOMsChartElement.style.display = 'block';
                    topOMsNoDataElement.classList.add('hidden');
                } else {
                    if (topOMsChart) {
                        topOMsChart.destroy();
                        topOMsChart = null;
                    }
                    topOMsChartElement.style.display = 'none';
                    topOMsNoDataElement.classList.remove('hidden');
                }
                const overallOMsByMonthData = data.overallOMsByMonth;
                overallOMsByMonthData.datasets.forEach(dataset => {
                    dataset.borderColor = getRandomColor();
                    dataset.backgroundColor = dataset.borderColor.replace('0.7', '0.2');
                    dataset.fill = false;
                    dataset.borderWidth = 2;
                    dataset.tension = 0.1;
                });
                if (overallOMsChart) {
                    overallOMsChart.data.labels = overallOMsByMonthData.labels;
                    overallOMsChart.data.datasets = overallOMsByMonthData.datasets;
                    overallOMsChart.update();
                }
            }
        } catch (error) {
            console.error('Error fetching data:', error);
        }
    }

    function updateTicketsTable(tableBodyId, tickets, filterType) {
        const tableBody = document.getElementById(tableBodyId);
        let html = '';
        if (tickets.length > 0) {
            tickets.forEach(ticket => {
                const statusClass = ticket.Status === 'RESOLVED' ? 'bg-green-700' : (ticket.Status === 'PENDING' ? 'bg-yellow-700' : 'bg-red-700');
                const urgencyClass = ticket.Urgency === 'High' ? 'bg-red-700' : (ticket.Urgency === 'Medium' ? 'bg-yellow-700' : 'bg-blue-700');
                let employeeOrOm = filterType === 'om' ? ticket.OM : ticket.Employee_name;
                html += `
                    <tr class="border-b border-gray-600 hover:bg-gray-600">
                        <td class="py-3 px-6 text-left whitespace-nowrap">${ticket.id}</td>
                        <td class="py-3 px-6 text-left">${employeeOrOm}</td>
                        <td class="py-3 px-6 text-left">${ticket.LOB}</td>
                        <td class="py-3 px-6 text-left">${ticket.Issues_Concerning}</td>
                        <td class="py-3 px-6 text-left">
                            <span class="${statusClass} text-white py-1 px-3 rounded-full text-xs">
                                ${ticket.Status}
                            </span>
                        </td>
                        <td class="py-3 px-6 text-left">
                            <span class="${urgencyClass} text-white py-1 px-3 rounded-full text-xs">
                                ${ticket.Urgency || 'Not specified'}
                            </span>
                        </td>
                        <td class="py-3 px-6 text-left">${ticket.Timestamp}</td>
                    </tr>
                `;
            });
        } else {
            html = `<tr><td colspan="7" class="text-center py-4 text-gray-500">No tickets found for this filter.</td></tr>`;
        }
        tableBody.innerHTML = html;
    }

    function updateIssueTicketsTable(tickets) {
        const tableBody = document.getElementById('issue-tickets-table-body');
        let html = '';
        if (tickets.length > 0) {
            tickets.forEach(ticket => {
                const statusClass = ticket.Status === 'RESOLVED' ? 'bg-green-700' : (ticket.Status === 'PENDING' ? 'bg-yellow-700' : 'bg-red-700');
                const urgencyClass = ticket.Urgency === 'High' ? 'bg-red-700' : (ticket.Urgency === 'Medium' ? 'bg-yellow-700' : 'bg-blue-700');
                html += `
                    <tr class="border-b border-gray-600 hover:bg-gray-600">
                        <td class="py-3 px-6 text-left whitespace-nowrap">${ticket.id}</td>
                        <td class="py-3 px-6 text-left">${ticket.Employee_name}</td>
                        <td class="py-3 px-6 text-left">${ticket.LOB}</td>
                        <td class="py-3 px-6 text-left">
                            <span class="${statusClass} text-white py-1 px-3 rounded-full text-xs">
                                ${ticket.Status}
                            </span>
                        </td>
                        <td class="py-3 px-6 text-left">
                            <span class="${urgencyClass} text-white py-1 px-3 rounded-full text-xs">
                                ${ticket.Urgency || 'Not specified'}
                            </span>
                        </td>
                        <td class="py-3 px-6 text-left">${ticket.Timestamp}</td>
                        <td class="py-3 px-6 text-left">${ticket.Issue_Details}</td>
                    </tr>
                `;
            });
        } else {
             html = `<tr><td colspan="7" class="text-center py-4 text-gray-500">No tickets found for this issue.</td></tr>`;
        }
        tableBody.innerHTML = html;
    }

    function updatePagination(paginationId, totalPages, currentPage, filterType, filterValue) {
        const paginationControls = document.getElementById(paginationId);
        let html = '';
        const prevDisabled = currentPage === 1 ? 'disabled' : '';
        html += `<button class="pagination-link px-3 py-1 text-sm bg-gray-600 text-white rounded hover:bg-gray-500 ${prevDisabled}" data-page="${currentPage - 1}">Previous</button>`;
        for (let i = 1; i <= totalPages; i++) {
            const activeClass = i === currentPage ? 'bg-blue-600 text-white' : 'bg-gray-600 text-white';
            html += `<button class="pagination-link px-3 py-1 text-sm rounded hover:bg-blue-700 ${activeClass}" data-page="${i}">${i}</button>`;
        }
        const nextDisabled = currentPage === totalPages ? 'disabled' : '';
        html += `<button class="pagination-link px-3 py-1 text-sm bg-gray-600 text-white rounded hover:bg-gray-500 ${nextDisabled}" data-page="${currentPage + 1}">Next</button>`;
        paginationControls.innerHTML = html;

        paginationControls.querySelectorAll('.pagination-link').forEach(button => {
            button.addEventListener('click', () => {
                if (!button.disabled) {
                    fetchTicketsByPage(button.dataset.page, filterType, filterValue);
                }
            });
        });
    }

    function updateFilteredPagination(paginationId, totalPages, currentPage, status) {
        const paginationControls = document.getElementById(paginationId);
        let html = '';
        const prevDisabled = currentPage === 1 ? 'disabled' : '';
        html += `<button class="filtered-pagination-link px-3 py-1 text-sm bg-gray-600 text-white rounded hover:bg-gray-500 ${prevDisabled}" data-page="${currentPage - 1}" data-status="${status}">Previous</button>`;
        for (let i = 1; i <= totalPages; i++) {
            const activeClass = i === currentPage ? 'bg-blue-600 text-white' : 'bg-gray-600 text-white';
            html += `<button class="filtered-pagination-link px-3 py-1 text-sm rounded hover:bg-blue-700 ${activeClass}" data-page="${i}" data-status="${status}">${i}</button>`;
        }
        const nextDisabled = currentPage === totalPages ? 'disabled' : '';
        html += `<button class="filtered-pagination-link px-3 py-1 text-sm bg-gray-600 text-white rounded hover:bg-gray-500 ${nextDisabled}" data-page="${currentPage + 1}" data-status="${status}">Next</button>`;
        paginationControls.innerHTML = html;
        paginationControls.querySelectorAll('.filtered-pagination-link').forEach(button => {
            button.addEventListener('click', () => {
                if (!button.disabled) {
                    fetchTicketsByStatus(button.dataset.page, button.dataset.status);
                }
            });
        });
    }

    function updateIssuePagination(paginationId, totalPages, currentPage, issueName) {
        const paginationControls = document.getElementById(paginationId);
        let html = '';
        const prevDisabled = currentPage === 1 ? 'disabled' : '';
        html += `<button class="issue-pagination-link px-3 py-1 text-sm bg-gray-600 text-white rounded hover:bg-gray-500 ${prevDisabled}" data-page="${currentPage - 1}" data-issue="${issueName}">Previous</button>`;
        for (let i = 1; i <= totalPages; i++) {
            const activeClass = i === currentPage ? 'bg-blue-600 text-white' : 'bg-gray-600 text-white';
            html += `<button class="issue-pagination-link px-3 py-1 text-sm rounded hover:bg-blue-700 ${activeClass}" data-page="${i}" data-issue="${issueName}">${i}</button>`;
        }
        const nextDisabled = currentPage === totalPages ? 'disabled' : '';
        html += `<button class="issue-pagination-link px-3 py-1 text-sm bg-gray-600 text-white rounded hover:bg-gray-500 ${nextDisabled}" data-page="${currentPage + 1}" data-issue="${issueName}">Next</button>`;
        paginationControls.innerHTML = html;
        paginationControls.querySelectorAll('.issue-pagination-link').forEach(button => {
            button.addEventListener('click', () => {
                if (!button.disabled) {
                    fetchIssueTicketsByPage(button.dataset.page, button.dataset.issue);
                }
            });
        });
    }

    async function fetchTicketsByPage(page, filterType = null, filterValue = null) {
        let url = `statistics.php?action=getTickets&page=${page}`;
        if (filterType && filterValue) {
            url += `&filter_type=${filterType}&filter_value=${filterValue}`;
            document.getElementById('show-all-tickets').classList.remove('hidden');
        } else {
            document.getElementById('show-all-tickets').classList.add('hidden');
        }
        try {
            const response = await fetch(url, { headers: { 'X-Requested-With': 'XMLHttpRequest' } });
            const data = await response.json();
            updateTicketsTable('all-tickets-table-body', data.tickets, filterType);
            updatePagination('pagination-controls', data.totalPages, data.currentPage, filterType, filterValue);
            currentFilter.type = filterType;
            currentFilter.value = filterValue;
            
            const tableHead = document.getElementById('all-tickets-table-head');
            if (filterType === 'om') {
                tableHead.innerHTML = `
                    <th class="py-3 px-6 text-left">ID</th>
                    <th class="py-3 px-6 text-left">OM</th>
                    <th class="py-3 px-6 text-left">Department</th>
                    <th class="py-3 px-6 text-left">Issue</th>
                    <th class="py-3 px-6 text-left">Status</th>
                    <th class="py-3 px-6 text-left">Urgency</th>
                    <th class="py-3 px-6 text-left">Date</th>
                `;
            } else {
                 tableHead.innerHTML = `
                    <th class="py-3 px-6 text-left">ID</th>
                    <th class="py-3 px-6 text-left">Employee</th>
                    <th class="py-3 px-6 text-left">Department</th>
                    <th class="py-3 px-6 text-left">Issue</th>
                    <th class="py-3 px-6 text-left">Status</th>
                    <th class="py-3 px-6 text-left">Urgency</th>
                    <th class="py-3 px-6 text-left">Date</th>
                `;
            }
        } catch (error) {
            console.error('Error fetching tickets:', error);
        }
    }

    async function fetchTicketsByStatus(page, status) {
        let url = `statistics.php?action=getTicketsByStatus&page=${page}&status=${status}`;
        let titleText = 'All Tickets';
        const showAllBtn = document.getElementById('show-all-tickets-btn');

        if (status !== 'total') {
            titleText = `${status.charAt(0).toUpperCase() + status.slice(1)} Tickets`;
            showAllBtn.classList.remove('hidden');
        } else {
            showAllBtn.classList.add('hidden');
        }

        try {
            const response = await fetch(url, { headers: { 'X-Requested-With': 'XMLHttpRequest' } });
            const data = await response.json();
            document.getElementById('ticket-table-title').textContent = titleText;
            updateTicketsTable('filtered-tickets-table-body', data.tickets);
            updateFilteredPagination('filtered-pagination-controls', data.totalPages, data.currentPage, status);
        } catch (error) {
            console.error('Error fetching filtered tickets:', error);
        }
    }

    async function fetchIssueTicketsByPage(page, issueName) {
        const url = `statistics.php?action=getTickets&page=${page}&filter_type=issue&filter_value=${encodeURIComponent(issueName)}`;
        try {
            const response = await fetch(url, { headers: { 'X-Requested-With': 'XMLHttpRequest' } });
            const data = await response.json();
            document.getElementById('all-issues-table-container').classList.add('hidden');
            document.getElementById('issue-tickets-container').classList.remove('hidden');
            document.getElementById('issue-tickets-title').textContent = issueName;
            updateIssueTicketsTable(data.tickets);
            updateIssuePagination('issue-pagination-controls', data.totalPages, data.currentPage, issueName);
        } catch (error) {
            console.error('Error fetching issue tickets:', error);
        }
    }
    
    document.getElementById('employee-month-select').addEventListener('change', (event) => {
        fetchDataAndUpdate(event.target.value, 'employee');
    });

    document.getElementById('om-month-select').addEventListener('change', async (event) => {
        fetchDataAndUpdate(event.target.value, 'om');
    });

    document.getElementById('show-all-tickets').addEventListener('click', () => {
        fetchTicketsByPage(1, null, null);
    });
    
    function attachRecentTicketListeners() {
        document.querySelectorAll('#employee-recent-tickets-tbody tr').forEach(row => {
            row.addEventListener('click', () => {
                fetchTicketsByPage(1, 'employee', row.dataset.filterValue);
            });
        });
        document.querySelectorAll('#om-recent-tickets-tbody tr').forEach(row => {
            row.addEventListener('click', () => {
                fetchTicketsByPage(1, 'om', row.dataset.filterValue);
            });
        });
    }

    function attachIssueBreakdownListeners() {
        document.querySelectorAll('.show-issue-tickets').forEach(button => {
            button.addEventListener('click', (event) => {
                event.stopPropagation();
                fetchIssueTicketsByPage(1, event.target.dataset.issueName);
            });
        });
        document.getElementById('back-to-issues').addEventListener('click', () => {
            document.getElementById('issue-tickets-container').classList.add('hidden');
            document.getElementById('all-issues-table-container').classList.remove('hidden');
        });
    }
    
    document.querySelectorAll('.tab-button').forEach(button => {
        button.addEventListener('click', () => {
            document.querySelectorAll('.tab-button').forEach(btn => btn.classList.remove('active'));
            document.querySelectorAll('.tab-content').forEach(content => content.classList.add('hidden'));
            button.classList.add('active');
            const tabId = button.getAttribute('data-tab');
            document.getElementById(tabId).classList.remove('hidden');
            
            attachRecentTicketListeners();

            if (tabId === 'issue-breakdown') {
                attachIssueBreakdownListeners();
            }

            if (tabId === 'employee-reports' || tabId === 'om-reports') {
                fetchTicketsByPage(1, null, null);
            }
            if (tabId === 'ticket-reports') {
                fetchTicketsByStatus(1, 'total');
            }
        });
    });
</script>

<?php renderFooter(); ?>