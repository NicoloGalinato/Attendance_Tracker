<?php
include ('../connection.php');

$year = date('Y'); 

$sql = "SELECT 
            MONTH(STR_TO_DATE(Timestamp, '%c/%e/%Y %H:%i:%s')) AS month,
            COUNT(CASE WHEN Issues_Concerning = 'Mouse' THEN 1 END) AS mouse_count,
            COUNT(CASE WHEN Issues_Concerning = 'Keyboard' THEN 1 END) AS keyboard_count,
            COUNT(CASE WHEN Issues_Concerning = 'Monitor' THEN 1 END) AS monitor_count,
            COUNT(CASE WHEN Issues_Concerning = 'Internet' THEN 1 END) AS internet_count,
            COUNT(CASE WHEN Issues_Concerning = 'NT Login Issue' THEN 1 END) AS nt_login_count,
            COUNT(CASE WHEN Issues_Concerning = 'Client Tool/System Issue' THEN 1 END) AS client_tool_count,
            COUNT(CASE WHEN Issues_Concerning = 'Full Storage' THEN 1 END) AS full_storage_count,
            COUNT(CASE WHEN Issues_Concerning = 'Windows tools error' THEN 1 END) AS windows_tools_count,
            COUNT(CASE WHEN Issues_Concerning = 'Other' THEN 1 END) AS other_count
        FROM ticket
        WHERE YEAR(STR_TO_DATE(Timestamp, '%c/%e/%Y %H:%i:%s')) = ?
        GROUP BY month
        ORDER BY month ASC";

$stmt = $con->prepare($sql);
$stmt->bind_param("i", $year);
$stmt->execute();
$result = $stmt->get_result();

$months = ["jan", "feb", "mar", "apr", "may", "jun", "jul", "aug", "sep", "oct", "nov", "dec"];
foreach ($months as $m) {
    ${$m . "Mouse"} = 0;
    ${$m . "Keyboard"} = 0;
    ${$m . "Monitor"} = 0;
    ${$m . "Internet"} = 0;
    ${$m . "NT"} = 0;
    ${$m . "CTSI"} = 0;
    ${$m . "FS"} = 0;
    ${$m . "WTC"} = 0;
    ${$m . "Other"} = 0;
}

while ($row = $result->fetch_assoc()) {
    $monthIndex = $row["month"] - 1; 
    $monthPrefix = $months[$monthIndex];

    ${$monthPrefix . "Mouse"} = $row["mouse_count"];
    ${$monthPrefix . "Keyboard"} = $row["keyboard_count"];
    ${$monthPrefix . "Monitor"} = $row["monitor_count"];
    ${$monthPrefix . "Internet"} = $row["internet_count"];
    ${$monthPrefix . "NT"} = $row["nt_login_count"];
    ${$monthPrefix . "CTSI"} = $row["client_tool_count"];
    ${$monthPrefix . "FS"} = $row["full_storage_count"];
    ${$monthPrefix . "WTC"} = $row["windows_tools_count"];
    ${$monthPrefix . "Other"} = $row["other_count"];
}

$mondayCount = 0;
$tuesdayCount = 0;
$wednesdayCount = 0;
$thursdayCount = 0;
$fridayCount = 0;
$saturdayCount = 0;
$sundayCount = 0;


if (isset($_GET['week_beginning'])) {
    $selectedWeek = $_GET['week_beginning'];

    $monday = DateTime::createFromFormat('m/d/Y', $selectedWeek);
    if (!$monday) {
        echo "Invalid date format.";
        exit;
    }

    
    $weekDates = [];
    for ($i = 0; $i < 7; $i++) {
        $day = clone $monday;
        $day->modify("+$i days");
        $weekDates[$day->format('l')] = $day->format('Y-m-d');
    }

    
    $startDate = $monday->format('Y-m-d') . " 00:00:00";
    $endDate = (clone $monday)->modify('+6 days')->format('Y-m-d') . " 23:59:59"; 

    
    $sql = "SELECT Timestamp, Issues_Concerning FROM ticket
            WHERE STR_TO_DATE(Timestamp, '%m/%d/%Y %H:%i:%s') 
            BETWEEN '$startDate' AND '$endDate'";

    $result = $con->query($sql);

    
    $dayCounts = [
        'Monday' => ['Mouse' => 0, 'Keyboard' => 0, 'Monitor' => 0, 'Internet' => 0, 'NT' => 0, 'CTSI' => 0, 'FS' => 0, 'WTC' => 0, 'Other' => 0],
        'Tuesday' => ['Mouse' => 0, 'Keyboard' => 0, 'Monitor' => 0, 'Internet' => 0, 'NT' => 0, 'CTSI' => 0, 'FS' => 0, 'WTC' => 0, 'Other' => 0],
        'Wednesday' => ['Mouse' => 0, 'Keyboard' => 0, 'Monitor' => 0, 'Internet' => 0, 'NT' => 0, 'CTSI' => 0, 'FS' => 0, 'WTC' => 0, 'Other' => 0],
        'Thursday' => ['Mouse' => 0, 'Keyboard' => 0, 'Monitor' => 0, 'Internet' => 0, 'NT' => 0, 'CTSI' => 0, 'FS' => 0, 'WTC' => 0, 'Other' => 0],
        'Friday' => ['Mouse' => 0, 'Keyboard' => 0, 'Monitor' => 0, 'Internet' => 0, 'NT' => 0, 'CTSI' => 0, 'FS' => 0, 'WTC' => 0, 'Other' => 0],
        'Saturday' => ['Mouse' => 0, 'Keyboard' => 0, 'Monitor' => 0, 'Internet' => 0, 'NT' => 0, 'CTSI' => 0, 'FS' => 0, 'WTC' => 0, 'Other' => 0],
        'Sunday' => ['Mouse' => 0, 'Keyboard' => 0, 'Monitor' => 0, 'Internet' => 0, 'NT' => 0, 'CTSI' => 0, 'FS' => 0, 'WTC' => 0, 'Other' => 0],
    ];

   
    while ($row = $result->fetch_assoc()) {
        $ts = DateTime::createFromFormat('m/d/Y H:i:s', $row['Timestamp']);
        if ($ts) {
            $day = $ts->format('l'); 
            $issueType = $row['Issues_Concerning']; 

        
            if (isset($dayCounts[$day][$issueType])) {
                $dayCounts[$day][$issueType]++;
            }
        }
    }

   
    $mondaymouseCount = $dayCounts['Monday']['Mouse'];
    $mondaykeyboardCount = $dayCounts['Monday']['Keyboard'];
    $mondaymonitorCount = $dayCounts['Monday']['Monitor'];
    $mondayinternetCount = $dayCounts['Monday']['Internet'];
    $mondayntCount = $dayCounts['Monday']['NT'];
    $mondayctsiCount = $dayCounts['Monday']['CTSI'];
    $mondayfsCount = $dayCounts['Monday']['FS'];
    $mondaywtcCount = $dayCounts['Monday']['WTC'];
    $mondayotherCount = $dayCounts['Monday']['Other'];

    $tuesdaymouseCount = $dayCounts['Tuesday']['Mouse'];
    $tuesdaykeyboardCount = $dayCounts['Tuesday']['Keyboard'];
    $tuesdaymonitorCount = $dayCounts['Tuesday']['Monitor'];
    $tuesdayinternetCount = $dayCounts['Tuesday']['Internet'];
    $tuesdayntCount = $dayCounts['Tuesday']['NT'];
    $tuesdayctsiCount = $dayCounts['Tuesday']['CTSI'];
    $tuesdayfsCount = $dayCounts['Tuesday']['FS'];
    $tuesdaywtcCount = $dayCounts['Tuesday']['WTC'];
    $tuesdayotherCount = $dayCounts['Tuesday']['Other'];

    $wednesdaymouseCount = $dayCounts['Wednesday']['Mouse'];
    $wednesdaykeyboardCount = $dayCounts['Wednesday']['Keyboard'];
    $wednesdaymonitorCount = $dayCounts['Wednesday']['Monitor'];
    $wednesdayinternetCount = $dayCounts['Wednesday']['Internet'];
    $wednesdayntCount = $dayCounts['Wednesday']['NT'];
    $wednesdayctsiCount = $dayCounts['Wednesday']['CTSI'];
    $wednesdayfsCount = $dayCounts['Wednesday']['FS'];
    $wednesdaywtcCount = $dayCounts['Wednesday']['WTC'];
    $wednesdayotherCount = $dayCounts['Wednesday']['Other'];

    $thursdaymouseCount = $dayCounts['Thursday']['Mouse'];
    $thursdaykeyboardCount = $dayCounts['Thursday']['Keyboard'];
    $thursdaymonitorCount = $dayCounts['Thursday']['Monitor'];
    $thursdayinternetCount = $dayCounts['Thursday']['Internet'];
    $thursdayntCount = $dayCounts['Thursday']['NT'];
    $thursdayctsiCount = $dayCounts['Thursday']['CTSI'];
    $thursdayfsCount = $dayCounts['Thursday']['FS'];
    $thursdaywtcCount = $dayCounts['Thursday']['WTC'];
    $thursdayotherCount = $dayCounts['Thursday']['Other'];

    $fridaymouseCount = $dayCounts['Friday']['Mouse'];
    $fridaykeyboardCount = $dayCounts['Friday']['Keyboard'];
    $fridaymonitorCount = $dayCounts['Friday']['Monitor'];
    $fridayinternetCount = $dayCounts['Friday']['Internet'];
    $fridayntCount = $dayCounts['Friday']['NT'];
    $fridayctsiCount = $dayCounts['Friday']['CTSI'];
    $fridayfsCount = $dayCounts['Friday']['FS'];
    $fridaywtcCount = $dayCounts['Friday']['WTC'];
    $fridayotherCount = $dayCounts['Friday']['Other'];

    $saturdaymouseCount = $dayCounts['Saturday']['Mouse'];
    $saturdaykeyboardCount = $dayCounts['Saturday']['Keyboard'];
    $saturdaymonitorCount = $dayCounts['Saturday']['Monitor'];
    $saturdayinternetCount = $dayCounts['Saturday']['Internet'];
    $saturdayntCount = $dayCounts['Saturday']['NT'];
    $saturdayctsiCount = $dayCounts['Saturday']['CTSI'];
    $saturdayfsCount = $dayCounts['Saturday']['FS'];
    $saturdaywtcCount = $dayCounts['Saturday']['WTC'];
    $saturdayotherCount = $dayCounts['Saturday']['Other'];

    $sundaymouseCount = $dayCounts['Sunday']['Mouse'];
    $sundaykeyboardCount = $dayCounts['Sunday']['Keyboard'];
    $sundaymonitorCount = $dayCounts['Sunday']['Monitor'];
    $sundayinternetCount = $dayCounts['Sunday']['Internet'];
    $sundayntCount = $dayCounts['Sunday']['NT'];
    $sundayctsiCount = $dayCounts['Sunday']['CTSI'];
    $sundayfsCount = $dayCounts['Sunday']['FS'];
    $sundaywtcCount = $dayCounts['Sunday']['WTC'];
    $sundayotherCount = $dayCounts['Sunday']['Other'];

    $mondayCount = 
        $mondaykeyboardCount + 
        $mondaymouseCount +
        $mondaymonitorCount +
        $mondayinternetCount +
        $mondayntCount +
        $mondayctsiCount +
        $mondayfsCount +
        $mondaywtcCount +
        $mondayotherCount;
        
    $tuesdayCount = 
        $tuesdaykeyboardCount + 
        $tuesdaymouseCount +
        $tuesdaymonitorCount +
        $tuesdayinternetCount +
        $tuesdayntCount +
        $tuesdayctsiCount +
        $tuesdayfsCount +
        $tuesdaywtcCount +
        $tuesdayotherCount;
        
    $wednesdayCount = 
        $wednesdaykeyboardCount + 
        $wednesdaymouseCount +
        $wednesdaymonitorCount +
        $wednesdayinternetCount +
        $wednesdayntCount +
        $wednesdayctsiCount +
        $wednesdayfsCount +
        $wednesdaywtcCount +
        $wednesdayotherCount;
        
    $thursdayCount = 
        $thursdaykeyboardCount + 
        $thursdaymouseCount +
        $thursdaymonitorCount +
        $thursdayinternetCount +
        $thursdayntCount +
        $thursdayctsiCount +
        $thursdayfsCount +
        $thursdaywtcCount +
        $thursdayotherCount;
        
    $fridayCount = 
        $fridaykeyboardCount + 
        $fridaymouseCount +
        $fridaymonitorCount +
        $fridayinternetCount +
        $fridayntCount +
        $fridayctsiCount +
        $fridayfsCount +
        $fridaywtcCount +
        $fridayotherCount;
        
    $saturdayCount = 
        $saturdaykeyboardCount + 
        $saturdaymouseCount +
        $saturdaymonitorCount +
        $saturdayinternetCount +
        $saturdayntCount +
        $saturdayctsiCount +
        $saturdayfsCount +
        $saturdaywtcCount +
        $saturdayotherCount;
        
    $sundayCount = 
        $sundaykeyboardCount + 
        $sundaymouseCount +
        $sundaymonitorCount +
        $sundayinternetCount +
        $sundayntCount +
        $sundayctsiCount +
        $sundayfsCount +
        $sundaywtcCount +
        $sundayotherCount;

}



?>
