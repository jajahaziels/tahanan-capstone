<?php
require_once '../session_auth.php';
require_once '../connection.php';

// Get admin info
$admin_name = $_SESSION['username'];

// Get statistics from database
$total_landlords = $conn->query("SELECT COUNT(*) as count FROM landlordtbl")->fetch_assoc()['count'];
$total_tenants = $conn->query("SELECT COUNT(*) as count FROM tenanttbl")->fetch_assoc()['count'];
$total_users = $total_landlords + $total_tenants;

// Fetch real listing statistics
$total_posts = $conn->query("SELECT COUNT(*) as count FROM listingtbl")->fetch_assoc()['count'];
$active_posts = $conn->query("SELECT COUNT(*) as count FROM listingtbl WHERE availability='available'")->fetch_assoc()['count'];
$rented_properties = $conn->query("SELECT COUNT(*) as count FROM listingtbl WHERE availability='occupied'")->fetch_assoc()['count'];

// Verification statistics
$pending_verification = $conn->query("SELECT COUNT(*) as count FROM landlordtbl WHERE verification_status='pending'")->fetch_assoc()['count'];
$verified_landlords = $conn->query("SELECT COUNT(*) as count FROM landlordtbl WHERE verification_status='verified'")->fetch_assoc()['count'];

// Recent listings (last 5)
$recent_listings = $conn->query("SELECT listingName, price, barangay, listingDate FROM listingtbl ORDER BY ID DESC LIMIT 5");

// Recent landlords (last 5)
$recent_landlords = $conn->query("SELECT firstName, lastName, email, created_at FROM landlordtbl ORDER BY ID DESC LIMIT 5");

// Recent tenants (last 5)
$recent_tenants = $conn->query("SELECT firstName, lastName, email, created_at FROM tenanttbl ORDER BY ID DESC LIMIT 5");
?>

<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Admin Dashboard - Tahanan</title>
  <link rel="stylesheet" href="homepage.css">
  <link rel="stylesheet" href="sidebar.css?v=<?= time(); ?>">
  <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
  <style>
    .dashboard-grid {
      display: grid;
      grid-template-columns: repeat(12, 1fr);
      gap: 20px;
      margin-bottom: 30px;
    }

    /* Stats Grid - Full Width */
    .stats-grid {
      grid-column: 1 / -1;
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
      gap: 20px;
      margin-bottom: 30px;
    }

    .stat-card {
      background: white;
      border-radius: 12px;
      padding: 25px;
      box-shadow: 0 2px 8px rgba(0,0,0,0.1);
      display: flex;
      align-items: center;
      gap: 20px;
      transition: all 0.3s;
      position: relative;
      overflow: hidden;
    }

    .stat-card::before {
      content: '';
      position: absolute;
      top: 0;
      left: 0;
      width: 4px;
      height: 100%;
      background: var(--card-color);
    }

    .stat-card:hover {
      transform: translateY(-5px);
      box-shadow: 0 8px 20px rgba(0,0,0,0.15);
    }

    .stat-card i {
      font-size: 48px;
      color: var(--card-color);
      opacity: 0.9;
    }

    .stat-card.users { --card-color: #667eea; }
    .stat-card.tenants { --card-color: #f093fb; }
    .stat-card.landlords { --card-color: #4facfe; }
    .stat-card.posts { --card-color: #43e97b; }
    .stat-card.active { --card-color: #fa709a; }
    .stat-card.rented { --card-color: #feca57; }

    .stat-info h3 {
      font-size: 32px;
      font-weight: 700;
      color: #333;
      margin: 0 0 5px 0;
    }

    .stat-info p {
      font-size: 14px;
      color: #666;
      margin: 0;
    }

    /* Quick Actions */
    .quick-actions {
      grid-column: 1 / 7;
      background: white;
      border-radius: 12px;
      padding: 25px;
      box-shadow: 0 2px 8px rgba(0,0,0,0.1);
    }

    .quick-actions h2 {
      margin: 0 0 20px 0;
      color: #333;
      font-size: 20px;
    }

    .action-buttons {
      display: grid;
      grid-template-columns: 1fr 1fr;
      gap: 15px;
    }

    .action-btn {
      padding: 15px;
      border: 2px solid #e0e0e0;
      border-radius: 10px;
      background: #f8f9fa;
      cursor: pointer;
      transition: all 0.3s;
      text-decoration: none;
      color: #333;
      display: flex;
      align-items: center;
      gap: 12px;
    }

    .action-btn:hover {
      border-color: #667eea;
      background: #f0f0ff;
      transform: translateX(5px);
    }

    .action-btn i {
      font-size: 28px;
      color: #667eea;
    }

    .action-btn-content h3 {
      margin: 0 0 5px 0;
      font-size: 15px;
      color: #333;
    }

    .action-btn-content p {
      margin: 0;
      font-size: 12px;
      color: #666;
    }

    /* Verification Status */
    .verification-status {
      grid-column: 7 / 13;
      background: white;
      border-radius: 12px;
      padding: 25px;
      box-shadow: 0 2px 8px rgba(0,0,0,0.1);
    }

    .verification-status h2 {
      margin: 0 0 20px 0;
      color: #333;
      font-size: 20px;
    }

    .verification-chart {
      position: relative;
      height: 200px;
      display: flex;
      align-items: center;
      justify-content: center;
    }

    .circular-progress {
      width: 160px;
      height: 160px;
      border-radius: 50%;
      background: conic-gradient(
        #43e97b 0deg <?= ($verified_landlords / max($total_landlords, 1) * 360) ?>deg,
        #feca57 <?= ($verified_landlords / max($total_landlords, 1) * 360) ?>deg <?= (($verified_landlords + $pending_verification) / max($total_landlords, 1) * 360) ?>deg,
        #f8f9fa <?= (($verified_landlords + $pending_verification) / max($total_landlords, 1) * 360) ?>deg
      );
      display: flex;
      align-items: center;
      justify-content: center;
      position: relative;
    }

    .circular-progress::before {
      content: '';
      width: 120px;
      height: 120px;
      background: white;
      border-radius: 50%;
      position: absolute;
    }

    .progress-text {
      position: relative;
      z-index: 1;
      text-align: center;
    }

    .progress-text h3 {
      font-size: 36px;
      margin: 0;
      color: #333;
    }

    .progress-text p {
      font-size: 12px;
      margin: 0;
      color: #666;
    }

    .verification-legend {
      display: flex;
      justify-content: center;
      gap: 20px;
      margin-top: 20px;
    }

    .legend-item {
      display: flex;
      align-items: center;
      gap: 8px;
      font-size: 14px;
    }

    .legend-color {
      width: 16px;
      height: 16px;
      border-radius: 4px;
    }

    /* Recent Activity */
    .recent-activity {
      grid-column: 1 / 9;
      background: white;
      border-radius: 12px;
      padding: 25px;
      box-shadow: 0 2px 8px rgba(0,0,0,0.1);
    }

    .recent-activity h2 {
      margin: 0 0 20px 0;
      color: #333;
      font-size: 20px;
      display: flex;
      align-items: center;
      gap: 10px;
    }

    .activity-tabs {
      display: flex;
      gap: 10px;
      margin-bottom: 20px;
      border-bottom: 2px solid #f0f0f0;
    }

    .activity-tab {
      padding: 10px 20px;
      border: none;
      background: transparent;
      border-bottom: 2px solid transparent;
      cursor: pointer;
      color: #666;
      font-size: 14px;
      font-weight: 500;
      transition: all 0.3s;
    }

    .activity-tab.active {
      color: #667eea;
      border-bottom-color: #667eea;
    }

    .activity-list {
      max-height: 350px;
      overflow-y: auto;
    }

    .activity-item {
      padding: 15px;
      border-left: 3px solid #667eea;
      background: #f8f9fa;
      margin-bottom: 10px;
      border-radius: 8px;
      transition: all 0.3s;
    }

    .activity-item:hover {
      background: #e9ecef;
      transform: translateX(5px);
    }

    .activity-item h4 {
      margin: 0 0 5px 0;
      font-size: 14px;
      color: #333;
    }

    .activity-item p {
      margin: 0;
      font-size: 12px;
      color: #666;
    }

    .activity-time {
      font-size: 11px;
      color: #999;
      margin-top: 5px;
    }

    /* System Health */
    .system-health {
      grid-column: 9 / 13;
      background: white;
      border-radius: 12px;
      padding: 25px;
      box-shadow: 0 2px 8px rgba(0,0,0,0.1);
    }

    .system-health h2 {
      margin: 0 0 20px 0;
      color: #333;
      font-size: 20px;
    }

    .health-item {
      display: flex;
      justify-content: space-between;
      align-items: center;
      padding: 15px 0;
      border-bottom: 1px solid #f0f0f0;
    }

    .health-item:last-child {
      border-bottom: none;
    }

    .health-label {
      font-size: 14px;
      color: #666;
    }

    .health-status {
      display: flex;
      align-items: center;
      gap: 8px;
      font-size: 14px;
      font-weight: 600;
    }

    .status-dot {
      width: 10px;
      height: 10px;
      border-radius: 50%;
    }

    .status-good { color: #43e97b; }
    .status-good .status-dot { background: #43e97b; }

    .status-warning { color: #feca57; }
    .status-warning .status-dot { background: #feca57; }

    @media (max-width: 1200px) {
      .quick-actions,
      .verification-status,
      .recent-activity,
      .system-health {
        grid-column: 1 / -1;
      }
    }

    @media (max-width: 768px) {
      .action-buttons {
        grid-template-columns: 1fr;
      }
    }
  </style>
</head>

<body>

  <!-- sidebar -->
  <nav class="sidebar">
    <header>
      <div class="image-text">
        <span class="image">
          <img src="logo.png" alt="logo">
        </span>

        <div class="text header-text">
          <span class="name">Tahanan</span>
        </div>

      </div>

      <i class='bx bx-chevron-right toggle'></i> 
    </header>

    <div class="menu-bar">
      <div class="menu">
        <ul class="menu-links">
          <li class="nav-link">
            <a href="homepage.php">
              <i class='bx bx-home icon'></i> 
              <span class="text nav-text">Home</span>
            </a>
          </li>
          <li class="nav-link">
            <a href="accounts.php">
              <i class='bx bx-user icon'></i>  
              <span class="text nav-text">Accounts</span>
            </a>
          </li>
          <li class="nav-link">
            <a href="report.php">
              <i class='bx bx-alert-circle icon'></i>  
              <span class="text nav-text">Reports</span>
            </a>
          </li>
          <li class="nav-link">
            <a href="listing.php">
              <i class='bx bx-list-ul icon'></i>   
              <span class="text nav-text">Listing</span>
            </a>
          </li>
          <li class="nav-link">
            <a href="verify.php">
              <i class='bx bx-check-circle icon'></i>  
              <span class="text nav-text">Verify Landlord</span>
            </a>
          </li>
        </ul>
      </div>

      <div class="bottom-content">
        <li class="">
            <a href="logout.php">
              <i class='bx bx-log-out icon'></i>   
              <span class="text nav-text">Logout</span>
            </a>
          </li>
          <li class="admin-info" style="padding: 10px; margin-top: 10px; border-top: 1px solid #ddd;">
            <small class="text nav-text" style="opacity: 0.7;">
              Logged in as:<br>
              <strong><?= htmlspecialchars($admin_name) ?></strong>
            </small>
          </li>
      </div>
    </div>
  </nav>
  <!-- sidebar -->

  <main class="content">
    <header class="page-header">
      <h1>👋 Welcome back, <?= htmlspecialchars(explode(' ', $admin_name)[0]) ?>!</h1>
      <p>Here's what's happening with your platform today • <?= date('l, F j, Y') ?></p>
    </header>

    <div class="dashboard-grid">
      <!-- Stats Grid -->
      <section class="stats-grid">
        <div class="stat-card users">
          <i class='bx bx-user'></i>
          <div class="stat-info">
            <h3><?= $total_users ?></h3>
            <p>Total Users</p>
          </div>
        </div>

        <div class="stat-card tenants">
          <i class='bx bx-home-alt'></i>
          <div class="stat-info">
            <h3><?= $total_tenants ?></h3>
            <p>Total Tenants</p>
          </div>
        </div>

        <div class="stat-card landlords">
          <i class='bx bx-building-house'></i>
          <div class="stat-info">
            <h3><?= $total_landlords ?></h3>
            <p>Total Landlords</p>
          </div>
        </div>

        <div class="stat-card posts">
          <i class='bx bx-file'></i>
          <div class="stat-info">
            <h3><?= $total_posts ?></h3>
            <p>Total Posts</p>
          </div>
        </div>

        <div class="stat-card active">
          <i class='bx bx-check-circle'></i>
          <div class="stat-info">
            <h3><?= $active_posts ?></h3>
            <p>Available Properties</p>
          </div>
        </div>

        <div class="stat-card rented">
          <i class='bx bx-key'></i>
          <div class="stat-info">
            <h3><?= $rented_properties ?></h3>
            <p>Occupied Properties</p>
          </div>
        </div>
      </section>

      <!-- Quick Actions -->
      <section class="quick-actions">
        <h2><i class='bx bx-zap'></i> Quick Actions</h2>
        <div class="action-buttons">
          <a href="verify.php" class="action-btn">
            <i class='bx bx-check-shield'></i>
            <div class="action-btn-content">
              <h3>Verify Landlords</h3>
              <p><?= $pending_verification ?> pending requests</p>
            </div>
          </a>
          <a href="admin.php" class="action-btn">
            <i class='bx bx-user-plus'></i>
            <div class="action-btn-content">
              <h3>Manage Users</h3>
              <p>View all accounts</p>
            </div>
          </a>
          <a href="listing.php" class="action-btn">
            <i class='bx bx-building'></i>
            <div class="action-btn-content">
              <h3>View Listings</h3>
              <p><?= $total_posts ?> total properties</p>
            </div>
          </a>
          <a href="report.php" class="action-btn">
            <i class='bx bx-error-circle'></i>
            <div class="action-btn-content">
              <h3>Check Reports</h3>
              <p>Review user feedback</p>
            </div>
          </a>
        </div>
      </section>

      <!-- Verification Status -->
      <section class="verification-status">
        <h2><i class='bx bx-pie-chart-alt'></i> Verification Status</h2>
        <div class="verification-chart">
          <div class="circular-progress">
            <div class="progress-text">
              <h3><?= $total_landlords > 0 ? round(($verified_landlords / $total_landlords) * 100) : 0 ?>%</h3>
              <p>Verified</p>
            </div>
          </div>
        </div>
        <div class="verification-legend">
          <div class="legend-item">
            <div class="legend-color" style="background: #43e97b;"></div>
            <span>Verified (<?= $verified_landlords ?>)</span>
          </div>
          <div class="legend-item">
            <div class="legend-color" style="background: #feca57;"></div>
            <span>Pending (<?= $pending_verification ?>)</span>
          </div>
        </div>
      </section>

      <!-- Recent Activity -->
      <section class="recent-activity">
        <h2><i class='bx bx-time-five'></i> Recent Activity</h2>
        <div class="activity-tabs">
          <button class="activity-tab active" onclick="showActivity('listings')">Listings</button>
          <button class="activity-tab" onclick="showActivity('landlords')">Landlords</button>
          <button class="activity-tab" onclick="showActivity('tenants')">Tenants</button>
        </div>

        <!-- Listings Activity -->
        <div id="listings-activity" class="activity-list">
          <?php if ($recent_listings->num_rows > 0): ?>
            <?php while($listing = $recent_listings->fetch_assoc()): ?>
              <div class="activity-item">
                <h4><?= htmlspecialchars($listing['listingName']) ?></h4>
                <p>₱<?= number_format($listing['price']) ?>/month • <?= htmlspecialchars($listing['barangay']) ?></p>
                <div class="activity-time"><?= date('M d, Y', strtotime($listing['listingDate'])) ?></div>
              </div>
            <?php endwhile; ?>
          <?php else: ?>
            <p style="text-align: center; color: #999; padding: 40px;">No recent listings</p>
          <?php endif; ?>
        </div>

        <!-- Landlords Activity -->
        <div id="landlords-activity" class="activity-list" style="display: none;">
          <?php if ($recent_landlords->num_rows > 0): ?>
            <?php while($landlord = $recent_landlords->fetch_assoc()): ?>
              <div class="activity-item">
                <h4><?= htmlspecialchars($landlord['firstName'] . ' ' . $landlord['lastName']) ?></h4>
                <p><?= htmlspecialchars($landlord['email']) ?></p>
                <div class="activity-time">Joined <?= date('M d, Y', strtotime($landlord['created_at'])) ?></div>
              </div>
            <?php endwhile; ?>
          <?php else: ?>
            <p style="text-align: center; color: #999; padding: 40px;">No recent landlords</p>
          <?php endif; ?>
        </div>

        <!-- Tenants Activity -->
        <div id="tenants-activity" class="activity-list" style="display: none;">
          <?php if ($recent_tenants->num_rows > 0): ?>
            <?php while($tenant = $recent_tenants->fetch_assoc()): ?>
              <div class="activity-item">
                <h4><?= htmlspecialchars($tenant['firstName'] . ' ' . $tenant['lastName']) ?></h4>
                <p><?= htmlspecialchars($tenant['email']) ?></p>
                <div class="activity-time">Joined <?= date('M d, Y', strtotime($tenant['created_at'])) ?></div>
              </div>
            <?php endwhile; ?>
          <?php else: ?>
            <p style="text-align: center; color: #999; padding: 40px;">No recent tenants</p>
          <?php endif; ?>
        </div>
      </section>

      <!-- System Health -->
      <section class="system-health">
        <h2><i class='bx bx-heart'></i> System Health</h2>
        <div class="health-item">
          <span class="health-label">Platform Status</span>
          <span class="health-status status-good">
            <span class="status-dot"></span>
            Operational
          </span>
        </div>
        <div class="health-item">
          <span class="health-label">Active Users</span>
          <span class="health-status status-good">
            <span class="status-dot"></span>
            <?= $total_users ?>
          </span>
        </div>
        <div class="health-item">
          <span class="health-label">Pending Reviews</span>
          <span class="health-status <?= $pending_verification > 5 ? 'status-warning' : 'status-good' ?>">
            <span class="status-dot"></span>
            <?= $pending_verification ?>
          </span>
        </div>
        <div class="health-item">
          <span class="health-label">Active Listings</span>
          <span class="health-status status-good">
            <span class="status-dot"></span>
            <?= $active_posts ?>
          </span>
        </div>
      </section>
    </div>
  </main>

<script src="sidebar.js"></script>
<script>
  function showActivity(type) {
    // Hide all activities
    document.querySelectorAll('.activity-list').forEach(list => {
      list.style.display = 'none';
    });
    
    // Remove active class from all tabs
    document.querySelectorAll('.activity-tab').forEach(tab => {
      tab.classList.remove('active');
    });
    
    // Show selected activity
    document.getElementById(type + '-activity').style.display = 'block';
    
    // Add active class to clicked tab
    event.target.classList.add('active');
  }
</script>

</body>
  
</html>