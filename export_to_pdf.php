<?php
function export_to_pdf($conn, $employee_id, $month) {
    // Fetch employee name from employee_id
    $employee_name_query = $conn->query("SELECT name FROM employees WHERE id = $employee_id");
    $employee_name_row = $employee_name_query->fetch_assoc();
    $employee_name = $employee_name_row['name'];

    $sql = "
        SELECT a.*, c.name AS customer_name, ecr.onsite_rate, ecr.remote_rate, ecr.travel_rate 
        FROM attendance a 
        JOIN customers c ON a.customer_id = c.id 
        LEFT JOIN employee_customer_rate ecr 
        ON a.employee_id = ecr.employee_id AND a.customer_id = ecr.customer_id 
        WHERE a.employee_id = $employee_id AND DATE_FORMAT(a.date, '%Y-%m') = '$month'
        ORDER BY a.date DESC
    ";
    $result = $conn->query($sql);

    if ($result) {
        require_once('tcpdf/tcpdf.php');

        class MYPDF extends TCPDF {
            // Page footer
            public function Footer() {
                $this->SetY(-15);
                $this->SetFont('dejavusans', 'I', 8);

                // First line of footer
                $additional_info = 'work.zahu.cz - docházkový systém';
                $this->Cell(0, 5, $additional_info, 0, 1, 'C'); // The '5' in the third parameter changes the cell height

                // Second line of footer
                $company_info = 'Zahu s.r.o. | Žitavská 56/50, Liberec | tel.: 776 188 697 | mail: servis@zahu.cz | IČ: 19957998 | datová schránka: bwgrwpe';
                $this->Cell(0, 5, $company_info, 0, 0, 'C'); // The '5' in the third parameter changes the cell height
            }
        }

        $pdf = new MYPDF('L', PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);
        $pdf->SetCreator(PDF_CREATOR);
        $pdf->SetAuthor('Employee Dashboard');
        $pdf->SetTitle('Pracovní záznamy za ' . $month . ' pro ' . $employee_name);
        $pdf->SetSubject('Work Entries');
        $pdf->SetKeywords('TCPDF, PDF, work, entries, export');
        $pdf->setPrintHeader(false);
        $pdf->setPrintFooter(true);
        $pdf->SetDefaultMonospacedFont(PDF_FONT_MONOSPACED);
        $pdf->SetMargins(10, 10, 10, true);
        $pdf->SetAutoPageBreak(TRUE, 15); // Enable auto page break with 15 units margin from bottom (slightly increased)
        $pdf->setImageScale(PDF_IMAGE_SCALE_RATIO);
        $pdf->AddPage();
        $pdf->SetFont('dejavusans', '', 10); // Set font size to 10

        // Add company logo in header
        $company_logo = 'logo.png';
        $pdf->Image($company_logo, 10, 10, 30);
        $pdf->SetY(20); // Position the next line just below the image
        $pdf->Cell(0, 10, 'Pracovní záznamy za ' . $month . ' pro ' . $employee_name, 0, 1, 'C');

        $html = '
        <style>
            table {
                border-collapse: collapse;
                width: 100%;
                margin-top: 0; /* Remove margin-top for zero space */
            }
            th, td {
                border: 1px solid #ddd;
                padding: 4px; /* Reduce padding for smaller cells */
                text-align: left;
                word-wrap: break-word;
                font-size: 10px; /* Smaller font size for better readability */
            }
            th {
                background-color: #f2f2f2;
                font-weight: bold;
                text-align: center;
            }
            .text-center {
                text-align: center;
            }
            .bg-light {
                background-color: #f9f9f9;
            }
            .header {
                background-color: #d9edf7;
                font-weight: bold;
                color: #31708f;
                text-align: center;
            }
            .total {
                font-weight: bold;
                background-color: #f2f2f2;
                text-align: right;
            }
            .highlight {
                background-color: #e6f7ff;
            }
        </style>
        <table>
            <thead>
                <tr class="header">
                    <th class="text-center">Zákazník</th>
                    <th class="text-center">Datum</th>
                    <th class="text-center">Popis práce</th>
                    <th class="text-center">Odpracované hodiny</th>
                    <th class="text-center">Typ práce</th>
                    <th class="text-center">Hodinová sazba</th>
                    <th class="text-center">Dopravné</th>
                    <th class="text-center">Materiál</th>
                    <th class="text-center">Cena materiálu</th>
                    <th class="text-center">Vydělané peníze</th>
                </tr>
            </thead>
            <tbody>
        ';

        $total_money_earned = 0;

        while ($row = $result->fetch_assoc()) {
            $hourly_rate = ($row['work_type'] == 'onsite') ? $row['onsite_rate'] : $row['remote_rate'];
            $travel_rate = $row['include_travel'] ? $row['travel_rate'] : 0;
            $money_earned = ($hourly_rate * $row['hours_worked']) + $travel_rate + $row['material_cost'];
            $total_money_earned += $money_earned;

            // Check if a new page is needed before adding the next row
            if ($pdf->GetY() + 15 > $pdf->getPageHeight() - 15) {
                $pdf->AddPage();
                $pdf->SetY(20); // Ensure the new page starts at the correct Y position
            }

            $html .= '
                <tr class="bg-light">
                    <td>' . htmlspecialchars($row['customer_name']) . '</td>
                    <td class="text-center">' . htmlspecialchars(date('d.m.Y', strtotime($row['date']))) . '</td>
                    <td>' . $row['work_description'] . '</td>
                    <td class="text-center">' . htmlspecialchars($row['hours_worked']) . '</td>
                    <td class="text-center">' . htmlspecialchars(ucfirst($row['work_type'])) . '</td>
                    <td class="text-center">' . htmlspecialchars($hourly_rate) . ' Kč</td>
                    <td class="text-center">' . htmlspecialchars($travel_rate) . ' Kč</td>
                    <td class="text-center">' . htmlspecialchars($row['material']) . '</td>
                    <td class="text-center">' . htmlspecialchars($row['material_cost']) . ' Kč</td>
                    <td class="text-center highlight">' . htmlspecialchars($money_earned) . ' Kč</td>
                </tr>
            ';
        }

        $html .= '
            <tr class="total">
                <td colspan="9" class="text-right">Celková částka</td>
                <td class="text-center">' . htmlspecialchars($total_money_earned) . ' Kč</td>
            </tr>
        ';

        $html .= '</tbody></table>';
        $pdf->writeHTML($html, true, false, true, false, '');
        $pdf->Output('work_entries_' . $month . '.pdf', 'D');
        exit();
    } else {
        $_SESSION['error'] = "Chyba při exportu dat.";
    }
}
?>