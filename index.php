<?php
require_once 'connection.php';

// Fetch only available listings (not rented)
$sql = "
    SELECT l.*, lt.firstName, lt.lastName, lt.profilePic
    FROM listingtbl AS l
    JOIN landlordtbl AS lt ON l.landlord_id = lt.ID
    WHERE l.ID NOT IN (
        SELECT listing_id FROM renttbl WHERE status = 'approved'
    )
    LIMIT 6
";
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
    <link rel="stylesheet" href="../TAHANAN/css/bootstrap.min.css">
    <!-- MAIN CSS -->
    <link rel="stylesheet" href="../TAHANAN/css/style.css?v=<?= time(); ?>">
    <!-- LEAFLET -->
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY=" crossorigin="" />
    <title>INDEX</title>
    <style>
        #map {
            height: 400px;
            max-width: 800px;
            padding: 0 !important;
            margin: auto;
        }
    </style>
</head>

<body>
    <!-- HEADER -->
    <header>
        <a href="#" class="logo d-flex justify-content-center align-items-center"><img src="img/logo.png" alt="">Tahanan</a>

        <ul class="nav-links">
            <li><a href="#home">Home</a></li>
            <li><a href="#services">Services</a></li>
            <li><a href="#home-listing">Listing</a></li>
            <li><a href="#testimonials">Testimonials</a></li>
            <li><a href="#footer">Contact</a></li>
        </ul>
        <!-- SIGN UP -->
        <div class="nav-icons">
            <i class="fa-solid fa-user"></i>
            <a href="../TAHANAN/LOGIN/login.php">Sign In</a>
            <!-- NAVMENU -->
            <div class="fa-solid fa-bars" id="navmenu"></div>
        </div>
    </header>
    <!-- HOME SECTION -->
    <div class="home" id="home">
        <div class="container d-flex align-items-center justify-content-center vh-100">
            <div class="row justify-content-center">
                <div class="col-lg-10">
                    <div class="row justify-content-center">
                        <div class="col-lg-6 col-sm-12">
                            <h1 class="mb-0">Tahanan</h1>
                            <p>We simplify property life for everyone. Landlords can easily track rent, manage maintenance, and handle leasing. Tenants can submit requests, pay rent online, and access community info—all in one intuitive place.</p>
                            <button class="main-button">Rent Now</button>
                            <button class="main-button mx-2 mb-4"> <a href="#home-listing" class="text-white">Listing</a></button>
                        </div>
                        <div class="col-lg-6 col-sm-12">
                            <img src="../TAHANAN/img/index.png" alt="" width="100%">
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!-- MORE INFO -->
    <section class="more-info" id="services">
        <div class="container m-auto">
            <h3 class="text-center mb-4">Convenient and Efficient House<br>Searching</h3>
            <div class="row gy-5">
                <div class="col-lg-4 col-sm-12 text-center info-animation mb-5">
                    <div class="info-icons d-flex justify-content-center mb-4"><i class="fa-solid fa-comments"></i>
                    </div>
                    <p>Real-Time Communication</p> <br>
                    <p>Directly connect landlords and tenants through an in-app messaging portal. Resolve issues faster and keep a clear record of all exchanges.</p>
                </div>
                <div class="col-lg-4 col-sm-12 text-center info-animation mb-5">
                    <div class="info-icons d-flex justify-content-center mb-4"><i class="fa-solid fa-user-check"></i>
                    </div>
                    <p>Verified and Updated Listing</p> <br>
                    <p>Every listing on Tahanan is vetted for safety and accuracy. View high-quality, up-to-date photos, check transparent pricing, and feel confident in your search.</p>
                </div>
                <div class="col-lg-4 col-sm-12 text-center info-animation mb-5">
                    <div class="info-icons d-flex justify-content-center mb-4"><i class="fa-solid fa-location-dot"></i>
                    </div>
                    <p>Mapping</p> <br>
                    <p>See exactly where your future home is located. Use our interactive map search to filter listings by proximity to work, schools, and transportation hubs.</p>
                </div>
            </div>
        </div>
    </section>
    <!-- HOME PAGE LISTING -->
    <section class="home-listing" id="home-listing">
        <div class="container m-auto">
            <h3 class="mb-4">Available Apartment</h3>
            <i class="fa-regular fa-star"></i>
            Featured Properties <br> <br>
            <!-- START ROW -->
            <div class="row gy-5">
                <?php if ($result && $result->num_rows > 0): ?>
                    <?php while ($row = $result->fetch_assoc()): ?>
                        <div class="col-lg-4 col-sm-12">
                            <div class="cards mb-4" onclick="window.location='property-details.php?id=<?= $row['ID']; ?>'">
                                <div class="position-relative">
                                    <?php
                                    $images = json_decode($row['images'], true);
                                    $imagePath = 'LANDLORD/uploads/placeholder.jpg';
                                    if (!empty($images) && is_array($images) && isset($images[0])) {
                                        $imagePath = 'LANDLORD/uploads/' . $images[0];
                                    }
                                    ?>
                                    <img src="<?= htmlspecialchars($imagePath); ?>"
                                        alt="Property Image"
                                        class="property-img"
                                        style="width:100%; max-height:200px; object-fit:cover;">

                                    <div class="labels">
                                        <div class="label"><i class="fa-regular fa-star"></i> Featured</div>
                                        <div class="label">Specials</div>
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

                                    <div class="divider my-3"></div>

                                    <div class="landlord-info">
                                        <div class="landlord-left d-flex align-items-center">
                                            <?php if (!empty($row['profilePic'])): ?>
                                                <img src="LANDLORD/uploads/<?= htmlspecialchars($row['profilePic']); ?>"
                                                    alt="Landlord"
                                                    style="width:40px; height:40px; border-radius:50%; object-fit:cover;">
                                            <?php else: ?>
                                                <div class="avatar">
                                                    <?= ucwords(substr($row['firstName'], 0, 1)); ?>
                                                </div>
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
                    <p class="text-center mt-4">No available apartments right now.</p>
                <?php endif; ?>
            </div>
            <!-- END NG ROW -->
            <div class="d-flex justify-content-center align-items-center mt-4 button-animation">
                <button class="main-button" onclick="location.href='properties.html'">View More</button>
            </div>
        </div>
    </section>
    <!-- MAP SECTION -->
    <div id="map-section">
        <div class="container m-auto mb-4">
            <h1>Listing Locations</h1>
            <div class="row">
                <div class="col-lg-6 col-sm-12">
                    <?php
                    $sql = "
                        SELECT ID AS listing_id, listingName, latitude, longitude 
                        FROM listingtbl 
                        WHERE latitude IS NOT NULL 
                        AND longitude IS NOT NULL
                        AND ID NOT IN (
                            SELECT listing_id FROM renttbl WHERE status = 'approved'
                        )
                    ";
                    $result = $conn->query($sql);

                    $listings = [];
                    while ($row = $result->fetch_assoc()) {
                        $listings[] = $row;
                    }
                    ?>
                    <!-- Only one actual map div -->
                    <div id="map"></div>
                </div>
                <div class="col-lg-6 col-sm-12">
                    <!-- Only one actual map div -->
                    <h3 class="mb-2">Explore Listings in San Pedro, Laguna</h3>
                    <p>Discover a variety of rental properties in San Pedro, Laguna. Our listings include</p>
                    <div class="d-flex">
                        <ul>
                            <li>Barangay</li>
                            <li>Bagong Silang</li>
                            <li>Calendola</li>
                            <li>Chrysanthemum</li>
                            <li>.....</li>
                        </ul>
                        <ul>
                            <li>Number of Rooms</li>
                            <li>1 Bedroom</li>
                            <li>2 Bedrooms</li>
                            <li>3 Bedrooms</li>
                            <li>4+ Bedrooms</li>
                        </ul>
                        <ul>
                            <li>Category</li>
                            <li>Condominiums</li>
                            <li>Townhouses</li>
                            <li>Houses for Rent</li>
                            <li>....</li>
                        </ul>
                    </div>
                    <p>Each listing provides detailed information, photos, and contact details to help you find
                        your perfect home. Start your search today and explore the best rental options in San Pedro,
                        Laguna!</p>
                </div>
            </div>
        </div>
    </div>
    <!-- TESTIMONIALS SECTION -->
    <section id="testimonials" class="mt-5 testimonials">
        <div class="container m-auto">
            <h3 class="mb-4">Hear From Our Happy Users</h3>
            <div id="carouselExample" class="carousel slide">
                <div class="carousel-inner">
                    <div class="carousel-item active">
                        <div class="row justify-content-center">
                            <div class="col-lg-8">
                                <div class="review text-center p-4">
                                    <i class="fa-regular fa-message mb-4 mt-5"></i>
                                    <p>"This platform has been a game-changer for managing our apartments. Everything is in one place—tenant requests, payments, and updates—so I no longer waste time juggling multiple tools. It keeps everything organized and stress-free."</p>
                                    <div class="person mt-5">
                                        <img src="../TAHANAN/img/mina.jpg" alt="" width="10%">
                                        <h5 class="mt-3">Jonathan Allen Mina</h5>
                                        <div class="stars">
                                            <i class="fa-solid fa-star"></i>
                                            <i class="fa-solid fa-star"></i>
                                            <i class="fa-solid fa-star"></i>
                                            <i class="fa-solid fa-star"></i>
                                            <i class="fa-solid fa-star-half-stroke"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="carousel-item">
                        <div class="row justify-content-center">
                            <div class="col-lg-8">
                                <div class="review text-center p-4">
                                    <i class="fa-regular fa-message mb-4 mt-5"></i>
                                    <p>"I love how easy it is to stay connected with tenants through this platform. From announcements to maintenance updates, communication is clear and efficient. It has improved relationships and boosted tenant satisfaction."</p>
                                    <div class="person mt-5">
                                        <img src="../TAHANAN/img/house1.jpeg" alt="" width="10%">
                                        <h5 class="mt-3">Jahaziel Sison</h5>
                                        <div class="stars">
                                            <i class="fa-solid fa-star"></i>
                                            <i class="fa-solid fa-star"></i>
                                            <i class="fa-solid fa-star"></i>
                                            <i class="fa-solid fa-star"></i>
                                            <i class="fa-solid fa-star-half-stroke"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                    </div>
                    <div class="carousel-item">
                        <div class="row justify-content-center">
                            <div class="col-lg-8">
                                <div class="review text-center p-4">
                                    <i class="fa-regular fa-message mb-4 mt-5"></i>
                                    <p>"Collecting rent used to be a hassle, but now it’s effortless. The platform’s payment system is reliable and secure, making the entire process smooth for both tenants and management. It’s a win-win!"</p>
                                    <div class="person mt-5">
                                        <img src="../TAHANAN/img/sam.png" alt="" width="10%">
                                        <h5 class="mt-3">Salmuel Whyette Alcazar</h5>
                                        <div class="stars">
                                            <i class="fa-solid fa-star"></i>
                                            <i class="fa-solid fa-star"></i>
                                            <i class="fa-solid fa-star"></i>
                                            <i class="fa-solid fa-star"></i>
                                            <i class="fa-solid fa-star-half-stroke"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="carousel-item">
                        <div class="row justify-content-center">
                            <div class="col-lg-8">
                                <div class="review text-center p-4">
                                    <i class="fa-regular fa-message mb-4 mt-5"></i>
                                    <p>"This apartment management platform has elevated the way we operate. It gives off a professional, modern feel that tenants notice and appreciate. It’s not just a tool—it’s part of delivering excellent service."</p>
                                    <div class="person mt-5">
                                        <img src="../TAHANAN/img/gio.jpg" alt="" width="10%">
                                        <h5 class="mt-3">Giorj Allen Gonzales</h5>
                                        <div class="stars">
                                            <i class="fa-solid fa-star"></i>
                                            <i class="fa-solid fa-star"></i>
                                            <i class="fa-solid fa-star"></i>
                                            <i class="fa-solid fa-star"></i>
                                            <i class="fa-solid fa-star-half-stroke"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <button class="carousel-control-prev" type="button" data-bs-target="#carouselExample" data-bs-slide="prev">
                    <span class="carousel-control-prev-icon" aria-hidden="true"></span>
                    <span class="visually-hidden">Previous</span>
                </button>
                <button class="carousel-control-next" type="button" data-bs-target="#carouselExample" data-bs-slide="next">
                    <span class="carousel-control-next-icon" aria-hidden="true"></span>
                    <span class="visually-hidden">Next</span>
                </button>
            </div>

        </div>
    </section>
    <!-- FOOTER -->
    <footer id="footer">
        <div class="container m-auto">
            <div class="footer-top">
                <!-- START NG ROW -->
                <div class="row justify-content-center gy-5">
                    <div class="col-lg-4 col-sm-12">
                        <a href="#" class="logo"><i class="fa-solid fa-house"></i>Tahanan</a>
                        <p>Our platform makes house searching simple and worry-free. You can easily search for verified and secure rental homes or list your own apartment with confidence.</p>
                        <div class="d-flex">
                            <div class="mt-4 mx-1 footer-box"><i class="fa-solid fa-user-check"></i> Verified Listing</div>
                            <div class="mt-4 mx-1 footer-box"><i class="fa-solid fa-users"></i> 3k+ Happy User</div>
                            <div class="mt-4 mx-1 footer-box"><i class="fa-regular fa-star"></i> 3.4 Rating</div>
                        </div>
                        <div class="mt-4">
                            <i class="fa-solid fa-phone mx-1"></i> (+63) 2 8123-4567 <br>
                            <i class="fa-solid fa-envelope mx-1"></i> tahanan@gmail.com <br>
                            <i class="fa-solid fa-location-dot mx-1"></i> Pacita 1, San Pedro, Laguna
                        </div>
                    </div>

                    <div class="col-lg-2 col-sm-12">
                        <div class="lead">For Tenants </div>
                        <div class="mt-4">
                            <p>Browse Rentals</p>
                            <p>Filter Location</p>
                            <p>Verified Listings</p>
                            <p>Location Map</p>
                            <p>Tenant Rights</p>
                            <p>Landlord FAQs</p>
                        </div>
                    </div>

                    <div class="col-lg-2 col-sm-12">
                        <div class="lead">For Landlord</div>
                        <div class="mt-4">
                            <p>List Your Properties</p>
                            <p>Verification Process</p>
                            <p>Update Property Information</p>
                            <p>Rental Reminders & Notifications</p>
                            <p>Landlords FAQs</p>
                        </div>
                    </div>

                    <div class="col-lg-2 col-sm-12">
                        <div class="lead">Company</div>
                        <div class="mt-4">
                            <p>About Us</p>
                            <p>Contact Us</p>
                            <p>Privacy Policy</p>
                            <p>Contact Support</p>
                            <p>Terms and Conditions</p>
                        </div>
                    </div>

                    <div class="col-lg-2 col-sm-12">
                        <div class="lead">Support Hours</div> <br>
                        <p>Mon - Fri: 8:00 AM - 9:00 PM</p>
                        <p>Sat - Sun: 10:00 AM - 5:00 PM</p>
                    </div>

                </div>
                <!-- END NG ROW -->
            </div>
        </div>
        <div class="footer-bottom mt-4">
            <div class="container m-auto">
                <div class="d-flex justify-content-center align-items-center pt-3">
                    <p>All Rights Reserved</p>
                </div>
            </div>
        </div>
    </footer>
</body>
<!-- MAIN JS -->
<script src="js/script.js?v=<?php echo time(); ?>" defer></script>
<!-- BS JS -->
<script src="js/bootstrap.bundle.min.js"></script>
<!-- SCROLL REVEAL -->
<script src="https://unpkg.com/scrollreveal"></script>
<!-- LEAFLET JS -->
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo=" crossorigin=""></script>
<script>
    // Pass PHP array to JS as JSON
    var listings = <?= json_encode($listings); ?>;

    // Default center (if no data)
    var map = L.map('map').setView([14.3647, 121.0556], 13);

    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '&copy; OpenStreetMap contributors'
    }).addTo(map);

    listings.forEach(function(item) {
        if (item.latitude && item.longitude) {
            // create popup content with a button
            var popupContent = `
            <button class="small-button" onclick="window.location.href='property-details.php?id=${item.listing_id}'">
                View
            </button>
        `;

            L.marker([item.latitude, item.longitude])
                .addTo(map)
                .bindPopup(popupContent);
        }
    });
</script>

</html>