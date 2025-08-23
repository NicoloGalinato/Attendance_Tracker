<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

if (isset($_GET['send_email']) && isset($_GET['type'])) {
    $id = (int)$_GET['send_email'];
    $type = $_GET['type'];
    
    // Validate type
    if (!in_array($type, ['tardiness', 'absenteeism'])) {
        die('Invalid type');
    }
    
    try {
        // Get the record from Employee for details
        $stmt = $pdo->prepare("SELECT * FROM $type WHERE id = ?");
        $stmt->execute([$id]);
        $record = $stmt->fetch();

        if (!$record) {
            die('Record not found');
        }

        // Get the record from users for esignature
        $stmt = $pdo->prepare("SELECT * FROM users WHERE sub_name = ?");
        $stmt->execute([$record['sub_name']]);
        $record2 = $stmt->fetch();
        
        // Get the record from users full_name to separate the first name format
        $stmt = $pdo->prepare("
            SELECT 
                CONCAT(
                    UPPER(SUBSTRING(TRIM(SUBSTRING_INDEX(SUBSTRING_INDEX(full_name, ',', -1), ' ', 2)), 1, 1)),
                    LOWER(SUBSTRING(TRIM(SUBSTRING_INDEX(SUBSTRING_INDEX(full_name, ',', -1), ' ', 2)), 2))
                ) AS first_name 
            FROM $type 
            WHERE full_name = ?;
        ");
        $stmt->execute([$record['full_name']]);
        $record3 = $stmt->fetch();
        
        // Get the record from management for email to
        $stmt = $pdo->prepare("SELECT * FROM management WHERE fullname = ?");
        $stmt->execute([$record['supervisor']]);
        $record4 = $stmt->fetch();
        
        // Get the record from management for email to
        $stmt = $pdo->prepare("SELECT * FROM operations_managers WHERE fullname = ?");
        $stmt->execute([$record['operation_manager']]);
        $record5 = $stmt->fetch();
        
        // Validate all required email addresses
$agentEmail = !empty($record['email']) && filter_var($record['email'], FILTER_VALIDATE_EMAIL) 
    ? $record['email'] 
    : (!empty($record4['email']) && filter_var($record4['email'], FILTER_VALIDATE_EMAIL) 
        ? $record4['email'] 
        : null);

$requiredEmails = array(
    'Agent/Supervisor' => $agentEmail,
    'Operation Manager' => isset($record5['email']) ? $record5['email'] : null
);

foreach ($requiredEmails as $role => $email) {
    if (empty($email)) {
        die("Email address for $role is missing");
    }
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        die("Invalid email format for $role: $email");
    }
}

        // Create PHPMailer instance
        $mail = new PHPMailer(true);
        
        // SMTP Configuration for Brevo
        $mail->isSMTP();
        $mail->Host = 'smtp.gmail.com';
        $mail->SMTPAuth = true;
        $mail->Username = 'cxi-slm@communixinc.com'; // Replace with your Brevo email
        $mail->Password = 'wwrb ohbj ghrm mseo'; // Replace with your Brevo SMTP password
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port = 587;


        // Add these for better reliability
        $mail->SMTPOptions = array(
            'ssl' => array(
                'verify_peer' => false,
                'verify_peer_name' => false,
                'allow_self_signed' => true
            )
        );
        $mail->Timeout = 30;
        
        // Sender and recipient
        $mail->setFrom('cxi-slm@communixinc.com', 'CXI Service Level Management');


        
        // Add validated email addresses - Agent/Supervisor logic
$agentEmail = !empty($record['email']) && filter_var($record['email'], FILTER_VALIDATE_EMAIL) 
    ? $record['email'] 
    : (!empty($record4['email']) && filter_var($record4['email'], FILTER_VALIDATE_EMAIL) 
        ? $record4['email'] 
        : null);

if ($agentEmail) {
    $mail->addAddress($agentEmail); // To Agent (or Supervisor if Agent email is invalid/missing)
}

// Add Operation Manager's email (required)
$mail->addAddress($record5['email']); // To Operation Manager

// Add Supervisor's email separately if it's different from what we already added
if (!empty($record4['email']) && 
    filter_var($record4['email'], FILTER_VALIDATE_EMAIL) && 
    $record4['email'] !== $agentEmail) {
    $mail->addAddress($record4['email']); // To Supervisor (only if not already added)
}

        // Default cc for the bosses
        $ccEmails = [
            'kiko.barrameda@communixinc.com',
            'phay.barrameda@communixinc.com',
            'cxi-slt@communixinc.com',
            'cxi-slm@communixinc.com',
            'ken.munoz@communixinc.com',
            'humanresources@communixinc.com',
            'cxi-hr@communixinc.com',
            'cxi.clinic@communixinc.com'
        ];

        // Remove cxi.clinic@communixinc.com if type is tardiness
        if ($type === 'tardiness') {
            $ccEmails = array_filter($ccEmails, function($email) {
                return $email !== 'cxi.clinic@communixinc.com';
            });
        }

        foreach ($ccEmails as $ccEmail) {
            if (filter_var($ccEmail, FILTER_VALIDATE_EMAIL)) {
                $mail->addCC($ccEmail);
            }
        }
        
        
        // Email content
        if ($type === 'absenteeism') {
            $subject =  strtoupper($record['sanction']) . " - " . strtoupper($record['full_name']) . " - " . date('M d, Y', strtotime($record['date_of_absent']));
            
            $body = "
            <html>
            <body>
                <p>Dear " . htmlspecialchars($record3['first_name']) . ",</p>
                
                <p>I hope this email finds you well. This is to keep track of the attendance infractions incurred.
                Please find the details below:</p>
                
                <p><strong>Employee ID:</strong> " . htmlspecialchars($record['employee_id']) . "<br>
                <strong>Name of Employee:</strong> " . htmlspecialchars($record['full_name']) . "<br>
                <strong>DEPARTMENT:</strong> " . htmlspecialchars($record['department']) . "<br>
                <strong>SUPERVISOR:</strong> " . htmlspecialchars($record['supervisor']) . "<br>
                <strong>OM:</strong> " . htmlspecialchars($record['operation_manager']) . "<br>
                <strong>Date of Absenteeism:</strong> " . date('M d, Y', strtotime($record['date_of_absent'])) . "<br>
                <strong>Scheduled Shift:</strong> " . htmlspecialchars($record['shift']) . "<br>
                <strong>Reason for Absence:</strong> " . htmlspecialchars($record['reason']) . "<br>
                <strong>Received advise in SLT number:</strong> " . htmlspecialchars($record['follow_call_in_procedure']) . "</p>
                
                <p>We understand that unforeseen circumstances may arise occasionally, resulting in unavoidable absences/late arrivals.</p>
                
                <p>If any personal or professional challenges are affecting your attendance, please don't hesitate to discuss them with your supervisor.</p>
                
                <p>Remember that consistent punctuality and attendance are crucial for your professional development and overall success within our organization. It also demonstrates your commitment to your responsibilities and the team.</p>
                
                <p>If you have any questions or concerns, you may always reach out to our SLT email at <a href=\"cxi-slm@communixinc.com \">cxi-slm@communixinc.com </a> or the following hotlines:</p>
                
                <p>Mana #: 0931-107-2077</p>
                
                <p>Best regards,<br></p>
                
                <table border=\"0\" cellspacing=\"0\" cellpadding=\"0\" style=\"margin-top: 15px;\">
                    <tr>
                        <td valign=\"top\" style=\"padding-right: 15px;\">
                            <img src=\"https://lh7-us.googleusercontent.com/hKfBBQPswq1rr28KAdC4A3hrJxQw4kwPsT9_aIPTcLxO5GSreRobUkI6AnEfxbu2A5iircddGLupW7i5J-Ky7Avxq3Fg8rz1qDJWoDcsPBR_ui5hsE6sP09jDrZl7jvnOVonYOPz2ofYiDR4g62vhRY\" alt=\"CXI Logo\" width=\"200\" style=\"display: block;\">
                        </td>
                        <td valign=\"top\">
                            <p style=\"margin: 0; font-weight: bold; color: #333;\">" . htmlspecialchars($record2['fullname']) . "</p>
                            <p style=\"margin: 5px 0 0 0; color: #555;\">CXI Services Inc</p>
                            <p style=\"margin: 5px 0 0 0; color: #555;\">Service Level Technician</p>
                            <p style=\"margin: 5px 0 0 0;\">
                                <img src=\"https://lightpink-cormorant-243207.hostingersite.com/assets/email.png\" width=\"16\" height=\"16\" style=\"vertical-align: middle; margin-right: 5px;\">
                                <a href=" . htmlspecialchars($record2['fullname']) . " style=\"color: #0066cc; text-decoration: none;\">" . htmlspecialchars($record2['slt_email']) . "</a>
                            </p>
                            <p style=\"margin: 5px 0 0 0;\">
                                <img src=\"https://lightpink-cormorant-243207.hostingersite.com/assets/globe.png\" width=\"16\" height=\"16\" style=\"vertical-align: middle; margin-right: 5px;\">
                                <a href=\"https://www.cxiph.com\" style=\"color: #0066cc; text-decoration: none;\">www.cxiph.com</a>
                            </p>
                        </td>
                    </tr>
                </table>
            </body>
            </html>
            ";
        } else { // Tardiness
            $subject = "TARDINESS - " . strtoupper($record['full_name']) . " - " . date('M d, Y', strtotime($record['date_of_incident'])); // SUBJECT
            
            $body = "
            <html>
            <body>
                <p>Dear " . htmlspecialchars($record3['first_name']) . ",</p>
                
                <p>I hope this email finds you well. This is to keep track of the attendance infractions incurred. Please find the details below:</p>
                
                <p><strong>Employee ID:</strong> " . htmlspecialchars($record['employee_id']) . "<br>
                <strong>Name of Employee:</strong> " . htmlspecialchars($record['full_name']) . "<br>
                <strong>DEPARTMENT:</strong> " . htmlspecialchars($record['department']) . "<br>
                <strong>SUPERVISOR:</strong> " . htmlspecialchars($record['supervisor']) . "<br>
                <strong>OM:</strong> " . htmlspecialchars($record['operation_manager']) . "<br>
                <strong>Date of Tardiness:</strong> " . date('M d, Y', strtotime($record['date_of_incident'])) . "<br>
                <strong>Scheduled Shift:</strong> " . htmlspecialchars($record['shift']) . "<br>
                <strong>Time IN:</strong> " . htmlspecialchars($record['time_in']) . "<br>
                <strong>Minutes of Late:</strong> " . htmlspecialchars($record['minutes_late']) . " minutes</p>
                
                <p>We understand that unforeseen circumstances may arise occasionally, resulting in unavoidable absences/late arrivals.</p>
                
                <p>If any personal or professional challenges are affecting your attendance, please don't hesitate to discuss them with your supervisor.</p>
                
                <p>Remember that consistent punctuality and attendance are crucial for your professional development and overall success within our organization. It also demonstrates your commitment to your responsibilities and the team.</p>
                
                <p>If you have any questions or concerns, you may always reach out to our SLT email at <a href=\"cxi-slm@communixinc.com \">cxi-slm@communixinc.com </a> or the following hotlines:</p>
                
                <p>Mana #: 0931-107-2077</p>
                
                <p>Best regards,<br></p>
                
                
                <table border=\"0\" cellspacing=\"0\" cellpadding=\"0\" style=\"margin-top: 15px;\">
                    <tr>
                        <td valign=\"top\" style=\"padding-right: 15px;\">
                            <img src=\"https://lh7-us.googleusercontent.com/hKfBBQPswq1rr28KAdC4A3hrJxQw4kwPsT9_aIPTcLxO5GSreRobUkI6AnEfxbu2A5iircddGLupW7i5J-Ky7Avxq3Fg8rz1qDJWoDcsPBR_ui5hsE6sP09jDrZl7jvnOVonYOPz2ofYiDR4g62vhRY\" alt=\"CXI Logo\" width=\"200\" style=\"display: block;\">
                        </td>
                        <td valign=\"top\">
                            <p style=\"margin: 0; font-weight: bold; color: #333;\">" . htmlspecialchars($record2['fullname']) . "</p>
                            <p style=\"margin: 5px 0 0 0; color: #555;\">CXI Services Inc</p>
                            <p style=\"margin: 5px 0 0 0; color: #555;\">Service Level Technician</p>
                            <p style=\"margin: 5px 0 0 0;\">
                                <img src=\"https://lightpink-cormorant-243207.hostingersite.com/assets/email.png\" width=\"16\" height=\"16\" style=\"vertical-align: middle; margin-right: 5px;\">
                                <a href=" . htmlspecialchars($record2['fullname']) . " style=\"color: #0066cc; text-decoration: none;\">" . htmlspecialchars($record2['slt_email']) . "</a>
                            </p>
                            <p style=\"margin: 5px 0 0 0;\">
                                <img src=\"https://lightpink-cormorant-243207.hostingersite.com/assets/globe.png\" width=\"16\" height=\"16\" style=\"vertical-align: middle; margin-right: 5px;\">
                                <a href=\"https://www.cxiph.com\" style=\"color: #0066cc; text-decoration: none;\">www.cxiph.com</a>
                            </p>
                        </td>
                    </tr>
                </table>
                
            </body>
            </html>
            ";
        }
        
        $mail->isHTML(true);
        $mail->Subject = $subject;
        $mail->Body = $body;
        
        // Send email
        $mail->send();
        
        // Update record to mark email as sent
        $updateStmt = $pdo->prepare("SET time_zone = '+08:00'; UPDATE $type SET email_sent = 1, email_sent_at = NOW() WHERE id = ?");
        $updateStmt->execute([$id]);
        $updateStmt->closeCursor(); // Close the cursor before next query
        
        // Get the record again to ensure we have fresh data
        $stmt = $pdo->prepare("SELECT full_name FROM $type WHERE id = ?");
        $stmt->execute([$id]);
        $record = $stmt->fetch();
        $stmt->closeCursor(); // Close this cursor too
        
        // Log the email activity
        logActivity("Sent email: '{$subject}' to {$record['full_name']}", $id, $type);
    
        
        // Redirect back with success message
        $_SESSION['success'] = "Email sent successfully to " . $record['full_name'];
        redirect('attendance.php?tab=' . $type);
        exit();
                
    } catch (Exception $e) {
        // Redirect back with error message
        $_SESSION['error'] = "Failed to send email: " . $mail->ErrorInfo;
        redirect('attendance.php?tab=' . $type);
        exit();
    }
    
} else {
    die('Invalid request');
}