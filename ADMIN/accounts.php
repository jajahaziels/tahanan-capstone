<?php
require_once '../session_auth.php';
require_once '../connection.php';

// Get admin info
$admin_name = $_SESSION['username'];

// Handle search and filter
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$verification_filter = isset($_GET['verification']) ? $_GET['verification'] : 'all';

// Fetch landlords with filters
$landlords_query = "SELECT * FROM landlordtbl WHERE 1=1";
if (!empty($search)) {
  $landlords_query .= " AND (firstName LIKE '%$search%' OR lastName LIKE '%$search%' OR email LIKE '%$search%' OR username LIKE '%$search%')";
}
if ($verification_filter !== 'all') {
  $landlords_query .= " AND verification_status = '$verification_filter'";
}
$landlords_query .= " ORDER BY ID DESC";
$landlords_result = $conn->query($landlords_query);

// Fetch tenants with filters
$tenants_query = "SELECT * FROM tenanttbl WHERE 1=1";
if (!empty($search)) {
  $tenants_query .= " AND (firstName LIKE '%$search%' OR lastName LIKE '%$search%' OR email LIKE '%$search%' OR username LIKE '%$search%')";
}
$tenants_query .= " ORDER BY ID DESC";
$tenants_result = $conn->query($tenants_query);

// Get statistics
$total_landlords = $conn->query("SELECT COUNT(*) as count FROM landlordtbl")->fetch_assoc()['count'];
$verified_landlords = $conn->query("SELECT COUNT(*) as count FROM landlordtbl WHERE verification_status='verified'")->fetch_assoc()['count'];
$pending_landlords = $conn->query("SELECT COUNT(*) as count FROM landlordtbl WHERE verification_status='pending'")->fetch_assoc()['count'];
$total_tenants = $conn->query("SELECT COUNT(*) as count FROM tenanttbl")->fetch_assoc()['count'];
?>

<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Manage Accounts - Admin Dashboard</title>
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
      --body-color: #f8f9fa;
      --sidebar-color: #1a1d29;
      --sidebar-hover: #252938;
      --primary-color: rgb(141, 11, 65);
      --primary-hover: rgb(115, 9, 53);
      --text-color: #e4e6eb;
      --text-muted: #8b92a7;
      --border-color: #2d3142;
      --transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    }

    body {
      min-height: 100vh;
      background: #0f1419;
      overflow-x: hidden;
    }

    /* ========== SIDEBAR ========== */
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
      color: #8b92a7;
      font-size: 14px;
    }

    /* ========== STATS CARDS ========== */
    .stats-grid {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
      gap: 20px;
      margin-bottom: 30px;
    }

    .stat-card {
      background: #1a1d29;
      padding: 24px;
      border-radius: 12px;
      box-shadow: 0 2px 8px rgba(0, 0, 0, 0.3);
      position: relative;
      overflow: hidden;
      transition: transform 0.3s, box-shadow 0.3s;
      border: 1px solid #2d3142;
    }

    .stat-card:hover {
      transform: translateY(-4px);
      box-shadow: 0 4px 16px rgba(0, 0, 0, 0.4);
      border-color: var(--primary-color);
    }

    .stat-card::before {
      content: '';
      position: absolute;
      top: 0;
      left: 0;
      width: 100%;
      height: 4px;
      background: linear-gradient(90deg, var(--primary-color) 0%, var(--primary-hover) 100%);
    }

    .stat-card.purple::before {
      background: linear-gradient(90deg, #667eea 0%, #764ba2 100%);
    }

    .stat-card.pink::before {
      background: linear-gradient(90deg, #f093fb 0%, #f5576c 100%);
    }

    .stat-card.blue::before {
      background: linear-gradient(90deg, #4facfe 0%, #00f2fe 100%);
    }

    .stat-card .stat-icon {
      width: 48px;
      height: 48px;
      border-radius: 12px;
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: 24px;
      margin-bottom: 16px;
      background: rgba(141, 11, 65, 0.1);
      color: var(--primary-color);
    }

    .stat-card.purple .stat-icon {
      background: rgba(102, 126, 234, 0.1);
      color: #667eea;
    }

    .stat-card.pink .stat-icon {
      background: rgba(240, 147, 251, 0.1);
      color: #f5576c;
    }

    .stat-card.blue .stat-icon {
      background: rgba(79, 172, 254, 0.1);
      color: #4facfe;
    }

    .stat-card h4 {
      font-size: 13px;
      font-weight: 500;
      color: #8b92a7;
      margin-bottom: 8px;
      text-transform: uppercase;
      letter-spacing: 0.5px;
    }

    .stat-card .number {
      font-size: 32px;
      font-weight: 700;
      color: #ffffff;
    }

    /* ========== SEARCH BAR ========== */
    .search-section {
      background: #1a1d29;
      padding: 24px;
      border-radius: 12px;
      box-shadow: 0 2px 8px rgba(0, 0, 0, 0.3);
      margin-bottom: 24px;
      border: 1px solid #2d3142;
    }

    .search-bar {
      display: flex;
      gap: 12px;
      flex-wrap: wrap;
    }

    .search-bar input {
      flex: 1;
      min-width: 250px;
      padding: 12px 16px;
      border: 2px solid #2d3142;
      border-radius: 10px;
      font-size: 14px;
      transition: all 0.3s;
      background: #252938;
      color: #ffffff;
    }

    .search-bar input::placeholder {
      color: #8b92a7;
    }

    .search-bar input:focus {
      outline: none;
      border-color: var(--primary-color);
      box-shadow: 0 0 0 3px rgba(141, 11, 65, 0.2);
      background: #2d3142;
    }

    .search-bar select {
      padding: 12px 16px;
      border: 2px solid #2d3142;
      border-radius: 10px;
      font-size: 14px;
      cursor: pointer;
      transition: all 0.3s;
      background: #252938;
      color: #ffffff;
    }

    .search-bar select:focus {
      outline: none;
      border-color: var(--primary-color);
      background: #2d3142;
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
      transition: all 0.3s;
    }

    .btn-clear:hover {
      background: #5a6268;
      color: white;
    }

    /* ========== TABS ========== */
    .tabs-container {
      background: #1a1d29;
      border-radius: 12px;
      box-shadow: 0 2px 8px rgba(0, 0, 0, 0.3);
      overflow: hidden;
      margin-bottom: 24px;
      border: 1px solid #2d3142;
    }

    .tabs {
      display: flex;
      border-bottom: 2px solid #2d3142;
      padding: 0 24px;
    }

    .tab {
      padding: 16px 24px;
      background: none;
      border: none;
      cursor: pointer;
      font-weight: 600;
      font-size: 14px;
      color: #8b92a7;
      border-bottom: 3px solid transparent;
      margin-bottom: -2px;
      transition: all 0.3s;
      position: relative;
    }

    .tab:hover {
      color: var(--primary-color);
      background: rgba(141, 11, 65, 0.05);
    }

    .tab.active {
      color: var(--primary-color);
      border-bottom-color: var(--primary-color);
      background: rgba(141, 11, 65, 0.1);
    }

    .tab-content {
      display: none;
      padding: 24px;
    }

    .tab-content.active {
      display: block;
    }

    /* ========== ACCOUNT CARDS ========== */
    .card-grid {
      display: grid;
      grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));
      gap: 20px;
    }

    .account-card {
      background: #1a1d29;
      border-radius: 12px;
      padding: 24px;
      box-shadow: 0 2px 8px rgba(0, 0, 0, 0.3);
      transition: all 0.3s;
      position: relative;
      border: 1px solid #2d3142;
    }

    .account-card:hover {
      transform: translateY(-4px);
      box-shadow: 0 8px 24px rgba(0, 0, 0, 0.4);
      border-color: var(--primary-color);
    }

    .account-card .profile-section {
      display: flex;
      align-items: center;
      gap: 16px;
      margin-bottom: 20px;
      padding-bottom: 20px;
      border-bottom: 1px solid #2d3142;
    }

    .profile-pic {
      width: 64px;
      height: 64px;
      border-radius: 50%;
      object-fit: cover;
      border: 3px solid var(--primary-color);
    }

    .profile-info h3 {
      font-size: 16px;
      font-weight: 600;
      color: #ffffff;
      margin-bottom: 4px;
    }

    .profile-info .username {
      font-size: 13px;
      color: #8b92a7;
      display: flex;
      align-items: center;
      gap: 4px;
    }

    .account-details {
      margin-bottom: 16px;
    }

    .detail-item {
      display: flex;
      align-items: center;
      gap: 8px;
      margin-bottom: 8px;
      font-size: 13px;
      color: #e4e6eb;
    }

    .detail-item i {
      color: var(--primary-color);
      font-size: 16px;
      width: 20px;
    }

    .badge-status {
      display: inline-flex;
      align-items: center;
      padding: 6px 12px;
      border-radius: 20px;
      font-size: 12px;
      font-weight: 600;
      margin-bottom: 16px;
    }

    .badge-status.verified {
      background: #d4edda;
      color: #155724;
    }

    .badge-status.pending {
      background: #fff3cd;
      color: #856404;
    }

    .badge-status.rejected {
      background: #f8d7da;
      color: #721c24;
    }

    .badge-status.active {
      background: #d1ecf1;
      color: #0c5460;
    }

    .card-actions {
      display: flex;
      gap: 8px;
    }

    .btn-action {
      flex: 1;
      padding: 10px;
      border: none;
      border-radius: 8px;
      font-size: 13px;
      font-weight: 600;
      cursor: pointer;
      transition: all 0.3s;
      display: flex;
      align-items: center;
      justify-content: center;
      gap: 6px;
    }

    .btn-view {
      background: var(--primary-color);
      color: white;
    }

    .btn-view:hover {
      background: var(--primary-hover);
      transform: translateY(-2px);
      box-shadow: 0 4px 12px rgba(141, 11, 65, 0.3);
    }

    .btn-remove {
      background: #dc3545;
      color: white;
    }

    .btn-remove:hover {
      background: #c82333;
      transform: translateY(-2px);
      box-shadow: 0 4px 12px rgba(220, 53, 69, 0.3);
    }

    /* ========== MODAL ========== */
    .modal {
      display: none;
      position: fixed;
      z-index: 2000;
      left: 0;
      top: 0;
      width: 100%;
      height: 100%;
      background-color: rgba(0, 0, 0, 0.6);
      animation: fadeIn 0.3s;
    }

    @keyframes fadeIn {
      from {
        opacity: 0;
      }

      to {
        opacity: 1;
      }
    }

    .modal-content {
      background-color: white;
      margin: 3% auto;
      padding: 0;
      border-radius: 16px;
      width: 90%;
      max-width: 600px;
      box-shadow: 0 10px 40px rgba(0, 0, 0, 0.3);
      animation: slideDown 0.3s;
    }

    @keyframes slideDown {
      from {
        transform: translateY(-50px);
        opacity: 0;
      }

      to {
        transform: translateY(0);
        opacity: 1;
      }
    }

    .modal-header {
      padding: 24px 30px;
      background: linear-gradient(135deg, var(--primary-color) 0%, var(--primary-hover) 100%);
      color: white;
      border-radius: 16px 16px 0 0;
      display: flex;
      justify-content: space-between;
      align-items: center;
    }

    .modal-header h2 {
      margin: 0;
      font-size: 20px;
      font-weight: 600;
    }

    .close {
      color: white;
      font-size: 28px;
      font-weight: bold;
      cursor: pointer;
      line-height: 20px;
      transition: opacity 0.2s;
    }

    .close:hover {
      opacity: 0.7;
    }

    .modal-body {
      padding: 30px;
    }

    .user-profile-modal {
      text-align: center;
      margin-bottom: 24px;
    }

    .user-profile-pic-modal {
      width: 100px;
      height: 100px;
      border-radius: 50%;
      object-fit: cover;
      border: 4px solid var(--primary-color);
      margin-bottom: 12px;
    }

    .user-detail-row {
      display: flex;
      padding: 14px 0;
      border-bottom: 1px solid #f1f3f5;
    }

    .user-detail-row:last-child {
      border-bottom: none;
    }

    .detail-label {
      font-weight: 600;
      width: 140px;
      color: #495057;
      font-size: 14px;
    }

    .detail-value {
      flex: 1;
      color: #1a1d29;
      font-size: 14px;
    }

    .verification-badge {
      display: inline-block;
      padding: 6px 14px;
      border-radius: 16px;
      font-size: 12px;
      font-weight: 600;
    }

    .badge-verified {
      background: #d4edda;
      color: #155724;
    }

    .badge-pending {
      background: #fff3cd;
      color: #856404;
    }

    .badge-rejected {
      background: #f8d7da;
      color: #721c24;
    }

    /* Empty State */
    .empty-state {
      text-align: center;
      padding: 60px 20px;
      color: #8b92a7;
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

      .search-bar {
        flex-direction: column;
      }

      .search-bar input,
      .search-bar select {
        width: 100%;
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
            <a href="accounts.php" class="active" data-tooltip="Accounts">
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
              <?php if ($pending_landlords > 0): ?>
                <span class="badge"><?= $pending_landlords ?></span>
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
      <h1>Account Management</h1>
      <p>Manage landlords and tenants across the platform</p>
    </div>

    <!-- Statistics Cards -->
    <div class="stats-grid">
      <div class="stat-card">
        <div class="stat-icon">
          <i class='bx bx-user'></i>
        </div>
        <h4>Total Landlords</h4>
        <div class="number"><?= $total_landlords ?></div>
      </div>
      <div class="stat-card purple">
        <div class="stat-icon">
          <i class='bx bx-shield-check'></i>
        </div>
        <h4>Verified</h4>
        <div class="number"><?= $verified_landlords ?></div>
      </div>
      <div class="stat-card pink">
        <div class="stat-icon">
          <i class='bx bx-time'></i>
        </div>
        <h4>Pending</h4>
        <div class="number"><?= $pending_landlords ?></div>
      </div>
      <div class="stat-card blue">
        <div class="stat-icon">
          <i class='bx bx-group'></i>
        </div>
        <h4>Total Tenants</h4>
        <div class="number"><?= $total_tenants ?></div>
      </div>
    </div>

    <!-- Search Section -->
    <div class="search-section">
      <form method="GET" class="search-bar">
        <input type="text" name="search" placeholder="🔍 Search by name, email, or username..." value="<?= htmlspecialchars($search) ?>">
        <select name="verification">
          <option value="all" <?= $verification_filter == 'all' ? 'selected' : '' ?>>All Status</option>
          <option value="verified" <?= $verification_filter == 'verified' ? 'selected' : '' ?>>Verified</option>
          <option value="pending" <?= $verification_filter == 'pending' ? 'selected' : '' ?>>Pending</option>
          <option value="rejected" <?= $verification_filter == 'rejected' ? 'selected' : '' ?>>Rejected</option>
        </select>
        <button type="submit" class="btn-search">
          <i class='bx bx-search'></i> Search
        </button>
        <?php if (!empty($search) || $verification_filter != 'all'): ?>
          <a href="accounts.php" class="btn-clear">
            <i class='bx bx-x'></i> Clear
          </a>
        <?php endif; ?>
      </form>
    </div>

    <!-- Tabs -->
    <div class="tabs-container">
      <div class="tabs">
        <button class="tab active" data-target="#landlords">
          <i class='bx bx-building'></i> Landlords (<?= $landlords_result->num_rows ?>)
        </button>
        <button class="tab" data-target="#tenants">
          <i class='bx bx-group'></i> Tenants (<?= $tenants_result->num_rows ?>)
        </button>
      </div>

      <!-- Landlords Tab -->
      <div id="landlords" class="tab-content active">
        <?php if ($landlords_result->num_rows > 0): ?>
          <div class="card-grid">
            <?php while ($landlord = $landlords_result->fetch_assoc()): ?>
              <div class="account-card">
                <div class="profile-section">
                  <img src="<?= !empty($landlord['profilePic']) ? htmlspecialchars($landlord['profilePic']) : 'https://via.placeholder.com/64' ?>"
                    alt="Landlord" class="profile-pic">
                  <div class="profile-info">
                    <h3><?= htmlspecialchars($landlord['firstName'] . ' ' . $landlord['lastName']) ?></h3>
                    <div class="username">
                      <i class='bx bx-at'></i>
                      <?= htmlspecialchars($landlord['username']) ?>
                    </div>
                  </div>
                </div>

                <span class="badge-status <?= $landlord['verification_status'] == 'verified' ? 'verified' : ($landlord['verification_status'] == 'rejected' ? 'rejected' : 'pending') ?>">
                  <i class='bx <?= $landlord['verification_status'] == 'verified' ? 'bx-badge-check' : ($landlord['verification_status'] == 'rejected' ? 'bx-x-circle' : 'bx-time') ?>'></i>
                  <?= ucfirst($landlord['verification_status'] ?? 'pending') ?>
                </span>

                <div class="account-details">
                  <div class="detail-item">
                    <i class='bx bx-envelope'></i>
                    <span><?= htmlspecialchars($landlord['email']) ?></span>
                  </div>
                  <?php if (!empty($landlord['phoneNum'])): ?>
                    <div class="detail-item">
                      <i class='bx bx-phone'></i>
                      <span><?= htmlspecialchars($landlord['phoneNum']) ?></span>
                    </div>
                  <?php endif; ?>
                </div>

                <div class="card-actions">
                  <button class="btn-action btn-view" onclick='viewUser(<?= json_encode($landlord) ?>, "landlord")'>
                    <i class='bx bx-show'></i> View
                  </button>
                  <button class="btn-action btn-remove" onclick="removeUser('landlord', <?= $landlord['ID'] ?>, '<?= htmlspecialchars($landlord['firstName'] . ' ' . $landlord['lastName']) ?>')">
                    <i class='bx bx-trash'></i> Remove
                  </button>
                </div>
              </div>
            <?php endwhile; ?>
          </div>
        <?php else: ?>
          <div class="empty-state">
            <i class='bx bx-user-x'></i>
            <p>No landlords found</p>
          </div>
        <?php endif; ?>
      </div>

      <!-- Tenants Tab -->
      <div id="tenants" class="tab-content">
        <?php if ($tenants_result->num_rows > 0): ?>
          <div class="card-grid">
            <?php while ($tenant = $tenants_result->fetch_assoc()): ?>
              <div class="account-card">
                <div class="profile-section">
                  <img src="<?= !empty($tenant['profilePic']) ? htmlspecialchars($tenant['profilePic']) : 'https://via.placeholder.com/64' ?>"
                    alt="Tenant" class="profile-pic">
                  <div class="profile-info">
                    <h3><?= htmlspecialchars($tenant['firstName'] . ' ' . $tenant['lastName']) ?></h3>
                    <div class="username">
                      <i class='bx bx-at'></i>
                      <?= htmlspecialchars($tenant['username']) ?>
                    </div>
                  </div>
                </div>

                <span class="badge-status active">
                  <i class='bx bx-check-circle'></i>
                  Active
                </span>

                <div class="account-details">
                  <div class="detail-item">
                    <i class='bx bx-envelope'></i>
                    <span><?= htmlspecialchars($tenant['email']) ?></span>
                  </div>
                  <?php if (!empty($tenant['phoneNum'])): ?>
                    <div class="detail-item">
                      <i class='bx bx-phone'></i>
                      <span><?= htmlspecialchars($tenant['phoneNum']) ?></span>
                    </div>
                  <?php endif; ?>
                </div>

                <div class="card-actions">
                  <button class="btn-action btn-view" onclick='viewUser(<?= json_encode($tenant) ?>, "tenant")'>
                    <i class='bx bx-show'></i> View
                  </button>
                  <button class="btn-action btn-remove" onclick="removeUser('tenant', <?= $tenant['ID'] ?>, '<?= htmlspecialchars($tenant['firstName'] . ' ' . $tenant['lastName']) ?>')">
                    <i class='bx bx-trash'></i> Remove
                  </button>
                </div>
              </div>
            <?php endwhile; ?>
          </div>
        <?php else: ?>
          <div class="empty-state">
            <i class='bx bx-user-x'></i>
            <p>No tenants found</p>
          </div>
        <?php endif; ?>
      </div>
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

  <!-- User Detail Modal -->
  <div id="userModal" class="modal">
    <div class="modal-content">
      <div class="modal-header">
        <h2 id="modalTitle">User Details</h2>
        <span class="close" onclick="closeModal()">&times;</span>
      </div>
      <div class="modal-body" id="modalBody"></div>
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

    // Tabs
    document.querySelectorAll('.tab').forEach(tab => {
      tab.addEventListener('click', () => {
        const target = tab.dataset.target;

        document.querySelectorAll('.tab').forEach(t => t.classList.remove('active'));
        document.querySelectorAll('.tab-content').forEach(c => c.classList.remove('active'));

        tab.classList.add('active');
        document.querySelector(target).classList.add('active');
      });
    });

    // View user modal
    function viewUser(user, type) {
      const modal = document.getElementById('userModal');
      const modalBody = document.getElementById('modalBody');
      const modalTitle = document.getElementById('modalTitle');

      modalTitle.textContent = type.charAt(0).toUpperCase() + type.slice(1) + ' Details';

      let verificationBadge = '';
      if (type === 'landlord' && user.verification_status) {
        const badgeClass = user.verification_status === 'verified' ? 'badge-verified' :
          user.verification_status === 'pending' ? 'badge-pending' : 'badge-rejected';
        verificationBadge = `<span class="verification-badge ${badgeClass}">${user.verification_status.toUpperCase()}</span>`;
      }

      modalBody.innerHTML = `
        <div class="user-profile-modal">
          <img src="${user.profilePic || 'https://via.placeholder.com/100'}" class="user-profile-pic-modal" alt="Profile">
        </div>
        
        <div class="user-detail-row">
          <div class="detail-label">User ID:</div>
          <div class="detail-value">#${user.ID}</div>
        </div>
        <div class="user-detail-row">
          <div class="detail-label">Full Name:</div>
          <div class="detail-value">${user.firstName} ${user.middleName || ''} ${user.lastName}</div>
        </div>
        <div class="user-detail-row">
          <div class="detail-label">Email:</div>
          <div class="detail-value">${user.email}</div>
        </div>
        <div class="user-detail-row">
          <div class="detail-label">Username:</div>
          <div class="detail-value">${user.username}</div>
        </div>
        ${user.phoneNum ? `
        <div class="user-detail-row">
          <div class="detail-label">Phone:</div>
          <div class="detail-value">${user.phoneNum}</div>
        </div>
        ` : ''}
        ${type === 'landlord' && user.verification_status ? `
        <div class="user-detail-row">
          <div class="detail-label">Verification:</div>
          <div class="detail-value">${verificationBadge}</div>
        </div>
        ` : ''}
        <div class="user-detail-row">
          <div class="detail-label">Registered:</div>
          <div class="detail-value">${user.created_at ? new Date(user.created_at).toLocaleDateString('en-US', {year: 'numeric', month: 'long', day: 'numeric'}) : 'N/A'}</div>
        </div>
      `;

      modal.style.display = 'block';
    }

    function closeModal() {
      document.getElementById('userModal').style.display = 'none';
    }

    window.onclick = function(event) {
      const modal = document.getElementById('userModal');
      if (event.target == modal) {
        modal.style.display = 'none';
      }
    }

    function removeUser(type, id, name) {
      if (confirm(`Are you sure you want to remove ${name}?\n\nThis action cannot be undone.`)) {
        window.location.href = `delete_user.php?type=${type}&id=${id}`;
      }
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