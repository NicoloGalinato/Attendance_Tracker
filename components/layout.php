<?php
function renderHead($title) {
    ?>
    <!DOCTYPE html>
    <html lang="en" class="dark">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title><?= htmlspecialchars($title) ?> | CXI Admin</title>
        <link rel="icon" href="<?= BASE_URL ?>assets/cxiico.png" type="image/png">
        <meta name="theme-color" content="#0ea5e9">
        <script src="https://cdn.tailwindcss.com"></script>
        <script>
            tailwind.config = {
                darkMode: 'class',
                theme: {
                    extend: {
                        colors: {
                            primary: {
                                50: '#f0f9ff',
                                100: '#e0f2fe',
                                200: '#bae6fd',
                                300: '#7dd3fc',
                                400: '#38bdf8',
                                500: '#0ea5e9',
                                600: '#0284c7',
                                700: '#0369a1',
                                800: '#075985',
                                900: '#0c4a6e',
                            }
                        }
                    }
                }
            }
        </script>
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
        <style>
            .sidebar-item.active {
                background-color: rgba(14, 165, 233, 0.2);
                border-left: 3px solid #0ea5e9;
            }
            .sidebar-item.active .sidebar-icon {
                color: #0ea5e9;
            }
            .sidebar-item:hover:not(.active) {
                background-color: rgba(255, 255, 255, 0.05);
            }
        </style>
    </head>
    <body class="bg-gray-900 text-gray-100 min-h-screen flex flex-col">
    <?php
}

function renderNavbar() {
    ?>
    <nav class="bg-gray-800 border-b border-gray-700">
        <div class="px-4 py-3 flex justify-between items-center">
            <div class="flex items-center space-x-4">            
                <button id="sidebarToggle" class="text-gray-400 hover:text-white focus:outline-none">
                    <i class="fas fa-bars fa-lg"></i>
                </button>
                <div class="flex items-center">
                    <img src="../assets/cxi.png" alt="CXI Logo" class="w-10 h-10 mr-2">
                    <span class="text-xl font-semibold">CXI Admin</span>
                </div>
            </div>
            <div class="flex items-center space-x-4">
                <div class="relative">
                    <button id="userMenuButton" class="flex items-center space-x-2 focus:outline-none">
                        <div class="w-8 h-8 rounded-full bg-primary-600 flex items-center justify-center">
                            <i class="fas fa-user text-white"></i>
                        </div>
                        <span class="hidden md:inline"><?= htmlspecialchars($_SESSION['nickname']); ?></span>
                    </button>
                    <div id="userMenu" class="hidden absolute right-0 mt-2 w-48 bg-gray-800 rounded-md shadow-lg py-1 z-50 border border-gray-700">
                        <a href="../logout.php" class="block px-4 py-2 text-sm text-gray-300 hover:bg-gray-700">Sign out</a>
                    </div>
                </div>
            </div>
        </div>
    </nav>
    <?php
}

function renderSidebar($activePage = 'dashboard') {
    ?>
    <aside id="sidebar" class="bg-gray-800 w-64 fixed h-full border-r border-gray-700 transition-all duration-300 z-40 -translate-x-full md:translate-x-0">
        <div class="p-4">
            <div class="flex items-center space-x-4">
                <div class="flex items-center">
                    <img src="../assets/cxi.png" alt="CXI Logo" class=" mr-2">
                </div>
            </div>
            <div class="border-t border-gray-700 my-4"></div>
            <div class="space-y-1 mt-6">
                <a href="dashboard.php" class="sidebar-item flex items-center px-4 py-3 text-gray-300 hover:text-white <?= $activePage === 'dashboard' ? 'active' : '' ?>">
                    <i class="sidebar-icon fas fa-tachometer-alt mr-3"></i>
                    <span>Dashboard</span>
                </a>
                <a href="#" class="sidebar-item flex items-center px-4 py-3 text-gray-300 hover:text-white <?= $activePage === '#' ? 'active' : '' ?>">
                    <i class="sidebar-icon fas fa-chart-line mr-3"></i>
                    <span>Tracker</span>
                </a>
                <a href="employees.php" class="sidebar-item flex items-center px-4 py-3 text-gray-300 hover:text-white <?= $activePage === 'employees' ? 'active' : '' ?>">
                    <i class="sidebar-icon fas fa-users mr-3"></i>
                    <span>Manage Agents</span>
                </a>
                <a href="users.php" class="sidebar-item flex items-center px-4 py-3 text-gray-300 hover:text-white <?= $activePage === 'users' ? 'active' : '' ?>">
                    <i class="sidebar-icon fas fa-cog mr-3"></i>
                    <span>Manage SLT</span>
                </a>
            </div>
        </div>
    </aside>
    <?php
}

function renderFooter() {
    ?>
    <footer class="bg-gray-800 border-t border-gray-700 py-4 mt-auto">
        <div class="container mx-auto px-4 text-center text-gray-400 text-sm">
            &copy; <?= date('Y') ?> CXI Services Inc. All rights reserved.
        </div>
    </footer>
    <script src="../assets/js/script.js"></script>
    <script src="../assets/js/prompt.js"></script>
    <script>
        // Toggle sidebar on mobile
        document.getElementById('sidebarToggle').addEventListener('click', function() {
            document.getElementById('sidebar').classList.toggle('-translate-x-full');
        });

        // Toggle user menu
        document.getElementById('userMenuButton').addEventListener('click', function() {
            document.getElementById('userMenu').classList.toggle('hidden');
        });

        // Close menus when clicking outside
        document.addEventListener('click', function(event) {
            if (!event.target.closest('#userMenuButton')) {
                document.getElementById('userMenu').classList.add('hidden');
            }
        });
    </script>
    </body>
    </html>
    <?php
}

function renderAlert() {
    if (isset($_SESSION['error'])) {
        echo '<div class="bg-red-500/10 border border-red-500/30 text-red-300 px-4 py-3 rounded-lg mb-6 text-sm">'.$_SESSION['error'].'</div>';
        unset($_SESSION['error']);
    }
    if (isset($_SESSION['success'])) {
        echo '<div class="bg-green-500/10 border border-green-500/30 text-green-300 px-4 py-3 rounded-lg mb-6 text-sm">'.$_SESSION['success'].'</div>';
        unset($_SESSION['success']);
    }
}
?>