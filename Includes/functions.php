<?php
require_once 'config.php';
require_once 'db.php';

ob_start();

function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

function isAdmin() {
    return isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin';
}

function redirect($url) {
    if (!headers_sent()) {
        header("Location: $url");
        exit();
    } else {
        echo "<script>window.location.href='$url';</script>";
        exit();
    }
}

function sanitizeInput($data) {
    return htmlspecialchars(strip_tags(trim($data)));
}

function updateLastActivity() {
    global $pdo;
    if (isset($_SESSION['user_id'])) {
        $stmt = $pdo->prepare("UPDATE users SET last_activity = NOW() WHERE id = ?");
        $stmt->execute([$_SESSION['user_id']]);
        $_SESSION['last_activity'] = time();
    }
}

function logActivity($description, $recordId = null, $recordType = null) {
    global $pdo;
    
    if (isset($_SESSION['user_id'])) {
        // Get the user's sub_name if not already in session
        if (!isset($_SESSION['sub_name'])) {
            $stmt = $pdo->prepare("SELECT sub_name FROM users WHERE id = ?");
            $stmt->execute([$_SESSION['user_id']]);
            $user = $stmt->fetch();
            $_SESSION['sub_name'] = $user['sub_name'] ?? 'System';
        }

        $stmt = $pdo->prepare("
            INSERT INTO activity_history 
            (user_id, sub_name, activity_description, record_id, record_type) 
            VALUES (?, ?, ?, ?, ?)
        ");
        $stmt->execute([
            $_SESSION['user_id'], 
            $_SESSION['sub_name'], 
            $description,
            $recordId,
            $recordType
        ]);
    }
}

ob_end_flush();
