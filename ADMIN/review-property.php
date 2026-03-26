<?php
require_once '../session_auth.php';
require_once '../connection.php';

$admin_name = $_SESSION['username'];
$admin_id = $_SESSION['admin_id'];

$property_id = $_GET['id'] ?? null;

if (!$property_id) {
    header("Location: verify-properties.php");
    exit;
}

$stmt = $conn->prepare("
    SELECT 
        l.*,
        CONCAT(ll.firstName, ' ', ll.middleName, ' ', ll.lastName) as landlord_name,
        ll.email as landlord_email,
        ll.phoneNum as landlord_phone,
        ll.profilePic as landlord_profile,
        ll.verification_status as landlord_verification
    FROM listingtbl l
    LEFT JOIN landlordtbl ll ON l.landlord_id = ll.ID
    WHERE l.ID = ?
");

$stmt->bind_param("i", $property_id);
$stmt->execute();
$result = $stmt->get_result();
$property = $result->fetch_assoc();

if (!$property) {
    header("Location: verify-properties.php");
    exit;
}

$images = !empty($property['images']) ? json_decode($property['images'], true) : [];
if (!is_array($images)) {
    $images = array_map('trim', explode(',', $property['images']));
}

$pending_verification = $conn->query("SELECT COUNT(*) as count FROM landlordtbl WHERE verification_status='pending'")->fetch_assoc()['count'];
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Review Property - Admin Dashboard</title>
  <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
  <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css"/>
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

    .sidebar .menu-bar {
      height: calc(100% - 82px);
      display: flex;
      flex-direction: column;
      padding: 16px 0;
      overflow-y: auto;
      overflow-x: hidden;
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
    }

    .user-info .user-avatar {
      width: 36px;
      height: 36px;
      border-radius: 50%;
      object-fit: cover;
      border: 2px solid var(--primary-color);
    }

    .user-info .user-name {
      font-size: 13px;
      font-weight: 600;
      color: var(--text-color);
      display: block;
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
    }

    .btn-back {
      padding: 10px 20px;
      background: var(--sidebar-hover);
      color: var(--text-color);
      border: 1px solid var(--border-color);
      border-radius: 8px;
      text-decoration: none;
      font-weight: 600;
      transition: all 0.3s;
      display: inline-flex;
      align-items: center;
      gap: 8px;
    }

    .btn-back:hover {
      background: var(--border-color);
      color: white;
    }

    .review-container {
      display: grid;
      grid-template-columns: 1fr 400px;
      gap: 24px;
    }

    .property-details {
      background: var(--card-bg);
      border-radius: 12px;
      padding: 24px;
      border: 1px solid var(--border-color);
    }

    .status-badge {
      display: inline-flex;
      align-items: center;
      padding: 8px 16px;
      border-radius: 20px;
      font-size: 13px;
      font-weight: 600;
      gap: 6px;
      margin-bottom: 20px;
    }

    .status-badge.pending {
      background: rgba(251, 191, 36, 0.15);
      color: #fbbf24;
    }

    .property-gallery {
      display: grid;
      grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
      gap: 12px;
      margin-bottom: 24px;
    }

    .property-gallery img {
      width: 100%;
      height: 150px;
      object-fit: cover;
      border-radius: 8px;
      cursor: pointer;
      transition: transform 0.3s;
      border: 2px solid var(--border-color);
    }

    .property-gallery img:hover {
      transform: scale(1.05);
    }

    .property-info h2 {
      font-size: 24px;
      color: #ffffff;
      margin-bottom: 8px;
    }

    .property-price {
      font-size: 28px;
      font-weight: 700;
      color: var(--primary-color);
      margin-bottom: 20px;
    }

    .info-grid {
      display: grid;
      grid-template-columns: repeat(2, 1fr);
      gap: 16px;
      margin-bottom: 24px;
    }

    .info-item {
      background: var(--sidebar-hover);
      padding: 16px;
      border-radius: 8px;
    }

    .info-item label {
      display: block;
      font-size: 12px;
      color: var(--text-muted);
      margin-bottom: 4px;
      text-transform: uppercase;
      letter-spacing: 0.5px;
    }

    .info-item value {
      display: block;
      font-size: 16px;
      color: var(--text-color);
      font-weight: 600;
    }

    .description-section {
      margin-top: 24px;
    }

    .description-section h3 {
      font-size: 18px;
      color: #ffffff;
      margin-bottom: 12px;
    }

    .description-section p {
      color: var(--text-muted);
      line-height: 1.6;
    }

    #map {
      height: 300px;
      border-radius: 8px;
      margin-top: 24px;
      border: 2px solid var(--border-color);
    }

    .action-panel {
      background: var(--card-bg);
      border-radius: 12px;
      padding: 24px;
      border: 1px solid var(--border-color);
      height: fit-content;
      position: sticky;
      top: 30px;
    }

    .action-panel h3 {
      font-size: 18px;
      color: #ffffff;
      margin-bottom: 20px;
    }

    .landlord-card {
      background: var(--sidebar-hover);
      padding: 16px;
      border-radius: 8px;
      margin-bottom: 24px;
      display: flex;
      align-items: center;
      gap: 12px;
    }

    .landlord-card img {
      width: 60px;
      height: 60px;
      border-radius: 50%;
      object-fit: cover;
      border: 2px solid var(--primary-color);
    }

    .landlord-info h4 {
      font-size: 16px;
      color: #ffffff;
      margin-bottom: 4px;
    }

    .landlord-info p {
      font-size: 12px;
      color: var(--text-muted);
      margin: 2px 0;
    }

    .action-buttons {
      display: flex;
      flex-direction: column;
      gap: 12px;
    }

    .btn-approve {
      width: 100%;
      padding: 14px;
      background: #10b981;
      color: white;
      border: none;
      border-radius: 8px;
      font-weight: 600;
      cursor: pointer;
      transition: all 0.3s;
      display: flex;
      align-items: center;
      justify-content: center;
      gap: 8px;
      font-size: 15px;
    }

    .btn-approve:hover {
      background: #059669;
      transform: translateY(-2px);
      box-shadow: 0 4px 12px rgba(16, 185, 129, 0.3);
    }

    .btn-reject {
      width: 100%;
      padding: 14px;
      background: #ef4444;
      color: white;
      border: none;
      border-radius: 8px;
      font-weight: 600;
      cursor: pointer;
      transition: all 0.3s;
      display: flex;
      align-items: center;
      justify-content: center;
      gap: 8px;
      font-size: 15px;
    }

    .btn-reject:hover {
      background: #dc2626;
      transform: translateY(-2px);
      box-shadow: 0 4px 12px rgba(239, 68, 68, 0.3);
    }

    .btn-schedule {
      width: 100%;
      padding: 14px;
      background: var(--primary-color);
      color: white;
      border: none;
      border-radius: 8px;
      font-weight: 600;
      cursor: pointer;
      transition: all 0.3s;
      display: flex;
      align-items: center;
      justify-content: center;
      gap: 8px;
      font-size: 15px;
    }

    .btn-schedule:hover {
      background: var(--primary-hover);
      transform: translateY(-2px);
    }

    @media (max-width: 1024px) {
      .review-container {
        grid-template-columns: 1fr;
      }

      .action-panel {
        position: static;
      }
    }
  </style>
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
            <a href="dashboard.php">
              <i class='bx bx-home icon'></i>
              <span class="text">Home</span>
            </a>
          </li>
          <li>
            <a href="accounts.php">
              <i class='bx bx-user icon'></i>
              <span class="text">Accounts</span>
            </a>
          </li>
        </ul>

        <div class="menu-section-title">Management</div>
        <ul class="menu-links">
          <li>
            <a href="reports.php">
              <i class='bx bx-bar-chart-alt-2 icon'></i>
              <span class="text">Reports</span>
            </a>
          </li>
          <li>
            <a href="listing.php">
              <i class='bx bx-building-house icon'></i>
              <span class="text">Listing</span>
            </a>
          </li>
          <li>
            <a href="verify-properties.php" class="active">
              <i class='bx bx-shield-alt-2 icon'></i>
              <span class="text">Verify Properties</span>
            </a>
          </li>
          <li>
            <a href="verify-landlord.php">
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
          <div>
            <span class="user-name"><?= htmlspecialchars($admin_name) ?></span>
          </div>
        </div>

        <ul class="menu-links">
          <li>
            <a href="../logout.php">
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
      <h1>Review Property</h1>
      <a href="verify-properties.php" class="btn-back">
        <i class='bx bx-arrow-back'></i> Back to List
      </a>
    </div>

    <div class="review-container">
      <div class="property-details">
        <span class="status-badge <?= $property['verification_status'] ?>">
          <i class='bx bx-time'></i>
          <?= ucfirst($property['verification_status']) ?> Verification
        </span>

        <?php if (!empty($images)): ?>
          <div class="property-gallery">
            <?php foreach($images as $image): ?>
              <?php if (!empty(trim($image))): ?>
                <img src="../LANDLORD/uploads/<?= htmlspecialchars(trim($image)) ?>" 
                     alt="Property" 
                     onclick="viewImage(this.src)"
                     onerror="this.src='../img/house1.jpeg'">
              <?php endif; ?>
            <?php endforeach; ?>
          </div>
        <?php endif; ?>

        <div class="property-info">
          <h2><?= htmlspecialchars($property['listingName']) ?></h2>
          <div class="property-price">₱<?= number_format($property['price']) ?>/month</div>

          <div class="info-grid">
            <div class="info-item">
              <label>Location</label>
              <value><?= htmlspecialchars($property['barangay']) ?>, San Pedro, Laguna</value>
            </div>
            <div class="info-item">
              <label>Category</label>
              <value><?= htmlspecialchars($property['category']) ?></value>
            </div>
            <div class="info-item">
              <label>Rooms</label>
              <value><?= htmlspecialchars($property['rooms']) ?> Bedroom(s)</value>
            </div>
            <div class="info-item">
              <label>Submitted</label>
              <value><?= date('M d, Y', strtotime($property['listingDate'])) ?></value>
            </div>
          </div>

          <div class="info-item">
            <label>Full Address</label>
            <value><?= htmlspecialchars($property['address']) ?></value>
          </div>

          <?php if (!empty($property['listingDesc'])): ?>
            <div class="description-section">
              <h3>Description</h3>
              <p><?= nl2br(htmlspecialchars($property['listingDesc'])) ?></p>
            </div>
          <?php endif; ?>

          <?php if (!empty($property['latitude']) && !empty($property['longitude'])): ?>
            <div id="map"></div>
          <?php endif; ?>
        </div>
      </div>

      <div class="action-panel">
        <h3>Landlord Information</h3>
        
        <div class="landlord-card">
          <img src="<?= !empty($property['landlord_profile']) ? '../uploads/' . htmlspecialchars($property['landlord_profile']) : 'https://via.placeholder.com/60' ?>" 
               alt="Landlord"
               onerror="this.src='https://via.placeholder.com/60'">
          <div class="landlord-info">
            <h4><?= htmlspecialchars($property['landlord_name']) ?></h4>
            <p><i class='bx bx-envelope'></i> <?= htmlspecialchars($property['landlord_email']) ?></p>
            <?php if (!empty($property['landlord_phone'])): ?>
              <p><i class='bx bx-phone'></i> <?= htmlspecialchars($property['landlord_phone']) ?></p>
            <?php endif; ?>
            <p><i class='bx bx-shield-check'></i> Landlord: <?= ucfirst($property['landlord_verification']) ?></p>
          </div>
        </div>

        <h3>Actions</h3>
        <div class="action-buttons">
          <button class="btn-schedule" onclick="scheduleVisit()">
            <i class='bx bx-calendar'></i> Schedule Site Visit
          </button>
          
          <button class="btn-approve" onclick="approveProperty()">
            <i class='bx bx-check-circle'></i> Approve Property
          </button>
          
          <button class="btn-reject" onclick="rejectProperty()">
            <i class='bx bx-x-circle'></i> Reject Property
          </button>
        </div>
      </div>
    </div>
  </main>

  <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
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

    <?php if (!empty($property['latitude']) && !empty($property['longitude'])): ?>
    var map = L.map('map').setView([<?= $property['latitude'] ?>, <?= $property['longitude'] ?>], 16);
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png').addTo(map);
    L.marker([<?= $property['latitude'] ?>, <?= $property['longitude'] ?>])
      .addTo(map)
      .bindPopup("<?= htmlspecialchars($property['listingName']) ?>")
      .openPopup();
    <?php endif; ?>

    function viewImage(src) {
      Swal.fire({
        imageUrl: src,
        imageAlt: 'Property Image',
        showCloseButton: true,
        showConfirmButton: false,
        width: '80%',
        background: '#1a1d29',
        customClass: {
          image: 'swal-image-full'
        }
      });
    }

    function scheduleVisit() {
      Swal.fire({
        title: 'Schedule Site Visit',
        html: `
          <input type="datetime-local" id="visit-datetime" class="swal2-input" style="width: 80%;">
          <textarea id="visit-notes" class="swal2-textarea" placeholder="Visit notes (optional)" style="width: 80%;"></textarea>
        `,
        showCancelButton: true,
        confirmButtonText: 'Schedule',
        confirmButtonColor: 'rgb(141, 11, 65)',
        background: '#1a1d29',
        color: '#e4e6eb',
        preConfirm: () => {
          const datetime = document.getElementById('visit-datetime').value;
          const notes = document.getElementById('visit-notes').value;
          
          if (!datetime) {
            Swal.showValidationMessage('Please select date and time');
            return false;
          }
          
          return { datetime, notes };
        }
      }).then((result) => {
        if (result.isConfirmed) {
          const formData = new FormData();
          formData.append('property_id', <?= $property_id ?>);
          formData.append('action', 'schedule');
          formData.append('datetime', result.value.datetime);
          formData.append('notes', result.value.notes);

          fetch('property-action.php', {
            method: 'POST',
            body: formData
          })
          .then(res => res.json())
          .then(data => {
            if (data.success) {
              Swal.fire({
                icon: 'success',
                title: 'Visit Scheduled!',
                text: 'Site visit has been scheduled successfully.',
                confirmButtonColor: 'rgb(141, 11, 65)',
                background: '#1a1d29',
                color: '#e4e6eb'
              });
            } else {
              Swal.fire({
                icon: 'error',
                title: 'Error',
                text: data.error,
                confirmButtonColor: '#ef4444',
                background: '#1a1d29',
                color: '#e4e6eb'
              });
            }
          });
        }
      });
    }

    function approveProperty() {
      Swal.fire({
        title: 'Approve Property?',
        html: `
          <p style="margin-bottom: 16px;">This property will be published and visible to tenants.</p>
          <textarea id="admin-notes" class="swal2-textarea" placeholder="Admin notes (optional)" style="width: 80%;"></textarea>
        `,
        icon: 'question',
        showCancelButton: true,
        confirmButtonText: 'Approve',
        confirmButtonColor: '#10b981',
        cancelButtonColor: '#6c757d',
        background: '#1a1d29',
        color: '#e4e6eb'
      }).then((result) => {
        if (result.isConfirmed) {
          const notes = document.getElementById('admin-notes').value;
          const formData = new FormData();
          formData.append('property_id', <?= $property_id ?>);
          formData.append('action', 'approve');
          formData.append('notes', notes);

          fetch('property-action.php', {
            method: 'POST',
            body: formData
          })
          .then(res => res.json())
          .then(data => {
            if (data.success) {
              Swal.fire({
                icon: 'success',
                title: 'Property Approved!',
                text: 'The property is now live and visible to tenants.',
                confirmButtonColor: '#10b981',
                background: '#1a1d29',
                color: '#e4e6eb'
              }).then(() => {
                window.location.href = 'verify-properties.php';
              });
            } else {
              Swal.fire({
                icon: 'error',
                title: 'Error',
                text: data.error,
                confirmButtonColor: '#ef4444',
                background: '#1a1d29',
                color: '#e4e6eb'
              });
            }
          });
        }
      });
    }

    function rejectProperty() {
      Swal.fire({
        title: 'Reject Property?',
        html: `
          <p style="margin-bottom: 16px;">Please provide a reason for rejection:</p>
          <textarea id="rejection-reason" class="swal2-textarea" placeholder="Rejection reason (required)" style="width: 80%; height: 100px;" required></textarea>
        `,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'Reject',
        confirmButtonColor: '#ef4444',
        cancelButtonColor: '#6c757d',
        background: '#1a1d29',
        color: '#e4e6eb',
        preConfirm: () => {
          const reason = document.getElementById('rejection-reason').value;
          if (!reason.trim()) {
            Swal.showValidationMessage('Please provide a rejection reason');
            return false;
          }
          return reason;
        }
      }).then((result) => {
        if (result.isConfirmed) {
          const formData = new FormData();
          formData.append('property_id', <?= $property_id ?>);
          formData.append('action', 'reject');
          formData.append('reason', result.value);

          fetch('property-action.php', {
            method: 'POST',
            body: formData
          })
          .then(res => res.json())
          .then(data => {
            if (data.success) {
              Swal.fire({
                icon: 'success',
                title: 'Property Rejected',
                text: 'The landlord has been notified.',
                confirmButtonColor: '#ef4444',
                background: '#1a1d29',
                color: '#e4e6eb'
              }).then(() => {
                window.location.href = 'verify-properties.php';
              });
            } else {
              Swal.fire({
                icon: 'error',
                title: 'Error',
                text: data.error,
                confirmButtonColor: '#ef4444',
                background: '#1a1d29',
                color: '#e4e6eb'
              });
            }
          });
        }
      });
    }
  </script>

</body>
</html>