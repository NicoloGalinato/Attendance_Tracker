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
        $table = ($type === 'peripherals') ? 'request_peripherals' : 'headset_tracker';
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
            <a href="<?= $currentTab === 'peripherals' ? 'peripherals_form.php' : 'headset_form.php' ?>?action=create" class="bg-primary-600 hover:bg-primary-700 text-white px-4 py-2 rounded-lg flex items-center">
                <i class="fas fa-plus mr-2"></i> Add New
            </a>
        </div>

        <!-- Tabs -->
        <div class="border-b border-gray-700 mb-6">
            <nav class="-mb-px flex space-x-8">
                <a href="?tab=headset" class="<?= $currentTab === 'headset' ? 'border-primary-500 text-primary-400' : 'border-transparent text-gray-400 hover:text-gray-300' ?> whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm">
                    Headset Tracker
                </a>
                <a href="?tab=peripherals" class="<?= $currentTab === 'peripherals' ? 'border-primary-500 text-primary-400' : 'border-transparent text-gray-400 hover:text-gray-300' ?> whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm">
                    Request Peripherals
                </a>
            </nav>
        </div>

        <?php renderAlert(); ?>
        
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">   
            <!-- Search Input -->
            <div class="relative sm:col-span-2 lg:col-span-1">
                <input type="text" id="searchInput" 
                    class="w-full pl-10 pr-4 py-2 bg-gray-700/50 border border-gray-600/50 rounded-lg text-gray-200" 
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
                    <?php if ($currentTab === 'headset'): ?>
                        <option value="ISSUED" <?= (isset($_GET['status']) && $_GET['status'] === 'ISSUED') ? 'selected' : '' ?>>ISSUED</option>
                        <option value="RETURNED" <?= (isset($_GET['status']) && $_GET['status'] === 'RETURNED') ? 'selected' : '' ?>>RETURNED</option>
                        <option value="DAMAGED" <?= (isset($_GET['status']) && $_GET['status'] === 'DAMAGED') ? 'selected' : '' ?>>DAMAGED</option>
                        <option value="LOST" <?= (isset($_GET['status']) && $_GET['status'] === 'LOST') ? 'selected' : '' ?>>LOST</option>
                    <?php else: ?>
                        <option value="RESOLVED" <?= (isset($_GET['status']) && $_GET['status'] === 'RESOLVED') ? 'selected' : '' ?>>RESOLVED</option>
                        <option value="PENDING" <?= (isset($_GET['status']) && $_GET['status'] === 'PENDING') ? 'selected' : '' ?>>PENDING</option>
                    <?php endif; ?>
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

<script>
document.addEventListener('DOMContentLoaded', function() {
    const searchInput = document.getElementById('searchInput');
    const dateFrom = document.getElementById('dateFrom');
    const dateTo = document.getElementById('dateTo');
    const statusFilter = document.getElementById('statusFilter');
    let searchTimeout;
    const currentTab = '<?= $currentTab ?>';

    // Function to load data with current filters
    function loadFilteredData(page = 1) {
        const formData = new FormData();
        
        // Always include search
        formData.append('search', searchInput.value);
        
        // Add other filters
        formData.append('date_from', dateFrom.value);
        formData.append('date_to', dateTo.value);
        formData.append('status', statusFilter.value);
        formData.append('type', currentTab);
        formData.append('page', page);
        
        fetch('partials/inventory_table.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.text())
        .then(data => {
            document.getElementById('inventoryTableContainer').innerHTML = data;
            setupPaginationLinks();
        });
    }

    function setupPaginationLinks() {
        document.querySelectorAll('.pagination-link').forEach(link => {
            link.addEventListener('click', function(e) {
                e.preventDefault();
                const page = this.getAttribute('data-page');
                loadFilteredData(page);
            });
        });
    }
            
    // Event listeners for filters
    searchInput.addEventListener('input', () => {
        clearTimeout(searchTimeout);
        searchTimeout = setTimeout(() => {
            loadFilteredData();
        }, 300);
    });
    
    dateFrom.addEventListener('change', loadFilteredData);
    dateTo.addEventListener('change', loadFilteredData);
    statusFilter.addEventListener('change', loadFilteredData);

    // Initial load
    loadFilteredData();
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

// Return equipment function
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