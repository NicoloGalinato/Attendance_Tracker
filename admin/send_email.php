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

        // Get the record from database
        $stmt = $pdo->prepare("SELECT * FROM users WHERE sub_name = ?");
        $stmt->execute([$record['sub_name']]);
        $record2 = $stmt->fetch();
        
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
        $mail->setFrom('nicolo.galinato@communixinc.com', 'CXI Service Level Management'); // Reply-to
        $mail->addAddress($record['email']); // To
        $mail->addCC('nicolo.galinato@communixinc.com'); // CC
        
        
        // Email content
        if ($type === 'absenteeism') {
            $subject =  strtoupper($record['sanction']) . " - " . strtoupper($record['full_name']) . " - " . date('M d, Y', strtotime($record['date_of_absent']));
            
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
                                <img src=\"https://www.pngarts.com/files/10/Vector-Email-Icon-Transparent-Images.png\" width=\"16\" height=\"16\" style=\"vertical-align: middle; margin-right: 5px;\">
                                <a href=" . htmlspecialchars($record2['fullname']) . " style=\"color: #0066cc; text-decoration: none;\">" . htmlspecialchars($record2['slt_email']) . "</a>
                            </p>
                            <p style=\"margin: 5px 0 0 0;\">
                                <img src=\"https://cdn-icons-png.flaticon.com/512/44/44386.png\" width=\"16\" height=\"16\" style=\"vertical-align: middle; margin-right: 5px;\">
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
                <p>Dear " . htmlspecialchars($record['full_name']) . ",</p>
                
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
                                <img src=\"https://www.pngarts.com/files/10/Vector-Email-Icon-Transparent-Images.png\" width=\"16\" height=\"16\" style=\"vertical-align: middle; margin-right: 5px;\">
                                <a href=" . htmlspecialchars($record2['fullname']) . " style=\"color: #0066cc; text-decoration: none;\">" . htmlspecialchars($record2['slt_email']) . "</a>
                            </p>
                            <p style=\"margin: 5px 0 0 0;\">
                                <img src=\"https://cdn-icons-png.flaticon.com/512/44/44386.png\" width=\"16\" height=\"16\" style=\"vertical-align: middle; margin-right: 5px;\">
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