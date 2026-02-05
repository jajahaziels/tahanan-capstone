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

// --- Query 2: Tenant Requests + Lease Info ---
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
    WHERE r.listing_id = ?
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

    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/7.0.1/css/all.min.css"
        crossorigin="anonymous" />
    <link rel="stylesheet" href="../css/bootstrap.min.css">
    <link rel="stylesheet" href="../css/style.css?v=<?= time(); ?>">

    <style>
        body {
            background-color: #f1f3f6;
        }

        .landlord-page {
            max-width: 1600px;
            margin: 120px auto 80px;
            padding: 30px;
        }

        .main-content {
            display: flex;
            gap: 2rem;
        }

        .main-content .left {
            flex: 2;
        }

        .main-content .right {
            flex: 1;
        }

        @media(max-width:991px) {
            .main-content {
                display: block;
            }

            .main-content .left,
            .main-content .right {
                width: 100%;
            }
        }

        /* --- Property Card --- */
        .property-card {
            background: #fff;
            border-radius: 1rem;
            padding: 1.5rem;
            box-shadow: 0 6px 15px rgba(0, 0, 0, 0.05);
        }

        .property-card img {
            width: 100%;
            height: 300px;
            object-fit: cover;
            border-radius: 1rem;
            margin-bottom: 15px;
        }

        .property-info {
            display: flex;
            flex-wrap: wrap;
            gap: 12px;
            margin-bottom: 10px;
        }

        .property-info div {
            background: #f5f5f5;
            padding: 6px 12px;
            border-radius: 8px;
            font-size: 0.9rem;
            font-weight: 500;
        }

        .property-price {
            font-weight: 700;
            font-size: 1.5rem;
            color: #007bff;
            margin: 10px 0;
        }

        .property-meta {
            font-size: 0.9rem;
            color: #555;
            margin-bottom: 6px;
        }

        .property-description {
            margin: 10px 0;
            font-size: 0.95rem;
            line-height: 1.5;
        }

        #carouselExample .carousel-inner {
            border-radius: 1rem;
            overflow: hidden;
        }

        /* --- Tenant Requests --- */
        .requests-card {
            background: #fff;
            border-radius: 1rem;
            padding: 1.5rem;
            box-shadow: 0 6px 15px rgba(0, 0, 0, 0.05);
        }

        .tenant-request {
            border: 1px solid #e0e0e0;
            border-radius: .8rem;
            padding: 1rem;
            margin-bottom: 1rem;
            transition: all .2s ease;
        }

        .tenant-request:hover {
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
        }

        .btn2 {
            border-radius: .6rem;
            cursor: pointer;
            font-size: .9rem;
            padding: .5rem 1.8rem;
            background: transparent;
            border: 2px solid;
            transition: all .3s linear;
        }

        .btn2-blue {
            border-color: #007bff;
            color: #007bff;
        }

        .btn2-blue:hover {
            background: #007bff;
            color: #fff;
        }

        .btn2-red {
            border-color: #dc3545;
            color: #dc3545;
        }

        .btn2-red:hover {
            background: #dc3545;
            color: #fff;
        }

        .badge-status {
            font-size: .9rem;
            padding: .4em .6em;
        }
    </style>
</head>

<body>
    <?php include '../Components/landlord-header.php'; ?>

    <div class="landlord-page container-fluid">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1>Property Details</h1>
            <button class="main-button" onclick="location.href='landlord-properties.php'">Back</button>
        </div>

        <div class="main-content">
            <!-- LEFT: Property -->
            <div class="left">
                <div class="property-card">
                    <div id="carouselExample" class="carousel slide mb-3">
                        <div class="carousel-inner">
                            <?php if (!empty($images)):
                                foreach ($images as $i => $img): ?>
                                    <div class="carousel-item <?= $i === 0 ? 'active' : '' ?>">
                                        <img src="../LANDLORD/uploads/<?= htmlspecialchars($img); ?>" alt="Property Image">
                                    </div>
                                <?php endforeach; else: ?>
                                <div class="carousel-item active">
                                    <img src="../LANDLORD/uploads/placeholder.jpg" alt="No Image">
                                </div>
                            <?php endif; ?>
                        </div>
                        <button class="carousel-control-prev" type="button" data-bs-target="#carouselExample"
                            data-bs-slide="prev">
                            <span class="carousel-control-prev-icon"></span><span
                                class="visually-hidden">Previous</span>
                        </button>
                        <button class="carousel-control-next" type="button" data-bs-target="#carouselExample"
                            data-bs-slide="next">
                            <span class="carousel-control-next-icon"></span><span class="visually-hidden">Next</span>
                        </button>
                    </div>

                    <h2><?= htmlspecialchars($property['listingName']); ?></h2>
                    <p class="property-price">₱<?= number_format($property['price']); ?>.00</p>

                    <div class="property-info">
                        <div><?= htmlspecialchars($property['category']); ?></div>
                        <div><?= htmlspecialchars($property['rooms']); ?> Bedroom(s)</div>
                        <div><?= htmlspecialchars($property['barangay']); ?>, San Pedro, Laguna</div>
                    </div>

                    <p class="property-meta"><strong>Address:</strong> <?= htmlspecialchars($property['address']); ?>,
                        <?= htmlspecialchars($property['barangay']); ?>, San Pedro, Laguna</p>
                    <p class="property-description"><?= nl2br(htmlspecialchars($property['listingDesc'])); ?></p>
                </div>
            </div>

            <!-- RIGHT: Tenant Requests -->
            <div class="right">
                <div class="requests-card">
                    <h2 class="text-center mb-3">Tenant Requests</h2>

                    <?php if ($requests->num_rows > 0):
                        while ($req = $requests->fetch_assoc()): ?>
                            <?php $req['lease_exists'] = !empty($req['lease_id']); ?>
                            <div class="tenant-request">
                                <p><strong><?= htmlspecialchars($req['firstName'] . ' ' . $req['lastName']); ?></strong></p>
                                <p><?= htmlspecialchars($req['email']); ?> | <?= htmlspecialchars($req['phoneNum']); ?></p>

                                <p>Status: <span
                                        class="badge bg-secondary badge-status"><?= ucfirst($req['request_status']); ?></span>
                                </p>

                                <?php if (empty($req['tenant_response']) || $req['tenant_response'] === 'pending'): ?>
                                    <p>Tenant Response to Agreement: <span class="badge bg-warning badge-status">Pending</span></p>
                                <?php elseif ($req['tenant_response'] === 'accepted'): ?>
                                    <p>Tenant Response to Agreement: <span class="badge bg-success badge-status">Accepted</span></p>
                                <?php elseif ($req['tenant_response'] === 'rejected'): ?>
                                    <p>Tenant Response to Agreement: <span class="badge bg-danger badge-status">Rejected</span></p>
                                <?php endif; ?>

                                <div class="d-flex gap-2 flex-wrap mt-2">
                                    <?php if ($req['lease_id'] && !empty($req['pdf_path'])): ?>
                                        <a href="<?= htmlspecialchars($req['pdf_path']); ?>" target="_blank"
                                            class="btn2 btn2-blue lease-btn">📄 View Lease</a>
                                    <?php elseif ($req['lease_id']): ?>
                                        <a href="lease-details.php?lease_id=<?= $req['lease_id']; ?>"
                                            class="btn2 btn2-blue lease-btn">📝 Lease Details</a>
                                    <?php else: ?>
                                        <a href="lease-form.php?request_id=<?= $req['request_id']; ?>&listing_id=<?= $listingID; ?>&tenant_id=<?= $req['tenant_id']; ?>"
                                            class="btn2 btn2-blue lease-btn" data-status="pending">📝 Make Lease Agreement</a>
                                    <?php endif; ?>

                                    <?php if ($req['tenant_response'] === 'rejected'): ?>
                                        <button type="button" class="btn2 btn2-red remove-btn"
                                            data-request="<?= $req['request_id']; ?>">
                                            <i class="fa-solid fa-trash"></i> Remove
                                        </button>
                                    <?php elseif (!$req['lease_exists']): ?>
                                        <button type="button" class="btn2 btn2-red reject-btn"
                                            data-request="<?= $req['request_id']; ?>">
                                            <i class="fa-solid fa-trash"></i> Reject
                                        </button>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endwhile; else: ?>
                        <p class="text-center">No tenant requests yet.</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <script src="../js/bootstrap.bundle.min.js"></script>
    <script>
        document.addEventListener("DOMContentLoaded", () => {
            // Reject tenant request
            document.querySelectorAll(".reject-btn").forEach(btn => {
                btn.addEventListener("click", () => {
                    if (!confirm("Reject this application?")) return;
                    const requestId = btn.dataset.request;
                    const card = btn.closest(".tenant-request");
                    fetch("update-request.php", {
                        method: "POST",
                        headers: { "Content-Type": "application/x-www-form-urlencoded" },
                        body: `request_id=${requestId}&action=reject`
                    }).then(res => res.text()).then(() => card.remove());
                });
            });

            // Remove rejected request
            document.querySelectorAll(".remove-btn").forEach(btn => {
                btn.addEventListener("click", () => {
                    if (!confirm("Remove this rejected application?")) return;
                    const requestId = btn.dataset.request;
                    const card = btn.closest(".tenant-request");
                    fetch("update-request.php", {
                        method: "POST",
                        headers: { "Content-Type": "application/x-www-form-urlencoded" },
                        body: `request_id=${requestId}&action=remove`
                    }).then(res => res.text()).then(() => card.remove());
                });
            });
        });
    </script>
</body>

</html>