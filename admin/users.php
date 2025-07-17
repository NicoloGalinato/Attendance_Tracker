<?php
require_once '../includes/config.php';
require_once '../includes/functions.php';

if (!isLoggedIn() || !isAdmin()) {
    redirect(BASE_URL);
}

// Handle user deletion
if (isset($_GET['delete'])) {
    $userId = (int)$_GET['delete'];
    
    try {
        $stmt = $pdo->prepare("DELETE FROM users WHERE id = ? AND role != 'admin'");
        $stmt->execute([$userId]);
        
        if ($stmt->rowCount() > 0) {
            $_SESSION['success'] = "SLT Account deleted successfully!";
        } else {
            $_SESSION['error'] = "Cannot delete admin users or user not found";
        }
    } catch (PDOException $e) {
        $_SESSION['error'] = "Error deleting user: " . $e->getMessage();
    }
    
    redirect('users.php');
}

require_once '../components/layout.php';
renderHead('Manage SLT');
renderNavbar();
renderSidebar('users');
?>

<div class="md:ml-64 pt-2 min-h-screen">
    <main class="p-6">
        <div class="flex justify-between items-center mb-6">
            <h1 class="text-2xl font-bold">Manage SLT Members</h1>
            <a href="profile.php?action=create" class="bg-primary-600 hover:bg-primary-700 text-white px-4 py-2 rounded-lg flex items-center">
                <i class="fas fa-plus mr-2"></i> Add New
            </a>
        </div>

        <?php renderAlert(); ?>
        <div class="mb-6">
            <div class="relative flex-grow">
                <input type="text" id="searchInput" 
                       class="w-full pl-10 pr-4 py-2 bg-gray-700/50 border border-gray-600/50 rounded-lg text-gray-200" 
                       placeholder="Search by username, SLT or full name">
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

    // Function to load users via AJAX
    function loadUsers(search = '', page = 1) {
        const formData = new FormData();
        formData.append('search', search);
        formData.append('page', page);

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
                    history.pushState(null, '', `?page=${page}${searchInput.value ? '&search=' + encodeURIComponent(searchInput.value) : ''}`);
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
            history.pushState(null, '', `?${this.value ? 'search=' + encodeURIComponent(this.value) : ''}`);
        }, 300);
    });

    // Handle back/forward navigation
    window.addEventListener('popstate', function() {
        const urlParams = new URLSearchParams(window.location.search);
        const searchParam = urlParams.get('search') || '';
        const pageParam = urlParams.get('page') || 1;
        
        searchInput.value = searchParam;
        loadUsers(searchParam, pageParam);
    });

    // Initial load with URL parameters
    const urlParams = new URLSearchParams(window.location.search);
    const initialSearch = urlParams.get('search') || '';
    const initialPage = urlParams.get('page') || 1;
    
    if (initialSearch) searchInput.value = initialSearch;
    loadUsers(initialSearch, initialPage);
});
</script>

<?php renderFooter(); ?>