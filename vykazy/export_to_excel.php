<?php
require '../lib/xlsxwriter.class.php'; // Include the XLSXWriter library

function export_to_excel($conn, $employee_name, $customer_name, $month) {
    $sql = "
        SELECT a.*, c.name AS customer_name, ecr.onsite_rate, ecr.remote_rate, ecr.travel_rate 
        FROM attendance a 
        JOIN customers c ON a.customer_id = c.id 
        LEFT JOIN employee_customer_rate ecr 
        ON a.employee_id = ecr.employee_id AND a.customer_id = ecr.customer_id 
        WHERE a.employee_id = (SELECT id FROM employees WHERE name = '$employee_name') 
        AND a.customer_id = (SELECT id FROM customers WHERE name = '$customer_name') 
        AND DATE_FORMAT(a.date, '%Y-%m') = '$month'
        ORDER BY a.date DESC
    ";
    $result = $conn->query($sql);

    if ($result && $result->num_rows > 0) {
        $headers = [
            'Zákazník', 'Datum', 'Popis práce', 'Odpracované hodiny', 
            'Typ práce', 'Hodinová sazba', 'Dopravné', 
            'Materiál', 'Cena materiálu', 'Vydělané peníze'
        ];
        $headerStyle = ['font'=>'Arial', 'font-size'=>10, 'font-style'=>'bold', 'fill'=>'#d9edf7', 'halign'=>'center', 'border'=>'left,right,top,bottom'];
        $rowStyle = ['font'=>'Arial', 'font-size'=>10, 'fill'=>'#f9f9f9', 'halign'=>'center', 'border'=>'left,right,top,bottom'];
        $alternateRowStyle = ['font'=>'Arial', 'font-size'=>10, 'fill'=>'#ffffff', 'halign'=>'center', 'border'=>'left,right,top,bottom'];
        $footerStyle = ['font'=>'Arial', 'font-size'=>12, 'font-style'=>'bold', 'halign'=>'right', 'border'=>'left,right,top,bottom'];

        $writer = new XLSXWriter();
        
        // Add header
        $writer->writeSheetRow('Sheet1', $headers, $headerStyle);
        
        $total_money_earned = 0;
        foreach ($result as $index => $row) {
            $hourly_rate = ($row['work_type'] == 'onsite') ? $row['onsite_rate'] : $row['remote_rate'];
            $travel_rate = $row['include_travel'] ? $row['travel_rate'] : 0;
            $money_earned = ($hourly_rate * $row['hours_worked']) + $travel_rate + $row['material_cost'];
            $total_money_earned += $money_earned;

            $data = [
                $row['customer_name'],
                date('d.m.Y', strtotime($row['date'])),
                $row['work_description'],
                $row['hours_worked'],
                ucfirst($row['work_type']),
                $hourly_rate . ' Kč',
                $travel_rate . ' Kč',
                $row['material'],
                $row['material_cost'] . ' Kč',
                $money_earned . ' Kč'
            ];

            $style = ($index % 2 == 0) ? $rowStyle : $alternateRowStyle;
            $writer->writeSheetRow('Sheet1', $data, $style);
        }

        // Add footer
        $footer = ['Celková částka bez DPH', '', '', '', '', '', '', '', '', $total_money_earned . ' Kč'];
        $writer->writeSheetRow('Sheet1', $footer, $footerStyle);

        // Save the file
        $fileName = "vykazy_{$month}.xlsx";
        $writer->writeToFile($fileName);

        // Output the file for download
        header('Content-Description: File Transfer');
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment; filename="' . basename($fileName) . '"');
        header('Content-Transfer-Encoding: binary');
        header('Expires: 0');
        header('Cache-Control: must-revalidate');
        header('Pragma: public');
        header('Content-Length: ' . filesize($fileName));
        readfile($fileName);

        // Delete the file after download
        unlink($fileName);
    }
}
?>