<?php
require_once '../connection.php';
include '../session_auth.php';

// Get landlord ID from session
$landlord_id = $_SESSION['landlord_id'];

// Fetch landlord profile
$sql = "SELECT firstName, lastName, phoneNum, email, created_at, profilePic 
        FROM landlordtbl 
        WHERE ID = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $landlord_id);
$stmt->execute();
$result = $stmt->get_result();
$landlord = $result->fetch_assoc();

// Image path
$profilePath = $landlord['profilePic'] ?? '';
if (!empty($profilePath) && !str_starts_with($profilePath, 'http')) {
    $profilePath = "../uploads/" . $profilePath;
}

$firstLetter = strtoupper(substr($landlord['firstName'], 0, 1));
$fullName = ucwords(strtolower($landlord['firstName'] . ' ' . $landlord['lastName']));

// Fetch tenant reviews/feedback
$reviews_sql = "SELECT 
    r.id,
    r.rating,
    r.review_text,
    r.created_at,
    t.firstName as tenant_first_name,
    t.lastName as tenant_last_name,
    t.profilePic as tenant_profile_pic
FROM reviews r
INNER JOIN tenanttbl t ON r.tenant_id = t.ID
WHERE r.landlord_id = ?
ORDER BY r.created_at DESC
LIMIT 20";

$reviews_stmt = $conn->prepare($reviews_sql);
$reviews_stmt->bind_param("i", $landlord_id);
$reviews_stmt->execute();
$reviews_result = $reviews_stmt->get_result();
$reviews = $reviews_result->fetch_all(MYSQLI_ASSOC);

// Calculate average rating
$avg_rating = 0;
$total_reviews = count($reviews);
if ($total_reviews > 0) {
    $sum_ratings = array_sum(array_column($reviews, 'rating'));
    $avg_rating = round($sum_ratings / $total_reviews, 1);
}

// Count properties from listingtbl (FIXED: was looking for 'properties' table)
$property_count = 0;
$table_check = $conn->query("SHOW TABLES LIKE 'listingtbl'");
if ($table_check && $table_check->num_rows > 0) {
    $properties_sql = "SELECT COUNT(*) as property_count FROM listingtbl WHERE landlord_id = ?";
    $props_stmt = $conn->prepare($properties_sql);
    $props_stmt->bind_param("i", $landlord_id);
    $props_stmt->execute();
    $props_result = $props_stmt->get_result();
    $property_data = $props_result->fetch_assoc();
    $property_count = $property_data['property_count'] ?? 0;
}

// Count active tenants (check if table exists first)
$tenant_count = 0;
$table_check = $conn->query("SHOW TABLES LIKE 'rental_agreements'");
if ($table_check && $table_check->num_rows > 0) {
    $tenants_sql = "SELECT COUNT(DISTINCT tenant_id) as tenant_count 
                    FROM rental_agreements 
                    WHERE landlord_id = ? AND status = 'active'";
    $tenants_stmt = $conn->prepare($tenants_sql);
    $tenants_stmt->bind_param("i", $landlord_id);
    $tenants_stmt->execute();
    $tenants_result = $tenants_stmt->get_result();
    $tenant_data = $tenants_result->fetch_assoc();
    $tenant_count = $tenant_data['tenant_count'] ?? 0;
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
    <!-- MAIN CSS -->
    <link rel="stylesheet" href="../css/style.css?v=<?= time(); ?>">
    <title>Account - <?= htmlspecialchars($fullName); ?> | Tahanan</title>

    <style>
        /* ========================================
            ACCOUNT PAGE STYLES
            Modern, Professional Design
           ======================================== */

        .landlord-page {
            margin-top: 140px !important;
            padding: 0 40px 60px 40px;
            min-height: calc(100vh - 140px);
            background: linear-gradient(135deg, #f5f7fa 0%, #e8eef5 100%);
        }

        /* Page Header */
        .page-header {
            margin-bottom: 40px;
        }

        .page-header h1 {
            color: #2d3748;
            font-weight: 700;
            font-size: 2.5rem;
            margin: 0;
        }

        .breadcrumb {
            background: transparent;
            padding: 0;
            margin: 10px 0 0 0;
        }

        .breadcrumb-item {
            color: #718096;
            font-size: 14px;
        }

        .breadcrumb-item.active {
            color: #8d0b41;
        }

        /* Profile Card */
        .profile-card {
            background: white;
            border-radius: 24px;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.08);
            padding: 40px;
            margin-bottom: 30px;
            position: relative;
            overflow: hidden;
        }

        .profile-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 120px;
            background: linear-gradient(135deg, #8d0b41 0%, #6a0831 100%);
            z-index: 0;
        }

        .profile-content {
            position: relative;
            z-index: 1;
        }

        .profile-header {
            display: flex;
            align-items: flex-start;
            gap: 30px;
            margin-bottom: 30px;
        }

        /* Profile Image */
        .profile-image-wrapper {
            flex-shrink: 0;
        }

        .profile-image {
            width: 160px;
            height: 160px;
            border-radius: 50%;
            border: 6px solid white;
            box-shadow: 0 8px 24px rgba(0, 0, 0, 0.15);
            object-fit: cover;
            background: white;
        }

        .profile-avatar {
            width: 160px;
            height: 160px;
            border-radius: 50%;
            border: 6px solid white;
            box-shadow: 0 8px 24px rgba(0, 0, 0, 0.15);
            background: linear-gradient(135deg, #8d0b41 0%, #6a0831 100%);
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 64px;
            font-weight: 700;
        }

        /* Profile Info */
        .profile-info {
            flex: 1;
            margin-top: 60px;
        }

        .profile-name {
            font-size: 2rem;
            font-weight: 700;
            color: #2d3748;
            margin: 0 0 8px 0;
            background: white;
            padding: 16px 40px;
            border-radius: 16px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
            display: inline-block;
        }

        .profile-role {
            color: #718096;
            font-size: 1rem;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .profile-role i {
            color: #8d0b41;
        }

        /* Stats Cards */
        .stats-row {
            display: flex;
            gap: 20px;
            margin: 30px 0;
        }

        .stat-card {
            flex: 1;
            background: linear-gradient(135deg, #f7fafc 0%, #edf2f7 100%);
            border-radius: 16px;
            padding: 20px;
            text-align: center;
            border: 1px solid #e2e8f0;
        }

        .stat-value {
            font-size: 2rem;
            font-weight: 700;
            color: #8d0b41;
            margin: 0;
        }

        .stat-label {
            color: #718096;
            font-size: 0.875rem;
            margin-top: 4px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .stat-icon {
            font-size: 1.5rem;
            color: #8d0b41;
            margin-bottom: 8px;
        }

        /* Contact Info */
        .contact-info {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin: 30px 0;
        }

        .contact-item {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 16px;
            background: #f7fafc;
            border-radius: 12px;
            border: 1px solid #e2e8f0;
        }

        .contact-icon {
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

        .contact-details {
            flex: 1;
            min-width: 0;
        }

        .contact-label {
            font-size: 0.75rem;
            color: #718096;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 2px;
        }

        .contact-value {
            font-size: 0.95rem;
            color: #2d3748;
            font-weight: 600;
            word-break: break-all;
        }

        /* Action Buttons */
        .profile-actions {
            display: flex;
            gap: 12px;
            flex-wrap: wrap;
            margin-top: 30px;
        }

        .action-btn {
            padding: 12px 24px;
            border-radius: 12px;
            border: none;
            font-weight: 600;
            font-size: 0.95rem;
            cursor: pointer;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }

        .action-btn-primary {
            background: linear-gradient(135deg, #8d0b41 0%, #6a0831 100%);
            color: white;
            box-shadow: 0 4px 12px rgba(141, 11, 65, 0.3);
        }

        .action-btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(141, 11, 65, 0.4);
        }

        .action-btn-secondary {
            background: white;
            color: #8d0b41;
            border: 2px solid #8d0b41;
        }

        .action-btn-secondary:hover {
            background: #8d0b41;
            color: white;
        }

        .action-btn-danger {
            background: #fed7d7;
            color: #c53030;
        }

        .action-btn-danger:hover {
            background: #fc8181;
            color: white;
        }

        /* Reviews Section */
        .reviews-section {
            background: white;
            border-radius: 24px;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.08);
            padding: 40px;
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

        /* Rating Summary */
        .rating-summary {
            background: linear-gradient(135deg, #f7fafc 0%, #edf2f7 100%);
            border-radius: 20px;
            padding: 30px;
            margin-bottom: 30px;
            display: flex;
            align-items: center;
            gap: 40px;
        }

        .rating-overview {
            text-align: center;
            flex-shrink: 0;
        }

        .rating-number {
            font-size: 4rem;
            font-weight: 700;
            color: #8d0b41;
            line-height: 1;
            margin-bottom: 8px;
        }

        .rating-stars {
            font-size: 1.5rem;
            color: #f59e0b;
            margin-bottom: 8px;
        }

        .rating-count {
            color: #718096;
            font-size: 0.95rem;
        }

        .rating-bars {
            flex: 1;
        }

        .rating-bar-item {
            display: flex;
            align-items: center;
            gap: 12px;
            margin-bottom: 12px;
        }

        .rating-bar-label {
            font-size: 0.875rem;
            color: #4a5568;
            width: 60px;
            display: flex;
            align-items: center;
            gap: 4px;
        }

        .rating-bar-bg {
            flex: 1;
            height: 8px;
            background: #e2e8f0;
            border-radius: 10px;
            overflow: hidden;
        }

        .rating-bar-fill {
            height: 100%;
            background: linear-gradient(90deg, #8d0b41 0%, #6a0831 100%);
            border-radius: 10px;
            transition: width 0.5s ease;
        }

        .rating-bar-count {
            font-size: 0.875rem;
            color: #718096;
            width: 40px;
            text-align: right;
        }

        /* Review Cards */
        .reviews-list {
            display: flex;
            flex-direction: column;
            gap: 20px;
        }

        .review-card {
            background: #fafbfc;
            border-radius: 16px;
            padding: 24px;
            border: 1px solid #e2e8f0;
            transition: all 0.3s ease;
        }

        .review-card:hover {
            box-shadow: 0 8px 24px rgba(0, 0, 0, 0.08);
            transform: translateY(-2px);
        }

        .review-header {
            display: flex;
            align-items: center;
            gap: 16px;
            margin-bottom: 16px;
        }

        .reviewer-avatar {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            object-fit: cover;
            border: 2px solid white;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
        }

        .reviewer-avatar-placeholder {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            background: linear-gradient(135deg, #8d0b41 0%, #6a0831 100%);
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
            font-size: 20px;
            border: 2px solid white;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
        }

        .review-info {
            flex: 1;
        }

        .reviewer-name {
            font-weight: 600;
            color: #2d3748;
            margin: 0 0 4px 0;
            font-size: 1rem;
        }

        .review-meta {
            display: flex;
            align-items: center;
            gap: 12px;
            font-size: 0.875rem;
            color: #718096;
        }

        .review-rating {
            color: #f59e0b;
            display: flex;
            align-items: center;
            gap: 4px;
        }

        .review-date {
            display: flex;
            align-items: center;
            gap: 4px;
        }

        .review-text {
            color: #4a5568;
            line-height: 1.7;
            margin: 0;
            font-size: 0.95rem;
        }

        /* Empty State */
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

        /* Responsive Design */
        @media (max-width: 991px) {
            .profile-header {
                flex-direction: column;
                align-items: center;
                text-align: center;
            }

            .profile-info {
                margin-top: 20px;
            }

            .rating-summary {
                flex-direction: column;
                gap: 30px;
            }

            .stats-row {
                flex-wrap: wrap;
            }

            .stat-card {
                min-width: 150px;
            }
        }

        @media (max-width: 768px) {
            .landlord-page {
                padding: 0 20px 40px 20px;
                margin-top: 120px !important;
            }

            .profile-card,
            .reviews-section {
                padding: 24px;
            }

            .page-header h1 {
                font-size: 2rem;
            }

            .profile-image,
            .profile-avatar {
                width: 120px;
                height: 120px;
            }

            .profile-avatar {
                font-size: 48px;
            }

            .profile-name {
                font-size: 1.5rem;
            }

            .rating-number {
                font-size: 3rem;
            }

            .section-title {
                font-size: 1.5rem;
            }

            .contact-info {
                grid-template-columns: 1fr;
            }

            .profile-actions {
                flex-direction: column;
            }

            .action-btn {
                width: 100%;
                justify-content: center;
            }
        }

        @media (max-width: 480px) {
            .stats-row {
                flex-direction: column;
            }

            .stat-card {
                width: 100%;
            }
        }
    </style>
</head>

<body>
    <!-- HEADER -->
    <?php include '../Components/landlord-header.php'; ?>

    <!-- ACCOUNT PAGE -->
    <div class="landlord-page">
        <div class="container">
            <!-- Page Header -->
            <div class="page-header">
                <h1>Account Profile</h1>
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item"><a href="../LANDLORD/landlord-home.php">Home</a></li>
                        <li class="breadcrumb-item active" aria-current="page">Account</li>
                    </ol>
                </nav>
            </div>

            <!-- Profile Card -->
            <div class="profile-card">
                <div class="profile-content">
                    <div class="profile-header">
                        <!-- Profile Image -->
                        <div class="profile-image-wrapper">
                            <?php if (!empty($landlord['profilePic'])): ?>
                                <img src="<?= htmlspecialchars($profilePath); ?>"
                                    alt="<?= htmlspecialchars($fullName); ?>"
                                    class="profile-image"
                                    onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';">
                                <div class="profile-avatar" style="display: none;">
                                    <?= $firstLetter ?>
                                </div>
                            <?php else: ?>
                                <div class="profile-avatar">
                                    <?= $firstLetter ?>
                                </div>
                            <?php endif; ?>
                        </div>

                        <!-- Profile Info -->
                        <div class="profile-info">
                            <h2 class="profile-name"><?= htmlspecialchars($fullName); ?></h2>
                            <div class="profile-role">
                                <i class="fas fa-user-tie"></i>
                                <span>Property Landlord</span>
                            </div>

                            <!-- Stats -->
                            <div class="stats-row">
                                <div class="stat-card">
                                    <div class="stat-icon">
                                        <i class="fas fa-building"></i>
                                    </div>
                                    <div class="stat-value"><?= $property_count ?></div>
                                    <div class="stat-label">Properties</div>
                                </div>
                                <div class="stat-card">
                                    <div class="stat-icon">
                                        <i class="fas fa-users"></i>
                                    </div>
                                    <div class="stat-value"><?= $tenant_count ?></div>
                                    <div class="stat-label">Active Tenants</div>
                                </div>
                                <div class="stat-card">
                                    <div class="stat-icon">
                                        <i class="fas fa-star"></i>
                                    </div>
                                    <div class="stat-value"><?= number_format($avg_rating, 1) ?></div>
                                    <div class="stat-label">Avg Rating</div>
                                </div>
                                <div class="stat-card">
                                    <div class="stat-icon">
                                        <i class="fas fa-comment"></i>
                                    </div>
                                    <div class="stat-value"><?= $total_reviews ?></div>
                                    <div class="stat-label">Reviews</div>
                                </div>
                            </div>

                            <!-- Contact Info -->
                            <div class="contact-info">
                                <div class="contact-item">
                                    <div class="contact-icon">
                                        <i class="fas fa-phone"></i>
                                    </div>
                                    <div class="contact-details">
                                        <div class="contact-label">Phone Number</div>
                                        <div class="contact-value"><?= htmlspecialchars($landlord['phoneNum'] ?: 'Not provided'); ?></div>
                                    </div>
                                </div>
                                <div class="contact-item">
                                    <div class="contact-icon">
                                        <i class="fas fa-envelope"></i>
                                    </div>
                                    <div class="contact-details">
                                        <div class="contact-label">Email Address</div>
                                        <div class="contact-value"><?= htmlspecialchars($landlord['email']); ?></div>
                                    </div>
                                </div>
                                <div class="contact-item">
                                    <div class="contact-icon">
                                        <i class="fas fa-calendar"></i>
                                    </div>
                                    <div class="contact-details">
                                        <div class="contact-label">Member Since</div>
                                        <div class="contact-value"><?= date("F j, Y", strtotime($landlord['created_at'])); ?></div>
                                    </div>
                                </div>
                            </div>

                            <!-- Action Buttons -->
                            <div class="profile-actions">
                                <button class="action-btn action-btn-primary" onclick="location.href='edit-account.php'">
                                    <i class="fas fa-edit"></i>
                                    Edit Profile
                                </button>
                                <button class="action-btn action-btn-secondary" onclick="shareProfile()">
                                    <i class="fas fa-share-alt"></i>
                                    Share Profile
                                </button>
                                <button class="action-btn action-btn-secondary" onclick="location.href='history.php'">
                                    <i class="fas fa-history"></i>
                                    View History
                                </button>
                                <button class="action-btn action-btn-danger" onclick="confirmDelete()">
                                    <i class="fas fa-trash-alt"></i>
                                    Delete Account
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Reviews Section -->
            <div class="reviews-section">
                <div class="section-header">
                    <h3 class="section-title">
                        <i class="fas fa-star"></i>
                        Tenant Reviews & Feedback
                    </h3>
                    <span class="badge bg-primary"><?= $total_reviews ?> Review<?= $total_reviews !== 1 ? 's' : '' ?></span>
                </div>

                <?php if ($total_reviews > 0): ?>
                    <!-- Rating Summary -->
                    <div class="rating-summary">
                        <div class="rating-overview">
                            <div class="rating-number"><?= number_format($avg_rating, 1) ?></div>
                            <div class="rating-stars">
                                <?php
                                for ($i = 1; $i <= 5; $i++) {
                                    if ($i <= floor($avg_rating)) {
                                        echo '<i class="fas fa-star"></i>';
                                    } elseif ($i <= ceil($avg_rating)) {
                                        echo '<i class="fas fa-star-half-alt"></i>';
                                    } else {
                                        echo '<i class="far fa-star"></i>';
                                    }
                                }
                                ?>
                            </div>
                            <div class="rating-count"><?= $total_reviews ?> review<?= $total_reviews !== 1 ? 's' : '' ?></div>
                        </div>

                        <div class="rating-bars">
                            <?php
                            // Calculate rating distribution
                            $rating_counts = array_fill(1, 5, 0);
                            foreach ($reviews as $review) {
                                $rating_counts[$review['rating']]++;
                            }

                            for ($star = 5; $star >= 1; $star--):
                                $count = $rating_counts[$star];
                                $percentage = $total_reviews > 0 ? ($count / $total_reviews) * 100 : 0;
                            ?>
                                <div class="rating-bar-item">
                                    <div class="rating-bar-label">
                                        <?= $star ?> <i class="fas fa-star" style="font-size: 12px;"></i>
                                    </div>
                                    <div class="rating-bar-bg">
                                        <div class="rating-bar-fill" style="width: <?= $percentage ?>%"></div>
                                    </div>
                                    <div class="rating-bar-count"><?= $count ?></div>
                                </div>
                            <?php endfor; ?>
                        </div>
                    </div>

                    <!-- Reviews List -->
                    <div class="reviews-list">
                        <?php foreach ($reviews as $review):
                            $tenant_name = ucwords(strtolower($review['tenant_first_name'] . ' ' . $review['tenant_last_name']));
                            $tenant_initial = strtoupper(substr($review['tenant_first_name'], 0, 1));
                            $review_date = date("F j, Y", strtotime($review['created_at']));
                            $time_ago = getTimeAgo($review['created_at']);
                        ?>
                            <div class="review-card">
                                <div class="review-header">
                                    <!-- Tenant Avatar -->
                                    <?php if (!empty($review['tenant_profile_pic'])): ?>
                                        <img src="../uploads/profiles/<?= htmlspecialchars($review['tenant_profile_pic']); ?>"
                                            alt="<?= htmlspecialchars($tenant_name); ?>"
                                            class="reviewer-avatar"
                                            onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';">
                                        <div class="reviewer-avatar-placeholder" style="display: none;">
                                            <?= $tenant_initial ?>
                                        </div>
                                    <?php else: ?>
                                        <div class="reviewer-avatar-placeholder">
                                            <?= $tenant_initial ?>
                                        </div>
                                    <?php endif; ?>

                                    <div class="review-info">
                                        <h4 class="reviewer-name"><?= htmlspecialchars($tenant_name); ?></h4>
                                        <div class="review-meta">
                                            <div class="review-rating">
                                                <?php for ($i = 1; $i <= 5; $i++): ?>
                                                    <i class="<?= $i <= $review['rating'] ? 'fas' : 'far' ?> fa-star"></i>
                                                <?php endfor; ?>
                                            </div>
                                            <span>•</span>
                                            <div class="review-date">
                                                <i class="far fa-clock"></i>
                                                <?= $time_ago ?>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <p class="review-text"><?= nl2br(htmlspecialchars($review['review_text'])); ?></p>
                            </div>
                        <?php endforeach; ?>
                    </div>

                <?php else: ?>
                    <!-- Empty State -->
                    <div class="empty-reviews">
                        <i class="far fa-comments"></i>
                        <h3>No Reviews Yet</h3>
                        <p>You haven't received any tenant reviews yet. Keep providing excellent service!</p>
                    </div>
                <?php endif; ?>
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
                const profileText = "Check out <?= htmlspecialchars($fullName); ?>'s profile on Tahanan";

                if (navigator.share) {
                    navigator.share({
                        title: 'Landlord Profile',
                        text: profileText,
                        url: profileUrl
                    }).catch(err => console.log('Error sharing:', err));
                } else {
                    // Fallback: Copy to clipboard
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

            // Animate rating bars on page load
            document.addEventListener('DOMContentLoaded', () => {
                const ratingBars = document.querySelectorAll('.rating-bar-fill');
                ratingBars.forEach(bar => {
                    const width = bar.style.width;
                    bar.style.width = '0';
                    setTimeout(() => {
                        bar.style.width = width;
                    }, 100);
                });
            });

            // ScrollReveal Animations
            if (typeof ScrollReveal !== 'undefined') {
                ScrollReveal().reveal('.profile-card', {
                    duration: 800,
                    origin: 'bottom',
                    distance: '30px',
                    easing: 'ease-out'
                });

                ScrollReveal().reveal('.reviews-section', {
                    duration: 800,
                    origin: 'bottom',
                    distance: '30px',
                    delay: 200,
                    easing: 'ease-out'
                });

                ScrollReveal().reveal('.review-card', {
                    duration: 600,
                    origin: 'bottom',
                    distance: '20px',
                    interval: 100,
                    easing: 'ease-out'
                });
            }
        </script>
</body>

</html>

<?php
// Helper function to get time ago
function getTimeAgo($datetime)
{
    $time = strtotime($datetime);
    $diff = time() - $time;

    if ($diff < 60) {
        return 'Just now';
    } elseif ($diff < 3600) {
        $mins = floor($diff / 60);
        return $mins . ' minute' . ($mins > 1 ? 's' : '') . ' ago';
    } elseif ($diff < 86400) {
        $hours = floor($diff / 3600);
        return $hours . ' hour' . ($hours > 1 ? 's' : '') . ' ago';
    } elseif ($diff < 604800) {
        $days = floor($diff / 86400);
        return $days . ' day' . ($days > 1 ? 's' : '') . ' ago';
    } elseif ($diff < 2592000) {
        $weeks = floor($diff / 604800);
        return $weeks . ' week' . ($weeks > 1 ? 's' : '') . ' ago';
    } else {
        return date("F j, Y", $time);
    }
}
?>