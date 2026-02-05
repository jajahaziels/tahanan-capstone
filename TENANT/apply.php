<?php
require_once '../connection.php';
include '../session_auth.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    die("Invalid request.");
}

$tenant_id = $_SESSION['tenant_id'] ?? 0;
$listing_id = intval($_POST['listing_id'] ?? 0);

if ($tenant_id <= 0) {
    die("You must be logged in to apply.");
}

if ($listing_id <= 0) {
    die("Invalid property.");
}

/* 🔍 Check latest request */
$checkSql = "
    SELECT id, status
    FROM requesttbl
    WHERE tenant_id = ? AND listing_id = ?
    ORDER BY id DESC
    LIMIT 1
";
$stmt = $conn->prepare($checkSql);
$stmt->bind_param("ii", $tenant_id, $listing_id);
$stmt->execute();
$res = $stmt->get_result();
$existing = $res->fetch_assoc();
$stmt->close();

/* 🚫 Pending → block */
if ($existing && $existing['status'] === 'pending') {
    echo "<script>
        alert('⏳ Please wait. Your application is still pending.');
        window.location.href='property-details.php?id=$listing_id';
    </script>";
    exit;
}

/* ✅ Approved / Rejected → allow re-apply (new row) */
$insertSql = "
    INSERT INTO requesttbl (date, tenant_id, listing_id, status)
    VALUES (NOW(), ?, ?, 'pending')
";
$stmt = $conn->prepare($insertSql);
$stmt->bind_param("ii", $tenant_id, $listing_id);
$stmt->execute();
$stmt->close();

echo "<script>
    alert('✅ Application sent successfully!');
    window.location.href='tenant.php';
</script>";
exit;
