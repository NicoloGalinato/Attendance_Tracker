<?php
require_once '../includes/config.php';
require_once '../includes/functions.php';

if (!isLoggedIn() || !isAdmin()) {
    redirect(BASE_URL);
}

updateLastActivity();

$action = isset($_GET['action']) ? $_GET['action'] : '';
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$type = isset($_GET['type']) ? $_GET['type'] : (isset($_POST['type']) ? $_POST['type'] : 'absenteeism');

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = (int)$_POST['id'];
    $type = $_POST['type'];
    
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
        $currentDate = date('Y-m-d');
        $currentTime = date('g:i A');
        
        // Check for duplicate entry
        if ($type === 'absenteeism') {
            $dateField = strtoupper(sanitizeInput($_POST['date_of_absent']));
            
            // Check if a record already exists for this employee on this date (excluding current record if editing)
             $duplicateCheck = $pdo->prepare("SELECT id FROM absenteeism WHERE employee_id = ? AND date_of_absent = ? AND id != ?");
            $duplicateCheck->execute([$employeeId, $dateField, $id]);
            $existingRecord = $duplicateCheck->fetch();
            
            if ($existingRecord) {
                throw new Exception("An absenteeism record already exists for this ". $employee['full_name'] ." on the selected date " . date('F j, Y', strtotime($dateField)) .".");
            }
            
            $data = [
                'month' => $currentMonth,
                'employee_id' => $employeeId,
                'full_name' => strtoupper($employee['full_name']),
                'department' => strtoupper($employee['department']),
                'supervisor' => strtoupper($employee['supervisor']),
                'operation_manager' => strtoupper($employee['operation_manager']),
                'email' => $employee['email'],
                'date_of_absent' => $dateField,
                'follow_call_in_procedure' => strtoupper(sanitizeInput($_POST['follow_call_in_procedure'])),
                'sanction' => strtoupper(sanitizeInput($_POST['sanction'])),
                'reason' => strtoupper($_POST['reason']),
                'coverage' => strtoupper($_POST['coverage']),
                'coverage_type' => strtoupper(sanitizeInput($_POST['coverage_type'])),
                'shift' => strtoupper(sanitizeInput($_POST['shift'])),
                'ir_form' => strtoupper(sanitizeInput($_POST['ir_form'])),
                'timestamp' => $currentTime,
                'sub_name' => $sub_name
            ];
            
            if ($id > 0) {
                // Update existing record
                $stmt = $pdo->prepare("UPDATE absenteeism SET 
                    month = :month,
                    employee_id = :employee_id,
                    full_name = :full_name,
                    department = :department,
                    supervisor = :supervisor,
                    operation_manager = :operation_manager,
                    email = :email,
                    date_of_absent = :date_of_absent,
                    follow_call_in_procedure = :follow_call_in_procedure,
                    sanction = :sanction,
                    reason = :reason,
                    coverage = :coverage,
                    coverage_type = :coverage_type,
                    shift = :shift,
                    ir_form = :ir_form,
                    timestamp = :timestamp,
                    sub_name = :sub_name
                    WHERE id = :id");
                
                $data['id'] = $id;
                $stmt->execute($data);
                
                $_SESSION['success'] = "Absenteeism record updated successfully!";
                logActivity("Updated absenteeism of {$employee['full_name']}", $id, 'absenteeism');
            } else {
                // Insert new record
                $stmt = $pdo->prepare("INSERT INTO absenteeism 
                    (month, employee_id, full_name, department, supervisor, operation_manager, email, 
                    date_of_absent, follow_call_in_procedure, sanction, reason, coverage, coverage_type, 
                    shift, ir_form, timestamp, sub_name)
                    VALUES 
                    (:month, :employee_id, :full_name, :department, :supervisor, :operation_manager, :email, 
                    :date_of_absent, :follow_call_in_procedure, :sanction, :reason, :coverage, :coverage_type,
                    :shift, :ir_form, :timestamp, :sub_name)");
                
                $stmt->execute($data);
                $recordId = $pdo->lastInsertId();
                
                $_SESSION['success'] = "Absenteeism record added successfully!";
                logActivity("Created absenteeism record for {$data['full_name']}", $recordId, 'absenteeism');

                // After successful insert/update
                $action = $id > 0 ? 'updated' : 'created';
                $recordId = $id > 0 ? $id : $pdo->lastInsertId();
                logActivity("$action absenteeism record for {$data['full_name']}", $recordId, 'absenteeism');
                
                // In the POST handling section, after successful insert/update
                if ($stmt->rowCount() > 0) {
                    $_SESSION['success'] = "Record " . ($id > 0 ? 'updated' : 'added') . " successfully!";
                    
                    // Check if this employee has any pending IR forms (regardless of current record's status)
                    $stmt = $pdo->prepare("SELECT COUNT(*) FROM absenteeism 
                                        WHERE employee_id = ? AND ir_form LIKE 'PENDING%'");
                    $stmt->execute([$employeeId]);
                    $pendingCount = $stmt->fetchColumn();
                    
                    if ($pendingCount > 0) {
                        $_SESSION['check_pending_ir'] = $employeeId;
                    }
                    
                    logActivity(($id > 0 ? "Updated" : "Created") . " {$type} record for {$data['full_name']}", 
                            $id > 0 ? $id : $pdo->lastInsertId(), $type);
                }
            }
        } else {
            // Tardiness form
            $dateField = strtoupper(sanitizeInput($_POST['date_of_incident']));
            $types = isset($_POST['types']) ? strtoupper(sanitizeInput($_POST['types'])) : 'LATE';
            
            // Check if a record already exists for this employee on this date (excluding current record if editing)
            $duplicateCheck = $pdo->prepare("SELECT id FROM tardiness WHERE employee_id = ? AND date_of_incident = ? AND types = ? AND id != ?");
            $duplicateCheck->execute([$employeeId, $dateField, $types, $id]);
            $existingRecord = $duplicateCheck->fetch();
            
            if ($existingRecord) {
                throw new Exception("A tardiness record already exists for this ". $employee['full_name'] ." on the selected date " . date('F j, Y', strtotime($dateField)) .".");
            }
            
            $data = [
                'month' => $currentMonth,
                'employee_id' => $employeeId,
                'full_name' => strtoupper($employee['full_name']),
                'department' => strtoupper($employee['department']),
                'supervisor' => strtoupper($employee['supervisor']),
                'operation_manager' => strtoupper($employee['operation_manager']),
                'email' => $employee['email'],
                'date_of_incident' => $dateField,
                'types' => $types,
                'minutes_late' => (int)$_POST['minutes_late'],
                'shift' => strtoupper(sanitizeInput($_POST['shift'])),
                'time_in' => strtoupper(sanitizeInput($_POST['time_in'])),
                'ir_form' => strtoupper(sanitizeInput($_POST['ir_form'])),
                'timestamp' => $currentTime,
                'sub_name' => $sub_name
            ];
            
            if ($id > 0) {
                // Update existing record
                $stmt = $pdo->prepare("UPDATE tardiness SET 
                    month = :month,
                    employee_id = :employee_id,
                    full_name = :full_name,
                    department = :department,
                    supervisor = :supervisor,
                    operation_manager = :operation_manager,
                    email = :email,
                    date_of_incident = :date_of_incident,
                    types = :types,
                    minutes_late = :minutes_late,
                    shift = :shift,
                    time_in = :time_in,
                    ir_form = :ir_form,
                    timestamp = :timestamp,
                    sub_name = :sub_name
                    WHERE id = :id");
                
                $data['id'] = $id;
                $stmt->execute($data);
                
                $_SESSION['success'] = "Tardiness record updated successfully!";
                logActivity("Updated tardiness of {$employee['full_name']}", $id, 'tardiness');
            } else {
                // Insert new record
                $stmt = $pdo->prepare("INSERT INTO tardiness 
                    (month, employee_id, full_name, department, supervisor, operation_manager, email, 
                    date_of_incident, types, minutes_late, shift, time_in, ir_form, timestamp, sub_name)
                    VALUES 
                    (:month, :employee_id, :full_name, :department, :supervisor, :operation_manager, :email, 
                    :date_of_incident, :types, :minutes_late, :shift, :time_in, :ir_form, :timestamp, :sub_name)");
                
                $stmt->execute($data);
                $recordId = $pdo->lastInsertId();
                
                $_SESSION['success'] = "Tardiness record added successfully!";
                logActivity("Created tardiness record for {$data['full_name']}", $recordId, 'tardiness');

                // After successful insert/update
                $action = $id > 0 ? 'updated' : 'created';
                $recordId = $id > 0 ? $id : $pdo->lastInsertId();
                logActivity("$action tardiness record for {$data['full_name']}", $recordId, 'tardiness');
            }
        }
        
        $pdo->commit();
        redirect('attendance.php?tab=' . $type);
    } catch (Exception $e) {
        $pdo->rollBack();
        $_SESSION['error'] = "Error: " . $e->getMessage();
        redirect($id ? 'attendance_form.php?id=' . $id . '&type=' . $type : 'attendance_form.php?action=create&type=' . $type);
    }
}

// Get record data
$record = null;
if ($id > 0) {
    try {
        $table = ($type === 'tardiness') ? 'tardiness' : 'absenteeism';
        $stmt = $pdo->prepare("SELECT * FROM $table WHERE id = ?");
        $stmt->execute([$id]);
        $record = $stmt->fetch();
        
        if (!$record) {
            $_SESSION['error'] = "Record not found";
            redirect('attendance.php?tab=' . $type);
        }
    } catch (PDOException $e) {
        $_SESSION['error'] = "Error fetching record: " . $e->getMessage();
        redirect('attendance.php?tab=' . $type);
    }
} elseif ($action !== 'create') {
    redirect('attendance.php?tab=' . $type);
}

require_once '../components/layout.php';
renderHead($id ? 'Edit Record' : 'Add Record');
renderNavbar();
renderSidebar('attendance');
?>

<div class="pt-2 min-h-screen">
    <main class="p-6">
        <div class="flex justify-between items-center mb-6">
            <h1 class="text-2xl font-bold"><?= $id ? 'Edit Record' : 'Add New Record' ?></h1>
            <a href="attendance.php?tab=<?= $type ?>" class="text-gray-400 hover:text-white">
                <i class="fas fa-times fa-lg"></i>
            </a>
        </div>

        <?php renderAlert(); ?>

        <div class="bg-gray-800 rounded-xl border border-gray-700 p-6 shadow">
            <form method="POST">
                <input type="hidden" name="id" value="<?= $id ?>">
                <input type="hidden" name="type" value="<?= $type ?>">
                
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
                        <label for="email" class="block text-sm font-medium text-gray-300 mb-2">Email</label>
                        <input type="text" id="email" name="email"
                               class="w-full px-4 py-3 bg-gray-700/50 border border-gray-600/50 rounded-lg text-gray-400" 
                               value="<?= $record ? htmlspecialchars($record['email']) : '' ?>">
                    </div>
                    
                    <?php if ($type === 'absenteeism'): ?>
                        <div>
                            <label for="date_of_absent" class="block text-sm font-medium text-gray-300 mb-2">Date of Absence</label>
                            <input type="date" id="date_of_absent" name="date_of_absent" style="text-transform: uppercase;"
                                   class="w-full px-4 py-3 bg-gray-700/50 border border-gray-600/50 rounded-lg text-gray-200" 
                                   value="<?= $record ? htmlspecialchars($record['date_of_absent']) : '' ?>" required>
                        </div>
                        
                        <div>
                            <label for="follow_call_in_procedure" class="block text-sm font-medium text-gray-300 mb-2">Received advise in SLT number:</label>
                            <input type="text" id="follow_call_in_procedure" name="follow_call_in_procedure" style="text-transform: uppercase;"
                                   class="w-full px-4 py-3 bg-gray-700/50 border border-gray-600/50 rounded-lg text-gray-200" 
                                   value="<?= $record ? htmlspecialchars($record['follow_call_in_procedure']) : '' ?>">
                        </div>
                        
                        <div>
                            <label for="sanction" class="block text-sm font-medium text-gray-300 mb-2">Sanction</label>
                            <select id="sanction" name="sanction" style="text-transform: uppercase;"
                                    class="w-full px-4 py-3 bg-gray-700/50 border border-gray-600/50 rounded-lg text-gray-200" required>
                                <option value="ABSENCE" <?= $record && $record['sanction'] === 'ABSENCE' ? 'selected' : '' ?>>ABSENCE</option>
                                <option value="ABSENCE / CWD" <?= $record && $record['sanction'] === 'ABSENCE / CWD' ? 'selected' : '' ?>>ABSENCE / CWD</option>
                                <option value="ABSENCE / NCNS" <?= $record && $record['sanction'] === 'ABSENCE / NCNS' ? 'selected' : '' ?>>ABSENCE / NCNS</option>
                                <option value="ABSENCE / NCNS (LATE ADVISE)" <?= $record && $record['sanction'] === 'ABSENCE / NCNS (LATE ADVISE)' ? 'selected' : '' ?>>ABSENCE / NCNS (LATE ADVISE)</option>
                                <option value="ABSENCE / NCNS / CWD" <?= $record && $record['sanction'] === 'ABSENCE / NCNS / CWD' ? 'selected' : '' ?>>ABSENCE / NCNS / CWD</option>
                                <option value="ABSENCE / NCNS / CWD (LATE ADVISE)" <?= $record && $record['sanction'] === 'ABSENCE / NCNS / CWD (LATE ADVISE)' ? 'selected' : '' ?>>ABSENCE / NCNS / CWD (LATE ADVISE)</option>
                                <option value="PVL" <?= $record && $record['sanction'] === 'PVL' ? 'selected' : '' ?>>PVL</option>
                            </select>
                        </div>
                        
                        <div>
                            <label for="reason" class="block text-sm font-medium text-gray-300 mb-2">Reason</label>
                            <textarea id="reason" name="reason" style="text-transform: uppercase;"
                                      class="w-full px-4 py-3 bg-gray-700/50 border border-gray-600/50 rounded-lg text-gray-200" 
                                      required><?= $record ? $record['reason'] : '' ?></textarea>
                        </div>
                        <div>
                            <label for="coverage" class="block text-sm font-medium text-gray-300 mb-2">Coverage</label>
                            <textarea id="coverage" name="coverage" style="text-transform: uppercase;"
                                      class="w-full px-4 py-3 bg-gray-700/50 border border-gray-600/50 rounded-lg text-gray-200" 
                                      required><?= $record ? $record['coverage'] : '' ?></textarea>
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
                                <option value="PVL" <?= $record && $record['coverage_type'] === 'PVL' ? 'selected' : '' ?>>PVL</option>
                            </select>
                        </div>
                        
                        <div>
                            <label for="shift" class="block text-sm font-medium text-gray-300 mb-2">Shift</label>
                            <input type="text" id="shift" name="shift" style="text-transform: uppercase;"
                                   class="w-full px-4 py-3 bg-gray-700/50 border border-gray-600/50 rounded-lg text-gray-200" 
                                   value="<?= $record ? htmlspecialchars($record['shift']) : '' ?>" required>
                        </div>
                        
                        <div>
                            <label for="ir_form" class="block text-sm font-medium text-gray-300 mb-2">IR Form</label>
                            <input type="text" id="ir_form" name="ir_form" style="text-transform: uppercase;"
                                   class="w-full px-4 py-3 bg-gray-700/50 border border-gray-600/50 rounded-lg text-gray-200" 
                                   value="<?= $record ? htmlspecialchars($record['ir_form']) : '' ?>">
                        </div>
                    <?php else: ?>
                        <div>
                            <label for="date_of_incident" class="block text-sm font-medium text-gray-300 mb-2">Date of Tardiness</label>
                            <input type="date" id="date_of_incident" name="date_of_incident" style="text-transform: uppercase;"
                                   class="w-full px-4 py-3 bg-gray-700/50 border border-gray-600/50 rounded-lg text-gray-200" 
                                   value="<?= $record ? htmlspecialchars($record['date_of_incident']) : '' ?>" required>
                        </div>
                        
                        <div>
                            <label for="types" class="block text-sm font-medium text-gray-300 mb-2">Type</label>
                            <select id="types" name="types"
                                    class="w-full px-4 py-3 bg-gray-700/50 border border-gray-600/50 rounded-lg text-gray-200" required>
                                <option value="LATE" <?= $record && $record['types'] === 'LATE' ? 'selected' : '' ?>>LATE</option>
                                <option value="UNDERTIME" <?= $record && $record['types'] === 'UNDERTIME' ? 'selected' : '' ?>>UNDERTIME</option>
                            </select>
                        </div>
                        
                        <div>
                            <label for="minutes_late" class="block text-sm font-medium text-gray-300 mb-2">Minutes Late/Undertime</label>
                            <input type="number" id="minutes_late" name="minutes_late" style="text-transform: uppercase;"
                                   class="w-full px-4 py-3 bg-gray-700/50 border border-gray-600/50 rounded-lg text-gray-200" 
                                   value="<?= $record ? htmlspecialchars($record['minutes_late']) : '' ?>" required>
                        </div>
                        
                        <div>
                            <label for="shift" class="block text-sm font-medium text-gray-300 mb-2">Shift</label>
                            <input type="text" id="shift" name="shift" style="text-transform: uppercase;"
                                   class="w-full px-4 py-3 bg-gray-700/50 border border-gray-600/50 rounded-lg text-gray-200" 
                                   value="<?= $record ? htmlspecialchars($record['shift']) : '' ?>" required>
                        </div>
                        <div>
                            <label for="time_in" class="block text-sm font-medium text-gray-300 mb-2">Time In</label>
                            <input type="text" id="time_in" name="time_in" style="text-transform: uppercase;"
                                   class="w-full px-4 py-3 bg-gray-700/50 border border-gray-600/50 rounded-lg text-gray-200" 
                                   value="<?= $record ? htmlspecialchars($record['time_in']) : '' ?>" required>
                        </div>
                        
                        <div>
                            <label for="ir_form" class="block text-sm font-medium text-gray-300 mb-2">IR Form</label>
                            <input type="text" id="ir_form" name="ir_form" style="text-transform: uppercase;"
                                   class="w-full px-4 py-3 bg-gray-700/50 border border-gray-600/50 rounded-lg text-gray-200" 
                                   value="<?= $record ? htmlspecialchars($record['ir_form']) : '' ?>">
                        </div>
                    <?php endif; ?>
                </div>
                
                <div class="flex justify-between items-center pt-6">
                    <div class="flex space-x-3">
                        <button type="submit" class="bg-primary-600 hover:bg-primary-700 text-white px-6 py-2 rounded-lg flex items-center">
                            <i class="fas fa-save mr-2"></i> Save
                        </button>
                        <a href="attendance.php?tab=<?= $type ?>" class="bg-gray-600 hover:bg-gray-700 text-white px-6 py-2 rounded-lg flex items-center">
                            Cancel
                        </a>
                    </div>
                    <a href="https://docs.google.com/forms/d/e/1FAIpQLScpWhJ-nnGQnLoOMFUx6oW9hQn2y6F335SHQYTbyr--x0ZZtw/viewform?pli=1&pli=1" 
                    target="_blank" 
                    rel="noopener noreferrer" 
                    class="bg-yellow-500/40 hover:bg-yellow-500/30 text-white px-6 py-2 rounded-lg flex items-center">
                        <i class="fas fa-file-alt text-yellow-500 mr-2"></i>
                        IR Form
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
                document.getElementById('email').value = data.employee.email;
            }
        })
        .catch(error => {
            console.error('Error:', error);
        });
}

// Auto-fill employee details if editing
document.addEventListener('DOMContentLoaded', function() {
    <?php if ($record): ?>
        fetchEmployeeDetails('<?= $record['employee_id'] ?>');
    <?php endif; ?>
});
</script>

<?php renderFooter(); ?>