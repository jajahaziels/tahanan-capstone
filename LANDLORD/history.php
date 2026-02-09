<?php
require_once '../connection.php';
include '../session_auth.php';

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
    <!-- MAIN CSS -->
    <link rel="stylesheet" href="../css/style.css?v=<?= time(); ?>">
    <title>History</title>
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

    /* Small Avatar for Table */
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

    .payment-status-badge {
        padding: 6px 12px;
        border-radius: 20px;
        font-size: 0.8rem;
        font-weight: 600;
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
    
</style>

<body>
    <!-- HEADER -->
    <?php include '../Components/landlord-header.php'; ?>
    <div>

        <!-- Active Tenants & Payments -->
        <div class="payments-section">
            <div class="section-header">
                <h3 class="section-title">
                    <i class="fas fa-credit-card"></i>
                    Active Tenants & Payments
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
                                            <img src="../uploads/profiles/<?= htmlspecialchars($tenant['profilePic']) ?>"
                                                alt="<?= htmlspecialchars($tenant_name) ?>"
                                                class="rounded-circle" width="40" height="40"
                                                style="object-fit: cover; border: 2px solid #8d0b41;">
                                        <?php else: ?>
                                            <div class="profile-avatar-sm"><?= $tenant_initial ?></div>
                                        <?php endif; ?>
                                    </td>
                                    <td class="fw-bold text-dark"><?= htmlspecialchars($tenant_name) ?></td>
                                    <td>
                                        <span class="text-muted"><i class="fas fa-home me-1"></i> <?= htmlspecialchars($tenant['property_name']) ?></span>
                                    </td>
                                    <td>
                                        <span class="fw-bold" style="color: #2d3748;">₱<?= number_format($tenant['amount'] ?? 0, 2) ?></span>
                                    </td>
                                    <td>
                                        <span class="badge <?= $tenant['last_payment_date'] ? 'bg-light text-success' : 'bg-light text-danger' ?>" style="border: 1px solid currentColor;">
                                            <?= $last_payment ?>
                                        </span>
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
    </div>


</body>

</html>