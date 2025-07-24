<?php
require_once '../includes/config.php';
require_once '../includes/functions.php';

if (!isLoggedIn() || !isAdmin()) {
    redirect(BASE_URL);
}

updateLastActivity();

// Handle user deletion
if (isset($_GET['delete'])) {
    $userId = (int)$_GET['delete'];
    $type = isset($_GET['type']) ? $_GET['type'] : 'users';
    
    try {
        $table = ($type === 'management') ? 'management' : (($type === 'operations') ? 'operations_managers' : 'users');
        $stmt = $pdo->prepare("DELETE FROM $table WHERE id = ?");
        $stmt->execute([$userId]);
        
        if ($stmt->rowCount() > 0) {
            $_SESSION['success'] = "Record deleted successfully!";
        } else {
            $_SESSION['error'] = "Record not found";
        }
    } catch (PDOException $e) {
        $_SESSION['error'] = "Error deleting record: " . $e->getMessage();
    }
    
    redirect('users.php?tab=' . $type);
}

// Handle status toggle
if (isset($_GET['toggle_status'])) {
    $userId = (int)$_GET['toggle_status'];
    $type = isset($_GET['type']) ? $_GET['type'] : 'users';
    
    try {
        // Get current status
        $table = ($type === 'management') ? 'management' : (($type === 'operations') ? 'operations_managers' : 'users');
        $stmt = $pdo->prepare("SELECT is_active FROM $table WHERE id = ?");
        $stmt->execute([$userId]);
        $currentStatus = $stmt->fetchColumn();
        
        // Toggle status
        $newStatus = $currentStatus ? 0 : 1;
        $stmt = $pdo->prepare("UPDATE $table SET is_active = ? WHERE id = ?");
        $stmt->execute([$newStatus, $userId]);
        
        $_SESSION['success'] = "Record status updated successfully!";
    } catch (PDOException $e) {
        $_SESSION['error'] = "Error updating record status: " . $e->getMessage();
    }
    
    redirect('users.php?tab=' . $type);
    
}

require_once '../components/layout.php';
renderHead('Manage SLT');
renderNavbar();
renderSidebar('users');

$currentTab = isset($_GET['tab']) ? $_GET['tab'] : 'users';
?>

<div class="md:ml-64 pt-2 min-h-screen">
    <main class="p-6">
        <div class="flex justify-between items-center mb-6">
            <h1 class="text-2xl font-bold">Manage Team Members</h1>
            <a href="profile.php?action=create&type=<?= $currentTab ?>" class="bg-primary-600 hover:bg-primary-700 text-white px-4 py-2 rounded-lg flex items-center">
                <i class="fas fa-plus mr-2"></i> Add New
            </a>
        </div>

        <!-- Tabs -->
        <div class="border-b border-gray-700 mb-6">
            <nav class="-mb-px flex space-x-8">
                <a href="?tab=users" class="<?= $currentTab === 'users' ? 'border-primary-500 text-primary-400' : 'border-transparent text-gray-400 hover:text-gray-300' ?> whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm">
                    SLT Members
                </a>
                <a href="?tab=management" style="display: none;" class="<?= $currentTab === 'management' ? 'border-primary-500 text-primary-400' : 'border-transparent text-gray-400 hover:text-gray-300' ?> whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm">
                    Executive
                </a>
                <a href="?tab=operations" class="<?= $currentTab === 'operations' ? 'border-primary-500 text-primary-400' : 'border-transparent text-gray-400 hover:text-gray-300' ?> whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm">
                    Operations Managers
                </a>
            </nav>
        </div>

        <?php renderAlert(); ?>
        <div class="mb-6">
            <div class="relative flex-grow">
                <input type="text" id="searchInput" 
                       class="w-full pl-10 pr-4 py-2 bg-gray-700/50 border border-gray-600/50 rounded-lg text-gray-200" 
                       placeholder="Search by CXI number or name...">
                <div class="absolute left-3 top-2.5 text-gray-400">
                    <i class="fas fa-search"></i>
                </div>
            </div>
        </div>

        <div id="usersTableContainer">
            <!-- This will be loaded via AJAX -->
            <?php include 'partials/users_table.php'; ?>
        </div>
    </main>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const searchInput = document.getElementById('searchInput');
    let searchTimeout;
    const urlParams = new URLSearchParams(window.location.search);
    const currentTab = urlParams.get('tab') || 'users';

    // Function to load users via AJAX
    function loadUsers(search = '', page = 1) {
        const formData = new FormData();
        formData.append('search', search);
        formData.append('page', page);
        formData.append('type', currentTab);

        fetch('partials/users_table.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.text())
        .then(data => {
            document.getElementById('usersTableContainer').innerHTML = data;
            
            // Re-attach event listeners to pagination links
            document.querySelectorAll('.pagination-link').forEach(link => {
                link.addEventListener('click', function(e) {
                    e.preventDefault();
                    const page = this.getAttribute('data-page');
                    loadUsers(searchInput.value, page);
                    // Update URL without reload
                    history.pushState(null, '', `?tab=${currentTab}&page=${page}${searchInput.value ? '&search=' + encodeURIComponent(searchInput.value) : ''}`);
                });
            });
        })
        .catch(error => console.error('Error:', error));
    }

    // Real-time search with debounce
    searchInput.addEventListener('input', function() {
        clearTimeout(searchTimeout);
        searchTimeout = setTimeout(() => {
            loadUsers(this.value);
            // Update URL without reload
            history.pushState(null, '', `?tab=${currentTab}${this.value ? '&search=' + encodeURIComponent(this.value) : ''}`);
        }, 300);
    });

    // Handle back/forward navigation
    window.addEventListener('popstate', function() {
        const urlParams = new URLSearchParams(window.location.search);
        const searchParam = urlParams.get('search') || '';
        const pageParam = urlParams.get('page') || 1;
        const tabParam = urlParams.get('tab') || 'users';
        
        searchInput.value = searchParam;
        loadUsers(searchParam, pageParam);
    });

    // Initial load with URL parameters
    const initialSearch = urlParams.get('search') || '';
    const initialPage = urlParams.get('page') || 1;
    
    if (initialSearch) searchInput.value = initialSearch;
    loadUsers(initialSearch, initialPage);
});
</script>

<?php renderFooter(); ?>