<?php
session_start();
require_once '../.php';

if (isset($_GET['id']) && isset($_GET['status'])) {
    $id = intval($_GET['id']);
    $status = $_GET['status'];

    if (in_array($status, ['verified', 'rejected'])) {
        $sql = "UPDATE landlordtbl SET verification_status = ? WHERE ID = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("si", $status, $id);
        $stmt->execute();

        header("Location: admin-verification.php");
        exit;
    }
}

$sql = "SELECT ID, firstName, lastName, email, ID_image 
        FROM landlordtbl 
        WHERE verification_status = 'pending'";
$result = $conn->query($sql);
?>
<!DOCTYPE html>
<html>

<head>
    <title>Admin - Landlord Verification</title>
</head>

<body>
    <h2>Pending Landlord Verifications</h2>

    <?php if ($result->num_rows > 0): ?>
        <?php while ($row = $result->fetch_assoc()): ?>
            <div style="border:1px solid #ccc; padding:10px; margin-bottom:15px;">
                <h3><?= htmlspecialchars($row['firstName'] . " " . $row['lastName']) ?></h3>
                <p>Email: <?= htmlspecialchars($row['email']) ?></p>

                <?php if (!empty($row['ID_image'])): ?>
                    <p><strong>Uploaded ID:</strong></p>
                    <img src="<?= htmlspecialchars($row['ID_image']) ?>" width="250" style="border:1px solid #333;"><br><br>
                <?php else: ?>
                    <p>No ID uploaded.</p>
                <?php endif; ?>

                <a href="admin-verification.php?id=<?= $row['ID'] ?>&status=verified"
                    style="color:green; font-weight:bold;">✅ Approve</a> |
                <a href="admin-verification.php?id=<?= $row['ID'] ?>&status=rejected"
                    style="color:red; font-weight:bold;">❌ Reject</a>
            </div>
        <?php endwhile; ?>
    <?php else: ?>
        <p>No pending verification requests at the moment.</p>
    <?php endif; ?>
</body>

</html>