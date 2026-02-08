<?php
require_once '../connection.php';
include '../session_auth.php';

$landlord_id = $_SESSION['landlord_id'] ?? 0;
if ($landlord_id <= 0) {
    die("Unauthorized access. Please log in as landlord.");
}

$sql = "
    SELECT ID AS listing_id, listingName, latitude, longitude, price
    FROM listingtbl 
    WHERE landlord_id = ? 
      AND latitude IS NOT NULL 
      AND longitude IS NOT NULL
";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $landlord_id);
$stmt->execute();
$result = $stmt->get_result();

$listings = [];
while ($row = $result->fetch_assoc()) {
    $listings[] = $row;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Landlord <?= htmlspecialchars(ucwords(strtolower($_SESSION['username']))); ?></title>

    <link rel="stylesheet" href="../css/bootstrap.min.css">
    <link rel="stylesheet" href="../css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/7.0.1/css/all.min.css"
        crossorigin="anonymous" />

    <style>
        .landlord-page {
            margin-top: 140px !important;
        }

        #map {
            height: 650px;
            width: 100%;
            border: 2px solid var(--main-color);
            border-radius: 10px;
            position: relative;
        }

        .small-button {
            padding: 4px 10px;
            font-size: 0.85rem;
            cursor: pointer;
            border: none;
            border-radius: 5px;
            background-color: var(--main-color);
            color: white;
        }

        .map-legend {
            background: #fff;
            padding: 10px;
            border-radius: 6px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.2);
        }

        .legend-item {
            display: flex;
            align-items: center;
            font-size: 14px;
            margin-bottom: 4px;
        }

        .legend-color {
            width: 18px;
            height: 18px;
            margin-right: 6px;
        }
    </style>
</head>

<body>

    <?php include '../Components/landlord-header.php'; ?>

    <div class="landlord-page container">
        <h1>Featured Map</h1>
        <p>Manage your properties and view hazard and proximity data.</p>
        <div id="map"></div>
    </div>

    <?php include '../Components/footer.php'; ?>

    <script src="../js/bootstrap.bundle.min.js"></script>
    <script src="https://unpkg.com/scrollreveal"></script>

    <script>
        const listings = <?= json_encode($listings); ?>;

        // Hospitals & Evacuation Centers coordinates (approximate central markers)
        const hospitals = [
            { name: "San Pedro Doctors Hospital", lat: 14.3579, lng: 121.0585 },
            { name: "Jose L. Amante Emergency Hospital", lat: 14.3505, lng: 121.0580 },
            { name: "Gavino Alvarez Lying‑In Center", lat: 14.3530, lng: 121.0500 },
            { name: "Divine Mercy Hospital", lat: 14.3590, lng: 121.0599 },
            { name: "Westlake Medical Center", lat: 14.3592, lng: 121.0535 },
            { name: "Evangelista Medical Specialty Hospital", lat: 14.3635, lng: 121.0518 },
            { name: "Family Care Hospital", lat: 14.3630, lng: 121.0505 }
        ];

        const evacCenters = [
            { name: "Landayan Evacuation Center", lat: 14.3578, lng: 121.0597 },
            { name: "Rosario Evacuation Center", lat: 14.3503, lng: 121.0573 },
            { name: "San Roque Evacuation Point", lat: 14.3500, lng: 121.0620 },
            { name: "Poblacion Evacuation Point", lat: 14.3615, lng: 121.0560 },
            { name: "Cuyab Evacuation Zone", lat: 14.3440, lng: 121.0640 }
        ];

        function getPriceColor(price) {
            if (price <= 10000) return "green";
            if (price <= 20000) return "orange";
            return "purple";
        }

        function initMap() {
            const defaultCenter = { lat: 14.3647, lng: 121.0556 };
            const map = new google.maps.Map(document.getElementById("map"), {
                center: defaultCenter,
                zoom: 13,
            });

            const bounds = new google.maps.LatLngBounds();
            const distanceService = new google.maps.DistanceMatrixService();

            // Search bar
            const searchDiv = document.createElement("div");
            searchDiv.style.padding = "8px";
            searchDiv.innerHTML = `
        <input id="proximitySearch" class="form-control" type="text" placeholder="Enter location to check distance/time">
        <button id="proximityBtn" class="btn btn-primary mt-1">Check</button>
    `;
            map.controls[google.maps.ControlPosition.TOP_LEFT].push(searchDiv);

            // Legend
            const legendDiv = document.createElement("div");
            legendDiv.classList.add("map-legend");
            legendDiv.innerHTML = `
    <div class="legend-item">
        <span class="legend-color" style="background:green;"></span>
        Budget-friendly ≤ ₱10k
    </div>

    <div class="legend-item">
        <span class="legend-color" style="background:orange;"></span>
        Comfortable ₱10k–20k
    </div>

    <div class="legend-item">
        <span class="legend-color" style="background:purple;"></span>
        Premium > ₱20k
    </div>

    <div class="legend-item">
        <span class="legend-color" style="background:red;"></span>
        Flood-prone Area
    </div>

    <div class="legend-item">
        <span class="legend-color" style="background:red; height:4px;"></span>
        Fault Line
    </div>

    <div class="legend-item">
        <img src="https://maps.google.com/mapfiles/ms/icons/hospitals.png"
             style="width:18px;height:18px;margin-right:6px;">
        Hospital
    </div>

    <div class="legend-item">
        <img src="https://maps.google.com/mapfiles/ms/icons/blue-pushpin.png"
             style="width:18px;height:18px;margin-right:6px;">
        Evacuation Center
    </div>
`;

            map.controls[google.maps.ControlPosition.LEFT_TOP].push(legendDiv);

            // Plot property markers
            listings.forEach(item => {
                const lat = parseFloat(item.latitude);
                const lng = parseFloat(item.longitude);
                if (!lat || !lng) return;

                const pos = { lat, lng };
                const marker = new google.maps.Marker({
                    position: pos,
                    map: map,
                    icon: `http://maps.google.com/mapfiles/ms/icons/${getPriceColor(item.price)}-dot.png`,
                    title: item.listingName
                });

                const infoWindow = new google.maps.InfoWindow({ content: "Calculating…" });
                item.infoWindow = infoWindow;

                marker.addListener("click", () => infoWindow.open(map, marker));
                bounds.extend(pos);
            });

             // ---------------- HOSPITAL MARKERS ----------------
hospitals.forEach(h => {
    new google.maps.Marker({
        position: { lat: h.lat, lng: h.lng },
        map: map,
        title: h.name,
        icon: {
            url: "https://maps.google.com/mapfiles/ms/icons/hospitals.png",
            scaledSize: new google.maps.Size(32, 32)
        }
    });
    bounds.extend(new google.maps.LatLng(h.lat, h.lng));
});

    
// ---------------- EVACUATION MARKERS ----------------
evacCenters.forEach(e => {
    new google.maps.Marker({
        position: { lat: e.lat, lng: e.lng },
        map: map,
        title: e.name,
        icon: {
            url: "https://maps.google.com/mapfiles/ms/icons/blue-pushpin.png",
            scaledSize: new google.maps.Size(32, 32)
        }
    });
    bounds.extend(new google.maps.LatLng(e.lat, e.lng));
});

            // Approximate flood polygons for each barangay (Level A)
            const floodPolygons = {
                "Landayan": [
                    { lat: 14.3577, lng: 121.0710 }, { lat: 14.3555, lng: 121.0695 },
                    { lat: 14.3540, lng: 121.0640 }, { lat: 14.3523, lng: 121.0607 },
                    { lat: 14.3508, lng: 121.0589 }
                ],
                "Poblacion": [
                    { lat: 14.3610, lng: 121.0545 }, { lat: 14.3600, lng: 121.0510 },
                    { lat: 14.3580, lng: 121.0495 }, { lat: 14.3570, lng: 121.0525 }
                ],
                "SanRoque": [
                    { lat: 14.3525, lng: 121.0625 }, { lat: 14.3515, lng: 121.0595 },
                    { lat: 14.3500, lng: 121.0580 }, { lat: 14.3490, lng: 121.0610 }
                ],
                "StoNino": [
                    { lat: 14.3495, lng: 121.0590 }, { lat: 14.3480, lng: 121.0565 },
                    { lat: 14.3470, lng: 121.0580 }, { lat: 14.3485, lng: 121.0610 }
                ],
                "Cuyab": [
                    { lat: 14.3455, lng: 121.0630 }, { lat: 14.3445, lng: 121.0595 },
                    { lat: 14.3430, lng: 121.0580 }, { lat: 14.3420, lng: 121.0610 }
                ]
            };

            Object.values(floodPolygons).forEach(coords => {
                new google.maps.Polygon({
                    paths: coords,
                    strokeColor: "red",
                    strokeOpacity: 0.7,
                    strokeWeight: 2,
                    fillColor: "red",
                    fillOpacity: 0.25,
                    map: map
                });
            });

            // Fault line (approximate West Valley Fault segment)
            const faultLine = new google.maps.Polyline({
                path: [
                    { lat: 14.3635, lng: 121.0520 },
                    { lat: 14.3620, lng: 121.0570 },
                    { lat: 14.3610, lng: 121.0620 },
                    { lat: 14.3580, lng: 121.0650 }
                ],
                map: map,
                strokeColor: "orange",
                strokeOpacity: 0.8,
                strokeWeight: 3
            });

            map.fitBounds(bounds);

            function updateDistance(origin) {
                listings.forEach(item => {
                    const lat = parseFloat(item.latitude);
                    const lng = parseFloat(item.longitude);
                    distanceService.getDistanceMatrix({
                        origins: [origin],
                        destinations: [{ lat, lng }],
                        travelMode: google.maps.TravelMode.DRIVING,
                        unitSystem: google.maps.UnitSystem.METRIC
                    }, (response, status) => {
                        let distText = "";
                        if (status === "OK") {
                            const e = response.rows[0].elements[0];
                            if (e.status === "OK") distText = `Distance: ${e.distance.text} (${e.duration.text})`;
                        }
                        item.infoWindow.setContent(`
                    <div style="text-align:center;">
                        <strong>${item.listingName}</strong><br>
                        Price: ₱${parseFloat(item.price).toLocaleString()}<br>
                        ${distText}<br>
                        <button class="small-button"
                            onclick="window.location.href='property-details.php?ID=${item.listing_id}'">
                            View
                        </button><br>
                        <button class="small-button mt-1"
                            onclick="window.open('https://www.google.com/maps/dir/?api=1&origin=${origin.lat()},${origin.lng()}&destination=${lat},${lng}','_blank')">
                            Directions
                        </button>
                    </div>
                `);
                    });
                });
            }

            updateDistance(new google.maps.LatLng(defaultCenter.lat, defaultCenter.lng));

            document.getElementById("proximityBtn").addEventListener("click", () => {

                const addr = document.getElementById("proximitySearch").value;
                if (!addr) return;
                const geocoder = new google.maps.Geocoder();
                geocoder.geocode({ address: addr }, (results, status) => {
                    if (status === "OK" && results[0]) {
                        updateDistance(results[0].geometry.location);
                        map.setCenter(results[0].geometry.location);
                        map.setZoom(13);
                    } else alert("Location not found.");
                });
            });
        }
    </script>

    <script src="https://maps.googleapis.com/maps/api/js?key=AIzaSyDWEGYpvzU62c47VL2_FCiMCtlNRk7VKl4&callback=initMap"
        async></script>
</body>

</html>
