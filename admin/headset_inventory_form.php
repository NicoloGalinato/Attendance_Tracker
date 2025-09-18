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
        $stmt = $pdo->prepare("SELECT * FROM headset_inventory WHERE id = ?");
        $stmt->execute([$id]);
        $record = $stmt->fetch();
    } catch (PDOException $e) {
        $_SESSION['error'] = "Error fetching record: " . $e->getMessage();
        redirect('inventory_tracker.php?tab=headset_inventory');
    }
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = [
        'c_no' => strtoupper($_POST['c_no']),
        'brand' => strtoupper($_POST['brand']),
        'yjack_serial_no' => strtoupper($_POST['yjack_serial_no']),
        'headset_serial_no' => strtoupper($_POST['headset_serial_no']),
        'status' => strtoupper($_POST['status']),
        'remarks' => strtoupper($_POST['remarks'])
    ];

    try {
        if ($action === 'create') {
            $sql = "INSERT INTO headset_inventory (c_no, brand, yjack_serial_no, headset_serial_no, status, remarks) 
                    VALUES (:c_no, :brand, :yjack_serial_no, :headset_serial_no, :status, :remarks)";
        } else {
            $sql = "UPDATE headset_inventory SET c_no = :c_no, brand = :brand, yjack_serial_no = :yjack_serial_no, headset_serial_no = :headset_serial_no, status = :status, remarks = :remarks WHERE id = :id";
            $data['id'] = $id;
        }

        $stmt = $pdo->prepare($sql);
        $stmt->execute($data);

        $_SESSION['success'] = "Record " . ($action === 'create' ? 'created' : 'updated') . " successfully!";
        redirect('inventory_tracker.php?tab=headset_inventory');
    } catch (PDOException $e) {
        $_SESSION['error'] = "Error saving record: " . $e->getMessage();
        redirect('headset_inventory_form.php?' . ($id ? "id=$id" : "action=create"));
    }
}

require_once '../components/layout.php';
renderHead('Headset Inventory Form');
renderNavbar();
renderSidebar('inventory');
?>

<div class="pt-2 min-h-screen">
    <main class="p-6">
        <div class="flex justify-between items-center mb-6">
            <h1 class="text-2xl font-bold"><?= $action === 'create' ? 'Add New' : 'Edit' ?> Headset Inventory</h1>
            <a href="inventory_tracker.php?tab=headset_inventory" class="bg-gray-600 hover:bg-gray-500 text-white px-4 py-2 rounded-lg flex items-center">
                <i class="fas fa-arrow-left mr-2"></i> Back to List
            </a>
        </div>

        <?php renderAlert(); ?>

        <form method="post" class="bg-gray-800 rounded-xl border border-gray-700 p-6">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <!-- First Row -->
                <div>
                    <label for="c_no" class="block text-sm font-medium text-gray-300 mb-1">C No</label>
                    <input type="text" id="c_no" name="c_no" value="<?= $record ? htmlspecialchars($record['c_no']) : '' ?>" required 
                        class="w-full px-4 py-3 bg-gray-700/50 border border-gray-600/50 rounded-lg text-gray-200" 
                        style="text-transform: uppercase;">
                </div>
                <div>
                    <label for="brand" class="block text-sm font-medium text-gray-300 mb-1">Brand/Model No</label>
                    <input type="text" id="brand" name="brand" value="<?= $record ? htmlspecialchars($record['brand']) : '' ?>" required 
                        class="w-full px-4 py-3 bg-gray-700/50 border border-gray-600/50 rounded-lg text-gray-200" 
                        style="text-transform: uppercase;">
                </div>
                <div>
                    <label for="yjack_serial_no" class="block text-sm font-medium text-gray-300 mb-1">YJack Serial No</label>
                    <input type="text" id="yjack_serial_no" name="yjack_serial_no" value="<?= $record ? htmlspecialchars($record['yjack_serial_no']) : '' ?>" required 
                        class="w-full px-4 py-3 bg-gray-700/50 border border-gray-600/50 rounded-lg text-gray-200" 
                        style="text-transform: uppercase;">
                </div>
                <div>
                    <label for="status" class="block text-sm font-medium text-gray-300 mb-1">Status</label>
                    <select id="status" name="status" required 
                            class="w-full px-4 py-3 bg-gray-700/50 border border-gray-600/50 rounded-lg text-gray-200" 
                            style="text-transform: uppercase;">
                        <option value="AVAILABLE" <?= $record && $record['status'] === 'AVAILABLE' ? 'selected' : '' ?>>AVAILABLE</option>
                        <option value="IN_USE" <?= $record && $record['status'] === 'IN_USE' ? 'selected' : '' ?>>IN USE</option>
                        <option value="DEFECTIVE" <?= $record && $record['status'] === 'DEFECTIVE' ? 'selected' : '' ?>>DEFECTIVE</option>
                        <option value="MAINTENANCE" <?= $record && $record['status'] === 'MAINTENANCE' ? 'selected' : '' ?>>MAINTENANCE</option>
                    </select>
                </div>

                <!-- Second Row -->
                <div>
                    <label for="headset_serial_no" class="block text-sm font-medium text-gray-300 mb-1">Headset Serial No</label>
                    <textarea id="headset_serial_no" name="headset_serial_no" 
                            class="w-full px-4 py-3 bg-gray-700/50 border border-gray-600/50 rounded-lg text-gray-200" 
                            style="text-transform: uppercase;"><?= $record ? htmlspecialchars($record['headset_serial_no']) : '' ?></textarea>
                </div>
                <div>
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

<?php renderFooter(); ?>