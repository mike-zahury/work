<?php
function export_to_pdf($result, $employee_name, $customer_name, $month, &$pdf) {
    $pdf->AddPage();
    $pdf->SetFont('dejavusans', '', 10); // Nastavení fontu

    // Hlavička stránky
    $pdf->Cell(0, 10, 'Pracovní záznamy za ' . $month . ' pro ' . htmlspecialchars($employee_name) . ' a ' . htmlspecialchars($customer_name), 0, 1, 'C');

    // Vytvoření HTML pro tabulku
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
                <th class="text-center">Celková částka</th>
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

        // Přidání nové stránky, pokud je potřeba
        if ($pdf->GetY() + 15 > $pdf->getPageHeight() - 15) {
            $pdf->AddPage();
        }

        $html .= '
            <tr class="bg-light">
                <td>' . htmlspecialchars($row['customer_name']) . '</td>
                <td class="text-center">' . htmlspecialchars(date('d.m.Y', strtotime($row['date']))) . '</td>
                <td>' . htmlspecialchars($row['work_description']) . '</td>
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
            <td colspan="9" class="text-right">Celková částka bez DPH</td>
            <td class="text-center">' . htmlspecialchars($total_money_earned) . ' Kč</td>
        </tr>
    </tbody>
    </table>';

    // Vložení HTML do PDF
    $pdf->writeHTML($html, true, false, true, false, '');
}
?>