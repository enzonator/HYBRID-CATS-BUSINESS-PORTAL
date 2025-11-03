<?php
session_start();
include '../config/db.php';

// ✅ Restrict access to admin only
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../public/login.php");
    exit;
}

include __DIR__ . "/../includes/admin-sidebar.php"; // Sidebar
?>

<?php if (isset($_GET['success']) && $_GET['success'] === 'deleted'): ?>
  <div class="alert alert-success">User deleted successfully.</div>
<?php elseif (isset($_GET['error'])): ?>
  <?php if ($_GET['error'] === 'invalid'): ?>
    <div class="alert alert-danger">Invalid user ID.</div>
  <?php elseif ($_GET['error'] === 'cannot_delete_admin'): ?>
    <div class="alert alert-warning">You cannot delete the main admin.</div>
  <?php else: ?>
    <div class="alert alert-danger">Failed to delete user.</div>
  <?php endif; ?>
<?php endif; ?>


<!-- Users Content -->
<div class="users-page">
  <div class="users-container">
    <h2 class="users-title">👥 Manage Users</h2>

    <?php if (isset($_GET['msg'])): ?>
      <div class="users-alert">
        <?php echo htmlspecialchars($_GET['msg']); ?>
      </div>
    <?php endif; ?>

    <div class="users-card">
      <table class="users-table">
        <thead>
          <tr>
            <th>ID</th>
            <th>Username</th>
            <th>Email</th>
            <th>Role</th>
            <th>Date Registered</th>
            <th>Actions</th>
          </tr>
        </thead>
        <tbody>
        <?php
        $result = $conn->query("SELECT id, username, email, role, created_at FROM users ORDER BY id DESC");

        if ($result && $result->num_rows > 0):
            while ($row = $result->fetch_assoc()):
        ?>
          <tr>
            <td><?php echo $row['id']; ?></td>
            <td><b><?php echo htmlspecialchars($row['username']); ?></b></td>
            <td><?php echo htmlspecialchars($row['email']); ?></td>
            <td>
              <span class="role-badge <?php echo ($row['role'] === 'admin') ? 'admin' : 'customer'; ?>">
                <?php echo ucfirst($row['role']); ?>
              </span>
            </td>
            <td><?php echo $row['created_at']; ?></td>
            <td>
              <a href="edit-user.php?id=<?php echo $row['id']; ?>" class="btn-edit">✏️ Edit</a>

              <a class="btn-delete"
                href="delete-user.php?id=<?= (int)$row['id'] ?>"
                onclick="return confirm('Are you sure you want to delete this user?');">
                Delete
              </a>



              <!-- <a href="delete-user.php?id=<?php echo $row['id']; ?>" 
                 class="btn-delete"
                 onclick="return confirm('Are you sure you want to delete this user?');">
                 🗑 Delete
              </a>  -->
            </td>
          </tr>
        <?php
            endwhile;
        else:
            echo "<tr><td colspan='6' class='empty-msg'>No users found</td></tr>";
        endif;
        ?>
        </tbody>
      </table>
    </div>
  </div>
</div>

<!-- Scoped CSS for Users Page -->
<style>
  .users-page {
  margin-left: 220px;   /* space for sidebar */
  padding: 20px;
  background: #f4f6f9;
  min-height: 100vh;
  font-family: Arial, sans-serif;

  /* ✅ Make it fill the rest of the screen */
  width: calc(100% - 220px);
  box-sizing: border-box;
}
  .users-title {
    margin-bottom: 20px;
    font-size: 22px;
    font-weight: bold;
    color: #333;
  }

  .users-alert {
    background: #d1e7dd;
    color: #0f5132;
    padding: 10px;
    border-radius: 5px;
    margin-bottom: 15px;
  }

  /* 🔥 Make card & table full width */
  .users-card {
    background: #fff;
    padding: 15px;
    border-radius: 10px;
    box-shadow: 0 2px 6px rgba(0,0,0,0.1);
    width: 100%;  /* Full width */
    box-sizing: border-box;
  }

  .users-table {
    width: 100%;
    border-collapse: collapse;
  }

  .users-table th {
    background: #333;
    color: #fff;
    padding: 12px;
    text-align: left;
  }

  .users-table td {
    padding: 12px;
    border-bottom: 1px solid #eee;
  }

  .users-table tr:hover {
    background: #f9f9f9;
  }

  .role-badge {
    padding: 4px 8px;
    border-radius: 6px;
    font-size: 12px;
    font-weight: bold;
    color: #fff;
  }
  .role-badge.admin {
    background: #dc3545;
  }
  .role-badge.customer {
    background: #6c757d;
  }

  .btn-edit, .btn-delete {
    display: inline-block;
    padding: 6px 12px;
    margin-right: 5px;
    font-size: 13px;
    border-radius: 5px;
    text-decoration: none;
    transition: 0.2s;
  }

  .btn-edit {
    background: #0d6efd;
    color: #fff;
  }
  .btn-edit:hover {
    background: #0b5ed7;
  }

  .btn-delete {
    background: #dc3545;
    color: #fff;
  }
  .btn-delete:hover {
    background: #bb2d3b;
  }

  .empty-msg {
    text-align: center;
    color: #888;
    padding: 20px 0;
  }
</style>

