<!-- sidebar.php -->
<div class="sidebar">
  <h2 style="padding-left:20px;">Dashboard</h2>
  <a href="products.php">Manage Pets</a>
  <a href="orders.php">View Orders</a>
  <a href="users.php">Manage Users</a>
  <a href="verify-requests.php">Verify Requests</a>
  <a href="reports.php">Reports</a>
  <a href="charts.php">Charts</a>

  <!-- Logout Button at the Bottom -->
  <div class="logout-container">
    <a href="/catshop/public/logout.php" class="logout-btn">🚪 Logout</a>
  </div>
</div>

<style>
  body {
    margin: 0;
    font-family: Arial, sans-serif;
    display: flex;
  }
  .sidebar {
    width: 220px;
    background: linear-gradient(to bottom, #28a745, #1d7a34);
    color: white;
    height: 100vh;
    padding-top: 20px;
    position: fixed;
  }
  .sidebar a {
    display: block;
    padding: 12px 20px;
    text-decoration: none;
    color: white;
    transition: background 0.2s;
  }
  .sidebar a:hover {
    background: rgba(255, 255, 255, 0.2);
  }

  .content {
    margin-left: 220px;
    padding: 20px;
    flex-grow: 1;
  }

  /* Logout button styling only */
  .logout-container {
    position: absolute;
    bottom: 20px;
    width: 100%;
    text-align: center;
  }
  .logout-btn {
    display: block;
    width: 80%;
    margin: 0 auto;
    padding: 12px;
    background: #dc3545;
    color: white;
    text-decoration: none;
    border-radius: 8px;
    font-weight: bold;
    text-align: center;
    transition: all 0.3s ease;
    box-shadow: 0px 4px 6px rgba(0,0,0,0.2);
  }
  .logout-btn:hover {
    background: #bb2d3b;
    transform: scale(1.05);
    box-shadow: 0px 6px 10px rgba(0,0,0,0.3);
  }
</style>
