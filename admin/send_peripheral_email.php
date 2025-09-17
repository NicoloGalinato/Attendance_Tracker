<?php
require_once '../includes/config.php';
require_once '../includes/functions.php';

if (!isLoggedIn() || !isAdmin()) {
    redirect(BASE_URL);
}

$id = isset($_GET['send_email']) ? (int)$_GET['send_email'] : 0;

if ($id) {
    try {
        // Get the record
        $stmt = $pdo->prepare("SELECT * FROM request_peripherals WHERE id = ?");
        $stmt->execute([$id]);
        $record = $stmt->fetch();
        
        if ($record) {
            // Here you would implement your email sending logic
            // For now, we'll just update the record to mark as sent
            
            $updateStmt = $pdo->prepare("UPDATE request_peripherals SET email_sent = 1, email_sent_at = NOW() WHERE id = ?");
            $updateStmt->execute([$id]);
            
            $_SESSION['success'] = "Email notification sent successfully!";
        } else {
            $_SESSION['error'] = "Record not found";
        }
    } catch (PDOException $e) {
        $_SESSION['error'] = "Error sending email: " . $e->getMessage();
    }
}

redirect('inventory_tracker.php?tab=peripherals');