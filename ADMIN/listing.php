<?php
require_once '../session_auth.php';
require_once '../connection.php';

// Get admin info
$admin_id = $_SESSION['admin_id'];
$admin_email = $_SESSION['email'];
$admin_name = $_SESSION['firstName'] . ' ' . $_SESSION['lastName'];

// Handle filters
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$barangay = isset($_GET['barangay']) ? $_GET['barangay'] : '';
$category = isset($_GET['category']) ? $_GET['category'] : '';
$rooms = isset($_GET['rooms']) ? $_GET['rooms'] : '';
$min_price = isset($_GET['min_price']) ? intval($_GET['min_price']) : '';
$max_price = isset($_GET['max_price']) ? intval($_GET['max_price']) : '';
$availability = isset($_GET['availability']) ? $_GET['availability'] : '';

// Build query with filters
$listings_query = "SELECT * FROM listingtbl WHERE 1=1";

if (!empty($search)) {
    $listings_query .= " AND (listingName LIKE '%$search%' OR listingDesc LIKE '%$search%' OR address LIKE '%$search%')";
}

if (!empty($barangay)) {
    $listings_query .= " AND barangay = '$barangay'";
}

if (!empty($category)) {
    $listings_query .= " AND category = '$category'";
}

if (!empty($rooms)) {
    $listings_query .= " AND rooms = $rooms";
}

if (!empty($min_price)) {
    $listings_query .= " AND price >= $min_price";
}

if (!empty($max_price)) {
    $listings_query .= " AND price <= $max_price";
}

if (!empty($availability)) {
    $listings_query .= " AND availability = '$availability'";
}

$listings_query .= " ORDER BY ID DESC";
$listings_result = $conn->query($listings_query);

// Get filter statistics
$total_listings = $conn->query("SELECT COUNT(*) as count FROM listingtbl")->fetch_assoc()['count'];
$filtered_count = $listings_result->num_rows;
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
  <style>
    .filters-container {
      background: white;
      border-radius: 12px;
      padding: 25px;
      margin-bottom: 25px;
      box-shadow: 0 2px 8px rgba(0,0,0,0.1);
    }

    .filters-header {
      display: flex;
      justify-content: space-between;
      align-items: center;
      margin-bottom: 20px;
    }

    .filters-header h2 {
      margin: 0;
      font-size: 18px;
      color: #333;
    }

    .filter-toggle {
      background: #58929c;
      color: white;
      border: none;
      padding: 8px 16px;
      border-radius: 6px;
      cursor: pointer;
      font-size: 14px;
      display: flex;
      align-items: center;
      gap: 8px;
    }

    .filters-grid {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
      gap: 15px;
      margin-bottom: 20px;
    }

    .filter-group {
      display: flex;
      flex-direction: column;
    }

    .filter-group label {
      font-size: 13px;
      color: #555;
      margin-bottom: 6px;
      font-weight: 500;
    }

    .filter-group select,
    .filter-group input {
      padding: 10px 12px;
      border: 1px solid #ddd;
      border-radius: 8px;
      font-size: 14px;
      background: white;
      transition: all 0.3s;
    }

    .filter-group select:focus,
    .filter-group input:focus {
      outline: none;
      border-color: #58929c;
      box-shadow: 0 0 0 3px rgba(88, 146, 156, 0.1);
    }

    .price-range {
      display: grid;
      grid-template-columns: 1fr auto 1fr;
      gap: 10px;
      align-items: end;
    }

    .price-range span {
      text-align: center;
      padding-bottom: 10px;
      color: #999;
    }

    .filter-actions {
      display: flex;
      gap: 10px;
      justify-content: flex-end;
    }

    .btn-filter {
      padding: 10px 24px;
      border: none;
      border-radius: 8px;
      cursor: pointer;
      font-size: 14px;
      font-weight: 500;
      transition: all 0.3s;
      display: flex;
      align-items: center;
      gap: 8px;
    }

    .btn-apply {
      background: #58929c;
      color: white;
    }

    .btn-apply:hover {
      background: #467580;
    }

    .btn-reset {
      background: #6c757d;
      color: white;
    }

    .btn-reset:hover {
      background: #5a6268;
    }

    .results-info {
      display: flex;
      justify-content: space-between;
      align-items: center;
      margin-bottom: 20px;
      padding: 15px 20px;
      background: #f8f9fa;
      border-radius: 8px;
    }

    .results-count {
      font-size: 14px;
      color: #666;
    }

    .results-count strong {
      color: #58929c;
      font-size: 16px;
    }

    .active-filters {
      display: flex;
      gap: 8px;
      flex-wrap: wrap;
    }

    .filter-tag {
      background: #58929c;
      color: white;
      padding: 5px 12px;
      border-radius: 20px;
      font-size: 12px;
      display: flex;
      align-items: center;
      gap: 6px;
    }

    .filter-tag i {
      cursor: pointer;
      font-size: 14px;
    }

    .filter-tag i:hover {
      opacity: 0.7;
    }

    @media (max-width: 768px) {
      .filters-grid {
        grid-template-columns: 1fr;
      }

      .filter-actions {
        flex-direction: column;
      }

      .btn-filter {
        width: 100%;
        justify-content: center;
      }
    }
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
  <h1 style="margin-bottom: 20px; color: #333;">Property Listings</h1>
  
  <!-- Advanced Filters -->
  <div class="filters-container">
    <div class="filters-header">
      <h2><i class='bx bx-filter-alt'></i> Advanced Filters</h2>
      <button class="filter-toggle" onclick="toggleFilters()">
        <i class='bx bx-chevron-down' id="filter-icon"></i>
        <span id="filter-text">Hide Filters</span>
      </button>
    </div>

    <form method="GET" action="" id="filter-form">
      <div class="filters-grid">
        <!-- Search -->
        <div class="filter-group">
          <label><i class='bx bx-search'></i> Search</label>
          <input type="text" name="search" placeholder="Search by name, description..." value="<?= htmlspecialchars($search) ?>">
        </div>

        <!-- Barangay -->
        <div class="filter-group">
          <label><i class='bx bx-map'></i> Barangay</label>
          <select name="barangay">
            <option value="">All Barangays</option>
            <option value="Bagong Silang" <?= $barangay == 'Bagong Silang' ? 'selected' : '' ?>>Bagong Silang</option>
            <option value="Calendola" <?= $barangay == 'Calendola' ? 'selected' : '' ?>>Calendola</option>
            <option value="Chrysanthemum" <?= $barangay == 'Chrysanthemum' ? 'selected' : '' ?>>Chrysanthemum</option>
            <option value="Cuyab" <?= $barangay == 'Cuyab' ? 'selected' : '' ?>>Cuyab</option>
            <option value="Estrella" <?= $barangay == 'Estrella' ? 'selected' : '' ?>>Estrella</option>
            <option value="Fatima" <?= $barangay == 'Fatima' ? 'selected' : '' ?>>Fatima</option>
            <option value="G.S.I.S." <?= $barangay == 'G.S.I.S.' ? 'selected' : '' ?>>G.S.I.S.</option>
            <option value="Landayan" <?= $barangay == 'Landayan' ? 'selected' : '' ?>>Landayan</option>
            <option value="Langgam" <?= $barangay == 'Langgam' ? 'selected' : '' ?>>Langgam</option>
            <option value="Laram" <?= $barangay == 'Laram' ? 'selected' : '' ?>>Laram</option>
            <option value="Magsaysay" <?= $barangay == 'Magsaysay' ? 'selected' : '' ?>>Magsaysay</option>
            <option value="Maharlika" <?= $barangay == 'Maharlika' ? 'selected' : '' ?>>Maharlika</option>
            <option value="Narra" <?= $barangay == 'Narra' ? 'selected' : '' ?>>Narra</option>
            <option value="Nueva" <?= $barangay == 'Nueva' ? 'selected' : '' ?>>Nueva</option>
            <option value="Pacita 1" <?= $barangay == 'Pacita 1' ? 'selected' : '' ?>>Pacita 1</option>
            <option value="Pacita 2" <?= $barangay == 'Pacita 2' ? 'selected' : '' ?>>Pacita 2</option>
            <option value="Poblacion" <?= $barangay == 'Poblacion' ? 'selected' : '' ?>>Poblacion</option>
            <option value="Riverside" <?= $barangay == 'Riverside' ? 'selected' : '' ?>>Riverside</option>
            <option value="Rosario" <?= $barangay == 'Rosario' ? 'selected' : '' ?>>Rosario</option>
            <option value="Sampaguita Village" <?= $barangay == 'Sampaguita Village' ? 'selected' : '' ?>>Sampaguita Village</option>
            <option value="San Antonio" <?= $barangay == 'San Antonio' ? 'selected' : '' ?>>San Antonio</option>
            <option value="San Roque" <?= $barangay == 'San Roque' ? 'selected' : '' ?>>San Roque</option>
            <option value="San Vicente" <?= $barangay == 'San Vicente' ? 'selected' : '' ?>>San Vicente</option>
            <option value="San Lorenzo Ruiz" <?= $barangay == 'San Lorenzo Ruiz' ? 'selected' : '' ?>>San Lorenzo Ruiz</option>
            <option value="Santo Niño" <?= $barangay == 'Santo Niño' ? 'selected' : '' ?>>Santo Niño</option>
            <option value="United Bayanihan" <?= $barangay == 'United Bayanihan' ? 'selected' : '' ?>>United Bayanihan</option>
            <option value="United Better Living" <?= $barangay == 'United Better Living' ? 'selected' : '' ?>>United Better Living</option>
          </select>
        </div>

        <!-- Category -->
        <div class="filter-group">
          <label><i class='bx bx-category'></i> Category</label>
          <select name="category">
            <option value="">All Categories</option>
            <option value="Condominium" <?= $category == 'Condominium' ? 'selected' : '' ?>>Condominium</option>
            <option value="Apartment complex" <?= $category == 'Apartment complex' ? 'selected' : '' ?>>Apartment complex</option>
            <option value="Single-family home" <?= $category == 'Single-family home' ? 'selected' : '' ?>>Single-family home</option>
            <option value="Townhouse" <?= $category == 'Townhouse' ? 'selected' : '' ?>>Townhouse</option>
            <option value="Low-rise apartment" <?= $category == 'Low-rise apartment' ? 'selected' : '' ?>>Low-rise apartment</option>
            <option value="High-rise apartment" <?= $category == 'High-rise apartment' ? 'selected' : '' ?>>High-rise apartment</option>
          </select>
        </div>

        <!-- Rooms -->
        <div class="filter-group">
          <label><i class='bx bx-bed'></i> Number of Rooms</label>
          <select name="rooms">
            <option value="">Any</option>
            <option value="1" <?= $rooms == '1' ? 'selected' : '' ?>>1 Room</option>
            <option value="2" <?= $rooms == '2' ? 'selected' : '' ?>>2 Rooms</option>
            <option value="3" <?= $rooms == '3' ? 'selected' : '' ?>>3 Rooms</option>
            <option value="4" <?= $rooms == '4' ? 'selected' : '' ?>>4 Rooms</option>
            <option value="5" <?= $rooms == '5' ? 'selected' : '' ?>>5+ Rooms</option>
          </select>
        </div>

        <!-- Availability -->
        <div class="filter-group">
          <label><i class='bx bx-check-circle'></i> Availability</label>
          <select name="availability">
            <option value="">All Status</option>
            <option value="available" <?= $availability == 'available' ? 'selected' : '' ?>>Available</option>
            <option value="occupied" <?= $availability == 'occupied' ? 'selected' : '' ?>>Occupied</option>
          </select>
        </div>
      </div>

      <!-- Price Range -->
      <div class="filter-group">
        <label><i class='bx bx-money'></i> Price Range (₱)</label>
        <div class="price-range">
          <input type="number" name="min_price" placeholder="Min Price" value="<?= htmlspecialchars($min_price) ?>" min="0">
          <span>to</span>
          <input type="number" name="max_price" placeholder="Max Price" value="<?= htmlspecialchars($max_price) ?>" min="0">
        </div>
      </div>

      <!-- Filter Actions -->
      <div class="filter-actions">
        <button type="submit" class="btn-filter btn-apply">
          <i class='bx bx-search'></i> Apply Filters
        </button>
        <a href="listing.php" class="btn-filter btn-reset">
          <i class='bx bx-reset'></i> Reset All
        </a>
      </div>
    </form>
  </div>

  <!-- Results Info -->
  <div class="results-info">
    <div class="results-count">
      Showing <strong><?= $filtered_count ?></strong> of <strong><?= $total_listings ?></strong> listings
    </div>
    
    <?php if (!empty($search) || !empty($barangay) || !empty($category) || !empty($rooms) || !empty($min_price) || !empty($max_price) || !empty($availability)): ?>
      <div class="active-filters">
        <?php if (!empty($search)): ?>
          <span class="filter-tag">
            Search: "<?= htmlspecialchars($search) ?>"
            <i class='bx bx-x' onclick="removeFilter('search')"></i>
          </span>
        <?php endif; ?>
        <?php if (!empty($barangay)): ?>
          <span class="filter-tag">
            <?= htmlspecialchars($barangay) ?>
            <i class='bx bx-x' onclick="removeFilter('barangay')"></i>
          </span>
        <?php endif; ?>
        <?php if (!empty($category)): ?>
          <span class="filter-tag">
            <?= htmlspecialchars($category) ?>
            <i class='bx bx-x' onclick="removeFilter('category')"></i>
          </span>
        <?php endif; ?>
        <?php if (!empty($rooms)): ?>
          <span class="filter-tag">
            <?= $rooms ?> Room<?= $rooms > 1 ? 's' : '' ?>
            <i class='bx bx-x' onclick="removeFilter('rooms')"></i>
          </span>
        <?php endif; ?>
        <?php if (!empty($min_price)): ?>
          <span class="filter-tag">
            Min: ₱<?= number_format($min_price) ?>
            <i class='bx bx-x' onclick="removeFilter('min_price')"></i>
          </span>
        <?php endif; ?>
        <?php if (!empty($max_price)): ?>
          <span class="filter-tag">
            Max: ₱<?= number_format($max_price) ?>
            <i class='bx bx-x' onclick="removeFilter('max_price')"></i>
          </span>
        <?php endif; ?>
        <?php if (!empty($availability)): ?>
          <span class="filter-tag">
            <?= ucfirst($availability) ?>
            <i class='bx bx-x' onclick="removeFilter('availability')"></i>
          </span>
        <?php endif; ?>
      </div>
    <?php endif; ?>
  </div>

  <div class="card-grid">
    <?php if ($listings_result && $listings_result->num_rows > 0): ?>
      <?php while($listing = $listings_result->fetch_assoc()): ?>
        <div class="listing-card">
          <?php
          $images = !empty($listing['images']) ? $listing['images'] : '';
          $first_image = '../img/house1.jpeg'; // Default fallback image
          
          if (!empty($images)) {
              // Check if it's JSON format (from landlord upload)
              $image_array = json_decode($images, true);
              
              // If not JSON, try comma-separated (legacy format)
              if (!is_array($image_array)) {
                  $image_array = explode(',', $images);
              }
              
              // Get first image
              if (!empty($image_array) && isset($image_array[0])) {
                  $raw_image = trim($image_array[0]);
                  
                  if (!empty($raw_image)) {
                      // Build correct path: ../LANDLORD/uploads/filename.jpg
                      if (strpos($raw_image, '../LANDLORD/') === 0) {
                          // Already has full path
                          $first_image = $raw_image;
                      } elseif (strpos($raw_image, 'uploads/') === 0) {
                          // Has uploads/ prefix
                          $first_image = '../LANDLORD/' . $raw_image;
                      } else {
                          // Just filename
                          $first_image = '../LANDLORD/uploads/' . $raw_image;
                      }
                  }
              }
          }
          ?>
          <img src="<?= htmlspecialchars($first_image) ?>" 
               alt="<?= htmlspecialchars($listing['listingName']) ?>"
               onerror="this.src='../img/house1.jpeg'">
          
          <div class="card-body">
            <h3><?= htmlspecialchars($listing['listingName']) ?></h3>
            <p class="price">₱<?= number_format($listing['price']) ?> / monthly</p>
            <p class="details">
              <i class='bx bx-bed'></i> <?= htmlspecialchars($listing['rooms']) ?> rooms • 
              <i class='bx bx-map'></i> <?= htmlspecialchars($listing['barangay']) ?>
              <?php if (!empty($listing['category'])): ?>
                <br><small><i class='bx bx-category'></i> <?= htmlspecialchars($listing['category']) ?></small>
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
        <i class='bx bx-search' style='font-size: 72px; opacity: 0.3; color: #58929c;'></i>
        <h3 style="margin-top: 20px; color: #666;">No listings found</h3>
        <p style="color: #999;">Try adjusting your filters to see more results.</p>
        <a href="listing.php" style="display: inline-block; margin-top: 15px; padding: 10px 20px; background: #58929c; color: white; text-decoration: none; border-radius: 8px;">
          Clear All Filters
        </a>
      </div>
    <?php endif; ?>
  </div>
</main>

<script src="sidebar.js"></script>
<script>
  let filtersVisible = true;

  function toggleFilters() {
    const filterForm = document.getElementById('filter-form');
    const filterIcon = document.getElementById('filter-icon');
    const filterText = document.getElementById('filter-text');
    
    filtersVisible = !filtersVisible;
    
    if (filtersVisible) {
      filterForm.style.display = 'block';
      filterIcon.className = 'bx bx-chevron-down';
      filterText.textContent = 'Hide Filters';
    } else {
      filterForm.style.display = 'none';
      filterIcon.className = 'bx bx-chevron-right';
      filterText.textContent = 'Show Filters';
    }
  }

  function removeFilter(filterName) {
    const url = new URL(window.location.href);
    url.searchParams.delete(filterName);
    window.location.href = url.toString();
  }

  function toggleFavorite(id) {
    alert('Favorite feature - Coming soon!');
  }
</script>

</body>
  
</html>