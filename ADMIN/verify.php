<?php
require_once '../session_auth.php';
require_once '../connection.php';

// Get admin info
$admin_name = $_SESSION['username'];

// Fetch pending landlord verification requests with all documents
$verify_query = "SELECT ID, firstName, lastName, email, username, phoneNum, 
                 valid_id, proof_of_ownership, landlord_insurance, 
                 gas_safety_cert, electric_safety_cert, lease_agreement,
                 submission_date, created_at
                 FROM landlordtbl 
                 WHERE verification_status = 'pending' 
                 ORDER BY submission_date DESC, created_at DESC";
$verify_result = $conn->query($verify_query);

// Fetch statistics
$stats_query = "SELECT 
                COUNT(CASE WHEN verification_status = 'pending' THEN 1 END) as pending_count,
                COUNT(CASE WHEN verification_status = 'verified' THEN 1 END) as verified_count,
                COUNT(CASE WHEN verification_status = 'rejected' THEN 1 END) as rejected_count
                FROM landlordtbl";
$stats_result = $conn->query($stats_query);
$stats = $stats_result->fetch_assoc();
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
    /* Statistics Cards */
    .stats-container {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
      gap: 20px;
      margin-bottom: 30px;
    }

    .stat-card {
      background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
      color: white;
      padding: 25px;
      border-radius: 15px;
      box-shadow: 0 4px 12px rgba(0,0,0,0.1);
      transition: transform 0.3s;
    }

    .stat-card:hover {
      transform: translateY(-5px);
    }

    .stat-card.pending {
      background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
    }

    .stat-card.verified {
      background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);
    }

    .stat-card.rejected {
      background: linear-gradient(135deg, #fa709a 0%, #fee140 100%);
    }

    .stat-card h3 {
      font-size: 36px;
      margin: 0 0 5px 0;
      font-weight: 700;
    }

    .stat-card p {
      margin: 0;
      opacity: 0.95;
      font-size: 14px;
    }

    /* Incomplete Document Warning */
    .incomplete-warning {
      background: #fff3cd;
      border: 1px solid #ffc107;
      color: #856404;
      padding: 10px 12px;
      border-radius: 8px;
      display: flex;
      align-items: center;
      gap: 8px;
      font-size: 13px;
    }

    .incomplete-warning i {
      font-size: 18px;
      flex-shrink: 0;
    }

    /* Verification Card - Two Column Layout */
    .verify-card {
      background: white;
      border-radius: 15px;
      padding: 30px;
      margin-bottom: 25px;
      box-shadow: 0 2px 12px rgba(0,0,0,0.08);
      transition: all 0.3s;
      border-left: 5px solid #58929c;
    }

    .verify-card:hover {
      box-shadow: 0 6px 20px rgba(0,0,0,0.12);
    }

    .card-layout {
      display: grid;
      grid-template-columns: 350px 1fr;
      gap: 30px;
    }

    @media (max-width: 1024px) {
      .card-layout {
        grid-template-columns: 1fr;
      }
    }

    /* Left Section - Landlord Info */
    .landlord-section {
      display: flex;
      flex-direction: column;
      gap: 15px;
    }

    .landlord-header-compact {
      display: flex;
      align-items: flex-start;
      gap: 15px;
      padding-bottom: 15px;
      border-bottom: 2px solid #f0f0f0;
    }

    .profile-pic {
      width: 80px;
      height: 80px;
      border-radius: 50%;
      object-fit: cover;
      border: 3px solid #58929c;
      flex-shrink: 0;
    }

    .landlord-info {
      flex: 1;
    }

    .landlord-info h3 {
      margin: 0 0 8px 0;
      color: #333;
      font-size: 20px;
    }

    .info-item {
      margin: 5px 0;
      color: #666;
      font-size: 13px;
      display: flex;
      align-items: center;
      gap: 8px;
    }

    .info-item i {
      color: #58929c;
      font-size: 14px;
    }

    /* Right Section - Documents & Actions */
    .documents-actions-section {
      display: flex;
      flex-direction: column;
      gap: 20px;
    }

    .documents-header-compact {
      display: flex;
      align-items: center;
      justify-content: space-between;
    }

    .doc-badge {
      display: flex;
      align-items: center;
      gap: 10px;
      font-weight: 600;
      color: #333;
      font-size: 15px;
    }

    .document-count {
      background: #58929c;
      color: white;
      padding: 4px 12px;
      border-radius: 20px;
      font-size: 12px;
      font-weight: 600;
    }

    /* Documents Grid - Compact */
    .documents-grid-compact {
      display: grid;
      grid-template-columns: repeat(3, 1fr);
      gap: 15px;
    }

    @media (max-width: 768px) {
      .documents-grid-compact {
        grid-template-columns: repeat(2, 1fr);
      }
    }

    .document-item-compact {
      background: white;
      border: 2px solid #e0e0e0;
      border-radius: 10px;
      padding: 10px;
      text-align: center;
      transition: all 0.3s;
      cursor: pointer;
      min-height: 180px;
      display: flex;
      flex-direction: column;
    }

    .document-item-compact:hover {
      border-color: #58929c;
      box-shadow: 0 4px 12px rgba(0,0,0,0.1);
      transform: translateY(-3px);
    }

    .document-item-compact.missing {
      border-color: #ffc107;
      background: #fff9e6;
      opacity: 0.7;
      cursor: default;
    }

    .document-item-compact.missing:hover {
      transform: none;
    }

    .document-item-compact img {
      width: 100%;
      height: 100px;
      object-fit: cover;
      border-radius: 6px;
      margin-bottom: 8px;
    }

    .doc-icon-small {
      font-size: 40px;
      color: #58929c;
      margin: 20px 0;
    }

    .doc-icon-small.missing-icon {
      color: #ffc107;
    }

    .doc-label-compact {
      font-size: 11px;
      font-weight: 600;
      color: #333;
      margin-top: auto;
      line-height: 1.3;
      padding: 5px 0;
    }

    .view-btn-compact {
      display: inline-block;
      padding: 5px 10px;
      background: #58929c;
      color: white;
      border-radius: 5px;
      text-decoration: none;
      font-size: 10px;
      margin-top: 8px;
      transition: all 0.3s;
    }

    .view-btn-compact:hover {
      background: #466f78;
      color: white;
    }

    /* Action Buttons */
    .actions-container {
      display: flex;
      gap: 12px;
      margin-top: 20px;
    }

    .btn {
      padding: 12px 28px;
      border: none;
      border-radius: 10px;
      cursor: pointer;
      font-weight: 600;
      font-size: 14px;
      transition: all 0.3s;
      display: inline-flex;
      align-items: center;
      gap: 8px;
    }

    .btn.approve {
      background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
      color: white;
    }

    .btn.approve:hover {
      background: linear-gradient(135deg, #218838 0%, #1aa179 100%);
      transform: translateY(-2px);
      box-shadow: 0 4px 12px rgba(40, 167, 69, 0.4);
    }

    .btn.reject {
      background: linear-gradient(135deg, #dc3545 0%, #c82333 100%);
      color: white;
    }

    .btn.reject:hover {
      background: linear-gradient(135deg, #c82333 0%, #bd2130 100%);
      transform: translateY(-2px);
      box-shadow: 0 4px 12px rgba(220, 53, 69, 0.4);
    }

    /* Rejection Form */
    .rejection-form {
      display: none;
      margin-top: 20px;
      padding: 20px;
      background: #fff3cd;
      border-radius: 10px;
      border-left: 4px solid #ffc107;
    }

    .rejection-form.active {
      display: block;
      animation: slideDown 0.3s ease;
    }

    @keyframes slideDown {
      from {
        opacity: 0;
        transform: translateY(-10px);
      }
      to {
        opacity: 1;
        transform: translateY(0);
      }
    }

    .rejection-form textarea {
      width: 100%;
      padding: 12px;
      border: 2px solid #ffc107;
      border-radius: 8px;
      margin: 10px 0;
      font-family: inherit;
      resize: vertical;
      min-height: 100px;
    }

    .rejection-form textarea:focus {
      outline: none;
      border-color: #e0a800;
    }

    /* Success/Error Messages */
    .message-box {
      padding: 18px 24px;
      border-radius: 12px;
      margin-bottom: 25px;
      display: flex;
      align-items: center;
      gap: 12px;
      font-weight: 500;
      animation: slideIn 0.4s ease;
    }

    @keyframes slideIn {
      from {
        opacity: 0;
        transform: translateX(-20px);
      }
      to {
        opacity: 1;
        transform: translateX(0);
      }
    }

    .message-box.success {
      background: #d4edda;
      color: #155724;
      border-left: 4px solid #28a745;
    }

    .message-box.error {
      background: #f8d7da;
      color: #721c24;
      border-left: 4px solid #dc3545;
    }

    .message-box i {
      font-size: 24px;
    }

    /* Empty State */
    .empty-state {
      text-align: center;
      padding: 80px 20px;
      background: white;
      border-radius: 15px;
    }

    .empty-state i {
      font-size: 80px;
      opacity: 0.2;
      color: #28a745;
    }

    .empty-state h3 {
      margin-top: 20px;
      color: #666;
    }

    .empty-state p {
      color: #999;
    }

    /* Modal */
    .modal {
      display: none;
      position: fixed;
      z-index: 1000;
      left: 0;
      top: 0;
      width: 100%;
      height: 100%;
      background-color: rgba(0,0,0,0.95);
      animation: fadeIn 0.3s ease;
    }

    @keyframes fadeIn {
      from { opacity: 0; }
      to { opacity: 1; }
    }

    .modal-content {
      margin: auto;
      display: block;
      max-width: 90%;
      max-height: 90%;
      margin-top: 50px;
      border-radius: 12px;
    }

    .close-modal {
      position: absolute;
      top: 20px;
      right: 35px;
      color: #f1f1f1;
      font-size: 50px;
      font-weight: bold;
      cursor: pointer;
      transition: all 0.3s;
    }

    .close-modal:hover {
      color: #ff6b6b;
      transform: rotate(90deg);
    }

    .modal-caption {
      text-align: center;
      color: white;
      padding: 20px;
      font-size: 16px;
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
            <strong><?= htmlspecialchars($admin_name) ?></strong>
          </small>
        </li>
      </div>
    </div>
  </nav>

  <!-- MAIN CONTENT -->
  <main class="content">
    <header class="page-header">
      <h1><i class='bx bx-shield-check'></i> Enhanced Landlord Verification</h1>
      <p>Review comprehensive documentation and approve or reject landlord applications</p>
    </header>

    <!-- Statistics Cards -->
    <div class="stats-container">
      <div class="stat-card pending">
        <h3><?= $stats['pending_count'] ?></h3>
        <p><i class='bx bx-time-five'></i> Pending Reviews</p>
      </div>
      <div class="stat-card verified">
        <h3><?= $stats['verified_count'] ?></h3>
        <p><i class='bx bx-check-circle'></i> Verified Landlords</p>
      </div>
      <div class="stat-card rejected">
        <h3><?= $stats['rejected_count'] ?></h3>
        <p><i class='bx bx-x-circle'></i> Rejected Applications</p>
      </div>
    </div>

    <!-- Success/Error Messages -->
    <?php if (isset($_GET['success'])): ?>
      <div class="message-box success">
        <i class='bx bx-check-circle'></i>
        <span>
          <?php if ($_GET['success'] == 'verified'): ?>
            Landlord verified successfully! They can now create property listings.
          <?php elseif ($_GET['success'] == 'rejected'): ?>
            Landlord application rejected. They will be notified to resubmit documents.
          <?php endif; ?>
        </span>
      </div>
    <?php endif; ?>

    <?php if (isset($_GET['error'])): ?>
      <div class="message-box error">
        <i class='bx bx-error-circle'></i>
        <span>Error processing verification request. Please try again.</span>
      </div>
    <?php endif; ?>

    <!-- Verification List -->
    <section class="verify-list">
      <?php if ($verify_result->num_rows > 0): ?>
        <?php while($landlord = $verify_result->fetch_assoc()): 
          // Check which documents are present
          $documents = [
            'valid_id' => ['name' => 'Valid Government ID', 'icon' => 'bx-id-card', 'path' => $landlord['valid_id']],
            'proof_of_ownership' => ['name' => 'Proof of Ownership', 'icon' => 'bx-home-alt', 'path' => $landlord['proof_of_ownership']],
            'landlord_insurance' => ['name' => 'Landlord Insurance', 'icon' => 'bx-shield-alt-2', 'path' => $landlord['landlord_insurance']],
            'gas_safety_cert' => ['name' => 'Gas Safety Certificate', 'icon' => 'bx-gas-pump', 'path' => $landlord['gas_safety_cert']],
            'electric_safety_cert' => ['name' => 'Electrical Safety Cert', 'icon' => 'bx-bolt', 'path' => $landlord['electric_safety_cert']],
            'lease_agreement' => ['name' => 'Lease Agreement', 'icon' => 'bx-file', 'path' => $landlord['lease_agreement']]
          ];
          
          $uploadedCount = 0;
          foreach ($documents as $doc) {
            if (!empty($doc['path'])) $uploadedCount++;
          }
          
          $isComplete = $uploadedCount === 6;
        ?>
          <div class="verify-card">
            <div class="card-layout">
              <!-- Left Section: Landlord Info -->
              <div class="landlord-section">
                <div class="landlord-header-compact">
                  <img src="<?= !empty($landlord['profile_pic']) ? htmlspecialchars($landlord['profile_pic']) : '../img/sams.png' ?>" 
                       alt="Landlord" class="profile-pic">
                  
                  <div class="landlord-info">
                    <h3><?= htmlspecialchars($landlord['firstName'] . ' ' . $landlord['lastName']) ?></h3>
                    <div class="info-item">
                      <i class='bx bx-envelope'></i>
                      <span><?= htmlspecialchars($landlord['email']) ?></span>
                    </div>
                    <div class="info-item">
                      <i class='bx bx-user'></i>
                      <span>@<?= htmlspecialchars($landlord['username']) ?></span>
                    </div>
                    <?php if (!empty($landlord['phoneNum'])): ?>
                      <div class="info-item">
                        <i class='bx bx-phone'></i>
                        <span><?= htmlspecialchars($landlord['phoneNum']) ?></span>
                      </div>
                    <?php endif; ?>
                    <div class="info-item">
                      <i class='bx bx-calendar'></i>
                      <span>Submitted: <?= $landlord['submission_date'] ? date('M d, Y \a\t g:i A', strtotime($landlord['submission_date'])) : 'N/A' ?></span>
                    </div>
                  </div>
                </div>

                <!-- Document Completeness Warning -->
                <?php if (!$isComplete): ?>
                  <div class="incomplete-warning">
                    <i class='bx bx-error'></i>
                    <span><strong>Warning:</strong> Only <?= $uploadedCount ?>/6 documents uploaded.</span>
                  </div>
                <?php endif; ?>
              </div>

              <!-- Right Section: Documents + Actions -->
              <div class="documents-actions-section">
                <!-- Documents Header -->
                <div class="documents-header-compact">
                  <div class="doc-badge">
                    <i class='bx bx-file-blank'></i> Submitted Documents
                    <span class="document-count"><?= $uploadedCount ?>/6</span>
                  </div>
                </div>

                <!-- Documents Grid -->
                <div class="documents-grid-compact">
                  <?php foreach ($documents as $key => $doc): 
                    $path = $doc['path'];
                    $fullPath = !empty($path) ? '../LANDLORD/' . $path : '';
                    $isImage = !empty($path) && preg_match('/\.(jpg|jpeg|png|gif)$/i', $path);
                    $isMissing = empty($path);
                  ?>
                    <div class="document-item-compact <?= $isMissing ? 'missing' : '' ?>" 
                         <?= !$isMissing ? "onclick=\"openModal('$fullPath', '{$doc['name']}')\"" : '' ?>>
                      <?php if ($isMissing): ?>
                        <div class="doc-icon-small missing-icon">
                          <i class='bx bx-x-circle'></i>
                        </div>
                      <?php elseif ($isImage): ?>
                        <img src="<?= htmlspecialchars($fullPath) ?>" alt="<?= $doc['name'] ?>"
                             onerror="this.src='../img/no-image.jpg';">
                      <?php else: ?>
                        <div class="doc-icon-small">
                          <i class='bx bx-file-blank'></i>
                        </div>
                      <?php endif; ?>
                      <div class="doc-label-compact"><?= $doc['name'] ?></div>
                      <?php if (!$isMissing): ?>
                        <a href="<?= htmlspecialchars($fullPath) ?>" target="_blank" class="view-btn-compact" onclick="event.stopPropagation()">
                          <i class='bx bx-show'></i> View Full
                        </a>
                      <?php endif; ?>
                    </div>
                  <?php endforeach; ?>
                </div>

                <!-- Action Buttons -->
                <div class="actions-container">
                  <button class="btn approve" 
                          onclick="verifyLandlord(<?= $landlord['ID'] ?>, 'verified', '<?= htmlspecialchars($landlord['firstName']) ?>')">
                    <i class='bx bx-check-circle'></i> Approve Verification
                  </button>
                  <button class="btn reject" 
                          onclick="toggleRejectForm(<?= $landlord['ID'] ?>)">
                    <i class='bx bx-x-circle'></i> Reject Application
                  </button>
                </div>
              </div>
            </div>

            <!-- Rejection Form (Full Width Below) -->
            <div class="rejection-form" id="reject-form-<?= $landlord['ID'] ?>">
              <h4 style="margin: 0 0 10px 0; color: #856404;">
                <i class='bx bx-error'></i> Provide Rejection Reason
              </h4>
              <p style="margin: 0 0 10px 0; font-size: 13px; color: #856404;">
                This message will be shown to the landlord. Be specific about what needs to be corrected.
              </p>
              <form method="POST" action="verify_action.php" onsubmit="return confirmRejection(<?= $landlord['ID'] ?>)">
                <input type="hidden" name="id" value="<?= $landlord['ID'] ?>">
                <input type="hidden" name="action" value="rejected">
                <textarea name="rejection_reason" id="rejection-reason-<?= $landlord['ID'] ?>" 
                          placeholder="Example: Gas Safety Certificate is expired. Please upload a current certificate dated within the last 12 months..."
                          required></textarea>
                <div style="display: flex; gap: 10px;">
                  <button type="submit" class="btn reject" style="margin: 0;">
                    <i class='bx bx-send'></i> Submit Rejection
                  </button>
                  <button type="button" class="btn" onclick="toggleRejectForm(<?= $landlord['ID'] ?>)"
                          style="background: #6c757d; color: white; margin: 0;">
                    <i class='bx bx-x'></i> Cancel
                  </button>
                </div>
              </form>
            </div>
          </div>
        <?php endwhile; ?>
      <?php else: ?>
        <div class="empty-state">
          <i class='bx bx-check-double'></i>
          <h3>All Caught Up!</h3>
          <p>No pending verification requests at the moment.</p>
          <p style="margin-top: 10px; font-size: 14px; color: #999;">
            You have reviewed all landlord applications.
          </p>
        </div>
      <?php endif; ?>
    </section>
  </main>

  <!-- Image Modal -->
  <div id="imageModal" class="modal" onclick="closeModal()">
    <span class="close-modal" onclick="closeModal()">&times;</span>
    <img class="modal-content" id="modalImage">
    <div class="modal-caption" id="modalCaption"></div>
  </div>

  <script src="sidebar.js"></script>
  <script>
    function verifyLandlord(landlordId, action, name) {
      const message = action === 'verified' 
        ? `approve ${name}'s verification and allow them to create property listings` 
        : `reject ${name}'s verification request`;
      
      if(confirm(`Are you sure you want to ${message}?`)) {
        window.location.href = `verify_action.php?id=${landlordId}&action=${action}`;
      }
    }

    function toggleRejectForm(landlordId) {
      const form = document.getElementById('reject-form-' + landlordId);
      form.classList.toggle('active');
      
      // Focus on textarea when opening
      if (form.classList.contains('active')) {
        setTimeout(() => {
          const textarea = document.getElementById('rejection-reason-' + landlordId);
          textarea.focus();
        }, 100);
      }
    }

    function confirmRejection(landlordId) {
      const textarea = document.getElementById('rejection-reason-' + landlordId);
      const reason = textarea.value.trim();
      
      if (reason.length < 20) {
        alert('Please provide a more detailed rejection reason (at least 20 characters).');
        textarea.focus();
        return false;
      }
      
      return confirm('Are you sure you want to reject this application? The landlord will need to resubmit their documents.');
    }

    function openModal(imageSrc, caption) {
      const modal = document.getElementById('imageModal');
      const modalImg = document.getElementById('modalImage');
      const modalCaption = document.getElementById('modalCaption');
      
      modal.style.display = 'block';
      modalImg.src = imageSrc;
      modalCaption.textContent = caption;
      
      // Prevent body scroll when modal is open
      document.body.style.overflow = 'hidden';
    }

    function closeModal() {
      const modal = document.getElementById('imageModal');
      modal.style.display = 'none';
      document.body.style.overflow = 'auto';
    }

    // Close modal with Escape key
    document.addEventListener('keydown', function(event) {
      if (event.key === 'Escape') {
        closeModal();
      }
    });

    // Prevent modal from closing when clicking on image
    document.getElementById('modalImage').addEventListener('click', function(event) {
      event.stopPropagation();
    });
  </script>

</body>

</html>