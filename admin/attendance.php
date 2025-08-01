<?php
require_once '../includes/config.php';
require_once '../includes/functions.php';

if (!isLoggedIn() || !isAdmin()) {
    redirect(BASE_URL);
}

updateLastActivity();

// Handle deletion
if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    $type = isset($_GET['type']) ? $_GET['type'] : 'absenteeism';
    
    try {
        $table = ($type === 'tardiness') ? 'tardiness' : 'absenteeism';
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

require_once '../components/layout.php';
renderHead('Attendance Tracker');
renderNavbar();
renderSidebar('attendance');

$currentTab = isset($_GET['tab']) ? $_GET['tab'] : 'absenteeism';
?>

<div class="md:ml-64 pt-2 min-h-screen">
    <main class="p-6">
        <div class="flex justify-between items-center mb-6">
            <h1 class="text-2xl font-bold">Attendance Tracker</h1>
            <a href="attendance_form.php?action=create&type=<?= $currentTab ?>" class="bg-primary-600 hover:bg-primary-700 text-white px-4 py-2 rounded-lg flex items-center">
                <i class="fas fa-plus mr-2"></i> Add New
            </a>
        </div>

        <!-- Tabs -->
        <div class="border-b border-gray-700 mb-6">
            <nav class="-mb-px flex space-x-8">
                <a href="?tab=absenteeism" class="<?= $currentTab === 'absenteeism' ? 'border-primary-500 text-primary-400' : 'border-transparent text-gray-400 hover:text-gray-300' ?> whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm">
                    Absenteeism
                </a>
                <a href="?tab=tardiness" class="<?= $currentTab === 'tardiness' ? 'border-primary-500 text-primary-400' : 'border-transparent text-gray-400 hover:text-gray-300' ?> whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm">
                    Tardiness
                </a>
            </nav>
        </div>

        <?php renderAlert(); ?>
        
        <div class="mb-6 grid grid-cols-1 md:grid-cols-3 gap-4">
            <!-- Search Input -->
            <div class="relative flex-grow">
                <input type="text" id="searchInput" 
                    class="w-full pl-10 pr-4 py-2 bg-gray-700/50 border border-gray-600/50 rounded-lg text-gray-200" 
                    placeholder="Search by employee ID or name..."
                    value="<?= isset($_GET['search']) ? htmlspecialchars($_GET['search']) : '' ?>">
                <div class="absolute left-3 top-2.5 text-gray-400">
                    <i class="fas fa-search"></i>
                </div>
            </div>
            
            <!-- Date Range Filter -->
            <div class="flex gap-2">
                <div class="relative flex-grow">
                    <input type="date" id="dateFrom" 
                        class="w-full px-4 py-2 bg-gray-700/50 border border-gray-600/50 rounded-lg text-gray-200"
                        value="<?= isset($_GET['from']) ? htmlspecialchars($_GET['from']) : '' ?>">
                </div>
                <div class="relative flex-grow">
                    <input type="date" id="dateTo" 
                        class="w-full px-4 py-2 bg-gray-700/50 border border-gray-600/50 rounded-lg text-gray-200"
                        value="<?= isset($_GET['to']) ? htmlspecialchars($_GET['to']) : '' ?>">
                </div>
            </div>
            
            <!-- Department Filter -->
            <div class="relative flex-grow">
                <select id="departmentFilter" 
                        class="w-full px-4 py-2 bg-gray-700/50 border border-gray-600/50 rounded-lg text-gray-200 appearance-none">
                    <option value="">All Departments</option>
                    <?php
                    // Get unique departments from both tables
                    $departments = [];
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
        </div>

        <div id="attendanceTableContainer">
            <?php include 'partials/attendance_table.php'; ?>
        </div>
    </main>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const searchInput = document.getElementById('searchInput');
        const dateFrom = document.getElementById('dateFrom');
        const dateTo = document.getElementById('dateTo');
        const departmentFilter = document.getElementById('departmentFilter');
        let searchTimeout;
        
        // Get current URL parameters
        const urlParams = new URLSearchParams(window.location.search);
        const currentTab = urlParams.get('tab') || 'absenteeism';

        function loadAttendance() {
            const formData = new FormData();
            formData.append('search', searchInput.value);
            formData.append('page', 1); // Always reset to page 1 when filtering
            formData.append('type', currentTab);
            formData.append('date_from', dateFrom.value);
            formData.append('date_to', dateTo.value);
            formData.append('department', departmentFilter.value);

            fetch('partials/attendance_table.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.text())
            .then(data => {
                document.getElementById('attendanceTableContainer').innerHTML = data;
                
                // Update pagination links
                document.querySelectorAll('.pagination-link').forEach(link => {
                    link.addEventListener('click', function(e) {
                        e.preventDefault();
                        const page = this.getAttribute('data-page');
                        updateUrlAndReload(page);
                    });
                });
            });
        }

        function updateUrlAndReload(page = 1) {
            const params = new URLSearchParams();
            params.set('tab', currentTab);
            if (searchInput.value) params.set('search', searchInput.value);
            if (page > 1) params.set('page', page);
            if (dateFrom.value) params.set('from', dateFrom.value);
            if (dateTo.value) params.set('to', dateTo.value);
            if (departmentFilter.value) params.set('dept', departmentFilter.value);
            
            // Update URL without reloading
            history.pushState(null, '', '?' + params.toString());
            
            // Reload data with new page
            const formData = new FormData();
            formData.append('search', searchInput.value);
            formData.append('page', page);
            formData.append('type', currentTab);
            formData.append('date_from', dateFrom.value);
            formData.append('date_to', dateTo.value);
            formData.append('department', departmentFilter.value);

            fetch('partials/attendance_table.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.text())
            .then(data => {
                document.getElementById('attendanceTableContainer').innerHTML = data;
                
                document.querySelectorAll('.pagination-link').forEach(link => {
                    link.addEventListener('click', function(e) {
                        e.preventDefault();
                        const newPage = this.getAttribute('data-page');
                        updateUrlAndReload(newPage);
                    });
                });
            });
        }

        function handleFilterChange() {
            clearTimeout(searchTimeout);
            searchTimeout = setTimeout(() => {
                updateUrlAndReload();
            }, 300);
        }

        // Event listeners
        searchInput.addEventListener('input', handleFilterChange);
        dateFrom.addEventListener('change', handleFilterChange);
        dateTo.addEventListener('change', handleFilterChange);
        departmentFilter.addEventListener('change', handleFilterChange);

        // Handle browser back/forward
        window.addEventListener('popstate', function() {
            const params = new URLSearchParams(window.location.search);
            searchInput.value = params.get('search') || '';
            dateFrom.value = params.get('from') || '';
            dateTo.value = params.get('to') || '';
            departmentFilter.value = params.get('dept') || '';
            
            loadAttendance();
        });

        // Initial load
        loadAttendance();
    });
</script>

<script>
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
                        <div class="flex items-start mb-6">
                            <div class="flex-shrink-0 h-10 w-10 rounded-full bg-blue-600 flex items-center justify-center text-white font-bold">
                                ${initials.substring(0, 2)}
                            </div>
                            <div class="ml-4">
                                <h4 class="text-base font-semibold text-gray-100">${activity.sub_name}</h4>
                                <p class="text-sm text-gray-400">${activity.activity_description}</p>
                            </div>
                        </div>
                        <div class="relative">
                            <div class="absolute -left-2.5 top-0 h-5 w-5 rounded-full bg-blue-600 border-4 border-gray-800"></div>
                            <div class="ml-4">
                                <p class="text-sm text-gray-400">${new Date(activity.activity_time).toLocaleString('en-US', { 
                                    month: 'short', 
                                    day: 'numeric', 
                                    year: 'numeric',
                                    hour: '2-digit',
                                    minute: '2-digit'
                                })}</p>
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

// Close modal when clicking outside or pressing Escape
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
</script>
<?php renderFooter(); ?>