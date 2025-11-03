<?php
// Start session and include DB connection
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once "../config/db.php";

// Default unread message count
$unread_count = 0;

if (isset($_SESSION['user_id'])) {
    $uid = $_SESSION['user_id'];

    // Get unread messages count
    $sqlUnread = "SELECT COUNT(*) AS cnt 
                  FROM messages 
                  WHERE receiver_id = ? AND is_read = 0";
    $stmtUnread = $conn->prepare($sqlUnread);
    $stmtUnread->bind_param("i", $uid);
    $stmtUnread->execute();
    $resultUnread = $stmtUnread->get_result()->fetch_assoc();
    $unread_count = $resultUnread['cnt'] ?? 0;
}
?>

<style>
.user-sidebar {
    width: 220px;
    min-height: 100vh;
    background: #f8f9fa;
    padding: 20px;
    border-right: 1px solid #ddd;
}
.user-sidebar h2 {
    margin-bottom: 20px;
    color: #333;
}
.user-sidebar a {
    display: block;
    padding: 10px;
    margin: 5px 0;
    text-decoration: none;
    color: #333;
    border-radius: 6px;
    transition: background 0.2s;
}
.user-sidebar a:hover {
    background: #007bff;
    color: #fff;
}

/* Badge */
.badge {
    background: red;
    color: white;
    font-size: 12px;
    padding: 2px 7px;
    border-radius: 12px;
    margin-left: 6px;
}
</style>

<div class="user-sidebar">
    <h2>User Menu</h2>
    <a href="profile.php">My Profile</a>
    <a href="wishlisted-pets.php">Wishlisted Pets</a>
    <a href="my-messages.php">
        My Messages
        <?php if ($unread_count > 0): ?>
            <span class="badge"><?= $unread_count; ?></span>
        <?php endif; ?>
    </a>
    <a href="cart.php">Cart</a>
</div>
