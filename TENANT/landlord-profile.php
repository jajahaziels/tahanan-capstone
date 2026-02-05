<?php
require_once '../connection.php';
include '../session_auth.php';

// Get tenant ID from session
$tenant_id = $_SESSION['tenant_id'];

// Get landlord ID from URL
$landlordId = $_GET['id'] ?? null;

if (!$landlordId || !is_numeric($landlordId)) {
    die("Invalid landlord ID.");
}

$landlordId = intval($landlordId);

// Fetch landlord info
$sql = "SELECT ID, firstName, lastName, email, phoneNum, profilePic, created_at 
        FROM landlordtbl 
        WHERE ID = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $landlordId);
$stmt->execute();
$landlordResult = $stmt->get_result();

if ($landlordResult->num_rows === 0) {
    die("Landlord not found.");
}
$landlord = $landlordResult->fetch_assoc();
$stmt->close();

// Fetch landlord's available listings only
$sql = "
    SELECT 
        l.ID, 
        l.listingName, 
        l.price, 
        l.address, 
        l.barangay, 
        l.category, 
        l.images, 
        l.rooms, 
        lt.firstName, 
        lt.lastName, 
        lt.profilePic
    FROM listingtbl AS l
    JOIN landlordtbl AS lt ON l.landlord_id = lt.ID
    LEFT JOIN renttbl AS r 
        ON l.ID = r.listing_id AND r.status = 'approved'
    WHERE lt.ID = ? 
      AND l.availability = 'available'
      AND r.ID IS NULL
";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $landlordId);
$stmt->execute();
$listingsResult = $stmt->get_result();
$listings = $listingsResult->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Fetch reviews for this landlord
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
$reviews_stmt->bind_param("i", $landlordId);
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

// Check if current tenant has already reviewed this landlord
$has_reviewed = false;
$user_review = null;
$check_review_sql = "SELECT * FROM reviews WHERE landlord_id = ? AND tenant_id = ?";
$check_stmt = $conn->prepare($check_review_sql);
$check_stmt->bind_param("ii", $landlordId, $tenant_id);
$check_stmt->execute();
$check_result = $check_stmt->get_result();
if ($check_result->num_rows > 0) {
    $has_reviewed = true;
    $user_review = $check_result->fetch_assoc();
}

$firstLetter = strtoupper(substr($landlord['firstName'], 0, 1));
$profilePath = !empty($landlord['profilePic']) ? "../uploads/" . $landlord['profilePic'] : "";
$fullName = ucwords(strtolower($landlord['firstName'] . ' ' . $landlord['lastName']));
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
    <title><?= htmlspecialchars($fullName); ?> - Landlord Profile | Tahanan</title>
    
    <style>
        /* Modern Landlord Profile Page */
        .landlord-page {
            margin-top: 140px !important;
            padding: 0 20px 60px 20px;
            background: linear-gradient(135deg, #f5f7fa 0%, #e8eef5 100%);
            min-height: calc(100vh - 140px);
        }

        /* Profile Header Section */
        .profile-header-card {
            background: white;
            border-radius: 24px;
            padding: 40px;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.08);
            margin-bottom: 30px;
            position: relative;
            overflow: hidden;
        }

        .profile-header-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 120px;
            background: linear-gradient(135deg, #8d0b41 0%, #6a0831 100%);
        }

        .profile-header-content {
            position: relative;
            display: flex;
            gap: 30px;
            align-items: flex-start;
        }

        .profile-image-section {
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

        .profile-info-section {
            flex: 1;
            margin-top: 60px;
        }

        .profile-name {
            font-size: 2rem;
            font-weight: 700;
            color: #2d3748;
            margin: 0 0 8px 0;
        }

        .profile-badge {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            background: linear-gradient(135deg, #f7fafc 0%, #edf2f7 100%);
            padding: 6px 16px;
            border-radius: 20px;
            font-size: 0.875rem;
            color: #4a5568;
            margin-bottom: 20px;
        }

        .profile-badge i {
            color: #8d0b41;
        }

        /* Rating Display */
        .rating-display {
            display: flex;
            align-items: center;
            gap: 12px;
            margin-bottom: 20px;
        }

        .rating-number {
            font-size: 2rem;
            font-weight: 700;
            color: #8d0b41;
        }

        .rating-stars {
            color: #f59e0b;
            font-size: 1.25rem;
        }

        .rating-count {
            color: #718096;
            font-size: 0.95rem;
        }

        /* Contact Info Grid */
        .contact-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 16px;
            margin: 20px 0;
        }

        .contact-item {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 12px;
            background: #f7fafc;
            border-radius: 12px;
            font-size: 0.9rem;
        }

        .contact-icon {
            width: 36px;
            height: 36px;
            border-radius: 8px;
            background: linear-gradient(135deg, #8d0b41 0%, #6a0831 100%);
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .contact-details strong {
            display: block;
            font-size: 0.75rem;
            color: #718096;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        /* Action Buttons */
        .action-buttons {
            display: flex;
            gap: 12px;
            flex-wrap: wrap;
            margin-top: 24px;
        }

        .action-btn {
            padding: 12px 24px;
            border-radius: 12px;
            border: none;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            font-size: 0.95rem;
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

        .action-btn-outline {
            background: transparent;
            color: #4a5568;
            border: 2px solid #e2e8f0;
        }

        .action-btn-outline:hover {
            background: #f7fafc;
            border-color: #cbd5e0;
        }

        /* Reviews Section */
        .reviews-section {
            background: white;
            border-radius: 24px;
            padding: 40px;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.08);
            margin-bottom: 30px;
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

        /* Write Review Button */
        .write-review-btn {
            background: linear-gradient(135deg, #8d0b41 0%, #6a0831 100%);
            color: white;
            padding: 12px 24px;
            border-radius: 12px;
            border: none;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }

        .write-review-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(141, 11, 65, 0.4);
        }

        /* Review Form Modal Styles */
        .review-modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.7);
            z-index: 9999;
            justify-content: center;
            align-items: center;
            animation: fadeIn 0.3s ease;
        }

        .review-modal.show {
            display: flex;
        }

        .review-modal-content {
            background: white;
            border-radius: 24px;
            padding: 40px;
            max-width: 600px;
            width: 90%;
            max-height: 90vh;
            overflow-y: auto;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
            animation: slideUp 0.3s ease;
        }

        @keyframes slideUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 24px;
        }

        .modal-title {
            font-size: 1.5rem;
            font-weight: 700;
            color: #2d3748;
        }

        .modal-close {
            background: none;
            border: none;
            font-size: 1.5rem;
            color: #718096;
            cursor: pointer;
            width: 36px;
            height: 36px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.3s ease;
        }

        .modal-close:hover {
            background: #f7fafc;
            color: #2d3748;
        }

        /* Star Rating Input */
        .star-rating {
            display: flex;
            gap: 8px;
            margin: 20px 0;
            justify-content: center;
        }

        .star-rating input[type="radio"] {
            display: none;
        }

        .star-rating label {
            font-size: 2.5rem;
            color: #e2e8f0;
            cursor: pointer;
            transition: all 0.2s ease;
        }

        .star-rating label:hover,
        .star-rating label:hover ~ label {
            color: #f59e0b;
            transform: scale(1.1);
        }

        .star-rating input[type="radio"]:checked ~ label {
            color: #f59e0b;
        }

        /* Reverse the order for CSS selector to work */
        .star-rating {
            flex-direction: row-reverse;
            justify-content: center;
        }

        .rating-label {
            text-align: center;
            color: #718096;
            font-size: 0.95rem;
            margin-top: 8px;
        }

        /* Form Groups */
        .form-group {
            margin-bottom: 24px;
        }

        .form-label {
            display: block;
            font-weight: 600;
            color: #2d3748;
            margin-bottom: 8px;
            font-size: 0.95rem;
        }

        .form-control {
            width: 100%;
            padding: 12px 16px;
            border: 2px solid #e2e8f0;
            border-radius: 12px;
            font-size: 0.95rem;
            transition: all 0.3s ease;
            font-family: inherit;
        }

        .form-control:focus {
            outline: none;
            border-color: #8d0b41;
            box-shadow: 0 0 0 4px rgba(141, 11, 65, 0.1);
        }

        textarea.form-control {
            resize: vertical;
            min-height: 120px;
        }

        /* Submit Button */
        .submit-review-btn {
            width: 100%;
            padding: 14px;
            background: linear-gradient(135deg, #8d0b41 0%, #6a0831 100%);
            color: white;
            border: none;
            border-radius: 12px;
            font-weight: 600;
            font-size: 1rem;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .submit-review-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(141, 11, 65, 0.4);
        }

        .submit-review-btn:disabled {
            background: #cbd5e0;
            cursor: not-allowed;
            transform: none;
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
            gap: 2px;
        }

        .review-text {
            color: #4a5568;
            line-height: 1.7;
            margin: 0;
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

        /* Listings Section */
        .listings-section {
            margin-top: 40px;
        }

        .listings-header {
            margin-bottom: 30px;
        }

        .listings-title {
            font-size: 1.75rem;
            font-weight: 700;
            color: #2d3748;
        }

        /* Alert Messages */
        .alert {
            padding: 16px 20px;
            border-radius: 12px;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .alert-success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }

        .alert-info {
            background: #d1ecf1;
            color: #0c5460;
            border: 1px solid #bee5eb;
        }

        /* Responsive */
        @media (max-width: 768px) {
            .landlord-page {
                margin-top: 120px !important;
                padding: 0 15px 40px 15px;
            }

            .profile-header-card {
                padding: 24px;
            }

            .profile-header-content {
                flex-direction: column;
                align-items: center;
                text-align: center;
            }

            .profile-info-section {
                margin-top: 20px;
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

            .action-buttons {
                flex-direction: column;
            }

            .action-btn {
                width: 100%;
                justify-content: center;
            }

            .reviews-section {
                padding: 24px;
            }

            .section-header {
                flex-direction: column;
                gap: 16px;
                align-items: flex-start;
            }

            .write-review-btn {
                width: 100%;
                justify-content: center;
            }

            .review-modal-content {
                padding: 24px;
            }
        }
    </style>
</head>

<body>
    <!-- HEADER -->
    <?php include '../Components/tenant-header.php'; ?>

    <!-- LANDLORD PROFILE PAGE -->
    <div class="landlord-page">
        <div class="container">
            <!-- Profile Header -->
            <div class="profile-header-card">
                <div class="profile-header-content">
                    <!-- Profile Image -->
                    <div class="profile-image-section">
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
                    <div class="profile-info-section">
                        <h1 class="profile-name"><?= htmlspecialchars($fullName); ?></h1>
                        
                        <div class="profile-badge">
                            <i class="fas fa-user-tie"></i>
                            <span>Property Landlord</span>
                        </div>

                        <!-- Rating Display -->
                        <?php if ($total_reviews > 0): ?>
                        <div class="rating-display">
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
                            <div class="rating-count">(<?= $total_reviews ?> review<?= $total_reviews !== 1 ? 's' : '' ?>)</div>
                        </div>
                        <?php endif; ?>

                        <!-- Contact Info -->
                        <div class="contact-grid">
                            <div class="contact-item">
                                <div class="contact-icon">
                                    <i class="fas fa-phone"></i>
                                </div>
                                <div class="contact-details">
                                    <strong>Phone</strong>
                                    <?= htmlspecialchars($landlord['phoneNum'] ?: 'Not provided'); ?>
                                </div>
                            </div>
                            <div class="contact-item">
                                <div class="contact-icon">
                                    <i class="fas fa-envelope"></i>
                                </div>
                                <div class="contact-details">
                                    <strong>Email</strong>
                                    <?= htmlspecialchars($landlord['email']); ?>
                                </div>
                            </div>
                            <div class="contact-item">
                                <div class="contact-icon">
                                    <i class="fas fa-calendar"></i>
                                </div>
                                <div class="contact-details">
                                    <strong>Joined</strong>
                                    <?= date("M Y", strtotime($landlord['created_at'])); ?>
                                </div>
                            </div>
                        </div>

                        <!-- Action Buttons -->
                        <div class="action-buttons">
                            <button class="action-btn action-btn-primary" onclick="window.location.href='tenant-messages.php?landlord_id=<?= htmlspecialchars($landlord['ID']); ?>'">
                                <i class="fas fa-comment-dots"></i>
                                Chat Now
                            </button>
                            <button class="action-btn action-btn-secondary" onclick="shareProfile()">
                                <i class="fas fa-share-alt"></i>
                                Share Profile
                            </button>
                            <button class="action-btn action-btn-outline" onclick="alert('Report function coming soon.')">
                                <i class="fas fa-flag"></i>
                                Report
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Reviews Section -->
            <div class="reviews-section">
                <div class="section-header">
                    <h3 class="section-title">
                        <i class="fas fa-star"></i>
                        Reviews & Ratings
                    </h3>
                    
                    <?php if (!$has_reviewed): ?>
                        <button class="write-review-btn" onclick="openReviewModal()">
                            <i class="fas fa-pen"></i>
                            Write a Review
                        </button>
                    <?php else: ?>
                        <button class="write-review-btn" onclick="openReviewModal()">
                            <i class="fas fa-edit"></i>
                            Edit Your Review
                        </button>
                    <?php endif; ?>
                </div>

                <?php if ($has_reviewed): ?>
                <div class="alert alert-info">
                    <i class="fas fa-info-circle"></i>
                    <span>You have already reviewed this landlord. You can edit your review anytime.</span>
                </div>
                <?php endif; ?>

                <!-- Reviews List -->
                <?php if ($total_reviews > 0): ?>
                    <div class="reviews-list">
                        <?php foreach ($reviews as $review): 
                            $tenant_name = ucwords(strtolower($review['tenant_first_name'] . ' ' . $review['tenant_last_name']));
                            $tenant_initial = strtoupper(substr($review['tenant_first_name'], 0, 1));
                            $time_ago = getTimeAgo($review['created_at']);
                            $is_own_review = ($review['tenant_id'] ?? 0) == $tenant_id;
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
                                    <h4 class="reviewer-name">
                                        <?= htmlspecialchars($tenant_name); ?>
                                        <?php if ($is_own_review): ?>
                                            <span style="font-size: 0.875rem; color: #8d0b41; font-weight: 600;"> (You)</span>
                                        <?php endif; ?>
                                    </h4>
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
                    <div class="empty-reviews">
                        <i class="far fa-comments"></i>
                        <h3>No Reviews Yet</h3>
                        <p>Be the first to review this landlord!</p>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Property Listings -->
            <div class="listings-section">
                <div class="listings-header">
                    <h2 class="listings-title">Available Properties</h2>
                </div>

                <div class="row justify-content-center">
                    <?php if (!empty($listings)): ?>
                        <?php foreach ($listings as $row): ?>
                            <div class="col-lg-4 col-md-6 col-sm-12 mb-4">
                                <div class="cards" onclick="window.location='property-details.php?ID=<?= $row['ID']; ?>'">
                                    <div class="position-relative">
                                        <?php
                                        $images = json_decode($row['images'], true);
                                        $imagePath = '../img/house1.jpeg';
                                        if (!empty($images) && is_array($images) && isset($images[0])) {
                                            $imagePath = '../LANDLORD/uploads/' . $images[0];
                                        }
                                        ?>
                                        <img src="<?= htmlspecialchars($imagePath); ?>"
                                            alt="Property Image"
                                            class="property-img"
                                            style="width:100%; max-height:200px; object-fit:cover;"
                                            onerror="this.src='../img/house1.jpeg'">

                                        <div class="labels">
                                            <div class="label"><i class="fa-regular fa-star"></i> Featured</div>
                                        </div>

                                        <div class="price-tag">₱ <?= number_format($row['price']); ?></div>
                                    </div>

                                    <div class="cards-content">
                                        <h5 class="mb-2 house-name"><?= htmlspecialchars($row['listingName']); ?></h5>

                                        <div class="mb-2 location">
                                            <i class="fas fa-map-marker-alt"></i>
                                            <?= htmlspecialchars($row['address']); ?>
                                        </div>

                                        <div class="features">
                                            <div class="m-2">
                                                <i class="fas fa-bed"></i> <?= htmlspecialchars($row['rooms']); ?> Bedroom
                                            </div>
                                            <div class="m-2">
                                                <i class="fa-solid fa-building"></i> <?= htmlspecialchars($row['category']); ?>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="col-12">
                            <div class="empty-reviews">
                                <i class="fas fa-home"></i>
                                <h3>No Available Properties</h3>
                                <p>This landlord doesn't have any available properties at the moment.</p>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Review Modal -->
    <div class="review-modal" id="reviewModal">
        <div class="review-modal-content">
            <div class="modal-header">
                <h3 class="modal-title"><?= $has_reviewed ? 'Edit Your Review' : 'Write a Review' ?></h3>
                <button class="modal-close" onclick="closeReviewModal()">
                    <i class="fas fa-times"></i>
                </button>
            </div>

            <form id="reviewForm" method="POST" action="../api/submit_review.php">
                <input type="hidden" name="landlord_id" value="<?= $landlordId ?>">
                <input type="hidden" name="tenant_id" value="<?= $tenant_id ?>">
                <?php if ($has_reviewed): ?>
                    <input type="hidden" name="review_id" value="<?= $user_review['id'] ?>">
                <?php endif; ?>

                <!-- Star Rating -->
                <div class="form-group">
                    <label class="form-label">Rating</label>
                    <div class="star-rating">
                        <input type="radio" id="star5" name="rating" value="5" <?= ($has_reviewed && $user_review['rating'] == 5) ? 'checked' : '' ?> required>
                        <label for="star5"><i class="fas fa-star"></i></label>
                        
                        <input type="radio" id="star4" name="rating" value="4" <?= ($has_reviewed && $user_review['rating'] == 4) ? 'checked' : '' ?>>
                        <label for="star4"><i class="fas fa-star"></i></label>
                        
                        <input type="radio" id="star3" name="rating" value="3" <?= ($has_reviewed && $user_review['rating'] == 3) ? 'checked' : '' ?>>
                        <label for="star3"><i class="fas fa-star"></i></label>
                        
                        <input type="radio" id="star2" name="rating" value="2" <?= ($has_reviewed && $user_review['rating'] == 2) ? 'checked' : '' ?>>
                        <label for="star2"><i class="fas fa-star"></i></label>
                        
                        <input type="radio" id="star1" name="rating" value="1" <?= ($has_reviewed && $user_review['rating'] == 1) ? 'checked' : '' ?>>
                        <label for="star1"><i class="fas fa-star"></i></label>
                    </div>
                    <div class="rating-label" id="ratingLabel">Select a rating</div>
                </div>

                <!-- Review Text -->
                <div class="form-group">
                    <label class="form-label" for="reviewText">Your Review</label>
                    <textarea 
                        class="form-control" 
                        id="reviewText" 
                        name="review_text" 
                        placeholder="Share your experience with this landlord..."
                        required
                        minlength="20"
                        maxlength="500"><?= $has_reviewed ? htmlspecialchars($user_review['review_text']) : '' ?></textarea>
                    <small style="color: #718096; font-size: 0.875rem;">Minimum 20 characters</small>
                </div>

                <!-- Submit Button -->
                <button type="submit" class="submit-review-btn">
                    <i class="fas fa-paper-plane"></i>
                    <?= $has_reviewed ? 'Update Review' : 'Submit Review' ?>
                </button>
            </form>
        </div>
    </div>

    <!-- SCRIPTS -->
    <script src="../js/bootstrap.bundle.min.js"></script>
    <script src="../js/script.js" defer></script>
    <script src="https://unpkg.com/scrollreveal"></script>
    
    <script>
        // Review Modal Functions
        function openReviewModal() {
            document.getElementById('reviewModal').classList.add('show');
            document.body.style.overflow = 'hidden';
        }

        function closeReviewModal() {
            document.getElementById('reviewModal').classList.remove('show');
            document.body.style.overflow = 'auto';
        }

        // Close modal on outside click
        document.getElementById('reviewModal').addEventListener('click', function(e) {
            if (e.target === this) {
                closeReviewModal();
            }
        });

        // Star Rating Label Update
        const ratingInputs = document.querySelectorAll('.star-rating input[type="radio"]');
        const ratingLabel = document.getElementById('ratingLabel');
        const ratingTexts = {
            '1': 'Poor',
            '2': 'Fair',
            '3': 'Good',
            '4': 'Very Good',
            '5': 'Excellent'
        };

        ratingInputs.forEach(input => {
            input.addEventListener('change', function() {
                ratingLabel.textContent = ratingTexts[this.value];
            });
        });

        // Set initial label if review exists
        <?php if ($has_reviewed): ?>
        ratingLabel.textContent = ratingTexts['<?= $user_review['rating'] ?>'];
        <?php endif; ?>

        // Form Submission
        document.getElementById('reviewForm').addEventListener('submit', async function(e) {
            e.preventDefault();
            
            const submitBtn = this.querySelector('.submit-review-btn');
            const originalText = submitBtn.innerHTML;
            submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Submitting...';
            submitBtn.disabled = true;

            const formData = new FormData(this);

            try {
                const response = await fetch(this.action, {
                    method: 'POST',
                    body: formData
                });

                const data = await response.json();

                if (data.success) {
                    alert('<?= $has_reviewed ? 'Review updated successfully!' : 'Review submitted successfully!' ?>');
                    window.location.reload();
                } else {
                    alert('Error: ' + (data.message || 'Failed to submit review'));
                    submitBtn.innerHTML = originalText;
                    submitBtn.disabled = false;
                }
            } catch (error) {
                console.error('Error:', error);
                alert('An error occurred. Please try again.');
                submitBtn.innerHTML = originalText;
                submitBtn.disabled = false;
            }
        });

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
                navigator.clipboard.writeText(profileUrl).then(() => {
                    alert('Profile link copied to clipboard!');
                }).catch(err => {
                    console.error('Could not copy text:', err);
                });
            }
        }

        // ScrollReveal Animations
        if (typeof ScrollReveal !== 'undefined') {
            ScrollReveal().reveal('.profile-header-card', {
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
function getTimeAgo($datetime) {
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