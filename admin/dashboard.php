<?php
require_once '../includes/config.php';
require_once '../includes/functions.php';

if (!isLoggedIn() || !isAdmin()) {
    redirect(BASE_URL);
}

updateLastActivity();

// Get user count
try {
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM users");
    $userCount = $stmt->fetch()['count'];
} catch (PDOException $e) {
    $userCount = 0;
}

// Get statistics
$stats = [
    'pending_emails' => 0,
    'pending_ir' => 0,
    'absent_today' => 0,
    'absent_week' => 0,
    'absent_month' => 0,
    'absent_year' => 0,
    'late_today' => 0,
    'late_week' => 0,
    'late_month' => 0,
    'late_year' => 0
];

try {
    // Pending emails (not sent)
    $stmt = $pdo->query("SELECT COUNT(*) FROM absenteeism WHERE email_sent = 0");
    $stats['pending_emails'] += $stmt->fetchColumn();
    
    $stmt = $pdo->query("SELECT COUNT(*) FROM tardiness WHERE email_sent = 0");
    $stats['pending_emails'] += $stmt->fetchColumn();
    
    // Pending IR forms
    $stmt = $pdo->query("SELECT COUNT(*) FROM absenteeism WHERE ir_form = '' OR ir_form IS NULL");
    $stats['pending_ir'] += $stmt->fetchColumn();
    
    $stmt = $pdo->query("SELECT COUNT(*) FROM tardiness WHERE ir_form = '' OR ir_form IS NULL");
    $stats['pending_ir'] += $stmt->fetchColumn();
    
    // Absenteeism stats
    $today = date('Y-m-d');
    $weekStart = date('Y-m-d', strtotime('monday this week'));
    $monthStart = date('Y-m-01');
    $yearStart = date('Y-01-01');
    
    // Today
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM absenteeism WHERE date_of_absent = ?");
    $stmt->execute([$today]);
    $stats['absent_today'] = $stmt->fetchColumn();
    
    // This week
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM absenteeism WHERE date_of_absent BETWEEN ? AND ?");
    $stmt->execute([$weekStart, $today]);
    $stats['absent_week'] = $stmt->fetchColumn();
    
    // This month
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM absenteeism WHERE date_of_absent BETWEEN ? AND ?");
    $stmt->execute([$monthStart, $today]);
    $stats['absent_month'] = $stmt->fetchColumn();
    
    // This year
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM absenteeism WHERE date_of_absent BETWEEN ? AND ?");
    $stmt->execute([$yearStart, $today]);
    $stats['absent_year'] = $stmt->fetchColumn();
    
    // Tardiness stats
    // Today
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM tardiness WHERE date_of_incident = ?");
    $stmt->execute([$today]);
    $stats['late_today'] = $stmt->fetchColumn();
    
    // This week
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM tardiness WHERE date_of_incident BETWEEN ? AND ?");
    $stmt->execute([$weekStart, $today]);
    $stats['late_week'] = $stmt->fetchColumn();
    
    // This month
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM tardiness WHERE date_of_incident BETWEEN ? AND ?");
    $stmt->execute([$monthStart, $today]);
    $stats['late_month'] = $stmt->fetchColumn();
    
    // This year
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM tardiness WHERE date_of_incident BETWEEN ? AND ?");
    $stmt->execute([$yearStart, $today]);
    $stats['late_year'] = $stmt->fetchColumn();
    
} catch (PDOException $e) {
    // If there's an error, we'll just use the default 0 values
}

// Get data for charts (last 12 months)
$chartData = [
    'months' => [],
    'absenteeism' => [],
    'tardiness' => []
];

try {
    for ($i = 11; $i >= 0; $i--) {
        $month = date('Y-m', strtotime("-$i months"));
        $startDate = date('Y-m-01', strtotime($month));
        $endDate = date('Y-m-t', strtotime($month));
        
        $chartData['months'][] = date('M Y', strtotime($month));
        
        // Absenteeism
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM absenteeism WHERE date_of_absent BETWEEN ? AND ?");
        $stmt->execute([$startDate, $endDate]);
        $chartData['absenteeism'][] = $stmt->fetchColumn();
        
        // Tardiness
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM tardiness WHERE date_of_incident BETWEEN ? AND ?");
        $stmt->execute([$startDate, $endDate]);
        $chartData['tardiness'][] = $stmt->fetchColumn();
    }
} catch (PDOException $e) {
    // If there's an error, we'll just use empty arrays
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
            <div class="text-sm text-gray-400">
                <?= date('F j, Y') ?>
            </div>
        </div>

        <?php renderAlert(); ?>

        <!-- Stats Cards -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
            <!-- Pending Emails -->
            <div class="bg-gray-800 rounded-xl border border-gray-700 p-6 shadow">
                <div class="flex items-center justify-between">
                    <div>
                        <h3 class="text-gray-400 text-sm font-medium">Pending Emails</h3>
                        <p class="text-3xl font-bold mt-2"><?= $stats['pending_emails'] ?></p>
                        <p class="text-xs text-gray-400 mt-1">Emails not yet sent</p>
                    </div>
                    <div class="bg-yellow-500/20 p-3 rounded-full">
                        <i class="fas fa-envelope text-yellow-500 text-xl"></i>
                    </div>
                </div>
            </div>

            <!-- Pending IR Forms -->
            <div class="bg-gray-800 rounded-xl border border-gray-700 p-6 shadow">
                <div class="flex items-center justify-between">
                    <div>
                        <h3 class="text-gray-400 text-sm font-medium">Pending IR Forms</h3>
                        <p class="text-3xl font-bold mt-2"><?= $stats['pending_ir'] ?></p>
                        <p class="text-xs text-gray-400 mt-1">Forms not yet submitted</p>
                    </div>
                    <div class="bg-red-500/20 p-3 rounded-full">
                        <i class="fas fa-file-alt text-red-500 text-xl"></i>
                    </div>
                </div>
            </div>

            <!-- Total Users -->
            <div class="bg-gray-800 rounded-xl border border-gray-700 p-6 shadow">
                <div class="flex items-center justify-between">
                    <div>
                        <h3 class="text-gray-400 text-sm font-medium">Total Users</h3>
                        <p class="text-3xl font-bold mt-2"><?= $userCount ?></p>
                        <p class="text-xs text-gray-400 mt-1">System users</p>
                    </div>
                    <div class="bg-primary-500/20 p-3 rounded-full">
                        <i class="fas fa-users text-primary-500 text-xl"></i>
                    </div>
                </div>
            </div>

            <!-- Your Role -->
            <div class="bg-gray-800 rounded-xl border border-gray-700 p-6 shadow">
                <div class="flex items-center justify-between">
                    <div>
                        <h3 class="text-gray-400 text-sm font-medium">Your Role</h3>
                        <p class="text-3xl font-bold mt-2">Admin</p>
                        <p class="text-xs text-gray-400 mt-1">System access</p>
                    </div>
                    <div class="bg-green-500/20 p-3 rounded-full">
                        <i class="fas fa-shield-alt text-green-500 text-xl"></i>
                    </div>
                </div>
            </div>
        </div>

        <!-- Attendance Overview -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
            <!-- Absenteeism Stats -->
            <div class="bg-gray-800 rounded-xl border border-gray-700 p-6 shadow">
                <h3 class="text-lg font-semibold mb-4 flex items-center">
                    <i class="fas fa-bed text-red-500 mr-2"></i>
                    Absenteeism Overview
                </h3>
                <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">
                    <div class="text-center">
                        <p class="text-2xl font-bold"><?= $stats['absent_today'] ?></p>
                        <p class="text-xs text-gray-400">Today</p>
                    </div>
                    <div class="text-center">
                        <p class="text-2xl font-bold"><?= $stats['absent_week'] ?></p>
                        <p class="text-xs text-gray-400">This Week</p>
                    </div>
                    <div class="text-center">
                        <p class="text-2xl font-bold"><?= $stats['absent_month'] ?></p>
                        <p class="text-xs text-gray-400">This Month</p>
                    </div>
                    <div class="text-center">
                        <p class="text-2xl font-bold"><?= $stats['absent_year'] ?></p>
                        <p class="text-xs text-gray-400">This Year</p>
                    </div>
                </div>
                <div class="h-64">
                    <canvas id="absenteeismChart"></canvas>
                </div>
            </div>

            <!-- Tardiness Stats -->
            <div class="bg-gray-800 rounded-xl border border-gray-700 p-6 shadow">
                <h3 class="text-lg font-semibold mb-4 flex items-center">
                    <i class="fas fa-clock text-yellow-500 mr-2"></i>
                    Tardiness Overview
                </h3>
                <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">
                    <div class="text-center">
                        <p class="text-2xl font-bold"><?= $stats['late_today'] ?></p>
                        <p class="text-xs text-gray-400">Today</p>
                    </div>
                    <div class="text-center">
                        <p class="text-2xl font-bold"><?= $stats['late_week'] ?></p>
                        <p class="text-xs text-gray-400">This Week</p>
                    </div>
                    <div class="text-center">
                        <p class="text-2xl font-bold"><?= $stats['late_month'] ?></p>
                        <p class="text-xs text-gray-400">This Month</p>
                    </div>
                    <div class="text-center">
                        <p class="text-2xl font-bold"><?= $stats['late_year'] ?></p>
                        <p class="text-xs text-gray-400">This Year</p>
                    </div>
                </div>
                <div class="h-64">
                    <canvas id="tardinessChart"></canvas>
                </div>
            </div>
        </div>

        <!-- Combined Chart -->
        <div class="bg-gray-800 rounded-xl border border-gray-700 p-6 shadow mb-8">
            <h3 class="text-lg font-semibold mb-4 flex items-center">
                <i class="fas fa-chart-line text-primary-500 mr-2"></i>
                Attendance Trend (Last 12 Months)
            </h3>
            <div class="h-80">
                <canvas id="combinedChart"></canvas>
            </div>
        </div>

        <!-- Quick Actions -->
        <div class="bg-gray-800 rounded-xl border border-gray-700 p-6 shadow">
            <h3 class="text-lg font-semibold mb-4 flex items-center">
                <i class="fas fa-bolt text-green-500 mr-2"></i>
                Quick Actions
            </h3>
            <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                <a href="attendance.php?tab=absenteeism" class="bg-gray-700 hover:bg-gray-600 text-white p-4 rounded-lg flex flex-col items-center justify-center transition-colors">
                    <i class="fas fa-bed text-red-500 text-2xl mb-2"></i>
                    <span>Absenteeism</span>
                </a>
                <a href="attendance.php?tab=tardiness" class="bg-gray-700 hover:bg-gray-600 text-white p-4 rounded-lg flex flex-col items-center justify-center transition-colors">
                    <i class="fas fa-clock text-yellow-500 text-2xl mb-2"></i>
                    <span>Tardiness</span>
                </a>
                <a href="users.php" class="bg-gray-700 hover:bg-gray-600 text-white p-4 rounded-lg flex flex-col items-center justify-center transition-colors">
                    <i class="fas fa-users text-primary-500 text-2xl mb-2"></i>
                    <span>Manage Users</span>
                </a>
                <a href="employees.php" class="bg-gray-700 hover:bg-gray-600 text-white p-4 rounded-lg flex flex-col items-center justify-center transition-colors">
                    <i class="fas fa-id-card text-blue-500 text-2xl mb-2"></i>
                    <span>Manage Agents</span>
                </a>
            </div>
        </div>
    </main>
</div>

<!-- Chart.js -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Absenteeism Chart
    const absentCtx = document.getElementById('absenteeismChart').getContext('2d');
    const absenteeismChart = new Chart(absentCtx, {
        type: 'bar',
        data: {
            labels: <?= json_encode($chartData['months']) ?>,
            datasets: [{
                label: 'Absenteeism',
                data: <?= json_encode($chartData['absenteeism']) ?>,
                backgroundColor: 'rgba(239, 68, 68, 0.7)',
                borderColor: 'rgba(239, 68, 68, 1)',
                borderWidth: 1
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: {
                y: {
                    beginAtZero: true,
                    grid: {
                        color: 'rgba(55, 65, 81, 0.5)'
                    },
                    ticks: {
                        color: '#9CA3AF'
                    }
                },
                x: {
                    grid: {
                        display: false
                    },
                    ticks: {
                        color: '#9CA3AF'
                    }
                }
            },
            plugins: {
                legend: {
                    labels: {
                        color: '#9CA3AF'
                    }
                }
            }
        }
    });

    // Tardiness Chart
    const tardyCtx = document.getElementById('tardinessChart').getContext('2d');
    const tardinessChart = new Chart(tardyCtx, {
        type: 'bar',
        data: {
            labels: <?= json_encode($chartData['months']) ?>,
            datasets: [{
                label: 'Tardiness',
                data: <?= json_encode($chartData['tardiness']) ?>,
                backgroundColor: 'rgba(234, 179, 8, 0.7)',
                borderColor: 'rgba(234, 179, 8, 1)',
                borderWidth: 1
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: {
                y: {
                    beginAtZero: true,
                    grid: {
                        color: 'rgba(55, 65, 81, 0.5)'
                    },
                    ticks: {
                        color: '#9CA3AF'
                    }
                },
                x: {
                    grid: {
                        display: false
                    },
                    ticks: {
                        color: '#9CA3AF'
                    }
                }
            },
            plugins: {
                legend: {
                    labels: {
                        color: '#9CA3AF'
                    }
                }
            }
        }
    });

    // Combined Chart
    const combinedCtx = document.getElementById('combinedChart').getContext('2d');
    const combinedChart = new Chart(combinedCtx, {
        type: 'line',
        data: {
            labels: <?= json_encode($chartData['months']) ?>,
            datasets: [
                {
                    label: 'Absenteeism',
                    data: <?= json_encode($chartData['absenteeism']) ?>,
                    borderColor: 'rgba(239, 68, 68, 1)',
                    backgroundColor: 'rgba(239, 68, 68, 0.1)',
                    borderWidth: 2,
                    tension: 0.3,
                    fill: true
                },
                {
                    label: 'Tardiness',
                    data: <?= json_encode($chartData['tardiness']) ?>,
                    borderColor: 'rgba(234, 179, 8, 1)',
                    backgroundColor: 'rgba(234, 179, 8, 0.1)',
                    borderWidth: 2,
                    tension: 0.3,
                    fill: true
                }
            ]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            interaction: {
                mode: 'index',
                intersect: false,
            },
            scales: {
                y: {
                    beginAtZero: true,
                    grid: {
                        color: 'rgba(55, 65, 81, 0.5)'
                    },
                    ticks: {
                        color: '#9CA3AF'
                    }
                },
                x: {
                    grid: {
                        color: 'rgba(55, 65, 81, 0.5)'
                    },
                    ticks: {
                        color: '#9CA3AF'
                    }
                }
            },
            plugins: {
                legend: {
                    labels: {
                        color: '#9CA3AF'
                    }
                },
                tooltip: {
                    mode: 'index',
                    intersect: false
                }
            }
        }
    });
});
</script>

<?php renderFooter(); ?>