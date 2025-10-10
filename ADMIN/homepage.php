<?php
require_once '../session_auth.php';
require_once '../connection.php';

// Get admin info
$admin_id = $_SESSION['admin_id'];
$admin_email = $_SESSION['email'];
$admin_name = $_SESSION['firstName'] . ' ' . $_SESSION['lastName'];

// Get statistics from database
$total_landlords = $conn->query("SELECT COUNT(*) as count FROM landlordtbl")->fetch_assoc()['count'];
$total_tenants = $conn->query("SELECT COUNT(*) as count FROM tenanttbl")->fetch_assoc()['count'];
$total_users = $total_landlords + $total_tenants;

// Fetch real listing statistics
$total_posts = $conn->query("SELECT COUNT(*) as count FROM listingtbl")->fetch_assoc()['count'];
$active_posts = $conn->query("SELECT COUNT(*) as count FROM listingtbl WHERE availability='available'")->fetch_assoc()['count'];
$rented_properties = $conn->query("SELECT COUNT(*) as count FROM listingtbl WHERE availability='occupied'")->fetch_assoc()['count'];
?>

<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Admin Dashboard - Tahanan</title>
  <link rel="stylesheet" href="homepage.css">
  <link rel="stylesheet" href="sidebar.css">
  <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
</head>

<body>

  <!-- sidebar -->
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
  <!-- sidebar -->

  <main class="content">
    <header class="page-header">
      <h1>Dashboard Overview</h1>
      <p>Quick summary of your platform activity</p>
    </header>

    <section class="stats-grid">
      <!-- Card 1 -->
      <div class="stat-card users">
        <i class='bx bx-user'></i>
        <div class="stat-info">
          <h3><?= $total_users ?></h3>
          <p>Total Users</p>
        </div>
      </div>

      <!-- Card 2 -->
      <div class="stat-card tenants">
        <i class='bx bx-home-alt'></i>
        <div class="stat-info">
          <h3><?= $total_tenants ?></h3>
          <p>Total Tenants</p>
        </div>
      </div>

      <!-- Card 3 -->
      <div class="stat-card landlords">
        <i class='bx bx-building-house'></i>
        <div class="stat-info">
          <h3><?= $total_landlords ?></h3>
          <p>Total Landlords</p>
        </div>
      </div>

      <!-- Card 4 -->
      <div class="stat-card posts">
        <i class='bx bx-file'></i>
        <div class="stat-info">
          <h3><?= $total_posts ?></h3>
          <p>Total Posts</p>
        </div>
      </div>

      <!-- Card 5 -->
      <div class="stat-card active">
        <i class='bx bx-check-circle'></i>
        <div class="stat-info">
          <h3><?= $active_posts ?></h3>
          <p>Active Posts</p>
        </div>
      </div>

      <!-- Card 6 -->
      <div class="stat-card rented">
        <i class='bx bx-key'></i>
        <div class="stat-info">
          <h3><?= $rented_properties ?></h3>
          <p>Rented Properties</p>
        </div>
      </div>
    </section>
  </main>

<script src="sidebar.js"></script>

</body>
  
</html>