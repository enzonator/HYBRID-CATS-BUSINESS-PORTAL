<?php
session_start();
require_once "../config/db.php";
include_once "../includes/header.php";

// Redirect if not logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// Fetch all orders for the current user
$sql = "SELECT o.*, p.name as pet_name, p.breed, p.type, u.username as seller_name, u.email as seller_email
        FROM orders o
        JOIN pets p ON o.pet_id = p.id
        JOIN users u ON o.seller_id = u.id
        WHERE o.buyer_id = ?
        ORDER BY o.order_date DESC";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$orders = $result->fetch_all(MYSQLI_ASSOC);

// Fetch pet images for orders
foreach ($orders as &$order) {
    $imgSql = "SELECT filename FROM pet_images WHERE pet_id = ? LIMIT 1";
    $imgStmt = $conn->prepare($imgSql);
    $imgStmt->bind_param("i", $order['pet_id']);
    $imgStmt->execute();
    $imgResult = $imgStmt->get_result();
    $imgRow = $imgResult->fetch_assoc();
    $order['image'] = $imgRow ? $imgRow['filename'] : 'default-pet.jpg';
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>My Orders - CatShop</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <style>
    body {
      background-color: #f9f9f9;
      font-family: "Segoe UI", Tahoma, Geneva, Verdana, sans-serif;
    }
    .orders-container {
      max-width: 1200px;
      margin: 40px auto;
      padding: 0 20px;
    }
    .page-header {
      background: white;
      padding: 30px;
      border-radius: 12px;
      box-shadow: 0 2px 10px rgba(0,0,0,0.1);
      margin-bottom: 30px;
    }
    .page-title {
      font-size: 32px;
      font-weight: 700;
      color: #333;
      margin-bottom: 10px;
    }
    .page-subtitle {
      color: #666;
      font-size: 16px;
    }
    .filter-tabs {
      display: flex;
      gap: 10px;
      margin-bottom: 20px;
      flex-wrap: wrap;
    }
    .filter-btn {
      padding: 10px 20px;
      border: 2px solid #dee2e6;
      background: white;
      border-radius: 8px;
      cursor: pointer;
      font-weight: 500;
      transition: all 0.3s ease;
    }
    .filter-btn:hover {
      border-color: #667eea;
      color: #667eea;
    }
    .filter-btn.active {
      background: #667eea;
      color: white;
      border-color: #667eea;
    }
    .order-card {
      background: white;
      border-radius: 12px;
      box-shadow: 0 2px 10px rgba(0,0,0,0.08);
      margin-bottom: 20px;
      overflow: hidden;
      transition: transform 0.3s ease, box-shadow 0.3s ease;
    }
    .order-card:hover {
      transform: translateY(-2px);
      box-shadow: 0 4px 20px rgba(0,0,0,0.12);
    }
    .order-header {
      background: #f8f9fa;
      padding: 20px;
      border-bottom: 1px solid #dee2e6;
      display: flex;
      justify-content: space-between;
      align-items: center;
      flex-wrap: wrap;
      gap: 15px;
    }
    .order-id {
      font-weight: 600;
      color: #333;
      font-size: 16px;
    }
    .order-date {
      color: #666;
      font-size: 14px;
    }
    .order-body {
      padding: 20px;
      display: flex;
      gap: 20px;
      align-items: center;
    }
    .order-image {
      width: 120px;
      height: 120px;
      object-fit: cover;
      border-radius: 10px;
      cursor: pointer;
      transition: transform 0.3s ease;
    }
    .order-image:hover {
      transform: scale(1.05);
    }
    .order-details {
      flex: 1;
    }
    .pet-name {
      font-size: 20px;
      font-weight: 600;
      color: #333;
      margin-bottom: 8px;
    }
    .pet-info {
      color: #666;
      font-size: 14px;
      margin-bottom: 5px;
    }
    .seller-info {
      color: #667eea;
      font-size: 14px;
      font-weight: 500;
    }
    .order-price {
      text-align: right;
    }
    .price-label {
      color: #666;
      font-size: 14px;
      margin-bottom: 5px;
    }
    .price-amount {
      font-size: 24px;
      font-weight: 700;
      color: #667eea;
    }
    .order-footer {
      background: #f8f9fa;
      padding: 15px 20px;
      border-top: 1px solid #dee2e6;
      display: flex;
      justify-content: space-between;
      align-items: center;
      flex-wrap: wrap;
      gap: 15px;
    }
    .status-badge {
      padding: 8px 16px;
      border-radius: 20px;
      font-weight: 600;
      font-size: 13px;
      text-transform: uppercase;
    }
    .status-pending {
      background: #fff3cd;
      color: #856404;
    }
    .status-confirmed {
      background: #d1ecf1;
      color: #0c5460;
    }
    .status-shipped {
      background: #cce5ff;
      color: #004085;
    }
    .status-delivered {
      background: #d4edda;
      color: #155724;
    }
    .status-cancelled {
      background: #f8d7da;
      color: #721c24;
    }
    .order-actions {
      display: flex;
      gap: 10px;
    }
    .btn-action {
      padding: 8px 16px;
      border: none;
      border-radius: 6px;
      font-size: 14px;
      font-weight: 500;
      cursor: pointer;
      transition: all 0.3s ease;
      text-decoration: none;
      display: inline-block;
    }
    .btn-view {
      background: #667eea;
      color: white;
    }
    .btn-view:hover {
      background: #5568d3;
      color: white;
    }
    .btn-contact {
      background: #28a745;
      color: white;
    }
    .btn-contact:hover {
      background: #218838;
      color: white;
    }
    .empty-orders {
      text-align: center;
      padding: 80px 20px;
      background: white;
      border-radius: 12px;
      box-shadow: 0 2px 10px rgba(0,0,0,0.08);
    }
    .empty-icon {
      font-size: 80px;
      margin-bottom: 20px;
    }
    .empty-title {
      font-size: 24px;
      font-weight: 600;
      color: #333;
      margin-bottom: 10px;
    }
    .empty-text {
      color: #666;
      margin-bottom: 30px;
    }
    .payment-method {
      display: inline-block;
      padding: 5px 12px;
      background: #e9ecef;
      border-radius: 5px;
      font-size: 13px;
      font-weight: 500;
      color: #495057;
      margin-top: 5px;
    }
    .order-info-grid {
      display: grid;
      grid-template-columns: repeat(2, 1fr);
      gap: 10px;
      margin-top: 10px;
    }
    .info-item {
      font-size: 13px;
    }
    .info-label {
      color: #666;
      font-weight: 500;
    }
    .info-value {
      color: #333;
    }
    
    @media (max-width: 768px) {
      .order-body {
        flex-direction: column;
        align-items: flex-start;
      }
      .order-image {
        width: 100%;
        height: 200px;
      }
      .order-price {
        text-align: left;
      }
      .order-header, .order-footer {
        flex-direction: column;
        align-items: flex-start;
      }
      .order-info-grid {
        grid-template-columns: 1fr;
      }
    }
  </style>
</head>
<body>

<div class="orders-container">
  <!-- Page Header -->
  <div class="page-header">
    <h1 class="page-title">📦 My Orders</h1>
    <p class="page-subtitle">Track and manage your pet orders</p>
  </div>

  <!-- Filter Tabs -->
  <div class="filter-tabs">
    <button class="filter-btn active" onclick="filterOrders('all')">All Orders</button>
    <button class="filter-btn" onclick="filterOrders('pending')">Pending</button>
    <button class="filter-btn" onclick="filterOrders('confirmed')">Confirmed</button>
    <button class="filter-btn" onclick="filterOrders('shipped')">Shipped</button>
    <button class="filter-btn" onclick="filterOrders('delivered')">Delivered</button>
    <button class="filter-btn" onclick="filterOrders('cancelled')">Cancelled</button>
  </div>

  <!-- Orders List -->
  <?php if (count($orders) > 0): ?>
    <div id="ordersList">
      <?php foreach ($orders as $order): ?>
        <div class="order-card" data-status="<?= htmlspecialchars($order['status']) ?>">
          <!-- Order Header -->
          <div class="order-header">
            <div>
              <div class="order-id">Order #<?= $order['id'] ?></div>
              <div class="order-date">
                <?= date('F d, Y - h:i A', strtotime($order['order_date'])) ?>
              </div>
            </div>
            <span class="status-badge status-<?= htmlspecialchars($order['status']) ?>">
              <?= ucfirst(htmlspecialchars($order['status'])) ?>
            </span>
          </div>

          <!-- Order Body -->
          <div class="order-body">
            <img src="../uploads/<?= htmlspecialchars($order['image']) ?>" 
                 alt="<?= htmlspecialchars($order['pet_name']) ?>"
                 class="order-image"
                 onclick="window.location.href='pet-details.php?id=<?= $order['pet_id'] ?>'">
            
            <div class="order-details">
              <div class="pet-name"><?= htmlspecialchars($order['pet_name']) ?></div>
              <div class="pet-info">
                🐾 <?= htmlspecialchars($order['type']) ?> • <?= htmlspecialchars($order['breed']) ?>
              </div>
              <div class="seller-info">
                👤 Seller: <?= htmlspecialchars($order['seller_name']) ?>
              </div>
              <div class="payment-method">
                💳 <?= strtoupper(htmlspecialchars($order['payment_method'])) ?>
              </div>
              
              <div class="order-info-grid">
                <div class="info-item">
                  <span class="info-label">Delivery to:</span><br>
                  <span class="info-value"><?= htmlspecialchars($order['city']) ?></span>
                </div>
                <div class="info-item">
                  <span class="info-label">Contact:</span><br>
                  <span class="info-value"><?= htmlspecialchars($order['phone']) ?></span>
                </div>
              </div>
            </div>

            <div class="order-price">
              <div class="price-label">Total Amount</div>
              <div class="price-amount">₱<?= number_format($order['total_amount'], 2) ?></div>
            </div>
          </div>

          <!-- Order Footer -->
          <div class="order-footer">
            <div>
              <?php if (!empty($order['notes'])): ?>
                <small style="color: #666;">
                  📝 Note: <?= htmlspecialchars($order['notes']) ?>
                </small>
              <?php endif; ?>
            </div>
            <div class="order-actions">
              <a href="order-details.php?id=<?= $order['id'] ?>" class="btn-action btn-view">
                View Details
              </a>
              <a href="message-seller.php?seller_id=<?= $order['seller_id'] ?>&pet_id=<?= $order['pet_id'] ?>" 
                 class="btn-action btn-contact">
                Contact Seller
              </a>
            </div>
          </div>
        </div>
      <?php endforeach; ?>
    </div>
  <?php else: ?>
    <!-- Empty State -->
    <div class="empty-orders">
      <div class="empty-icon">📦</div>
      <h2 class="empty-title">No Orders Yet</h2>
      <p class="empty-text">You haven't placed any orders yet. Start shopping for your perfect pet!</p>
      <a href="products.php" class="btn btn-primary btn-lg">Browse Pets</a>
    </div>
  <?php endif; ?>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
function filterOrders(status) {
  // Update active button
  document.querySelectorAll('.filter-btn').forEach(btn => {
    btn.classList.remove('active');
  });
  event.target.classList.add('active');

  // Filter orders
  const orderCards = document.querySelectorAll('.order-card');
  
  orderCards.forEach(card => {
    if (status === 'all') {
      card.style.display = 'block';
    } else {
      if (card.dataset.status === status) {
        card.style.display = 'block';
      } else {
        card.style.display = 'none';
      }
    }
  });
}
</script>

</body>
</html>

<?php include_once "../includes/footer.php"; ?>