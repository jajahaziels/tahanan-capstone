<?php
require_once '../session_auth.php';
require_once '../connection.php';

$admin_name = $_SESSION['username'];
$admin_id = $_SESSION['admin_id'];

$pending_verification = $conn->query("SELECT COUNT(*) as count FROM landlordtbl WHERE verification_status='pending'")->fetch_assoc()['count'];

$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$status_filter = isset($_GET['status']) ? $_GET['status'] : 'pending';

$pending_query = "
    SELECT 
        l.*,
        CONCAT(ll.firstName, ' ', ll.middleName, ' ', ll.lastName) as landlord_name,
        ll.email as landlord_email,
        ll.phoneNum as landlord_phone
    FROM listingtbl l
    LEFT JOIN landlordtbl ll ON l.landlord_id = ll.ID
    WHERE l.verification_status = ?
";

if (!empty($search)) {
    $pending_query .= " AND (l.listingName LIKE '%$search%' OR l.barangay LIKE '%$search%' OR ll.firstName LIKE '%$search%' OR ll.lastName LIKE '%$search%')";
}

$pending_query .= " ORDER BY l.listingDate DESC";

$stmt = $conn->prepare($pending_query);
$stmt->bind_param("s", $status_filter);
$stmt->execute();
$pending_result = $stmt->get_result();

$total_pending = $conn->query("SELECT COUNT(*) as count FROM listingtbl WHERE verification_status='pending'")->fetch_assoc()['count'];
$total_approved = $conn->query("SELECT COUNT(*) as count FROM listingtbl WHERE verification_status='approved'")->fetch_assoc()['count'];
$total_rejected = $conn->query("SELECT COUNT(*) as count FROM listingtbl WHERE verification_status='rejected'")->fetch_assoc()['count'];
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Property Verification - Admin Dashboard</title>
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
      box-shadow: 0 2px 8px rgba(0,0,0,0.3);
      display: flex;
      align-items: center;
      gap: 16px;
      transition: all 0.3s;
      cursor: pointer;
    }

    .stat-box:hover {
      transform: translateY(-4px);
      box-shadow: 0 4px 16px rgba(0,0,0,0.4);
      border-color: var(--stat-color);
    }

    .stat-box.pending { --stat-color: #fbbf24; }
    .stat-box.approved { --stat-color: #10b981; }
    .stat-box.rejected { --stat-color: #ef4444; }
    .stat-box.active { border-color: var(--stat-color); border-width: 2px; }

    .stat-icon {
      width: 50px;
      height: 50px;
      border-radius: 12px;
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: 24px;
      background: rgba(255,255,255,0.05);
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

    .filters-section {
      background: var(--card-bg);
      padding: 24px;
      border-radius: 12px;
      box-shadow: 0 2px 8px rgba(0,0,0,0.3);
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

    .properties-table {
      background: var(--card-bg);
      border-radius: 12px;
      padding: 24px;
      box-shadow: 0 2px 8px rgba(0,0,0,0.3);
      border: 1px solid var(--border-color);
      overflow-x: auto;
    }

    table {
      width: 100%;
      border-collapse: collapse;
    }

    thead tr {
      background: var(--sidebar-hover);
    }

    th {
      padding: 16px;
      text-align: left;
      font-size: 13px;
      font-weight: 600;
      color: var(--text-muted);
      text-transform: uppercase;
      letter-spacing: 0.5px;
      border-bottom: 2px solid var(--border-color);
    }

    td {
      padding: 16px;
      color: var(--text-color);
      border-bottom: 1px solid var(--border-color);
      font-size: 14px;
    }

    tr:hover {
      background: var(--sidebar-hover);
    }

    .property-thumb {
      width: 80px;
      height: 60px;
      border-radius: 8px;
      object-fit: cover;
      border: 2px solid var(--border-color);
    }

    .status-badge {
      display: inline-flex;
      align-items: center;
      padding: 6px 12px;
      border-radius: 20px;
      font-size: 12px;
      font-weight: 600;
      gap: 6px;
    }

    .status-badge.pending {
      background: rgba(251, 191, 36, 0.15);
      color: #fbbf24;
    }

    .status-badge.approved {
      background: rgba(16, 185, 129, 0.15);
      color: #10b981;
    }

    .status-badge.rejected {
      background: rgba(239, 68, 68, 0.15);
      color: #ef4444;
    }

    .btn-action {
      padding: 8px 16px;
      border: none;
      border-radius: 8px;
      cursor: pointer;
      font-weight: 600;
      font-size: 12px;
      transition: all 0.3s;
      display: inline-flex;
      align-items: center;
      gap: 6px;
      background: var(--primary-color);
      color: white;
      text-decoration: none;
    }

    .btn-action:hover {
      background: var(--primary-hover);
      transform: translateY(-2px);
      box-shadow: 0 4px 12px rgba(141, 11, 65, 0.3);
    }

    .empty-state {
      text-align: center;
      padding: 60px 20px;
      color: var(--text-muted);
    }

    .empty-state i {
      font-size: 64px;
      opacity: 0.3;
      margin-bottom: 16px;
    }

    @media (max-width: 768px) {
      .sidebar {
        transform: translateX(-100%);
      }

      .content {
        margin-left: 0;
      }

      .stats-bar {
        grid-template-columns: 1fr;
      }
    }
  </style>
   <link rel="stylesheet" href="admin-theme.css">
</head>

<body>

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
            <a href="verify-properties.php" class="active" data-tooltip="Verify Properties">
              <i class='bx bx-shield-alt-2 icon'></i>
              <span class="text">Verify Properties</span>
              <?php if($total_pending > 0): ?>
                <span class="badge"><?= $total_pending ?></span>
              <?php endif; ?>
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

  <main class="content">
    <div class="page-header">
      <h1>Property Verification</h1>
      <p>Review and verify property listings before they go live</p>
    </div>

    <div class="stats-bar">
      <div class="stat-box pending <?= $status_filter === 'pending' ? 'active' : '' ?>" onclick="filterByStatus('pending')">
        <div class="stat-icon">
          <i class='bx bx-time'></i>
        </div>
        <div class="stat-info">
          <h3><?= $total_pending ?></h3>
          <p>Pending Review</p>
        </div>
      </div>

      <div class="stat-box approved <?= $status_filter === 'approved' ? 'active' : '' ?>" onclick="filterByStatus('approved')">
        <div class="stat-icon">
          <i class='bx bx-check-circle'></i>
        </div>
        <div class="stat-info">
          <h3><?= $total_approved ?></h3>
          <p>Approved</p>
        </div>
      </div>

      <div class="stat-box rejected <?= $status_filter === 'rejected' ? 'active' : '' ?>" onclick="filterByStatus('rejected')">
        <div class="stat-icon">
          <i class='bx bx-x-circle'></i>
        </div>
        <div class="stat-info">
          <h3><?= $total_rejected ?></h3>
          <p>Rejected</p>
        </div>
      </div>
    </div>

    <div class="filters-section">
      <form method="GET" class="search-bar">
        <input type="hidden" name="status" value="<?= htmlspecialchars($status_filter) ?>">
        <input type="text" 
               name="search" 
               placeholder="🔍 Search by property name, location, or landlord..." 
               value="<?= htmlspecialchars($search) ?>">
        <button type="submit" class="btn-search">
          <i class='bx bx-search'></i> Search
        </button>
      </form>
    </div>

    <div class="properties-table">
      <?php if ($pending_result->num_rows > 0): ?>
        <table>
          <thead>
            <tr>
              <th>Property</th>
              <th>Landlord</th>
              <th>Location</th>
              <th>Price</th>
              <th>Submitted</th>
              <th>Status</th>
              <th>Action</th>
            </tr>
          </thead>
          <tbody>
            <?php while($property = $pending_result->fetch_assoc()): ?>
              <?php
              $images = !empty($property['images']) ? $property['images'] : '';
              $first_image = '../img/house1.jpeg';
              
              if (!empty($images)) {
                  $image_array = json_decode($images, true);
                  if (!is_array($image_array)) {
                      $image_array = array_map('trim', explode(',', $images));
                  }
                  if (!empty($image_array) && isset($image_array[0])) {
                      $raw_image = trim($image_array[0]);
                      if (!empty($raw_image)) {
                          $first_image = '../LANDLORD/uploads/' . $raw_image;
                      }
                  }
              }
              ?>
              <tr>
                <td>
                  <div style="display: flex; align-items: center; gap: 12px;">
                    <img src="<?= htmlspecialchars($first_image) ?>" 
                         class="property-thumb"
                         onerror="this.src='../img/house1.jpeg';">
                    <div>
                      <strong><?= htmlspecialchars($property['listingName']) ?></strong><br>
                      <small style="color: var(--text-muted);"><?= htmlspecialchars($property['category']) ?></small>
                    </div>
                  </div>
                </td>
                <td>
                  <strong><?= htmlspecialchars($property['landlord_name']) ?></strong><br>
                  <small style="color: var(--text-muted);"><?= htmlspecialchars($property['landlord_email']) ?></small>
                </td>
                <td><?= htmlspecialchars($property['barangay']) ?>, San Pedro</td>
                <td><strong style="color: var(--primary-color);">₱<?= number_format($property['price']) ?></strong>/mo</td>
                <td><?= date('M d, Y', strtotime($property['listingDate'])) ?></td>
                <td>
                  <span class="status-badge <?= $property['verification_status'] ?>">
                    <i class='bx <?= $property['verification_status'] === 'pending' ? 'bx-time' : ($property['verification_status'] === 'approved' ? 'bx-check' : 'bx-x') ?>'></i>
                    <?= ucfirst($property['verification_status']) ?>
                  </span>
                </td>
                <td>
                  <a href="review-property.php?id=<?= $property['ID'] ?>" class="btn-action">
                    <i class='bx bx-search'></i> Review
                  </a>
                </td>
              </tr>
            <?php endwhile; ?>
          </tbody>
        </table>
      <?php else: ?>
        <div class="empty-state">
          <i class='bx bx-building-house'></i>
          <p>No <?= $status_filter ?> properties found</p>
        </div>
      <?php endif; ?>
    </div>
  </main>

  <script>
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

    function filterByStatus(status) {
      const urlParams = new URLSearchParams(window.location.search);
      urlParams.set('status', status);
      urlParams.delete('search');
      window.location.search = urlParams.toString();
    }
  </script>

</body>
</html>