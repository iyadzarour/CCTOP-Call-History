<?php
// Include the PhpSpreadsheet library
header("Access-Control-Allow-Origin: http://localhost:3001");
header("Access-Control-Allow-Methods: POST"); // You can adjust this as needed
header("Access-Control-Allow-Headers: Content-Type"); // You can adjust this as needed

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
set_time_limit(1080000000); // Set the maximum execution time to 3 hours (10800 seconds)
require 'vendor/autoload.php'; // Include the Composer autoloader
ini_set('memory_limit', '102400M');

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

$data = json_decode(file_get_contents("php://input"), true);

$data = isset($data['data']) ? $data['data'] : null;

// Create a new PhpSpreadsheet object
$spreadsheet = new Spreadsheet();
$sheet = $spreadsheet->getActiveSheet();

// Add headers
$headers = array_keys($data[0]); // Remove the unnecessary 'data' key here
$sheet->fromArray([$headers], null, 'A1');

// Chunk size for reading and writing data
$chunkSize = 100000;

// Add data in chunks
for ($start = 0; $start < count($data); $start += $chunkSize) {
    $chunk = array_slice($data, $start, $chunkSize);
    $rowData = [];

    foreach ($chunk as $row) {
        $rowData[] = array_values($row);
    }

    $sheet->fromArray($rowData, null, 'A' . ($start + 2));
}

// Create a new Excel Writer object
$writer = new Xlsx($spreadsheet);

// Save the Excel file to a location on the server
$filename = 'exported_data.xlsx'; // Change this to your desired filename
$writer->save($filename);

// Send the Excel file to the browser for download
header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header('Content-Disposition: attachment;filename="' . $filename . '"');
header('Cache-Control: max-age=0');

$writer->save('php://output');

// Delete the Excel file from the server
unlink($filename);
?>
