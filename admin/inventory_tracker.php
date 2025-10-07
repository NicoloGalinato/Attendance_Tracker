<?php
require_once '../includes/config.php';
require_once '../includes/functions.php';

if (!isLoggedIn() || !isAdmin()) {
    redirect(BASE_URL);
}

updateLastActivity();

// Handle deletion
if (isset($_GET['delete'])) {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    
    $id = (int)$_GET['delete'];
    $type = isset($_GET['type']) ? $_GET['type'] : 'headset';
    
    $requiredPassword = "SLT@2025";
    $providedPassword = $_POST['delete_password'] ?? '';
    
    if (empty($providedPassword) || $providedPassword !== $requiredPassword) {
        $_SESSION['error'] = "Incorrect or missing password for deletion";
        redirect('inventory_tracker.php?tab=' . $type);
        exit();
    }
    
    try {
        if ($type === 'peripherals') {
            $table = 'request_peripherals';
        } elseif ($type === 'headset_inventory') {
            $table = 'headset_inventory';
        } else {
            $table = 'headset_tracker';
            
            $getCNo = $pdo->prepare("SELECT c_no, status FROM headset_tracker WHERE id = ?");
            $getCNo->execute([$id]);
            $headsetRecord = $getCNo->fetch();
            
            if ($headsetRecord && !empty($headsetRecord['c_no']) && $headsetRecord['status'] === 'PENDING') {
                $updateInventory = $pdo->prepare("UPDATE headset_inventory SET status = 'AVAILABLE' WHERE c_no = ?");
                $updateInventory->execute([$headsetRecord['c_no']]);
            }
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
        $stmt = $pdo->prepare("SELECT * FROM request_peripherals WHERE id = ?");
        $stmt->execute([$id]);
        $record = $stmt->fetch();
        
        if ($record) {
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
    
    $userStmt = $pdo->prepare("SELECT sub_name FROM users WHERE id = ?");
    $userStmt->execute([$_SESSION['user_id']]);
    $user = $userStmt->fetch();
    $sub_name = $user['sub_name'];
    
    try {
        $getCNo = $pdo->prepare("SELECT c_no FROM headset_tracker WHERE id = ?");
        $getCNo->execute([$return_id]);
        $headsetRecord = $getCNo->fetch();
        $c_no = $headsetRecord['c_no'];
        
        $stmt = $pdo->prepare("UPDATE headset_tracker SET received_by = ?, return_time = CONVERT_TZ(CURTIME(), '+00:00', '+08:00'), return_date = CURDATE(), status = 'RETURNED', remarks = ? WHERE id = ?");
        $stmt->execute([$sub_name, $remarks, $return_id]);

        if (!empty($c_no)) {
            $updateInventory = $pdo->prepare("UPDATE headset_inventory SET status = 'AVAILABLE' WHERE c_no = ?");
            $updateInventory->execute([$c_no]);
        }

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

<!-- Minimal Loading Screen - Only for initial page load -->
<div id="initialLoading" class="fixed inset-0 bg-gray-900 flex items-center justify-center z-50 transition-opacity duration-300">
    <div class="text-center">
        <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-blue-500 mx-auto mb-4"></div>
        <p class="text-white text-lg">Loading Inventory...</p>
    </div>
</div>

<div class="pt-2 min-h-screen">
    <main class="p-6">
        <div class="flex justify-between items-center mb-6">
            <h1 class="text-2xl font-bold text-white">Inventory Tracker</h1>
            <?php if ($currentTab === 'headset_inventory'): ?>
                <a href="headset_inventory_form.php?action=create" class="bg-primary-600 hover:bg-primary-700 text-white px-4 py-2 rounded-lg flex items-center transition-colors duration-200">
                    <i class="fas fa-plus mr-2"></i> Add New Headset
                </a>
            <?php else: ?>
                <a href="<?= $currentTab === 'peripherals' ? 'peripherals_form.php' : 'headset_form.php' ?>?action=create" class="bg-primary-600 hover:bg-primary-700 text-white px-4 py-2 rounded-lg flex items-center transition-colors duration-200">
                    <i class="fas fa-plus mr-2"></i> Add New
                </a>
            <?php endif; ?>
        </div>

        <!-- Tabs -->
        <div class="border-b border-gray-700 mb-6">
            <nav class="-mb-px flex space-x-8">
                <a href="?tab=headset" class="<?= $currentTab === 'headset' ? 'border-primary-500 text-primary-400' : 'border-transparent text-gray-400 hover:text-gray-300' ?> whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm transition-colors duration-200">
                    Headset Tracker
                </a>
                <a href="?tab=headset_inventory" class="<?= $currentTab === 'headset_inventory' ? 'border-primary-500 text-primary-400' : 'border-transparent text-gray-400 hover:text-gray-300' ?> whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm transition-colors duration-200">
                    Headset Inventory
                </a>
                <a href="?tab=peripherals" class="<?= $currentTab === 'peripherals' ? 'border-primary-500 text-primary-400' : 'border-transparent text-gray-400 hover:text-gray-300' ?> whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm transition-colors duration-200" style="display:none;">
                    Request Peripherals
                </a>
            </nav>
        </div>

        <?php renderAlert(); ?>
        
        <!-- Wrap your filters in a form that preserves all filters -->
        <form method="get" action="inventory_tracker.php" id="mainFilterForm">
            <input type="hidden" name="tab" value="<?= $currentTab ?>">
            <?php if (!empty($_GET['search'])): ?>
                <input type="hidden" name="search" value="<?= htmlspecialchars($_GET['search']) ?>">
            <?php endif; ?>
            <?php if (!empty($_GET['from'])): ?>
                <input type="hidden" name="from", value="<?= htmlspecialchars($_GET['from']) ?>">
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
                    class="w-full pl-10 pr-4 py-2 bg-gray-700/50 border border-gray-600/50 rounded-lg text-gray-200 placeholder-gray-400 focus:border-blue-500 focus:ring-1 focus:ring-blue-500 transition-colors duration-200" 
                    placeholder="<?= $currentTab === 'headset_inventory' ? 'Search by serial no...' : 'Search by employee ID or name...' ?>"
                    value="<?= isset($_GET['search']) ? htmlspecialchars($_GET['search']) : '' ?>">
                <div class="absolute left-3 top-2.5 text-gray-400">
                    <i class="fas fa-search"></i>
                </div>
            </div>
            
            <!-- Date Range Filter -->
            <div class="flex gap-2 sm:col-span-2 lg:col-span-1">
                <div class="relative flex-grow">
                    <input type="date" id="dateFrom" 
                        class="w-full px-4 py-2 bg-gray-700/50 border border-gray-600/50 rounded-lg text-gray-200 focus:border-blue-500 focus:ring-1 focus:ring-blue-500 transition-colors duration-200"
                        value="<?= isset($_GET['from']) ? htmlspecialchars($_GET['from']) : '' ?>">
                </div>
            </div>
            <div class="flex gap-2 sm:col-span-2 lg:col-span-1">
                <div class="relative flex-grow">
                    <input type="date" id="dateTo" 
                        class="w-full px-4 py-2 bg-gray-700/50 border border-gray-600/50 rounded-lg text-gray-200 focus:border-blue-500 focus:ring-1 focus:ring-blue-500 transition-colors duration-200"
                        value="<?= isset($_GET['to']) ? htmlspecialchars($_GET['to']) : '' ?>">
                </div>
            </div>
            
            <!-- Status Filter -->
            <div class="relative sm:col-span-1 lg:col-span-1">
                <select id="statusFilter" 
                        class="w-full px-4 py-2 bg-gray-700/50 border border-gray-600/50 rounded-lg text-gray-200 focus:border-blue-500 focus:ring-1 focus:ring-blue-500 transition-colors duration-200 appearance-none">
                    <option value="">ALL STATUS</option>
                    <?php
                    $statuses = [];
                    if ($currentTab === 'headset') {
                        $stmt = $pdo->query("SELECT DISTINCT status FROM headset_tracker WHERE status IS NOT NULL AND status != ''");
                        $statuses = $stmt->fetchAll(PDO::FETCH_COLUMN);
                    } elseif ($currentTab === 'peripherals') {
                        $stmt = $pdo->query("SELECT DISTINCT status FROM request_peripherals WHERE status IS NOT NULL AND status != ''");
                        $statuses = $stmt->fetchAll(PDO::FETCH_COLUMN);
                    } elseif ($currentTab === 'headset_inventory') {
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

        <!-- Inventory Table Container -->
        <div id="inventoryTableContainer" class="transition-opacity duration-200">
            <?php include 'partials/inventory_table.php'; ?>
        </div>
    </main>
</div>

<!-- Return with Remarks Modal -->
<div id="returnModal" class="hidden fixed inset-0 bg-gray-900/50 backdrop-blur-sm flex items-center justify-center z-50 transition-opacity duration-200">
    <div class="bg-gray-800 rounded-lg border border-gray-700 shadow-xl w-full max-w-md transform transition-transform duration-200 scale-95">
        <div class="px-6 py-6">
            <div class="flex justify-between items-center mb-4">
                <h3 class="text-lg font-bold text-gray-100">Mark as Returned</h3>
                <button type="button" onclick="closeReturnModal()" class="text-gray-400 hover:text-white">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <form id="returnForm" method="post" action="inventory_tracker.php">
                <input type="hidden" name="return_id" id="returnId">
                <div class="mb-4">
                    <label for="remarks" class="block text-sm font-medium text-gray-300 mb-2">Remarks:</label>
                    <textarea name="remarks" id="remarks" rows="3" class="w-full px-3 py-2 bg-gray-700 border border-gray-600 rounded-md text-gray-200 focus:border-blue-500 focus:ring-1 focus:ring-blue-500 transition-colors duration-200" placeholder="Enter remarks about the returned equipment" required></textarea>
                </div>
                <div class="flex justify-end space-x-3">
                    <button type="button" onclick="closeReturnModal()" class="px-4 py-2 bg-gray-600 text-gray-100 rounded-md hover:bg-gray-500 transition-colors duration-200">
                        Cancel
                    </button>
                    <button type="submit" name="return_with_remarks" class="px-4 py-2 bg-primary-600 text-white rounded-md hover:bg-primary-500 transition-colors duration-200">
                        Mark as Returned
                    </button>
                </div>
            </form>
        </div>
    </div>
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
                }, 200);
            }
        }, 500);
    }
}

// Initialize simple loading manager
const simpleLoadingManager = new SimpleLoadingManager();

// Optimized Inventory Manager - No loading on search/filter
class InventoryManager {
    constructor() {
        this.searchTimeout = null;
        this.debounceDelay = 400;
        this.currentPage = 1;
        this.currentTab = 'headset';
        this.filters = {
            search: '',
            date_from: '',
            date_to: '',
            status: ''
        };
        this.isLoading = false;
        this.init();
    }

    init() {
        this.bindEvents();
        this.loadInitialData();
    }

    bindEvents() {
        const searchInput = document.getElementById('searchInput');
        const dateFrom = document.getElementById('dateFrom');
        const dateTo = document.getElementById('dateTo');
        const statusFilter = document.getElementById('statusFilter');

        searchInput.addEventListener('input', () => this.handleFilterChange('search', searchInput.value));
        if (dateFrom) dateFrom.addEventListener('change', () => this.handleFilterChange('date_from', dateFrom.value));
        if (dateTo) dateTo.addEventListener('change', () => this.handleFilterChange('date_to', dateTo.value));
        statusFilter.addEventListener('change', () => this.handleFilterChange('status', statusFilter.value));

        // Tab change handling
        document.querySelectorAll('nav a[href^="?tab="]').forEach(tab => {
            tab.addEventListener('click', (e) => {
                e.preventDefault();
                const url = new URL(tab.href);
                const tabParam = url.searchParams.get('tab');
                this.switchTab(tabParam);
            });
        });

        // Keyboard shortcuts
        document.addEventListener('keydown', (e) => {
            if (e.ctrlKey || e.metaKey) {
                switch(e.key) {
                    case 'f':
                        e.preventDefault();
                        searchInput.focus();
                        break;
                    case 'q':
                        e.preventDefault();
                        const addUrl = this.currentTab === 'headset_inventory' ? 
                            'headset_inventory_form.php?action=create' : 
                            `${this.currentTab === 'peripherals' ? 'peripherals_form.php' : 'headset_form.php'}?action=create`;
                        window.location.href = addUrl;
                        break;
                }
            }
        });
    }

    handleFilterChange(type, value) {
        this.filters[type] = value;
        
        clearTimeout(this.searchTimeout);
        this.searchTimeout = setTimeout(() => {
            this.loadInventoryData(1);
            this.updateUrl(1);
        }, this.debounceDelay);
    }

    switchTab(tab) {
        this.currentTab = tab;
        this.currentPage = 1;
        this.resetFilters();
        
        // Update active tab styling
        document.querySelectorAll('nav a').forEach(link => {
            link.classList.remove('border-primary-500', 'text-primary-400');
            link.classList.add('border-transparent', 'text-gray-400');
        });
        
        const activeTab = document.querySelector(`nav a[href="?tab=${tab}"]`);
        if (activeTab) {
            activeTab.classList.add('border-primary-500', 'text-primary-400');
            activeTab.classList.remove('border-transparent', 'text-gray-400');
        }
        
        this.loadInventoryData(1);
        this.updateUrl(1);
        history.pushState(null, '', `?tab=${tab}`);
    }

    resetFilters() {
        this.filters = {
            search: '',
            date_from: '',
            date_to: '',
            status: ''
        };
        
        document.getElementById('searchInput').value = '';
        const dateFrom = document.getElementById('dateFrom');
        const dateTo = document.getElementById('dateTo');
        const statusFilter = document.getElementById('statusFilter');
        
        if (dateFrom) dateFrom.value = '';
        if (dateTo) dateTo.value = '';
        if (statusFilter) statusFilter.value = '';
    }

    async loadInventoryData(page = 1) {
        if (this.isLoading) return;
        
        this.currentPage = page;
        this.isLoading = true;

        try {
            const formData = new FormData();
            formData.append('search', this.filters.search);
            formData.append('date_from', this.filters.date_from);
            formData.append('date_to', this.filters.date_to);
            formData.append('status', this.filters.status);
            formData.append('type', this.currentTab);
            formData.append('page', page);

            const response = await fetch('partials/inventory_table.php', {
                method: 'POST',
                body: formData,
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            });

            if (!response.ok) throw new Error('Network response was not ok');

            const data = await response.text();
            document.getElementById('inventoryTableContainer').innerHTML = data;
            
            this.rebindEvents();

        } catch (error) {
            console.error('Error loading inventory:', error);
            this.showError('Error loading data. Please try again.');
        } finally {
            this.isLoading = false;
        }
    }

    rebindEvents() {
        // Rebind pagination events
        document.querySelectorAll('.pagination-link').forEach(link => {
            link.addEventListener('click', (e) => {
                e.preventDefault();
                const page = parseInt(link.getAttribute('data-page'));
                this.loadInventoryData(page);
                this.updateUrl(page);
            });
        });
    }

    updateUrl(page) {
        const params = new URLSearchParams();
        params.append('tab', this.currentTab);
        
        Object.entries(this.filters).forEach(([key, value]) => {
            if (value) params.append(key, value);
        });
        
        if (page > 1) params.append('page', page);
        
        const queryString = params.toString();
        const newUrl = queryString ? `?${queryString}` : window.location.pathname;
        history.replaceState(null, '', newUrl);
    }

    loadInitialData() {
        const urlParams = new URLSearchParams(window.location.search);
        
        this.currentTab = urlParams.get('tab') || 'headset';
        this.filters = {
            search: urlParams.get('search') || '',
            date_from: urlParams.get('from') || '',
            date_to: urlParams.get('to') || '',
            status: urlParams.get('status') || ''
        };
        
        const initialPage = parseInt(urlParams.get('page')) || 1;
        
        // Set initial values
        document.getElementById('searchInput').value = this.filters.search;
        const dateFrom = document.getElementById('dateFrom');
        const dateTo = document.getElementById('dateTo');
        const statusFilter = document.getElementById('statusFilter');
        
        if (dateFrom) dateFrom.value = this.filters.date_from;
        if (dateTo) dateTo.value = this.filters.date_to;
        if (statusFilter) statusFilter.value = this.filters.status;
        
        // Set active tab
        document.querySelectorAll('nav a').forEach(link => {
            link.classList.remove('border-primary-500', 'text-primary-400');
            link.classList.add('border-transparent', 'text-gray-400');
        });
        
        const activeTab = document.querySelector(`nav a[href="?tab=${this.currentTab}"]`);
        if (activeTab) {
            activeTab.classList.add('border-primary-500', 'text-primary-400');
            activeTab.classList.remove('border-transparent', 'text-gray-400');
        }
        
        this.loadInventoryData(initialPage);
    }

    showError(message) {
        document.getElementById('inventoryTableContainer').innerHTML = `
            <div class="bg-red-900/20 border border-red-700 rounded-lg p-6 text-center">
                <i class="fas fa-exclamation-triangle text-red-400 text-2xl mb-3"></i>
                <p class="text-red-300 mb-4">${message}</p>
                <button onclick="inventoryManager.loadInventoryData(1)" 
                        class="bg-red-600 hover:bg-red-500 text-white px-4 py-2 rounded-lg transition-colors duration-200">
                    <i class="fas fa-redo mr-2"></i>Try Again
                </button>
            </div>
        `;
    }
}

// Delete Modal Functions
function showDeleteModal(recordId, recordType = '') {
    const modal = document.createElement('div');
    modal.id = 'deleteModal';
    modal.className = 'fixed inset-0 bg-gray-900/50 backdrop-blur-sm flex items-center justify-center z-50 transition-opacity duration-200';
    modal.innerHTML = `
        <div class="bg-gray-800 rounded-lg border border-gray-700 shadow-xl w-full max-w-md transform transition-transform duration-200 scale-95">
            <div class="px-6 py-6">
                <div class="flex justify-between items-center mb-4">
                    <h3 class="text-lg font-bold text-gray-100">Confirm Deletion</h3>
                    <button type="button" onclick="closeDeleteModal()" class="text-gray-400 hover:text-white">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
                <p class="text-gray-300 mb-4">Are you sure you want to delete this record?</p>
                <form method="post" action="${window.location.pathname}?delete=${recordId}${recordType ? '&type=' + recordType : ''}" class="space-y-4">
                    <div>
                        <label for="delete_password" class="block text-sm font-medium text-gray-300 mb-1">To confirm please enter the KEY:</label>
                        <input type="password" name="delete_password" id="delete_password" 
                               class="w-full px-3 py-2 bg-gray-700 border border-gray-600 rounded-md text-gray-200 focus:border-red-500 focus:ring-1 focus:ring-red-500 transition-colors duration-200" 
                               required autocomplete="off" placeholder="Enter deletion key">
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

// Return with remarks modal functions
function showReturnModal(recordId) {
    document.getElementById('returnId').value = recordId;
    document.getElementById('returnModal').classList.remove('hidden');
    setTimeout(() => {
        document.getElementById('returnModal').querySelector('div').classList.remove('scale-95');
    }, 10);
}

function closeReturnModal() {
    const modal = document.getElementById('returnModal');
    modal.querySelector('div').classList.add('scale-95');
    setTimeout(() => {
        modal.classList.add('hidden');
        document.getElementById('returnForm').reset();
    }, 150);
}

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

// Global event listeners
document.addEventListener('click', function(e) {
    if (e.target === document.getElementById('deleteModal')) closeDeleteModal();
    if (e.target === document.getElementById('returnModal')) closeReturnModal();
});

document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        if (document.getElementById('deleteModal')) closeDeleteModal();
        if (document.getElementById('returnModal')) closeReturnModal();
    }
});

// Form submission loading states
document.addEventListener('DOMContentLoaded', function() {
    const forms = document.querySelectorAll('form');
    forms.forEach(form => {
        if (!form.action.includes('delete') && !form.id.includes('returnForm')) {
            form.addEventListener('submit', function() {
                const submitBtn = this.querySelector('button[type="submit"]');
                if (submitBtn && !submitBtn.classList.contains('bg-red-600')) {
                    submitBtn.disabled = true;
                    submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i> Processing...';
                    submitBtn.classList.add('opacity-50', 'cursor-not-allowed');
                }
            });
        }
    });
});

// Initialize inventory manager
const inventoryManager = new InventoryManager();
</script>

<?php renderFooter(); ?>