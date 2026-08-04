<?php
declare(strict_types=1);
require __DIR__ . '/../../app/bootstrap.php';

require_role(ROLE_WELFARE, ROLE_ADMIN);

$appId = (int)($_GET['application_id'] ?? 0);
$app   = $appId > 0 ? AdoptionModel::find_with_details($appId) : null;

if ($app === null) {
    flash('error', 'Application not found.');
    redirect('/admin/adoption/index.php');
}

if (!in_array($app['status'], ['approved', 'completed'], true)) {
    flash('error', 'PDF agreement can only be generated for approved or completed applications.');
    redirect('/admin/adoption/view.php?id=' . $appId);
}

$existing = AdoptionModel::find_agreement($appId);

if ($existing !== null) {
    $pdfFile = UPLOAD_DIR . '/' . $existing['pdf_path'];
    if (is_file($pdfFile)) {

        header('Content-Type: application/pdf');
        header('Content-Disposition: attachment; filename="' . basename($pdfFile) . '"');
        header('Content-Length: ' . filesize($pdfFile));
        readfile($pdfFile);
        exit;
    }

}

$agreementNumber = 'ADOPT-' . date('Y') . '-' . str_pad((string)$appId, 6, '0', STR_PAD_LEFT);

$ageStr = 'Unknown';
if (!empty($app['pet_approx_age_months'])) {
    $months = (int)$app['pet_approx_age_months'];
    $ageStr = $months < 12
        ? $months . ' month' . ($months !== 1 ? 's' : '')
        : round($months / 12, 1) . ' year' . (round($months / 12, 1) !== 1.0 ? 's' : '');
}

$petTypeLabel = match ($app['pet_animal_type'] ?? 'other') {
    'dog'   => 'Dog',
    'cat'   => 'Cat',
    default => $app['pet_species_other'] ?? 'Other',
};

require_once APP_ROOT . '/lib/fpdf/fpdf.php';

$pdf = new FPDF('P', 'mm', 'A4');
$pdf->SetMargins(20, 20, 20);
$pdf->AddPage();
$pdf->SetAutoPageBreak(true, 20);

$pdf->SetFont('Helvetica', 'B', 22);
$pdf->SetTextColor(80, 40, 10);
$pdf->Cell(0, 10, 'RePawter', 0, 1, 'C');

$pdf->SetFont('Helvetica', '', 11);
$pdf->SetTextColor(100, 100, 100);
$pdf->Cell(0, 6, 'Community Animal Welfare & Adoption Platform', 0, 1, 'C');

$pdf->SetDrawColor(210, 120, 50);
$pdf->SetLineWidth(0.8);
$pdf->Line(20, $pdf->GetY() + 2, 190, $pdf->GetY() + 2);
$pdf->Ln(6);

$pdf->SetFont('Helvetica', 'B', 16);
$pdf->SetTextColor(0, 0, 0);
$pdf->Cell(0, 10, 'ADOPTION AGREEMENT', 0, 1, 'C');
$pdf->Ln(2);

$pdf->SetFont('Helvetica', '', 10);
$pdf->SetTextColor(60, 60, 60);
$pdf->Cell(90, 6, 'Agreement Number: ' . $agreementNumber, 0, 0, 'L');
$pdf->Cell(80, 6, 'Date: ' . date('F d, Y'), 0, 1, 'R');
$pdf->Ln(4);

$sectionHeader = function (string $title) use ($pdf): void {
    $pdf->SetFont('Helvetica', 'B', 11);
    $pdf->SetFillColor(240, 230, 220);
    $pdf->SetTextColor(80, 40, 10);
    $pdf->Cell(0, 8, '  ' . $title, 0, 1, 'L', true);
    $pdf->SetTextColor(0, 0, 0);
    $pdf->Ln(2);
};

$row = function (string $label, string $value) use ($pdf): void {
    $pdf->SetFont('Helvetica', 'B', 10);
    $pdf->Cell(55, 6, $label . ':', 0, 0);
    $pdf->SetFont('Helvetica', '', 10);
    $pdf->MultiCell(0, 6, $value, 0, 'L');
};

$sectionHeader('ADOPTER INFORMATION');
$row('Full Name',       $app['applicant_full_name']);
$row('Email Address',   $app['applicant_email']);
$row('Contact Number',  $app['applicant_contact_number'] ?? 'N/A');
$pdf->Ln(4);

$sectionHeader('PET INFORMATION');
$row('Pet Name',        $app['pet_name']);
$row('Animal Type',     $petTypeLabel);
$row('Sex',             ucfirst($app['pet_sex'] ?? 'unknown'));
$row('Approximate Age', $ageStr);
if (!empty($app['pet_breed'])) {
    $row('Breed', $app['pet_breed']);
}
$pdf->Ln(4);

$sectionHeader('TERMS AND CONDITIONS');

$pdf->SetFont('Helvetica', '', 10);
$pdf->SetTextColor(30, 30, 30);

$terms = [
    '1. Responsible Ownership — The Adopter agrees to provide the adopted animal with adequate food, clean water, shelter, veterinary care, and a safe living environment at all times.',
    '2. Vaccination & Anti-Rabies Compliance — The Adopter shall ensure the animal receives all required vaccinations including the mandatory annual anti-rabies vaccine as prescribed by local and national regulations (RA 9482).',
    '3. No Abandonment — The Adopter shall not abandon, neglect, or subject the animal to cruelty. Abandonment is punishable under the Animal Welfare Act of the Philippines (RA 8485 as amended by RA 10631).',
    '4. No Resale or Transfer — The Adopter shall not sell, trade, or transfer ownership of the animal without prior written consent from the shelter/organization that facilitated the adoption.',
    '5. Return Policy — Should the Adopter be unable to continue caring for the animal, they must contact the shelter or the local barangay welfare officer to arrange a responsible return or rehoming.',
    '6. Right to Follow-up — The shelter/organization reserves the right to conduct a welfare follow-up visit within 60 days of adoption to ensure the animal\'s wellbeing.',
    '7. Legal Compliance — The Adopter agrees to comply with all applicable local ordinances regarding pet ownership, including but not limited to registration, leash laws, and confinement requirements.',
];

foreach ($terms as $term) {
    $pdf->SetFont('Helvetica', '', 9.5);
    $pdf->MultiCell(0, 6, $term, 0, 'L');
    $pdf->Ln(1);
}

$pdf->Ln(6);

$pdf->SetFont('Helvetica', 'B', 10);
$pdf->Cell(80, 6, 'Adopter\'s Signature:', 0, 0);
$pdf->Cell(10, 6, '', 0, 0);
$pdf->Cell(80, 6, 'Authorized Representative:', 0, 1);

$pdf->Ln(10);
$pdf->SetDrawColor(0, 0, 0);
$pdf->SetLineWidth(0.3);
$pdf->Line(20, $pdf->GetY(), 90, $pdf->GetY());
$pdf->Line(110, $pdf->GetY(), 190, $pdf->GetY());
$pdf->Ln(2);

$pdf->SetFont('Helvetica', '', 9);
$pdf->SetTextColor(80, 80, 80);
$pdf->Cell(80, 5, $app['applicant_full_name'], 0, 0, 'C');
$pdf->Cell(10, 5, '', 0, 0);
$pdf->Cell(80, 5, 'RePawter / Shelter Representative', 0, 1, 'C');

$pdf->Ln(2);
$pdf->SetFont('Helvetica', 'I', 8);
$pdf->Cell(80, 5, 'Printed Name & Date', 0, 0, 'C');
$pdf->Cell(10, 5, '', 0, 0);
$pdf->Cell(80, 5, 'Printed Name, Position & Date', 0, 1, 'C');

$pdf->Ln(8);

$pdf->SetFont('Helvetica', 'I', 8);
$pdf->SetTextColor(120, 120, 120);
$pdf->MultiCell(0, 5,
    'This agreement is issued by the RePawter platform on behalf of the participating shelter or organization. ' .
    'For concerns, please contact your local barangay welfare officer or the shelter directly.',
    0, 'C');

$agreementsDir = UPLOAD_DIR . '/agreements';
if (!is_dir($agreementsDir)) {
    mkdir($agreementsDir, 0775, true);
}

$filename    = $agreementNumber . '.pdf';
$savePath    = $agreementsDir . '/' . $filename;
$relativePath = 'agreements/' . $filename;

$pdf->Output('F', $savePath);

if ($existing === null) {
    AdoptionModel::create_agreement($appId, $relativePath, (int)user_id());
}

header('Content-Type: application/pdf');
header('Content-Disposition: attachment; filename="' . $filename . '"');
header('Content-Length: ' . filesize($savePath));
readfile($savePath);
exit;
