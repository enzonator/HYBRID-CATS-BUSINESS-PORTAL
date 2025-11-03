<?php
ob_start(); // Prevent "headers already sent" errors
session_start();
require_once "../config/db.php";
include_once "../includes/header.php";

// Ensure user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id   = $_SESSION['user_id'];
$pet_id    = intval($_GET['pet_id'] ?? 0);
$seller_id = intval($_GET['seller_id'] ?? 0);

if (!$pet_id || !$seller_id) {
    die("Invalid request.");
}

// Fetch pet info
$petSql = "SELECT name FROM pets WHERE id = ?";
$petStmt = $conn->prepare($petSql);
if (!$petStmt) { die("Prepare failed: " . $conn->error); }
$petStmt->bind_param("i", $pet_id);
$petStmt->execute();
$pet = $petStmt->get_result()->fetch_assoc();

// Mark unread messages as read (on page load)
$markSql = "UPDATE messages SET is_read = 1 
            WHERE pet_id = ? AND sender_id = ? AND receiver_id = ? AND is_read = 0";
$markStmt = $conn->prepare($markSql);
if ($markStmt) {
    $markStmt->bind_param("iii", $pet_id, $seller_id, $user_id);
    $markStmt->execute();
    $markStmt->close();
}

// Handle new message
if ($_SERVER["REQUEST_METHOD"] === "POST" && !empty(trim($_POST['message']))) {
    $message = trim($_POST['message']);

    // Insert message
    $sql = "INSERT INTO messages (pet_id, sender_id, receiver_id, message) VALUES (?, ?, ?, ?)";
    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        die("Prepare failed: " . $conn->error);
    }
    $stmt->bind_param("iiis", $pet_id, $user_id, $seller_id, $message);
    $stmt->execute();
    $stmt->close();

    // Create notification for the seller
    $senderUsername = $_SESSION['username'] ?? 'Someone';
    $petName = $pet['name'] ?? 'your pet';
    $notificationMessage = "$senderUsername sent you a message about $petName";
    
    $notifSql = "INSERT INTO notifications (user_id, message, type, pet_id, is_read, created_at) 
                 VALUES (?, ?, 'new_message', ?, 0, NOW())";
    $notifStmt = $conn->prepare($notifSql);
    if ($notifStmt) {
        $notifStmt->bind_param("isi", $seller_id, $notificationMessage, $pet_id);
        $notifStmt->execute();
        $notifStmt->close();
    }

    // Redirect to avoid form resubmission
    header("Location: message-seller.php?pet_id={$pet_id}&seller_id={$seller_id}");
    exit();
}

// Initial fetch of messages
$msgSql = "SELECT m.*, u.username 
           FROM messages m
           JOIN users u ON m.sender_id = u.id
           WHERE m.pet_id = ? AND 
                 ((m.sender_id = ? AND m.receiver_id = ?) OR 
                  (m.sender_id = ? AND m.receiver_id = ?))
           ORDER BY m.created_at ASC";
$msgStmt = $conn->prepare($msgSql);
if (!$msgStmt) { die("Prepare failed: " . $conn->error); }
$msgStmt->bind_param("iiiii", $pet_id, $user_id, $seller_id, $seller_id, $user_id);
$msgStmt->execute();
$messages = $msgStmt->get_result()->fetch_all(MYSQLI_ASSOC);
$msgStmt->close();
?>

<style>
.chat-container {
    max-width: 800px;
    margin: 30px auto;
    background: #fff;
    padding: 20px;
    border-radius: 12px;
    box-shadow: 0px 4px 10px rgba(0,0,0,0.1);
}

.back-button {
    display: inline-block;
    margin-bottom: 15px;
    padding: 8px 14px;
    background: #f5f5f5;
    border: 1px solid #ddd;
    border-radius: 6px;
    text-decoration: none;
    color: #333;
    font-size: 14px;
    transition: background 0.2s;
}
.back-button:hover {
    background: #e2e2e2;
}

.messages {
    max-height: 500px;
    overflow-y: auto;
    margin-bottom: 20px;
    padding: 10px;
    border: 1px solid #ddd;
    border-radius: 8px;
    background: #fafafa;
}

.message {
    margin: 8px 0;
    padding: 8px 12px;
    border-radius: 6px;
    max-width: 70%;
    word-wrap: break-word;
}

.message.sent {
    background: #d1ffd6;
    margin-left: auto;
    text-align: right;
}

.message.received {
    background: #f1f1f1;
    margin-right: auto;
    text-align: left;
}

.send-form {
    display: flex;
    gap: 10px;
}
.send-form textarea {
    flex: 1;
    resize: none;
    padding: 10px;
    border-radius: 6px;
    border: 1px solid #ccc;
}
.send-form button {
    background: #007bff;
    color: white;
    border: none;
    padding: 10px 16px;
    border-radius: 6px;
    cursor: pointer;
    transition: background 0.3s;
}
.send-form button:hover {
    background: #0056b3;
}

.message small {
    display: block;
    margin-top: 6px;
    color: #666;
    font-size: 12px;
}
</style>

<div class="chat-container">
    <a href="my-messages.php" class="back-button">← Back</a>
    <h2>Inquire about <?= htmlspecialchars($pet['name'] ?? 'this pet'); ?></h2>

    <div class="messages" id="messages">
        <?php if (!empty($messages)): ?>
            <?php foreach ($messages as $msg): ?>
                <div class="message <?= ($msg['sender_id'] == $user_id) ? 'sent' : 'received' ?>">
                    <strong><?= htmlspecialchars($msg['username']); ?>:</strong><br>
                    <?= nl2br(htmlspecialchars($msg['message'])); ?>
                    <small><em><?= htmlspecialchars($msg['created_at']); ?></em></small>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <p><em>No messages yet. Start the conversation!</em></p>
        <?php endif; ?>
    </div>

    <form method="POST" class="send-form" id="sendForm">
        <textarea name="message" rows="2" placeholder="Type your message..." required id="messageInput"></textarea>
        <button type="submit">Send</button>
    </form>
</div>

<script>
let lastMessageCount = 0;

function scrollMessagesToBottom() {
    const container = document.getElementById('messages');
    if (container) {
        container.scrollTop = container.scrollHeight;
    }
}

function renderMessages(messages) {
    const container = document.getElementById('messages');

    // If the number of messages increased → auto update
    if (messages.length > lastMessageCount) {
        container.innerHTML = ""; 

        messages.forEach(msg => {
            const div = document.createElement('div');
            div.className = "message " + (msg.sender_id == <?= $user_id ?> ? "sent" : "received");
            div.innerHTML = `
                <strong>${msg.username}</strong><br>
                ${msg.message.replace(/\n/g, "<br>")}
                <small><em>${msg.created_at}</em></small>
            `;
            container.appendChild(div);
        });

        scrollMessagesToBottom();
        lastMessageCount = messages.length;
    }
}

function fetchMessages() {
    fetch("fetch-messages.php?pet_id=<?= $pet_id ?>&seller_id=<?= $seller_id ?>")
        .then(res => res.json())
        .then(data => {
            renderMessages(data);
        })
        .catch(err => console.error("Error fetching messages:", err));
}

document.addEventListener("DOMContentLoaded", () => {
    fetchMessages(); // initial load
    setInterval(fetchMessages, 2000); // check every 2 seconds
});
</script>

<?php include_once "../includes/footer.php"; ?>
<?php ob_end_flush(); ?>
