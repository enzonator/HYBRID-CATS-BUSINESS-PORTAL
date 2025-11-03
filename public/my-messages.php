<?php
session_start();
require_once "../config/db.php";
include_once "../includes/header.php";

// Make sure user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// Fetch list of conversations (with unread count)
$sql = "
    SELECT 
        m.pet_id,
        p.name AS pet_name,
        CASE 
            WHEN m.sender_id = ? THEN m.receiver_id
            ELSE m.sender_id
        END AS other_user_id,
        u.username AS other_username,
        MAX(m.created_at) AS last_message_time,
        SUBSTRING_INDEX(MAX(CONCAT(m.created_at, '|', m.message)), '|', -1) AS last_message,
        SUM(
            CASE 
                WHEN m.receiver_id = ? AND m.is_read = 0 THEN 1 
                ELSE 0 
            END
        ) AS unread_count
    FROM messages m
    JOIN pets p ON m.pet_id = p.id
    JOIN users u ON u.id = 
        CASE 
            WHEN m.sender_id = ? THEN m.receiver_id
            ELSE m.sender_id
        END
    WHERE m.sender_id = ? OR m.receiver_id = ?
    GROUP BY m.pet_id, other_user_id
    ORDER BY last_message_time DESC
";

$stmt = $conn->prepare($sql);
$stmt->bind_param("iiiii", $user_id, $user_id, $user_id, $user_id, $user_id);
$stmt->execute();
$conversations = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
?>

<style>
/* Wrapper to hold sidebar + content side by side */
.wrapper {
    display: flex;
    min-height: 100vh;
    width: 100%;
}

/* Sidebar */
.sidebar {
    width: 220px;
    background: #2c3e50;
    color: #fff;
    padding-top: 20px;
    flex-shrink: 0;
}

/* Main content area */
.main-content {
    flex: 1;
    padding: 20px;
    background: #f9f9f9;
}

/* Messages container */
.messages-container {
    background: #fff;
    padding: 20px;
    border-radius: 12px;
    box-shadow: 0px 4px 10px rgba(0,0,0,0.1);
}

/* Conversation card */
.conversation {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 12px 16px;
    margin-bottom: 10px;
    border: 1px solid #ddd;
    border-radius: 8px;
    transition: background 0.2s;
    text-decoration: none;
    color: inherit;
}

.conversation:hover {
    background: #f5f5f5;
}

.conversation .info {
    flex: 1;
}

.conversation .pet {
    font-weight: bold;
    color: #007bff;
}

.conversation .last-msg {
    color: #555;
    margin-top: 4px;
    font-size: 14px;
}

.conversation .time {
    font-size: 12px;
    color: #888;
    white-space: nowrap;
    margin-left: 15px;
}

/* Unread badge */
.unread-badge {
    background: red;
    color: white;
    font-size: 12px;
    padding: 3px 8px;
    border-radius: 12px;
    margin-left: 10px;
}
</style>

<div class="wrapper">
    <!-- Sidebar -->
    <?php include_once "../includes/sidebar.php"; ?>

    <!-- Main content beside sidebar -->
    <div class="main-content">
        <div class="messages-container">
            <h2>My Messages</h2>

            <?php if (!empty($conversations)): ?>
                <?php foreach ($conversations as $c): ?>
                    <a class="conversation" 
                       href="message-seller.php?pet_id=<?= $c['pet_id']; ?>&seller_id=<?= $c['other_user_id']; ?>">
                        <div class="info">
                            <div class="pet">🐾 <?= htmlspecialchars($c['pet_name']); ?></div>
                            <div><strong><?= htmlspecialchars($c['other_username']); ?></strong></div>
                            <div class="last-msg"><?= htmlspecialchars($c['last_message']); ?></div>
                        </div>
                        <div class="time">
                            <?= $c['last_message_time']; ?>
                            <?php if ((int)$c['unread_count'] > 0): ?>
                                <span class="unread-badge"><?= (int)$c['unread_count']; ?></span>
                            <?php endif; ?>
                        </div>
                    </a>
                <?php endforeach; ?>
            <?php else: ?>
                <p><em>No conversations yet.</em></p>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php include_once "../includes/footer.php"; ?>
