<?php
require_once '../connection.php';
include '../session_auth.php';

$landlord_id = $_SESSION['landlord_id'];

$sql = "
SELECT l.*,
       r.ID AS rent_id,
       r.status AS rent_status,
       COUNT(DISTINCT CASE WHEN m.status = 'Pending' THEN m.id END) as pending_maintenance
FROM listingtbl l
LEFT JOIN renttbl r 
       ON r.listing_id = l.ID AND r.status = 'approved'
LEFT JOIN maintenance_requeststbl m
       ON m.landlord_id = ? AND m.lease_id IN (
           SELECT ID FROM leasetbl WHERE listing_id = l.ID AND status = 'active'
       ) AND m.status = 'Pending'
WHERE l.landlord_id = ?
GROUP BY l.ID
";
$stmt = $conn->prepare($sql);
$stmt->bind_param("ii", $landlord_id, $landlord_id);
$stmt->execute();
$result = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/7.0.1/css/all.min.css" crossorigin="anonymous" referrerpolicy="no-referrer" />
    <link rel="stylesheet" href="../css/bootstrap.min.css">
    <link rel="stylesheet" href="../css/style.css?v=<?= time(); ?>">
    <title>My Properties</title>
    <style>
        /* ── Page Layout ── */
        .page-hero {
            margin-top: 110px;
            padding: 40px 0 24px;
            background: var(--bg-color);
        }

        .page-hero .inner {
            max-width: 1140px;
            margin: 0 auto;
            padding: 0 24px;
            display: flex;
            align-items: flex-end;
            justify-content: space-between;
            gap: 16px;
        }

        .page-hero h1 {
            font-size: clamp(1.6rem, 3vw, 2.4rem);
            font-weight: 700;
            color: var(--text-color);
            line-height: 1.2;
        }

        .page-hero h1 span {
            color: var(--main-color);
        }

        .page-hero p {
            font-size: 0.88rem;
            color: var(--text-alt-color);
            margin-top: 5px;
        }

        /* ── Add Button ── */
        .btn-add {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            background: var(--main-color);
            color: #fff;
            border: none;
            padding: 12px 26px;
            border-radius: 50px;
            font-family: inherit;
            font-size: 0.88rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.25s ease;
            white-space: nowrap;
            text-decoration: none;
            box-shadow: 0 4px 16px rgba(141, 11, 65, 0.25);
        }

        .btn-add:hover {
            background: #6e0932;
            transform: translateY(-2px);
            box-shadow: 0 8px 24px rgba(141, 11, 65, 0.35);
            color: #fff;
        }

        /* ── Section Divider ── */
        .section-divider {
            max-width: 1140px;
            margin: 28px auto 28px;
            padding: 0 24px;
            display: flex;
            align-items: center;
            gap: 14px;
        }

        .section-divider span {
            font-size: 0.72rem;
            font-weight: 700;
            letter-spacing: 0.14em;
            text-transform: uppercase;
            color: var(--text-alt-color);
            white-space: nowrap;
        }

        .section-divider::after {
            content: '';
            flex: 1;
            height: 1px;
            background: linear-gradient(to right, var(--main-color), transparent);
            opacity: 0.25;
        }

        /* ── Grid ── */
        .property-grid {
            max-width: 1140px;
            margin: 0 auto;
            padding: 0 24px 80px;
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(330px, 1fr));
            gap: 26px;
            align-items: stretch;
        }

        /* ── Card ── */
        .prop-card {
            background: var(--bg-color);
            border-radius: 16px;
            overflow: hidden;
            border: 1.5px solid transparent;
            background-clip: padding-box;
            box-shadow: 0 4px 20px var(--shadow-color);
            display: flex;
            flex-direction: column;
            position: relative;
            transition: transform 0.3s ease, box-shadow 0.3s ease, border-color 0.3s ease;
            animation: fadeUp 0.45s ease both;
        }

        /* Decorative left border accent */
        .prop-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 4px;
            height: 100%;
            background: linear-gradient(to bottom, var(--main-color), transparent);
            border-radius: 16px 0 0 16px;
            opacity: 0;
            transition: opacity 0.3s ease;
            z-index: 2;
        }

        .prop-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 12px 36px var(--shadow-color);
            border-color: rgba(141, 11, 65, 0.2);
        }

        .prop-card:hover::before {
            opacity: 1;
        }

        @keyframes fadeUp {
            from { opacity: 0; transform: translateY(20px); }
            to   { opacity: 1; transform: translateY(0); }
        }

        .prop-card:nth-child(1) { animation-delay: 0.04s; }
        .prop-card:nth-child(2) { animation-delay: 0.10s; }
        .prop-card:nth-child(3) { animation-delay: 0.16s; }
        .prop-card:nth-child(4) { animation-delay: 0.22s; }
        .prop-card:nth-child(5) { animation-delay: 0.28s; }
        .prop-card:nth-child(6) { animation-delay: 0.34s; }

        /* ── Card Image ── */
        .card-img-wrap {
            position: relative;
            overflow: hidden;
            height: 200px;
            flex-shrink: 0;
        }

        .card-img-wrap img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            transition: transform 0.5s ease;
        }

        .prop-card:hover .card-img-wrap img {
            transform: scale(1.06);
        }

        /* ── Image Badges ── */
        .img-badges {
            position: absolute;
            top: 12px;
            left: 12px;
            right: 12px;
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            z-index: 1;
        }

        .badge-pill {
            font-size: 0.72rem;
            font-weight: 700;
            padding: 5px 12px;
            border-radius: 50px;
            display: inline-flex;
            align-items: center;
            gap: 5px;
            letter-spacing: 0.02em;
            backdrop-filter: blur(6px);
        }

        .badge-occupied    { background: rgba(220, 53, 69, 0.88);  color: #fff; }
        .badge-available   { background: rgba(25, 135, 84, 0.88);  color: #fff; }
        .badge-pending-v   { background: rgba(255, 193, 7, 0.92);  color: #3d2b00; }
        .badge-rejected    { background: rgba(220, 53, 69, 0.88);  color: #fff; }

        .badge-maintenance {
            font-size: 0.72rem;
            font-weight: 700;
            padding: 5px 11px;
            border-radius: 50px;
            background: #fff;
            color: var(--main-color);
            display: inline-flex;
            align-items: center;
            gap: 5px;
            box-shadow: 0 2px 10px rgba(141,11,65,0.3);
            animation: pulse 2s infinite;
        }

        @keyframes pulse {
            0%, 100% { box-shadow: 0 2px 10px rgba(141,11,65,0.3); }
            50%       { box-shadow: 0 2px 18px rgba(141,11,65,0.6); }
        }

        /* ── Price ribbon ── */
        .price-ribbon {
            position: absolute;
            bottom: 0;
            left: 0;
            right: 0;
            background: linear-gradient(to top, rgba(0,0,0,0.68), transparent);
            padding: 30px 14px 10px;
            color: #fff;
            font-size: 1.15rem;
            font-weight: 700;
        }

        .price-ribbon small {
            font-size: 0.72rem;
            font-weight: 400;
            opacity: 0.75;
            margin-left: 2px;
        }

        /* ── Card Body ── */
        .card-body-inner {
            padding: 18px 18px 0;
            flex: 1;
            display: flex;
            flex-direction: column;
        }

        .card-title {
            font-size: 1rem;
            font-weight: 700;
            color: var(--text-color);
            margin-bottom: 5px;
            line-height: 1.35;
        }

        .card-location {
            font-size: 0.78rem;
            color: var(--text-alt-color);
            display: flex;
            align-items: center;
            gap: 5px;
            margin-bottom: 14px;
        }

        .card-location i { color: var(--main-color); }

        /* ── Feature Pills ── */
        .card-features {
            display: flex;
            gap: 7px;
            flex-wrap: wrap;
            margin-bottom: 16px;
        }

        .feature-pill {
            background: var(--bg-alt-color);
            color: var(--text-alt-color);
            font-size: 0.75rem;
            font-weight: 600;
            padding: 4px 11px;
            border-radius: 50px;
            display: inline-flex;
            align-items: center;
            gap: 5px;
            border: 1px solid rgba(141,11,65,0.1);
        }

        .feature-pill i { color: var(--main-color); font-size: 0.72rem; }

        /* ── Card Footer ── */
        .card-footer-actions {
            margin-top: auto;
            padding: 14px 18px 18px;
            display: flex;
            gap: 10px;
        }

        .btn-card {
            flex: 1;
            padding: 10px 0;
            border-radius: 10px;
            font-family: inherit;
            font-size: 0.82rem;
            font-weight: 600;
            cursor: pointer;
            border: none;
            transition: all 0.22s ease;
            text-align: center;
        }

        .btn-card-outline {
            background: transparent;
            border: 1.5px solid rgba(141,11,65,0.25);
            color: var(--main-color);
        }

        .btn-card-outline:hover {
            background: rgba(141,11,65,0.06);
            border-color: var(--main-color);
        }

        .btn-card-primary {
            background: var(--main-color);
            color: #fff;
            box-shadow: 0 3px 10px rgba(141,11,65,0.2);
        }

        .btn-card-primary:hover {
            background: #6e0932;
            box-shadow: 0 5px 16px rgba(141,11,65,0.35);
        }

        /* ── Empty State ── */
        .empty-state {
            grid-column: 1 / -1;
            text-align: center;
            padding: 80px 20px;
            color: var(--text-alt-color);
        }

        .empty-state i {
            font-size: 3.5rem;
            color: var(--main-color);
            opacity: 0.25;
            display: block;
            margin-bottom: 14px;
        }

        .empty-state p { font-size: 0.95rem; }

        @media (max-width: 600px) {
            .page-hero .inner { flex-direction: column; align-items: flex-start; }
            .property-grid { grid-template-columns: 1fr; }
        }
    </style>
</head>

<body>
    <?php include '../Components/landlord-header.php'; ?>

    <!-- Page Header -->
    <div class="page-hero">
        <div class="inner">
            <div>
                <h1>My <span>Properties</span></h1>
                <p>Manage and monitor all your listed apartments</p>
            </div>
            <button class="btn-add" onclick="location.href='add-property.php'">
                <i class="bi bi-plus-lg"></i> Add Apartment
            </button>
        </div>
    </div>

    <!-- Section Label -->
    <div class="section-divider">
        <span>All Listings</span>
    </div>

    <!-- Property Grid -->
    <div class="property-grid">

        <?php if ($result && $result->num_rows > 0): ?>
            <?php while ($row = $result->fetch_assoc()): ?>
                <?php
                    $isOccupied     = !empty($row['rent_id']);
                    $hasMaintenance = $row['pending_maintenance'] > 0;
                    $images         = json_decode($row['images'], true);
                    $imagePath      = '../LANDLORD/uploads/placeholder.jpg';
                    if (!empty($images) && is_array($images) && isset($images[0])) {
                        $imagePath = '../LANDLORD/uploads/' . $images[0];
                    }
                ?>

                <div class="prop-card">

                    <!-- Image -->
                    <div class="card-img-wrap">
                        <img src="<?= htmlspecialchars($imagePath) ?>" alt="Property Image">

                        <div class="img-badges">
                            <!-- Status badge -->
                            <?php if ($row['verification_status'] === 'pending'): ?>
                                <span class="badge-pill badge-pending-v">
                                    <i class="fas fa-clock"></i> Pending Verification
                                </span>
                            <?php elseif ($row['verification_status'] === 'rejected'): ?>
                                <span class="badge-pill badge-rejected">
                                    <i class="fas fa-times-circle"></i> Rejected
                                </span>
                            <?php else: ?>
                                <span class="badge-pill <?= $isOccupied ? 'badge-occupied' : 'badge-available' ?>">
                                    <i class="fas <?= $isOccupied ? 'fa-user-check' : 'fa-check-circle' ?>"></i>
                                    <?= $isOccupied ? 'Occupied' : 'Available' ?>
                                </span>
                            <?php endif; ?>

                            <!-- Maintenance badge -->
                            <?php if ($hasMaintenance): ?>
                                <span class="badge-maintenance">
                                    <i class="bi bi-exclamation-triangle-fill"></i>
                                    <?= $row['pending_maintenance'] ?> Request<?= $row['pending_maintenance'] > 1 ? 's' : '' ?>
                                </span>
                            <?php endif; ?>
                        </div>

                        <!-- Price -->
                        <div class="price-ribbon">
                            ₱ <?= number_format($row['price']) ?><small>/mo</small>
                        </div>
                    </div>

                    <!-- Body -->
                    <div class="card-body-inner">
                        <h3 class="card-title"><?= htmlspecialchars($row['listingName']) ?></h3>
                        <div class="card-location">
                            <i class="fas fa-map-marker-alt"></i>
                            <?= htmlspecialchars($row['address']) ?>
                        </div>
                        <div class="card-features">
                            <span class="feature-pill">
                                <i class="fas fa-bed"></i> <?= htmlspecialchars($row['rooms']) ?> Bedroom
                            </span>
                            <span class="feature-pill">
                                <i class="fa-solid fa-building"></i> <?= htmlspecialchars($row['category']) ?>
                            </span>
                        </div>
                    </div>

                    <!-- Footer -->
                    <div class="card-footer-actions">
                        <button class="btn-card btn-card-outline"
                            onclick="location.href='edit-property.php?ID=<?= $row['ID'] ?>'">
                            <i class="fas fa-pen" style="font-size:0.75rem; margin-right:4px;"></i> Edit
                        </button>

                        <?php if ($isOccupied): ?>
                            <button class="btn-card btn-card-primary"
                                onclick="location.href='landlord-rental.php?rent_id=<?= $row['rent_id'] ?>'">
                                View Rental
                            </button>
                        <?php else: ?>
                            <button class="btn-card btn-card-primary"
                                onclick="location.href='property-details.php?ID=<?= $row['ID'] ?>'">
                                View Details
                            </button>
                        <?php endif; ?>
                    </div>

                </div>

            <?php endwhile; ?>

        <?php else: ?>
            <div class="empty-state">
                <i class="bi bi-building-slash"></i>
                <p>No properties listed yet. Add your first apartment!</p>
            </div>
        <?php endif; ?>

    </div>

    <?php include '../Components/footer.php'; ?>
    <script src="../js/script.js" defer></script>
    <script src="../js/bootstrap.bundle.min.js"></script>
    <script src="https://unpkg.com/scrollreveal"></script>
</body>
</html>