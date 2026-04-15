<?php
require_once '../session_auth.php';
require_once '../connection.php';

// Get admin info
$admin_name = $_SESSION['username'];

// Get pending verification count for badge
$pending_verification = $conn->query("SELECT COUNT(*) as count FROM landlordtbl WHERE verification_status='pending'")->fetch_assoc()['count'];

// Get listing details
if (!isset($_GET['id'])) {
    header("Location: listing.php");
    exit;
}

$listing_id = intval($_GET['id']);

// Fetch listing details with landlord info
$stmt = $conn->prepare("
    SELECT l.*, ll.firstName, ll.lastName, ll.email, ll.phoneNum 
    FROM listingtbl l 
    LEFT JOIN landlordtbl ll ON l.landlord_id = ll.ID 
    WHERE l.ID = ?
");
$stmt->bind_param("i", $listing_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    header("Location: listing.php?error=not_found");
    exit;
}

$listing = $result->fetch_assoc();

// Parse images - handle both JSON and comma-separated formats
$images_raw = !empty($listing['images']) ? $listing['images'] : '';
$images = [];

if (!empty($images_raw)) {
    $decoded = json_decode($images_raw, true);
    
    if (is_array($decoded)) {
        $images_raw = $decoded;
    } else {
        $images_raw = explode(',', $images_raw);
    }
    
    foreach ($images_raw as $img) {
        $img = trim($img);
        if (!empty($img)) {
            if (strpos($img, '../LANDLORD/') === 0) {
                $images[] = $img;
            } elseif (strpos($img, 'uploads/') === 0) {
                $images[] = '../LANDLORD/' . $img;
            } else {
                $images[] = '../LANDLORD/uploads/' . $img;
            }
        }
    }
}

if (empty($images)) {
    $images = ['../img/house1.jpeg'];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= htmlspecialchars($listing['listingName']) ?> - Admin</title>
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

    /* Back Button */
    .back-link {
      color: var(--primary-color);
      text-decoration: none;
      margin-bottom: 20px;
      display: inline-flex;
      align-items: center;
      gap: 8px;
      font-weight: 600;
      transition: all 0.3s;
      padding: 8px 16px;
      border-radius: 8px;
      background: rgba(141, 11, 65, 0.1);
    }

    .back-link:hover {
      background: rgba(141, 11, 65, 0.2);
      transform: translateX(-4px);
    }

    /* Listing Detail Container */
    .listing-detail {
      background: var(--card-bg);
      border-radius: 12px;
      padding: 30px;
      box-shadow: 0 2px 8px rgba(0,0,0,0.3);
      max-width: 1200px;
      border: 1px solid var(--border-color);
    }

    /* Header Section */
    .detail-header {
      display: flex;
      justify-content: space-between;
      align-items: start;
      margin-bottom: 24px;
      padding-bottom: 20px;
      border-bottom: 2px solid var(--border-color);
    }

    .detail-header h1 {
      margin: 0 0 10px 0;
      color: #ffffff;
      font-size: 28px;
    }

    .price-section h2 {
      margin: 0;
      color: var(--primary-color);
      font-size: 32px;
    }

    .price-section small {
      color: var(--text-muted);
      font-size: 14px;
    }

    /* Status Badge */
    .status-badge {
      display: inline-flex;
      align-items: center;
      padding: 8px 16px;
      border-radius: 20px;
      font-weight: 600;
      font-size: 13px;
      gap: 6px;
    }

    .status-available {
      background: rgba(67, 233, 123, 0.15);
      color: #43e97b;
    }

    .status-occupied {
      background: rgba(254, 202, 87, 0.15);
      color: #feca57;
    }

    /* Images */
    .main-image {
      width: 100%;
      max-height: 500px;
      object-fit: cover;
      border-radius: 12px;
      margin-bottom: 20px;
      border: 1px solid var(--border-color);
    }

    .image-gallery {
      display: grid;
      grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
      gap: 12px;
      margin-bottom: 30px;
    }

    .image-gallery img {
      width: 100%;
      height: 180px;
      object-fit: cover;
      border-radius: 8px;
      cursor: pointer;
      transition: all 0.3s;
      border: 1px solid var(--border-color);
    }

    .image-gallery img:hover {
      transform: scale(1.05);
      border-color: var(--primary-color);
    }

    /* Detail Sections */
    .detail-section {
      margin-bottom: 30px;
    }

    .detail-section h2 {
      color: var(--primary-color);
      margin-bottom: 16px;
      padding-bottom: 12px;
      border-bottom: 2px solid var(--border-color);
      font-size: 20px;
      display: flex;
      align-items: center;
      gap: 10px;
    }

    .detail-grid {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
      gap: 16px;
    }

    .detail-item {
      padding: 16px;
      background: var(--sidebar-hover);
      border-radius: 8px;
      border: 1px solid var(--border-color);
      transition: all 0.3s;
    }

    .detail-item:hover {
      border-color: var(--primary-color);
      transform: translateY(-2px);
    }

    .detail-label {
      font-weight: 600;
      color: var(--text-muted);
      margin-bottom: 6px;
      font-size: 13px;
      display: flex;
      align-items: center;
      gap: 6px;
    }

    .detail-value {
      color: var(--text-color);
      font-size: 15px;
    }

    /* Description */
    .description-text {
      line-height: 1.8;
      color: var(--text-muted);
      padding: 16px;
      background: var(--sidebar-hover);
      border-radius: 8px;
      border-left: 4px solid var(--primary-color);
    }

    /* Map Container */
    .map-container {
      width: 100%;
      height: 300px;
      background: var(--sidebar-hover);
      border-radius: 8px;
      display: flex;
      align-items: center;
      justify-content: center;
      color: var(--text-muted);
      border: 1px solid var(--border-color);
    }

    /* Action Buttons */
    .action-buttons {
      margin-top: 30px;
      display: flex;
      gap: 12px;
      flex-wrap: wrap;
      padding-top: 24px;
      border-top: 2px solid var(--border-color);
    }

    .btn {
      padding: 12px 24px;
      border: none;
      border-radius: 10px;
      cursor: pointer;
      font-size: 14px;
      font-weight: 600;
      transition: all 0.3s;
      display: inline-flex;
      align-items: center;
      gap: 8px;
    }

    .btn:hover {
      transform: translateY(-2px);
      box-shadow: 0 4px 12px rgba(0,0,0,0.3);
    }

    .btn-danger {
      background: #fa709a;
      color: white;
    }

    .btn-warning {
      background: #feca57;
      color: #0f1419;
    }

    .btn-secondary {
      background: #6c757d;
      color: white;
    }

    .btn-success {
      background: #43e97b;
      color: #0f1419;
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

      .detail-header {
        flex-direction: column;
        gap: 16px;
      }

      .detail-grid {
        grid-template-columns: 1fr;
      }

      .action-buttons {
        flex-direction: column;
      }

      .btn {
        width: 100%;
        justify-content: center;
      }
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
    <a href="listing.php" class="back-link">
      <i class='bx bx-arrow-back'></i> Back to Listings
    </a>

    <div class="listing-detail">
      <div class="detail-header">
        <div>
          <h1><?= htmlspecialchars($listing['listingName']) ?></h1>
          <span class="status-badge status-<?= $listing['availability'] ?>">
            <i class='bx bx-<?= $listing['availability'] == 'available' ? 'check-circle' : 'lock' ?>'></i>
            <?= ucfirst($listing['availability']) ?>
          </span>
        </div>
        <div class="price-section" style="text-align: right;">
          <h2>₱<?= number_format($listing['price']) ?></h2>
          <small>per month</small>
        </div>
      </div>

      <!-- Main Image -->
      <img src="<?= htmlspecialchars(trim($images[0])) ?>" alt="Main property image" class="main-image" onerror="this.src='../img/house1.jpeg'">

      <!-- Image Gallery -->
      <?php if (count($images) > 1): ?>
        <div class="image-gallery">
          <?php foreach(array_slice($images, 1, 4) as $img): ?>
            <img src="<?= htmlspecialchars(trim($img)) ?>" alt="Property image" onerror="this.src='../img/house1.jpeg'">
          <?php endforeach; ?>
        </div>
      <?php endif; ?>

      <!-- Property Details -->
      <div class="detail-section">
        <h2><i class='bx bx-info-circle'></i> Property Details</h2>
        <div class="detail-grid">
          <div class="detail-item">
            <div class="detail-label"><i class='bx bx-bed'></i> Bedrooms</div>
            <div class="detail-value"><?= htmlspecialchars($listing['rooms']) ?></div>
          </div>
          <div class="detail-item">
            <div class="detail-label"><i class='bx bx-category'></i> Category</div>
            <div class="detail-value"><?= htmlspecialchars($listing['category']) ?></div>
          </div>
          <div class="detail-item">
            <div class="detail-label"><i class='bx bx-map'></i> Barangay</div>
            <div class="detail-value"><?= htmlspecialchars($listing['barangay']) ?></div>
          </div>
          <div class="detail-item">
            <div class="detail-label"><i class='bx bx-calendar'></i> Listed Date</div>
            <div class="detail-value"><?= date('F d, Y', strtotime($listing['listingDate'])) ?></div>
          </div>
        </div>
      </div>

      <!-- Description -->
      <div class="detail-section">
        <h2><i class='bx bx-file-blank'></i> Description</h2>
        <div class="description-text"><?= nl2br(htmlspecialchars($listing['listingDesc'])) ?></div>
      </div>

      <!-- Location -->
      <div class="detail-section">
        <h2><i class='bx bx-map-pin'></i> Location</h2>
        <p style="margin-bottom: 15px; color: var(--text-muted); padding: 12px; background: var(--sidebar-hover); border-radius: 8px;">
          <strong style="color: var(--text-color);">Address:</strong> <?= htmlspecialchars($listing['address']) ?>
        </p>
        <?php if (!empty($listing['latitude']) && !empty($listing['longitude'])): ?>
          <div class="map-container">
            <div style="text-align: center;">
              <i class='bx bx-map' style='font-size: 48px;'></i><br>
              <strong>Map:</strong> <?= htmlspecialchars($listing['latitude']) ?>, <?= htmlspecialchars($listing['longitude']) ?><br>
              <small>Integrate Google Maps API here</small>
            </div>
          </div>
        <?php endif; ?>
      </div>

      <!-- Landlord Info -->
      <div class="detail-section">
        <h2><i class='bx bx-user'></i> Landlord Information</h2>
        <div class="detail-grid">
          <div class="detail-item">
            <div class="detail-label">Name</div>
            <div class="detail-value"><?= htmlspecialchars($listing['firstName'] . ' ' . $listing['lastName']) ?></div>
          </div>
          <div class="detail-item">
            <div class="detail-label">Email</div>
            <div class="detail-value"><?= htmlspecialchars($listing['email']) ?></div>
          </div>
          <div class="detail-item">
            <div class="detail-label">Phone</div>
            <div class="detail-value"><?= htmlspecialchars($listing['phoneNum']) ?></div>
          </div>
          <div class="detail-item">
            <div class="detail-label">Landlord ID</div>
            <div class="detail-value">#<?= $listing['landlord_id'] ?></div>
          </div>
        </div>
      </div>

      <!-- Admin Actions -->
      <div class="action-buttons">
        <button class="btn btn-<?= $listing['availability'] == 'available' ? 'warning' : 'success' ?>" 
                onclick="toggleAvailability(<?= $listing['ID'] ?>, '<?= $listing['availability'] ?>')">
          <i class='bx bx-toggle-<?= $listing['availability'] == 'available' ? 'left' : 'right' ?>'></i>
          <?= $listing['availability'] == 'available' ? 'Mark as Occupied' : 'Mark as Available' ?>
        </button>
        <button class="btn btn-danger" onclick="deleteListing(<?= $listing['ID'] ?>)">
          <i class='bx bx-trash'></i> Delete Listing
        </button>
        <button class="btn btn-secondary" onclick="window.location.href='listing.php'">
          <i class='bx bx-x'></i> Close
        </button>
      </div>
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

    // Toggle availability
    function toggleAvailability(id, currentStatus) {
      const newStatus = currentStatus === 'available' ? 'occupied' : 'available';
      const action = newStatus === 'available' ? 'mark as available' : 'mark as occupied';
      
      if(confirm(`Are you sure you want to ${action}?`)) {
        window.location.href = `manage_listing.php?id=${id}&action=toggle`;
      }
    }

    // Delete listing
    function deleteListing(id) {
      if(confirm('Are you sure you want to DELETE this listing? This cannot be undone!')) {
        window.location.href = `manage_listing.php?id=${id}&action=delete`;
      }
    }
  </script>

</body>
</html>