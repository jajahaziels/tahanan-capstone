<?php
require_once '../session_auth.php';
require_once '../connection.php';

// Get admin info
$admin_id = $_SESSION['admin_id'];
$admin_email = $_SESSION['email'];
$admin_name = $_SESSION['firstName'] . ' ' . $_SESSION['lastName'];

// Fetch pending landlord verification requests
// Changed to use verification_status instead of is_verified
$verify_query = "SELECT ID, firstName, lastName, email, username, phoneNum, ID_image, created_at
                 FROM landlordtbl 
                 WHERE verification_status = 'pending' 
                 ORDER BY created_at DESC";
$verify_result = $conn->query($verify_query);
?>

<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Verify Landlords - Admin Dashboard</title>
  <link rel="stylesheet" href="verify.css">
  <link rel="stylesheet" href="sidebar.css">
  <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
  <style>
    .id-image-container {
      margin: 15px 0;
      text-align: center;
    }
    .id-image {
      max-width: 100%;
      height: auto;
      max-height: 300px;
      border: 2px solid #ddd;
      border-radius: 8px;
      cursor: pointer;
      transition: transform 0.3s;
    }
    .id-image:hover {
      transform: scale(1.05);
    }
    .no-id-badge {
      display: inline-block;
      padding: 10px 20px;
      background: #ffc107;
      color: #333;
      border-radius: 8px;
      font-weight: 500;
    }
    .verify-card {
      background: white;
      border-radius: 12px;
      padding: 25px;
      margin-bottom: 20px;
      box-shadow: 0 2px 8px rgba(0,0,0,0.1);
      transition: transform 0.3s, box-shadow 0.3s;
    }
    .verify-card:hover {
      transform: translateY(-5px);
      box-shadow: 0 4px 16px rgba(0,0,0,0.15);
    }
    .success-message {
      padding: 15px;
      background: #d4edda;
      color: #155724;
      border-radius: 8px;
      margin-bottom: 20px;
      border-left: 4px solid #28a745;
    }
    .modal {
      display: none;
      position: fixed;
      z-index: 1000;
      left: 0;
      top: 0;
      width: 100%;
      height: 100%;
      background-color: rgba(0,0,0,0.9);
    }
    .modal-content {
      margin: auto;
      display: block;
      max-width: 90%;
      max-height: 90%;
      margin-top: 50px;
    }
    .close-modal {
      position: absolute;
      top: 20px;
      right: 35px;
      color: #f1f1f1;
      font-size: 40px;
      font-weight: bold;
      cursor: pointer;
    }
  </style>
</head>

<body>
  <!-- SIDEBAR -->
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

  <!-- SIDEBAR -->

  <main class="content">
    <header class="page-header">
      <h1>Verify Landlord Requests</h1>
      <p>Review ID documents and approve or reject landlord applications</p>
    </header>

    <?php if (isset($_GET['success'])): ?>
      <div class="success-message">
        <i class='bx bx-check-circle'></i>
        <?php if ($_GET['success'] == 'verified'): ?>
          Landlord verified successfully!
        <?php elseif ($_GET['success'] == 'rejected'): ?>
          Landlord application rejected.
        <?php endif; ?>
      </div>
    <?php endif; ?>

    <section class="verify-list">
      <?php if ($verify_result->num_rows > 0): ?>
        <?php while($landlord = $verify_result->fetch_assoc()): ?>
          <div class="verify-card">
            <div style="display: grid; grid-template-columns: auto 1fr auto; gap: 20px; align-items: start;">
              
              <!-- Profile Picture Section -->
              <div style="text-align: center;">
                <img src="<?= !empty($landlord['profile_pic']) ? htmlspecialchars($landlord['profile_pic']) : '../img/sams.png' ?>" 
                     alt="Landlord" class="profile-pic" 
                     style="width: 100px; height: 100px; border-radius: 50%; object-fit: cover; border: 3px solid #58929c;">
              </div>

              <!-- Details Section -->
              <div class="details">
                <h3 style="margin: 0 0 10px 0; color: #333;">
                  <?= htmlspecialchars($landlord['firstName'] . ' ' . $landlord['lastName']) ?>
                </h3>
                <p style="margin: 5px 0; color: #666;">
                  <i class='bx bx-envelope'></i> <?= htmlspecialchars($landlord['email']) ?>
                </p>
                <p style="margin: 5px 0; color: #666;">
                  <i class='bx bx-user'></i> Username: <?= htmlspecialchars($landlord['username']) ?>
                </p>
                <?php if (!empty($landlord['phoneNum'])): ?>
                  <p style="margin: 5px 0; color: #666;">
                    <i class='bx bx-phone'></i> <?= htmlspecialchars($landlord['phoneNum']) ?>
                  </p>
                <?php endif; ?>
                <p style="margin: 5px 0; color: #999; font-size: 13px;">
                  <i class='bx bx-calendar'></i> Requested: <?= isset($landlord['created_at']) ? date('M d, Y', strtotime($landlord['created_at'])) : 'N/A' ?>
                </p>

                <!-- ID Image Section -->
                <div class="id-image-container">
                  <?php if (!empty($landlord['ID_image'])): ?>
                    <p style="margin: 10px 0 5px 0; font-weight: 600; color: #58929c;">
                      <i class='bx bx-id-card'></i> Uploaded ID Document:
                    </p>
                    <?php 
                    // Build correct path to landlord's uploaded ID
                    $id_image_path = '../LANDLORD/' . $landlord['ID_image'];
                    ?>
                    <img src="<?= htmlspecialchars($id_image_path) ?>" 
                         alt="ID Document" 
                         class="id-image"
                         onclick="openModal('<?= htmlspecialchars($id_image_path) ?>')"
                         onerror="this.src='../img/no-image.jpg'; this.style.opacity='0.5';">
                    <br>
                    <small style="color: #666;">Click image to enlarge</small>
                  <?php else: ?>
                    <span class="no-id-badge">
                      <i class='bx bx-error'></i> No ID uploaded
                    </span>
                  <?php endif; ?>
                </div>
              </div>

              <!-- Actions Section -->
              <div class="actions" style="display: flex; flex-direction: column; gap: 10px;">
                <button class="btn approve" 
                        onclick="verifyLandlord(<?= $landlord['ID'] ?>, 'verified', '<?= htmlspecialchars($landlord['firstName']) ?>')"
                        style="padding: 10px 20px; background: #28a745; color: white; border: none; border-radius: 8px; cursor: pointer; font-weight: 500;">
                  <i class='bx bx-check'></i> Approve
                </button>
                <button class="btn reject" 
                        onclick="verifyLandlord(<?= $landlord['ID'] ?>, 'rejected', '<?= htmlspecialchars($landlord['firstName']) ?>')"
                        style="padding: 10px 20px; background: #dc3545; color: white; border: none; border-radius: 8px; cursor: pointer; font-weight: 500;">
                  <i class='bx bx-x'></i> Reject
                </button>
              </div>
            </div>
          </div>
        <?php endwhile; ?>
      <?php else: ?>
        <div style="text-align: center; padding: 60px; background: white; border-radius: 12px;">
          <i class='bx bx-check-circle' style='font-size: 72px; opacity: 0.3; color: #28a745;'></i>
          <h3 style="margin-top: 20px; color: #666;">All caught up!</h3>
          <p style="color: #999;">No pending verification requests at the moment.</p>
        </div>
      <?php endif; ?>
    </section>
  </main>

  <!-- Image Modal -->
  <div id="imageModal" class="modal" onclick="closeModal()">
    <span class="close-modal" onclick="closeModal()">&times;</span>
    <img class="modal-content" id="modalImage">
  </div>

  <script src="sidebar.js"></script>
  <script>
    function verifyLandlord(landlordId, action, name) {
      const message = action === 'verified' 
        ? `approve ${name}'s verification request` 
        : `reject ${name}'s verification request`;
      
      if(confirm(`Are you sure you want to ${message}?`)) {
        window.location.href = `verify_action.php?id=${landlordId}&action=${action}`;
      }
    }

    function openModal(imageSrc) {
      const modal = document.getElementById('imageModal');
      const modalImg = document.getElementById('modalImage');
      modal.style.display = 'block';
      modalImg.src = imageSrc;
    }

    function closeModal() {
      document.getElementById('imageModal').style.display = 'none';
    }

    // Close modal with Escape key
    document.addEventListener('keydown', function(event) {
      if (event.key === 'Escape') {
        closeModal();
      }
    });
  </script>

</body>

</html>