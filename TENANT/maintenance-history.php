<?php
require_once __DIR__ . '/../connection.php'; // <-- this gives you $conn
require_once __DIR__ . '/../session_auth.php';

if (!isset($_SESSION['tenant_id'])) {
    header("Location: ../login.php");
    exit();
}

$tenant_id = $_SESSION['tenant_id'];

/* =========================
   FETCH MAINTENANCE HISTORY
========================= */

$sql = "
    SELECT *
    FROM maintenance_requeststbl
    WHERE tenant_id = ?
    ORDER BY 
        CASE priority
            WHEN 'Urgent' THEN 1
            WHEN 'High' THEN 2
            WHEN 'Medium' THEN 3
            WHEN 'Low' THEN 4
        END,
        created_at DESC
";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $tenant_id);
$stmt->execute();
$result = $stmt->get_result();
$maintenanceRequests = $result->fetch_all(MYSQLI_ASSOC);

/* =========================
   STATUS + OVERDUE LOGIC
========================= */

function getMaintenanceStatus($status, $created_at)
{
    $created = new DateTime($created_at);
    $now = new DateTime();
    $days = $created->diff($now)->days;

    if (strtolower($status) === 'pending' && $days > 3) {
        return '<span class="badge bg-danger">OVERDUE</span>';
    }

    return match ($status) {
        'Pending' => '<span class="badge bg-warning text-dark">Pending</span>',
        'In Progress' => '<span class="badge bg-primary">In Progress</span>',
        'Completed' => '<span class="badge bg-success">Completed</span>',
        'Rejected' => '<span class="badge bg-danger">Rejected</span>',
        default => '<span class="badge bg-secondary">' . htmlspecialchars($status) . '</span>'
    };
}

function getPriorityBadge($priority)
{
    return match ($priority) {
        'Low' => '<span class="badge bg-success">Low</span>',
        'Medium' => '<span class="badge bg-warning text-dark">Medium</span>',
        'High' => '<span class="badge bg-danger">High</span>',
        'Urgent' => '<span class="badge bg-dark text-white">Urgent</span>',
        default => '<span class="badge bg-secondary">' . htmlspecialchars($priority) . '</span>'
    };
}

$statusOptions = [
    'pending' => 'Pending',
    'in progress' => 'Scheduled',
    'completed' => 'Completed',
    'approved' => 'Approved',
    'rejected' => 'Rejected'
];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Maintenance History</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        body { font-family: 'Montserrat', sans-serif; background: #f5f6f8; }
        .rentals-section { background: #fff; border-radius: 14px; padding: 22px; box-shadow: 0 6px 18px rgba(0,0,0,.06); margin: 50px auto; max-width: 1000px; }
        .rentals-header { display: flex; align-items: center; gap: 12px; margin-bottom: 20px; }
        .rentals-title { font-size: 1.75rem; font-weight: 700; color: #2d3748; }
        .rentals-table { width: 100%; border-collapse: separate; border-spacing: 0 8px; }
        .rentals-table thead th { background-color: #f8fafc; color: #718096; text-transform: uppercase; font-size: 0.75rem; letter-spacing: 0.05em; border: none; padding: 16px; }
        .rentals-table tbody tr { background-color: #ffffff; box-shadow: 0 2px 4px rgba(0,0,0,0.02); transition: transform 0.2s ease; }
        .rentals-table tbody tr:hover { transform: scale(1.005); background-color: #fffafb; }
        .rentals-table td { padding: 16px; border-top: 1px solid #edf2f7; border-bottom: 1px solid #edf2f7; vertical-align: middle; }
        .badge { font-weight: 500; }
    </style>
</head>
<body>

<div class="rentals-section">
    <div class="rentals-header">
        <i class="bi bi-tools" style="font-size:1.5rem; color:#8d0b41;"></i>
        <h3 class="rentals-title">Maintenance History</h3>
    </div>

    <table class="rentals-table table">
        <thead>
            <tr>
                <th>Title</th>
                <th>Category</th>
                <th>Priority</th>
                <th>Status</th>
                <th>Date Filed</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($maintenanceRequests)): ?>
                <tr>
                    <td colspan="5" class="text-center text-muted">No maintenance history</td>
                </tr>
            <?php else: ?>
                <?php foreach ($maintenanceRequests as $req): ?>
                    <tr>
                        <td><?= htmlspecialchars($req['title']); ?></td>
                        <td><?= htmlspecialchars($req['category']); ?></td>
                        <td><?= getPriorityBadge($req['priority']); ?></td>
                        <td><?= getMaintenanceStatus($req['status'], $req['created_at']); ?></td>
                        <td><?= date("F d, Y", strtotime($req['created_at'])); ?></td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</div>

</body>
</html>