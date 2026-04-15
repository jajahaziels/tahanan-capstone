<?php
require_once '../connection.php';
include '../session_auth.php';

$listingID = intval($_GET['id'] ?? $_GET['ID'] ?? 0);
if ($listingID <= 0)
    die("Invalid property ID.");

$sql = "
SELECT 
    l.*,
    l.ID AS listing_id,
    ld.ID          AS landlord_id,
    ld.firstName   AS landlord_fname,
    ld.lastName    AS landlord_lname,
    ld.profilePic  AS landlord_profilePic
FROM listingtbl l
JOIN landlordtbl ld ON l.landlord_id = ld.ID
WHERE l.ID = ?
LIMIT 1
";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $listingID);
$stmt->execute();
$res = $stmt->get_result();
if ($res->num_rows === 0) die("Property not found.");
$property = $res->fetch_assoc();
$stmt->close();

$images    = json_decode($property['images'] ?? '[]', true) ?? [];
$terms     = isset($property['terms']) && !empty($property['terms'])
             ? (json_decode($property['terms'], true) ?? [])
             : [];
$tenant_id = (int) ($_SESSION['tenant_id'] ?? 0);

$requestStatus = null;
$requestId     = null;

if ($tenant_id > 0) {
    $checkSql = "
        SELECT id, status FROM requesttbl
        WHERE tenant_id = ? AND listing_id = ?
        ORDER BY id DESC LIMIT 1
    ";
    $stmt = $conn->prepare($checkSql);
    $stmt->bind_param("ii", $tenant_id, $listingID);
    $stmt->execute();
    $r = $stmt->get_result();
    if ($row = $r->fetch_assoc()) {
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

if ($hasActiveLease || $hasActiveRent) {
    $btnState = 'renting';
} elseif ($requestStatus === 'pending') {
    $btnState = 'pending';
} elseif ($requestStatus === 'approved' && !$hasActiveLease) {
    $btnState = 'apply';
} else {
    $btnState = 'apply';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="shortcut icon" href="favicon.ico" type="image/x-icon">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/7.0.1/css/all.min.css"
        crossorigin="anonymous" referrerpolicy="no-referrer" />
    <link rel="stylesheet" href="../css/bootstrap.min.css">
    <link rel="stylesheet" href="../css/style.css?v=<?= time(); ?>">
    <title><?= htmlspecialchars($property['listingName']); ?> - Details</title>

    <style>
        .tenant-page { margin-top: 50px !important; }

        .prorperty-details {
            background-color: var(--bg-color);
            padding: 30px;
            border-radius: 20px;
            box-shadow: 2px 2px 10px rgba(0,0,0,0.165);
        }

        .back-button { position: fixed; margin-top: 160px; }
        .price { font-size: 3rem; }

        .avatar {
            width: 60px !important; height: 60px !important;
            border-radius: 50%;
            background: var(--main-color);
            color: var(--bg-color);
            display: flex; align-items: center; justify-content: center;
            font-weight: bold; font-size: 20px;
        }
        .avatar img { width: 60px; height: 60px; border-radius: 50%; object-fit: cover; }

        #map { height: 400px; padding: 0; margin: auto; }

        /* ── Carousel ── */
        .carousel-inner { height: 420px !important; }
        #carouselExample { max-width: 500px !important; margin: 0 auto !important; }
        #carouselExample img {
            height: 400px !important;
            object-fit: cover !important;
            border-radius: 20px !important;
            cursor: zoom-in;
            transition: transform 0.2s ease;
        }
        #carouselExample img:hover { transform: scale(1.01); }

        .rent-warning-card {
            background: #fff6f6;
            border-radius: 15px;
            padding: 15px 18px;
            box-shadow: 0 3px 8px rgba(0,0,0,0.08);
        }
        .warning-icon {
            width: 40px; height: 40px; border-radius: 50%;
            background: var(--main-color); color: white;
            display: flex; align-items: center; justify-content: center;
            font-size: 18px; margin-right: 12px;
        }
        .warning-text h6 { font-weight: 600; color: var(--main-color); }
        .warning-text p  { font-size: 0.9rem; color: #555; }

        .btn-cancel-apply {
            background: transparent;
            border: 2px solid var(--main-color);
            color: var(--main-color);
            border-radius: 25px;
            padding: 8px 20px;
            font-weight: 600; font-size: 0.9rem;
            transition: all 0.3s ease;
            cursor: pointer;
        }
        .btn-cancel-apply:hover { background: var(--main-color); color: #fff; }

        /* ── House Rules Card ── */
        .house-rules-card {
            background: #fff8f0;
            border: 1px solid #ffe0b2;
            border-radius: 14px;
            padding: 20px 22px;
            margin-top: 24px;
        }

        .house-rules-card h5 {
            color: var(--main-color);
            font-weight: 700;
            margin-bottom: 14px;
            font-size: 1.05rem;
        }

        .rules-list {
            list-style: none;
            padding: 0;
            margin: 0;
        }

        .rules-list li {
            display: flex;
            align-items: flex-start;
            gap: 10px;
            padding: 9px 0;
            border-bottom: 1px dashed #f0d9c0;
            font-size: 0.95rem;
            color: #444;
        }

        .rules-list li:last-child {
            border-bottom: none;
            padding-bottom: 0;
        }

        .rules-list li:first-child {
            padding-top: 0;
        }

        .rule-icon {
            color: var(--main-color);
            margin-top: 2px;
            flex-shrink: 0;
            font-size: 0.9rem;
        }

        /* ── Lightbox ── */
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

        .lightbox-img-wrap {
            display: flex;
            align-items: center;
            justify-content: center;
            max-width: 92vw;
            max-height: 78vh;
        }
        .lightbox-img-wrap img {
            max-width: 92vw;
            max-height: 78vh;
            border-radius: 12px;
            object-fit: contain;
            box-shadow: 0 8px 40px rgba(0,0,0,0.6);
            user-select: none;
            transition: opacity 0.18s ease;
        }

        .lightbox-close {
            position: fixed;
            top: 18px; right: 22px;
            background: rgba(255,255,255,0.13);
            border: none; color: #fff;
            font-size: 1.7rem;
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
            font-size: 1.6rem;
            width: 50px; height: 50px;
            border-radius: 50%;
            cursor: pointer;
            display: flex; align-items: center; justify-content: center;
            transition: background 0.2s;
            z-index: 10001;
        }
        .lightbox-nav:hover { background: rgba(255,255,255,0.28); }
        .lightbox-prev { left: 16px; }
        .lightbox-next { right: 16px; }
        .lightbox-nav.hidden { display: none; }

        .lightbox-counter {
            color: rgba(255,255,255,0.75);
            font-size: 0.85rem;
            margin-top: 12px;
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
            width: 54px; height: 40px;
            object-fit: cover;
            border-radius: 6px;
            cursor: pointer;
            opacity: 0.5;
            border: 2px solid transparent;
            transition: all 0.2s;
        }
        .lightbox-thumbnails img.active,
        .lightbox-thumbnails img:hover {
            opacity: 1;
            border-color: #fff;
        }

        #applyBtnWrapper {
            display: flex;
            align-items: center;
        }
    </style>
</head>

<!-- Apply Confirm Modal -->
<div class="modal fade" id="applyConfirmModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Confirm Application</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body text-center">
                Are you sure you want to apply for
                <strong><?= htmlspecialchars($property['listingName']); ?></strong>?
            </div>
            <div class="modal-footer justify-content-center">
                <button type="button" class="main-button" data-bs-dismiss="modal">Cancel</button>
                <form action="apply.php" method="POST">
                    <input type="hidden" name="listing_id" value="<?= $property['listing_id']; ?>">
                    <button type="submit" class="main-button">Yes, Apply</button>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Cancel Apply Confirm Modal -->
<div class="modal fade" id="cancelApplyModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Cancel Application</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body text-center">
                Are you sure you want to <strong>cancel</strong> your application for
                <strong><?= htmlspecialchars($property['listingName']); ?></strong>?
            </div>
            <div class="modal-footer justify-content-center">
                <button type="button" class="main-button" data-bs-dismiss="modal">No, Keep It</button>
                <button type="button" class="main-button" id="confirmCancelApply">Yes, Cancel</button>
            </div>
        </div>
    </div>
</div>

<!-- Lightbox -->
<div class="lightbox-overlay" id="lightbox">
    <button class="lightbox-close" id="lightboxClose"><i class="fa-solid fa-xmark"></i></button>
    <button class="lightbox-nav lightbox-prev" id="lightboxPrev"><i class="fa-solid fa-chevron-left"></i></button>
    <button class="lightbox-nav lightbox-next" id="lightboxNext"><i class="fa-solid fa-chevron-right"></i></button>
    <div class="lightbox-img-wrap">
        <img id="lightboxImg" src="" alt="Full Image">
    </div>
    <div class="lightbox-counter" id="lightboxCounter"></div>
    <div class="lightbox-thumbnails" id="lightboxThumbs"></div>
</div>

<body>
    <?php include '../Components/tenant-header.php' ?>

    <div class="tenant-page">
        <div class="container m-auto">
            <div class="d-flex justify-content-start align-items-center">
                <button class="main-button back-button" onclick="location.href='tenant.php'">Back</button>
            </div>
        </div>

        <div class="row justify-content-center align-items-center mt-5">
            <div class="col-lg-6 p-3">
                <div class="prorperty-details">

                    <!-- Carousel -->
                    <div id="carouselExample" class="carousel slide mb-4">
                        <div class="carousel-inner">
                            <?php if (!empty($images)): ?>
                                <?php foreach ($images as $index => $img): ?>
                                    <div class="carousel-item <?= $index === 0 ? 'active' : '' ?>">
                                        <div class="row justify-content-center">
                                            <div class="col-lg-12">
                                                <img src="../LANDLORD/uploads/<?= htmlspecialchars($img); ?>"
                                                    class="d-block w-100"
                                                    style="max-height:400px; object-fit:cover;"
                                                    alt="Property Image"
                                                    data-index="<?= $index ?>"
                                                    onclick="event.stopPropagation(); openLightbox(<?= $index ?>)">
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <div class="carousel-item active">
                                    <div class="row justify-content-center">
                                        <div class="col-lg-12">
                                            <img src="../LANDLORD/uploads/placeholder.jpg"
                                                class="d-block w-100"
                                                style="max-height:400px; object-fit:cover;"
                                                alt="No Image">
                                        </div>
                                    </div>
                                </div>
                            <?php endif; ?>
                        </div>
                        <button class="carousel-control-prev" type="button" data-bs-target="#carouselExample" data-bs-slide="prev">
                            <span class="carousel-control-prev-icon" aria-hidden="true"></span>
                        </button>
                        <button class="carousel-control-next" type="button" data-bs-target="#carouselExample" data-bs-slide="next">
                            <span class="carousel-control-next-icon" aria-hidden="true"></span>
                        </button>
                    </div>

                    <!-- Active Rent Warning -->
                    <?php if ($hasActiveRent || $hasActiveLease): ?>
                        <div class="rent-warning-card mt-3">
                            <div class="d-flex align-items-center">
                                <div class="warning-icon">
                                    <i class="fa-solid fa-triangle-exclamation"></i>
                                </div>
                                <div class="warning-text">
                                    <h6 class="mb-1">Active Lease Detected</h6>
                                    <p class="mb-1">
                                        You already have an active apartment. You cannot apply for another property
                                        until your current lease ends.
                                    </p>
                                </div>
                            </div>
                        </div>
                    <?php endif; ?>

                    <!-- Property Header + Apply Button -->
                    <p class="mb-0 mt-4"><?= htmlspecialchars($property['barangay'] ?? ''); ?>, San Pedro, Laguna</p>
                    <div class="d-flex justify-content-between align-items-center">
                        <h4 class="mb-0 mt-0"><?= htmlspecialchars($property['listingName']); ?></h4>

                        <div id="applyBtnWrapper" class="mx-5">
                            <?php if ($btnState === 'renting'): ?>
                                <button class="main-button" disabled>Already Renting</button>
                            <?php elseif ($btnState === 'pending'): ?>
                                <button class="btn-cancel-apply"
                                    data-bs-toggle="modal"
                                    data-bs-target="#cancelApplyModal">
                                    <i class="fa-solid fa-xmark me-1"></i> Cancel Apply
                                </button>
                            <?php else: ?>
                                <button class="main-button"
                                    data-bs-toggle="modal"
                                    data-bs-target="#applyConfirmModal">
                                    Apply
                                </button>
                            <?php endif; ?>
                        </div>
                    </div>

                    <h2 class="price">
                        ₱ <?= number_format($property['price']); ?>.00
                        <small class="text-muted fs-5">/month</small>
                    </h2>

                    <!-- Landlord Info -->
                    <div class="d-flex align-items-center p-2 border rounded mb-4 mt-4">
                        <div class="avatar me-3">
                            <?php if (!empty($property['landlord_profilePic'])): ?>
                                <img src="../uploads/<?= htmlspecialchars($property['landlord_profilePic']); ?>" alt="Profile">
                            <?php else: ?>
                                <div class="landlord-info">
                                    <?= strtoupper(substr($property['landlord_fname'], 0, 1)); ?>
                                </div>
                            <?php endif; ?>
                        </div>
                        <div class="info flex-grow-1 mt-2">
                            <h1 class="mb-0">
                                <?= htmlspecialchars(ucwords(strtolower($property['landlord_fname'] . ' ' . $property['landlord_lname']))); ?>
                            </h1>
                            <p class="text-muted">Landlord</p>
                        </div>
                        <div class="d-flex">
                            <button class="small-button"
                                onclick="window.location.href='landlord-profile.php?landlord_id=<?= $property['landlord_id']; ?>'">
                                <i class="fa-solid fa-user"></i>
                            </button>
                            <button class="small-button mx-3"
                                onclick="contactLandlord(<?= $property['landlord_id']; ?>, <?= $property['listing_id']; ?>, '<?= htmlspecialchars(addslashes($property['listingName'])); ?>')">
                                <i class="fas fa-comment-dots"></i>
                            </button>
                        </div>
                    </div>

                    <!-- Property Description -->
                    <h3>Property Description</h3>
                    <p><?= nl2br(htmlspecialchars($property['listingDesc'] ?? "No description available.")); ?></p>
                    <ul>
                        <li><strong>Address:</strong> <?= htmlspecialchars($property['address']); ?>, <?= htmlspecialchars($property['barangay']); ?>, San Pedro, Laguna</li>
                        <li><strong>Category:</strong> <?= htmlspecialchars($property['category']); ?></li>
                        <li><strong>Rooms:</strong> <?= htmlspecialchars($property['rooms']); ?> Bedroom(s)</li>
                    </ul>

                    <!-- ── HOUSE RULES CARD ── -->
                    <?php if (!empty($terms)): ?>
                        <div class="house-rules-card">
                            <h5>
                                <i class="fa-solid fa-clipboard-list me-2"></i>House Rules &amp; Terms
                            </h5>
                            <ul class="rules-list">
                                <?php foreach ($terms as $rule): ?>
                                    <li>
                                        <i class="fa-solid fa-circle-check rule-icon"></i>
                                        <?= htmlspecialchars($rule) ?>
                                    </li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    <?php endif; ?>

                    <!-- Map -->
                    <div id="map" class="mt-4"></div>
                </div>
            </div>
        </div>
    </div>

    <?php include '../Components/footer.php' ?>

    <script src="../js/script.js" defer></script>
    <script src="../js/bootstrap.bundle.min.js"></script>
    <script src="https://unpkg.com/scrollreveal"></script>
    <script src="../js/contact-landlord.js"></script>
    <script src="https://maps.googleapis.com/maps/api/js?key=&callback=initMap" async defer></script>

    <!-- Lightbox -->
    <script>
        const lightboxImages = <?= json_encode(array_values(array_map(
            fn($img) => '../LANDLORD/uploads/' . $img,
            $images
        ))) ?>;

        let currentIdx = 0;
        let thumbsBuilt = false;

        const lightbox       = document.getElementById('lightbox');
        const lightboxImg    = document.getElementById('lightboxImg');
        const lightboxCtr    = document.getElementById('lightboxCounter');
        const lightboxPrev   = document.getElementById('lightboxPrev');
        const lightboxNext   = document.getElementById('lightboxNext');
        const lightboxThumbs = document.getElementById('lightboxThumbs');

        // ── FIX: Pause carousel transitions while lightbox is open ──
        function pauseCarousel() {
            const carouselEl = document.getElementById('carouselExample');
            const carouselInstance = bootstrap.Carousel.getOrCreateInstance(carouselEl);
            carouselInstance.pause();
            // Disable slide animation to prevent blink behind lightbox
            carouselEl.querySelectorAll('.carousel-item').forEach(item => {
                item.style.transition = 'none';
            });
        }

        // ── FIX: Resume carousel and sync to current lightbox index on close ──
        function resumeCarousel() {
            const carouselEl = document.getElementById('carouselExample');
            const carouselInstance = bootstrap.Carousel.getOrCreateInstance(carouselEl);
            // Re-enable transitions
            carouselEl.querySelectorAll('.carousel-item').forEach(item => {
                item.style.transition = '';
            });
            // Silently jump carousel to the last viewed image without animation
            if (lightboxImages.length > 1) {
                // Manually set active class without triggering slide event
                const items = carouselEl.querySelectorAll('.carousel-item');
                items.forEach((item, i) => {
                    item.classList.toggle('active', i === currentIdx);
                });
            }
            carouselInstance.cycle();
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
            // ── FIX: Stop carousel from animating behind the lightbox ──
            pauseCarousel();
        }

        function closeLightbox() {
            lightbox.classList.remove('active');
            document.body.style.overflow = '';
            // ── FIX: Sync carousel position silently then resume ──
            resumeCarousel();
        }

        function lightboxNav(dir) {
            currentIdx = (currentIdx + dir + lightboxImages.length) % lightboxImages.length;
            renderLightbox();
        }

        function renderLightbox() {
            lightboxImg.style.opacity = '0';
            setTimeout(() => {
                lightboxImg.src = lightboxImages[currentIdx];
                lightboxImg.style.opacity = '1';
            }, 160);
            lightboxCtr.textContent = (currentIdx + 1) + ' / ' + lightboxImages.length;

            // ── FIX: Do NOT sync carousel while lightbox is open — causes blink ──
            // Carousel will be synced silently when lightbox closes via resumeCarousel()

            document.querySelectorAll('#lightboxThumbs img').forEach((t, i) => {
                t.classList.toggle('active', i === currentIdx);
            });
        }

        function buildThumbs() {
            lightboxImages.forEach((src, i) => {
                const img = document.createElement('img');
                img.src = src;
                img.alt = 'Thumb ' + (i + 1);
                if (i === currentIdx) img.classList.add('active');
                img.addEventListener('click', (e) => {
                    e.stopPropagation();
                    currentIdx = i;
                    renderLightbox();
                });
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

        // ── FIX: Only update currentIdx from carousel when lightbox is NOT open ──
        document.getElementById('carouselExample').addEventListener('slid.bs.carousel', (e) => {
            if (lightbox.classList.contains('active')) return; // ignore while lightbox is open
            currentIdx = e.to;
        });
    </script>

    <!-- Cancel Apply -->
    <script>
        document.getElementById('confirmCancelApply')?.addEventListener('click', function () {
            const listingId = <?= (int)$property['listing_id'] ?>;
            fetch('cancel-apply.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: `listing_id=${listingId}`
            })
            .then(res => res.json())
            .then(data => {
                bootstrap.Modal.getInstance(
                    document.getElementById('cancelApplyModal')
                ).hide();
                if (data.success) {
                    document.getElementById('applyBtnWrapper').innerHTML = `
                        <button class="main-button"
                            data-bs-toggle="modal"
                            data-bs-target="#applyConfirmModal">
                            Apply
                        </button>`;
                } else {
                    alert(data.message || 'Could not cancel application.');
                }
            })
            .catch(() => alert('Network error. Please try again.'));
        });
    </script>

    <!-- Map -->
    <script>
        function initMap() {
            const lat = <?= $property['latitude']  ?: 14.3647 ?>;
            const lng = <?= $property['longitude'] ?: 121.0556 ?>;
            const apartmentLocation = { lat, lng };

            const map = new google.maps.Map(document.getElementById("map"), {
                center: apartmentLocation,
                zoom: 15,
                mapTypeControl: false,
                streetViewControl: false,
                fullscreenControl: true
            });

            new google.maps.Marker({
                position: apartmentLocation,
                map,
                title: "<?= htmlspecialchars($property['listingName']); ?>",
                icon: "https://maps.google.com/mapfiles/ms/icons/red-dot.png"
            });

            const service    = new google.maps.places.PlacesService(map);
            const infoWindow = new google.maps.InfoWindow();

            function getDistanceKM(lat1, lng1, lat2, lng2) {
                const R    = 6371;
                const dLat = (lat2 - lat1) * Math.PI / 180;
                const dLng = (lng2 - lng1) * Math.PI / 180;
                const a    =
                    Math.sin(dLat/2) * Math.sin(dLat/2) +
                    Math.cos(lat1 * Math.PI/180) *
                    Math.cos(lat2 * Math.PI/180) *
                    Math.sin(dLng/2) * Math.sin(dLng/2);
                const c        = 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1 - a));
                const distance = R * c;
                return distance < 1
                    ? `${Math.round(distance * 1000)} meters`
                    : `${distance.toFixed(2)} km`;
            }

            service.nearbySearch({
                location: apartmentLocation, radius: 3000, type: "hospital"
            }, (results, status) => {
                if (status === google.maps.places.PlacesServiceStatus.OK) {
                    results.forEach(place => {
                        const distance = getDistanceKM(lat, lng,
                            place.geometry.location.lat(), place.geometry.location.lng());
                        const marker = new google.maps.Marker({
                            position: place.geometry.location, map,
                            title: place.name,
                            icon: "https://maps.google.com/mapfiles/ms/icons/hospitals.png"
                        });
                        marker.addListener("click", () => {
                            infoWindow.setContent(`<strong>🏥 ${place.name}</strong><br>Distance: ${distance}`);
                            infoWindow.open(map, marker);
                        });
                    });
                }
            });

            service.nearbySearch({
                location: apartmentLocation, radius: 5000, keyword: "evacuation center"
            }, (results, status) => {
                if (status === google.maps.places.PlacesServiceStatus.OK) {
                    results.forEach(place => {
                        const distance = getDistanceKM(lat, lng,
                            place.geometry.location.lat(), place.geometry.location.lng());
                        const marker = new google.maps.Marker({
                            position: place.geometry.location, map,
                            title: place.name,
                            icon: "https://maps.google.com/mapfiles/ms/icons/caution.png"
                        });
                        marker.addListener("click", () => {
                            infoWindow.setContent(`<strong>🚨 ${place.name}</strong><br>Distance: ${distance}`);
                            infoWindow.open(map, marker);
                        });
                    });
                }
            });
        }
    </script>
</body>
</html>