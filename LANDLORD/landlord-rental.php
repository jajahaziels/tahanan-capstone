<?php
require_once '../connection.php';
require_once '../session_auth.php';
include '../TENANT/auto-expire-rental.php';

$rental = null;
$error = '';

// Check landlord session
if (!isset($_SESSION['landlord_id'])) {
    $error = "Unauthorized access. Please log in.";
} else {
    $landlord_id = (int) $_SESSION['landlord_id'];

    // Get request_id from URL or fallback to first approved rental
    $request_id = isset($_GET['request_id']) ? (int) $_GET['request_id'] : 0;

    if ($request_id <= 0) {
        // Fallback: get first approved rental for this landlord
        $stmt = $conn->prepare("
            SELECT r.ID AS rental_id
            FROM renttbl r
            JOIN listingtbl ls ON r.listing_id = ls.ID
            WHERE ls.landlord_id = ? AND r.status = 'approved'
            ORDER BY r.start_date ASC
            LIMIT 1
        ");
        $stmt->bind_param('i', $landlord_id);
        $stmt->execute();
        $res = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        if ($res)
            $request_id = $res['rental_id'];
        else
            $error = "No approved rentals found.";
    }

    // Fetch rental info if request_id is valid
    if ($request_id > 0 && !$error) {
        $sql = "
        SELECT 
            r.ID AS rental_id,
            r.tenant_id,
            r.listing_id,
            r.start_date,
            r.end_date,
            t.firstName AS tenant_firstName,
            t.lastName AS tenant_lastName,
            t.phoneNum AS tenant_phone,
            t.email AS tenant_email,
            ls.listingName,
            ls.address,
            ls.images,
            l.firstName AS landlord_firstName,
            l.lastName AS landlord_lastName,
            l.phoneNum AS landlord_phone,
            l.email AS landlord_email
        FROM renttbl r
        LEFT JOIN listingtbl ls ON r.listing_id = ls.ID
        LEFT JOIN tenanttbl t ON r.tenant_id = t.ID
        LEFT JOIN landlordtbl l ON ls.landlord_id = l.ID
        WHERE r.ID = ? AND ls.landlord_id = ? AND r.status = 'approved'
        LIMIT 1";

        if ($stmt = $conn->prepare($sql)) {
            $stmt->bind_param('ii', $request_id, $landlord_id);
            $stmt->execute();
            $result = $stmt->get_result();
            if ($result && $result->num_rows > 0) {
                $rental = $result->fetch_assoc();
            } else {
                $error = "No approved rental found for this selection.";
            }
            $stmt->close();
        } else {
            $error = "Database error: " . $conn->error;
        }
    }

    // Default property image
    $propertyImg = "../img/house1.jpeg";
    $images = [];
    if ($rental && !empty($rental['images'])) {
        $images = json_decode($rental['images'], true);
        if (!empty($images))
            $propertyImg = "../LANDLORD/uploads/" . $images[0];
    }

    // Fetch extension requests
    $extendRequests = [];
    if ($rental) {
        $stmt = $conn->prepare("SELECT ID, new_end_date, status, created_at FROM extension_requesttbl WHERE rent_id = ? ORDER BY created_at DESC");
        $stmt->bind_param('i', $rental['rental_id']);
        $stmt->execute();
        $extendRequests = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt->close();
    }

    // Fetch cancel requests
    $cancelRequests = [];
    if ($rental) {
        $stmt = $conn->prepare("
            SELECT c.*, t.firstName, t.lastName 
            FROM cancel_requesttbl c
            LEFT JOIN tenanttbl t ON c.tenant_id = t.ID
            WHERE c.rent_id = ?
            ORDER BY c.created_at DESC
        ");
        $stmt->bind_param('i', $rental['rental_id']);
        $stmt->execute();
        $cancelRequests = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt->close();
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="shortcut icon" href="favicon.ico" type="image/x-icon">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/7.0.1/css/all.min.css">
    <link rel="stylesheet" href="../css/bootstrap.min.css">
    <link rel="stylesheet" href="../css/style.css?v=<?= time(); ?>">
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    <link href="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.15/index.global.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.15/index.global.min.js"></script>
    <title>LANDLORD RENTAL</title>
    <style>
        .landlord-page {
            margin-top: 140px;
        }

        #calendar {
            max-width: 500px;
            height: 350px;
            margin: 40px auto;
        }

        #carouselExample img {
            height: 400px;
            object-fit: cover;
            border-radius: 20px;
        }

        .rental-details {
            background-color: var(--bg-color);
            padding: 20px;
            border-radius: 20px;
            box-shadow: 2px 2px 10px rgba(0, 0, 0, 0.165);
        }

        .small-button {
            margin-right: 5px;
        }
    </style>
</head>

<body>
    <?php include '../Components/landlord-header.php'; ?>

    <div class="landlord-page">
        <div class="container m-auto">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h1>Rental Info</h1>
                <?php if ($rental): ?>
                    <form method="post" action="payment.php">
                        <input type="hidden" name="rental_id" value="<?= $rental['rental_id']; ?>">
                        <input type="hidden" name="listing_id" value="<?= htmlspecialchars($rental['listing_id']); ?>">
                        <button type="submit" class="main-button">View History</button>
                    </form>
                <?php endif; ?>
            </div>

            <?php if ($rental): ?>
                <div class="row justify-content-center mb-4">
                    <div class="col-lg-6 col-sm-12 mb-3">
                        <div id="carouselExample" class="carousel slide">
                            <div class="carousel-inner">
                                <?php if (!empty($images)):
                                    foreach ($images as $index => $img): ?>
                                        <div class="carousel-item <?= $index === 0 ? 'active' : '' ?>">
                                            <img src="../LANDLORD/uploads/<?= htmlspecialchars($img); ?>" class="d-block w-100"
                                                alt="Property">
                                        </div>
                                    <?php endforeach; else: ?>
                                    <div class="carousel-item active">
                                        <img src="../LANDLORD/uploads/placeholder.jpg" class="d-block w-100" alt="No Image">
                                    </div>
                                <?php endif; ?>
                            </div>
                            <button class="carousel-control-prev" type="button" data-bs-target="#carouselExample"
                                data-bs-slide="prev">
                                <span class="carousel-control-prev-icon"></span>
                            </button>
                            <button class="carousel-control-next" type="button" data-bs-target="#carouselExample"
                                data-bs-slide="next">
                                <span class="carousel-control-next-icon"></span>
                            </button>
                        </div>
                    </div>
                    <div class="col-lg-6 col-sm-12">
                        <div id="calendar"></div>
                    </div>
                </div>

                <div class="row justify-content-center rental-details mb-4">
                    <div class="col-lg-6 col-sm-12">
                        <h2><?= htmlspecialchars($rental['listingName']); ?></h2>
                        <p><strong>Address:</strong> <?= htmlspecialchars($rental['address']); ?></p>
                        <p><strong>Start Date:</strong> <?= date("F j, Y", strtotime($rental['start_date'])); ?></p>
                        <p><strong>End Date:</strong> <?= date("F j, Y", strtotime($rental['end_date'])); ?></p>
                    </div>
                    <div class="col-lg-5 col-sm-12">
                        <h2>Tenant Info</h2>
                        <p><strong>Name:</strong>
                            <?= htmlspecialchars(ucwords(strtolower($rental['tenant_firstName'] . ' ' . $rental['tenant_lastName']))); ?>
                        </p>
                        <p><strong>Phone:</strong> <?= htmlspecialchars($rental['tenant_phone']); ?></p>
                        <p><strong>Email:</strong> <?= htmlspecialchars($rental['tenant_email']); ?></p>
                        <button class="small-button"
                            onclick="window.location.href='tenant-profile.php?tenant_id=<?= $rental['tenant_id'] ?>'"><i
                                class="fa-solid fa-user"></i></button>
                        <button class="small-button" onclick="window.location.href='landlord-message.php'"><i
                                class="fas fa-comment-dots"></i></button>
                    </div>
                </div>

                <?php if (!empty($extendRequests) || !empty($cancelRequests)): ?>
                    <div class="mb-4">
                        <h3>Requests</h3>
                        <table class="table table-bordered">
                            <thead>
                                <tr>
                                    <th>Request Type</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($extendRequests as $req): ?>
                                    <tr>
                                        <td>Extend to <?= date('F j, Y', strtotime($req['new_end_date'])); ?></td>
                                        <td><?= htmlspecialchars($req['status']); ?></td>
                                        <td>
                                            <?php if ($req['status'] === 'pending'): ?>
                                                <form action="handle-extend-request.php" method="POST" style="display:inline;">
                                                    <input type="hidden" name="request_id" value="<?= $req['ID'] ?>">
                                                    <input type="hidden" name="rental_id" value="<?= $rental['rental_id'] ?>">
                                                    <button type="submit" name="action" value="approve"
                                                        class="small-button">Approve</button>
                                                    <button type="submit" name="action" value="reject"
                                                        class="small-button">Reject</button>
                                                </form>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>

                                <?php foreach ($cancelRequests as $req): ?>
                                    <tr>
                                        <td>Cancel Rental</td>
                                        <td><?= htmlspecialchars($req['status']); ?></td>
                                        <td>
                                            <?php if ($req['status'] === 'pending'): ?>
                                                <form action="handle-cancel-request.php" method="POST" style="display:inline;">
                                                    <input type="hidden" name="request_id" value="<?= $req['ID'] ?>">
                                                    <input type="hidden" name="rental_id" value="<?= $rental['rental_id'] ?>">
                                                    <button type="submit" name="action" value="approve"
                                                        class="small-button">Approve</button>
                                                    <button type="submit" name="action" value="reject"
                                                        class="small-button">Reject</button>
                                                </form>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>

            <?php else: ?>
                <p class="text-danger"><?= $error; ?></p>
            <?php endif; ?>

        </div>
    </div>

    <script src="../js/script.js"></script>
    <script src="../js/bootstrap.bundle.min.js?v=<?= time(); ?>" defer></script>
    <script src="https://unpkg.com/scrollreveal"></script>
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            <?php if ($rental): ?>
                var calendarEl = document.getElementById('calendar');
                var calendar = new FullCalendar.Calendar(calendarEl, {
                    initialView: 'dayGridMonth',
                    events: [
                        { title: 'Rent Start', start: '<?= $rental['start_date']; ?>', color: 'green' },
                        { title: 'Rent Due', start: '<?= $rental['end_date']; ?>', color: 'red' }
                    ]
                });
                calendar.render();
            <?php endif; ?>
        });
    </script>
</body>

</html>