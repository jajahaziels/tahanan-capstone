<?php
require('../fpdf/fpdf.php');

// ---------------------------
// FULL PDF GENERATION
// ---------------------------

// Create PDF
$pdf = new FPDF();
$pdf->AddPage();

// Title (centered)
$pdf->SetFont('Arial', 'B', 16);
$pdf->Cell(0, 12, 'LEASE AGREEMENT', 0, 1, 'C');
$pdf->Ln(5);

// ---------------------------
// Lease Info (LEFT-aligned)
// ---------------------------
$pdf->SetFont('Arial', '', 12);

// Bold specific fields
$fields = [
    'Property' => $request['listingName'],
    'Tenant' => $request['tenant_firstName'] . ' ' . $request['tenant_lastName'],
    'Landlord' => $request['landlord_firstName'] . ' ' . $request['landlord_lastName'],
    'Start Date' => $start_date,
    'End Date' => $end_date,
    'Monthly Rent' => "₱" . number_format($request['price'], 2),
    'Deposit' => $deposit
];

foreach ($fields as $key => $value) {
    if (in_array($key, ['Property', 'Tenant', 'Landlord', 'Monthly Rent'])) {
        $pdf->SetFont('Arial', 'B', 12);
    } else {
        $pdf->SetFont('Arial', '', 12);
    }
    $pdf->Cell(0, 8, "$key: $value", 0, 1, 'L');
}

$pdf->Ln(8);

// ---------------------------
// Terms & Agreements Title (centered)
// ---------------------------
$pdf->SetFont('Arial', 'B', 14);
$pdf->Cell(0, 10, 'TERMS & AGREEMENTS', 0, 1, 'C');
$pdf->Ln(3);

// ---------------------------
// Terms with checkboxes LEFT-aligned
// ---------------------------
$pdf->SetFont('Arial', '', 12);

// Combine posted terms and custom terms
$all_terms = $_POST['terms'] ?? [];
$custom_terms = $_POST['custom_terms'] ?? [];
$termsArray = array_filter(array_merge($all_terms, $custom_terms));

foreach ($termsArray as $term) {
    $term = trim($term);
    if ($term === '')
        continue;

    // ASCII checkbox to avoid UTF-8 issues
    $checkbox = '[x]';
    $pdf->MultiCell(0, 8, $checkbox . " " . $term, 0, 'L');
}

$pdf->Ln(10);

// Agreement line (centered)
$pdf->Cell(0, 8, "Both parties agree to the terms above.", 0, 1, 'C');
$pdf->Ln(15);

// ---------------------------
// Signatures: Landlord LEFT, Tenant RIGHT
// ---------------------------
$pdf->SetFont('Arial', '', 12);
$pdf->SetX(20);
$pdf->Cell(80, 8, "__________________________", 0, 0, 'L');
$pdf->SetX(-110); // move to right side
$pdf->Cell(80, 8, "__________________________", 0, 1, 'R');

$pdf->SetX(20);
$pdf->Cell(80, 8, "Landlord Signature", 0, 0, 'L');
$pdf->SetX(-110);
$pdf->Cell(80, 8, "Tenant Signature", 0, 1, 'R');

// ---------------------------
// Save PDF
// ---------------------------
$pdf_folder = '../LANDLORD/leases/';
if (!file_exists($pdf_folder))
    mkdir($pdf_folder, 0777, true);
$pdf_file = $pdf_folder . "lease_$request_id.pdf";
$pdf->Output('F', $pdf_file);
?>
