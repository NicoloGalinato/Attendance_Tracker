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
        // Get the record from database
        $stmt = $pdo->prepare("SELECT * FROM $type WHERE id = ?");
        $stmt->execute([$id]);
        $record = $stmt->fetch();
        
        if (!$record) {
            die('Record not found');
        }
        
        // Create PHPMailer instance
        $mail = new PHPMailer(true);
        
        // SMTP Configuration for Brevo
        $mail->isSMTP();
        $mail->Host = 'smtp-relay.brevo.com';
        $mail->SMTPAuth = true;
        $mail->Username = '93613c002@smtp-brevo.com'; // Replace with your Brevo email
        $mail->Password = '67qpgPsHfhTnx2NM'; // Replace with your Brevo SMTP password
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port = 587;
        
        // Sender and recipient
        $mail->setFrom('nicolo.galinato@communixinc.com', 'Test email'); // Reply-to
        $mail->addAddress($record['email']); // To
        $mail->addCC('nicolo.galinato@communixinc.com'); // CC
        
        
        // Email content
        if ($type === 'absenteeism') {
            $subject = "ABSENCE / NCNS / CWD - " . strtoupper($record['full_name']) . " - " . date('M d, Y', strtotime($record['date_of_absent']));
            
            $body = "
            <html>
            <body>
                <p>Dear " . htmlspecialchars($record['full_name']) . ",</p>
                
                <p>I hope this email finds you well. This is to keep track of the attendance infractions incurred.
                Please find the details below:</p>
                
                <p><strong>Employee ID:</strong> " . htmlspecialchars($record['employee_id']) . "<br>
                <strong>Name of Employee:</strong> " . htmlspecialchars($record['full_name']) . "<br>
                <strong>DEPARTMENT:</strong> " . htmlspecialchars($record['department']) . "<br>
                <strong>SUPERVISOR:</strong> " . htmlspecialchars($record['supervisor']) . "<br>
                <strong>OM:</strong> " . htmlspecialchars($record['om']) . "<br>
                <strong>Date of Absenteeism:</strong> " . date('M d, Y', strtotime($record['date_of_absent'])) . "<br>
                <strong>Scheduled Shift:</strong> " . htmlspecialchars($record['shift']) . "<br>
                <strong>Reason for Absence:</strong> " . htmlspecialchars($record['reason']) . "<br>
                <strong>Received advise in SLT number:</strong> " . htmlspecialchars($record['slt_number']) . "</p>
                
                <p>We understand that unforeseen circumstances may arise occasionally, resulting in unavoidable absences/late arrivals.</p>
                
                <p>If any personal or professional challenges are affecting your attendance, please don't hesitate to discuss them with your supervisor.</p>
                
                <p>Remember that consistent punctuality and attendance are crucial for your professional development and overall success within our organization. It also demonstrates your commitment to your responsibilities and the team.</p>
                
                <p>If you have any questions or concerns, you may always reach out to our SLT email at <a href=\"mailto:cxl-slm@communityinc.com\">cxl-slm@communityinc.com</a> or the following hotlines:</p>
                
                <p>Mana #: 0931-107-2077</p>
                
                <p>Best regards,<br>
                HR Department</p>
            </body>
            </html>
            ";
        } else { // Tardiness
            $subject = "TARDINESS - " . strtoupper($record['full_name']) . " - " . date('M d, Y', strtotime($record['date_of_incident']));
            
            $body = "
            <html>
            <body>
                <p>Dear " . htmlspecialchars($record['full_name']) . ",</p>
                
                <p>I hope this email finds you well. This is to keep track of the attendance infractions incurred. Please find the details below:</p>
                
                <p><strong>Employee ID:</strong> " . htmlspecialchars($record['employee_id']) . "<br>
                <strong>Name of Employee:</strong> " . htmlspecialchars($record['full_name']) . "<br>
                <strong>DEPARTMENT:</strong> " . htmlspecialchars($record['department']) . "<br>
                <strong>SUPERVISOR:</strong> " . htmlspecialchars($record['supervisor']) . "<br>
                <strong>OM:</strong> " . htmlspecialchars($record['om']) . "<br>
                <strong>Date of Tardiness:</strong> " . date('M d, Y', strtotime($record['date_of_incident'])) . "<br>
                <strong>Scheduled Shift:</strong> " . htmlspecialchars($record['shift']) . "<br>
                <strong>Time IN:</strong> " . htmlspecialchars($record['time_in']) . "<br>
                <strong>Minutes of Late:</strong> " . htmlspecialchars($record['minutes_late']) . " minutes</p>
                
                <p>We understand that unforeseen circumstances may arise occasionally, resulting in unavoidable absences/late arrivals.</p>
                
                <p>If any personal or professional challenges are affecting your attendance, please don't hesitate to discuss them with your supervisor.</p>
                
                <p>Remember that consistent punctuality and attendance are crucial for your professional development and overall success within our organization. It also demonstrates your commitment to your responsibilities and the team.</p>
                
                <p>If you have any questions or concerns, you may always reach out to our SLT email at <a href=\"mailto:cxi-sim@communityline.com\">cxi-sim@communityline.com</a> or the following hotlines:</p>
                
                <p>Mana #: 0931-107-2077</p>
                
                <p>Best regards,<br>
                HR Department</p>
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
        $updateStmt = $pdo->prepare("UPDATE $type SET email_sent = 1, email_sent_at = NOW() WHERE id = ?");
        $updateStmt->execute([$id]);
        
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