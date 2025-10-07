<?php
require_once 'config.php';
require_once 'functions.php';

header('Content-Type: application/json');

if (!isLoggedIn() || !isAdmin()) {
    echo json_encode(['error' => 'Unauthorized access']);
    exit();
}

try {
    $range = $_GET['range'] ?? '30days';
    $chartData = [
        'labels' => [],
        'absenteeism' => [],
        'tardiness' => [],
        'absenteeism_percentage' => [],
        'tardiness_percentage' => []
    ];

    // Get total number of active agents for percentage calculation
    $stmt = $pdo->query("SELECT COUNT(*) FROM employees WHERE is_active = 1");
    $totalActiveAgents = $stmt->fetchColumn();
    $totalActiveAgents = max($totalActiveAgents, 1);

    switch ($range) {
        case '7days':
            // Last 7 days
            for ($i = 6; $i >= 0; $i--) {
                $date = date('Y-m-d', strtotime("-$i days"));
                $chartData['labels'][] = date('M j', strtotime($date));
                
                // Absenteeism
                $stmt = $pdo->prepare("SELECT COUNT(*) FROM absenteeism WHERE date_of_absent = ?");
                $stmt->execute([$date]);
                $absentCount = $stmt->fetchColumn();
                $chartData['absenteeism'][] = $absentCount;
                $chartData['absenteeism_percentage'][] = round(($absentCount / $totalActiveAgents) * 100, 2);
                
                // Tardiness
                $stmt = $pdo->prepare("SELECT COUNT(*) FROM tardiness WHERE date_of_incident = ?");
                $stmt->execute([$date]);
                $tardyCount = $stmt->fetchColumn();
                $chartData['tardiness'][] = $tardyCount;
                $chartData['tardiness_percentage'][] = round(($tardyCount / $totalActiveAgents) * 100, 2);
            }
            break;

        case '30days':
            // Last 30 days
            for ($i = 29; $i >= 0; $i--) {
                $date = date('Y-m-d', strtotime("-$i days"));
                $chartData['labels'][] = date('M j', strtotime($date));
                
                // Absenteeism
                $stmt = $pdo->prepare("SELECT COUNT(*) FROM absenteeism WHERE date_of_absent = ?");
                $stmt->execute([$date]);
                $absentCount = $stmt->fetchColumn();
                $chartData['absenteeism'][] = $absentCount;
                $chartData['absenteeism_percentage'][] = round(($absentCount / $totalActiveAgents) * 100, 2);
                
                // Tardiness
                $stmt = $pdo->prepare("SELECT COUNT(*) FROM tardiness WHERE date_of_incident = ?");
                $stmt->execute([$date]);
                $tardyCount = $stmt->fetchColumn();
                $chartData['tardiness'][] = $tardyCount;
                $chartData['tardiness_percentage'][] = round(($tardyCount / $totalActiveAgents) * 100, 2);
            }
            break;

        case '12months':
        default:
            // Last 12 months
            $currentDate = new DateTime('first day of this month');
            
            for ($i = 11; $i >= 0; $i--) {
                $monthDate = clone $currentDate;
                $monthDate->sub(new DateInterval("P{$i}M"));
                
                $startDate = $monthDate->format('Y-m-01');
                $endDate = $monthDate->format('Y-m-t');
                
                $chartData['labels'][] = $monthDate->format('M Y');
                
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
            break;
    }

    echo json_encode($chartData);

} catch (Exception $e) {
    error_log("Error in fetch_chart_data.php: " . $e->getMessage());
    echo json_encode(['error' => 'Failed to fetch chart data']);
}
?>