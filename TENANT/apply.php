<?php
require_once '../connection.php';
include '../session_auth.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    die("Invalid request.");
}

$tenant_id  = $_SESSION['tenant_id'] ?? 0;
$listing_id = intval($_POST['listing_id'] ?? 0);

if ($tenant_id <= 0) die("You must be logged in to apply.");
if ($listing_id <= 0) die("Invalid property.");

// ── Check latest request ─────────────────────────────────────────────────────
$stmt = $conn->prepare("
    SELECT id, status FROM requesttbl
    WHERE tenant_id = ? AND listing_id = ?
    ORDER BY id DESC LIMIT 1
");
$stmt->bind_param("ii", $tenant_id, $listing_id);
$stmt->execute();
$existing = $stmt->get_result()->fetch_assoc();
$stmt->close();

// Pending → block
if ($existing && $existing['status'] === 'pending') {
    echo "<script>
        alert('⏳ Please wait. Your application is still pending.');
        window.location.href='property-details.php?id=$listing_id';
    </script>";
    exit;
}

// ── Insert new application ───────────────────────────────────────────────────
$stmt = $conn->prepare("
    INSERT INTO requesttbl (date, tenant_id, listing_id, status)
    VALUES (NOW(), ?, ?, 'pending')
");
$stmt->bind_param("ii", $tenant_id, $listing_id);
$stmt->execute();
$stmt->close();

// ── Fetch listing name + landlord_id for the notification ────────────────────
$stmt = $conn->prepare("
    SELECT l.listingName, l.landlord_id,
           CONCAT(t.firstName, ' ', t.lastName) AS tenant_name
    FROM listingtbl l
    JOIN tenanttbl t ON t.ID = ?
    WHERE l.ID = ?
");
$stmt->bind_param("ii", $tenant_id, $listing_id);
$stmt->execute();
$stmt->bind_result($listing_name, $landlord_id, $tenant_name);
$stmt->fetch();
$stmt->close();

// ── Notify the landlord ──────────────────────────────────────────────────────
if ($landlord_id) {
    $message = "🏠 " . $tenant_name . " has applied for your listing \"" . $listing_name . "\". Review their application now.";
$type      = 'rental';
$user_type = 'landlord';
$link      = "property-details.php?ID=" . $listing_id; // ← landlord goes to their property details

$notif = $conn->prepare("
    INSERT INTO notifications (user_id, user_type, message, type, link, is_read, created_at)
    VALUES (?, ?, ?, ?, ?, 0, NOW())
");
$notif->bind_param("issss", $landlord_id, $user_type, $message, $type, $link);
    $notif->execute();
    $notif->close();
}

echo "<script>
    alert('✅ Application sent successfully!');
    window.location.href='tenant.php';
</script>";
exit;