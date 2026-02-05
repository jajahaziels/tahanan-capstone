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

    // Title
    $pdf->SetFont('Arial', 'B', 16);
    $pdf->Cell(0, 10, 'LEASE AGREEMENT', 0, 1, 'C');
    $pdf->Ln(5);

    // Property, Tenant, Landlord, Rent, Deposit, Contract
    $pdf->SetFont('Arial', 'B', 12);
    $pdf->Cell(35, 8, "Property:", 0, 0);
    $pdf->SetFont('Arial', '', 12);
    $pdf->Cell(0, 8, "{$request['listingName']}", 0, 1);

    $pdf->SetFont('Arial', 'B', 12);
    $pdf->Cell(35, 8, "Tenant:", 0, 0);
    $pdf->SetFont('Arial', '', 12);
    $pdf->Cell(0, 8, "{$request['tenant_first']} {$request['tenant_last']}", 0, 1);

    $pdf->SetFont('Arial', 'B', 12);
    $pdf->Cell(35, 8, "Landlord:", 0, 0);
    $pdf->SetFont('Arial', '', 12);
    $pdf->Cell(0, 8, "{$request['landlord_first']} {$request['landlord_last']}", 0, 1);

    $pdf->SetFont('Arial', 'B', 12);
    $pdf->Cell(35, 8, "Rent:", 0, 0);
    $pdf->SetFont('Arial', '', 12);
    $pdf->Cell(0, 8, "PHP " . number_format($rent, 2), 0, 1);

    $pdf->SetFont('Arial', 'B', 12);
    $pdf->Cell(35, 8, "Deposit:", 0, 0);
    $pdf->SetFont('Arial', '', 12);
    $pdf->Cell(0, 8, "PHP " . number_format($deposit, 2), 0, 1);

    $pdf->SetFont('Arial', 'B', 12);
    $pdf->Cell(35, 8, "Contract:", 0, 0);
    $pdf->SetFont('Arial', '', 12);
    $pdf->Cell(0, 8, "$start_date to $end_date", 0, 1);

    $pdf->Ln(6);

    // Terms & Conditions
    $pdf->SetFont('Arial', 'B', 12);
    $pdf->Cell(0, 8, "TERMS & CONDITIONS", 0, 1);
    $pdf->SetFont('Arial', '', 11);
    foreach ($all_terms as $term) {
        $pdf->MultiCell(0, 7, "- " . $term);
    }

    $pdf->Ln(30);

    // Signatures centered with black line
    $lineWidth = 80; // width of the signature line
    $centerX = ($pdf->GetPageWidth() - ($lineWidth * 2 + 20)) / 2; // spacing between lines

    $pdf->SetFont('Arial', 'B', 12);

    // Draw lines
    $pdf->SetLineWidth(0.5);
    $pdf->Line($centerX, $pdf->GetY(), $centerX + $lineWidth, $pdf->GetY()); // Landlord line
    $pdf->Line($centerX + $lineWidth + 20, $pdf->GetY(), $centerX + $lineWidth * 2 + 20, $pdf->GetY()); // Tenant line

    $pdf->Ln(5); // move below the line

    // Signature labels
    $pdf->SetFont('Arial', 'B', 12);
    $pdf->SetX($centerX);
    $pdf->Cell($lineWidth, 8, "Landlord", 0, 0, 'C');
    $pdf->SetX($centerX + $lineWidth + 20);
    $pdf->Cell($lineWidth, 8, "Tenant", 0, 1, 'C');

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
<html>

<head>
    <title>Create Lease</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <style>
        body {
            font-family: Arial;
            background: #f4f6f8;
        }

        .container {
            width: 650px;
            margin: 30px auto;
            background: white;
            padding: 25px;
            border-radius: 12px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
        }

        label {
            font-weight: bold;
            display: block;
            margin-top: 15px;
        }

        input,
        select {
            width: 100%;
            padding: 10px;
            border-radius: 6px;
            border: 1px solid #ccc;
            margin-top: 5px;
        }

        .term-row {
            display: flex;
            align-items: center;
            margin: 10px 0;
        }

        .term-row input {
            width: auto;
            transform: scale(1.3);
            margin-right: 12px;
        }

        .term-text {
            font-weight: bold;
        }

        button {
            border-radius: .3rem;
            cursor: pointer;
            font-size: 1rem;
            letter-spacing: 3px;
            padding: .4rem 2rem;
            border: 4px solid transparent;
            background-color: pink;
            color: white;
            margin-top: 5px;
            margin-bottom: 5px;
        }

        button:hover {
            transform: scale(0.95);
            border-right: 4px solid #7c7c7c;
            border-bottom: 4px solid #7c7c7c;
        }

        .addCustomField {
            background-color: #8d0b41;
        }

        .send {
            background-color: #78C841;
        }

        .cancel {
            background-color: #FF3F33;
        }

        .lease-title {
            font-size: 36px;
            font-weight: bold;
            display: flex;
            align-items: center;
            gap: 15px;
            color: #8d0b41;
        }

        .lease-title i {
            margin-right: 9px;
            font-size: 30px;
        }
    </style>
    <script>
        function addCustomField() {
            const div = document.getElementById("customArea");
            const input = document.createElement("input");
            input.type = "text";
            input.name = "custom_terms[]";
            input.placeholder = "Enter landlord agreement";
            input.style.marginTop = "10px";
            div.appendChild(input);
        }
    </script>
</head>

<body>
    <div class="container">
        <h2 class="lease-title"><i class="fa-solid fa-file-contract"></i> Lease Agreement</h2>

        <p><b>Property:</b> <?= htmlspecialchars($request['listingName']) ?> </p>
        <p><b>Owner/Landlord:</b> <?= htmlspecialchars($request['landlord_first'] . ' ' . $request['landlord_last']) ?>
        </p>
        <p><b>Tenant:</b> <?= htmlspecialchars($request['tenant_first'] . ' ' . $request['tenant_last']) ?> </p>

        <form method="POST">
            <label>Start Date</label>
            <input type="date" name="start_date" required>

            <label>End of Contract</label>
            <input type="date" name="end_date" required>

            <label>Rent</label>
            <input type="number" name="rent" value="<?= htmlspecialchars($request['price']) ?>" required>

            <label>Deposit</label>
            <input type="number" name="deposit" required>

            <label>Terms & Agreements</label>
            <div class="term-row"><input type="checkbox" name="terms[]"
                    value="Tenant pays 1 month advance rent and 1 month security deposit." checked> <span
                    class="term-text">Tenant pays 1 month advance rent and 1 month security deposit.</span></div>
            <div class="term-row"><input type="checkbox" name="terms[]"
                    value="Security deposit refundable upon move-out minus damages." checked> <span
                    class="term-text">Security deposit refundable upon move-out minus damages.</span></div>
            <div class="term-row"><input type="checkbox" name="terms[]"
                    value="Rent must be paid on or before the due date." checked> <span class="term-text">Rent must be
                    paid on or before the due date.</span></div>
            <div class="term-row"><input type="checkbox" name="terms[]" value="No subleasing without landlord approval."
                    checked> <span class="term-text">No subleasing without landlord approval.</span></div>
            <div class="term-row"><input type="checkbox" name="terms[]" value="No pets allowed." checked> <span
                    class="term-text">No pets allowed.</span></div>

            <hr>
            <label>Landlord Custom Agreements</label>
            <div id="customArea">
                <input type="text" name="custom_terms[]" placeholder="Enter landlord agreement">
            </div>

            <button type="button" onclick="addCustomField()" class="addCustomField">Add Another
                Agreement</button><br><br>
            <button type="submit" class="send">Send</button>
            <button type="button" class="cancel" onclick="history.back()">Cancel</button>
        </form>
    </div>
</body>

</html>