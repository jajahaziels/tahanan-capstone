<?php
require_once '../connection.php';
include '../session_auth.php';

if (!isset($_SESSION['landlord_id'])) {
    die("Unauthorized access. Please log in as landlord.");
}

function formatPhilippinePhone($phoneNum) {
    if (empty($phoneNum)) return 'Not provided';
    $phone = preg_replace('/[^0-9]/', '', $phoneNum);
    if (substr($phone, 0, 1) === '0') $phone = substr($phone, 1);
    if (substr($phone, 0, 2) === '63') $phone = substr($phone, 2);
    if (strlen($phone) === 10)
        return '+63 ' . substr($phone, 0, 3) . ' ' . substr($phone, 3, 3) . ' ' . substr($phone, 6);
    if (strlen($phone) === 9)
        return '+63 ' . substr($phone, 0, 3) . ' ' . substr($phone, 3, 3) . ' ' . substr($phone, 6);
    return '+63 ' . $phone;
}

$landlord_id = (int) $_SESSION['landlord_id'];
$tenant_id = isset($_GET['tenant_id']) ? (int) $_GET['tenant_id'] : 0;
if ($tenant_id <= 0) die("Invalid tenant ID.");

$sql = "SELECT ID, firstName, lastName, phoneNum, email, created_at, profilePic FROM tenanttbl WHERE ID = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $tenant_id);
$stmt->execute();
$tenant = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$tenant) die("Tenant not found.");

$profilePath = $tenant['profilePic'] ?? '';
if (!empty($profilePath) && !str_starts_with($profilePath, 'http'))
    $profilePath = "../uploads/" . $profilePath;

$firstLetter = strtoupper(substr($tenant['firstName'], 0, 1));
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link rel="stylesheet" href="../css/bootstrap.min.css">
    <link rel="stylesheet" href="../css/style.css?v=<?= time(); ?>">
    <title>Tenant Profile</title>
    <style>
        .tenant-profile-page {
            margin-top: 140px;
            padding: 0 20px 60px;
            min-height: calc(100vh - 140px);
            background: #eef2f7;
        }

        .profile-card {
            background: #fff;
            border-radius: 20px;
            border: 1px solid #e2e8f0;
            overflow: hidden;
        }

        .profile-banner {
            height: 160px;
            background: #8d0b41;
        }

        .profile-body {
            text-align: center;
            padding: 0 28px 40px;
        }

        /* Avatar — both image and initial fallback */
        .avatar-wrap {
            display: flex;
            justify-content: center;
            margin-top: -64px;
            margin-bottom: 16px;
        }

        .profile-img,
        .avatar-initial {
            width: 128px;
            height: 128px;
            border-radius: 50%;
            border: 5px solid #fff;
            box-shadow: 0 4px 16px rgba(0,0,0,0.10);
        }

        .profile-img {
            object-fit: cover;
        }

        .avatar-initial {
            background: #6a0831;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 52px;
            font-weight: 600;
            color: #f4c0d1;
            letter-spacing: -2px;
            flex-shrink: 0;
        }

        .profile-name {
            font-size: 1.6rem;
            font-weight: 700;
            color: #1a202c;
            margin-bottom: 8px;
        }

        .profile-badge {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            background: #fbeaf0;
            color: #8d0b41;
            font-size: 0.85rem;
            font-weight: 600;
            padding: 5px 16px;
            border-radius: 50px;
            margin-bottom: 28px;
        }

        /* Info grid */
        .info-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 14px;
            margin-bottom: 28px;
        }

        .info-card {
            background: #f7f9fc;
            border: 1px solid #e2e8f0;
            border-radius: 14px;
            padding: 14px 16px;
            display: flex;
            align-items: center;
            gap: 12px;
            text-align: left;
        }

        .info-icon {
            width: 40px;
            height: 40px;
            background: #8d0b41;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
            color: #f4c0d1;
            font-size: 15px;
        }

        .info-label {
            font-size: 10px;
            font-weight: 700;
            letter-spacing: 0.6px;
            text-transform: uppercase;
            color: #718096;
            margin-bottom: 3px;
        }

        .info-value {
            font-size: 0.9rem;
            font-weight: 600;
            color: #2d3748;
        }

        /* Action buttons */
        .profile-actions {
            display: flex;
            justify-content: center;
            gap: 12px;
            flex-wrap: wrap;
        }

        .btn-chat {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 10px 26px;
            border-radius: 50px;
            background: #8d0b41;
            color: #fff;
            border: none;
            font-weight: 600;
            font-size: 0.9rem;
            cursor: pointer;
            transition: background 0.2s, transform 0.15s;
        }

        .btn-chat:hover {
            background: #6a0831;
            transform: translateY(-1px);
        }

        .btn-report {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 10px 26px;
            border-radius: 50px;
            background: #fbd5d5;
            color: #c53030;
            border: none;
            font-weight: 600;
            font-size: 0.9rem;
            cursor: pointer;
            transition: background 0.2s, transform 0.15s;
        }

        .btn-report:hover {
            background: #fc8181;
            color: #fff;
            transform: translateY(-1px);
        }

        @media (max-width: 640px) {
            .info-grid { grid-template-columns: 1fr; }
            .profile-actions { flex-direction: column; align-items: stretch; }
            .btn-chat, .btn-report { justify-content: center; }
            .avatar-initial, .profile-img { width: 100px; height: 100px; font-size: 40px; }
            .avatar-wrap { margin-top: -50px; }
        }
    </style>
</head>
<body>

<?php include '../Components/landlord-header.php'; ?>

<div class="tenant-profile-page">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-lg-9">
                <div class="profile-card">
                    <div class="profile-banner"></div>

                    <div class="profile-body">

                        <div class="avatar-wrap">
                            <?php if (!empty($tenant['profilePic'])): ?>
                                <img src="<?= htmlspecialchars($profilePath) ?>"
                                     alt="<?= htmlspecialchars($tenant['firstName']) ?>"
                                     class="profile-img">
                            <?php else: ?>
                                <div class="avatar-initial"><?= $firstLetter ?></div>
                            <?php endif; ?>
                        </div>

                        <h2 class="profile-name">
                            <?= htmlspecialchars(ucwords($tenant['firstName'] . ' ' . $tenant['lastName'])) ?>
                        </h2>

                        <div class="profile-badge">
                            <i class="fas fa-user" style="font-size:12px;"></i>
                            Tenant
                        </div>

                        <div class="info-grid">
                            <div class="info-card">
                                <div class="info-icon">
                                    <i class="fas fa-phone"></i>
                                </div>
                                <div>
                                    <div class="info-label">Phone number</div>
                                    <div class="info-value"><?= formatPhilippinePhone($tenant['phoneNum']) ?></div>
                                </div>
                            </div>

                            <div class="info-card">
                                <div class="info-icon">
                                    <i class="fas fa-envelope"></i>
                                </div>
                                <div>
                                    <div class="info-label">Email address</div>
                                    <div class="info-value"><?= htmlspecialchars($tenant['email']) ?></div>
                                </div>
                            </div>

                            <div class="info-card">
                                <div class="info-icon">
                                    <i class="fas fa-calendar"></i>
                                </div>
                                <div>
                                    <div class="info-label">Member since</div>
                                    <div class="info-value"><?= date("F j, Y", strtotime($tenant['created_at'])) ?></div>
                                </div>
                            </div>
                        </div>

                        <div class="profile-actions">
                            <button class="btn-chat"
                                onclick="location.href='landlord-message.php?tenant_id=<?= $tenant['ID'] ?>'">
                                <i class="fas fa-comments"></i> Chat
                            </button>
                            <button class="btn-report"
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