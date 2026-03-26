<?php
require_once '../connection.php';
include '../session_auth.php';

if (!isset($_SESSION['tenant_id'])) {
    die("Unauthorized access.");
}

$tenant_id = (int) $_SESSION['tenant_id'];

/* =========================
   FETCH ACTIVE LEASE
========================= */
$leaseSql = "
    SELECT le.ID AS lease_id, le.start_date, le.end_date, le.status,
           ls.listingName, ls.price
    FROM leasetbl le
    JOIN listingtbl ls ON le.listing_id = ls.ID
    WHERE le.tenant_id = ? AND le.status = 'active'
    LIMIT 1
";
$leaseStmt = $conn->prepare($leaseSql);
$leaseStmt->bind_param("i", $tenant_id);
$leaseStmt->execute();
$lease = $leaseStmt->get_result()->fetch_assoc();

/* =========================
   FETCH PAYMENT HISTORY
   Columns: id, lease_id, tenant_id, landlord_id, payment_type,
            amount, due_date, paid_date, payment_method, status,
            reference_no, remarks, created_at, updated_at
========================= */
$payments  = [];
$totalPaid = 0;

if ($lease) {
    $paymentSql = "
        SELECT
            p.id,
            p.payment_type,
            p.amount,
            p.due_date,
            p.paid_date,
            p.payment_method,
            p.status,
            p.reference_no,
            p.remarks,
            p.created_at
        FROM paymentstbl p
        WHERE p.lease_id = ? AND p.tenant_id = ?
        ORDER BY p.created_at DESC
    ";
    $payStmt = $conn->prepare($paymentSql);
    $payStmt->bind_param("ii", $lease['lease_id'], $tenant_id);
    $payStmt->execute();
    $payments = $payStmt->get_result()->fetch_all(MYSQLI_ASSOC);

    foreach ($payments as $p) {
        if (in_array(strtolower($p['status']), ['paid', 'partial'])) {
            $totalPaid += (float)($p['amount'] ?? 0);
        }
    }
}

/* =========================
   HELPERS
========================= */
function getPaymentStatusBadge($status) {
    return match (strtolower($status ?? '')) {
        'paid'    => '<span class="badge badge-paid"><i class="bi bi-check-circle-fill me-1"></i>Paid</span>',
        'partial' => '<span class="badge badge-partial"><i class="bi bi-dash-circle-fill me-1"></i>Partial</span>',
        'pending' => '<span class="badge badge-pending"><i class="bi bi-clock-fill me-1"></i>Pending</span>',
        'overdue' => '<span class="badge badge-overdue"><i class="bi bi-exclamation-circle-fill me-1"></i>Overdue</span>',
        default   => '<span class="badge badge-secondary">'.htmlspecialchars($status ?? 'N/A').'</span>'
    };
}

function getPaymentTypeLabel($type) {
    return match (strtolower($type ?? '')) {
        'rent'    => '<span class="type-pill type-rent"><i class="bi bi-house-fill"></i> Rent</span>',
        'deposit' => '<span class="type-pill type-deposit"><i class="bi bi-safe2-fill"></i> Deposit</span>',
        'penalty' => '<span class="type-pill type-penalty"><i class="bi bi-exclamation-triangle-fill"></i> Penalty</span>',
        default   => '<span class="type-pill type-other"><i class="bi bi-tag-fill"></i> '.htmlspecialchars($type ?? 'Other').'</span>'
    };
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payment History</title>

    <link rel="stylesheet" href="../css/bootstrap.min.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;500;600;700&display=swap" rel="stylesheet">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.6.0/jquery.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/twitter-bootstrap/5.2.0/js/bootstrap.min.js"></script>

    <style>
        :root {
            --primary:       #8d0b41;
            --primary-dark:  #6f0833;
            --primary-light: rgba(141, 11, 65, 0.08);
            --surface:       #ffffff;
            --bg:            #f5f6f8;
            --border:        #e2e8f0;
            --text-main:     #25343F;
            --text-muted:    #718096;
        }

        *, *::before, *::after { box-sizing: border-box; }

        body {
            font-family: "Montserrat", sans-serif;
            background: var(--bg);
            font-size: 14px;
            color: var(--text-main);
        }

        .container { margin-top: 130px; margin-bottom: 60px; }

        /* ── Page Header ── */
        .page-header {
            display: flex;
            align-items: center;
            gap: 14px;
            margin-bottom: 24px;
            flex-wrap: wrap;
        }
        .back-btn {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            background: var(--surface);
            border: 1px solid var(--border);
            border-radius: 10px;
            padding: 8px 16px;
            font-size: 0.85rem;
            font-weight: 500;
            color: var(--text-main);
            text-decoration: none;
            transition: all 0.2s ease;
        }
        .back-btn:hover {
            border-color: var(--primary);
            color: var(--primary);
            box-shadow: 0 2px 8px rgba(141,11,65,0.12);
        }
        .page-title {
            font-size: 1.5rem;
            font-weight: 700;
            color: var(--text-main);
            display: flex;
            align-items: center;
            gap: 10px;
            margin: 0;
        }
        .page-title i { color: var(--primary); }

        /* ── Apt Info Bar ── */
        .apt-info-bar {
            background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%);
            border-radius: 12px;
            padding: 16px 22px;
            margin-bottom: 22px;
            display: flex;
            align-items: center;
            gap: 14px;
            color: #fff;
            flex-wrap: wrap;
        }
        .apt-info-bar > i      { font-size: 1.6rem; opacity: 0.85; }
        .apt-info-name         { font-weight: 700; font-size: 1rem; }
        .apt-info-sub          { font-size: 0.82rem; opacity: 0.8; margin-top: 2px; }
        .apt-info-price        { margin-left: auto; text-align: right; }
        .apt-info-price .label { font-size: 0.75rem; opacity: 0.75; }
        .apt-info-price .value { font-size: 1.3rem; font-weight: 700; }

        /* ── Summary Cards ── */
        .summary-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(190px, 1fr));
            gap: 16px;
            margin-bottom: 24px;
        }
        .summary-card {
            background: var(--surface);
            border-radius: 14px;
            padding: 18px 20px;
            border: 1px solid var(--border);
            box-shadow: 0 4px 14px rgba(0,0,0,0.05);
            position: relative;
            overflow: hidden;
            transition: transform 0.25s ease, box-shadow 0.25s ease;
        }
        .summary-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 10px 24px rgba(0,0,0,0.09);
        }
        .summary-card::before {
            content: "";
            position: absolute;
            top: 0; left: 0;
            width: 5px; height: 100%;
            border-radius: 14px 0 0 14px;
        }
        .summary-card.green::before { background: linear-gradient(to bottom, #56ab2f, #a8e063); }
        .summary-card.blue::before  { background: linear-gradient(to bottom, #2196f3, #64b5f6); }
        .summary-card.red::before   { background: linear-gradient(to bottom, #f00000, #dc281e); }
        .summary-card.gold::before  { background: linear-gradient(to bottom, #f7971e, #ffd200); }

        .summary-icon {
            position: absolute;
            top: 14px; right: 14px;
            font-size: 1.7rem;
            opacity: 0.08;
            color: var(--text-main);
        }
        .summary-label {
            font-size: 0.72rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.06em;
            color: var(--text-muted);
            margin-bottom: 6px;
        }
        .summary-value {
            font-size: 1.4rem;
            font-weight: 700;
            color: var(--text-main);
            line-height: 1.2;
        }
        .summary-sub {
            font-size: 0.76rem;
            color: var(--text-muted);
            margin-top: 3px;
        }

        /* ── Table Section ── */
        .table-section {
            background: var(--surface);
            border-radius: 14px;
            border: 1px solid var(--border);
            box-shadow: 0 6px 18px rgba(0,0,0,0.06);
            overflow: hidden;
        }
        .table-section-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 18px 22px;
            border-bottom: 2px solid var(--border);
            flex-wrap: wrap;
            gap: 12px;
        }
        .section-title {
            font-size: 1rem;
            font-weight: 600;
            color: var(--text-main);
            display: flex;
            align-items: center;
            gap: 8px;
            margin: 0;
        }
        .section-title i { color: var(--primary); }

        /* Filters */
        .filter-group { display: flex; align-items: center; gap: 10px; flex-wrap: wrap; }
        .filter-input {
            border: 1px solid var(--border);
            border-radius: 8px;
            padding: 7px 12px;
            font-size: 0.83rem;
            font-family: "Montserrat", sans-serif;
            color: var(--text-main);
            outline: none;
            transition: border-color 0.2s;
            background: #fff;
        }
        .filter-input:focus {
            border-color: var(--primary);
            box-shadow: 0 0 0 0.15rem rgba(141,11,65,0.18);
        }

        /* ── Table ── */
        .payments-table { width: 100%; border-collapse: collapse; }
        .payments-table thead th {
            background: #f8fafc;
            color: var(--text-muted);
            text-transform: uppercase;
            font-size: 0.7rem;
            font-weight: 600;
            letter-spacing: 0.06em;
            padding: 13px 16px;
            border-bottom: 1px solid var(--border);
            white-space: nowrap;
        }
        .payments-table tbody tr {
            border-bottom: 1px solid #f0f4f8;
            transition: background 0.15s ease;
        }
        .payments-table tbody tr:hover { background: #fffafb; }
        .payments-table tbody tr:last-child { border-bottom: none; }
        .payments-table td {
            padding: 14px 16px;
            color: #4a5568;
            font-size: 0.87rem;
            vertical-align: middle;
        }
        .payments-table td.amount-col {
            font-weight: 700;
            font-size: 0.95rem;
            color: var(--text-main);
        }

        /* Badges */
        .badge {
            font-family: "Montserrat", sans-serif;
            font-weight: 500;
            font-size: 0.74rem;
            letter-spacing: 0.2px;
            padding: 5px 10px;
            border-radius: 20px;
            display: inline-flex;
            align-items: center;
        }
        .badge-paid      { background: #d4edda; color: #155724; }
        .badge-partial   { background: #cce5ff; color: #004085; }
        .badge-pending   { background: #fff3cd; color: #856404; }
        .badge-overdue   { background: #f8d7da; color: #721c24; }
        .badge-secondary { background: #e2e8f0; color: #4a5568; }

        /* Payment type pills */
        .type-pill {
            display: inline-flex;
            align-items: center;
            gap: 4px;
            font-size: 0.76rem;
            font-weight: 600;
            padding: 4px 10px;
            border-radius: 20px;
        }
        .type-rent    { background: var(--primary-light); color: var(--primary); }
        .type-deposit { background: #e8f5e9; color: #2e7d32; }
        .type-penalty { background: #fff3e0; color: #e65100; }
        .type-other   { background: #e2e8f0; color: #4a5568; }

        /* Method pill */
        .method-pill {
            display: inline-flex;
            align-items: center;
            gap: 4px;
            background: #f1f5f9;
            color: #4a5568;
            border-radius: 20px;
            padding: 4px 10px;
            font-size: 0.76rem;
            font-weight: 600;
            text-transform: capitalize;
        }

        /* Reference code */
        .ref-code {
            font-family: "Courier New", monospace;
            font-size: 0.78rem;
            background: #f1f5f9;
            padding: 3px 8px;
            border-radius: 5px;
            color: #4a5568;
        }

        /* Table footer */
        .table-footer {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 13px 18px;
            border-top: 1px solid var(--border);
            flex-wrap: wrap;
            gap: 8px;
            font-size: 0.82rem;
            color: var(--text-muted);
        }

        /* Empty state */
        .empty-state { text-align: center; padding: 55px 20px; }
        .empty-icon-wrap {
            width: 76px; height: 76px;
            background: var(--primary-light);
            border-radius: 50%;
            display: flex; align-items: center; justify-content: center;
            margin: 0 auto 16px;
        }
        .empty-icon-wrap i { font-size: 1.9rem; color: var(--primary); }
        .empty-state h5 { font-weight: 600; color: var(--text-main); margin-bottom: 5px; }
        .empty-state p  { color: var(--text-muted); font-size: 0.88rem; }

        @media (max-width: 768px) {
            .summary-grid { grid-template-columns: 1fr 1fr; }
            .apt-info-price { margin-left: 0; }
        }
    </style>
</head>
<body>

    <?php include '../Components/tenant-header.php'; ?>

    <div class="container">

        <!-- Page Header -->
        <div class="page-header">
            <a href="tenant-rental.php" class="back-btn">
                <i class="bi bi-arrow-left"></i> Back
            </a>
            <h1 class="page-title">
                <i class="bi bi-receipt-cutoff"></i> Payment History
            </h1>
        </div>

        <?php if ($lease): ?>

            <!-- Apartment Info Bar -->
            <div class="apt-info-bar">
                <i class="bi bi-building"></i>
                <div>
                    <div class="apt-info-name"><?= htmlspecialchars($lease['listingName']) ?></div>
                    <div class="apt-info-sub">
                        Lease: <?= date("F j, Y", strtotime($lease['start_date'])) ?>
                        &mdash; <?= date("F j, Y", strtotime($lease['end_date'])) ?>
                    </div>
                </div>
                <div class="apt-info-price">
                    <div class="label">Monthly Rent</div>
                    <div class="value">₱<?= number_format($lease['price'], 2) ?></div>
                </div>
            </div>

            <!-- Summary Cards -->
            <?php
                $paidCount    = count(array_filter($payments, fn($p) => strtolower($p['status']) === 'paid'));
                $pendingCount = count(array_filter($payments, fn($p) => strtolower($p['status']) === 'pending'));
                $overdueCount = count(array_filter($payments, fn($p) => strtolower($p['status']) === 'overdue'));
            ?>
            <div class="summary-grid">

                <div class="summary-card green">
                    <i class="bi bi-cash-coin summary-icon"></i>
                    <div class="summary-label">Total Paid</div>
                    <div class="summary-value">₱<?= number_format($totalPaid, 2) ?></div>
                    <div class="summary-sub">Across all paid records</div>
                </div>

                <div class="summary-card blue">
                    <i class="bi bi-calendar-check summary-icon"></i>
                    <div class="summary-label">Total Transactions</div>
                    <div class="summary-value"><?= count($payments) ?></div>
                    <div class="summary-sub"><?= $paidCount ?> paid &bull; <?= $pendingCount ?> pending</div>
                </div>

                <div class="summary-card gold">
                    <i class="bi bi-house summary-icon"></i>
                    <div class="summary-label">Monthly Rent</div>
                    <div class="summary-value">₱<?= number_format($lease['price'], 2) ?></div>
                    <div class="summary-sub">Expected per month</div>
                </div>

                <div class="summary-card red">
                    <i class="bi bi-exclamation-circle summary-icon"></i>
                    <div class="summary-label">Overdue</div>
                    <div class="summary-value"><?= $overdueCount ?></div>
                    <div class="summary-sub">Record(s) marked overdue</div>
                </div>

            </div>

            <!-- Payments Table -->
            <div class="table-section">
                <div class="table-section-header">
                    <h2 class="section-title">
                        <i class="bi bi-list-ul"></i> All Transactions
                    </h2>
                    <div class="filter-group">
                        <input type="text" id="searchInput" class="filter-input"
                            placeholder="🔍 Search reference, method..." style="min-width:200px;">
                        <select id="statusFilter" class="filter-input">
                            <option value="">All Status</option>
                            <option value="paid">Paid</option>
                            <option value="partial">Partial</option>
                            <option value="pending">Pending</option>
                            <option value="overdue">Overdue</option>
                        </select>
                        <select id="typeFilter" class="filter-input">
                            <option value="">All Types</option>
                            <option value="rent">Rent</option>
                            <option value="deposit">Deposit</option>
                            <option value="penalty">Penalty</option>
                        </select>
                    </div>
                </div>

                <?php if (!empty($payments)): ?>
                    <div style="overflow-x:auto;">
                        <table class="payments-table" id="paymentsTable">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>Type</th>
                                    <th>Due Date</th>
                                    <th>Date Paid</th>
                                    <th>Amount</th>
                                    <th>Method</th>
                                    <th>Reference No.</th>
                                    <th>Status</th>
                                    <th>Remarks</th>
                                    <th>Recorded On</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($payments as $i => $p):
                                    $searchStr = strtolower(
                                        ($p['payment_type']   ?? '') . ' ' .
                                        ($p['paid_date']      ?? '') . ' ' .
                                        ($p['due_date']       ?? '') . ' ' .
                                        ($p['payment_method'] ?? '') . ' ' .
                                        ($p['reference_no']   ?? '') . ' ' .
                                        ($p['status']         ?? '') . ' ' .
                                        ($p['remarks']        ?? '')
                                    );
                                ?>
                                    <tr data-status="<?= strtolower($p['status'] ?? '') ?>"
                                        data-type="<?= strtolower($p['payment_type'] ?? '') ?>"
                                        data-search="<?= htmlspecialchars($searchStr) ?>">

                                        <td class="text-muted" style="font-size:0.8rem;"><?= $i + 1 ?></td>

                                        <td><?= getPaymentTypeLabel($p['payment_type']) ?></td>

                                        <td>
                                            <?= $p['due_date']
                                                ? date("M j, Y", strtotime($p['due_date']))
                                                : '<span class="text-muted">—</span>' ?>
                                        </td>

                                        <td>
                                            <?= $p['paid_date']
                                                ? date("M j, Y", strtotime($p['paid_date']))
                                                : '<span class="text-muted" style="font-size:0.82rem;">Not yet paid</span>' ?>
                                        </td>

                                        <td class="amount-col">
                                            <?= $p['amount'] !== null
                                                ? '₱' . number_format($p['amount'], 2)
                                                : '<span class="text-muted">—</span>' ?>
                                        </td>

                                        <td>
                                            <?php if (!empty($p['payment_method'])): ?>
                                                <span class="method-pill">
                                                    <i class="bi bi-credit-card"></i>
                                                    <?= htmlspecialchars(str_replace('_', ' ', $p['payment_method'])) ?>
                                                </span>
                                            <?php else: ?>
                                                <span class="text-muted">—</span>
                                            <?php endif; ?>
                                        </td>

                                        <td>
                                            <?= !empty($p['reference_no'])
                                                ? '<span class="ref-code">'.htmlspecialchars($p['reference_no']).'</span>'
                                                : '<span class="text-muted">—</span>' ?>
                                        </td>

                                        <td><?= getPaymentStatusBadge($p['status']) ?></td>

                                        <td style="max-width:160px; font-size:0.8rem; color:var(--text-muted);">
                                            <?= !empty($p['remarks'])
                                                ? htmlspecialchars($p['remarks'])
                                                : '<span class="text-muted">—</span>' ?>
                                        </td>

                                        <td style="font-size:0.8rem; color:var(--text-muted); white-space:nowrap;">
                                            <?= $p['created_at']
                                                ? date("M j, Y", strtotime($p['created_at']))
                                                : '—' ?>
                                        </td>

                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>

                    <div class="table-footer">
                        <span id="rowCount"><?= count($payments) ?> transaction(s) found</span>
                        <span>Total paid: <strong style="color:var(--primary);">₱<?= number_format($totalPaid, 2) ?></strong></span>
                    </div>

                <?php else: ?>
                    <div class="empty-state">
                        <div class="empty-icon-wrap">
                            <i class="bi bi-receipt"></i>
                        </div>
                        <h5>No Payment Records Yet</h5>
                        <p>Your payment history will appear here once your landlord records a payment.</p>
                    </div>
                <?php endif; ?>

            </div><!-- end table-section -->

        <?php else: ?>
            <div class="table-section">
                <div class="empty-state">
                    <div class="empty-icon-wrap">
                        <i class="bi bi-house-x"></i>
                    </div>
                    <h5>No Active Lease Found</h5>
                    <p>Payment history is only available when you have an active lease.</p>
                    <a href="tenant.php" class="btn mt-3 text-white"
                        style="background:var(--primary);border:none;border-radius:25px;padding:8px 22px;font-family:Montserrat,sans-serif;font-weight:500;">
                        Find Apartment
                    </a>
                </div>
            </div>
        <?php endif; ?>

    </div><!-- end container -->

    <script src="../js/bootstrap.bundle.min.js"></script>
    <script>
        const searchInput  = document.getElementById('searchInput');
        const statusFilter = document.getElementById('statusFilter');
        const typeFilter   = document.getElementById('typeFilter');
        const rows         = document.querySelectorAll('#paymentsTable tbody tr');
        const rowCount     = document.getElementById('rowCount');

        function filterRows() {
            const q      = searchInput?.value.toLowerCase() ?? '';
            const status = statusFilter?.value.toLowerCase() ?? '';
            const type   = typeFilter?.value.toLowerCase()   ?? '';
            let visible  = 0;

            rows.forEach(row => {
                const matchSearch = !q      || row.dataset.search.includes(q);
                const matchStatus = !status || row.dataset.status === status;
                const matchType   = !type   || row.dataset.type   === type;
                const show        = matchSearch && matchStatus && matchType;
                row.style.display = show ? '' : 'none';
                if (show) visible++;
            });

            if (rowCount) rowCount.textContent = visible + ' transaction(s) found';
        }

        searchInput?.addEventListener('input',  filterRows);
        statusFilter?.addEventListener('change', filterRows);
        typeFilter?.addEventListener('change',   filterRows);
    </script>

</body>
</html>