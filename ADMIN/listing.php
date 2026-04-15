<?php
require_once '../session_auth.php';
require_once '../connection.php';

// Get admin info
$admin_name = $_SESSION['username'];

// Get pending verification count for badge
$pending_verification = $conn->query("SELECT COUNT(*) as count FROM landlordtbl WHERE verification_status='pending'")->fetch_assoc()['count'];

// Handle search
$search = isset($_GET['search']) ? trim($_GET['search']) : '';

// Handle verification filter
$filter = isset($_GET['filter']) ? $_GET['filter'] : 'all';

// Fetch listings with search and filter
$listings_query = "SELECT * FROM listingtbl WHERE 1=1";

if (!empty($search)) {
  $listings_query .= " AND (listingName LIKE '%$search%' OR barangay LIKE '%$search%' OR listingDesc LIKE '%$search%')";
}

if ($filter !== 'all') {
  $listings_query .= " AND verification_status = '$filter'";
}

$listings_query .= " ORDER BY ID DESC";
$listings_result = $conn->query($listings_query);

// Get statistics
$total_listings = $conn->query("SELECT COUNT(*) as count FROM listingtbl")->fetch_assoc()['count'];
$available_listings = $conn->query("SELECT COUNT(*) as count FROM listingtbl WHERE availability='available'")->fetch_assoc()['count'];
$occupied_listings = $conn->query("SELECT COUNT(*) as count FROM listingtbl WHERE availability='occupied'")->fetch_assoc()['count'];
?>

<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Property Listings - Admin Dashboard</title>
  <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>

  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

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

    .sidebar.collapsed {
      width: 75px;
    }

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

    .sidebar .menu-bar::-webkit-scrollbar {
      width: 4px;
    }

    .sidebar .menu-bar::-webkit-scrollbar-thumb {
      background: var(--border-color);
      border-radius: 10px;
    }

    .sidebar .menu {
      padding: 0 12px;
    }

    .sidebar .menu-links {
      padding: 0;
      margin: 0;
    }

    .sidebar li {
      list-style: none;
      margin: 4px 0;
    }

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

    .sidebar li a:hover {
      background: var(--sidebar-hover);
    }

    .sidebar li a:hover::before {
      transform: scaleY(1);
    }

    .sidebar li a.active {
      background: linear-gradient(90deg, rgba(141, 11, 65, 0.15) 0%, rgba(141, 11, 65, 0.05) 100%);
    }

    .sidebar li a.active::before {
      transform: scaleY(1);
    }

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

    .sidebar.collapsed~.content {
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

    /* ========== STATS BAR ========== */
    .stats-bar {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
      gap: 20px;
      margin-bottom: 30px;
    }

    .stat-box {
      background: var(--card-bg);
      border-radius: 12px;
      padding: 20px;
      border: 1px solid var(--border-color);
      box-shadow: 0 2px 8px rgba(0, 0, 0, 0.3);
      display: flex;
      align-items: center;
      gap: 16px;
      transition: all 0.3s;
    }

    .stat-box:hover {
      transform: translateY(-4px);
      box-shadow: 0 4px 16px rgba(0, 0, 0, 0.4);
      border-color: var(--stat-color);
    }

    .stat-box.total {
      --stat-color: #667eea;
    }

    .stat-box.available {
      --stat-color: #43e97b;
    }

    .stat-box.occupied {
      --stat-color: #feca57;
    }

    .stat-icon {
      width: 50px;
      height: 50px;
      border-radius: 12px;
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: 24px;
      background: rgba(255, 255, 255, 0.05);
      color: var(--stat-color);
    }

    .stat-info h3 {
      font-size: 28px;
      font-weight: 700;
      color: #ffffff;
      margin: 0 0 4px 0;
    }

    .stat-info p {
      font-size: 13px;
      color: var(--text-muted);
      margin: 0;
    }

    /* ========== SEARCH BAR ========== */
    .search-section {
      background: var(--card-bg);
      padding: 24px;
      border-radius: 12px;
      box-shadow: 0 2px 8px rgba(0, 0, 0, 0.3);
      margin-bottom: 24px;
      border: 1px solid var(--border-color);
    }

    .search-bar {
      display: flex;
      gap: 12px;
      align-items: center;
    }

    .search-bar input {
      flex: 1;
      padding: 12px 16px;
      border: 2px solid var(--border-color);
      border-radius: 10px;
      font-size: 14px;
      transition: all 0.3s;
      background: var(--sidebar-hover);
      color: #ffffff;
    }

    .search-bar input::placeholder {
      color: var(--text-muted);
    }

    .search-bar input:focus {
      outline: none;
      border-color: var(--primary-color);
      box-shadow: 0 0 0 3px rgba(141, 11, 65, 0.2);
      background: var(--border-color);
    }

    .btn-search {
      padding: 12px 24px;
      background: var(--primary-color);
      color: white;
      border: none;
      border-radius: 10px;
      font-weight: 600;
      cursor: pointer;
      transition: all 0.3s;
      display: flex;
      align-items: center;
      gap: 8px;
    }

    .btn-search:hover {
      background: var(--primary-hover);
      transform: translateY(-2px);
      box-shadow: 0 4px 12px rgba(141, 11, 65, 0.3);
    }

    .btn-clear {
      padding: 12px 24px;
      background: #6c757d;
      color: white;
      border: none;
      border-radius: 10px;
      font-weight: 600;
      cursor: pointer;
      text-decoration: none;
      display: inline-flex;
      align-items: center;
      gap: 8px;
      transition: all 0.3s;
    }

    .btn-clear:hover {
      background: #5a6268;
      color: white;
    }

    /* ========== LISTING CARDS ========== */
    .listings-container {
      background: var(--card-bg);
      border-radius: 12px;
      padding: 24px;
      box-shadow: 0 2px 8px rgba(0, 0, 0, 0.3);
      border: 1px solid var(--border-color);
    }

    .card-grid {
      display: grid;
      grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
      gap: 24px;
    }

    .listing-card {
      background: var(--sidebar-hover);
      border-radius: 12px;
      overflow: hidden;
      box-shadow: 0 2px 8px rgba(0, 0, 0, 0.3);
      transition: all 0.3s ease;
      opacity: 1;
      border: 1px solid var(--border-color);
    }

    .listing-card:hover {
      transform: translateY(-8px);
      box-shadow: 0 8px 24px rgba(0, 0, 0, 0.4);
      border-color: var(--primary-color);
    }

    .listing-card.is-hidden {
      opacity: 0;
      transform: scale(0.97);
      pointer-events: none;
    }

    .listing-card img {
      width: 100%;
      height: 200px;
      object-fit: cover;
      transition: transform 0.3s ease;
      background: var(--border-color);
    }

    .listing-card:hover img {
      transform: scale(1.05);
    }

    .card-body {
      padding: 20px;
      display: flex;
      flex-direction: column;
      gap: 12px;
    }

    .card-body h3 {
      margin: 0;
      font-size: 18px;
      font-weight: 600;
      color: #ffffff;
    }

    .card-body .price {
      font-weight: 700;
      font-size: 20px;
      color: var(--primary-color);
      margin: 0;
    }

    .card-body .details {
      font-size: 14px;
      color: var(--text-muted);
      line-height: 1.6;
      flex-grow: 1;
    }

    .card-body .details i {
      color: var(--primary-color);
      margin-right: 6px;
    }

    .availability-badge {
      display: inline-flex;
      align-items: center;
      padding: 6px 12px;
      border-radius: 20px;
      font-size: 12px;
      font-weight: 600;
      gap: 6px;
    }

    .availability-badge.available {
      background: rgba(67, 233, 123, 0.15);
      color: #43e97b;
    }

    .availability-badge.occupied {
      background: rgba(254, 202, 87, 0.15);
      color: #feca57;
    }

    .card-actions {
      display: flex;
      gap: 10px;
      margin-top: 12px;
    }

    .btn-outline {
      flex: 1;
      border: 2px solid var(--primary-color);
      background: transparent;
      color: var(--primary-color);
      padding: 10px;
      border-radius: 8px;
      cursor: pointer;
      font-weight: 600;
      font-size: 13px;
      transition: all 0.3s;
      display: flex;
      align-items: center;
      justify-content: center;
      gap: 6px;
    }

    .btn-outline:hover {
      background: var(--primary-color);
      color: white;
      transform: translateY(-2px);
      box-shadow: 0 4px 12px rgba(141, 11, 65, 0.3);
    }

    .btn-primary {
      flex: 1;
      background: var(--primary-color);
      color: white;
      border: none;
      padding: 10px;
      border-radius: 8px;
      cursor: pointer;
      font-weight: 600;
      font-size: 13px;
      transition: all 0.3s;
      display: flex;
      align-items: center;
      justify-content: center;
      gap: 6px;
    }

    .btn-primary:hover {
      background: var(--primary-hover);
      transform: translateY(-2px);
      box-shadow: 0 4px 12px rgba(141, 11, 65, 0.3);
    }

    /* Empty State */
    .empty-state {
      text-align: center;
      padding: 60px 20px;
      color: var(--text-muted);
    }

    .empty-state i {
      font-size: 64px;
      opacity: 0.3;
      margin-bottom: 16px;
      color: #6c757d;
    }

    .empty-state p {
      font-size: 16px;
      font-weight: 500;
    }

    /* ========== RESPONSIVE ========== */
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

      .sidebar.collapsed~.content {
        margin-left: 0;
      }

      .card-grid {
        grid-template-columns: 1fr;
      }
    }

    /* ========== EMERGENCY ALERT MODAL STYLES  ========== */
    .emergency-modal {
      display: none;
      position: fixed;
      z-index: 10000;
      left: 0;
      top: 0;
      width: 100%;
      height: 100%;
      background-color: rgba(0, 0, 0, 0.5);
      backdrop-filter: blur(3px);
      overflow-y: auto;
    }

    .emergency-modal-content {
      background: #ffffff;
      margin: 20px auto;
      width: 90%;
      max-width: 550px;
      border-radius: 20px;
      border: none;
      animation: slideIn 0.3s ease;
      overflow: hidden;
      position: relative;
      box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
    }

    @keyframes slideIn {
      from {
        transform: translateY(-50px);
        opacity: 0;
      }

      to {
        transform: translateY(0);
        opacity: 1;
      }
    }

    .emergency-modal-header {
      background: linear-gradient(135deg, #6a0831, #811621);
      color: white;
      padding: 20px 24px;
      display: flex;
      justify-content: space-between;
      align-items: center;
      position: sticky;
      top: 0;
      z-index: 10;
    }

    .emergency-modal-header h2 {
      margin: 0;
      font-size: 22px;
      display: flex;
      align-items: center;
      gap: 10px;
      color: white;
    }

    .emergency-close {
      font-size: 28px;
      font-weight: bold;
      cursor: pointer;
      color: white;
      transition: 0.3s;
      line-height: 1;
    }

    .emergency-close:hover {
      color: #ddd;
      transform: scale(1.1);
    }

    .emergency-form-body {
      max-height: calc(80vh - 80px);
      overflow-y: auto;
      padding: 0;
    }

    .emergency-form-body::-webkit-scrollbar {
      width: 6px;
    }

    .emergency-form-body::-webkit-scrollbar-track {
      background: var(--border-color);
      border-radius: 10px;
    }

    .emergency-form-body::-webkit-scrollbar-thumb {
      background: var(--primary-color);
      border-radius: 10px;
    }

    .emergency-form-group {
      padding: 20px 24px;
      border-bottom: 1px solid #e9ecef;
    }

    .emergency-form-group label {
      display: block;
      font-weight: 600;
      margin-bottom: 8px;
      color: #2d3748;
      font-size: 14px;
    }

    .emergency-form-group select,
    .emergency-form-group input,
    .emergency-form-group textarea {
      width: 100%;
      padding: 12px;
      border: 1px solid #e2e8f0;
      border-radius: 10px;
      background: #ffffff;
      color: #1a202c;
      font-size: 14px;
      transition: all 0.3s;
    }

    .emergency-form-group select:focus,
    .emergency-form-group input:focus,
    .emergency-form-group textarea:focus {
      outline: none;
      border-color: #dc3545;
      box-shadow: 0 0 0 3px rgba(220, 53, 69, 0.1);
    }

    .emergency-form-group select option {
      background: #ffffff;
      color: #1a202c;
    }

    .emergency-preview {
      background: #f8f9fa;
      border-radius: 12px;
      padding: 15px;
      margin-top: 10px;
      border-left: 4px solid #dc3545;
    }

    .emergency-preview-badge {
      font-size: 24px;
      margin-bottom: 8px;
      display: inline-block;
    }

    .emergency-preview-text {
      color: #6c757d;
      font-size: 13px;
    }

    .emergency-preview-text strong {
      color: #2d3748;
      display: block;
      margin-bottom: 5px;
    }

    .emergency-btn-send {
      background: linear-gradient(135deg, #6a0831, #811621);
      color: white;
      border: none;
      padding: 14px;
      margin: 0 24px 24px;
      width: calc(100% - 48px);
      border-radius: 10px;
      font-size: 16px;
      font-weight: 600;
      cursor: pointer;
      transition: all 0.3s;
    }

    .emergency-btn-send:hover {
      transform: translateY(-2px);
      box-shadow: 0 5px 20px rgba(220, 53, 69, 0.4);
    }

    .emergency-btn-send:disabled {
      opacity: 0.6;
      cursor: not-allowed;
      transform: none;
    }

    .emergency-btn-send i {
      margin-right: 8px;
    }

    /* Placeholder text color */
    .emergency-form-group input::placeholder,
    .emergency-form-group textarea::placeholder {
      color: #adb5bd;
    }
  </style>
  <link rel="stylesheet" href="admin-theme.css">
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
            <a href="listing.php" class="active" data-tooltip="Listing">
              <i class='bx bx-building-house icon'></i>
              <span class="text">Listing</span>
            </a>
          </li>
          <li>
            <a href="verify-properties.php" data-tooltip="Verify Properties">
              <i class='bx bx-shield-alt-2 icon'></i>
              <span class="text">Verify Properties</span>
              <?php
              $pending_props = $conn->query("SELECT COUNT(*) as count FROM listingtbl WHERE verification_status='pending'")->fetch_assoc()['count'];
              if ($pending_props > 0):
              ?>
                <span class="badge"><?= $pending_props ?></span>
              <?php endif; ?>
            </a>
          </li>
          <li>
            <a href="verify-landlord.php" data-tooltip="Verify Landlord">
              <i class='bx bx-shield-check icon'></i>
              <span class="text">Verify Landlord</span>
              <?php if ($pending_verification > 0): ?>
                <span class="badge"><?= $pending_verification ?></span>
              <?php endif; ?>
            </a>
          </li>
          <li>
            <a href="#" id="emergencyAlertBtn" data-tooltip="Alert Users">
              <i class='bx bx-alert-square icon'></i>
              <span class="text">Alert Users</span>
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
      <h1>Property Listings</h1>
      <p>Manage all property listings across the platform</p>
    </div>

    <!-- Stats Bar -->
    <div class="stats-bar">
      <div class="stat-box total">
        <div class="stat-icon">
          <i class='bx bx-building'></i>
        </div>
        <div class="stat-info">
          <h3><?= $total_listings ?></h3>
          <p>Total Listings</p>
        </div>
      </div>

      <div class="stat-box available">
        <div class="stat-icon">
          <i class='bx bx-check-circle'></i>
        </div>
        <div class="stat-info">
          <h3><?= $available_listings ?></h3>
          <p>Available</p>
        </div>
      </div>

      <div class="stat-box occupied">
        <div class="stat-icon">
          <i class='bx bx-key'></i>
        </div>
        <div class="stat-info">
          <h3><?= $occupied_listings ?></h3>
          <p>Occupied</p>
        </div>
      </div>
    </div>

    <!-- Verification Filter -->
    <div class="stats-bar" style="margin-bottom: 20px;">
      <a href="?filter=all" style="text-decoration: none;">
        <div class="stat-box" style="cursor: pointer;">
          <div class="stat-icon"><i class='bx bx-building'></i></div>
          <div class="stat-info">
            <h3><?= $conn->query("SELECT COUNT(*) as count FROM listingtbl")->fetch_assoc()['count'] ?></h3>
            <p>All Properties</p>
          </div>
        </div>
      </a>

      <a href="?filter=pending" style="text-decoration: none;">
        <div class="stat-box" style="cursor: pointer; --stat-color: #fbbf24;">
          <div class="stat-icon"><i class='bx bx-time'></i></div>
          <div class="stat-info">
            <h3><?= $conn->query("SELECT COUNT(*) as count FROM listingtbl WHERE verification_status='pending'")->fetch_assoc()['count'] ?></h3>
            <p>Pending</p>
          </div>
        </div>
      </a>

      <a href="?filter=approved" style="text-decoration: none;">
        <div class="stat-box" style="cursor: pointer; --stat-color: #10b981;">
          <div class="stat-icon"><i class='bx bx-check-circle'></i></div>
          <div class="stat-info">
            <h3><?= $conn->query("SELECT COUNT(*) as count FROM listingtbl WHERE verification_status='approved'")->fetch_assoc()['count'] ?></h3>
            <p>Approved</p>
          </div>
        </div>
      </a>

      <a href="?filter=rejected" style="text-decoration: none;">
        <div class="stat-box" style="cursor: pointer; --stat-color: #ef4444;">
          <div class="stat-icon"><i class='bx bx-x-circle'></i></div>
          <div class="stat-info">
            <h3><?= $conn->query("SELECT COUNT(*) as count FROM listingtbl WHERE verification_status='rejected'")->fetch_assoc()['count'] ?></h3>
            <p>Rejected</p>
          </div>
        </div>
      </a>
    </div>

    <!-- Search Section -->
    <div class="search-section">
      <form method="GET" class="search-bar">
        <input type="text"
          name="search"
          id="searchInput"
          placeholder="🔍 Search by property name, location..."
          value="<?= htmlspecialchars($search) ?>">
        <button type="submit" class="btn-search">
          <i class='bx bx-search'></i> Search
        </button>
        <?php if (!empty($search)): ?>
          <a href="listing.php" class="btn-clear">
            <i class='bx bx-x'></i> Clear
          </a>
        <?php endif; ?>
      </form>
    </div>

    <!-- Listings Grid -->
    <div class="listings-container">
      <?php if ($listings_result && $listings_result->num_rows > 0): ?>
        <div class="card-grid">
          <?php while ($listing = $listings_result->fetch_assoc()): ?>
            <div class="listing-card">
              <?php
              $images = !empty($listing['images']) ? $listing['images'] : '';
              $first_image = '../img/house1.jpeg';

              if (!empty($images)) {
                $image_array = json_decode($images, true);

                if (!is_array($image_array)) {
                  $image_array = array_map('trim', explode(',', $images));
                }

                if (!empty($image_array) && isset($image_array[0])) {
                  $raw_image = trim($image_array[0]);

                  if (!empty($raw_image)) {
                    if (strpos($raw_image, '../LANDLORD/') === 0) {
                      $first_image = $raw_image;
                    } elseif (strpos($raw_image, 'uploads/') === 0) {
                      $first_image = '../LANDLORD/' . $raw_image;
                    } elseif (strpos($raw_image, 'LANDLORD/') === 0) {
                      $first_image = '../' . $raw_image;
                    } else {
                      $first_image = '../LANDLORD/uploads/' . $raw_image;
                    }
                  }
                }
              }
              ?>
              <img src="<?= htmlspecialchars($first_image) ?>"
                alt="<?= htmlspecialchars($listing['listingName']) ?>"
                loading="lazy"
                onerror="this.onerror=null; this.src='../img/house1.jpeg';">
              <div class="card-body">
                <h3><?= htmlspecialchars($listing['listingName']) ?></h3>
                <div class="price">₱<?= number_format($listing['price']) ?>/month</div>

                <?php if (isset($listing['verification_status'])): ?>
                  <?php if ($listing['verification_status'] === 'pending'): ?>
                    <span class="availability-badge" style="background: rgba(251, 191, 36, 0.15); color: #fbbf24;">
                      <i class='bx bx-time'></i>
                      Pending Verification
                    </span>
                  <?php elseif ($listing['verification_status'] === 'rejected'): ?>
                    <span class="availability-badge" style="background: rgba(239, 68, 68, 0.15); color: #ef4444;">
                      <i class='bx bx-x-circle'></i>
                      Rejected
                    </span>
                  <?php else: ?>
                    <span class="availability-badge <?= $listing['availability'] ?>">
                      <i class='bx <?= $listing['availability'] == 'available' ? 'bx-check-circle' : 'bx-lock' ?>'></i>
                      <?= ucfirst($listing['availability']) ?>
                    </span>
                  <?php endif; ?>
                <?php else: ?>
                  <span class="availability-badge <?= $listing['availability'] ?>">
                    <i class='bx <?= $listing['availability'] == 'available' ? 'bx-check-circle' : 'bx-lock' ?>'></i>
                    <?= ucfirst($listing['availability']) ?>
                  </span>
                <?php endif; ?>

                <div class="details">
                  <div><i class='bx bx-map'></i><?= htmlspecialchars($listing['barangay']) ?><?= !empty($listing['city']) ? ', ' . htmlspecialchars($listing['city']) : '' ?></div>
                  <?php if (!empty($listing['bedrooms'])): ?>
                    <div><i class='bx bx-bed'></i><?= htmlspecialchars($listing['bedrooms']) ?> Bedrooms</div>
                  <?php endif; ?>
                  <?php if (!empty($listing['bathrooms'])): ?>
                    <div><i class='bx bx-bath'></i><?= htmlspecialchars($listing['bathrooms']) ?> Bathrooms</div>
                  <?php endif; ?>
                  <?php if (!empty($listing['rooms'])): ?>
                    <div><i class='bx bx-door-open'></i><?= htmlspecialchars($listing['rooms']) ?> Rooms</div>
                  <?php endif; ?>
                </div>

                <div class="card-actions">
                  <button class="btn-outline" onclick="viewListing(<?= $listing['ID'] ?>)">
                    <i class='bx bx-show'></i> View
                  </button>
                  <button class="btn-primary" onclick="deleteListing(<?= $listing['ID'] ?>, '<?= htmlspecialchars($listing['listingName']) ?>')">
                    <i class='bx bx-trash'></i> Remove
                  </button>
                </div>
              </div>
            </div>
          <?php endwhile; ?>
        </div>
      <?php else: ?>
        <div class="empty-state">
          <i class='bx bx-building-house'></i>
          <p><?= !empty($search) ? 'No listings found matching your search' : 'No property listings available' ?></p>
        </div>
      <?php endif; ?>
    </div>
  </main>

  <!-- ========== EMERGENCY ALERT MODAL (LIGHT THEME FIXED) ========== -->
  <div id="emergencyModal" class="emergency-modal">
    <div class="emergency-modal-content">
      <div class="emergency-modal-header">
        <h2>
          <i class='bx bx-alarm-exclamation'></i>
          Send Emergency Alert
        </h2>
        <span class="emergency-close">&times;</span>
      </div>

      <form id="emergencyForm">
        <div class="emergency-form-body">
          <div class="emergency-form-group">
            <label>⚠️ Alert Type:</label>
            <select id="alertType" required>
              <option value="flood">🌊 Flood Alert</option>
              <option value="earthquake">🌋 Earthquake Alert</option>
              <option value="fire">🔥 Fire Alert</option>
              <option value="storm">🌪️ Storm Alert</option>
              <option value="typhoon">🌀 Typhoon Alert</option>
            </select>
          </div>

          <div class="emergency-form-group">
            <label>📊 Severity Level:</label>
            <select id="severity" required>
              <option value="advisory">📢 Advisory - Be Aware</option>
              <option value="alert">⚠️ Alert - Be Prepared</option>
              <option value="warning">🚨 Warning - Take Action</option>
              <option value="emergency">🆘 Emergency - Immediate Action Required</option>
            </select>
          </div>

          <div class="emergency-form-group">
            <label>📝 Alert Title:</label>
            <input type="text" id="alertTitle" placeholder="e.g., Typhoon Signal #3 Alert" required>
          </div>

          <div class="emergency-form-group">
            <label>📄 Alert Message:</label>
            <textarea id="alertMessage" rows="4" placeholder="Provide clear instructions and safety measures..." required></textarea>
          </div>

          <div class="emergency-form-group">
            <label>👁️ Preview:</label>
            <div class="emergency-preview">
              <div class="emergency-preview-badge" id="previewBadge">📢</div>
              <div class="emergency-preview-text" id="previewText">
                <strong>Alert Title</strong>
                <small>Your message will appear here...</small>
              </div>
            </div>
          </div>
        </div>

        <button type="submit" class="emergency-btn-send" id="sendAlertBtn">
          🚨 Send Alert to ALL Users
        </button>
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

    // View listing
    function viewListing(id) {
      window.location.href = `view-listing.php?id=${id}`;
    }

    // Delete listing
    function deleteListing(id, name) {
      if (confirm(`Are you sure you want to remove "${name}"?\n\nThis action cannot be undone.`)) {
        window.location.href = `delete-listing.php?id=${id}`;
      }
    }

    // Live search (client-side filtering for smooth UX)
    const searchInput = document.getElementById('searchInput');
    const cards = Array.from(document.querySelectorAll('.listing-card'));
    const FADE_MS = 220;

    function hideCard(card) {
      if (card.classList.contains('is-hidden')) return;
      card.classList.add('is-hidden');
      setTimeout(() => {
        card.style.display = 'none';
      }, FADE_MS);
    }

    function showCard(card) {
      if (!card.classList.contains('is-hidden') && card.style.display !== 'none') return;
      card.style.display = '';
      void card.offsetWidth;
      card.classList.remove('is-hidden');
    }

    function filterCards(query) {
      const q = query.trim().toLowerCase();
      cards.forEach(card => {
        const title = card.querySelector('.card-body h3')?.innerText || '';
        const details = card.querySelector('.card-body .details')?.innerText || '';
        const price = card.querySelector('.card-body .price')?.innerText || '';
        const text = (title + ' ' + details + ' ' + price).toLowerCase();

        if (q === '' || text.includes(q)) {
          showCard(card);
        } else {
          hideCard(card);
        }
      });
    }

    function debounce(fn, wait) {
      let t;
      return function(...args) {
        clearTimeout(t);
        t = setTimeout(() => fn.apply(this, args), wait);
      };
    }

    const onInput = debounce((e) => {
      filterCards(e.target.value);
    }, 120);

    if (searchInput) {
      searchInput.addEventListener('input', onInput);
    }

    // ========== EMERGENCY ALERT SYSTEM ==========
    // Get modal elements
    const modal = document.getElementById('emergencyModal');
    const emergencyBtn = document.getElementById('emergencyAlertBtn');
    const closeBtn = document.querySelector('.emergency-close');
    const form = document.getElementById('emergencyForm');
    const sendBtn = document.getElementById('sendAlertBtn');

    // Open modal when clicking Alert Users in sidebar
    if (emergencyBtn) {
      emergencyBtn.addEventListener('click', (e) => {
        e.preventDefault();
        modal.style.display = 'block';
        document.body.style.overflow = 'hidden'; // Prevent body scroll when modal is open
      });
    }

    // Close modal function
    function closeModal() {
      modal.style.display = 'none';
      document.body.style.overflow = ''; // Restore body scroll
    }

    // Close modal
    if (closeBtn) {
      closeBtn.addEventListener('click', closeModal);
    }

    // Close modal when clicking outside
    window.addEventListener('click', (e) => {
      if (e.target === modal) {
        closeModal();
      }
    });

    // Live preview functionality
    const alertType = document.getElementById('alertType');
    const severity = document.getElementById('severity');
    const alertTitle = document.getElementById('alertTitle');
    const alertMessage = document.getElementById('alertMessage');
    const previewBadge = document.getElementById('previewBadge');
    const previewText = document.getElementById('previewText');

    function updatePreview() {
      const type = alertType ? alertType.value : 'flood';
      const sev = severity ? severity.value : 'advisory';
      const title = alertTitle ? (alertTitle.value || 'Alert Title') : 'Alert Title';
      const message = alertMessage ? (alertMessage.value || 'Your message will appear here...') : 'Your message will appear here...';

      const icons = {
        flood: '🌊',
        earthquake: '🌋',
        fire: '🔥',
        storm: '🌪️',
        typhoon: '🌀'
      };

      const severityIcons = {
        advisory: '📢',
        alert: '⚠️',
        warning: '🚨',
        emergency: '🆘'
      };

      if (previewBadge) {
        previewBadge.innerHTML = `${severityIcons[sev]} ${icons[type]}`;
      }
      if (previewText) {
        previewText.innerHTML = `<strong>${escapeHtml(title)}</strong><br><small>${escapeHtml(message.substring(0, 100))}${message.length > 100 ? '...' : ''}</small>`;
      }
    }

    function escapeHtml(text) {
      const div = document.createElement('div');
      div.textContent = text;
      return div.innerHTML;
    }

    if (alertType) alertType.addEventListener('change', updatePreview);
    if (severity) severity.addEventListener('change', updatePreview);
    if (alertTitle) alertTitle.addEventListener('input', updatePreview);
    if (alertMessage) alertMessage.addEventListener('input', updatePreview);

    // Form submission
    if (form) {
      form.addEventListener('submit', async (e) => {
        e.preventDefault();

        // Disable button and show loading
        if (sendBtn) {
          sendBtn.disabled = true;
          sendBtn.innerHTML = '<i class="bx bx-loader-alt bx-spin"></i> Sending Alert...';
        }

        const alertData = {
          alert_type: alertType ? alertType.value : 'flood',
          title: alertTitle ? alertTitle.value : '',
          message: alertMessage ? alertMessage.value : '',
          severity: severity ? severity.value : 'alert'
        };

        // Validate inputs
        if (!alertData.title || !alertData.message) {
          Swal.fire({
            icon: 'error',
            title: 'Missing Information',
            text: 'Please fill in both title and message fields!',
            background: '#1a1d29',
            color: '#fff'
          });
          if (sendBtn) {
            sendBtn.disabled = false;
            sendBtn.innerHTML = '🚨 Send Alert to ALL Users';
          }
          return;
        }

        try {
          const response = await fetch('../api/alerts/send_alert.php', {
            method: 'POST',
            headers: {
              'Content-Type': 'application/json'
            },
            body: JSON.stringify(alertData)
          });

          const result = await response.json();

          if (result.success) {
            Swal.fire({
              icon: 'success',
              title: 'Alert Sent!',
              html: `✅ Emergency alert sent successfully to <strong>${result.recipients}</strong> users!`,
              background: '#1a1d29',
              color: '#fff',
              confirmButtonColor: '#dc3545'
            });

            // Reset form
            if (form) form.reset();
            updatePreview();
            closeModal();
          } else {
            Swal.fire({
              icon: 'error',
              title: 'Failed to Send',
              text: result.message || 'Something went wrong. Please try again.',
              background: '#1a1d29',
              color: '#fff'
            });
          }
        } catch (error) {
          console.error('Error:', error);
          Swal.fire({
            icon: 'error',
            title: 'Connection Error',
            text: 'Failed to connect to the server. Please check your connection.',
            background: '#1a1d29',
            color: '#fff'
          });
        } finally {
          if (sendBtn) {
            sendBtn.disabled = false;
            sendBtn.innerHTML = '🚨 Send Alert to ALL Users';
          }
        }
      });
    }
  </script>

</body>

</html>