<?php
require_once '../connection.php';
include '../session_auth.php';

$landlord_id = $_SESSION['landlord_id'];

// Fetch active tenants and their last payment info based on tahanandb schema
$query = "SELECT 
            t.ID as tenant_id, 
            t.firstName, 
            t.lastName, 
            t.profilePic,
            l.listingName as property_name, 
            ls.rent as amount, 
            ls.pdf_path,
            MAX(p.paid_date) AS last_payment_date
          FROM leasetbl ls
          JOIN tenanttbl t ON ls.tenant_id = t.ID
          JOIN listingtbl l ON ls.listing_id = l.ID
          LEFT JOIN paymentstbl p ON ls.ID = p.lease_id
          WHERE ls.landlord_id = ? 
          AND ls.status = 'active'
          GROUP BY ls.ID";

$stmt = $conn->prepare($query);
$stmt->bind_param("i", $landlord_id);
$stmt->execute();
$result = $stmt->get_result();

$active_tenants = [];
while ($row = $result->fetch_assoc()) {
    $active_tenants[] = $row;
}

// Fetch maintenance requests / complaints for this landlord
$complaints_query = "SELECT 
                        mr.ID as complaint_id,
                        mr.title,
                        mr.description,
                        mr.category,
                        mr.priority,
                        mr.status,
                        mr.requested_date,
                        mr.scheduled_date,
                        mr.completed_date,
                        mr.photo_path,
                        t.firstName,
                        t.lastName,
                        t.profilePic,
                        l.listingName as property_name
                    FROM maintenance_requeststbl mr
                    JOIN leasetbl ls ON mr.lease_id = ls.ID
                    JOIN tenanttbl t ON ls.tenant_id = t.ID
                    JOIN listingtbl l ON ls.listing_id = l.ID
                    WHERE mr.landlord_id = ?
                    ORDER BY mr.requested_date DESC";

$stmt2 = $conn->prepare($complaints_query);
$stmt2->bind_param("i", $landlord_id);
$stmt2->execute();
$result2 = $stmt2->get_result();

$complaints = [];
while ($row = $result2->fetch_assoc()) {
    $complaints[] = $row;
}

function getPriorityBadge($priority)
{
    return match (strtolower($priority)) {
        'low' => '<span class="badge bg-success">Low</span>',
        'medium' => '<span class="badge bg-warning text-dark">Medium</span>',
        'high' => '<span class="badge bg-danger">High</span>',
        'urgent' => '<span class="badge bg-dark text-white">Urgent</span>',
        default => '<span class="badge bg-secondary">' . htmlspecialchars($priority) . '</span>'
    };
}

function getStatusBadge($status)
{
    return match (strtolower($status)) {
        'pending' => '<span class="badge bg-warning text-dark">Pending</span>',
        'in progress' => '<span class="badge bg-primary">Scheduled</span>', // Friendly label
        'completed' => '<span class="badge bg-success">Completed</span>',
        'rejected' => '<span class="badge bg-danger">Rejected</span>',
        default => '<span class="badge bg-secondary">' . htmlspecialchars($status) . '</span>'
    };
}

?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <!-- FAVICON -->
    <link rel="shortcut icon" href="../favicon.ico" type="image/x-icon">
    <!-- FONT AWESOME -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css"
        integrity="sha512-DTOQO9RWCH3ppGqcWaEA1BIZOC6xxalwEsw9c2QQeAIftl+Vegovlnee1c9QX4TctnWMn13TZye+giMm8e2LwA=="
        crossorigin="anonymous" referrerpolicy="no-referrer" />
    <!-- BOOTSTRAP -->
    <link rel="stylesheet" href="../css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <!-- MAIN CSS -->
    <link rel="stylesheet" href="../css/style.css?v=<?= time(); ?>">
    <title>Rental Management</title>
</head>

<style>
    /* Payments Section Styles */
    .payments-section {
        margin-top: 140px !important;
        width: 80%;
        background: white;
        border-radius: 24px;
        box-shadow: 0 10px 40px rgba(0, 0, 0, 0.08);
        padding: 40px;
        position: relative;
        left: 50%;
        transform: translateX(-50%);
    }

    .table {
        border-collapse: separate;
        border-spacing: 0 8px;
        margin-top: -8px;
    }

    .table thead th {
        background-color: #f8fafc;
        color: #718096;
        text-transform: uppercase;
        font-size: 0.75rem;
        letter-spacing: 0.05em;
        border: none;
        padding: 16px;
    }

    .table tbody tr {
        background-color: white;
        box-shadow: 0 2px 4px rgba(0, 0, 0, 0.02);
        transition: transform 0.2s;
    }

    .table tbody tr:hover {
        transform: scale(1.005);
        background-color: #fffafb !important;
    }

    .table td {
        padding: 20px 16px;
        border-top: 1px solid #edf2f7;
        border-bottom: 1px solid #edf2f7;
        color: #4a5568;
        vertical-align: middle;
    }

    .profile-avatar-sm {
        width: 40px;
        height: 40px;
        border-radius: 50%;
        background: linear-gradient(135deg, #8d0b41 0%, #6a0831 100%);
        color: white;
        display: flex;
        align-items: center;
        justify-content: center;
        font-weight: 600;
        font-size: 14px;
    }

    .section-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 30px;
        padding-bottom: 20px;
        border-bottom: 2px solid #e2e8f0;
    }

    .section-title {
        font-size: 1.75rem;
        font-weight: 700;
        color: #2d3748;
        margin: 0;
        display: flex;
        align-items: center;
        gap: 12px;
    }

    .section-title i {
        color: #8d0b41;
    }

    .empty-reviews {
        text-align: center;
        padding: 60px 20px;
        color: #718096;
    }

    .empty-reviews i {
        font-size: 64px;
        color: #cbd5e0;
        margin-bottom: 20px;
    }

    .empty-reviews h3 {
        color: #4a5568;
        margin-bottom: 8px;
    }

    .empty-reviews p {
        color: #a0aec0;
    }

    .btn-theme {
    background-color: #8d0b41;
    border-color: #8d0b41;
    color: #fff;
    border-radius: 20px;
    font-size: 0.8rem;
}

.btn-theme:hover {
    background-color: #6a0831;
    border-color: #6a0831;
    color: #fff;
}
    
    .lease-btn {
        border: 1px solid #0d6efd;   
        color: #0d6efd;          
        background-color: transparent; 
        transition: all 0.3s ease;
    }

    .lease-btn:hover {
        background-color: #0d6efd;  
        border-color: #0d6efd;   
        color: #fff;               
    }                      

    .remove-complaint-btn {
        border: 1px solid #FF0000;   
        color: #FF0000;          
        background-color: transparent; 
        transition: all 0.3s ease;
        }

        .remove-complaint-btn:hover {
        background-color: #FF0000;  
        border-color: #FF0000;   
        color: #fff;               
    }
    
</style>

<body>
    <!-- HEADER -->
    <?php include '../Components/landlord-header.php'; ?>

    <div class="payments-section">
        <div class="section-header">
            <h3 class="section-title">
                <i class="bi bi-person-vcard" style="color: #8d0b41; font-size: 2.5rem; margin-right: 8px;"></i>
                Active Tenants & Payment Records
            </h3>
            <?php if (!empty($active_tenants)): ?>
                <span class="action-btn-primary" style="padding: 5px 15px; border-radius: 20px; font-size: 14px;">
                    <?= count($active_tenants) ?> Active
                </span>
            <?php endif; ?>
        </div>

        <?php if (!empty($active_tenants)): ?>
            <div class="table-responsive">
                <table class="table align-middle">
                    <thead>
                        <tr>
                            <th>Profile</th>
                            <th>Tenant Name</th>
                            <th>Property</th>
                            <th>Lease</th>
                            <th>Rent Amount</th>
                            <th>Last Payment</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($active_tenants as $tenant):
                            $tenant_name = ucwords(strtolower($tenant['firstName'] . ' ' . $tenant['lastName']));
                            $tenant_initial = strtoupper(substr($tenant['firstName'], 0, 1));
                            $last_payment = $tenant['last_payment_date'] ? date("M j, Y", strtotime($tenant['last_payment_date'])) : 'No Payment';
                            ?>
                            <tr>
                                <td>
                                    <?php if (!empty($tenant['profilePic'])): ?>
                                        <a href="tenant-profile.php?tenant_id=<?= $tenant['tenant_id'] ?>">
                                            <img src="../uploads/<?= htmlspecialchars($tenant['profilePic']) ?>"
                                                alt="<?= htmlspecialchars($tenant_name) ?>" class="rounded-circle" width="40" height="40"
                                                style="object-fit: cover; border: 2px solid #8d0b41;">
                                        </a>
                                    <?php else: ?>
                                        <div class="profile-avatar-sm"><?= $tenant_initial ?></div>
                                    <?php endif; ?>
                                </td>
                                <td class="fw-bold text-dark"><?= htmlspecialchars($tenant_name) ?></td>
                                <td>
                                    <span class="text-muted"><i class="fas fa-home me-1"></i>
                                        <?= htmlspecialchars($tenant['property_name']) ?></span>
                                </td>
                                <td>
                                    <?php if (!empty($tenant['pdf_path'])): ?>
                                        <a href="../uploads/<?= htmlspecialchars($tenant['pdf_path']) ?>" target="_blank"
                                            class="btn btn-primary btn-sm rounded-pill lease-btn">
                                            <i class="bi bi-file-earmark-pdf"></i> View Lease
                                        </a>

                                        <style>
                                            .lease-btn {
                                                border: 1px solid #0d6efd;   
                                                color: #0d6efd;          
                                                background-color: transparent; 
                                                transition: all 0.3s ease;
                                            }

                                            .lease-btn:hover {
                                                background-color: #0d6efd;  
                                                border-color: #0d6efd;   
                                                color: #fff;               
                                            }
                                        </style>
                    
                                    <?php else: ?>
                                        <span class="text-muted">No Lease</span>
                                    <?php endif; ?>
                                </td>  
                                <td>
                                    <span class="fw-bold"
                                        style="color: #2d3748;">₱<?= number_format($tenant['amount'] ?? 0, 2) ?></span>
                                </td>
                                <td>
                                    <button
                                        class="btn btn-sm <?= $tenant['last_payment_date'] ? 'btn-success' : 'btn-danger' ?>"
                                        style="border-radius: 20px; font-size: 0.8rem;" data-bs-toggle="modal"
                                        data-bs-target="#paymentModal" data-tenant="<?= $tenant['tenant_id'] ?>"
                                        data-name="<?= htmlspecialchars($tenant_name) ?>">
                                        <?= $last_payment ?>
                                    </button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <div class="empty-reviews">
                <i class="fas fa-user-slash"></i>
                <h3>No Active Tenants</h3>
                <p>Once you approve applications, your active tenants and their rent status will appear here.</p>
            </div>
        <?php endif; ?>
    </div>

    <!-- Payment Modal -->
    <div class="modal fade" id="paymentModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <form id="paymentForm" method="post" action="record-payment.php">
                    <div class="modal-header">
                        <h5 class="modal-title">Record Payment</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <input type="hidden" name="tenant_id" id="tenant_id">
                        <div class="mb-3">
                            <label for="amount" class="form-label">Payment Amount</label>
                            <input type="number" class="form-control" name="amount" id="amount" required>
                        </div>
                        <div class="mb-3">
                            <label for="paid_date" class="form-label">Payment Date</label>
                            <input type="date" class="form-control" name="paid_date" id="paid_date" required>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="submit" class="btn btn-primary">Save Payment</button>
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="payments-section mt-5">
    <div class="section-header">
        <h3 class="section-title">
            <i class="bi bi-tools"></i>
            Complaint / Maintenance Requests
        </h3>
        <?php if (!empty($complaints)): ?>
                <span class="action-btn-primary" style="padding: 5px 15px; border-radius: 20px; font-size: 14px;">
                    <?= count($complaints) ?> Requests
                </span>
            <?php endif; ?>
        </div>
    
        <!-- Maintenance Request Table -->
        <?php if (!empty($complaints)): ?>
            <div class="table-responsive">
                <table class="table align-middle">
                    <thead>
                        <tr>
                            <th>Profile</th>
                            <th>Tenant Name</th>
                            <th>Property</th>
                            <th>Title</th>
                            <th>Category</th>
                            <th>Priority</th>
                            <th>Status</th>
                            <th>Requested Date</th>
                            <th>Scheduled Date</th>
                            <th>Completed Date</th>
                            <th>Photo</th>
                            <th>Action</th>
                        </tr>
                    <thead>
                <tbody>
        <?php foreach ($complaints as $complaint):
            $status = strtolower(trim($complaint['status']));
            $tenant_name = ucwords(strtolower($complaint['firstName'] . ' ' . $complaint['lastName']));
                            $tenant_initial = strtoupper(substr($complaint['firstName'], 0, 1));
                            $requested_date = $complaint['requested_date'] ? date("M j, Y", strtotime($complaint['requested_date'])) : '-';
                            $scheduled_date = $complaint['scheduled_date'] ? date("M j, Y", strtotime($complaint['scheduled_date'])) : '-';
                            $completed_date = $complaint['completed_date'] ? date("M j, Y", strtotime($complaint['completed_date'])) : '-';
                            ?>
                            <tr>
                                <td>
                                    <?php if (!empty($complaint['profilePic'])): ?>
                                        <a href="tenant-profile.php?tenant_id=<?= $tenant['tenant_id'] ?>">
                                            <img src="../uploads/<?= htmlspecialchars($tenant['profilePic']) ?>"
                                                alt="<?= htmlspecialchars($tenant_name) ?>" class="rounded-circle" width="40" height="40"
                                                style="object-fit: cover; border: 2px solid #8d0b41;">
                                        </a>
                                    <?php else: ?>
                                        <div class="profile-avatar-sm"><?= $tenant_initial ?></div>
                                    <?php endif; ?>
                                </td>
                                <td class="fw-bold text-dark"><?= htmlspecialchars($tenant_name) ?></td>
                                <td><span class="text-muted"><i
                                            class="fas fa-home me-1"></i><?= htmlspecialchars($complaint['property_name']) ?></span></td>
                                <td><?= htmlspecialchars($complaint['title']) ?></td>
                                <td><?= htmlspecialchars($complaint['category']) ?></td>
                                <td><?= getPriorityBadge($complaint['priority']) ?></td>
                                <td><?= getStatusBadge($complaint['status']) ?></td>
                                <td><?= $requested_date ?></td>
                                <td><?= $scheduled_date ?></td>
                                <td><?= $completed_date ?></td>
                                <td>
                                    <?php if (!empty($complaint['photo_path'])): ?>
                                        <a href="../uploads/<?= htmlspecialchars($complaint['photo_path']) ?>" target="_blank">
                                            <i class="fas fa-image"></i> View
                                        </a>
                                    <?php else: ?>
                                        -
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if ($status === 'completed'): ?>
                                <!-- Remove only -->
                                <button class="btn btn-sm rounded-pill remove-complaint-btn"
                                    data-id="<?= $complaint['complaint_id'] ?>">
                                            <i class="bi bi-trash3-fill"></i> Remove
                                </button>

                                <style>
                                            .remove-complaint-btn {
                                            border: 1px solid #FF0000;   
                                            color: #FF0000;          
                                            background-color: transparent; 
                                            transition: all 0.3s ease;
                                        }

                                            .remove-complaint-btn:hover {
                                            background-color: #FF0000;  
                                            border-color: #FF0000;   
                                            color: #fff;               
                                        }
                                    </style>
        <?php elseif ($status === 'rejected'): ?>

                                <!-- Respond + Remove -->
                            <div class="d-flex gap-2">
                                <button class="btn btn-sm btn-outline-primary"
                                    data-bs-toggle="modal" 
                                    data-bs-target="#complaintModal"
                                    data-id="<?= $complaint['complaint_id'] ?>" 
                                    data-title="<?= htmlspecialchars($complaint['title']) ?>">
                                    <i class="bi bi-reply-all-fill"></i> Respond
                                </button>
                                <button class="btn btn-sm btn-outline-danger remove-complaint-btn"
                                    data-id="<?= $complaint['complaint_id'] ?>">
                                    <i class="bi bi-trash3-fill"></i>
                                </button>
                            </div>

                        <?php else: ?>
                            <!-- pending + in progress -->
                            <button class="btn btn-sm btn-primary shadow-sm"
                                data-bs-toggle="modal" 
                                data-bs-target="#complaintModal"
                                data-id="<?= $complaint['complaint_id'] ?>" 
                                data-title="<?= htmlspecialchars($complaint['title']) ?>">
                                <i class="bi bi-reply-all-fill"></i> Respond
                            </button>
                        <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <div class="empty-reviews">
                <i class="fas fa-exclamation-triangle"></i>
                <h3>No Complaints / Requests</h3>
                <p>Once tenants submit maintenance requests, they will appear here for your review and action.</p>
            </div>
        <?php endif; ?>
    </div>

        <!-- Complaint Response Modal -->
<div class="modal fade" id="complaintModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form id="complaintForm" method="post" action="maintenance-respond.php">
                <div class="modal-header">
                    <h5 class="modal-title">Respond to Complaint</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="complaint_id" id="complaint_id">
                    <div class="mb-3">
                        <label for="status" class="form-label">Status</label>
                        <select class="form-select" name="status" id="status" required>
                        <option value="pending">Pending</option>
                        <option value="in progress">Scheduled</option> 
                        <option value="completed">Completed</option>
                        <option value="rejected">Rejected</option>
                    </select>
                    </div>
                    <div class="mb-3">
                        <label for="response" class="form-label">Message / Action to Tenant</label>
                        <textarea class="form-control" name="response" id="response" rows="3" placeholder="Write your message here..."></textarea>
                    </div>
                    <div class="mb-3">
                        <label for="scheduled_date" class="form-label">Scheduled Date (if applicable)</label>
                        <input type="date" class="form-control" name="scheduled_date" id="scheduled_date">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="submit" class="btn btn-primary">Send Response</button>
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                </div>
            </form>
        </div>
    </div>
</div>

    <script src="../js/bootstrap.bundle.min.js"></script>
    <script>
        var paymentModal = document.getElementById('paymentModal');
        paymentModal.addEventListener('show.bs.modal', function (event) {
            var button = event.relatedTarget;
            var tenantId = button.getAttribute('data-tenant');
            var tenantName = button.getAttribute('data-name');

            paymentModal.querySelector('#tenant_id').value = tenantId;
            paymentModal.querySelector('.modal-title').textContent = 'Record Payment for ' + tenantName;
        });
    </script>

    <script>
    var complaintModal = document.getElementById('complaintModal');
    complaintModal.addEventListener('show.bs.modal', function (event) {
    var button = event.relatedTarget;
    var complaintId = button.getAttribute('data-id');
    var title = button.getAttribute('data-title');
    var status = button.getAttribute('data-status');

    complaintModal.querySelector('#complaint_id').value = complaintId;
    complaintModal.querySelector('.modal-title').textContent = 'Respond to: ' + title;
    complaintModal.querySelector('#status').value = status;
});
</script>

<script>
    document.querySelectorAll('.remove-complaint-btn').forEach(button => {
    button.addEventListener('click', function() {

        const complaintId = this.dataset.id;

        if (!confirm("Are you sure you want to remove this completed request?")) {
            return;
        }

        fetch('maintenance-delete.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: 'complaint_id=' + complaintId
        })
        .then(res => res.text())
        .then(response => {
            if (response.trim() === 'success') {
                location.reload();
            } else {
                alert("Failed to delete: " + response);
            }
        })
        .catch(error => {
            alert("Error: " + error);
        });

    });
});
</script>

</body>

</html>