<?php
require_once '../connection.php';
include '../session_auth.php';

$tenant_id = $_SESSION['tenant_id'] ?? 0;
if ($tenant_id <= 0)
    die("Unauthorized access. Please log in.");

// Fetch ALL available listings (not currently rented/approved)
$sql = "
    SELECT ID AS listing_id, listingName, latitude, longitude, price
    FROM listingtbl 
    WHERE latitude IS NOT NULL 
      AND longitude IS NOT NULL
      AND ID NOT IN (
          SELECT listing_id FROM renttbl WHERE status = 'approved'
      )
";
$result = $conn->query($sql);

$listings = [];
while ($row = $result->fetch_assoc())
    $listings[] = $row;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Property Map — San Pedro, Laguna</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:ital,wght@0,100..900;1,100..900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../css/bootstrap.min.css">
    <link rel="stylesheet" href="../css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" />
    <style>
        body, h1, h2, h3, h4, h5, h6, p, a, span, div,
        button, input, select, textarea, label, td, th {
            font-family: 'Montserrat', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
        }
        :root { --main-color: #8B0000; }
        .tenant-page { margin-top: 140px !important; }

        .map-wrapper {
            display: flex;
            border: 1px solid #e0e0e0;
            border-radius: 14px;
            overflow: hidden;
            box-shadow: 0 6px 32px rgba(0,0,0,0.10);
            background: #fff;
            min-height: 700px;
        }

        .map-sidebar {
            width: 280px; min-width: 280px;
            background: #fff; border-right: 1px solid #f0f0f0;
            display: flex; flex-direction: column; overflow-y: auto;
        }
        .sidebar-header { padding: 20px 18px 14px; border-bottom: 1px solid #f0f0f0; }
        .sidebar-header h2 { font-size: 15px; font-weight: 800; margin: 0 0 3px; color: #111; }
        .sidebar-header p  { font-size: 12px; color: #999; margin: 0; font-weight: 600; }

        .sidebar-section { padding: 14px 18px 12px; border-bottom: 1px solid #f5f5f5; }
        .sidebar-section-title {
            font-size: 10px; font-weight: 800; letter-spacing: 0.09em;
            text-transform: uppercase; color: #bbb; margin: 0 0 10px;
        }

        .stat-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 7px; }
        .stat-card { background: #fafafa; border: 1px solid #f0f0f0; border-radius: 8px; padding: 9px 10px; }
        .stat-card.full { grid-column: 1 / -1; }
        .stat-label { font-size: 10px; color: #aaa; margin: 0 0 3px; line-height: 1.3; font-weight: 700; }
        .stat-val   { font-size: 18px; font-weight: 900; color: #111; line-height: 1; }

        .layer-toggle {
            display: flex; align-items: center; gap: 9px; padding: 6px 4px;
            cursor: pointer; user-select: none; font-size: 13px; color: #333;
            border-radius: 6px; transition: background 0.12s; font-weight: 600;
        }
        .layer-toggle:hover { background: #f7f7f7; }
        .layer-toggle input[type="checkbox"] {
            width: 15px; height: 15px; accent-color: var(--main-color); cursor: pointer; flex-shrink: 0;
        }

        .swatch        { width: 13px; height: 13px; border-radius: 3px; flex-shrink: 0; }
        .swatch-circle { border-radius: 50%; }
        .swatch-line   { height: 3px; border-radius: 2px; width: 18px; margin-top: 2px; }
        .swatch-square { border-radius: 2px; opacity: 0.85; }

        .legend-item {
            display: flex; align-items: center; gap: 9px;
            font-size: 12px; color: #555; padding: 3px 0; font-weight: 600;
        }

        .status-badge {
            display: inline-flex; align-items: center; gap: 5px;
            font-size: 11px; padding: 3px 8px; border-radius: 20px; font-weight: 700;
        }
        .badge-live { background: #dcfce7; color: #166534; }
        .badge-dot  { width: 6px; height: 6px; border-radius: 50%; background: currentColor; }
        .badge-noah { background: #fef3c7; color: #92400e; }

        .source-note { font-size: 11px; color: #ccc; line-height: 1.5; margin: 0; font-weight: 600; }

        #map { flex: 1; min-height: 700px; }

        .gm-style .gm-style-iw-c {
            border-radius: 12px !important; padding: 0 !important;
            font-family: 'Montserrat', sans-serif !important;
            box-shadow: 0 8px 28px rgba(0,0,0,0.14) !important;
        }
        .gm-style .gm-style-iw-d { overflow: hidden !important; }
        .gm-style-iw-t::after { display: none !important; }
        .gm-ui-hover-effect { top: 4px !important; right: 4px !important; }

        .popup-card   { padding: 16px 18px; min-width: 210px; font-family: 'Montserrat', sans-serif; }
        .popup-title  { font-size: 14px; font-weight: 800; color: #111; margin: 0 0 3px; }
        .popup-sub    { font-size: 12px; color: #aaa; margin: 0 0 10px; font-weight: 600; }
        .popup-price  { font-size: 18px; font-weight: 900; margin: 0 0 12px; }
        .popup-actions { display: flex; gap: 7px; }
        .popup-btn {
            flex: 1; padding: 7px 0; font-size: 12px; font-weight: 700;
            border: none; border-radius: 7px; cursor: pointer;
            text-align: center; text-decoration: none; display: inline-block;
            font-family: 'Montserrat', sans-serif;
        }
        .popup-btn-primary   { background: var(--main-color); color: #fff; }
        .popup-btn-secondary { background: #f0f0f0; color: #444; }

        .popup-info { padding: 14px 16px; min-width: 190px; font-family: 'Montserrat', sans-serif; }
        .pi-title   { font-size: 13px; font-weight: 800; color: #111; margin: 0 0 5px; }
        .pi-badge   { display: inline-block; font-size: 11px; font-weight: 700; padding: 3px 9px; border-radius: 20px; margin-bottom: 6px; }
        .pi-sub     { font-size: 11px; color: #aaa; margin: 0; font-weight: 600; line-height: 1.6; }

        @media (max-width: 768px) {
            .map-wrapper { flex-direction: column; }
            .map-sidebar { width: 100%; min-width: 0; border-right: none; border-bottom: 1px solid #f0f0f0; }
            #map { min-height: 420px; }
        }

        /* ── Hazard Info Buttons in Sidebar ── */
        .hazard-info-btn {
            display: flex; align-items: center; gap: 8px;
            width: 100%; padding: 8px 10px; margin-top: 6px;
            border: 1px solid #f0f0f0; border-radius: 8px;
            background: #fafafa; cursor: pointer; font-size: 12px;
            font-weight: 700; color: #444; font-family: 'Montserrat', sans-serif;
            transition: background 0.12s;
        }
        .hazard-info-btn:hover { background: #f0f0f0; }
        .hazard-info-btn .btn-icon { font-size: 16px; }

        /* ── Hazard Modal ── */
        .hazard-modal-overlay {
            display: none; position: fixed; inset: 0;
            background: rgba(0,0,0,0.5); z-index: 9999;
            align-items: center; justify-content: center;
        }
        .hazard-modal-overlay.active { display: flex; }
        .hazard-modal {
            background: #fff; border-radius: 16px; padding: 24px;
            max-width: 500px; width: 90%; max-height: 85vh;
            overflow-y: auto; position: relative; font-family: 'Montserrat', sans-serif;
        }
        .hazard-modal-close {
            position: absolute; top: 12px; right: 16px;
            background: none; border: none; font-size: 20px;
            cursor: pointer; color: #aaa;
        }
        .hazard-badge {
            display: inline-block; padding: 4px 12px;
            border-radius: 20px; font-size: 11px; font-weight: 700; margin-bottom: 12px;
        }
        .hazard-section-title {
            font-size: 12px; font-weight: 800; letter-spacing: 0.08em;
            text-transform: uppercase; color: #bbb; margin: 16px 0 8px;
        }
        .hazard-list { list-style: none; padding: 0; margin: 0; }
        .hazard-list li {
            font-size: 13px; color: #444; padding: 5px 0 5px 20px;
            border-bottom: 1px solid #f5f5f5; position: relative; font-weight: 600;
            line-height: 1.5;
        }
        .hazard-list li::before {
            content: '•'; position: absolute; left: 6px; color: #ccc;
        }
        .hazard-level-row {
            display: flex; align-items: center; gap: 10px;
            padding: 6px 0; font-size: 13px; font-weight: 600; color: #333;
        }
        .hazard-level-dot { width: 14px; height: 14px; border-radius: 3px; flex-shrink: 0; }

        /* flood loading indicator */
        #flood-loading {
            display: none; position: absolute; bottom: 16px; right: 16px;
            background: rgba(255,255,255,0.92); border-radius: 8px;
            padding: 7px 13px; font-size: 12px; font-weight: 700; color: #555;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1); z-index: 10; pointer-events: none;
        }
    </style>
</head>
<body>
    <?php include '../Components/tenant-header.php'; ?>

    <div class="tenant-page container-fluid px-4">
        <div class="mb-3">
            <h1 class="h4 mb-1" style="font-weight:900">Property Map</h1>
            <p class="text-muted small mb-0" style="font-weight:600">
                Browse available listings, hospitals, evacuation centers, fault line, and flood hazard overlay for <strong>San Pedro, Laguna</strong>.
            </p>
        </div>

        <div class="map-wrapper" style="position:relative;">
            <div class="map-sidebar">
                <div class="sidebar-header">
                    <h2>San Pedro, Laguna</h2>
                    <p>Region IV-A CALABARZON · 4023</p>
                    <div style="margin-top:8px; display:flex; gap:6px; flex-wrap:wrap;">
                        <span class="status-badge badge-live">
                            <span class="badge-dot"></span> Google Maps
                        </span>
                        <span class="status-badge badge-noah">🌊 NOAH Data</span>
                    </div>
                </div>

                <!-- Available Listings Stats -->
                <div class="sidebar-section">
                    <div class="sidebar-section-title">Available Listings</div>
                    <div class="stat-grid">
                        <div class="stat-card full">
                            <div class="stat-label">Total available properties</div>
                            <div class="stat-val" id="stat-total">—</div>
                        </div>
                        <div class="stat-card">
                            <div class="stat-label">≤ ₱10k / mo</div>
                            <div class="stat-val" style="color:#059669;" id="stat-low">—</div>
                        </div>
                        <div class="stat-card">
                            <div class="stat-label">₱10k – ₱20k</div>
                            <div class="stat-val" style="color:#d97706;" id="stat-mid">—</div>
                        </div>
                        <div class="stat-card full">
                            <div class="stat-label">&gt; ₱20k / mo</div>
                            <div class="stat-val" style="color:#7c3aed;" id="stat-high">—</div>
                        </div>
                    </div>
                </div>

                <!-- Map Layers -->
                <div class="sidebar-section">
                    <div class="sidebar-section-title">Map Layers</div>
                    <label class="layer-toggle">
                        <input type="checkbox" id="tog-listings" checked>
                        <span class="swatch swatch-circle" style="background:#8B0000;"></span>
                        Available Listings
                    </label>
                    
                    <label class="layer-toggle">
                        <input type="checkbox" id="tog-flood" checked>
                        <span class="swatch swatch-square" style="background:#FF8000;"></span>
                        Flood Hazard Zones
                    </label>
                    <label class="layer-toggle">
                        <input type="checkbox" id="tog-fault" checked>
                        <span class="swatch swatch-line" style="background:#dc2626;"></span>
                        West Valley Fault
                    </label>
                    <label class="layer-toggle">
                        <input type="checkbox" id="tog-evac" checked>
                        <span class="swatch swatch-circle" style="background:#2563eb;"></span>
                        Evacuation Centers
                    </label>
                    <label class="layer-toggle">
                        <input type="checkbox" id="tog-border" checked>
                        <span class="swatch swatch-line" style="background:#0ea5e9;"></span>
                        City Boundary
                    </label>
                </div>

                <!-- Rent Price Legend -->
                <div class="sidebar-section">
                    <div class="sidebar-section-title">Rent Price Legend</div>
                    <div class="legend-item"><span class="swatch swatch-circle" style="background:#059669;"></span> ≤ ₱10,000 / month</div>
                    <div class="legend-item"><span class="swatch swatch-circle" style="background:#d97706;"></span> ₱10,001 – ₱20,000</div>
                    <div class="legend-item"><span class="swatch swatch-circle" style="background:#7c3aed;"></span> &gt; ₱20,000</div>
                </div>

                <!-- Flood Hazard Legend -->
                <div class="sidebar-section">
                    <div class="sidebar-section-title">Flood Hazard Legend</div>
                    <div class="legend-item">
                        <span class="swatch swatch-square" style="background:#FFFF00; border:1px solid #ccc;"></span>
                        Low <span style="color:#aaa;font-size:11px;">(0.1–0.5m depth)</span>
                    </div>
                    <div class="legend-item">
                        <span class="swatch swatch-square" style="background:#FF8000;"></span>
                        Medium <span style="color:#aaa;font-size:11px;">(0.5–1.5m depth)</span>
                    </div>
                    <div class="legend-item">
                        <span class="swatch swatch-square" style="background:#FF0000;"></span>
                        High <span style="color:#aaa;font-size:11px;">(above 1.5m depth)</span>
                    </div>
                    <div style="margin-top:6px;">
                        <span style="font-size:10px;color:#bbb;font-weight:700;">Source: Project NOAH / DOST-ASTI</span>
                    </div>
                    <div style="margin-top:4px;">
                        <span style="font-size:10px;color:#bbb;font-weight:700;">Click any flood zone for safety info</span>
                    </div>
                    <button class="hazard-info-btn" onclick="showHazardModal('flood', 'General')">
                        <span class="btn-icon">🌊</span> Flood Preparedness Guide
                    </button>
                </div>

                <!-- Fault Line Legend -->
                <div class="sidebar-section">
                    <div class="sidebar-section-title">Fault Line Legend</div>
                    <div class="legend-item">
                        <span class="swatch swatch-line" style="background:#dc2626;"></span>
                        Certain Fault Trace (WVF)
                    </div>
                    <div class="legend-item">
                        <span class="swatch swatch-line" style="background:#f97316; border-top: 2px dashed #f97316; background:none; width:18px;"></span>
                        Approximate Fault Trace
                    </div>
                    <div style="margin-top:6px;">
                        <span style="font-size:10px;color:#bbb;font-weight:700;">Source: PHIVOLCS Sheet AFT-2021-043425</span>
                    </div>
                    <button class="hazard-info-btn" onclick="showHazardModal('earthquake', '')">
                        <span class="btn-icon">🏚️</span> Earthquake Preparedness Guide
                    </button>
                </div>

                <!-- Storm Surge -->
                <div class="sidebar-section">
                    <div class="sidebar-section-title">Storm Surge Advisory</div>
                    <div style="font-size:11px;color:#888;font-weight:600;line-height:1.6;margin-bottom:8px;">
                        San Pedro, Laguna is <strong style="color:#0369a1;">inland</strong> — not directly at risk for coastal storm surge. However Laguna de Bay flooding may occur during typhoons.
                    </div>
                    <button class="hazard-info-btn" onclick="showHazardModal('stormsurge', '')">
                        <span class="btn-icon">🌀</span> Storm &amp; Typhoon Guide
                    </button>
                </div>

                <!-- Data Sources -->
                <div class="sidebar-section" style="margin-top:auto;">
                    <div class="sidebar-section-title">Data Sources</div>
                    <p class="source-note">
                        Flood Hazard: Project NOAH / DOST-ASTI<br>
                        Fault: PHIVOLCS AFT-2021-043425-02<br>
                        Boundary: © OpenStreetMap contributors<br>
                        Basemap: © Google Maps
                    </p>
                </div>
            </div>

            <div id="map"></div>
            <div id="flood-loading">⏳ Loading flood layer…</div>
        </div>
    </div>

    <!-- ── Hazard Modal ── -->
    <div class="hazard-modal-overlay" id="hazardModal">
        <div class="hazard-modal">
            <button class="hazard-modal-close" id="hazardModalCloseBtn">&times;</button>
            <div id="hazardModalContent"></div>
        </div>
    </div>

    <?php include '../Components/footer.php'; ?>
    <script src="../js/bootstrap.bundle.min.js"></script>

    <script>
    const listings = <?= json_encode($listings) ?>;

    const sanPedroBoundary = [[14.346677,121.0054681],[14.3464663,121.0055255],[14.3463462,121.0056215],[14.3462314,121.005749],[14.3461178,121.0061],[14.3460283,121.0062339],[14.345755,121.0064212],[14.345166,121.0069245],[14.3439677,121.006854],[14.341119,121.0083848],[14.3353709,121.0079253],[14.3327815,121.0068016],[14.3326789,121.0067748],[14.3315237,121.0069853],[14.3289094,121.007511],[14.3276152,121.0069907],[14.3267186,121.0067734],[14.3263352,121.0066135],[14.3258,121.0075437],[14.3255257,121.0079563],[14.3251048,121.0087823],[14.3221732,121.0106118],[14.3221393,121.0106268],[14.3215296,121.0108972],[14.3216184,121.0110368],[14.3216717,121.0112058],[14.3217715,121.011453],[14.3218224,121.0115907],[14.3218354,121.0118294],[14.3218978,121.0120292],[14.3219786,121.0122165],[14.3220134,121.0123484],[14.3220017,121.0125361],[14.3219991,121.0126689],[14.3220383,121.0127951],[14.3221447,121.0130846],[14.3222826,121.0133745],[14.3223708,121.0136533],[14.322484,121.01388],[14.3226164,121.0139926],[14.3227283,121.0141174],[14.322755,121.0141548],[14.3228487,121.0142553],[14.3229668,121.0143398],[14.3231543,121.0144445],[14.323352,121.0145399],[14.32363,121.0146405],[14.3238443,121.0147127],[14.3239548,121.0147704],[14.3240264,121.0148618],[14.3241341,121.0150695],[14.3241836,121.0152561],[14.3242108,121.0154369],[14.3241978,121.0155281],[14.3241809,121.0156329],[14.3241848,121.0157374],[14.3241666,121.0158272],[14.3241082,121.0159306],[14.3239976,121.0160968],[14.3239133,121.0162216],[14.323873,121.0163853],[14.3239029,121.0165019],[14.3240407,121.0166669],[14.3241627,121.0168397],[14.3242408,121.0170438],[14.3242848,121.0172045],[14.3243082,121.0173748],[14.3244604,121.0176942],[14.3246331,121.0180213],[14.3246811,121.0181983],[14.3247227,121.0183083],[14.3247748,121.0183969],[14.3248189,121.0186087],[14.3248631,121.0187253],[14.3249281,121.0188327],[14.3249449,121.0189734],[14.3249163,121.0191008],[14.3248423,121.0192592],[14.324802,121.0194053],[14.3247904,121.0195034],[14.3248747,121.0197419],[14.3249632,121.0199674],[14.3251257,121.0204649],[14.3252048,121.0206793],[14.3252334,121.0209985],[14.3252282,121.0212265],[14.3252361,121.0214372],[14.3252475,121.0215771],[14.3252686,121.0217738],[14.3252958,121.0218796],[14.3253803,121.0220005],[14.3258313,121.0224431],[14.3259846,121.0225382],[14.3260793,121.0225716],[14.32616,121.0225691],[14.3263119,121.0225542],[14.3264654,121.0224926],[14.32659,121.0224402],[14.3267279,121.0224269],[14.3268368,121.0224415],[14.3269176,121.0224914],[14.3269369,121.0225394],[14.3269252,121.0226333],[14.3269213,121.0227071],[14.3269382,121.022754],[14.3269734,121.0227836],[14.3271123,121.0228747],[14.3272371,121.0229888],[14.3272747,121.0230303],[14.3273046,121.0230973],[14.3274671,121.0232234],[14.3275606,121.0232797],[14.3276178,121.0233401],[14.3276634,121.0234233],[14.3277672,121.0235332],[14.3279218,121.0236405],[14.3280206,121.0237196],[14.3280818,121.0238123],[14.3282325,121.0241637],[14.328236,121.0243178],[14.3282142,121.0244626],[14.3282207,121.0246061],[14.3282402,121.0246611],[14.3282883,121.0247268],[14.3283975,121.0248154],[14.3285754,121.0250218],[14.3286469,121.0251613],[14.3287132,121.0253464],[14.3287977,121.0255329],[14.3289185,121.0256991],[14.329068,121.0259044],[14.3291147,121.0260196],[14.32919,121.0261242],[14.3293293,121.0262276],[14.3297268,121.0264771],[14.3299749,121.0265963],[14.3301867,121.0267371],[14.330431,121.0268445],[14.3306661,121.0270013],[14.3308779,121.0271072],[14.3310756,121.0272737],[14.3311625,121.0274103],[14.3312237,121.0276089],[14.331299,121.027857],[14.3314613,121.0281117],[14.3315315,121.0282686],[14.3315381,121.028419],[14.3315653,121.0285462],[14.3315913,121.0287461],[14.3316666,121.0288976],[14.3317434,121.0290694],[14.3318005,121.0292007],[14.3319655,121.0294662],[14.3319851,121.0295441],[14.3320384,121.0297949],[14.3320708,121.0299584],[14.3320826,121.0301489],[14.3321292,121.0303152],[14.3321702,121.0303804],[14.3322072,121.0304412],[14.3322969,121.0306585],[14.3323731,121.0308279],[14.3327335,121.0314069],[14.332801,121.0314766],[14.3329843,121.0316001],[14.3332818,121.031781],[14.3335039,121.0318843],[14.3335963,121.0319058],[14.3336989,121.0318977],[14.3338262,121.0319486],[14.3339511,121.031981],[14.334151,121.032021],[14.3343162,121.0320816],[14.334467,121.0322441],[14.3343082,121.0329612],[14.3340315,121.0335378],[14.3339665,121.0338557],[14.3338458,121.0343649],[14.3337444,121.0348723],[14.3334243,121.0363959],[14.332866,121.0386826],[14.3328159,121.0389669],[14.332647,121.0397341],[14.3325292,121.0402265],[14.3324337,121.0406254],[14.3323969,121.040721],[14.3323085,121.0408925],[14.3323884,121.0411471],[14.3328505,121.0422976],[14.3334721,121.0440484],[14.3339939,121.0455184],[14.3343521,121.0464483],[14.3348111,121.0476428],[14.3348308,121.0476921],[14.3348978,121.0479592],[14.3349546,121.0481315],[14.3349703,121.0481836],[14.3350302,121.0485161],[14.3351919,121.0493942],[14.3352506,121.0497606],[14.3353003,121.049983],[14.3357006,121.0499273],[14.3358948,121.0499409],[14.3360204,121.0499802],[14.3366201,121.0514015],[14.336906,121.0520602],[14.3370586,121.0523706],[14.3371691,121.0526065],[14.3379684,121.0544282],[14.3383045,121.0557285],[14.3384569,121.0563647],[14.3384584,121.0563681],[14.3391011,121.057792],[14.3392585,121.0581203],[14.3392806,121.0581792],[14.3384906,121.0585882],[14.3379086,121.0590068],[14.3376825,121.0587707],[14.3372641,121.0591113],[14.3370744,121.0588673],[14.3369497,121.0589424],[14.3367207,121.0591329],[14.3367611,121.0592081],[14.3360448,121.0595715],[14.3365558,121.0605708],[14.3369848,121.0614021],[14.3372081,121.061838],[14.337096,121.0619324],[14.3371211,121.061993],[14.3371545,121.062053],[14.3374601,121.0624031],[14.3379145,121.0620423],[14.3380015,121.0619701],[14.3381197,121.0621203],[14.3383454,121.0623776],[14.3385508,121.0626306],[14.3386318,121.0627309],[14.3391574,121.0633814],[14.3392149,121.0634736],[14.3392691,121.0635571],[14.3393498,121.0637899],[14.3394538,121.0641406],[14.3405071,121.0638264],[14.3406126,121.0637893],[14.3407816,121.0639655],[14.3412312,121.0644242],[14.3415872,121.0647702],[14.3418886,121.0650867],[14.3421272,121.0653366],[14.3423289,121.065554],[14.3425939,121.0657847],[14.3427706,121.0658759],[14.343124,121.0659563],[14.3436074,121.066069],[14.3437893,121.0660582],[14.3440439,121.0659456],[14.3444233,121.0657954],[14.3445741,121.0657632],[14.3447456,121.0658034],[14.3448291,121.0658634],[14.3449158,121.0659256],[14.3454452,121.0668803],[14.3456421,121.0671224],[14.3457642,121.0673457],[14.345837,121.0675281],[14.3459981,121.067807],[14.3462319,121.0682308],[14.3464554,121.0684991],[14.3466165,121.0687941],[14.3466789,121.0689604],[14.3468746,121.0691652],[14.3470213,121.0692174],[14.3471466,121.0692232],[14.3472506,121.0692018],[14.3473337,121.0691374],[14.3473909,121.069116],[14.3474585,121.0691535],[14.3475052,121.0692393],[14.3475052,121.0693735],[14.3476456,121.0695827],[14.3477807,121.0697651],[14.3479366,121.0699421],[14.3483004,121.0702157],[14.3485187,121.070339],[14.3489034,121.07072],[14.3486643,121.0714066],[14.3505353,121.0727155],[14.3530714,121.0748291],[14.3546513,121.0762775],[14.3555815,121.0761031],[14.3987691,121.1436651],[14.4114052,121.1343954],[14.4100751,121.1160277],[14.4065836,121.1045263],[14.4009306,121.0931967],[14.3875455,121.0741853],[14.3814971,121.0621047],[14.3805669,121.0584381],[14.3804682,121.0583496],[14.3803903,121.0582343],[14.3802786,121.0580518],[14.3800278,121.057333],[14.3799196,121.0571723],[14.3797287,121.0570225],[14.3795159,121.0568904],[14.3794314,121.0567952],[14.379321,121.0565176],[14.3791956,121.0564],[14.3790557,121.0563438],[14.3788687,121.0563384],[14.3785102,121.0563873],[14.3782775,121.0564424],[14.3780085,121.0564862],[14.3779167,121.0563862],[14.3778543,121.0562682],[14.377818,121.056075],[14.3777504,121.0559785],[14.3776153,121.0558444],[14.377501,121.055839],[14.3773139,121.0557585],[14.3770749,121.0555976],[14.3767631,121.0553455],[14.3764461,121.0550612],[14.3762954,121.0548949],[14.3759576,121.054691],[14.3756926,121.0544389],[14.37536,121.0541278],[14.3751522,121.054058],[14.374856,121.0540473],[14.3743831,121.0539561],[14.3741337,121.0538864],[14.3740142,121.0536932],[14.3740142,121.0535752],[14.3741597,121.053543],[14.3743935,121.0535752],[14.3745962,121.0535967],[14.3748144,121.0536611],[14.3749287,121.0536557],[14.3749963,121.0535591],[14.3750379,121.0534518],[14.3750898,121.0531783],[14.3750742,121.053012],[14.3749651,121.0527276],[14.3748716,121.0526043],[14.3747209,121.0525023],[14.3744974,121.0524433],[14.3742532,121.052497],[14.3741804,121.0525935],[14.3741337,121.0527759],[14.3741077,121.0528403],[14.3740194,121.052851],[14.373957,121.0528564],[14.3738375,121.0528564],[14.3736946,121.0527652],[14.3735906,121.0526606],[14.3735049,121.0526016],[14.3733952,121.0525694],[14.3732776,121.0526298],[14.3731639,121.0526827],[14.3730632,121.0526834],[14.3729307,121.0526257],[14.372806,121.0525292],[14.3725483,121.0524563],[14.3722967,121.0523923],[14.3720031,121.0523468],[14.3717952,121.0523039],[14.3716861,121.0522073],[14.371499,121.0519659],[14.3714024,121.0518144],[14.3713377,121.0517988],[14.37126,121.051813],[14.3711769,121.0518747],[14.3710781,121.0519123],[14.3711093,121.0520142],[14.3711249,121.0521054],[14.3710521,121.0522073],[14.3709222,121.0522395],[14.3707845,121.0521667],[14.3705818,121.0520517],[14.3704961,121.0519552],[14.3704805,121.0518211],[14.3704649,121.051636],[14.3704311,121.0514777],[14.3703766,121.0513973],[14.370322,121.0513436],[14.3702233,121.0513275],[14.3701401,121.0513866],[14.3701168,121.0514455],[14.3701427,121.0516172],[14.3701609,121.051703],[14.3701557,121.0517862],[14.3700492,121.0517782],[14.3699089,121.0516843],[14.3696698,121.0515261],[14.3695555,121.0513114],[14.3695036,121.0511398],[14.369497,121.050944],[14.3694711,121.0508824],[14.3694074,121.0508542],[14.3691645,121.0508595],[14.3690774,121.0506302],[14.3690774,121.0502546],[14.3691772,121.0499371],[14.3690878,121.0497128],[14.3690306,121.0496243],[14.3689355,121.0495978],[14.3686603,121.0496116],[14.3684708,121.0495415],[14.3682772,121.0494419],[14.3680849,121.0493105],[14.3679711,121.049233],[14.3678718,121.0489511],[14.3676795,121.0488331],[14.3675081,121.0487419],[14.3673677,121.0485434],[14.3673106,121.0483181],[14.3673616,121.0482267],[14.3674313,121.0481687],[14.367572,121.0481048],[14.3677594,121.0479607],[14.3679293,121.0478427],[14.3680212,121.0477191],[14.3680381,121.0475349],[14.3680381,121.047374],[14.3679913,121.0471916],[14.3679238,121.0470682],[14.3679134,121.0469984],[14.3679758,121.0468482],[14.3679446,121.0467624],[14.3678666,121.0467356],[14.3677003,121.0467141],[14.3676533,121.0466309],[14.3676386,121.046521],[14.367675,121.046379],[14.3676172,121.046285],[14.367534,121.0461884],[14.3674509,121.0461723],[14.3673651,121.0461911],[14.3672521,121.0463011],[14.3671794,121.046348],[14.3671184,121.046328],[14.3670671,121.0462131],[14.3670183,121.0460341],[14.3670418,121.0459457],[14.3671079,121.0458639],[14.3672002,121.0457888],[14.3672586,121.0457337],[14.3672729,121.0455595],[14.3673093,121.0454856],[14.3674223,121.0453998],[14.3677185,121.0453918],[14.368029,121.045432],[14.368359,121.0455232],[14.3686188,121.0455876],[14.3687553,121.045589],[14.3687799,121.0455514],[14.3687929,121.0454776],[14.3687799,121.045361],[14.3687345,121.0452657],[14.3686409,121.0452175],[14.3684902,121.0452175],[14.3684071,121.0452255],[14.3683655,121.0452202],[14.3682694,121.0451692],[14.368259,121.045027],[14.3683187,121.04496],[14.3684331,121.0449359],[14.3685422,121.0449358],[14.3687033,121.0449358],[14.3688306,121.0449278],[14.3689994,121.0449171],[14.3691554,121.044917],[14.369475,121.0449063],[14.3696543,121.0448392],[14.3697557,121.0447669],[14.369831,121.0446167],[14.3698414,121.044382],[14.3698869,121.0443538],[14.3700401,121.0443592],[14.3701935,121.0442559],[14.3702649,121.0441433],[14.3704338,121.0440775],[14.3706104,121.0440963],[14.3707585,121.044166],[14.3708495,121.0441955],[14.3709222,121.0441607],[14.3711093,121.0440587],[14.3713587,121.0437422],[14.3715094,121.0436403],[14.3717225,121.0435813],[14.3719927,121.0433989],[14.3719823,121.0432434],[14.3718836,121.0430663],[14.3717173,121.0429],[14.3713205,121.0427195],[14.3711437,121.0425538],[14.3708682,121.0422955],[14.3701323,121.0419237],[14.3699816,121.041795],[14.3698569,121.041634],[14.3698517,121.0412585],[14.3697374,121.0410064],[14.369384,121.0407435],[14.3692619,121.0405719],[14.3691087,121.0402672],[14.3690234,121.0400053],[14.3691396,121.0395539],[14.3691661,121.0393624],[14.3689941,121.0391902],[14.368643,121.0389776],[14.3681228,121.0386607],[14.3681104,121.0382827],[14.368049,121.0379539],[14.3680043,121.0375961],[14.3677227,121.0372646],[14.3676505,121.0369481],[14.3675263,121.0367099],[14.3672628,121.0365055],[14.3669744,121.0364937],[14.3667878,121.0364572],[14.3664531,121.0363028],[14.3663565,121.0361407],[14.3664121,121.0359283],[14.3665649,121.0357513],[14.3667509,121.0354917],[14.3667546,121.0352374],[14.3665311,121.0349332],[14.3663009,121.0345373],[14.3662983,121.0341886],[14.3663425,121.0338512],[14.3662276,121.0335036],[14.3658826,121.0331318],[14.3656757,121.0328904],[14.3655862,121.0327619],[14.3653977,121.0324913],[14.365433,121.0319785],[14.3656929,121.0313627],[14.3659537,121.0306792],[14.3660005,121.0301535],[14.3659205,121.0296128],[14.365749,121.0291461],[14.3653717,121.029159],[14.364931,121.0292341],[14.3644789,121.0291858],[14.3641952,121.0289476],[14.3637722,121.0287899],[14.3634126,121.0285839],[14.3631013,121.0281317],[14.3628264,121.027768],[14.3627526,121.0273141],[14.3626595,121.0269628],[14.3623472,121.0266693],[14.3619434,121.0265363],[14.3615558,121.0265384],[14.3613357,121.0264743],[14.3611894,121.0264317],[14.3608989,121.0262353],[14.3604961,121.0262852],[14.3602482,121.0262219],[14.3600627,121.0260497],[14.3597914,121.0260009],[14.3594505,121.025907],[14.3592442,121.0257858],[14.358718,121.0252016],[14.3582693,121.0247033],[14.3580786,121.0245509],[14.3579954,121.024405],[14.3581789,121.0242376],[14.3581757,121.0241019],[14.3573604,121.0230827],[14.3571047,121.0228842],[14.3568209,121.0227748],[14.3565159,121.0227447],[14.3562623,121.0227254],[14.3560775,121.0227359],[14.3558678,121.0225049],[14.3556922,121.022256],[14.3556287,121.0218869],[14.3556443,121.0215731],[14.3554827,121.0213462],[14.3552281,121.0211542],[14.3552704,121.0208652],[14.3552413,121.02073],[14.3549661,121.0206848],[14.3547785,121.02071],[14.3546029,121.0206703],[14.3544745,121.0205877],[14.3543425,121.0202792],[14.3541637,121.0200244],[14.3539423,121.0197782],[14.3536918,121.0195958],[14.3532283,121.0195352],[14.3530526,121.0194504],[14.352917,121.0192407],[14.3528759,121.0189719],[14.3527507,121.0186957],[14.3527408,121.0184553],[14.3527803,121.0182048],[14.3527829,121.0179758],[14.3526098,121.0177955],[14.3523926,121.0176469],[14.3523692,121.0174704],[14.3523983,121.0172419],[14.3523505,121.017001],[14.3522034,121.0167666],[14.3520922,121.0165874],[14.3522775,121.0162829],[14.3522458,121.0161096],[14.3521377,121.0160984],[14.3518757,121.01613],[14.3516175,121.0161817],[14.3515359,121.0160817],[14.3514283,121.0159019],[14.3513566,121.0157529],[14.3512812,121.0156097],[14.3512074,121.0155324],[14.3510754,121.0154976],[14.3510217,121.0153925],[14.3509769,121.0153048],[14.3505092,121.0145645],[14.3504016,121.0142142],[14.3498154,121.0139878],[14.3497123,121.0138587],[14.3495691,121.0136794],[14.3492523,121.0133774],[14.3472554,121.0113193],[14.3478051,121.0104942],[14.3483915,121.0096139],[14.3485188,121.0094677],[14.3485123,121.0092223],[14.3484396,121.0091762],[14.3481383,121.0088597],[14.3478744,121.0085617],[14.348001,121.0080065],[14.3482275,121.0080442],[14.3481392,121.0086288],[14.3483667,121.0088109],[14.3484805,121.0085634],[14.3487295,121.0086933],[14.3489627,121.0083047],[14.3491182,121.0080272],[14.3491862,121.0079059],[14.3497497,121.0069735],[14.3495079,121.0068451],[14.3495362,121.006764],[14.3494403,121.0058648],[14.3485204,121.0059707],[14.3480964,121.0058826],[14.3478994,121.0056799],[14.346677,121.0054681]];

    const evacCenters = [
        {name:'Landayan Evacuation Center',  lat:14.3578, lng:121.0597},
        {name:'Rosario Evacuation Center',   lat:14.3503, lng:121.0573},
        {name:'San Roque Evacuation Point',  lat:14.3500, lng:121.0620},
        {name:'Poblacion Evacuation Point',  lat:14.3615, lng:121.0560},
        {name:'Cuyab Evacuation Zone',       lat:14.3440, lng:121.0640},
        {name:'Bagong Silang Evac Center',   lat:14.3660, lng:121.0490},
        {name:'San Antonio Evac Center',     lat:14.3535, lng:121.0490},
        {name:'San Vicente Evac Point',      lat:14.3565, lng:121.0520},
    ];

    const faultPath = [
        {lat:14.3875, lng:121.0362},{lat:14.3858, lng:121.0368},{lat:14.3842, lng:121.0373},
        {lat:14.3825, lng:121.0378},{lat:14.3808, lng:121.0382},{lat:14.3792, lng:121.0385},
        {lat:14.3775, lng:121.0388},{lat:14.3758, lng:121.0390},{lat:14.3742, lng:121.0391},
        {lat:14.3725, lng:121.0392},{lat:14.3708, lng:121.0391},{lat:14.3692, lng:121.0388},
        {lat:14.3675, lng:121.0383},{lat:14.3658, lng:121.0377},{lat:14.3642, lng:121.0370},
        {lat:14.3625, lng:121.0362},{lat:14.3608, lng:121.0354},{lat:14.3592, lng:121.0346},
        {lat:14.3575, lng:121.0338},{lat:14.3558, lng:121.0330},{lat:14.3542, lng:121.0322},
        {lat:14.3525, lng:121.0314},{lat:14.3508, lng:121.0306},{lat:14.3492, lng:121.0298},
        {lat:14.3475, lng:121.0290},{lat:14.3458, lng:121.0282},{lat:14.3442, lng:121.0274},
        {lat:14.3425, lng:121.0266},{lat:14.3408, lng:121.0258},{lat:14.3390, lng:121.0250},
    ];

    const faultPathSecondary = [
        {lat:14.3822, lng:121.0435},{lat:14.3805, lng:121.0440},{lat:14.3788, lng:121.0443},
        {lat:14.3770, lng:121.0445},{lat:14.3752, lng:121.0447},{lat:14.3735, lng:121.0448},
        {lat:14.3718, lng:121.0450},{lat:14.3700, lng:121.0452},{lat:14.3682, lng:121.0455},
        {lat:14.3665, lng:121.0458},{lat:14.3648, lng:121.0462},{lat:14.3630, lng:121.0466},
        {lat:14.3612, lng:121.0470},{lat:14.3595, lng:121.0474},{lat:14.3578, lng:121.0478},
        {lat:14.3560, lng:121.0482},{lat:14.3542, lng:121.0486},{lat:14.3525, lng:121.0490},
    ];

    const floodColors = { 1: '#FFFF00', 2: '#FF8000', 3: '#FF0000' };
    const floodLabels = { 1: 'Low',     2: 'Medium',  3: 'High'   };
    const priceColor  = p => p <= 10000 ? '#059669' : p <= 20000 ? '#d97706' : '#7c3aed';

    let map, iw;
    // FIX: All marker arrays declared at top scope so toggles can reach them
    let listingMarkers  = [];
    let hospitalMarkers = [];   // was never populated in original
    let evacMarkers     = [];
    let faultPolyline = null, faultPolylineSecondary = null, borderPolygon = null, floodLayer = null;

    function initMap() {
        map = new google.maps.Map(document.getElementById('map'), {
            center: { lat: 14.3580, lng: 121.0480 },
            zoom: 13,
            mapTypeId: 'roadmap',
            mapTypeControl: true,
            fullscreenControl: true,
            streetViewControl: false,
        });

        iw = new google.maps.InfoWindow({ pixelOffset: new google.maps.Size(0, -4) });

        // ── 1. City Boundary ──
        borderPolygon = new google.maps.Polygon({
            paths: sanPedroBoundary.map(c => ({ lat: c[0], lng: c[1] })),
            strokeColor: '#0ea5e9', strokeOpacity: 0.85, strokeWeight: 2.5,
            fillColor: '#0ea5e9', fillOpacity: 0.04,
            map: map, zIndex: 1,
        });
        borderPolygon.addListener('click', e => {
            iw.setContent(`<div class="popup-info">
                <p class="pi-title">City of San Pedro, Laguna</p>
                <p class="pi-sub">Region IV-A · CALABARZON · 4023<br>Total area: 24.06 km²</p>
            </div>`);
            iw.setPosition(e.latLng);
            iw.open(map);
        });

        // ── 2. Flood Hazard GeoJSON ──
        const floodLoading = document.getElementById('flood-loading');
        floodLoading.style.display = 'block';
        floodLayer = new google.maps.Data({ map: map });
        floodLayer.loadGeoJson(
            'sanedrofloodzone.geojson',
            null,
            () => { floodLoading.style.display = 'none'; }
        );
        floodLayer.setStyle(feature => {
            const v = Math.round(Number(feature.getProperty('Var')));
            const color = floodColors[v] || '#aaaaaa';
            return {
                fillColor: color, fillOpacity: 0.55,
                strokeColor: color, strokeWeight: 0.5, strokeOpacity: 0.8, zIndex: 2,
            };
        });
        floodLayer.addListener('click', e => {
            const v     = Math.round(Number(e.feature.getProperty('Var')));
            const label = floodLabels[v] || 'Unknown';
            showHazardModal('flood', label);
        });

        // ── 3. West Valley Fault — Main (Red Solid) ──
        faultPolyline = new google.maps.Polyline({
            path: faultPath,
            strokeColor: '#dc2626', strokeOpacity: 0.95, strokeWeight: 3.5,
            map: map, zIndex: 5,
        });
        faultPolyline.addListener('click', e => {
            iw.setContent(`<div class="popup-info">
                <p class="pi-title">West Valley Fault — Certain Trace</p>
                <span class="pi-badge" style="background:#fee2e2;color:#991b1b;">⚠ Active Fault — Trace Certain</span>
                <p class="pi-sub">
                    Source: PHIVOLCS AFT-2021-043425-02<br>
                    Fault System: Valley Fault System (VFS)<br>
                    Mandatory buffer: ≥ 5m both sides<br>
                    Rupture potential: M7.2–7.6<br>
                    <span style="color:#dc2626;font-weight:800;">No construction allowed within 5m of trace</span>
                </p>
            </div>`);
            iw.setPosition(e.latLng);
            iw.open(map);
        });

        // ── 4. Secondary Fault Trace (Orange Dashed) ──
        faultPolylineSecondary = new google.maps.Polyline({
            path: faultPathSecondary,
            strokeColor: '#f97316', strokeOpacity: 0.85, strokeWeight: 2.5,
            icons: [{
                icon: { path: 'M 0,-1 0,1', strokeOpacity: 1, scale: 3 },
                offset: '0', repeat: '12px'
            }],
            map: map, zIndex: 5,
        });
        faultPolylineSecondary.addListener('click', e => {
            iw.setContent(`<div class="popup-info">
                <p class="pi-title">West Valley Fault — Approximate Trace</p>
                <span class="pi-badge" style="background:#ffedd5;color:#c2410c;">⚠ Active Fault — Trace Approximate</span>
                <p class="pi-sub">
                    Source: PHIVOLCS AFT-2021-043425-02<br>
                    Fault System: Valley Fault System (VFS)<br>
                    Exact trace location still being verified<br>
                    Treat same as certain trace for safety
                </p>
            </div>`);
            iw.setPosition(e.latLng);
            iw.open(map);
        });


        // ── 6. Evacuation Centers ──
        // FIX: markers pushed into evacMarkers[] (was correct in original but confirmed here)
        evacCenters.forEach(ec => {
            const marker = new google.maps.Marker({
                position: { lat: ec.lat, lng: ec.lng }, map, title: ec.name,
                icon: {
                    path: google.maps.SymbolPath.CIRCLE,
                    scale: 9, fillColor: '#2563eb', fillOpacity: 1,
                    strokeColor: '#fff', strokeWeight: 2
                },
                zIndex: 10,
            });
            marker.addListener('click', () => {
                iw.setContent(`<div class="popup-info">
                    <p class="pi-title">⛺ ${ec.name}</p>
                    <p class="pi-sub">Evacuation Center · San Pedro, Laguna</p>
                </div>`);
                iw.open(map, marker);
            });
            evacMarkers.push(marker); // was correct in original
        });

        // ── 7. Available Listings ──
        let statTotal = 0, statLow = 0, statMid = 0, statHigh = 0;
        listings.forEach(item => {
            statTotal++;
            const p = Number(item.price);
            if      (p <= 10000) statLow++;
            else if (p <= 20000) statMid++;
            else                 statHigh++;

            const color  = priceColor(p);
            const svgPin = `<svg xmlns="http://www.w3.org/2000/svg" width="28" height="36" viewBox="0 0 28 36"><path d="M14 0C6.268 0 0 6.268 0 14c0 10.5 14 22 14 22S28 24.5 28 14C28 6.268 21.732 0 14 0z" fill="${color}" stroke="#fff" stroke-width="2"/><circle cx="14" cy="14" r="5" fill="#fff"/></svg>`;
            const iconUrl = 'data:image/svg+xml;charset=UTF-8,' + encodeURIComponent(svgPin);

            const marker = new google.maps.Marker({
                position: { lat: parseFloat(item.latitude), lng: parseFloat(item.longitude) },
                map, title: item.listingName,
                icon: { url: iconUrl, scaledSize: new google.maps.Size(28, 36), anchor: new google.maps.Point(14, 36) },
                zIndex: 1000,
            });

            const dirUrl = `https://www.google.com/maps/dir/?api=1&destination=${item.latitude},${item.longitude}`;
            marker.addListener('click', () => {
                iw.setContent(`
                    <div class="popup-card">
                        <p class="popup-title">${item.listingName}</p>
                        <p class="popup-sub">San Pedro, Laguna &nbsp;·&nbsp; Available</p>
                        <p class="popup-price" style="color:${color};">
                            ₱${p.toLocaleString()}
                            <span style="font-size:12px;font-weight:600;color:#bbb;">/ mo</span>
                        </p>
                        <div class="popup-actions">
                            <a href="property-details.php?ID=${item.listing_id}" class="popup-btn popup-btn-primary">View Listing</a>
                            <a href="${dirUrl}" target="_blank" class="popup-btn popup-btn-secondary">Directions</a>
                        </div>
                    </div>`);
                iw.open(map, marker);
            });
            listingMarkers.push(marker);
        });

        document.getElementById('stat-total').textContent = statTotal;
        document.getElementById('stat-low').textContent   = statLow;
        document.getElementById('stat-mid').textContent   = statMid;
        document.getElementById('stat-high').textContent  = statHigh;

        // ── Layer Toggles ──
        // FIX: all six toggles now correctly wired to populated arrays/objects
        const setMarkers = (arr, show) => arr.forEach(m => m.setMap(show ? map : null));

        document.getElementById('tog-listings').addEventListener('change', function() {
            setMarkers(listingMarkers, this.checked);
        });
        document.getElementById('tog-evac').addEventListener('change', function() {
            // FIX: now works because evacMarkers is confirmed populated above
            setMarkers(evacMarkers, this.checked);
        });
        document.getElementById('tog-border').addEventListener('change', function() {
            borderPolygon.setMap(this.checked ? map : null);
        });
        document.getElementById('tog-flood').addEventListener('change', function() {
            floodLayer.setMap(this.checked ? map : null);
        });
        document.getElementById('tog-fault').addEventListener('change', function() {
            faultPolyline.setMap(this.checked ? map : null);
            faultPolylineSecondary.setMap(this.checked ? map : null);
        });

    } // end initMap

    // ══════════════════════════════════════════════════════════
    // Hazard Modal — Flood / Earthquake / Storm Surge
    // ══════════════════════════════════════════════════════════
    function showHazardModal(type, level) {
        const content = document.getElementById('hazardModalContent');

        if (type === 'flood') {
            const bgColors  = { Low: '#FFFF00', Medium: '#FF8000', High: '#FF0000', General: '#1e40af' };
            const txtColors = { Low: '#333',    Medium: '#fff',    High: '#fff',    General: '#fff'    };
            const depthInfo = { Low: '0.1 – 0.5m (ankle to knee)', Medium: '0.5 – 1.5m (waist to overhead)', High: 'Above 1.5m (life-threatening)', General: '' };
            content.innerHTML = `
                <h2 style="font-size:18px;font-weight:900;margin:0 0 4px;">🌊 Know Your Hazard: Flooding</h2>
                <p style="font-size:12px;color:#999;font-weight:600;margin:0 0 10px;">San Pedro, Laguna · PAGASA / NDRRMC</p>
                ${level !== 'General' ? `
                <span class="hazard-badge" style="background:${bgColors[level]||'#eee'};color:${txtColors[level]||'#333'};">
                    ${level} Flood Risk Zone
                </span>
                <p style="font-size:12px;color:#666;font-weight:700;margin:0 0 12px;">
                    Estimated water depth: <strong>${depthInfo[level]}</strong>
                </p>` : ''}
                <p style="font-size:13px;color:#555;font-weight:600;line-height:1.6;margin:0 0 12px;">
                    Flooding occurs when water overflows onto normally dry land, often caused by heavy or prolonged rainfall,
                    typhoons, or the overflow of rivers and lakes. Laguna de Bay and nearby river systems
                    make San Pedro, Laguna particularly susceptible during the rainy season (June–November).
                </p>
                <div class="hazard-section-title">Flood Susceptibility Levels</div>
                <div class="hazard-level-row"><span class="hazard-level-dot" style="background:#FFFF00;border:1px solid #ccc;"></span>Low — 0.1–0.5m · Minor flooding; minimal risk to life</div>
                <div class="hazard-level-row"><span class="hazard-level-dot" style="background:#FF8000;"></span>Medium — 0.5–1.5m · Moderate flooding; mobility impaired</div>
                <div class="hazard-level-row"><span class="hazard-level-dot" style="background:#FF0000;"></span>High — Above 1.5m · Severe flooding; life-threatening</div>
                <div class="hazard-section-title">PAGASA Rainfall Warning Signals</div>
                <div class="hazard-level-row"><span class="hazard-level-dot" style="background:#FFFF00;border:1px solid #ccc;"></span>Yellow — 7.5–15mm/hr · Moderate; stay alert</div>
                <div class="hazard-level-row"><span class="hazard-level-dot" style="background:#FF8000;"></span>Orange — 15–30mm/hr · Heavy; prepare to evacuate</div>
                <div class="hazard-level-row"><span class="hazard-level-dot" style="background:#FF0000;"></span>Red — >30mm/hr · Intense; evacuate immediately</div>
                <div class="hazard-section-title">Before a Flood</div>
                <ul class="hazard-list">
                    <li>Know your Barangay's evacuation route and nearest Evacuation Center.</li>
                    <li>Prepare a Go-Bag for at least 72 hours of self-sufficiency.</li>
                    <li>Store important documents in a waterproof pouch or sealed bag.</li>
                    <li>Monitor PAGASA weather bulletins and NDRRMC advisories.</li>
                    <li>Elevate appliances and valuables off the ground floor if flooding is expected.</li>
                </ul>
                <div class="hazard-section-title">During a Flood</div>
                <ul class="hazard-list">
                    <li>Follow the Mandatory Evacuation Order issued by your Barangay immediately.</li>
                    <li>Never walk through moving floodwater — 15cm of fast-moving water can knock an adult down.</li>
                    <li>Never drive through flooded roads — 30cm of water can stall a vehicle.</li>
                    <li>Turn off electricity at the main breaker and shut off LPG supply valves before evacuating.</li>
                    <li>Call 911 (National Emergency Hotline) if you need rescue assistance.</li>
                </ul>
                <div class="hazard-section-title">After a Flood</div>
                <ul class="hazard-list">
                    <li>Return home only after your Barangay or LGU officially declares the area safe.</li>
                    <li>Do not use tap water until local authorities confirm it is safe.</li>
                    <li>Wear rubber boots and gloves when cleaning; floodwater may contain sewage and pathogens.</li>
                    <li>Watch for leptospirosis symptoms — seek medical attention immediately if symptoms appear.</li>
                    <li>Document damage with photos for DSWD Assistance and insurance claims.</li>
                </ul>
                <div class="hazard-section-title">Key Emergency Contacts</div>
                <ul class="hazard-list">
                    <li><strong>National Emergency Hotline:</strong> 911</li>
                    <li><strong>NDRRMC Operations Center:</strong> (02) 8911-1406 / 8911-1873</li>
                    <li><strong>San Pedro, Laguna CDRRMO:</strong> (02) 8403-2648 / 0998 594 1743</li>
                    <li><strong>Philippine Red Cross:</strong> 143</li>
                </ul>`;

        } else if (type === 'earthquake') {
            content.innerHTML = `
                <h2 style="font-size:18px;font-weight:900;margin:0 0 4px;">🏚️ Know Your Hazard: Earthquake</h2>
                <p style="font-size:12px;color:#999;font-weight:600;margin:0 0 10px;">San Pedro, Laguna · PHIVOLCS</p>
                <span class="hazard-badge" style="background:#fee2e2;color:#991b1b;">⚠ West Valley Fault — Active, Certain</span>
                <p style="font-size:13px;color:#555;font-weight:600;line-height:1.6;margin:8px 0 12px;">
                    San Pedro City sits along the <strong>West Valley Fault (WVF)</strong>, part of the Valley Fault System.
                    A major rupture is estimated at <strong>M7.2–7.6</strong> and could affect millions in the CALABARZON region.
                </p>
                <div class="hazard-section-title">Fault Hazard Zones</div>
                <div class="hazard-level-row"><span class="hazard-level-dot" style="background:#dc2626;"></span>0–5m from trace — Ground rupture zone, NO construction allowed</div>
                <div class="hazard-level-row"><span class="hazard-level-dot" style="background:#f97316;"></span>5–50m — High hazard: strong shaking + liquefaction risk</div>
                <div class="hazard-level-row"><span class="hazard-level-dot" style="background:#facc15;border:1px solid #ccc;"></span>50–100m — Moderate hazard: structural damage possible</div>
                <div class="hazard-section-title">Before an Earthquake</div>
                <ul class="hazard-list">
                    <li>Identify safe spots in every room (under sturdy tables, inner walls).</li>
                    <li>Secure heavy furniture, bookshelves, and appliances to walls.</li>
                    <li>Know how to turn off gas, water, and electricity.</li>
                    <li>Prepare an emergency kit and family communication plan.</li>
                    <li>Practice earthquake drills — Drop, Cover, Hold On.</li>
                </ul>
                <div class="hazard-section-title">During an Earthquake</div>
                <ul class="hazard-list">
                    <li><strong>DROP</strong> to your hands and knees immediately.</li>
                    <li><strong>COVER</strong> your head and neck under a sturdy table or against an inner wall.</li>
                    <li><strong>HOLD ON</strong> until the shaking stops.</li>
                    <li>Stay away from windows, heavy furniture, and exterior walls.</li>
                    <li>Do NOT run outside while shaking is happening — most injuries occur from falling debris.</li>
                </ul>
                <div class="hazard-section-title">After an Earthquake</div>
                <ul class="hazard-list">
                    <li>Expect aftershocks — repeat Drop, Cover, Hold On.</li>
                    <li>Check for gas leaks — if you smell gas, open windows and leave immediately.</li>
                    <li>Do not use elevators after an earthquake.</li>
                    <li>Listen to official broadcasts (DOST-PHIVOLCS, NDRRMC).</li>
                </ul>
                <div class="hazard-section-title">Key Emergency Contacts</div>
                <ul class="hazard-list">
                    <li><strong>National Emergency Hotline:</strong> 911</li>
                    <li><strong>NDRRMC Operations Center:</strong> (02) 8911-1406 / 8911-1873</li>
                    <li><strong>San Pedro, Laguna CDRRMO:</strong> (02) 8403-2648 / 0998 594 1743</li>
                    <li><strong>Philippine Red Cross:</strong> 143</li>
                </ul>`;

        } else if (type === 'stormsurge') {
            content.innerHTML = `
                <h2 style="font-size:18px;font-weight:900;margin:0 0 4px;">🌀 Know Your Hazard: Typhoon &amp; Storm</h2>
                <p style="font-size:12px;color:#999;font-weight:600;margin:0 0 10px;">San Pedro, Laguna · PAGASA / NDRRMC</p>
                <span class="hazard-badge" style="background:#e0f2fe;color:#0369a1;">ℹ San Pedro is inland — limited storm surge risk</span>
                <p style="font-size:13px;color:#555;font-weight:600;line-height:1.6;margin:8px 0 12px;">
                    San Pedro City is <strong>not directly coastal</strong>, so it is not in a primary storm surge zone.
                    However, during strong typhoons, <strong>Laguna de Bay</strong> water levels can rise significantly,
                    causing lakeshore flooding. Combined with heavy rainfall, flash flooding is a serious concern.
                </p>
                <div class="hazard-section-title">PAGASA Storm Signal Levels</div>
                <div class="hazard-level-row"><span class="hazard-level-dot" style="background:#facc15;border:1px solid #ccc;"></span>Signal 1 — 30–60 km/h winds expected in 36 hrs</div>
                <div class="hazard-level-row"><span class="hazard-level-dot" style="background:#f97316;"></span>Signal 2 — 60–90 km/h winds expected in 24 hrs</div>
                <div class="hazard-level-row"><span class="hazard-level-dot" style="background:#ef4444;"></span>Signal 3 — 90–120 km/h winds expected in 18 hrs</div>
                <div class="hazard-level-row"><span class="hazard-level-dot" style="background:#991b1b;"></span>Signal 4 &amp; 5 — 120 km/h+ winds, extreme danger</div>
                <div class="hazard-section-title">Before a Typhoon</div>
                <ul class="hazard-list">
                    <li>Monitor PAGASA typhoon bulletins and local DRRMO advisories.</li>
                    <li>Prepare and update your emergency Go-Bag.</li>
                    <li>Reinforce windows and doors; bring loose items indoors.</li>
                    <li>Stock up on food, water, and medicines for at least 3 days.</li>
                    <li>Know your barangay's evacuation route and designated center.</li>
                </ul>
                <div class="hazard-section-title">During a Typhoon</div>
                <ul class="hazard-list">
                    <li>Stay indoors and away from windows.</li>
                    <li>Do not go outside during the eye of the storm — winds will return.</li>
                    <li>Evacuate immediately if near rivers, esteros, or low-lying areas.</li>
                    <li>Never attempt to cross flooded roads or bridges.</li>
                </ul>
                <div class="hazard-section-title">After a Typhoon</div>
                <ul class="hazard-list">
                    <li>Wait for all-clear signal from local authorities before going outside.</li>
                    <li>Avoid downed power lines — treat all as live.</li>
                    <li>Boil all drinking water until declared safe.</li>
                </ul>
                <div class="hazard-section-title">Emergency Contacts</div>
                <ul class="hazard-list">
                    <li><strong>National Emergency Hotline:</strong> 911</li>
                    <li><strong>San Pedro CDRRMO:</strong> (02) 8403-2649 / 0998 594 1743</li>
                    <li><strong>PAGASA:</strong> (02) 8284-0800</li>
                    <li><strong>Red Cross:</strong> 143</li>
                </ul>`;
        }

        document.getElementById('hazardModal').classList.add('active');
    }

    document.getElementById('hazardModalCloseBtn').addEventListener('click', () => {
        document.getElementById('hazardModal').classList.remove('active');
    });
    document.getElementById('hazardModal').addEventListener('click', function(e) {
        if (e.target === this) this.classList.remove('active');
    });
    </script>

    <script async defer
        src="https://maps.googleapis.com/maps/api/js?key=AIzaSyCZ3GIqm75W_KKyz1dfW_Pvjw1PeJDpEJU&callback=initMap">
    </script>
</body>
</html>