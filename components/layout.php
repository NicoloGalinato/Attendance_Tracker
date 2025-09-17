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
            
            /* Sidebar hover styles */
            #sidebar {
                width: 5rem;
                transition: all 0.3s ease;
                overflow: hidden;
            }
            
            #sidebar:hover {
                width: 16rem;
            }
            
            #sidebar:hover .sidebar-text {
                opacity: 1;
                transition: opacity 0.3s ease 0.2s;
            }
            
            .sidebar-text {
                opacity: 0;
                transition: opacity 0.1s ease;
                white-space: nowrap;
            }
            
            /* Adjust main content margin */
            .main-content {
                margin-left: 5rem;
                transition: margin-left 0.3s ease;
            }
            
            #sidebar:hover ~ .main-content {
                margin-left: 16rem;
            }
            /* Custom CSS to hide the scrollbar for the modal */
            .hide-scrollbar::-webkit-scrollbar {
                display: none; /* Para sa Chrome, Safari, at Opera */
            }

            .hide-scrollbar {
                -ms-overflow-style: none; /* Para sa IE at Edge */
                scrollbar-width: none; /* Para sa Firefox */
            }
            
            /* Responsive adjustments */
            @media (max-width: 768px) {
                #sidebar {
                    width: 16rem;
                    transform: translateX(-100%);
                }
                
                #sidebar:hover {
                    width: 16rem;
                }
                
                .main-content {
                    margin-left: 0;
                }
                
                #sidebarToggle:hover + #sidebar,
                #sidebar:hover {
                    transform: translateX(0);
                }
                
                .sidebar-text {
                    opacity: 1;
                }
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
            <div class="flex items-center space-x-4" style="opacity:0;">            
                <button id="sidebarToggle" class="text-gray-400 hover:text-white focus:outline-none md:hidden">
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
                        <span class="relative inline-flex rounded-full h-3 w-3 bg-gray-400 online-status" data-user-id="<?= $_SESSION['user_id'] ?>"></span>
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
    <aside id="sidebar" class="bg-gray-800 fixed h-full border-r border-gray-700 z-40">
        <div class="p-4">
            <div class="flex items-center space-x-4">
                <div class="logo-container">
                    <img src="../assets/cxi.png" alt="CXI Logo" class="logo">
                </div>
            </div>
            <div class="border-t border-gray-700 my-4"></div>
            <div class="space-y-1 mt-6">
                <a href="dashboard.php" class="sidebar-item flex items-center px-4 py-3 text-gray-300 hover:text-white <?= $activePage === 'dashboard' ? 'active' : '' ?>">
                    <i class="sidebar-icon fas fa-tachometer-alt mr-3"></i>
                    <span class="sidebar-text">Dashboard</span>
                </a>
                <a href="attendance.php" class="sidebar-item flex items-center px-4 py-3 text-gray-300 hover:text-white <?= $activePage === 'attendance' ? 'active' : '' ?>">
                    <i class="sidebar-icon fas fa-chart-line mr-3"></i>
                    <span class="sidebar-text">Tracker</span>
                </a>
                <a href="attendance_statistics.php" class="sidebar-item flex items-center px-4 py-3 text-gray-300 hover:text-white <?= $activePage === 'attendance_statistics' ? 'active' : '' ?>">
                    <i class="sidebar-icon fas fa-chart-pie mr-3"></i>
                    <span class="sidebar-text">Attendance Statistics</span>
                </a>     
                <a href="ticket_dashboard.php" class="sidebar-item flex items-center px-4 py-3 text-gray-300 hover:text-white <?= $activePage === 'ticket_dashboard' ? 'active' : '' ?>">
                    <i class="sidebar-icon fas fa-clipboard-list mr-3"></i>
                    <span class="sidebar-text">Ticket Dashboard</span>
                </a>
                
                <a href="statistics.php" class="sidebar-item flex items-center px-4 py-3 text-gray-300 hover:text-white <?= $activePage === 'statistics' ? 'active' : '' ?>">
                    <i class="sidebar-icon fas fa-chart-bar mr-3"></i>
                    <span class="sidebar-text">Ticket Statistics</span>
                </a>     
                <a href="inventory_tracker.php" class="sidebar-item flex items-center px-4 py-3 text-gray-300 hover:text-white <?= $activePage === 'inventory' ? 'active' : '' ?>">
                    <i class="sidebar-icon fas fa-boxes mr-3"></i>
                    <span class="sidebar-text">SLT Inventory</span>
                </a>   
                <a href="employees.php" class="sidebar-item flex items-center px-4 py-3 text-gray-300 hover:text-white <?= $activePage === 'employees' ? 'active' : '' ?>">
                    <i class="sidebar-icon fas fa-users mr-3"></i>
                    <span class="sidebar-text">Manage Agents</span>
                </a>
                <a href="users.php" class="sidebar-item flex items-center px-4 py-3 text-gray-300 hover:text-white <?= $activePage === 'users' ? 'active' : '' ?>">
                    <i class="sidebar-icon fas fa-cog mr-3"></i>
                    <span class="sidebar-text">Management Settings</span>
                </a>


                <!-- Disabled Ticket Dashboard with Coming Soon message -->
                <br>
                <span class="sidebar-text">Coming soon!</span>
                <div class="relative group">
                    <div class="sidebar-item flex items-center px-4 py-3 text-gray-500 cursor-not-allowed opacity-50">
                        <i class="sidebar-icon fas fa-boxes mr-3"></i>
                        <span class="sidebar-text">SLT Inventory</span>
                    </div>
                </div>
                
                
            </div>
        </div>
    </aside>
    <div class="main-content flex-1 flex flex-col">

    <style>
  /* Hover effect for logo */
  .logo {
    transition: all 0.3s ease;
    width: 250px; /* Default size */
    height: auto;
  }
  
  /* Hover effect for menu items */
  .menu-item {
    transition: all 0.3s ease;
    font-size: 16px; /* Default size */
    display: inline-block;
    margin: 0 15px;
  }
  
  .menu-item:hover {
    transform: scale(1.3); /* Lumalaki ng 30% */
    font-weight: bold; /* Optional: nagiging bold din */
  }
</style>
    <?php
}

function renderFooter() {
    ?>
    </div> <!-- Close main-content div -->
    <footer class="bg-gray-800 border-t border-gray-700 py-4 mt-auto">
        <div class="container mx-auto px-4 text-center text-gray-400 text-sm">
            &copy; <?= date('Y') ?> CXI Services Inc. All rights reserved.
        </div>
    </footer>
    <script src="../assets/js/script.js"></script>
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

        // Online status functionality
        function checkOnlineStatus() {
            fetch('../api/online_status.php')
                .then(response => response.json())
                .then(data => {
                    if (!data.success) return;
                    
                    // Get all user indicators
                    const allUserIds = Array.from(document.querySelectorAll('.online-status'))
                        .map(el => parseInt(el.getAttribute('data-user-id')));
                    
                    // Update all indicators
                    document.querySelectorAll('.online-status').forEach(indicator => {
                        const userId = parseInt(indicator.getAttribute('data-user-id'));
                        const pingElement = indicator.previousElementSibling;
                        
                        // If user is in onlineUsers array, show as online
                        if (data.onlineUsers.includes(userId)) {
                            indicator.classList.remove('bg-gray-400');
                            indicator.classList.add('bg-green-400');
                            pingElement.style.display = 'inline-flex';
                        } else {
                            // User is offline
                            indicator.classList.remove('bg-green-400');
                            indicator.classList.add('bg-gray-400');
                            pingElement.style.display = 'none';
                        }
                    });
                })
                .catch(error => console.error('Error checking online status:', error));
        }

        // Check immediately and then every 15 seconds
        checkOnlineStatus();
        const statusCheckInterval = setInterval(checkOnlineStatus, 15000);
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