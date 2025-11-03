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

// Handle Remove from Cart
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['remove_from_cart'])) {
    $cart_id = intval($_POST['cart_id']);
    
    // Verify the cart item belongs to the current user before deleting
    $deleteSql = "DELETE FROM cart WHERE id = ? AND user_id = ?";
    $deleteStmt = $conn->prepare($deleteSql);
    $deleteStmt->bind_param("ii", $cart_id, $user_id);
    
    if ($deleteStmt->execute()) {
        $_SESSION['cart_message'] = "Item removed from cart successfully!";
        $_SESSION['cart_message_type'] = "success";
    } else {
        $_SESSION['cart_message'] = "Failed to remove item from cart.";
        $_SESSION['cart_message_type'] = "danger";
    }
    
    // Redirect to prevent form resubmission
    header("Location: cart.php");
    exit();
}

// Fetch cart items joined with pet details
$sql = "
    SELECT 
        c.id AS cart_id,
        p.id AS pet_id,
        p.name AS pet_name,
        p.price
    FROM cart c
    JOIN pets p ON c.pet_id = p.id
    WHERE c.user_id = ?
";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();

$cart_items = [];
$total = 0;

if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        // Fetch the first image for this pet
        $imgSql = "SELECT filename FROM pet_images WHERE pet_id = ? LIMIT 1";
        $imgStmt = $conn->prepare($imgSql);
        $imgStmt->bind_param("i", $row['pet_id']);
        $imgStmt->execute();
        $imgResult = $imgStmt->get_result();
        $imgRow = $imgResult->fetch_assoc();
        
        $row['image'] = $imgRow ? $imgRow['filename'] : 'default-pet.jpg';
        
        $cart_items[] = $row;
        $total += $row['price'];
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>My Cart - CatShop</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <style>
    body {
      background-color: #f9f9f9;
      font-family: "Segoe UI", Tahoma, Geneva, Verdana, sans-serif;
    }
    .cart-container {
      flex-grow: 1;
      margin: 20px;
      padding: 20px;
      background: #fff;
      border-radius: 10px;
      box-shadow: 0px 3px 6px rgba(0,0,0,0.1);
    }
    .cart-item {
      display: flex;
      align-items: center;
      justify-content: space-between;
      border-bottom: 1px solid #eee;
      padding: 15px 0;
      transition: background-color 0.2s ease;
    }
    .cart-item:hover {
      background-color: #f8f9fa;
    }
    .cart-item-checkbox {
      margin-right: 15px;
    }
    .cart-item-checkbox input[type="checkbox"] {
      width: 20px;
      height: 20px;
      cursor: pointer;
      accent-color: #28a745;
    }
    .cart-item-left {
      display: flex;
      align-items: center;
      flex: 1;
    }
    .cart-item img {
      width: 90px;
      height: 90px;
      object-fit: cover;
      border-radius: 10px;
      margin-right: 20px;
      cursor: pointer;
    }
    .cart-item-info {
      flex: 1;
    }
    .cart-total {
      text-align: right;
      margin-top: 20px;
      font-size: 1.2em;
    }
    .select-all-container {
      padding: 15px 0;
      border-bottom: 2px solid #dee2e6;
      margin-bottom: 10px;
    }
    .select-all-container label {
      font-weight: 600;
      cursor: pointer;
      user-select: none;
    }
    .select-all-container input[type="checkbox"] {
      width: 20px;
      height: 20px;
      cursor: pointer;
      margin-right: 10px;
      accent-color: #28a745;
    }
    .select-all-container {
      padding: 15px 0;
      border-bottom: 2px solid #dee2e6;
      margin-bottom: 10px;
    }
    .select-all-container label {
      font-weight: 600;
      cursor: pointer;
      user-select: none;
    }
    .select-all-container input[type="checkbox"] {
      width: 20px;
      height: 20px;
      cursor: pointer;
      margin-right: 10px;
      accent-color: #28a745;
    }
    .btn-remove {
      background: #dc3545;
      border: none;
      color: white;
      padding: 8px 16px;
      border-radius: 6px;
      cursor: pointer;
      font-size: 14px;
      transition: background-color 0.3s ease, transform 0.1s ease;
    }
    .btn-remove:hover {
      background: #c82333;
      transform: scale(1.05);
    }
    .btn-remove:active {
      transform: scale(0.98);
    }
    .empty-cart {
      text-align: center;
      padding: 50px;
      font-size: 1.2em;
      color: #777;
    }
    .alert {
      margin-bottom: 20px;
      animation: slideDown 0.3s ease;
    }
    @keyframes slideDown {
      from {
        opacity: 0;
        transform: translateY(-10px);
      }
      to {
        opacity: 1;
        transform: translateY(0);
      }
    }
    .cart-item-actions {
      display: flex;
      align-items: center;
      gap: 10px;
    }

    /* Custom Modal Styles */
    .modal-overlay {
      display: none;
      position: fixed;
      top: 0;
      left: 0;
      width: 100%;
      height: 100%;
      background: rgba(0, 0, 0, 0.5);
      z-index: 9999;
      justify-content: center;
      align-items: center;
    }
    .modal-overlay.active {
      display: flex;
    }
    .modal-content {
      background: white;
      padding: 30px;
      border-radius: 12px;
      box-shadow: 0 10px 40px rgba(0, 0, 0, 0.3);
      max-width: 400px;
      width: 90%;
      animation: modalSlideIn 0.3s ease;
    }
    @keyframes modalSlideIn {
      from {
        opacity: 0;
        transform: translateY(-50px);
      }
      to {
        opacity: 1;
        transform: translateY(0);
      }
    }
    .modal-header {
      font-weight: 600;
      font-size: 16px;
      color: #333;
      margin-bottom: 8px;
    }
    .modal-message {
      color: #666;
      font-size: 15px;
      margin-bottom: 25px;
      line-height: 1.5;
    }
    .modal-buttons {
      display: flex;
      gap: 12px;
      justify-content: flex-end;
    }
    .modal-btn {
      padding: 10px 24px;
      border: none;
      border-radius: 20px;
      font-size: 14px;
      font-weight: 500;
      cursor: pointer;
      transition: all 0.3s ease;
    }
    .modal-btn-ok {
      background: #8B6914;
      color: white;
    }
    .modal-btn-ok:hover {
      background: #6d5010;
      transform: scale(1.05);
    }
    .modal-btn-cancel {
      background: #f5deb3;
      color: #8B6914;
    }
    .modal-btn-cancel:hover {
      background: #e8d5a8;
      transform: scale(1.05);
    }

    .carty{
      display: flex;
      min-height: 100vh;
      background: #f9f9f9;
    }

  </style>
</head>
<body>

<div class="carty">
  <?php include_once "../includes/sidebar.php"; ?>
  <div class="cart-container">
    <h2 class="text-center mb-4">🛒 My Cart</h2>
  
    <?php
    // Display success/error messages
    if (isset($_SESSION['cart_message'])):
    ?>
      <div class="alert alert-<?= $_SESSION['cart_message_type'] ?> alert-dismissible fade show" role="alert">
        <?= htmlspecialchars($_SESSION['cart_message']) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
      </div>
    <?php
      unset($_SESSION['cart_message']);
      unset($_SESSION['cart_message_type']);
    endif;
    ?>
  
    <?php if (count($cart_items) > 0): ?>
      <!-- Select All Checkbox -->
      <div class="select-all-container">
        <input type="checkbox" id="selectAll" onchange="toggleSelectAll(this)">
        <label for="selectAll">Select All</label>
      </div>
  
      <?php foreach ($cart_items as $item): ?>
        <div class="cart-item">
          <div class="cart-item-left">
            <div class="cart-item-checkbox">
              <input type="checkbox"
                     class="item-checkbox"
                     data-price="<?= $item['price'] ?>"
                     data-pet-id="<?= $item['pet_id'] ?>"
                     onchange="updateTotal()">
            </div>
            <img src="../uploads/<?= htmlspecialchars($item['image']) ?>"
                 alt="<?= htmlspecialchars($item['pet_name']) ?>"
                 onclick="window.location.href='pet-details.php?id=<?= $item['pet_id'] ?>'">
            <div class="cart-item-info">
              <h5>
                <a href="pet-details.php?id=<?= $item['pet_id'] ?>" style="text-decoration: none; color: inherit;">
                  <?= htmlspecialchars($item['pet_name']) ?>
                </a>
              </h5>
              <p class="mb-0 text-muted">₱<?= number_format($item['price'], 2) ?></p>
            </div>
          </div>
          <div class="cart-item-actions">
            <form method="POST" id="removeForm_<?= $item['cart_id'] ?>" style="margin:0;">
              <input type="hidden" name="cart_id" value="<?= $item['cart_id'] ?>">
              <input type="hidden" name="remove_from_cart" value="1">
              <button type="button" class="btn-remove" onclick="showRemoveModal(<?= $item['cart_id'] ?>)">
                🗑️ Remove
              </button>
            </form>
          </div>
        </div>
      <?php endforeach; ?>
  
      <div class="cart-total">
        <strong>Selected Items Total:</strong> <span id="selectedTotal">₱0.00</span>
      </div>
  
      <div class="text-end mt-3">
        <a href="products.php" class="btn btn-secondary me-2">Continue Shopping</a>
        <button id="checkoutBtn" class="btn btn-success" onclick="proceedToCheckout()" disabled>
          Proceed to Checkout
        </button>
      </div>
    <?php else: ?>
      <div class="empty-cart">
        <p>Your cart is empty 😿</p>
        <a href="products.php" class="btn btn-primary mt-3">Browse Cats</a>
      </div>
    <?php endif; ?>
  </div>
  
  <!-- Custom Remove Confirmation Modal -->
  <div class="modal-overlay" id="removeModal">
    <div class="modal-content">
      <div class="modal-header">localhost says</div>
      <div class="modal-message">Are you sure you want to remove this pet from your cart?</div>
      <div class="modal-buttons">
        <button class="modal-btn modal-btn-ok" onclick="confirmRemove()">OK</button>
        <button class="modal-btn modal-btn-cancel" onclick="closeModal()">Cancel</button>
      </div>
    </div>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
  let currentCartId = null;

  // Toggle Select All
  function toggleSelectAll(checkbox) {
    const itemCheckboxes = document.querySelectorAll('.item-checkbox');
    itemCheckboxes.forEach(cb => {
      cb.checked = checkbox.checked;
    });
    updateTotal();
  }

  // Update total based on selected items
  function updateTotal() {
    const checkboxes = document.querySelectorAll('.item-checkbox:checked');
    let total = 0;
    
    checkboxes.forEach(cb => {
      total += parseFloat(cb.dataset.price);
    });

    document.getElementById('selectedTotal').textContent = '₱' + total.toLocaleString('en-PH', {
      minimumFractionDigits: 2,
      maximumFractionDigits: 2
    });

    // Enable/disable checkout button
    const checkoutBtn = document.getElementById('checkoutBtn');
    if (checkboxes.length > 0) {
      checkoutBtn.disabled = false;
    } else {
      checkoutBtn.disabled = true;
    }

    // Update "Select All" checkbox state
    const allCheckboxes = document.querySelectorAll('.item-checkbox');
    const selectAllCheckbox = document.getElementById('selectAll');
    selectAllCheckbox.checked = allCheckboxes.length === checkboxes.length && allCheckboxes.length > 0;
  }

  // Proceed to checkout with selected items
  function proceedToCheckout() {
    const selectedCheckboxes = document.querySelectorAll('.item-checkbox:checked');
    
    if (selectedCheckboxes.length === 0) {
      alert('Please select at least one item to checkout.');
      return;
    }

    const petIds = Array.from(selectedCheckboxes).map(cb => cb.dataset.petId);
    window.location.href = 'checkout.php?pet_ids=' + petIds.join(',');
  }

  function showRemoveModal(cartId) {
    currentCartId = cartId;
    document.getElementById('removeModal').classList.add('active');
  }

  function closeModal() {
    document.getElementById('removeModal').classList.remove('active');
    currentCartId = null;
  }

  function confirmRemove() {
    if (currentCartId) {
      document.getElementById('removeForm_' + currentCartId).submit();
    }
  }

  // Close modal when clicking outside
  document.getElementById('removeModal').addEventListener('click', function(e) {
    if (e.target === this) {
      closeModal();
    }
  });

  // Close modal with Escape key
  document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
      closeModal();
    }
  });
</script>
</body>
</html>

<?php include_once "../includes/footer.php"; ?>