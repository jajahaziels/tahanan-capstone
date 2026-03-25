<?php
require_once '../session_auth.php';
require_once '../connection.php';

// Check if admin is logged in
if (!isset($_SESSION['username'])) {
    header("Location: login.php");
    exit();
}

$admin_name = $_SESSION['username'];

// Get report ID
$report_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($report_id <= 0) {
    header("Location: reports.php");
    exit();
}

// Fetch report details
$report_query = "SELECT 
    r.*,
    t.firstName as tenant_first,
    t.lastName as tenant_last,
    t.email as tenant_email,
    t.phoneNum as tenant_phone,
    l.firstName as landlord_first,
    l.lastName as landlord_last,
    l.email as landlord_email,
    l.phoneNum as landlord_phone,
    l.verification_status as landlord_verification
FROM reportstbl r
LEFT JOIN tenanttbl t ON r.tenant_id = t.ID
LEFT JOIN landlordtbl l ON r.landlord_id = l.ID
WHERE r.ID = ?";

$stmt = $conn->prepare($report_query);
$stmt->bind_param("i", $report_id);
$stmt->execute();
$report_result = $stmt->get_result();

if ($report_result->num_rows === 0) {
    header("Location: reports.php");
    exit();
}

$report = $report_result->fetch_assoc();

// Get action history
$history_query = "SELECT * FROM report_actions_log WHERE report_id = ? ORDER BY created_at DESC";
$history_stmt = $conn->prepare($history_query);
$history_stmt->bind_param("i", $report_id);
$history_stmt->execute();
$history_result = $history_stmt->get_result();

// Get landlord warnings count
$warnings_query = "SELECT COUNT(*) as count FROM landlord_warnings WHERE landlord_id = ? AND is_active = 1";
$warnings_stmt = $conn->prepare($warnings_query);
$warnings_stmt->bind_param("i", $report['landlord_id']);
$warnings_stmt->execute();
$warnings_count = $warnings_stmt->get_result()->fetch_assoc()['count'];

// Get pending verification count for badge
$pending_verification = $conn->query("SELECT COUNT(*) as count FROM landlordtbl WHERE verification_status='pending'")->fetch_assoc()['count'];
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>View Report #<?= $report_id ?> - Admin Dashboard</title>
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

    /* ========== SIDEBAR (Same as reports.php) ========== */
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
      display: flex;
      justify-content: space-between;
      align-items: center;
    }

    .page-header h1 {
      font-size: 28px;
      font-weight: 700;
      color: #ffffff;
      display: flex;
      align-items: center;
      gap: 12px;
    }

    .back-btn {
      padding: 10px 20px;
      background: var(--sidebar-hover);
      color: var(--text-color);
      border: 1px solid var(--border-color);
      border-radius: 8px;
      text-decoration: none;
      font-size: 14px;
      font-weight: 600;
      display: inline-flex;
      align-items: center;
      gap: 8px;
      transition: all 0.3s;
    }

    .back-btn:hover {
      background: var(--border-color);
      color: white;
    }

    /* Report Container */
    .report-container {
      display: grid;
      grid-template-columns: 2fr 1fr;
      gap: 24px;
    }

    .report-main,
    .report-sidebar {
      background: var(--card-bg);
      border-radius: 12px;
      padding: 24px;
      border: 1px solid var(--border-color);
      box-shadow: 0 2px 8px rgba(0,0,0,0.3);
    }

    /* Priority & Status Badges */
    .badge-group {
      display: flex;
      gap: 12px;
      margin-bottom: 20px;
    }

    .badge {
      display: inline-flex;
      align-items: center;
      padding: 6px 12px;
      border-radius: 12px;
      font-size: 12px;
      font-weight: 600;
      gap: 6px;
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

    /* Report Info Sections */
    .section-title {
      font-size: 16px;
      font-weight: 700;
      color: #ffffff;
      margin-bottom: 16px;
      padding-bottom: 12px;
      border-bottom: 2px solid var(--border-color);
      display: flex;
      align-items: center;
      gap: 8px;
    }

    .info-row {
      display: flex;
      justify-content: space-between;
      padding: 12px 0;
      border-bottom: 1px solid var(--border-color);
    }

    .info-row:last-child {
      border-bottom: none;
    }

    .info-label {
      font-size: 13px;
      font-weight: 600;
      color: var(--text-muted);
    }

    .info-value {
      font-size: 14px;
      color: var(--text-color);
      text-align: right;
    }

    .description-box {
      background: var(--sidebar-hover);
      padding: 16px;
      border-radius: 8px;
      border-left: 4px solid var(--primary-color);
      margin: 16px 0;
    }

    .description-box p {
      color: var(--text-color);
      line-height: 1.6;
      margin: 0;
    }

    /* Action Buttons */
    .actions-section {
      margin-top: 24px;
    }

    .action-btn {
      width: 100%;
      padding: 12px;
      margin-bottom: 10px;
      border-radius: 8px;
      border: none;
      font-size: 14px;
      font-weight: 600;
      cursor: pointer;
      transition: all 0.3s;
      display: flex;
      align-items: center;
      justify-content: center;
      gap: 8px;
    }

    .btn-investigating {
      background: rgba(79, 172, 254, 0.2);
      color: #4facfe;
      border: 1px solid #4facfe;
    }

    .btn-investigating:hover {
      background: #4facfe;
      color: white;
    }

    .btn-warning {
      background: rgba(251, 146, 60, 0.2);
      color: #fb923c;
      border: 1px solid #fb923c;
    }

    .btn-warning:hover {
      background: #fb923c;
      color: white;
    }

    .btn-suspend {
      background: rgba(239, 68, 68, 0.2);
      color: #ef4444;
      border: 1px solid #ef4444;
    }

    .btn-suspend:hover {
      background: #ef4444;
      color: white;
    }

    .btn-resolved {
      background: rgba(67, 233, 123, 0.2);
      color: #43e97b;
      border: 1px solid #43e97b;
    }

    .btn-resolved:hover {
      background: #43e97b;
      color: white;
    }

    .btn-dismiss {
      background: rgba(156, 163, 175, 0.2);
      color: #9ca3af;
      border: 1px solid #9ca3af;
    }

    .btn-dismiss:hover {
      background: #9ca3af;
      color: white;
    }

    /* History Timeline */
    .history-item {
      padding: 16px;
      background: var(--sidebar-hover);
      border-radius: 8px;
      border-left: 3px solid var(--primary-color);
      margin-bottom: 12px;
    }

    .history-header {
      display: flex;
      justify-content: space-between;
      margin-bottom: 8px;
    }

    .history-action {
      font-weight: 600;
      color: var(--text-color);
      font-size: 13px;
    }

    .history-time {
      font-size: 12px;
      color: var(--text-muted);
    }

    .history-details {
      font-size: 13px;
      color: var(--text-muted);
    }

    /* Warning Box */
    .warning-box {
      background: rgba(251, 146, 60, 0.1);
      border: 1px solid #fb923c;
      border-radius: 8px;
      padding: 12px;
      margin-bottom: 16px;
      display: flex;
      align-items: center;
      gap: 12px;
    }

    .warning-box i {
      font-size: 20px;
      color: #fb923c;
    }

    .warning-box p {
      margin: 0;
      font-size: 13px;
      color: var(--text-color);
    }

    /* Modal */
    .modal {
      display: none;
      position: fixed;
      top: 0;
      left: 0;
      width: 100%;
      height: 100%;
      background: rgba(0, 0, 0, 0.8);
      z-index: 10000;
      justify-content: center;
      align-items: center;
    }

    .modal.show {
      display: flex;
    }

    .modal-content {
      background: var(--card-bg);
      border-radius: 12px;
      padding: 30px;
      max-width: 500px;
      width: 90%;
      border: 1px solid var(--border-color);
    }

    .modal-header {
      display: flex;
      justify-content: space-between;
      align-items: center;
      margin-bottom: 20px;
    }

    .modal-title {
      font-size: 20px;
      font-weight: 700;
      color: #ffffff;
    }

    .modal-close {
      background: none;
      border: none;
      font-size: 24px;
      color: var(--text-muted);
      cursor: pointer;
    }

    .form-group {
      margin-bottom: 20px;
    }

    .form-label {
      display: block;
      font-size: 14px;
      font-weight: 600;
      color: var(--text-color);
      margin-bottom: 8px;
    }

    .form-control {
      width: 100%;
      padding: 12px;
      background: var(--sidebar-hover);
      border: 1px solid var(--border-color);
      border-radius: 8px;
      color: var(--text-color);
      font-size: 14px;
    }

    .form-control:focus {
      outline: none;
      border-color: var(--primary-color);
    }

    textarea.form-control {
      resize: vertical;
      min-height: 100px;
    }

    .btn-submit {
      width: 100%;
      padding: 12px;
      background: var(--primary-color);
      color: white;
      border: none;
      border-radius: 8px;
      font-size: 14px;
      font-weight: 600;
      cursor: pointer;
      transition: all 0.3s;
    }

    .btn-submit:hover {
      background: var(--primary-hover);
    }

    /* RESPONSIVE */
    @media (max-width: 1024px) {
      .report-container {
        grid-template-columns: 1fr;
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

      .page-header {
        flex-direction: column;
        gap: 16px;
        align-items: flex-start;
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
      <h1><i class='bx bx-flag'></i> Report #<?= $report_id ?></h1>
      <a href="reports.php" class="back-btn">
        <i class='bx bx-arrow-back'></i> Back to Reports
      </a>
    </div>

    <div class="report-container">
      <!-- Main Report Details -->
      <div class="report-main">
        <!-- Badges -->
        <div class="badge-group">
          <span class="badge priority-<?= $report['priority'] ?>">
            <i class='bx bx-error-circle'></i>
            <?= ucfirst($report['priority']) ?> Priority
          </span>
          <span class="badge status-<?= $report['status'] ?>">
            <i class='bx bx-circle'></i>
            <?= ucfirst($report['status']) ?>
          </span>
          <?php if ($report['is_anonymous']): ?>
            <span class="badge" style="background: rgba(156, 163, 175, 0.2); color: #9ca3af;">
              <i class='bx bx-user-circle'></i>
              Anonymous
            </span>
          <?php endif; ?>
        </div>

        <!-- Report Details -->
        <div class="section-title">
          <i class='bx bx-info-circle'></i>
          Report Details
        </div>

        <div class="info-row">
          <span class="info-label">Category:</span>
          <span class="info-value"><?= htmlspecialchars($report['category']) ?></span>
        </div>

        <div class="info-row">
          <span class="info-label">Subject:</span>
          <span class="info-value" style="font-weight: 600;"><?= htmlspecialchars($report['subject']) ?></span>
        </div>

        <div class="info-row">
          <span class="info-label">Submitted:</span>
          <span class="info-value"><?= date('F j, Y g:i A', strtotime($report['created_at'])) ?></span>
        </div>

        <?php if ($report['incident_date']): ?>
        <div class="info-row">
          <span class="info-label">Incident Date:</span>
          <span class="info-value"><?= date('F j, Y', strtotime($report['incident_date'])) ?></span>
        </div>
        <?php endif; ?>

        <!-- Description -->
        <div class="section-title" style="margin-top: 24px;">
          <i class='bx bx-detail'></i>
          Description
        </div>

        <div class="description-box">
          <p><?= nl2br(htmlspecialchars($report['description'])) ?></p>
        </div>

        <!-- Reported By -->
        <div class="section-title">
          <i class='bx bx-user'></i>
          Reported By
        </div>

        <?php if ($report['is_anonymous']): ?>
          <p style="color: var(--text-muted); font-size: 14px;">
            <i class='bx bx-user-circle'></i> This report was submitted anonymously
          </p>
        <?php else: ?>
          <div class="info-row">
            <span class="info-label">Name:</span>
            <span class="info-value"><?= htmlspecialchars($report['tenant_first'] . ' ' . $report['tenant_last']) ?></span>
          </div>
          <div class="info-row">
            <span class="info-label">Email:</span>
            <span class="info-value"><?= htmlspecialchars($report['tenant_email']) ?></span>
          </div>
          <div class="info-row">
            <span class="info-label">Phone:</span>
            <span class="info-value"><?= htmlspecialchars($report['tenant_phone'] ?: 'Not provided') ?></span>
          </div>
        <?php endif; ?>

        <!-- Reported Against -->
        <div class="section-title" style="margin-top: 24px;">
          <i class='bx bx-shield-x'></i>
          Reported Against (Landlord)
        </div>

        <?php if ($warnings_count > 0): ?>
        <div class="warning-box">
          <i class='bx bx-error'></i>
          <p><strong>Warning:</strong> This landlord has <?= $warnings_count ?> active warning(s)</p>
        </div>
        <?php endif; ?>

        <div class="info-row">
          <span class="info-label">Name:</span>
          <span class="info-value"><?= htmlspecialchars($report['landlord_first'] . ' ' . $report['landlord_last']) ?></span>
        </div>
        <div class="info-row">
          <span class="info-label">Email:</span>
          <span class="info-value"><?= htmlspecialchars($report['landlord_email']) ?></span>
        </div>
        <div class="info-row">
          <span class="info-label">Phone:</span>
          <span class="info-value"><?= htmlspecialchars($report['landlord_phone'] ?: 'Not provided') ?></span>
        </div>
        <div class="info-row">
          <span class="info-label">Verification:</span>
          <span class="info-value"><?= ucfirst($report['landlord_verification']) ?></span>
        </div>

        <!-- Admin Notes -->
        <?php if ($report['admin_notes']): ?>
        <div class="section-title" style="margin-top: 24px;">
          <i class='bx bx-note'></i>
          Admin Notes
        </div>
        <div class="description-box">
          <p><?= nl2br(htmlspecialchars($report['admin_notes'])) ?></p>
        </div>
        <?php endif; ?>

        <!-- Action History -->
        <div class="section-title" style="margin-top: 24px;">
          <i class='bx bx-history'></i>
          Action History
        </div>

        <?php if ($history_result->num_rows > 0): ?>
          <?php while($history = $history_result->fetch_assoc()): ?>
            <div class="history-item">
              <div class="history-header">
                <span class="history-action">
                  <i class='bx bx-check-circle'></i>
                  <?= ucfirst(str_replace('_', ' ', $history['action_type'])) ?>
                </span>
                <span class="history-time"><?= date('M j, Y g:i A', strtotime($history['created_at'])) ?></span>
              </div>
              <div class="history-details">
                By: <?= htmlspecialchars($history['admin_username']) ?>
                <?php if ($history['action_details']): ?>
                  <br><?= htmlspecialchars($history['action_details']) ?>
                <?php endif; ?>
              </div>
            </div>
          <?php endwhile; ?>
        <?php else: ?>
          <p style="color: var(--text-muted); font-size: 14px;">No actions taken yet</p>
        <?php endif; ?>
      </div>

      <!-- Sidebar Actions -->
      <div class="report-sidebar">
        <div class="section-title">
          <i class='bx bx-cog'></i>
          Actions
        </div>

        <div class="actions-section">
          <?php if ($report['status'] === 'pending'): ?>
            <button class="action-btn btn-investigating" onclick="updateStatus('investigating')">
              <i class='bx bx-search'></i> Mark as Investigating
            </button>
          <?php endif; ?>

          <button class="action-btn btn-warning" onclick="openWarningModal()">
            <i class='bx bx-error'></i> Issue Warning
          </button>

          <button class="action-btn btn-suspend" onclick="openSuspendModal()">
            <i class='bx bx-block'></i> Suspend Account
          </button>

          <button class="action-btn btn-resolved" onclick="openResolveModal()">
            <i class='bx bx-check-circle'></i> Mark as Resolved
          </button>

          <button class="action-btn btn-dismiss" onclick="openDismissModal()">
            <i class='bx bx-x-circle'></i> Dismiss Report
          </button>
        </div>

        <!-- Quick Info -->
        <div class="section-title" style="margin-top: 30px;">
          <i class='bx bx-info-square'></i>
          Quick Info
        </div>

        <div class="info-row">
          <span class="info-label">Report ID:</span>
          <span class="info-value">#<?= $report_id ?></span>
        </div>

        <div class="info-row">
          <span class="info-label">Landlord ID:</span>
          <span class="info-value"><?= $report['landlord_id'] ?></span>
        </div>

        <div class="info-row">
          <span class="info-label">Tenant ID:</span>
          <span class="info-value"><?= $report['is_anonymous'] ? 'Anonymous' : $report['tenant_id'] ?></span>
        </div>

        <div class="info-row">
          <span class="info-label">Last Updated:</span>
          <span class="info-value"><?= date('M j, Y', strtotime($report['updated_at'])) ?></span>
        </div>
      </div>
    </div>
  </main>

  <!-- Modals will be added here -->
  <!-- Warning Modal -->
  <div class="modal" id="warningModal">
    <div class="modal-content">
      <div class="modal-header">
        <h3 class="modal-title">Issue Warning</h3>
        <button class="modal-close" onclick="closeModal('warningModal')">&times;</button>
      </div>
      <form id="warningForm">
        <div class="form-group">
          <label class="form-label">Warning Level</label>
          <select class="form-control" name="warning_level" required>
            <option value="first">First Warning</option>
            <option value="second">Second Warning</option>
            <option value="final">Final Warning</option>
          </select>
        </div>
        <div class="form-group">
          <label class="form-label">Reason</label>
          <textarea class="form-control" name="reason" required placeholder="Explain the warning..."></textarea>
        </div>
        <button type="submit" class="btn-submit">Issue Warning</button>
      </form>
    </div>
  </div>

  <!-- Suspend Modal -->
  <div class="modal" id="suspendModal">
    <div class="modal-content">
      <div class="modal-header">
        <h3 class="modal-title">Suspend Account</h3>
        <button class="modal-close" onclick="closeModal('suspendModal')">&times;</button>
      </div>
      <form id="suspendForm">
        <div class="form-group">
          <label class="form-label">Suspension Type</label>
          <select class="form-control" name="suspension_type" required onchange="toggleEndDate(this)">
            <option value="temporary">Temporary</option>
            <option value="permanent">Permanent</option>
          </select>
        </div>
        <div class="form-group" id="endDateGroup">
          <label class="form-label">End Date</label>
          <input type="date" class="form-control" name="end_date" min="<?= date('Y-m-d', strtotime('+1 day')) ?>">
        </div>
        <div class="form-group">
          <label class="form-label">Reason</label>
          <textarea class="form-control" name="reason" required placeholder="Explain the suspension..."></textarea>
        </div>
        <button type="submit" class="btn-submit">Suspend Account</button>
      </form>
    </div>
  </div>

  <!-- Resolve Modal -->
  <div class="modal" id="resolveModal">
    <div class="modal-content">
      <div class="modal-header">
        <h3 class="modal-title">Mark as Resolved</h3>
        <button class="modal-close" onclick="closeModal('resolveModal')">&times;</button>
      </div>
      <form id="resolveForm">
        <div class="form-group">
          <label class="form-label">Resolution Details</label>
          <textarea class="form-control" name="resolution" required placeholder="Explain how this was resolved..."></textarea>
        </div>
        <button type="submit" class="btn-submit">Mark as Resolved</button>
      </form>
    </div>
  </div>

  <!-- Dismiss Modal -->
  <div class="modal" id="dismissModal">
    <div class="modal-content">
      <div class="modal-header">
        <h3 class="modal-title">Dismiss Report</h3>
        <button class="modal-close" onclick="closeModal('dismissModal')">&times;</button>
      </div>
      <form id="dismissForm">
        <div class="form-group">
          <label class="form-label">Dismissal Reason</label>
          <select class="form-control" name="dismissal_reason" required>
            <option value="">Select reason</option>
            <option value="invalid">Invalid Report</option>
            <option value="insufficient">Insufficient Evidence</option>
            <option value="resolved_privately">Resolved Privately</option>
            <option value="false_report">False Report</option>
            <option value="other">Other</option>
          </select>
        </div>
        <div class="form-group">
          <label class="form-label">Additional Notes</label>
          <textarea class="form-control" name="notes" placeholder="Any additional details..."></textarea>
        </div>
        <button type="submit" class="btn-submit">Dismiss Report</button>
      </form>
    </div>
  </div>

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

    // Modal functions
    function openWarningModal() {
      document.getElementById('warningModal').classList.add('show');
    }

    function openSuspendModal() {
      document.getElementById('suspendModal').classList.add('show');
    }

    function openResolveModal() {
      document.getElementById('resolveModal').classList.add('show');
    }

    function openDismissModal() {
      document.getElementById('dismissModal').classList.add('show');
    }

    function closeModal(modalId) {
      document.getElementById(modalId).classList.remove('show');
    }

    function toggleEndDate(select) {
      const endDateGroup = document.getElementById('endDateGroup');
      if (select.value === 'permanent') {
        endDateGroup.style.display = 'none';
      } else {
        endDateGroup.style.display = 'block';
      }
    }

    // Quick status update
    function updateStatus(status) {
      if (confirm('Are you sure you want to mark this report as ' + status + '?')) {
        // TODO: Create handler PHP file
        fetch('report_action.php', {
          method: 'POST',
          headers: {'Content-Type': 'application/json'},
          body: JSON.stringify({
            report_id: <?= $report_id ?>,
            action: 'update_status',
            status: status
          })
        })
        .then(response => response.json())
        .then(data => {
          if (data.success) {
            alert('Status updated successfully');
            location.reload();
          } else {
            alert('Error: ' + data.message);
          }
        });
      }
    }

    // Form submissions (TODO: Create handler PHP files)
    document.getElementById('warningForm').addEventListener('submit', function(e) {
      e.preventDefault();
      // Submit warning form
      alert('Warning feature coming soon!');
    });

    document.getElementById('suspendForm').addEventListener('submit', function(e) {
      e.preventDefault();
      // Submit suspension form
      alert('Suspension feature coming soon!');
    });

    document.getElementById('resolveForm').addEventListener('submit', function(e) {
      e.preventDefault();
      // Submit resolve form
      alert('Resolve feature coming soon!');
    });

    document.getElementById('dismissForm').addEventListener('submit', function(e) {
      e.preventDefault();
      // Submit dismiss form
      alert('Dismiss feature coming soon!');
    });
  </script>

</body>
</html>