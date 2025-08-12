<?php
require_once '../includes/config.php';
require_once '../includes/functions.php';

if (!isLoggedIn() || !isAdmin()) {
    redirect(BASE_URL);
}

updateLastActivity();

$action = isset($_GET['action']) ? $_GET['action'] : '';
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = (int)$_POST['id'];
    
    try {
        $pdo->beginTransaction();
        
        // Get current user's sub_name
        $userStmt = $pdo->prepare("SELECT sub_name FROM users WHERE id = ?");
        $userStmt->execute([$_SESSION['user_id']]);
        $user = $userStmt->fetch();
        $sub_name = $user['sub_name'];
        
        // Get employee details
        $employeeId = sanitizeInput($_POST['employee_id']);
        $employeeStmt = $pdo->prepare("SELECT * FROM employees WHERE employee_id = ?");
        $employeeStmt->execute([$employeeId]);
        $employee = $employeeStmt->fetch();
        
        if (!$employee) {
            throw new Exception("Employee not found");
        }
        
        $currentMonth = date('M Y');
        $currentTime = date('g:i A');
        
        $data = [
            'month' => $currentMonth,
            'employee_id' => $employeeId,
            'full_name' => strtoupper($employee['full_name']),
            'department' => strtoupper($employee['department']),
            'supervisor' => strtoupper($employee['supervisor']),
            'operation_manager' => strtoupper($employee['operation_manager']),
            'shift' => strtoupper(sanitizeInput($_POST['shift'])),
            'coverage' => strtoupper(sanitizeInput($_POST['coverage'])),
            'coverage_type' => strtoupper(sanitizeInput($_POST['coverage_type'])),
            'time_in' => sanitizeInput($_POST['time_in']),
            'time_out' => sanitizeInput($_POST['time_out']),
            'mins_of_work' => (int)$_POST['mins_of_work'],
            'vto_mins' => (int)$_POST['vto_mins'],
            'vto_type' => strtoupper(sanitizeInput($_POST['vto_type'])),
            'timestamp' => $currentTime,
            'sub_name' => $sub_name
        ];
        
        if ($id > 0) {
            // Update existing record
            $stmt = $pdo->prepare("UPDATE vto_tracker SET 
                month = :month,
                employee_id = :employee_id,
                full_name = :full_name,
                department = :department,
                supervisor = :supervisor,
                operation_manager = :operation_manager,
                shift = :shift,
                coverage = :coverage,
                coverage_type = :coverage_type,
                time_in = :time_in,
                time_out = :time_out,
                mins_of_work = :mins_of_work,
                vto_mins = :vto_mins,
                vto_type = :vto_type,
                timestamp = :timestamp,
                sub_name = :sub_name
                WHERE id = :id");
            
            $data['id'] = $id;
            $stmt->execute($data);
            
            $_SESSION['success'] = "VTO record updated successfully!";
            logActivity("Updated VTO record for {$employee['full_name']}", $id, 'vto');
        } else {
            // Insert new record
            $stmt = $pdo->prepare("INSERT INTO vto_tracker 
                (month, employee_id, full_name, department, supervisor, operation_manager, 
                shift, coverage, coverage_type, time_in, time_out, mins_of_work, vto_mins, 
                vto_type, timestamp, sub_name)
                VALUES 
                (:month, :employee_id, :full_name, :department, :supervisor, :operation_manager,
                :shift, :coverage, :coverage_type, :time_in, :time_out, :mins_of_work, :vto_mins,
                :vto_type, :timestamp, :sub_name)");
            
            $stmt->execute($data);
            $recordId = $pdo->lastInsertId();
            
            $_SESSION['success'] = "VTO record added successfully!";
            logActivity("Created VTO record for {$data['full_name']}", $recordId, 'vto');
        }
        
        $pdo->commit();
        redirect('attendance.php?tab=vto');
    } catch (Exception $e) {
        $pdo->rollBack();
        $_SESSION['error'] = "Error: " . $e->getMessage();
        redirect($id ? 'vto_form.php?id=' . $id : 'vto_form.php?action=create');
    }
}

// Get record data
$record = null;
if ($id > 0) {
    try {
        $stmt = $pdo->prepare("SELECT * FROM vto_tracker WHERE id = ?");
        $stmt->execute([$id]);
        $record = $stmt->fetch();
        
        if (!$record) {
            $_SESSION['error'] = "Record not found";
            redirect('attendance.php?tab=vto');
        }
    } catch (PDOException $e) {
        $_SESSION['error'] = "Error fetching record: " . $e->getMessage();
        redirect('attendance.php?tab=vto');
    }
} elseif ($action !== 'create') {
    redirect('attendance.php?tab=vto');
}

require_once '../components/layout.php';
renderHead($id ? 'Edit VTO Record' : 'Add VTO Record');
renderNavbar();
renderSidebar('attendance');
?>

<div class="pt-2 min-h-screen">
    <main class="p-6">
        <div class="flex justify-between items-center mb-6">
            <h1 class="text-2xl font-bold"><?= $id ? 'Edit VTO Record' : 'Add VTO Record' ?></h1>
            <a href="attendance.php?tab=vto" class="text-gray-400 hover:text-white">
                <i class="fas fa-times fa-lg"></i>
            </a>
        </div>

        <?php renderAlert(); ?>

        <div class="bg-gray-800 rounded-xl border border-gray-700 p-6 shadow">
            <form method="POST">
                <input type="hidden" name="id" value="<?= $id ?>">
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label for="employee_id" class="block text-sm font-medium text-gray-300 mb-2">Employee ID</label>
                        <input type="text" id="employee_id" name="employee_id" style="text-transform: uppercase;"
                            class="w-full px-4 py-3 bg-gray-700/50 border border-gray-600/50 rounded-lg text-gray-200" 
                            value="<?= $record ? htmlspecialchars($record['employee_id']) : '' ?>" required
                            onchange="fetchEmployeeDetails(this.value)"
                            autocomplete="off">
                        <div id="employeeSearchResults" class="hidden absolute z-10 mt-1 w-full max-w-md bg-gray-800 border border-gray-700 rounded-lg shadow-lg"></div>
                    </div>
                    
                    <div>
                        <label for="full_name" class="block text-sm font-medium text-gray-300 mb-2">Full Name</label>
                        <input type="text" id="full_name" name="full_name" style="text-transform: uppercase;" readonly
                               class="w-full px-4 py-3 bg-gray-700/50 border border-gray-600/50 rounded-lg text-gray-400" 
                               value="<?= $record ? htmlspecialchars($record['full_name']) : '' ?>">
                    </div>
                    
                    <div>
                        <label for="department" class="block text-sm font-medium text-gray-300 mb-2">Department</label>
                        <input type="text" id="department" name="department" style="text-transform: uppercase;" readonly
                               class="w-full px-4 py-3 bg-gray-700/50 border border-gray-600/50 rounded-lg text-gray-400" 
                               value="<?= $record ? htmlspecialchars($record['department']) : '' ?>">
                    </div>
                    
                    <div>
                        <label for="supervisor" class="block text-sm font-medium text-gray-300 mb-2">Supervisor</label>
                        <input type="text" id="supervisor" name="supervisor" style="text-transform: uppercase;" readonly
                               class="w-full px-4 py-3 bg-gray-700/50 border border-gray-600/50 rounded-lg text-gray-400" 
                               value="<?= $record ? htmlspecialchars($record['supervisor']) : '' ?>">
                    </div>
                    
                    <div>
                        <label for="operation_manager" class="block text-sm font-medium text-gray-300 mb-2">Operations Manager</label>
                        <input type="text" id="operation_manager" name="operation_manager" style="text-transform: uppercase;" readonly
                               class="w-full px-4 py-3 bg-gray-700/50 border border-gray-600/50 rounded-lg text-gray-400" 
                               value="<?= $record ? htmlspecialchars($record['operation_manager']) : '' ?>">
                    </div>
                    
                    <div>
                        <label for="shift" class="block text-sm font-medium text-gray-300 mb-2">Shift</label>
                        <input type="text" id="shift" name="shift" style="text-transform: uppercase;"
                               class="w-full px-4 py-3 bg-gray-700/50 border border-gray-600/50 rounded-lg text-gray-200" 
                               value="<?= $record ? htmlspecialchars($record['shift']) : '' ?>" required>
                    </div>
                    
                    <div>
                        <label for="coverage" class="block text-sm font-medium text-gray-300 mb-2">Coverage</label>
                        <input type="text" id="coverage" name="coverage" style="text-transform: uppercase;"
                               class="w-full px-4 py-3 bg-gray-700/50 border border-gray-600/50 rounded-lg text-gray-200" 
                               value="<?= $record ? htmlspecialchars($record['coverage']) : '' ?>" required>
                    </div>
                    
                    <div>
                        <label for="coverage_type" class="block text-sm font-medium text-gray-300 mb-2">Coverage Type</label>
                        <select id="coverage_type" name="coverage_type"
                                class="w-full px-4 py-3 bg-gray-700/50 border border-gray-600/50 rounded-lg text-gray-200" required>
                            <option value="-" <?= $record && $record['coverage_type'] === '-' ? 'selected' : '' ?>>-</option>
                            <option value="NO NEED" <?= $record && $record['coverage_type'] === 'NO NEED' ? 'selected' : '' ?>>NO NEED</option>
                            <option value="TRAINEE" <?= $record && $record['coverage_type'] === 'TRAINEE' ? 'selected' : '' ?>>TRAINEE</option>
                            <option value="BACK UP" <?= $record && $record['coverage_type'] === 'BACK UP' ? 'selected' : '' ?>>BACK UP</option>
                            <option value="PENDING" <?= $record && $record['coverage_type'] === 'PENDING' ? 'selected' : '' ?>>PENDING</option>
                            <option value="DSOT" <?= $record && $record['coverage_type'] === 'DSOT' ? 'selected' : '' ?>>DSOT</option>
                            <option value="RDOT" <?= $record && $record['coverage_type'] === 'RDOT' ? 'selected' : '' ?>>RDOT</option>
                            <option value="AGENT MODE" <?= $record && $record['coverage_type'] === 'AGENT MODE' ? 'selected' : '' ?>>AGENT MODE</option>
                        </select>
                    </div>
                    
                    <div>
                        <label for="time_in" class="block text-sm font-medium text-gray-300 mb-2">Time In</label>
                        <input type="time" id="time_in" name="time_in"
                               class="w-full px-4 py-3 bg-gray-700/50 border border-gray-600/50 rounded-lg text-gray-200" 
                               value="<?= $record ? htmlspecialchars($record['time_in']) : '' ?>" required>
                    </div>
                    
                    <div>
                        <label for="time_out" class="block text-sm font-medium text-gray-300 mb-2">Time Out</label>
                        <input type="time" id="time_out" name="time_out"
                               class="w-full px-4 py-3 bg-gray-700/50 border border-gray-600/50 rounded-lg text-gray-200" 
                               value="<?= $record ? htmlspecialchars($record['time_out']) : '' ?>" required>
                    </div>
                    
                    <div>
                        <label for="mins_of_work" class="block text-sm font-medium text-gray-300 mb-2">Minutes Worked</label>
                        <input type="number" id="mins_of_work" name="mins_of_work"
                               class="w-full px-4 py-3 bg-gray-700/50 border border-gray-600/50 rounded-lg text-gray-200" 
                               value="<?= $record ? htmlspecialchars($record['mins_of_work']) : '' ?>" required>
                    </div>
                    
                    <div>
                        <label for="vto_mins" class="block text-sm font-medium text-gray-300 mb-2">VTO Minutes</label>
                        <input type="number" id="vto_mins" name="vto_mins"
                               class="w-full px-4 py-3 bg-gray-700/50 border border-gray-600/50 rounded-lg text-gray-200" 
                               value="<?= $record ? htmlspecialchars($record['vto_mins']) : '' ?>" required>
                    </div>
                    
                    <div>
                        <label for="vto_type" class="block text-sm font-medium text-gray-300 mb-2">VTO Type</label>
                        <select id="vto_type" name="vto_type"
                                class="w-full px-4 py-3 bg-gray-700/50 border border-gray-600/50 rounded-lg text-gray-200" required>
                            <option value="PRE APPROVED" <?= $record && $record['vto_type'] === 'PRE APPROVED' ? 'selected' : '' ?>>PRE APPROVED</option>
                            <option value="REALTIME" <?= $record && $record['vto_type'] === 'REALTIME' ? 'selected' : '' ?>>REALTIME</option>
                        </select>
                    </div>
                </div>
                
                <div class="flex space-x-3 pt-6">
                    <button type="submit" class="bg-primary-600 hover:bg-primary-700 text-white px-6 py-2 rounded-lg flex items-center">
                        <i class="fas fa-save mr-2"></i> Save
                    </button>
                    <a href="attendance.php?tab=vto" class="bg-gray-600 hover:bg-gray-700 text-white px-6 py-2 rounded-lg flex items-center">
                        Cancel
                    </a>
                </div>
            </form>
        </div>
    </main>
</div>

<script>
function searchEmployees(query) {
    if (query.length < 2) {
        document.getElementById('employeeSearchResults').classList.add('hidden');
        return;
    }

    fetch('../api/search_employees.php?query=' + encodeURIComponent(query))
        .then(response => response.json())
        .then(data => {
            const resultsContainer = document.getElementById('employeeSearchResults');
            resultsContainer.innerHTML = '';
            
            if (data.success && data.employees.length > 0) {
                data.employees.forEach(employee => {
                    const item = document.createElement('div');
                    item.className = 'px-4 py-2 hover:bg-gray-700 cursor-pointer border-b border-gray-700';
                    item.innerHTML = `
                        <div class="font-medium text-gray-200">${employee.employee_id}</div>
                        <div class="text-sm text-gray-400">${employee.full_name}</div>
                    `;
                    item.addEventListener('click', () => {
                        document.getElementById('employee_id').value = employee.employee_id;
                        fetchEmployeeDetails(employee.employee_id);
                        resultsContainer.classList.add('hidden');
                    });
                    resultsContainer.appendChild(item);
                });
                resultsContainer.classList.remove('hidden');
            } else {
                const item = document.createElement('div');
                item.className = 'px-4 py-2 text-gray-400';
                item.textContent = 'No employees found';
                resultsContainer.appendChild(item);
                resultsContainer.classList.remove('hidden');
            }
        })
        .catch(error => {
            console.error('Error:', error);
        });
}

// Add event listener for search
document.getElementById('employee_id').addEventListener('input', function() {
    searchEmployees(this.value);
});

// Hide results when clicking outside
document.addEventListener('click', function(e) {
    if (!document.getElementById('employee_id').contains(e.target) && 
        !document.getElementById('employeeSearchResults').contains(e.target)) {
        document.getElementById('employeeSearchResults').classList.add('hidden');
    }
});

// Auto-fill employee details if editing
document.addEventListener('DOMContentLoaded', function() {
    <?php if ($record): ?>
        fetchEmployeeDetails('<?= $record['employee_id'] ?>');
    <?php endif; ?>
});

function fetchEmployeeDetails(employeeId) {
    if (!employeeId) return;
    
    fetch('../api/get_employee.php?employee_id=' + encodeURIComponent(employeeId))
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                document.getElementById('full_name').value = data.employee.full_name;
                document.getElementById('department').value = data.employee.department;
                document.getElementById('supervisor').value = data.employee.supervisor;
                document.getElementById('operation_manager').value = data.employee.operation_manager;
            }
        })
        .catch(error => {
            console.error('Error:', error);
        });
}

// Calculate minutes worked when time in/out changes
document.getElementById('time_in').addEventListener('change', calculateWorkTime);
document.getElementById('time_out').addEventListener('change', calculateWorkTime);

function calculateWorkTime() {
    const timeIn = document.getElementById('time_in').value;
    const timeOut = document.getElementById('time_out').value;
    
    if (timeIn && timeOut) {
        const inDate = new Date(`2000-01-01T${timeIn}`);
        const outDate = new Date(`2000-01-01T${timeOut}`);
        
        if (outDate > inDate) {
            const diffMs = outDate - inDate;
            const diffMins = Math.round(diffMs / 60000);
            document.getElementById('mins_of_work').value = diffMins;
        } else {
            document.getElementById('mins_of_work').value = '';
        }
    }
}
</script>

<?php renderFooter(); ?>