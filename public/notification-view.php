<?php
session_start();
require_once "../config/db.php";

// Redirect if not logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$notif_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($notif_id > 0) {
    // Mark notification as read
    $updateSql = "UPDATE notifications SET is_read = 1 WHERE id = ? AND user_id = ?";
    $updateStmt = $conn->prepare($updateSql);
    $updateStmt->bind_param("ii", $notif_id, $user_id);
    $updateStmt->execute();
    
    // Get notification details
    $notifSql = "SELECT * FROM notifications WHERE id = ? AND user_id = ?";
    $notifStmt = $conn->prepare($notifSql);
    $notifStmt->bind_param("ii", $notif_id, $user_id);
    $notifStmt->execute();
    $result = $notifStmt->get_result();
    $notification = $result->fetch_assoc();
    
    if ($notification) {
        // Redirect based on notification type
        if ($notification['type'] === 'adoption_application' && !empty($notification['cat_id'])) {
            // Redirect to adoption applications management (you'll need to create this page)
            header("Location: adoption-applications.php?cat_id=" . $notification['cat_id']);
            exit();
        } elseif ($notification['type'] === 'new_order' && !empty($notification['order_id'])) {
            header("Location: order-details.php?id=" . $notification['order_id']);
            exit();
        } elseif ($notification['type'] === 'new_message' && !empty($notification['pet_id'])) {
            // Redirect to messages page for this specific pet
            header("Location: my-messages.php");
            exit();
        } elseif (strpos($notification['type'], 'order_') === 0 && !empty($notification['order_id'])) {
            // Handle all order-related notifications
            header("Location: order-details.php?id=" . $notification['order_id']);
            exit();
        }
    }
}

// If no specific action, redirect to orders page
header("Location: orders.php");
exit();
?>