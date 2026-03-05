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
  <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
  
  <style>
    @import url('https://fonts.googleapis.com/css2?family=Montserrat:wght@400;500;600;700&display=swap');

    * {
      margin: 0;
      padding: 0;
      font-family: 'Montserrat', sans-serif;
      box-sizing: border-box;
    }

    :root {
      --body-color: #0f1419;
      --sidebar-color: #1a1d29;
      --sidebar-hover: #252938;
      --primary-color: rgb(141, 11, 65);
      --primary-hover: rgb(115, 9, 53);
      --text-color: #e4e6eb;
      --text-muted: #8b92a7;
      --border-color: #2d3142;
      --card-bg: #1a1d29;
      --transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    }

    body {
      min-height: 100vh;
      background: var(--body-color);
      overflow-x: hidden;
    }

    /* ========== SIDEBAR STYLES ========== */
    .sidebar {
      position: fixed;
      top: 0;
      left: 0;
      height: 100vh;
      width: 260px;
      background: var(--sidebar-color);
      padding: 0;
      transition: var(--transition);
      z-index: 1000;
      box-shadow: 2px 0 10px rgba(0, 0, 0, 0.1);
    }

    .sidebar.collapsed { width: 75px; }

    .sidebar header {
      padding: 20px 16px;
      border-bottom: 1px solid var(--border-color);
      background: linear-gradient(135deg, #1e2230 0%, #1a1d29 100%);
    }

    .sidebar .image-text {
      display: flex;
      align-items: center;
      gap: 12px;
    }

    .sidebar .image-text img {
      width: 42px;
      height: 42px;
      border-radius: 10px;
      object-fit: cover;
      border: 2px solid var(--primary-color);
    }

    .sidebar .header-text {
      display: flex;
      flex-direction: column;
      gap: 2px;
      opacity: 1;
      transition: var(--transition);
    }

    .sidebar.collapsed .header-text {
      opacity: 0;
      width: 0;
      overflow: hidden;
    }

    .header-text .name {
      font-size: 16px;
      font-weight: 600;
      color: var(--text-color);
      white-space: nowrap;
    }

    .header-text .role {
      font-size: 12px;
      color: var(--text-muted);
    }

    .sidebar header .toggle {
      position: absolute;
      top: 26px;
      right: -14px;
      height: 28px;
      width: 28px;
      background: var(--primary-color);
      display: flex;
      align-items: center;
      justify-content: center;
      border-radius: 50%;
      color: white;
      font-size: 16px;
      cursor: pointer;
      transition: var(--transition);
      box-shadow: 0 2px 8px rgba(141, 11, 65, 0.4);
    }

    .sidebar header .toggle:hover {
      background: var(--primary-hover);
      transform: scale(1.1);
    }

    .sidebar.collapsed header .toggle {
      transform: rotate(180deg);
    }

    .sidebar .menu-bar {
      height: calc(100% - 82px);
      display: flex;
      flex-direction: column;
      padding: 16px 0;
      overflow-y: auto;
      overflow-x: hidden;
    }

    .sidebar .menu-bar::-webkit-scrollbar { width: 4px; }
    .sidebar .menu-bar::-webkit-scrollbar-thumb {
      background: var(--border-color);
      border-radius: 10px;
    }

    .sidebar .menu { padding: 0 12px; }
    .sidebar .menu-links { padding: 0; margin: 0; }
    .sidebar li { list-style: none; margin: 4px 0; }

    .sidebar li a {
      display: flex;
      align-items: center;
      height: 48px;
      padding: 0 14px;
      text-decoration: none;
      border-radius: 10px;
      transition: var(--transition);
      position: relative;
    }

    .sidebar li a::before {
      content: '';
      position: absolute;
      left: 0;
      top: 0;
      height: 100%;
      width: 3px;
      background: var(--primary-color);
      transform: scaleY(0);
      transition: transform 0.2s;
    }

    .sidebar li a:hover { background: var(--sidebar-hover); }
    .sidebar li a:hover::before { transform: scaleY(1); }

    .sidebar li a.active {
      background: linear-gradient(90deg, rgba(141, 11, 65, 0.15) 0%, rgba(141, 11, 65, 0.05) 100%);
    }

    .sidebar li a.active::before { transform: scaleY(1); }

    .sidebar li .icon {
      min-width: 45px;
      display: flex;
      align-items: center;
      justify-content: flex-start;
      font-size: 20px;
      color: var(--text-muted);
      transition: var(--transition);
    }

    .sidebar.collapsed li .icon {
      justify-content: center;
      min-width: 100%;
    }

    .sidebar li a:hover .icon,
    .sidebar li a.active .icon {
      color: var(--primary-color);
    }

    .sidebar .text {
      font-size: 14px;
      font-weight: 500;
      color: var(--text-color);
      white-space: nowrap;
      opacity: 1;
      transition: var(--transition);
    }

    .sidebar.collapsed .text {
      opacity: 0;
      width: 0;
    }

    .menu-section-title {
      font-size: 11px;
      font-weight: 600;
      color: var(--text-muted);
      text-transform: uppercase;
      letter-spacing: 1px;
      padding: 20px 14px 8px;
      transition: var(--transition);
    }

    .sidebar.collapsed .menu-section-title {
      opacity: 0;
      height: 0;
      padding: 0;
      overflow: hidden;
    }

    .sidebar .bottom-content {
      margin-top: auto;
      padding: 16px 12px;
      border-top: 1px solid var(--border-color);
    }

    .user-info {
      display: flex;
      align-items: center;
      gap: 12px;
      padding: 12px 14px;
      background: var(--sidebar-hover);
      border-radius: 10px;
      transition: var(--transition);
      cursor: pointer;
    }

    .user-info .user-avatar {
      width: 36px;
      height: 36px;
      border-radius: 50%;
      object-fit: cover;
      border: 2px solid var(--primary-color);
    }

    .user-info .user-details {
      flex: 1;
      opacity: 1;
      transition: var(--transition);
    }

    .sidebar.collapsed .user-info .user-details {
      opacity: 0;
      width: 0;
      overflow: hidden;
    }

    .user-info .user-name {
      font-size: 13px;
      font-weight: 600;
      color: var(--text-color);
      display: block;
      line-height: 1.3;
    }

    .user-info .user-status {
      font-size: 11px;
      color: var(--text-muted);
    }

    .sidebar .badge {
      position: absolute;
      right: 14px;
      background: #ef4444;
      color: white;
      font-size: 10px;
      font-weight: 600;
      padding: 2px 6px;
      border-radius: 10px;
      min-width: 18px;
      text-align: center;
    }

    .sidebar.collapsed .badge {
      right: 8px;
      top: 8px;
    }

    .sidebar.collapsed li a:hover::after {
      content: attr(data-tooltip);
      position: absolute;
      left: 70px;
      top: 50%;
      transform: translateY(-50%);
      background: var(--sidebar-color);
      color: var(--text-color);
      padding: 8px 12px;
      border-radius: 6px;
      font-size: 13px;
      white-space: nowrap;
      box-shadow: 0 4px 12px rgba(0, 0, 0, 0.3);
      z-index: 1001;
    }

    /* ========== MAIN CONTENT ========== */
    .content {
      margin-left: 260px;
      padding: 30px;
      transition: var(--transition);
      min-height: 100vh;
    }

    .sidebar.collapsed ~ .content {
      margin-left: 75px;
    }

    .page-header {
      margin-bottom: 30px;
    }

    .page-header h1 {
      font-size: 28px;
      font-weight: 700;
      color: #ffffff;
      margin-bottom: 8px;
    }

    .page-header p {
      color: var(--text-muted);
      font-size: 14px;
    }

    /* ========== DASHBOARD GRID ========== */
    .dashboard-grid {
      display: grid;
      grid-template-columns: repeat(12, 1fr);
      gap: 20px;
    }

    /* ========== STATS GRID ========== */
    .stats-grid {
      grid-column: 1 / -1;
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
      gap: 20px;
      margin-bottom: 30px;
    }

    .stat-card {
      background: var(--card-bg);
      border-radius: 12px;
      padding: 24px;
      box-shadow: 0 2px 8px rgba(0,0,0,0.3);
      display: flex;
      align-items: center;
      gap: 20px;
      transition: all 0.3s;
      position: relative;
      overflow: hidden;
      border: 1px solid var(--border-color);
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
      box-shadow: 0 8px 20px rgba(0,0,0,0.4);
      border-color: var(--card-color);
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
      color: #ffffff;
      margin: 0 0 5px 0;
    }

    .stat-info p {
      font-size: 14px;
      color: var(--text-muted);
      margin: 0;
    }

    /* ========== QUICK ACTIONS ========== */
    .quick-actions {
      grid-column: 1 / 7;
      background: var(--card-bg);
      border-radius: 12px;
      padding: 25px;
      box-shadow: 0 2px 8px rgba(0,0,0,0.3);
      border: 1px solid var(--border-color);
    }

    .quick-actions h2 {
      margin: 0 0 20px 0;
      color: #ffffff;
      font-size: 20px;
      display: flex;
      align-items: center;
      gap: 10px;
    }

    .action-buttons {
      display: grid;
      grid-template-columns: 1fr 1fr;
      gap: 15px;
    }

    .action-btn {
      padding: 15px;
      border: 2px solid var(--border-color);
      border-radius: 10px;
      background: var(--sidebar-hover);
      cursor: pointer;
      transition: all 0.3s;
      text-decoration: none;
      color: var(--text-color);
      display: flex;
      align-items: center;
      gap: 12px;
    }

    .action-btn:hover {
      border-color: var(--primary-color);
      background: rgba(141, 11, 65, 0.1);
      transform: translateX(5px);
    }

    .action-btn i {
      font-size: 28px;
      color: var(--primary-color);
    }

    .action-btn-content h3 {
      margin: 0 0 5px 0;
      font-size: 15px;
      color: #ffffff;
    }

    .action-btn-content p {
      margin: 0;
      font-size: 12px;
      color: var(--text-muted);
    }

    /* ========== VERIFICATION STATUS ========== */
    .verification-status {
      grid-column: 7 / 13;
      background: var(--card-bg);
      border-radius: 12px;
      padding: 25px;
      box-shadow: 0 2px 8px rgba(0,0,0,0.3);
      border: 1px solid var(--border-color);
    }

    .verification-status h2 {
      margin: 0 0 20px 0;
      color: #ffffff;
      font-size: 20px;
      display: flex;
      align-items: center;
      gap: 10px;
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
        var(--border-color) <?= (($verified_landlords + $pending_verification) / max($total_landlords, 1) * 360) ?>deg
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
      background: var(--card-bg);
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
      color: #ffffff;
    }

    .progress-text p {
      font-size: 12px;
      margin: 0;
      color: var(--text-muted);
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
      color: var(--text-color);
    }

    .legend-color {
      width: 16px;
      height: 16px;
      border-radius: 4px;
    }

    /* ========== RECENT ACTIVITY ========== */
    .recent-activity {
      grid-column: 1 / 9;
      background: var(--card-bg);
      border-radius: 12px;
      padding: 25px;
      box-shadow: 0 2px 8px rgba(0,0,0,0.3);
      border: 1px solid var(--border-color);
    }

    .recent-activity h2 {
      margin: 0 0 20px 0;
      color: #ffffff;
      font-size: 20px;
      display: flex;
      align-items: center;
      gap: 10px;
    }

    .activity-tabs {
      display: flex;
      gap: 10px;
      margin-bottom: 20px;
      border-bottom: 2px solid var(--border-color);
    }

    .activity-tab {
      padding: 10px 20px;
      border: none;
      background: transparent;
      border-bottom: 2px solid transparent;
      cursor: pointer;
      color: var(--text-muted);
      font-size: 14px;
      font-weight: 500;
      transition: all 0.3s;
    }

    .activity-tab.active {
      color: var(--primary-color);
      border-bottom-color: var(--primary-color);
    }

    .activity-list {
      max-height: 350px;
      overflow-y: auto;
    }

    .activity-list::-webkit-scrollbar { width: 6px; }
    .activity-list::-webkit-scrollbar-thumb {
      background: var(--border-color);
      border-radius: 10px;
    }

    .activity-item {
      padding: 15px;
      border-left: 3px solid var(--primary-color);
      background: var(--sidebar-hover);
      margin-bottom: 10px;
      border-radius: 8px;
      transition: all 0.3s;
    }

    .activity-item:hover {
      background: var(--border-color);
      transform: translateX(5px);
    }

    .activity-item h4 {
      margin: 0 0 5px 0;
      font-size: 14px;
      color: #ffffff;
    }

    .activity-item p {
      margin: 0;
      font-size: 12px;
      color: var(--text-muted);
    }

    .activity-time {
      font-size: 11px;
      color: #6c757d;
      margin-top: 5px;
    }

    /* ========== SYSTEM HEALTH ========== */
    .system-health {
      grid-column: 9 / 13;
      background: var(--card-bg);
      border-radius: 12px;
      padding: 25px;
      box-shadow: 0 2px 8px rgba(0,0,0,0.3);
      border: 1px solid var(--border-color);
    }

    .system-health h2 {
      margin: 0 0 20px 0;
      color: #ffffff;
      font-size: 20px;
      display: flex;
      align-items: center;
      gap: 10px;
    }

    .health-item {
      display: flex;
      justify-content: space-between;
      align-items: center;
      padding: 15px 0;
      border-bottom: 1px solid var(--border-color);
    }

    .health-item:last-child {
      border-bottom: none;
    }

    .health-label {
      font-size: 14px;
      color: var(--text-muted);
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

    /* ========== RESPONSIVE ========== */
    @media (max-width: 1200px) {
      .quick-actions,
      .verification-status,
      .recent-activity,
      .system-health {
        grid-column: 1 / -1;
      }
    }

    @media (max-width: 768px) {
      .sidebar {
        transform: translateX(-100%);
      }

      .sidebar.active {
        transform: translateX(0);
      }

      .content {
        margin-left: 0;
      }

      .sidebar.collapsed ~ .content {
        margin-left: 0;
      }

      .action-buttons {
        grid-template-columns: 1fr;
      }
    }
  </style>
</head>

<body>

  <!-- SIDEBAR -->
  <nav class="sidebar">
    <header>
      <div class="image-text">
        <span class="image">
          <img src="https://via.placeholder.com/42" alt="Tahanan">
        </span>
        <div class="header-text">
          <span class="name">Tahanan</span>
          <span class="role">Admin Panel</span>
        </div>
      </div>
      <i class='bx bx-chevron-right toggle'></i>
    </header>

    <div class="menu-bar">
      <div class="menu">
        <div class="menu-section-title">Main</div>
        <ul class="menu-links">
          <li>
            <a href="dashboard.php" class="active" data-tooltip="Home">
              <i class='bx bx-home icon'></i>
              <span class="text">Home</span>
            </a>
          </li>
          <li>
            <a href="accounts.php" data-tooltip="Accounts">
              <i class='bx bx-user icon'></i>
              <span class="text">Accounts</span>
            </a>
          </li>
        </ul>

        <div class="menu-section-title">Management</div>
        <ul class="menu-links">
          <li>
            <a href="reports.php" data-tooltip="Reports">
              <i class='bx bx-bar-chart-alt-2 icon'></i>
              <span class="text">Reports</span>
            </a>
          </li>
          <li>
            <a href="listing.php" data-tooltip="Listing">
              <i class='bx bx-building-house icon'></i>
              <span class="text">Listing</span>
            </a>
          </li>
          <li>
            <a href="verify-landlord.php" data-tooltip="Verify Landlord">
              <i class='bx bx-shield-check icon'></i>
              <span class="text">Verify Landlord</span>
              <?php if($pending_verification > 0): ?>
                <span class="badge"><?= $pending_verification ?></span>
              <?php endif; ?>
            </a>
          </li>
        </ul>
      </div>

      <div class="bottom-content">
        <div class="user-info">
          <img src="https://via.placeholder.com/36" alt="Admin" class="user-avatar">
          <div class="user-details">
            <span class="user-name"><?= htmlspecialchars($admin_name) ?></span>
            <span class="user-status">Online</span>
          </div>
        </div>

        <ul class="menu-links">
          <li>
            <a href="../logout.php" data-tooltip="Logout">
              <i class='bx bx-log-out icon'></i>
              <span class="text">Logout</span>
            </a>
          </li>
        </ul>
      </div>
    </div>
  </nav>

  <!-- MAIN CONTENT -->
  <main class="content">
    <!-- Page Header -->
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
          <a href="verify-landlord.php" class="action-btn">
            <i class='bx bx-check-shield'></i>
            <div class="action-btn-content">
              <h3>Verify Landlords</h3>
              <p><?= $pending_verification ?> pending requests</p>
            </div>
          </a>
          <a href="accounts.php" class="action-btn">
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
          <a href="reports.php" class="action-btn">
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
            <p style="text-align: center; color: var(--text-muted); padding: 40px;">No recent listings</p>
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
            <p style="text-align: center; color: var(--text-muted); padding: 40px;">No recent landlords</p>
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
            <p style="text-align: center; color: var(--text-muted); padding: 40px;">No recent tenants</p>
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

  <script>
    // Sidebar toggle
    const sidebar = document.querySelector('.sidebar');
    const toggle = document.querySelector('.toggle');

    if (localStorage.getItem('sidebarState') === 'collapsed') {
      sidebar.classList.add('collapsed');
    }

    toggle.addEventListener('click', () => {
      sidebar.classList.toggle('collapsed');
      localStorage.setItem(
        'sidebarState',
        sidebar.classList.contains('collapsed') ? 'collapsed' : 'expanded'
      );
    });

    // Activity tabs
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