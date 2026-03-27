<?php
    $current_page = basename($_SERVER['PHP_SELF']);
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
        
        <style>
            /* MOBILE RESPONSIVE STYLES */
            @media (max-width: 768px) {
                header {
                    padding: 1rem;
                }

                .nav-links {
                    position: fixed;
                    top: 0;
                    right: -100%;
                    width: 70%;
                    height: 100vh;
                    background: linear-gradient(135deg, #8d0b41 0%, #6a0831 100%);
                    flex-direction: column;
                    align-items: flex-start;
                    padding: 80px 20px 20px;
                    transition: right 0.3s ease;
                    z-index: 999;
                    box-shadow: -5px 0 15px rgba(0, 0, 0, 0.3);
                }

                .nav-links.active {
                    right: 0;
                }

                .nav-links li {
                    width: 100%;
                    margin: 0;
                    padding: 0;
                }

                .nav-links li a {
                    display: block;
                    width: 100%;
                    padding: 15px 20px;
                    color: white !important;
                    border-bottom: 1px solid rgba(255, 255, 255, 0.1);
                }

                .nav-links li a.active {
                    background: rgba(255, 255, 255, 0.1);
                    border-left: 4px solid white;
                }

                #navmenu {
                    display: block !important;
                    font-size: 24px;
                    cursor: pointer;
                    color: #8d0b41;
                }

                .logo {
                    font-size: 18px;
                }

                .logo img {
                    width: 30px;
                    height: 30px;
                }
            }

            @media (min-width: 769px) {
                #navmenu {
                    display: none !important;
                }
            }

            /* SETTINGS MODAL STYLES */
            .settings-modal {
                display: none;
                position: fixed;
                top: 0;
                left: 0;
                width: 100%;
                height: 100%;
                background: rgba(0, 0, 0, 0.7);
                z-index: 10000;
                justify-content: center;
                align-items: center;
                animation: fadeIn 0.3s ease;
            }

            .settings-modal.active {
                display: flex;
            }

            .settings-content {
                background: white;
                border-radius: 20px;
                width: 90%;
                max-width: 600px;
                max-height: 90vh;
                overflow-y: auto;
                box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
                animation: slideUp 0.3s ease;
            }

            @keyframes fadeIn {
                from { opacity: 0; }
                to { opacity: 1; }
            }

            @keyframes slideUp {
                from { transform: translateY(50px); opacity: 0; }
                to { transform: translateY(0); opacity: 1; }
            }

            .settings-header {
                background: linear-gradient(135deg, #8d0b41 0%, #6a0831 100%);
                color: white;
                padding: 24px;
                border-radius: 20px 20px 0 0;
                display: flex;
                justify-content: space-between;
                align-items: center;
            }

            .settings-header h2 {
                margin: 0;
                font-size: 24px;
                font-weight: 600;
            }

            .settings-close {
                background: none;
                border: none;
                color: white;
                font-size: 24px;
                cursor: pointer;
                width: 40px;
                height: 40px;
                border-radius: 50%;
                display: flex;
                align-items: center;
                justify-content: center;
                transition: background 0.2s;
            }

            .settings-close:hover {
                background: rgba(255, 255, 255, 0.2);
            }

            .settings-body {
                padding: 24px;
            }

            .settings-section {
                margin-bottom: 32px;
            }

            .settings-section:last-child {
                margin-bottom: 0;
            }

            .settings-section-title {
                font-size: 14px;
                font-weight: 600;
                color: #8d0b41;
                text-transform: uppercase;
                letter-spacing: 0.5px;
                margin-bottom: 16px;
                display: flex;
                align-items: center;
                gap: 8px;
            }

            .settings-item {
                display: flex;
                justify-content: space-between;
                align-items: center;
                padding: 16px;
                border-radius: 12px;
                background: #f8f9fa;
                margin-bottom: 12px;
                cursor: pointer;
                transition: all 0.2s;
            }

            .settings-item:hover {
                background: #e9ecef;
                transform: translateX(5px);
            }

            .settings-item-info {
                display: flex;
                align-items: center;
                gap: 16px;
            }

            .settings-item-icon {
                width: 40px;
                height: 40px;
                border-radius: 10px;
                background: linear-gradient(135deg, #8d0b41 0%, #6a0831 100%);
                display: flex;
                align-items: center;
                justify-content: center;
                color: white;
                font-size: 18px;
            }

            .settings-item-text h4 {
                margin: 0;
                font-size: 16px;
                font-weight: 600;
                color: #2d3748;
            }

            .settings-item-text p {
                margin: 4px 0 0 0;
                font-size: 13px;
                color: #718096;
            }

            .settings-item-arrow {
                color: #cbd5e0;
                font-size: 18px;
            }

            /* TOGGLE SWITCH */
            .toggle-switch {
                position: relative;
                width: 50px;
                height: 26px;
            }

            .toggle-switch input {
                opacity: 0;
                width: 0;
                height: 0;
            }

            .toggle-slider {
                position: absolute;
                cursor: pointer;
                top: 0;
                left: 0;
                right: 0;
                bottom: 0;
                background-color: #cbd5e0;
                transition: 0.3s;
                border-radius: 34px;
            }

            .toggle-slider:before {
                position: absolute;
                content: "";
                height: 18px;
                width: 18px;
                left: 4px;
                bottom: 4px;
                background-color: white;
                transition: 0.3s;
                border-radius: 50%;
            }

            input:checked + .toggle-slider {
                background: linear-gradient(135deg, #8d0b41 0%, #6a0831 100%);
            }

            input:checked + .toggle-slider:before {
                transform: translateX(24px);
            }

            .password-modal {
                display: none;
                position: fixed;
                top: 0;
                left: 0;
                width: 100%;
                height: 100%;
                background: rgba(0, 0, 0, 0.8);
                z-index: 11000;
                justify-content: center;
                align-items: center;
            }

            .password-modal.active {
                display: flex;
            }

            .password-form-container {
                background: white;
                border-radius: 16px;
                width: 90%;
                max-width: 450px;
                padding: 32px;
                box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
            }

            .password-form-header {
                display: flex;
                justify-content: space-between;
                align-items: center;
                margin-bottom: 24px;
            }

            .password-form-header h3 {
                margin: 0;
                font-size: 22px;
                color: #2d3748;
            }

            .password-close {
                background: none;
                border: none;
                font-size: 24px;
                cursor: pointer;
                color: #718096;
                width: 32px;
                height: 32px;
                display: flex;
                align-items: center;
                justify-content: center;
                border-radius: 50%;
                transition: all 0.2s;
            }

            .password-close:hover {
                background: #f7fafc;
                color: #2d3748;
            }

            .form-group {
                margin-bottom: 20px;
            }

            .form-group label {
                display: block;
                margin-bottom: 8px;
                font-weight: 600;
                color: #2d3748;
                font-size: 14px;
            }

            .form-group input {
                width: 100%;
                padding: 12px 16px;
                border: 2px solid #e2e8f0;
                border-radius: 8px;
                font-size: 14px;
                transition: all 0.2s;
            }

            .form-group input:focus {
                outline: none;
                border-color: #8d0b41;
                box-shadow: 0 0 0 3px rgba(141, 11, 65, 0.1);
            }

            .password-submit-btn {
                width: 100%;
                padding: 14px;
                background: linear-gradient(135deg, #8d0b41 0%, #6a0831 100%);
                color: white;
                border: none;
                border-radius: 8px;
                font-size: 16px;
                font-weight: 600;
                cursor: pointer;
                transition: all 0.2s;
            }

            .password-submit-btn:hover {
                transform: translateY(-2px);
                box-shadow: 0 8px 16px rgba(141, 11, 65, 0.3);
            }

            .password-submit-btn:disabled {
                opacity: 0.6;
                cursor: not-allowed;
            }

            .alert-message {
                padding: 12px 16px;
                border-radius: 8px;
                margin-bottom: 20px;
                font-size: 14px;
            }

            .alert-success {
                background: #d4edda;
                color: #155724;
                border: 1px solid #c3e6cb;
            }

            .alert-error {
                background: #f8d7da;
                color: #721c24;
                border: 1px solid #f5c6cb;
            }

            .terms-modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.8);
            z-index: 11000;
            justify-content: center;
            align-items: center;
        }

        .terms-modal.active {
            display: flex;
        }

        .terms-container {
            background: white;
            border-radius: 16px;
            width: 90%;
            max-width: 800px;
            max-height: 90vh;
            display: flex;
            flex-direction: column;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
        }

        .terms-header {
            background: linear-gradient(135deg, #8d0b41 0%, #6a0831 100%);
            color: white;
            padding: 24px;
            border-radius: 16px 16px 0 0;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .terms-header h3 {
            margin: 0;
            font-size: 22px;
            color: white;
        }

        .terms-close {
            background: none;
            border: none;
            color: white;
            font-size: 24px;
            cursor: pointer;
            width: 40px;
            height: 40px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: background 0.2s;
        }

        .terms-close:hover {
            background: rgba(255, 255, 255, 0.2);
        }

        .terms-content {
            padding: 32px;
            overflow-y: auto;
            flex: 1;
        }

        .terms-content h1 {
            color: #2c3e50;
            border-bottom: 2px solid #ccc;
            padding-bottom: 10px;
            margin-bottom: 20px;
            font-size: 28px;
        }

        .terms-content h2 {
            color: #2c3e50;
            margin-top: 30px;
            margin-bottom: 15px;
            font-size: 20px;
        }

        .terms-content p {
            line-height: 1.7;
            color: #000000;
            margin-bottom: 15px;
        }

        .terms-content ul {
            margin-left: 20px;
            margin-bottom: 15px;
        }

        .terms-content li {
            margin-bottom: 8px;
            line-height: 1.7;
        }

        .terms-content strong {
            color: #2c3e50;
        }

        .terms-footer {
            padding: 20px 32px;
            border-top: 1px solid #e2e8f0;
            text-align: center;
        }

        .terms-accept-btn {
            padding: 12px 32px;
            background: linear-gradient(135deg, #8d0b41 0%, #6a0831 100%);
            color: white;
            border: none;
            border-radius: 8px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s;
        }

        .terms-accept-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 16px rgba(141, 11, 65, 0.3);
        }

            @media (max-width: 768px) {
    header .nav-links {
        position: fixed !important;
        top: 0 !important;
        right: -100% !important;
        width: 70% !important;
        height: 100vh !important;
        background: linear-gradient(135deg, #8d0b41 0%, #6a0831 100%) !important;
        flex-direction: column !important;
        align-items: flex-start !important;
        padding: 80px 20px 20px !important;
        transition: right 0.3s ease !important;
        z-index: 9999 !important;
        box-shadow: -5px 0 15px rgba(0, 0, 0, 0.3) !important;
        display: flex !important;
    }

    header .nav-links.active {
        right: 0 !important;
    }

    header .nav-links li {
        width: 100% !important;
        margin: 0 !important;
        padding: 0 !important;
        list-style: none !important;
    }

    header .nav-links li a {
        display: block !important;
        width: 100% !important;
        padding: 15px 20px !important;
        color: white !important;
        border-bottom: 1px solid rgba(255, 255, 255, 0.1) !important;
        text-decoration: none !important;
    }

    header .nav-links li a.active {
        background: rgba(255, 255, 255, 0.1) !important;
        border-left: 4px solid white !important;
    }

    header #navmenu {
        display: block !important;
        font-size: 24px !important;
        cursor: pointer !important;
        color: #8d0b41 !important;
    }
}
        </style>
    </head>

    <body>
        <!-- HEADER -->
        <header>
            <a href="#" class="logo d-flex justify-content-center align-items-center"><img src="../img/logo.png" alt="">Tahanan</a>

            <ul class="nav-links">
                <li><a href="landlord-properties.php" class="<?= $current_page == 'landlord-properties.php' ? 'active' : '' ?>">Properties</a></li>
                <li><a href="history.php" class="<?= $current_page == 'history.php' ? 'active' : '' ?>">Rentals</a></li>
                <li><a href="landlord-map.php" class="<?= $current_page == 'landlord-map.php' ? 'active' : '' ?>">Map</a></li>
                <li><a href="landlord-message.php" class="<?= $current_page == 'landlord-message.php' ? 'active' : '' ?>">Messages</a></li>
                <li><a href="support.php" class="<?= $current_page == 'support.php' ? 'active' : '' ?>">Support</a></li>
            </ul>
            
            <!-- NAV ICON / NAME -->
            <div class="nav-icons">
                <!-- DROP DOWN -->
                <div class="dropdown">
                    <i class="fa-solid fa-user"></i>
                    <?= htmlspecialchars(ucwords(strtolower($_SESSION['username']))); ?>
                    <div class="dropdown-content">
                        <a href="account.php">Account</a>
                        <a href="#" id="openSettings">Settings</a>
                        <a href="../LOGIN/logout.php">Log out</a>
                    </div>
                </div>
                <!-- NAVMENU -->
                <div class="fa-solid fa-bars" id="navmenu"></div>
            </div>

            <!-- NOTIFICATION -->
            <div class="dropdown">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item dropdown">
                        <a href="#" class="nav-link position-relative" role="button" data-bs-toggle="dropdown">
                            <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger count" style="display:none;">0</span>
                            <i class="fa-solid fa-bell fs-4 bell-icon ps-5"></i>
                        </a>

                        <ul class="dropdown-menu dropdown-menu-end p-0">
                            <li class="dropdown-header d-flex justify-content-between px-3 py-2">
                                <span class="fw-semibold p-3">Notifications</span>
                                <button class="btn btn-sm btn-link text-danger ps-4" id="clearNotifications">Clear all</button>
                            </li>
                            <li><hr class="dropdown-divider m-0"></li>
                            <div id="notificationList">
                                <li>
                                    <span class="dropdown-item text-muted text-center py-3">No notifications</span>
                                </li>
                            </div>
                        </ul>
                    </li>
                </ul>
            </div>
        </header>

        <!-- SETTINGS MODAL -->
        <div class="settings-modal" id="settingsModal">
            <div class="settings-content">
                <div class="settings-header">
                    <h2><i class="fa-solid fa-gear me-2"></i> Settings</h2>
                    <button class="settings-close" id="closeSettings">
                        <i class="fa-solid fa-times"></i>
                    </button>
                </div>
                
                <div class="settings-body">
                
                    <!-- ACCOUNT SECTION -->
                    <div class="settings-section">
                        <div class="settings-section-title">
                            <i class="fa-solid fa-user"></i> Account
                        </div>
                        
                        <div class="settings-item" onclick="window.location.href='account.php'">
                            <div class="settings-item-info">
                                <div class="settings-item-icon">
                                    <i class="fa-solid fa-id-card"></i>
                                </div>
                                <div class="settings-item-text">
                                    <h4>View Account</h4>
                                    <p>See your profile information</p>
                                </div>
                            </div>
                            <i class="fa-solid fa-chevron-right settings-item-arrow"></i>
                        </div>

                        <div class="settings-item" id="changePasswordBtn">
                            <div class="settings-item-info">
                                <div class="settings-item-icon">
                                    <i class="fa-solid fa-lock"></i>
                                </div>
                                <div class="settings-item-text">
                                    <h4>Change Password</h4>
                                    <p>Update your account password</p>
                                </div>
                            </div>
                            <i class="fa-solid fa-chevron-right settings-item-arrow"></i>
                        </div>
                    </div>

                    <!-- LEGAL SECTION -->
                    <div class="settings-section">
                        <div class="settings-section-title">
                            <i class="fa-solid fa-scale-balanced"></i> Legal
                        </div>
                        
                        <div class="settings-item" id="termsBtn">
                            <div class="settings-item-info">
                                <div class="settings-item-icon">
                                    <i class="fa-solid fa-file-contract"></i>
                                </div>
                                <div class="settings-item-text">
                                    <h4>Terms & Conditions</h4>
                                    <p>Read our terms of service</p>
                                </div>
                            </div>
                            <i class="fa-solid fa-chevron-right settings-item-arrow"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="password-modal" id="passwordModal">
            <div class="password-form-container">
                <div class="password-form-header">
                    <h3><i class="fa-solid fa-lock me-2"></i> Change Password</h3>
                    <button class="password-close" id="closePasswordModal">
                        <i class="fa-solid fa-times"></i>
                    </button>
                </div>
                
                <div id="passwordAlert"></div>
                
                <form id="changePasswordForm">
                    <div class="form-group">
                        <label for="currentPassword">Current Password</label>
                        <input type="password" id="currentPassword" name="current_password" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="newPassword">New Password (min. 8 characters)</label>
                        <input type="password" id="newPassword" name="new_password" required minlength="8">
                    </div>
                    
                    <div class="form-group">
                        <label for="confirmPassword">Confirm New Password</label>
                        <input type="password" id="confirmPassword" name="confirm_password" required>
                    </div>
                    
                    <button type="submit" class="password-submit-btn" id="submitPasswordBtn">
                        Change Password
                    </button>
                </form>
            </div>
        </div>

        <div class="terms-modal" id="termsModal">
    <div class="terms-container">
        <div class="terms-header">
            <h3><i class="fa-solid fa-file-contract me-2"></i> Terms & Conditions</h3>
            <button class="terms-close" id="closeTermsModal">
                <i class="fa-solid fa-times"></i>
            </button>
        </div>
        
        <div class="terms-content">
            <h1>Landlord Terms and Conditions</h1>
            <p>Welcome to our <strong>Web-based Apartment Management Platform Incorporating Tenant and Landlord Features with Real-Time Communication and Locations Mapping</strong>. 
                As a landlord, you agree to the terms and responsibilities below to ensure fair and transparent property management within the system.</p>

            <section>
                <h2>1. Landlord Information Collection</h2>
                <p>Landlords must provide accurate and verifiable information to maintain transparency and trust within the
                    platform. The following data may be required for verification:</p>
                <ul>
                    <li>Full name and contact details</li>
                    <li>Government-issued valid ID (e.g., Driver's License, Passport, or National ID)</li>
                    <li>Proof of property ownership or authorization (e.g., land title, lease agreement, or certification)</li>
                    <li>Property details such as address, images, rental rates, and descriptions</li>
                </ul>
                <p>Submitted documents are strictly confidential and reviewed only by authorized administrators. Data will be
                    deleted upon request or once the academic project concludes.</p>
            </section>

            <section>
                <h2>2. Landlord Responsibilities</h2>
                <p>Landlords must post accurate property information and respond promptly to tenant inquiries or maintenance
                    requests. False, misleading, or exaggerated property details are prohibited.</p>
                <p>Repeated violations may result in account suspension or removal.</p>
            </section>

            <section>
                <h2>3. Respect and Professional Conduct</h2>
                <p>Landlords must maintain professionalism when communicating with tenants. Harassment, discrimination, or
                    offensive remarks are not tolerated.</p>
                <p>All interactions should align with ethical standards and respect both parties' privacy and dignity.</p>
            </section>

            <section>
                <h2>4. Real-Time Communication</h2>
                <p>The platform's messaging feature allows direct communication between landlords and tenants. Landlords are
                    expected to use this feature responsibly and avoid sharing unnecessary or sensitive information.</p>
            </section>

            <section>
                <h2>5. Location Mapping</h2>
                <p>Landlords must ensure that uploaded property locations are accurate and legitimate. The mapping feature
                    exists to help tenants locate properties easily and should not be used to mislead users.</p>
            </section>

            <section>
                <h2>6. Payment Reminders</h2>
                <p>The system provides rent payment reminders to assist landlords in monitoring due dates. However, all
                    transactions must occur directly between the landlord and tenant.</p>
                <p>The platform does not process or store any payments.</p>
            </section>

            <section>
                <h2>7. System Usage</h2>
                <p>Landlords must refrain from unauthorized access, data modification, or actions that could harm platform
                    integrity.</p>
            </section>

            <section>
                <h2>8. Updates to Terms</h2>
                <p>These terms may change periodically. Continued use of the platform means acceptance of the updated
                    conditions.</p>
            </section>

            <p><strong>Last Updated:</strong> November 2025</p>
        </div>
        
        <div class="terms-footer">
            <button class="terms-accept-btn" id="acceptTermsBtn">I Understand</button>
        </div>
    </div>
</div>

        <!-- GLOBAL NOTIFICATION SYSTEM -->
        <?php if (isset($_SESSION['landlord_id'])): ?>
        <script>
            window.currentUser = {
                id: <?php echo (int)$_SESSION['landlord_id']; ?>,
                type: 'landlord'
            };
        </script>
        <script src="../js/global-notification-init.js"></script>
        <?php endif; ?>

        <script>
            // Notification dropdown
            const notificationList = document.getElementById("notificationList");
            const badge = document.querySelector(".count");
            const clearBtn = document.getElementById("clearNotifications");

            clearBtn.addEventListener("click", () => {
                notificationList.innerHTML = `
                    <li>
                        <span class="dropdown-item text-muted text-center py-3">
                            No notifications
                        </span>
                    </li>
                `;
                const badge = document.querySelector(".count");
                if (badge) {
                    badge.style.display = "none";
                }
            });

            // Mobile menu toggle
            const navMenu = document.getElementById('navmenu');
            const navLinks = document.querySelector('.nav-links');
            
            if (navMenu && navLinks) {
                navMenu.addEventListener('click', () => {
                    navLinks.classList.toggle('active');
                    document.body.style.overflow = navLinks.classList.contains('active') ? 'hidden' : '';
                });

                // Close on link click
                navLinks.querySelectorAll('a').forEach(link => {
                    link.addEventListener('click', () => {
                        navLinks.classList.remove('active');
                        document.body.style.overflow = '';
                    });
                });

                // Close on resize
                window.addEventListener('resize', () => {
                    if (window.innerWidth > 768) {
                        navLinks.classList.remove('active');
                        document.body.style.overflow = '';
                    }
                });
            }

            
            // Settings modal
            const settingsModal = document.getElementById('settingsModal');
            const openSettings = document.getElementById('openSettings');
            const closeSettings = document.getElementById('closeSettings');

            openSettings.addEventListener('click', (e) => {
                e.preventDefault();
                settingsModal.classList.add('active');
                document.body.style.overflow = 'hidden';
            });

            closeSettings.addEventListener('click', () => {
                settingsModal.classList.remove('active');
                document.body.style.overflow = '';
            });

            settingsModal.addEventListener('click', (e) => {
                if (e.target === settingsModal) {
                    settingsModal.classList.remove('active');
                    document.body.style.overflow = '';
                }
            });

            const passwordModal = document.getElementById('passwordModal');
            const closePasswordModal = document.getElementById('closePasswordModal');
            const changePasswordForm = document.getElementById('changePasswordForm');
            const passwordAlert = document.getElementById('passwordAlert');

            document.getElementById('changePasswordBtn').addEventListener('click', () => {
                settingsModal.classList.remove('active');
                passwordModal.classList.add('active');
                document.body.style.overflow = 'hidden';
            });

            closePasswordModal.addEventListener('click', () => {
                passwordModal.classList.remove('active');
                document.body.style.overflow = '';
                changePasswordForm.reset();
                passwordAlert.innerHTML = '';
            });

            passwordModal.addEventListener('click', (e) => {
                if (e.target === passwordModal) {
                    passwordModal.classList.remove('active');
                    document.body.style.overflow = '';
                    changePasswordForm.reset();
                    passwordAlert.innerHTML = '';
                }
            });

            changePasswordForm.addEventListener('submit', async (e) => {
                e.preventDefault();
                
                const submitBtn = document.getElementById('submitPasswordBtn');
                const formData = new FormData(changePasswordForm);
                
                // Client-side validation
                const newPassword = formData.get('new_password');
                const confirmPassword = formData.get('confirm_password');
                
                if (newPassword !== confirmPassword) {
                    passwordAlert.innerHTML = '<div class="alert-message alert-error">Passwords do not match!</div>';
                    return;
                }
                
                if (newPassword.length < 8) {
                    passwordAlert.innerHTML = '<div class="alert-message alert-error">Password must be at least 8 characters!</div>';
                    return;
                }
                
                submitBtn.disabled = true;
                submitBtn.textContent = 'Changing...';
                
                try {
                    const response = await fetch('../API/change_password.php', {
                        method: 'POST',
                        body: formData
                    });
                    
                    const data = await response.json();
                    
                    if (data.success) {
                        passwordAlert.innerHTML = '<div class="alert-message alert-success">Password changed successfully!</div>';
                        changePasswordForm.reset();
                        setTimeout(() => {
                            passwordModal.classList.remove('active');
                            document.body.style.overflow = '';
                            passwordAlert.innerHTML = '';
                        }, 2000);
                    } else {
                        passwordAlert.innerHTML = `<div class="alert-message alert-error">${data.error}</div>`;
                    }
                } catch (error) {
                    passwordAlert.innerHTML = '<div class="alert-message alert-error">An error occurred. Please try again.</div>';
                } finally {
                    submitBtn.disabled = false;
                    submitBtn.textContent = 'Change Password';
                }
            });

           const termsModal = document.getElementById('termsModal');
            const closeTermsModal = document.getElementById('closeTermsModal');
            const acceptTermsBtn = document.getElementById('acceptTermsBtn');

            document.getElementById('termsBtn').addEventListener('click', () => {
                settingsModal.classList.remove('active');
                termsModal.classList.add('active');
                document.body.style.overflow = 'hidden';
            });

            closeTermsModal.addEventListener('click', () => {
                termsModal.classList.remove('active');
                document.body.style.overflow = '';
            });

            acceptTermsBtn.addEventListener('click', () => {
                termsModal.classList.remove('active');
                document.body.style.overflow = '';
            });

            termsModal.addEventListener('click', (e) => {
                if (e.target === termsModal) {
                    termsModal.classList.remove('active');
                    document.body.style.overflow = '';
                }
            });
                    </script>
        <script src="../js/chat-notifications.js?v=<?= time() ?>"></script>
    </body>
    </html>