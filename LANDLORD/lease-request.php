<?php
require_once '../connection.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $lease_id = $_POST['lease_id'] ?? null;
    $type = $_POST['type'] ?? '';
    $status = $_POST['status'] ?? '';
    $message = $_POST['message'] ?? '';

    if (!$lease_id || !$type || !$status) {
        echo json_encode(["success" => false, "message" => "Missing data"]);
        exit;
    }

    if ($type === "renewal") {

        $query = "UPDATE lease_renewaltbl 
                  SET landlord_status = ?, 
                      landlord_response = ?, 
                      responded_at = NOW()
                  WHERE lease_id = ?";

    } elseif ($type === "termination") {

        $query = "UPDATE lease_terminationstbl 
                  SET landlord_status = ?, 
                      landlord_response = ?, 
                      responded_at = NOW()
                  WHERE lease_id = ?";

    } else {
        echo json_encode(["success" => false, "message" => "Invalid request type"]);
        exit;
    }

    $stmt = $conn->prepare($query);
    $stmt->bind_param("ssi", $status, $message, $lease_id);

    if ($stmt->execute()) {
        echo json_encode(["success" => true, "message" => ucfirst($type) . " " . $status . " successfully"]);
    } else {
        echo json_encode(["success" => false, "message" => "Database error"]);
    }
}