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
        
        <div class="mb-6">
            <div class="relative flex-grow">
                <input type="text" id="searchInput" 
                       class="w-full pl-10 pr-4 py-2 bg-gray-700/50 border border-gray-600/50 rounded-lg text-gray-200" 
                       placeholder="Search by employee ID or name...">
                <div class="absolute left-3 top-2.5 text-gray-400">
                    <i class="fas fa-search"></i>
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
    let searchTimeout;
    const urlParams = new URLSearchParams(window.location.search);
    const currentTab = urlParams.get('tab') || 'absenteeism';

    function loadAttendance(search = '', page = 1) {
        const formData = new FormData();
        formData.append('search', search);
        formData.append('page', page);
        formData.append('type', currentTab);

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
                    const page = this.getAttribute('data-page');
                    loadAttendance(searchInput.value, page);
                    history.pushState(null, '', `?tab=${currentTab}&page=${page}${searchInput.value ? '&search=' + encodeURIComponent(searchInput.value) : ''}`);
                });
            });
        });
    }

    searchInput.addEventListener('input', function() {
        clearTimeout(searchTimeout);
        searchTimeout = setTimeout(() => {
            loadAttendance(this.value);
            history.pushState(null, '', `?tab=${currentTab}${this.value ? '&search=' + encodeURIComponent(this.value) : ''}`);
        }, 300);
    });

    window.addEventListener('popstate', function() {
        const urlParams = new URLSearchParams(window.location.search);
        const searchParam = urlParams.get('search') || '';
        const pageParam = urlParams.get('page') || 1;
        const tabParam = urlParams.get('tab') || 'absenteeism';
        
        searchInput.value = searchParam;
        loadAttendance(searchParam, pageParam);
    });

    const initialSearch = urlParams.get('search') || '';
    const initialPage = urlParams.get('page') || 1;
    
    if (initialSearch) searchInput.value = initialSearch;
    loadAttendance(initialSearch, initialPage);
});
</script>

<?php renderFooter(); ?>