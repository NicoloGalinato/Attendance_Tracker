<?php
require_once '../includes/config.php';
require_once '../includes/functions.php';

if (!isLoggedIn() || !isAdmin()) {
    redirect(BASE_URL);
}

updateLastActivity();


// Get statistics
$stats = [
    
    'pending_emails' => 0,
    'pending_ir' => 0,
    'uncovered_shift' => 0, 
    'pending_coverage' => 0, 
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
    $stmt = $pdo->query("SELECT COUNT(*) FROM absenteeism WHERE ir_form NOT REGEXP '^(YES|NO NEED)'");
    $stats['pending_ir'] += $stmt->fetchColumn();

    $stmt = $pdo->query("SELECT COUNT(*) FROM tardiness WHERE ir_form NOT REGEXP '^(YES|FOR ACCUMULATION|NO NEED|EXPIRED)'");
    $stats['pending_ir'] += $stmt->fetchColumn();

    // Pending Coverage
    $stmt = $pdo->query("SELECT COUNT(*) FROM absenteeism WHERE coverage = 'PENDING'");
    $stats['pending_coverage'] += $stmt->fetchColumn();

    $todayDate = date('Y-m-d');

    // Pending Uncovered Shift
    $todayDate = date('Y-m-d');
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM absenteeism WHERE coverage = 'UNCOVERED' AND date_of_absent >= ?");
    $stmt->execute([$todayDate]);
    $stats['uncovered_shift'] += $stmt->fetchColumn();

    // Absenteeism stats
    $today = date('Y-m-d');
    $weekStart = date('Y-m-d', strtotime('monday this week'));
    $monthStart = date('Y-m-01');
    $monthEnd = date('Y-m-t');
    $yearStart = date('Y-01-01');
    $yearEnd = date('Y-12-31');

    
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
    $stmt->execute([$monthStart, $monthEnd]);
    $stats['absent_month'] = $stmt->fetchColumn();
    
    // This year
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM absenteeism WHERE date_of_absent BETWEEN ? AND ?");
    $stmt->execute([$yearStart, $yearEnd]);
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
    'tardiness' => [],
    'absenteeism_percentage' => [],
    'tardiness_percentage' => []
];

try {
    // Get total number of active agents for percentage calculation
    $stmt = $pdo->query("SELECT COUNT(*) FROM employees WHERE is_active = 1");
    $totalActiveAgents = $stmt->fetchColumn();
    $totalActiveAgents = max($totalActiveAgents, 1); // Ensure we don't divide by zero

    // Get the first day of the current month
    $currentDate = new DateTime('first day of this month');
    
    for ($i = 11; $i >= 0; $i--) {
        // Create a copy of the current date
        $monthDate = clone $currentDate;
        
        // Subtract the appropriate number of months
        $monthDate->sub(new DateInterval("P{$i}M"));
        
        // Get the month info
        $month = $monthDate->format('Y-m');
        $startDate = $monthDate->format('Y-m-01');
        $endDate = $monthDate->format('Y-m-t'); // Last day of month
        
        $chartData['months'][] = $monthDate->format('M Y');
        
        // Absenteeism
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM absenteeism WHERE date_of_absent BETWEEN ? AND ?");
        $stmt->execute([$startDate, $endDate]);
        $absentCount = $stmt->fetchColumn();
        $chartData['absenteeism'][] = $absentCount;
        $chartData['absenteeism_percentage'][] = round(($absentCount / $totalActiveAgents) * 100, 2);
        
        // Tardiness
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM tardiness WHERE date_of_incident BETWEEN ? AND ?");
        $stmt->execute([$startDate, $endDate]);
        $tardyCount = $stmt->fetchColumn();
        $chartData['tardiness'][] = $tardyCount;
        $chartData['tardiness_percentage'][] = round(($tardyCount / $totalActiveAgents) * 100, 2);
    }
} catch (PDOException $e) {
    // Log error if needed
    error_log("Database error in dashboard chart data: " . $e->getMessage());
}

require_once '../components/layout.php';
renderHead('Dashboard');
renderNavbar();
renderSidebar('dashboard');
?>

<div class="pt-2 min-h-screen">
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
                    <div class="bg-primary-500/20 p-3 rounded-full">
                        <i class="fas fa-envelope text-primary-500 text-xl"></i>
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
                    <div class="bg-yellow-500/20 p-3 rounded-full">
                        <i class="fas fa-file-alt text-yellow-500 text-xl"></i>
                    </div>
                </div>
            </div>

            <!-- Pending Coverage -->
            <div class="bg-gray-800 rounded-xl border border-gray-700 p-6 shadow">
                <div class="flex items-center justify-between">
                    <div>
                        <h3 class="text-gray-400 text-sm font-medium">Pending Coverage</h3>
                        <p class="text-3xl font-bold mt-2"><?= $stats['pending_coverage'] ?></p>
                        <p class="text-xs text-gray-400 mt-1">Shift not yet covered</p>
                    </div>
                    <div class="bg-orange-500/20 p-3 rounded-full">
                        <i class="fas fa-clock text-orange-500 text-xl"></i>
                    </div>
                </div>
            </div>

            <!-- Uncovered shift -->
            <div class="bg-gray-800 rounded-xl border border-gray-700 p-6 shadow">
                <div class="flex items-center justify-between">
                    <div>
                        <h3 class="text-gray-400 text-sm font-medium">Uncovered Shift</h3>
                        <p class="text-3xl font-bold mt-2"><?= $stats['uncovered_shift'] ?></p>
                        <p class="text-xs text-gray-400 mt-1">Today's uncovered shift</p>
                    </div>
                    <div class="bg-red-500/20 p-3 rounded-full">
                        <i class="fas fa-book text-red-500 text-xl"></i>
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
            <div class="flex justify-between items-center mb-4">
                <h3 class="text-lg font-semibold flex items-center">
                    <i class="fas fa-chart-line text-primary-500 mr-2"></i>
                    Attendance Trend
                </h3>
                <div class="flex space-x-2">
                    <select id="timeRange" class="bg-gray-700 border border-gray-600 text-white text-sm rounded-lg px-3 py-1">
                        <option value="12months">Last 12 Months</option>
                        <option value="30days" selected>Last 30 Days</option>
                        <option value="7days">Last 7 Days</option>
                    </select>
                </div>
            </div>
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

    // Combined Chart - now with dynamic data loading
    const combinedCtx = document.getElementById('combinedChart').getContext('2d');
    let combinedChart = new Chart(combinedCtx, {
        type: 'line',
        data: {
            labels: <?= json_encode($chartData['months']) ?>,
            datasets: [
                {
                    label: 'Absenteeism (Count)',
                    data: <?= json_encode($chartData['absenteeism']) ?>,
                    borderColor: 'rgba(239, 68, 68, 1)',
                    backgroundColor: 'rgba(239, 68, 68, 0.1)',
                    borderWidth: 2,
                    tension: 0.3,
                    fill: true,
                    yAxisID: 'y-count'
                },
                {
                    label: 'Tardiness (Count)',
                    data: <?= json_encode($chartData['tardiness']) ?>,
                    borderColor: 'rgba(234, 179, 8, 1)',
                    backgroundColor: 'rgba(234, 179, 8, 0.1)',
                    borderWidth: 2,
                    tension: 0.3,
                    fill: true,
                    yAxisID: 'y-count'
                },
                {
                    label: 'Absenteeism %',
                    data: <?= json_encode($chartData['absenteeism_percentage']) ?>,
                    borderColor: 'rgba(239, 68, 68, 0.7)',
                    borderWidth: 2,
                    borderDash: [5, 5],
                    pointRadius: 3,
                    pointHoverRadius: 5,
                    yAxisID: 'y-percentage'
                },
                {
                    label: 'Tardiness %',
                    data: <?= json_encode($chartData['tardiness_percentage']) ?>,
                    borderColor: 'rgba(234, 179, 8, 0.7)',
                    borderWidth: 2,
                    borderDash: [5, 5],
                    pointRadius: 3,
                    pointHoverRadius: 5,
                    yAxisID: 'y-percentage'
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
                'y-count': {
                    type: 'linear',
                    display: true,
                    position: 'left',
                    title: {
                        display: true,
                        text: 'Count',
                        color: '#9CA3AF'
                    },
                    beginAtZero: true,
                    grid: {
                        color: 'rgba(55, 65, 81, 0.5)'
                    },
                    ticks: {
                        color: '#9CA3AF',
                        precision: 0
                    }
                },
                'y-percentage': {
                    type: 'linear',
                    display: true,
                    position: 'right',
                    title: {
                        display: true,
                        text: 'Percentage (%)',
                        color: '#9CA3AF'
                    },
                    beginAtZero: true,
                    grid: {
                        drawOnChartArea: false,
                    },
                    ticks: {
                        color: '#9CA3AF',
                        callback: function(value) {
                            return value + '%';
                        },
                        maxTicksLimit: 6
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
                        color: '#9CA3AF',
                        usePointStyle: true,
                        pointStyle: 'line',
                        padding: 20
                    }
                },
                tooltip: {
                    mode: 'index',
                    intersect: false,
                    callbacks: {
                        label: function(context) {
                            let label = context.dataset.label || '';
                            if (label) {
                                label += ': ';
                            }
                            if (context.datasetIndex < 2) {
                                // Count datasets
                                label += context.raw;
                            } else {
                                // Percentage datasets
                                label += context.raw.toFixed(1) + '% of agents';
                            }
                            return label;
                        }
                    }
                }
            }
        }
    });


fetchChartData('30days');

// Time range selector functionality
document.getElementById('timeRange').addEventListener('change', function() {
    const timeRange = this.value;
    fetchChartData(timeRange);
});

function fetchChartData(timeRange) {
    // Show loading state
    combinedChart.data.labels = ['Loading...'];
    combinedChart.data.datasets.forEach(dataset => {
        dataset.data = [0];
    });
    combinedChart.update();

    fetch(`../includes/fetch_chart_data.php?range=${timeRange}`)
        .then(response => {
            if (!response.ok) {
                throw new Error('Network response was not ok');
            }
            return response.json();
        })
        .then(data => {
            if (data.error) {
                throw new Error(data.error);
            }
            updateChart(combinedChart, data);
        })
        .catch(error => {
            console.error('Error fetching chart data:', error);
            // Show error state
            combinedChart.data.labels = ['Error loading data'];
            combinedChart.data.datasets.forEach(dataset => {
                dataset.data = [0];
            });
            combinedChart.update();
        });
}

function updateChart(chart, newData) {
    // Ensure we have all required data
    if (!newData.labels || !newData.absenteeism || !newData.tardiness || 
        !newData.absenteeism_percentage || !newData.tardiness_percentage) {
        throw new Error('Incomplete chart data received');
    }

    chart.data.labels = newData.labels;
    chart.data.datasets[0].data = newData.absenteeism;
    chart.data.datasets[1].data = newData.tardiness;
    chart.data.datasets[2].data = newData.absenteeism_percentage;
    chart.data.datasets[3].data = newData.tardiness_percentage;
    
    // Update chart scales based on time range
    const isDaily = document.getElementById('timeRange').value !== '12months';
    chart.options.scales.x.ticks.maxRotation = isDaily ? 45 : 0;
    chart.options.scales.x.ticks.autoSkip = isDaily;
    
    chart.update();
}
});
</script>

<?php renderFooter(); ?>