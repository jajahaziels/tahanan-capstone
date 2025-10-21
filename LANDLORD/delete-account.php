<?php
require_once '../connection.php';
require_once '../session_auth.php';

// Detect logged-in user
$user_id = 0;
$user_type = '';

if (isset($_SESSION['landlord_id'])) {
    $user_id = (int)$_SESSION['landlord_id'];
    $user_type = 'landlord';
} elseif (isset($_SESSION['tenant_id'])) {
    $user_id = (int)$_SESSION['tenant_id'];
    $user_type = 'tenant';
} else {
    die("Unauthorized access.");
}

// Handle deletion (after SweetAlert confirmation)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['confirm_delete'])) {

    if ($user_type === 'landlord') {
        // Delete related listings first (avoid FK constraint)
        $conn->query("DELETE FROM listingtbl WHERE landlord_id = $user_id");
        $delete = $conn->prepare("DELETE FROM landlordtbl WHERE ID = ?");
        $delete->bind_param("i", $user_id);
        $delete->execute();
    } elseif ($user_type === 'tenant') {
        // Delete related rentals first (avoid FK constraint)
        $conn->query("DELETE FROM renttbl WHERE tenant_id = $user_id");
        $delete = $conn->prepare("DELETE FROM tenanttbl WHERE ID = ?");
        $delete->bind_param("i", $user_id);
        $delete->execute();
    }

    // Logout user
    session_unset();
    session_destroy();

    echo "<script>
        localStorage.removeItem('auth');
        window.location.href = '../LOGIN/login.php?deleted=1';
    </script>";
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Delete Account</title>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>
<body>

<script>
    // Trigger SweetAlert confirmation on page load
    Swal.fire({
        title: 'Delete Account?',
        text: "This action cannot be undone. All your data will be permanently removed.",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        cancelButtonColor: '#3085d6',
        confirmButtonText: 'Yes, delete it!',
        cancelButtonText: 'Cancel'
    }).then((result) => {
        if (result.isConfirmed) {
            // Submit hidden form to delete account
            const form = document.createElement('form');
            form.method = 'POST';
            form.innerHTML = '<input type="hidden" name="confirm_delete" value="1">';
            document.body.appendChild(form);
            form.submit();
        } else {
            // Go back if canceled
            window.location.href = '<?php echo ($user_type === "landlord") ? "account.php" : "account.php"; ?>';
        }
    });
</script>

</body>
</html>
