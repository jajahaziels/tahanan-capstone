<?php
require_once '../connection.php';
include '../session_auth.php';

$landlord_id = $_SESSION['landlord_id'] ?? null;
$message = "";
$messageType = "info";

if (!$landlord_id) {
    die("You must log in as a landlord to access this page.");
}

if (!empty($_SESSION['error'])) {
    $message = $_SESSION['error'];
    $messageType = "danger";
    unset($_SESSION['error']);
}

if (!empty($_SESSION['success'])) {
    $message = $_SESSION['success'];
    $messageType = "success";
    unset($_SESSION['success']);
}

// Handle document upload
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_documents'])) {
    $targetDir = __DIR__ . "/uploads/verification/";
    if (!is_dir($targetDir)) {
        mkdir($targetDir, 0777, true);
    }

    $requiredDocuments = [
        'valid_id' => 'Valid Government ID',
        'proof_of_ownership' => 'Proof of Ownership',
        'landlord_insurance' => 'Landlord Insurance',
        'gas_safety_cert' => 'Gas Safety Certificate',
        'electric_safety_cert' => 'Electrical Safety Certificate',
        'lease_agreement' => 'Lease Agreement Template'
    ];

    $uploadedFiles = [];
    $errors = [];
    $allFilesUploaded = true;

    // Validate and upload each document
    foreach ($requiredDocuments as $fieldName => $displayName) {
        if (isset($_FILES[$fieldName]) && $_FILES[$fieldName]['error'] === UPLOAD_ERR_OK) {
            $fileExtension = strtolower(pathinfo($_FILES[$fieldName]['name'], PATHINFO_EXTENSION));
            $allowedExtensions = ['jpg', 'jpeg', 'png', 'pdf', 'doc', 'docx'];
            
            // File size validation (10MB max)
            if ($_FILES[$fieldName]['size'] > 10485760) {
                $errors[] = "$displayName exceeds 10MB size limit.";
                $allFilesUploaded = false;
                continue;
            }

            // Extension validation
            if (!in_array($fileExtension, $allowedExtensions)) {
                $errors[] = "$displayName has invalid file type. Allowed: JPG, PNG, PDF, DOC, DOCX";
                $allFilesUploaded = false;
                continue;
            }

            $fileName = $landlord_id . "_" . $fieldName . "_" . time() . "." . $fileExtension;
            $targetFile = $targetDir . $fileName;

            if (move_uploaded_file($_FILES[$fieldName]["tmp_name"], $targetFile)) {
                $dbPath = "uploads/verification/" . $fileName;
                $uploadedFiles[$fieldName] = $dbPath;
            } else {
                $errors[] = "Failed to upload $displayName.";
                $allFilesUploaded = false;
            }
        } else {
            $errors[] = "$displayName is required.";
            $allFilesUploaded = false;
        }
    }

    // If all files uploaded successfully, update database
    if ($allFilesUploaded && empty($errors)) {
        $sql = "UPDATE landlordtbl SET 
                    valid_id = ?, 
                    proof_of_ownership = ?,
                    landlord_insurance = ?,
                    gas_safety_cert = ?,
                    electric_safety_cert = ?,
                    lease_agreement = ?,
                    verification_status = 'pending',
                    submission_date = NOW()
                WHERE ID = ?";
        
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ssssssi", 
            $uploadedFiles['valid_id'],
            $uploadedFiles['proof_of_ownership'],
            $uploadedFiles['landlord_insurance'],
            $uploadedFiles['gas_safety_cert'],
            $uploadedFiles['electric_safety_cert'],
            $uploadedFiles['lease_agreement'],
            $landlord_id
        );
        
        if ($stmt->execute()) {
            $_SESSION['success'] = "✅ All documents submitted successfully! Your verification is now pending admin review.";
            header("Location: " . $_SERVER['PHP_SELF']);
            exit();
        } else {
            $message = "❌ Database error occurred. Please try again.";
            $messageType = "danger";
        }
    } else {
        $message = "❌ Please fix the following errors:<br>" . implode("<br>", $errors);
        $messageType = "danger";
    }
}

// Fetch current verification status and documents
$sql = "SELECT verification_status, valid_id, proof_of_ownership, landlord_insurance, 
               gas_safety_cert, electric_safety_cert, lease_agreement, admin_rejection_reason, submission_date 
        FROM landlordtbl WHERE ID = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $landlord_id);
$stmt->execute();
$result = $stmt->get_result()->fetch_assoc();

$status = $result['verification_status'] ?? 'not_submitted';
$documents = [
    'valid_id' => $result['valid_id'] ?? null,
    'proof_of_ownership' => $result['proof_of_ownership'] ?? null,
    'landlord_insurance' => $result['landlord_insurance'] ?? null,
    'gas_safety_cert' => $result['gas_safety_cert'] ?? null,
    'electric_safety_cert' => $result['electric_safety_cert'] ?? null,
    'lease_agreement' => $result['lease_agreement'] ?? null
];
$rejectionReason = $result['admin_rejection_reason'] ?? null;
$submissionDate = $result['submission_date'] ?? null;

// Check if all documents are present
$allDocsPresent = !in_array(null, $documents);
if ($status === 'pending' && !$allDocsPresent) {
    $status = 'not_submitted';
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <!-- FAVICON -->
    <link rel="shortcut icon" href="favicon.ico" type="image/x-icon">
    <!-- FA -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/7.0.1/css/all.min.css"
        integrity="sha512-2SwdPD6INVrV/lHTZbO2nodKhrnDdJK9/kg2XD1r9uGqPo1cUbujc+IYdlYdEErWNu69gVcYgdxlmVmzTWnetw=="
        crossorigin="anonymous" referrerpolicy="no-referrer" />
    <!-- BS -->
    <link rel="stylesheet" href="../css/bootstrap.min.css">
    <!-- MAIN CSS -->
    <link rel="stylesheet" href="../css/style.css">
    <title>Landlord Verification - Enhanced</title>
    <style>
        .landlord-page {
            margin: 140px 0px 80px 0px !important;
        }

        .verification-form {
            background-color: var(--bg-color);
            padding: 30px;
            border-radius: 20px;
            box-shadow: 2px 2px 10px rgba(0, 0, 0, 0.165);
        }

        .document-card {
            background: white;
            border: 2px solid #e0e0e0;
            border-radius: 12px;
            padding: 20px;
            margin-bottom: 20px;
            transition: all 0.3s ease;
        }

        .document-card:hover {
            border-color: var(--main-color);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
        }

        .document-card h5 {
            color: var(--main-color);
            margin-bottom: 10px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .document-card .icon {
            font-size: 24px;
        }

        .document-card p {
            font-size: 14px;
            color: #666;
            margin-bottom: 15px;
        }

        .file-input-wrapper {
            position: relative;
            overflow: hidden;
            display: inline-block;
            width: 100%;
        }

        .file-input-wrapper input[type=file] {
            position: absolute;
            left: -9999px;
        }

        .file-input-label {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            padding: 12px 20px;
            background: var(--main-color);
            color: white;
            border-radius: 8px;
            cursor: pointer;
            transition: all 0.3s ease;
            font-weight: 500;
        }

        .file-input-label:hover {
            background: var(--main-color-dark, #0056b3);
            transform: translateY(-2px);
        }

        .file-name {
            margin-top: 8px;
            font-size: 13px;
            color: #28a745;
            font-weight: 500;
        }

        .status-badge {
            display: inline-block;
            padding: 12px 24px;
            border-radius: 25px;
            font-weight: 600;
            margin: 15px 0;
            font-size: 16px;
        }

        .status-pending {
            background: linear-gradient(135deg, #fff3cd 0%, #ffeaa7 100%);
            color: #856404;
        }

        .status-verified {
            background: linear-gradient(135deg, #d4edda 0%, #a8e6a3 100%);
            color: #155724;
        }

        .status-rejected {
            background: linear-gradient(135deg, #f8d7da 0%, #f5b7b1 100%);
            color: #721c24;
        }

        .status-not_submitted {
            background: linear-gradient(135deg, #d1ecf1 0%, #a8d8ea 100%);
            color: #0c5460;
        }

        .uploaded-docs-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
            gap: 20px;
            margin-top: 20px;
        }

        .uploaded-doc-item {
            border: 2px solid var(--main-color);
            border-radius: 10px;
            padding: 15px;
            background: white;
            text-align: center;
        }

        .uploaded-doc-item img {
            width: 100%;
            max-height: 200px;
            object-fit: cover;
            border-radius: 8px;
            margin-bottom: 10px;
        }

        .uploaded-doc-item .doc-label {
            font-weight: 600;
            color: var(--main-color);
            margin-bottom: 8px;
        }

        .uploaded-doc-item .view-btn {
            display: inline-block;
            padding: 8px 16px;
            background: var(--main-color);
            color: white;
            border-radius: 6px;
            text-decoration: none;
            font-size: 14px;
            transition: all 0.3s;
        }

        .uploaded-doc-item .view-btn:hover {
            background: var(--main-color-dark, #0056b3);
            color: white;
        }

        .requirements-box {
            background: #f8f9fa;
            border-left: 4px solid var(--main-color);
            padding: 20px;
            margin: 20px 0;
            border-radius: 8px;
        }

        .requirements-box h4 {
            color: var(--main-color);
            margin-bottom: 15px;
        }

        .requirements-box ul {
            margin-bottom: 0;
        }

        .requirements-box li {
            margin-bottom: 8px;
            font-size: 14px;
        }

        .rejection-reason {
            background: #fff3cd;
            border-left: 4px solid #ffc107;
            padding: 15px;
            margin: 15px 0;
            border-radius: 8px;
        }

        .progress-indicator {
            display: flex;
            justify-content: space-between;
            margin: 30px 0;
            position: relative;
        }

        .progress-indicator::before {
            content: '';
            position: absolute;
            top: 20px;
            left: 0;
            right: 0;
            height: 2px;
            background: #e0e0e0;
            z-index: 0;
        }

        .progress-step {
            text-align: center;
            position: relative;
            z-index: 1;
            flex: 1;
        }

        .progress-step-circle {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: #e0e0e0;
            margin: 0 auto 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 600;
        }

        .progress-step.active .progress-step-circle {
            background: var(--main-color);
            color: white;
        }

        .progress-step.completed .progress-step-circle {
            background: #28a745;
            color: white;
        }
    </style>
</head>

<body>
    <!-- HEADER -->
    <?php include '../Components/landlord-header.php'; ?>

    <!-- VERIFICATION PAGE -->
    <div class="landlord-page">
        <div class="container m-auto">
            <h1><i class="fas fa-shield-check"></i> Enhanced Landlord Verification</h1>
            <p>To maintain the highest standards of safety and trust on our platform, all landlords must complete a comprehensive verification process. This multi-document verification ensures that only legitimate, qualified property owners can list rentals, protecting both tenants and the community.</p>

            <!-- Progress Indicator -->
            <div class="progress-indicator">
                <div class="progress-step <?= $status === 'not_submitted' ? 'active' : 'completed' ?>">
                    <div class="progress-step-circle">1</div>
                    <small>Submit Documents</small>
                </div>
                <div class="progress-step <?= $status === 'pending' ? 'active' : ($status === 'verified' || $status === 'rejected' ? 'completed' : '') ?>">
                    <div class="progress-step-circle">2</div>
                    <small>Admin Review</small>
                </div>
                <div class="progress-step <?= $status === 'verified' ? 'active' : '' ?>">
                    <div class="progress-step-circle">3</div>
                    <small>Verified</small>
                </div>
            </div>

            <div class="row justify-content-center mt-4">
                <div class="col-lg-10">
                    <div class="verification-form">
                        <!-- Status Badge -->
                        <div class="text-center">
                            <div class="status-badge status-<?= htmlspecialchars($status) ?>">
                                <?php if ($status === 'pending'): ?>
                                    <i class="fas fa-clock"></i> Verification Status: Pending Admin Review
                                <?php elseif ($status === 'verified'): ?>
                                    <i class="fas fa-check-circle"></i> Verification Status: Verified
                                <?php elseif ($status === 'rejected'): ?>
                                    <i class="fas fa-times-circle"></i> Verification Status: Rejected
                                <?php else: ?>
                                    <i class="fas fa-upload"></i> Verification Status: Documents Required
                                <?php endif; ?>
                            </div>
                            <?php if ($submissionDate && $status === 'pending'): ?>
                                <p class="text-muted"><small>Submitted on: <?= date('F j, Y \a\t g:i A', strtotime($submissionDate)) ?></small></p>
                            <?php endif; ?>
                        </div>

                        <!-- Messages -->
                        <?php if (!empty($message)): ?>
                            <div class="alert alert-<?= $messageType ?> mt-3" role="alert">
                                <?= $message ?>
                            </div>
                        <?php endif; ?>

                        <?php if ($status === 'pending'): ?>
                            <!-- Pending Status -->
                            <div class="alert alert-warning mt-3" role="alert">
                                <h5><i class="fas fa-hourglass-half"></i> Your verification is being reviewed</h5>
                                <p>Our admin team is carefully reviewing your submitted documents. This process typically takes 24-48 hours. You will be notified via email once the review is complete.</p>
                            </div>

                            <h3 class="mt-4">Submitted Documents</h3>
                            <div class="uploaded-docs-grid">
                                <?php 
                                $docLabels = [
                                    'valid_id' => 'Valid Government ID',
                                    'proof_of_ownership' => 'Proof of Ownership',
                                    'landlord_insurance' => 'Landlord Insurance',
                                    'gas_safety_cert' => 'Gas Safety Certificate',
                                    'electric_safety_cert' => 'Electrical Safety Certificate',
                                    'lease_agreement' => 'Lease Agreement'
                                ];
                                foreach ($documents as $key => $path): 
                                    if ($path):
                                        $isImage = preg_match('/\.(jpg|jpeg|png|gif)$/i', $path);
                                ?>
                                    <div class="uploaded-doc-item">
                                        <?php if ($isImage): ?>
                                            <img src="<?= htmlspecialchars($path) ?>" alt="<?= $docLabels[$key] ?>">
                                        <?php else: ?>
                                            <div style="padding: 40px; background: #f0f0f0; border-radius: 8px;">
                                                <i class="fas fa-file-pdf" style="font-size: 48px; color: #dc3545;"></i>
                                            </div>
                                        <?php endif; ?>
                                        <div class="doc-label"><?= $docLabels[$key] ?></div>
                                        <a href="<?= htmlspecialchars($path) ?>" target="_blank" class="view-btn">
                                            <i class="fas fa-eye"></i> View Document
                                        </a>
                                    </div>
                                <?php 
                                    endif;
                                endforeach; 
                                ?>
                            </div>

                        <?php elseif ($status === 'verified'): ?>
                            <!-- Verified Status -->
                            <div class="alert alert-success mt-3" role="alert">
                                <h5><i class="fas fa-check-circle"></i> Congratulations! You are verified!</h5>
                                <p>Your verification has been approved. You can now create and manage property listings on our platform.</p>
                                <a href="property_listings.php" class="btn btn-success mt-2">
                                    <i class="fas fa-plus-circle"></i> Create Your First Property Listing
                                </a>
                            </div>

                            <h3 class="mt-4">Verified Documents</h3>
                            <div class="uploaded-docs-grid">
                                <?php 
                                $docLabels = [
                                    'valid_id' => 'Valid Government ID',
                                    'proof_of_ownership' => 'Proof of Ownership',
                                    'landlord_insurance' => 'Landlord Insurance',
                                    'gas_safety_cert' => 'Gas Safety Certificate',
                                    'electric_safety_cert' => 'Electrical Safety Certificate',
                                    'lease_agreement' => 'Lease Agreement'
                                ];
                                foreach ($documents as $key => $path): 
                                    if ($path):
                                        $isImage = preg_match('/\.(jpg|jpeg|png|gif)$/i', $path);
                                ?>
                                    <div class="uploaded-doc-item">
                                        <?php if ($isImage): ?>
                                            <img src="<?= htmlspecialchars($path) ?>" alt="<?= $docLabels[$key] ?>">
                                        <?php else: ?>
                                            <div style="padding: 40px; background: #f0f0f0; border-radius: 8px;">
                                                <i class="fas fa-file-pdf" style="font-size: 48px; color: #dc3545;"></i>
                                            </div>
                                        <?php endif; ?>
                                        <div class="doc-label"><?= $docLabels[$key] ?></div>
                                        <a href="<?= htmlspecialchars($path) ?>" target="_blank" class="view-btn">
                                            <i class="fas fa-eye"></i> View Document
                                        </a>
                                    </div>
                                <?php 
                                    endif;
                                endforeach; 
                                ?>
                            </div>

                        <?php elseif ($status === 'rejected'): ?>
                            <!-- Rejected Status -->
                            <div class="alert alert-danger mt-3" role="alert">
                                <h5><i class="fas fa-exclamation-triangle"></i> Verification Rejected</h5>
                                <p>Unfortunately, your verification submission was not approved. Please review the reason below and resubmit the required documents.</p>
                            </div>

                            <?php if ($rejectionReason): ?>
                                <div class="rejection-reason">
                                    <h5><i class="fas fa-info-circle"></i> Rejection Reason:</h5>
                                    <p><?= nl2br(htmlspecialchars($rejectionReason)) ?></p>
                                </div>
                            <?php endif; ?>

                            <?php include 'verification-form-section.php'; ?>

                        <?php else: ?>
                            <!-- Not Submitted Status -->
                            <div class="alert alert-info mt-3" role="alert">
                                <h5><i class="fas fa-info-circle"></i> Complete Your Verification</h5>
                                <p>To start listing properties, please submit all required documents below. Our team will review your submission within 24-48 hours.</p>
                            </div>

                            <?php include 'verification_form_section.php'; ?>

                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <?php include '../Components/footer.php'; ?>

    <!-- MAIN JS -->
    <script src="../js/script.js" defer></script>
    <!-- BS JS -->
    <script src="../js/bootstrap.bundle.min.js"></script>
    <!-- SCROLL REVEAL -->
    <script src="https://unpkg.com/scrollreveal"></script>
    
    <script>
        // File input preview
        document.querySelectorAll('input[type="file"]').forEach(input => {
            input.addEventListener('change', function(e) {
                const fileName = e.target.files[0]?.name || 'No file chosen';
                const fileNameDisplay = this.closest('.file-input-wrapper').querySelector('.file-name');
                if (fileNameDisplay) {
                    fileNameDisplay.textContent = '📄 ' + fileName;
                }
            });
        });

        // Form validation
        document.querySelector('form')?.addEventListener('submit', function(e) {
            const fileInputs = this.querySelectorAll('input[type="file"]');
            let allFilled = true;
            
            fileInputs.forEach(input => {
                if (!input.files || input.files.length === 0) {
                    allFilled = false;
                }
            });

            if (!allFilled) {
                e.preventDefault();
                alert('Please upload all required documents before submitting.');
            }
        });
    </script>
</body>

</html>