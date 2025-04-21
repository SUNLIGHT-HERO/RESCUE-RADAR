<?php
require_once '../config/database.php';
require_once '../includes/session.php';

if (Session::isLoggedIn()) {
    $database = new Database();
    $db = $database->getConnection();

    // Log the logout action
    $logQuery = "INSERT INTO audit_logs (agency_id, action_type, ip_address) VALUES (:agency_id, 'logout', :ip)";
    $logStmt = $db->prepare($logQuery);
    $logStmt->bindParam(':agency_id', $_SESSION['agency_id']);
    $logStmt->bindParam(':ip', $_SERVER['REMOTE_ADDR']);
    $logStmt->execute();
}

// Destroy the session
Session::destroy();

// Redirect to login page
header("Location: login.php");
exit();
?> 