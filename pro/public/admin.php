<?php
require_once '../config/database.php';
require_once '../includes/session.php';

// Require admin access
Session::requireLogin();
Session::requireAdmin();

$database = new Database();
$db = $database->getConnection();

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'create_alert':
                $title = $_POST['title'] ?? '';
                $description = $_POST['description'] ?? '';
                $severity = $_POST['severity'] ?? 'medium';

                if (!empty($title) && !empty($description)) {
                    $query = "INSERT INTO alerts (title, description, severity, created_by) 
                             VALUES (:title, :description, :severity, :created_by)";
                    $stmt = $db->prepare($query);
                    $stmt->bindParam(':title', $title);
                    $stmt->bindParam(':description', $description);
                    $stmt->bindParam(':severity', $severity);
                    $stmt->bindParam(':created_by', $_SESSION['agency_id']);
                    $stmt->execute();

                    // Log the alert creation
                    $logQuery = "INSERT INTO audit_logs (agency_id, action_type, details, ip_address) 
                                VALUES (:agency_id, 'alert_create', :details, :ip)";
                    $logStmt = $db->prepare($logQuery);
                    $details = "Created alert: " . $title;
                    $logStmt->bindParam(':agency_id', $_SESSION['agency_id']);
                    $logStmt->bindParam(':details', $details);
                    $logStmt->bindParam(':ip', $_SERVER['REMOTE_ADDR']);
                    $logStmt->execute();
                }
                break;

            case 'update_agency_status':
                $agency_id = $_POST['agency_id'] ?? 0;
                $is_active = $_POST['is_active'] ?? 0;

                if ($agency_id > 0) {
                    $query = "UPDATE agencies SET is_active = :is_active WHERE id = :id";
                    $stmt = $db->prepare($query);
                    $stmt->bindParam(':is_active', $is_active, PDO::PARAM_BOOL);
                    $stmt->bindParam(':id', $agency_id);
                    $stmt->execute();
                }
                break;
        }
    }
}

// Get all agencies
$agenciesQuery = "SELECT * FROM agencies ORDER BY created_at DESC";
$agenciesStmt = $db->prepare($agenciesQuery);
$agenciesStmt->execute();
$agencies = $agenciesStmt->fetchAll();

// Get all alerts
$alertsQuery = "SELECT a.*, ag.name as creator_name 
                FROM alerts a 
                JOIN agencies ag ON a.created_by = ag.id 
                ORDER BY a.created_at DESC";
$alertsStmt = $db->prepare($alertsQuery);
$alertsStmt->execute();
$alerts = $alertsStmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Panel - CRCP</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
</head>
<body class="bg-gray-100">
    <!-- Navigation -->
    <nav class="bg-white shadow-lg">
        <div class="max-w-7xl mx-auto px-4">
            <div class="flex justify-between h-16">
                <div class="flex">
                    <div class="flex-shrink-0 flex items-center">
                        <span class="text-xl font-bold text-indigo-600">CRCP Admin</span>
                    </div>
                </div>
                <div class="flex items-center">
                    <a href="dashboard.php" class="text-gray-700 hover:text-indigo-600 mr-4">Dashboard</a>
                    <a href="logout.php" class="text-gray-700 hover:text-indigo-600">Logout</a>
                </div>
            </div>
        </div>
    </nav>

    <div class="max-w-7xl mx-auto px-4 py-6">
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            <!-- Create Alert -->
            <div class="bg-white rounded-lg shadow-lg p-4">
                <h2 class="text-xl font-semibold mb-4">Create New Alert</h2>
                <form method="POST" class="space-y-4">
                    <input type="hidden" name="action" value="create_alert">
                    <div>
                        <label for="title" class="block text-sm font-medium text-gray-700">Title</label>
                        <input type="text" id="title" name="title" required
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                    </div>
                    <div>
                        <label for="description" class="block text-sm font-medium text-gray-700">Description</label>
                        <textarea id="description" name="description" rows="3" required
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"></textarea>
                    </div>
                    <div>
                        <label for="severity" class="block text-sm font-medium text-gray-700">Severity</label>
                        <select id="severity" name="severity" required
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                            <option value="low">Low</option>
                            <option value="medium" selected>Medium</option>
                            <option value="high">High</option>
                            <option value="critical">Critical</option>
                        </select>
                    </div>
                    <button type="submit"
                        class="w-full flex justify-center py-2 px-4 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                        Create Alert
                    </button>
                </form>
            </div>

            <!-- Manage Agencies -->
            <div class="bg-white rounded-lg shadow-lg p-4">
                <h2 class="text-xl font-semibold mb-4">Manage Agencies</h2>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Name</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Type</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <?php foreach ($agencies as $agency): ?>
                                <tr>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                        <?php echo htmlspecialchars($agency['name']); ?>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                        <?php echo ucfirst($agency['agency_type']); ?>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium 
                                            <?php echo $agency['is_active'] ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'; ?>">
                                            <?php echo $agency['is_active'] ? 'Active' : 'Inactive'; ?>
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                        <form method="POST" class="inline">
                                            <input type="hidden" name="action" value="update_agency_status">
                                            <input type="hidden" name="agency_id" value="<?php echo $agency['id']; ?>">
                                            <input type="hidden" name="is_active" value="<?php echo $agency['is_active'] ? '0' : '1'; ?>">
                                            <button type="submit"
                                                class="text-indigo-600 hover:text-indigo-900">
                                                <?php echo $agency['is_active'] ? 'Deactivate' : 'Activate'; ?>
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Alerts List -->
            <div class="lg:col-span-2 bg-white rounded-lg shadow-lg p-4">
                <h2 class="text-xl font-semibold mb-4">All Alerts</h2>
                <div class="space-y-4">
                    <?php foreach ($alerts as $alert): ?>
                        <div class="border-b border-gray-200 py-4">
                            <div class="flex justify-between items-start">
                                <div>
                                    <h3 class="text-lg font-medium text-gray-900"><?php echo htmlspecialchars($alert['title']); ?></h3>
                                    <p class="text-gray-600 mt-1"><?php echo htmlspecialchars($alert['description']); ?></p>
                                    <p class="text-sm text-gray-500 mt-2">Created by: <?php echo htmlspecialchars($alert['creator_name']); ?></p>
                                </div>
                                <div>
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium 
                                        <?php echo $alert['severity'] === 'critical' ? 'bg-red-100 text-red-800' : 
                                            ($alert['severity'] === 'high' ? 'bg-orange-100 text-orange-800' : 
                                            ($alert['severity'] === 'medium' ? 'bg-yellow-100 text-yellow-800' : 
                                            'bg-green-100 text-green-800')); ?>">
                                        <?php echo ucfirst($alert['severity']); ?>
                                    </span>
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium 
                                        <?php echo $alert['status'] === 'active' ? 'bg-green-100 text-green-800' : 
                                            ($alert['status'] === 'resolved' ? 'bg-blue-100 text-blue-800' : 
                                            'bg-gray-100 text-gray-800'); ?> ml-2">
                                        <?php echo ucfirst($alert['status']); ?>
                                    </span>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>
</body>
</html> 