<?php

$host = "localhost";
$dbname = "auth_system";
$username = "root";
$password = "";

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("Database connection failed: " . $e->getMessage());
    die("
        
    ");
}

include_once('submit_ticket.php');

date_default_timezone_set('Asia/Manila');
$timenow = date('g:i A');
$datenow = date('F d, Y');
$timestamp = date('n/j/Y H:i:s');

$employeeDetails = null;
$error = null;
$successMessage = null;

$currentDate = new DateTime();
$currentDate->modify('this week monday');
$mondayDate = $currentDate->format('m/d/Y');


if (isset($_POST['submitEID'])) {
    $eid = trim($_POST['eid'] ?? '');

    if (!empty($eid)) {
        try {
            $stmtSlt = $pdo->prepare("SELECT username FROM users WHERE username = :eid");
            $stmtSlt->execute(['eid' => $eid]);
            $sltAccount = $stmtSlt->fetch();

            if ($sltAccount) {
                header("Location: ../index.php?eid=" . urlencode($eid));
                exit();
            }

            $stmt = $pdo->prepare("SELECT * FROM ticket_users WHERE EID = :eid");
            $stmt->execute(['eid' => $eid]);
            $employeeDetails = $stmt->fetch();

            if (!$employeeDetails) {
                $error = "No employee found with ID: " . htmlspecialchars($eid) . ". Please check the ID and try again.";
            }
        } catch (PDOException $e) {
            error_log("Employee lookup failed: " . $e->getMessage());
            $error = "An error occurred while fetching employee details. Please try again.";
        }
    } else {
        $error = "Employee ID is required. Please enter your ID to proceed.";
    }
}


if (isset($_POST['submitTix'])) {
    
    if (!$employeeDetails && isset($_POST['EID'])) {
        try {
            $stmt = $pdo->prepare("SELECT * FROM ticket_users WHERE EID = :eid");
            $stmt->execute(['eid' => $_POST['EID']]);
            $employeeDetails = $stmt->fetch();
        } catch (PDOException $e) {
            error_log("Employee re-lookup failed during ticket submission: " . $e->getMessage());
            $error = "An error occurred. Please try re-entering your EID.";
        }
    }

    if ($employeeDetails) {
        $stationNumber = trim($_POST['Station_Number'] ?? '');
        $issue = trim($_POST['Issues_Concerning'] ?? '');
        
        $issueDetails = trim($_POST['Issue_Details'] ?? ''); 
        $site = trim($_POST['Site'] ?? '');
        $urgency = trim($_POST['urgency'] ?? '');

        
        if (empty($stationNumber) || empty($issue) || empty($site) || empty($urgency) || empty($issueDetails)) {
            $error = "Please fill in all required ticket details, including elaborating on the issue.";
        } else {
            $ticketData = [
                'Station_Number' => $stationNumber,
                'Issues_Concerning' => $issue,
                'Issue_Details' => $issueDetails, 
                'Site' => $site,
                'urgency' => $urgency,
                'Timestamp' => $timestamp,
                'EID' => $employeeDetails['EID'] ?? 'N/A',
                'Affected_employee' => 'Individual',
                'Employee_name' => $employeeDetails['name'] ?? 'N/A',
                'Email_Address' => $employeeDetails['email'] ?? 'N/A',
                'Department' => $employeeDetails['department'] ?? 'N/A',
                'LOB' => $employeeDetails['LOB'] ?? 'N/A',
                'OM' => $employeeDetails['OM'] ?? 'N/A',
                'TIME_RECEIVED' => $timenow,
                'TIME_RESOLVED' => 'PENDING',
                'SLT_on_DUTY' => 'PENDING',
                'Week_Beginning' => $mondayDate,
                'Status' => 'PENDING',
            ];

            if (function_exists('submit_ticket')) {
                $ticketSubmissionResult = submit_ticket($pdo, $ticketData);
                if ($ticketSubmissionResult === true) {
                    $lastId = $pdo->lastInsertId();
                    $successMessage = "Your ticket has been submitted successfully! We'll get back to you shortly. Your Ticket ID is #". $lastId . ".";
                    
                    $employeeDetails = null;
                    $_POST = [];
                } else {
                    $error = $ticketSubmissionResult ?: "Failed to submit ticket. Please try again.";
                }
            } 
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en" class="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Submit a Ticket | CXI Services Inc.</title>
    <link rel="icon" href="assets/cxiico.png" type="image/png">
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
    <style>
        .bg-auth {
            background-image: linear-gradient(to right, rgba(15, 23, 42, 0.9), rgba(15, 23, 42, 0.9)), 
                              url('https://source.unsplash.com/random/1920x1080/?abstract,dark');
            background-size: cover;
            background-position: center;
        }
    </style>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="bg-auth min-h-screen flex items-center justify-center p-4 font-sans">
    <div class="w-full max-w-lg">
        <div class="bg-gray-800/80 backdrop-blur-sm rounded-xl shadow-xl overflow-hidden border border-gray-700/50">
            <div class="p-8">
                <div class="flex justify-center mb-8">
                    <img src="img/logbg1.jpg" alt="CXI Services Inc.">
                </div>
                <h2 class="text-gray-400 text-center text-2xl font-semibold mb-6">Submit a Support Ticket</h2>
                
                <?php if ($successMessage): ?>
                    <div class="bg-green-500/10 border border-green-500/30 text-green-300 px-4 py-3 rounded-lg mb-6 text-sm flex items-center gap-2">
                        <i class="fas fa-check-circle"></i> <span><?= htmlspecialchars($successMessage) ?></span>
                    </div>
                <?php endif; ?>

                <?php if ($error): ?>
                    <div class="bg-red-500/10 border border-red-500/30 text-red-300 px-4 py-3 rounded-lg mb-6 text-sm flex items-center gap-2">
                        <i class="fas fa-exclamation-circle"></i> <span><?= htmlspecialchars($error) ?></span>
                    </div>
                <?php endif; ?>

                <?php if (!$employeeDetails): ?>
                    <form method="POST" class="space-y-6">
                        <p class="text-gray-400 text-center text-sm">Please enter your Employee ID to proceed.</p>
                        <div>
                            <label for="eid" class="block text-sm font-medium text-gray-300 mb-2">Employee ID</label>
                            <div class="relative">
                                <input type="text" id="eid" name="eid" class="w-full px-4 py-3 bg-gray-700/50 border border-gray-600/50 rounded-lg text-gray-200 focus:ring-2 focus:ring-primary-500 focus:border-primary-500 outline-none transition" placeholder="e.g., CXI00001" value="<?= htmlspecialchars($_POST['eid'] ?? '') ?>" required>
                                <div class="absolute inset-y-0 right-0 flex items-center pr-3 pointer-events-none">
                                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="w-5 h-5 text-gray-500">
                                      <path fill-rule="evenodd" d="M9 3.5a5.5 5.5 0 100 11 5.5 5.5 0 000-11zM2 9a7 7 0 1112.452 4.391l3.328 3.329a.75.75 0 11-1.06 1.06l-3.329-3.328A7 7 0 012 9z" clip-rule="evenodd" />
                                    </svg>
                                </div>
                            </div>
                        </div>
                        <button type="submit" name="submitEID" class="w-full py-3 px-4 bg-primary-600 hover:bg-primary-700 text-white font-medium rounded-lg transition duration-200 flex items-center justify-center gap-2">
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="w-5 h-5">
                                <path fill-rule="evenodd" d="M9 3.5a5.5 5.5 0 100 11 5.5 5.5 0 000-11zM2 9a7 7 0 1112.452 4.391l3.328 3.329a.75.75 0 11-1.06 1.06l-3.329-3.328A7 7 0 012 9z" clip-rule="evenodd" />
                            </svg>
                            Search
                        </button>
                    </form>
                <?php else: ?>
                    <div class="text-center mb-6">
                        <h4 class="text-xl font-semibold text-white"><?= htmlspecialchars($employeeDetails['name'] ?? 'N/A') ?></h4>
                        <p class="text-sm text-gray-400"><?= htmlspecialchars($employeeDetails['department'] ?? 'N/A') ?></p>
                        <p class="text-sm text-gray-400"><?= htmlspecialchars($employeeDetails['email'] ?? 'N/A') ?></p>
                    </div>
                    
                    <hr class="border-gray-700/50 my-6">

                    <form id="ticketForm" method="POST" class="space-y-6">
                        <input type="hidden" name="EID" value="<?= htmlspecialchars($employeeDetails['EID'] ?? '') ?>">
                        <input type="hidden" name="Employee_name" value="<?= htmlspecialchars($employeeDetails['name'] ?? 'N/A') ?>">
                        <input type="hidden" name="Email_Address" value="<?= htmlspecialchars($employeeDetails['email'] ?? 'N/A') ?>">
                        <input type="hidden" name="Department" value="<?= htmlspecialchars($employeeDetails['department'] ?? 'N/A') ?>">
                        <input type="hidden" name="LOB" value="<?= htmlspecialchars($employeeDetails['LOB'] ?? 'N/A') ?>">
                        <input type="hidden" name="OM" value="<?= htmlspecialchars($employeeDetails['OM'] ?? 'N/A') ?>">
                        <input type="hidden" name="Timestamp" value="<?= htmlspecialchars($timestamp) ?>">
                        <input type="hidden" name="TIME_RECEIVED" value="<?= htmlspecialchars($timenow) ?>">
                        <input type="hidden" name="TIME_RESOLVED" value="PENDING">
                        <input type="hidden" name="SLT_on_DUTY" value="PENDING">
                        <input type="hidden" name="Week_Beginning" value="<?= htmlspecialchars($mondayDate) ?>">
                        <input type="hidden" name="Status" value="PENDING">
                        <input type="hidden" name="Affected_employee" value="Individual">

                        <div>
                            <label for="stationNumber" class="block text-sm font-medium text-gray-300 mb-2">Station Number</label>
                            <input class="w-full px-4 py-3 bg-gray-700/50 border border-gray-600/50 rounded-lg text-gray-200 focus:ring-2 focus:ring-primary-500 focus:border-primary-500 outline-none transition" type="text" id="stationNumber" name="Station_Number" placeholder="e.g., STN0000" required>
                        </div>

                        <div>
                            <label for="issues" class="block text-sm font-medium text-gray-300 mb-2">Select an Issue</label>
                            <select class="w-full px-4 py-3 bg-gray-700/50 border border-gray-600/50 rounded-lg text-gray-200 focus:ring-2 focus:ring-primary-500 focus:border-primary-500 outline-none transition" id="issues" name="Issues_Concerning" required>
                                <option value="" selected disabled>Choose an issue...</option>
                                <option value="Keyboard">Keyboard</option>
                                <option value="Mouse">Mouse</option>
                                <option value="Headset">Headset</option>
                                <option value="Monitor">Monitor</option>
                                <option value="Internet">Internet</option>
                                <option value="NT Login Issue">NT Login Issue</option>
                                <option value="Client Tool/System Issue">Client Tool/System Issue</option>
                                <option value="Full Storage">Full Storage</option>
                                <option value="Windows tools error">Windows tools error</option>
                                <option value="Other">Other</option>
                            </select>
                            <div id="troubleshootingTip" class="mt-2 p-3 text-sm bg-primary-900/40 border-l-4 border-primary-500 text-gray-300 rounded-md" style="display: none;"></div>
                        </div>

                        <div>
                            <label for="issueDetails" class="block text-sm font-medium text-gray-300 mb-2">Elaborate on the Issue</label>
                            <textarea class="w-full px-4 py-3 bg-gray-700/50 border border-gray-600/50 rounded-lg text-gray-200 focus:ring-2 focus:ring-primary-500 focus:border-primary-500 outline-none transition" id="issueDetails" name="Issue_Details" rows="4" placeholder="Please describe your issue in detail, e.g., 'My mouse is not responding, I've tried restarting the PC but it didn't help.'" required></textarea>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label for="site" class="block text-sm font-medium text-gray-300 mb-2">Site</label>
                                <select class="w-full px-4 py-3 bg-gray-700/50 border border-gray-600/50 rounded-lg text-gray-200 focus:ring-2 focus:ring-primary-500 focus:border-primary-500 outline-none transition" id="site" name="Site" required>
                                    <option value="" selected disabled>Select your site...</option>
                                    <option value="KAWIT">KAWIT</option>
                                    <option value="BACOOR">BACOOR</option>
                                </select>
                            </div>
                            <div>
                                <label for="urgency" class="block text-sm font-medium text-gray-300 mb-2">Urgency Level</label>
                                <select class="w-full px-4 py-3 bg-gray-700/50 border border-gray-600/50 rounded-lg text-gray-200 focus:ring-2 focus:ring-primary-500 focus:border-primary-500 outline-none transition" id="urgency" name="urgency" required>
                                    <option value="" selected disabled>Select urgency...</option>
                                    <option value="High">Priority</option>
                                    <option value="Medium">Moderate</option>
                                    <option value="Low">Minimal</option>
                                </select>
                            </div>
                        </div>

                        <div class="flex flex-col sm:flex-row gap-4 pt-2">
                            <button class="w-full py-3 px-4 bg-primary-600 hover:bg-primary-700 text-white font-medium rounded-lg transition duration-200 flex items-center justify-center gap-2" type="submit" name="submitTix">
                                <i class="fas fa-paper-plane"></i>
                                Submit Ticket
                            </button>
                            <a class="w-full py-3 px-4 border border-gray-600 hover:bg-gray-700 text-gray-300 font-medium rounded-lg transition duration-200 flex items-center justify-center gap-2" href="<?= $_SERVER['PHP_SELF'] ?>">
                                <i class="fas fa-redo"></i>
                                Reset / Submit New
                            </a>
                        </div>
                    </form>
                <?php endif; ?>
            </div>
            
            
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script>
        $(document).ready(function() {
            // Fade out alerts after 5 seconds
            setTimeout(function() {
                $('.bg-red-500\\/10, .bg-green-500\\/10').fadeOut('slow');
            }, 5000);

            // Scroll to alert messages if they exist
            const alerts = document.querySelectorAll('.bg-red-500\\/10, .bg-green-500\\/10');
            if (alerts.length > 0) {
                window.scrollTo({
                    top: alerts[0].offsetTop - 200,
                    behavior: 'smooth'
                });
            }

            // Handle the issue dropdown to show troubleshooting tips
            $('#issues').on('change', function() {
                var selectedIssue = $(this).val();
                var tipContainer = $('#troubleshootingTip');
                var tips = {
                    'Monitor': "Tip: No display? Check if it's turned on. If the problem continues, our team will check it for you.",
                    'Keyboard': "Tip: Not working? Try unplugging the keyboard from the computer and plugging it back in. If the problem continues, our team will check it for you.",
                    'Mouse': "Tip: Not working? Try unplugging the mouse from the computer and plugging it back in. If the problem continues, our team will check it for you.",
                    'Headset': "Tip: No sound? Try unplugging the headset from the computer and plugging it back in. If the problem continues, our team will check it for you.",
                    'Internet': "Tip: Can't connect to the internet? Try replugging the internet cable. If the problem continues, our team will check it for you.",
                    'NT Login Issue': "Locked account? Our team will check it for you and get you notified once fixed. Make sure to elaborate your issue if this is not the problem.",
                    'Client Tool/System Issue': "Tip: Is a program not working? Try closing the program and opening it again. If the problem continues, our team will check it for you.",
                    'Full Storage': "Our team will go to your station to free up some space for you.",
                    'Windows tools error': "Tip: Seeing an error? Try restarting your application. If it doesn't solve the issue, please describe the error message below, our team will check it for you",
                    'Other': "Tip: Can't find your problem on the list? Please describe the issue in the box below as clearly as possible. Our team will check it for you.",
                };
                if (tips[selectedIssue]) {
                    tipContainer.text(tips[selectedIssue]).slideDown();
                } else {
                    tipContainer.slideUp();
                }
            });
        });
    </script>
</body>
</html>