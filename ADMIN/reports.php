<?php
require_once '../session_auth.php';
require_once '../connection.php';

// Get admin info
$admin_name = $_SESSION['username'];

// Get pending verification count for badge
$pending_verification = $conn->query("SELECT COUNT(*) as count FROM landlordtbl WHERE verification_status='pending'")->fetch_assoc()['count'];

// Get filter parameters
$status_filter = isset($_GET['status']) ? $_GET['status'] : 'all';
$priority_filter = isset($_GET['priority']) ? $_GET['priority'] : 'all';
$category_filter = isset($_GET['category']) ? $_GET['category'] : 'all';

// Build query with filters
$reports_query = "SELECT 
    r.*,
    t.firstName as tenant_first,
    t.lastName as tenant_last,
    t.email as tenant_email,
    l.firstName as landlord_first,
    l.lastName as landlord_last,
    l.email as landlord_email
FROM reportstbl r
LEFT JOIN tenanttbl t ON r.tenant_id = t.ID
LEFT JOIN landlordtbl l ON r.landlord_id = l.ID
WHERE 1=1";

if ($status_filter !== 'all') {
    $reports_query .= " AND r.status = '" . $conn->real_escape_string($status_filter) . "'";
}
if ($priority_filter !== 'all') {
    $reports_query .= " AND r.priority = '" . $conn->real_escape_string($priority_filter) . "'";
}
if ($category_filter !== 'all') {
    $reports_query .= " AND r.category = '" . $conn->real_escape_string($category_filter) . "'";
}

$reports_query .= " ORDER BY 
    CASE r.priority 
        WHEN 'urgent' THEN 1 
        WHEN 'high' THEN 2 
        WHEN 'medium' THEN 3 
        WHEN 'low' THEN 4 
    END,
    r.created_at DESC";

$reports_result = $conn->query($reports_query);

// Get statistics
$stats_query = "SELECT 
    COUNT(*) as total_reports,
    COUNT(CASE WHEN status = 'pending' THEN 1 END) as pending_count,
    COUNT(CASE WHEN status = 'investigating' THEN 1 END) as investigating_count,
    COUNT(CASE WHEN status = 'resolved' THEN 1 END) as resolved_count,
    COUNT(CASE WHEN status = 'dismissed' THEN 1 END) as dismissed_count,
    COUNT(CASE WHEN priority = 'urgent' THEN 1 END) as urgent_count
FROM reportstbl";
$stats_result = $conn->query($stats_query);
$stats = $stats_result->fetch_assoc();
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Reports Management - Admin Dashboard</title>
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

    /* ========== SIDEBAR (Same as other pages) ========== */
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
      display: flex;
      align-items: center;
      gap: 12px;
    }

    .page-header p {
      color: var(--text-muted);
      font-size: 14px;
    }

    /* ========== STATS CARDS ========== */
    .stats-container {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
      gap: 20px;
      margin-bottom: 30px;
    }

    .stat-card {
      background: var(--card-bg);
      padding: 20px;
      border-radius: 12px;
      border: 1px solid var(--border-color);
      box-shadow: 0 2px 8px rgba(0,0,0,0.3);
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
      background: var(--stat-color);
    }

    .stat-card:hover {
      transform: translateY(-4px);
      box-shadow: 0 6px 16px rgba(0,0,0,0.4);
      border-color: var(--stat-color);
    }

    .stat-card.total { --stat-color: #667eea; }
    .stat-card.pending { --stat-color: #feca57; }
    .stat-card.investigating { --stat-color: #4facfe; }
    .stat-card.resolved { --stat-color: #43e97b; }
    .stat-card.dismissed { --stat-color: #fa709a; }
    .stat-card.urgent { --stat-color: #ef4444; }

    .stat-card h3 {
      font-size: 32px;
      font-weight: 700;
      color: #ffffff;
      margin: 0 0 8px 0;
    }

    .stat-card p {
      font-size: 13px;
      color: var(--text-muted);
      margin: 0;
      display: flex;
      align-items: center;
      gap: 6px;
    }

    .stat-card p i {
      color: var(--stat-color);
      font-size: 16px;
    }

    /* ========== FILTERS ========== */
    .filters-section {
      background: var(--card-bg);
      padding: 24px;
      border-radius: 12px;
      box-shadow: 0 2px 8px rgba(0,0,0,0.3);
      margin-bottom: 24px;
      border: 1px solid var(--border-color);
    }

    .filters-grid {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
      gap: 16px;
    }

    .filter-group label {
      display: block;
      font-size: 13px;
      font-weight: 600;
      color: var(--text-color);
      margin-bottom: 8px;
    }

    .filter-group select {
      width: 100%;
      padding: 10px 12px;
      background: var(--sidebar-hover);
      border: 2px solid var(--border-color);
      border-radius: 8px;
      color: var(--text-color);
      font-size: 14px;
      transition: all 0.3s;
    }

    .filter-group select:focus {
      outline: none;
      border-color: var(--primary-color);
      box-shadow: 0 0 0 3px rgba(141, 11, 65, 0.2);
    }

    /* ========== REPORTS TABLE ========== */
    .reports-container {
      background: var(--card-bg);
      border-radius: 12px;
      padding: 24px;
      box-shadow: 0 2px 8px rgba(0,0,0,0.3);
      border: 1px solid var(--border-color);
    }

    .reports-table {
      width: 100%;
      border-collapse: collapse;
    }

    .reports-table thead {
      background: var(--sidebar-hover);
    }

    .reports-table th {
      padding: 14px 16px;
      text-align: left;
      font-weight: 600;
      font-size: 13px;
      color: var(--text-color);
      text-transform: uppercase;
      letter-spacing: 0.5px;
      border-bottom: 2px solid var(--border-color);
    }

    .reports-table tbody tr {
      border-bottom: 1px solid var(--border-color);
      transition: all 0.3s;
    }

    .reports-table tbody tr:hover {
      background: var(--sidebar-hover);
    }

    .reports-table td {
      padding: 16px;
      font-size: 14px;
      color: var(--text-muted);
    }

    /* Priority Badges */
    .priority-badge {
      display: inline-flex;
      align-items: center;
      padding: 4px 10px;
      border-radius: 12px;
      font-size: 11px;
      font-weight: 600;
      gap: 4px;
    }

    .priority-urgent {
      background: rgba(239, 68, 68, 0.2);
      color: #ef4444;
    }

    .priority-high {
      background: rgba(251, 146, 60, 0.2);
      color: #fb923c;
    }

    .priority-medium {
      background: rgba(254, 202, 87, 0.2);
      color: #feca57;
    }

    .priority-low {
      background: rgba(156, 163, 175, 0.2);
      color: #9ca3af;
    }

    /* Status Badges */
    .status-badge {
      display: inline-flex;
      align-items: center;
      padding: 4px 10px;
      border-radius: 12px;
      font-size: 11px;
      font-weight: 600;
      gap: 4px;
    }

    .status-pending {
      background: rgba(254, 202, 87, 0.2);
      color: #feca57;
    }

    .status-investigating {
      background: rgba(79, 172, 254, 0.2);
      color: #4facfe;
    }

    .status-resolved {
      background: rgba(67, 233, 123, 0.2);
      color: #43e97b;
    }

    .status-dismissed {
      background: rgba(156, 163, 175, 0.2);
      color: #9ca3af;
    }

    /* Action Buttons */
    .btn-view {
      padding: 8px 16px;
      background: var(--primary-color);
      color: white;
      border: none;
      border-radius: 8px;
      font-size: 13px;
      font-weight: 600;
      cursor: pointer;
      transition: all 0.3s;
      display: inline-flex;
      align-items: center;
      gap: 6px;
      text-decoration: none;
    }

    .btn-view:hover {
      background: var(--primary-hover);
      transform: translateY(-2px);
      box-shadow: 0 4px 12px rgba(141, 11, 65, 0.3);
      color: white;
    }

    /* Empty State */
    .empty-state {
      text-align: center;
      padding: 60px 20px;
      color: var(--text-muted);
    }

    .empty-state i {
      font-size: 64px;
      opacity: 0.2;
      margin-bottom: 16px;
      color: var(--primary-color);
    }

    .empty-state p {
      font-size: 15px;
    }

    /* RESPONSIVE */
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

      .stats-container {
        grid-template-columns: repeat(2, 1fr);
      }

      .reports-table {
        display: block;
        overflow-x: auto;
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
            <a href="dashboard.php" data-tooltip="Home">
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
            <a href="reports.php" class="active" data-tooltip="Reports">
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
    <div class="page-header">
      <h1><i class='bx bx-flag'></i> Reports Management</h1>
      <p>Review and manage tenant reports against landlords</p>
    </div>

    <!-- Statistics Cards -->
    <div class="stats-container">
      <div class="stat-card total">
        <h3><?= $stats['total_reports'] ?></h3>
        <p><i class='bx bx-file'></i> Total Reports</p>
      </div>

      <div class="stat-card pending">
        <h3><?= $stats['pending_count'] ?></h3>
        <p><i class='bx bx-time-five'></i> Pending</p>
      </div>

      <div class="stat-card investigating">
        <h3><?= $stats['investigating_count'] ?></h3>
        <p><i class='bx bx-search'></i> Investigating</p>
      </div>

      <div class="stat-card resolved">
        <h3><?= $stats['resolved_count'] ?></h3>
        <p><i class='bx bx-check-circle'></i> Resolved</p>
      </div>

      <div class="stat-card dismissed">
        <h3><?= $stats['dismissed_count'] ?></h3>
        <p><i class='bx bx-x-circle'></i> Dismissed</p>
      </div>

      <div class="stat-card urgent">
        <h3><?= $stats['urgent_count'] ?></h3>
        <p><i class='bx bx-error'></i> Urgent</p>
      </div>
    </div>

    <!-- Filters -->
    <div class="filters-section">
      <form method="GET" action="">
        <div class="filters-grid">
          <div class="filter-group">
            <label>Status</label>
            <select name="status" onchange="this.form.submit()">
              <option value="all" <?= $status_filter == 'all' ? 'selected' : '' ?>>All Status</option>
              <option value="pending" <?= $status_filter == 'pending' ? 'selected' : '' ?>>Pending</option>
              <option value="investigating" <?= $status_filter == 'investigating' ? 'selected' : '' ?>>Investigating</option>
              <option value="resolved" <?= $status_filter == 'resolved' ? 'selected' : '' ?>>Resolved</option>
              <option value="dismissed" <?= $status_filter == 'dismissed' ? 'selected' : '' ?>>Dismissed</option>
            </select>
          </div>

          <div class="filter-group">
            <label>Priority</label>
            <select name="priority" onchange="this.form.submit()">
              <option value="all" <?= $priority_filter == 'all' ? 'selected' : '' ?>>All Priorities</option>
              <option value="urgent" <?= $priority_filter == 'urgent' ? 'selected' : '' ?>>Urgent</option>
              <option value="high" <?= $priority_filter == 'high' ? 'selected' : '' ?>>High</option>
              <option value="medium" <?= $priority_filter == 'medium' ? 'selected' : '' ?>>Medium</option>
              <option value="low" <?= $priority_filter == 'low' ? 'selected' : '' ?>>Low</option>
            </select>
          </div>

          <div class="filter-group">
            <label>Category</label>
            <select name="category" onchange="this.form.submit()">
              <option value="all" <?= $category_filter == 'all' ? 'selected' : '' ?>>All Categories</option>
              <option value="Scamming/Fraud">Scamming/Fraud</option>
              <option value="Property Misrepresentation">Property Misrepresentation</option>
              <option value="Poor Property Maintenance">Poor Property Maintenance</option>
              <option value="Unresponsive/Bad Communication">Unresponsive/Bad Communication</option>
              <option value="Violation of Lease Terms">Violation of Lease Terms</option>
              <option value="Harassment/Inappropriate Behavior">Harassment/Inappropriate Behavior</option>
              <option value="Illegal Activities">Illegal Activities</option>
              <option value="Overcharging/Hidden Fees">Overcharging/Hidden Fees</option>
              <option value="Unsafe Property Conditions">Unsafe Property Conditions</option>
              <option value="Other">Other</option>
            </select>
          </div>
        </div>
      </form>
    </div>

    <!-- Reports Table -->
    <div class="reports-container">
      <?php if ($reports_result && $reports_result->num_rows > 0): ?>
        <table class="reports-table">
          <thead>
            <tr>
              <th>ID</th>
              <th>Category</th>
              <th>Reported By</th>
              <th>Against</th>
              <th>Priority</th>
              <th>Status</th>
              <th>Date</th>
              <th>Action</th>
            </tr>
          </thead>
          <tbody>
            <?php while($report = $reports_result->fetch_assoc()): ?>
              <tr>
                <td style="color: #ffffff; font-weight: 600;">#<?= $report['ID'] ?></td>
                <td>
                  <div style="color: #ffffff; font-weight: 500;"><?= htmlspecialchars($report['category']) ?></div>
                  <small style="color: var(--text-muted); font-size: 12px;"><?= htmlspecialchars(substr($report['subject'], 0, 30)) ?>...</small>
                </td>
                <td>
                  <?php if ($report['is_anonymous']): ?>
                    <i class='bx bx-user-circle'></i> Anonymous
                  <?php else: ?>
                    <?= htmlspecialchars($report['tenant_first'] . ' ' . $report['tenant_last']) ?>
                  <?php endif; ?>
                </td>
                <td><?= htmlspecialchars($report['landlord_first'] . ' ' . $report['landlord_last']) ?></td>
                <td>
                  <span class="priority-badge priority-<?= $report['priority'] ?>">
                    <i class='bx bx-error-circle'></i>
                    <?= ucfirst($report['priority']) ?>
                  </span>
                </td>
                <td>
                  <span class="status-badge status-<?= $report['status'] ?>">
                    <i class='bx bx-circle'></i>
                    <?= ucfirst($report['status']) ?>
                  </span>
                </td>
                <td><?= date('M d, Y', strtotime($report['created_at'])) ?></td>
                <td>
                  <a href="view-report.php?id=<?= $report['ID'] ?>" class="btn-view">
                    <i class='bx bx-show'></i> View
                  </a>
                </td>
              </tr>
            <?php endwhile; ?>
          </tbody>
        </table>
      <?php else: ?>
        <div class="empty-state">
          <i class='bx bx-check-circle'></i>
          <p>No reports found matching your filters.</p>
        </div>
      <?php endif; ?>
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
  </script>

</body>
</html>