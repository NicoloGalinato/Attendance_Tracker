<?php
require_once '../includes/config.php';
require_once '../includes/functions.php';

if (!isLoggedIn() || !isAdmin()) {
    redirect(BASE_URL);
}

updateLastActivity();

// Handle user deletion
if (isset($_GET['delete'])) {
    // Start session if not already started
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    
    $userId = (int)$_GET['delete'];
    $type = isset($_GET['type']) ? $_GET['type'] : 'users';
    
    // Check if password is provided and correct
    $requiredPassword = "SLT@2025"; // Change this to your actual password
    $providedPassword = $_POST['delete_password'] ?? '';
    
    if (empty($providedPassword) || $providedPassword !== $requiredPassword) {
        $_SESSION['error'] = "Incorrect or missing password for deletion";
        redirect('users.php?tab=' . $type);
        exit();
    }
    
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
        $_SESSION['error'] = "Cannot delete record: Record of this user could not be deleted. It has a history record in the tracker. Kindly contact the developer.";
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

<div class="pt-2 min-h-screen">
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
                <a href="?tab=operations" class="<?= $currentTab === 'operations' ? 'border-primary-500 text-primary-400' : 'border-transparent text-gray-400 hover:text-gray-300' ?> whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm">
                    Operations Managers
                </a>
                <a href="?tab=management" class="<?= $currentTab === 'management' ? 'border-primary-500 text-primary-400' : 'border-transparent text-gray-400 hover:text-gray-300' ?> whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm">
                    Team Leaders
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

// Add event listeners for clicking outside modal or pressing Escape
document.addEventListener('click', function(e) {
    if (e.target === document.getElementById('deleteModal')) {
        closeDeleteModal();
    }
});

document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape' && document.getElementById('deleteModal')) {
        closeDeleteModal();
    }
});

// Real-time online status polling
function updateOnlineStatuses() {
    fetch('../api/online_status.php')
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Update all online indicators
                document.querySelectorAll('.online-status').forEach(indicator => {
                    const userId = indicator.getAttribute('data-user-id');
                    const isOnline = data.onlineUsers.includes(parseInt(userId));
                    
                    if (isOnline) {
                        indicator.classList.remove('bg-gray-400');
                        indicator.classList.add('bg-green-400');
                        // Show the ping animation
                        indicator.previousElementSibling.style.display = 'inline-flex';
                    } else {
                        indicator.classList.remove('bg-green-400');
                        indicator.classList.add('bg-gray-400');
                        // Hide the ping animation
                        indicator.previousElementSibling.style.display = 'none';
                    }
                });
            }
        })
        .catch(error => console.error('Error checking online status:', error));
}

// Update online status immediately and then every 5 seconds
updateOnlineStatuses();
setInterval(updateOnlineStatuses, 1000);
</script>

<?php renderFooter(); ?>