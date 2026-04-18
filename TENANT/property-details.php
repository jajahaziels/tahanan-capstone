<?php

ini_set('display_errors', 1);
error_reporting(E_ALL); 

require_once '../connection.php';
include '../session_auth.php';

$listingID = intval($_GET['id'] ?? $_GET['ID'] ?? 0);
if ($listingID <= 0) die("Invalid property ID.");

$sql = "
    SELECT l.*, l.ID AS listing_id,
           ld.ID AS landlord_id,
           ld.firstName AS landlord_fname,
           ld.lastName AS landlord_lname,
           ld.profilePic AS landlord_profilePic
    FROM listingtbl l
    JOIN landlordtbl ld ON l.landlord_id = ld.ID
    WHERE l.ID = ? LIMIT 1
";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $listingID);
$stmt->execute();
$res = $stmt->get_result();
if ($res->num_rows === 0) die("Property not found.");
$property = $res->fetch_assoc();
$stmt->close();

$images    = json_decode($property['images'] ?? '[]', true) ?? [];
$terms     = !empty($property['terms']) ? (json_decode($property['terms'], true) ?? []) : [];
$tenant_id = (int)($_SESSION['tenant_id'] ?? 0);

$requestStatus = null;
$requestId     = null;
if ($tenant_id > 0) {
    $stmt = $conn->prepare("SELECT id, status FROM requesttbl WHERE tenant_id = ? AND listing_id = ? ORDER BY id DESC LIMIT 1");
    $stmt->bind_param("ii", $tenant_id, $listingID);
    $stmt->execute();
    if ($row = $stmt->get_result()->fetch_assoc()) {
        $requestStatus = $row['status'];
        $requestId     = $row['id'];
    }
    $stmt->close();
}

$hasActiveLease = false;
if ($tenant_id > 0) {
    $stmt = $conn->prepare("SELECT ID FROM leasetbl WHERE tenant_id=? AND listing_id=? AND status='active' LIMIT 1");
    $stmt->bind_param("ii", $tenant_id, $listingID);
    $stmt->execute();
    $hasActiveLease = $stmt->get_result()->num_rows > 0;
    $stmt->close();
}

$hasActiveRent = false;
if ($tenant_id > 0) {
    $stmt = $conn->prepare("SELECT ID FROM renttbl WHERE tenant_id=? AND tenant_removed=0 LIMIT 1");
    $stmt->bind_param("i", $tenant_id);
    $stmt->execute();
    $hasActiveRent = $stmt->get_result()->num_rows > 0;
    $stmt->close();
}

if ($hasActiveLease || $hasActiveRent) $btnState = 'renting';
elseif ($requestStatus === 'pending')  $btnState = 'pending';
else                                   $btnState = 'apply';

$sqlOther = "
    SELECT l.ID, l.listingName, l.price, l.category, l.barangay, l.images
    FROM listingtbl l
    WHERE l.ID != ? AND l.availability = 'available'
      AND (l.barangay = ? OR l.category = ?)
    ORDER BY RAND()
    LIMIT 4
";
$stmtO = $conn->prepare($sqlOther);
$stmtO->bind_param("iss", $listingID, $property['barangay'], $property['category']);
$stmtO->execute();
$otherListings = $stmtO->get_result()->fetch_all(MYSQLI_ASSOC);
$stmtO->close();

if (count($otherListings) < 4) {
    $exclude = array_merge([$listingID], array_column($otherListings, 'ID'));
    $placeholders = implode(',', array_fill(0, count($exclude), '?'));
    $types = str_repeat('i', count($exclude));
    $stmtF = $conn->prepare("SELECT ID, listingName, price, category, barangay, images FROM listingtbl WHERE ID NOT IN ($placeholders) AND availability='available' ORDER BY RAND() LIMIT " . (4 - count($otherListings)));
    $stmtF->bind_param($types, ...$exclude);
    $stmtF->execute();
    $otherListings = array_merge($otherListings, $stmtF->get_result()->fetch_all(MYSQLI_ASSOC));
    $stmtF->close();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="shortcut icon" href="favicon.ico" type="image/x-icon">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link rel="stylesheet" href="../css/bootstrap.min.css">
    <link rel="stylesheet" href="../css/style.css?v=<?= time() ?>">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <title><?= htmlspecialchars($property['listingName']) ?> - Details</title>
    <style>
        body { background: var(--bg-alt-color); }

        .tenant-page {
            margin-top: 80px;
            padding: 0 0 80px;
        }

        .page-wrap {
            max-width: 860px;
            margin: 0 auto;
            padding: 28px 20px 0;
        }

        /* ── Top bar ── */
        .top-bar {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 22px;
        }

        .top-bar h1 {
            font-size: 1.4rem;
            font-weight: 700;
            color: var(--text-color);
            margin: 0;
        }

        .top-bar h1 span { color: var(--main-color); }

        .btn-back {
            display: inline-flex;
            align-items: center;
            gap: 7px;
            background: var(--bg-color);
            color: var(--text-color);
            border: 1.5px solid rgba(141,11,65,0.2);
            padding: 10px 22px;
            border-radius: 50px;
            font-size: 13px;
            font-weight: 600;
            cursor: pointer;
            text-decoration: none;
            transition: all 0.22s;
        }

        .btn-back:hover { background: var(--main-color); color: #fff; border-color: var(--main-color); }

        /* ── Main card ── */
        .detail-card {
            background: var(--bg-color);
            border-radius: 22px;
            border: 1px solid rgba(141,11,65,0.08);
            box-shadow: 0 4px 28px var(--shadow-color);
            overflow: hidden;
            margin-bottom: 36px;
        }

        /* ── Gallery ── */
        .gallery-wrap { position: relative; cursor: pointer; }

        /* Main hero image — tall, full-bleed, covers the full width */
        .gallery-wrap .carousel-item img {
            width: 100%;
            height: 480px;
            object-fit: cover;
            object-position: center;
            display: block;
            transition: transform 0.4s;
        }

        .gallery-wrap:hover .carousel-item.active img { transform: scale(1.015); }

        .gallery-hint {
            position: absolute;
            bottom: 14px;
            right: 14px;
            background: rgba(0,0,0,0.52);
            color: #fff;
            font-size: 11px;
            font-weight: 600;
            padding: 5px 13px;
            border-radius: 50px;
            display: flex;
            align-items: center;
            gap: 5px;
            pointer-events: none;
            z-index: 5;
            backdrop-filter: blur(3px);
        }

        .img-count {
            position: absolute;
            bottom: 14px;
            left: 14px;
            background: rgba(0,0,0,0.48);
            color: #fff;
            font-size: 11px;
            font-weight: 600;
            padding: 5px 12px;
            border-radius: 50px;
            pointer-events: none;
            z-index: 5;
        }

        /* ── Detail body ── */
        .detail-body { padding: 32px 36px; }

        /* ── Warning ── */
        .warn-box {
            background: #fff6f6;
            border: 1px solid rgba(220,53,69,0.18);
            border-left: 4px solid #dc3545;
            border-radius: 0 12px 12px 0;
            padding: 15px 20px;
            display: flex;
            align-items: flex-start;
            gap: 14px;
            margin-bottom: 24px;
        }

        .warn-icon {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: var(--main-color);
            color: #fff;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
            font-size: 15px;
        }

        .warn-text h6 { font-size: 13.5px; font-weight: 700; color: var(--main-color); margin-bottom: 3px; }
        .warn-text p  { font-size: 12.5px; color: #666; margin: 0; }

        /* ── Property header ── */
        .prop-location {
            font-size: 13px;
            color: var(--text-alt-color);
            margin-bottom: 7px;
            display: flex;
            align-items: center;
            gap: 5px;
        }

        .prop-location i { color: var(--main-color); font-size: 11px; }

        .prop-row {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            gap: 14px;
            margin-bottom: 10px;
        }

        .prop-name {
            font-size: 1.65rem;
            font-weight: 700;
            color: var(--text-color);
            flex: 1;
            line-height: 1.3;
        }

        .prop-price {
            font-size: 1.85rem;
            font-weight: 800;
            color: var(--main-color);
            margin-bottom: 24px;
        }

        .prop-price small { font-size: 13px; font-weight: 400; color: var(--text-alt-color); }

        /* ── Apply buttons ── */
        .btn-apply {
            background: var(--main-color);
            color: #fff;
            border: none;
            padding: 12px 28px;
            border-radius: 50px;
            font-size: 14px;
            font-weight: 700;
            cursor: pointer;
            white-space: nowrap;
            transition: all 0.22s;
            box-shadow: 0 4px 16px rgba(141,11,65,0.28);
        }

        .btn-apply:hover { background: #6e0932; transform: translateY(-1px); }
        .btn-apply:disabled { background: #ccc; cursor: not-allowed; box-shadow: none; transform: none; }

        .btn-cancel-apply {
            background: transparent;
            border: 1.5px solid var(--main-color);
            color: var(--main-color);
            padding: 12px 26px;
            border-radius: 50px;
            font-size: 14px;
            font-weight: 700;
            cursor: pointer;
            transition: all 0.22s;
            white-space: nowrap;
        }

        .btn-cancel-apply:hover { background: var(--main-color); color: #fff; }

        /* ── Landlord strip ── */
        .landlord-strip {
            display: flex;
            align-items: center;
            gap: 14px;
            background: var(--bg-alt-color);
            border: 1px solid rgba(141,11,65,0.1);
            border-radius: 16px;
            padding: 16px 20px;
            margin-bottom: 28px;
        }

        .ll-avatar {
            width: 52px;
            height: 52px;
            border-radius: 50%;
            background: #6a0831;
            color: #f4c0d1;
            font-size: 19px;
            font-weight: 700;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
            overflow: hidden;
        }

        .ll-avatar img { width: 52px; height: 52px; object-fit: cover; }
        .ll-name { font-size: 15px; font-weight: 700; color: var(--text-color); }
        .ll-role { font-size: 12px; color: var(--text-alt-color); }

        .ll-actions { margin-left: auto; display: flex; gap: 10px; }

        .ll-btn {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            border: 1px solid rgba(141,11,65,0.2);
            background: var(--bg-color);
            color: var(--main-color);
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            font-size: 14px;
            transition: all 0.22s;
        }

        .ll-btn:hover { background: var(--main-color); color: #fff; border-color: var(--main-color); }

        /* ── Divider ── */
        .section-divider {
            border: none;
            border-top: 1px solid rgba(141,11,65,0.08);
            margin: 26px 0;
        }

        /* ── Section label ── */
        .section-label {
            font-size: 11px;
            font-weight: 700;
            letter-spacing: 0.9px;
            text-transform: uppercase;
            color: var(--main-color);
            margin-bottom: 14px;
        }

        /* ── Info tiles ── */
        .info-tiles-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 12px;
            margin-bottom: 12px;
        }

        .info-tile {
            background: var(--bg-alt-color);
            border: 1px solid rgba(141,11,65,0.1);
            border-radius: 14px;
            padding: 16px 18px;
            display: flex;
            align-items: flex-start;
            gap: 13px;
        }

        .info-tile-full {
            margin-bottom: 0;
        }

        .info-tile-icon {
            width: 38px;
            height: 38px;
            border-radius: 10px;
            background: #fbeaf0;
            color: var(--main-color);
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
            font-size: 15px;
        }

        .info-tile-label {
            font-size: 11px;
            color: var(--text-alt-color);
            margin-bottom: 4px;
            font-weight: 600;
            letter-spacing: 0.2px;
        }

        .info-tile-value {
            font-size: 14px;
            font-weight: 700;
            color: var(--text-color);
            line-height: 1.4;
        }

        /* ── Description ── */
        .desc-text {
            font-size: 14.5px;
            color: var(--text-color);
            line-height: 1.85;
            margin-bottom: 0;
        }

        /* ── Rules ── */
        .rules-box {
            background: var(--bg-alt-color);
            border: 1px solid rgba(141,11,65,0.12);
            border-radius: 16px;
            padding: 22px;
            margin-bottom: 0;
        }

        .rule-row {
            display: flex;
            align-items: flex-start;
            gap: 13px;
            padding: 10px 0;
            border-bottom: 1px dashed rgba(141,11,65,0.1);
            font-size: 14px;
            color: var(--text-color);
            line-height: 1.6;
        }

        .rule-row:first-child { padding-top: 0; }
        .rule-row:last-child  { border-bottom: none; padding-bottom: 0; }

        .rule-dot {
            width: 8px;
            height: 8px;
            border-radius: 50%;
            background: var(--main-color);
            flex-shrink: 0;
            margin-top: 7px;
        }

        /* ── Map ── */
        #map {
            height: 300px;
            border-radius: 16px;
            overflow: hidden;
        }

        /* ════ OTHER LISTINGS — match tenant.php card style ════ */
        .section-heading {
            font-size: 1.15rem;
            font-weight: 700;
            color: var(--text-color);
            margin-bottom: 16px;
            display: flex;
            align-items: center;
            gap: 6px;
        }

        .section-heading span { color: var(--main-color); }

        /* 2-column grid so cards are wide enough to look good */
        .listings-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 22px;
            margin-bottom: 48px;
        }

        /* Card shell — matches prop-card style from tenant.php */
        .listing-card {
            background: var(--bg-color);
            border-radius: 16px;
            overflow: hidden;
            border: 1.5px solid transparent;
            box-shadow: 0 4px 20px var(--shadow-color);
            display: flex;
            flex-direction: column;
            cursor: pointer;
            text-decoration: none;
            position: relative;
            transition: transform 0.3s ease, box-shadow 0.3s ease, border-color 0.3s ease;
        }

        .listing-card::before {
            content: '';
            position: absolute;
            top: 0; left: 0;
            width: 4px;
            height: 100%;
            background: linear-gradient(to bottom, var(--main-color), transparent);
            border-radius: 16px 0 0 16px;
            opacity: 0;
            transition: opacity 0.3s ease;
            z-index: 2;
        }

        .listing-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 12px 36px var(--shadow-color);
            border-color: rgba(141,11,65,0.2);
        }

        .listing-card:hover::before { opacity: 1; }

        /* Image area — same proportions as tenant.php card-img-wrap */
        .lc-img {
            position: relative;
            overflow: hidden;
            height: 200px;
            flex-shrink: 0;
            background: var(--bg-alt-color);
        }

        .lc-img img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            object-position: center;
            transition: transform 0.5s ease;
        }

        .listing-card:hover .lc-img img { transform: scale(1.06); }

        /* Price ribbon overlay — matches tenant.php style */
        .lc-price-ribbon {
            position: absolute;
            bottom: 0; left: 0; right: 0;
            background: linear-gradient(to top, rgba(0,0,0,0.68), transparent);
            padding: 30px 14px 10px;
            color: #fff;
            font-size: 1.1rem;
            font-weight: 700;
        }

        .lc-price-ribbon small {
            font-size: 0.72rem;
            font-weight: 400;
            opacity: 0.75;
            margin-left: 2px;
        }

        /* Card body */
        .lc-body {
            padding: 16px 18px 0;
            flex: 1;
            display: flex;
            flex-direction: column;
        }

        .lc-name {
            font-size: 1rem;
            font-weight: 700;
            color: var(--text-color);
            margin-bottom: 5px;
            line-height: 1.35;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }

        .lc-loc {
            font-size: 0.78rem;
            color: var(--text-alt-color);
            display: flex;
            align-items: center;
            gap: 5px;
            margin-bottom: 12px;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }

        .lc-loc i { color: var(--main-color); flex-shrink: 0; font-size: 0.7rem; }

        /* Footer row with tag pill */
        .lc-footer {
            margin-top: auto;
            padding: 12px 18px;
            border-top: 1px solid var(--bg-alt-color);
            display: flex;
            align-items: center;
            justify-content: flex-end;
        }

        .lc-tag {
            background: var(--bg-alt-color);
            color: var(--text-alt-color);
            font-size: 0.75rem;
            font-weight: 600;
            padding: 4px 12px;
            border-radius: 50px;
            border: 1px solid rgba(141,11,65,0.1);
            display: inline-flex;
            align-items: center;
            gap: 5px;
        }

        .lc-tag i { color: var(--main-color); font-size: 0.72rem; }

        /* ════ LIGHTBOX ════ */
        .lightbox-overlay {
            display: none;
            position: fixed;
            inset: 0;
            background: rgba(0,0,0,0.93);
            z-index: 9999;
            align-items: center;
            justify-content: center;
            flex-direction: column;
        }

        .lightbox-overlay.active { display: flex; }

        .lightbox-img-wrap img {
            max-width: 92vw;
            max-height: 78vh;
            border-radius: 12px;
            object-fit: contain;
            transition: opacity 0.15s;
        }

        .lightbox-close {
            position: fixed;
            top: 18px; right: 22px;
            background: rgba(255,255,255,0.13);
            border: none; color: #fff;
            font-size: 1.5rem;
            width: 44px; height: 44px;
            border-radius: 50%;
            cursor: pointer;
            display: flex; align-items: center; justify-content: center;
            transition: background 0.2s;
            z-index: 10001;
        }

        .lightbox-close:hover { background: rgba(255,255,255,0.28); }

        .lightbox-nav {
            position: fixed;
            top: 50%; transform: translateY(-50%);
            background: rgba(255,255,255,0.13);
            border: none; color: #fff;
            font-size: 1.4rem;
            width: 48px; height: 48px;
            border-radius: 50%;
            cursor: pointer;
            display: flex; align-items: center; justify-content: center;
            transition: background 0.2s;
            z-index: 10001;
        }

        .lightbox-nav:hover { background: rgba(255,255,255,0.28); }
        .lightbox-prev { left: 14px; }
        .lightbox-next { right: 14px; }
        .lightbox-nav.hidden { display: none; }

        .lightbox-counter {
            color: rgba(255,255,255,0.7);
            font-size: 12px;
            margin-top: 13px;
            letter-spacing: 0.05em;
        }

        .lightbox-thumbnails {
            display: flex;
            gap: 8px;
            margin-top: 12px;
            flex-wrap: wrap;
            justify-content: center;
            max-width: 90vw;
        }

        .lightbox-thumbnails img {
            width: 56px; height: 42px;
            object-fit: cover;
            border-radius: 7px;
            cursor: pointer;
            opacity: 0.45;
            border: 2px solid transparent;
            transition: all 0.2s;
        }

        .lightbox-thumbnails img.active,
        .lightbox-thumbnails img:hover { opacity: 1; border-color: #fff; }

        /* ── Responsive ── */
        @media (max-width: 640px) {
            .detail-body { padding: 22px 18px; }
            .gallery-wrap .carousel-item img { height: 260px; }
            .prop-name { font-size: 1.3rem; }
            .prop-price { font-size: 1.5rem; }
            .info-tiles-grid { grid-template-columns: 1fr 1fr; }
            .listings-grid { grid-template-columns: 1fr; }
        }

        @media (max-width: 420px) {
            .info-tiles-grid { grid-template-columns: 1fr; }
        }
    </style>
</head>
<body>

<!-- Lightbox -->
<div class="lightbox-overlay" id="lightbox">
    <button class="lightbox-close" id="lightboxClose"><i class="fas fa-times"></i></button>
    <button class="lightbox-nav lightbox-prev" id="lightboxPrev"><i class="fas fa-chevron-left"></i></button>
    <button class="lightbox-nav lightbox-next" id="lightboxNext"><i class="fas fa-chevron-right"></i></button>
    <div class="lightbox-img-wrap"><img id="lightboxImg" src="" alt="Full Image"></div>
    <div class="lightbox-counter" id="lightboxCounter"></div>
    <div class="lightbox-thumbnails" id="lightboxThumbs"></div>
</div>

<?php include '../Components/tenant-header.php' ?>

<div class="tenant-page">
    <div class="page-wrap">

        <!-- Top bar -->
        <div class="top-bar">
            <h1>Property <span>Details</span></h1>
            <a class="btn-back" href="tenant.php">
                <i class="fas fa-arrow-left"></i> Back
            </a>
        </div>

        <!-- ══ Main detail card ══ -->
        <div class="detail-card">

            <!-- Gallery -->
            <div class="gallery-wrap" id="galleryWrap">
                <div id="carouselExample" class="carousel slide">
                    <div class="carousel-inner">
                        <?php if (!empty($images)):
                            foreach ($images as $i => $img): ?>
                                <div class="carousel-item <?= $i === 0 ? 'active' : '' ?>">
                                    <img src="../LANDLORD/uploads/<?= htmlspecialchars($img) ?>"
                                         alt="Property Image"
                                         data-index="<?= $i ?>"
                                         onclick="event.stopPropagation(); openLightbox(<?= $i ?>)">
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
                <?php if (count($images) > 1): ?>
                    <div class="img-count">1 / <?= count($images) ?></div>
                <?php endif; ?>
                <div class="gallery-hint"><i class="fas fa-expand"></i> Click to expand</div>
            </div>

            <!-- Body -->
            <div class="detail-body">

                <!-- Warning -->
                <?php if ($hasActiveRent || $hasActiveLease): ?>
                    <div class="warn-box">
                        <div class="warn-icon"><i class="fas fa-triangle-exclamation"></i></div>
                        <div class="warn-text">
                            <h6>Active lease detected</h6>
                            <p>You already have an active apartment. You cannot apply until your current lease ends.</p>
                        </div>
                    </div>
                <?php endif; ?>

                <!-- Location breadcrumb -->
                <div class="prop-location">
                    <i class="fas fa-map-pin"></i>
                    <?= htmlspecialchars($property['barangay']) ?>, San Pedro, Laguna
                </div>

                <!-- Name + Apply button -->
                <div class="prop-row">
                    <div class="prop-name"><?= htmlspecialchars($property['listingName']) ?></div>
                    <div id="applyBtnWrapper">
                        <?php if ($btnState === 'renting'): ?>
                            <button class="btn-apply" disabled>Already renting</button>
                        <?php elseif ($btnState === 'pending'): ?>
                            <button class="btn-cancel-apply" id="cancelApplyBtn">
                                <i class="fas fa-xmark me-1"></i> Cancel apply
                            </button>
                        <?php else: ?>
                            <button class="btn-apply" id="applyBtn">Apply</button>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Price -->
                <div class="prop-price">₱<?= number_format($property['price']) ?>.00<small> /month</small></div>

                <!-- Landlord strip -->
                <div class="landlord-strip">
                    <div class="ll-avatar">
                        <?php if (!empty($property['landlord_profilePic'])): ?>
                            <img src="../uploads/<?= htmlspecialchars($property['landlord_profilePic']) ?>" alt="Landlord">
                        <?php else: ?>
                            <?= strtoupper(substr($property['landlord_fname'], 0, 1)) ?>
                        <?php endif; ?>
                    </div>
                    <div>
                        <div class="ll-name"><?= htmlspecialchars(ucwords(strtolower($property['landlord_fname'] . ' ' . $property['landlord_lname']))) ?></div>
                        <div class="ll-role">Landlord</div>
                    </div>
                    <div class="ll-actions">
                        <button class="ll-btn" title="View profile" onclick="location.href='landlord-profile.php?landlord_id=<?= $property['landlord_id'] ?>'">
                            <i class="fas fa-user"></i>
                        </button>
                        <button class="ll-btn" title="Message landlord" onclick="contactLandlord(<?= $property['landlord_id'] ?>, <?= $property['listing_id'] ?>, '<?= htmlspecialchars(addslashes($property['listingName'])) ?>')">
                            <i class="fas fa-comment-dots"></i>
                        </button>
                    </div>
                </div>

                <hr class="section-divider">

                <!-- ── Property Info Tiles ── -->
                <div class="section-label">Property details</div>

                <div class="info-tiles-grid">
                    <div class="info-tile">
                        <div class="info-tile-icon"><i class="fas fa-house"></i></div>
                        <div>
                            <div class="info-tile-label">Type</div>
                            <div class="info-tile-value"><?= htmlspecialchars($property['category']) ?></div>
                        </div>
                    </div>
                    <div class="info-tile">
                        <div class="info-tile-icon"><i class="fas fa-bed"></i></div>
                        <div>
                            <div class="info-tile-label">Bedrooms</div>
                            <div class="info-tile-value"><?= htmlspecialchars($property['rooms']) ?> Bedroom(s)</div>
                        </div>
                    </div>
                    <div class="info-tile">
                        <div class="info-tile-icon"><i class="fas fa-map-pin"></i></div>
                        <div>
                            <div class="info-tile-label">Barangay</div>
                            <div class="info-tile-value"><?= htmlspecialchars($property['barangay']) ?></div>
                        </div>
                    </div>
                </div>

                <!-- Full address — full width row -->
                <div class="info-tile info-tile-full" style="margin-top: 12px;">
                    <div class="info-tile-icon"><i class="fas fa-location-dot"></i></div>
                    <div>
                        <div class="info-tile-label">Full address</div>
                        <div class="info-tile-value"><?= htmlspecialchars($property['address']) ?>, <?= htmlspecialchars($property['barangay']) ?>, San Pedro, Laguna</div>
                    </div>
                </div>

                <hr class="section-divider">

                <!-- About -->
                <div class="section-label">About this property</div>
                <div class="desc-text"><?= nl2br(htmlspecialchars($property['listingDesc'] ?? 'No description available.')) ?></div>

                <!-- House rules -->
                <?php if (!empty($terms)): ?>
                    <hr class="section-divider">
                    <div class="section-label">House rules &amp; terms</div>
                    <div class="rules-box">
                        <?php foreach ($terms as $rule): ?>
                            <div class="rule-row">
                                <div class="rule-dot"></div>
                                <?= htmlspecialchars($rule) ?>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>

                <hr class="section-divider">

                <!-- Map -->
                <div class="section-label">Location</div>
                <div id="map"></div>

            </div>
        </div>

        <!-- ══ Other listings ══ -->
        <?php if (!empty($otherListings)): ?>
            <div class="section-heading">
                Other <span>listings</span> you might like
            </div>
            <div class="listings-grid">
                <?php foreach ($otherListings as $ol):
                    $olImages = json_decode($ol['images'] ?? '[]', true) ?? [];
                    $olThumb  = !empty($olImages) ? '../LANDLORD/uploads/' . htmlspecialchars($olImages[0]) : '../LANDLORD/uploads/placeholder.jpg';
                ?>
                    <a href="property-details.php?ID=<?= $ol['ID'] ?>" class="listing-card">

                        <!-- Image with price ribbon overlay -->
                        <div class="lc-img">
                            <img src="<?= $olThumb ?>" alt="<?= htmlspecialchars($ol['listingName']) ?>"
                                 onerror="this.src='../img/house1.jpeg'">
                            <div class="lc-price-ribbon">
                                ₱<?= number_format($ol['price']) ?><small>/mo</small>
                            </div>
                        </div>

                        <!-- Body -->
                        <div class="lc-body">
                            <div class="lc-name"><?= htmlspecialchars($ol['listingName']) ?></div>
                            <div class="lc-loc">
                                <i class="fas fa-map-marker-alt"></i>
                                <?= htmlspecialchars($ol['barangay']) ?>, San Pedro, Laguna
                            </div>
                        </div>

                        <!-- Footer pill -->
                        <div class="lc-footer">
                            <span class="lc-tag">
                                <i class="fas fa-building"></i>
                                <?= htmlspecialchars($ol['category']) ?>
                            </span>
                        </div>

                    </a>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

    </div>
</div>

<?php include '../Components/footer.php' ?>

<script src="../js/script.js" defer></script>
<script src="../js/bootstrap.bundle.min.js"></script>
<script src="https://unpkg.com/scrollreveal"></script>
<script src="../js/contact-landlord.js"></script>
<script src="https://maps.googleapis.com/maps/api/js?key=&libraries=places&callback=initMap" async defer></script>

<script>
// ── Carousel counter ──
document.getElementById('carouselExample').addEventListener('slid.bs.carousel', (e) => {
    const countEl = document.querySelector('.img-count');
    if (countEl) countEl.textContent = (e.to + 1) + ' / <?= count($images) ?>';
    if (!lightbox.classList.contains('active')) currentIdx = e.to;
});

// ── Lightbox ──
const lightboxImages = <?= json_encode(array_values(array_map(fn($img) => '../LANDLORD/uploads/' . $img, $images))) ?>;
let currentIdx  = 0;
let thumbsBuilt = false;

const lightbox       = document.getElementById('lightbox');
const lightboxImg    = document.getElementById('lightboxImg');
const lightboxCtr    = document.getElementById('lightboxCounter');
const lightboxPrev   = document.getElementById('lightboxPrev');
const lightboxNext   = document.getElementById('lightboxNext');
const lightboxThumbs = document.getElementById('lightboxThumbs');

function pauseCarousel() {
    bootstrap.Carousel.getOrCreateInstance(document.getElementById('carouselExample')).pause();
}

function resumeCarousel() {
    const carouselEl = document.getElementById('carouselExample');
    const c = bootstrap.Carousel.getOrCreateInstance(carouselEl);
    carouselEl.querySelectorAll('.carousel-item').forEach((item, i) => {
        item.classList.toggle('active', i === currentIdx);
    });
    c.cycle();
}

function openLightbox(index) {
    if (!lightboxImages.length) return;
    currentIdx = index;
    lightbox.classList.add('active');
    document.body.style.overflow = 'hidden';
    if (lightboxImages.length <= 1) {
        lightboxPrev.classList.add('hidden');
        lightboxNext.classList.add('hidden');
    }
    if (!thumbsBuilt) buildThumbs();
    renderLightbox();
    pauseCarousel();
}

function closeLightbox() {
    lightbox.classList.remove('active');
    document.body.style.overflow = '';
    resumeCarousel();
}

function lightboxNav(dir) {
    currentIdx = (currentIdx + dir + lightboxImages.length) % lightboxImages.length;
    renderLightbox();
}

function renderLightbox() {
    lightboxImg.style.opacity = '0';
    setTimeout(() => { lightboxImg.src = lightboxImages[currentIdx]; lightboxImg.style.opacity = '1'; }, 150);
    lightboxCtr.textContent = (currentIdx + 1) + ' / ' + lightboxImages.length;
    document.querySelectorAll('#lightboxThumbs img').forEach((t, i) => t.classList.toggle('active', i === currentIdx));
}

function buildThumbs() {
    lightboxImages.forEach((src, i) => {
        const img = document.createElement('img');
        img.src = src;
        if (i === currentIdx) img.classList.add('active');
        img.addEventListener('click', (e) => { e.stopPropagation(); currentIdx = i; renderLightbox(); });
        lightboxThumbs.appendChild(img);
    });
    thumbsBuilt = true;
}

lightbox.addEventListener('click', (e) => { if (e.target === lightbox) closeLightbox(); });
document.getElementById('lightboxClose').addEventListener('click', closeLightbox);
lightboxPrev.addEventListener('click', (e) => { e.stopPropagation(); lightboxNav(-1); });
lightboxNext.addEventListener('click', (e) => { e.stopPropagation(); lightboxNav(1); });
document.addEventListener('keydown', (e) => {
    if (!lightbox.classList.contains('active')) return;
    if (e.key === 'ArrowRight') lightboxNav(1);
    if (e.key === 'ArrowLeft')  lightboxNav(-1);
    if (e.key === 'Escape')     closeLightbox();
});
</script>

<script>
// ════════════════════════════════════════
// APPLY — SweetAlert2
// ════════════════════════════════════════
const LISTING_ID   = <?= (int)$property['listing_id'] ?>;
const LISTING_NAME = <?= json_encode($property['listingName']) ?>;

function attachApplyBtn() {
    const btn = document.getElementById('applyBtn');
    if (!btn) return;
    btn.addEventListener('click', () => {
        Swal.fire({
            title: 'Confirm Application',
            html: `You are about to apply for:<br><strong style="color:#8D0B41">${LISTING_NAME}</strong>`,
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: '#8D0B41',
            cancelButtonColor: '#6c757d',
            confirmButtonText: '<i class="fas fa-file-signature"></i> Yes, Apply',
            cancelButtonText: 'Cancel'
        }).then(result => {
            if (!result.isConfirmed) return;

            Swal.fire({ title: 'Submitting…', allowOutsideClick: false, didOpen: () => Swal.showLoading() });

            const fd = new FormData();
            fd.append('listing_id', LISTING_ID);

            fetch('apply.php', { method: 'POST', body: fd })
                .then(async res => {
                    const text = await res.text();
                    try { return JSON.parse(text); }
                    catch { return res.ok ? { success: true } : { success: false, message: text.substring(0, 120) }; }
                })
                .then(data => {
                    if (data.success) {
                        document.getElementById('applyBtnWrapper').innerHTML = `
                            <button class="btn-cancel-apply" id="cancelApplyBtn">
                                <i class="fas fa-xmark me-1"></i> Cancel apply
                            </button>`;
                        attachCancelBtn();
                        Swal.fire({
                            icon: 'success',
                            title: 'Application Submitted!',
                            html: `Your application for <strong>${LISTING_NAME}</strong> has been sent. The landlord will review it shortly.`,
                            confirmButtonColor: '#8D0B41',
                            confirmButtonText: 'Got it'
                        });
                    } else {
                        Swal.fire({ icon: 'error', title: 'Application Failed', text: data.message || 'Something went wrong. Please try again.', confirmButtonColor: '#8D0B41' });
                    }
                })
                .catch(() => {
                    Swal.fire({ icon: 'error', title: 'Network Error', text: 'Could not reach the server. Please check your connection.', confirmButtonColor: '#8D0B41' });
                });
        });
    });
}

// ════════════════════════════════════════
// CANCEL — SweetAlert2
// ════════════════════════════════════════
function attachCancelBtn() {
    const btn = document.getElementById('cancelApplyBtn');
    if (!btn) return;
    btn.addEventListener('click', () => {
        Swal.fire({
            title: 'Cancel Application?',
            html: `Are you sure you want to cancel your application for:<br><strong style="color:#8D0B41">${LISTING_NAME}</strong>`,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#8D0B41',
            cancelButtonColor: '#6c757d',
            confirmButtonText: 'Yes, Cancel It',
            cancelButtonText: 'No, Keep It'
        }).then(result => {
            if (!result.isConfirmed) return;

            Swal.fire({ title: 'Cancelling…', allowOutsideClick: false, didOpen: () => Swal.showLoading() });

            fetch('cancel-apply.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: `listing_id=${LISTING_ID}`
            })
            .then(async res => {
                const text = await res.text();
                try { return JSON.parse(text); }
                catch { return res.ok ? { success: true } : { success: false, message: text.substring(0, 120) }; }
            })
            .then(data => {
                if (data.success) {
                    document.getElementById('applyBtnWrapper').innerHTML = `
                        <button class="btn-apply" id="applyBtn">Apply</button>`;
                    attachApplyBtn();
                    Swal.fire({
                        icon: 'success',
                        title: 'Application Cancelled',
                        text: 'Your application has been successfully cancelled. You can apply again anytime.',
                        confirmButtonColor: '#8D0B41'
                    });
                } else {
                    Swal.fire({ icon: 'error', title: 'Cancellation Failed', text: data.message || 'Could not cancel your application. Please try again.', confirmButtonColor: '#8D0B41' });
                }
            })
            .catch(() => {
                Swal.fire({ icon: 'error', title: 'Network Error', text: 'Could not reach the server. Please check your connection.', confirmButtonColor: '#8D0B41' });
            });
        });
    });
}

attachApplyBtn();
attachCancelBtn();
</script>

<script>
function initMap() {
    const lat = <?= $property['latitude']  ?: 14.3647 ?>;
    const lng = <?= $property['longitude'] ?: 121.0556 ?>;
    const loc = { lat, lng };

    const map = new google.maps.Map(document.getElementById("map"), {
        center: loc, zoom: 15,
        mapTypeControl: false, streetViewControl: false, fullscreenControl: true
    });

    new google.maps.Marker({
        position: loc, map,
        title: "<?= htmlspecialchars($property['listingName']) ?>",
        icon: "https://maps.google.com/mapfiles/ms/icons/red-dot.png"
    });

    const service    = new google.maps.places.PlacesService(map);
    const infoWindow = new google.maps.InfoWindow();

    function getDistanceKM(lat1, lng1, lat2, lng2) {
        const R = 6371, dLat = (lat2-lat1)*Math.PI/180, dLng = (lng2-lng1)*Math.PI/180;
        const a = Math.sin(dLat/2)**2 + Math.cos(lat1*Math.PI/180)*Math.cos(lat2*Math.PI/180)*Math.sin(dLng/2)**2;
        const d = R * 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1-a));
        return d < 1 ? `${Math.round(d*1000)} meters` : `${d.toFixed(2)} km`;
    }

    [{ type: "hospital", icon: "https://maps.google.com/mapfiles/ms/icons/hospitals.png", emoji: "🏥" },
     { keyword: "evacuation center", icon: "https://maps.google.com/mapfiles/ms/icons/caution.png", emoji: "🚨" }]
    .forEach(({ type, keyword, icon, emoji }) => {
        service.nearbySearch({ location: loc, radius: type ? 3000 : 5000, ...(type ? { type } : { keyword }) }, (results, status) => {
            if (status !== google.maps.places.PlacesServiceStatus.OK) return;
            results.forEach(place => {
                const dist = getDistanceKM(lat, lng, place.geometry.location.lat(), place.geometry.location.lng());
                const m = new google.maps.Marker({ position: place.geometry.location, map, title: place.name, icon });
                m.addListener("click", () => {
                    infoWindow.setContent(`<strong>${emoji} ${place.name}</strong><br>Distance: ${dist}`);
                    infoWindow.open(map, m);
                });
            });
        });
    });
}
</script>

</body>
</html>