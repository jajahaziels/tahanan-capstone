<?php
require_once '../connection.php';
$lease_id = (int) $_GET['lease_id'];

$sql = "SELECT * FROM payments WHERE lease_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $lease_id);
$stmt->execute();
$payments = $stmt->get_result();
?>

<div class="card-custom">
    <h4 class="section-title text-primary">Payments</h4>

    <table class="table table-hover">
        <thead>
            <tr>
                <th>Type</th>
                <th>Amount</th>
                <th>Due Date</th>
                <th>Status</th>
            </tr>
        </thead>
        <tbody>
            <?php while ($p = $payments->fetch_assoc()): ?>
                <tr>
                    <td><?= $p['payment_type']; ?></td>
                    <td>₱<?= number_format($p['amount'], 2); ?></td>
                    <td><?= date("F j, Y", strtotime($p['due_date'])); ?></td>
                    <td>
                        <span class="badge bg-<?= $p['status'] === 'Paid' ? 'success' : 'warning'; ?>">
                            <?= $p['status']; ?>
                        </span>
                    </td>
                </tr>
            <?php endwhile; ?>
        </tbody>
    </table>
</div>