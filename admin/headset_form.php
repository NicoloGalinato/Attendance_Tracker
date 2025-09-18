<?php
require_once '../includes/config.php';
require_once '../includes/functions.php';

if (!isLoggedIn() || !isAdmin()) {
    redirect(BASE_URL);
}

// Function to get the Monday of the current week
function getWeekBeginningDate() {
    $today = new DateTime();
    $dayOfWeek = $today->format('w'); // 0 (Sunday) to 6 (Saturday)
    
    // Calculate days to subtract to get to Monday
    $daysToSubtract = ($dayOfWeek == 0) ? 6 : $dayOfWeek - 1;
    
    if ($daysToSubtract > 0) {
        $today->modify("-$daysToSubtract days");
    }
    
    return $today->format('Y-m-d');
}

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$action = $id ? 'edit' : 'create';
$record = null;

$currentDate = date('Y-m-d');
$weekBeginningDate = getWeekBeginningDate(); // Get Monday of the current week
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// Get current user's sub_name
$userStmt = $pdo->prepare("SELECT sub_name FROM users WHERE id = ?");
$userStmt->execute([$_SESSION['user_id']]);
$user = $userStmt->fetch();
$sub_name = $user['sub_name'];

if ($id) {
    try {
        $stmt = $pdo->prepare("SELECT * FROM headset_tracker WHERE id = ?");
        $stmt->execute([$id]);
        $record = $stmt->fetch();
    } catch (PDOException $e) {
        $_SESSION['error'] = "Error fetching record: " . $e->getMessage();
        redirect('inventory_tracker.php?tab=headset');
    }
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = [
        'week_beginning' => $weekBeginningDate, // Use the calculated Monday date
        'date_issued' => $currentDate,
        'employee_id' => strtoupper($_POST['employee_id']),
        'full_name' => strtoupper($_POST['full_name']),
        'department' => strtoupper($_POST['department']),
        'operation_manager' => strtoupper($_POST['operation_manager']),
        'brand_model_no' => strtoupper($_POST['brand_model_no']),
        'c_no' => strtoupper($_POST['c_no']),
        'yjack_serial_no' => strtoupper($_POST['yjack_serial_no']),
        'w_xtra_foam' => strtoupper($_POST['w_xtra_foam']),
        '_condition' => strtoupper($_POST['_condition']),
        'release_by' => $sub_name,
        'release_time' => $_POST['release_time'],
        'equipment_status' => $_POST['equipment_status'],
        'remarks' => strtoupper($_POST['remarks'])
    ];

    try {
        if ($action === 'create') {
            $sql = "INSERT INTO headset_tracker (week_beginning, date_issued, employee_id, full_name, department, operation_manager, brand_model_no, c_no, yjack_serial_no, w_xtra_foam, _condition, release_by, release_time, equipment_status, remarks) 
                    VALUES (:week_beginning, :date_issued, :employee_id, :full_name, :department, :operation_manager, :brand_model_no, :c_no, :yjack_serial_no, :w_xtra_foam, :_condition, :release_by, :release_time, :equipment_status, :remarks)";
        } else {
            $sql = "UPDATE headset_tracker SET week_beginning = :week_beginning, date_issued = :date_issued, employee_id = :employee_id, full_name = :full_name, department = :department, operation_manager = :operation_manager, brand_model_no = :brand_model_no, c_no = :c_no, yjack_serial_no = :yjack_serial_no, w_xtra_foam = :w_xtra_foam, _condition = :_condition, release_by = :release_by, release_time = :release_time, equipment_status = :equipment_status, remarks = :remarks WHERE id = :id";
            $data['id'] = $id;
        }

        $stmt = $pdo->prepare($sql);
        $stmt->execute($data);

        $_SESSION['success'] = "Record " . ($action === 'create' ? 'created' : 'updated') . " successfully!";
        redirect('inventory_tracker.php?tab=headset');
    } catch (PDOException $e) {
        $_SESSION['error'] = "Error saving record: " . $e->getMessage();
        redirect('headset_form.php?' . ($id ? "id=$id" : "action=create"));
    }
}

require_once '../components/layout.php';
renderHead('Headset Tracker Form');
renderNavbar();
renderSidebar('inventory');
?>

<div class="pt-2 min-h-screen">
    <main class="p-6">
        <div class="flex justify-between items-center mb-6">
            <h1 class="text-2xl font-bold"><?= $action === 'create' ? 'Add New' : 'Edit' ?> Headset Record</h1>
            <a href="inventory_tracker.php?tab=headset" class="bg-gray-600 hover:bg-gray-500 text-white px-4 py-2 rounded-lg flex items-center">
                <i class="fas fa-arrow-left mr-2"></i> Back to List
            </a>
        </div>

        <?php renderAlert(); ?>

        <form method="post" class="bg-gray-800 rounded-xl border border-gray-700 p-6">
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                <div class="relative">
                    <label for="employee_id" class="block text-sm font-medium text-gray-300 mb-1">Employee ID</label>
                    <input type="text" id="employee_id" name="employee_id" value="<?= $record ? htmlspecialchars($record['employee_id']) : '' ?>" required 
                           class="w-full px-4 py-3 bg-gray-700/50 border border-gray-600/50 rounded-lg text-gray-200" 
                           style="text-transform: uppercase;"
                           onchange="fetchEmployeeDetails(this.value)"
                           autocomplete="off">
                    <div id="employeeSearchResults" class="hidden absolute z-10 mt-1 w-full max-w-md bg-gray-800 border border-gray-700 rounded-lg shadow-lg"></div>
                </div>

                <div>
                    <label for="full_name" class="block text-sm font-medium text-gray-300 mb-1">Full Name</label>
                    <input type="text" id="full_name" name="full_name" value="<?= $record ? htmlspecialchars($record['full_name']) : '' ?>" required 
                           class="w-full px-4 py-3 bg-gray-700/50 border border-gray-600/50 rounded-lg text-gray-200" 
                           style="text-transform: uppercase;"
                           onchange="searchEmployees(this.value)">
                </div>

                <div>
                    <label for="department" class="block text-sm font-medium text-gray-300 mb-1">Department</label>
                    <input type="text" id="department" name="department" value="<?= $record ? htmlspecialchars($record['department']) : '' ?>" required 
                           class="w-full px-4 py-3 bg-gray-700/50 border border-gray-600/50 rounded-lg text-gray-200" 
                           style="text-transform: uppercase;" readonly>
                </div>
                <div>
                    <label for="operation_manager" class="block text-sm font-medium text-gray-300 mb-1">Operation Manager</label>
                    <input type="text" id="operation_manager" name="operation_manager" value="<?= $record ? htmlspecialchars($record['operation_manager']) : '' ?>" required 
                           class="w-full px-4 py-3 bg-gray-700/50 border border-gray-600/50 rounded-lg text-gray-200" 
                           style="text-transform: uppercase;" readonly>
                </div>
                
                <!-- C No Field with Auto-fill -->
                <div class="relative">
                    <label for="c_no" class="block text-sm font-medium text-gray-300 mb-1">C No</label>
                    <input type="text" id="c_no" name="c_no" value="<?= $record ? htmlspecialchars($record['c_no']) : '' ?>" 
                           class="w-full px-4 py-3 bg-gray-700/50 border border-gray-600/50 rounded-lg text-gray-200" 
                           style="text-transform: uppercase;"
                           onchange="fetchHeadsetDetails(this.value)"
                           autocomplete="off">
                    <div id="headsetSearchResults" class="hidden absolute z-10 mt-1 w-full max-w-md bg-gray-800 border border-gray-700 rounded-lg shadow-lg"></div>
                </div>

                <div>
                    <label for="brand_model_no" class="block text-sm font-medium text-gray-300 mb-1">Brand/Model No</label>
                    <input type="text" id="brand_model_no" name="brand_model_no" value="<?= $record ? htmlspecialchars($record['brand_model_no']) : '' ?>" required 
                           class="w-full px-4 py-3 bg-gray-700/50 border border-gray-600/50 rounded-lg text-gray-200" 
                           style="text-transform: uppercase;">
                </div>

                <div>
                    <label for="yjack_serial_no" class="block text-sm font-medium text-gray-300 mb-1">YJack Serial No</label>
                    <input type="text" id="yjack_serial_no" name="yjack_serial_no" value="<?= $record ? htmlspecialchars($record['yjack_serial_no']) : '' ?>" 
                           class="w-full px-4 py-3 bg-gray-700/50 border border-gray-600/50 rounded-lg text-gray-200" 
                           style="text-transform: uppercase;">
                </div>

                <div>
                    <label for="w_xtra_foam" class="block text-sm font-medium text-gray-300 mb-1">With Extra Foam</label>
                    <select id="w_xtra_foam" name="w_xtra_foam" required 
                            class="w-full px-4 py-3 bg-gray-700/50 border border-gray-600/50 rounded-lg text-gray-200" 
                            style="text-transform: uppercase;">
                        <option value="YES" <?= $record && $record['w_xtra_foam'] === 'YES' ? 'selected' : '' ?>>YES</option>
                        <option value="NO" <?= $record && $record['w_xtra_foam'] === 'NO' ? 'selected' : '' ?>>NO</option>
                    </select>
                </div>

                <div>
                    <label for="_condition" class="block text-sm font-medium text-gray-300 mb-1">Condition</label>
                    <select id="_condition" name="_condition" required 
                            class="w-full px-4 py-3 bg-gray-700/50 border border-gray-600/50 rounded-lg text-gray-200" 
                            style="text-transform: uppercase;">
                        <option value="GOOD" <?= $record && $record['_condition'] === 'GOOD' ? 'selected' : '' ?>>GOOD</option>
                        <option value="DEFECTIVE" <?= $record && $record['_condition'] === 'DEFECTIVE' ? 'selected' : '' ?>>DEFECTIVE</option>
                    </select>
                </div>

                <div>
                    <label for="release_time" class="block text-sm font-medium text-gray-300 mb-1">Release Time</label>
                    <input type="time" id="release_time" name="release_time" value="<?= $record ? htmlspecialchars($record['release_time']) : date('H:i') ?>" required 
                           class="w-full px-4 py-3 bg-gray-700/50 border border-gray-600/50 rounded-lg text-gray-200" 
                           style="text-transform: uppercase;">
                </div>

                <div>
                    <label for="return_date" class="block text-sm font-medium text-gray-300 mb-1">Return Date</label>
                    <input type="date" id="return_date" name="return_date" value="<?= $record ? htmlspecialchars($record['return_date']) : '' ?>" 
                           class="w-full px-4 py-3 bg-gray-700/50 border border-gray-600/50 rounded-lg text-gray-200" 
                           style="text-transform: uppercase;">
                </div>

                <div>
                    <label for="return_time" class="block text-sm font-medium text-gray-300 mb-1">Return Time</label>
                    <input type="text" id="return_time" name="return_time" value="<?= $record ? htmlspecialchars($record['return_time']) : '' ?>" 
                           class="w-full px-4 py-3 bg-gray-700/50 border border-gray-600/50 rounded-lg text-gray-200" 
                           style="text-transform: uppercase;">
                </div>

                <div>
                    <label for="equipment_status" class="block text-sm font-medium text-gray-300 mb-1">Equipment Status</label>
                    <select id="equipment_status" name="equipment_status" required 
                            class="w-full px-4 py-3 bg-gray-700/50 border border-gray-600/50 rounded-lg text-gray-200" 
                            style="text-transform: uppercase;">
                        <option value="WORKING ALL ITEMS" <?= $record && $record['equipment_status'] === 'WORKING ALL ITEMS' ? 'selected' : '' ?>>WORKING ALL ITEMS</option>
                        <option value="NOT WORKING - HEADSET" <?= $record && $record['equipment_status'] === 'NOT WORKING - HEADSET' ? 'selected' : '' ?>>NOT WORKING - HEADSET</option>
                        <option value="NOT WORKING - YJACK" <?= $record && $record['equipment_status'] === 'NOT WORKING - YJACK' ? 'selected' : '' ?>>NOT WORKING - YJACK</option>
                        <option value="WITH ISSUE" <?= $record && $record['equipment_status'] === 'WITH ISSUE' ? 'selected' : '' ?>>WITH ISSUE</option>
                    </select>
                </div>

                <div class="md:col-span-2">
                    <label for="remarks" class="block text-sm font-medium text-gray-300 mb-1">Remarks</label>
                    <textarea id="remarks" name="remarks" 
                              class="w-full px-4 py-3 bg-gray-700/50 border border-gray-600/50 rounded-lg text-gray-200" 
                              style="text-transform: uppercase;"><?= $record ? htmlspecialchars($record['remarks']) : '' ?></textarea>
                </div>
            </div>

            <div class="mt-8 flex justify-end">
                <button type="submit" class="bg-primary-600 hover:bg-primary-700 text-white px-6 py-2 rounded-md">
                    <?= $action === 'create' ? 'Create Record' : 'Update Record' ?>
                </button>
            </div>
        </form>
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

// Search headsets by C No, YJack Serial No, or Headset Serial No
function searchHeadsets(query) {
    if (query.length < 1) {
        document.getElementById('headsetSearchResults').classList.add('hidden');
        return;
    }

    fetch('../api/search_headsets.php?query=' + encodeURIComponent(query))
        .then(response => response.json())
        .then(data => {
            const resultsContainer = document.getElementById('headsetSearchResults');
            resultsContainer.innerHTML = '';
            
            if (data.success && data.headsets.length > 0) {
                data.headsets.forEach(headset => {
                    const item = document.createElement('div');
                    item.className = 'px-4 py-2 hover:bg-gray-700 cursor-pointer border-b border-gray-700';
                    item.innerHTML = `
                        <div class="font-medium text-gray-200">C No: ${headset.c_no || 'N/A'}</div>
                        <div class="text-sm text-gray-400">YJack: ${headset.yjack_serial_no || 'N/A'} | Headset: ${headset.headset_serial_no || 'N/A'}</div>
                        <div class="text-xs text-gray-500">Status: ${headset.status || 'N/A'}</div>
                    `;
                    item.addEventListener('click', () => {
                        document.getElementById('c_no').value = headset.c_no || '';
                        fetchHeadsetDetails(headset.c_no);
                        resultsContainer.classList.add('hidden');
                    });
                    resultsContainer.appendChild(item);
                });
                resultsContainer.classList.remove('hidden');
            } else {
                const item = document.createElement('div');
                item.className = 'px-4 py-2 text-gray-400';
                item.textContent = 'No headsets found';
                resultsContainer.appendChild(item);
                resultsContainer.classList.remove('hidden');
            }
        })
        .catch(error => {
            console.error('Error:', error);
        });
}

// Add event listeners for search
document.getElementById('employee_id').addEventListener('input', function() {
    searchEmployees(this.value);
});

document.getElementById('full_name').addEventListener('input', function() {
    searchEmployees(this.value);
});

document.getElementById('c_no').addEventListener('input', function() {
    searchHeadsets(this.value);
});

// Hide results when clicking outside
document.addEventListener('click', function(e) {
    if (!document.getElementById('employee_id').contains(e.target) && 
        !document.getElementById('employeeSearchResults').contains(e.target)) {
        document.getElementById('employeeSearchResults').classList.add('hidden');
    }
    
    if (!document.getElementById('c_no').contains(e.target) && 
        !document.getElementById('headsetSearchResults').contains(e.target)) {
        document.getElementById('headsetSearchResults').classList.add('hidden');
    }
});

// Auto-fill employee details if editing
document.addEventListener('DOMContentLoaded', function() {
    <?php if ($record): ?>
        fetchEmployeeDetails('<?= $record['employee_id'] ?>');
        fetchHeadsetDetails('<?= $record['c_no'] ?>');
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
                document.getElementById('operation_manager').value = data.employee.operation_manager;
            }
        })
        .catch(error => {
            console.error('Error:', error);
        });
}

// Fetch headset details based on C No
function fetchHeadsetDetails(cNo) {
    if (!cNo) return;
    
    fetch('../api/get_headset.php?c_no=' + encodeURIComponent(cNo))
        .then(response => response.json())
        .then(data => {
            if (data.success && data.headset) {
                const headset = data.headset;
                
                // Populate the form fields with headset data
                document.getElementById('brand_model_no').value = headset.brand || '';
                document.getElementById('yjack_serial_no').value = headset.yjack_serial_no || '';
                
                // Set condition based on headset status
                if (headset.status === 'DEFECTIVE' || headset.status === 'MAINTENANCE') {
                    document.getElementById('_condition').value = 'DEFECTIVE';
                } else {
                    document.getElementById('_condition').value = 'GOOD';
                }
                
                // Set equipment status based on headset status
                if (headset.status === 'DEFECTIVE') {
                    document.getElementById('equipment_status').value = 'NOT WORKING - HEADSET';
                } else if (headset.status === 'AVAILABLE' || headset.status === 'IN_USE') {
                    document.getElementById('equipment_status').value = 'WORKING ALL ITEMS';
                }
                
                // Set remarks if headset has remarks
                if (headset.remarks) {
                    document.getElementById('remarks').value = headset.remarks;
                }
                
                // Set extra foam if available in headset data
                if (headset.w_xtra_foam) {
                    document.getElementById('w_xtra_foam').value = headset.w_xtra_foam;
                }
            }
        })
        .catch(error => {
            console.error('Error:', error);
        });
}

// Add event listeners for search
document.addEventListener('DOMContentLoaded', function() {
    const cNoInput = document.getElementById('c_no');
    if (cNoInput) {
        cNoInput.addEventListener('input', function() {
            searchHeadsets(this.value);
        });
    }

    // Auto-fill headset details if editing
    <?php if ($record && !empty($record['c_no'])): ?>
        fetchHeadsetDetails('<?= $record['c_no'] ?>');
    <?php endif; ?>

    // Hide results when clicking outside
    document.addEventListener('click', function(e) {
        const headsetResults = document.getElementById('headsetSearchResults');
        const cNoInput = document.getElementById('c_no');
        
        if (headsetResults && cNoInput && 
            !cNoInput.contains(e.target) && 
            !headsetResults.contains(e.target)) {
            headsetResults.classList.add('hidden');
        }
    });
});
</script>

<?php renderFooter(); ?>