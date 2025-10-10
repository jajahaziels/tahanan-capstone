<?php
require_once '../session_auth.php';
require_once '../connection.php';

// Get admin info
$admin_id = $_SESSION['admin_id'];
$admin_email = $_SESSION['email'];
$admin_name = $_SESSION['firstName'] . ' ' . $_SESSION['lastName'];

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

// Parse images
$images = !empty($listing['images']) ? explode(',', $listing['images']) : ['/img/house1.jpeg'];
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= htmlspecialchars($listing['listingName']) ?> - Admin</title>
  <link rel="stylesheet" href="sidebar.css">
  <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
  <style>
    .content { margin-left: 260px; padding: 40px; }
    .listing-detail { background: white; border-radius: 12px; padding: 30px; box-shadow: 0 2px 8px rgba(0,0,0,0.1); max-width: 1000px; }
    .image-gallery { display: grid; grid-template-columns: repeat(auto-fill, minmax(200px, 1fr)); gap: 10px; margin-bottom: 30px; }
    .image-gallery img { width: 100%; height: 200px; object-fit: cover; border-radius: 8px; cursor: pointer; transition: transform 0.2s; }
    .image-gallery img:hover { transform: scale(1.05); }
    .main-image { width: 100%; max-height: 500px; object-fit: cover; border-radius: 12px; margin-bottom: 20px; }
    .detail-section { margin-bottom: 30px; }
    .detail-section h2 { color: #58929c; margin-bottom: 15px; border-bottom: 2px solid #58929c; padding-bottom: 10px; }
    .detail-grid { display: grid; grid-template-columns: repeat(2, 1fr); gap: 15px; }
    .detail-item { padding: 15px; background: #f8f9fa; border-radius: 8px; }
    .detail-label { font-weight: bold; color: #555; margin-bottom: 5px; }
    .detail-value { color: #333; }
    .status-badge { display: inline-block; padding: 8px 16px; border-radius: 20px; font-weight: bold; }
    .status-available { background: #28a745; color: white; }
    .status-occupied { background: #ffc107; color: #333; }
    .action-buttons { margin-top: 30px; display: flex; gap: 10px; flex-wrap: wrap; }
    .btn { padding: 12px 24px; border: none; border-radius: 8px; cursor: pointer; font-size: 14px; font-weight: 500; transition: all 0.3s; }
    .btn:hover { transform: translateY(-2px); box-shadow: 0 4px 8px rgba(0,0,0,0.2); }
    .btn-danger { background: #dc3545; color: white; }
    .btn-warning { background: #ffc107; color: #333; }
    .btn-secondary { background: #6c757d; color: white; }
    .btn-success { background: #28a745; color: white; }
    .map-container { width: 100%; height: 300px; background: #e9ecef; border-radius: 8px; display: flex; align-items: center; justify-content: center; color: #6c757d; }
  </style>
</head>
<body>

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
            <a href="admin.php">
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
            <strong><?= htmlspecialchars($admin_email) ?></strong>
          </small>
        </li>
      </div>
    </div>
  </nav>

  <main class="content">
    <a href="listing.php" style="color: #58929c; text-decoration: none; margin-bottom: 20px; display: inline-flex; align-items: center; gap: 5px; font-weight: 500;">
      <i class='bx bx-arrow-back'></i> Back to Listings
    </a>

    <div class="listing-detail">
      <div style="display: flex; justify-content: space-between; align-items: start; margin-bottom: 20px;">
        <div>
          <h1 style="margin: 0 0 10px 0; color: #333;"><?= htmlspecialchars($listing['listingName']) ?></h1>
          <span class="status-badge status-<?= $listing['availability'] ?>">
            <?= ucfirst($listing['availability']) ?>
          </span>
        </div>
        <div style="text-align: right;">
          <h2 style="margin: 0; color: #58929c;">₱<?= number_format($listing['price']) ?></h2>
          <small style="color: #666;">per month</small>
        </div>
      </div>

      <!-- Main Image -->
      <img src="<?= htmlspecialchars(trim($images[0])) ?>" alt="Main property image" class="main-image" onerror="this.src='/img/house1.jpeg'">

      <!-- Image Gallery -->
      <?php if (count($images) > 1): ?>
        <div class="image-gallery">
          <?php foreach(array_slice($images, 1, 4) as $img): ?>
            <img src="<?= htmlspecialchars(trim($img)) ?>" alt="Property image" onerror="this.src='/img/house1.jpeg'">
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
        <p style="line-height: 1.8; color: #555;"><?= nl2br(htmlspecialchars($listing['listingDesc'])) ?></p>
      </div>

      <!-- Location -->
      <div class="detail-section">
        <h2><i class='bx bx-map-pin'></i> Location</h2>
        <p style="margin-bottom: 15px; color: #555;">
          <strong>Address:</strong> <?= htmlspecialchars($listing['address']) ?>
        </p>
        <?php if (!empty($listing['latitude']) && !empty($listing['longitude'])): ?>
          <div class="map-container">
            <div style="text-align: center;">
              <i class='bx bx-map' style='font-size: 48px;'></i><br>
              Map: <?= htmlspecialchars($listing['latitude']) ?>, <?= htmlspecialchars($listing['longitude']) ?><br>
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

  <script src="sidebar.js"></script>
  <script>
    function toggleAvailability(id, currentStatus) {
      const newStatus = currentStatus === 'available' ? 'occupied' : 'available';
      const action = newStatus === 'available' ? 'mark as available' : 'mark as occupied';
      
      if(confirm(`Are you sure you want to ${action}?`)) {
        window.location.href = `manage_listing.php?id=${id}&action=toggle`;
      }
    }

    function deleteListing(id) {
      if(confirm('Are you sure you want to DELETE this listing? This cannot be undone!')) {
        window.location.href = `manage_listing.php?id=${id}&action=delete`;
      }
    }
  </script>

</body>
</html>