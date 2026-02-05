<?php
require_once '../connection.php';
include '../admin_session_auth.php'; // Assuming you have admin authentication

$message = "";
$messageType = "info";

// Handle verification decision
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $landlord_id = $_POST['landlord_id'] ?? null;
    $action = $_POST['action'];
    $rejection_reason = $_POST['rejection_reason'] ?? null;

    if ($landlord_id) {
        if ($action === 'approve') {
            $sql = "UPDATE landlordtbl SET verification_status = 'verified', admin_rejection_reason = NULL WHERE ID = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("i", $landlord_id);
            
            if ($stmt->execute()) {
                $message = "✅ Landlord verification approved successfully!";
                $messageType = "success";
                
                // Optional: Send email notification to landlord
                // sendVerificationEmail($landlord_id, 'approved');
            } else {
                $message = "❌ Error approving verification.";
                $messageType = "danger";
            }
        } elseif ($action === 'reject') {
            if (empty($rejection_reason)) {
                $message = "❌ Please provide a reason for rejection.";
                $messageType = "danger";
            } else {
                $sql = "UPDATE landlordtbl SET verification_status = 'rejected', admin_rejection_reason = ? WHERE ID = ?";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("si", $rejection_reason, $landlord_id);
                
                if ($stmt->execute()) {
                    $message = "❌ Landlord verification rejected.";
                    $messageType = "warning";
                    
                    // Optional: Send email notification to landlord
                    // sendVerificationEmail($landlord_id, 'rejected', $rejection_reason);
                } else {
                    $message = "❌ Error rejecting verification.";
                    $messageType = "danger";
                }
            }
        }
    }
}

// Fetch pending verifications
$sql = "SELECT ID, name, email, phone, submission_date, 
               valid_id, proof_of_ownership, landlord_insurance, 
               gas_safety_cert, electric_safety_cert, lease_agreement
        FROM landlordtbl 
        WHERE verification_status = 'pending'
        ORDER BY submission_date ASC";
$result = $conn->query($sql);
$pendingVerifications = $result->fetch_all(MYSQLI_ASSOC);

// Fetch verified landlords count
$verifiedCount = $conn->query("SELECT COUNT(*) as count FROM landlordtbl WHERE verification_status = 'verified'")->fetch_assoc()['count'];

// Fetch rejected landlords count
$rejectedCount = $conn->query("SELECT COUNT(*) as count FROM landlordtbl WHERE verification_status = 'rejected'")->fetch_assoc()['count'];
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="shortcut icon" href="favicon.ico" type="image/x-icon">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/7.0.1/css/all.min.css"
        integrity="sha512-2SwdPD6INVrV/lHTZbO2nodKhrnDdJK9/kg2XD1r9uGqPo1cUbujc+IYdlYdEErWNu69gVcYgdxlmVmzTWnetw=="
        crossorigin="anonymous" referrerpolicy="no-referrer" />
    <link rel="stylesheet" href="../css/bootstrap.min.css">
    <link rel="stylesheet" href="../css/style.css">
    <title>Admin - Landlord Verification Review</title>
    <style>
        .admin-page {
            margin: 140px 0px 80px 0px !important;
        }

        .stats-card {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 20px;
            border-radius: 15px;
            margin-bottom: 20px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
        }

        .stats-card h3 {
            font-size: 32px;
            font-weight: 700;
            margin-bottom: 5px;
        }

        .stats-card p {
            margin: 0;
            opacity: 0.9;
        }

        .verification-card {
            background: white;
            border-radius: 15px;
            padding: 25px;
            margin-bottom: 30px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
        }

        .landlord-info {
            border-bottom: 2px solid #f0f0f0;
            padding-bottom: 15px;
            margin-bottom: 20px;
        }

        .landlord-info h4 {
            color: var(--main-color);
            margin-bottom: 10px;
        }

        .documents-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
            gap: 15px;
            margin: 20px 0;
        }

        .doc-preview {
            border: 2px solid #e0e0e0;
            border-radius: 10px;
            padding: 10px;
            text-align: center;
            background: #f9f9f9;
        }

        .doc-preview img {
            width: 100%;
            max-height: 150px;
            object-fit: cover;
            border-radius: 8px;
            margin-bottom: 8px;
        }

        .doc-preview .doc-icon {
            font-size: 48px;
            color: #dc3545;
            margin: 20px 0;
        }

        .doc-preview .doc-name {
            font-size: 12px;
            font-weight: 600;
            color: #333;
            margin-bottom: 8px;
        }

        .doc-preview .view-full {
            display: inline-block;
            padding: 5px 12px;
            background: var(--main-color);
            color: white;
            border-radius: 5px;
            text-decoration: none;
            font-size: 12px;
            transition: all 0.3s;
        }

        .doc-preview .view-full:hover {
            background: #0056b3;
            color: white;
        }

        .action-buttons {
            display: flex;
            gap: 15px;
            margin-top: 20px;
        }

        .btn-approve {
            background: #28a745;
            color: white;
            border: none;
            padding: 12px 30px;
            border-radius: 8px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
        }

        .btn-approve:hover {
            background: #218838;
            transform: translateY(-2px);
        }

        .btn-reject {
            background: #dc3545;
            color: white;
            border: none;
            padding: 12px 30px;
            border-radius: 8px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
        }

        .btn-reject:hover {
            background: #c82333;
            transform: translateY(-2px);
        }

        .rejection-form {
            display: none;
            margin-top: 15px;
            padding: 15px;
            background: #fff3cd;
            border-radius: 8px;
        }

        .rejection-form textarea {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 5px;
            margin-bottom: 10px;
        }

        .empty-state {
            text-align: center;
            padding: 60px 20px;
            color: #999;
        }

        .empty-state i {
            font-size: 64px;
            margin-bottom: 20px;
        }
    </style>
</head>

<body>
    <!-- HEADER -->
    <?php include '../Components/admin-header.php'; ?>

    <!-- ADMIN PAGE -->
    <div class="admin-page">
        <div class="container m-auto">
            <h1><i class="fas fa-user-check"></i> Landlord Verification Review</h1>
            
            <?php if (!empty($message)): ?>
                <div class="alert alert-<?= $messageType ?> mt-3" role="alert">
                    <?= $message ?>
                </div>
            <?php endif; ?>

            <!-- Statistics -->
            <div class="row mt-4">
                <div class="col-md-4">
                    <div class="stats-card" style="background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);">
                        <h3><?= count($pendingVerifications) ?></h3>
                        <p><i class="fas fa-clock"></i> Pending Reviews</p>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="stats-card" style="background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);">
                        <h3><?= $verifiedCount ?></h3>
                        <p><i class="fas fa-check-circle"></i> Verified Landlords</p>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="stats-card" style="background: linear-gradient(135deg, #fa709a 0%, #fee140 100%);">
                        <h3><?= $rejectedCount ?></h3>
                        <p><i class="fas fa-times-circle"></i> Rejected Applications</p>
                    </div>
                </div>
            </div>

            <!-- Pending Verifications -->
            <h2 class="mt-5"><i class="fas fa-list"></i> Pending Verifications</h2>

            <?php if (empty($pendingVerifications)): ?>
                <div class="empty-state">
                    <i class="fas fa-inbox"></i>
                    <h3>No Pending Verifications</h3>
                    <p>All verification requests have been reviewed.</p>
                </div>
            <?php else: ?>
                <?php foreach ($pendingVerifications as $landlord): ?>
                    <div class="verification-card">
                        <div class="landlord-info">
                            <h4><i class="fas fa-user"></i> <?= htmlspecialchars($landlord['name']) ?></h4>
                            <p><strong>Email:</strong> <?= htmlspecialchars($landlord['email']) ?></p>
                            <p><strong>Phone:</strong> <?= htmlspecialchars($landlord['phone']) ?></p>
                            <p><strong>Submitted:</strong> <?= date('F j, Y \a\t g:i A', strtotime($landlord['submission_date'])) ?></p>
                        </div>

                        <h5><i class="fas fa-folder-open"></i> Submitted Documents</h5>
                        <div class="documents-grid">
                            <?php
                            $documents = [
                                'valid_id' => ['name' => 'Valid Government ID', 'icon' => 'fa-id-card'],
                                'proof_of_ownership' => ['name' => 'Proof of Ownership', 'icon' => 'fa-home'],
                                'landlord_insurance' => ['name' => 'Landlord Insurance', 'icon' => 'fa-shield-alt'],
                                'gas_safety_cert' => ['name' => 'Gas Safety Cert', 'icon' => 'fa-fire'],
                                'electric_safety_cert' => ['name' => 'Electrical Safety Cert', 'icon' => 'fa-bolt'],
                                'lease_agreement' => ['name' => 'Lease Agreement', 'icon' => 'fa-file-contract']
                            ];

                            foreach ($documents as $key => $doc):
                                $path = $landlord[$key];
                                if ($path):
                                    $isImage = preg_match('/\.(jpg|jpeg|png|gif)$/i', $path);
                            ?>
                                <div class="doc-preview">
                                    <?php if ($isImage): ?>
                                        <img src="../LANDLORD/<?= htmlspecialchars($path) ?>" alt="<?= $doc['name'] ?>">
                                    <?php else: ?>
                                        <div class="doc-icon">
                                            <i class="fas fa-file-pdf"></i>
                                        </div>
                                    <?php endif; ?>
                                    <div class="doc-name"><?= $doc['name'] ?></div>
                                    <a href="../LANDLORD/<?= htmlspecialchars($path) ?>" target="_blank" class="view-full">
                                        <i class="fas fa-eye"></i> View Full
                                    </a>
                                </div>
                            <?php
                                endif;
                            endforeach;
                            ?>
                        </div>

                        <div class="action-buttons">
                            <form method="POST" style="display: inline;">
                                <input type="hidden" name="landlord_id" value="<?= $landlord['ID'] ?>">
                                <input type="hidden" name="action" value="approve">
                                <button type="submit" class="btn-approve" onclick="return confirm('Are you sure you want to approve this landlord?')">
                                    <i class="fas fa-check"></i> Approve Verification
                                </button>
                            </form>

                            <button class="btn-reject" onclick="toggleRejectForm(<?= $landlord['ID'] ?>)">
                                <i class="fas fa-times"></i> Reject Verification
                            </button>
                        </div>

                        <div class="rejection-form" id="reject-form-<?= $landlord['ID'] ?>">
                            <form method="POST">
                                <input type="hidden" name="landlord_id" value="<?= $landlord['ID'] ?>">
                                <input type="hidden" name="action" value="reject">
                                <label><strong>Reason for Rejection:</strong></label>
                                <textarea name="rejection_reason" rows="4" placeholder="Explain why the verification is rejected..." required></textarea>
                                <button type="submit" class="btn btn-danger">
                                    <i class="fas fa-paper-plane"></i> Submit Rejection
                                </button>
                                <button type="button" class="btn btn-secondary" onclick="toggleRejectForm(<?= $landlord['ID'] ?>)">
                                    Cancel
                                </button>
                            </form>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>

    <?php include '../Components/footer.php'; ?>

    <script src="../js/script.js" defer></script>
    <script src="../js/bootstrap.bundle.min.js"></script>

    <script>
        function toggleRejectForm(landlordId) {
            const form = document.getElementById('reject-form-' + landlordId);
            form.style.display = form.style.display === 'none' || form.style.display === '' ? 'block' : 'none';
        }
    </script>
</body>

</html>