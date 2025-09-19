<?php
require_once '../includes/config.php';
require_once '../includes/functions.php';

if (!isLoggedIn() || !isAdmin()) {
    redirect(BASE_URL);
}

updateLastActivity();

// Handle deletion
if (isset($_GET['delete'])) {
    // Start session if not already started
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    
    $id = (int)$_GET['delete'];
    $type = isset($_GET['type']) ? $_GET['type'] : 'headset';
    
    // Check if password is provided and correct
    $requiredPassword = "SLT@2025";
    $providedPassword = $_POST['delete_password'] ?? '';
    
    if (empty($providedPassword) || $providedPassword !== $requiredPassword) {
        $_SESSION['error'] = "Incorrect or missing password for deletion";
        redirect('inventory_tracker.php?tab=' . $type);
        exit();
    }
    
    try {
        // Determine which table to delete from based on type
        if ($type === 'peripherals') {
            $table = 'request_peripherals';
        } elseif ($type === 'headset_inventory') {
            $table = 'headset_inventory';
        } else {
            $table = 'headset_tracker';
        }
        
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
    
    redirect('inventory_tracker.php?tab=' . $type);
}

// Handle email sending for peripherals
if (isset($_GET['send_email'])) {
    $id = (int)$_GET['send_email'];
    $type = 'peripherals';
    
    try {
        // Get record data
        $stmt = $pdo->prepare("SELECT * FROM request_peripherals WHERE id = ?");
        $stmt->execute([$id]);
        $record = $stmt->fetch();
        
        if ($record) {
            // Update the record to mark as sent
            $updateStmt = $pdo->prepare("UPDATE request_peripherals SET email_sent = 1, email_sent_at = CONVERT_TZ(NOW(), 'SYSTEM', 'Asia/Manila') WHERE id = ?");
            $updateStmt->execute([$id]);
            
            $_SESSION['success'] = "Email sent successfully!";
        } else {
            $_SESSION['error'] = "Record not found";
        }
    } catch (PDOException $e) {
        $_SESSION['error'] = "Error sending email: " . $e->getMessage();
    }
    
    redirect('inventory_tracker.php?tab=' . $type);
}

// Handle return with remarks
if (isset($_POST['return_with_remarks'])) {
    $return_id = (int)$_POST['return_id'];
    $remarks = trim(strtoupper($_POST['remarks']));
    
    // Get current user's sub_name
    $userStmt = $pdo->prepare("SELECT sub_name FROM users WHERE id = ?");
    $userStmt->execute([$_SESSION['user_id']]);
    $user = $userStmt->fetch();
    $sub_name = $user['sub_name'];
    
    try {
        $stmt = $pdo->prepare("UPDATE headset_tracker SET received_by = ?, return_time = CONVERT_TZ(CURTIME(), '+00:00', '+08:00'), return_date = CURDATE(), status = 'RETURNED', remarks = ? WHERE id = ?");
        $stmt->execute([$sub_name, $remarks, $return_id]);

        $_SESSION['success'] = "Headset marked as returned successfully!";
        redirect('inventory_tracker.php?tab=headset');
    } catch (PDOException $e) {
        $_SESSION['error'] = "Error updating record: " . $e->getMessage();
        redirect('inventory_tracker.php?tab=headset');
    }
}

require_once '../components/layout.php';
renderHead('Inventory Tracker');
renderNavbar();
renderSidebar('inventory');

$currentTab = isset($_GET['tab']) ? $_GET['tab'] : 'headset';
?>

<div class="pt-2 min-h-screen">
    <main class="p-6">
        <div class="flex justify-between items-center mb-6">
            <h1 class="text-2xl font-bold">Inventory Tracker</h1>
            <?php if ($currentTab === 'headset_inventory'): ?>
                <a href="headset_inventory_form.php?action=create" class="bg-primary-600 hover:bg-primary-700 text-white px-4 py-2 rounded-lg flex items-center">
                    <i class="fas fa-plus mr-2"></i> Add New Headset
                </a>
            <?php else: ?>
                <a href="<?= $currentTab === 'peripherals' ? 'peripherals_form.php' : 'headset_form.php' ?>?action=create" class="bg-primary-600 hover:bg-primary-700 text-white px-4 py-2 rounded-lg flex items-center">
                    <i class="fas fa-plus mr-2"></i> Add New
                </a>
            <?php endif; ?>
        </div>

        <!-- Tabs -->
        <div class="border-b border-gray-700 mb-6">
            <nav class="-mb-px flex space-x-8">
                <a href="?tab=headset" class="<?= $currentTab === 'headset' ? 'border-primary-500 text-primary-400' : 'border-transparent text-gray-400 hover:text-gray-300' ?> whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm">
                    Headset Tracker
                </a>
                <a href="?tab=headset_inventory" class="<?= $currentTab === 'headset_inventory' ? 'border-primary-500 text-primary-400' : 'border-transparent text-gray-400 hover:text-gray-300' ?> whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm">
                    Headset Inventory
                </a>
                <a href="?tab=peripherals" class="<?= $currentTab === 'peripherals' ? 'border-primary-500 text-primary-400' : 'border-transparent text-gray-400 hover:text-gray-300' ?> whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm">
                    Request Peripherals
                </a>
            </nav>
        </div>

        <?php renderAlert(); ?>
        
        <!-- Wrap your filters in a form that preserves all filters -->
        <form method="get" action="inventory_tracker.php" id="mainFilterForm">
            <input type="hidden" name="tab" value="<?= $currentTab ?>">
            <!-- Preserve existing filters -->
            <?php if (!empty($_GET['search'])): ?>
                <input type="hidden" name="search" value="<?= htmlspecialchars($_GET['search']) ?>">
            <?php endif; ?>
            <?php if (!empty($_GET['from'])): ?>
                <input type="hidden" name="from" value="<?= htmlspecialchars($_GET['from']) ?>">
            <?php endif; ?>
            <?php if (!empty($_GET['to'])): ?>
                <input type="hidden" name="to", value="<?= htmlspecialchars($_GET['to']) ?>">
            <?php endif; ?>
            <?php if (!empty($_GET['status'])): ?>
                <input type="hidden" name="status", value="<?= htmlspecialchars($_GET['status']) ?>">
            <?php endif; ?>
        </form>
        
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">   
            <!-- Search Input -->
            <div class="relative sm:col-span-2 lg:col-span-1">
                <input type="text" id="searchInput" 
                    class="w-full pl-10 pr-4 py-2 bg-gray-700/50 border border-gray-600/50 rounded-lg text-gray-200" 
                    placeholder="<?= $currentTab === 'headset_inventory' ? 'Search by serial no...' : 'Search by employee ID or name...' ?>"
                    value="<?= isset($_GET['search']) ? htmlspecialchars($_GET['search']) : '' ?>">
                <div class="absolute left-3 top-2.5 text-gray-400">
                    <i class="fas fa-search"></i>
                </div>
            </div>
            
            <!-- Date Range Filter (only show for headset and peripherals tabs) -->
            <div class="flex gap-2 sm:col-span-2 lg:col-span-1">
                <div class="relative flex-grow">
                    <input type="date" id="dateFrom" 
                        class="w-full px-4 py-2 bg-gray-700/50 border border-gray-600/50 rounded-lg text-gray-200"
                        value="<?= isset($_GET['from']) ? htmlspecialchars($_GET['from']) : '' ?>">
                </div>
            </div>
            <div class="flex gap-2 sm:col-span-2 lg:col-span-1">
                <div class="relative flex-grow">
                    <input type="date" id="dateTo" 
                        class="w-full px-4 py-2 bg-gray-700/50 border border-gray-600/50 rounded-lg text-gray-200"
                        value="<?= isset($_GET['to']) ? htmlspecialchars($_GET['to']) : '' ?>">
                </div>
            </div>
            
            <!-- Status Filter -->
            <div class="relative sm:col-span-1 lg:col-span-1">
                <select id="statusFilter" 
                        class="w-full px-4 py-2 bg-gray-700/50 border border-gray-600/50 rounded-lg text-gray-200 appearance-none">
                    <option value="">ALL STATUS</option>
                    <?php
                    // Get available statuses based on current tab
                    $statuses = [];
                    if ($currentTab === 'headset') {
                        $stmt = $pdo->query("SELECT DISTINCT status FROM headset_tracker WHERE status IS NOT NULL AND status != ''");
                        $statuses = $stmt->fetchAll(PDO::FETCH_COLUMN);
                    } elseif ($currentTab === 'peripherals') {
                        $stmt = $pdo->query("SELECT DISTINCT status FROM request_peripherals WHERE status IS NOT NULL AND status != ''");
                        $statuses = $stmt->fetchAll(PDO::FETCH_COLUMN);
                    } elseif ($currentTab === 'headset_inventory') {
                        // For headset inventory, we might have different statuses or none
                        $stmt = $pdo->query("SELECT DISTINCT status FROM headset_inventory WHERE status IS NOT NULL AND status != ''");
                        $statuses = $stmt->fetchAll(PDO::FETCH_COLUMN);
                    }
                    
                    $statuses = array_unique($statuses);
                    foreach ($statuses as $status) {
                        $selected = (isset($_GET['status']) && $_GET['status'] === $status) ? 'selected' : '';
                        echo '<option value="'.htmlspecialchars($status).'" '.$selected.'>'.htmlspecialchars($status).'</option>';
                    }
                    ?>
                </select>
                <div class="absolute right-3 top-2.5 text-gray-400 pointer-events-none">
                    <i class="fas fa-chevron-down"></i>
                </div>
            </div>
        </div>

        <div id="inventoryTableContainer">
            <?php include 'partials/inventory_table.php'; ?>
        </div>
    </main>
</div>

<!-- Return with Remarks Modal -->
<div id="returnModal" class="hidden fixed inset-0 bg-gray-900 bg-opacity-50 flex items-center justify-center z-50">
    <div class="bg-gray-800 rounded-lg border border-gray-700 shadow-xl w-full max-w-md">
        <div class="px-6 py-6">
            <h3 class="text-lg font-bold text-gray-100 mb-4">Mark as Returned</h3>
            <form id="returnForm" method="post" action="inventory_tracker.php">
                <input type="hidden" name="return_id" id="returnId">
                <div class="mb-4">
                    <label for="remarks" class="block text-sm font-medium text-gray-300 mb-2">Remarks:</label>
                    <textarea name="remarks" id="remarks" rows="3" class="w-full px-3 py-2 bg-gray-700 border border-gray-600 rounded-md text-gray-200" placeholder="Enter remarks about the returned equipment"></textarea>
                </div>
                <div class="flex justify-end space-x-3">
                    <button type="button" onclick="closeReturnModal()" class="px-4 py-2 bg-gray-600 text-gray-100 rounded-md hover:bg-gray-500">
                        Cancel
                    </button>
                    <button type="submit" name="return_with_remarks" class="px-4 py-2 bg-primary-600 text-white rounded-md hover:bg-primary-500">
                        Mark as Returned
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const searchInput = document.getElementById('searchInput');
    const dateFrom = document.getElementById('dateFrom');
    const dateTo = document.getElementById('dateTo');
    const statusFilter = document.getElementById('statusFilter');
    let searchTimeout;

    // Function to load data with current filters
    function loadFilteredData(page = 1) {
        const urlParams = new URLSearchParams(window.location.search);
        const formData = new FormData();
        
        // Always include search
        formData.append('search', searchInput.value);
        
        // Add other filters
        formData.append('date_from', dateFrom ? dateFrom.value : '');
        formData.append('date_to', dateTo ? dateTo.value : '');
        formData.append('status', statusFilter.value);
        formData.append('type', urlParams.get('tab') || 'headset');
        formData.append('page', page);
        
        fetch('partials/inventory_table.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.text())
        .then(data => {
            document.getElementById('inventoryTableContainer').innerHTML = data;
            setupPaginationLinks();
        })
        .catch(error => {
            console.error('Error:', error);
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
            
    // Dropdown filter handler
    function handleDropdownFilter() {
        const urlParams = new URLSearchParams(window.location.search);
        
        // Set dropdown filters
        if (searchInput.value) {
            urlParams.set('search', searchInput.value);
        } else {
            urlParams.delete('search');
        }
        
        if (dateFrom && dateFrom.value) {
            urlParams.set('from', dateFrom.value);
        } else {
            urlParams.delete('from');
        }
        
        if (dateTo && dateTo.value) {
            urlParams.set('to', dateTo.value);
        } else {
            urlParams.delete('to');
        }
        
        if (statusFilter.value) {
            urlParams.set('status', statusFilter.value);
        } else {
            urlParams.delete('status');
        }
        
        history.pushState(null, '', '?' + urlParams.toString());
        loadFilteredData();
    }

    // Initialize event listeners
    searchInput.addEventListener('input', () => {
        clearTimeout(searchTimeout);
        searchTimeout = setTimeout(() => {
            handleDropdownFilter();
        }, 300);
    });
    
    if (dateFrom) dateFrom.addEventListener('change', handleDropdownFilter);
    if (dateTo) dateTo.addEventListener('change', handleDropdownFilter);
    statusFilter.addEventListener('change', handleDropdownFilter);

    // Initial load
    const urlParams = new URLSearchParams(window.location.search);
    const initialPage = urlParams.get('page') || 1;
    loadFilteredData(initialPage);
});

// Delete confirmation modal
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

// Return with remarks modal functions
function showReturnModal(recordId) {
    document.getElementById('returnId').value = recordId;
    document.getElementById('returnModal').classList.remove('hidden');
}

function closeReturnModal() {
    document.getElementById('returnModal').classList.add('hidden');
    document.getElementById('returnForm').reset();
}

// Close modal when clicking outside
document.addEventListener('click', function(e) {
    if (e.target === document.getElementById('returnModal')) {
        closeReturnModal();
    }
});

// Return equipment function (for direct return without remarks)
function returnEquipment(recordId) {
    if (!confirm('Are you sure you want to mark this equipment as returned?')) {
        return;
    }
    
    const formData = new FormData();
    formData.append('action', 'return_equipment');
    formData.append('record_id', recordId);
    
    fetch('../includes/update_inventory.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert('Equipment marked as returned successfully!');
            location.reload();
        } else {
            alert('Error: ' + data.message);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('An error occurred while updating the record.');
    });
}

// Resolve request function
function resolveRequest(recordId) {
    if (!confirm('Are you sure you want to mark this request as resolved?')) {
        return;
    }
    
    const formData = new FormData();
    formData.append('action', 'resolve_request');
    formData.append('record_id', recordId);
    
    fetch('../includes/update_inventory.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert('Request marked as resolved successfully!');
            location.reload();
        } else {
            alert('Error: ' + data.message);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('An error occurred while updating the record.');
    });
}
</script>

<?php renderFooter(); ?>