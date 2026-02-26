<?php
require_once '../connection.php';
include '../session_auth.php';

if (!isset($_SESSION['tenant_id'])) {
    die("Unauthorized access.");
}

$tenant_id = (int) $_SESSION['tenant_id'];

// Fetch active lease with listing info and landlord_id
$leaseSql = "
    SELECT 
        l.ID as lease_id, 
        l.listing_id, 
        l.landlord_id,
        ls.listingName, 
        ls.address
    FROM leasetbl l
    JOIN listingtbl ls ON l.listing_id = ls.ID
    WHERE l.tenant_id = ? AND l.status = 'active'
    ORDER BY l.end_date DESC
    LIMIT 1
";
$leaseStmt = $conn->prepare($leaseSql);
$leaseStmt->bind_param("i", $tenant_id);
$leaseStmt->execute();
$lease = $leaseStmt->get_result()->fetch_assoc();

if (!$lease) {
    die("You don't have an active lease to submit a maintenance request.");
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = trim($_POST['title'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $category = $_POST['category'] ?? '';
    $priority = $_POST['priority'] ?? '';
    
    if (empty($title) || empty($description) || empty($category) || empty($priority)) {
        $error = "Please fill in all required fields.";
    } else {
        // Handle image upload
        $imagePath = null;
        if (isset($_FILES['photo']) && $_FILES['photo']['error'] === UPLOAD_ERR_OK) {
            $uploadDir = '../uploads/maintenance/';
            
            // Create directory if it doesn't exist
            if (!file_exists($uploadDir)) {
                mkdir($uploadDir, 0777, true);
            }
            
            $fileExtension = strtolower(pathinfo($_FILES['photo']['name'], PATHINFO_EXTENSION));
            $allowedExtensions = ['jpg', 'jpeg', 'png', 'gif'];
            
            if (in_array($fileExtension, $allowedExtensions)) {
                // Generate unique filename
                $newFileName = 'maintenance_' . time() . '_' . uniqid() . '.' . $fileExtension;
                $targetPath = $uploadDir . $newFileName;
                
                if (move_uploaded_file($_FILES['photo']['tmp_name'], $targetPath)) {
                    $imagePath = $targetPath;
                }
            }
        }
        
        // Insert matching your exact table structure
        $insertSql = "
            INSERT INTO maintenance_requeststbl 
            (lease_id, tenant_id, landlord_id, title, description, category, priority, status, photo_path, requested_date, created_at)
            VALUES (?, ?, ?, ?, ?, ?, ?, 'Pending', ?, CURDATE(), NOW())
        ";
        $insertStmt = $conn->prepare($insertSql);
        $insertStmt->bind_param(
            "iiisssss", 
            $lease['lease_id'],
            $tenant_id,
            $lease['landlord_id'],
            $title,
            $description,
            $category,
            $priority,
            $imagePath
        );
        
        if ($insertStmt->execute()) {
            header("Location: tenant-rental.php?success=1");
            exit;
        } else {
            $error = "Failed to submit request. Please try again.";
        }
    }

function getPriorityBadge($priority) {
    switch ($priority) {
        case 'Low':
            return '<span class="badge bg-success">Low</span>';
        case 'Medium':
            return '<span class="badge bg-warning text-dark">Medium</span>';
        case 'High':
            return '<span class="badge bg-danger">High</span>';
        case 'Urgent':
            return '<span class="badge bg-dark">Urgent</span>';
        default:
            return '<span class="badge bg-secondary">'.$priority.'</span>';
    }
}
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Submit Maintenance Request</title>
    <link rel="stylesheet" href="../css/bootstrap.min.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;500;600;700&display=swap" rel="stylesheet">
    
    <style>
        * {
            font-family: "Montserrat", sans-serif;
        }
        
        body {
            background: #f5f6f8;
        }
        
        .container {
            margin-top: 150px;
            margin-bottom: 50px;
        }
        
        .form-card {
            background: #ffffff;
            border-radius: 14px;
            padding: 30px;
            box-shadow: 0 6px 18px rgba(0, 0, 0, .08);
            max-width: 700px;
            margin: 0 auto;
        }
        
        .form-card h2 {
            font-weight: 600;
            margin-bottom: 10px;
            color: #25343F;
        }
        
        .property-info {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 25px;
        }
        
        .form-label {
            font-weight: 600;
            color: #25343F;
            margin-bottom: 8px;
        }
        
        .form-control, .form-select {
            border-radius: 8px;
            border: 1px solid #ddd;
            padding: 10px 15px;
        }
        
        .form-control:focus, .form-select:focus {
            border-color: #ac1152;
            box-shadow: 0 0 0 0.2rem rgba(172, 17, 82, 0.15);
        }
        
        textarea.form-control {
            min-height: 150px;
        }
        
        .btn-submit {
            background: #ac1152;
            color: white;
            border: none;
            padding: 12px 40px;
            border-radius: 25px;
            font-weight: 600;
            transition: all 0.3s;
        }
        
        .btn-submit:hover {
            background: #8d0b41;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(172, 17, 82, 0.3);
        }
        
        .btn-back {
            background: transparent;
            color: #ac1152;
            border: 2px solid #ac1152;
            padding: 10px 30px;
            border-radius: 25px;
            font-weight: 600;
            transition: all 0.3s;
        }
        
        .btn-back:hover {
            background: #ac1152;
            color: white;
        }
        
        .alert {
            border-radius: 10px;
        }
    </style>
</head>
<body>
    <?php include '../Components/tenant-header.php'; ?>
    
    <div class="container">
        <div class="form-card">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h2><i class="bi bi-tools"></i> Submit Maintenance Request</h2>
                <button class="btn-back" onclick="location.href='tenant-rental.php'">
                    <i class="bi bi-arrow-left"></i> Back
                </button>
            </div>
            
            <?php if (isset($error)): ?>
                <div class="alert alert-danger">
                    <i class="bi bi-exclamation-triangle"></i> <?= htmlspecialchars($error); ?>
                </div>
            <?php endif; ?>
            
            <div class="property-info">
                <h5><i class="bi bi-house-door"></i> <?= htmlspecialchars($lease['listingName']); ?></h5>
                <p class="mb-0 text-muted"><i class="bi bi-geo-alt"></i> <?= htmlspecialchars($lease['address']); ?></p>
            </div>
            
            <form method="POST" action="" enctype="multipart/form-data">
                <div class="mb-3">
                    <label class="form-label">Title / Issue Summary *</label>
                    <input type="text" name="title" class="form-control" 
                           placeholder="e.g., Leaking faucet in bathroom" 
                           required maxlength="150">
                </div>
                
                <div class="mb-3">
                    <label class="form-label">Category *</label>
                    <select name="category" class="form-select" required>
                        <option value="">Select category...</option>
                        <option value="Plumbing">Plumbing</option>
                        <option value="Electrical">Electrical</option>
                        <option value="Appliances">Appliances</option>
                        <option value="Structural">Structural</option>
                    </select>
                </div>
                
                <div class="mb-3">
                    <label class="form-label">Priority Level *</label>
                    <select name="priority" class="form-select" required>
                        <option value="">Select priority...</option>
                        <option value="Low">Low - Can wait a few days</option>
                        <option value="Medium">Medium - Needs attention soon</option>
                        <option value="High">High - Urgent issue</option>
                        <option value="Urgent">Urgent - Immediate attention required</option>
                    </select>
                </div>
                
                <div class="mb-3">
                    <label class="form-label">Description *</label>
                    <textarea name="description" class="form-control" 
                              placeholder="Please describe the issue in detail, including location and when it started..."
                              required></textarea>
                    <small class="text-muted">Be as specific as possible to help us resolve the issue faster.</small>
                </div>
                
                <div class="mb-4">
                    <label class="form-label">Upload Photo (Optional)</label>
                    <input type="file" name="photo" class="form-control" accept="image/*" id="photoInput">
                    <small class="text-muted">Upload a photo of the issue to help us understand the problem better.</small>
                    
                    <!-- Image Preview -->
                    <div id="imagePreview" style="display: none; margin-top: 10px;">
                        <img id="previewImg" style="max-width: 100%; max-height: 200px; border-radius: 8px;">
                    </div>
                </div>
                
                <div class="text-center">
                    <button type="submit" class="btn-submit">
                        <i class="bi bi-send"></i> Submit Request
                    </button>
                </div>
            </form>
            
            <script>
                // Image preview
                document.getElementById('photoInput').addEventListener('change', function(e) {
                    const file = e.target.files[0];
                    if (file) {
                        const reader = new FileReader();
                        reader.onload = function(e) {
                            document.getElementById('previewImg').src = e.target.result;
                            document.getElementById('imagePreview').style.display = 'block';
                        }
                        reader.readAsDataURL(file);
                    } else {
                        document.getElementById('imagePreview').style.display = 'none';
                    }
                });
            </script>
        </div>
    </div>
    
    <script src="../js/bootstrap.bundle.min.js"></script>
</body>
</html>