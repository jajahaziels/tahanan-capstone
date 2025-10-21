<?php
require_once '../session_auth.php';
require_once '../connection.php';

// Get admin info
$admin_name = $_SESSION['username'];

// Fetch feedbacks and reports from database
// NOTE: Adjust table names and columns based on your database structure
// $feedbacks = $conn->query("SELECT * FROM feedbacktbl ORDER BY created_at DESC");
// $reports = $conn->query("SELECT * FROM reportstbl ORDER BY created_at DESC");
?>

<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Reports & Feedback - Admin Dashboard</title>
  <link rel="stylesheet" href="report.css">
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
            <a href="accounts.php">
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
  

<div id="container">
  <!-- Feedbacks Column -->
  <div class="column">
    <h2>FEEDBACKS</h2>
    
    <?php 
    // Uncomment when you have feedbacks table
    /*
    if (isset($feedbacks) && $feedbacks->num_rows > 0) {
        while($feedback = $feedbacks->fetch_assoc()) {
            echo '<div class="report-item">';
            echo '<h4>' . htmlspecialchars($feedback['user_name']) . '</h4>';
            echo '<p>' . htmlspecialchars($feedback['message']) . '</p>';
            echo '<small>' . date('M d, Y', strtotime($feedback['created_at'])) . '</small>';
            echo '</div>';
        }
    } else {
        echo '<p>No feedbacks yet.</p>';
    }
    */
    ?>
    
    <p style="text-align: center; padding: 40px; color: #666;">
      <i class='bx bx-message-square-dots' style='font-size: 48px; opacity: 0.3;'></i><br>
      No feedbacks yet. User feedback will appear here.
    </p>
  </div>

  <!-- Reports Column -->
  <div class="column">
    <h2>REPORTS</h2>
    
    <?php 
    // Uncomment when you have reports table
    /*
    if (isset($reports) && $reports->num_rows > 0) {
        while($report = $reports->fetch_assoc()) {
            echo '<div class="report-item">';
            echo '<h4>' . htmlspecialchars($report['report_type']) . '</h4>';
            echo '<p>' . htmlspecialchars($report['description']) . '</p>';
            echo '<small>Reported by: ' . htmlspecialchars($report['reporter_name']) . '</small>';
            echo '<small> on ' . date('M d, Y', strtotime($report['created_at'])) . '</small>';
            echo '</div>';
        }
    } else {
        echo '<p>No reports yet.</p>';
    }
    */
    ?>
    
    <p style="text-align: center; padding: 40px; color: #666;">
      <i class='bx bx-error-circle' style='font-size: 48px; opacity: 0.3;'></i><br>
      No reports yet. User reports will appear here.
    </p>
  </div>
</div>

<script src="sidebar.js"></script>
</body>
  
</html>