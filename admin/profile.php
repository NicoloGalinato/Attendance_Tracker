<?php
require_once '../includes/config.php';
require_once '../includes/functions.php';

if (!isLoggedIn() || !isAdmin()) {
    redirect(BASE_URL);
}

$action = isset($_GET['action']) ? $_GET['action'] : '';
$userId = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// Handle form submission
// At the top of the file, after session_start()
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $userId = (int)$_POST['user_id'];
    $empid = strtoupper (sanitizeInput($_POST['empid']));
    $fullname_ed = strtoupper (sanitizeInput($_POST['fullname_ed']));
    $fullname = strtoupper (sanitizeInput($_POST['fullname']));
    $username = sanitizeInput($_POST['username']);
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
    $role = sanitizeInput($_POST['role']);
    

    try {
        $pdo->beginTransaction();

        //to get the ID in the hidden input
        $stmt = $pdo->prepare("SELECT id FROM users WHERE id = ?");
        $stmt->execute([$userId]);


        if ($stmt->rowCount() > 0) {
            // Update existing profile
           $stmt = $pdo->prepare("UPDATE users SET fullname = ? WHERE id = ?");
           $stmt->execute([$fullname_ed, $userId]);
           
        } else {
            // Insert new profile
           $stmt = $pdo->prepare("INSERT INTO users (emp_id, fullname, username, password, role) VALUES (?, ?, ?, ?, ?)");
           $stmt->execute([$empid, $fullname, $username, $password, $role]);

        }


        // Handle password change if new password is provided
        if (!empty($_POST['new_password'])) {
            // Verify new passwords match
            if ($_POST['new_password'] !== $_POST['confirm_password']) {
                throw new Exception("New passwords do not match");
            }
            else {
                // Update password
                $new_hashed_password = password_hash($_POST['new_password'], PASSWORD_DEFAULT);
                $stmt = $pdo->prepare("UPDATE users SET password = ? WHERE id = ?");
                $stmt->execute([$new_hashed_password, $_POST['user_id']]);
            }
        }
        
        $pdo->commit();
        $_SESSION['success'] = "Profile updated successfully";
        redirect(ADMIN_URL . 'users.php');
        
    } catch (Exception $e) {
        $pdo->rollBack();
        $_SESSION['error'] = $e->getMessage();
        redirect(ADMIN_URL . 'profile.php?id=' . $_POST['user_id']);
    }
}

// Get user data
$user = null;
if ($userId > 0) {
    try {
        // Get user
        $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
        $stmt->execute([$userId]);
        $user = $stmt->fetch();
        
        if (!$user) {
            $_SESSION['error'] = "User not found";
            redirect(ADMIN_URL . 'users.php');
        }
        
    } catch (PDOException $e) {
        $_SESSION['error'] = "Error fetching data: " . $e->getMessage();
        redirect(ADMIN_URL . 'users.php');
    }
} elseif ($action !== 'create') {
    redirect(ADMIN_URL . 'users.php');
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $userId ? 'Edit' : 'Add'; ?> User | Auth System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="../assets/css/style.css" rel="stylesheet">
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary">
        <div class="container">
            <a class="navbar-brand" href="#">Auth System</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="index.php">Dashboard</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="users.php">Manage Users</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="../logout.php">Logout</a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <div class="container mt-5">
        <?php if (isset($_SESSION['error'])): ?>
            <div class="alert alert-danger"><?= $_SESSION['error']; unset($_SESSION['error']); ?></div>
        <?php endif; ?>
        
        <div class="card shadow">
            <div class="card-header bg-primary text-white">
                <h4><?= $userId ? 'Edit User Profile' : 'Add New User'; ?></h4>
            </div>
            <div class="card-body">
                <form method="POST">
                    <input type="hidden" name="user_id" value="<?= $userId; ?>">
                    
                    <?php if ($userId): ?>
                        <div class="mb-3">
                            <label class="form-label">Employee ID</label>
                            <input type="text" style="text-transform: uppercase;" class="form-control" value="<?= htmlspecialchars($user['emp_id']); ?>" readonly>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Username</label>
                            <input type="text" class="form-control" value="<?= htmlspecialchars($user['username']); ?>" readonly>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Full Name</label>
                            <input type="text" style="text-transform: uppercase;" class="form-control" id="fullname_ed" name="fullname_ed" value="<?= htmlspecialchars($user['fullname']); ?>" required>
                        </div>

                        <!-- Add this after the address field in the form -->
                        <div class="mb-3">
                            <label for="new_password" class="form-label">New Password</label>
                            <input type="password" class="form-control" id="new_password" name="new_password">
                        </div>
                        <div class="mb-5">
                            <label for="confirm_password" class="form-label">Confirm New Password</label>
                            <input type="password" class="form-control" id="confirm_password" name="confirm_password">

                            <!-- After the confirm password field -->
                            <div id="password-match-error" class="invalid-feedback d-none">Passwords do not match</div>
                            <div class="password-strength-meter mt-2">
                                <div class="progress">
                                    <div id="password-strength" class="progress-bar" role="progressbar" style="width: 0%"></div>
                                </div>
                                <small class="text-muted">Password strength: <span id="strength-text">Weak</span></small>
                            </div>
                        </div>

                    <?php else: ?>
                        <div class="mb-3">
                            <label for="username" class="form-label" >Employee ID</label>
                            <input type="text" style="text-transform: uppercase;" class="form-control" id="empid" name="empid" required>
                        </div>
                        <div class="mb-3">
                            <label for="fullanme" class="form-label">Full Name</label>
                            <input type="text" style="text-transform: uppercase;" class="form-control" id="fullname" name="fullname" required>
                        </div>
                        <div class="mb-3">
                            <label for="username" class="form-label">Username</label>
                            <input type="text" class="form-control" id="username" name="username" required>
                        </div>
                        <div class="mb-3">
                            <label for="password" class="form-label">Password</label>
                            <input type="password" class="form-control" id="password" name="password" required>
                        </div>
                        <div class="mb-5">
                            <label for="role" class="form-label">Role</label>
                            <select class="form-select" id="role" name="role">
                                <option value="user">User</option>
                                <option value="admin">Admin</option>
                            </select>
                        </div>
                    <?php endif; ?>  

                    <div class="d-grid gap-2">
                        <button type="submit" class="btn btn-primary">Save</button>
                        <a href="users.php" class="btn btn-secondary">Cancel</a>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../assets/js/script.js"></script>
</body>
</html>