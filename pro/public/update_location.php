<?php
require_once '../config/database.php';
require_once '../includes/session.php';

// Require login
Session::requireLogin();

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $latitude = filter_input(INPUT_POST, 'latitude', FILTER_VALIDATE_FLOAT);
    $longitude = filter_input(INPUT_POST, 'longitude', FILTER_VALIDATE_FLOAT);

    if ($latitude === false || $longitude === false) {
        echo json_encode(['success' => false, 'message' => 'Invalid coordinates']);
        exit();
    }

    $database = new Database();
    $db = $database->getConnection();

    try {
        // Start transaction
        $db->beginTransaction();

        // Insert new location
        $locationQuery = "INSERT INTO locations (agency_id, latitude, longitude) VALUES (:agency_id, :latitude, :longitude)";
        $locationStmt = $db->prepare($locationQuery);
        $locationStmt->bindParam(':agency_id', $_SESSION['agency_id']);
        $locationStmt->bindParam(':latitude', $latitude);
        $locationStmt->bindParam(':longitude', $longitude);
        $locationStmt->execute();

        // Log the update
        $logQuery = "INSERT INTO audit_logs (agency_id, action_type, details, ip_address) 
                    VALUES (:agency_id, 'location_update', :details, :ip)";
        $logStmt = $db->prepare($logQuery);
        $details = "Updated location to: " . $latitude . ", " . $longitude;
        $logStmt->bindParam(':agency_id', $_SESSION['agency_id']);
        $logStmt->bindParam(':details', $details);
        $logStmt->bindParam(':ip', $_SERVER['REMOTE_ADDR']);
        $logStmt->execute();

        // Commit transaction
        $db->commit();

        echo json_encode(['success' => true]);
    } catch (Exception $e) {
        // Rollback transaction on error
        $db->rollBack();
        echo json_encode(['success' => false, 'message' => 'Database error']);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
}
?> 