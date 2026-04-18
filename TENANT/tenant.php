<?php
require_once '../connection.php';
include '../session_auth.php';

$tenant_id = $_SESSION['tenant_id'];

$tenant_sql = "SELECT firstName, lastName, username FROM tenanttbl WHERE ID = ?";
$tenant_stmt = $conn->prepare($tenant_sql);
$tenant_stmt->bind_param("i", $tenant_id);
$tenant_stmt->execute();
$tenant_result = $tenant_stmt->get_result();
$tenant_data = $tenant_result->fetch_assoc();

$tenant_name = isset($tenant_data['firstName']) && isset($tenant_data['lastName'])
    ? ucwords(strtolower($tenant_data['firstName'] . ' ' . $tenant_data['lastName']))
    : (isset($_SESSION['username']) ? ucwords(strtolower($_SESSION['username'])) : 'Tenant');

$search    = isset($_GET['search'])    ? trim($_GET['search'])       : '';
$barangay  = isset($_GET['barangay'])  ? $_GET['barangay']           : '';
$category  = isset($_GET['category'])  ? $_GET['category']           : '';
$rooms     = isset($_GET['rooms'])     ? $_GET['rooms']              : '';
$min_price = isset($_GET['min_price']) ? intval($_GET['min_price'])  : '';
$max_price = isset($_GET['max_price']) ? intval($_GET['max_price'])  : '';

$sql = "
    SELECT l.*, lt.firstName, lt.lastName, lt.profilePic
    FROM listingtbl AS l
    JOIN landlordtbl AS lt ON l.landlord_id = lt.ID
    LEFT JOIN renttbl AS r ON l.ID = r.listing_id AND r.status = 'approved'
    WHERE r.ID IS NULL
    AND l.availability = 'available'
    AND l.verification_status = 'approved'
";

if (!empty($search))    $sql .= " AND (l.listingName LIKE '%$search%' OR l.listingDesc LIKE '%$search%' OR l.address LIKE '%$search%')";
if (!empty($barangay))  $sql .= " AND l.barangay = '$barangay'";
if (!empty($category))  $sql .= " AND l.category = '$category'";
if (!empty($rooms))     $sql .= " AND l.rooms = $rooms";
if (!empty($min_price)) $sql .= " AND l.price >= $min_price";
if (!empty($max_price)) $sql .= " AND l.price <= $max_price";

$sql .= " ORDER BY l.listingDate DESC";
$result = $conn->query($sql);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="shortcut icon" href="favicon.ico" type="image/x-icon">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/7.0.1/css/all.min.css" crossorigin="anonymous" referrerpolicy="no-referrer" />
    <link rel="stylesheet" href="../css/bootstrap.min.css">
    <link rel="stylesheet" href="../css/style.css?v=<?= time(); ?>">
    <title><?= htmlspecialchars($tenant_name) ?> - Tahanan</title>
    <style>

        body { background-color: var(--bg-alt-color); }

        /* ── Page Hero ── */
        .page-hero {
            margin-top: 100px;
            padding: 40px 0 24px;
            background: var(--bg-color);
        }

        .page-hero .inner {
            max-width: 1140px;
            margin: 0 auto;
            padding: 0 24px;
            display: flex;
            align-items: flex-end;
            justify-content: space-between;
            gap: 16px;
            flex-wrap: wrap;
        }

        .page-hero h1 {
            font-size: clamp(1.5rem, 3vw, 2.2rem);
            font-weight: 700;
            color: var(--text-color);
            line-height: 1.2;
        }

        .page-hero h1 span { color: var(--main-color); }

        .page-hero p {
            font-size: 0.88rem;
            color: var(--text-alt-color);
            margin-top: 5px;
        }

        /* ── Search bar ── */
        .search-row {
            display: flex;
            gap: 8px;
            align-items: center;
        }

        .search-input {
            padding: 11px 16px;
            border: 1.5px solid rgba(141,11,65,0.15);
            border-radius: 50px;
            font-family: inherit;
            font-size: 0.85rem;
            background: var(--bg-alt-color);
            color: var(--text-color);
            transition: all 0.22s ease;
            width: 220px;
            outline: none;
        }

        .search-input:focus {
            border-color: var(--main-color);
            background: var(--bg-color);
            box-shadow: 0 0 0 3px rgba(141,11,65,0.08);
        }

        .search-input::placeholder { color: var(--text-alt-color); }

        .btn-search {
            padding: 11px 20px;
            background: var(--main-color);
            color: #fff;
            border: none;
            border-radius: 50px;
            font-family: inherit;
            font-size: 0.85rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.22s ease;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            box-shadow: 0 4px 14px rgba(141,11,65,0.22);
            white-space: nowrap;
        }

        .btn-search:hover {
            background: #6e0932;
            transform: translateY(-1px);
            box-shadow: 0 6px 20px rgba(141,11,65,0.32);
        }

        /* ── Section Divider ── */
        .section-divider {
            max-width: 1140px;
            margin: 28px auto 28px;
            padding: 0 24px;
            display: flex;
            align-items: center;
            gap: 14px;
        }

        .section-divider span {
            font-size: 0.72rem;
            font-weight: 700;
            letter-spacing: 0.14em;
            text-transform: uppercase;
            color: var(--text-alt-color);
            white-space: nowrap;
        }

        .section-divider::after {
            content: '';
            flex: 1;
            height: 1px;
            background: linear-gradient(to right, var(--main-color), transparent);
            opacity: 0.25;
        }

        /* ── Active filters bar ── */
        .active-filters {
            max-width: 1140px;
            margin: -10px auto 18px;
            padding: 0 24px;
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
            align-items: center;
        }

        .filter-chip {
            background: rgba(141,11,65,0.08);
            border: 1px solid rgba(141,11,65,0.2);
            color: var(--main-color);
            font-size: 0.75rem;
            font-weight: 600;
            padding: 4px 12px;
            border-radius: 50px;
            display: inline-flex;
            align-items: center;
            gap: 6px;
        }

        .filter-chip a {
            color: var(--main-color);
            text-decoration: none;
            opacity: 0.7;
            font-size: 0.8rem;
        }

        .filter-chip a:hover { opacity: 1; }

        /* ── Property Grid ── */
        .property-grid {
            max-width: 1140px;
            margin: 0 auto;
            padding: 0 24px 80px;
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(330px, 1fr));
            gap: 26px;
            align-items: stretch;
        }

        /* ── Card ── */
        .prop-card {
            background: var(--bg-color);
            border-radius: 16px;
            overflow: hidden;
            border: 1.5px solid transparent;
            box-shadow: 0 4px 20px var(--shadow-color);
            display: flex;
            flex-direction: column;
            position: relative;
            cursor: pointer;
            transition: transform 0.3s ease, box-shadow 0.3s ease, border-color 0.3s ease;
            animation: fadeUp 0.45s ease both;
            text-decoration: none;
        }

        .prop-card::before {
            content: '';
            position: absolute;
            top: 0; left: 0;
            width: 4px;
            height: 100%;
            background: linear-gradient(to bottom, var(--main-color), transparent);
            border-radius: 16px 0 0 16px;
            opacity: 0;
            transition: opacity 0.3s ease;
            z-index: 2;
        }

        .prop-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 12px 36px var(--shadow-color);
            border-color: rgba(141,11,65,0.2);
        }

        .prop-card:hover::before { opacity: 1; }

        @keyframes fadeUp {
            from { opacity: 0; transform: translateY(20px); }
            to   { opacity: 1; transform: translateY(0); }
        }

        .prop-card:nth-child(1) { animation-delay: 0.04s; }
        .prop-card:nth-child(2) { animation-delay: 0.10s; }
        .prop-card:nth-child(3) { animation-delay: 0.16s; }
        .prop-card:nth-child(4) { animation-delay: 0.22s; }
        .prop-card:nth-child(5) { animation-delay: 0.28s; }
        .prop-card:nth-child(6) { animation-delay: 0.34s; }

        /* ── Card Image ── */
        .card-img-wrap {
            position: relative;
            overflow: hidden;
            height: 200px;
            flex-shrink: 0;
        }

        .card-img-wrap img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            transition: transform 0.5s ease;
        }

        .prop-card:hover .card-img-wrap img { transform: scale(1.06); }

        .price-ribbon {
            position: absolute;
            bottom: 0; left: 0; right: 0;
            background: linear-gradient(to top, rgba(0,0,0,0.68), transparent);
            padding: 30px 14px 10px;
            color: #fff;
            font-size: 1.15rem;
            font-weight: 700;
        }

        .price-ribbon small {
            font-size: 0.72rem;
            font-weight: 400;
            opacity: 0.75;
            margin-left: 2px;
        }

        /* ── Card Body ── */
        .card-body-inner {
            padding: 18px 18px 0;
            flex: 1;
            display: flex;
            flex-direction: column;
        }

        .card-title {
            font-size: 1rem;
            font-weight: 700;
            color: var(--text-color);
            margin-bottom: 5px;
            line-height: 1.35;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }

        .card-location {
            font-size: 0.78rem;
            color: var(--text-alt-color);
            display: flex;
            align-items: center;
            gap: 5px;
            margin-bottom: 12px;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }

        .card-location i { color: var(--main-color); flex-shrink: 0; }

        .card-features {
            display: flex;
            gap: 7px;
            flex-wrap: wrap;
            margin-bottom: 14px;
        }

        .feature-pill {
            background: var(--bg-alt-color);
            color: var(--text-alt-color);
            font-size: 0.75rem;
            font-weight: 600;
            padding: 4px 11px;
            border-radius: 50px;
            display: inline-flex;
            align-items: center;
            gap: 5px;
            border: 1px solid rgba(141,11,65,0.1);
        }

        .feature-pill i { color: var(--main-color); font-size: 0.72rem; }

        /* ── Landlord Footer ── */
        .card-landlord {
            margin-top: auto;
            padding: 14px 18px;
            border-top: 1px solid var(--bg-alt-color);
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 10px;
        }

        .landlord-left {
            display: flex;
            align-items: center;
            gap: 10px;
            min-width: 0;
        }

        .landlord-avatar {
            width: 36px;
            height: 36px;
            border-radius: 50%;
            object-fit: cover;
            border: 2px solid rgba(141,11,65,0.15);
            flex-shrink: 0;
        }

        .landlord-avatar-placeholder {
            width: 36px;
            height: 36px;
            border-radius: 50%;
            background: var(--main-color);
            color: #fff;
            font-size: 0.78rem;
            font-weight: 700;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
        }

        .landlord-info-text { min-width: 0; }

        .landlord-name {
            font-size: 0.8rem;
            font-weight: 700;
            color: var(--text-color);
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .landlord-role {
            font-size: 0.7rem;
            color: var(--text-alt-color);
        }

        .landlord-actions {
            display: flex;
            gap: 6px;
            flex-shrink: 0;
        }

        .action-icon {
            width: 32px;
            height: 32px;
            border-radius: 8px;
            background: var(--bg-alt-color);
            border: 1px solid rgba(141,11,65,0.12);
            color: var(--main-color);
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-size: 0.8rem;
            transition: all 0.2s ease;
            text-decoration: none;
        }

        .action-icon:hover {
            background: var(--main-color);
            color: #fff;
            border-color: var(--main-color);
        }

        /* ── Empty State ── */
        .empty-state {
            grid-column: 1 / -1;
            text-align: center;
            padding: 80px 20px;
            color: var(--text-alt-color);
        }

        .empty-state i {
            font-size: 3.5rem;
            color: var(--main-color);
            opacity: 0.2;
            display: block;
            margin-bottom: 14px;
        }

        .empty-state p { font-size: 0.95rem; }

        /* ── Filter Modal ── */
        .filter-modal {
            display: none;
            position: fixed;
            z-index: 2000;
            inset: 0;
            background: rgba(0,0,0,0.55);
            animation: fadeIn 0.2s ease;
            align-items: center;
            justify-content: center;
        }

        .filter-modal.open { display: flex; }

        @keyframes fadeIn { from { opacity: 0; } to { opacity: 1; } }

        .filter-modal-box {
            background: var(--bg-color);
            border-radius: 18px;
            width: 90%;
            max-width: 520px;
            box-shadow: 0 12px 48px rgba(0,0,0,0.25);
            overflow: hidden;
            animation: slideUp 0.25s ease;
        }

        @keyframes slideUp {
            from { transform: translateY(30px); opacity: 0; }
            to   { transform: translateY(0);    opacity: 1; }
        }

        .filter-modal-header {
            padding: 20px 24px;
            background: var(--main-color);
            color: #fff;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .filter-modal-header h2 {
            font-size: 1rem;
            font-weight: 700;
            color: #fff;
            margin: 0;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .filter-close {
            background: rgba(255,255,255,0.15);
            border: none;
            color: #fff;
            width: 30px;
            height: 30px;
            border-radius: 50%;
            cursor: pointer;
            font-size: 1.1rem;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: background 0.2s;
        }

        .filter-close:hover { background: rgba(255,255,255,0.3); }

        .filter-modal-body { padding: 24px; }

        .filter-group { margin-bottom: 18px; }

        .filter-group label {
            display: block;
            font-size: 0.8rem;
            font-weight: 700;
            color: var(--text-alt-color);
            text-transform: uppercase;
            letter-spacing: 0.07em;
            margin-bottom: 7px;
        }

        .filter-group select,
        .filter-group input[type="number"] {
            width: 100%;
            padding: 10px 14px;
            border: 1.5px solid rgba(141,11,65,0.15);
            border-radius: 10px;
            font-family: inherit;
            font-size: 0.85rem;
            background: var(--bg-alt-color);
            color: var(--text-color);
            outline: none;
            transition: border-color 0.2s;
        }

        .filter-group select:focus,
        .filter-group input[type="number"]:focus {
            border-color: var(--main-color);
            box-shadow: 0 0 0 3px rgba(141,11,65,0.08);
        }

        .price-range {
            display: grid;
            grid-template-columns: 1fr auto 1fr;
            gap: 8px;
            align-items: center;
        }

        .price-range span {
            text-align: center;
            font-size: 0.8rem;
            color: var(--text-alt-color);
        }

        .filter-actions { display: flex; gap: 10px; margin-top: 20px; }

        .btn-apply {
            flex: 1;
            padding: 11px;
            background: var(--main-color);
            color: #fff;
            border: none;
            border-radius: 10px;
            font-family: inherit;
            font-size: 0.88rem;
            font-weight: 700;
            cursor: pointer;
            transition: background 0.22s;
        }

        .btn-apply:hover { background: #6e0932; }

        .btn-clear {
            flex: 1;
            padding: 11px;
            background: var(--bg-alt-color);
            color: var(--text-alt-color);
            border: 1.5px solid rgba(141,11,65,0.12);
            border-radius: 10px;
            font-family: inherit;
            font-size: 0.88rem;
            font-weight: 700;
            cursor: pointer;
            text-decoration: none;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.22s;
        }

        .btn-clear:hover {
            background: rgba(141,11,65,0.06);
            color: var(--main-color);
            border-color: var(--main-color);
        }

        @media (max-width: 600px) {
            .page-hero .inner { flex-direction: column; align-items: flex-start; }
            .property-grid { grid-template-columns: 1fr; }
            .search-row { width: 100%; }
            .search-input { width: 100%; }
        }

        /* ── Autocomplete Dropdown ── */
.search-autocomplete-wrap { position: relative; }

#autocompleteList {
    position: absolute;
    top: calc(100% + 6px);
    left: 0;
    right: 0;
    background: var(--bg-color);
    border: 1.5px solid rgba(141,11,65,0.18);
    border-radius: 14px;
    list-style: none;
    margin: 0;
    padding: 6px;
    z-index: 9999;
    box-shadow: 0 8px 28px rgba(0,0,0,0.12);
    display: none;
    max-height: 260px;
    overflow-y: auto;
}

#autocompleteList.open { display: block; }

#autocompleteList li {
    padding: 9px 14px;
    font-size: 0.84rem;
    color: var(--text-color);
    border-radius: 9px;
    cursor: pointer;
    display: flex;
    align-items: center;
    gap: 8px;
    transition: background 0.15s ease;
}

#autocompleteList li:hover,
#autocompleteList li.active {
    background: rgba(141,11,65,0.08);
    color: var(--main-color);
}

#autocompleteList li i {
    color: var(--main-color);
    font-size: 0.75rem;
    opacity: 0.7;
    flex-shrink: 0;
}

#autocompleteList li mark {
    background: transparent;
    color: var(--main-color);
    font-weight: 700;
    padding: 0;
}

#autocompleteList .ac-section-label {
    font-size: 0.68rem;
    font-weight: 700;
    letter-spacing: 0.1em;
    text-transform: uppercase;
    color: var(--text-alt-color);
    padding: 6px 14px 4px;
    cursor: default;
    border-bottom: 1px solid rgba(141,11,65,0.08);
    margin-bottom: 4px;
    border-radius: 0;
    display: block;
}

#autocompleteList .ac-section-label:hover { background: transparent; color: var(--text-alt-color); }
    </style>
</head>

<body>
    <?php include '../Components/tenant-header.php' ?>

    <!-- Filter Modal -->
    <div id="filterModal" class="filter-modal">
        <div class="filter-modal-box">
            <div class="filter-modal-header">
                <h2><i class="fas fa-sliders-h"></i> Advanced Filters</h2>
                <button class="filter-close" onclick="closeFilterModal()"><i class="fas fa-times"></i></button>
            </div>
            <div class="filter-modal-body">
                <form method="GET" action="" id="filterForm">
                    <input type="hidden" name="search" value="<?= htmlspecialchars($search) ?>">

                    <div class="filter-group">
                        <label>Barangay</label>
                        <select name="barangay">
                            <option value="">All Barangays</option>
                            <?php
                            $barangays = ['Bagong Silang','Calendola','Chrysanthemum','Cuyab','Estrella','Fatima','G.S.I.S.','Landayan','Langgam','Laram','Magsaysay','Maharlika','Narra','Nueva','Pacita 1','Pacita 2','Poblacion','Riverside','Rosario','Sampaguita Village','San Antonio','San Roque','San Vicente','San Lorenzo Ruiz','Santo Niño','United Bayanihan','United Better Living'];
                            foreach ($barangays as $b):
                            ?>
                                <option value="<?= $b ?>" <?= $barangay == $b ? 'selected' : '' ?>><?= $b ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="filter-group">
                        <label>Category</label>
                        <select name="category">
                            <option value="">All Categories</option>
                            <?php
                            $categories = ['Condominium','Apartment complex','Single-family home','Townhouse','Low-rise apartment','High-rise apartment'];
                            foreach ($categories as $c):
                            ?>
                                <option value="<?= $c ?>" <?= $category == $c ? 'selected' : '' ?>><?= $c ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="filter-group">
                        <label>Number of Rooms</label>
                        <select name="rooms">
                            <option value="">Any</option>
                            <?php for ($i = 1; $i <= 5; $i++): ?>
                                <option value="<?= $i ?>" <?= $rooms == $i ? 'selected' : '' ?>><?= $i ?><?= $i == 5 ? '+' : '' ?> Room<?= $i > 1 ? 's' : '' ?></option>
                            <?php endfor; ?>
                        </select>
                    </div>

                    <div class="filter-group">
                        <label>Price Range (₱)</label>
                        <div class="price-range">
                            <input type="number" name="min_price" placeholder="Min" value="<?= htmlspecialchars($min_price) ?>" min="0">
                            <span>—</span>
                            <input type="number" name="max_price" placeholder="Max" value="<?= htmlspecialchars($max_price) ?>" min="0">
                        </div>
                    </div>

                    <div class="filter-actions">
                        <button type="submit" class="btn-apply">Apply Filters</button>
                        <a href="<?= $_SERVER['PHP_SELF'] ?>" class="btn-clear">Clear All</a>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Page Hero -->
    <div class="page-hero">
        <div class="inner">
            <div>
                <h1>Welcome, <span><?= htmlspecialchars($tenant_name) ?>!</span></h1>
                <p>Browse available properties in San Pedro, Laguna</p>
            </div>
            <div class="search-row">
    <form method="GET" action="" style="display:contents;" id="mainSearchForm">
        <?php if (!empty($barangay)):  ?><input type="hidden" name="barangay"  value="<?= htmlspecialchars($barangay)  ?>"><?php endif; ?>
        <?php if (!empty($category)):  ?><input type="hidden" name="category"  value="<?= htmlspecialchars($category)  ?>"><?php endif; ?>
        <?php if (!empty($rooms)):      ?><input type="hidden" name="rooms"     value="<?= htmlspecialchars($rooms)     ?>"><?php endif; ?>
        <?php if (!empty($min_price)):  ?><input type="hidden" name="min_price" value="<?= htmlspecialchars($min_price) ?>"><?php endif; ?>
        <?php if (!empty($max_price)):  ?><input type="hidden" name="max_price" value="<?= htmlspecialchars($max_price) ?>"><?php endif; ?>

        <div class="search-autocomplete-wrap" style="position:relative;">
            <input type="text" name="search" id="mainSearchInput" class="search-input"
                placeholder="Search properties or barangay..."
                value="<?= htmlspecialchars($search) ?>"
                autocomplete="off">
            <ul id="autocompleteList"></ul>
        </div>

        <button type="submit" class="btn-search"><i class="fas fa-search"></i> Search</button>
    </form>

    <button type="button" class="btn-search" style="background: var(--bg-color); color: var(--main-color); border: 1.5px solid rgba(141,11,65,0.25); box-shadow: none;" onclick="openFilterModal()">
        <i class="fas fa-sliders-h"></i> Filters
        <?php $hasFilters = !empty($barangay) || !empty($category) || !empty($rooms) || !empty($min_price) || !empty($max_price); ?>
        <?php if ($hasFilters): ?>
            <span style="background:var(--main-color);color:#fff;border-radius:50%;width:16px;height:16px;font-size:0.65rem;display:inline-flex;align-items:center;justify-content:center;">✓</span>
        <?php endif; ?>
    </button>
</div>
        </div>
    </div>

    <!-- Section label + active filters -->
    <div class="section-divider">
        <span>
            <?php
            $totalCount = $result ? $result->num_rows : 0;
            echo $totalCount . ' Propert' . ($totalCount == 1 ? 'y' : 'ies') . ' Found';
            ?>
        </span>
    </div>

    <?php if ($hasFilters): ?>
    <div class="active-filters">
        <?php if (!empty($barangay)):  ?><span class="filter-chip"><i class="fas fa-map-pin"></i> <?= htmlspecialchars($barangay) ?></span><?php endif; ?>
        <?php if (!empty($category)):  ?><span class="filter-chip"><i class="fas fa-building"></i> <?= htmlspecialchars($category) ?></span><?php endif; ?>
        <?php if (!empty($rooms)):     ?><span class="filter-chip"><i class="fas fa-bed"></i> <?= $rooms ?> Room(s)</span><?php endif; ?>
        <?php if (!empty($min_price) || !empty($max_price)): ?>
            <span class="filter-chip"><i class="fas fa-tag"></i> ₱<?= number_format($min_price ?: 0) ?> – <?= $max_price ? '₱'.number_format($max_price) : 'Any' ?></span>
        <?php endif; ?>
        <a href="<?= $_SERVER['PHP_SELF'] ?>" style="font-size:0.75rem; color:var(--main-color); text-decoration:none; font-weight:600; opacity:0.7;">
            <i class="fas fa-times"></i> Clear all
        </a>
    </div>
    <?php endif; ?>

    <!-- Property Grid -->
    <div class="property-grid">

        <?php if ($result && $result->num_rows > 0):
            // Reset pointer since we used num_rows
            $result->data_seek(0);
            while ($row = $result->fetch_assoc()):
                $images    = json_decode($row['images'], true);
                $imagePath = '../img/house1.jpeg';
                if (!empty($images) && is_array($images) && isset($images[0])) {
                    $imagePath = '../LANDLORD/uploads/' . $images[0];
                }
                $landlordInitials = strtoupper(substr($row['firstName'],0,1) . substr($row['lastName'],0,1));
                $landlordFullName = ucwords(htmlspecialchars($row['firstName'] . ' ' . $row['lastName']));
        ?>

            <div class="prop-card" onclick="window.location='property-details.php?ID=<?= $row['ID'] ?>'">

                <!-- Image -->
                <div class="card-img-wrap">
                    <img src="<?= htmlspecialchars($imagePath) ?>" alt="Property Image"
                        onerror="this.src='../img/house1.jpeg'">
                    <div class="price-ribbon">
                        ₱ <?= number_format($row['price']) ?><small>/mo</small>
                    </div>
                </div>

                <!-- Body -->
                <div class="card-body-inner">
                    <h3 class="card-title"><?= htmlspecialchars($row['listingName']) ?></h3>
                    <div class="card-location">
                        <i class="fas fa-map-marker-alt"></i>
                        <?= htmlspecialchars($row['address']) ?>
                    </div>
                    <div class="card-features">
                        <span class="feature-pill">
                            <i class="fas fa-bed"></i> <?= htmlspecialchars($row['rooms']) ?> Bedroom
                        </span>
                        <span class="feature-pill">
                            <i class="fa-solid fa-building"></i> <?= htmlspecialchars($row['category']) ?>
                        </span>
                    </div>
                </div>

                <!-- Landlord footer -->
                <div class="card-landlord" onclick="event.stopPropagation()">
                    <div class="landlord-left">
                        <?php if (!empty($row['profilePic'])): ?>
                            <img src="../uploads/<?= htmlspecialchars($row['profilePic']) ?>"
                                alt="Landlord"
                                class="landlord-avatar"
                                onerror="this.style.display='none'">
                        <?php else: ?>
                            <span class="landlord-avatar-placeholder"><?= $landlordInitials ?></span>
                        <?php endif; ?>
                        <div class="landlord-info-text">
                            <div class="landlord-name"><?= $landlordFullName ?></div>
                            <div class="landlord-role">Landlord</div>
                        </div>
                    </div>
                    <div class="landlord-actions">
                        <a href="landlord-profile.php?landlord_id=<?= $row['landlord_id'] ?>" class="action-icon" title="View Profile">
                            <i class="fas fa-user"></i>
                        </a>
                        <a href="tenant-messages.php?landlord_id=<?= $row['landlord_id'] ?>" class="action-icon" title="Send Message">
                            <i class="fas fa-comment-dots"></i>
                        </a>
                    </div>
                </div>

            </div>

        <?php endwhile; else: ?>
            <div class="empty-state">
                <i class="bi bi-building-slash"></i>
                <p>No properties found matching your criteria.<br>Try adjusting your filters.</p>
            </div>
        <?php endif; ?>

    </div>

    <?php include '../Components/footer.php'; ?>

    <script src="../js/script.js" defer></script>
    <script src="../js/bootstrap.bundle.min.js"></script>
    <script src="https://unpkg.com/scrollreveal"></script>

    
    <script>
    function openFilterModal() {
        const modal = document.getElementById('filterModal');
        modal.classList.add('open');
        document.body.style.overflow = 'hidden';
    }

    function closeFilterModal() {
        const modal = document.getElementById('filterModal');
        modal.classList.remove('open');
        document.body.style.overflow = '';
    }

    document.getElementById('filterModal').addEventListener('click', function(e) {
        if (e.target === this) closeFilterModal();
    });

    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') closeFilterModal();
    });

    (function () {
        const barangays = [
            'Bagong Silang','Calendola','Chrysanthemum','Cuyab','Estrella','Fatima',
            'G.S.I.S.','Landayan','Langgam','Laram','Magsaysay','Maharlika','Narra',
            'Nueva','Pacita 1','Pacita 2','Poblacion','Riverside','Rosario',
            'Sampaguita Village','San Antonio','San Roque','San Vicente',
            'San Lorenzo Ruiz','Santo Niño','United Bayanihan','United Better Living'
        ];

        const input = document.getElementById('mainSearchInput');
        const list  = document.getElementById('autocompleteList');
        const form  = document.getElementById('mainSearchForm');
        const grid  = document.querySelector('.property-grid');
        const cards = document.querySelectorAll('.prop-card');
        const countEl = document.querySelector('.section-divider span');
        let activeIdx = -1;

        if (!input) return;

        /* ── Highlight matched letters ── */
        function highlight(text, query) {
            if (!query) return text;
            const escaped = query.replace(/[.*+?^${}()|[\]\\]/g, '\\$&');
            return text.replace(new RegExp(`(${escaped})`, 'gi'), '<mark>$1</mark>');
        }

        /* ── Autocomplete dropdown ── */
        function renderList(query) {
            list.innerHTML = '';
            activeIdx = -1;

            if (!query.trim()) { list.classList.remove('open'); return; }

            const q = query.toLowerCase();
            const matches = barangays.filter(b => b.toLowerCase().includes(q));

            if (!matches.length) { list.classList.remove('open'); return; }

            const label = document.createElement('li');
            label.className = 'ac-section-label';
            label.textContent = 'Barangays';
            list.appendChild(label);

            matches.forEach(b => {
                const li = document.createElement('li');
                li.innerHTML = `<i class="fas fa-map-pin"></i> ${highlight(b, query)}`;
                li.dataset.value = b;
                li.addEventListener('mousedown', (e) => {
                    e.preventDefault();
                    selectBarangay(b);
                });
                list.appendChild(li);
            });

            list.classList.add('open');
        }

        function selectBarangay(value) {
            input.value = value;
            list.classList.remove('open');

            let hidden = form.querySelector('input[name="barangay"]');
            if (!hidden) {
                hidden = document.createElement('input');
                hidden.type = 'hidden';
                hidden.name = 'barangay';
                form.appendChild(hidden);
            }
            hidden.value = value;

            form.submit();
        }

        /* ── Live card filtering ── */
        function filterCards(query) {
            const q = query.toLowerCase().trim();
            let visible = 0;

            cards.forEach(card => {
                const text = card.textContent.toLowerCase();
                const show = !q || text.includes(q);
                card.style.display = show ? '' : 'none';
                if (show) visible++;
            });

            if (countEl) {
                countEl.textContent = visible + ' Propert' + (visible === 1 ? 'y' : 'ies') + ' Found';
            }

            let empty = grid.querySelector('.empty-state');
            if (visible === 0) {
                if (!empty) {
                    empty = document.createElement('div');
                    empty.className = 'empty-state';
                    empty.style.gridColumn = '1 / -1';
                    grid.appendChild(empty);
                }
                empty.innerHTML = `
                    <i class="fas fa-building" style="font-size:3.5rem;color:var(--main-color);opacity:0.2;display:block;margin-bottom:14px;"></i>
                    <p>No properties match "<strong>${query}</strong>".<br>Try a different keyword.</p>
                `;
                empty.style.display = '';
            } else if (empty) {
                empty.style.display = 'none';
            }
        }

        /* ── Single input listener for both ── */
        input.addEventListener('input', function () {
            renderList(this.value);
            filterCards(this.value);
        });

        input.addEventListener('focus', () => {
            if (input.value) renderList(input.value);
        });

        /* ── Keyboard navigation ── */
        input.addEventListener('keydown', (e) => {
            const items = list.querySelectorAll('li:not(.ac-section-label)');
            if (!items.length) return;

            if (e.key === 'ArrowDown') {
                e.preventDefault();
                activeIdx = Math.min(activeIdx + 1, items.length - 1);
            } else if (e.key === 'ArrowUp') {
                e.preventDefault();
                activeIdx = Math.max(activeIdx - 1, 0);
            } else if (e.key === 'Enter' && activeIdx >= 0) {
                e.preventDefault();
                selectBarangay(items[activeIdx].dataset.value);
                return;
            } else if (e.key === 'Escape') {
                list.classList.remove('open');
                return;
            }

            items.forEach((item, i) => item.classList.toggle('active', i === activeIdx));
            if (activeIdx >= 0) items[activeIdx].scrollIntoView({ block: 'nearest' });
        });

        document.addEventListener('click', (e) => {
            if (!input.contains(e.target) && !list.contains(e.target)) {
                list.classList.remove('open');
            }
        });
    })();
</script>
</body>
</html>