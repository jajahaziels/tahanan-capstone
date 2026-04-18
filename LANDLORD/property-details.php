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
$images   = json_decode($property['images'], true) ?? [];
$terms    = json_decode($property['terms']  ?? '[]', true) ?? [];
$stmt->close();

// --- Query 2: Tenant Requests + Latest Lease Info ---
$sqlRequests = "
    SELECT
        r.ID           AS request_id,
        r.tenant_id,
        r.status       AS request_status,
        r.date,
        r.tenant_action,
        t.firstName,
        t.lastName,
        t.phoneNum,
        t.email,
        t.profilePic,
        l.ID             AS lease_id,
        l.pdf_path,
        l.tenant_response,
        l.status         AS lease_status
    FROM requesttbl r
    JOIN tenanttbl t ON r.tenant_id = t.ID
    LEFT JOIN leasetbl l
        ON  l.listing_id = r.listing_id
        AND l.tenant_id  = r.tenant_id
        AND l.ID = (
            SELECT MAX(l2.ID)
            FROM leasetbl l2
            WHERE l2.listing_id = r.listing_id
              AND l2.tenant_id  = r.tenant_id
        )
    WHERE r.listing_id = ?
      AND (r.tenant_action IS NULL OR r.tenant_action NOT IN ('removed'))
    ORDER BY r.date DESC
";
$stmt2 = $conn->prepare($sqlRequests);
$stmt2->bind_param("i", $listingID);
$stmt2->execute();
$requests = $stmt2->get_result();
$stmt2->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Property Details</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/7.0.1/css/all.min.css" crossorigin="anonymous" />
    <link rel="stylesheet" href="../css/bootstrap.min.css">
    <link rel="stylesheet" href="../css/style.css?v=<?= time(); ?>">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>

        body { background-color: var(--bg-alt-color); }

        .page-wrap {
            max-width: 1400px;
            margin: 0 auto;
            padding: 120px 24px 80px;
        }

        /* ── Page Header ── */
        .page-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 28px;
            gap: 16px;
        }

        .page-header h1 {
            font-size: clamp(1.4rem, 3vw, 2rem);
            font-weight: 700;
            color: var(--text-color);
        }

        .page-header h1 span { color: var(--main-color); }

        .btn-back {
            display: inline-flex;
            align-items: center;
            gap: 7px;
            background: var(--bg-color);
            color: var(--text-color);
            border: 1.5px solid rgba(141,11,65,0.2);
            padding: 10px 22px;
            border-radius: 50px;
            font-family: inherit;
            font-size: 0.85rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.22s ease;
            text-decoration: none;
        }

        .btn-back:hover {
            background: var(--main-color);
            color: #fff;
            border-color: var(--main-color);
        }

        /* ── Layout ── */
        .main-content {
            display: grid;
            grid-template-columns: 1.6fr 1fr;
            gap: 24px;
            align-items: start;
        }

        @media(max-width: 991px) {
            .main-content { grid-template-columns: 1fr; }
        }

        /* ── Panel base ── */
        .panel {
            background: var(--bg-color);
            border-radius: 18px;
            box-shadow: 0 4px 24px var(--shadow-color);
            overflow: hidden;
            border: 1.5px solid rgba(141,11,65,0.08);
        }

        .panel-body { padding: 24px; }

        /* ── Image Gallery ── */
        .gallery-wrap {
            position: relative;
            cursor: pointer;
        }

        .gallery-wrap .carousel-inner { max-height: 340px; }
        .gallery-wrap .carousel-item img {
            width: 100%;
            height: 340px;
            object-fit: cover;
            transition: transform 0.4s ease;
        }

        .gallery-wrap:hover .carousel-item.active img {
            transform: scale(1.02);
        }

        /* zoom hint */
        .gallery-hint {
            position: absolute;
            bottom: 14px;
            right: 14px;
            background: rgba(0,0,0,0.55);
            color: #fff;
            font-size: 0.72rem;
            font-weight: 600;
            padding: 5px 12px;
            border-radius: 50px;
            display: flex;
            align-items: center;
            gap: 5px;
            backdrop-filter: blur(4px);
            pointer-events: none;
            z-index: 5;
        }

        /* ── Property Info ── */
        .prop-name {
            font-size: 1.4rem;
            font-weight: 700;
            color: var(--text-color);
            margin-bottom: 4px;
        }

        .prop-price {
            font-size: 1.6rem;
            font-weight: 800;
            color: var(--main-color);
            margin-bottom: 14px;
        }

        .prop-price small {
            font-size: 0.8rem;
            font-weight: 400;
            color: var(--text-alt-color);
        }

        .prop-tags {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
            margin-bottom: 16px;
        }

        .prop-tag {
            background: var(--bg-alt-color);
            color: var(--text-alt-color);
            font-size: 0.78rem;
            font-weight: 600;
            padding: 5px 14px;
            border-radius: 50px;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            border: 1px solid rgba(141,11,65,0.1);
        }

        .prop-tag i { color: var(--main-color); font-size: 0.75rem; }

        .prop-meta {
            font-size: 0.85rem;
            color: var(--text-alt-color);
            margin-bottom: 8px;
            display: flex;
            align-items: flex-start;
            gap: 7px;
        }

        .prop-meta i { color: var(--main-color); margin-top: 2px; flex-shrink: 0; }

        .prop-description {
            font-size: 0.9rem;
            color: var(--text-color);
            line-height: 1.7;
            margin-top: 14px;
            padding-top: 14px;
            border-top: 1px solid var(--bg-alt-color);
        }

        /* ── House Rules ── */
        .rules-panel {
            background: var(--bg-alt-color);
            border: 1px solid rgba(141,11,65,0.2);
            border-radius: 12px;
            padding: 16px 20px;
            margin-top: 18px;
        }

        .rules-panel h6 {
            color: var(--main-color);
            font-weight: 700;
            margin-bottom: 12px;
            font-size: 0.88rem;
            text-transform: uppercase;
            letter-spacing: 0.06em;
        }

        .rules-panel ul { margin: 0; padding: 0; list-style: none; }

        .rules-panel ul li {
            display: flex;
            align-items: flex-start;
            gap: 9px;
            padding: 7px 0;
            border-bottom: 1px dashed rgba(141,11,65,0.15);
            font-size: 0.87rem;
            color: var(--text-color);
        }

        .rules-panel ul li:last-child { border-bottom: none; }
        .rules-panel ul li i { color: var(--main-color); margin-top: 3px; flex-shrink: 0; }

        .no-rules-note { font-size: 0.83rem; color: var(--text-alt-color); font-style: italic; }
        .no-rules-note a { color: var(--main-color); }

        /* ── Edit button ── */
        .btn-edit {
            display: inline-flex;
            align-items: center;
            gap: 7px;
            margin-top: 20px;
            background: var(--main-color);
            color: #fff;
            border: none;
            padding: 11px 24px;
            border-radius: 50px;
            font-family: inherit;
            font-size: 0.85rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.22s ease;
            text-decoration: none;
            box-shadow: 0 4px 14px rgba(141,11,65,0.25);
        }

        .btn-edit:hover {
            background: #6e0932;
            color: #fff;
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(141,11,65,0.35);
        }

        /* ── Requests Panel ── */
        .requests-header {
            padding: 20px 24px 0;
            border-bottom: 1px solid var(--bg-alt-color);
            margin-bottom: 20px;
            padding-bottom: 16px;
        }

        .requests-header h2 {
            font-size: 1.1rem;
            font-weight: 700;
            color: var(--text-color);
        }

        .requests-header h2 span { color: var(--main-color); }

        .requests-body { padding: 0 20px 20px; }

        /* ── Tenant Request Card ── */
        .tenant-card {
            background: var(--bg-alt-color);
            border: 1.5px solid rgba(141,11,65,0.1);
            border-radius: 14px;
            padding: 16px;
            margin-bottom: 14px;
            transition: all 0.22s ease;
            display: flex;
            flex-direction: column;
            gap: 8px;
        }

        .tenant-card:hover {
            border-color: rgba(141,11,65,0.3);
            box-shadow: 0 4px 16px var(--shadow-color);
        }

        .tenant-name {
            font-size: 0.95rem;
            font-weight: 700;
            color: var(--text-color);
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .tenant-avatar {
            width: 34px;
            height: 34px;
            border-radius: 50%;
            background: var(--main-color);
            color: #fff;
            font-size: 0.8rem;
            font-weight: 700;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
        }

        .tenant-contact {
            font-size: 0.78rem;
            color: var(--text-alt-color);
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
        }

        .tenant-contact span { display: flex; align-items: center; gap: 4px; }
        .tenant-contact i { color: var(--main-color); }

        /* ── Status row ── */
        .status-row {
            display: flex;
            align-items: center;
            gap: 8px;
            flex-wrap: wrap;
            font-size: 0.8rem;
            color: var(--text-alt-color);
        }

        .status-badge {
            font-size: 0.72rem;
            font-weight: 700;
            padding: 3px 10px;
            border-radius: 50px;
            display: inline-flex;
            align-items: center;
            gap: 4px;
        }

        .s-secondary { background: rgba(100,120,135,0.12); color: var(--text-alt-color); }
        .s-success   { background: rgba(25,135,84,0.12);   color: #198754; }
        .s-warning   { background: rgba(255,193,7,0.15);    color: #856404; }

        /* ── Notice boxes ── */
        .notice {
            border-radius: 10px;
            padding: 10px 14px;
            font-size: 0.82rem;
            font-weight: 500;
            display: flex;
            flex-direction: column;
            gap: 4px;
        }

        .notice-ended {
            background: rgba(220,53,69,0.08);
            border: 1px solid rgba(220,53,69,0.2);
            border-left: 3px solid #dc3545;
            color: #842029;
        }

        .notice-reapply {
            background: rgba(141,11,65,0.06);
            border: 1px solid rgba(141,11,65,0.15);
            border-left: 3px solid var(--main-color);
            color: var(--main-color);
        }

        .notice-termination {
            background: rgba(255,193,7,0.08);
            border: 1px solid rgba(255,193,7,0.25);
            border-left: 3px solid #ffc107;
            color: #856404;
        }

        .notice-termination .reason-text {
            font-style: italic;
            color: var(--text-alt-color);
            font-size: 0.8rem;
        }

        /* ── Action buttons ── */
        .action-row {
            display: flex;
            gap: 8px;
            flex-wrap: wrap;
            margin-top: 4px;
            padding-top: 10px;
            border-top: 1px solid rgba(141,11,65,0.08);
        }

        .btn2 {
            border-radius: 8px;
            cursor: pointer;
            font-size: 0.8rem;
            font-weight: 600;
            padding: 8px 16px;
            background: transparent;
            border: 1.5px solid;
            transition: all 0.22s ease;
            font-family: inherit;
            display: inline-flex;
            align-items: center;
            gap: 5px;
            text-decoration: none;
        }

        .btn2-blue   { border-color: #0d6efd; color: #0d6efd; }
        .btn2-blue:hover  { background: #0d6efd; color: #fff; }
        .btn2-green  { border-color: #198754; color: #198754; }
        .btn2-green:hover { background: #198754; color: #fff; }
        .btn2-red    { border-color: #dc3545; color: #dc3545; }
        .btn2-red:hover   { background: #dc3545; color: #fff; }
        .btn2-orange { border-color: #fd7e14; color: #fd7e14; }
        .btn2-orange:hover { background: #fd7e14; color: #fff; }

        /* ── Empty state ── */
        .empty-requests {
            text-align: center;
            padding: 40px 20px;
            color: var(--text-alt-color);
        }

        .empty-requests i {
            font-size: 2.5rem;
            color: var(--main-color);
            opacity: 0.2;
            display: block;
            margin-bottom: 10px;
        }

        /* ════════════════════════════════════════
           LIGHTBOX
        ════════════════════════════════════════ */
        .lightbox-overlay {
            display: none;
            position: fixed;
            inset: 0;
            background: rgba(0,0,0,0.92);
            z-index: 9999;
            align-items: center;
            justify-content: center;
            animation: lbFadeIn 0.2s ease;
        }

        .lightbox-overlay.active { display: flex; }

        @keyframes lbFadeIn {
            from { opacity: 0; }
            to   { opacity: 1; }
        }

        .lightbox-inner {
            position: relative;
            width: 100%;
            max-width: 900px;
            padding: 16px;
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 14px;
        }

        .lightbox-img-wrap {
            width: 100%;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .lightbox-img-wrap img {
            max-width: 100%;
            max-height: 80vh;
            border-radius: 12px;
            object-fit: contain;
            box-shadow: 0 8px 40px rgba(0,0,0,0.6);
            animation: lbSlide 0.25s ease;
        }

        @keyframes lbSlide {
            from { opacity: 0; transform: scale(0.96); }
            to   { opacity: 1; transform: scale(1); }
        }

        .lightbox-close {
            position: absolute;
            top: -10px;
            right: 16px;
            background: rgba(255,255,255,0.1);
            border: none;
            color: #fff;
            font-size: 1.4rem;
            width: 40px;
            height: 40px;
            border-radius: 50%;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: background 0.2s;
        }

        .lightbox-close:hover { background: var(--main-color); }

        .lightbox-nav {
            position: absolute;
            top: 50%;
            transform: translateY(-50%);
            background: rgba(255,255,255,0.1);
            border: none;
            color: #fff;
            font-size: 1.3rem;
            width: 44px;
            height: 44px;
            border-radius: 50%;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: background 0.2s;
            z-index: 2;
        }

        .lightbox-nav:hover { background: var(--main-color); }
        .lightbox-prev { left: 0; }
        .lightbox-next { right: 0; }

        .lightbox-counter {
            color: rgba(255,255,255,0.6);
            font-size: 0.8rem;
            font-weight: 600;
            letter-spacing: 0.08em;
        }

        .lightbox-thumbs {
            display: flex;
            gap: 8px;
            flex-wrap: wrap;
            justify-content: center;
        }

        .lightbox-thumbs img {
            width: 56px;
            height: 42px;
            object-fit: cover;
            border-radius: 6px;
            cursor: pointer;
            opacity: 0.5;
            border: 2px solid transparent;
            transition: all 0.2s;
        }

        .lightbox-thumbs img.active,
        .lightbox-thumbs img:hover {
            opacity: 1;
            border-color: var(--main-color);
        }

        .avatar-wrap { position: relative; flex-shrink: 0; }

.tenant-avatar {
    cursor: pointer;
    transition: border-color 0.2s, box-shadow 0.2s;
}

.tenant-avatar:hover {
    border: 2px solid rgba(141,11,65,0.5);
    box-shadow: 0 0 0 3px rgba(141,11,65,0.12);
}

.avatar-dropdown {
    position: absolute;
    top: calc(100% + 6px);
    left: 0;
    background: var(--bg-color);
    border: 1px solid rgba(141,11,65,0.15);
    border-radius: 10px;
    box-shadow: 0 6px 20px var(--shadow-color);
    min-width: 170px;
    z-index: 99;
    overflow: hidden;
    display: none;
}

.avatar-dropdown.open { display: block; }

.avatar-dropdown a {
    display: flex;
    align-items: center;
    gap: 9px;
    padding: 11px 16px;
    font-size: 0.83rem;
    font-weight: 600;
    color: var(--text-color);
    text-decoration: none;
    transition: background 0.15s;
}

.avatar-dropdown a:hover {
    background: var(--bg-alt-color);
    color: var(--main-color);
}

.avatar-dropdown a i { color: var(--main-color); font-size: 0.78rem; width: 14px; }

img.tenant-avatar {
    object-fit: cover;
    padding: 0;
    border: 2px solid transparent;
}
    </style>
</head>

<body>
    <?php include '../Components/landlord-header.php'; ?>

    <!-- Lightbox -->
    <div class="lightbox-overlay" id="lightbox">
        <div class="lightbox-inner">
            <button class="lightbox-close" id="lbClose"><i class="fas fa-times"></i></button>
            <button class="lightbox-nav lightbox-prev" id="lbPrev"><i class="fas fa-chevron-left"></i></button>
            <button class="lightbox-nav lightbox-next" id="lbNext"><i class="fas fa-chevron-right"></i></button>

            <div class="lightbox-img-wrap">
                <img src="" alt="Full View" id="lbImg">
            </div>

            <div class="lightbox-counter" id="lbCounter"></div>

            <div class="lightbox-thumbs" id="lbThumbs"></div>
        </div>
    </div>

    <div class="page-wrap">

        <!-- Header -->
        <div class="page-header">
            <h1>Property <span>Details</span></h1>
            <button class="btn-back" onclick="location.href='landlord-properties.php'">
                <i class="fas fa-arrow-left"></i> Back
            </button>
        </div>

        <div class="main-content">

            <!-- LEFT -->
            <div class="left">
                <div class="panel">

                    <!-- Gallery -->
                    <div class="gallery-wrap" id="galleryWrap">
                        <div id="carouselExample" class="carousel slide">
                            <div class="carousel-inner">
                                <?php if (!empty($images)):
                                    foreach ($images as $i => $img): ?>
                                        <div class="carousel-item <?= $i === 0 ? 'active' : '' ?>">
                                            <img src="../LANDLORD/uploads/<?= htmlspecialchars($img) ?>" alt="Property Image">
                                        </div>
                                    <?php endforeach; else: ?>
                                    <div class="carousel-item active">
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
                        <div class="gallery-hint"><i class="fas fa-expand"></i> Click to expand</div>
                    </div>

                    <!-- Property Info -->
                    <div class="panel-body">
                        <h2 class="prop-name"><?= htmlspecialchars($property['listingName']) ?></h2>
                        <div class="prop-price">
                            ₱<?= number_format($property['price']) ?>.00 <small>/ month</small>
                        </div>

                        <div class="prop-tags">
                            <span class="prop-tag"><i class="fas fa-building"></i> <?= htmlspecialchars($property['category']) ?></span>
                            <span class="prop-tag"><i class="fas fa-bed"></i> <?= htmlspecialchars($property['rooms']) ?> Bedroom(s)</span>
                            <span class="prop-tag"><i class="fas fa-map-pin"></i> <?= htmlspecialchars($property['barangay']) ?></span>
                        </div>

                        <div class="prop-meta">
                            <i class="fas fa-map-marker-alt"></i>
                            <?= htmlspecialchars($property['address']) ?>, <?= htmlspecialchars($property['barangay']) ?>, San Pedro, Laguna
                        </div>

                        <div class="prop-description">
                            <?= nl2br(htmlspecialchars($property['listingDesc'])) ?>
                        </div>

                        <!-- House Rules -->
                        <div class="rules-panel">
                            <h6><i class="fa-solid fa-clipboard-list me-1"></i> House Rules &amp; Terms</h6>
                            <?php if (!empty($terms)): ?>
                                <ul>
                                    <?php foreach ($terms as $rule): ?>
                                        <li>
                                            <i class="fa-solid fa-circle-dot"></i>
                                            <?= htmlspecialchars($rule) ?>
                                        </li>
                                    <?php endforeach; ?>
                                </ul>
                            <?php else: ?>
                                <p class="no-rules-note mb-0">
                                    No house rules set yet.
                                    <a href="edit-property.php?ID=<?= $listingID ?>">Edit property</a> to add some.
                                </p>
                            <?php endif; ?>
                        </div>

                        <a href="edit-property.php?ID=<?= $listingID ?>" class="btn-edit">
                            <i class="fa-solid fa-pen"></i> Edit Property
                        </a>
                    </div>
                </div>
            </div>

            <!-- RIGHT -->
            <div class="right">
                <div class="panel">
                    <div class="requests-header">
                        <h2>Tenant <span>Requests</span></h2>
                    </div>
                    <div class="requests-body">

                        <?php if ($requests->num_rows > 0):
                            while ($req = $requests->fetch_assoc()):

                                $leaseStatus    = $req['lease_status']    ?? null;
                                $tenantResponse = $req['tenant_response'] ?? null;
                                $leaseId        = $req['lease_id']        ?? null;

                                $hasLease           = !empty($leaseId);
                                $leaseIsTerminated  = $hasLease && $leaseStatus === 'terminated';
                                $leaseIsCancelled   = $hasLease && $leaseStatus === 'cancelled';
                                $tenantRejected     = $tenantResponse === 'rejected';
                                $leaseIsActive      = $hasLease && $leaseStatus === 'active';
                                $leaseIsPending     = $hasLease && $leaseStatus === 'pending';
                                $leaseIsCancellable = $leaseIsPending && $tenantResponse !== 'accepted';
                                $canMakeNewLease    = !$hasLease || $leaseIsCancelled || $leaseIsTerminated || $tenantRejected;

                                $terminationInfo = null;
                                if ($leaseIsActive) {
                                    $tStmt = $conn->prepare("
                                        SELECT ID, reason, landlord_status
                                        FROM lease_terminationstbl
                                        WHERE lease_id = ? AND terminated_by = 'tenant'
                                        ORDER BY ID DESC LIMIT 1
                                    ");
                                    $tStmt->bind_param("i", $leaseId);
                                    $tStmt->execute();
                                    $terminationInfo = $tStmt->get_result()->fetch_assoc();
                                    $tStmt->close();
                                }
                                $hasPendingTermination = $terminationInfo && $terminationInfo['landlord_status'] === 'pending';

                                $initials = strtoupper(substr($req['firstName'],0,1) . substr($req['lastName'],0,1));
                        ?>

                            <div class="tenant-card" id="req-card-<?= $req['request_id'] ?>">

                                <!-- Name + Avatar -->
<div class="tenant-name">
    <div class="avatar-wrap" title="Tenant options">
    <?php if (!empty($req['profilePic'])): ?>
        <img src="../uploads/<?= htmlspecialchars($req['profilePic']) ?>"
     alt="<?= htmlspecialchars($req['firstName']) ?>"
     class="tenant-avatar avatar-trigger"
     style="object-fit: cover; padding: 0;">
    <?php else: ?>
        <span class="tenant-avatar avatar-trigger"><?= $initials ?></span>
    <?php endif; ?>
        <div class="avatar-dropdown">
            <a href="tenant-profile.php?tenant_id=<?= $req['tenant_id'] ?>">
                <i class="fas fa-user"></i> View Profile
            </a>
            <a href="landlord-message.php?tenant_id=<?= $req['tenant_id'] ?>">
                <i class="fas fa-comment"></i> Message Tenant
            </a>
        </div>
    </div>
    <?= htmlspecialchars($req['firstName'] . ' ' . $req['lastName']) ?>
</div>

                                <!-- Contact -->
                                <div class="tenant-contact">
                                    <span><i class="fas fa-envelope"></i> <?= htmlspecialchars($req['email']) ?></span>
                                    <span><i class="fas fa-phone"></i> <?= htmlspecialchars($req['phoneNum']) ?></span>
                                </div>

                                <!-- Request status -->
                                <div class="status-row">
                                    <span>Request:</span>
                                    <span class="status-badge s-secondary"><?= ucfirst($req['request_status']) ?></span>

                                    <?php if ($leaseIsActive || ($leaseIsPending && !$tenantRejected)): ?>
                                        <span>Response:</span>
                                        <?php if ($tenantResponse === 'accepted'): ?>
                                            <span class="status-badge s-success"><i class="fas fa-check"></i> Accepted</span>
                                        <?php else: ?>
                                            <span class="status-badge s-warning"><i class="fas fa-clock"></i> Pending</span>
                                        <?php endif; ?>
                                    <?php endif; ?>
                                </div>

                                <!-- Notices -->
                                <?php if ($tenantRejected): ?>
                                    <div class="notice notice-ended">
                                        <strong><i class="fas fa-times-circle me-1"></i> Tenant rejected the lease agreement.</strong>
                                    </div>
                                <?php elseif ($leaseIsTerminated): ?>
                                    <div class="notice notice-ended">
                                        <strong><i class="fas fa-ban me-1"></i> Lease was terminated.</strong>
                                    </div>
                                <?php elseif ($leaseIsCancelled): ?>
                                    <div class="notice notice-ended">
                                        <strong><i class="fas fa-ban me-1"></i> Lease was cancelled.</strong>
                                    </div>
                                <?php endif; ?>

                                <?php if ($canMakeNewLease && $hasLease): ?>
                                    <div class="notice notice-reapply">
                                        <strong><i class="fas fa-info-circle me-1"></i> Tenant can re-apply.</strong>
                                        <span style="font-size:0.78rem; opacity:0.85;">You may create a new lease agreement for this tenant.</span>
                                    </div>
                                <?php endif; ?>

                                <?php if ($hasPendingTermination): ?>
                                    <div class="notice notice-termination">
                                        <strong><i class="fas fa-exclamation-triangle me-1"></i> Tenant Requested Lease Termination</strong>
                                        <div class="reason-text">"<?= htmlspecialchars($terminationInfo['reason']) ?>"</div>
                                        <div class="d-flex gap-2 flex-wrap mt-1">
                                            <button class="btn2 btn2-red approve-termination-btn"
                                                data-lease="<?= $leaseId ?>"
                                                data-termination="<?= $terminationInfo['ID'] ?>"
                                                data-request="<?= $req['request_id'] ?>">
                                                <i class="fas fa-check"></i> Approve
                                            </button>
                                            <button class="btn2 btn2-orange reject-termination-btn"
                                                data-lease="<?= $leaseId ?>"
                                                data-termination="<?= $terminationInfo['ID'] ?>">
                                                <i class="fas fa-times"></i> Reject
                                            </button>
                                        </div>
                                    </div>
                                <?php endif; ?>

                                <!-- Action buttons -->
                                <div class="action-row">
                                    <?php if ($canMakeNewLease): ?>
                                        <a href="lease-form.php?request_id=<?= $req['request_id'] ?>&listing_id=<?= $listingID ?>&tenant_id=<?= $req['tenant_id'] ?>"
                                            class="btn2 btn2-green">
                                            <i class="fas fa-file-signature"></i>
                                            <?= $hasLease ? 'New Lease' : 'Make Lease' ?>
                                        </a>
                                        <button type="button" class="btn2 btn2-red remove-btn"
                                            data-request="<?= $req['request_id'] ?>">
                                            <i class="fas fa-trash"></i> Remove
                                        </button>

                                    <?php elseif ($leaseIsActive || $leaseIsPending): ?>
                                        <?php if (!empty($req['pdf_path'])): ?>
                                            <a href="<?= htmlspecialchars($req['pdf_path']) ?>" target="_blank" class="btn2 btn2-blue">
                                                <i class="fas fa-file-pdf"></i> View Lease
                                            </a>
                                        <?php else: ?>
                                            <a href="lease-details.php?lease_id=<?= $leaseId ?>" class="btn2 btn2-blue">
                                                <i class="fas fa-file-alt"></i> Lease Details
                                            </a>
                                        <?php endif; ?>

                                        <?php if ($leaseIsCancellable): ?>
                                            <button type="button" class="btn2 btn2-orange cancel-lease-btn"
                                                data-lease="<?= $leaseId ?>"
                                                data-request="<?= $req['request_id'] ?>">
                                                <i class="fas fa-times"></i> Cancel Lease
                                            </button>
                                        <?php endif; ?>

                                    <?php elseif (!$hasLease): ?>
                                        <button type="button" class="btn2 btn2-red remove-btn"
                                            data-request="<?= $req['request_id'] ?>">
                                            <i class="fas fa-trash"></i> Remove
                                        </button>
                                    <?php endif; ?>
                                </div>

                            </div>

                        <?php endwhile; else: ?>
                            <div class="empty-requests">
                                <i class="fas fa-users-slash"></i>
                                <p>No tenant requests yet.</p>
                            </div>
                        <?php endif; ?>

                    </div>
                </div>
            </div>

        </div>
    </div><!-- /page-wrap -->

    <script src="../js/bootstrap.bundle.min.js"></script>
    <script>
    // ════════════════════════════════════════
    // LIGHTBOX
    // ════════════════════════════════════════
    const images = <?= json_encode(
        !empty($images)
            ? array_map(fn($img) => '../LANDLORD/uploads/' . $img, $images)
            : ['../LANDLORD/uploads/placeholder.jpg']
    ) ?>;

    let lbIndex = 0;

    const overlay   = document.getElementById('lightbox');
    const lbImg     = document.getElementById('lbImg');
    const lbCounter = document.getElementById('lbCounter');
    const lbThumbs  = document.getElementById('lbThumbs');
    const lbClose   = document.getElementById('lbClose');
    const lbPrev    = document.getElementById('lbPrev');
    const lbNext    = document.getElementById('lbNext');

    // Build thumbnails
    images.forEach((src, i) => {
        const t = document.createElement('img');
        t.src = src;
        t.addEventListener('click', () => openLightbox(i));
        lbThumbs.appendChild(t);
    });

    function openLightbox(index) {
        lbIndex = index;
        updateLightbox();
        overlay.classList.add('active');
        document.body.style.overflow = 'hidden';
    }

    function closeLightbox() {
        overlay.classList.remove('active');
        document.body.style.overflow = '';
    }

    function updateLightbox() {
        lbImg.src = images[lbIndex];
        lbCounter.textContent = `${lbIndex + 1} / ${images.length}`;
        lbThumbs.querySelectorAll('img').forEach((t, i) => {
            t.classList.toggle('active', i === lbIndex);
        });
        lbPrev.style.display = images.length <= 1 ? 'none' : '';
        lbNext.style.display = images.length <= 1 ? 'none' : '';
    }

    // Open on gallery click
    document.getElementById('galleryWrap').addEventListener('click', (e) => {
        if (e.target.closest('.carousel-control-prev') || e.target.closest('.carousel-control-next')) return;
        const activeIndex = [...document.querySelectorAll('#carouselExample .carousel-item')]
            .findIndex(el => el.classList.contains('active'));
        openLightbox(activeIndex >= 0 ? activeIndex : 0);
    });

    lbClose.addEventListener('click', closeLightbox);
    overlay.addEventListener('click', (e) => { if (e.target === overlay) closeLightbox(); });

    lbPrev.addEventListener('click', (e) => {
        e.stopPropagation();
        lbIndex = (lbIndex - 1 + images.length) % images.length;
        updateLightbox();
    });

    lbNext.addEventListener('click', (e) => {
        e.stopPropagation();
        lbIndex = (lbIndex + 1) % images.length;
        updateLightbox();
    });

    // Keyboard navigation
    document.addEventListener('keydown', (e) => {
        if (!overlay.classList.contains('active')) return;
        if (e.key === 'Escape') closeLightbox();
        if (e.key === 'ArrowLeft')  { lbIndex = (lbIndex - 1 + images.length) % images.length; updateLightbox(); }
        if (e.key === 'ArrowRight') { lbIndex = (lbIndex + 1) % images.length; updateLightbox(); }
    });

    // ════════════════════════════════════════
    // TENANT REQUEST ACTIONS
    // ════════════════════════════════════════
    document.addEventListener("DOMContentLoaded", () => {

        document.querySelectorAll(".remove-btn").forEach(btn => {
            btn.addEventListener("click", () => {
                Swal.fire({
                    title: 'Remove this request?',
                    text: 'This will hide the request from your list.',
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#8D0B41',
                    cancelButtonColor: '#6c757d',
                    confirmButtonText: 'Yes, remove it'
                }).then(result => {
                    if (!result.isConfirmed) return;
                    const requestId = btn.dataset.request;
                    const card = btn.closest(".tenant-card");
                    fetch("update-request.php", {
                        method: "POST",
                        headers: { "Content-Type": "application/x-www-form-urlencoded" },
                        body: `request_id=${requestId}&action=remove`
                    })
                    .then(r => r.json())
                    .then(data => {
                        if (data.success) {
                            card.style.transition = 'opacity 0.3s';
                            card.style.opacity = '0';
                            setTimeout(() => card.remove(), 300);
                        } else {
                            Swal.fire('Error', data.message, 'error');
                        }
                    })
                    .catch(() => Swal.fire('Error', 'Something went wrong.', 'error'));
                });
            });
        });

        document.querySelectorAll(".cancel-lease-btn").forEach(btn => {
            btn.addEventListener("click", () => {
                const leaseId   = btn.dataset.lease;
                const requestId = btn.dataset.request;
                Swal.fire({
                    title: 'Cancel this Lease Agreement?',
                    html: 'The lease will be marked <b>cancelled</b> so you can create a new one for this tenant.',
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#8D0B41',
                    cancelButtonColor: '#6c757d',
                    confirmButtonText: 'Yes, cancel it'
                }).then(result => {
                    if (!result.isConfirmed) return;
                    fetch("update-lease.php", {
                        method: "POST",
                        headers: { "Content-Type": "application/x-www-form-urlencoded" },
                        body: `lease_id=${leaseId}&action=cancel`
                    })
                    .then(r => r.json())
                    .then(data => {
                        Swal.fire({
                            icon: data.success ? 'success' : 'error',
                            title: data.success ? 'Lease Cancelled' : 'Error',
                            text: data.message,
                            confirmButtonColor: '#8D0B41'
                        }).then(() => { if (data.success) location.reload(); });
                    })
                    .catch(() => Swal.fire('Error', 'Something went wrong.', 'error'));
                });
            });
        });

        document.querySelectorAll(".approve-termination-btn").forEach(btn => {
            btn.addEventListener("click", () => {
                const leaseId       = btn.dataset.lease;
                const terminationId = btn.dataset.termination;
                const requestId     = btn.dataset.request;
                Swal.fire({
                    title: 'Approve Termination?',
                    text: 'The lease will be terminated and the apartment will become available again.',
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#8D0B41',
                    cancelButtonColor: '#6c757d',
                    confirmButtonText: 'Yes, approve'
                }).then(result => {
                    if (!result.isConfirmed) return;
                    fetch("update-lease.php", {
                        method: "POST",
                        headers: { "Content-Type": "application/x-www-form-urlencoded" },
                        body: `lease_id=${leaseId}&termination_id=${terminationId}&request_id=${requestId}&action=approve_termination`
                    })
                    .then(r => r.json())
                    .then(data => {
                        Swal.fire({
                            icon: data.success ? 'success' : 'error',
                            title: data.success ? 'Termination Approved' : 'Error',
                            text: data.message,
                            confirmButtonColor: '#8D0B41'
                        }).then(() => { if (data.success) location.reload(); });
                    })
                    .catch(() => Swal.fire('Error', 'Something went wrong.', 'error'));
                });
            });
        });

        document.querySelectorAll(".reject-termination-btn").forEach(btn => {
            btn.addEventListener("click", () => {
                const terminationId = btn.dataset.termination;
                Swal.fire({
                    title: 'Reject Termination Request?',
                    text: "The tenant's request will be rejected. The lease stays active.",
                    icon: 'question',
                    showCancelButton: true,
                    confirmButtonColor: '#8D0B41',
                    cancelButtonColor: '#6c757d',
                    confirmButtonText: 'Yes, reject it'
                }).then(result => {
                    if (!result.isConfirmed) return;
                    fetch("update-lease.php", {
                        method: "POST",
                        headers: { "Content-Type": "application/x-www-form-urlencoded" },
                        body: `termination_id=${terminationId}&action=reject_termination`
                    })
                    .then(r => r.json())
                    .then(data => {
                        Swal.fire({
                            icon: data.success ? 'success' : 'error',
                            title: data.success ? 'Request Rejected' : 'Error',
                            text: data.message,
                            confirmButtonColor: '#8D0B41'
                        }).then(() => { if (data.success) location.reload(); });
                    })
                    .catch(() => Swal.fire('Error', 'Something went wrong.', 'error'));
                });
            });
        });

        // Avatar dropdown toggle
document.querySelectorAll('.avatar-trigger').forEach(avatar => {
    avatar.addEventListener('click', (e) => {
        e.stopPropagation();
        const dropdown = avatar.nextElementSibling;
        // Close all other open dropdowns first
        document.querySelectorAll('.avatar-dropdown.open').forEach(d => {
            if (d !== dropdown) d.classList.remove('open');
        });
        dropdown.classList.toggle('open');
    });
});

// Close dropdowns when clicking outside
document.addEventListener('click', () => {
    document.querySelectorAll('.avatar-dropdown.open').forEach(d => d.classList.remove('open'));
});

    });
    </script>
</body>
</html>