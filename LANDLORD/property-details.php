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

    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/7.0.1/css/all.min.css"
        crossorigin="anonymous" />
    <link rel="stylesheet" href="../css/bootstrap.min.css">
    <link rel="stylesheet" href="../css/style.css?v=<?= time(); ?>">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <style>
        body { background-color: #f1f3f6; }

        .landlord-page {
            max-width: 1600px;
            margin: 120px auto 80px;
            padding: 30px;
        }

        .main-content { display: flex; gap: 2rem; }
        .main-content .left  { flex: 2; }
        .main-content .right { flex: 1; }

        @media(max-width:991px) {
            .main-content { display: block; }
            .main-content .left,
            .main-content .right { width: 100%; }
        }

        .property-card {
            background: #fff;
            border-radius: 1rem;
            padding: 1.5rem;
            box-shadow: 0 6px 15px rgba(0,0,0,.05);
        }
        .property-card img {
            width: 100%; height: 300px;
            object-fit: cover;
            border-radius: 1rem;
            margin-bottom: 15px;
        }
        .property-info {
            display: flex; flex-wrap: wrap;
            gap: 12px; margin-bottom: 10px;
        }
        .property-info div {
            background: #f5f5f5;
            padding: 6px 12px;
            border-radius: 8px;
            font-size: .9rem; font-weight: 500;
        }
        .property-price { font-weight: 700; font-size: 1.5rem; color: #007bff; margin: 10px 0; }
        .property-meta  { font-size: .9rem; color: #555; margin-bottom: 6px; }
        .property-description { margin: 10px 0; font-size: .95rem; line-height: 1.5; }
        #carouselExample .carousel-inner { border-radius: 1rem; overflow: hidden; }

        /* ── House Rules (landlord view) ── */
        .house-rules-panel {
            background: var(--bg-alt-color);
            border: 1px solid #8d0b41;
            border-radius: 12px;
            padding: 16px 20px;
            margin-top: 16px;
        }

        .house-rules-panel h6 {
            color: #8d0b41;
            font-weight: 700;
            margin-bottom: 10px;
            font-size: 0.95rem;
        }

        .house-rules-panel ul {
            margin: 0;
            padding-left: 0;
            list-style: none;
        }

        .house-rules-panel ul li {
            display: flex;
            align-items: flex-start;
            gap: 8px;
            padding: 6px 0;
            border-bottom: 1px dashed #8d0b41;
            font-size: 0.9rem;
            color: #333;
        }

        .house-rules-panel ul li:last-child { border-bottom: none; }

        .house-rules-panel ul li i {
            color: #8d0b41;
            margin-top: 2px;
            flex-shrink: 0;
            font-size: 0.8rem;
        }

        .no-rules-note {
            font-size: 0.85rem;
            color: #999;
            font-style: italic;
        }

        /* ── Requests panel ── */
        .requests-card {
            background: #fff;
            border-radius: 1rem;
            padding: 1.5rem;
            box-shadow: 0 6px 15px rgba(0,0,0,.05);
        }
        .tenant-request {
            border: 1px solid #e0e0e0;
            border-radius: .8rem;
            padding: 1rem;
            margin-bottom: 1rem;
            transition: all .2s ease;
        }
        .tenant-request:hover { box-shadow: 0 4px 12px rgba(0,0,0,.1); }

        .btn2 {
            border-radius: .6rem; cursor: pointer;
            font-size: .9rem; padding: .5rem 1.8rem;
            background: transparent; border: 2px solid;
            transition: all .3s linear;
        }
        .btn2-blue   { border-color: #007bff; color: #007bff; }
        .btn2-blue:hover  { background: #007bff; color: #fff; }
        .btn2-green  { border-color: #28a745; color: #28a745; }
        .btn2-green:hover { background: #28a745; color: #fff; }
        .btn2-red    { border-color: #dc3545; color: #dc3545; }
        .btn2-red:hover   { background: #dc3545; color: #fff; }
        .btn2-orange { border-color: #fd7e14; color: #fd7e14; }
        .btn2-orange:hover { background: #fd7e14; color: #fff; }

        .badge-status { font-size: .9rem; padding: .4em .6em; }

        .termination-notice {
            background: #fff3cd;
            border: 1px solid #ffc107;
            border-left: 4px solid #fd7e14;
            border-radius: 8px;
            padding: 10px 14px;
            margin: 10px 0;
            font-size: .88rem;
            color: #856404;
        }
        .termination-notice .reason-text {
            font-style: italic;
            color: #6c757d;
            margin-top: 4px;
        }

        .reapply-notice {
            background: #e8f4fd;
            border: 1px solid #bee3f8;
            border-left: 4px solid #007bff;
            border-radius: 8px;
            padding: 10px 14px;
            margin: 10px 0;
            font-size: .88rem;
            color: #0c5460;
        }

        .ended-notice {
            background: #f8d7da;
            border: 1px solid #f5c2c7;
            border-left: 4px solid #dc3545;
            border-radius: 8px;
            padding: 10px 14px;
            margin: 10px 0;
            font-size: .88rem;
            color: #842029;
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

            <!-- LEFT: Property Info -->
            <div class="left">
                <div class="property-card">
                    <div id="carouselExample" class="carousel slide mb-3">
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
                            <span class="visually-hidden">Previous</span>
                        </button>
                        <button class="carousel-control-next" type="button" data-bs-target="#carouselExample" data-bs-slide="next">
                            <span class="carousel-control-next-icon"></span>
                            <span class="visually-hidden">Next</span>
                        </button>
                    </div>

                    <h2><?= htmlspecialchars($property['listingName']) ?></h2>
                    <p class="property-price">₱<?= number_format($property['price']) ?>.00</p>

                    <div class="property-info">
                        <div><?= htmlspecialchars($property['category']) ?></div>
                        <div><?= htmlspecialchars($property['rooms']) ?> Bedroom(s)</div>
                        <div><?= htmlspecialchars($property['barangay']) ?>, San Pedro, Laguna</div>
                    </div>

                    <p class="property-meta">
                        <strong>Address:</strong> <?= htmlspecialchars($property['address']) ?>,
                        <?= htmlspecialchars($property['barangay']) ?>, San Pedro, Laguna
                    </p>

                    <p class="property-description">
                        <?= nl2br(htmlspecialchars($property['listingDesc'])) ?>
                    </p>

                    <!-- ── HOUSE RULES PANEL (Landlord View) ── -->
                    <div class="house-rules-panel">
                        <h6>
                            <i class="fa-solid fa-clipboard-list me-1"></i> House Rules &amp; Terms
                        </h6>
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

                    <!-- Edit shortcut -->
                    <div class="mt-3">
                        <a href="edit-property.php?ID=<?= $listingID ?>" class="main-button"
                            style="display:inline-block; text-decoration:none;">
                            <i class="fa-solid fa-pen me-1"></i> Edit Property
                        </a>
                    </div>
                </div>
            </div>

            <!-- RIGHT: Tenant Requests -->
            <div class="right">
                <div class="requests-card">
                    <h2 class="text-center mb-3">Tenant Requests</h2>

                    <?php if ($requests->num_rows > 0):
                        while ($req = $requests->fetch_assoc()):

                            $leaseStatus    = $req['lease_status']    ?? null;
                            $tenantResponse = $req['tenant_response'] ?? null;
                            $leaseId        = $req['lease_id']        ?? null;

                            $hasLease           = !empty($leaseId);
                            $leaseIsTerminated  = $hasLease && in_array($leaseStatus, ['terminated']);
                            $leaseIsCancelled   = $hasLease && $leaseStatus === 'cancelled';
                            $tenantRejected     = $tenantResponse === 'rejected';
                            $leaseIsActive      = $hasLease && $leaseStatus === 'active';
                            $leaseIsPending     = $hasLease && $leaseStatus === 'pending';
                            $leaseIsCancellable = $leaseIsPending && $tenantResponse !== 'accepted';
                            $canMakeNewLease    = !$hasLease
                                                  || $leaseIsCancelled
                                                  || $leaseIsTerminated
                                                  || $tenantRejected;

                            // Check for pending termination request from tenant
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
                            $hasPendingTermination = $terminationInfo
                                                     && $terminationInfo['landlord_status'] === 'pending';
                    ?>

                        <div class="tenant-request" id="req-card-<?= $req['request_id'] ?>">

                            <p><strong><?= htmlspecialchars($req['firstName'] . ' ' . $req['lastName']) ?></strong></p>
                            <p><?= htmlspecialchars($req['email']) ?> | <?= htmlspecialchars($req['phoneNum']) ?></p>

                            <p>Request Status:
                                <span class="badge bg-secondary badge-status"><?= ucfirst($req['request_status']) ?></span>
                            </p>

                            <!-- Lease / response status -->
                            <?php if ($tenantRejected): ?>
                                <div class="ended-notice">
                                    <strong>✖ Tenant rejected the lease agreement.</strong>
                                </div>
                            <?php elseif ($leaseIsTerminated): ?>
                                <div class="ended-notice">
                                    <strong>🔴 Lease was terminated.</strong>
                                </div>
                            <?php elseif ($leaseIsCancelled): ?>
                                <div class="ended-notice">
                                    <strong>🔴 Lease was cancelled.</strong>
                                </div>
                            <?php elseif ($leaseIsPending && !$tenantRejected): ?>
                                <p>Tenant Response:
                                    <?php if ($tenantResponse === 'accepted'): ?>
                                        <span class="badge bg-success badge-status">Accepted</span>
                                    <?php else: ?>
                                        <span class="badge bg-warning badge-status">Pending</span>
                                    <?php endif; ?>
                                </p>
                            <?php elseif ($leaseIsActive): ?>
                                <p>Tenant Response:
                                    <span class="badge bg-success badge-status">Accepted</span>
                                </p>
                            <?php endif; ?>

                            <!-- Re-apply notice -->
                            <?php if ($canMakeNewLease && $hasLease): ?>
                                <div class="reapply-notice">
                                    <i class="fa-solid fa-circle-info"></i>
                                    <strong> Tenant can re-apply.</strong>
                                    You may create a new lease agreement for this tenant.
                                </div>
                            <?php endif; ?>

                            <!-- Pending termination notice -->
                            <?php if ($hasPendingTermination): ?>
                                <div class="termination-notice">
                                    <strong>⚠ Tenant Requested Lease Termination</strong>
                                    <div class="reason-text">"<?= htmlspecialchars($terminationInfo['reason']) ?>"</div>
                                    <div class="mt-2 d-flex gap-2 flex-wrap">
                                        <button class="btn2 btn2-red approve-termination-btn"
                                            data-lease="<?= $leaseId ?>"
                                            data-termination="<?= $terminationInfo['ID'] ?>"
                                            data-request="<?= $req['request_id'] ?>">
                                            ✔ Approve Termination
                                        </button>
                                        <button class="btn2 btn2-orange reject-termination-btn"
                                            data-lease="<?= $leaseId ?>"
                                            data-termination="<?= $terminationInfo['ID'] ?>">
                                            ✖ Reject
                                        </button>
                                    </div>
                                </div>
                            <?php endif; ?>

                            <!-- Action buttons -->
                            <div class="d-flex gap-2 flex-wrap mt-2">

                                <?php if ($canMakeNewLease): ?>
                                    <a href="lease-form.php?request_id=<?= $req['request_id'] ?>&listing_id=<?= $listingID ?>&tenant_id=<?= $req['tenant_id'] ?>"
                                        class="btn2 btn2-green">
                                        📝 <?= $hasLease ? 'New Lease Agreement' : 'Make Lease Agreement' ?>
                                    </a>
                                    <button type="button" class="btn2 btn2-red remove-btn"
                                        data-request="<?= $req['request_id'] ?>">
                                        <i class="fa-solid fa-trash"></i> Remove
                                    </button>

                                <?php elseif ($leaseIsActive || $leaseIsPending): ?>
                                    <?php if (!empty($req['pdf_path'])): ?>
                                        <a href="<?= htmlspecialchars($req['pdf_path']) ?>" target="_blank"
                                            class="btn2 btn2-blue">📄 View Lease</a>
                                    <?php else: ?>
                                        <a href="lease-details.php?lease_id=<?= $leaseId ?>"
                                            class="btn2 btn2-blue">📝 Lease Details</a>
                                    <?php endif; ?>

                                    <?php if ($leaseIsCancellable): ?>
                                        <button type="button" class="btn2 btn2-orange cancel-lease-btn"
                                            data-lease="<?= $leaseId ?>"
                                            data-request="<?= $req['request_id'] ?>">
                                            ✖ Cancel Lease
                                        </button>
                                    <?php endif; ?>

                                <?php elseif (!$hasLease): ?>
                                    <button type="button" class="btn2 btn2-red remove-btn"
                                        data-request="<?= $req['request_id'] ?>">
                                        <i class="fa-solid fa-trash"></i> Remove
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

            /* ── Remove request ── */
            document.querySelectorAll(".remove-btn").forEach(btn => {
                btn.addEventListener("click", () => {
                    Swal.fire({
                        title: 'Remove this request?',
                        text: 'This will hide the request from your list.',
                        icon: 'warning',
                        showCancelButton: true,
                        confirmButtonColor: '#dc3545',
                        cancelButtonColor: '#6c757d',
                        confirmButtonText: 'Yes, remove it'
                    }).then(result => {
                        if (!result.isConfirmed) return;
                        const requestId = btn.dataset.request;
                        const card = btn.closest(".tenant-request");

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

            /* ── Cancel Lease ── */
            document.querySelectorAll(".cancel-lease-btn").forEach(btn => {
                btn.addEventListener("click", () => {
                    const leaseId   = btn.dataset.lease;
                    const requestId = btn.dataset.request;

                    Swal.fire({
                        title: 'Cancel this Lease Agreement?',
                        html: 'The lease will be marked <b>cancelled</b> so you can create a new one for this tenant.<br>The record is kept for reference.',
                        icon: 'warning',
                        showCancelButton: true,
                        confirmButtonColor: '#fd7e14',
                        cancelButtonColor: '#6c757d',
                        confirmButtonText: 'Yes, cancel it',
                        cancelButtonText: 'Go back'
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
                                confirmButtonColor: '#007bff'
                            }).then(() => { if (data.success) location.reload(); });
                        })
                        .catch(() => Swal.fire('Error', 'Something went wrong.', 'error'));
                    });
                });
            });

            /* ── Approve termination ── */
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
                        confirmButtonColor: '#dc3545',
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
                                confirmButtonColor: '#007bff'
                            }).then(() => { if (data.success) location.reload(); });
                        })
                        .catch(() => Swal.fire('Error', 'Something went wrong.', 'error'));
                    });
                });
            });

            /* ── Reject termination ── */
            document.querySelectorAll(".reject-termination-btn").forEach(btn => {
                btn.addEventListener("click", () => {
                    const terminationId = btn.dataset.termination;

                    Swal.fire({
                        title: 'Reject Termination Request?',
                        text: "The tenant's request will be rejected. The lease stays active.",
                        icon: 'question',
                        showCancelButton: true,
                        confirmButtonColor: '#fd7e14',
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
                                confirmButtonColor: '#007bff'
                            }).then(() => { if (data.success) location.reload(); });
                        })
                        .catch(() => Swal.fire('Error', 'Something went wrong.', 'error'));
                    });
                });
            });

        });
    </script>
</body>
</html>