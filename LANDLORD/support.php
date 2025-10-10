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
    <?php include '../Components/landlord-header.php'; ?>


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
                                <p class="fs-6 mb-0">Phone number</p>
                                <p class="fs-6 mb-0">0912315782</p>
                            </div>
                            <div class="support-contact text-center">
                                <i class="fa-solid fa-location-dot"></i>
                                <p class="fs-6 mb-0">Phone number</p>
                                <p class="fs-6 mb-0">0912315782</p>
                            </div>
                            <div class="support-contact text-center">
                                <i class="fa-solid fa-clock"></i>
                                <p class="fs-6 mb-0">Phone number</p>
                                <p class="fs-6 mb-0">0912315782</p>
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


<!-- MAIN JS -->
<script src="../js/script.js" defer></script>
<!-- BS JS -->
<script src="../js/bootstrap.bundle.min.js"></script>
<!-- SCROLL REVEAL -->
<script src="https://unpkg.com/scrollreveal"></script>
<!-- LEAFLET JS -->
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo=" crossorigin=""></script>
<script>
    // Initialize map centered at Colegio de San Pedro
    var map = L.map('map').setView([14.3476602, 121.0594527], 17); // zoomed in closer

    // Tile layer (OpenStreetMap)
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '&copy; OpenStreetMap contributors'
    }).addTo(map);

    // Fixed marker at Colegio de San Pedro
    var marker = L.marker([14.3476602, 121.0594527]).addTo(map)
        .bindPopup("<b>Colegio de San Pedro</b>").openPopup();


    document.getElementById("contact-form").addEventListener("submit", async function(e) {
        e.preventDefault();

        const formData = new FormData(this);

        Swal.fire({
            title: 'Sending...',
            text: 'Please wait while we send your message.',
            allowOutsideClick: false,
            didOpen: () => {
                Swal.showLoading();
            }
        });

        try {
            const response = await fetch("https://api.web3forms.com/submit", {
                method: "POST",
                body: formData
            });

            const result = await response.json();

            if (result.success) {
                Swal.fire({
                    icon: 'success',
                    title: 'Message Sent!',
                    text: 'Your email was sent successfully.',
                    confirmButtonColor: '#4caf50'
                });
                this.reset(); // Clear the form
            } else {
                Swal.fire({
                    icon: 'error',
                    title: 'Oops...',
                    text: 'Something went wrong: ' + result.message
                });
            }
        } catch (error) {
            Swal.fire({
                icon: 'error',
                title: 'Network Error',
                text: 'Unable to send your message. Please try again later.'
            });
        }
    });
</script>

</html>