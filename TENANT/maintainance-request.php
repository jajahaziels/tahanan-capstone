<?php
require_once '../connection.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $sql = "INSERT INTO maintenance_requests 
    (lease_id, tenant_id, landlord_id, title, description, priority)
    VALUES (?,?,?,?,?,?)";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param(
        "iiisss",
        $_POST['lease_id'],
        $_POST['tenant_id'],
        $_POST['landlord_id'],
        $_POST['title'],
        $_POST['description'],
        $_POST['priority']
    );
    $stmt->execute();
}
?>

<div class="card-custom">
    <h4 class="section-title text-primary">Maintenance Request</h4>

    <form method="POST">
        <input type="hidden" name="lease_id" value="<?= $_GET['lease_id']; ?>">

        <div class="mb-3">
            <label>Title</label>
            <input class="form-control" name="title" required>
        </div>

        <div class="mb-3">
            <label>Description</label>
            <textarea class="form-control" rows="4" name="description"></textarea>
        </div>

        <div class="mb-3">
            <label>Priority</label>
            <select class="form-control" name="priority">
                <option>Low</option>
                <option selected>Medium</option>
                <option>High</option>
            </select>
        </div>

        <button class="btn btn-primary">Submit Request</button>
    </form>
</div>