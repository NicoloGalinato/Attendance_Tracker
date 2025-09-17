<?php

include('../connection.php');
// New session handling based on dashboard.php
require_once '../includes/config.php';
require_once '../includes/functions.php';

if (!isLoggedIn() || !isAdmin()) {
    redirect(BASE_URL);
}

updateLastActivity();

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// Get current user's sub_name
$userStmt = $pdo->prepare("SELECT sub_name FROM users WHERE id = ?");
$userStmt->execute([$_SESSION['user_id']]);
$user = $userStmt->fetch();
$sub_name = $user['sub_name'];

// AJAX Endpoint for updating ticket status
if (isset($_POST['action']) && $_POST['action'] === 'resolve_ticket') {
    $ticketId = $_POST['id'] ?? null;
    $resolution = $_POST['resolution'] ?? null;
    $response = ['success' => false, 'message' => 'Invalid request.'];

    if ($ticketId) {
        $query = "UPDATE ticket SET Status = 'RESOLVED', TIME_RESOLVED = NOW(), resolution = ?, SLT_on_DUTY = ? WHERE id = ?";
        $stmt = $con->prepare($query);
        
        if ($stmt) {
            $stmt->bind_param("ssi", $resolution, $sub_name, $ticketId);
            if ($stmt->execute()) {
                $response['success'] = true;
                $response['message'] = 'Ticket resolved successfully.';
            } else {
                $response['message'] = 'Failed to resolve ticket: ' . $stmt->error;
            }
        } else {
            $response['message'] = 'Failed to prepare statement: ' . $con->error;
        }
    }

    header('Content-Type: application/json');
    echo json_encode($response);
    exit;
}

// AJAX endpoint to get pending tickets count (for all pages)
if (isset($_GET['action']) && $_GET['action'] === 'get_pending_count') {
    $pendingCount = getPendingTicketsCount();
    header('Content-Type: application/json');
    echo json_encode(['pending_count' => $pendingCount]);
    exit;
}

// AJAX endpoint to fetch all ticket data
if (isset($_GET['action']) && $_GET['action'] === 'get_tickets_data') {
    function fetchAllTicketsForRealtime($con) {
        $query = "SELECT * FROM ticket ORDER BY STR_TO_DATE(Timestamp, '%m/%d/%Y %H:%i:%s') DESC";
        $result = $con->query($query);
        return $result->fetch_all(MYSQLI_ASSOC);
    }
    $tickets = fetchAllTicketsForRealtime($con);
    header('Content-Type: application/json');
    echo json_encode(['tickets' => $tickets]);
    exit;
}

// AJAX Endpoint for fetching chart data
if (isset($_GET['action']) && $_GET['action'] === 'get_resolved_slt_data') {
    $month = $_GET['month'] ?? date('Y-m');
    $startDate = $month . '-01';
    $endDate = date('Y-m-d', strtotime($startDate . ' +1 month -1 day'));

    $query = "
        SELECT SLT_on_DUTY, COUNT(*) AS resolved_count
        FROM ticket
        WHERE Status = 'RESOLVED'
        AND STR_TO_DATE(Timestamp, '%m/%d/%Y %H:%i:%s') BETWEEN ? AND ?
        GROUP BY SLT_on_DUTY
        ORDER BY resolved_count DESC
    ";

    $stmt = $con->prepare($query);
    $stmt->bind_param("ss", $startDate, $endDate);
    $stmt->execute();
    $result = $stmt->get_result();
    $data = $result->fetch_all(MYSQLI_ASSOC);

    header('Content-Type: application/json');
    echo json_encode(['data' => $data]);
    exit;
}

// Function to get pending tickets count
function getPendingTicketsCount() {
    global $con;
    $query = "SELECT COUNT(*) as count FROM ticket WHERE Status = 'PENDING'";
    $stmt = $con->prepare($query);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    return $row['count'];
}

// Get pending tickets count for the notification badge
$pendingTicketsCount = getPendingTicketsCount();

require_once '../components/layout.php';
renderHead('Ticket Dashboard');
renderNavbar();
renderSidebar('ticket_dashboard' , $pendingTicketsCount ?? 0);

// Get counts for dashboard cards
function getCount($status = null, $date = null) {
    global $con;
    $query = "SELECT COUNT(*) as count FROM ticket";
    $conditions = [];
    $params = [];
    $types = '';
    if ($status) {
        $conditions[] = "Status = ?";
        $params[] = $status;
        $types .= 's';
    }
    if ($date) {
        $conditions[] = "DATE(STR_TO_DATE(Timestamp, '%m/%d/%Y %H:%i:%s')) = ?";
        $params[] = $date;
        $types .= 's';
    }
    if (!empty($conditions)) {
        $query .= " WHERE " . implode(' AND ', $conditions);
    }
    $stmt = $con->prepare($query);
    if (!empty($params)) {
        $stmt->bind_param($types, ...$params);
    }
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    return $row['count'];
}

$totalTicketsOverall = getCount();
$ticketsReceivedToday = getCount(null, date('Y-m-d'));
$ticketsResolvedToday = getCount('RESOLVED', date('Y-m-d'));
$pendingTicketsCount = getCount('PENDING');

// Get months with resolved tickets for the dropdown
$monthsQuery = "SELECT DISTINCT DATE_FORMAT(STR_TO_DATE(Timestamp, '%m/%d/%Y %H:%i:%s'), '%Y-%m') AS month FROM ticket WHERE Status = 'RESOLVED' ORDER BY month DESC";
$monthsResult = $con->query($monthsQuery);
$monthsWithData = $monthsResult->fetch_all(MYSQLI_ASSOC);

?>
<div class="pt-2 min-h-screen bg-gray-900 text-white">
    <main class="p-6">
        <div class="flex justify-between items-center mb-6">
            <h1 class="text-2xl font-bold">Admin Dashboard</h1>
            <div class="text-sm text-gray-400">
                <?= date('F j, Y') ?>
            </div>
        </div>
        
        <?php renderAlert(); ?>
        
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
            <a href="#" class="bg-gray-800 border border-gray-700 p-4 rounded-lg shadow-md hover:bg-gray-700 transition duration-300" onclick="filterAndPaginate('all', 1); return false;">
                <h2 class="text-gray-400 text-sm font-medium">Overall Tickets</h2>
                <p class="text-3xl font-bold text-white" id="total-tickets-count"><?= $totalTicketsOverall ?></p>
            </a>
            <a href="#" class="bg-gray-800 border border-gray-700 p-4 rounded-lg shadow-md hover:bg-gray-700 transition duration-300" onclick="filterAndPaginate('received_today', 1); return false;">
                <h2 class="text-gray-400 text-sm font-medium">Received Today</h2>
                <p class="text-3xl font-bold text-white" id="received-today-count"><?= $ticketsReceivedToday ?></p>
            </a>
            <a href="#" class="bg-gray-800 border border-gray-700 p-4 rounded-lg shadow-md hover:bg-gray-700 transition duration-300" onclick="filterAndPaginate('resolved_today', 1); return false;">
                <h2 class="text-gray-400 text-sm font-medium">Resolved Today</h2>
                <p class="text-3xl font-bold text-white" id="resolved-today-count"><?= $ticketsResolvedToday ?></p>
            </a>
            <a href="#" class="bg-gray-800 border border-gray-700 p-4 rounded-lg shadow-md hover:bg-gray-700 transition duration-300" onclick="filterAndPaginate('pending', 1); return false;">
                <h2 class="text-gray-400 text-sm font-medium">Pending Tickets</h2>
                <p class="text-3xl font-bold text-white" id="pending-tickets-count"><?= $pendingTicketsCount ?></p>
            </a>
        </div>

        <div class="bg-gray-800 border border-gray-700 p-6 rounded-lg shadow-md overflow-x-auto">
            <h2 class="text-xl font-semibold mb-4 text-white">All Tickets</h2>
            <table class="min-w-full divide-y divide-gray-700">
                <thead class="bg-gray-700">
                    <tr>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-400 uppercase tracking-wider">ID</th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-400 uppercase tracking-wider">Timestamp</th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-400 uppercase tracking-wider">LOB</th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-400 uppercase tracking-wider">Status</th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-400 uppercase tracking-wider">Issues Concerning</th>
                    </tr>
                </thead>
                <tbody id="ticket-table-body" class="bg-gray-800 divide-y divide-gray-700">
                    </tbody>
            </table>

            <nav id="pagination-controls" class="mt-4 flex justify-center">
                </nav>
        </div>

        <div class="mt-6 bg-gray-800 border border-gray-700 p-6 rounded-lg shadow-md">
            <div class="flex justify-between items-center mb-4">
                <h2 class="text-xl font-semibold text-white">Most Resolved Tickets by SLT on Duty</h2>
                <select id="monthSelector" class="bg-gray-700 text-white rounded-lg p-2 border-gray-600">
                    <?php 
                        $currentMonth = date('Y-m');
                        foreach($monthsWithData as $month): 
                        $selected = ($month['month'] == $currentMonth) ? 'selected' : '';
                    ?>
                        <option value="<?= $month['month'] ?>" <?= $selected ?>>
                            <?= date('F Y', strtotime($month['month'] . '-01')) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="h-80">
                <canvas id="resolvedTicketsChart"></canvas>
            </div>
        </div>
        
        <div id="ticketModal" class="fixed inset-0 z-50 overflow-y-auto hidden bg-gray-600 bg-opacity-50 flex items-center justify-center">
            <div class="relative bg-gray-800 rounded-lg shadow-xl w-full max-w-2xl p-6 m-4 text-white">
                <div class="flex justify-between items-center pb-3 border-b border-gray-700">
                    <h3 class="text-2xl font-bold">Ticket Details</h3>
                    <button class="text-gray-400 hover:text-gray-300" onclick="closeModal()">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>
                <div id="modalContent" class="py-4 text-gray-300">
                    </div>
                <div id="modalFooter" class="flex justify-end pt-3 border-t border-gray-700 mt-4">
                    </div>
            </div>
        </div>
    </main>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    let allTickets = [];
    const ticketsPerPage = 10;
    let currentFilter = 'all';
    let currentPage = 1;
    let resolvedTicketsChart = null;
    
    function getTodayDateString() {
        const today = new Date();
        const month = (today.getMonth() + 1).toString();
        const day = today.getDate().toString();
        const year = today.getFullYear();
        return `${month}/${day}/${year}`;
    }

    async function fetchTicketData() {
        try {
            const response = await fetch('ticket_dashboard.php?action=get_tickets_data');
            const data = await response.json();
            allTickets = data.tickets;
            updateDashboard();
        } catch (error) {
            console.error('Error fetching ticket data:', error);
        }
    }

    function updateDashboard() {
        updateCardCounts();
        filterAndPaginate(currentFilter, currentPage);
    }
    
    function updateCardCounts() {
        const todayDateString = getTodayDateString();
        
        const receivedTodayCount = allTickets.filter(ticket => 
            ticket.Timestamp.split(' ')[0] === todayDateString
        ).length;
        
        const resolvedTodayCount = allTickets.filter(ticket => 
            ticket.Status === 'RESOLVED' && 
            ticket.Timestamp.split(' ')[0] === todayDateString
        ).length;
        
        const pendingCount = allTickets.filter(ticket => ticket.Status === 'PENDING').length;
        
        document.getElementById('total-tickets-count').textContent = allTickets.length;
        document.getElementById('received-today-count').textContent = receivedTodayCount;
        document.getElementById('resolved-today-count').textContent = resolvedTodayCount;
        document.getElementById('pending-tickets-count').textContent = pendingCount;
        
        // Also update the notification badge
        const badge = document.getElementById('pending-tickets-badge');
        if (pendingCount > 0) {
            badge.textContent = pendingCount > 9 ? '9+' : pendingCount;
            badge.style.display = 'flex';
            badge.classList.add('animate-pulse');
        } else {
            badge.style.display = 'none';
            badge.classList.remove('animate-pulse');
        }
    }

    function filterAndPaginate(filter, page) {
        currentFilter = filter;
        currentPage = page;
        
        let filteredTickets = [];
        const todayDateString = getTodayDateString();

        switch (filter) {
            case 'all':
                filteredTickets = allTickets;
                break;
            case 'received_today':
                filteredTickets = allTickets.filter(ticket => ticket.Timestamp.split(' ')[0] === todayDateString);
                break;
            case 'resolved_today':
                filteredTickets = allTickets.filter(ticket => ticket.Status === 'RESOLVED' && ticket.Timestamp.split(' ')[0] === todayDateString);
                break;
            case 'pending':
                filteredTickets = allTickets.filter(ticket => ticket.Status === 'PENDING');
                break;
            default:
                filteredTickets = allTickets;
        }

        const totalPages = Math.ceil(filteredTickets.length / ticketsPerPage);
        const start = (page - 1) * ticketsPerPage;
        const end = start + ticketsPerPage;
        const paginatedTickets = filteredTickets.slice(start, end);
        
        renderTable(paginatedTickets);
        renderPagination(totalPages, page);
    }

    function renderTable(ticketsToRender) {
        const tableBody = document.getElementById('ticket-table-body');
        let html = '';
        if (ticketsToRender.length === 0) {
            html = `<tr><td colspan="5" class="px-6 py-4 text-center text-gray-400">No tickets found.</td></tr>`;
        } else {
            ticketsToRender.forEach(ticket => {
                const statusBadge = ticket.Status === 'PENDING' 
                    ? `<span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-yellow-100 text-yellow-800">Pending</span>`
                    : `<span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800">Resolved</span>`;
                
                html += `
                    <tr class="cursor-pointer hover:bg-gray-700 transition duration-300" onclick="openModal(${JSON.stringify(ticket).replace(/"/g, '&quot;')})">
                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-white">${ticket.id}</td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-400">${ticket.Timestamp}</td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-400">${ticket.Department}</td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm">${statusBadge}</td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-400">${ticket.Issues_Concerning}</td>
                    </tr>
                `;
            });
        }
        tableBody.innerHTML = html;
    }

    function renderPagination(totalPages, page) {
        const paginationControls = document.getElementById('pagination-controls');
        let html = '<ul class="flex items-center space-x-2">';
        
        const prevDisabled = page === 1 ? 'pointer-events-none opacity-50' : '';
        html += `<li><a href="#" class="px-3 py-2 leading-tight text-gray-400 bg-gray-700 border border-gray-600 rounded-lg hover:bg-gray-600 hover:text-white ${prevDisabled}" onclick="filterAndPaginate(currentFilter, ${Math.max(1, page - 1)}); return false;">Previous</a></li>`;
        
        for (let i = 1; i <= totalPages; i++) {
            const activeClass = i === page ? 'font-bold bg-blue-600 text-white hover:bg-blue-600' : 'bg-gray-700 text-gray-400 hover:bg-gray-600 hover:text-white';
            html += `<li><a href="#" class="px-3 py-2 leading-tight border border-gray-600 rounded-lg ${activeClass}" onclick="filterAndPaginate(currentFilter, ${i}); return false;">${i}</a></li>`;
        }
        
        const nextDisabled = page === totalPages ? 'pointer-events-none opacity-50' : '';
        html += `<li><a href="#" class="px-3 py-2 leading-tight text-gray-400 bg-gray-700 border border-gray-600 rounded-lg hover:bg-gray-600 hover:text-white ${nextDisabled}" onclick="filterAndPaginate(currentFilter, ${Math.min(totalPages, page + 1)}); return false;">Next</a></li>`;
        
        html += '</ul>';
        paginationControls.innerHTML = html;
    }

    function openModal(ticket) {
        const modal = document.getElementById('ticketModal');
        const modalContent = document.getElementById('modalContent');
        const modalFooter = document.getElementById('modalFooter');
        
        const detailsHtml = `
            <div class="space-y-4">
                <div class="space-y-2 pb-4 border-b border-gray-600">
                    <h4 class="text-xl font-bold text-gray-200">Issue Details</h4>
                    <p class="mt-2 p-3 text-gray-300 bg-gray-700 rounded-lg whitespace-pre-wrap">${ticket.Issue_Details}</p>
                </div>

                <div class="space-y-2 pt-4 pb-4 border-b border-gray-600">
                    <h4 class="text-xl font-bold text-gray-200">Employee Details</h4>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-2 text-gray-300">
                        <div><strong>Employee Name:</strong> ${ticket.Employee_name}</div>
                        <div><strong>Work Number:</strong> ${ticket.Work_Number}</div>
                        <div><strong>EID:</strong> ${ticket.EID}</div>
                        <div><strong>Site:</strong> ${ticket.Site}</div>
                        <div><strong>Department:</strong> ${ticket.LOB}</div>
                    </div>
                </div>

                <div class="space-y-2 pt-4">
                    <h4 class="text-lg font-semibold text-gray-200">Resolution Details</h4>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-2 text-gray-300">
                        <div><strong>OM:</strong> ${ticket.OM}</div>
                        <div><strong>SLT on DUTY:</strong> ${ticket.SLT_on_DUTY || 'N/A'}</div>
                        <div><strong>Time Received:</strong> ${ticket.TIME_RECEIVED}</div>
                        <div><strong>Time Resolved:</strong> ${ticket.TIME_RESOLVED || 'N/A'}</div>
                        <div><strong>Urgency:</strong> ${ticket.Urgency}</div>
                        <div><strong>Station Number:</strong> ${ticket.Station_Number}</div>
                    </div>
                </div>
                
                ${ticket.Status === 'PENDING' ? `
                    <div class="mt-4">
                        <label for="resolution_details" class="block text-sm font-medium text-gray-400 mb-2">What did you do to fix the issue?</label>
                        <textarea id="resolution_details" rows="4" class="block w-full rounded-md bg-gray-700 text-white border-gray-600 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm p-2"></textarea>
                    </div>
                ` : `
                    <div class="mt-4">
                        <h4 class="text-lg font-semibold text-gray-200">Resolution Notes</h4>
                        <p class="mt-2 p-3 text-gray-300 bg-gray-700 rounded-lg whitespace-pre-wrap">${ticket.resolution || 'No resolution details provided.'}</p>
                    </div>
                `}
            </div>
        `;
        
        modalContent.innerHTML = detailsHtml;

        if (ticket.Status === 'PENDING') {
            modalFooter.innerHTML = `<button onclick="resolveTicket(${ticket.id})" class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition duration-300">Resolve Ticket</button>`;
        } else {
            modalFooter.innerHTML = '';
        }

        modal.classList.remove('hidden');
    }

    function closeModal() {
        const modal = document.getElementById('ticketModal');
        modal.classList.add('hidden');
    }

    async function resolveTicket(ticketId) {
        if (confirm("Are you sure you want to resolve this ticket?")) {
            const resolution = document.getElementById('resolution_details').value;
            
            if (resolution.trim() === '') {
                alert('Please provide details on what you did to fix the issue.');
                return;
            }

            const formData = new FormData();
            formData.append('action', 'resolve_ticket');
            formData.append('id', ticketId);
            formData.append('resolution', resolution);

            try {
                const response = await fetch('ticket_dashboard.php', {
                    method: 'POST',
                    body: formData
                });
                const result = await response.json();

                if (result.success) {
                    alert(result.message);
                    closeModal();
                    fetchTicketData();
                } else {
                    alert(result.message);
                }
            } catch (error) {
                console.error('Error:', error);
                alert('An error occurred. Please try again.');
            }
        }
    }

    async function updateChart(month) {
        try {
            const response = await fetch(`ticket_dashboard.php?action=get_resolved_slt_data&month=${month}`);
            const result = await response.json();

            const labels = result.data.map(item => item.SLT_on_DUTY);
            const counts = result.data.map(item => item.resolved_count);

            if (resolvedTicketsChart) {
                resolvedTicketsChart.destroy();
            }

            const ctx = document.getElementById('resolvedTicketsChart').getContext('2d');
            resolvedTicketsChart = new Chart(ctx, {
                type: 'bar',
                data: {
                    labels: labels,
                    datasets: [{
                        label: 'Resolved Tickets',
                        data: counts,
                        backgroundColor: 'rgba(59, 130, 246, 0.7)',
                        borderColor: 'rgba(59, 130, 246, 1)',
                        borderWidth: 1
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    scales: {
                        x: {
                            ticks: { color: '#9CA3AF' },
                            grid: { color: 'rgba(55, 65, 81, 0.5)' }
                        },
                        y: {
                            beginAtZero: true,
                            ticks: { color: '#9CA3AF', stepSize: 1 },
                            grid: { color: 'rgba(55, 65, 81, 0.5)' }
                        }
                    },
                    plugins: {
                        legend: {
                            labels: { color: '#9CA3AF' }
                        }
                    }
                }
            });

        } catch (error) {
            console.error('Error fetching chart data:', error);
        }
    }
    
    document.addEventListener('DOMContentLoaded', () => {
        const monthSelector = document.getElementById('monthSelector');
        
        fetchTicketData();
        updateChart(monthSelector.value);
        
        setInterval(fetchTicketData, 15000); 

        monthSelector.addEventListener('change', (event) => {
            updateChart(event.target.value);
        });
    });

    // Function to update the notification badge
    async function updateNotificationBadge() {
        try {
            const response = await fetch('ticket_dashboard.php?action=get_pending_count');
            const data = await response.json();
            const badge = document.getElementById('pending-tickets-badge');
            
            if (data.pending_count > 0) {
                badge.textContent = data.pending_count > 9 ? '9+' : data.pending_count;
                badge.style.display = 'flex';
                
                // Add animation class
                badge.classList.add('animate-pulse');
            } else {
                badge.style.display = 'none';
                badge.classList.remove('animate-pulse');
            }
        } catch (error) {
            console.error('Error updating notification badge:', error);
        }
    }

    // Update badge periodically (every 0.5 second)
    setInterval(updateNotificationBadge, 500);

    // Initial update
    updateNotificationBadge();

</script>

<?php renderFooter(); ?>