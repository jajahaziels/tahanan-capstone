<?php
$current_page = basename($_SERVER['PHP_SELF']);
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

    <style>
        /* =============================================
           HEADER RESPONSIVE — LANDLORD
        ============================================= */

        #navmenu {
            display: none;
            font-size: 24px;
            cursor: pointer;
            color: #8d0b41;
        }

        #navOverlay {
            display: none;
            position: fixed;
            inset: 0;
            background: rgba(0, 0, 0, 0.4);
            z-index: 9998;
        }
        #navOverlay.active { display: block; }

        .nav-icons {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .bell-wrapper { position: relative; }

        @media (max-width: 768px) {
            .logo { font-size: 18px !important; }
            .logo img { width: 30px !important; height: 30px !important; }

            #navmenu { display: block; }

            .nav-username { display: none; }
            .nav-icons { gap: 6px; }

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
                box-shadow: -5px 0 15px rgba(0,0,0,0.3) !important;
                display: flex !important;
                overflow-y: auto !important;
            }
            header .nav-links.active { right: 0 !important; }

            header .nav-links li { width:100% !important; margin:0 !important; padding:0 !important; list-style:none !important; }

            header .nav-links li a {
                display: block !important; width: 100% !important;
                padding: 15px 20px !important; color: white !important;
                border-bottom: 1px solid rgba(255,255,255,0.1) !important;
                text-decoration: none !important;
            }
            header .nav-links li a.active {
                background: rgba(255,255,255,0.15) !important;
                border-left: 4px solid white !important;
            }
        }

        /* =============================================
           SETTINGS MODAL
        ============================================= */
        .settings-modal {
            display: none; position: fixed; inset: 0;
            background: rgba(0,0,0,0.7); z-index: 10000;
            justify-content: center; align-items: center;
            animation: fadeIn 0.3s ease;
        }
        .settings-modal.active { display: flex; }

        .settings-content {
            background: white; border-radius: 20px;
            width: 90%; max-width: 600px; max-height: 90vh;
            overflow-y: auto; box-shadow: 0 20px 60px rgba(0,0,0,0.3);
            animation: slideUp 0.3s ease;
        }

        @keyframes fadeIn { from { opacity:0; } to { opacity:1; } }
        @keyframes slideUp { from { transform:translateY(50px); opacity:0; } to { transform:translateY(0); opacity:1; } }

        .settings-header {
            background: linear-gradient(135deg, #8d0b41 0%, #6a0831 100%);
            color: white; padding: 24px; border-radius: 20px 20px 0 0;
            display: flex; justify-content: space-between; align-items: center;
        }
        .settings-header h2 { margin:0; font-size:24px; font-weight:600; }

        .settings-close {
            background:none; border:none; color:white; font-size:24px; cursor:pointer;
            width:40px; height:40px; border-radius:50%;
            display:flex; align-items:center; justify-content:center; transition:background 0.2s;
        }
        .settings-close:hover { background: rgba(255,255,255,0.2); }

        .settings-body { padding:24px; }
        .settings-section { margin-bottom:32px; }
        .settings-section:last-child { margin-bottom:0; }

        .settings-section-title {
            font-size:14px; font-weight:600; color:#8d0b41; text-transform:uppercase;
            letter-spacing:0.5px; margin-bottom:16px; display:flex; align-items:center; gap:8px;
        }

        .settings-item {
            display:flex; justify-content:space-between; align-items:center;
            padding:16px; border-radius:12px; background:#f8f9fa;
            margin-bottom:12px; cursor:pointer; transition:all 0.2s;
        }
        .settings-item:hover { background:#e9ecef; transform:translateX(5px); }
        .settings-item-info  { display:flex; align-items:center; gap:16px; }

        .settings-item-icon {
            width:40px; height:40px; border-radius:10px;
            background:linear-gradient(135deg, #8d0b41 0%, #6a0831 100%);
            display:flex; align-items:center; justify-content:center; color:white; font-size:18px;
        }
        .settings-item-text h4 { margin:0; font-size:16px; font-weight:600; color:#2d3748; }
        .settings-item-text p  { margin:4px 0 0 0; font-size:13px; color:#718096; }
        .settings-item-arrow   { color:#cbd5e0; font-size:18px; }

        /* =============================================
           PASSWORD MODAL
        ============================================= */
        .password-modal {
            display:none; position:fixed; inset:0;
            background:rgba(0,0,0,0.8); z-index:11000;
            justify-content:center; align-items:center;
        }
        .password-modal.active { display:flex; }

        .password-form-container {
            background:white; border-radius:16px;
            width:90%; max-width:450px; padding:32px;
            box-shadow:0 20px 60px rgba(0,0,0,0.3);
        }
        .password-form-header { display:flex; justify-content:space-between; align-items:center; margin-bottom:24px; }
        .password-form-header h3 { margin:0; font-size:22px; color:#2d3748; }

        .password-close {
            background:none; border:none; font-size:24px; cursor:pointer; color:#718096;
            width:32px; height:32px; display:flex; align-items:center; justify-content:center;
            border-radius:50%; transition:all 0.2s;
        }
        .password-close:hover { background:#f7fafc; color:#2d3748; }

        .form-group { margin-bottom:20px; }
        .form-group label { display:block; margin-bottom:8px; font-weight:600; color:#2d3748; font-size:14px; }
        .form-group input { width:100%; padding:12px 16px; border:2px solid #e2e8f0; border-radius:8px; font-size:14px; transition:all 0.2s; }
        .form-group input:focus { outline:none; border-color:#8d0b41; box-shadow:0 0 0 3px rgba(141,11,65,0.1); }

        .password-submit-btn {
            width:100%; padding:14px;
            background:linear-gradient(135deg, #8d0b41 0%, #6a0831 100%);
            color:white; border:none; border-radius:8px; font-size:16px; font-weight:600; cursor:pointer; transition:all 0.2s;
        }
        .password-submit-btn:hover    { transform:translateY(-2px); box-shadow:0 8px 16px rgba(141,11,65,0.3); }
        .password-submit-btn:disabled { opacity:0.6; cursor:not-allowed; }

        .alert-message { padding:12px 16px; border-radius:8px; margin-bottom:20px; font-size:14px; }
        .alert-success { background:#d4edda; color:#155724; border:1px solid #c3e6cb; }
        .alert-error   { background:#f8d7da; color:#721c24; border:1px solid #f5c6cb; }

        /* =============================================
           TERMS / LEGAL MODALS
        ============================================= */
        .terms-modal {
            display:none; position:fixed; inset:0;
            background:rgba(0,0,0,0.8); z-index:11000;
            justify-content:center; align-items:center;
        }
        .terms-modal.active { display:flex; }

        .terms-container {
            background:white; border-radius:16px;
            width:90%; max-width:800px; max-height:90vh;
            display:flex; flex-direction:column;
            box-shadow:0 20px 60px rgba(0,0,0,0.3);
        }
        .terms-header {
            background:linear-gradient(135deg, #8d0b41 0%, #6a0831 100%);
            color:white; padding:24px; border-radius:16px 16px 0 0;
            display:flex; justify-content:space-between; align-items:center;
        }
        .terms-header h3 { margin:0; font-size:22px; color:white; }

        .terms-close {
            background:none; border:none; color:white; font-size:24px; cursor:pointer;
            width:40px; height:40px; border-radius:50%;
            display:flex; align-items:center; justify-content:center; transition:background 0.2s;
        }
        .terms-close:hover { background:rgba(255,255,255,0.2); }

        .terms-content    { padding:32px; overflow-y:auto; flex:1; }
        .terms-content h1 { color:#2c3e50; border-bottom:2px solid #ccc; padding-bottom:10px; margin-bottom:20px; font-size:28px; }
        .terms-content h2 { color:#2c3e50; margin-top:30px; margin-bottom:15px; font-size:20px; }
        .terms-content p  { line-height:1.7; color:#000; margin-bottom:15px; }
        .terms-content ul { margin-left:20px; margin-bottom:15px; }
        .terms-content li { margin-bottom:8px; line-height:1.7; }
        .terms-content strong { color:#2c3e50; }

        .terms-footer { padding:20px 32px; border-top:1px solid #e2e8f0; text-align:center; }
        .terms-accept-btn {
            padding:12px 32px;
            background:linear-gradient(135deg, #8d0b41 0%, #6a0831 100%);
            color:white; border:none; border-radius:8px; font-size:16px; font-weight:600; cursor:pointer; transition:all 0.2s;
        }
        .terms-accept-btn:hover { transform:translateY(-2px); box-shadow:0 8px 16px rgba(141,11,65,0.3); }

        /* =============================================
           EMERGENCY ALERT POPUP STYLES
        ============================================= */
        .emergency-popup {
            position: fixed;
            top: 80px;
            right: 20px;
            z-index: 10001;
            min-width: 320px;
            max-width: 450px;
            background: white;
            border-radius: 16px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.3);
            animation: slideInRight 0.4s ease;
            overflow: hidden;
        }

        .emergency-popup.flood { border-left: 8px solid #2196F3; }
        .emergency-popup.earthquake { border-left: 8px solid #FF9800; }
        .emergency-popup.fire { border-left: 8px solid #f44336; }
        .emergency-popup.storm { border-left: 8px solid #9C27B0; }
        .emergency-popup.typhoon { border-left: 8px solid #00BCD4; }

        .popup-header {
            padding: 15px;
            background: #f8f9fa;
            display: flex;
            justify-content: space-between;
            align-items: center;
            font-weight: bold;
        }

        .popup-body {
            padding: 15px;
        }

        .emergency-badge {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: bold;
        }

        .severity-emergency { background: #dc3545; color: white; }
        .severity-warning { background: #ffc107; color: #333; }
        .severity-alert { background: #fd7e14; color: white; }
        .severity-advisory { background: #17a2b8; color: white; }

        .popup-close {
            background: none;
            border: none;
            font-size: 24px;
            cursor: pointer;
            color: #999;
        }

        @keyframes slideInRight {
            from { transform: translateX(400px); opacity: 0; }
            to { transform: translateX(0); opacity: 1; }
        }

        /* Emergency alert items in notification dropdown */
        .emergency-notif-item {
            background: linear-gradient(135deg, #fff5f5 0%, #ffe8e8 100%);
            border-left: 4px solid #dc3545;
            margin: 8px;
            border-radius: 8px;
            transition: all 0.2s;
        }
        .emergency-notif-item:hover {
            transform: translateX(3px);
        }
        .emergency-notif-item.flood { border-left-color: #2196F3; background: linear-gradient(135deg, #e3f2fd 0%, #bbdef5 100%); }
        .emergency-notif-item.earthquake { border-left-color: #FF9800; background: linear-gradient(135deg, #fff3e0 0%, #ffe0b2 100%); }
        .emergency-notif-item.fire { border-left-color: #f44336; background: linear-gradient(135deg, #ffebee 0%, #ffcdd2 100%); }
        .emergency-notif-item.storm { border-left-color: #9C27B0; background: linear-gradient(135deg, #f3e5f5 0%, #e1bee7 100%); }
        .emergency-notif-item.typhoon { border-left-color: #00BCD4; background: linear-gradient(135deg, #e0f7fa 0%, #b2ebf2 100%); }
    </style>
</head>

<body>
    <!-- NAV OVERLAY -->
    <div id="navOverlay"></div>

    <!-- Container for emergency popups -->
    <div id="emergencyPopupContainer"></div>

    <header>
        <!-- LOGO -->
        <a href="#" class="logo d-flex justify-content-center align-items-center">
            <img src="../img/new_logo.png" alt="Logo" style="height: 2.5rem; width: auto; margin-right: 10px;">
            Map Aware Home
        </a>

        <!-- NAV LINKS -->
        <ul class="nav-links">
            <li><a href="landlord-properties.php" class="<?= $current_page == 'landlord-properties.php' ? 'active' : '' ?>">Properties</a></li>
            <li><a href="history.php"             class="<?= $current_page == 'history.php'             ? 'active' : '' ?>">Rentals</a></li>
            <li><a href="landlord-map.php"        class="<?= $current_page == 'landlord-map.php'        ? 'active' : '' ?>">Map</a></li>
            <li><a href="landlord-message.php"    class="<?= $current_page == 'landlord-message.php'    ? 'active' : '' ?>">Messages</a></li>
            <li><a href="support.php"             class="<?= $current_page == 'support.php'             ? 'active' : '' ?>">Support</a></li>
        </ul>

        <div class="nav-icons">

            <!-- USER DROPDOWN -->
            <div class="dropdown">
                <i class="fa-solid fa-user"></i>
                <span class="nav-username"><?= htmlspecialchars(ucwords(strtolower($_SESSION['username']))); ?></span>
                <div class="dropdown-content">
                    <a href="account.php">Account</a>
                    <a href="#" id="openSettings">Settings</a>
                    <a href="../LOGIN/logout.php">Log out</a>
                </div>
            </div>

            <!-- BELL with Emergency Alert Integration -->
            <div class="bell-wrapper dropdown">
                <a href="#" class="nav-link position-relative" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                    <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger count" style="display:none;">0</span>
                    <i class="fa-solid fa-bell fs-5"></i>
                </a>
                <ul class="dropdown-menu dropdown-menu-end p-0" style="min-width:320px; max-height:500px; overflow-y:auto;">
                    <li class="dropdown-header d-flex justify-content-between align-items-center px-3 py-2" style="background:#f8f9fa;">
                        <div>
                            <i class="fa-regular fa-bell me-2"></i>
                            <span class="fw-semibold">Notifications</span>
                        </div>
                        <div>
                            <button class="btn btn-sm btn-link text-danger" id="clearAllNotifications">Clear all</button>
                        </div>
                    </li>
                    <li><hr class="dropdown-divider m-0"></li>
                    <div id="notificationList">
                        <li><span class="dropdown-item text-muted text-center py-3">No notifications</span></li>
                    </div>
                </ul>
            </div>

            <!-- HAMBURGER -->
            <div class="fa-solid fa-bars" id="navmenu"></div>

        </div><!-- /.nav-icons -->
    </header>

    <!-- =============================================
         SETTINGS MODAL
    ============================================= -->
    <div class="settings-modal" id="settingsModal">
        <div class="settings-content">
            <div class="settings-header">
                <h2><i class="fa-solid fa-gear me-2"></i> Settings</h2>
                <button class="settings-close" id="closeSettings"><i class="fa-solid fa-times"></i></button>
            </div>
            <div class="settings-body">
                <div class="settings-section">
                    <div class="settings-section-title"><i class="fa-solid fa-user"></i> Account</div>
                    <div class="settings-item" onclick="window.location.href='account.php'">
                        <div class="settings-item-info">
                            <div class="settings-item-icon"><i class="fa-solid fa-id-card"></i></div>
                            <div class="settings-item-text"><h4>View Account</h4><p>See your profile information</p></div>
                        </div>
                        <i class="fa-solid fa-chevron-right settings-item-arrow"></i>
                    </div>
                    <div class="settings-item" onclick="window.location.href='edit-account.php'">
                            <div class="settings-item-info">
                                <div class="settings-item-icon">
                                    <i class="fa-solid fa-user-pen"></i>
                                </div>
                            <div class="settings-item-text">
                                <h4>Edit Account</h4>
                                <p>Update your profile information</p>
                            </div>
                            </div>
                                <i class="fa-solid fa-chevron-right settings-item-arrow"></i>
                            </div>
                    </div>

                <div class="settings-section">
                    <div class="settings-section-title"><i class="fa-solid fa-scale-balanced"></i> Legal &amp; Guidelines</div>
                    <div class="settings-item" id="termsBtn">
                        <div class="settings-item-info">
                            <div class="settings-item-icon"><i class="fa-solid fa-file-contract"></i></div>
                            <div class="settings-item-text"><h4>Terms &amp; Conditions</h4><p>Read our terms of service</p></div>
                        </div>
                        <i class="fa-solid fa-chevron-right settings-item-arrow"></i>
                    </div>
                    <div class="settings-item" id="rentalRulesBtn">
                        <div class="settings-item-info">
                            <div class="settings-item-icon"><i class="fa-solid fa-house-circle-check"></i></div>
                            <div class="settings-item-text"><h4>Rules Related to Renting</h4><p>Guidelines for renting properties</p></div>
                        </div>
                        <i class="fa-solid fa-chevron-right settings-item-arrow"></i>
                    </div>
                    <div class="settings-item" id="legalContractBtn">
                        <div class="settings-item-info">
                            <div class="settings-item-icon"><i class="fa-solid fa-file-signature"></i></div>
                            <div class="settings-item-text"><h4>Legal Contract Guidelines</h4><p>Important legal information</p></div>
                        </div>
                        <i class="fa-solid fa-chevron-right settings-item-arrow"></i>
                    </div>
                    <div class="settings-item" id="codeOfConductBtn">
                        <div class="settings-item-info">
                            <div class="settings-item-icon"><i class="fa-solid fa-users"></i></div>
                            <div class="settings-item-text"><h4>Code of Conduct</h4><p>Community guidelines and conduct</p></div>
                        </div>
                        <i class="fa-solid fa-chevron-right settings-item-arrow"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- PASSWORD MODAL -->
    <div class="password-modal" id="passwordModal">
        <div class="password-form-container">
            <div class="password-form-header">
                <h3><i class="fa-solid fa-lock me-2"></i> Change Password</h3>
                <button class="password-close" id="closePasswordModal"><i class="fa-solid fa-times"></i></button>
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
                <button type="submit" class="password-submit-btn" id="submitPasswordBtn">Change Password</button>
            </form>
        </div>
    </div>

    <!-- TERMS MODAL -->
    <div class="terms-modal" id="termsModal">
        <div class="terms-container">
            <div class="terms-header">
                <h3><i class="fa-solid fa-file-contract me-2"></i> Terms &amp; Conditions</h3>
                <button class="terms-close" id="closeTermsModal"><i class="fa-solid fa-times"></i></button>
            </div>
            <div class="terms-content">
                <h1>Landlord Terms and Conditions</h1>
                <p>Welcome to <strong>Map Aware Home</strong>. As a landlord, you agree to the following terms and responsibilities.</p>
                <section><h2>1. Property Information</h2><p>Landlords must provide accurate property information including location, amenities, rental price, and safety features. Your data is handled according to privacy laws.</p></section>
                <section><h2>2. Respect and Truthfulness</h2><p>Maintain respectful interactions with tenants. False listings or offensive messages are prohibited and will result in account suspension.</p></section>
                <section><h2>3. Communication Policy</h2><p>Use real-time chat responsibly for property-related communication. Respond to tenant inquiries promptly.</p></section>
                <section><h2>4. Proximity Mapping &amp; Safety</h2><p>Ensure proximity mapping coordinates are accurate to help tenants verify property locations and assess surrounding safety.</p></section>
                <section><h2>5. Payment Reminders</h2><p>Automatic rent payment reminders are provided as a courtesy. Map Aware Home does not process transactions.</p></section>
                <section><h2>6. System Usage</h2><p>Do not attempt unauthorized access, modify system data, or disrupt platform operation.</p></section>
                <section><h2>7. Updates to Terms</h2><p>We may update these terms at any time. Continued use means agreement to the latest version.</p></section>
                <p><strong>Last Updated:</strong> December 2025</p>
            </div>
            <div class="terms-footer"><button class="terms-accept-btn" id="acceptTermsBtn">I Understand</button></div>
        </div>
    </div>

    <!-- RENTAL RULES MODAL -->
    <div class="terms-modal" id="rentalRulesModal">
        <div class="terms-container">
            <div class="terms-header">
                <h3><i class="fa-solid fa-house-circle-check me-2"></i> Rules Related to Renting</h3>
                <button class="terms-close" data-close="rentalRulesModal"><i class="fa-solid fa-times"></i></button>
            </div>
            <div class="terms-content">
                <h1>Rules Related to Renting</h1>
                <p><strong>Map Aware Home</strong> ensures fair and transparent rental practices for all users.</p>
                <section><h2>Property Listings &amp; Accuracy</h2><ul><li>Accurate property info including location, amenities, price, and safety features</li><li>Photos must be recent and represent actual condition</li><li>Proximity mapping coordinates must be accurate</li></ul></section>
                <section><h2>Rental Agreements</h2><ul><li>All agreements must comply with local housing laws</li><li>Terms must be clearly stated before proceeding</li><li>The platform facilitates communication but does not enforce legal contracts</li></ul></section>
                <section><h2>Payment Rules</h2><ul><li>Rent payments are handled directly between landlord and tenant</li><li>Map Aware Home does not process financial transactions</li></ul></section>
                <section><h2>Property Maintenance</h2><ul><li>Landlords must maintain properties in safe and habitable condition</li><li>Urgent safety issues must be addressed immediately</li></ul></section>
                <section><h2>Tenant Rights</h2><ul><li>Right to safe and habitable housing</li><li>Discrimination based on protected classes is strictly prohibited</li></ul></section>
                <section><h2>Landlord Rights</h2><ul><li>Right to screen tenants according to legal guidelines</li><li>Can set reasonable house rules that comply with local laws</li></ul></section>
                <p><strong>Last Updated:</strong> December 2025</p>
            </div>
            <div class="terms-footer"><button class="terms-accept-btn" data-close="rentalRulesModal">I Understand</button></div>
        </div>
    </div>

    <!-- LEGAL CONTRACT MODAL -->
    <div class="terms-modal" id="legalContractModal">
        <div class="terms-container">
            <div class="terms-header">
                <h3><i class="fa-solid fa-file-signature me-2"></i> Legal Contract Guidelines</h3>
                <button class="terms-close" data-close="legalContractModal"><i class="fa-solid fa-times"></i></button>
            </div>
            <div class="terms-content">
                <h1>Legal Contract Guidelines</h1>
                <p>Important legal information for <strong>Map Aware Home</strong> users.</p>
                <section><h2>Platform Role</h2><ul><li>Map Aware Home is a property management and communication platform only</li><li>We do not create, validate, or enforce rental contracts</li><li>Users should consult legal professionals for contract creation and review</li></ul></section>
                <section><h2>Recommended Contract Elements</h2><ul><li>Full legal names and contact info of all parties</li><li>Property address with proximity mapping coordinates</li><li>Lease term, rent amount, payment due dates</li><li>Security deposit amount and return conditions</li><li>Termination and eviction procedures according to local law</li></ul></section>
                <section><h2>Liability Disclaimer</h2><ul><li>Map Aware Home is not liable for contract breaches or personal disputes</li><li>Users assume all risks associated with rental agreements</li></ul></section>
                <p><strong>Last Updated:</strong> December 2025</p>
            </div>
            <div class="terms-footer"><button class="terms-accept-btn" data-close="legalContractModal">I Understand</button></div>
        </div>
    </div>

    <!-- CODE OF CONDUCT MODAL -->
    <div class="terms-modal" id="codeOfConductModal">
        <div class="terms-container">
            <div class="terms-header">
                <h3><i class="fa-solid fa-users me-2"></i> Code of Conduct</h3>
                <button class="terms-close" data-close="codeOfConductModal"><i class="fa-solid fa-times"></i></button>
            </div>
            <div class="terms-content">
                <h1>Code of Conduct</h1>
                <p><strong>Map Aware Home</strong> Community Standards</p>
                <section><h2>Respectful Communication</h2><ul><li>All users must communicate respectfully and professionally</li><li>Harassment, threats, hate speech, or discriminatory language is strictly prohibited</li></ul></section>
                <section><h2>Honesty and Transparency</h2><ul><li>Provide truthful information about properties and personal details</li><li>False reviews or misleading information will result in account suspension</li></ul></section>
                <section><h2>Privacy and Data Protection</h2><ul><li>Respect the privacy of other users</li><li>Do not share personal information without consent</li></ul></section>
                <section><h2>Prohibited Activities</h2><ul><li>Creating fake accounts or impersonating others</li><li>Posting properties you don't own or have authorization to list</li><li>Using the platform for illegal activities</li></ul></section>
                <section><h2>Consequences for Violations</h2><ul><li>First offense: Warning and temporary restriction</li><li>Second offense: Account suspension for 30 days</li><li>Severe or repeated violations: Permanent account termination</li></ul></section>
                <p><strong>Map Aware Home reserves the right to review accounts, suspend violating users, and cooperate with law enforcement when necessary.</strong></p>
                <p><strong>Last Updated:</strong> December 2025</p>
            </div>
            <div class="terms-footer"><button class="terms-accept-btn" data-close="codeOfConductModal">I Understand</button></div>
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

    <!-- Emergency Alert System - Full Integration -->
    <script>
    /* MOBILE NAV */
    var navMenu    = document.getElementById('navmenu');
    var navLinks   = document.querySelector('.nav-links');
    var navOverlay = document.getElementById('navOverlay');

    function openNav()  { navLinks.classList.add('active');    navOverlay.classList.add('active');    document.body.style.overflow = 'hidden'; }
    function closeNav() { navLinks.classList.remove('active'); navOverlay.classList.remove('active'); document.body.style.overflow = ''; }

    if (navMenu) {
        navMenu.addEventListener('click', () => navLinks.classList.contains('active') ? closeNav() : openNav());
        navOverlay.addEventListener('click', closeNav);
        navLinks.querySelectorAll('a').forEach(link => link.addEventListener('click', closeNav));
        window.addEventListener('resize', () => { if (window.innerWidth > 768) closeNav(); });
    }

    /* =============================================
       EMERGENCY ALERT SYSTEM
    ============================================= */
    
    // Alert type icons
    const alertIcons = {
        flood: '🌊',
        earthquake: '🌋',
        fire: '🔥',
        storm: '🌪️',
        typhoon: '🌀'
    };
    
    const severityText = {
        advisory: 'ADVISORY',
        alert: 'ALERT',
        warning: 'WARNING',
        emergency: 'EMERGENCY'
    };
    
    const severityClass = {
        advisory: 'severity-advisory',
        alert: 'severity-alert',
        warning: 'severity-warning',
        emergency: 'severity-emergency'
    };
    
    let lastAlertCheck = localStorage.getItem('lastAlertCheckLandlord') || Math.floor(Date.now() / 1000);
    let shownAlerts = JSON.parse(localStorage.getItem('shownAlertsLandlord') || '[]');
    
    // Function to fetch emergency alerts
    async function fetchEmergencyAlerts() {
        if (!window.currentUser || !window.currentUser.id) return;
        
        try {
            const response = await fetch(`../API/alerts/fetch_alerts.php?last_check=${lastAlertCheck}&_=${Date.now()}`);
            const data = await response.json();
            
            if (data.success && data.alerts && data.alerts.length > 0) {
                for (const alert of data.alerts) {
                    // Check if alert already shown
                    if (!shownAlerts.includes(alert.id)) {
                        // Show popup on page
                        showEmergencyPopup(alert);
                        // Add to notification dropdown
                        addEmergencyToDropdown(alert);
                        // Mark as read
                        await markEmergencyAlertRead(alert.id);
                        // Add to shown alerts
                        shownAlerts.push(alert.id);
                    }
                }
                lastAlertCheck = Math.floor(Date.now() / 1000);
                localStorage.setItem('lastAlertCheckLandlord', lastAlertCheck);
                localStorage.setItem('shownAlertsLandlord', JSON.stringify(shownAlerts));
                updateNotificationBadgeCount();
            }
        } catch (error) {
            console.error('Error fetching emergency alerts:', error);
        }
    }
    
    // Show popup on page
    function showEmergencyPopup(alert) {
        const container = document.getElementById('emergencyPopupContainer');
        if (!container) return;
        
        const popup = document.createElement('div');
        popup.className = `emergency-popup ${alert.alert_type}`;
        popup.innerHTML = `
            <div class="popup-header">
                <span>
                    ${alertIcons[alert.alert_type]} <strong>${escapeHtml(alert.title)}</strong>
                    <span class="emergency-badge ${severityClass[alert.severity]}">${severityText[alert.severity]}</span>
                </span>
                <button class="popup-close" onclick="this.closest('.emergency-popup').remove()">&times;</button>
            </div>
            <div class="popup-body">
                ${escapeHtml(alert.message)}
                <br><small style="color:#666; display:block; margin-top:10px;">📅 ${new Date(alert.created_at).toLocaleString()}</small>
            </div>
        `;
        
        container.appendChild(popup);
        
        // Play sound effect
        playAlertSound();
        
        // Auto remove after 30 seconds
        setTimeout(() => {
            if (popup.parentNode) popup.remove();
        }, 30000);
    }
    
    // Add to notification dropdown
    function addEmergencyToDropdown(alert) {
        const notificationList = document.getElementById('notificationList');
        if (!notificationList) return;
        
        // Remove "no notifications" message if exists
        if (notificationList.innerHTML.includes('No notifications')) {
            notificationList.innerHTML = '';
        }
        
        const notifItem = document.createElement('div');
        notifItem.className = `emergency-notif-item ${alert.alert_type}`;
        notifItem.style.margin = '8px';
        notifItem.style.padding = '12px';
        notifItem.style.borderRadius = '8px';
        notifItem.style.cursor = 'pointer';
        notifItem.onclick = () => showEmergencyPopup(alert);
        
        notifItem.innerHTML = `
            <div style="display: flex; align-items: flex-start; gap: 10px;">
                <div style="font-size: 24px;">${alertIcons[alert.alert_type]}</div>
                <div style="flex: 1;">
                    <div style="font-weight: bold; margin-bottom: 4px;">
                        ${escapeHtml(alert.title)}
                        <span class="emergency-badge ${severityClass[alert.severity]}" style="font-size: 9px; margin-left: 6px;">${severityText[alert.severity]}</span>
                    </div>
                    <div style="font-size: 12px; color: #666; margin-bottom: 4px;">${escapeHtml(alert.message.substring(0, 80))}${alert.message.length > 80 ? '...' : ''}</div>
                    <div style="font-size: 10px; color: #999;">🚨 Emergency Alert • ${new Date(alert.created_at).toLocaleTimeString()}</div>
                </div>
            </div>
        `;
        
        notificationList.insertBefore(notifItem, notificationList.firstChild);
    }
    
    // Mark alert as read in database
    async function markEmergencyAlertRead(alertId) {
        try {
            await fetch('../API/alerts/mark_read.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ alert_id: alertId })
            });
        } catch(err) {
            console.error('Error marking alert read:', err);
        }
    }
    
    // Update notification badge count
    function updateNotificationBadgeCount() {
        const notificationItems = document.querySelectorAll('#notificationList .emergency-notif-item');
        const badge = document.querySelector('.count');
        if (badge) {
            const count = notificationItems.length;
            if (count > 0) {
                badge.textContent = count;
                badge.style.display = 'inline-block';
            } else {
                badge.style.display = 'none';
            }
        }
    }
    
    // Play alert sound
    function playAlertSound() {
        try {
            // Simple beep using Web Audio API
            const audioContext = new (window.AudioContext || window.webkitAudioContext)();
            const oscillator = audioContext.createOscillator();
            const gainNode = audioContext.createGain();
            oscillator.connect(gainNode);
            gainNode.connect(audioContext.destination);
            oscillator.frequency.value = 880;
            gainNode.gain.value = 0.3;
            oscillator.start();
            gainNode.gain.exponentialRampToValueAtTime(0.00001, audioContext.currentTime + 1);
            oscillator.stop(audioContext.currentTime + 1);
        } catch(e) {
            console.log('Sound not supported');
        }
    }
    
    function escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }
    
    // Start polling for alerts every 10 seconds
    let pollInterval = setInterval(fetchEmergencyAlerts, 10000);
    
    // Initial fetch on page load
    document.addEventListener('DOMContentLoaded', () => {
        setTimeout(fetchEmergencyAlerts, 1000);
    });
    
    /* Clear all notifications */
    document.getElementById('clearAllNotifications').addEventListener('click', async () => {
        const notificationList = document.getElementById('notificationList');
        notificationList.innerHTML = '<li><span class="dropdown-item text-muted text-center py-3">No notifications</span></li>';
        const badge = document.querySelector('.count');
        if (badge) badge.style.display = 'none';
        
        // Clear shown alerts
        shownAlerts = [];
        localStorage.setItem('shownAlertsLandlord', JSON.stringify(shownAlerts));
        
        if (window.currentUser) {
            try {
                const resp = await fetch('../API/mark_notifications_read.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: `user_id=${window.currentUser.id}&user_type=${window.currentUser.type}&mark_all=1`
                });
                const data = await resp.json();
                console.log('Notifications cleared:', data);
            } catch(e) { console.error(e); }
        }
    });

    /* MODAL HELPERS */
    function openModal(id)  { document.getElementById(id).classList.add('active');    document.body.style.overflow = 'hidden'; }
    function closeModal(id) { document.getElementById(id).classList.remove('active'); document.body.style.overflow = ''; }

    document.querySelectorAll('[data-close]').forEach(btn =>
        btn.addEventListener('click', () => closeModal(btn.dataset.close))
    );
    document.querySelectorAll('.terms-modal').forEach(modal =>
        modal.addEventListener('click', e => { if (e.target === modal) closeModal(modal.id); })
    );

    /* SETTINGS */
    document.getElementById('openSettings').addEventListener('click', e => { e.preventDefault(); openModal('settingsModal'); });
    document.getElementById('closeSettings').addEventListener('click', () => closeModal('settingsModal'));
    document.getElementById('settingsModal').addEventListener('click', e => { if (e.target === document.getElementById('settingsModal')) closeModal('settingsModal'); });

    /* PASSWORD */
    const passwordModal      = document.getElementById('passwordModal');
    const changePasswordForm = document.getElementById('changePasswordForm');
    const passwordAlert      = document.getElementById('passwordAlert');

    document.getElementById('changePasswordBtn').addEventListener('click', () => { closeModal('settingsModal'); openModal('passwordModal'); });

    function closePasswordModal() { closeModal('passwordModal'); changePasswordForm.reset(); passwordAlert.innerHTML = ''; }
    document.getElementById('closePasswordModal').addEventListener('click', closePasswordModal);
    passwordModal.addEventListener('click', e => { if (e.target === passwordModal) closePasswordModal(); });

    changePasswordForm.addEventListener('submit', async e => {
        e.preventDefault();
        const submitBtn       = document.getElementById('submitPasswordBtn');
        const formData        = new FormData(changePasswordForm);
        const newPassword     = formData.get('new_password');
        const confirmPassword = formData.get('confirm_password');

        if (newPassword !== confirmPassword) { passwordAlert.innerHTML = '<div class="alert-message alert-error">Passwords do not match!</div>'; return; }
        if (newPassword.length < 8)          { passwordAlert.innerHTML = '<div class="alert-message alert-error">Password must be at least 8 characters!</div>'; return; }

        submitBtn.disabled = true; submitBtn.textContent = 'Changing...';
        try {
            const res  = await fetch('../API/change_password.php', { method: 'POST', body: formData });
            const data = await res.json();
            if (data.success) { passwordAlert.innerHTML = '<div class="alert-message alert-success">Password changed successfully!</div>'; changePasswordForm.reset(); setTimeout(closePasswordModal, 2000); }
            else              { passwordAlert.innerHTML = `<div class="alert-message alert-error">${data.error}</div>`; }
        } catch {
            passwordAlert.innerHTML = '<div class="alert-message alert-error">An error occurred. Please try again.</div>';
        } finally {
            submitBtn.disabled = false; submitBtn.textContent = 'Change Password';
        }
    });

    /* LEGAL MODALS */
    document.getElementById('termsBtn').addEventListener('click',         () => { closeModal('settingsModal'); openModal('termsModal'); });
    document.getElementById('closeTermsModal').addEventListener('click',  () => closeModal('termsModal'));
    document.getElementById('acceptTermsBtn').addEventListener('click',   () => closeModal('termsModal'));
    document.getElementById('termsModal').addEventListener('click', e => { if (e.target === document.getElementById('termsModal')) closeModal('termsModal'); });
    document.getElementById('rentalRulesBtn').addEventListener('click',   () => { closeModal('settingsModal'); openModal('rentalRulesModal'); });
    document.getElementById('legalContractBtn').addEventListener('click', () => { closeModal('settingsModal'); openModal('legalContractModal'); });
    document.getElementById('codeOfConductBtn').addEventListener('click', () => { closeModal('settingsModal'); openModal('codeOfConductModal'); });
    </script>

    <script src="../js/chat-notifications.js?v=<?= time() ?>"></script>
</body>
</html>