<?php
require_once '../includes/config.php';
require_once '../includes/functions.php';

if (!isLoggedIn() || !isAdmin()) {
    redirect(BASE_URL);
}

$action = isset($_GET['action']) ? $_GET['action'] : '';
$userId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$type = isset($_GET['type']) ? $_GET['type'] : (isset($_POST['type']) ? $_POST['type'] : 'users');

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $userId = (int)$_POST['user_id'];
    $type = $_POST['type'];
    
    try {
        $pdo->beginTransaction();
        
        if ($type === 'users') {
            // Handle SLT users form
            $nickname_ed = strtoupper(sanitizeInput($_POST['nickname_ed']));
            $fullname_ed = strtoupper(sanitizeInput($_POST['fullname_ed']));
            $slt_email_ed = sanitizeInput($_POST['slt_email_ed']);

            $fullname = strtoupper(sanitizeInput($_POST['fullname']));
            $nickname = strtoupper(sanitizeInput($_POST['nickname']));
            $email = sanitizeInput($_POST['email']);
            $username = sanitizeInput($_POST['username']);
            $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
            $role = sanitizeInput($_POST['role']);

            // Check if user exists
            $stmt = $pdo->prepare("SELECT id FROM users WHERE id = ?");
            $stmt->execute([$userId]);
            $is_active = isset($_POST['is_active']) ? 1 : 0;

            if ($stmt->rowCount() > 0) {
                // Update existing profile
                $stmt = $pdo->prepare("UPDATE users SET fullname = ?, slt_email = ?, sub_name = ?, is_active = ? WHERE id = ?");
                $stmt->execute([$fullname_ed, $slt_email_ed, $nickname_ed, $is_active, $userId]);
            } else {
                // Insert new profile
                $stmt = $pdo->prepare("INSERT INTO users (fullname, sub_name, slt_email, username, password, role, is_active) VALUES (?, ?, ?, ?, ?, ?, ?)");
                $stmt->execute([$fullname, $nickname, $email, $username, $password, $role, $is_active]);
            }

            // Handle password change if new password is provided
            if (!empty($_POST['new_password'])) {
                if ($_POST['new_password'] !== $_POST['confirm_password']) {
                    throw new Exception("New passwords do not match");
                }
                $new_hashed_password = password_hash($_POST['new_password'], PASSWORD_DEFAULT);
                $stmt = $pdo->prepare("UPDATE users SET password = ? WHERE id = ?");
                $stmt->execute([$new_hashed_password, $_POST['user_id']]);
            }
        } elseif ($type === 'management' || $type === 'operations') {
            // Handle Management/Operations form
            $cxi_id = sanitizeInput($_POST['cxi_id']);
            $fullname = strtoupper(sanitizeInput($_POST['fullname']));
            $department = sanitizeInput($_POST['department']);
            $email = sanitizeInput($_POST['email']);

            $table = ($type === 'management') ? 'management' : 'operations_managers';

            // Check if record exists
            $stmt = $pdo->prepare("SELECT id FROM $table WHERE id = ?");
            $stmt->execute([$userId]);
            $is_active = isset($_POST['is_active']) ? 1 : 0;


            if ($stmt->rowCount() > 0) {
                // Update existing record
                $stmt = $pdo->prepare("UPDATE $table SET cxi_id = ?, fullname = ?, department = ?, email = ?, is_active = ? WHERE id = ?");
                $stmt->execute([$cxi_id, $fullname, $department, $email, $is_active, $userId]);
            } else {
                // Insert new record
                $stmt = $pdo->prepare("INSERT INTO $table (cxi_id, fullname, department, email, is_active) VALUES (?, ?, ?, ?, ?)");
                $stmt->execute([$cxi_id, $fullname, $department, $email, $is_active]);
            }
        }
        
        $pdo->commit();
        $_SESSION['success'] = "Record saved successfully!";
        redirect('users.php?tab=' . $type);
        
    } catch (Exception $e) {
        $pdo->rollBack();
        $_SESSION['error'] = "Error: " . $e->getMessage();
        redirect('profile.php?id=' . $_POST['user_id'] . '&type=' . $type);
    }
}

// Get user data
$record = null;
if ($userId > 0) {
    try {
        $table = ($type === 'management') ? 'management' : (($type === 'operations') ? 'operations_managers' : 'users');
        $stmt = $pdo->prepare("SELECT * FROM $table WHERE id = ?");
        $stmt->execute([$userId]);
        $record = $stmt->fetch();
        
        if (!$record) {
            $_SESSION['error'] = "Record not found";
            redirect('users.php?tab=' . $type);
        }
    } catch (PDOException $e) {
        $_SESSION['error'] = "Error fetching data: " . $e->getMessage();
        redirect('users.php?tab=' . $type);
    }
} elseif ($action !== 'create') {
    redirect('users.php?tab=' . $type);
}

require_once '../components/layout.php';
renderHead($userId ? 'Edit Record' : 'Add Record');
renderNavbar();
renderSidebar('users');
?>

<div class="md:ml-64 pt-2 min-h-screen">
    <main class="p-6">
        <div class="flex justify-between items-center mb-6">
            <h1 class="text-2xl font-bold"><?= $userId ? 'Edit Record' : 'Add New Record' ?></h1>
            <a href="users.php?tab=<?= $type ?>" class="text-gray-400 hover:text-white">
                <i class="fas fa-times fa-lg"></i>
            </a>
        </div>

        <?php renderAlert(); ?>

        <div class="bg-gray-800 rounded-xl border border-gray-700 p-6 shadow">
            <form method="POST">
                <input type="hidden" name="user_id" value="<?= $userId ?>">
                <input type="hidden" name="type" value="<?= $type ?>">
                
                <div class="space-y-6">
                    <?php if ($type === 'users'): ?>
                        <?php if ($userId): ?>
                            <div>
                                <label class="block text-sm font-medium text-gray-300 mb-2">Username</label>
                                <input type="text" class="w-full px-4 py-3 bg-gray-700/50 border border-gray-600/50 rounded-lg text-gray-200" value="<?= htmlspecialchars($record['username']) ?>" readonly>
                            </div>

                            <div>
                                <label for="nickname_ed" class="block text-sm font-medium text-gray-300 mb-2">Nickname</label>
                                <input type="text" style="text-transform: uppercase;" class="w-full px-4 py-3 bg-gray-700/50 border border-gray-600/50 rounded-lg text-gray-200" id="nickname_ed" name="nickname_ed" value="<?= htmlspecialchars($record['sub_name']) ?>" required>
                            </div>
                            
                            <div>
                                <label for="fullname_ed" class="block text-sm font-medium text-gray-300 mb-2">Full Name</label>
                                <input type="text" style="text-transform: uppercase;" class="w-full px-4 py-3 bg-gray-700/50 border border-gray-600/50 rounded-lg text-gray-200" id="fullname_ed" name="fullname_ed" value="<?= htmlspecialchars($record['fullname']) ?>" required>
                            </div>

                            <div>
                                <label for="slt_email_ed" class="block text-sm font-medium text-gray-300 mb-2">Email</label>
                                <input type="email" class="w-full px-4 py-3 bg-gray-700/50 border border-gray-600/50 rounded-lg text-gray-200" id="slt_email_ed" name="slt_email_ed" value="<?= htmlspecialchars($record['slt_email']) ?>" required>
                            </div>

                            <div class="space-y-4">
                                <div>
                                    <label for="new_password" class="block text-sm font-medium text-gray-300 mb-2">New Password</label>
                                    <input type="password" class="w-full px-4 py-3 bg-gray-700/50 border border-gray-600/50 rounded-lg text-gray-200" id="new_password" name="new_password">
                                </div>
                                <div>
                                    <label for="confirm_password" class="block text-sm font-medium text-gray-300 mb-2">Confirm New Password</label>
                                    <input type="password" class="w-full px-4 py-3 bg-gray-700/50 border border-gray-600/50 rounded-lg text-gray-200" id="confirm_password" name="confirm_password">
                                </div>
                                <div id="password-match-error" class="hidden text-sm text-red-500">Passwords do not match</div>
                                <div class="space-y-2">
                                    <div class="h-1.5 w-full bg-gray-700 rounded-full overflow-hidden">
                                        <div id="password-strength" class="h-full bg-red-500" style="width: 0%"></div>
                                    </div>
                                    <p class="text-xs text-gray-400">Password strength: <span id="strength-text">Weak</span></p>
                                </div>
                            </div>
                            <div class="flex items-center">
                                <input type="checkbox" id="is_active" name="is_active" 
                                    class="w-4 h-4 text-primary-600 bg-gray-700 border-gray-600 rounded focus:ring-primary-500 focus:ring-2"
                                    <?= $userId && $record['is_active'] ? 'checked' : '' ?>>
                                <label for="is_active" class="ml-2 text-sm font-medium text-gray-300">Active</label>
                            </div>
                        <?php else: ?>
                            <div>
                                <label for="fullname" class="block text-sm font-medium text-gray-300 mb-2">Full Name</label>
                                <input type="text" style="text-transform: uppercase;" class="w-full px-4 py-3 bg-gray-700/50 border border-gray-600/50 rounded-lg text-gray-200" id="fullname" name="fullname" required>
                            </div>
                            
                            <div>
                                <label for="nickname" class="block text-sm font-medium text-gray-300 mb-2">Nickname</label>
                                <input type="text" style="text-transform: uppercase;" class="w-full px-4 py-3 bg-gray-700/50 border border-gray-600/50 rounded-lg text-gray-200" id="nickname" name="nickname" required>
                            </div>

                            <div>
                                <label for="email" class="block text-sm font-medium text-gray-300 mb-2">Email</label>
                                <input type="email" class="w-full px-4 py-3 bg-gray-700/50 border border-gray-600/50 rounded-lg text-gray-200" id="email" name="email" required>
                            </div>
                            
                            <div>
                                <label for="username" class="block text-sm font-medium text-gray-300 mb-2">Username</label>
                                <input type="text" class="w-full px-4 py-3 bg-gray-700/50 border border-gray-600/50 rounded-lg text-gray-200" id="username" name="username" required>
                            </div>
                            
                            <div>
                                <label for="password" class="block text-sm font-medium text-gray-300 mb-2">Password</label>
                                <input type="password" class="w-full px-4 py-3 bg-gray-700/50 border border-gray-600/50 rounded-lg text-gray-200" id="password" name="password" required>
                            </div>
                            <div class="flex items-center">
                                <input type="checkbox" id="is_active" name="is_active" 
                                    class="w-4 h-4 text-primary-600 bg-gray-700 border-gray-600 rounded focus:ring-primary-500 focus:ring-2"
                                    <?= $userId && $record['is_active'] ? 'checked' : '' ?> checked>
                                <label for="is_active" class="ml-2 text-sm font-medium text-gray-300">Active</label>
                            </div>
                                                        
                            <div class="hidden">
                                <label for="role" class="block text-sm font-medium text-gray-300 mb-2">Role</label>
                                <select class="w-full px-4 py-3 bg-gray-700/50 border border-gray-600/50 rounded-lg text-gray-200" id="role" name="role">
                                    <option value="admin">Admin</option>
                                    <option value="user">User</option>
                                </select>
                            </div>
                        <?php endif; ?>
                    <?php else: ?>
                        <div>
                            <label for="cxi_id" class="block text-sm font-medium text-gray-300 mb-2">CXI ID</label>
                            <input type="text" class="w-full px-4 py-3 bg-gray-700/50 border border-gray-600/50 rounded-lg text-gray-200" id="cxi_id" name="cxi_id" value="<?= $record ? htmlspecialchars($record['cxi_id']) : '' ?>" required>
                        </div>
                        
                        <div>
                            <label for="fullname" class="block text-sm font-medium text-gray-300 mb-2">Full Name</label>
                            <input type="text" style="text-transform: uppercase;" class="w-full px-4 py-3 bg-gray-700/50 border border-gray-600/50 rounded-lg text-gray-200" id="fullname" name="fullname" value="<?= $record ? htmlspecialchars($record['fullname']) : '' ?>" required>
                        </div>
                        
                        <div>
                            <label for="department" class="block text-sm font-medium text-gray-300 mb-2">Department</label>
                            <input type="text" style="text-transform: uppercase;" class="w-full px-4 py-3 bg-gray-700/50 border border-gray-600/50 rounded-lg text-gray-200" id="department" name="department" value="<?= $record ? htmlspecialchars($record['department']) : '' ?>" required>
                        </div>
                        
                        <div>
                            <label for="email" class="block text-sm font-medium text-gray-300 mb-2">Email</label>
                            <input type="email" class="w-full px-4 py-3 bg-gray-700/50 border border-gray-600/50 rounded-lg text-gray-200" id="email" name="email" value="<?= $record ? htmlspecialchars($record['email']) : '' ?>" required>
                        </div>

                        <div class="flex items-center">
                            <input type="checkbox" id="is_active" name="is_active" 
                                class="w-4 h-4 text-primary-600 bg-gray-700 border-gray-600 rounded focus:ring-primary-500 focus:ring-2"
                                <?= $userId && $record['is_active'] ? 'checked' : '' ?>>
                            <label for="is_active" class="ml-2 text-sm font-medium text-gray-300">Active</label>
                        </div>
                    <?php endif; ?>
                    
                    <div class="flex space-x-3 pt-4">
                        <button type="submit" class="bg-primary-600 hover:bg-primary-700 text-white px-6 py-2 rounded-lg flex items-center">
                            <i class="fas fa-save mr-2"></i> Save
                        </button>
                        <a href="users.php?tab=<?= $type ?>" class="bg-gray-600 hover:bg-gray-700 text-white px-6 py-2 rounded-lg flex items-center">
                            Cancel
                        </a>
                    </div>
                </div>
            </form>
        </div>
    </main>
</div>

<script>
// Password strength and match checking
document.addEventListener('DOMContentLoaded', function() {
    const newPassword = document.getElementById('new_password');
    const confirmPassword = document.getElementById('confirm_password');
    const matchError = document.getElementById('password-match-error');
    const strengthBar = document.getElementById('password-strength');
    const strengthText = document.getElementById('strength-text');

    function checkPasswordMatch() {
        if (newPassword && confirmPassword && newPassword.value && confirmPassword.value && newPassword.value !== confirmPassword.value) {
            matchError.classList.remove('hidden');
            return false;
        } else {
            if (matchError) matchError.classList.add('hidden');
            return true;
        }
    }

    function checkPasswordStrength(password) {
        let strength = 0;
        
        if (password.length >= 8) strength += 1;
        if (password.match(/[a-z]/)) strength += 1;
        if (password.match(/[A-Z]/)) strength += 1;
        if (password.match(/[0-9]/)) strength += 1;
        if (password.match(/[^a-zA-Z0-9]/)) strength += 1;
        
        return strength;
    }

    function updatePasswordStrength() {
        const password = newPassword.value;
        const strength = checkPasswordStrength(password);
        
        let width = 0;
        let color = 'bg-red-500';
        let text = 'Weak';
        
        if (strength >= 4) {
            width = 100;
            color = 'bg-green-500';
            text = 'Strong';
        } else if (strength >= 2) {
            width = 66;
            color = 'bg-yellow-500';
            text = 'Medium';
        } else if (password.length > 0) {
            width = 33;
        }
        
        if (strengthBar) strengthBar.style.width = width + '%';
        if (strengthBar) strengthBar.className = `h-full ${color}`;
        if (strengthText) strengthText.textContent = text;
    }

    if (newPassword && confirmPassword) {
        newPassword.addEventListener('input', function() {
            checkPasswordMatch();
            updatePasswordStrength();
        });
        
        confirmPassword.addEventListener('input', checkPasswordMatch);
    }
});
</script>

<?php renderFooter(); ?>