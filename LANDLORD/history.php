<?php
require_once '../connection.php';
include '../session_auth.php';

$landlord_id = $_SESSION['landlord_id'];

$verifyQuery = "SELECT verification_status, admin_rejection_reason FROM landlordtbl WHERE ID = ?";
$verifyStmt  = $conn->prepare($verifyQuery);
$verifyStmt->bind_param("i", $landlord_id);
$verifyStmt->execute();
$resultVerify = $verifyStmt->get_result();
$landlord     = $resultVerify->fetch_assoc();
$status       = $landlord['verification_status'] ?? 'unverified';
$reason       = $landlord['admin_rejection_reason'] ?? '';

$activeLeasesSql = "
    SELECT ls.ID as lease_id, ls.tenant_id, ls.start_date, ls.end_date, ls.rent
    FROM leasetbl ls
    WHERE ls.landlord_id = ? AND ls.status = 'active'
";
$alStmt = $conn->prepare($activeLeasesSql);
$alStmt->bind_param("i", $landlord_id);
$alStmt->execute();
$activeLeases = $alStmt->get_result()->fetch_all(MYSQLI_ASSOC);

$today = new DateTime();

foreach ($activeLeases as $al) {
    $leaseStart = new DateTime($al['start_date']);
    $cursor     = clone $leaseStart;
    $cursor->modify('first day of this month');

    while ($cursor <= $today) {
        $dueYear  = (int)$cursor->format('Y');
        $dueMonth = (int)$cursor->format('m');

        $chkSql  = "
            SELECT COUNT(*) AS cnt FROM paymentstbl
            WHERE lease_id = ? AND tenant_id = ?
              AND payment_type = 'rent'
              AND YEAR(due_date) = ? AND MONTH(due_date) = ?
        ";
        $chkStmt = $conn->prepare($chkSql);
        $chkStmt->bind_param("iiii", $al['lease_id'], $al['tenant_id'], $dueYear, $dueMonth);
        $chkStmt->execute();
        $cnt = $chkStmt->get_result()->fetch_assoc()['cnt'];

        if ($cnt == 0 && $cursor < $today) {
            $dueDate = (clone $cursor)->modify('last day of this month')->format('Y-m-d');
            if (new DateTime($dueDate) < $today) {
                $insSql = "
                    INSERT INTO paymentstbl
                        (lease_id, tenant_id, landlord_id, payment_type, amount, due_date,
                         paid_date, payment_method, status, reference_no, remarks, created_at)
                    VALUES (?, ?, ?, 'rent', ?, ?, NULL, NULL, 'overdue', NULL, NULL, NOW())
                ";
                $insStmt = $conn->prepare($insSql);
                $rentAmt = $al['rent'];
                $insStmt->bind_param("iiids", $al['lease_id'], $al['tenant_id'], $landlord_id, $rentAmt, $dueDate);
                $insStmt->execute();
            }
        }

        $cursor->modify('+1 month');
    }
}

$query = "SELECT 
    t.ID as tenant_id,
    t.firstName,
    t.lastName,
    t.profilePic,
    l.listingName as property_name,
    ls.ID as lease_id,
    ls.rent as amount,
    ls.pdf_path,
    MAX(p.paid_date) AS last_payment_date,
    (SELECT COUNT(*) FROM lease_renewaltbl r WHERE r.lease_id = ls.ID AND r.landlord_status = 'pending') AS pending_renewal,
    (SELECT COUNT(*) FROM lease_terminationstbl tt WHERE tt.lease_id = ls.ID AND tt.landlord_status = 'pending') AS pending_termination,
    (SELECT tt.reason FROM lease_terminationstbl tt WHERE tt.lease_id = ls.ID AND tt.landlord_status = 'pending' ORDER BY tt.terminated_at DESC LIMIT 1) AS termination_reason,
    (SELECT tt.terminated_at FROM lease_terminationstbl tt WHERE tt.lease_id = ls.ID AND tt.landlord_status = 'pending' ORDER BY tt.terminated_at DESC LIMIT 1) AS termination_date,
    (SELECT rr.landlord_response FROM lease_renewaltbl rr WHERE rr.lease_id = ls.ID ORDER BY rr.requested_date DESC LIMIT 1) AS renewal_response,
    (SELECT COUNT(*) FROM paymentstbl pv WHERE pv.lease_id = ls.ID AND pv.status = 'pending_verification') AS pending_payments
FROM leasetbl ls
JOIN tenanttbl t ON ls.tenant_id = t.ID
JOIN listingtbl l ON ls.listing_id = l.ID
LEFT JOIN paymentstbl p ON ls.ID = p.lease_id AND p.status IN ('paid','partial')
WHERE ls.landlord_id = ?
AND ls.status = 'active'
GROUP BY 
    ls.ID, t.ID, t.firstName, t.lastName,
    t.profilePic, l.listingName, ls.rent, ls.pdf_path";

$stmt = $conn->prepare($query);
$stmt->bind_param("i", $landlord_id);
$stmt->execute();
$result = $stmt->get_result();

$active_tenants = [];
while ($row = $result->fetch_assoc()) {
    $active_tenants[] = $row;
}

$complaints_query = "SELECT 
                        mr.ID as complaint_id,
                        mr.title,
                        mr.description,
                        mr.category,
                        mr.priority,
                        mr.status,
                        mr.requested_date,
                        mr.scheduled_date,
                        mr.completed_date,
                        mr.photo_path,
                        t.ID as tenant_id,
                        t.firstName,
                        t.lastName,
                        t.profilePic,
                        l.listingName as property_name
                    FROM maintenance_requeststbl mr
                    JOIN leasetbl ls ON mr.lease_id = ls.ID
                    JOIN tenanttbl t ON ls.tenant_id = t.ID
                    JOIN listingtbl l ON ls.listing_id = l.ID
                    WHERE mr.landlord_id = ?
                    ORDER BY mr.requested_date DESC";

$stmt2 = $conn->prepare($complaints_query);
$stmt2->bind_param("i", $landlord_id);
$stmt2->execute();
$result2 = $stmt2->get_result();

$complaints = [];
while ($row = $result2->fetch_assoc()) {
    $complaints[] = $row;
}

$paymentHistoryMap = [];
$histSql = "
    SELECT 
        p.id, p.payment_type, p.amount, p.due_date, p.paid_date,
        p.payment_method, p.status, p.reference_no, p.remarks, p.created_at,
        p.tenant_id, p.proof_path
    FROM paymentstbl p
    JOIN leasetbl ls ON p.lease_id = ls.ID
    WHERE ls.landlord_id = ?
    ORDER BY p.created_at DESC
";
$histStmt = $conn->prepare($histSql);
$histStmt->bind_param("i", $landlord_id);
$histStmt->execute();
$histResult = $histStmt->get_result();
while ($row = $histResult->fetch_assoc()) {
    $paymentHistoryMap[$row['tenant_id']][] = $row;
}

$total_tenants     = count($active_tenants);
$monthly_revenue   = array_sum(array_column($active_tenants, 'amount'));
$total_maintenance = count($complaints);

function getPriorityBadge($priority) {
    return match (strtolower($priority)) {
        'low'    => '<span class="badge badge-low">Low</span>',
        'medium' => '<span class="badge badge-medium">Medium</span>',
        'high'   => '<span class="badge badge-high">High</span>',
        'urgent' => '<span class="badge badge-urgent">🔴 Urgent</span>',
        default  => '<span class="badge badge-secondary">' . htmlspecialchars($priority) . '</span>'
    };
}

function getStatusBadge($status) {
    return match (strtolower($status)) {
        'pending'     => '<span class="badge badge-pending">Pending</span>',
        'in progress' => '<span class="badge badge-scheduled">Scheduled</span>',
        'completed'   => '<span class="badge badge-complete">Completed</span>',
        'rejected'    => '<span class="badge badge-overdue">Rejected</span>',
        default       => '<span class="badge badge-secondary">' . htmlspecialchars($status) . '</span>'
    };
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="shortcut icon" href="../favicon.ico" type="image/x-icon">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:ital,wght@0,100..900;1,100..900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css"
        integrity="sha512-DTOQO9RWCH3ppGqcWaEA1BIZOC6xxalwEsw9c2QQeAIftl+Vegovlnee1c9QX4TctnWMn13TZye+giMm8e2LwA=="
        crossorigin="anonymous" referrerpolicy="no-referrer" />
    <link rel="stylesheet" href="../css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <link rel="stylesheet" href="../css/style.css?v=<?= time(); ?>">
    <title>Rental Management</title>
</head>

<style>
/* Global Montserrat — but NOT on icon elements */
*:not(i):not([class^="bi"]):not([class^="fa"]) {
    font-family: 'Montserrat', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
}

:root {
    --maroon:    #8d0b41;
    --maroon-dk: #6a0831;
    --maroon-lt: #fdf2f7;
    --maroon-md: #fce7f3;
    --ink:       #1a1a2e;
    --ink2:      #4a5568;
    --ink3:      #718096;
    --surface:   #ffffff;
    --surface2:  #f8fafc;
    --surface3:  #f1f5f9;
    --border:    #e2e8f0;
    --border2:   #cbd5e0;
    --green:     #0d9488;
    --green-lt:  #f0fdf9;
    --amber:     #d97706;
    --amber-lt:  #fffbeb;
    --blue:      #2563eb;
    --blue-lt:   #eff6ff;
    --red:       #dc2626;
    --red-lt:    #fef2f2;
    --purple:    #7c3aed;
    --purple-lt: #f5f3ff;
    --radius:    14px;
    --radius-sm: 8px;
    --radius-lg: 20px;
    --shadow:    0 1px 3px rgba(0,0,0,.06), 0 4px 16px rgba(0,0,0,.04);
    --shadow-md: 0 4px 20px rgba(0,0,0,.08), 0 1px 4px rgba(0,0,0,.04);
}

body { background: #eef2f7; color: var(--ink); min-height: 100vh; }

.rm-wrapper { margin: 140px auto 60px; width: 90%; max-width: 1400px; }

/* ─── PAGE HEADER ─── */
.page-header { display:flex; align-items:flex-start; justify-content:space-between; flex-wrap:wrap; gap:12px; margin-bottom:28px; }
.page-title { font-size:1.65rem; font-weight:900; color:var(--ink); letter-spacing:-.025em; line-height:1.15; }
.page-title span { color:var(--maroon); }
.page-subtitle { font-size:.82rem; color:var(--ink3); margin-top:4px; font-weight:600; }
.header-live-badge {
    display:inline-flex; align-items:center; gap:7px;
    background:var(--maroon); color:#fff;
    padding:8px 18px; border-radius:40px; font-size:.8rem; font-weight:700;
    white-space:nowrap; box-shadow:0 4px 12px rgba(141,11,65,.25);
}
.live-dot { width:7px; height:7px; background:#fde68a; border-radius:50%; animation:pulseDot 1.5s ease-in-out infinite; }
@keyframes pulseDot { 0%,100%{opacity:1;transform:scale(1)} 50%{opacity:.5;transform:scale(1.4)} }

/* ─── STAT CARDS ─── */
.stat-grid {
    display:grid;
    grid-template-columns:repeat(auto-fit, minmax(200px,1fr));
    gap:16px; margin-bottom:30px;
}
.stat-card {
    background:var(--surface);
    border-radius:var(--radius);
    border:1px solid var(--border);
    border-left:5px solid transparent;
    padding:20px 20px 16px 18px;
    position:relative;
    transition:transform .2s, box-shadow .2s;
    box-shadow:var(--shadow);
    cursor:default;
}
.stat-card:hover { transform:translateY(-3px); box-shadow:var(--shadow-md); }

.stat-card.c-maroon { border-left-color:var(--maroon); }
.stat-card.c-green  { border-left-color:var(--green); }
.stat-card.c-amber  { border-left-color:var(--amber); }
.stat-card.c-red    { border-left-color:var(--red); }
.stat-card.c-blue   { border-left-color:var(--blue); }
.stat-card.c-purple { border-left-color:var(--purple); }

.stat-icon-wrap { width:40px; height:40px; border-radius:10px; display:flex; align-items:center; justify-content:center; font-size:1.15rem; margin-bottom:14px; }
.stat-card.c-maroon .stat-icon-wrap { background:var(--maroon-lt); color:var(--maroon); }
.stat-card.c-green  .stat-icon-wrap { background:var(--green-lt);  color:var(--green); }
.stat-card.c-amber  .stat-icon-wrap { background:var(--amber-lt);  color:var(--amber); }
.stat-card.c-red    .stat-icon-wrap { background:var(--red-lt);    color:var(--red); }
.stat-card.c-blue   .stat-icon-wrap { background:var(--blue-lt);   color:var(--blue); }
.stat-card.c-purple .stat-icon-wrap { background:var(--purple-lt); color:var(--purple); }

.stat-value { font-size:1.9rem; font-weight:900; color:var(--ink); line-height:1; letter-spacing:-.03em; }
.stat-value.is-money { font-size:1.35rem; font-weight:800; }
.stat-label { font-size:.7rem; font-weight:800; text-transform:uppercase; letter-spacing:.07em; color:var(--ink3); margin-top:6px; }
.stat-sub   { font-size:.75rem; color:var(--ink3); margin-top:2px; font-weight:600; }

.stat-card-footer { display:flex; justify-content:flex-end; margin-top:12px; }
.stat-trend { font-size:.68rem; font-weight:800; padding:3px 9px; border-radius:20px; display:inline-block; }
.trend-ok   { background:#dcfce7; color:#15803d; }
.trend-warn { background:#fef9c3; color:#92400e; }
.trend-bad  { background:#fee2e2; color:#991b1b; }

/* ─── SECTION CARDS ─── */
.section-card { background:var(--surface); border-radius:var(--radius-lg); border:1px solid var(--border); margin-bottom:24px; overflow:hidden; box-shadow:var(--shadow); }
.section-head { display:flex; align-items:center; justify-content:space-between; padding:20px 26px; border-bottom:1px solid var(--border); flex-wrap:wrap; gap:12px; }
.section-head-left { display:flex; align-items:center; gap:12px; }
.section-icon { width:38px; height:38px; border-radius:10px; background:var(--maroon-lt); color:var(--maroon); display:flex; align-items:center; justify-content:center; font-size:1.05rem; flex-shrink:0; }
.section-title { font-size:1rem; font-weight:800; color:var(--ink); }
.section-desc  { font-size:.75rem; color:var(--ink3); margin-top:1px; font-weight:600; }
.count-pill { background:var(--maroon); color:#fff; padding:5px 14px; border-radius:40px; font-size:.75rem; font-weight:800; box-shadow:0 2px 8px rgba(141,11,65,.2); }

/* ─── TABLE ─── */
.tbl-wrap { overflow-x:auto; }
.rm-table { width:100%; border-collapse:collapse; }
.rm-table thead th {
    padding:12px 18px; font-size:.68rem; font-weight:800; color:var(--ink3);
    text-transform:uppercase; letter-spacing:.07em;
    background:var(--surface2); border-bottom:1px solid var(--border);
    white-space:nowrap; text-align:left;
}
.rm-table tbody tr { border-bottom:1px solid var(--border); transition:background .15s; }
.rm-table tbody tr:last-child { border-bottom:none; }
.rm-table tbody tr:hover { background:var(--maroon-lt); }
.rm-table td { padding:14px 18px; font-size:.85rem; color:var(--ink2); vertical-align:middle; white-space:nowrap; font-weight:600; }

/* ─── TENANT CELL ─── */
.tenant-cell { display:flex; align-items:center; gap:11px; }
.avatar {
    width:38px; height:38px; border-radius:50%;
    background:linear-gradient(135deg, var(--maroon) 0%, var(--maroon-dk) 100%);
    color:#fff; display:flex; align-items:center; justify-content:center;
    font-weight:900; font-size:.82rem; flex-shrink:0;
    border:2px solid #fff; box-shadow:0 2px 8px rgba(141,11,65,.18); overflow:hidden;
}
.avatar img { width:100%; height:100%; object-fit:cover; border-radius:50%; display:block; }
.tenant-name { font-weight:800; color:var(--ink); font-size:.875rem; line-height:1.2; }
.tenant-prop { font-size:.73rem; color:var(--ink3); margin-top:2px; display:flex; align-items:center; gap:4px; font-weight:600; }

.tenant-link {
    display:inline-flex; align-items:center; gap:11px;
    text-decoration:none; color:inherit;
    border-radius:8px; padding:3px 8px 3px 3px;
    transition:background .15s;
    margin:-3px -8px -3px -3px;
}
.tenant-link:hover { background:var(--maroon-lt); }
.tenant-link:hover .tenant-name { color:var(--maroon); text-decoration:underline; }

/* ─── BADGES ─── */
.badge { display:inline-flex; align-items:center; gap:4px; padding:4px 10px; border-radius:20px; font-size:.7rem; font-weight:800; white-space:nowrap; }
.badge-low      { background:#16a34a; color:#fff; }
.badge-medium   { background:#d97706; color:#fff; }
.badge-high     { background:#dc2626; color:#fff; }
.badge-urgent   { background:#1a1a2e; color:#fde68a; }
.badge-pending  { background:#b45309; color:#fff; }
.badge-complete { background:#0d9488; color:#fff; }
.badge-scheduled{ background:#2563eb; color:#fff; }
.badge-overdue  { background:#dc2626; color:#fff; }
.badge-secondary{ background:#64748b; color:#fff; }

/* ─── PAYMENT PILL ─── */
.pay-pill { display:inline-flex; align-items:center; gap:5px; padding:5px 11px; border-radius:20px; font-size:.75rem; font-weight:700; white-space:nowrap; }
.pay-paid { background:#dcfce7; color:#15803d; }
.pay-none { background:#fee2e2; color:#991b1b; }

/* ─── BUTTONS ─── */
.btn {
    display:inline-flex; align-items:center; justify-content:center; gap:5px;
    height:32px; padding:0 13px; font-size:.78rem; font-weight:700;
    border-radius:var(--radius-sm); border:none; cursor:pointer;
    transition:all .18s; white-space:nowrap; text-decoration:none;
    font-family:'Montserrat', sans-serif; line-height:1; letter-spacing:.01em;
}
.btn-maroon { background:var(--maroon); color:#fff; box-shadow:0 2px 8px rgba(141,11,65,.2); }
.btn-maroon:hover { background:var(--maroon-dk); transform:translateY(-1px); color:#fff; }
.btn-outline { background:transparent; border:1.5px solid var(--border2); color:var(--ink2); }
.btn-outline:hover { border-color:var(--maroon); color:var(--maroon); background:var(--maroon-lt); }
.btn-blue { background:var(--blue); color:#fff; box-shadow:0 2px 8px rgba(37,99,235,.18); }
.btn-blue:hover { background:#1d4ed8; transform:translateY(-1px); color:#fff; }
.btn-danger { background:var(--red-lt); color:var(--red); border:1.5px solid #fca5a5; font-weight:800; }
.btn-danger:hover { background:var(--red); color:#fff; border-color:var(--red); transform:translateY(-1px); }
.btn-icon { width:30px; height:30px; padding:0; border-radius:var(--radius-sm); }
.action-group { display:flex; gap:6px; align-items:center; flex-wrap:nowrap; }

/* ─── PENDING CHIP ─── */
.pending-chip {
    display:inline-flex; align-items:center; gap:6px;
    background:#fff; border:1.5px solid #f59e0b; color:#92400e;
    border-radius:20px; padding:4px 11px; font-size:.73rem; font-weight:800;
    cursor:pointer; transition:all .18s; font-family:'Montserrat', sans-serif;
}
.pending-chip:hover { background:#fffbeb; }
.pulse-dot { width:7px; height:7px; background:#f59e0b; border-radius:50%; animation:pulseDot 1.2s infinite; flex-shrink:0; }

/* ─── REQUEST BUTTONS ─── */
.req-btn {
    display:inline-flex; align-items:center; justify-content:center; gap:4px;
    height:30px; padding:0 12px; font-size:.75rem; font-weight:800;
    border-radius:6px; border:none; cursor:pointer; transition:all .18s;
    font-family:'Montserrat', sans-serif; white-space:nowrap; letter-spacing:.01em;
}
.req-renewal { background:var(--blue-lt); color:var(--blue); border:1.5px solid #bfdbfe; }
.req-renewal:hover { background:var(--blue); color:#fff; border-color:var(--blue); }
.req-term    { background:var(--maroon-lt); color:var(--maroon); border:1.5px solid #fbcfe8; }
.req-term:hover { background:var(--maroon); color:#fff; border-color:var(--maroon); }

.rent-amount { font-weight:900; color:var(--ink); font-size:.875rem; }
.complaint-title { font-weight:700; color:var(--ink); font-size:.84rem; max-width:160px; overflow:hidden; text-overflow:ellipsis; white-space:nowrap; }

/* ─── EMPTY STATE ─── */
.empty-state { text-align:center; padding:64px 24px; }
.empty-state-icon { font-size:3.5rem; opacity:.18; display:block; margin-bottom:16px; }
.empty-state-title { font-size:1.05rem; font-weight:800; color:var(--ink2); margin-bottom:8px; }
.empty-state-desc  { font-size:.83rem; color:var(--ink3); font-weight:600; }

/* ─── MODALS ─── */
.modal-overlay {
    position:fixed; inset:0; background:rgba(15,20,40,.45);
    backdrop-filter:blur(3px); display:flex; align-items:center; justify-content:center;
    z-index:1055; padding:16px;
}
.modal-box { background:var(--surface); border-radius:18px; width:100%; max-width:500px; max-height:92vh; overflow-y:auto; box-shadow:0 24px 60px rgba(0,0,0,.2); animation:modalIn .22s ease; }
.modal-box-xl { max-width:860px; }
@keyframes modalIn { from{transform:translateY(14px);opacity:0} to{transform:none;opacity:1} }

.modal-head { background:var(--maroon); color:#fff; padding:18px 22px; border-radius:18px 18px 0 0; display:flex; align-items:center; justify-content:space-between; position:sticky; top:0; z-index:2; }
.modal-head h4 { font-size:.95rem; font-weight:800; display:flex; align-items:center; gap:8px; color:#fff; margin:0; }
.modal-close { background:none; border:none; color:rgba(255,255,255,.75); font-size:1.1rem; cursor:pointer; padding:4px 8px; border-radius:6px; transition:all .15s; line-height:1; font-family:'Montserrat',sans-serif; }
.modal-close:hover { color:#fff; background:rgba(255,255,255,.15); }
.modal-body { padding:24px; }
.modal-foot { padding:14px 22px; border-top:1px solid var(--border); background:var(--surface2); border-radius:0 0 18px 18px; display:flex; justify-content:flex-end; gap:10px; }

.modal-label { font-size:.75rem; font-weight:800; text-transform:uppercase; letter-spacing:.06em; color:var(--ink3); margin-bottom:6px; display:block; }
.modal-input, .modal-select, .modal-textarea {
    width:100%; border:1.5px solid var(--border); border-radius:var(--radius-sm);
    padding:10px 12px; font-size:.88rem; color:var(--ink); background:var(--surface);
    font-family:'Montserrat', sans-serif; transition:border-color .2s, box-shadow .2s; outline:none;
}
.modal-input:focus, .modal-select:focus, .modal-textarea:focus {
    border-color:var(--maroon); box-shadow:0 0 0 3px rgba(141,11,65,.1);
}

/* ─── IMPROVED COMPLAINT MODAL ─── */
.complaint-modal-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 14px;
    margin-bottom: 16px;
}
.complaint-photo-preview {
    width: 100%;
    max-height: 180px;
    object-fit: cover;
    border-radius: 10px;
    border: 2px solid var(--border);
    display: block;
}
.complaint-photo-wrap {
    grid-column: 1 / -1;
    background: var(--surface2);
    border: 1.5px dashed var(--border2);
    border-radius: 10px;
    padding: 12px;
    text-align: center;
}
.complaint-photo-wrap a {
    display: block;
}
.field-group {
    display: flex;
    flex-direction: column;
    gap: 6px;
}
.status-option-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 8px;
}
.status-radio {
    display: none;
}
.status-label {
    display: flex;
    align-items: center;
    gap: 8px;
    padding: 10px 12px;
    border: 1.5px solid var(--border);
    border-radius: 8px;
    cursor: pointer;
    font-size: .78rem;
    font-weight: 700;
    color: var(--ink2);
    transition: all .18s;
    background: var(--surface);
}
.status-label:hover { border-color: var(--maroon); background: var(--maroon-lt); }
.status-radio:checked + .status-label {
    border-color: var(--maroon);
    background: var(--maroon-lt);
    color: var(--maroon);
}
.status-label .status-dot {
    width: 10px; height: 10px;
    border-radius: 50%;
    flex-shrink: 0;
}
.sdot-pending   { background: #b45309; }
.sdot-progress  { background: #2563eb; }
.sdot-completed { background: #0d9488; }
.sdot-rejected  { background: #dc2626; }
.complaint-meta-chip {
    display: inline-flex; align-items: center; gap: 5px;
    background: var(--surface2); border: 1px solid var(--border);
    border-radius: 20px; padding: 4px 10px;
    font-size: .72rem; font-weight: 700; color: var(--ink3);
}

.info-box { border-radius:10px; padding:14px 16px; margin-bottom:16px; }
.info-box-red  { background:var(--maroon-lt); border-left:4px solid var(--maroon); }
.info-box-blue { background:var(--blue-lt);   border-left:4px solid var(--blue); }
.info-box-label { font-size:.72rem; font-weight:800; text-transform:uppercase; letter-spacing:.05em; margin-bottom:6px; display:flex; align-items:center; gap:6px; }
.info-box-red  .info-box-label { color:var(--maroon); }
.info-box-blue .info-box-label { color:var(--blue); }
.info-box-text { font-size:.88rem; color:var(--ink2); line-height:1.5; font-weight:600; }
.info-box-date { font-size:.75rem; color:var(--ink3); margin-top:5px; font-weight:600; }

.mbtn { display:inline-flex; align-items:center; gap:6px; height:38px; padding:0 20px; font-size:.84rem; font-weight:700; border-radius:var(--radius-sm); border:none; cursor:pointer; transition:all .18s; font-family:'Montserrat', sans-serif; }
.mbtn-cancel  { background:var(--surface3); color:var(--ink2); border:1px solid var(--border); }
.mbtn-cancel:hover { background:var(--border); }
.mbtn-primary { background:var(--maroon); color:#fff; }
.mbtn-primary:hover { background:var(--maroon-dk); box-shadow:0 4px 12px rgba(141,11,65,.25); }
.mbtn-approve { background:var(--green); color:#fff; }
.mbtn-approve:hover { background:#0f766e; }
.mbtn-reject  { background:var(--maroon); color:#fff; }
.mbtn-reject:hover { background:var(--maroon-dk); }

/* ─── HISTORY MODAL ─── */
.hist-hero { background:linear-gradient(120deg,var(--maroon) 0%,var(--maroon-dk) 100%); color:#fff; padding:16px 24px; display:flex; align-items:center; gap:14px; flex-wrap:wrap; }
.hist-hero-icon { font-size:1.6rem; opacity:.75; }
.hist-hero-prop { font-weight:800; font-size:.95rem; }
.hist-hero-sub  { font-size:.73rem; opacity:.7; margin-top:1px; font-weight:600; }
.hist-hero-rent-label { font-size:.68rem; opacity:.65; text-align:right; font-weight:700; }
.hist-hero-rent-val   { font-size:1.15rem; font-weight:900; text-align:right; }

.hist-stat-grid { display:grid; grid-template-columns:repeat(auto-fit,minmax(130px,1fr)); gap:12px; padding:18px 24px 10px; }
.hist-stat { background:var(--surface2); border-radius:12px; padding:13px 15px; border-left:3px solid var(--maroon); }
.hist-stat.hs-green  { border-left-color:var(--green); }
.hist-stat.hs-blue   { border-left-color:var(--blue); }
.hist-stat.hs-amber  { border-left-color:var(--amber); }
.hist-stat.hs-red    { border-left-color:var(--red); }
.hist-stat.hs-purple { border-left-color:var(--purple); }
.hist-stat-label { font-size:.65rem; font-weight:800; text-transform:uppercase; letter-spacing:.07em; color:var(--ink3); margin-bottom:4px; }
.hist-stat-val   { font-size:1.25rem; font-weight:900; color:var(--ink); line-height:1; }
.hist-stat-sub   { font-size:.68rem; color:var(--ink3); margin-top:2px; font-weight:600; }

.hist-filters { display:flex; gap:10px; padding:10px 24px 14px; flex-wrap:wrap; }
.hist-input { border:1.5px solid var(--border); border-radius:var(--radius-sm); padding:7px 11px; font-size:.8rem; color:var(--ink); background:var(--surface); font-family:'Montserrat', sans-serif; outline:none; transition:border-color .2s; font-weight:600; }
.hist-input:focus { border-color:var(--maroon); box-shadow:0 0 0 3px rgba(141,11,65,.1); }
.hist-input-search { flex:1; min-width:180px; }

.hist-tbl-wrap { overflow-x:auto; padding:0 24px; }
.hist-tbl { width:100%; border-collapse:separate; border-spacing:0 4px; }
.hist-tbl thead th { background:var(--surface2); padding:10px 12px; font-size:.65rem; font-weight:800; color:var(--ink3); text-transform:uppercase; letter-spacing:.07em; border:none; white-space:nowrap; text-align:left; }
.hist-tbl tbody tr { background:var(--surface); }
.hist-tbl tbody tr.tr-overdue td { background:#fff8f8 !important; }
.hist-tbl tbody tr.tr-verify  td { background:#fffdf0 !important; }
.hist-tbl td { padding:11px 12px; font-size:.8rem; color:var(--ink2); border-top:1px solid var(--border); border-bottom:1px solid var(--border); vertical-align:middle; white-space:nowrap; font-weight:600; }
.hist-tbl td:first-child { border-left:1px solid var(--border); border-radius:8px 0 0 8px; }
.hist-tbl td:last-child  { border-right:1px solid var(--border); border-radius:0 8px 8px 0; }

.hbadge { display:inline-flex; align-items:center; gap:4px; padding:3px 9px; border-radius:20px; font-size:.7rem; font-weight:800; }
.hb-paid    { background:#dcfce7; color:#15803d; }
.hb-partial { background:#dbeafe; color:#1e40af; }
.hb-pending { background:#fef9c3; color:#92400e; }
.hb-overdue { background:#fee2e2; color:#991b1b; }
.hb-verify  { background:#ede9fe; color:#5b21b6; }
.hb-rejected{ background:#fee2e2; color:#991b1b; }

.htype { display:inline-flex; align-items:center; gap:4px; padding:3px 8px; border-radius:20px; font-size:.7rem; font-weight:800; }
.ht-rent    { background:var(--maroon-lt); color:var(--maroon); }
.ht-deposit { background:#dcfce7; color:#15803d; }
.ht-penalty { background:#fff7ed; color:#c2410c; }
.ht-other   { background:var(--surface3); color:var(--ink3); }

.ref-mono { font-family:'Montserrat', monospace; font-size:.72rem; background:var(--surface2); padding:2px 8px; border-radius:5px; font-weight:700; }
.days-late-chip { display:inline-flex; align-items:center; gap:3px; background:#fee2e2; color:#991b1b; border-radius:20px; padding:1px 7px; font-size:.64rem; font-weight:800; margin-left:4px; }

.approve-pay-btn {
    display:inline-flex; align-items:center; gap:3px;
    background:#dcfce7; color:#15803d; border:1.5px solid #86efac;
    border-radius:6px; padding:4px 9px; font-size:.7rem; font-weight:800;
    cursor:pointer; transition:all .18s; font-family:'Montserrat', sans-serif; white-space:nowrap;
}
.approve-pay-btn:hover { background:var(--green); color:#fff; border-color:var(--green); }

.reject-pay-btn {
    display:inline-flex; align-items:center; gap:3px;
    background:#fee2e2; color:#991b1b; border:1.5px solid #fca5a5;
    border-radius:6px; padding:4px 9px; font-size:.7rem; font-weight:800;
    cursor:pointer; transition:all .18s; font-family:'Montserrat', sans-serif; white-space:nowrap;
}
.reject-pay-btn:hover { background:var(--red); color:#fff; border-color:var(--red); }

.hist-foot { display:flex; justify-content:space-between; align-items:center; padding:12px 24px; border-top:1px solid var(--border); font-size:.78rem; color:var(--ink3); flex-wrap:wrap; gap:8px; font-weight:600; }

/* ─── VERIFY GATE ─── */
.verify-gate { display:flex; align-items:center; justify-content:center; min-height:calc(100vh - 140px); }
.verify-card { background:var(--surface); border-radius:20px; padding:48px 40px; text-align:center; max-width:500px; width:100%; box-shadow:var(--shadow-md); border:1px solid var(--border); border-top:5px solid var(--maroon); }


</style>

<body>
 <?php include '../Components/landlord-header.php'; ?>

<!-- ─── VERIFICATION GATE ─── -->
<?php if ($status !== 'verified'): ?>
<div class="rm-wrapper">
    <div class="verify-gate">
        <div class="verify-card">
            <?php
            if ($status === 'pending') {
                $vIcon = 'bi-hourglass-split'; $vColor = '#d97706'; $vTitle = 'Verification in Progress';
                $vMsg  = 'Your submitted documents are currently being reviewed by the administrator.';
            } elseif ($status === 'rejected') {
                $vIcon = 'bi-x-circle-fill'; $vColor = '#dc2626'; $vTitle = 'Verification Rejected';
                $vMsg  = 'Your verification request was rejected. Please review the reason below and submit again.';
            } else {
                $vIcon = 'bi-shield-lock-fill'; $vColor = '#8d0b41'; $vTitle = 'Account Verification Required';
                $vMsg  = 'You must verify your landlord account before accessing landlord features.';
            }
            ?>
            <i class="bi <?= $vIcon ?>" style="font-size:72px;color:<?= $vColor ?>"></i>
            <h3 style="margin-top:18px;font-size:1.3rem;font-weight:800;color:var(--ink)"><?= $vTitle ?></h3>
            <p style="color:var(--ink3);margin-top:10px;font-size:.9rem;line-height:1.6;font-weight:600"><?= $vMsg ?></p>
            <?php if ($status === 'rejected'): ?>
            <div style="background:#fee2e2;border:1px solid #fecaca;border-radius:10px;padding:14px;margin-top:18px;text-align:left;font-size:.85rem;color:#991b1b;font-weight:600">
                <strong>Admin Reason:</strong><br><?= htmlspecialchars($reason) ?>
            </div>
            <?php endif; ?>
            <a href="landlord-verification.php" class="btn btn-maroon" style="margin-top:24px;height:42px;padding:0 28px;font-size:.9rem;border-radius:10px;text-decoration:none">
                Verify Your Account
            </a>
        </div>
    </div>
</div>
</body>
</html>
<?php exit; ?>
<?php endif; ?>

<!-- ─── MAIN WRAPPER ─── -->
<div class="rm-wrapper">

    <!-- PAGE HEADER -->
    <div class="page-header">
        <div>
            <div class="page-title">Rental <span>Management</span></div>
            <div class="page-subtitle">Track tenants, payments, leases, and maintenance requests</div>
        </div>
        <div class="header-live-badge">
            <span class="live-dot"></span>
            <?= $total_tenants ?> Active <?= $total_tenants === 1 ? 'Tenant' : 'Tenants' ?>
        </div>
    </div>

    <!-- STAT CARDS -->
    <?php
    $pending_verifications = 0;
    $overdue_count = 0;
    foreach ($paymentHistoryMap as $recs) {
        foreach ($recs as $r) {
            $s = strtolower($r['status']);
            if ($s === 'overdue') $overdue_count++;
            if ($s === 'pending_verification') $pending_verifications++;
        }
    }
    $renewal_count = 0; $term_count = 0;
    foreach ($active_tenants as $t) {
        $renewal_count += (int)($t['pending_renewal'] ?? 0);
        $term_count    += (int)($t['pending_termination'] ?? 0);
    }
    $pending_requests = $renewal_count + $term_count;
    $urgentCount = count(array_filter($complaints, fn($c) => strtolower($c['priority']) === 'urgent'));
    ?>
    <div class="stat-grid">

        <div class="stat-card c-maroon">
            <div class="stat-icon-wrap"><i class="bi bi-people-fill"></i></div>
            <div class="stat-value"><?= $total_tenants ?></div>
            <div class="stat-label">Active Tenants</div>
            <div class="stat-sub">Currently leasing</div>
            <div class="stat-card-footer"><div class="stat-trend trend-ok">On track</div></div>
        </div>

        <div class="stat-card c-green">
            <div class="stat-icon-wrap"><i class="bi bi-cash-coin"></i></div>
            <div class="stat-value is-money">₱<?= number_format($monthly_revenue, 0) ?></div>
            <div class="stat-label">Monthly Revenue</div>
            <div class="stat-sub">From active leases</div>
            <div class="stat-card-footer"><div class="stat-trend trend-ok">Active</div></div>
        </div>

        <div class="stat-card c-amber">
            <div class="stat-icon-wrap"><i class="bi bi-hourglass-split"></i></div>
            <div class="stat-value"><?= $pending_verifications ?></div>
            <div class="stat-label">Awaiting Review</div>
            <div class="stat-sub">Payment submissions</div>
            <div class="stat-card-footer">
                <div class="stat-trend <?= $pending_verifications > 0 ? 'trend-warn' : 'trend-ok' ?>">
                    <?= $pending_verifications > 0 ? 'Action needed' : 'All clear' ?>
                </div>
            </div>
        </div>

        <div class="stat-card c-red">
            <div class="stat-icon-wrap"><i class="bi bi-exclamation-triangle-fill"></i></div>
            <div class="stat-value"><?= $overdue_count ?></div>
            <div class="stat-label">Overdue Records</div>
            <div class="stat-sub">Unpaid rent entries</div>
            <div class="stat-card-footer">
                <div class="stat-trend <?= $overdue_count > 0 ? 'trend-bad' : 'trend-ok' ?>">
                    <?= $overdue_count > 0 ? 'Follow up' : 'None' ?>
                </div>
            </div>
        </div>

        <div class="stat-card c-blue">
            <div class="stat-icon-wrap"><i class="bi bi-file-earmark-text-fill"></i></div>
            <div class="stat-value"><?= $pending_requests ?></div>
            <div class="stat-label">Tenant Requests</div>
            <div class="stat-sub"><?= $renewal_count ?> renewal · <?= $term_count ?> termination</div>
            <div class="stat-card-footer">
                <div class="stat-trend <?= $pending_requests > 0 ? 'trend-warn' : 'trend-ok' ?>">
                    <?= $pending_requests > 0 ? 'Pending' : 'None' ?>
                </div>
            </div>
        </div>

        <div class="stat-card c-purple">
            <div class="stat-icon-wrap"><i class="bi bi-tools"></i></div>
            <div class="stat-value"><?= $total_maintenance ?></div>
            <div class="stat-label">Maintenance</div>
            <div class="stat-sub">Open requests</div>
            <div class="stat-card-footer">
                <?php if ($urgentCount > 0): ?>
                <div class="stat-trend trend-bad"><?= $urgentCount ?> urgent</div>
                <?php elseif ($total_maintenance > 0): ?>
                <div class="stat-trend trend-warn">Open</div>
                <?php else: ?>
                <div class="stat-trend trend-ok">None</div>
                <?php endif; ?>
            </div>
        </div>

    </div>

    <!-- ─── ACTIVE TENANTS SECTION ─── -->
    <div class="section-card">
        <div class="section-head">
            <div class="section-head-left">
                <div class="section-icon"><i class="bi bi-person-vcard-fill"></i></div>
                <div>
                    <div class="section-title">Active Tenants &amp; Payment Records</div>
                    <div class="section-desc">Track rent status, lease documents, and pending requests</div>
                </div>
            </div>
            <?php if (!empty($active_tenants)): ?>
            <span class="count-pill"><?= count($active_tenants) ?> <?= count($active_tenants) === 1 ? 'Tenant' : 'Tenants' ?></span>
            <?php endif; ?>
        </div>

        <?php if (!empty($active_tenants)): ?>
        <div class="tbl-wrap">
            <table class="rm-table">
                <thead>
                    <tr>
                        <th>Tenant</th>
                        <th>Rent / mo</th>
                        <th>Lease</th>
                        <th>Last Payment</th>
                        <th>Pending</th>
                        <th>Requests</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($active_tenants as $tenant):
                        $tenant_name    = ucwords(strtolower($tenant['firstName'] . ' ' . $tenant['lastName']));
                        $tenant_initial = strtoupper(substr($tenant['firstName'], 0, 1));
                        $last_payment   = $tenant['last_payment_date'] ? date("M j, Y", strtotime($tenant['last_payment_date'])) : null;
                        $termReason     = htmlspecialchars($tenant['termination_reason'] ?? 'No reason provided.');
                        $termDate       = $tenant['termination_date'] ? date("M j, Y h:i A", strtotime($tenant['termination_date'])) : '';
                        $pendingPayCount = (int)($tenant['pending_payments'] ?? 0);
                    ?>
                    <tr>
                        <td>
                            <div class="tenant-cell">
                                <?php if (!empty($tenant['profilePic'])): ?>
                                    <a href="tenant-profile.php?tenant_id=<?= $tenant['tenant_id'] ?>" style="text-decoration:none;flex-shrink:0">
                                        <div class="avatar"><img src="../uploads/<?= htmlspecialchars($tenant['profilePic']) ?>" alt=""></div>
                                    </a>
                                <?php else: ?>
                                    <div class="avatar"><?= $tenant_initial ?></div>
                                <?php endif; ?>
                                <div>
                                    <div class="tenant-name"><?= htmlspecialchars($tenant_name) ?></div>
                                    <div class="tenant-prop">
                                        <i class="bi bi-house-fill" style="font-size:.7rem;color:var(--maroon)"></i>
                                        <?= htmlspecialchars($tenant['property_name']) ?>
                                    </div>
                                </div>
                            </div>
                        </td>
                        <td><span class="rent-amount">₱<?= number_format($tenant['amount'] ?? 0, 2) ?></span></td>
                        <td>
                            <?php if (!empty($tenant['pdf_path'])): ?>
                                <a href="../uploads/<?= htmlspecialchars($tenant['pdf_path']) ?>" target="_blank" class="btn btn-outline" style="text-decoration:none">
                                    <i class="bi bi-file-earmark-pdf" style="color:var(--red)"></i> View Lease
                                </a>
                            <?php else: ?>
                                <span style="color:var(--ink3);font-weight:600">—</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <div style="display:flex;align-items:center;gap:8px;flex-wrap:wrap">
                                <span class="pay-pill <?= $last_payment ? 'pay-paid' : 'pay-none' ?>">
                                    <?php if ($last_payment): ?>
                                        <i class="bi bi-check-circle-fill"></i> <?= $last_payment ?>
                                    <?php else: ?>
                                        <i class="bi bi-x-circle-fill"></i> No Payment
                                    <?php endif; ?>
                                </span>
                                <button class="btn btn-outline view-history-btn"
                                    data-tenant-id="<?= $tenant['tenant_id'] ?>"
                                    data-tenant-name="<?= htmlspecialchars($tenant_name) ?>"
                                    data-property="<?= htmlspecialchars($tenant['property_name']) ?>"
                                    data-amount="<?= $tenant['amount'] ?? 0 ?>"
                                    style="height:28px;padding:0 10px;font-size:.73rem">
                                    <i class="bi bi-clock-history"></i> History
                                </button>
                            </div>
                        </td>
                        <td>
                            <?php if ($pendingPayCount > 0): ?>
                                <button class="pending-chip view-history-btn"
                                    data-tenant-id="<?= $tenant['tenant_id'] ?>"
                                    data-tenant-name="<?= htmlspecialchars($tenant_name) ?>"
                                    data-property="<?= htmlspecialchars($tenant['property_name']) ?>"
                                    data-amount="<?= $tenant['amount'] ?? 0 ?>"
                                    data-filter-status="pending_verification">
                                    <span class="pulse-dot"></span>
                                    <?= $pendingPayCount ?> Awaiting Review
                                </button>
                            <?php else: ?>
                                <span style="color:var(--ink3);font-size:.82rem;font-weight:600">—</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <div style="display:flex;gap:7px;flex-wrap:wrap;align-items:center">
                                <?php $hasReq = false;
                                if (($tenant['pending_renewal'] ?? 0) > 0): $hasReq = true; ?>
                                    <button class="req-btn req-renewal renewal-btn"
                                        data-lease-id="<?= $tenant['lease_id'] ?>"
                                        data-type="renewal" data-reason="" data-date="">
                                        <i class="bi bi-arrow-repeat"></i> Renewal
                                    </button>
                                <?php endif;
                                if (($tenant['pending_termination'] ?? 0) > 0): $hasReq = true; ?>
                                    <button class="req-btn req-term termination-btn"
                                        data-lease-id="<?= $tenant['lease_id'] ?>"
                                        data-type="termination"
                                        data-reason="<?= $termReason ?>"
                                        data-date="<?= $termDate ?>">
                                        <i class="bi bi-file-earmark-x-fill"></i> Termination
                                    </button>
                                <?php endif;
                                if (!$hasReq): ?>
                                    <span style="color:var(--ink3);font-size:.82rem;font-weight:600">—</span>
                                <?php endif; ?>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php else: ?>
        <div class="empty-state">
            <i class="bi bi-person-slash empty-state-icon"></i>
            <div class="empty-state-title">No Active Tenants</div>
            <div class="empty-state-desc">Once you approve applications, your active tenants and their rent status will appear here.</div>
        </div>
        <?php endif; ?>
    </div>

    <!-- ─── MAINTENANCE / COMPLAINTS SECTION ─── -->
    <div class="section-card">
        <div class="section-head">
            <div class="section-head-left">
                <div class="section-icon"><i class="bi bi-tools"></i></div>
                <div>
                    <div class="section-title">Complaint / Maintenance Requests</div>
                    <div class="section-desc">Review and respond to tenant-submitted issues</div>
                </div>
            </div>
            <?php if (!empty($complaints)): ?>
            <span class="count-pill"><?= count($complaints) ?> Request<?= count($complaints) !== 1 ? 's' : '' ?></span>
            <?php endif; ?>
        </div>

        <?php if (!empty($complaints)): ?>
        <div class="tbl-wrap">
            <table class="rm-table">
                <thead>
                    <tr>
                        <th>Tenant</th>
                        <th>Title</th>
                        <th>Category</th>
                        <th>Priority</th>
                        <th>Status</th>
                        <th>Requested</th>
                        <th>Scheduled</th>
                        <th>Completed</th>
                        <th>Photo</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($complaints as $complaint):
                        $cs       = strtolower(trim($complaint['status']));
                        $cName    = ucwords(strtolower($complaint['firstName'] . ' ' . $complaint['lastName']));
                        $cInitial = strtoupper(substr($complaint['firstName'], 0, 1));
                        $reqDate  = $complaint['requested_date'] ? date("M j, Y", strtotime($complaint['requested_date'])) : '—';
                        $sched    = $complaint['scheduled_date'] ? date("M j, Y", strtotime($complaint['scheduled_date'])) : '—';
                        $completed = $complaint['completed_date'] ? date("M j, Y", strtotime($complaint['completed_date'])) : '—';
                    ?>
                    <tr>
                        <td>
                            <a href="tenant-profile.php?tenant_id=<?= $complaint['tenant_id'] ?>" class="tenant-link">
                                <?php if (!empty($complaint['profilePic'])): ?>
                                    <div class="avatar" style="width:34px;height:34px;font-size:.75rem;flex-shrink:0">
                                        <img src="../uploads/<?= htmlspecialchars($complaint['profilePic']) ?>" alt="">
                                    </div>
                                <?php else: ?>
                                    <div class="avatar" style="width:34px;height:34px;font-size:.75rem;flex-shrink:0"><?= $cInitial ?></div>
                                <?php endif; ?>
                                <div>
                                    <div class="tenant-name" style="font-size:.82rem"><?= htmlspecialchars($cName) ?></div>
                                    <div class="tenant-prop"><?= htmlspecialchars($complaint['property_name']) ?></div>
                                </div>
                            </a>
                        </td>
                        <td>
                            <div class="complaint-title" title="<?= htmlspecialchars($complaint['title']) ?>">
                                <?= htmlspecialchars($complaint['title']) ?>
                            </div>
                        </td>
                        <td><span style="font-size:.8rem;color:var(--ink2);font-weight:600"><?= htmlspecialchars($complaint['category']) ?></span></td>
                        <td><?= getPriorityBadge($complaint['priority']) ?></td>
                        <td><?= getStatusBadge($complaint['status']) ?></td>
                        <td style="font-size:.8rem"><?= $reqDate ?></td>
                        <td style="font-size:.8rem;color:var(--ink3)"><?= $sched ?></td>
                        <td style="font-size:.8rem;color:var(--ink3)"><?= $completed ?></td>
                        <td>
    <?php if (!empty($complaint['photo_path'])): ?>
        <a href="/<?= htmlspecialchars($complaint['photo_path']); ?>" target="_blank">
            <img src="/<?= htmlspecialchars($complaint['photo_path']); ?>" 
                 style="width:60px; height:60px; object-fit:cover; border-radius:8px; cursor:pointer; border:2px solid var(--border);">
        </a>
    <?php else: ?>
        <span style="color:var(--ink3); font-size:.8rem; font-weight:600">—</span>
    <?php endif; ?>
</td>
                        <td>
                            <div class="action-group">
                                <?php if ($cs === 'completed'): ?>
                                    <button class="btn btn-danger remove-complaint-btn"
                                        style="height:30px;padding:0 12px;font-size:.74rem"
                                        data-id="<?= $complaint['complaint_id'] ?>">
                                        <i class="bi bi-trash3-fill"></i> Remove
                                    </button>
                                <?php elseif ($cs === 'rejected'): ?>
                                    <button class="btn btn-blue respond-btn"
    style="height:30px;padding:0 11px;font-size:.74rem"
    data-id="<?= $complaint['complaint_id'] ?>"
    data-title="<?= htmlspecialchars($complaint['title']) ?>"
    data-description="<?= htmlspecialchars($complaint['description'] ?? '') ?>"
    data-category="<?= htmlspecialchars($complaint['category'] ?? '') ?>"
    data-priority="<?= htmlspecialchars($complaint['priority'] ?? '') ?>"
    data-status="<?= htmlspecialchars($complaint['status'] ?? 'pending') ?>"
    data-photo="<?= htmlspecialchars($complaint['photo_path'] ?? '') ?>">
    <i class="bi bi-reply-fill"></i> Respond
</button>
                                    <button class="btn btn-danger btn-icon remove-complaint-btn"
                                        title="Remove request"
                                        data-id="<?= $complaint['complaint_id'] ?>">
                                        <i class="bi bi-trash3-fill"></i>
                                    </button>
                                <?php else: ?>
                                    <button class="btn btn-blue respond-btn"
                                        style="height:30px;padding:0 11px;font-size:.74rem"
                                        data-id="<?= $complaint['complaint_id'] ?>"
                                        data-title="<?= htmlspecialchars($complaint['title']) ?>"
                                        data-description="<?= htmlspecialchars($complaint['description'] ?? '') ?>">
                                        <i class="bi bi-reply-fill"></i> Respond
                                    </button>
                                <?php endif; ?>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php else: ?>
        <div class="empty-state">
            <i class="bi bi-tools empty-state-icon"></i>
            <div class="empty-state-title">No Complaints / Requests</div>
            <div class="empty-state-desc">Once tenants submit maintenance requests, they will appear here for your review and action.</div>
        </div>
        <?php endif; ?>
    </div>

</div><!-- /.rm-wrapper -->

<!-- ─── MAINTENANCE RESPOND MODAL ─── -->
<div class="modal-overlay" id="complaintModal" style="display:none" onclick="if(event.target===this)closeModal('complaintModal')">
    <div class="modal-box" style="max-width:580px" onclick="event.stopPropagation()">
        <form id="complaintForm" method="post" action="maintenance-respond.php">
            <div class="modal-head">
                <h4><i class="bi bi-tools"></i> <span id="compModalTitle">Respond to Complaint</span></h4>
                <button type="button" class="modal-close" onclick="closeModal('complaintModal')">✕</button>
            </div>
            <div class="modal-body" style="padding:20px 22px">
                <input type="hidden" name="complaint_id" id="complaint_id_input">

                <!-- Meta chips: category + priority -->
                <div id="compMetaRow" style="display:flex;gap:8px;flex-wrap:wrap;margin-bottom:14px"></div>

                <!-- Photo preview -->
                <div id="compPhotoWrap" class="complaint-photo-wrap" style="display:none;margin-bottom:16px">
                    <a id="compPhotoLink" href="#" target="_blank">
                        <img id="compPhotoImg" class="complaint-photo-preview" src="" alt="Issue photo">
                    </a>
                    <div style="font-size:.72rem;color:var(--ink3);margin-top:6px;font-weight:600">
                        <i class="bi bi-image"></i> Tenant-submitted photo — click to view full size
                    </div>
                </div>

                <!-- Description -->
                <div id="compDescSection" style="display:none;margin-bottom:16px">
                    <label class="modal-label"><i class="bi bi-chat-quote" style="margin-right:4px"></i>Tenant's Description</label>
                    <div class="complaint-desc-box" id="compDescText"></div>
                </div>

                <!-- Status picker -->
                <div style="margin-bottom:16px">
                    <label class="modal-label">Update Status</label>
                    <div class="status-option-grid">
                        <div>
                            <input type="radio" name="status" value="pending" id="s_pending" class="status-radio">
                            <label for="s_pending" class="status-label">
                                <span class="status-dot sdot-pending"></span> Pending
                            </label>
                        </div>
                        <div>
                            <input type="radio" name="status" value="in progress" id="s_progress" class="status-radio">
                            <label for="s_progress" class="status-label">
                                <span class="status-dot sdot-progress"></span> In Progress
                            </label>
                        </div>
                        <div>
                            <input type="radio" name="status" value="completed" id="s_completed" class="status-radio">
                            <label for="s_completed" class="status-label">
                                <span class="status-dot sdot-completed"></span> Completed
                            </label>
                        </div>
                        <div>
                            <input type="radio" name="status" value="rejected" id="s_rejected" class="status-radio">
                            <label for="s_rejected" class="status-label">
                                <span class="status-dot sdot-rejected"></span> Rejected
                            </label>
                        </div>
                    </div>
                </div>

                <div class="complaint-modal-grid">
                    <!-- Scheduled date -->
                    <div class="field-group">
                        <label class="modal-label">
                            <i class="bi bi-calendar-event" style="margin-right:3px"></i>
                            Scheduled Date
                            <span style="color:var(--ink3);font-weight:600;text-transform:none;letter-spacing:0">(optional)</span>
                        </label>
                        <input type="date" class="modal-input" name="scheduled_date" id="compScheduledDate">
                    </div>

                    <!-- Remarks -->
                    <div class="field-group">
                        <label class="modal-label">
                            <i class="bi bi-tag" style="margin-right:3px"></i>
                            Internal Remarks
                            <span style="color:var(--ink3);font-weight:600;text-transform:none;letter-spacing:0">(optional)</span>
                        </label>
                        <input type="text" class="modal-input" name="remarks" placeholder="e.g., Parts ordered">
                    </div>
                </div>

                <!-- Response message -->
                <div class="field-group">
                    <label class="modal-label">
                        <i class="bi bi-send" style="margin-right:3px"></i>
                        Message to Tenant *
                    </label>
                    <textarea class="modal-textarea" name="response" rows="4"
                        placeholder="Write your response or update here… The tenant will see this message."
                        style="resize:vertical" required></textarea>
                </div>
            </div>
            <div class="modal-foot">
                <button type="button" class="mbtn mbtn-cancel" onclick="closeModal('complaintModal')">
                    <i class="bi bi-x"></i> Cancel
                </button>
                <button type="submit" class="mbtn mbtn-primary">
                    <i class="bi bi-send-fill"></i> Send Response
                </button>
            </div>
        </form>
    </div>
</div>

<!-- ─── APPROVE / REJECT REQUEST MODAL ─── -->
<div class="modal-overlay" id="requestModal" style="display:none" onclick="if(event.target===this)closeModal('requestModal')">
    <div class="modal-box" onclick="event.stopPropagation()">
        <div class="modal-head">
            <h4 id="requestModalTitle"><i class="bi bi-file-earmark-text-fill"></i> Manage Request</h4>
            <button type="button" class="modal-close" onclick="closeModal('requestModal')">✕</button>
        </div>
        <div class="modal-body">
            <input type="hidden" id="reqLeaseId">
            <input type="hidden" id="reqType">
            <div id="terminationReasonSection" style="display:none">
                <div class="info-box info-box-red">
                    <div class="info-box-label"><i class="bi bi-chat-left-text-fill"></i> Tenant's Reason</div>
                    <div class="info-box-text" id="terminationReasonText">—</div>
                    <div class="info-box-date" id="terminationDateText"></div>
                </div>
            </div>
            <div id="renewalInfoSection" style="display:none">
                <div class="info-box info-box-blue">
                    <div class="info-box-label"><i class="bi bi-arrow-repeat"></i> Renewal Request</div>
                    <div class="info-box-text">The tenant is requesting to renew their lease. Please approve or reject below.</div>
                </div>
            </div>
        </div>
        <div class="modal-foot">
            <button type="button" class="mbtn mbtn-cancel" onclick="closeModal('requestModal')">
                <i class="bi bi-x"></i> Cancel
            </button>
            <button class="mbtn mbtn-approve" id="approveBtn">
                <i class="bi bi-check-circle-fill"></i> Approve
            </button>
            <button class="mbtn mbtn-reject" id="rejectBtn">
                <i class="bi bi-x-circle-fill"></i> Reject
            </button>
        </div>
    </div>
</div>

<!-- ─── PAYMENT HISTORY MODAL ─── -->
<div class="modal-overlay" id="paymentHistoryModal" style="display:none" onclick="if(event.target===this)closeModal('paymentHistoryModal')">
    <div class="modal-box modal-box-xl" onclick="event.stopPropagation()">
        <div class="modal-head">
            <h4><i class="bi bi-clock-history"></i> Payment History — <span id="histModalTenantName">—</span></h4>
            <button type="button" class="modal-close" onclick="closeModal('paymentHistoryModal')">✕</button>
        </div>
        <div class="hist-hero">
            <div class="hist-hero-icon"><i class="bi bi-building"></i></div>
            <div>
                <div class="hist-hero-prop" id="histModalProperty">—</div>
                <div class="hist-hero-sub">Active Lease</div>
            </div>
            <div style="margin-left:auto">
                <div class="hist-hero-rent-label">Monthly Rent</div>
                <div class="hist-hero-rent-val" id="histModalRent">₱0.00</div>
            </div>
        </div>
        <div class="hist-stat-grid">
            <div class="hist-stat hs-green">
                <div class="hist-stat-label">Total Paid</div>
                <div class="hist-stat-val" id="histTotalPaid">₱0.00</div>
                <div class="hist-stat-sub">All paid records</div>
            </div>
            <div class="hist-stat hs-blue">
                <div class="hist-stat-label">Transactions</div>
                <div class="hist-stat-val" id="histTotalCount">0</div>
                <div class="hist-stat-sub" id="histPaidPending">0 paid · 0 pending</div>
            </div>
            <div class="hist-stat hs-amber">
                <div class="hist-stat-label">Last Payment</div>
                <div class="hist-stat-val" id="histLastPayment" style="font-size:.95rem">—</div>
                <div class="hist-stat-sub">Most recent</div>
            </div>
            <div class="hist-stat hs-red">
                <div class="hist-stat-label">Overdue</div>
                <div class="hist-stat-val" id="histOverdueCount">0</div>
                <div class="hist-stat-sub">Record(s)</div>
            </div>
            <div class="hist-stat hs-purple">
                <div class="hist-stat-label">Awaiting Review</div>
                <div class="hist-stat-val" id="histVerifyCount">0</div>
                <div class="hist-stat-sub">Submitted by tenant</div>
            </div>
        </div>
        <div class="hist-filters">
            <input type="text" id="histSearch" class="hist-input hist-input-search"
                placeholder="🔍 Search reference, method, remarks…" oninput="filterHist()">
            <select id="histStatusFilter" class="hist-input" onchange="filterHist()">
                <option value="">All Status</option>
                <option value="paid">Paid</option>
                <option value="partial">Partial</option>
                <option value="pending">Pending</option>
                <option value="overdue">Overdue</option>
                <option value="pending_verification">Awaiting Review</option>
                <option value="rejected">Rejected</option>
            </select>
            <select id="histTypeFilter" class="hist-input" onchange="filterHist()">
                <option value="">All Types</option>
                <option value="rent">Rent</option>
                <option value="deposit">Deposit</option>
                <option value="penalty">Penalty</option>
            </select>
        </div>
        <div class="hist-tbl-wrap">
            <table class="hist-tbl">
                <thead>
                    <tr>
                        <th>#</th><th>Type</th><th>Due Date</th><th>Date Paid</th>
                        <th>Amount</th><th>Method</th><th>Ref No.</th>
                        <th>Proof</th><th>Status</th><th>Remarks</th><th>Action</th>
                    </tr>
                </thead>
                <tbody id="histTableBody">
                    <tr><td colspan="11" style="text-align:center;padding:48px;color:var(--ink3)">No records found.</td></tr>
                </tbody>
            </table>
        </div>
        <div class="hist-foot">
            <span id="histRowCount">0 transaction(s)</span>
            <span>Total paid: <strong style="color:var(--maroon);font-weight:900" id="histFooterTotal">₱0.00</strong></span>
        </div>
        <div class="modal-foot">
            <button type="button" class="mbtn mbtn-cancel" onclick="closeModal('paymentHistoryModal')">
                <i class="bi bi-x"></i> Close
            </button>
        </div>
    </div>
</div>

<?php include '../Components/footer.php'; ?>

<script src="../js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
function openModal(id)  { document.getElementById(id).style.display='flex'; document.body.style.overflow='hidden'; }
function closeModal(id) { document.getElementById(id).style.display='none';  document.body.style.overflow=''; }

/* ─── COMPLAINT MODAL ─── */
document.querySelectorAll('.respond-btn').forEach(btn => {
    btn.addEventListener('click', function() {
        document.getElementById('complaint_id_input').value = this.dataset.id;
        document.getElementById('compModalTitle').textContent = 'Respond to: ' + this.dataset.title;

        // Description
        const desc = (this.dataset.description || '').trim();
        const section = document.getElementById('compDescSection');
        if (desc) {
            document.getElementById('compDescText').textContent = desc;
            section.style.display = 'block';
        } else {
            section.style.display = 'none';
        }

        // Photo preview
        const photo = this.dataset.photo || '';
        const photoWrap = document.getElementById('compPhotoWrap');
        if (photo) {
            document.getElementById('compPhotoImg').src = '/' + photo;
            document.getElementById('compPhotoLink').href = '/' + photo;
            photoWrap.style.display = 'block';
        } else {
            photoWrap.style.display = 'none';
        }

        // Meta chips: category + priority
        const category = this.dataset.category || '';
        const priority = this.dataset.priority || '';
        const metaRow = document.getElementById('compMetaRow');
        metaRow.innerHTML = '';
        if (category) metaRow.innerHTML += `<span class="complaint-meta-chip"><i class="bi bi-tag-fill"></i>${category}</span>`;
        const pColors = {low:'#16a34a',medium:'#d97706',high:'#dc2626',urgent:'#1a1a2e'};
        if (priority) metaRow.innerHTML += `<span class="complaint-meta-chip" style="background:${pColors[priority.toLowerCase()]||'#64748b'};color:#fff;border-color:transparent"><i class="bi bi-flag-fill"></i>${priority}</span>`;

        // Pre-select current status
        const currentStatus = (this.dataset.status || 'pending').toLowerCase();
        const statusMap = {'pending':'s_pending','in progress':'s_progress','completed':'s_completed','rejected':'s_rejected'};
        const radioId = statusMap[currentStatus] || 's_pending';
        const radio = document.getElementById(radioId);
        if (radio) radio.checked = true;

        openModal('complaintModal');
    });
});

/* ─── REMOVE COMPLAINT ─── */
document.querySelectorAll('.remove-complaint-btn').forEach(btn => {
    btn.addEventListener('click', function() {
        const id = this.dataset.id;
        Swal.fire({
            title: 'Remove this request?',
            text: 'This action cannot be undone.',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#dc2626',
            cancelButtonColor: '#718096',
            confirmButtonText: 'Yes, Remove'
        }).then(r => {
            if (!r.isConfirmed) return;
            fetch('maintenance-delete.php', {
                method: 'POST',
                headers: {'Content-Type':'application/x-www-form-urlencoded'},
                body: 'complaint_id=' + id
            })
            .then(res => res.text())
            .then(txt => {
                if (txt.trim() === 'success') location.reload();
                else Swal.fire({icon:'error', title:'Failed', text:'Could not remove: '+txt, confirmButtonColor:'#8d0b41'});
            })
            .catch(e => Swal.fire({icon:'error', title:'Network Error', text:e.message, confirmButtonColor:'#8d0b41'}));
        });
    });
});

/* ─── RENEWAL / TERMINATION MODAL ─── */
document.querySelectorAll('.renewal-btn, .termination-btn').forEach(btn => {
    btn.addEventListener('click', function() {
        document.getElementById('reqLeaseId').value = this.dataset.leaseId;
        document.getElementById('reqType').value    = this.dataset.type;
        const type   = this.dataset.type;
        const reason = this.dataset.reason || '';
        const date   = this.dataset.date   || '';
        const titleEl = document.getElementById('requestModalTitle');
        if (type === 'termination') {
            titleEl.innerHTML = '<i class="bi bi-file-earmark-x-fill"></i> Termination Request';
            document.getElementById('terminationReasonSection').style.display = 'block';
            document.getElementById('renewalInfoSection').style.display = 'none';
            document.getElementById('terminationReasonText').textContent = reason || 'No reason provided.';
            document.getElementById('terminationDateText').textContent   = date ? 'Submitted: ' + date : '';
        } else {
            titleEl.innerHTML = '<i class="bi bi-arrow-repeat"></i> Renewal Request';
            document.getElementById('terminationReasonSection').style.display = 'none';
            document.getElementById('renewalInfoSection').style.display = 'block';
        }
        openModal('requestModal');
    });
});

document.getElementById('approveBtn').addEventListener('click', () => sendRequest('approved'));
document.getElementById('rejectBtn').addEventListener('click',  () => sendRequest('rejected'));

function sendRequest(status) {
    const leaseId = document.getElementById('reqLeaseId').value;
    const type    = document.getElementById('reqType').value;
    const ab = document.getElementById('approveBtn');
    const rb = document.getElementById('rejectBtn');
    ab.disabled = rb.disabled = true;
    ab.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span> Processing…';
    rb.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span> Processing…';

    fetch('update-request.php', {
        method:'POST', headers:{'Content-Type':'application/x-www-form-urlencoded'},
        body:`lease_id=${leaseId}&type=${type}&status=${status}`
    })
    .then(r => r.json())
    .then(data => {
        closeModal('requestModal');
        ab.disabled = rb.disabled = false;
        ab.innerHTML = '<i class="bi bi-check-circle-fill"></i> Approve';
        rb.innerHTML = '<i class="bi bi-x-circle-fill"></i> Reject';
        let icon='success', title='Success!', text=data.message;
        if (type==='termination'&&status==='approved'){ title='Termination Approved'; text='The lease has been terminated.'; }
        else if (type==='termination'&&status==='rejected'){ icon='info'; title='Termination Rejected'; text='The lease remains active.'; }
        else if (type==='renewal'&&status==='approved'){ title='Renewal Approved'; text='Lease renewal approved.'; }
        else if (type==='renewal'&&status==='rejected'){ icon='info'; title='Renewal Rejected'; text='Renewal request rejected.'; }
        if (data.success) Swal.fire({icon,title,text,confirmButtonColor:'#8d0b41'}).then(()=>location.reload());
        else Swal.fire({icon:'error',title:'Action Failed',text:data.message||'Something went wrong.',confirmButtonColor:'#8d0b41'});
    })
    .catch(() => {
        ab.disabled = rb.disabled = false;
        ab.innerHTML = '<i class="bi bi-check-circle-fill"></i> Approve';
        rb.innerHTML = '<i class="bi bi-x-circle-fill"></i> Reject';
        Swal.fire({icon:'error',title:'Network Error',text:'Could not connect.',confirmButtonColor:'#8d0b41'});
    });
}

/* ─── PAYMENT HISTORY ─── */
const paymentHistoryData = <?= json_encode($paymentHistoryMap) ?>;
let histAllRows = [];

function fmtDate(d) {
    if (!d) return '—';
    const dt = new Date(d);
    return isNaN(dt.getTime()) ? '—' : dt.toLocaleDateString('en-PH',{month:'short',day:'numeric',year:'numeric'});
}
function fmtAmt(a) { return '₱'+parseFloat(a||0).toLocaleString('en-PH',{minimumFractionDigits:2}); }
function daysLate(ds) {
    if (!ds) return null;
    const due=new Date(ds),now=new Date();
    now.setHours(0,0,0,0); due.setHours(0,0,0,0);
    return now<=due ? null : Math.floor((now-due)/86400000);
}
function statusBadge(s) {
    const m={paid:['hb-paid','bi-check-circle-fill','Paid'],partial:['hb-partial','bi-dash-circle-fill','Partial'],pending:['hb-pending','bi-clock-fill','Pending'],overdue:['hb-overdue','bi-exclamation-circle-fill','Overdue'],pending_verification:['hb-verify','bi-hourglass-split','Awaiting Review'],rejected:['hb-rejected','bi-x-circle-fill','Rejected']};
    const [cls,ico,lbl]=m[s]||['hb-pending','bi-question-circle',s||'Unknown'];
    return `<span class="hbadge ${cls}"><i class="bi ${ico}"></i>${lbl}</span>`;
}
function typePill(t) {
    const m={rent:['ht-rent','bi-house-fill','Rent'],deposit:['ht-deposit','bi-safe2-fill','Deposit'],penalty:['ht-penalty','bi-exclamation-triangle-fill','Penalty']};
    const [cls,ico,lbl]=m[t]||['ht-other','bi-tag-fill',t||'Other'];
    return `<span class="htype ${cls}"><i class="bi ${ico}"></i>${lbl}</span>`;
}
function methodPill(m) {
    if(!m) return '<span style="color:var(--ink3)">—</span>';
    return `<span style="background:var(--surface2);color:var(--ink2);padding:3px 9px;border-radius:20px;font-size:.72rem;font-weight:700;display:inline-flex;align-items:center;gap:4px"><i class="bi bi-credit-card"></i>${m.replace(/_/g,' ')}</span>`;
}

function renderHistTable(rows) {
    const tbody = document.getElementById('histTableBody');
    if (!rows.length) {
        tbody.innerHTML = `<tr><td colspan="11" style="text-align:center;padding:48px;color:var(--ink3);font-size:.85rem"><i class="bi bi-receipt" style="font-size:2.5rem;display:block;margin-bottom:12px;opacity:.3"></i>No records found.</td></tr>`;
        document.getElementById('histRowCount').textContent='0 transaction(s)';
        document.getElementById('histFooterTotal').textContent='₱0.00';
        return;
    }
    const totalPaid=rows.reduce((s,p)=>['paid','partial'].includes((p.status||'').toLowerCase())?s+parseFloat(p.amount||0):s,0);
    tbody.innerHTML = rows.map((p,i) => {
        const s=(p.status||'').toLowerCase();
        const dl=s==='overdue'?daysLate(p.due_date):null;
        const rc=s==='overdue'?'tr-overdue':(s==='pending_verification'?'tr-verify':'');
        const proof=p.proof_path?`<a href="../uploads/${p.proof_path}" target="_blank" style="color:var(--blue);font-size:.76rem;display:inline-flex;align-items:center;gap:3px;font-weight:700"><i class="bi bi-file-earmark-check"></i>View</a>`:'<span style="color:var(--ink3)">—</span>';
        const action=s==='pending_verification'?`<div style="display:flex;gap:5px"><button class="approve-pay-btn" onclick="reviewPayment(${p.id},'approved')"><i class="bi bi-check2"></i>Approve</button><button class="reject-pay-btn" onclick="reviewPayment(${p.id},'rejected')"><i class="bi bi-x"></i>Reject</button></div>`:'<span style="color:var(--ink3)">—</span>';
        const dueHtml=p.due_date?(fmtDate(p.due_date)+(dl!==null?`<span class="days-late-chip"><i class="bi bi-clock-history"></i>${dl}d late</span>`:'')):' —';
        return `<tr class="${rc}">
            <td style="color:var(--ink3);font-size:.75rem">${i+1}</td>
            <td>${typePill((p.payment_type||'').toLowerCase())}</td>
            <td>${dueHtml}</td>
            <td>${p.paid_date?fmtDate(p.paid_date):'<span style="color:var(--ink3)">Not yet paid</span>'}</td>
            <td style="font-weight:900;color:var(--ink)">${p.amount!=null?fmtAmt(p.amount):'—'}</td>
            <td>${methodPill(p.payment_method)}</td>
            <td>${p.reference_no?`<span class="ref-mono">${p.reference_no}</span>`:'<span style="color:var(--ink3)">—</span>'}</td>
            <td>${proof}</td>
            <td>${statusBadge(s)}</td>
            <td style="max-width:140px;font-size:.76rem;color:var(--ink3);white-space:normal;font-weight:600">${p.remarks||'<span style="color:var(--ink3)">—</span>'}</td>
            <td>${action}</td>
        </tr>`;
    }).join('');
    document.getElementById('histRowCount').textContent=rows.length+' transaction(s)';
    document.getElementById('histFooterTotal').textContent=fmtAmt(totalPaid);
}

function filterHist() {
    const q=document.getElementById('histSearch').value.toLowerCase();
    const st=document.getElementById('histStatusFilter').value.toLowerCase();
    const tp=document.getElementById('histTypeFilter').value.toLowerCase();
    renderHistTable(histAllRows.filter(p=>{
        const txt=[p.payment_type||'',p.payment_method||'',p.reference_no||'',p.status||'',p.remarks||''].join(' ').toLowerCase();
        return(!q||txt.includes(q))&&(!st||(p.status||'').toLowerCase()===st)&&(!tp||(p.payment_type||'').toLowerCase()===tp);
    }));
}

function reviewPayment(id, action) {
    Swal.fire({
        title:(action==='approved'?'Approve':'Reject')+' this payment?',
        text:action==='approved'?'This will mark the payment as Paid.':"This will reject the tenant's payment.",
        icon:action==='approved'?'question':'warning',
        showCancelButton:true,
        confirmButtonColor:action==='approved'?'#0d9488':'#dc2626',
        cancelButtonColor:'#718096',
        confirmButtonText:action==='approved'?'Yes, Approve':'Yes, Reject'
    }).then(r=>{
        if(!r.isConfirmed) return;
        fetch('review-payment.php',{method:'POST',headers:{'Content-Type':'application/x-www-form-urlencoded'},body:`payment_id=${id}&action=${action}`})
        .then(r=>r.text()).then(raw=>{
            let data;
            try{data=JSON.parse(raw);}catch{Swal.fire({icon:'error',title:'Error',text:'Unexpected response.',confirmButtonColor:'#8d0b41'});return;}
            Swal.fire({
                icon:data.success?'success':'error',
                title:data.success?'Done!':'Failed',
                text:data.success?(action==='approved'?'Payment approved.':'Payment rejected.'):(data.message||'Something went wrong.'),
                confirmButtonColor:'#8d0b41'
            }).then(()=>{if(data.success)location.reload();});
        })
        .catch(e=>Swal.fire({icon:'error',title:'Network Error',text:e.message,confirmButtonColor:'#8d0b41'}));
    });
}

document.querySelectorAll('.view-history-btn').forEach(btn => {
    btn.addEventListener('click', function() {
        const tid=this.dataset.tenantId, name=this.dataset.tenantName,
              prop=this.dataset.property, rent=parseFloat(this.dataset.amount||0),
              records=paymentHistoryData[tid]||[], pre=this.dataset.filterStatus||'';
        histAllRows=records;
        document.getElementById('histModalTenantName').textContent=name;
        document.getElementById('histModalProperty').textContent=prop;
        document.getElementById('histModalRent').textContent=fmtAmt(rent);
        const paid=records.reduce((s,p)=>['paid','partial'].includes((p.status||'').toLowerCase())?s+parseFloat(p.amount||0):s,0);
        document.getElementById('histTotalPaid').textContent=fmtAmt(paid);
        document.getElementById('histTotalCount').textContent=records.length;
        document.getElementById('histPaidPending').textContent=records.filter(p=>(p.status||'').toLowerCase()==='paid').length+' paid · '+records.filter(p=>(p.status||'').toLowerCase()==='pending').length+' pending';
        document.getElementById('histOverdueCount').textContent=records.filter(p=>(p.status||'').toLowerCase()==='overdue').length;
        document.getElementById('histVerifyCount').textContent=records.filter(p=>(p.status||'').toLowerCase()==='pending_verification').length;
        const last=records.find(p=>p.paid_date);
        document.getElementById('histLastPayment').textContent=last?fmtDate(last.paid_date):'—';
        document.getElementById('histFooterTotal').textContent=fmtAmt(paid);
        document.getElementById('histSearch').value='';
        document.getElementById('histStatusFilter').value=pre;
        document.getElementById('histTypeFilter').value='';
        if(pre) filterHist(); else renderHistTable(records);
        openModal('paymentHistoryModal');
    });
});
['histSearch','histStatusFilter','histTypeFilter'].forEach(id=>{
    document.getElementById(id).addEventListener(id==='histSearch'?'input':'change', filterHist);
});
</script>
</body>
</html>