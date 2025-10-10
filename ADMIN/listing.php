<?php
require_once '../session_auth.php';
require_once '../connection.php';

// Get admin info
$admin_id = $_SESSION['admin_id'];
$admin_email = $_SESSION['email'];
$admin_name = $_SESSION['firstName'] . ' ' . $_SESSION['lastName'];

// Handle search
$search = isset($_GET['search']) ? trim($_GET['search']) : '';

if (!empty($search)) {
    $stmt = $conn->prepare("SELECT * FROM listingtbl WHERE listingName LIKE ? OR listingDesc LIKE ? OR address LIKE ? ORDER BY ID DESC");
    $searchTerm = "%{$search}%";
    $stmt->bind_param("sss", $searchTerm, $searchTerm, $searchTerm);
    $stmt->execute();
    $listings_result = $stmt->get_result();
} else {
    $listings_query = "SELECT * FROM listingtbl ORDER BY ID DESC";
    $listings_result = $conn->query($listings_query);
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Listings - Admin Dashboard</title>
  <link rel="stylesheet" href="listing.css">
  <link rel="stylesheet" href="sidebar.css">
  <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
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
  <h1 style="margin-bottom: 20px; color: #333;">Property Listings</h1>
  
  <div class="search-bar">
    <form method="GET" action="" style="display: flex; width: 100%; gap: 10px;">
      <input type="text" name="search" id="searchInput" placeholder="Search by name, description, or address..." value="<?= htmlspecialchars($search) ?>">
      <button type="submit">Search</button>
      <?php if (!empty($search)): ?>
        <a href="listing.php" style="padding: 10px 20px; border-radius: 12px; background: #dc3545; color: white; text-decoration: none; display: flex; align-items: center;">Clear</a>
      <?php endif; ?>
    </form>
  </div>

  <div class="card-grid">
    <?php if ($listings_result && $listings_result->num_rows > 0): ?>
      <?php while($listing = $listings_result->fetch_assoc()): ?>
        <div class="listing-card">
          <?php
          // Handle multiple images stored in 'images' field (longtext)
          $images = !empty($listing['images']) ? $listing['images'] : '';
          $first_image = '/img/house1.jpeg'; // default image
          
          // If images is a comma-separated list or JSON array
          if (!empty($images)) {
              $image_array = explode(',', $images);
              $first_image = trim($image_array[0]);
          }
          ?>
          <img src="<?= htmlspecialchars($first_image) ?>" 
               alt="<?= htmlspecialchars($listing['listingName']) ?>"
               onerror="this.src='../img/house1.jpeg'">
          
          <div class="card-body">
            <h3><?= htmlspecialchars($listing['listingName']) ?></h3>
            <p class="price">₱<?= number_format($listing['price']) ?> / monthly</p>
            <p class="details">
              <?= htmlspecialchars($listing['rooms']) ?> bedrooms, 
              <?= htmlspecialchars($listing['barangay']) ?>
              <?php if (!empty($listing['listingDesc'])): ?>
                <br><small><?= htmlspecialchars(substr($listing['listingDesc'], 0, 60)) ?>...</small>
              <?php endif; ?>
            </p>
            <span style="display: inline-block; padding: 4px 10px; border-radius: 12px; font-size: 12px; margin-bottom: 10px; 
                         background: <?= $listing['availability'] == 'available' ? '#28a745' : '#ffc107' ?>; color: white;">
              <?= ucfirst($listing['availability']) ?>
            </span>
            <div class="card-actions">
              <button class="btn-outline" onclick="toggleFavorite(<?= $listing['ID'] ?>)">☆</button>
              <button class="btn-primary" onclick="window.location.href='view_listing.php?id=<?= $listing['ID'] ?>'">View Details</button>
            </div>
          </div>
        </div>
      <?php endwhile; ?>
    <?php else: ?>
      <div style="grid-column: 1/-1; text-align: center; padding: 60px;">
        <i class='bx bx-home' style='font-size: 72px; opacity: 0.3; color: #58929c;'></i>
        <h3 style="margin-top: 20px; color: #666;">
          <?= !empty($search) ? 'No listings found matching your search.' : 'No listings yet.' ?>
        </h3>
        <p style="color: #999;">
          <?= !empty($search) ? 'Try a different search term.' : 'Property listings will appear here once landlords post them.' ?>
        </p>
      </div>
    <?php endif; ?>
  </div>
</main>

<script src="sidebar.js"></script>
<script>
  function toggleFavorite(id) {
    // Add favorite functionality here if needed
    alert('Favorite feature - Coming soon!');
  }
</script>

</body>
  
</html>