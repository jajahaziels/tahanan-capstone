<?php
require_once '../connection.php';
include '../session_auth.php';

$errors = [];
$success = "";

$landlord_id = $_SESSION['landlord_id'] ?? null;

if ($landlord_id) {
    $sql = "SELECT verification_status FROM landlordtbl WHERE ID = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $landlord_id);
    $stmt->execute();
    $stmt->bind_result($status);
    $stmt->fetch();
    $stmt->close();

    if ($status !== 'verified') {
        $_SESSION['error'] = "❌ You must be verified before posting a property. Please upload your ID first.";
        header("Location: landlord-verification.php");
        exit;
    }
} else {
    $errors[] = "Landlord is not logged in.";
}

if ($_SERVER["REQUEST_METHOD"] === "POST" && empty($errors)) {
    $listingName  = trim($_POST['listing_name'] ?? '');
    $address      = trim($_POST['address'] ?? '');
    $barangay     = $_POST['barangay'] ?? '';
    $price        = (float)($_POST['price'] ?? 0);
    $rooms        = (int)($_POST['rooms'] ?? 0);
    $category     = $_POST['category'] ?? '';
    $listingDesc  = trim($_POST['description'] ?? '');
    $latitude     = $_POST['latitude'] ?? null;
    $longitude    = $_POST['longitude'] ?? null;

    // House Rules / Terms
    $terms = $_POST['terms'] ?? [];
    $terms = array_values(array_filter(array_map('trim', $terms)));

    if ($listingName === '') $errors[] = "Listing name is required.";
    if ($address === '') $errors[] = "Address is required.";
    if ($barangay === '') $errors[] = "Barangay is required.";
    if ($price <= 0) $errors[] = "Price must be greater than 0.";
    if ($rooms < 0) $errors[] = "Number of rooms is required.";
    if ($category === '') $errors[] = "Category is required.";
    if (empty($latitude) || empty($longitude)) $errors[] = "Location must be pinned on the map.";
    if (empty($_FILES['image']['name'][0])) $errors[] = "At least one image is required.";

    $listingDate = date('Y-m-d H:i:s');
    if ($listingDesc === '') $listingDesc = null;

    $uploadedImages = [];
    if (!empty($_FILES['image']['name'][0])) {
        $targetDir = __DIR__ . "/uploads/";
        if (!is_dir($targetDir)) {
            mkdir($targetDir, 0777, true);
        }

        foreach ($_FILES['image']['name'] as $key => $name) {
            $tmpName = $_FILES['image']['tmp_name'][$key];
            $error   = $_FILES['image']['error'][$key];

            if ($error === UPLOAD_ERR_OK && !empty($tmpName)) {
                $newName    = time() . "_" . uniqid() . "_" . basename($name);
                $targetFile = $targetDir . $newName;

                if (move_uploaded_file($tmpName, $targetFile)) {
                    $uploadedImages[] = $newName;
                } else {
                    $errors[] = "Failed to upload image: $name";
                }
            }
        }
    }

    if (empty($errors)) {
        $imagesJson = json_encode($uploadedImages);
        $termsJson  = json_encode($terms);

        $stmt = $conn->prepare("
            INSERT INTO listingtbl 
            (listingName, address, barangay, price, rooms, category, listingDesc, terms, images, listingDate, latitude, longitude, landlord_id, verification_status)  
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'pending')
        ");

        $stmt->bind_param(
            "sssiisssssddi",
            $listingName,
            $address,
            $barangay,
            $price,
            $rooms,
            $category,
            $listingDesc,
            $termsJson,
            $imagesJson,
            $listingDate,
            $latitude,
            $longitude,
            $landlord_id
        );

        if ($stmt->execute()) {
            $_SESSION['property_submitted'] = true;
            header("Location: " . $_SERVER['PHP_SELF']);
            exit;
        } else {
            $errors[] = "Database error: " . $stmt->error;
        }

        $stmt->close();
    }
}

$showPendingModal = false;
if (isset($_SESSION['property_submitted'])) {
    $showPendingModal = true;
    unset($_SESSION['property_submitted']);
}
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
    <link rel="stylesheet" href="../css/style.css?v=<?= time(); ?>">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <title>ADD PROPERTIES</title>
    <style>
        .landlord-page {
            margin-top: 140px !important;
            margin-bottom: 80px !important;
        }

        form {
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

        /* ── Button Container Alignment ── */
        .button-container {
            display: flex;
            gap: 15px;
            justify-content: flex-start;
            align-items: center;
            margin-top: 20px;
        }

        .button-container .main-button {
            margin: 0 !important;
        }
    </style>
</head>

<body>
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

    <?php if ($showPendingModal): ?>
        <script>
            document.addEventListener('DOMContentLoaded', function () {
                Swal.fire({
                    icon: 'success',
                    title: 'Property Submitted!',
                    text: 'Your property is pending admin approval.',
                    confirmButtonColor: 'var(--main-color)'
                }).then(() => {
                    window.location.href = 'landlord-properties.php';
                });
            });
        </script>
    <?php endif; ?>

    <div class="landlord-page">
        <div class="container m-auto">
            <h2>Add Property</h2>
            <div class="row gy-4 justify-content-center">
                <div class="col-lg-6">
                    <form id="property-form" method="POST" enctype="multipart/form-data" action="add-property.php">

                        <!-- Listing Name -->
                        <div class="row mb-1">
                            <div class="col">
                                <label class="form-label">Listing Name</label>
                                <input type="text" name="listing_name" class="form-control" required>
                            </div>
                        </div>

                        <!-- Address -->
                        <div class="row mb-1">
                            <div class="col">
                                <label class="form-label">Address</label>
                                <input type="text" name="address" class="form-control" required>
                            </div>
                        </div>

                        <!-- Barangay / City / Province -->
                        <div class="row mb-1">
                            <div class="col">
                                <label class="form-label">Barangay</label>
                                <select name="barangay" class="form-control" required>
                                    <option value="" disabled selected>Select Barangay</option>
                                    <option value="Bagong Silang">Bagong Silang</option>
                                    <option value="Calendola">Calendola</option>
                                    <option value="Chrysanthemum">Chrysanthemum</option>
                                    <option value="Cuyab">Cuyab</option>
                                    <option value="Estrella">Estrella</option>
                                    <option value="Fatima">Fatima</option>
                                    <option value="G.S.I.S.">G.S.I.S.</option>
                                    <option value="Landayan">Landayan</option>
                                    <option value="Langgam">Langgam</option>
                                    <option value="Laram">Laram</option>
                                    <option value="Magsaysay">Magsaysay</option>
                                    <option value="Maharlika">Maharlika</option>
                                    <option value="Narra">Narra</option>
                                    <option value="Nueva">Nueva</option>
                                    <option value="Pacita 1">Pacita 1</option>
                                    <option value="Pacita 2">Pacita 2</option>
                                    <option value="Poblacion">Poblacion</option>
                                    <option value="Riverside">Riverside</option>
                                    <option value="Rosario">Rosario</option>
                                    <option value="Sampaguita Village">Sampaguita Village</option>
                                    <option value="San Antonio">San Antonio</option>
                                    <option value="San Roque">San Roque</option>
                                    <option value="San Vicente">San Vicente</option>
                                    <option value="San Lorenzo Ruiz">San Lorenzo Ruiz</option>
                                    <option value="Santo Niño">Santo Niño</option>
                                    <option value="United Bayanihan">United Bayanihan</option>
                                    <option value="United Better Living">United Better Living</option>
                                </select>
                            </div>
                            <div class="col">
                                <label class="form-label">City</label>
                                <input type="text" name="city" class="form-control" value="San Pedro" readonly disabled>
                            </div>
                            <div class="col">
                                <label class="form-label">Province</label>
                                <input type="text" name="province" class="form-control" value="Laguna" readonly disabled>
                            </div>
                        </div>

                        <!-- Price / Rooms / Category -->
                        <div class="row mb-1">
                            <div class="col">
                                <label class="form-label">Price</label>
                                <div style="position: relative;">
                                    <span style="position: absolute; left: 10px; top: 50%; transform: translateY(-50%);">₱</span>
                                    <input type="number" name="price" class="form-control" placeholder="0.00" style="padding-left: 25px;" step="0.01" min="1" required>
                                </div>
                            </div>
                            <div class="col">
                                <label class="form-label">No. of Rooms</label>
                                <select name="rooms" class="form-control" required>
                                    <option value="" disabled selected>Select No. of Rooms</option>
                                    <option value="0">Studio Type</option>
                                    <option value="1">1 Bedroom</option>
                                    <option value="2">2 Bedrooms</option>
                                    <option value="3">3 Bedrooms</option>
                                    <option value="4">4 Bedrooms</option>
                                    <option value="5">5+ Bedrooms</option>
                                </select>
                            </div>
                            <div class="col">
                                <label class="form-label">Category</label>
                                <select name="category" class="form-control" required>
                                    <option value="" disabled selected>Select Category</option>
                                    <option value="Condominium">Condominium</option>
                                    <option value="Apartment complex">Apartment complex</option>
                                    <option value="Single-family home">Single-family home</option>
                                    <option value="Townhouse">Townhouse</option>
                                    <option value="Low-rise apartment">Low-rise apartment</option>
                                    <option value="High-rise apartment">High-rise apartment</option>
                                </select>
                            </div>
                        </div>

                        <!-- Description -->
                        <div class="row mb-1">
                            <div class="col">
                                <label class="form-label">Description</label>
                                <textarea name="description" class="form-control" rows="3"></textarea>
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
                                        <div class="rule-row">
                                            <input type="text" name="terms[]" class="form-control"
                                                placeholder="e.g. No pets allowed">
                                            <button type="button" class="remove-rule-btn" title="Remove rule">
                                                <i class="fa-solid fa-xmark"></i>
                                            </button>
                                        </div>
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

                        <!-- Images -->
                        <div class="row mb-1">
                            <div class="col">
                                <label class="form-label">Images (Select multiple)</label>
                                <input type="file" name="image[]" class="form-control" accept="image/*" multiple required>
                                <small class="text-muted">You can select multiple images at once</small>
                            </div>
                        </div>

                        <!-- Map -->
                        <div class="row mb-3">
                            <div class="col">
                                <label class="form-label">Pin Location on Map</label>
                                <div id="map"></div>
                                <input type="hidden" name="latitude" id="latitude" required>
                                <input type="hidden" name="longitude" id="longitude" required>
                                <small class="text-muted">Click on the map to pin your property location</small>
                            </div>
                        </div>

                        <!-- Buttons -->
                        <div class="button-container">
                            <button type="submit" class="main-button">
                                <i class="fas fa-plus"></i> Submit Property
                            </button>
                            <button type="button" class="main-button" onclick="location.href='landlord-properties.php'">
                                <i class="fas fa-times"></i> Cancel
                            </button>
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
            const defaultLocation = { lat: 14.3647, lng: 121.0556 };
            const map = new google.maps.Map(document.getElementById("map"), {
                zoom: 15,
                center: defaultLocation,
            });
            let marker;
            map.addListener("click", function (event) {
                const lat = event.latLng.lat();
                const lng = event.latLng.lng();
                if (marker) marker.setMap(null);
                marker = new google.maps.Marker({ position: { lat, lng }, map });
                document.getElementById("latitude").value  = lat;
                document.getElementById("longitude").value = lng;
            });
        }
    </script>
    <script async defer src="https://maps.googleapis.com/maps/api/js?key=AIzaSyCZ3GIqm75W_KKyz1dfW_Pvjw1PeJDpEJU&libraries=places&callback=initMap"></script>

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
                // Keep at least one row — just clear the value
                rows[0].querySelector('input').value = '';
                rows[0].querySelector('input').focus();
            } else {
                btn.closest('.rule-row').remove();
            }
        });
    </script>
</body>
</html>