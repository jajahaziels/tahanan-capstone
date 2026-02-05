<?php
require_once '../connection.php';
include '../session_auth.php';

if (!isset($_POST['lease_id'], $_POST['action']))
    die("Unauthorized");

$lease_id = (int) $_POST['lease_id'];
$action = $_POST['action'];

switch ($action) {
    case 'renew':
        // Extend lease by 1 month (example, you can change)
        $update = $conn->prepare("UPDATE leasetbl SET end_date = DATE_ADD(end_date, INTERVAL 1 MONTH) WHERE ID = ?");
        $update->bind_param("i", $lease_id);
        $update->execute();
        echo "Lease renewed for 1 month.";
        break;

    case 'terminate':
        // Mark lease as terminated
        $update = $conn->prepare("UPDATE leasetbl SET status='terminated' WHERE ID = ?");
        $update->bind_param("i", $lease_id);
        $update->execute();
        echo "Lease terminated.";
        break;

    default:
        echo "Invalid action.";
}
?>