<?php
require_once '../connection.php';
include '../session_auth.php';

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
    <!-- LEAFLET -->
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY=" crossorigin="" />
    <!-- SWEET ALERT -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <title>SUPPORT</title>
    <style>
        .landlord-page {
            margin-top: 140px !important;
        }

        form {
            background-color: var(--bg-color);
            padding: 20px;
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
            height: 80px;
        }

        .grid-icons {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            grid-template-rows: repeat(2, auto);
            gap: 20px;
            justify-items: center;
            align-items: center;
            font-size: 24px;
        }

        #map {
            height: 300px;
            padding: 0;
            margin: auto;
            border-radius: 20px;
        }
    </style>
</head>

<!-- HEADER -->
<?php include '../Components/landlord-header.php' ?>

<div class="landlord-page">
    <div class="container m-auto">
        <h1>Customer Support</h1>
        <div class="row justify-content-center">
            <div class="col-lg-8">
                <div class="row justify-content-center support-container mt-4">
                    <div class="col-lg-6 p-4">
                        <form method="POST" enctype="multipart/form-data" action="https://api.web3forms.com/submit" id="contact-form">
                            <input type="hidden" name="access_key" value="13d1a21a-6274-4f2e-bdbb-525a612af4d5">
                            <h3>Contact Us</h3>
                            <div class="row mb-3">
                                <div class="col">
                                    <input type="text" name="name" class="form-control" placeholder="Name" required>
                                </div>
                            </div>
                            <div class="row mb-3">
                                <div class="col">
                                    <input type="text" name="email" class="form-control" placeholder="Email" required>
                                </div>
                            </div>
                            <div class="row mb-3">
                                <div class="col">
                                    <input type="text" name="subject" class="form-control" placeholder="Subject" required>
                                </div>
                            </div>

                            <div class="row mb-3">
                                <div class="col">
                                    <textarea name="Message" class="form-control" rows="5" placeholder="Message"></textarea>
                                </div>
                            </div>

                            <div class="grid">
                                <input type="checkbox" name="botcheck" class="hidden" style="display: none;">
                                <button type="submit" class="main-button mx-2">Send</button>
                            </div>
                        </form>
                    </div>

                    <div class="col-lg-6 p-4">
                        <h1>Need Help? We're Here For You.</h1>
                        <p>Whether you have a question about a listing, need help with account access, or require technical support for our platform, our team is ready to assist you.</p>
                        <div class="grid-icons">
                            <div class="support-contact text-center">
                                <i class="fa-solid fa-phone"></i>
                                <p class="fs-6 mb-0">Phone number</p>
                                <p class="fs-6 mb-0">0912315782</p>
                            </div>
                            <div class="support-contact text-center">
                                <i class="fa-solid fa-envelope"></i>
                                <p class="fs-6 mb-0">Email Address</p>
                                <p class="fs-6 mb-0">tahanan@gmail.com</p>
                            </div>
                            <div class="support-contact text-center">
                                <i class="fa-solid fa-location-dot"></i>
                                <p class="fs-6 mb-0">Location</p>
                                <p class="fs-6 mb-0"> Phase 1A, Pacita Complex 1, S.P.L 4023</p>
                            </div>
                            <div class="support-contact text-center">
                                <i class="fa-solid fa-clock"></i>
                                <p class="fs-6 mb-0">Contact Hours</p>
                                <p class="fs-6 mb-0">Monday - Friday: 9AM - 5PM</p>
                            </div>
                    </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="row justify-content-center">
            <div id="map" class="mt-2"></div>
        </div>
    </div>
</div>
    <?php include '../Components/footer.php'; ?>

<!-- MAIN JS -->
<script src="../js/script.js" defer></script>
<!-- BS JS -->
<script src="../js/bootstrap.bundle.min.js"></script>
<!-- SCROLL REVEAL -->
<script src="https://unpkg.com/scrollreveal"></script>
<!-- GOOGLE MAPS -->
<div id="map" class="mt-2" style="height:300px;"></div>

<script>
function initMap() {
    // Centered at Colegio de San Pedro
    const colegio = { lat: 14.3476602, lng: 121.0594527 };
    
    // Initialize map
    const map = new google.maps.Map(document.getElementById("map"), {
        zoom: 17,
        center: colegio
    });

    // Marker
    const marker = new google.maps.Marker({
        position: colegio,
        map: map,
        title: "Colegio de San Pedro"
    });

    // Info window
    const infoWindow = new google.maps.InfoWindow({
        content: "<b>Colegio de San Pedro</b>"
    });

    marker.addListener("click", () => {
        infoWindow.open(map, marker);
    });
}
</script>

<!-- Google Maps API (replace YOUR_API_KEY with your key) -->
<script async
    src="https://maps.googleapis.com/maps/api/js?key=AIzaSyDWEGYpvzU62c47VL2_FCiMCtlNRk7VKl4&callback=initMap">
</script>


</html>