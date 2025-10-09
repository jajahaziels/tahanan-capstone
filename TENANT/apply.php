<?php
require_once '../connection.php';
include '../session_auth.php';

$tenant_id = $_SESSION['tenant_id'];
$listing_id = intval($_POST['listing_id'] ?? 0);
$start_date = $_POST['start_date'] ?? null;
$end_date   = $_POST['end_date'] ?? null;

// --- Validate listing ID ---
if ($listing_id <= 0) {
    die("Invalid property ID.");
}

// --- Validate dates ---
if (empty($start_date) || empty($end_date)) {
    echo "<script>
        alert('⚠️ Please select both start and end dates.');
        window.history.back();
    </script>";
    exit;
}

if ($end_date < $start_date) {
    echo "<script>
        alert('⚠️ End date cannot be earlier than start date.');
        window.history.back();
    </script>";
    exit;
}

// --- Prevent tenant from renting another approved property ---
$check = $conn->prepare("SELECT ID FROM renttbl WHERE tenant_id=? AND status='approved'");
$check->bind_param("i", $tenant_id);
$check->execute();
$result = $check->get_result();

if ($result->num_rows > 0) {
    echo "<script>
        alert('⚠️ You already have a property rented. You cannot rent another one.');
        window.history.back();
    </script>";
    exit;
}

// --- Prevent duplicate or active applications for the same property ---
$checkDup = $conn->prepare("
    SELECT ID, status 
    FROM renttbl 
    WHERE tenant_id=? AND listing_id=? 
    ORDER BY ID DESC 
    LIMIT 1
");
$checkDup->bind_param("ii", $tenant_id, $listing_id);
$checkDup->execute();
$dupResult = $checkDup->get_result();

if ($dupResult->num_rows > 0) {
    $existing = $dupResult->fetch_assoc();

    // 🔹 Block if the existing one is still pending or already approved
    if (in_array($existing['status'], ['pending', 'approved'])) {
        echo "<script>
            alert('⚠️ You already have an active request for this property.');
            window.history.back();
        </script>";
        exit;
    }

    // 🔹 If rejected, allow to apply again (continue execution)
}

// --- Get landlord_id from listing ---
$listingStmt = $conn->prepare("SELECT landlord_id FROM listingtbl WHERE ID=?");
$listingStmt->bind_param("i", $listing_id);
$listingStmt->execute();
$listingResult = $listingStmt->get_result();

if ($listingResult->num_rows === 0) {
    die("Invalid listing.");
}

$listing = $listingResult->fetch_assoc();
$landlord_id = $listing['landlord_id'];

// --- Insert application into renttbl ---
$stmt = $conn->prepare("
    INSERT INTO renttbl (tenant_id, listing_id, landlord_id, start_date, end_date, status)
    VALUES (?, ?, ?, ?, ?, 'pending')
");
$stmt->bind_param("iiiss", $tenant_id, $listing_id, $landlord_id, $start_date, $end_date);
$stmt->execute();

// --- Success message ---
echo "<script>
    alert('✅ Request submitted successfully!');
    window.location.href='tenant-rental.php';
</script>";
exit;
?>
