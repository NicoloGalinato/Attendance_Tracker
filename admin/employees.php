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
                
                $stmt = $pdo->prepare("INSERT INTO employees 
                    (employee_id, full_name, department, supervisor, operation_manager, email, is_active) 
                    VALUES 
                    (:employee_id, :full_name, :department, :supervisor, :operation_manager, :email, :is_active)
                    ON DUPLICATE KEY UPDATE
                    full_name = VALUES(full_name),
                    department = VALUES(department),
                    supervisor = VALUES(supervisor),
                    operation_manager = VALUES(operation_manager),
                    email = VALUES(email),
                    is_active = VALUES(is_active)");
                
                $imported = 0;
                $skipped = 0;
                
                while (($data = fgetcsv($handle)) !== FALSE) {
                    if (count($data) >= 6) {
                        $employeeData = [
                            ':employee_id' => isset($data[0]) ? strtoupper(trim($data[0])) : '',
                            ':full_name' => isset($data[1]) ? strtoupper(trim($data[1])) : '',
                            ':department' => isset($data[2]) ? strtoupper(trim($data[2])) : '',
                            ':supervisor' => isset($data[3]) ? strtoupper(trim($data[3])) : '',
                            ':operation_manager' => isset($data[4]) ? strtoupper(trim($data[4])) : '',
                            ':email' => isset($data[5]) ? strtolower(trim($data[5])) : '',
                            ':is_active' => isset($data[6]) ? (int)$data[6] : 1
                        ];
                        
                        try {
                            $stmt->execute($employeeData);
                            $imported++;
                        } catch (PDOException $e) {
                            $skipped++;
                            continue;
                        }
                    }
                }
                                
                fclose($handle);
                $pdo->commit();
                
                $_SESSION['success'] = "Import completed! $imported records imported successfully" . ($skipped > 0 ? ", $skipped records skipped." : ".");
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
?>

<div class="md:ml-64 pt-2 min-h-screen">
    <main class="p-6">
        <div class="flex justify-between items-center mb-6">
            <h1 class="text-2xl font-bold">Manage Agents</h1>
            <div class="flex space-x-2">
                <button type="button" onclick="document.getElementById('importModal').classList.remove('hidden')" 
                        class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg flex items-center">
                    <i class="fas fa-file-import mr-2"></i> Import
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
        
        <div class="mb-6">
            <div class="relative flex-grow">
                <input type="text" id="searchInput" 
                       class="w-full pl-10 pr-4 py-2 bg-gray-700/50 border border-gray-600/50 rounded-lg text-gray-200" 
                       placeholder="Search by CXI number or name...">
                <div class="absolute left-3 top-2.5 text-gray-400">
                    <i class="fas fa-search"></i>
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

document.addEventListener('DOMContentLoaded', function() {
    const searchInput = document.getElementById('searchInput');
    let searchTimeout;

    function loadEmployees(search = '', page = 1) {
        const formData = new FormData();
        formData.append('search', search);
        formData.append('page', page);

        fetch('partials/employees_table.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.text())
        .then(data => {
            document.getElementById('employeesTableContainer').innerHTML = data;
            
            document.querySelectorAll('.pagination-link').forEach(link => {
                link.addEventListener('click', function(e) {
                    e.preventDefault();
                    const page = this.getAttribute('data-page');
                    loadEmployees(searchInput.value, page);
                    history.pushState(null, '', `?page=${page}${searchInput.value ? '&search=' + encodeURIComponent(searchInput.value) : ''}`);
                });
            });
        });
    }

    searchInput.addEventListener('input', function() {
        clearTimeout(searchTimeout);
        searchTimeout = setTimeout(() => {
            loadEmployees(this.value);
            history.pushState(null, '', `?${this.value ? 'search=' + encodeURIComponent(this.value) : ''}`);
        }, 300);
    });

    window.addEventListener('popstate', function() {
        const urlParams = new URLSearchParams(window.location.search);
        const searchParam = urlParams.get('search') || '';
        const pageParam = urlParams.get('page') || 1;
        
        searchInput.value = searchParam;
        loadEmployees(searchParam, pageParam);
    });

    const urlParams = new URLSearchParams(window.location.search);
    const initialSearch = urlParams.get('search') || '';
    const initialPage = urlParams.get('page') || 1;
    
    if (initialSearch) searchInput.value = initialSearch;
    loadEmployees(initialSearch, initialPage);
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
});

document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape' && document.getElementById('deleteModal')) {
        closeDeleteModal();
    }
});
</script>

<?php renderFooter(); ?>