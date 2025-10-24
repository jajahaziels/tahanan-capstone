<?php
require_once '../connection.php';
require_once '../session_auth.php';

// Ensure tenant is logged in
if (!isset($_SESSION['tenant_id'])) {
    die("Unauthorized access. Please log in.");
}

$tenant_id = (int) $_SESSION['tenant_id'];

// Fetch payment history
$sql = "
    SELECT 
        p.*,
        r.start_date,
        r.end_date,
        l.listingName,
        l.address,
        ll.firstName AS landlord_firstName,
        ll.lastName AS landlord_lastName
    FROM paymenttbl p
    JOIN renttbl r ON p.rental_id = r.ID
    JOIN listingtbl l ON r.listing_id = l.ID
    JOIN landlordtbl ll ON l.landlord_id = ll.ID
    WHERE r.tenant_id = ?
    ORDER BY p.payment_date DESC
";

$payments = [];
if ($stmt = $conn->prepare($sql)) {
    $stmt->bind_param('i', $tenant_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    while ($row = $result->fetch_assoc()) {
        $payments[] = $row;
    }
    
    $stmt->close();
}

// Calculate summary
$total_paid = 0;
$pending_count = 0;
$approved_count = 0;

foreach ($payments as $payment) {
    if ($payment['status'] == 'approved') {
        $total_paid += $payment['amount'];
        $approved_count++;
    } elseif ($payment['status'] == 'pending') {
        $pending_count++;
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
    <title>Payment History</title>
    <style>
        .tenant-page {
            margin-top: 140px;
            margin-bottom: 60px;
        }

        .page-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
        }

        .page-header h1 {
            margin: 0;
            color: #333;
        }

        .back-btn {
            background: #8D0B41;
            color: white;
            padding: 10px 20px;
            border: none;
            border-radius: 8px;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            font-weight: 500;
            transition: all 0.3s;
        }

        .back-btn:hover {
            background: #6d0832;
            transform: translateY(-2px);
            color: white;
        }

        /* Summary Cards */
        .summary-cards {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .summary-card {
            background: #8D0B41;
            padding: 25px;
            border-radius: 12px;
            color: white;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
        }

        .summary-card:nth-child(2) {
            background: #8D0B41;
        }

        .summary-card:nth-child(3) {
            background: #8D0B41;
        }

        .summary-card h3 {
            font-size: 14px;
            margin: 0 0 10px 0;
            opacity: 0.9;
            color: white;
        }

        .summary-card .value {
            font-size: 32px;
            font-weight: bold;
            margin: 0;
            color: white;
        }

        /* Filter Tabs */
        .filter-tabs {
            display: flex;
            gap: 10px;
            margin-bottom: 25px;
            border-bottom: 2px solid #e0e0e0;
            flex-wrap: wrap;
        }

        .filter-tab {
            padding: 10px 20px;
            background: transparent;
            border: none;
            border-bottom: 3px solid transparent;
            cursor: pointer;
            font-weight: 500;
            color: #666;
            transition: all 0.3s;
        }

        .filter-tab:hover {
            color: #8D0B41;
        }

        .filter-tab.active {
            color: #8D0B41;
            border-bottom-color: #8D0B41;
        }

        /* Payment Timeline */
        .payment-timeline {
            position: relative;
        }

        .timeline-item {
            position: relative;
            padding-left: 40px;
            padding-bottom: 30px;
        }

        .timeline-item:last-child {
            padding-bottom: 0;
        }

        .timeline-item::before {
            content: '';
            position: absolute;
            left: 12px;
            top: 30px;
            bottom: -30px;
            width: 2px;
            background: #e0e0e0;
        }

        .timeline-item:last-child::before {
            display: none;
        }

        .timeline-dot {
            position: absolute;
            left: 0;
            top: 8px;
            width: 28px;
            height: 28px;
            border-radius: 50%;
            background: white;
            border: 3px solid #8D0B41;
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: 1;
        }

        .timeline-dot i {
            font-size: 12px;
            color: #8D0B41;
        }

        .timeline-dot.pending {
            border-color: #ffc107;
        }

        .timeline-dot.pending i {
            color: #ffc107;
        }

        .timeline-dot.rejected {
            border-color: #dc3545;
        }

        .timeline-dot.rejected i {
            color: #dc3545;
        }

        /* Payment Card */
        .payment-card {
            background: white;
            border-radius: 12px;
            padding: 20px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.08);
            transition: all 0.3s;
        }

        .payment-card:hover {
            box-shadow: 0 4px 16px rgba(0,0,0,0.12);
            transform: translateY(-2px);
        }

        .payment-header {
            display: flex;
            justify-content: space-between;
            align-items: start;
            margin-bottom: 15px;
        }

        .payment-title {
            font-size: 18px;
            font-weight: 600;
            color: #333;
            margin: 0 0 5px 0;
        }

        .payment-address {
            font-size: 13px;
            color: #666;
            margin: 0;
        }

        .payment-status {
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            text-transform: uppercase;
        }

        .status-approved {
            background: #d4edda;
            color: #155724;
        }

        .status-pending {
            background: #fff3cd;
            color: #856404;
        }

        .status-rejected {
            background: #f8d7da;
            color: #721c24;
        }

        .payment-details {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 15px;
            margin-top: 15px;
            padding-top: 15px;
            border-top: 1px solid #f0f0f0;
        }

        .payment-detail {
            display: flex;
            flex-direction: column;
        }

        .payment-detail-label {
            font-size: 12px;
            color: #999;
            margin-bottom: 4px;
        }

        .payment-detail-value {
            font-size: 14px;
            color: #333;
            font-weight: 500;
        }

        .payment-amount {
            font-size: 24px;
            font-weight: bold;
            color: #8D0B41;
        }

        .payment-actions {
            display: flex;
            gap: 10px;
            margin-top: 15px;
        }

        .btn-action {
            padding: 8px 16px;
            border: 1px solid #e0e0e0;
            border-radius: 6px;
            background: white;
            cursor: pointer;
            font-size: 13px;
            transition: all 0.3s;
            display: flex;
            align-items: center;
            gap: 6px;
        }

        .btn-action:hover {
            border-color: #8D0B41;
            color: #8D0B41;
            background: #f8f9fa;
        }

        .empty-state {
            text-align: center;
            padding: 60px 20px;
        }

        .empty-state i {
            font-size: 64px;
            color: #ddd;
            margin-bottom: 20px;
        }

        .empty-state h3 {
            color: #666;
            margin-bottom: 10px;
        }

        .empty-state p {
            color: #999;
        }

        /* Proof Modal */
        .proof-modal {
            display: none;
            position: fixed;
            z-index: 9999;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0,0,0,0.8);
            animation: fadeIn 0.3s;
        }

        .proof-modal.show {
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }

        .proof-modal-content {
            position: relative;
            max-width: 90%;
            max-height: 90%;
        }

        .proof-modal-content img {
            max-width: 100%;
            max-height: 90vh;
            border-radius: 8px;
        }

        .proof-modal-close {
            position: absolute;
            top: -40px;
            right: 0;
            color: white;
            font-size: 32px;
            cursor: pointer;
        }

        @media (max-width: 768px) {
            .page-header {
                flex-direction: column;
                align-items: flex-start;
                gap: 15px;
            }

            .summary-cards {
                grid-template-columns: 1fr;
            }

            .payment-details {
                grid-template-columns: 1fr;
            }

            .payment-actions {
                flex-direction: column;
            }
        }
    </style>
</head>

<body>
    <!-- HEADER -->
    <?php include '../Components/tenant-header.php' ?>

    <div class="tenant-page">
        <div class="container m-auto">
            <div class="page-header">
                <h1><i class="fa-solid fa-history"></i> Payment History</h1>
                <a href="tenant-rental.php" class="back-btn">
                    <i class="fa-solid fa-arrow-left"></i>
                    Back to Rental Info
                </a>
            </div>

            <!-- Summary Cards -->
            <div class="summary-cards">
                <div class="summary-card">
                    <h3>Total Paid</h3>
                    <p class="value">₱<?= number_format($total_paid, 2) ?></p>
                </div>
                <div class="summary-card">
                    <h3>Approved Payments</h3>
                    <p class="value"><?= $approved_count ?></p>
                </div>
                <div class="summary-card">
                    <h3>Pending Approval</h3>
                    <p class="value"><?= $pending_count ?></p>
                </div>
            </div>

            <!-- Filter Tabs -->
            <div class="filter-tabs">
                <button class="filter-tab active" onclick="filterPayments('all')">All Payments</button>
                <button class="filter-tab" onclick="filterPayments('approved')">Approved</button>
                <button class="filter-tab" onclick="filterPayments('pending')">Pending</button>
                <button class="filter-tab" onclick="filterPayments('rejected')">Rejected</button>
            </div>

            <!-- Payment Timeline -->
            <div class="payment-timeline">
                <?php if (count($payments) > 0): ?>
                    <?php foreach ($payments as $payment): ?>
                        <div class="timeline-item" data-status="<?= htmlspecialchars($payment['status']) ?>">
                            <div class="timeline-dot <?= htmlspecialchars($payment['status']) ?>">
                                <?php if ($payment['status'] == 'approved'): ?>
                                    <i class="fa-solid fa-check"></i>
                                <?php elseif ($payment['status'] == 'pending'): ?>
                                    <i class="fa-solid fa-clock"></i>
                                <?php else: ?>
                                    <i class="fa-solid fa-times"></i>
                                <?php endif; ?>
                            </div>

                            <div class="payment-card">
                                <div class="payment-header">
                                    <div>
                                        <h3 class="payment-title"><?= htmlspecialchars($payment['listingName']) ?></h3>
                                        <p class="payment-address">
                                            <i class="fa-solid fa-location-dot"></i>
                                            <?= htmlspecialchars($payment['address']) ?>
                                        </p>
                                    </div>
                                    <span class="payment-status status-<?= htmlspecialchars($payment['status']) ?>">
                                        <?= ucfirst($payment['status']) ?>
                                    </span>
                                </div>

                                <div class="payment-details">
                                    <div class="payment-detail">
                                        <span class="payment-detail-label">Amount</span>
                                        <span class="payment-amount">₱<?= number_format($payment['amount'], 2) ?></span>
                                    </div>
                                    <div class="payment-detail">
                                        <span class="payment-detail-label">Payment Date</span>
                                        <span class="payment-detail-value">
                                            <?= date('M d, Y', strtotime($payment['payment_date'])) ?>
                                        </span>
                                    </div>
                                    <div class="payment-detail">
                                        <span class="payment-detail-label">Method</span>
                                        <span class="payment-detail-value">
                                            <i class="fa-solid fa-mobile-screen"></i>
                                            <?= ucfirst($payment['payment_method']) ?>
                                        </span>
                                    </div>
                                    <div class="payment-detail">
                                        <span class="payment-detail-label">Reference No.</span>
                                        <span class="payment-detail-value">
                                            <?= htmlspecialchars($payment['reference_number']) ?>
                                        </span>
                                    </div>
                                    <div class="payment-detail">
                                        <span class="payment-detail-label">Rental Period</span>
                                        <span class="payment-detail-value">
                                            <?= date('M d', strtotime($payment['start_date'])) ?> - 
                                            <?= date('M d, Y', strtotime($payment['end_date'])) ?>
                                        </span>
                                    </div>
                                    <div class="payment-detail">
                                        <span class="payment-detail-label">Landlord</span>
                                        <span class="payment-detail-value">
                                            <?= htmlspecialchars($payment['landlord_firstName'] . ' ' . $payment['landlord_lastName']) ?>
                                        </span>
                                    </div>
                                </div>

                                <div class="payment-actions">
                                    <?php if (!empty($payment['payment_proof'])): ?>
                                        <button class="btn-action" onclick="viewProof('<?= htmlspecialchars($payment['payment_proof']) ?>')">
                                            <i class="fa-solid fa-image"></i>
                                            View Proof
                                        </button>
                                    <?php endif; ?>
                                    <button class="btn-action" onclick="window.print()">
                                        <i class="fa-solid fa-print"></i>
                                        Print Receipt
                                    </button>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="empty-state">
                        <i class="fa-solid fa-receipt"></i>
                        <h3>No Payment History</h3>
                        <p>You haven't made any payments yet.</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Proof Modal -->
    <div id="proofModal" class="proof-modal">
        <div class="proof-modal-content">
            <span class="proof-modal-close" onclick="closeProofModal()">&times;</span>
            <img id="proofImage" src="" alt="Payment Proof">
        </div>
    </div>

    <!-- MAIN JS -->
    <script src="../js/script.js" defer></script>
    <!-- BS JS -->
    <script src="../js/bootstrap.bundle.min.js"></script>
    <script>
        function filterPayments(status) {
            // Update active tab
            document.querySelectorAll('.filter-tab').forEach(tab => {
                tab.classList.remove('active');
            });
            event.target.classList.add('active');

            // Filter timeline items
            document.querySelectorAll('.timeline-item').forEach(item => {
                if (status === 'all' || item.dataset.status === status) {
                    item.style.display = 'block';
                } else {
                    item.style.display = 'none';
                }
            });
        }

        function viewProof(proofPath) {
            const modal = document.getElementById('proofModal');
            const image = document.getElementById('proofImage');
            image.src = '../TENANT/uploads/payment_proofs/' + proofPath;
            modal.classList.add('show');
            document.body.style.overflow = 'hidden';
        }

        function closeProofModal() {
            const modal = document.getElementById('proofModal');
            modal.classList.remove('show');
            document.body.style.overflow = 'auto';
        }

        // Close modal on click outside
        window.onclick = function(event) {
            const modal = document.getElementById('proofModal');
            if (event.target === modal) {
                closeProofModal();
            }
        }

        // Close modal on Escape
        document.addEventListener('keydown', function(event) {
            if (event.key === 'Escape') {
                closeProofModal();
            }
        });
    </script>
</body>

</html>