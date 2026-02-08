<?php
require('../fpdf/fpdf.php');

// ---------------------------
// FULL PDF GENERATION
// ---------------------------

// Create PDF
$pdf = new FPDF();
$pdf->AddPage();
$pdf->SetAutoPageBreak(true, 25); // bottom margin

// ---------------------------
// Title (centered)
// ---------------------------
$pdf->SetFont('Arial', 'B', 20); // bigger font
$pdf->Cell(0, 12, 'LEASE AGREEMENT', 0, 1, 'C');
$pdf->Ln(8);

// ---------------------------
// Lease Info (LEFT-aligned)
// ---------------------------
$fields = [
    'Property' => $request['listingName'],
    'Tenant' => $request['tenant_firstName'] . ' ' . $request['tenant_lastName'],
    'Landlord' => $request['landlord_firstName'] . ' ' . $request['landlord_lastName'],
    'Start Date' => $start_date,
    'End Date' => $end_date,
    'Monthly Rent' => "₱" . number_format($request['price'], 2),
    'Deposit' => "₱" . number_format($deposit, 2)
];

foreach ($fields as $key => $value) {
    $pdf->SetFont('Arial', 'B', 13);
    $pdf->Cell(45, 8, "$key:", 0, 0, 'L');
    $pdf->SetFont('Arial', '', 13);
    $pdf->MultiCell(0, 8, $value);
}
$pdf->Ln(10);

// ---------------------------
// Terms & Agreements Title (centered)
// ---------------------------
$pdf->SetFont('Arial', 'B', 14);
$pdf->Cell(0, 10, 'TERMS & AGREEMENTS', 0, 1, 'C');
$pdf->Ln(3);

// ---------------------------
// Terms with empty checkboxes LEFT-aligned
// ---------------------------
$pdf->SetFont('Arial', '', 13);
$all_terms = $_POST['terms'] ?? [];
$custom_terms = $_POST['custom_terms'] ?? [];
$termsArray = array_filter(array_merge($all_terms, $custom_terms));

foreach ($termsArray as $term) {
    $term = trim($term);
    if ($term === '')
        continue;

    // Empty box for manual landlord check
    $checkbox = "☐";
    $pdf->MultiCell(0, 9, $checkbox . " " . $term, 0, 'L');
    $pdf->Ln(1);
}

$pdf->Ln(15); // extra spacing before agreement line

// ---------------------------
// Agreement Statement (centered)
// ---------------------------
$pdf->SetFont('Arial', 'B', 13);
$pdf->Cell(0, 8, "Both parties agree to the terms above.", 0, 1, 'C');
$pdf->Ln(25); // extra space before signatures

// ---------------------------
// Signatures: Landlord LEFT, Tenant RIGHT
// ---------------------------
$pdf->SetFont('Arial', '', 13);
$lineWidth = 80;
$gap = 20;

$centerX = ($pdf->GetPageWidth() - ($lineWidth * 2 + $gap)) / 2;

// Draw signature lines
$pdf->SetX($centerX);
$pdf->Cell($lineWidth, 8, "__________________________", 0, 0, 'C');
$pdf->SetX($centerX + $lineWidth + $gap);
$pdf->Cell($lineWidth, 8, "__________________________", 0, 1, 'C');

// Signature labels
$pdf->SetX($centerX);
$pdf->Cell($lineWidth, 8, "Landlord Signature", 0, 0, 'C');
$pdf->SetX($centerX + $lineWidth + $gap);
$pdf->Cell($lineWidth, 8, "Tenant Signature", 0, 1, 'C');

// ---------------------------
// Save PDF
// ---------------------------
$pdf_folder = '../LANDLORD/leases/';
if (!file_exists($pdf_folder))
    mkdir($pdf_folder, 0777, true);

$pdf_file = $pdf_folder . "lease_$request_id.pdf";
$pdf->Output('F', $pdf_file);

$file = $_GET['file'];
$path = __DIR__ . '/' . $file;

if (file_exists($path)) {
    header('Content-Type: application/pdf');
    header('Content-Disposition: inline; filename="'.basename($path).'"');
    readfile($path);
    exit;
} else {
    echo "File not found.";
}
?>

