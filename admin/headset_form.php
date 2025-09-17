<?php
require_once '../includes/config.php';
require_once '../includes/functions.php';

if (!isLoggedIn() || !isAdmin()) {
    redirect(BASE_URL);
}

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$action = $id ? 'edit' : 'create';
$record = null;

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
        'date_issued' => $_POST['date_issued'],
        'employee_id' => $_POST['employee_id'],
        'full_name' => $_POST['full_name'],
        'department_operation_manager' => $_POST['department_operation_manager'],
        'brand_model_no' => $_POST['brand_model_no'],
        'c_no' => $_POST['c_no'],
        'yjack_serial_no' => $_POST['yjack_serial_no'],
        'w_xtra_foam' => $_POST['w_xtra_foam'],
        'condition' => $_POST['condition'],
        'release_by' => $_POST['release_by'],
        'release_time' => $_POST['release_time'],
        'return_date' => $_POST['return_date'],
        'return_time' => $_POST['return_time'],
        'equipment_status' => $_POST['equipment_status'],
        'remarks' => $_POST['remarks']
    ];

    try {
        if ($action === 'create') {
            $sql = "INSERT INTO headset_tracker (date_issued, employee_id, full_name, department_operation_manager, brand_model_no, c_no, yjack_serial_no, w_xtra_foam, condition, release_by, release_time, return_date, return_time, equipment_status, remarks) 
                    VALUES (:date_issued, :employee_id, :full_name, :department_operation_manager, :brand_model_no, :c_no, :yjack_serial_no, :w_xtra_foam, :condition, :release_by, :release_time, :return_date, :return_time, :equipment_status, :remarks)";
        } else {
            $sql = "UPDATE headset_tracker SET date_issued = :date_issued, employee_id = :employee_id, full_name = :full_name, department_operation_manager = :department_operation_manager, brand_model_no = :brand_model_no, c_no = :c_no, yjack_serial_no = :yjack_serial_no, w_xtra_foam = :w_xtra_foam, condition = :condition, release_by = :release_by, release_time = :release_time, return_date = :return_date, return_time = :return_time, equipment_status = :equipment_status, remarks = :remarks WHERE id = :id";
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
                <div>
                    <label for="employee_id" class="block text-sm font-medium text-gray-300 mb-1">Employee ID</label>
                    <input type="text" id="employee_id" name="employee_id" value="<?= $record ? htmlspecialchars($record['employee_id']) : '' ?>" required class="w-full px-4 py-3 bg-gray-700/50 border border-gray-600/50 rounded-lg text-gray-200">
                </div>

                <div>
                    <label for="full_name" class="block text-sm font-medium text-gray-300 mb-1">Full Name</label>
                    <input type="text" id="full_name" name="full_name" value="<?= $record ? htmlspecialchars($record['full_name']) : '' ?>" required class="w-full px-4 py-3 bg-gray-700/50 border border-gray-600/50 rounded-lg text-gray-200">
                </div>

                <div>
                    <label for="department_operation_manager" class="block text-sm font-medium text-gray-300 mb-1">Department</label>
                    <input type="text" id="department_operation_manager" name="department_operation_manager" value="<?= $record ? htmlspecialchars($record['department_operation_manager']) : '' ?>" required class="w-full px-4 py-3 bg-gray-700/50 border border-gray-600/50 rounded-lg text-gray-200">
                </div>
                <div>
                    <label for="operation_manager" class="block text-sm font-medium text-gray-300 mb-1">Operation Manager</label>
                    <input type="text" id="operation_manager" name="operation_manager" value="<?= $record ? htmlspecialchars($record['operation_manager']) : '' ?>" required class="w-full px-4 py-3 bg-gray-700/50 border border-gray-600/50 rounded-lg text-gray-200">
                </div>
                <div>
                    <label for="c_no" class="block text-sm font-medium text-gray-300 mb-1">C No</label>
                    <input type="text" id="c_no" name="c_no" value="<?= $record ? htmlspecialchars($record['c_no']) : '' ?>" class="w-full px-4 py-3 bg-gray-700/50 border border-gray-600/50 rounded-lg text-gray-200">
                </div>

                <div>
                    <label for="brand_model_no" class="block text-sm font-medium text-gray-300 mb-1">Brand/Model No</label>
                    <input type="text" id="brand_model_no" name="brand_model_no" value="<?= $record ? htmlspecialchars($record['brand_model_no']) : '' ?>" required class="w-full px-4 py-3 bg-gray-700/50 border border-gray-600/50 rounded-lg text-gray-200">
                </div>

                <div>
                    <label for="yjack_serial_no" class="block text-sm font-medium text-gray-300 mb-1">YJack Serial No</label>
                    <input type="text" id="yjack_serial_no" name="yjack_serial_no" value="<?= $record ? htmlspecialchars($record['yjack_serial_no']) : '' ?>" class="w-full px-4 py-3 bg-gray-700/50 border border-gray-600/50 rounded-lg text-gray-200">
                </div>

                <div>
                    <label for="w_xtra_foam" class="block text-sm font-medium text-gray-300 mb-1">With Extra Foam</label>
                    <select id="w_xtra_foam" name="w_xtra_foam" required class="w-full px-4 py-3 bg-gray-700/50 border border-gray-600/50 rounded-lg text-gray-200">
                        <option value="YES" <?= $record && $record['w_xtra_foam'] === 'YES' ? 'selected' : '' ?>>YES</option>
                        <option value="NO" <?= $record && $record['w_xtra_foam'] === 'NO' ? 'selected' : '' ?>>NO</option>
                    </select>
                </div>

                <div>
                    <label for="condition" class="block text-sm font-medium text-gray-300 mb-1">Condition</label>
                    <select id="condition" name="condition" required class="w-full px-4 py-3 bg-gray-700/50 border border-gray-600/50 rounded-lg text-gray-200">
                        <option value="GOOD" <?= $record && $record['condition'] === 'GOOD' ? 'selected' : '' ?>>GOOD</option>
                        <option value="DEFECTIVE" <?= $record && $record['condition'] === 'DEFECTIVE' ? 'selected' : '' ?>>DEFECTIVE</option>
                    </select>
                </div>

                <div>
                    <label for="release_time" class="block text-sm font-medium text-gray-300 mb-1">Release Time</label>
                    <input type="time" id="release_time" name="release_time" value="<?= $record ? htmlspecialchars($record['release_time']) : date('H:i') ?>" required class="w-full px-4 py-3 bg-gray-700/50 border border-gray-600/50 rounded-lg text-gray-200">
                </div>

                <div>
                    <label for="return_date" class="block text-sm font-medium text-gray-300 mb-1">Return Date</label>
                    <input type="date" id="return_date" name="return_date" value="<?= $record ? htmlspecialchars($record['return_date']) : '' ?>" class="w-full px-4 py-3 bg-gray-700/50 border border-gray-600/50 rounded-lg text-gray-200">
                </div>

                <div>
                    <label for="return_time" class="block text-sm font-medium text-gray-300 mb-1">Return Time</label>
                    <input type="text" id="return_time" name="return_time" value="<?= $record ? htmlspecialchars($record['return_time']) : '' ?>" class="w-full px-4 py-3 bg-gray-700/50 border border-gray-600/50 rounded-lg text-gray-200">
                </div>

                <div>
                    <label for="equipment_status" class="block text-sm font-medium text-gray-300 mb-1">Equipment Status</label>
                    <select id="equipment_status" name="equipment_status" required class="w-full px-4 py-3 bg-gray-700/50 border border-gray-600/50 rounded-lg text-gray-200">
                        <option value="ISSUED" <?= $record && $record['equipment_status'] === 'ISSUED' ? 'selected' : '' ?>>ISSUED</option>
                        <option value="RETURNED" <?= $record && $record['equipment_status'] === 'RETURNED' ? 'selected' : '' ?>>RETURNED</option>
                        <option value="DAMAGED" <?= $record && $record['equipment_status'] === 'DAMAGED' ? 'selected' : '' ?>>DAMAGED</option>
                        <option value="LOST" <?= $record && $record['equipment_status'] === 'LOST' ? 'selected' : '' ?>>LOST</option>
                    </select>
                </div>

                <div class="md:col-span-2">
                    <label for="remarks" class="block text-sm font-medium text-gray-300 mb-1">Remarks</label>
                    <textarea id="remarks" name="remarks" class="w-full  bg-gray-700/50 border border-gray-600/50 rounded-lg text-gray-200"><?= $record ? htmlspecialchars($record['remarks']) : '' ?></textarea>
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

<?php renderFooter(); ?>