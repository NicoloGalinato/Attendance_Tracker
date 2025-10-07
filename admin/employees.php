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
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    
    $employeeId = (int)$_GET['delete'];
    $requiredPassword = "SLT@2025";
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
        $stmt = $pdo->prepare("SELECT is_active FROM employees WHERE id = ?");
        $stmt->execute([$employeeId]);
        $currentStatus = $stmt->fetchColumn();
        
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
                        
                        if (empty($employeeData['employee_id']) || empty($employeeData['full_name'])) {
                            $skipped++;
                            continue;
                        }
                        
                        try {
                            $checkStmt->execute([$employeeData['employee_id'], $employeeData['full_name']]);
                            $existingEmployee = $checkStmt->fetch();
                            
                            if ($existingEmployee) {
                                $updateStmt->execute($employeeData);
                                $updated++;
                            } else {
                                $insertStmt->execute($employeeData);
                                $imported++;
                            }
                        } catch (PDOException $e) {
                            $skipped++;
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

<!-- Minimal Loading Screen - Only for initial page load -->
<div id="initialLoading" class="fixed inset-0 bg-gray-900 flex items-center justify-center z-50 transition-opacity duration-300">
    <div class="text-center">
        <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-blue-500 mx-auto mb-4"></div>
        <p class="text-white text-lg">Loading Employees...</p>
    </div>
</div>

<div class="pt-2 min-h-screen">
    <main class="p-6">
        <div class="flex justify-between items-center mb-6">
            <h1 class="text-2xl font-bold text-white">Manage Agents</h1>
            <div class="flex space-x-2">
                <button type="button" onclick="showImportModal()" 
                        class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg flex items-center transition-colors duration-200">
                    <i class="fas fa-file-import mr-2"></i> Import
                </button>
                <button type="button" id="editTeamBtn" onclick="showEditTeamModal()" 
                        class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded-lg flex items-center transition-colors duration-200 hidden">
                    <i class="fas fa-users-cog mr-2"></i> Edit Team
                </button>
                <a href="employee.php?action=create" class="bg-primary-600 hover:bg-primary-700 text-white px-4 py-2 rounded-lg flex items-center transition-colors duration-200">
                    <i class="fas fa-plus mr-2"></i> Add New
                </a>
            </div>
        </div>

        <?php renderAlert(); ?>
        
        <!-- Import Modal -->
        <div id="importModal" class="hidden fixed inset-0 bg-gray-900/50 backdrop-blur-sm flex items-center justify-center z-50 transition-opacity duration-200">
            <div class="bg-gray-800 rounded-lg border border-gray-700 shadow-xl w-full max-w-md transform transition-transform duration-200 scale-95">
                <div class="p-6">
                    <div class="flex justify-between items-center mb-4">
                        <h3 class="text-xl font-bold text-white">Import Employees</h3>
                        <button type="button" onclick="hideImportModal()" class="text-gray-400 hover:text-white">
                            <i class="fas fa-times"></i>
                        </button>
                    </div>
                    
                    <div class="bg-gray-700/50 rounded-lg p-4 border border-gray-600 mb-4">
                        <p class="text-sm text-gray-300 mb-3">
                            Upload a CSV file with employee data. Required columns:
                        </p>
                        <code class="text-xs bg-gray-600 p-2 rounded block text-gray-200">
                            employee_id, full_name, department, supervisor, operation_manager, email, is_active
                        </code>
                        <p class="text-sm text-gray-400 mt-3">
                            <a href="#" onclick="downloadSampleCSV()" class="text-blue-400 hover:underline text-sm">
                                <i class="fas fa-download mr-1"></i>Download sample CSV
                            </a>
                        </p>
                    </div>
                    
                    <form method="POST" enctype="multipart/form-data" id="importForm">
                        <div class="mb-4">
                            <input type="file" name="import_file" id="import_file" 
                                   class="block w-full text-sm text-gray-400
                                          file:mr-4 file:py-2 file:px-4
                                          file:rounded-lg file:border-0
                                          file:text-sm file:font-semibold
                                          file:bg-blue-500 file:text-white
                                          hover:file:bg-blue-600 transition-colors duration-200"
                                   accept=".csv" required>
                        </div>
                        
                        <div class="flex justify-end space-x-3">
                            <button type="button" onclick="hideImportModal()" 
                                    class="bg-gray-600 hover:bg-gray-500 text-white px-4 py-2 rounded-lg transition-colors duration-200">
                                Cancel
                            </button>
                            <button type="submit" name="import" id="importSubmit" 
                                    class="bg-blue-600 hover:bg-blue-500 text-white px-4 py-2 rounded-lg flex items-center transition-colors duration-200">
                                <i class="fas fa-file-import mr-2"></i> Import
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        
        <!-- Edit Team Modal -->
        <div id="editTeamModal" class="hidden fixed inset-0 bg-gray-900/50 backdrop-blur-sm flex items-center justify-center z-50 transition-opacity duration-200">
            <div class="bg-gray-800 rounded-lg border border-gray-700 shadow-xl w-full max-w-md transform transition-transform duration-200 scale-95">
                <div class="p-6">
                    <div class="flex justify-between items-center mb-4">
                        <h3 class="text-xl font-bold text-white">Edit Team Information</h3>
                        <button type="button" onclick="hideEditTeamModal()" class="text-gray-400 hover:text-white">
                            <i class="fas fa-times"></i>
                        </button>
                    </div>
                    
                    <form method="POST" id="editTeamForm">
                        <div id="selectedEmployeesContainer"></div>
                        
                        <div class="space-y-4">
                            <div>
                                <label for="edit_department" class="block text-sm font-medium text-gray-300 mb-1">Department</label>
                                <select id="edit_department" name="department" 
                                        class="w-full px-3 py-2 bg-gray-700 border border-gray-600 rounded-md text-gray-200 focus:border-blue-500 focus:ring-1 focus:ring-blue-500 transition-colors duration-200 uppercase">
                                    <option value="">-- Keep Current --</option>
                                    <?php foreach ($departments as $dept): ?>
                                        <option value="<?= htmlspecialchars($dept) ?>"><?= htmlspecialchars($dept) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            
                            <div>
                                <label for="edit_supervisor" class="block text-sm font-medium text-gray-300 mb-1">Supervisor</label>
                                <select id="edit_supervisor" name="supervisor" 
                                        class="w-full px-3 py-2 bg-gray-700 border border-gray-600 rounded-md text-gray-200 focus:border-blue-500 focus:ring-1 focus:ring-blue-500 transition-colors duration-200 uppercase">
                                    <option value="">-- Keep Current --</option>
                                    <?php foreach ($supervisors as $supervisor): ?>
                                        <option value="<?= htmlspecialchars($supervisor) ?>"><?= htmlspecialchars($supervisor) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            
                            <div>
                                <label for="edit_operation_manager" class="block text-sm font-medium text-gray-300 mb-1">Operation Manager</label>
                                <select id="edit_operation_manager" name="operation_manager" 
                                        class="w-full px-3 py-2 bg-gray-700 border border-gray-600 rounded-md text-gray-200 focus:border-blue-500 focus:ring-1 focus:ring-blue-500 transition-colors duration-200 uppercase">
                                    <option value="">-- Keep Current --</option>
                                    <?php foreach ($operationManagers as $opManager): ?>
                                        <option value="<?= htmlspecialchars($opManager) ?>"><?= htmlspecialchars($opManager) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            
                            <div class="bg-yellow-900/20 border border-yellow-700 rounded-lg p-3">
                                <p class="text-sm text-yellow-300 flex items-start">
                                    <i class="fas fa-exclamation-triangle mr-2 mt-0.5 flex-shrink-0"></i>
                                    This will update the selected fields for all checked employees. Leave fields empty to keep current values.
                                </p>
                            </div>
                        </div>
                        
                        <div class="flex justify-end space-x-3 mt-6">
                            <button type="button" onclick="hideEditTeamModal()" 
                                    class="bg-gray-600 hover:bg-gray-500 text-white px-4 py-2 rounded-lg transition-colors duration-200">
                                Cancel
                            </button>
                            <button type="submit" name="bulk_update_team" id="updateTeamSubmit"
                                    class="bg-green-600 hover:bg-green-500 text-white px-4 py-2 rounded-lg flex items-center transition-colors duration-200">
                                <i class="fas fa-save mr-2"></i> Update Team
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        
        <!-- Search and Filter Section -->
        <div class="mb-6 grid grid-cols-1 md:grid-cols-2 lg:grid-cols-5 gap-3">
            <div class="relative flex-grow">
                <input type="text" id="searchInput" 
                       class="w-full pl-10 pr-4 py-2 bg-gray-700/50 border border-gray-600/50 rounded-lg text-gray-200 placeholder-gray-400 focus:border-blue-500 focus:ring-1 focus:ring-blue-500 transition-colors duration-200" 
                       placeholder="Search by CXI number or name...">
                <div class="absolute left-3 top-2.5 text-gray-400">
                    <i class="fas fa-search"></i>
                </div>
            </div>
            <div class="relative">
                <select id="departmentFilter" 
                        class="w-full pl-4 pr-10 py-2 bg-gray-700/50 border border-gray-600/50 rounded-lg text-gray-200 focus:border-blue-500 focus:ring-1 focus:ring-blue-500 transition-colors duration-200 appearance-none uppercase">
                    <option value="">All Departments</option>
                    <?php foreach ($departments as $dept): ?>
                        <option value="<?= htmlspecialchars($dept) ?>"><?= htmlspecialchars($dept) ?></option>
                    <?php endforeach; ?>
                </select>
                <div class="absolute right-3 top-2.5 text-gray-400 pointer-events-none">
                    <i class="fas fa-chevron-down text-sm"></i>
                </div>
            </div>
            <div class="relative">
                <select id="supervisorFilter" 
                        class="w-full pl-4 pr-10 py-2 bg-gray-700/50 border border-gray-600/50 rounded-lg text-gray-200 focus:border-blue-500 focus:ring-1 focus:ring-blue-500 transition-colors duration-200 appearance-none uppercase">
                    <option value="">All Supervisors</option>
                    <?php foreach ($supervisors as $supervisor): ?>
                        <option value="<?= htmlspecialchars($supervisor) ?>"><?= htmlspecialchars($supervisor) ?></option>
                    <?php endforeach; ?>
                </select>
                <div class="absolute right-3 top-2.5 text-gray-400 pointer-events-none">
                    <i class="fas fa-chevron-down text-sm"></i>
                </div>
            </div>
            <div class="relative">
                <select id="operationManagerFilter" 
                        class="w-full pl-4 pr-10 py-2 bg-gray-700/50 border border-gray-600/50 rounded-lg text-gray-200 focus:border-blue-500 focus:ring-1 focus:ring-blue-500 transition-colors duration-200 appearance-none uppercase">
                    <option value="">All Operation Managers</option>
                    <?php foreach ($operationManagers as $opManager): ?>
                        <option value="<?= htmlspecialchars($opManager) ?>"><?= htmlspecialchars($opManager) ?></option>
                    <?php endforeach; ?>
                </select>
                <div class="absolute right-3 top-2.5 text-gray-400 pointer-events-none">
                    <i class="fas fa-chevron-down text-sm"></i>
                </div>
            </div>
            <div class="relative">
                <select id="statusFilter" 
                        class="w-full pl-4 pr-10 py-2 bg-gray-700/50 border border-gray-600/50 rounded-lg text-gray-200 focus:border-blue-500 focus:ring-1 focus:ring-blue-500 transition-colors duration-200 appearance-none">
                    <option value="">All Status</option>
                    <option value="1">Active</option>
                    <option value="0">Inactive</option>
                </select>
                <div class="absolute right-3 top-2.5 text-gray-400 pointer-events-none">
                    <i class="fas fa-chevron-down text-sm"></i>
                </div>
            </div>
        </div>

        <!-- Employees Table Container -->
        <div id="employeesTableContainer" class="transition-opacity duration-200">
            <?php include 'partials/employees_table.php'; ?>
        </div>
    </main>
</div>

<script>
// Simple Loading Manager - Only for initial load
class SimpleLoadingManager {
    constructor() {
        this.initialLoading = document.getElementById('initialLoading');
        this.init();
    }

    init() {
        // Hide loading when page is ready
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', () => this.hideInitialLoading());
        } else {
            this.hideInitialLoading();
        }
    }

    hideInitialLoading() {
        setTimeout(() => {
            if (this.initialLoading) {
                this.initialLoading.style.opacity = '0';
                setTimeout(() => {
                    this.initialLoading.remove();
                }, 200);
            }
        }, 500);
    }
}

// Initialize simple loading manager
const simpleLoadingManager = new SimpleLoadingManager();

// Optimized Employee Manager - No loading on search
class EmployeeManager {
    constructor() {
        this.searchTimeout = null;
        this.debounceDelay = 400; // Slightly longer delay for better UX
        this.currentPage = 1;
        this.filters = {
            search: '',
            department: '',
            supervisor: '',
            operationManager: '',
            status: ''
        };
        this.isLoading = false;
        this.init();
    }

    init() {
        this.bindEvents();
        this.loadInitialData();
    }

    bindEvents() {
        const searchInput = document.getElementById('searchInput');
        const departmentFilter = document.getElementById('departmentFilter');
        const supervisorFilter = document.getElementById('supervisorFilter');
        const operationManagerFilter = document.getElementById('operationManagerFilter');
        const statusFilter = document.getElementById('statusFilter');

        searchInput.addEventListener('input', () => this.handleFilterChange('search', searchInput.value));
        departmentFilter.addEventListener('change', () => this.handleFilterChange('department', departmentFilter.value));
        supervisorFilter.addEventListener('change', () => this.handleFilterChange('supervisor', supervisorFilter.value));
        operationManagerFilter.addEventListener('change', () => this.handleFilterChange('operationManager', operationManagerFilter.value));
        statusFilter.addEventListener('change', () => this.handleFilterChange('status', statusFilter.value));

        // Keyboard shortcuts
        document.addEventListener('keydown', (e) => {
            if (e.ctrlKey || e.metaKey) {
                switch(e.key) {
                    case 'f':
                        e.preventDefault();
                        searchInput.focus();
                        break;
                    case 'q':
                        e.preventDefault();
                        window.location.href = 'employee.php?action=create';
                        break;
                }
            }
        });
    }

    handleFilterChange(type, value) {
        this.filters[type] = value;
        
        clearTimeout(this.searchTimeout);
        this.searchTimeout = setTimeout(() => {
            this.loadEmployees(1);
            this.updateUrl(1);
        }, this.debounceDelay);
    }

    async loadEmployees(page = 1) {
        if (this.isLoading) return; // Prevent multiple simultaneous requests
        
        this.currentPage = page;
        this.isLoading = true;

        try {
            const formData = new FormData();
            formData.append('search', this.filters.search);
            formData.append('department', this.filters.department);
            formData.append('supervisor', this.filters.supervisor);
            formData.append('operation_manager', this.filters.operationManager);
            formData.append('status', this.filters.status);
            formData.append('page', page);

            // Preserve selected employees
            const selectedEmployees = this.getSelectedEmployees();
            formData.append('selected_employees_json', JSON.stringify(selectedEmployees));

            const response = await fetch('partials/employees_table.php', {
                method: 'POST',
                body: formData,
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            });

            if (!response.ok) throw new Error('Network response was not ok');

            const data = await response.text();
            document.getElementById('employeesTableContainer').innerHTML = data;
            
            this.rebindEvents();
            this.restoreSelections(selectedEmployees);
            this.updateButtonStates();

        } catch (error) {
            console.error('Error loading employees:', error);
            this.showError('Error loading data. Please try again.');
        } finally {
            this.isLoading = false;
        }
    }

    getSelectedEmployees() {
        return Array.from(document.querySelectorAll('.employee-checkbox:checked'))
            .map(checkbox => checkbox.value);
    }

    restoreSelections(selectedEmployees) {
        if (selectedEmployees.length > 0) {
            selectedEmployees.forEach(employeeId => {
                const checkbox = document.querySelector(`.employee-checkbox[value="${employeeId}"]`);
                if (checkbox) checkbox.checked = true;
            });
        }
    }

    rebindEvents() {
        // Rebind checkbox events
        document.querySelectorAll('.employee-checkbox').forEach(checkbox => {
            checkbox.addEventListener('change', () => this.updateButtonStates());
        });

        // Rebind pagination events
        document.querySelectorAll('.pagination-link').forEach(link => {
            link.addEventListener('click', (e) => {
                e.preventDefault();
                const page = parseInt(link.getAttribute('data-page'));
                this.loadEmployees(page);
                this.updateUrl(page);
            });
        });

        // Rebind select all
        const selectAll = document.getElementById('selectAll');
        if (selectAll) {
            selectAll.addEventListener('change', (e) => this.selectAllEmployees(e.target.checked));
        }
    }

    selectAllEmployees(checked) {
        document.querySelectorAll('.employee-checkbox').forEach(checkbox => {
            checkbox.checked = checked;
        });
        this.updateButtonStates();
    }

    updateButtonStates() {
        const selectedCount = this.getSelectedEmployees().length;
        const editTeamBtn = document.getElementById('editTeamBtn');
        
        if (selectedCount > 0) {
            editTeamBtn.classList.remove('hidden');
        } else {
            editTeamBtn.classList.add('hidden');
        }
    }

    updateUrl(page) {
        const params = new URLSearchParams();
        
        Object.entries(this.filters).forEach(([key, value]) => {
            if (value) params.append(key, value);
        });
        
        if (page > 1) params.append('page', page);
        
        const queryString = params.toString();
        const newUrl = queryString ? `?${queryString}` : window.location.pathname;
        history.replaceState(null, '', newUrl);
    }

    loadInitialData() {
        const urlParams = new URLSearchParams(window.location.search);
        
        this.filters = {
            search: urlParams.get('search') || '',
            department: urlParams.get('department') || '',
            supervisor: urlParams.get('supervisor') || '',
            operationManager: urlParams.get('operation_manager') || '',
            status: urlParams.get('status') || ''
        };
        
        const initialPage = parseInt(urlParams.get('page')) || 1;
        
        // Set filter values
        Object.entries(this.filters).forEach(([key, value]) => {
            const element = document.getElementById(`${key}Filter`) || document.getElementById('searchInput');
            if (element) element.value = value;
        });
        
        this.loadEmployees(initialPage);
    }

    showError(message) {
        document.getElementById('employeesTableContainer').innerHTML = `
            <div class="bg-red-900/20 border border-red-700 rounded-lg p-6 text-center">
                <i class="fas fa-exclamation-triangle text-red-400 text-2xl mb-3"></i>
                <p class="text-red-300 mb-4">${message}</p>
                <button onclick="employeeManager.loadEmployees(1)" 
                        class="bg-red-600 hover:bg-red-500 text-white px-4 py-2 rounded-lg transition-colors duration-200">
                    <i class="fas fa-redo mr-2"></i>Try Again
                </button>
            </div>
        `;
    }
}

// Modal Management
class ModalManager {
    static showImportModal() {
        document.getElementById('importModal').classList.remove('hidden');
        setTimeout(() => {
            document.getElementById('importModal').querySelector('div').classList.remove('scale-95');
        }, 10);
    }

    static hideImportModal() {
        const modal = document.getElementById('importModal');
        modal.querySelector('div').classList.add('scale-95');
        setTimeout(() => modal.classList.add('hidden'), 150);
    }

    static showEditTeamModal() {
        const selectedCount = document.querySelectorAll('.employee-checkbox:checked').length;
        
        if (selectedCount === 0) {
            alert('Please select at least one employee to edit.');
            return;
        }
        
        const container = document.getElementById('selectedEmployeesContainer');
        container.innerHTML = '';
        
        document.querySelectorAll('.employee-checkbox:checked').forEach(checkbox => {
            const hiddenInput = document.createElement('input');
            hiddenInput.type = 'hidden';
            hiddenInput.name = 'selected_employees[]';
            hiddenInput.value = checkbox.value;
            container.appendChild(hiddenInput);
        });
        
        document.getElementById('editTeamModal').classList.remove('hidden');
        setTimeout(() => {
            document.getElementById('editTeamModal').querySelector('div').classList.remove('scale-95');
        }, 10);
    }

    static hideEditTeamModal() {
        const modal = document.getElementById('editTeamModal');
        modal.querySelector('div').classList.add('scale-95');
        setTimeout(() => modal.classList.add('hidden'), 150);
    }
}

// Utility Functions
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

// Delete Modal Functions
function showDeleteModal(recordId, recordType = '') {
    const modal = document.createElement('div');
    modal.id = 'deleteModal';
    modal.className = 'fixed inset-0 bg-gray-900/50 backdrop-blur-sm flex items-center justify-center z-50 transition-opacity duration-200';
    modal.innerHTML = `
        <div class="bg-gray-800 rounded-lg border border-gray-700 shadow-xl w-full max-w-md transform transition-transform duration-200 scale-95">
            <div class="px-6 py-6">
                <h3 class="text-lg font-bold text-gray-100 mb-4">Confirm Deletion</h3>
                <p class="text-gray-300 mb-4">Are you sure you want to delete this record?</p>
                <form method="post" action="${window.location.pathname}?delete=${recordId}${recordType ? '&type=' + recordType : ''}" class="space-y-4">
                    <div>
                        <label for="delete_password" class="block text-sm font-medium text-gray-300 mb-1">To confirm please enter the KEY:</label>
                        <input type="password" name="delete_password" id="delete_password" 
                               class="w-full px-3 py-2 bg-gray-700 border border-gray-600 rounded-md text-gray-200 focus:border-red-500 focus:ring-1 focus:ring-red-500 transition-colors duration-200" 
                               required autocomplete="off">
                    </div>
                    <div class="flex justify-end space-x-3">
                        <button type="button" onclick="closeDeleteModal()" 
                                class="px-4 py-2 bg-gray-600 text-gray-100 rounded-md hover:bg-gray-500 transition-colors duration-200">
                            Cancel
                        </button>
                        <button type="submit" 
                                class="px-4 py-2 bg-red-600 text-white rounded-md hover:bg-red-500 transition-colors duration-200">
                            Delete
                        </button>
                    </div>
                </form>
            </div>
        </div>
    `;
    
    document.body.appendChild(modal);
    
    setTimeout(() => {
        modal.querySelector('div').classList.remove('scale-95');
    }, 10);
    
    modal.querySelector('input').focus();
}

function closeDeleteModal() {
    const modal = document.getElementById('deleteModal');
    if (modal) {
        modal.querySelector('div').classList.add('scale-95');
        setTimeout(() => modal.remove(), 150);
    }
}

// Global event listeners
document.addEventListener('click', function(e) {
    if (e.target === document.getElementById('deleteModal')) closeDeleteModal();
    if (e.target === document.getElementById('editTeamModal')) ModalManager.hideEditTeamModal();
    if (e.target === document.getElementById('importModal')) ModalManager.hideImportModal();
});

document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        if (document.getElementById('deleteModal')) closeDeleteModal();
        if (document.getElementById('editTeamModal')) ModalManager.hideEditTeamModal();
        if (document.getElementById('importModal')) ModalManager.hideImportModal();
    }
});

// Form submission loading states
document.addEventListener('DOMContentLoaded', function() {
    const forms = document.querySelectorAll('form');
    forms.forEach(form => {
        form.addEventListener('submit', function() {
            const submitBtn = this.querySelector('button[type="submit"]');
            if (submitBtn) {
                submitBtn.disabled = true;
                submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i> Processing...';
                submitBtn.classList.add('opacity-50', 'cursor-not-allowed');
            }
        });
    });
});

// Initialize employee manager
const employeeManager = new EmployeeManager();

// Global functions for HTML onclick attributes
function showImportModal() { ModalManager.showImportModal(); }
function hideImportModal() { ModalManager.hideImportModal(); }
function showEditTeamModal() { ModalManager.showEditTeamModal(); }
function hideEditTeamModal() { ModalManager.hideEditTeamModal(); }
function toggleEditTeamButton() { employeeManager.updateButtonStates(); }
function selectAllEmployees(checkbox) { employeeManager.selectAllEmployees(checkbox.checked); }
</script>

<?php renderFooter(); ?>