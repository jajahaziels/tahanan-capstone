<?php
require_once '../connection.php';
include '../session_auth.php';

// --- Get Property ID ---
$listingID = intval($_GET['ID'] ?? 0);
if ($listingID <= 0)
    die("Invalid property ID.");

// --- Query 1: Property + Landlord ---
$sqlProperty = "
    SELECT l.*, ld.firstName AS landlord_fname, ld.lastName AS landlord_lname, ld.profilePic 
    FROM listingtbl l
    JOIN landlordtbl ld ON l.landlord_id = ld.ID
    WHERE l.ID = ?
    LIMIT 1
";
$stmt = $conn->prepare($sqlProperty);
$stmt->bind_param("i", $listingID);
$stmt->execute();
$resultProperty = $stmt->get_result();
if ($resultProperty->num_rows === 0)
    die("Property not found.");
$property = $resultProperty->fetch_assoc();
$images = json_decode($property['images'], true) ?? [];
$stmt->close();

// --- Query 2: Current Tenant (Active Lease) ---
$sqlCurrentTenant = "
    SELECT 
        l.ID AS lease_id,
        l.start_date,
        l.end_date,
        l.pdf_path,
        t.firstName,
        t.lastName,
        t.email,
        t.phoneNum
    FROM leasetbl l
    JOIN tenanttbl t ON l.tenant_id = t.ID
    WHERE l.listing_id = ? AND l.status = 'active'
    LIMIT 1
";
$stmt2 = $conn->prepare($sqlCurrentTenant);
$stmt2->bind_param("i", $listingID);
$stmt2->execute();
$currentTenant = $stmt2->get_result()->fetch_assoc();
$stmt2->close();

// --- Query 3: Pending/Approved Tenant Requests ---
$sqlRequests = "
    SELECT 
        r.ID AS request_id,
        r.tenant_id,
        r.status AS request_status,
        r.date,
        t.firstName,
        t.lastName,
        t.phoneNum,
        t.email,
        l.ID AS lease_id,
        l.pdf_path,
        l.tenant_response
    FROM requesttbl r
    JOIN tenanttbl t 
        ON r.tenant_id = t.ID
    LEFT JOIN leasetbl l
        ON l.listing_id = r.listing_id
       AND l.tenant_id = r.tenant_id
    WHERE r.listing_id = ? AND r.status != 'rejected'
    ORDER BY r.date DESC
";

$stmt3 = $conn->prepare($sqlRequests);
$stmt3->bind_param("i", $listingID);
$stmt3->execute();
$requests = $stmt3->get_result();
$stmt3->close();

// --- Query 4: Active Maintenance Requests ---
$sqlActiveMaintenance = "
    SELECT 
        m.*,
        t.firstName,
        t.lastName,
        t.email,
        t.phoneNum
    FROM maintenance_requeststbl m
    JOIN tenanttbl t ON m.tenant_id = t.ID
    JOIN leasetbl l ON m.lease_id = l.ID
    WHERE l.listing_id = ? AND m.status != 'Completed'
    ORDER BY 
        CASE m.status 
            WHEN 'Pending' THEN 1
            WHEN 'Approved' THEN 2
            WHEN 'In Progress' THEN 3
        END,
        m.created_at DESC
";
$stmt4 = $conn->prepare($sqlActiveMaintenance);
$stmt4->bind_param("i", $listingID);
$stmt4->execute();
$activeMaintenance = $stmt4->get_result();
$stmt4->close();

// --- Query 5: Completed Maintenance History ---
$sqlMaintenanceHistory = "
    SELECT 
        m.*,
        t.firstName,
        t.lastName
    FROM maintenance_requeststbl m
    JOIN tenanttbl t ON m.tenant_id = t.ID
    JOIN leasetbl l ON m.lease_id = l.ID
    WHERE l.listing_id = ? AND m.status = 'Completed'
    ORDER BY m.completed_date DESC
    LIMIT 10
";
$stmt5 = $conn->prepare($sqlMaintenanceHistory);
$stmt5->bind_param("i", $listingID);
$stmt5->execute();
$maintenanceHistory = $stmt5->get_result();
$stmt5->close();
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Property Details</title>

    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/7.0.1/css/all.min.css"
        crossorigin="anonymous" />
    <link rel="stylesheet" href="../css/bootstrap.min.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css" rel="stylesheet">
    <link rel="stylesheet" href="../css/style.css?v=<?= time(); ?>">

    <style>
        :root {
            --primary-color: rgb(141, 11, 65);
            --primary-light: rgba(141, 11, 65, 0.1);
            --primary-dark: rgb(115, 9, 53);
        }

        body {
            background-color: #f8f9fa;
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
        }

        .page-container {
            max-width: 1400px;
            margin: 100px auto 50px;
            padding: 0 20px;
        }

        .page-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
        }

        .page-header h1 {
            font-size: 1.75rem;
            font-weight: 700;
            color: #1a1a1a;
            margin: 0;
        }

        /* Property Hero Section */
        .property-hero {
            background: white;
            border-radius: 16px;
            overflow: hidden;
            box-shadow: 0 2px 12px rgba(0,0,0,0.08);
            margin-bottom: 24px;
        }

        .property-hero-grid {
            display: grid;
            grid-template-columns: 1fr 380px;
            min-height: 200px;
        }

        @media(max-width: 1024px) {
            .property-hero-grid {
                grid-template-columns: 1fr;
            }
        }

        .property-carousel {
            position: relative;
            background: #000;
            max-height: 250px;
            overflow: hidden;
        }

        .property-carousel img {
            width: 100%;
            height: 250px;
            object-fit: cover;
        }

        .property-info-panel {
            padding: 24px;
            display: flex;
            flex-direction: column;
            gap: 16px;
        }

        .property-title {
            font-size: 1.3rem;
            font-weight: 700;
            color: #1a1a1a;
            margin: 0;
        }

        .property-price {
            font-size: 1.6rem;
            font-weight: 800;
            color: var(--primary-color);
            margin: 8px 0;
        }

        .property-tags {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
        }

        .property-tag {
            background: #f1f3f5;
            padding: 6px 12px;
            border-radius: 8px;
            font-size: 0.85rem;
            font-weight: 500;
            color: #495057;
            display: inline-flex;
            align-items: center;
            gap: 6px;
        }

        .property-description {
            font-size: 0.85rem;
            line-height: 1.5;
            color: #6c757d;
            margin-top: 8px;
        }

        /* Two Column Layout */
        .content-grid {
            display: grid;
            grid-template-columns: 1fr 380px;
            gap: 24px;
            align-items: start;
        }

        @media(max-width: 1024px) {
            .content-grid {
                grid-template-columns: 1fr;
            }
        }

        /* Card Styles */
        .card {
            background: white;
            border-radius: 12px;
            padding: 24px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.06);
            margin-bottom: 20px;
        }

        .card-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            padding-bottom: 16px;
            border-bottom: 1px solid #e9ecef;
        }

        .card-title {
            font-size: 1.1rem;
            font-weight: 600;
            color: #1a1a1a;
            display: flex;
            align-items: center;
            gap: 10px;
            margin: 0;
        }

        /* Tenant Info Card */
        .tenant-card {
            background: linear-gradient(135deg, var(--primary-color) 0%, var(--primary-dark) 100%);
            color: white;
            border-radius: 12px;
            padding: 24px;
            box-shadow: 0 4px 20px rgba(141, 11, 65, 0.3);
            margin-bottom: 20px;
            position: sticky;
            top: 20px;
        }

        .tenant-card h5 {
            font-weight: 700;
            margin-bottom: 16px;
            font-size: 1.1rem;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .tenant-info-item {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 12px;
            font-size: 0.9rem;
            padding: 8px 0;
        }

        .tenant-info-item i {
            width: 20px;
            opacity: 0.9;
        }

        .tenant-divider {
            border: 0;
            border-top: 1px solid rgba(255,255,255,0.2);
            margin: 16px 0;
        }

        .btn-view-contract {
            background: rgba(255,255,255,0.2);
            color: white;
            border: 1px solid rgba(255,255,255,0.3);
            padding: 10px 20px;
            border-radius: 8px;
            font-size: 0.9rem;
            font-weight: 600;
            width: 100%;
            transition: all 0.2s;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
        }

        .btn-view-contract:hover {
            background: rgba(255,255,255,0.3);
            color: white;
        }

        /* Maintenance Item */
        .maintenance-item {
            background: #f8f9fa;
            border: 1px solid #e9ecef;
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 16px;
            transition: all 0.2s;
        }

        .maintenance-item:hover {
            box-shadow: 0 4px 12px rgba(0,0,0,0.08);
            transform: translateY(-2px);
        }

        .maintenance-item.Pending {
            border-left: 4px solid #ffc107;
            background: #fffbf0;
        }

        .maintenance-item.In.Progress {
            border-left: 4px solid #0d6efd;
            background: #f0f7ff;
        }

        .maintenance-header {
            display: flex;
            justify-content: space-between;
            align-items: start;
            margin-bottom: 12px;
        }

        .maintenance-title {
            font-weight: 600;
            font-size: 1.05rem;
            color: #1a1a1a;
            margin-bottom: 8px;
        }

        .maintenance-meta {
            display: flex;
            flex-wrap: wrap;
            gap: 12px;
            font-size: 0.85rem;
            color: #6c757d;
            margin-bottom: 12px;
        }

        .maintenance-meta > span {
            display: flex;
            align-items: center;
            gap: 6px;
        }

        .maintenance-description {
            background: white;
            padding: 14px;
            border-radius: 8px;
            font-size: 0.9rem;
            line-height: 1.6;
            color: #495057;
            margin-bottom: 12px;
        }

        /* Photo Badge */
        .photo-badge {
            background: var(--primary-color);
            color: white;
            padding: 6px 14px;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            border: none;
        }

        .photo-badge:hover {
            background: var(--primary-dark);
            transform: scale(1.05);
        }

        /* Priority Badge */
        .priority-badge {
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 600;
        }

        .priority-Low {
            background: #d1ecf1;
            color: #0c5460;
        }

        .priority-Medium {
            background: #fff3cd;
            color: #856404;
        }

        .priority-High {
            background: #f8d7da;
            color: #721c24;
        }

        .priority-Urgent {
            background: #dc3545;
            color: white;
        }

        .category-badge {
            background: #e7f5ff;
            color: #1971c2;
            padding: 4px 10px;
            border-radius: 6px;
            font-size: 0.8rem;
            font-weight: 500;
        }

        /* Action Buttons */
        .maintenance-actions {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-top: 12px;
        }

        .btn-action {
            padding: 10px 24px;
            border-radius: 8px;
            font-size: 0.9rem;
            font-weight: 600;
            border: none;
            cursor: pointer;
            transition: all 0.2s;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }

        .btn-confirm {
            background: #198754;
            color: white;
        }

        .btn-confirm:hover {
            background: #157347;
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(25, 135, 84, 0.3);
        }

        .btn-complete {
            background: #0d6efd;
            color: white;
        }

        .btn-complete:hover {
            background: #0b5ed7;
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(13, 110, 253, 0.3);
        }

        /* History Item */
        .history-item {
            padding: 12px 16px;
            background: #f0fdf4;
            border-left: 3px solid #22c55e;
            border-radius: 6px;
            margin-bottom: 10px;
        }

        .history-title {
            font-weight: 600;
            font-size: 0.9rem;
            color: #1a1a1a;
            margin-bottom: 4px;
        }

        .history-meta {
            font-size: 0.8rem;
            color: #6c757d;
        }

        /* Modal */
        .photo-modal {
            display: none;
            position: fixed;
            z-index: 9999;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.9);
            animation: fadeIn 0.2s;
        }

        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }

        .photo-modal-content {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            max-width: 90%;
            max-height: 90%;
        }

        .photo-modal-content img {
            max-width: 100%;
            max-height: 90vh;
            border-radius: 8px;
        }

        .photo-modal-close {
            position: absolute;
            top: 20px;
            right: 40px;
            color: white;
            font-size: 40px;
            font-weight: bold;
            cursor: pointer;
            transition: 0.2s;
        }

        .photo-modal-close:hover {
            color: #ccc;
        }

        /* Empty State */
        .empty-state {
            text-align: center;
            padding: 40px 20px;
            color: #adb5bd;
        }

        .empty-state i {
            font-size: 3rem;
            margin-bottom: 12px;
            opacity: 0.4;
        }

        /* Application Item */
        .application-item {
            background: #f8f9fa;
            border: 1px solid #e9ecef;
            border-radius: 10px;
            padding: 16px;
            margin-bottom: 12px;
        }

        .application-item:hover {
            box-shadow: 0 4px 12px rgba(0,0,0,0.08);
        }

        .btn-small {
            padding: 6px 16px;
            border-radius: 6px;
            font-size: 0.85rem;
            font-weight: 500;
            border: 2px solid;
            background: transparent;
            cursor: pointer;
            transition: all 0.2s;
        }

        .btn-primary {
            border-color: #0d6efd;
            color: #0d6efd;
        }

        .btn-primary:hover {
            background: #0d6efd;
            color: white;
        }

        .btn-danger {
            border-color: #dc3545;
            color: #dc3545;
        }

        .btn-danger:hover {
            background: #dc3545;
            color: white;
        }
    </style>
</head>

<body>
    <?php include '../Components/landlord-header.php'; ?>

    <div class="page-container">
        <div class="page-header">
            <h1>Property Management</h1>
            <button class="main-button" onclick="location.href='landlord-properties.php'">
                <i class="bi bi-arrow-left"></i> Back to Properties
            </button>
        </div>

        <!-- Property Hero -->
        <div class="property-hero">
            <div class="property-hero-grid">
                <!-- Carousel -->
                <div class="property-carousel">
                    <div id="carouselExample" class="carousel slide h-100">
                        <div class="carousel-inner h-100">
                            <?php if (!empty($images)):
                                foreach ($images as $i => $img): ?>
                                    <div class="carousel-item h-100 <?= $i === 0 ? 'active' : '' ?>">
                                        <img src="../LANDLORD/uploads/<?= htmlspecialchars($img); ?>" alt="Property">
                                    </div>
                                <?php endforeach; else: ?>
                                <div class="carousel-item active h-100">
                                    <img src="../LANDLORD/uploads/placeholder.jpg" alt="No Image">
                                </div>
                            <?php endif; ?>
                        </div>
                        <button class="carousel-control-prev" type="button" data-bs-target="#carouselExample" data-bs-slide="prev">
                            <span class="carousel-control-prev-icon"></span>
                        </button>
                        <button class="carousel-control-next" type="button" data-bs-target="#carouselExample" data-bs-slide="next">
                            <span class="carousel-control-next-icon"></span>
                        </button>
                    </div>
                </div>

                <!-- Info Panel -->
                <div class="property-info-panel">
                    <div>
                        <h2 class="property-title"><?= htmlspecialchars($property['listingName']); ?></h2>
                        <p class="property-price">₱<?= number_format($property['price']); ?><small style="font-size: 0.5em; color: #6c757d;">/month</small></p>
                    </div>

                    <div class="property-tags">
                        <span class="property-tag">
                            <i class="bi bi-building"></i> <?= htmlspecialchars($property['category']); ?>
                        </span>
                        <span class="property-tag">
                            <i class="bi bi-door-closed"></i> <?= htmlspecialchars($property['rooms']); ?> Bedroom
                        </span>
                        <span class="property-tag">
                            <i class="bi bi-geo-alt"></i> <?= htmlspecialchars($property['barangay']); ?>
                        </span>
                    </div>

                    <div class="property-description">
                        <?= nl2br(htmlspecialchars($property['listingDesc'])); ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- Content Grid -->
        <div class="content-grid">
            <!-- Main Content -->
            <div>
                <!-- Active Maintenance -->
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">
                            <i class="bi bi-tools"></i> Active Maintenance Requests
                            <?php
                            $pendingCount = 0;
                            if ($activeMaintenance->num_rows > 0) {
                                $activeMaintenance->data_seek(0);
                                while ($m = $activeMaintenance->fetch_assoc()) {
                                    if ($m['status'] === 'Pending') $pendingCount++;
                                }
                                $activeMaintenance->data_seek(0);
                            }
                            if ($pendingCount > 0): ?>
                                <span class="badge bg-danger rounded-pill"><?= $pendingCount; ?></span>
                            <?php endif; ?>
                        </h3>
                    </div>

                    <?php if ($activeMaintenance->num_rows > 0): ?>
                        <?php while ($m = $activeMaintenance->fetch_assoc()): ?>
                            <div class="maintenance-item <?= htmlspecialchars(str_replace(' ', '.', $m['status'])); ?>">
                                <div class="maintenance-header">
                                    <div style="flex: 1;">
                                        <div class="maintenance-title">
                                            <i class="bi bi-wrench-adjustable"></i>
                                            <?= htmlspecialchars($m['title']); ?>
                                        </div>
                                        <div class="maintenance-meta">
                                            <span>
                                                <i class="bi bi-person"></i>
                                                <?= htmlspecialchars($m['firstName'] . ' ' . $m['lastName']); ?>
                                            </span>
                                            <span>
                                                <i class="bi bi-calendar3"></i>
                                                <?= date('M d, Y', strtotime($m['requested_date'])); ?>
                                            </span>
                                            <span class="category-badge"><?= htmlspecialchars($m['category']); ?></span>
                                        </div>
                                    </div>
                                    <span class="priority-badge priority-<?= htmlspecialchars($m['priority']); ?>">
                                        <?= htmlspecialchars($m['priority']); ?>
                                    </span>
                                </div>

                                <div class="maintenance-description">
                                    <?= nl2br(htmlspecialchars($m['description'])); ?>
                                </div>

                                <div class="maintenance-actions">
                                    <div>
                                        <?php if (!empty($m['photo_path']) && file_exists($m['photo_path'])): ?>
                                            <button class="photo-badge" onclick="viewPhoto('<?= htmlspecialchars($m['photo_path']); ?>')">
                                                <i class="bi bi-camera-fill"></i> View Photo
                                            </button>
                                        <?php endif; ?>
                                    </div>
                                    <div>
                                        <?php if ($m['status'] === 'Pending'): ?>
                                            <button class="btn-action btn-confirm" onclick="confirmRequest(<?= $m['id']; ?>)">
                                                <i class="bi bi-check-circle"></i> Confirm Request
                                            </button>
                                        <?php elseif ($m['status'] === 'Approved' || $m['status'] === 'In Progress'): ?>
                                            <button class="btn-action btn-complete" onclick="completeRequest(<?= $m['id']; ?>)">
                                                <i class="bi bi-check-all"></i> Mark Complete
                                            </button>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <div class="empty-state">
                            <i class="bi bi-inbox"></i>
                            <p>No active maintenance requests</p>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- History -->
                <?php if ($maintenanceHistory->num_rows > 0): ?>
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title">
                                <i class="bi bi-clock-history"></i> Maintenance History
                            </h3>
                        </div>

                        <?php while ($h = $maintenanceHistory->fetch_assoc()): ?>
                            <div class="history-item">
                                <div class="history-title">
                                    <i class="bi bi-check-circle-fill text-success"></i>
                                    <?= htmlspecialchars($h['title']); ?>
                                </div>
                                <div class="history-meta">
                                    <?= htmlspecialchars($h['firstName'] . ' ' . $h['lastName']); ?> •
                                    Completed: <?= date('M d, Y', strtotime($h['completed_date'])); ?> •
                                    <?= htmlspecialchars($h['category']); ?>
                                </div>
                            </div>
                        <?php endwhile; ?>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Sidebar -->
            <div>
                <?php if ($currentTenant): ?>
                    <!-- Current Tenant -->
                    <div class="tenant-card">
                        <h5><i class="bi bi-person-check-fill"></i> Current Tenant</h5>
                        
                        <div class="tenant-info-item">
                            <i class="bi bi-person-circle"></i>
                            <strong><?= htmlspecialchars($currentTenant['firstName'] . ' ' . $currentTenant['lastName']); ?></strong>
                        </div>
                        <div class="tenant-info-item">
                            <i class="bi bi-telephone-fill"></i>
                            <?= htmlspecialchars($currentTenant['phoneNum']); ?>
                        </div>
                        <div class="tenant-info-item">
                            <i class="bi bi-envelope-fill"></i>
                            <?= htmlspecialchars($currentTenant['email']); ?>
                        </div>
                        
                        <hr class="tenant-divider">
                        
                        <div class="tenant-info-item">
                            <i class="bi bi-calendar-range-fill"></i>
                            <?= date('M d, Y', strtotime($currentTenant['start_date'])); ?>
                        </div>
                        <div class="tenant-info-item">
                            <i class="bi bi-calendar-x-fill"></i>
                            <?= date('M d, Y', strtotime($currentTenant['end_date'])); ?>
                        </div>
                        
                        <?php if (!empty($currentTenant['pdf_path'])): ?>
                            <a href="<?= htmlspecialchars($currentTenant['pdf_path']); ?>" target="_blank" 
                               class="btn-view-contract">
                                <i class="bi bi-file-pdf-fill"></i> View Lease Contract
                            </a>
                        <?php endif; ?>
                    </div>
                <?php else: ?>
                    <!-- Applications -->
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title">
                                <i class="bi bi-file-text"></i> Applications
                            </h3>
                        </div>

                        <?php if ($requests->num_rows > 0): ?>
                            <?php while ($req = $requests->fetch_assoc()): ?>
                                <div class="application-item">
                                    <strong style="display: block; margin-bottom: 8px;">
                                        <?= htmlspecialchars($req['firstName'] . ' ' . $req['lastName']); ?>
                                    </strong>
                                    <small class="d-block text-muted mb-2" style="font-size: 0.8rem;">
                                        <?= htmlspecialchars($req['phoneNum']); ?>
                                    </small>

                                    <span class="badge bg-secondary mb-2"><?= ucfirst($req['request_status']); ?></span>

                                    <?php if (!empty($req['tenant_response'])): ?>
                                        <span class="badge <?= $req['tenant_response'] === 'accepted' ? 'bg-success' : 'bg-danger'; ?> mb-2">
                                            <?= ucfirst($req['tenant_response']); ?>
                                        </span>
                                    <?php endif; ?>

                                    <div class="d-flex gap-2 mt-2">
                                        <?php if ($req['lease_id']): ?>
                                            <button class="btn-small btn-primary" onclick="window.open('<?= htmlspecialchars($req['pdf_path'] ?? '#'); ?>', '_blank')">
                                                View
                                            </button>
                                        <?php else: ?>
                                            <button class="btn-small btn-primary" onclick="window.location='lease-form.php?request_id=<?= $req['request_id']; ?>&listing_id=<?= $listingID; ?>&tenant_id=<?= $req['tenant_id']; ?>'">
                                                Create Lease
                                            </button>
                                        <?php endif; ?>

                                        <?php if ($req['tenant_response'] === 'rejected' || empty($req['lease_id'])): ?>
                                            <button class="btn-small btn-danger reject-btn" data-request="<?= $req['request_id']; ?>">
                                                <?= $req['tenant_response'] === 'rejected' ? 'Remove' : 'Reject'; ?>
                                            </button>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <div class="empty-state">
                                <i class="bi bi-inbox"></i>
                                <p>No applications</p>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Photo Modal -->
    <div id="photoModal" class="photo-modal" onclick="closePhoto()">
        <span class="photo-modal-close">&times;</span>
        <div class="photo-modal-content" onclick="event.stopPropagation()">
            <img id="modalImage" src="" alt="Maintenance Photo">
        </div>
    </div>

    <script src="../js/bootstrap.bundle.min.js"></script>
    <script>
        function viewPhoto(photoPath) {
            document.getElementById('modalImage').src = photoPath;
            document.getElementById('photoModal').style.display = 'block';
        }

        function closePhoto() {
            document.getElementById('photoModal').style.display = 'none';
        }

        // Close on ESC key
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape') closePhoto();
        });

        function confirmRequest(requestId) {
            if (!confirm('Confirm this maintenance request?')) return;
            updateStatus(requestId, 'In Progress');
        }

        function completeRequest(requestId) {
            if (!confirm('Mark this maintenance as completed?')) return;
            updateStatus(requestId, 'Completed');
        }

        function updateStatus(requestId, status) {
            fetch("update-maintenance-status.php", {
                method: "POST",
                headers: { "Content-Type": "application/x-www-form-urlencoded" },
                body: `request_id=${requestId}&status=${encodeURIComponent(status)}`
            })
            .then(res => res.text())
            .then(result => {
                if (result.trim() === 'success') {
                    location.reload();
                } else {
                    alert("Failed to update status");
                }
            });
        }

        document.querySelectorAll(".reject-btn").forEach(btn => {
            btn.addEventListener("click", () => {
                if (!confirm("Are you sure?")) return;
                const requestId = btn.dataset.request;
                fetch("update-request.php", {
                    method: "POST",
                    headers: { "Content-Type": "application/x-www-form-urlencoded" },
                    body: `request_id=${requestId}&action=reject`
                }).then(() => location.reload());
            });
        });
    </script>
</body>
</html>