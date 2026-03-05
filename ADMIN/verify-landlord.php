<?php
require_once '../session_auth.php';
require_once '../connection.php';

// Get admin info
$admin_name = $_SESSION['username'];

// Get pending verification count for badge
$pending_verification = $conn->query("SELECT COUNT(*) as count FROM landlordtbl WHERE verification_status='pending'")->fetch_assoc()['count'];

// Fetch pending landlord verification requests with all documents
$verify_query = "SELECT ID, firstName, lastName, email, username, phoneNum, 
                 valid_id, proof_of_ownership, landlord_insurance, 
                 gas_safety_cert, electric_safety_cert, lease_agreement,
                 submission_date, created_at
                 FROM landlordtbl 
                 WHERE verification_status = 'pending' 
                 ORDER BY submission_date DESC, created_at DESC";
$verify_result = $conn->query($verify_query);

// Fetch statistics
$stats_query = "SELECT 
                COUNT(CASE WHEN verification_status = 'pending' THEN 1 END) as pending_count,
                COUNT(CASE WHEN verification_status = 'verified' THEN 1 END) as verified_count,
                COUNT(CASE WHEN verification_status = 'rejected' THEN 1 END) as rejected_count
                FROM landlordtbl";
$stats_result = $conn->query($stats_query);
$stats = $stats_result->fetch_assoc();
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Verify Landlords - Admin Dashboard</title>
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

    /* ========== SIDEBAR STYLES (Same as other pages) ========== */
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
      grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
      gap: 20px;
      margin-bottom: 30px;
    }

    .stat-card {
      background: var(--card-bg);
      color: white;
      padding: 24px;
      border-radius: 12px;
      box-shadow: 0 2px 8px rgba(0,0,0,0.3);
      transition: all 0.3s;
      border: 1px solid var(--border-color);
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
      transform: translateY(-5px);
      box-shadow: 0 8px 20px rgba(0,0,0,0.4);
      border-color: var(--stat-color);
    }

    .stat-card.pending { --stat-color: #feca57; }
    .stat-card.verified { --stat-color: #43e97b; }
    .stat-card.rejected { --stat-color: #fa709a; }

    .stat-card h3 {
      font-size: 36px;
      margin: 0 0 8px 0;
      font-weight: 700;
      color: #ffffff;
    }

    .stat-card p {
      margin: 0;
      opacity: 0.9;
      font-size: 14px;
      color: var(--text-muted);
      display: flex;
      align-items: center;
      gap: 6px;
    }

    .stat-card p i {
      font-size: 18px;
      color: var(--stat-color);
    }

    /* ========== MESSAGES ========== */
    .message-box {
      padding: 18px 24px;
      border-radius: 12px;
      margin-bottom: 25px;
      display: flex;
      align-items: center;
      gap: 12px;
      font-weight: 500;
      animation: slideIn 0.4s ease;
    }

    @keyframes slideIn {
      from {
        opacity: 0;
        transform: translateX(-20px);
      }
      to {
        opacity: 1;
        transform: translateX(0);
      }
    }

    .message-box.success {
      background: rgba(67, 233, 123, 0.15);
      color: #43e97b;
      border-left: 4px solid #43e97b;
    }

    .message-box.error {
      background: rgba(250, 112, 154, 0.15);
      color: #fa709a;
      border-left: 4px solid #fa709a;
    }

    .message-box i {
      font-size: 24px;
    }

    /* ========== VERIFICATION CARDS ========== */
    .verify-list {
      display: flex;
      flex-direction: column;
      gap: 24px;
    }

    .verify-card {
      background: var(--card-bg);
      border-radius: 12px;
      padding: 30px;
      box-shadow: 0 2px 8px rgba(0,0,0,0.3);
      transition: all 0.3s;
      border: 1px solid var(--border-color);
      border-left: 5px solid var(--primary-color);
    }

    .verify-card:hover {
      box-shadow: 0 6px 20px rgba(0,0,0,0.4);
      border-left-color: var(--primary-color);
    }

    .card-layout {
      display: grid;
      grid-template-columns: 350px 1fr;
      gap: 30px;
    }

    @media (max-width: 1024px) {
      .card-layout {
        grid-template-columns: 1fr;
      }
    }

    /* LEFT SECTION */
    .landlord-section {
      display: flex;
      flex-direction: column;
      gap: 15px;
    }

    .landlord-header-compact {
      display: flex;
      align-items: flex-start;
      gap: 15px;
      padding-bottom: 15px;
      border-bottom: 2px solid var(--border-color);
    }

    .profile-pic {
      width: 80px;
      height: 80px;
      border-radius: 50%;
      object-fit: cover;
      border: 3px solid var(--primary-color);
      flex-shrink: 0;
    }

    .landlord-info {
      flex: 1;
    }

    .landlord-info h3 {
      margin: 0 0 8px 0;
      color: #ffffff;
      font-size: 20px;
    }

    .info-item {
      margin: 5px 0;
      color: var(--text-muted);
      font-size: 13px;
      display: flex;
      align-items: center;
      gap: 8px;
    }

    .info-item i {
      color: var(--primary-color);
      font-size: 14px;
    }

    /* INCOMPLETE WARNING */
    .incomplete-warning {
      background: rgba(254, 202, 87, 0.15);
      border: 1px solid #feca57;
      color: #feca57;
      padding: 12px 14px;
      border-radius: 8px;
      display: flex;
      align-items: center;
      gap: 10px;
      font-size: 13px;
    }

    .incomplete-warning i {
      font-size: 20px;
      flex-shrink: 0;
    }

    /* RIGHT SECTION */
    .documents-actions-section {
      display: flex;
      flex-direction: column;
      gap: 20px;
    }

    .documents-header-compact {
      display: flex;
      align-items: center;
      justify-content: space-between;
    }

    .doc-badge {
      display: flex;
      align-items: center;
      gap: 10px;
      font-weight: 600;
      color: #ffffff;
      font-size: 15px;
    }

    .document-count {
      background: var(--primary-color);
      color: white;
      padding: 4px 12px;
      border-radius: 20px;
      font-size: 12px;
      font-weight: 600;
    }

    /* DOCUMENTS GRID */
    .documents-grid-compact {
      display: grid;
      grid-template-columns: repeat(3, 1fr);
      gap: 15px;
    }

    @media (max-width: 768px) {
      .documents-grid-compact {
        grid-template-columns: repeat(2, 1fr);
      }
    }

    .document-item-compact {
      background: var(--sidebar-hover);
      border: 2px solid var(--border-color);
      border-radius: 10px;
      padding: 12px;
      text-align: center;
      transition: all 0.3s;
      cursor: pointer;
      min-height: 180px;
      display: flex;
      flex-direction: column;
    }

    .document-item-compact:hover {
      border-color: var(--primary-color);
      box-shadow: 0 4px 12px rgba(141, 11, 65, 0.3);
      transform: translateY(-3px);
    }

    .document-item-compact.missing {
      border-color: #feca57;
      background: rgba(254, 202, 87, 0.1);
      opacity: 0.7;
      cursor: default;
    }

    .document-item-compact.missing:hover {
      transform: none;
      box-shadow: none;
    }

    .document-item-compact img {
      width: 100%;
      height: 100px;
      object-fit: cover;
      border-radius: 6px;
      margin-bottom: 8px;
    }

    .doc-icon-small {
      font-size: 40px;
      color: var(--primary-color);
      margin: 20px 0;
    }

    .doc-icon-small.missing-icon {
      color: #feca57;
    }

    .doc-label-compact {
      font-size: 11px;
      font-weight: 600;
      color: var(--text-color);
      margin-top: auto;
      line-height: 1.3;
      padding: 5px 0;
    }

    .view-btn-compact {
      display: inline-block;
      padding: 5px 10px;
      background: var(--primary-color);
      color: white;
      border-radius: 5px;
      text-decoration: none;
      font-size: 10px;
      margin-top: 8px;
      transition: all 0.3s;
    }

    .view-btn-compact:hover {
      background: var(--primary-hover);
      color: white;
      transform: translateY(-2px);
    }

    /* ACTION BUTTONS */
    .actions-container {
      display: flex;
      gap: 12px;
      margin-top: 20px;
    }

    .btn {
      padding: 12px 28px;
      border: none;
      border-radius: 10px;
      cursor: pointer;
      font-weight: 600;
      font-size: 14px;
      transition: all 0.3s;
      display: inline-flex;
      align-items: center;
      gap: 8px;
    }

    .btn.approve {
      background: #43e97b;
      color: #0f1419;
    }

    .btn.approve:hover {
      background: #38d16b;
      transform: translateY(-2px);
      box-shadow: 0 4px 12px rgba(67, 233, 123, 0.4);
    }

    .btn.reject {
      background: #fa709a;
      color: white;
    }

    .btn.reject:hover {
      background: #f85c8a;
      transform: translateY(-2px);
      box-shadow: 0 4px 12px rgba(250, 112, 154, 0.4);
    }

    /* REJECTION FORM */
    .rejection-form {
      display: none;
      margin-top: 20px;
      padding: 20px;
      background: rgba(254, 202, 87, 0.1);
      border-radius: 10px;
      border-left: 4px solid #feca57;
    }

    .rejection-form.active {
      display: block;
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

    .rejection-form h4 {
      color: #feca57;
      margin-bottom: 10px;
    }

    .rejection-form p {
      color: var(--text-muted);
      font-size: 13px;
      margin-bottom: 15px;
    }

    .rejection-form textarea {
      width: 100%;
      padding: 12px;
      border: 2px solid #feca57;
      border-radius: 8px;
      margin: 10px 0;
      font-family: inherit;
      resize: vertical;
      min-height: 100px;
      background: var(--sidebar-hover);
      color: var(--text-color);
    }

    .rejection-form textarea:focus {
      outline: none;
      border-color: var(--primary-color);
      box-shadow: 0 0 0 3px rgba(141, 11, 65, 0.2);
    }

    .rejection-form textarea::placeholder {
      color: var(--text-muted);
    }

    /* EMPTY STATE */
    .empty-state {
      text-align: center;
      padding: 80px 20px;
      background: var(--card-bg);
      border-radius: 15px;
      border: 1px solid var(--border-color);
    }

    .empty-state i {
      font-size: 80px;
      opacity: 0.2;
      color: #43e97b;
    }

    .empty-state h3 {
      margin-top: 20px;
      color: #ffffff;
    }

    .empty-state p {
      color: var(--text-muted);
    }

    /* MODAL */
    .modal {
      display: none;
      position: fixed;
      z-index: 2000;
      left: 0;
      top: 0;
      width: 100%;
      height: 100%;
      background-color: rgba(0,0,0,0.95);
      animation: fadeIn 0.3s ease;
    }

    @keyframes fadeIn {
      from { opacity: 0; }
      to { opacity: 1; }
    }

    .modal-content {
      margin: auto;
      display: block;
      max-width: 90%;
      max-height: 90%;
      margin-top: 50px;
      border-radius: 12px;
    }

    .close-modal {
      position: absolute;
      top: 20px;
      right: 35px;
      color: #f1f1f1;
      font-size: 50px;
      font-weight: bold;
      cursor: pointer;
      transition: all 0.3s;
    }

    .close-modal:hover {
      color: var(--primary-color);
      transform: rotate(90deg);
    }

    .modal-caption {
      text-align: center;
      color: white;
      padding: 20px;
      font-size: 16px;
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

      .actions-container {
        flex-direction: column;
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
            <a href="verify-landlord.php" class="active" data-tooltip="Verify Landlord">
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
      <h1><i class='bx bx-shield-check'></i> Landlord Verification</h1>
      <p>Review documentation and approve or reject landlord applications</p>
    </header>

    <!-- Statistics Cards -->
    <div class="stats-container">
      <div class="stat-card pending">
        <h3><?= $stats['pending_count'] ?></h3>
        <p><i class='bx bx-time-five'></i> Pending Reviews</p>
      </div>
      <div class="stat-card verified">
        <h3><?= $stats['verified_count'] ?></h3>
        <p><i class='bx bx-check-circle'></i> Verified Landlords</p>
      </div>
      <div class="stat-card rejected">
        <h3><?= $stats['rejected_count'] ?></h3>
        <p><i class='bx bx-x-circle'></i> Rejected Applications</p>
      </div>
    </div>

    <!-- Success/Error Messages -->
    <?php if (isset($_GET['success'])): ?>
      <div class="message-box success">
        <i class='bx bx-check-circle'></i>
        <span>
          <?php if ($_GET['success'] == 'verified'): ?>
            Landlord verified successfully! They can now create property listings.
          <?php elseif ($_GET['success'] == 'rejected'): ?>
            Landlord application rejected. They will be notified to resubmit documents.
          <?php endif; ?>
        </span>
      </div>
    <?php endif; ?>

    <?php if (isset($_GET['error'])): ?>
      <div class="message-box error">
        <i class='bx bx-error-circle'></i>
        <span>Error processing verification request. Please try again.</span>
      </div>
    <?php endif; ?>

    <!-- Verification List -->
    <section class="verify-list">
      <?php if ($verify_result->num_rows > 0): ?>
        <?php while($landlord = $verify_result->fetch_assoc()): 
          // Check which documents are present
          $documents = [
            'valid_id' => ['name' => 'Valid Government ID', 'icon' => 'bx-id-card', 'path' => $landlord['valid_id']],
            'proof_of_ownership' => ['name' => 'Proof of Ownership', 'icon' => 'bx-home-alt', 'path' => $landlord['proof_of_ownership']],
            'landlord_insurance' => ['name' => 'Landlord Insurance', 'icon' => 'bx-shield-alt-2', 'path' => $landlord['landlord_insurance']],
            'gas_safety_cert' => ['name' => 'Gas Safety Certificate', 'icon' => 'bx-gas-pump', 'path' => $landlord['gas_safety_cert']],
            'electric_safety_cert' => ['name' => 'Electrical Safety Cert', 'icon' => 'bx-bolt', 'path' => $landlord['electric_safety_cert']],
            'lease_agreement' => ['name' => 'Lease Agreement', 'icon' => 'bx-file', 'path' => $landlord['lease_agreement']]
          ];
          
          $uploadedCount = 0;
          foreach ($documents as $doc) {
            if (!empty($doc['path'])) $uploadedCount++;
          }
          
          $isComplete = $uploadedCount === 6;
        ?>
          <div class="verify-card">
            <div class="card-layout">
              <!-- Left Section: Landlord Info -->
              <div class="landlord-section">
                <div class="landlord-header-compact">
                  <img src="<?= !empty($landlord['profile_pic']) ? htmlspecialchars($landlord['profile_pic']) : 'https://via.placeholder.com/80' ?>" 
                       alt="Landlord" class="profile-pic">
                  
                  <div class="landlord-info">
                    <h3><?= htmlspecialchars($landlord['firstName'] . ' ' . $landlord['lastName']) ?></h3>
                    <div class="info-item">
                      <i class='bx bx-envelope'></i>
                      <span><?= htmlspecialchars($landlord['email']) ?></span>
                    </div>
                    <div class="info-item">
                      <i class='bx bx-user'></i>
                      <span>@<?= htmlspecialchars($landlord['username']) ?></span>
                    </div>
                    <?php if (!empty($landlord['phoneNum'])): ?>
                      <div class="info-item">
                        <i class='bx bx-phone'></i>
                        <span><?= htmlspecialchars($landlord['phoneNum']) ?></span>
                      </div>
                    <?php endif; ?>
                    <div class="info-item">
                      <i class='bx bx-calendar'></i>
                      <span>Submitted: <?= $landlord['submission_date'] ? date('M d, Y \a\t g:i A', strtotime($landlord['submission_date'])) : 'N/A' ?></span>
                    </div>
                  </div>
                </div>

                <!-- Document Completeness Warning -->
                <?php if (!$isComplete): ?>
                  <div class="incomplete-warning">
                    <i class='bx bx-error'></i>
                    <span><strong>Warning:</strong> Only <?= $uploadedCount ?>/6 documents uploaded.</span>
                  </div>
                <?php endif; ?>
              </div>

              <!-- Right Section: Documents + Actions -->
              <div class="documents-actions-section">
                <!-- Documents Header -->
                <div class="documents-header-compact">
                  <div class="doc-badge">
                    <i class='bx bx-file-blank'></i> Submitted Documents
                    <span class="document-count"><?= $uploadedCount ?>/6</span>
                  </div>
                </div>

                <!-- Documents Grid -->
                <div class="documents-grid-compact">
                  <?php foreach ($documents as $key => $doc): 
                    $path = $doc['path'];
                    $fullPath = !empty($path) ? '../LANDLORD/' . $path : '';
                    $isImage = !empty($path) && preg_match('/\.(jpg|jpeg|png|gif)$/i', $path);
                    $isMissing = empty($path);
                  ?>
                    <div class="document-item-compact <?= $isMissing ? 'missing' : '' ?>" 
                         <?= !$isMissing ? "onclick=\"openModal('$fullPath', '{$doc['name']}')\"" : '' ?>>
                      <?php if ($isMissing): ?>
                        <div class="doc-icon-small missing-icon">
                          <i class='bx bx-x-circle'></i>
                        </div>
                      <?php elseif ($isImage): ?>
                        <img src="<?= htmlspecialchars($fullPath) ?>" alt="<?= $doc['name'] ?>"
                             onerror="this.src='https://via.placeholder.com/100';">
                      <?php else: ?>
                        <div class="doc-icon-small">
                          <i class='bx bx-file-blank'></i>
                        </div>
                      <?php endif; ?>
                      <div class="doc-label-compact"><?= $doc['name'] ?></div>
                      <?php if (!$isMissing): ?>
                        <a href="<?= htmlspecialchars($fullPath) ?>" target="_blank" class="view-btn-compact" onclick="event.stopPropagation()">
                          <i class='bx bx-show'></i> View Full
                        </a>
                      <?php endif; ?>
                    </div>
                  <?php endforeach; ?>
                </div>

                <!-- Action Buttons -->
                <div class="actions-container">
                  <button class="btn approve" 
                          onclick="verifyLandlord(<?= $landlord['ID'] ?>, 'verified', '<?= htmlspecialchars($landlord['firstName']) ?>')">
                    <i class='bx bx-check-circle'></i> Approve Verification
                  </button>
                  <button class="btn reject" 
                          onclick="toggleRejectForm(<?= $landlord['ID'] ?>)">
                    <i class='bx bx-x-circle'></i> Reject Application
                  </button>
                </div>
              </div>
            </div>

            <!-- Rejection Form (Full Width Below) -->
            <div class="rejection-form" id="reject-form-<?= $landlord['ID'] ?>">
              <h4><i class='bx bx-error'></i> Provide Rejection Reason</h4>
              <p>This message will be shown to the landlord. Be specific about what needs to be corrected.</p>
              <form method="POST" action="verify_action.php" onsubmit="return confirmRejection(<?= $landlord['ID'] ?>)">
                <input type="hidden" name="id" value="<?= $landlord['ID'] ?>">
                <input type="hidden" name="action" value="rejected">
                <textarea name="rejection_reason" id="rejection-reason-<?= $landlord['ID'] ?>" 
                          placeholder="Example: Gas Safety Certificate is expired. Please upload a current certificate dated within the last 12 months..."
                          required></textarea>
                <div style="display: flex; gap: 10px;">
                  <button type="submit" class="btn reject" style="margin: 0;">
                    <i class='bx bx-send'></i> Submit Rejection
                  </button>
                  <button type="button" class="btn" onclick="toggleRejectForm(<?= $landlord['ID'] ?>)"
                          style="background: #6c757d; color: white; margin: 0;">
                    <i class='bx bx-x'></i> Cancel
                  </button>
                </div>
              </form>
            </div>
          </div>
        <?php endwhile; ?>
      <?php else: ?>
        <div class="empty-state">
          <i class='bx bx-check-double'></i>
          <h3>All Caught Up!</h3>
          <p>No pending verification requests at the moment.</p>
          <p style="margin-top: 10px; font-size: 14px;">
            You have reviewed all landlord applications.
          </p>
        </div>
      <?php endif; ?>
    </section>
  </main>

  <!-- Image Modal -->
  <div id="imageModal" class="modal" onclick="closeModal()">
    <span class="close-modal" onclick="closeModal()">&times;</span>
    <img class="modal-content" id="modalImage">
    <div class="modal-caption" id="modalCaption"></div>
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

    // Verify landlord function
    function verifyLandlord(landlordId, action, name) {
      const message = action === 'verified' 
        ? `approve ${name}'s verification and allow them to create property listings` 
        : `reject ${name}'s verification request`;
      
      if(confirm(`Are you sure you want to ${message}?`)) {
        window.location.href = `verify_action.php?id=${landlordId}&action=${action}`;
      }
    }

    // Toggle rejection form
    function toggleRejectForm(landlordId) {
      const form = document.getElementById('reject-form-' + landlordId);
      form.classList.toggle('active');
      
      if (form.classList.contains('active')) {
        setTimeout(() => {
          const textarea = document.getElementById('rejection-reason-' + landlordId);
          textarea.focus();
        }, 100);
      }
    }

    // Confirm rejection
    function confirmRejection(landlordId) {
      const textarea = document.getElementById('rejection-reason-' + landlordId);
      const reason = textarea.value.trim();
      
      if (reason.length < 20) {
        alert('Please provide a more detailed rejection reason (at least 20 characters).');
        textarea.focus();
        return false;
      }
      
      return confirm('Are you sure you want to reject this application? The landlord will need to resubmit their documents.');
    }

    // Modal functions
    function openModal(imageSrc, caption) {
      const modal = document.getElementById('imageModal');
      const modalImg = document.getElementById('modalImage');
      const modalCaption = document.getElementById('modalCaption');
      
      modal.style.display = 'block';
      modalImg.src = imageSrc;
      modalCaption.textContent = caption;
      document.body.style.overflow = 'hidden';
    }

    function closeModal() {
      const modal = document.getElementById('imageModal');
      modal.style.display = 'none';
      document.body.style.overflow = 'auto';
    }

    // Close modal with Escape key
    document.addEventListener('keydown', function(event) {
      if (event.key === 'Escape') {
        closeModal();
      }
    });

    // Prevent modal from closing when clicking on image
    document.getElementById('modalImage').addEventListener('click', function(event) {
      event.stopPropagation();
    });
  </script>

</body>
</html>