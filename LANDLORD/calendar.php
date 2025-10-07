<?php
// fetch_events_landlord.php
require_once '../connection.php';
require_once '../session_auth.php';

header('Content-Type: application/json');

// Check landlord session
if (!isset($_SESSION['landlord_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized access. Please log in.']);
    exit;
}

$landlord_id = (int) $_SESSION['landlord_id'];
$events = [];
$today = date('Y-m-d');

// Check database connection
if (!$conn) {
    http_response_code(500);
    echo json_encode(['error' => 'Database connection failed.']);
    exit;
}

// Fetch only **one active tenant per property** (or earliest future rental if none active)
// This fetches all rentals for the landlord
$sql = "SELECT r.ID AS rental_id, r.start_date, r.end_date, ls.listingName, t.firstName AS tenant_name
        FROM renttbl r
        JOIN listingtbl ls ON r.listing_id = ls.ID
        JOIN tenanttbl t ON r.tenant_id = t.ID
        WHERE ls.landlord_id = ? AND r.status = 'approved'
        ORDER BY r.start_date ASC";


$stmt = $conn->prepare($sql);
$stmt->bind_param('iss', $landlord_id, $today, $today);
$stmt->execute();
$result = $stmt->get_result();

if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        // Rent Start event (green)
        $events[] = [
            'title' => $row['listingName'] . ' - ' . $row['tenant_name'] . ' (Start)',
            'start' => $row['start_date'],
            'color' => 'green',
            'display' => 'auto'
        ];

        // Due Date event (red)
        $events[] = [
            'title' => $row['listingName'] . ' - ' . $row['tenant_name'] . ' (Due)',
            'start' => $row['end_date'],
            'color' => 'red',
            'display' => 'auto'
        ];
    }
}

$stmt->close();

// Return JSON
echo json_encode($events);
?>
