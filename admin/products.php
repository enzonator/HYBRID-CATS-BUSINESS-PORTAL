<?php
require_once __DIR__ . '/../includes/auth.php';
requireAdmin();

require_once __DIR__ . '/../config/db.php';

$q = trim($_GET['q'] ?? '');
$sort = $_GET['sort'] ?? 'recent';

switch ($sort) {
    case 'id': $orderBy = "p.id ASC"; break;
    case 'price_asc': $orderBy = "p.price ASC"; break;
    case 'price_desc': $orderBy = "p.price DESC"; break;
    default: $orderBy = "p.created_at DESC"; break;
}

if ($q !== '') {
    $like = "%$q%";
    $sql = "SELECT p.id, p.name, p.type, p.breed, p.price, p.created_at, u.username
            FROM pets p
            JOIN users u ON u.id = p.user_id
            WHERE p.name LIKE ? OR p.breed LIKE ? OR p.type LIKE ? OR u.username LIKE ?
            ORDER BY $orderBy";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('ssss', $like, $like, $like, $like);
    $stmt->execute();
    $pets = $stmt->get_result();
} else {
    $sql = "SELECT p.id, p.name, p.type, p.breed, p.price, p.created_at, u.username
            FROM pets p
            JOIN users u ON u.id = p.user_id
            ORDER BY $orderBy";
    $pets = $conn->query($sql);
}

include __DIR__ . "/../includes/admin-sidebar.php";
?>

<div class="products-page">
  <div class="content-wrapper">
    <h2>🐾 Manage Pets</h2>

    <!-- Search & Sort -->
    <div class="actions-bar">
      <form method="get" class="search-form">
        <input type="text" name="q" placeholder="Search pets, breed, type, seller…" value="<?= htmlspecialchars($q) ?>">
        <button type="submit">Search</button>
      </form>
      <form method="get" class="sort-form">
        <input type="hidden" name="q" value="<?= htmlspecialchars($q) ?>">
        <label>Sort by:</label>
        <select name="sort" onchange="this.form.submit()">
          <option value="recent" <?= $sort==='recent'?'selected':'' ?>>Most Recent</option>
          <option value="id" <?= $sort==='id'?'selected':'' ?>>ID</option>
          <option value="price_asc" <?= $sort==='price_asc'?'selected':'' ?>>Price: Low → High</option>
          <option value="price_desc" <?= $sort==='price_desc'?'selected':'' ?>>Price: High → Low</option>
        </select>
      </form>
    </div>

    <!-- Table -->
    <div class="table-container">
      <table>
        <thead>
          <tr>
            <th>ID</th>
            <th>Name</th>
            <th>Type</th>
            <th>Breed</th>
            <th>Seller</th>
            <th>Price (₱)</th>
            <th>Created</th>
            <th>Actions</th>
          </tr>
        </thead>
        <tbody>
        <?php if ($pets && $pets->num_rows > 0): ?>
          <?php while ($row = $pets->fetch_assoc()): ?>
            <tr>
              <td><?= $row['id'] ?></td>
              <td><strong><?= htmlspecialchars($row['name']) ?></strong></td>
              <td><?= htmlspecialchars($row['type']) ?></td>
              <td><?= htmlspecialchars($row['breed']) ?></td>
              <td><?= htmlspecialchars($row['username']) ?></td>
              <td><span class="badge">₱<?= number_format($row['price'],2) ?></span></td>
              <td><?= $row['created_at'] ?></td>
              <td>
                <div class="actions">
                  <a href="/catshop/public/pet-details.php?id=<?= $row['id'] ?>" class="btn view">View</a>
                  <a href="#" class="btn edit disabled">Edit</a>
                  <a href="/catshop/admin/delete-pet.php?id=<?= $row['id'] ?>" class="btn delete"
                     onclick="return confirm('Delete this pet?');">Delete</a>
                </div>
              </td>
            </tr>
          <?php endwhile; ?>
        <?php else: ?>
          <tr><td colspan="8" class="empty">No pets found.</td></tr>
        <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>

<style>
.products-page {
  margin-left: 220px;
  padding: 20px;
  background: #f4f6f9;
  min-height: 100vh;
  width: calc(100% - 220px);
  box-sizing: border-box;
}
.products-page .content-wrapper {
  background: #fff;
  padding: 20px;
  border-radius: 10px;
  box-shadow: 0 3px 8px rgba(0,0,0,0.05);
}
.products-page h2 {
  margin-bottom: 20px;
  font-weight: bold;
  color: #333;
}
.actions-bar {
  display: flex;
  justify-content: space-between;
  margin-bottom: 15px;
}
.search-form input, .sort-form select {
  padding: 6px 10px;
  border: 1px solid #ddd;
  border-radius: 6px;
}
.search-form button {
  padding: 6px 14px;
  margin-left: 5px;
  border: none;
  border-radius: 6px;
  background: #28a745;
  color: #fff;
  cursor: pointer;
}
.search-form button:hover {
  background: #218838;
}
.table-container {
  overflow-x: auto;
}
.table-container table {
  width: 100%;
  border-collapse: collapse;
}
.table-container thead {
  background: #343a40;
  color: #fff;
}
.table-container th, .table-container td {
  padding: 10px;
  text-align: left;
  border-bottom: 1px solid #eee;
}
.table-container tbody tr:hover {
  background: #f9f9f9;
}
.badge {
  background: #28a745;
  color: #fff;
  padding: 3px 8px;
  border-radius: 12px;
  font-size: 0.85em;
}
.actions {
  display: flex;
  gap: 5px;
}
.btn {
  padding: 4px 10px;
  border-radius: 6px;
  font-size: 0.85em;
  text-decoration: none;
  color: #fff;
}
.btn.view { background: #17a2b8; }
.btn.view:hover { background: #138496; }
.btn.edit { background: #6c757d; cursor: not-allowed; }
.btn.delete { background: #dc3545; }
.btn.delete:hover { background: #c82333; }
.empty {
  text-align: center;
  color: #777;
  padding: 20px;
}
</style>
