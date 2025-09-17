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
        $stmt = $pdo->prepare("SELECT * FROM request_peripherals WHERE id = ?");
        $stmt->execute([$id]);
        $record = $stmt->fetch();
    } catch (PDOException $e) {
        $_SESSION['error'] = "Error fetching record: " . $e->getMessage();
        redirect('inventory_tracker.php?tab=peripherals');
    }
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = [
        'request_date' => $_POST['request_date'],
        'stn_no' => $_POST['stn_no'],
        'peripheral_type' => $_POST['peripheral_type'],
        'serial_no' => $_POST['serial_no'],
        'issue' => $_POST['issue'],
        'request_form' => $_POST['request_form'],
        'remarks' => $_POST['remarks'],
        'resolved' => $_POST['resolved'],
        'date_resolved' => $_POST['date_resolved'],
        'slt' => $_POST['slt']
    ];

    try {
        if ($action === 'create') {
            $sql = "INSERT INTO request_peripherals (request_date, stn_no, peripheral_type, serial_no, issue, request_form, remarks, resolved, date_resolved, slt) 
                    VALUES (:request_date, :stn_no, :peripheral_type, :serial_no, :issue, :request_form, :remarks, :resolved, :date_resolved, :slt)";
        } else {
            $sql = "UPDATE request_peripherals SET request_date = :request_date, stn_no = :stn_no, peripheral_type = :peripheral_type, serial_no = :serial_no, issue = :issue, request_form = :request_form, remarks = :remarks, resolved = :resolved, date_resolved = :date_resolved, slt = :slt WHERE id = :id";
            $data['id'] = $id;
        }

        $stmt = $pdo->prepare($sql);
        $stmt->execute($data);

        $_SESSION['success'] = "Record " . ($action === 'create' ? 'created' : 'updated') . " successfully!";
        redirect('inventory_tracker.php?tab=peripherals');
    } catch (PDOException $e) {
        $_SESSION['error'] = "Error saving record: " . $e->getMessage();
        redirect('peripherals_form.php?' . ($id ? "id=$id" : "action=create"));
    }
}

require_once '../components/layout.php';
renderHead('Peripherals Request Form');
renderNavbar();
renderSidebar('inventory');
?>

<div class="pt-2 min-h-screen">
    <main class="p-6">
        <div class="flex justify-between items-center mb-6">
            <h1 class="text-2xl font-bold"><?= $action === 'create' ? 'Add New' : 'Edit' ?> Peripheral Request</h1>
            <a href="inventory_tracker.php?tab=peripherals" class="bg-gray-600 hover:bg-gray-500 text-white px-4 py-2 rounded-lg flex items-center">
                <i class="fas fa-arrow-left mr-2"></i> Back to List
            </a>
        </div>

        <?php renderAlert(); ?>

        <form method="post" class="bg-gray-800 rounded-xl border border-gray-700 p-6 shadow">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <label for="request_date" class="block text-sm font-medium text-gray-300 mb-1">Request Date</label>
                    <input type="date" id="request_date" name="request_date" value="<?= $record ? htmlspecialchars($record['request_date']) : date('Y-m-d') ?>" required class="w-full px-4 py-3 bg-gray-700/50 border border-gray-600/50 rounded-lg text-gray-200">
                </div>

                <div>
                    <label for="stn_no" class="block text-sm font-medium text-gray-300 mb-1">Station No</label>
                    <input type="text" id="stn_no" name="stn_no" value="<?= $record ? htmlspecialchars($record['stn_no']) : '' ?>" required class="w-full px-4 py-3 bg-gray-700/50 border border-gray-600/50 rounded-lg text-gray-200">
                </div>

                <div>
                    <label for="peripheral_type" class="block text-sm font-medium text-gray-300 mb-1">Peripheral Type</label>
                    <select id="peripheral_type" name="peripheral_type" required class="w-full px-4 py-3 bg-gray-700/50 border border-gray-600/50 rounded-lg text-gray-200">
                        <option value="CPU" <?= $record && $record['peripheral_type'] === 'CPU' ? 'selected' : '' ?>>CPU</option>
                        <option value="HEADSET" <?= $record && $record['peripheral_type'] === 'HEADSET' ? 'selected' : '' ?>>HEADSET</option>
                        <option value="KEYBOARD" <?= $record && $record['peripheral_type'] === 'KEYBOARD' ? 'selected' : '' ?>>KEYBOARD</option>
                        <option value="MONITOR" <?= $record && $record['peripheral_type'] === 'MONITOR' ? 'selected' : '' ?>>MONITOR</option>
                        <option value="MOUSE" <?= $record && $record['peripheral_type'] === 'MOUSE' ? 'selected' : '' ?>>MOUSE</option>
                    </select>
                </div>

                <div>
                    <label for="serial_no" class="block text-sm font-medium text-gray-300 mb-1">Serial No</label>
                    <input type="text" id="serial_no" name="serial_no" value="<?= $record ? htmlspecialchars($record['serial_no']) : '' ?>" class="w-full px-4 py-3 bg-gray-700/50 border border-gray-600/50 rounded-lg text-gray-200">
                </div>

                <div>
                    <label for="issue" class="block text-sm font-medium text-gray-300 mb-1">Issue *</label>
                    <textarea id="issue" name="issue" rows="3" required class="w-full px-4 py-3 bg-gray-700/50 border border-gray-600/50 rounded-lg text-gray-200"><?= $record ? htmlspecialchars($record['issue']) : '' ?></textarea>
                </div>

                <div>
                    <label for="request_form" class="block text-sm font-medium text-gray-300 mb-1">Request Form *</label>
                    <select id="request_form" name="request_form" required class="w-full px-4 py-3 bg-gray-700/50 border border-gray-600/50 rounded-lg text-gray-200">
                        <option value="NO" <?= $record && $record['request_form'] === 'NO' ? 'selected' : '' ?>>NO</option>
                        <option value="YES" <?= $record && $record['request_form'] === 'YES' ? 'selected' : '' ?>>YES</option>
                    </select>
                </div>

                <div>
                    <label for="resolved" class="block text-sm font-medium text-gray-300 mb-1">Resolved *</label>
                    <select id="resolved" name="resolved" required class="w-full px-4 py-3 bg-gray-700/50 border border-gray-600/50 rounded-lg text-gray-200">
                        <option value="NO" <?= $record && $record['resolved'] === 'NO' ? 'selected' : '' ?>>NO</option>
                        <option value="YES" <?= $record && $record['resolved'] === 'YES' ? 'selected' : '' ?>>YES</option>
                    </select>
                </div>

                <div>
                    <label for="date_of_incident" class="block text-sm font-medium text-gray-300 mb-1">Date of Incident</label>
                    <input type="date" id="date_of_incident" name="date_of_incident" style="text-transform: uppercase;"
                        class="w-full px-4 py-3 bg-gray-700/50 border border-gray-600/50 rounded-lg text-gray-200" 
                        value="<?= $record ? htmlspecialchars($record['date_of_incident']) : '' ?>" required>
                    </div>
                <div>
                    <label for="remarks" class="block text-sm font-medium text-gray-300 mb-1">Remarks</label>
                    <textarea id="remarks" name="remarks" rows="3" class="w-full px-4 py-3 bg-gray-700/50 border border-gray-600/50 rounded-lg text-gray-200"><?= $record ? htmlspecialchars($record['remarks']) : '' ?></textarea>
                </div>
            </div>

            <div class="mt-8 flex justify-end">
                <button type="submit" class="bg-primary-600 hover:bg-primary-700 text-white px-6 py-2 rounded-md">
                    <?= $action === 'create' ? 'Create Request' : 'Update Request' ?>
                </button>
            </div>
        </form>
    </main>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const resolvedSelect = document.getElementById('resolved');
    const dateResolvedInput = document.getElementById('date_resolved');
    
    function toggleDateResolved() {
        if (resolvedSelect.value === 'YES') {
            dateResolvedInput.disabled = false;
            if (!dateResolvedInput.value) {
                dateResolvedInput.value = '<?= date('Y-m-d') ?>';
            }
        } else {
            dateResolvedInput.disabled = true;
            dateResolvedInput.value = '';
        }
    }
    
    resolvedSelect.addEventListener('change', toggleDateResolved);
    toggleDateResolved(); // Initial call
});
</script>

<?php renderFooter(); ?>