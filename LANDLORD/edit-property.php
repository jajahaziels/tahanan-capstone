<?php
require_once '../connection.php';
session_start();

$errors = [];
$success = "";

$ID = $_GET['ID'] ?? null;
if (!$ID) {
    die("Invalid request.");
}

$stmt = $conn->prepare("SELECT * FROM listingtbl WHERE ID = ?");
$stmt->bind_param("i", $ID);
$stmt->execute();
$result = $stmt->get_result();
$property = $result->fetch_assoc();
$stmt->close();

if (!$property) {
    die("Property not found.");
}

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $listingName  = trim($_POST['listingName'] ?? '');
    $address      = trim($_POST['address'] ?? '');
    $barangay     = $_POST['barangay'] ?? '';
    $price        = $_POST['price'] ?? '';
    $rooms        = $_POST['rooms'] ?? '';
    $category     = $_POST['category'] ?? '';
    $listingDesc  = trim($_POST['description'] ?? '');
    $latitude     = $_POST['latitude'] ?? null;
    $longitude    = $_POST['longitude'] ?? null;

    // House Rules / Terms
    $terms = $_POST['terms'] ?? [];
    $terms = array_values(array_filter(array_map('trim', $terms)));

    // Validation
    if (empty($listingName)) $errors[] = "Listing name is required.";
    if (empty($address)) $errors[] = "Address is required.";
    if (empty($barangay)) $errors[] = "Barangay is required.";
    if (empty($price) || $price <= 0) $errors[] = "Price must be greater than 0.";
    if (empty($rooms)) $errors[] = "Number of rooms is required.";
    if (empty($category)) $errors[] = "Category is required.";
    if (empty($latitude) || empty($longitude)) $errors[] = "Location must be pinned on the map.";

    // Get current images from DB
    $uploadedImages = json_decode($property['images'], true) ?? [];

    // Handle image removals
    if (!empty($_POST['remove_images'])) {
        foreach ($_POST['remove_images'] as $removeImg) {
            if (($key = array_search($removeImg, $uploadedImages)) !== false) {
                unset($uploadedImages[$key]);
                $filePath = "uploads/" . $removeImg;
                if (file_exists($filePath)) {
                    unlink($filePath);
                }
            }
        }
        $uploadedImages = array_values($uploadedImages);
    }

    
 // Handle new image uploads
if (isset($_FILES['image']) && !empty($_FILES['image']['tmp_name'][0])) {
    $uploadDir = __DIR__ . "/uploads/";
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0777, true);
    }

    foreach ($_FILES['image']['tmp_name'] as $key => $tmpName) {
        $fileErr  = $_FILES['image']['error'][$key];
        $fileName = $_FILES['image']['name'][$key];

        if ($fileErr === UPLOAD_ERR_OK && is_uploaded_file($tmpName)) {
            $ext      = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
            $newName  = time() . "_" . $key . "_" . uniqid() . "." . $ext;
            $target   = $uploadDir . $newName;

            if (move_uploaded_file($tmpName, $target)) {
                $uploadedImages[] = $newName;
            } else {
                $errors[] = "Failed to move: " . htmlspecialchars($fileName);
            }
        }
    }
}

    if (empty($errors)) {
        $imagesJson = json_encode($uploadedImages);
        $termsJson  = json_encode($terms);

        $stmt = $conn->prepare("
            UPDATE listingtbl 
            SET listingName=?, address=?, barangay=?, price=?, rooms=?, category=?, listingDesc=?, terms=?, images=?, latitude=?, longitude=?
            WHERE ID=?
        ");

        $stmt->bind_param(
            "sssiissssddi",
            $listingName,
            $address,
            $barangay,
            $price,
            $rooms,
            $category,
            $listingDesc,
            $termsJson,
            $imagesJson,
            $latitude,
            $longitude,
            $ID
        );

        if ($stmt->execute()) {
            $_SESSION['success'] = "Property updated successfully!";
            header("Location: landlord-properties.php");
            exit;
        } else {
            $errors[] = "Database error: " . $stmt->error;
        }
        $stmt->close();
    }
}

// Pre-load existing terms for the form
$existingTerms = json_decode($property['terms'] ?? '[]', true) ?? [];
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="shortcut icon" href="favicon.ico" type="image/x-icon">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/7.0.1/css/all.min.css"
        integrity="sha512-2SwdPD6INVrV/lHTZbO2nodKhrnDdJK9/kg2XD1r9uGqPo1cUbujc+IYdlYdEErWNu69gVcYgdxlmVmzTWnetw=="
        crossorigin="anonymous" referrerpolicy="no-referrer" />
    <link rel="stylesheet" href="../css/bootstrap.min.css">
    <link rel="stylesheet" href="../css/style.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <title>EDIT PROPERTIES</title>
    <style>
        .landlord-page {
            margin: 140px 0px 80px 0px !important;
        }

        .form-container {
            background-color: var(--bg-color);
            padding: 30px;
            border-radius: 20px;
            box-shadow: 2px 2px 10px rgba(0, 0, 0, 0.165);
        }

        input:focus,
        select:focus,
        textarea:focus {
            border: 2px solid var(--main-color) !important;
            background: var(--bg-alt-color) !important;
            outline: none !important;
            box-shadow: none !important;
        }

        .form-control {
            background: var(--bg-alt-color);
        }

        textarea {
            height: 120px;
            resize: vertical;
        }

        #map {
            height: 300px;
            padding: 0 !important;
            margin: auto;
            border-radius: 10px;
        }

        /* ── House Rules Section ── */
        .rules-section {
            background: var(--bg-alt-color);
            border: 1px solid rgba(0,0,0,0.08);
            border-radius: 12px;
            padding: 18px 20px;
        }

        .rules-section .section-label {
            font-weight: 600;
            font-size: 0.95rem;
            color: var(--main-color);
            margin-bottom: 12px;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .rule-row {
            display: flex;
            align-items: center;
            gap: 8px;
            margin-bottom: 10px;
            animation: slideIn 0.2s ease;
        }

        @keyframes slideIn {
            from { opacity: 0; transform: translateY(-6px); }
            to   { opacity: 1; transform: translateY(0); }
        }

        .rule-row .form-control {
            border-radius: 8px;
        }

        .rule-row .remove-rule-btn {
            flex-shrink: 0;
            width: 36px;
            height: 36px;
            border-radius: 8px;
            border: 1.5px solid #dc3545;
            background: transparent;
            color: #dc3545;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: all 0.2s ease;
            font-size: 0.85rem;
        }

        .rule-row .remove-rule-btn:hover {
            background: #dc3545;
            color: #fff;
        }

        .add-rule-btn {
            background: transparent;
            border: 1.5px dashed var(--main-color);
            color: var(--main-color);
            border-radius: 8px;
            padding: 6px 16px;
            font-size: 0.88rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s ease;
            display: inline-flex;
            align-items: center;
            gap: 6px;
        }

        .add-rule-btn:hover {
            background: var(--main-color);
            color: #fff;
            border-style: solid;
        }

        .rules-hint {
            font-size: 0.8rem;
            color: #888;
            margin-top: 8px;
        }
    </style>
</head>

<body>
    <!-- HEADER -->
    <?php include '../Components/landlord-header.php'; ?>

    <?php if (!empty($errors)): ?>
        <script>
            document.addEventListener('DOMContentLoaded', function () {
                Swal.fire({
                    icon: 'error',
                    title: 'Please fix the following:',
                    html: `<ul style="text-align:left"><?php foreach ($errors as $e): ?><li><?= htmlspecialchars($e) ?></li><?php endforeach; ?></ul>`,
                    confirmButtonColor: 'var(--main-color)'
                });
            });
        </script>
    <?php endif; ?>

    <!-- PROPERTY PAGE -->
    <div class="landlord-page">
        <div class="container m-auto">
            <div class="d-flex justify-content-between mb-3">
                <h2>Edit Property</h2>
                <form method="POST" action="delete-property.php"
                    onsubmit="return confirm('Are you sure you want to delete this property?');">
                    <input type="hidden" name="id" value="<?= $property['ID']; ?>">
                    <button type="submit" class="main-button">Delete Property</button>
                </form>
            </div>

            <div class="row gy-4 justify-content-center">
                <div class="col-6">
                    <form method="POST" enctype="multipart/form-data" class="form-container">

                        <!-- Listing Name -->
                        <div class="row mb-1">
                            <div class="col">
                                <label class="form-label">Listing Name</label>
                                <input type="text" name="listingName" class="form-control"
                                    value="<?= htmlspecialchars($property['listingName']) ?>" required>
                            </div>
                        </div>

                        <!-- Address -->
                        <div class="row mb-1">
                            <div class="col">
                                <label class="form-label">Address</label>
                                <input type="text" name="address" class="form-control"
                                    value="<?= htmlspecialchars($property['address']) ?>" required>
                            </div>
                        </div>

                        <!-- Barangay / City / Province -->
                        <div class="row mb-1">
                            <div class="col">
                                <label class="form-label">Barangay</label>
                                <select name="barangay" class="form-control" required>
                                    <option value="" disabled>Select Barangay</option>
                                    <?php
                                    $barangays = [
                                        "Bagong Silang","Calendola","Chrysanthemum","Cuyab","Estrella","Fatima",
                                        "G.S.I.S.","Landayan","Langgam","Laram","Magsaysay","Maharlika","Narra",
                                        "Nueva","Pacita 1","Pacita 2","Poblacion","Riverside","Rosario",
                                        "Sampaguita Village","San Antonio","San Roque","San Vicente",
                                        "San Lorenzo Ruiz","Santo Niño","United Bayanihan","United Better Living"
                                    ];
                                    foreach ($barangays as $b):
                                        $sel = $property['barangay'] === $b ? 'selected' : '';
                                    ?>
                                        <option value="<?= $b ?>" <?= $sel ?>><?= $b ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col">
                                <label class="form-label">City</label>
                                <input type="text" class="form-control" value="San Pedro" readonly disabled>
                            </div>
                            <div class="col">
                                <label class="form-label">Province</label>
                                <input type="text" class="form-control" value="Laguna" readonly disabled>
                            </div>
                        </div>

                        <!-- Price / Rooms / Category -->
                        <div class="row mb-1">
                            <div class="col">
                                <label class="form-label">Price</label>
                                <div style="position: relative;">
                                    <span style="position: absolute; left: 10px; top: 50%; transform: translateY(-50%);">₱</span>
                                    <input type="text" name="price" class="form-control" placeholder="0.00"
                                        style="padding-left: 25px;" value="<?= $property['price'] ?>" required>
                                </div>
                            </div>
                            <div class="col">
                                <label class="form-label">No. of Rooms</label>
                                <select name="rooms" class="form-control" required>
                                    <option value="0" <?= $property['rooms'] == 0 ? 'selected' : '' ?>>Studio Type</option>
                                    <option value="1" <?= $property['rooms'] == 1 ? 'selected' : '' ?>>1 Bedroom</option>
                                    <option value="2" <?= $property['rooms'] == 2 ? 'selected' : '' ?>>2 Bedrooms</option>
                                    <option value="3" <?= $property['rooms'] == 3 ? 'selected' : '' ?>>3 Bedrooms</option>
                                    <option value="4" <?= $property['rooms'] == 4 ? 'selected' : '' ?>>4 Bedrooms</option>
                                    <option value="5" <?= $property['rooms'] == 5 ? 'selected' : '' ?>>5+ Bedrooms</option>
                                </select>
                            </div>
                            <div class="col">
                                <label class="form-label">Category</label>
                                <select name="category" class="form-control" required>
                                    <?php
                                    $categories = [
                                        "Condominium","Apartment complex","Single-family home","Studio Unit",
                                        "Boarding House","Commercial Residential","Townhouse",
                                        "Low-rise apartment","High-rise apartment"
                                    ];
                                    foreach ($categories as $cat):
                                        $sel = $property['category'] === $cat ? 'selected' : '';
                                    ?>
                                        <option value="<?= $cat ?>" <?= $sel ?>><?= $cat ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>

                        <!-- Description + Images -->
                        <div class="row mb-1">
                            <div class="col">
                                <label class="form-label">Description</label>
                                <textarea name="description" class="form-control"><?= htmlspecialchars($property['listingDesc']) ?></textarea>
                            </div>
                            <div class="col">
                                <label class="form-label">Upload New Images (optional)</label>
                                <input type="file" name="image[]" class="form-control" accept="image/*" multiple>

                                <p class="mt-2 mb-1"><small>Current Images:</small></p>
                                <div class="d-flex flex-wrap gap-2">
                                    <?php foreach (json_decode($property['images'], true) ?? [] as $img): ?>
                                        <div style="text-align:center;">
                                            <img src="uploads/<?= htmlspecialchars($img) ?>" alt="property image"
                                                width="70" style="border-radius:6px; display:block; margin-bottom:4px;">
                                            <label style="font-size:0.78rem;">
                                                <input type="checkbox" name="remove_images[]" value="<?= htmlspecialchars($img) ?>">
                                                Remove
                                            </label>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        </div>

                        <!-- ── HOUSE RULES / TERMS ── -->
                        <div class="row mb-3">
                            <div class="col">
                                <div class="rules-section">
                                    <div class="section-label">
                                        <i class="fa-solid fa-clipboard-list"></i>
                                        House Rules &amp; Terms
                                    </div>
                                    <div id="rulesContainer">
                                        <?php if (!empty($existingTerms)): ?>
                                            <?php foreach ($existingTerms as $term): ?>
                                                <div class="rule-row">
                                                    <input type="text" name="terms[]" class="form-control"
                                                        value="<?= htmlspecialchars($term) ?>"
                                                        placeholder="e.g. No pets allowed">
                                                    <button type="button" class="remove-rule-btn" title="Remove rule">
                                                        <i class="fa-solid fa-xmark"></i>
                                                    </button>
                                                </div>
                                            <?php endforeach; ?>
                                        <?php else: ?>
                                            <div class="rule-row">
                                                <input type="text" name="terms[]" class="form-control"
                                                    placeholder="e.g. No pets allowed">
                                                <button type="button" class="remove-rule-btn" title="Remove rule">
                                                    <i class="fa-solid fa-xmark"></i>
                                                </button>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                    <button type="button" class="add-rule-btn mt-1" id="addRuleBtn">
                                        <i class="fa-solid fa-plus"></i> Add Rule
                                    </button>
                                    <p class="rules-hint">
                                        <i class="fa-solid fa-circle-info"></i>
                                        Specify occupancy limits, pet policies, curfews, visitor rules, etc.
                                        Tenants will see these before applying.
                                    </p>
                                </div>
                            </div>
                        </div>

                        <!-- Map -->
                        <div class="row mb-3">
                            <div class="col">
                                <label>Pin Location</label>
                                <div id="map"></div>
                                <input type="hidden" name="latitude" id="latitude" value="<?= $property['latitude'] ?>">
                                <input type="hidden" name="longitude" id="longitude" value="<?= $property['longitude'] ?>">
                            </div>
                        </div>

                        <!-- Buttons -->
                        <div class="mb-3">
                            <button type="submit" class="main-button mx-2">Update property</button>
                            <button type="button" class="main-button"
                                onclick="location.href='landlord-properties.php'">Cancel</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script src="../js/script.js" defer></script>
    <script src="../js/bootstrap.bundle.min.js"></script>
    <script src="https://unpkg.com/scrollreveal"></script>

    <!-- Google Maps -->
    <script>
        function initMap() {
            var lat = parseFloat(document.getElementById('latitude').value) || 14.3647;
            var lng = parseFloat(document.getElementById('longitude').value) || 121.0556;

            var map = new google.maps.Map(document.getElementById('map'), {
                center: { lat: lat, lng: lng },
                zoom: 15
            });

            var marker = new google.maps.Marker({
                position: { lat: lat, lng: lng },
                map: map,
                draggable: true
            });

            marker.addListener('dragend', function (event) {
                document.getElementById('latitude').value  = event.latLng.lat();
                document.getElementById('longitude').value = event.latLng.lng();
            });

            map.addListener('click', function (event) {
                var clickedLocation = event.latLng;
                marker.setPosition(clickedLocation);
                document.getElementById('latitude').value  = clickedLocation.lat();
                document.getElementById('longitude').value = clickedLocation.lng();
            });
        }
    </script>
    <script async defer src="https://maps.googleapis.com/maps/api/js?key=AIzaSyCZ3GIqm75W_KKyz1dfW_Pvjw1PeJDpEJU&callback=initMap"></script>

    <!-- House Rules JS -->
    <script>
        const rulesContainer = document.getElementById('rulesContainer');

        document.getElementById('addRuleBtn').addEventListener('click', function () {
            const row = document.createElement('div');
            row.className = 'rule-row';
            row.innerHTML = `
                <input type="text" name="terms[]" class="form-control"
                       placeholder="e.g. Maximum 5 occupants">
                <button type="button" class="remove-rule-btn" title="Remove rule">
                    <i class="fa-solid fa-xmark"></i>
                </button>`;
            rulesContainer.appendChild(row);
            row.querySelector('input').focus();
        });

        rulesContainer.addEventListener('click', function (e) {
            const btn = e.target.closest('.remove-rule-btn');
            if (!btn) return;
            const rows = rulesContainer.querySelectorAll('.rule-row');
            if (rows.length === 1) {
                rows[0].querySelector('input').value = '';
                rows[0].querySelector('input').focus();
            } else {
                btn.closest('.rule-row').remove();
            }
        });
    </script>
</body>
</html>