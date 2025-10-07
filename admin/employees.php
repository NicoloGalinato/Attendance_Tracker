<?php
require_once '../includes/config.php';
require_once '../includes/functions.php';

if (!isLoggedIn() || !isAdmin()) {
    redirect(BASE_URL);
}

updateLastActivity();

require_once '../components/layout.php';
renderHead('Manage Employees');
renderNavbar();
renderSidebar('employees');

// Handle employee deletion
if (isset($_GET['delete'])) {
    // Start session if not already started
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    
    $employeeId = (int)$_GET['delete'];
    
    // Check if password is provided and correct
    $requiredPassword = "SLT@2025"; // Change this to your actual password
    $providedPassword = $_POST['delete_password'] ?? '';
    
    if (empty($providedPassword) || $providedPassword !== $requiredPassword) {
        $_SESSION['error'] = "Incorrect or missing password for deletion";
        redirect('employees.php');
        exit();
    }
    
    try {
        $stmt = $pdo->prepare("DELETE FROM employees WHERE id = ?");
        $stmt->execute([$employeeId]);
        
        if ($stmt->rowCount() > 0) {
            $_SESSION['success'] = "Employee deleted successfully!";
        } else {
            $_SESSION['error'] = "Employee not found";
        }
    } catch (PDOException $e) {
        $_SESSION['error'] = "Error deleting employee: " . $e->getMessage();
    }
    
    redirect('employees.php');
}

// Handle status toggle
if (isset($_GET['toggle_status'])) {
    $employeeId = (int)$_GET['toggle_status'];
    
    try {
        // Get current status
        $stmt = $pdo->prepare("SELECT is_active FROM employees WHERE id = ?");
        $stmt->execute([$employeeId]);
        $currentStatus = $stmt->fetchColumn();
        
        // Toggle status
        $newStatus = $currentStatus ? 0 : 1;
        $stmt = $pdo->prepare("UPDATE employees SET is_active = ? WHERE id = ?");
        $stmt->execute([$newStatus, $employeeId]);
        
        $_SESSION['success'] = "Employee status updated successfully!";
    } catch (PDOException $e) {
        $_SESSION['error'] = "Error updating employee status: " . $e->getMessage();
    }
    
    redirect('employees.php');
}

// Handle file import
if (isset($_POST['import'])) {
    if (isset($_FILES['import_file']) && $_FILES['import_file']['error'] === UPLOAD_ERR_OK) {
        $file = $_FILES['import_file']['tmp_name'];
        $fileType = pathinfo($_FILES['import_file']['name'], PATHINFO_EXTENSION);
        
        if ($fileType === 'csv') {
            try {
                $pdo->beginTransaction();
                $handle = fopen($file, 'r');
                
                // Skip header row
                fgetcsv($handle);
                
                $insertStmt = $pdo->prepare("INSERT INTO employees 
                    (employee_id, full_name, department, supervisor, operation_manager, email, is_active) 
                    VALUES 
                    (:employee_id, :full_name, :department, :supervisor, :operation_manager, :email, :is_active)");
                
                $updateStmt = $pdo->prepare("UPDATE employees SET
                    department = :department,
                    supervisor = :supervisor,
                    operation_manager = :operation_manager,
                    email = :email,
                    is_active = :is_active,
                    updated_at = NOW()
                    WHERE employee_id = :employee_id AND full_name = :full_name");
                
                $checkStmt = $pdo->prepare("SELECT id FROM employees WHERE employee_id = ? AND full_name = ?");
                
                $imported = 0;
                $updated = 0;
                $skipped = 0;
                
                while (($data = fgetcsv($handle)) !== FALSE) {
                    if (count($data) >= 6) {
                        $employeeData = [
                            'employee_id' => isset($data[0]) ? strtoupper(trim($data[0])) : '',
                            'full_name' => isset($data[1]) ? strtoupper(trim($data[1])) : '',
                            'department' => isset($data[2]) ? strtoupper(trim($data[2])) : '',
                            'supervisor' => isset($data[3]) ? strtoupper(trim($data[3])) : '',
                            'operation_manager' => isset($data[4]) ? strtoupper(trim($data[4])) : '',
                            'email' => isset($data[5]) ? strtolower(trim($data[5])) : '',
                            'is_active' => isset($data[6]) ? (int)$data[6] : 1
                        ];
                        
                        // Skip if required fields are empty
                        if (empty($employeeData['employee_id']) || empty($employeeData['full_name'])) {
                            $skipped++;
                            continue;
                        }
                        
                        try {
                            // Check if employee already exists
                            $checkStmt->execute([$employeeData['employee_id'], $employeeData['full_name']]);
                            $existingEmployee = $checkStmt->fetch();
                            
                            if ($existingEmployee) {
                                // UPDATE existing employee
                                $updateStmt->execute($employeeData);
                                $updated++;
                            } else {
                                // INSERT new employee
                                $insertStmt->execute($employeeData);
                                $imported++;
                            }
                        } catch (PDOException $e) {
                            $skipped++;
                            error_log("Import error for {$employeeData['employee_id']}: " . $e->getMessage());
                            continue;
                        }
                    }
                }
                                
                fclose($handle);
                $pdo->commit();
                
                $message = "Import completed! ";
                if ($imported > 0) $message .= "$imported new records imported. ";
                if ($updated > 0) $message .= "$updated existing records updated. ";
                
                $_SESSION['success'] = trim($message);
            } catch (Exception $e) {
                $pdo->rollBack();
                $_SESSION['error'] = "Error during import: " . $e->getMessage();
            }
        } else {
            $_SESSION['error'] = "Only CSV files are allowed.";
        }
    } else {
        $_SESSION['error'] = "Please select a valid file to import.";
    }
    
    redirect('employees.php');
}

// Handle bulk team update
if (isset($_POST['bulk_update_team'])) {
    $selectedEmployees = $_POST['selected_employees'] ?? [];
    $newDepartment = $_POST['department'] ?? '';
    $newSupervisor = $_POST['supervisor'] ?? '';
    $newOperationManager = $_POST['operation_manager'] ?? '';
    
    if (empty($selectedEmployees)) {
        $_SESSION['error'] = "Please select at least one employee to update.";
        redirect('employees.php');
    }
    
    try {
        $pdo->beginTransaction();
        $updatedCount = 0;
        
        foreach ($selectedEmployees as $employeeId) {
            $employeeId = (int)$employeeId;
            
            $updateFields = [];
            $updateParams = [];
            
            if (!empty($newDepartment)) {
                $updateFields[] = "department = ?";
                $updateParams[] = $newDepartment;
            }
            
            if (!empty($newSupervisor)) {
                $updateFields[] = "supervisor = ?";
                $updateParams[] = $newSupervisor;
            }
            
            if (!empty($newOperationManager)) {
                $updateFields[] = "operation_manager = ?";
                $updateParams[] = $newOperationManager;
            }
            
            if (!empty($updateFields)) {
                $updateParams[] = $employeeId;
                $sql = "UPDATE employees SET " . implode(', ', $updateFields) . " WHERE id = ?";
                $stmt = $pdo->prepare($sql);
                $stmt->execute($updateParams);
                $updatedCount++;
            }
        }
        
        $pdo->commit();
        $_SESSION['success'] = "Successfully updated team information for $updatedCount employees!";
    } catch (PDOException $e) {
        $pdo->rollBack();
        $_SESSION['error'] = "Error updating team information: " . $e->getMessage();
    }
    
    redirect('employees.php');
}

// Get unique values for filter dropdowns
try {
    $deptStmt = $pdo->query("SELECT DISTINCT department FROM employees WHERE department IS NOT NULL AND department != '' ORDER BY department");
    $departments = $deptStmt->fetchAll(PDO::FETCH_COLUMN);
    
    $supervisorStmt = $pdo->query("SELECT DISTINCT supervisor FROM employees WHERE supervisor IS NOT NULL AND supervisor != '' ORDER BY supervisor");
    $supervisors = $supervisorStmt->fetchAll(PDO::FETCH_COLUMN);
    
    $opManagerStmt = $pdo->query("SELECT DISTINCT operation_manager FROM employees WHERE operation_manager IS NOT NULL AND operation_manager != '' ORDER BY operation_manager");
    $operationManagers = $opManagerStmt->fetchAll(PDO::FETCH_COLUMN);
} catch (PDOException $e) {
    $departments = [];
    $supervisors = [];
    $operationManagers = [];
}
?>

<div class="pt-2 min-h-screen">
    <main class="p-6">
        <div class="flex justify-between items-center mb-6">
            <h1 class="text-2xl font-bold">Manage Agents</h1>
            <div class="flex space-x-2">
                <button type="button" onclick="document.getElementById('importModal').classList.remove('hidden')" 
                        class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg flex items-center">
                    <i class="fas fa-file-import mr-2"></i> Import
                </button>
                <button type="button" id="editTeamBtn" onclick="showEditTeamModal()" 
                        class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded-lg flex items-center hidden">
                    <i class="fas fa-users-cog mr-2"></i> Edit Team
                </button>
                <a href="employee.php?action=create" class="bg-primary-600 hover:bg-primary-700 text-white px-4 py-2 rounded-lg flex items-center">
                    <i class="fas fa-plus mr-2"></i> Add New
                </a>
            </div>
        </div>

        <?php renderAlert(); ?>
        
        <!-- Import Modal -->
        <div id="importModal" class="hidden fixed inset-0 bg-gray-900 bg-opacity-50 flex items-center justify-center z-50">
            <div class="bg-gray-800 rounded-lg border border-gray-700 shadow-xl w-full max-w-md">
                <div class="p-6">
                    <div class="flex justify-between items-center mb-4">
                        <h3 class="text-xl font-bold">Import Employees</h3>
                    </div>
                    
                    <div class="bg-gray-800 rounded-lg p-4 shadow-md border border-gray-700 mb-4">
                        <p class="text-sm text-gray-400 mb-4">
                            Upload a CSV file with employee data. The file should have the following columns in order:<br>
                            <span class="font-mono text-xs bg-gray-700 p-1 rounded">employee_id, full_name, department, supervisor, operation_manager, email, is_active(optional)</span>
                        </p>
                        <p class="text-sm text-gray-400">
                            <a href="#" onclick="downloadSampleCSV()" class="text-blue-400 hover:underline">Download sample CSV file</a>
                        </p>
                    </div>
                    
                    <form method="POST" enctype="multipart/form-data">
                        <div class="mb-4">
                            <input type="file" name="import_file" id="import_file" 
                                   class="block w-full text-sm text-gray-400
                                          file:mr-4 file:py-2 file:px-4
                                          file:rounded-lg file:border-0
                                          file:text-sm file:font-semibold
                                          file:bg-blue-500 file:text-white
                                          hover:file:bg-blue-600"
                                   accept=".csv" required>
                        </div>
                        
                        <div class="flex justify-end space-x-3">
                            <button type="button" onclick="document.getElementById('importModal').classList.add('hidden')" 
                                    class="bg-gray-600 hover:bg-gray-700 text-white px-4 py-2 rounded-lg">
                                Cancel
                            </button>
                            <button type="submit" name="import" 
                                    class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg flex items-center">
                                <i class="fas fa-file-import mr-2"></i> Import
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        
        <!-- Edit Team Modal -->
        <div id="editTeamModal" class="hidden fixed inset-0 bg-gray-900 bg-opacity-50 flex items-center justify-center z-50">
            <div class="bg-gray-800 rounded-lg border border-gray-700 shadow-xl w-full max-w-md">
                <div class="p-6">
                    <div class="flex justify-between items-center mb-4">
                        <h3 class="text-xl font-bold">Edit Team Information</h3>
                    </div>
                    
                    <form method="POST" id="editTeamForm">
                        <!-- Hidden field para sa selected employees -->
                        <div id="selectedEmployeesContainer"></div>
                        
                        <div class="space-y-4">
                            <div>
                                <label for="edit_department" class="block text-sm font-medium text-gray-300 mb-1">Department</label>
                                <select id="edit_department" name="department" 
                                        class="w-full px-3 py-2 bg-gray-700 border border-gray-600 rounded-md text-gray-200 appearance-none" style="text-transform: uppercase;">
                                    <option value="">-- Keep Current --</option>
                                    <?php foreach ($departments as $dept): ?>
                                        <option value="<?= htmlspecialchars($dept) ?>"><?= htmlspecialchars($dept) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            
                            <div>
                                <label for="edit_supervisor" class="block text-sm font-medium text-gray-300 mb-1">Supervisor</label>
                                <select id="edit_supervisor" name="supervisor" 
                                        class="w-full px-3 py-2 bg-gray-700 border border-gray-600 rounded-md text-gray-200 appearance-none" style="text-transform: uppercase;">
                                    <option value="">-- Keep Current --</option>
                                    <?php foreach ($supervisors as $supervisor): ?>
                                        <option value="<?= htmlspecialchars($supervisor) ?>"><?= htmlspecialchars($supervisor) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            
                            <div>
                                <label for="edit_operation_manager" class="block text-sm font-medium text-gray-300 mb-1">Operation Manager</label>
                                <select id="edit_operation_manager" name="operation_manager" 
                                        class="w-full px-3 py-2 bg-gray-700 border border-gray-600 rounded-md text-gray-200 appearance-none" style="text-transform: uppercase;">
                                    <option value="">-- Keep Current --</option>
                                    <?php foreach ($operationManagers as $opManager): ?>
                                        <option value="<?= htmlspecialchars($opManager) ?>"><?= htmlspecialchars($opManager) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            
                            <div class="bg-yellow-900/20 border border-yellow-700 rounded-lg p-3">
                                <p class="text-sm text-yellow-300">
                                    <i class="fas fa-exclamation-triangle mr-2"></i>
                                    This will update the selected fields for all checked employees. Leave fields empty to keep current values.
                                </p>
                            </div>
                        </div>
                        
                        <div class="flex justify-end space-x-3 mt-6">
                            <button type="button" onclick="closeEditTeamModal()" 
                                    class="bg-gray-600 hover:bg-gray-700 text-white px-4 py-2 rounded-lg">
                                Cancel
                            </button>
                            <button type="submit" name="bulk_update_team" 
                                    class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded-lg flex items-center">
                                <i class="fas fa-save mr-2"></i> Update Team
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        
        <!-- Search and Filter Section -->
        <div class="mb-6 grid grid-cols-1 md:grid-cols-2 lg:grid-cols-5 gap-4">
            <div class="relative flex-grow">
                <input type="text" id="searchInput" 
                       class="w-full pl-10 pr-4 py-2 bg-gray-700/50 border border-gray-600/50 rounded-lg text-gray-200" 
                       placeholder="Search by CXI number or name..." >
                <div class="absolute left-3 top-2.5 text-gray-400">
                    <i class="fas fa-search"></i>
                </div>
            </div>
            <div class="relative">
                <select id="departmentFilter" 
                        class="w-full pl-4 pr-4 py-2 bg-gray-700/50 border border-gray-600/50 rounded-lg text-gray-200 appearance-none" style="text-transform: uppercase;">
                    <option value="">All Departments</option>
                    <?php foreach ($departments as $dept): ?>
                        <option value="<?= htmlspecialchars($dept) ?>"><?= htmlspecialchars($dept) ?></option>
                    <?php endforeach; ?>
                </select>
                <div class="absolute right-3 top-2.5 text-gray-400">
                    <i class="fas fa-chevron-down"></i>
                </div>
            </div>
            <div class="relative">
                <select id="supervisorFilter" 
                        class="w-full pl-4 pr-4 py-2 bg-gray-700/50 border border-gray-600/50 rounded-lg text-gray-200 appearance-none" style="text-transform: uppercase;">
                    <option value="">All Supervisors</option>
                    <?php foreach ($supervisors as $supervisor): ?>
                        <option value="<?= htmlspecialchars($supervisor) ?>"><?= htmlspecialchars($supervisor) ?></option>
                    <?php endforeach; ?>
                </select>
                <div class="absolute right-3 top-2.5 text-gray-400">
                    <i class="fas fa-chevron-down"></i>
                </div>
            </div>
            <div class="relative">
                <select id="operationManagerFilter" 
                        class="w-full pl-4 pr-4 py-2 bg-gray-700/50 border border-gray-600/50 rounded-lg text-gray-200 appearance-none" style="text-transform: uppercase;">
                    <option value="">All Operation Managers</option>
                    <?php foreach ($operationManagers as $opManager): ?>
                        <option value="<?= htmlspecialchars($opManager) ?>"><?= htmlspecialchars($opManager) ?></option>
                    <?php endforeach; ?>
                </select>
                <div class="absolute right-3 top-2.5 text-gray-400">
                    <i class="fas fa-chevron-down"></i>
                </div>
            </div>
            <div class="relative">
                <select id="statusFilter" 
                        class="w-full pl-4 pr-4 py-2 bg-gray-700/50 border border-gray-600/50 rounded-lg text-gray-200 appearance-none" style="text-transform: uppercase;">
                    <option value="">All Status</option>
                    <option value="1">Active</option>
                    <option value="0">Inactive</option>
                </select>
                <div class="absolute right-3 top-2.5 text-gray-400">
                    <i class="fas fa-chevron-down"></i>
                </div>
            </div>
        </div>

        <div id="employeesTableContainer">
            <?php include 'partials/employees_table.php'; ?>
        </div>
    </main>
</div>

<script>
function downloadSampleCSV() {
    const csvContent = "employee_id,full_name,department,supervisor,operation_manager,email,is_active\n" +
                      "CXI12345,JUAN DELA CRUZ,SALES,MARIA SANTOS,PEDRO REYES,juan.delacruz@example.com,1\n" +
                      "CXI12346,MARIA CLARA,HR,JOSE RIZAL,ANDRES BONIFACIO,maria.clara@example.com,1\n" +
                      "CXI12347,JOSE PROTASIO,IT,EMILIO AGUINALDO,APOLINARIO MABINI,jose.protasio@example.com,0";
    
    const blob = new Blob([csvContent], { type: 'text/csv;charset=utf-8;' });
    const url = URL.createObjectURL(blob);
    const link = document.createElement('a');
    link.setAttribute('href', url);
    link.setAttribute('download', 'employees_sample.csv');
    link.style.visibility = 'hidden';
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
}

function showEditTeamModal() {
    const selectedCheckboxes = document.querySelectorAll('.employee-checkbox:checked');
    const selectedCount = selectedCheckboxes.length;
    
    if (selectedCount === 0) {
        alert('Please select at least one employee to edit.');
        return;
    }
    
    // Clear previous selected employees
    const container = document.getElementById('selectedEmployeesContainer');
    container.innerHTML = '';
    
    // Add hidden inputs for each selected employee
    selectedCheckboxes.forEach(checkbox => {
        const hiddenInput = document.createElement('input');
        hiddenInput.type = 'hidden';
        hiddenInput.name = 'selected_employees[]';
        hiddenInput.value = checkbox.value;
        container.appendChild(hiddenInput);
    });
    
    document.getElementById('editTeamModal').classList.remove('hidden');
}

function closeEditTeamModal() {
    document.getElementById('editTeamModal').classList.add('hidden');
}

function toggleEditTeamButton() {
    const selectedCount = document.querySelectorAll('.employee-checkbox:checked').length;
    const editTeamBtn = document.getElementById('editTeamBtn');
    
    if (selectedCount > 0) {
        editTeamBtn.classList.remove('hidden');
    } else {
        editTeamBtn.classList.add('hidden');
    }
}

function selectAllEmployees(checkbox) {
    const checkboxes = document.querySelectorAll('.employee-checkbox');
    checkboxes.forEach(cb => {
        cb.checked = checkbox.checked;
    });
    toggleEditTeamButton();
}

document.addEventListener('DOMContentLoaded', function() {
    const searchInput = document.getElementById('searchInput');
    const departmentFilter = document.getElementById('departmentFilter');
    const supervisorFilter = document.getElementById('supervisorFilter');
    const operationManagerFilter = document.getElementById('operationManagerFilter');
    const statusFilter = document.getElementById('statusFilter');
    let searchTimeout;

    function loadEmployees(search = '', department = '', supervisor = '', operationManager = '', status = '', page = 1) {
        const formData = new FormData();
        formData.append('search', search);
        formData.append('department', department);
        formData.append('supervisor', supervisor);
        formData.append('operation_manager', operationManager);
        formData.append('status', status);
        formData.append('page', page);

        // I-send ang mga currently selected employees
        const selectedEmployees = Array.from(document.querySelectorAll('.employee-checkbox:checked'))
            .map(checkbox => checkbox.value);
        formData.append('selected_employees_json', JSON.stringify(selectedEmployees));

        fetch('partials/employees_table.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.text())
        .then(data => {
            document.getElementById('employeesTableContainer').innerHTML = data;
            
            // Re-attach event listeners after table reload
            document.querySelectorAll('.employee-checkbox').forEach(checkbox => {
                checkbox.addEventListener('change', toggleEditTeamButton);
            });

            // Re-check previously selected employees after table reload
            if (selectedEmployees.length > 0) {
                selectedEmployees.forEach(employeeId => {
                    const checkbox = document.querySelector(`.employee-checkbox[value="${employeeId}"]`);
                    if (checkbox) {
                        checkbox.checked = true;
                    }
                });
                toggleEditTeamButton();
            }
            
            document.querySelectorAll('.pagination-link').forEach(link => {
                link.addEventListener('click', function(e) {
                    e.preventDefault();
                    const page = this.getAttribute('data-page');
                    loadEmployees(searchInput.value, departmentFilter.value, supervisorFilter.value, operationManagerFilter.value, statusFilter.value, page);
                    updateUrl(searchInput.value, departmentFilter.value, supervisorFilter.value, operationManagerFilter.value, statusFilter.value, page);
                });
            });
        });
    }

    function updateUrl(search, department, supervisor, operationManager, status, page) {
        const params = new URLSearchParams();
        if (search) params.append('search', search);
        if (department) params.append('department', department);
        if (supervisor) params.append('supervisor', supervisor);
        if (operationManager) params.append('operation_manager', operationManager);
        if (status) params.append('status', status);
        if (page && page > 1) params.append('page', page);
        
        const queryString = params.toString();
        history.pushState(null, '', `?${queryString}`);
    }

    function handleFilterChange() {
        clearTimeout(searchTimeout);
        searchTimeout = setTimeout(() => {
            loadEmployees(searchInput.value, departmentFilter.value, supervisorFilter.value, operationManagerFilter.value, statusFilter.value);
            updateUrl(searchInput.value, departmentFilter.value, supervisorFilter.value, operationManagerFilter.value, statusFilter.value, 1);
        }, 300);
    }

    searchInput.addEventListener('input', handleFilterChange);
    departmentFilter.addEventListener('change', handleFilterChange);
    supervisorFilter.addEventListener('change', handleFilterChange);
    operationManagerFilter.addEventListener('change', handleFilterChange);
    statusFilter.addEventListener('change', handleFilterChange);

    window.addEventListener('popstate', function() {
        const urlParams = new URLSearchParams(window.location.search);
        const searchParam = urlParams.get('search') || '';
        const departmentParam = urlParams.get('department') || '';
        const supervisorParam = urlParams.get('supervisor') || '';
        const operationManagerParam = urlParams.get('operation_manager') || '';
        const statusParam = urlParams.get('status') || '';
        const pageParam = urlParams.get('page') || 1;
        
        searchInput.value = searchParam;
        departmentFilter.value = departmentParam;
        supervisorFilter.value = supervisorParam;
        operationManagerFilter.value = operationManagerParam;
        statusFilter.value = statusParam;
        loadEmployees(searchParam, departmentParam, supervisorParam, operationManagerParam, statusParam, pageParam);
    });

    // Initialize with URL parameters
    const urlParams = new URLSearchParams(window.location.search);
    const initialSearch = urlParams.get('search') || '';
    const initialDepartment = urlParams.get('department') || '';
    const initialSupervisor = urlParams.get('supervisor') || '';
    const initialOperationManager = urlParams.get('operation_manager') || '';
    const initialStatus = urlParams.get('status') || '';
    const initialPage = urlParams.get('page') || 1;
    
    if (initialSearch) searchInput.value = initialSearch;
    if (initialDepartment) departmentFilter.value = initialDepartment;
    if (initialSupervisor) supervisorFilter.value = initialSupervisor;
    if (initialOperationManager) operationManagerFilter.value = initialOperationManager;
    if (initialStatus) statusFilter.value = initialStatus;
    loadEmployees(initialSearch, initialDepartment, initialSupervisor, initialOperationManager, initialStatus, initialPage);
});

// Delete confirmation modal
function showDeleteModal(recordId, recordType = '') {
    const modal = `
        <div id="deleteModal" class="fixed inset-0 bg-gray-900 bg-opacity-50 flex items-center justify-center z-50">
            <div class="bg-gray-800 rounded-lg border border-gray-700 shadow-xl w-full max-w-md">
                <div class="px-6 py-6">
                    <h3 class="text-lg font-bold text-gray-100 mb-4">Confirm Deletion</h3>
                    <p class="text-gray-300 mb-4">Are you sure you want to delete this record?</p>
                    <form method="post" action="${window.location.pathname}?delete=${recordId}${recordType ? '&type=' + recordType : ''}" class="space-y-4">
                        <div>
                            <label for="delete_password" class="block text-sm font-medium text-gray-300 mb-1">To confirm please enter the KEY:</label>
                            <input type="password" name="delete_password" id="delete_password" 
                                   class="w-full px-3 py-2 bg-gray-700 border border-gray-600 rounded-md text-gray-200" required>
                        </div>
                        <div class="flex justify-end space-x-3">
                            <button type="button" onclick="closeDeleteModal()" 
                                    class="px-4 py-2 bg-gray-600 text-gray-100 rounded-md hover:bg-gray-500">
                                Cancel
                            </button>
                            <button type="submit" 
                                    class="px-4 py-2 bg-red-600 text-white rounded-md hover:bg-red-500">
                                Delete
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    `;
    
    document.body.insertAdjacentHTML('beforeend', modal);
}

function closeDeleteModal() {
    const modal = document.getElementById('deleteModal');
    if (modal) {
        modal.remove();
    }
}

// Add event listeners for clicking outside modal or pressing Escape
document.addEventListener('click', function(e) {
    if (e.target === document.getElementById('deleteModal')) {
        closeDeleteModal();
    }
    if (e.target === document.getElementById('editTeamModal')) {
        closeEditTeamModal();
    }
});

document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        if (document.getElementById('deleteModal')) {
            closeDeleteModal();
        }
        if (document.getElementById('editTeamModal')) {
            closeEditTeamModal();
        }
    }
});
</script>

<?php renderFooter(); ?>