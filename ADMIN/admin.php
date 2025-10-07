<?php
require_once '../LOGIN/session_auth.php';
require_once '../connection.php';

// Get admin info
$admin_id = $_SESSION['admin_id'];
$admin_email = $_SESSION['email'];
$admin_name = $_SESSION['firstName'] . ' ' . $_SESSION['lastName'];

// Fetch all landlords
$landlords_query = "SELECT * FROM landlordtbl ORDER BY ID DESC";
$landlords_result = $conn->query($landlords_query);

// Fetch all tenants
$tenants_query = "SELECT * FROM tenanttbl ORDER BY ID DESC";
$tenants_result = $conn->query($tenants_query);
?>

<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Admin Dashboard - Tahanan</title>
  <link rel="stylesheet" href="account.css">
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
    <h1>Manage Accounts</h1>

    <!-- Tabs -->
    <div class="tabs">
      <button class="tab active" data-target="#landlords">Landlords</button>
      <button class="tab" data-target="#tenants">Tenants</button>
    </div>

    <!-- Landlords -->
    <div id="landlords" class="tab-content active">
      <div class="card-grid">
        <?php if ($landlords_result->num_rows > 0): ?>
          <?php while($landlord = $landlords_result->fetch_assoc()): ?>
            <div class="account-card">
              <img src="<?= !empty($landlord['profile_pic']) ? htmlspecialchars($landlord['profile_pic']) : '../img/default-avatar.jpg' ?>" 
                   alt="Landlord" class="profile-pic">
              <div class="info">
                <h3><?= htmlspecialchars($landlord['firstName'] . ' ' . $landlord['lastName']) ?></h3>
                <p><?= htmlspecialchars($landlord['email']) ?></p>
                <p><small>Username: <?= htmlspecialchars($landlord['username']) ?></small></p>
                <span class="badge <?= isset($landlord['status']) && $landlord['status'] == 'active' ? 'active' : 'pending' ?>">
                  <?= isset($landlord['status']) ? ucfirst($landlord['status']) : 'Active' ?>
                </span>
              </div>
              <div class="actions">
                <button class="btn green" onclick="approveUser('landlord', <?= $landlord['ID'] ?>)">
                  <i class='bx bx-check'></i>
                </button>
                <button class="btn red" onclick="deleteUser('landlord', <?= $landlord['ID'] ?>, '<?= htmlspecialchars($landlord['firstName']) ?>')">
                  <i class='bx bx-x'></i>
                </button>
              </div>
            </div>
          <?php endwhile; ?>
        <?php else: ?>
          <p>No landlords found.</p>
        <?php endif; ?>
      </div>
    </div>

    <!-- Tenants -->
    <div id="tenants" class="tab-content">
      <div class="card-grid">
        <?php if ($tenants_result->num_rows > 0): ?>
          <?php while($tenant = $tenants_result->fetch_assoc()): ?>
            <div class="account-card">
              <img src="<?= !empty($tenant['profile_pic']) ? htmlspecialchars($tenant['profile_pic']) : '../img/default-avatar.jpg' ?>" 
                   alt="Tenant" class="profile-pic">
              <div class="info">
                <h3><?= htmlspecialchars($tenant['firstName'] . ' ' . $tenant['lastName']) ?></h3>
                <p><?= htmlspecialchars($tenant['email']) ?></p>
                <p><small>Username: <?= htmlspecialchars($tenant['username']) ?></small></p>
                <span class="badge <?= isset($tenant['status']) && $tenant['status'] == 'active' ? 'active' : 'pending' ?>">
                  <?= isset($tenant['status']) ? ucfirst($tenant['status']) : 'Active' ?>
                </span>
              </div>
              <div class="actions">
                <button class="btn green" onclick="approveUser('tenant', <?= $tenant['ID'] ?>)">
                  <i class='bx bx-check'></i>
                </button>
                <button class="btn red" onclick="deleteUser('tenant', <?= $tenant['ID'] ?>, '<?= htmlspecialchars($tenant['firstName']) ?>')">
                  <i class='bx bx-x'></i>
                </button>
              </div>
            </div>
          <?php endwhile; ?>
        <?php else: ?>
          <p>No tenants found.</p>
        <?php endif; ?>
      </div>
    </div>
  </main>

<script src="sidebar.js"></script>
<script src="tabs.js"></script>
<script>
  function approveUser(type, id) {
    if(confirm('Approve this ' + type + '?')) {
      window.location.href = 'approve_user.php?type=' + type + '&id=' + id;
    }
  }

  function deleteUser(type, id, name) {
    if(confirm('Are you sure you want to delete ' + name + '?')) {
      window.location.href = 'delete_user.php?type=' + type + '&id=' + id;
    }
  }
</script>

</body>
  
</html>