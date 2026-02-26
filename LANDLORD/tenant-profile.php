<?php
require_once '../connection.php';
include '../session_auth.php';

if (!isset($_SESSION['landlord_id'])) {
    die("Unauthorized access. Please log in as landlord.");
}

$landlord_id = (int) $_SESSION['landlord_id'];

$tenant_id = isset($_GET['tenant_id']) ? (int) $_GET['tenant_id'] : 0;
if ($tenant_id <= 0) {
    die("Invalid tenant ID.");
}

$sql = "SELECT ID, firstName, lastName, phoneNum, email, created_at, profilePic 
        FROM tenanttbl 
        WHERE ID = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $tenant_id);
$stmt->execute();
$result = $stmt->get_result();
$tenant = $result->fetch_assoc();
$stmt->close();

if (!$tenant) {
    die("Tenant not found.");
}

$profilePath = $tenant['profilePic'] ?? '';
if (!empty($profilePath) && !str_starts_with($profilePath, 'http')) {
    $profilePath = "../uploads/" . $profilePath;
}

$firstLetter = strtoupper(substr($tenant['firstName'], 0, 1));
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/7.0.1/css/all.min.css">
    <link rel="stylesheet" href="../css/bootstrap.min.css">
    <link rel="stylesheet" href="../css/style.css?v=<?= time(); ?>">

    <title>Tenant Profile</title>

    <style>
        /* PAGE WRAPPER */
        /* ==========================================
   TENANT PROFILE (LANDLORD VIEW)
   SAME STYLE AS MY ACCOUNT
========================================== */

.landlord-page {
    margin-top: 140px;
    padding: 0 20px 60px 20px;
    min-height: calc(100vh - 140px);
    background: linear-gradient(135deg, #eef2f7 0%, #dde5ee 100%);
}

.account-card {
    background: #f8f9fb;
    border-radius: 28px;
    box-shadow: 0 20px 50px rgba(0,0,0,0.08);
    overflow: hidden;
    padding-bottom: 50px;
    position: relative;
}

.account-card::before {
    content: "";
    display: block;
    height: 220px;
    background: linear-gradient(135deg, #8d0b41 0%, #6a0831 100%);
}

.profile-content {
    text-align: center;
    margin-top: -80px;
    padding: 0 30px;
}

.profile-img,
.avatar {
    width: 160px;
    height: 160px;
    border-radius: 50%;
    border: 6px solid #ffffff;
    box-shadow: 0 10px 30px rgba(0,0,0,0.15);
    object-fit: cover;
}

.avatar {
    display: flex;
    align-items: center;
    justify-content: center;
    background: linear-gradient(135deg, #8d0b41 0%, #6a0831 100%);
    color: white;
    font-size: 64px;
    font-weight: 700;
}

.profile-name {
    font-size: 2rem;
    font-weight: 700;
    color: #2d3748;
    margin-top: 20px;
    margin-bottom: 10px;
}

.profile-role {
    display: inline-block;
    padding: 6px 18px;
    border-radius: 50px;
    background: #e2e8f0;
    color: #8d0b41;
    font-size: 0.9rem;
    font-weight: 500;
    margin-bottom: 30px;
}

.info-cards {
    display: flex;
    justify-content: center;
    gap: 20px;
    flex-wrap: wrap;
    margin-bottom: 35px;
}

.info-card {
    background: #eef2f7;
    border-radius: 14px;
    padding: 18px 24px;
    min-width: 260px;
    display: flex;
    align-items: center;
    gap: 15px;
    border: 1px solid #d6dde6;
}

.info-icon {
    width: 44px;
    height: 44px;
    background: linear-gradient(135deg, #8d0b41 0%, #6a0831 100%);
    border-radius: 10px;
    display: flex;
    align-items: center;
    justify-content: center;
    color: #ffffff;
    font-size: 18px;
}

.info-text small {
    display: block;
    font-size: 0.75rem;
    color: #718096;
    font-weight: 600;
    letter-spacing: 0.5px;
}

.info-text span {
    font-size: 0.95rem;
    font-weight: 600;
    color: #2d3748;
}

.account-actions {
    display: flex;
    justify-content: center;
    gap: 15px;
    flex-wrap: wrap;
}

.btn-primary-custom {
    padding: 10px 22px;
    border-radius: 50px;
    background: linear-gradient(135deg, #8d0b41 0%, #6a0831 100%);
    color: white;
    border: none;
    font-weight: 600;
    transition: 0.3s ease;
}

.btn-primary-custom:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 18px rgba(141, 11, 65, 0.3);
}

.btn-danger-custom {
    padding: 10px 22px;
    border-radius: 50px;
    background: #fbd5d5;
    color: #c53030;
    border: none;
    font-weight: 600;
    transition: 0.3s ease;
}

.btn-danger-custom:hover {
    background: #fc8181;
    color: white;
}

@media (max-width: 768px) {
    .profile-img,
    .avatar {
        width: 120px;
        height: 120px;
    }

    .profile-name {
        font-size: 1.5rem;
    }

    .info-card {
        width: 100%;
    }

    .account-actions {
        flex-direction: column;
    }

    .btn-primary-custom,
    .btn-danger-custom {
        width: 100%;
    }
}
        }
    </style>
</head>

<body>

    <?php include '../Components/landlord-header.php'; ?>

    <div class="landlord-page">
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-lg-10">
                    <div class="account-card">
    <div class="profile-content">

        <?php if (!empty($tenant['profilePic'])): ?>
            <img src="<?= htmlspecialchars($profilePath); ?>" class="profile-img">
        <?php else: ?>
            <div class="avatar"><?= $firstLetter ?></div>
        <?php endif; ?>

        <h2 class="profile-name">
            <?= htmlspecialchars(ucwords($tenant['firstName'] . ' ' . $tenant['lastName'])); ?>
        </h2>

        <div class="profile-role">
            <i class="fas fa-user"></i> Tenant
        </div>

        <div class="info-cards">

            <div class="info-card">
                <div class="info-icon">
                    <i class="fas fa-phone"></i>
                </div>
                <div class="info-text">
                    <small>PHONE NUMBER</small>
                    <span><?= htmlspecialchars($tenant['phoneNum']); ?></span>
                </div>
            </div>

            <div class="info-card">
                <div class="info-icon">
                    <i class="fas fa-envelope"></i>
                </div>
                <div class="info-text">
                    <small>EMAIL ADDRESS</small>
                    <span><?= htmlspecialchars($tenant['email']); ?></span>
                </div>
            </div>

            <div class="info-card">
                <div class="info-icon">
                    <i class="fas fa-calendar"></i>
                </div>
                <div class="info-text">
                    <small>MEMBER SINCE</small>
                    <span><?= date("F j, Y", strtotime($tenant['created_at'])); ?></span>
                </div>
            </div>

        </div>

        <div class="account-actions">
            <button class="btn-primary-custom"
                onclick="location.href='landlord-message.php?tenant_id=<?= $tenant['ID']; ?>'">
                <i class="fas fa-comments"></i> Chat
            </button>

            <button class="btn-danger-custom"
                onclick="location.href='report-tenant.php?tenant_id=<?= $tenant_id ?>'">
                <i class="fas fa-flag"></i> Report
            </button>
        </div>

    </div>
</div>
                </div>
            </div>
        </div>
    </div>

    <script src="../js/bootstrap.bundle.min.js"></script>
</body>

</html>