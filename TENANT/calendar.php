<?php
// fetch_events.php
require_once '../connection.php';
require_once '../session_auth.php';

header('Content-Type: application/json');

// Check tenant session
if (!isset($_SESSION['tenant_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized access. Please log in.']);
    exit;
}

$tenant_id = (int) $_SESSION['tenant_id'];
$events = [];

// Check database connection
if (!$conn) {
    http_response_code(500);
    echo json_encode(['error' => 'Database connection failed.']);
    exit;
}

// Fetch approved rentals
$sql = "SELECT r.ID AS rental_id, r.start_date, r.end_date, ls.listingName
        FROM renttbl r
        JOIN listingtbl ls ON r.listing_id = ls.ID
        WHERE r.tenant_id = ? AND r.status = 'approved'";

$stmt = $conn->prepare($sql);
if (!$stmt) {
    http_response_code(500);
    echo json_encode(['error' => 'Prepare failed: ' . $conn->error]);
    exit;
}

$stmt->bind_param('i', $tenant_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        // Rent Start event (green)
        // Rent Start event (green dot)
        $events[] = [
            'title' => 'Rent Start' . ' - Rent Start',
            'start' => $row['start_date'],
            'color' => 'green',
            'display' => 'auto' // ensures it shows normally
        ];

        // Due Date event (red dot)
        $events[] = [
            'title' => 'Rent End' . ' - Due Date',
            'start' => $row['end_date'],
            'color' => 'red',
            'display' => 'auto'
        ];
    }
}

$stmt->close();

// Return JSON
echo json_encode($events);
