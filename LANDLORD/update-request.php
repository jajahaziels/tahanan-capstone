<?php
require_once '../connection.php';
include '../session_auth.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $request_id = intval($_POST['request_id']);
    $listing_id = intval($_POST['listing_id']);
    $action = $_POST['action'] ?? '';

    $landlord_id = $_SESSION['landlord_id'] ?? 0;
    if ($landlord_id <= 0) {
        die("❌ Unauthorized action. Please log in as landlord.");
    }

    // ✅ Verify that this rental belongs to the landlord
    $verify = $conn->prepare("
        SELECT r.ID 
        FROM renttbl r
        JOIN listingtbl l ON r.listing_id = l.ID
        WHERE r.ID = ? AND l.landlord_id = ?
    ");
    $verify->bind_param("ii", $request_id, $landlord_id);
    $verify->execute();
    $verifyResult = $verify->get_result();

    if ($verifyResult->num_rows === 0) {
        echo "<script>
            alert('❌ Invalid request or unauthorized action.');
            window.history.back();
        </script>";
        exit;
    }

    // ✅ Validate action
    if ($action === "approve") {
        $status = "approved";

        // Prevent multiple approvals for same listing
        $check = $conn->prepare("SELECT ID FROM renttbl WHERE listing_id=? AND status='approved'");
        $check->bind_param("i", $listing_id);
        $check->execute();
        if ($check->get_result()->num_rows > 0) {
            echo "<script>
                alert('⚠️ This property already has an approved tenant.');
                window.history.back();
            </script>";
            exit;
        }

    } elseif ($action === "reject") {
        $status = "rejected";
    } else {
        echo "<script>
            alert('❌ Invalid action.');
            window.history.back();
        </script>";
        exit;
    }

    // ✅ Update rental status
    $stmt = $conn->prepare("UPDATE renttbl SET status=? WHERE ID=?");
    $stmt->bind_param("si", $status, $request_id);

    if ($stmt->execute() && $stmt->affected_rows > 0) {
        // ✅ Update listing availability
        if ($status === 'approved') {
            $conn->query("UPDATE listingtbl SET availability='occupied' WHERE ID=$listing_id");
            echo "<script>
                alert('✅ Rental request approved successfully!');
                window.location.href='landlord-rental.php?ID=$listing_id';
            </script>";
        } elseif ($status === 'rejected') {
            $conn->query("UPDATE listingtbl SET availability='available' WHERE ID=$listing_id");
            echo "<script>
                alert('❌ Rental request rejected.');
                window.location.href='property-details.php?ID=$listing_id';
            </script>";
        }
    } else {
        echo "<script>
            alert('⚠️ Failed to update request. Please try again.');
            window.history.back();
        </script>";
    }
}
?>
