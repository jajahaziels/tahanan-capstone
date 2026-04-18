<?php
require_once '../connection.php';
include '../session_auth.php';

$landlord_id    = (int)$_SESSION['landlord_id'];
$complaint_id   = (int)($_POST['complaint_id']  ?? 0);
$status         = trim($_POST['status']          ?? 'pending');
$response       = trim($_POST['response']        ?? '');
$landlord_remarks = trim($_POST['landlord_remarks'] ?? '');
$scheduled_raw  = trim($_POST['scheduled_date']  ?? '');
$scheduled_date = ($scheduled_raw !== '') ? $scheduled_raw : null;

if (!$complaint_id || !$response) {
    header('Location: rental-management.php?error=missing_fields');
    exit;
}

/* ── verify ownership & fetch old values ── */
$ownSql  = "SELECT mr.ID, mr.lease_id,
                   mr.landlord_remarks AS old_remarks,
                   ls.tenant_id
            FROM maintenance_requeststbl mr
            JOIN leasetbl ls ON mr.lease_id = ls.ID
            WHERE mr.ID = ? AND mr.landlord_id = ?
            LIMIT 1";
$ownStmt = $conn->prepare($ownSql);
if (!$ownStmt) {
    header('Location: rental-management.php?error=db_error&msg=' . urlencode($conn->error));
    exit;
}
$ownStmt->bind_param("ii", $complaint_id, $landlord_id);
$ownStmt->execute();
$row = $ownStmt->get_result()->fetch_assoc();

if (!$row) {
    header('Location: rental-management.php?error=unauthorized');
    exit;
}

$tenant_id = (int)$row['tenant_id'];

/* ── remarks persistence: keep old value if field left blank ── */
$final_remarks = ($landlord_remarks !== '') ? $landlord_remarks : ($row['old_remarks'] ?? null);

/* ── set completed_date only when marking complete ── */
$completed_date  = (strtolower($status) === 'completed') ? date('Y-m-d H:i:s') : null;
$response_date   = date('Y-m-d H:i:s');

/* ── UPDATE with correct column names ── */
if ($completed_date !== null) {
    $updSql = "UPDATE maintenance_requeststbl
               SET status             = ?,
                   scheduled_date     = ?,
                   landlord_remarks   = ?,
                   landlord_response  = ?,
                   response_date      = ?,
                   completed_date     = ?,
                   updated_at         = NOW()
               WHERE ID = ? AND landlord_id = ?";
    $updStmt = $conn->prepare($updSql);
    if (!$updStmt) {
        header('Location: rental-management.php?error=db_error&msg=' . urlencode($conn->error));
        exit;
    }
    $updStmt->bind_param("ssssssii",
        $status, $scheduled_date, $final_remarks,
        $response, $response_date, $completed_date,
        $complaint_id, $landlord_id
    );
} else {
    $updSql = "UPDATE maintenance_requeststbl
               SET status             = ?,
                   scheduled_date     = ?,
                   landlord_remarks   = ?,
                   landlord_response  = ?,
                   response_date      = ?,
                   updated_at         = NOW()
               WHERE ID = ? AND landlord_id = ?";
    $updStmt = $conn->prepare($updSql);
    if (!$updStmt) {
        header('Location: rental-management.php?error=db_error&msg=' . urlencode($conn->error));
        exit;
    }
    $updStmt->bind_param("sssssii",
        $status, $scheduled_date, $final_remarks,
        $response, $response_date,
        $complaint_id, $landlord_id
    );
}

if (!$updStmt->execute()) {
    header('Location: rental-management.php?error=db_update_failed&msg=' . urlencode($updStmt->error));
    exit;
}

/* ── notify tenant — fully non-fatal ── */
try {
    $statusLabel  = ucfirst(str_replace('_', ' ', $status));
    $notifTitle   = 'Maintenance Request Update';
    $notifMessage = "Your maintenance request status is now: {$statusLabel}. Landlord: {$response}";
    if ($scheduled_date) {
        $notifMessage .= ' Scheduled for: ' . date('F j, Y', strtotime($scheduled_date)) . '.';
    }
    $notifSql  = "INSERT INTO notificationstbl
                      (user_id, user_type, title, message, type, reference_id, is_read, created_at)
                  VALUES (?, 'tenant', ?, ?, 'maintenance', ?, 0, NOW())";
    $notifStmt = $conn->prepare($notifSql);
    if ($notifStmt) {
        $notifStmt->bind_param("issi", $tenant_id, $notifTitle, $notifMessage, $complaint_id);
        $notifStmt->execute();
    }
} catch (Throwable $e) {
    /* Notification failed silently */
}

header('Location: rental-management.php?success=maintenance_updated');
exit;