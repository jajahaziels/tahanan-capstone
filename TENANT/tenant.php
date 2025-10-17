<?php
require_once '../connection.php';
include '../session_auth.php';

$tenant_id = $_SESSION['tenant_id'];

// Handle search and filters
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$barangay = isset($_GET['barangay']) ? $_GET['barangay'] : '';
$category = isset($_GET['category']) ? $_GET['category'] : '';
$rooms = isset($_GET['rooms']) ? $_GET['rooms'] : '';
$min_price = isset($_GET['min_price']) ? intval($_GET['min_price']) : '';
$max_price = isset($_GET['max_price']) ? intval($_GET['max_price']) : '';

// Build query with filters
$sql = "
    SELECT l.*, lt.firstName, lt.lastName, lt.profilePic
    FROM listingtbl AS l
    JOIN landlordtbl AS lt ON l.landlord_id = lt.ID
    LEFT JOIN renttbl AS r 
        ON l.ID = r.listing_id AND r.status = 'approved'
    WHERE r.ID IS NULL AND l.availability = 'available'
";

if (!empty($search)) {
    $sql .= " AND (l.listingName LIKE '%$search%' OR l.listingDesc LIKE '%$search%' OR l.address LIKE '%$search%')";
}

if (!empty($barangay)) {
    $sql .= " AND l.barangay = '$barangay'";
}

if (!empty($category)) {
    $sql .= " AND l.category = '$category'";
}

if (!empty($rooms)) {
    $sql .= " AND l.rooms = $rooms";
}

if (!empty($min_price)) {
    $sql .= " AND l.price >= $min_price";
}

if (!empty($max_price)) {
    $sql .= " AND l.price <= $max_price";
}

$sql .= " ORDER BY l.listingDate DESC";
$result = $conn->query($sql);
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <!-- FAVICON -->
    <link rel="shortcut icon" href="favicon.ico" type="image/x-icon">
    <!-- FA -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/7.0.1/css/all.min.css"
        integrity="sha512-2SwdPD6INVrV/lHTZbO2nodKhrnDdJK9/kg2XD1r9uGqPo1cUbujc+IYdlYdEErWNu69gVcYgdxlmVmzTWnetw=="
        crossorigin="anonymous" referrerpolicy="no-referrer" />
    <!-- BS -->
    <link rel="stylesheet" href="../css/bootstrap.min.css">
    <!-- MAIN CSS -->
    <link rel="stylesheet" href="../css/style.css?v=<?= time(); ?>">
    <title>Tenant <?= htmlspecialchars($_SESSION['username']); ?>!</title>
    <style>
        .tenant-page {
            margin-top: 140px !important;
        }

        .search-bar-container {
            padding: 0;
            margin-bottom: 20px;
            background: none;
            border-radius: 0;
        }

        .search-bar-wrapper {
            display: flex;
            gap: 8px;
            max-width: 400px;
            margin: 0;
            padding: 0;
        }

        .search-input-group {
            flex: 1;
        }

        .search-input {
            width: 100%;
            padding: 10px 15px;
            border: 1px solid #ddd;
            border-radius: 6px;
            font-size: 14px;
            background: white;
            color: #333;
            transition: all 0.3s;
        }

        .search-input:focus {
            outline: none;
            border-color: #667eea;
            box-shadow: 0 0 0 2px rgba(102, 126, 234, 0.1);
        }

        .search-input::placeholder {
            color: #999;
        }

        .filter-btn-tenant {
            padding: 10px 16px;
            background: #8D0B41;
            color: white;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-weight: 500;
            font-size: 14px;
            transition: all 0.3s;
            display: flex;
            align-items: center;
            gap: 5px;
            white-space: nowrap;
        }

        .filter-btn-tenant:hover {
            background: #7c0a3aff;
            transform: translateY(-1px);
        }

        .filter-btn-tenant i {
            font-size: 14px;
        }

        /* Modal Styles */
        .filter-modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0,0,0,0.5);
            animation: fadeIn 0.3s;
        }

        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }

        .modal-content-filter {
            background-color: #fefefe;
            margin: 5% auto;
            padding: 0;
            border-radius: 12px;
            width: 90%;
            max-width: 550px;
            box-shadow: 0 5px 30px rgba(0,0,0,0.3);
            animation: slideDown 0.3s;
        }

        @keyframes slideDown {
            from { transform: translateY(-50px); opacity: 0; }
            to { transform: translateY(0); opacity: 1; }
        }

        .modal-header-filter {
            padding: 20px 25px;
            background: #8D0B41;
            color: white;
            border-radius: 12px 12px 0 0;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .modal-header-filter h2 {
            margin: 0;
            font-size: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
            color: white;
        }

        .close-filter {
            color: white;
            font-size: 28px;
            font-weight: bold;
            cursor: pointer;
            line-height: 20px;
        }

        .close-filter:hover {
            opacity: 0.7;
        }

        .modal-body-filter {
            padding: 25px;
        }

        .filter-group {
            margin-bottom: 20px;
        }

        .filter-group label {
            display: block;
            font-weight: 600;
            color: #333;
            margin-bottom: 8px;
            font-size: 14px;
        }

        .filter-group select,
        .filter-group input {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 6px;
            font-size: 14px;
            background: white;
            transition: all 0.3s;
        }

        .filter-group select:focus,
        .filter-group input:focus {
            outline: none;
            border-color: #8D0B41;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }

        .price-range {
            display: grid;
            grid-template-columns: 1fr auto 1fr;
            gap: 8px;
            align-items: end;
        }

        .price-range span {
            text-align: center;
            color: #999;
            font-size: 13px;
        }

        .filter-actions {
            display: flex;
            gap: 8px;
            margin-top: 20px;
        }

        .btn-apply-filter {
            flex: 1;
            padding: 10px;
            background: #8D0B41;
            color: white;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-weight: 600;
            font-size: 14px;
            transition: all 0.3s;
        }

        .btn-apply-filter:hover {
            background: #7c0a3aff;
        }

        .btn-clear-filter {
            flex: 1;
            padding: 10px;
            background: #f0f0f0;
            color: #333;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-weight: 600;
            font-size: 14px;
            transition: all 0.3s;
        }

        .btn-clear-filter:hover {
            background: #e0e0e0;
        }

        @media (max-width: 768px) {
            .search-bar-wrapper {
                flex-direction: column;
            }

            .filter-btn-tenant {
                width: 100%;
                justify-content: center;
            }

            .price-range {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>

<body>
    <!-- HEADER -->
    <?php include '../Components/tenant-header.php' ?>

    <!-- FILTER MODAL -->
    <div id="filterModal" class="filter-modal">
        <div class="modal-content-filter">
            <div class="modal-header-filter">
                <h2><i class="fas fa-filter"></i>Advanced Filters</h2>
                <span class="close-filter" onclick="closeFilterModal()">&times;</span>
            </div>
            <div class="modal-body-filter">
                <form method="GET" action="" id="filterForm">
                    <input type="hidden" name="search" value="<?= htmlspecialchars($search) ?>">

                    <div class="filter-group">
                        <label>Barangay</label>
                        <select name="barangay">
                            <option value="">All Barangays</option>
                            <option value="Bagong Silang" <?= $barangay == 'Bagong Silang' ? 'selected' : '' ?>>Bagong Silang</option>
                            <option value="Calendola" <?= $barangay == 'Calendola' ? 'selected' : '' ?>>Calendola</option>
                            <option value="Chrysanthemum" <?= $barangay == 'Chrysanthemum' ? 'selected' : '' ?>>Chrysanthemum</option>
                            <option value="Cuyab" <?= $barangay == 'Cuyab' ? 'selected' : '' ?>>Cuyab</option>
                            <option value="Estrella" <?= $barangay == 'Estrella' ? 'selected' : '' ?>>Estrella</option>
                            <option value="Fatima" <?= $barangay == 'Fatima' ? 'selected' : '' ?>>Fatima</option>
                            <option value="G.S.I.S." <?= $barangay == 'G.S.I.S.' ? 'selected' : '' ?>>G.S.I.S.</option>
                            <option value="Landayan" <?= $barangay == 'Landayan' ? 'selected' : '' ?>>Landayan</option>
                            <option value="Langgam" <?= $barangay == 'Langgam' ? 'selected' : '' ?>>Langgam</option>
                            <option value="Laram" <?= $barangay == 'Laram' ? 'selected' : '' ?>>Laram</option>
                            <option value="Magsaysay" <?= $barangay == 'Magsaysay' ? 'selected' : '' ?>>Magsaysay</option>
                            <option value="Maharlika" <?= $barangay == 'Maharlika' ? 'selected' : '' ?>>Maharlika</option>
                            <option value="Narra" <?= $barangay == 'Narra' ? 'selected' : '' ?>>Narra</option>
                            <option value="Nueva" <?= $barangay == 'Nueva' ? 'selected' : '' ?>>Nueva</option>
                            <option value="Pacita 1" <?= $barangay == 'Pacita 1' ? 'selected' : '' ?>>Pacita 1</option>
                            <option value="Pacita 2" <?= $barangay == 'Pacita 2' ? 'selected' : '' ?>>Pacita 2</option>
                            <option value="Poblacion" <?= $barangay == 'Poblacion' ? 'selected' : '' ?>>Poblacion</option>
                            <option value="Riverside" <?= $barangay == 'Riverside' ? 'selected' : '' ?>>Riverside</option>
                            <option value="Rosario" <?= $barangay == 'Rosario' ? 'selected' : '' ?>>Rosario</option>
                            <option value="Sampaguita Village" <?= $barangay == 'Sampaguita Village' ? 'selected' : '' ?>>Sampaguita Village</option>
                            <option value="San Antonio" <?= $barangay == 'San Antonio' ? 'selected' : '' ?>>San Antonio</option>
                            <option value="San Roque" <?= $barangay == 'San Roque' ? 'selected' : '' ?>>San Roque</option>
                            <option value="San Vicente" <?= $barangay == 'San Vicente' ? 'selected' : '' ?>>San Vicente</option>
                            <option value="San Lorenzo Ruiz" <?= $barangay == 'San Lorenzo Ruiz' ? 'selected' : '' ?>>San Lorenzo Ruiz</option>
                            <option value="Santo Niño" <?= $barangay == 'Santo Niño' ? 'selected' : '' ?>>Santo Niño</option>
                            <option value="United Bayanihan" <?= $barangay == 'United Bayanihan' ? 'selected' : '' ?>>United Bayanihan</option>
                            <option value="United Better Living" <?= $barangay == 'United Better Living' ? 'selected' : '' ?>>United Better Living</option>
                        </select>
                    </div>

                    <div class="filter-group">
                        <label>Category</label>
                        <select name="category">
                            <option value="">All Categories</option>
                            <option value="Condominium" <?= $category == 'Condominium' ? 'selected' : '' ?>>Condominium</option>
                            <option value="Apartment complex" <?= $category == 'Apartment complex' ? 'selected' : '' ?>>Apartment complex</option>
                            <option value="Single-family home" <?= $category == 'Single-family home' ? 'selected' : '' ?>>Single-family home</option>
                            <option value="Townhouse" <?= $category == 'Townhouse' ? 'selected' : '' ?>>Townhouse</option>
                            <option value="Low-rise apartment" <?= $category == 'Low-rise apartment' ? 'selected' : '' ?>>Low-rise apartment</option>
                            <option value="High-rise apartment" <?= $category == 'High-rise apartment' ? 'selected' : '' ?>>High-rise apartment</option>
                        </select>
                    </div>

                    <div class="filter-group">
                        <label>Number of Rooms</label>
                        <select name="rooms">
                            <option value="">Any</option>
                            <option value="1" <?= $rooms == '1' ? 'selected' : '' ?>>1 Room</option>
                            <option value="2" <?= $rooms == '2' ? 'selected' : '' ?>>2 Rooms</option>
                            <option value="3" <?= $rooms == '3' ? 'selected' : '' ?>>3 Rooms</option>
                            <option value="4" <?= $rooms == '4' ? 'selected' : '' ?>>4 Rooms</option>
                            <option value="5" <?= $rooms == '5' ? 'selected' : '' ?>>5+ Rooms</option>
                        </select>
                    </div>

                    <div class="filter-group">
                        <label>Price Range (₱)</label>
                        <div class="price-range">
                            <input type="number" name="min_price" placeholder="Min" value="<?= htmlspecialchars($min_price) ?>" min="0">
                            <span>to</span>
                            <input type="number" name="max_price" placeholder="Max" value="<?= htmlspecialchars($max_price) ?>" min="0">
                        </div>
                    </div>

                    <div class="filter-actions">
                        <button type="submit" class="btn-apply-filter">Apply Filters</button>
                        <a href="<?= $_SERVER['PHP_SELF'] ?>" class="btn-clear-filter" style="text-decoration: none; display: flex; align-items: center; justify-content: center;">Clear All</a>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- HOME PAGE CONTENT -->
    <div class="tenant-page">
        <div class="container m-auto">
            <h1>Welcome, Tenant <?= htmlspecialchars(ucwords(strtolower($_SESSION['username']))); ?>!</h1>
            <p>Here are some featured properties</p>

            <!-- SEARCH CONTAINER -->
            <div class="search-bar-container" style="margin-top: 20px;">
                <div class="search-bar-wrapper">
                    <form method="GET" action="" class="search-input-group">
                        <input 
                            type="text" 
                            name="search" 
                            class="search-input" 
                            placeholder="Search properties..." 
                            value="<?= htmlspecialchars($search) ?>"
                        >
                    </form>
                    <button type="button" class="filter-btn-tenant" onclick="openFilterModal()">
                        <i class="fas fa-filter"></i>
                        Filters
                    </button>
                </div>
            </div>

            <div class="row justify-content-center">
                <div class="col-lg-10">
                    <div class="row justify-content-center">
                        <?php if ($result && $result->num_rows > 0): ?>
                            <?php while ($row = $result->fetch_assoc()): ?>
                                <div class="col-lg-4 col-sm-12">
                                    <div class="cards mb-4" onclick="window.location='property-details.php?ID=<?= $row['ID']; ?>'">
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
                                                <div class="label">Specials</div>
                                            </div>

                                            <!-- Price -->
                                            <div class="price-tag">₱ <?= number_format($row['price']); ?></div>
                                        </div>

                                        <div class="cards-content">
                                            <!-- Property Name -->
                                            <h5 class="mb-2 house-name"><?= htmlspecialchars($row['listingName']); ?></h5>

                                            <!-- Address -->
                                            <div class="mb-2 location">
                                                <i class="fas fa-map-marker-alt"></i>
                                                <?= htmlspecialchars($row['address']); ?>
                                            </div>

                                            <!-- Features -->
                                            <div class="features">
                                                <div class="m-2">
                                                    <i class="fas fa-bed"></i> <?= htmlspecialchars($row['rooms']); ?> Bedroom
                                                </div>
                                                <div class="m-2">
                                                    <i class="fa-solid fa-building"></i> <?= htmlspecialchars($row['category']); ?>
                                                </div>
                                            </div>

                                            <div class="divider my-3"></div>

                                            <!-- Landlord Info -->
                                            <div class="landlord-info">
                                                <div class="landlord-left d-flex align-items-center">
                                                    <?php if (!empty($row['profilePic'])): ?>
                                                        <img src="../LANDLORD/uploads/<?= htmlspecialchars($row['profilePic']); ?>"
                                                            alt="Landlord"
                                                            style="width:40px; height:40px; border-radius:50%; object-fit:cover;"
                                                            onerror="this.style.display='none'">
                                                    <?php endif; ?>

                                                    <div class="ms-2">
                                                        <div class="landlord-name">
                                                            <?= ucwords(htmlspecialchars($row['firstName'] . ' ' . $row['lastName'])); ?>
                                                        </div>
                                                        <div class="landlord-role">Landlord</div>
                                                    </div>
                                                </div>

                                                <div class="landlord-actions">
                                                    <div class="btn"><i class="fa-solid fa-user"></i></div>
                                                    <div class="btn"><i class="fas fa-comment-dots"></i></div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <p style="text-align: center; color: #666;">No listings found matching your criteria.</p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- MAIN JS -->
    <script src="../js/script.js" defer></script>
    <!-- BS JS -->
    <script src="../js/bootstrap.bundle.min.js"></script>
    <!-- SCROLL REVEAL -->
    <script src="https://unpkg.com/scrollreveal"></script>
    <script>
        function openFilterModal() {
            document.getElementById('filterModal').style.display = 'block';
        }

        function closeFilterModal() {
            document.getElementById('filterModal').style.display = 'none';
        }

        window.onclick = function(event) {
            const modal = document.getElementById('filterModal');
            if (event.target == modal) {
                modal.style.display = 'none';
            }
        }

        document.addEventListener('keydown', function(event) {
            if (event.key === 'Escape') {
                document.getElementById('filterModal').style.display = 'none';
            }
        });
    </script>
</body>

</html>