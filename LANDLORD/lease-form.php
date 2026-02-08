<?php
require_once '../connection.php';
include '../session_auth.php';
require('../fpdf/fpdf.php');

/* =========================
   AUTH CHECK
========================= */
if (!isset($_SESSION['landlord_id'])) {
    die("Unauthorized access.");
}

$landlord_id = (int) $_SESSION['landlord_id'];
$request_id = (int) ($_GET['request_id'] ?? 0);
$listing_id = (int) ($_GET['listing_id'] ?? 0);

if ($request_id <= 0 || $listing_id <= 0) {
    die("Invalid request.");
}

/* =========================
   FETCH REQUEST INFO
========================= */
$stmt = $conn->prepare("
    SELECT 
        r.ID AS request_id,
        r.tenant_id,
        t.firstName AS tenant_first,
        t.lastName AS tenant_last,
        ls.listingName,
        ls.price,
        l.firstName AS landlord_first,
        l.lastName AS landlord_last
    FROM requesttbl r
    JOIN tenanttbl t ON r.tenant_id = t.ID
    JOIN listingtbl ls ON r.listing_id = ls.ID
    JOIN landlordtbl l ON ls.landlord_id = l.ID
    WHERE r.ID = ? AND ls.landlord_id = ?
");
$stmt->bind_param("ii", $request_id, $landlord_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    die("Request not found.");
}

$request = $result->fetch_assoc();
$stmt->close();

/* =========================
   CHECK EXISTING LEASE
========================= */
$check = $conn->prepare("
    SELECT ID FROM leasetbl 
    WHERE tenant_id = ? AND listing_id = ?
");
$check->bind_param("ii", $request['tenant_id'], $listing_id);
$check->execute();
$check->store_result();

if ($check->num_rows > 0) {
    die("Lease already exists for this request.");
}
$check->close();

/* =========================
   FORM SUBMIT
========================= */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $start_date = $_POST['start_date'];
    $end_date = $_POST['end_date'];
    $rent = (float) $_POST['rent'];
    $deposit = (float) $_POST['deposit'];

    if ($end_date <= $start_date) {
        die("Invalid contract dates.");
    }

    /* =========================
       TERMS
    ========================== */
    $terms = $_POST['terms'] ?? [];
    $custom = $_POST['custom_terms'] ?? [];
    $all_terms = array_filter(array_merge($terms, $custom));
    $terms_json = json_encode($all_terms);

    /* =========================
       INSERT LEASE
    ========================= */
    $status = 'pending';
    $pdf_path = '';

    $insert = $conn->prepare("
    INSERT INTO leasetbl
    (listing_id, tenant_id, landlord_id,
     start_date, end_date, rent, deposit,
     terms, status, pdf_path)
    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
");

    $insert->bind_param(
        "iiissddsss",
        $listing_id,
        $request['tenant_id'],
        $landlord_id,
        $start_date,
        $end_date,
        $rent,
        $deposit,
        $terms_json,
        $status,
        $pdf_path
    );

    $insert->execute();
    $lease_id = $conn->insert_id;
    $insert->close();

    /* =========================
       LINK LEASE TO REQUEST
    ========================= */
    $updateRequest = $conn->prepare("
    UPDATE requesttbl
    SET lease_id = ?
    WHERE ID = ?
");
    $updateRequest->bind_param("ii", $lease_id, $request['ID']); // <-- request ID
    $updateRequest->execute();
    $updateRequest->close();

    /* =========================
       GENERATE PDF
    ========================= */

    $pdf = new FPDF();
    $pdf->AddPage();
    $pdf->SetAutoPageBreak(true, 25);

    // Title
    $pdf->SetFont('Arial', 'B', 18);
    $pdf->Cell(0, 12, 'LEASE AGREEMENT', 0, 1, 'C');
    $pdf->Ln(10);

    // Helper function
    function pdfRow($pdf, $label, $value)
    {
        $pdf->SetFont('Arial', 'B', 12);
        $pdf->Cell(45, 8, $label, 0, 0);
        $pdf->SetFont('Arial', '', 12);
        $pdf->MultiCell(0, 8, $value);
        $pdf->Ln(2);
    }

    /* =========================
       LEASE DETAILS
    ========================= */
    pdfRow($pdf, "Property:", $request['listingName']);
    pdfRow($pdf, "Tenant:", "{$request['tenant_first']} {$request['tenant_last']}");
    pdfRow($pdf, "Landlord:", "{$request['landlord_first']} {$request['landlord_last']}");
    pdfRow($pdf, "Rent:", "PHP " . number_format($rent, 2));
    pdfRow($pdf, "Deposit:", "PHP " . number_format($deposit, 2));
    pdfRow($pdf, "Contract Period:", "$start_date to $end_date");

    $pdf->Ln(10);

        /* =========================
        TERMS & CONDITIONS
        ========================= */
        $pdf->SetFont('Arial', 'B', 13);
        $pdf->Cell(0, 10, "TERMS & CONDITIONS", 0, 1);
        $pdf->Ln(2);

    $pdf->SetFont('Arial', '', 11);
    foreach ($all_terms as $term) {
        $pdf->MultiCell(0, 8, "- " . $term);
        $pdf->Ln(2);
    }

    $pdf->Ln(10);
    $pdf->SetLineWidth(0.6);
    $pdf->Line(15, $pdf->GetY(), $pdf->GetPageWidth() - 15, $pdf->GetY());

    $pdf->Ln(40);

    /* =========================
       SIGNATURE SECTION
    ========================= */
    $lineWidth = 75;
    $gap = 25;
    $pageWidth = $pdf->GetPageWidth();
    $startX = ($pageWidth - ($lineWidth * 2 + $gap)) / 2;
    $y = $pdf->GetY();

    $pdf->SetLineWidth(0.5);

    // Signature lines
    $pdf->Line($startX, $y, $startX + $lineWidth, $y);
    $pdf->Line($startX + $lineWidth + $gap, $y, $startX + ($lineWidth * 2) + $gap, $y);

    $pdf->Ln(6);

    // Labels
    $pdf->SetFont('Arial', 'B', 12);
    $pdf->SetX($startX);
    $pdf->Cell($lineWidth, 8, "Landlord Signature", 0, 0, 'C');
    $pdf->SetX($startX + $lineWidth + $gap);
    $pdf->Cell($lineWidth, 8, "Tenant Signature", 0, 1, 'C');

    $pdf->Ln(20);

    /* =========================
       SAVE PDF
    ========================= */
    $folder = "../LANDLORD/leases/";
    if (!file_exists($folder)) {
        mkdir($folder, 0777, true);
    }

    $pdf_file = $folder . "lease_{$lease_id}.pdf";
    $pdf->Output('F', $pdf_file);


    /* =========================
       UPDATE PDF PATH IN DATABASE
    ========================= */
    $update = $conn->prepare("
    UPDATE leasetbl
    SET pdf_path = ?
    WHERE ID = ?
");
    $update->bind_param("si", $pdf_file, $lease_id);
    $update->execute();
    $update->close();

    /* =========================
       UPDATE REQUEST STATUS
    ========================= */
    $req = $conn->prepare("
    UPDATE requesttbl SET status = 'approved' WHERE ID = ?
");
    $req->bind_param("i", $request_id);
    $req->execute();
    $req->close();


    echo "<script>
    alert('✅ Lease of Agreement is sent to the tenant!');
          window.location='property-details.php?ID=$listing_id';</script>";
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Create Lease Agreement</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">

    <style>
        body {
            font-family: Arial, sans-serif;
            background: #f4f6f8;
            padding: 40px 0;
        }

        .container {
            width: 720px;
            margin: auto;
            background: #fff;
            padding: 45px 55px 55px;
            border-radius: 18px;
            box-shadow: 0 12px 35px rgba(0,0,0,0.08);
        }

        .lease-title {
            font-size: 34px;
            font-weight: 800;
            color: #8d0b41;
            display: flex;
            align-items: center;
            gap: 12px;
            margin-bottom: 35px;
        }

        .section {
            margin-top: 35px;
        }

        .section-title {
            font-size: 18px;
            font-weight: 700;
            color: #333;
            border-left: 5px solid #8d0b41;
            padding-left: 12px;
            margin-bottom: 18px;
        }

        .info p {
            margin: 6px 0;
            font-size: 15px;
        }

        label {
            font-weight: 600;
            margin-top: 20px;
            display: block;
        }

        input[type="date"],
        input[type="number"],
        input[type="text"] {
            width: 100%;
            padding: 14px 16px;
            margin-top: 8px;
            border-radius: 10px;
            border: 1px solid #ccc;
            font-size: 15px;
            transition: 0.2s;
        }

        input:focus {
            border-color: #8d0b41;
            outline: none;
            box-shadow: 0 0 0 2px rgba(141,11,65,0.15);
        }

        .term-row {
            display: flex;
            align-items: flex-start;
            gap: 14px;
            padding: 12px 14px;
            background: #fafafa;
            border-radius: 10px;
            margin-bottom: 10px;
        }

        .term-row input {
            transform: scale(1.2);
            margin-top: 4px;
        }

        .term-text {
            font-size: 14px;
            font-weight: 600;
            color: #444;
        }

        #customArea input {
            margin-top: 10px;
        }

        .btn-group {
            display: flex;
            justify-content: flex-end;
            gap: 15px;
            margin-top: 45px;
        }

        button {
            border-radius: 10px;
            cursor: pointer;
            font-size: 15px;
            padding: 12px 28px;
            border: none;
            color: #fff;
            font-weight: 600;
            transition: 0.2s;
        }

        .addCustomField {
            background-color: #8d0b41;
            margin-top: 10px;
        }

        .send {
            background-color: #78C841;
        }

        .cancel {
            background-color: #FF3F33;
        }

        button:hover {
            opacity: 0.9;
            transform: translateY(-1px);
        }
    </style>

    <script>
        function addCustomField() {
            const div = document.getElementById("customArea");
            const input = document.createElement("input");
            input.type = "text";
            input.name = "custom_terms[]";
            input.placeholder = "Enter landlord agreement";
            div.appendChild(input);
        }
    </script>
</head>

<body>

<div class="container">

    <h2 class="lease-title">
        <i class="fa-solid fa-file-contract"></i> Lease Agreement
    </h2>

    <!-- PROPERTY INFO -->
    <div class="info section">
        <div class="section-title">Property Information</div>
        <p><b>Property:</b> <?= htmlspecialchars($request['listingName']) ?></p>
        <p><b>Landlord:</b> <?= htmlspecialchars($request['landlord_first'] . ' ' . $request['landlord_last']) ?></p>
        <p><b>Tenant:</b> <?= htmlspecialchars($request['tenant_first'] . ' ' . $request['tenant_last']) ?></p>
    </div>

    <form method="POST">

        <!-- LEASE DETAILS -->
        <div class="section">
            <div class="section-title">Lease Details</div>

            <label>Start Date</label>
            <input type="date" name="start_date" required>

            <label>End of Contract</label>
            <input type="date" name="end_date" required>

            <label>Monthly Rent</label>
            <input type="number" name="rent" value="<?= htmlspecialchars($request['price']) ?>" required>

            <label>Security Deposit</label>
            <input type="number" name="deposit" required>
        </div>

        <!-- TERMS -->
        <div class="section">
            <div class="section-title">Terms & Agreements</div>

            <div class="term-row">
                <input type="checkbox" name="terms[]" value="Tenant pays 1 month advance rent and 1 month security deposit." checked>
                <span class="term-text">Tenant pays 1 month advance rent and 1 month security deposit.</span>
            </div>

            <div class="term-row">
                <input type="checkbox" name="terms[]" value="Security deposit refundable upon move-out minus damages." checked>
                <span class="term-text">Security deposit refundable upon move-out minus damages.</span>
            </div>

            <div class="term-row">
                <input type="checkbox" name="terms[]" value="Rent must be paid on or before the due date." checked>
                <span class="term-text">Rent must be paid on or before the due date.</span>
            </div>

            <div class="term-row">
                <input type="checkbox" name="terms[]" value="No subleasing without landlord approval." checked>
                <span class="term-text">No subleasing without landlord approval.</span>
            </div>

            <div class="term-row">
                <input type="checkbox" name="terms[]" value="No pets allowed." checked>
                <span class="term-text">No pets allowed.</span>
            </div>
        </div>

        <!-- CUSTOM TERMS -->
        <div class="section">
            <div class="section-title">Custom Agreements</div>

            <div id="customArea">
                <input type="text" name="custom_terms[]" placeholder="Enter landlord agreement">
            </div>

            <button type="button" class="addCustomField" onclick="addCustomField()">
                Add Another Agreement
            </button>
        </div>

        <!-- ACTIONS -->
        <div class="btn-group">
            <button type="button" class="cancel" onclick="history.back()">Cancel</button>
            <button type="submit" class="send">Send Lease</button>
        </div>

    </form>

</div>

</body>
</html>
