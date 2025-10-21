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
  <link rel="stylesheet" href="accounts.css">
  <link rel="stylesheet" href="sidebar.css">
  <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
  <style>
    .stats-bar {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
      gap: 15px;
      margin-bottom: 25px;
    }
    .stat-card {
      background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
      padding: 20px;
      border-radius: 12px;
      color: white;
      box-shadow: 0 4px 12px rgba(0,0,0,0.15);
    }
    .stat-card:nth-child(2) {
      background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
    }
    .stat-card:nth-child(3) {
      background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);
    }
    .stat-card:nth-child(4) {
      background: linear-gradient(135deg, #43e97b 0%, #38f9d7 100%);
    }
    .stat-card h4 {
      font-size: 14px;
      opacity: 0.9;
      margin-bottom: 10px;
    }
    .stat-card .number {
      font-size: 32px;
      font-weight: bold;
    }
    .search-filter-bar {
      display: flex;
      gap: 10px;
      margin-bottom: 20px;
      flex-wrap: wrap;
    }
    .search-filter-bar input,
    .search-filter-bar select {
      padding: 10px 15px;
      border: 1px solid #ddd;
      border-radius: 8px;
      font-size: 14px;
    }
    .search-filter-bar input {
      flex: 1;
      min-width: 250px;
    }
    .search-filter-bar button {
      padding: 10px 20px;
      background: #58929c;
      color: white;
      border: none;
      border-radius: 8px;
      cursor: pointer;
      font-weight: 500;
    }
    .search-filter-bar button:hover {
      background: #467580;
    }
    .btn-view {
      background: #58929c;
      color: white;
      padding: 8px 16px;
      border: none;
      border-radius: 6px;
      cursor: pointer;
      font-size: 13px;
      transition: all 0.3s;
    }
    .btn-view:hover {
      background: #467580;
      transform: translateY(-2px);
    }
    .btn-remove {
      background: #dc3545;
      color: white;
      padding: 8px 16px;
      border: none;
      border-radius: 6px;
      cursor: pointer;
      font-size: 13px;
      transition: all 0.3s;
    }
    .btn-remove:hover {
      background: #c82333;
      transform: translateY(-2px);
    }
    .actions {
      display: flex;
      gap: 8px;
      flex-direction: column;
    }
    
    /* Modal Styles */
    .modal {
      display: none;
      position: fixed;
      z-index: 1000;
      left: 0;
      top: 0;
      width: 100%;
      height: 100%;
      background-color: rgba(0,0,0,0.5);
      animation: fadeIn 0.3s;
    }
    @keyframes fadeIn {
      from { opacity: 0; }
      to { opacity: 1; }
    }
    .modal-content {
      background-color: #fefefe;
      margin: 3% auto;
      padding: 0;
      border-radius: 12px;
      width: 90%;
      max-width: 600px;
      box-shadow: 0 5px 30px rgba(0,0,0,0.3);
      animation: slideDown 0.3s;
    }
    @keyframes slideDown {
      from { transform: translateY(-50px); opacity: 0; }
      to { transform: translateY(0); opacity: 1; }
    }
    .modal-header {
      padding: 20px 30px;
      background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
      color: white;
      border-radius: 12px 12px 0 0;
      display: flex;
      justify-content: space-between;
      align-items: center;
    }
    .modal-header h2 {
      margin: 0;
      font-size: 22px;
    }
    .close {
      color: white;
      font-size: 32px;
      font-weight: bold;
      cursor: pointer;
      line-height: 20px;
    }
    .close:hover {
      opacity: 0.7;
    }
    .modal-body {
      padding: 30px;
    }
    .user-detail-row {
      display: flex;
      padding: 12px 0;
      border-bottom: 1px solid #eee;
    }
    .user-detail-row:last-child {
      border-bottom: none;
    }
    .detail-label {
      font-weight: 600;
      width: 150px;
      color: #555;
    }
    .detail-value {
      flex: 1;
      color: #333;
    }
    .verification-badge {
      display: inline-block;
      padding: 5px 12px;
      border-radius: 12px;
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
    .user-profile-pic-modal {
      width: 120px;
      height: 120px;
      border-radius: 50%;
      object-fit: cover;
      margin: 0 auto 20px;
      display: block;
      border: 4px solid #667eea;
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
  
  <main class="content">
    <h1>Manage Accounts</h1>

    <!-- Statistics Cards -->
    <div class="stats-bar">
      <div class="stat-card">
        <h4>Total Landlords</h4>
        <div class="number"><?= $total_landlords ?></div>
      </div>
      <div class="stat-card">
        <h4>Verified Landlords</h4>
        <div class="number"><?= $verified_landlords ?></div>
      </div>
      <div class="stat-card">
        <h4>Pending Verification</h4>
        <div class="number"><?= $pending_landlords ?></div>
      </div>
      <div class="stat-card">
        <h4>Total Tenants</h4>
        <div class="number"><?= $total_tenants ?></div>
      </div>
    </div>

    <!-- Search and Filter Bar -->
    <form method="GET" class="search-filter-bar">
      <input type="text" name="search" placeholder="Search by name, email, or username..." value="<?= htmlspecialchars($search) ?>">
      <select name="verification">
        <option value="all" <?= $verification_filter == 'all' ? 'selected' : '' ?>>All Verification Status</option>
        <option value="verified" <?= $verification_filter == 'verified' ? 'selected' : '' ?>>Verified</option>
        <option value="pending" <?= $verification_filter == 'pending' ? 'selected' : '' ?>>Pending</option>
        <option value="rejected" <?= $verification_filter == 'rejected' ? 'selected' : '' ?>>Rejected</option>
      </select>
      <button type="submit"><i class='bx bx-search'></i> Search</button>
      <?php if (!empty($search) || $verification_filter != 'all'): ?>
        <a href="admin.php" style="padding: 10px 20px; background: #6c757d; color: white; text-decoration: none; border-radius: 8px;">Clear</a>
      <?php endif; ?>
    </form>

    <!-- Tabs -->
    <div class="tabs">
      <button class="tab active" data-target="#landlords">
        Landlords (<?= $landlords_result->num_rows ?>)
      </button>
      <button class="tab" data-target="#tenants">
        Tenants (<?= $tenants_result->num_rows ?>)
      </button>
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
                <p><i class='bx bx-envelope'></i> <?= htmlspecialchars($landlord['email']) ?></p>
                <p><small><i class='bx bx-user'></i> <?= htmlspecialchars($landlord['username']) ?></small></p>
                <span class="badge <?= $landlord['verification_status'] == 'verified' ? 'active' : 'pending' ?>">
                  <?= ucfirst($landlord['verification_status'] ?? 'pending') ?>
                </span>
              </div>
              <div class="actions">
                <button class="btn-view" onclick='viewUser(<?= json_encode($landlord) ?>, "landlord")'>
                  <i class='bx bx-show'></i> View Details
                </button>
                <button class="btn-remove" onclick="removeUser('landlord', <?= $landlord['ID'] ?>, '<?= htmlspecialchars($landlord['firstName'] . ' ' . $landlord['lastName']) ?>')">
                  <i class='bx bx-trash'></i> Remove
                </button>
              </div>
            </div>
          <?php endwhile; ?>
        <?php else: ?>
          <p style="text-align: center; color: #666; padding: 40px;">No landlords found.</p>
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
                <p><i class='bx bx-envelope'></i> <?= htmlspecialchars($tenant['email']) ?></p>
                <p><small><i class='bx bx-user'></i> <?= htmlspecialchars($tenant['username']) ?></small></p>
                <span class="badge active">Active</span>
              </div>
              <div class="actions">
                <button class="btn-view" onclick='viewUser(<?= json_encode($tenant) ?>, "tenant")'>
                  <i class='bx bx-show'></i> View Details
                </button>
                <button class="btn-remove" onclick="removeUser('tenant', <?= $tenant['ID'] ?>, '<?= htmlspecialchars($tenant['firstName'] . ' ' . $tenant['lastName']) ?>')">
                  <i class='bx bx-trash'></i> Remove
                </button>
              </div>
            </div>
          <?php endwhile; ?>
        <?php else: ?>
          <p style="text-align: center; color: #666; padding: 40px;">No tenants found.</p>
        <?php endif; ?>
      </div>
    </div>
  </main>

  <!-- User Detail Modal -->
  <div id="userModal" class="modal">
    <div class="modal-content">
      <div class="modal-header">
        <h2 id="modalTitle">User Details</h2>
        <span class="close" onclick="closeModal()">&times;</span>
      </div>
      <div class="modal-body" id="modalBody">
        <!-- Content will be populated by JavaScript -->
      </div>
    </div>
  </div>

<script src="sidebar.js"></script>
<script src="tabs.js"></script>
<script>
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
      <img src="${user.profile_pic || '../img/default-avatar.jpg'}" class="user-profile-pic-modal" alt="Profile">
      
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
      ${type === 'landlord' ? `
      <div class="user-detail-row">
        <div class="detail-label">Verification:</div>
        <div class="detail-value">${verificationBadge}</div>
      </div>
      ${user.ID_image ? `
      <div class="user-detail-row">
        <div class="detail-label">ID Document:</div>
        <div class="detail-value">
          <a href="../LANDLORD/${user.ID_image}" target="_blank" style="color: #58929c; text-decoration: underline;">
            View Uploaded ID
          </a>
        </div>
      </div>
      ` : ''}
      ` : ''}
      <div class="user-detail-row">
        <div class="detail-label">Account Created:</div>
        <div class="detail-value">${user.created_at ? new Date(user.created_at).toLocaleDateString('en-US', {year: 'numeric', month: 'long', day: 'numeric'}) : 'N/A'}</div>
      </div>
    `;
    
    modal.style.display = 'block';
  }
  
  function closeModal() {
    document.getElementById('userModal').style.display = 'none';
  }
  
  // Close modal when clicking outside
  window.onclick = function(event) {
    const modal = document.getElementById('userModal');
    if (event.target == modal) {
      modal.style.display = 'none';
    }
  }
  
  function removeUser(type, id, name) {
    if(confirm(`Are you sure you want to remove ${name} from the system?\n\nThis action cannot be undone and will delete all their data.`)) {
      window.location.href = `delete_user.php?type=${type}&id=${id}`;
    }
  }
</script>

</body>
  
</html>