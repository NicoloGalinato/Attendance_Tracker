<?php
require_once '../includes/config.php';
require_once '../includes/functions.php';

if (!isLoggedIn() || !isAdmin()) {
    redirect(BASE_URL);
}

// Get user count
try {
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM users");
    $userCount = $stmt->fetch()['count'];
} catch (PDOException $e) {
    $userCount = 0;
}

require_once '../components/layout.php';
renderHead('Dashboard');
renderNavbar();
renderSidebar('dashboard');
?>

<div class="md:ml-64 pt-16 min-h-screen">
    <main class="p-6">
        <div class="flex justify-between items-center mb-6">
            <h1 class="text-2xl font-bold">Dashboard</h1>
        </div>

        <?php renderAlert(); ?>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-8">
            <div class="bg-gray-800 rounded-xl border border-gray-700 p-6 shadow">
                <div class="flex items-center justify-between">
                    <div>
                        <h3 class="text-gray-400 text-sm font-medium">Total Users</h3>
                        <p class="text-3xl font-bold mt-2"><?= $userCount ?></p>
                    </div>
                    <div class="bg-primary-500/20 p-3 rounded-full">
                        <i class="fas fa-users text-primary-500 text-xl"></i>
                    </div>
                </div>
            </div>

            <div class="bg-gray-800 rounded-xl border border-gray-700 p-6 shadow">
                <div class="flex items-center justify-between">
                    <div>
                        <h3 class="text-gray-400 text-sm font-medium">Your Role</h3>
                        <p class="text-3xl font-bold mt-2">Admin</p>
                    </div>
                    <div class="bg-green-500/20 p-3 rounded-full">
                        <i class="fas fa-shield-alt text-green-500 text-xl"></i>
                    </div>
                </div>
            </div>
        </div>

        <div class="bg-gray-800 rounded-xl border border-gray-700 p-6 shadow">
            <h2 class="text-xl font-semibold mb-4">Welcome to CXI Admin Panel</h2>
            <p class="text-gray-400">
                You can manage all SLT members and their profiles from this dashboard. 
                Use the navigation menu to access different sections of the system.
            </p>
        </div>
    </main>
</div>

<?php renderFooter(); ?>