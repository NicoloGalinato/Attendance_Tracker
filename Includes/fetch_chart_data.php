<?php
require_once 'config.php';
require_once 'functions.php';

header('Content-Type: application/json');

if (!isLoggedIn() || !isAdmin()) {
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

$range = $_GET['range'] ?? '12months';
$response = [
    'labels' => [],
    'absenteeism' => [],
    'tardiness' => [],
    'absenteeism_percentage' => [],
    'tardiness_percentage' => []
];

try {
    // Get total number of active agents
    $stmt = $pdo->query("SELECT COUNT(*) FROM employees WHERE is_active = 1");
    $totalActiveAgents = $stmt->fetchColumn();
    $totalActiveAgents = max($totalActiveAgents, 1);

    if ($range === '12months') {
        // 12 months data
        $currentDate = new DateTime('first day of this month');
        
        for ($i = 11; $i >= 0; $i--) {
            $monthDate = clone $currentDate;
            $monthDate->sub(new DateInterval("P{$i}M"));
            
            $startDate = $monthDate->format('Y-m-01');
            $endDate = $monthDate->format('Y-m-t');
            
            $response['labels'][] = $monthDate->format('M Y');
            
            // Absenteeism
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM absenteeism WHERE date_of_absent BETWEEN ? AND ?");
            $stmt->execute([$startDate, $endDate]);
            $absentCount = $stmt->fetchColumn();
            $response['absenteeism'][] = (int)$absentCount;
            $response['absenteeism_percentage'][] = round(($absentCount / $totalActiveAgents) * 100, 2);
            
            // Tardiness
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM tardiness WHERE date_of_incident BETWEEN ? AND ?");
            $stmt->execute([$startDate, $endDate]);
            $tardyCount = $stmt->fetchColumn();
            $response['tardiness'][] = (int)$tardyCount;
            $response['tardiness_percentage'][] = round(($tardyCount / $totalActiveAgents) * 100, 2);
        }
    } 
    elseif ($range === '30days') {
        // Last 30 days
        $endDate = new DateTime();
        $startDate = clone $endDate;
        $startDate->sub(new DateInterval('P29D'));
        
        $interval = new DateInterval('P1D');
        $period = new DatePeriod($startDate, $interval, $endDate);
        
        foreach ($period as $date) {
            $day = $date->format('Y-m-d');
            $response['labels'][] = $date->format('M j');
            
            // Absenteeism
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM absenteeism WHERE date_of_absent = ?");
            $stmt->execute([$day]);
            $absentCount = $stmt->fetchColumn();
            $response['absenteeism'][] = (int)$absentCount;
            $response['absenteeism_percentage'][] = round(($absentCount / $totalActiveAgents) * 100, 2);
            
            // Tardiness
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM tardiness WHERE date_of_incident = ?");
            $stmt->execute([$day]);
            $tardyCount = $stmt->fetchColumn();
            $response['tardiness'][] = (int)$tardyCount;
            $response['tardiness_percentage'][] = round(($tardyCount / $totalActiveAgents) * 100, 2);
        }
    }
    elseif ($range === '7days') {
        // Last 7 days
        $endDate = new DateTime();
        $startDate = clone $endDate;
        $startDate->sub(new DateInterval('P6D'));
        
        $interval = new DateInterval('P1D');
        $period = new DatePeriod($startDate, $interval, $endDate);
        
        foreach ($period as $date) {
            $day = $date->format('Y-m-d');
            $response['labels'][] = $date->format('D, M j');
            
            // Absenteeism
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM absenteeism WHERE date_of_absent = ?");
            $stmt->execute([$day]);
            $absentCount = $stmt->fetchColumn();
            $response['absenteeism'][] = (int)$absentCount;
            $response['absenteeism_percentage'][] = round(($absentCount / $totalActiveAgents) * 100, 2);
            
            // Tardiness
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM tardiness WHERE date_of_incident = ?");
            $stmt->execute([$day]);
            $tardyCount = $stmt->fetchColumn();
            $response['tardiness'][] = (int)$tardyCount;
            $response['tardiness_percentage'][] = round(($tardyCount / $totalActiveAgents) * 100, 2);
        }
    }
} catch (PDOException $e) {
    $response['error'] = 'Database error: ' . $e->getMessage();
}

echo json_encode($response);