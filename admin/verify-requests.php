<?php
session_start();
require_once "../config/db.php";

// ✅ Restrict access to admin only
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../public/login.php");
    exit;
}

include __DIR__ . "/../includes/admin-sidebar.php"; // Sidebar
?>

<!-- Handle success/error messages -->
<?php if (isset($_GET['status'])): ?>
  <?php if ($_GET['status'] === 'approved'): ?>
    <div class="alert alert-success">✅ Verification approved successfully!</div>
  <?php elseif ($_GET['status'] === 'rejected'): ?>
    <div class="alert alert-warning">❌ Verification rejected successfully.</div>
  <?php elseif ($_GET['status'] === 'error'): ?>
    <div class="alert alert-danger">⚠️ Something went wrong.</div>
  <?php endif; ?>
<?php endif; ?>

<!-- Verification Requests Page -->
<div class="verify-page">
  <div class="verify-container">
    <h2 class="verify-title">🧾 Verification Requests</h2>

    <div class="verify-card">
      <table class="verify-table">
        <thead>
          <tr>
            <th>ID</th>
            <th>User</th>
            <th>ID Image</th>
            <th>Status</th>
            <th>Date Submitted</th>
            <th>Action</th>
          </tr>
        </thead>
        <tbody>
          <?php
          $sql = "SELECT v.id, v.user_id, v.id_image, v.status, v.created_at, u.username, u.email
                  FROM verifications v
                  JOIN users u ON v.user_id = u.id
                  ORDER BY v.created_at DESC";
          $result = $conn->query($sql);

          if ($result && $result->num_rows > 0):
              while ($row = $result->fetch_assoc()):
          ?>
            <tr>
              <td><?= $row['id'] ?></td>
              <td>
                <b><?= htmlspecialchars($row['username']) ?></b><br>
                <small><?= htmlspecialchars($row['email']) ?></small>
              </td>
              <td>
                <?php if (!empty($row['id_image'])): ?>
                  <a href="../uploads/verifications/<?= htmlspecialchars($row['id_image']) ?>" target="_blank">📎 View File</a>
                <?php else: ?>
                  <em>No file</em>
                <?php endif; ?>
              </td>
              <td>
                <span class="status-badge <?= strtolower($row['status']) ?>">
                  <?= htmlspecialchars($row['status']) ?>
                </span>
              </td>
              <td><?= $row['created_at'] ?></td>
              <td>
                <?php if ($row['status'] === 'Pending'): ?>
                  <a href="verify-action.php?id=<?= $row['id'] ?>&action=approve" class="btn-approve"
                     onclick="return confirm('Approve this verification request?');">✅ Approve</a>
                  <a href="verify-action.php?id=<?= $row['id'] ?>&action=reject" class="btn-reject"
                     onclick="return confirm('Reject this verification request?');">❌ Reject</a>
                <?php else: ?>
                  <em>N/A</em>
                <?php endif; ?>
              </td>
            </tr>
          <?php
              endwhile;
          else:
              echo "<tr><td colspan='6' class='empty-msg'>No verification requests found.</td></tr>";
          endif;
          ?>
        </tbody>
      </table>
    </div>
  </div>
</div>

<!-- Scoped CSS -->
<style>
.verify-page {
  margin-left: 220px;
  padding: 20px;
  background: #f4f6f9;
  min-height: 100vh;
  font-family: Arial, sans-serif;
  width: calc(100% - 220px);
  box-sizing: border-box;
}

.verify-title {
  font-size: 22px;
  font-weight: bold;
  margin-bottom: 20px;
  color: #333;
}

.verify-card {
  background: #fff;
  padding: 15px;
  border-radius: 10px;
  box-shadow: 0 2px 6px rgba(0,0,0,0.1);
  width: 100%;
}

.verify-table {
  width: 100%;
  border-collapse: collapse;
}

.verify-table th {
  background: #333;
  color: #fff;
  padding: 12px;
  text-align: left;
}

.verify-table td {
  padding: 12px;
  border-bottom: 1px solid #eee;
}

.status-badge {
  padding: 5px 8px;
  border-radius: 6px;
  font-size: 13px;
  font-weight: bold;
  color: #fff;
}

.status-badge.pending { background: #ffc107; color: #000; }
.status-badge.approved { background: #28a745; }
.status-badge.rejected { background: #dc3545; }

.btn-approve, .btn-reject {
  display: inline-block;
  padding: 6px 12px;
  margin-right: 5px;
  font-size: 13px;
  border-radius: 5px;
  text-decoration: none;
  transition: 0.2s;
  font-weight: bold;
}

.btn-approve { background: #28a745; color: #fff; }
.btn-approve:hover { background: #218838; }

.btn-reject { background: #dc3545; color: #fff; }
.btn-reject:hover { background: #bb2d3b; }

.empty-msg {
  text-align: center;
  color: #777;
  padding: 20px 0;
}
.alert {
  margin-left: 220px;
  padding: 12px 20px;
  background: #e7f3fe;
  border-left: 5px solid #0b5ed7;
  margin-bottom: 10px;
}
</style>
