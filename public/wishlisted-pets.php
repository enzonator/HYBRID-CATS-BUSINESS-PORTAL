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

// Fetch pets in wishlist
$sql = "SELECT p.*, 
        (SELECT filename FROM pet_images WHERE pet_id = p.id LIMIT 1) as image1,
        (SELECT filename FROM pet_images WHERE pet_id = p.id LIMIT 1,1) as image2
        FROM wishlist w
        JOIN pets p ON w.pet_id = p.id
        WHERE w.user_id = ?
        ORDER BY w.created_at DESC";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$wishlist = $result->fetch_all(MYSQLI_ASSOC);
?>

<style>
.dashboard {
    display: flex;
    min-height: 100vh;
    background: #f9f9f9;
}

.wishlist-container {
    flex-grow: 1;
    margin: 20px;
    padding: 20px;
    background: #fff;
    border-radius: 10px;
    box-shadow: 0px 3px 6px rgba(0,0,0,0.1);
}

.wishlist-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(220px, 1fr));
    gap: 15px;
}

.wishlist-card {
    border: 1px solid #ddd;
    border-radius: 8px;
    overflow: hidden;
    background: #fafafa;
    transition: 0.2s;
    position: relative;
}

.wishlist-card:hover {
    transform: scale(1.03);
    box-shadow: 0px 4px 10px rgba(0,0,0,0.15);
}

.wishlist-card img {
    width: 100%;
    height: 180px;
    object-fit: cover;
    transition: opacity 0.3s ease;
}

.wishlist-card .image-wrapper {
    position: relative;
    width: 100%;
    height: 180px;
}

.wishlist-card .image-wrapper img.second {
    position: absolute;
    top: 0;
    left: 0;
    opacity: 0;
}

.wishlist-card .image-wrapper:hover img.first {
    opacity: 0;
}

.wishlist-card .image-wrapper:hover img.second {
    opacity: 1;
}

.wishlist-card .info {
    padding: 10px;
}

.wishlist-card h4 {
    margin: 0 0 5px;
    font-size: 16px;
}

.wishlist-card p {
    margin: 3px 0;
    font-size: 14px;
    color: #555;
}

.wishlist-card a {
    display: inline-block;
    margin-top: 8px;
    padding: 6px 10px;
    background: #007bff;
    color: white;
    border-radius: 5px;
    font-size: 13px;
    text-decoration: none;
}

.wishlist-card a:hover {
    background: #0056b3;
}

.remove-btn {
    position: absolute;
    top: 10px;
    right: 10px;
    background: rgba(255, 255, 255, 0.9);
    border: none;
    border-radius: 50%;
    padding: 8px;
    cursor: pointer;
    font-size: 18px;
    color: #e74c3c;
    transition: background 0.2s, transform 0.2s;
    z-index: 10;
}

.remove-btn:hover {
    transform: scale(1.1);
    background: #fff;
}
</style>

<div class="dashboard">
    <!-- Sidebar -->
    <?php include_once "../includes/sidebar.php"; ?>

    <!-- Wishlist Section -->
    <div class="wishlist-container">
        <h2>My Wishlisted Pets</h2>
        <div class="wishlist-grid">
            <?php if (!empty($wishlist)): ?>
                <?php foreach ($wishlist as $pet): ?>
                    <div class="wishlist-card">
                        <div class="image-wrapper">
                            <form method="POST" action="wishlist_remove.php">
                                <input type="hidden" name="pet_id" value="<?= $pet['id']; ?>">
                                <button type="submit" class="remove-btn" title="Remove from Wishlist">✖</button>
                            </form>

                            <img src="../uploads/<?= htmlspecialchars($pet['image1'] ?? 'no-image.png'); ?>" 
                                 alt="Pet" class="first">
                            <?php if (!empty($pet['image2'])): ?>
                                <img src="../uploads/<?= htmlspecialchars($pet['image2']); ?>" 
                                     alt="Pet Hover" class="second">
                            <?php endif; ?>
                        </div>
                        <div class="info">
                            <h4><?= htmlspecialchars($pet['name']); ?></h4>
                            <p><strong>Type:</strong> <?= htmlspecialchars($pet['type']); ?></p>
                            <p><strong>Breed:</strong> <?= htmlspecialchars($pet['breed']); ?></p>
                            <p><strong>₱<?= number_format($pet['price'], 2); ?></strong></p>
                            <a href="pet-details.php?id=<?= $pet['id']; ?>">View Details</a>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <p>You don’t have any pets in your wishlist yet.</p>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php include_once "../includes/footer.php"; ?>
