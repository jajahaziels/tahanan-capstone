<?php
require_once '../connection.php';
include '../session_auth.php';

// login tenant from session
$tenant_id = $_SESSION['user_id'];

// fetch tenant profile
$sql = "SELECT firstName, lastName, phoneNum, email, created_at, profilePic 
        FROM tenanttbl 
        WHERE ID= ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $tenant_id);
$stmt->execute();
$result = $stmt->get_result();
$tenant = $result->fetch_assoc();

// image path
$profilePath = $tenant['profilePic'] ?? '';
if (!empty($profilePath) && !str_starts_with($profilePath, 'http')) {
    $profilePath = "../uploads/" . $profilePath;
}

$firstLetter = strtoupper(substr($tenant['firstName'], 0, 1));
$fullName = ucwords($tenant['firstName'] . ' ' . $tenant['lastName']);
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
    <title>My Account - <?= htmlspecialchars($fullName); ?> | Tahanan</title>
    
    <style>
        /* ========================================
           TENANT ACCOUNT PAGE - MODERN UI
           ======================================== */
        
        .tenant-page {
            margin-top: 140px !important;
            padding: 0 20px 60px 20px;
            min-height: calc(100vh - 140px);
            background: linear-gradient(135deg, #f5f7fa 0%, #e8eef5 100%);
        }

        /* Page Header */
        .page-header {
            margin-bottom: 40px;
        }

        .page-title {
            color: #2d3748;
            font-weight: 700;
            font-size: 2.5rem;
            margin: 0;
        }

        /* Profile Card */
        .user-profile {
            background: white;
            border-radius: 24px;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.08);
            padding: 40px;
            position: relative;
            overflow: hidden;
            border: 1px solid rgba(141, 11, 65, 0.1);
        }

        /* Maroon Banner */
        .user-profile::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 200px;
            background: linear-gradient(135deg, #8d0b41 0%, #6a0831 100%);
            z-index: 0;
        }

        /* Profile Content */
        .profile-content {
            position: relative;
            z-index: 1;
            display: flex;
            flex-direction: column;
            align-items: center;
            text-align: center;
            padding-top: 60px;
        }

        /* Profile Image */
        .account-img {
            margin-bottom: 20px;
            margin-top: -20px;
        }

        .account-img img {
            width: 160px !important;
            height: 160px !important;
            border-radius: 50% !important;
            border: 6px solid white !important;
            box-shadow: 0 8px 24px rgba(0, 0, 0, 0.15) !important;
            object-fit: cover;
        }

        .avatar {
            width: 160px !important;
            height: 160px !important;
            border-radius: 50% !important;
            background: linear-gradient(135deg, #8d0b41 0%, #6a0831 100%) !important;
            color: white !important;
            border: 6px solid white !important;
            box-shadow: 0 8px 24px rgba(0, 0, 0, 0.15) !important;
            display: flex !important;
            align-items: center !important;
            justify-content: center !important;
            font-size: 64px !important;
            font-weight: 700 !important;
        }

        /* Profile Name */
        .profile-name {
            font-size: 2rem;
            font-weight: 700;
            color: #2d3748;
            margin: 0 0 8px 0;
        }

        /* Profile Badge */
        .profile-badge {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            background: linear-gradient(135deg, #f7fafc 0%, #edf2f7 100%);
            padding: 8px 20px;
            border-radius: 20px;
            font-size: 0.875rem;
            color: #4a5568;
            margin-bottom: 30px;
            border: 1px solid #e2e8f0;
        }

        .profile-badge i {
            color: #8d0b41;
        }

        /* Contact Info Grid */
        .contact-info-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 16px;
            margin: 20px 0 30px 0;
            max-width: 800px;
            width: 100%;
        }

        .contact-card {
            background: #f7fafc;
            border-radius: 12px;
            padding: 16px;
            border: 1px solid #e2e8f0;
            display: flex;
            align-items: center;
            gap: 12px;
            text-align: left;
            transition: all 0.3s ease;
        }

        .contact-card:hover {
            background: #edf2f7;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
        }

        .contact-card-icon {
            width: 40px;
            height: 40px;
            border-radius: 10px;
            background: linear-gradient(135deg, #8d0b41 0%, #6a0831 100%);
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 18px;
            flex-shrink: 0;
        }

        .contact-card-details {
            flex: 1;
            min-width: 0;
        }

        .contact-card-label {
            font-size: 0.75rem;
            color: #718096;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 4px;
            font-weight: 600;
        }

        .contact-card-value {
            font-size: 0.95rem;
            color: #2d3748;
            font-weight: 600;
            word-break: break-all;
        }

        .account-action {
            display: flex;
            gap: 12px;
            flex-wrap: wrap;
            justify-content: center;
            margin-top: 10px;
            width: 100%;
            max-width: 600px;
        }

        .small-button {
            padding: 12px 24px !important;
            border-radius: 12px !important;
            border: none !important;
            font-weight: 600 !important;
            cursor: pointer;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1) !important;
            display: inline-flex !important;
            align-items: center !important;
            gap: 8px !important;
            font-size: 0.95rem !important;
            text-decoration: none !important;
            white-space: nowrap !important; /* Add this */
            min-width: fit-content !important; /* Add this */
        }

        .small-button i {
            flex-shrink: 0; /* Add this to prevent icon from shrinking */
        }

        .small-button:first-child {
            background: linear-gradient(135deg, #8d0b41 0%, #6a0831 100%) !important;
            color: white !important;
            box-shadow: 0 4px 12px rgba(141, 11, 65, 0.3) !important;
        }

        .small-button:first-child:hover {
            transform: translateY(-2px) !important;
            box-shadow: 0 6px 20px rgba(141, 11, 65, 0.4) !important;
        }

        .small-button:nth-child(2) {
            background: #fed7d7 !important;
            color: #c53030 !important;
        }

        .small-button:nth-child(2):hover {
            background: #fc8181 !important;
            color: white !important;
            transform: translateY(-2px) !important;
        }

        .small-button:nth-child(3) {
            background: white !important;
            color: #8d0b41 !important;
            border: 2px solid #8d0b41 !important;
        }

        .small-button:nth-child(3):hover {
            background: #8d0b41 !important;
            color: white !important;
        }

        /* Responsive Design */
        @media (max-width: 768px) {
            .tenant-page {
                margin-top: 120px !important;
                padding: 0 15px 40px 15px;
            }

            .user-profile {
                padding: 24px;
            }

            .page-title {
                font-size: 2rem;
            }

            .profile-name {
                font-size: 1.5rem;
            }

            .account-img img,
            .avatar {
                width: 120px !important;
                height: 120px !important;
            }

            .avatar {
                font-size: 48px !important;
            }

            .contact-info-grid {
                grid-template-columns: 1fr;
            }

            .account-action {
                flex-direction: column;
                width: 100%;
            }

            .small-button {
                width: 100% !important;
                justify-content: center !important;
            }
        }

        @media (max-width: 480px) {
            .tenant-page {
                padding: 0 10px 30px 10px;
            }

            .user-profile::before {
                height: 180px;
            }
        }
    </style>
</head>

<body>
    <!-- HEADER -->
    <?php include '../Components/tenant-header.php'; ?>

    <!-- ACCOUNT PAGE -->
    <div class="tenant-page">
        <div class="container m-auto">
            <!-- Page Header -->
            <div class="page-header">
                <h1 class="page-title">My Account</h1>
            </div>

            <!-- Profile Card -->
            <div class="row justify-content-center">
                <div class="col-lg-10">
                    <div class="user-profile">
                        <div class="profile-content">
                            <!-- Profile Image -->
                            <div class="account-img">
                                <?php if (!empty($tenant['profilePic'])): ?>
                                    <img src="<?= htmlspecialchars($profilePath); ?>"
                                        alt="<?= htmlspecialchars($fullName); ?>"
                                        onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';">
                                    <div class="avatar" style="display: none;">
                                        <?= $firstLetter ?>
                                    </div>
                                <?php else: ?>
                                    <div class="avatar">
                                        <?= $firstLetter ?>
                                    </div>
                                <?php endif; ?>
                            </div>

                            <!-- Profile Name -->
                            <h2 class="profile-name"><?= htmlspecialchars($fullName); ?></h2>

                            <!-- Profile Badge -->
                            <div class="profile-badge">
                                <i class="fas fa-user"></i>
                                <span>Tenant</span>
                            </div>

                            <!-- Contact Info Grid -->
                            <div class="contact-info-grid">
                                <div class="contact-card">
                                    <div class="contact-card-icon">
                                        <i class="fas fa-phone"></i>
                                    </div>
                                    <div class="contact-card-details">
                                        <div class="contact-card-label">Phone Number</div>
                                        <div class="contact-card-value"><?= htmlspecialchars($tenant['phoneNum'] ?: 'Not provided'); ?></div>
                                    </div>
                                </div>

                                <div class="contact-card">
                                    <div class="contact-card-icon">
                                        <i class="fas fa-envelope"></i>
                                    </div>
                                    <div class="contact-card-details">
                                        <div class="contact-card-label">Email Address</div>
                                        <div class="contact-card-value"><?= htmlspecialchars($tenant['email']); ?></div>
                                    </div>
                                </div>

                                <div class="contact-card">
                                    <div class="contact-card-icon">
                                        <i class="fas fa-calendar"></i>
                                    </div>
                                    <div class="contact-card-details">
                                        <div class="contact-card-label">Member Since</div>
                                        <div class="contact-card-value"><?= date("F j, Y", strtotime($tenant['created_at'])); ?></div>
                                    </div>
                                </div>
                            </div>

                            <!-- Action Buttons -->
                            <div class="account-action">
                                <button class="small-button" onclick="location.href='edit-account.php'">
                                    <i class="fas fa-edit"></i>
                                    Edit Profile
                                </button>
                                <button class="small-button" onclick="confirmDelete()">
                                    <i class="fas fa-trash-alt"></i>
                                    Delete Account
                                </button>
                                <button class="small-button" onclick="shareProfile()">
                                    <i class="fas fa-share-alt"></i>
                                    Share Profile
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- SCRIPTS -->
    <script src="../js/bootstrap.bundle.min.js"></script>
    <script src="../js/script.js" defer></script>
    <script src="https://unpkg.com/scrollreveal"></script>
    
    <script>
        // Share Profile Function
        function shareProfile() {
            const profileUrl = window.location.href;
            const profileText = "Check out my profile on Tahanan";
            
            if (navigator.share) {
                navigator.share({
                    title: 'My Profile',
                    text: profileText,
                    url: profileUrl
                }).catch(err => console.log('Error sharing:', err));
            } else {
                navigator.clipboard.writeText(profileUrl).then(() => {
                    alert('Profile link copied to clipboard!');
                }).catch(err => {
                    console.error('Could not copy text:', err);
                });
            }
        }

        // Confirm Delete Function
        function confirmDelete() {
            if (confirm('Are you sure you want to delete your account? This action cannot be undone.')) {
                if (confirm('This will permanently delete all your data. Are you absolutely sure?')) {
                    window.location.href = 'delete-account.php';
                }
            }
        }

        // ScrollReveal Animation
        if (typeof ScrollReveal !== 'undefined') {
            ScrollReveal().reveal('.user-profile', {
                duration: 800,
                origin: 'bottom',
                distance: '30px',
                easing: 'ease-out'
            });
        }
    </script>
</body>
</html>