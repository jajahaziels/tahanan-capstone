<?php
require_once '../connection.php';
require_once '../session_auth.php';
include 'auto-expire-rental.php'; // Automatically expire old rentals

$rental = null;
$error = '';

// Ensure tenant is logged in
if (!isset($_SESSION['tenant_id'])) {
    die("Unauthorized access. Please log in.");
}

$tenant_id = (int) $_SESSION['tenant_id'];

// Fetch the tenant's current approved rental (only one at a time)
$sql = "
    SELECT 
        r.ID AS rental_id,
        r.date,
        r.start_date,
        r.end_date,
        r.listing_id,
        l.ID AS landlord_id,
        l.firstName AS landlord_firstName,
        l.lastName AS landlord_lastName,
        l.phoneNum AS landlord_phone,
        l.email AS landlord_email,
        ls.listingName,
        ls.address,
        ls.images
    FROM renttbl r
    JOIN listingtbl ls ON r.listing_id = ls.ID
    JOIN landlordtbl l ON ls.landlord_id = l.ID
    WHERE r.tenant_id = ? 
      AND r.status = 'approved'
    LIMIT 1
";

if ($stmt = $conn->prepare($sql)) {
    $stmt->bind_param('i', $tenant_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result && $result->num_rows > 0) {
        $rental = $result->fetch_assoc();
    } else {
        $error = "No active rental found for your account.";
    }

    $stmt->close();
} else {
    $error = "Database error: " . $conn->error;
}

// Prepare property image
$propertyImg = "../img/house1.jpeg"; // fallback
if ($rental) {
    $images = json_decode($rental['images'], true) ?? [];
    if (!empty($images[0])) {
        $propertyImg = "../LANDLORD/uploads/" . htmlspecialchars($images[0]);
    }
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
    <link rel="stylesheet" href="../css/style.css?v=<?= time(); ?>">
    <!-- LEAFLET -->
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY=" crossorigin="" />
    <!-- CALENDAR CDN -->
    <link href="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.15/index.global.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.15/index.global.min.js"></script>
    <!-- SWEETALERT -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <title>Rental Information</title>
    <style>
        .tenant-page {
            margin-top: 140px;
        }

        #calendar {
            max-width: 500px;
            height: 350px;
            margin: 40px auto;
        }

        .rental-details {
            background-color: var(--bg-color);
            padding: 20px;
            border-radius: 20px;
            box-shadow: 2px 2px 10px rgba(0, 0, 0, 0.165);
        }

        .carousel-inner {
            height: 300px !important;
        }

        #carouselExample {
            max-width: 500px !important;
            margin-top: 80px !important;
        }

        #carouselExample img {
            height: 400px !important;
            object-fit: cover !important;
            border-radius: 20px !important;
        }

        .payment-modal {
            display: none;
            position: fixed;
            z-index: 9999;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.6);
            backdrop-filter: blur(5px);
            animation: fadeIn 0.3s ease;
            overflow-y: auto;
        }

        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }

        .payment-modal.show {
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }

        .payment-modal-content {
            background: white;
            padding: 25px;
            border-radius: 16px;
            max-width: 450px;
            width: 100%;
            max-height: 90vh;
            overflow-y: auto;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.3);
            position: relative;
            animation: slideUp 0.3s ease;
        }

        @keyframes slideUp {
            from { 
                transform: translateY(50px);
                opacity: 0;
            }
            to { 
                transform: translateY(0);
                opacity: 1;
            }
        }

        .payment-close {
            position: absolute;
            right: 15px;
            top: 15px;
            font-size: 24px;
            font-weight: bold;
            color: #999;
            cursor: pointer;
            transition: color 0.3s;
            line-height: 1;
        }

        .payment-close:hover {
            color: #333;
        }

        .payment-modal h2 {
            margin: 0 0 5px 0;
            color: #333;
            font-size: 22px;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .payment-modal h2 i {
            font-size: 20px;
        }

        .payment-subtitle {
            color: #666;
            margin-bottom: 20px;
            font-size: 13px;
        }

        .payment-summary {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
        }

        .payment-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 8px;
            font-size: 13px;
            color: #666;
        }

        .payment-row.total {
            border-top: 2px solid #ddd;
            padding-top: 10px;
            margin-top: 10px;
            font-size: 18px;
            font-weight: bold;
            color: #333;
        }

        .payment-method {
            margin-bottom: 20px;
        }

        .payment-method h3 {
            font-size: 14px;
            margin-bottom: 10px;
            color: #333;
            font-weight: 600;
        }

        .payment-options {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 8px;
        }

        .payment-option {
            border: 2px solid #e0e0e0;
            border-radius: 8px;
            padding: 12px 8px;
            text-align: center;
            cursor: pointer;
            transition: all 0.3s;
            background: white;
        }

        .payment-option:hover {
            border-color: #8D0B41;
            background: #f8f9fa;
        }

        .payment-option.selected {
            border-color: #8D0B41;
            background: linear-gradient(135deg, rgba(141, 11, 65, 0.1), rgba(141, 11, 65, 0.05));
        }

        .payment-option i {
            font-size: 20px;
            margin-bottom: 6px;
            color: #8D0B41;
        }

        .payment-option span {
            display: block;
            font-size: 11px;
            color: #666;
        }

        .payment-form-group {
            margin-bottom: 15px;
        }

        .payment-form-group label {
            display: block;
            margin-bottom: 6px;
            color: #333;
            font-weight: 500;
            font-size: 13px;
        }

        .payment-form-group input,
        .payment-form-group select {
            width: 100%;
            padding: 10px 12px;
            border: 2px solid #e0e0e0;
            border-radius: 8px;
            font-size: 14px;
            transition: border-color 0.3s;
        }

        .payment-form-group input:focus,
        .payment-form-group select:focus {
            outline: none;
            border-color: #8D0B41;
        }

        .payment-proof {
            border: 2px dashed #ccc;
            border-radius: 8px;
            padding: 15px;
            text-align: center;
            cursor: pointer;
            transition: all 0.3s;
        }

        .payment-proof:hover {
            border-color: #8D0B41;
            background: #f8f9fa;
        }

        .payment-proof i {
            font-size: 36px;
            color: #ccc;
            margin-bottom: 8px;
            display: block;
        }

        .payment-proof p {
            margin: 0;
            color: #666;
            font-size: 13px;
        }

        .payment-proof.has-file {
            border-color: #8D0B41;
            background: linear-gradient(135deg, rgba(141, 11, 65, 0.1), rgba(141, 11, 65, 0.05));
        }

        .payment-proof.has-file i {
            color: #8D0B41;
        }

        .payment-buttons {
            display: flex;
            gap: 10px;
            margin-top: 20px;
        }

        .payment-btn {
            flex: 1;
            padding: 12px;
            border: none;
            border-radius: 8px;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
        }

        .payment-btn-primary {
            background: #8D0B41;
            color: white;
        }

        .payment-btn-primary:hover {
            background: #6d0832;
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(141, 11, 65, 0.3);
        }

        .payment-btn-secondary {
            background: #e9ecef;
            color: #666;
        }

        .payment-btn-secondary:hover {
            background: #dee2e6;
        }

        @media (max-width: 576px) {
            .payment-modal-content {
                padding: 20px;
                max-width: 95%;
                max-height: 85vh;
            }

            .payment-modal h2 {
                font-size: 20px;
            }

            .payment-subtitle {
                font-size: 12px;
            }

            .payment-summary {
                padding: 12px;
            }

            .payment-row {
                font-size: 12px;
            }

            .payment-row.total {
                font-size: 16px;
            }

            .payment-options {
                grid-template-columns: repeat(3, 1fr);
                gap: 6px;
            }

            .payment-option {
                padding: 10px 6px;
            }

            .payment-option i {
                font-size: 18px;
                margin-bottom: 4px;
            }

            .payment-option span {
                font-size: 10px;
            }

            .payment-form-group input,
            .payment-form-group select {
                padding: 8px 10px;
                font-size: 13px;
            }

            .payment-proof {
                padding: 12px;
            }

            .payment-proof i {
                font-size: 30px;
            }

            .payment-buttons {
                flex-direction: column;
                gap: 8px;
            }

            .payment-btn {
                padding: 10px;
                font-size: 13px;
            }
        }

        @media (max-width: 380px) {
            .payment-modal-content {
                padding: 15px;
            }

            .payment-close {
                font-size: 20px;
                right: 10px;
                top: 10px;
            }
        }

        .payment-modal-content::-webkit-scrollbar {
            width: 6px;
        }

        .payment-modal-content::-webkit-scrollbar-track {
            background: #f1f1f1;
            border-radius: 10px;
        }

        .payment-modal-content::-webkit-scrollbar-thumb {
            background: #8D0B41;
            border-radius: 10px;
        }

        .payment-modal-content::-webkit-scrollbar-thumb:hover {
            background: #6d0832;
        }
    </style>
</head>

<body>
    <!-- HEADER -->
    <?php include '../Components/tenant-header.php' ?>

    <div class="tenant-page">
        <div class="container m-auto">
            <div class="d-flex justify-content-between">
            <h1 class="mb-1">Rental Info</h1>
            <form method="post" action="cancel-rental.php">
                <input type="hidden" name="rental_id" value="<?php echo $rental['rental_id']; ?>">
                <input type="hidden" name="listing_id" value="<?php echo htmlspecialchars($rental['listing_id']); ?>">
                <button type="button" class="main-button" onclick="openPaymentModal()">Payment</button>
                <button type="submit" class="main-button">View History</button>
            </form>
            </div>

            <?php if ($rental): ?>
                <!-- ROW 1: Image + Calendar -->
                <div class="row justify-content-center">
                    <div class="col-lg-10">
                        <div class="row justify-content-center gy-5">
                            <div class="col-lg-6 col-sm-12">
                                <!-- Bootstrap Carousel -->
                                <div id="carouselExample" class="carousel slide">
                                    <div class="carousel-inner">
                                        <?php if (!empty($images)): ?>
                                            <?php foreach ($images as $index => $img): ?>
                                                <div class="carousel-item <?= $index === 0 ? 'active' : '' ?>">
                                                    <div class="row justify-content-center">
                                                        <div class="col-lg-12">
                                                            <img src="../LANDLORD/uploads/<?= htmlspecialchars($img); ?>"
                                                                class="d-block w-100"
                                                                style="max-height:300px; object-fit:cover;"
                                                                alt="Property Image">
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
                                                            style="max-height:300px; object-fit:cover;"
                                                            alt="No Image">
                                                    </div>
                                                </div>
                                            </div>
                                        <?php endif; ?>
                                    </div>

                                    <!-- Carousel Controls -->
                                    <button class="carousel-control-prev" type="button" data-bs-target="#carouselExample" data-bs-slide="prev">
                                        <span class="carousel-control-prev-icon" aria-hidden="true"></span>
                                        <span class="visually-hidden">Previous</span>
                                    </button>
                                    <button class="carousel-control-next" type="button" data-bs-target="#carouselExample" data-bs-slide="next">
                                        <span class="carousel-control-next-icon" aria-hidden="true"></span>
                                        <span class="visually-hidden">Next</span>
                                    </button>
                                </div>
                            </div>

                            <div class="col-lg-6 col-sm-12">
                                <div id="calendar" class="mt-5"></div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- ROW 2: Property + Tenant Info -->
                <div class="row justify-content-center">
                    <div class="col-lg-10 rental-details">
                        <div class="row justify-content-center gy-5">
                            <div class="col-lg-6 col-sm-12">
                                <h2><?php echo htmlspecialchars($rental['listingName']); ?></h2>
                                <p><strong>Address:</strong> <?php echo htmlspecialchars($rental['address']); ?></p>
                                <p><strong>Rental Start Date:</strong>
                                    <?php echo date("F j, Y", strtotime($rental['start_date'])); ?>
                                </p>
                                <p><strong>Rental Due Date:</strong>
                                    <?php echo date("F j, Y", strtotime($rental['end_date'])); ?>
                                </p>
                                <form action="tenant-extend-request.php" method="POST" style="display:inline;">
                                    <input type="hidden" name="rental_id" value="<?= $rental['rental_id'] ?>">
                                    <input type="hidden" name="listing_id" value="<?= $rental['listing_id'] ?>">
                                    <input type="date" name="new_end_date" required>
                                    <button type="submit" class="small-button">Extend</button>
                                </form>
                                <form action="tenant-cancel-request.php" method="POST" style="display:inline;">
                                    <input type="hidden" name="rent_id" value="<?= $rental['rental_id'] ?>">
                                    <input type="hidden" name="tenant_id" value="<?= $_SESSION['tenant_id'] ?>">
                                    <input type="hidden" name="landlord_id" value="<?= $rental['landlord_id'] ?>">
                                    <input type="hidden" name="listing_id" value="<?= $rental['listing_id'] ?>">
                                    <button type="submit" class="small-button"
                                        onclick="return confirm('Request to cancel this rental?')">Cancel</button>
                                </form>





                            </div>

                            <div class="col-lg-5 col-sm-12">
                                <h2>Landlord Information</h2>
                                <p><strong>Name: </strong><?php echo htmlspecialchars(ucwords(strtolower($rental['landlord_firstName'] . ' ' . $rental['landlord_lastName']))); ?>
                                </p>
                                <p><strong>Phone:</strong> <?php echo htmlspecialchars($rental['landlord_phone']); ?></p>
                                <p><strong>Email:</strong> <?php echo htmlspecialchars($rental['landlord_email']); ?></p>
                                <button class="small-button"
                                    onclick="window.location.href='landlord-profile.php?id=<?= htmlspecialchars($rental['landlord_id']); ?>'">
                                    <i class="fa-solid fa-user"></i>
                                </button>
                                <button class="small-button"
                                    onclick="window.location.href='tenant-messages.php?landlord_id=<?= htmlspecialchars($rental['landlord_id']); ?>'">
                                    <i class="fas fa-comment-dots"></i>
                                </button>

                            </div>
                        </div>
                    </div>
                </div>
            <?php else: ?>
                <p><?php echo $error; ?></p>
            <?php endif; ?>
        </div>
    </div>
    
    <div id="paymentModal" class="payment-modal">
    <div class="payment-modal-content">
        <span class="payment-close" onclick="closePaymentModal()">&times;</span>
        
        <h2><i class="fa-solid fa-credit-card"></i> Payment</h2>
        <p class="payment-subtitle">Complete your rent payment securely</p>

        
        <div class="payment-summary">
            <div class="payment-row">
                <span>Property:</span>
                <strong><?php echo htmlspecialchars($rental['listingName'] ?? 'N/A'); ?></strong>
            </div>
            <div class="payment-row">
                <span>Period:</span>
                <span><?php echo date("M j", strtotime($rental['start_date'] ?? 'now')); ?> - <?php echo date("M j, Y", strtotime($rental['end_date'] ?? 'now')); ?></span>
            </div>
            <div class="payment-row total">
                <span>Total Amount:</span>
                <span>₱ 5,000.00</span> 
            </div>
        </div>

        <form id="paymentForm" method="POST" action="process-payment.php" enctype="multipart/form-data">
            <input type="hidden" name="rental_id" value="<?php echo $rental['rental_id'] ?? ''; ?>">
            
            <!-- Payment Method -->
            <div class="payment-method">
                <h3>Payment Method</h3>
                <div class="payment-options">
                    <div class="payment-option selected" onclick="selectPaymentMethod('gcash', this)">
                        <i class="fa-solid fa-mobile-screen"></i>
                        <span>GCash</span>
                    </div>
                    <div class="payment-option" onclick="selectPaymentMethod('maya', this)">
                        <i class="fa-solid fa-wallet"></i>
                        <span>Maya</span>
                    </div>
                    <div class="payment-option" onclick="selectPaymentMethod('bank', this)">
                        <i class="fa-solid fa-building-columns"></i>
                        <span>Bank</span>
                    </div>
                </div>
                <input type="hidden" name="payment_method" id="paymentMethodInput" value="gcash">
            </div>

            <!-- Payment Details -->
            <div class="payment-form-group">
                <label>Reference Number</label>
                <input type="text" name="reference_number" placeholder="Enter transaction reference number" required>
            </div>

            <div class="payment-form-group">
                <label>Amount Paid</label>
                <input type="number" name="amount" placeholder="5000.00" step="0.01" required>
            </div>

            <!-- Upload Proof -->
            <div class="payment-form-group">
                <label>Upload Proof of Payment</label>
                <input type="file" id="paymentProofInput" name="payment_proof" accept="image/*" style="display: none;" onchange="handleFileSelect(this)" required>
                <div class="payment-proof" onclick="document.getElementById('paymentProofInput').click()">
                    <i class="fa-solid fa-cloud-arrow-up"></i>
                    <p id="proofText">Click to upload screenshot</p>
                </div>
            </div>

            <!-- Buttons -->
            <div class="payment-buttons">
                <button type="button" class="payment-btn payment-btn-secondary" onclick="closePaymentModal()">
                    Cancel
                </button>
                <button type="submit" class="payment-btn payment-btn-primary">
                    <i class="fa-solid fa-paper-plane"></i> Submit Payment
                </button>
            </div>
        </form>
    </div>
</div>

<script>
function openPaymentModal() {
    document.getElementById('paymentModal').classList.add('show');
    document.body.style.overflow = 'hidden';
}

function closePaymentModal() {
    document.getElementById('paymentModal').classList.remove('show');
    document.body.style.overflow = 'auto';
}

function selectPaymentMethod(method, element) {
    
    document.querySelectorAll('.payment-option').forEach(opt => {
        opt.classList.remove('selected');
    });
    
    
    element.classList.add('selected');
    

    document.getElementById('paymentMethodInput').value = method;
}

function handleFileSelect(input) {
    const proofDiv = document.querySelector('.payment-proof');
    const proofText = document.getElementById('proofText');
    
    if (input.files && input.files[0]) {
        proofDiv.classList.add('has-file');
        proofText.textContent = input.files[0].name;
    }
}


window.onclick = function(event) {
    const modal = document.getElementById('paymentModal');
    if (event.target === modal) {
        closePaymentModal();
    }
}

document.addEventListener('keydown', function(event) {
    if (event.key === 'Escape') {
        closePaymentModal();
    }
});
</script>

</body>
<!-- MAIN JS -->
<script src="../js/script.js" defer></script>
<!-- BS JS -->
<script src="../js/bootstrap.bundle.min.js"></script>
<!-- SCROLL REVEAL -->
<script src="https://unpkg.com/scrollreveal"></script>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        var calendarEl = document.getElementById('calendar');

        var calendar = new FullCalendar.Calendar(calendarEl, {
            initialView: 'dayGridMonth',
            events: 'calendar.php', // fetch Rent Start & Due Date

        });

        calendar.render();
    });
</script>

</html>