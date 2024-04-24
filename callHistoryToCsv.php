<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
set_time_limit(10800); // Set the maximum execution time to 3 stunden (10800 seconds)
require 'vendor/autoload.php'; // Include the Composer autoloader
ini_set('memory_limit', '256M');

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

$spreadsheet = new Spreadsheet();
$sheet = $spreadsheet->getActiveSheet();

error_reporting(E_ALL);

// Database configuration
$servername = "192.168.61.108";
$username = "iyad";
$password = "iyad";
$dbname = "cctop2";

// Create a connection
$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
$conn->set_charset("utf8");


header("Access-Control-Allow-Origin: *"); // Allow requests from all domains (replace * with specific domains if needed)
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");

// Get the parameters from the URL
$depot = isset($_GET['depot']) ? $_GET['depot'] : null;
$month = isset($_GET['month']) ? $_GET['month'] : null;
$year = isset($_GET['year']) ? $_GET['year'] : null;

$sql = "SELECT call_id, call_kat, call_maskid, call_user, call_user_del, call_information, call_status,
call_start, call_phone, call_save, call_done, call_depot, call_kunde, call_empf, call_lock, call_tel,
call_partner, call_prio, call_bonitaet, call_dt_depot, call_dt_tour, call_durchwahl, call_kunden_info,
call_abs_empf, call_sendnr, call_to_creator, call_prio_ex
FROM cctop2.cc_call_archiv WHERE 1=1";

// Create an array to store the parameter bindings
$bindings = array();

// Add conditions for the required parameters
if ($depot !== null && !empty($depot)) {
    $sql .= " AND call_depot = ? ";
    $bindings[] = $depot;
}

if (($month !== null) && ($year !== null)) {
    // change the date format to match the database format
    // for test get data for this month
    $sql .= " AND call_save BETWEEN ? AND ? ";
    $bindings[] =   $year . '-' . $month . '-01';
    $bindings[] =  $year . '-' . $month . '-31';



}

$header =[];
$header[] = "call_id";
$header[] = "call_kat";
$header[] = "call_maskid";
$header[] = "call_user";
$header[] = "call_user_del";
$header[] = "call_information";
$header[] = "call_status";
$header[] = "call_start";
$header[] = "call_phone";
$header[] = "call_save";
$header[] = "call_done";
$header[] = "call_depot";
$header[] = "call_kunde";
$header[] = "call_empf";
$header[] = "call_lock";
$header[] = "call_tel";
$header[] = "call_partner";
$header[] = "call_prio";
$header[] = "call_bonitaet";
$header[] = "call_dt_depot";
$header[] = "call_dt_tour";
$header[] = "call_durchwahl";
$header[] = "call_kunden_info";
$header[] = "call_abs_empf";
$header[] = "call_sendnr";
$header[] = "call_to_creator";
$header[] = "call_prio_ex";

// Execute the prepared statement
$stmt = $conn->prepare($sql);
$stmt->bind_param(str_repeat('s', count($bindings)), ...$bindings);
$stmt->execute();

// Get the result
$result = $stmt->get_result();
$mskIds = array(); // Initialize the array to store unique IDs

$data = array();
$headerToValue = array();
while ($row = $result->fetch_assoc()) {
    $headerToValue['call_id'] = $row['call_id'];
    $headerToValue['call_kat'] = $row['call_kat'];
    $headerToValue['call_maskid'] = $row['call_maskid'];
    $headerToValue['call_user'] = $row['call_user'];
    $headerToValue['call_user_del'] = $row['call_user_del'];
    $headerToValue['call_information'] = $row['call_information'];
    $headerToValue['call_status'] = $row['call_status'];
    $headerToValue['call_start'] = $row['call_start'];
    $headerToValue['call_phone'] = $row['call_phone'];
    $headerToValue['call_save'] = $row['call_save'];
    $headerToValue['call_done'] = $row['call_done'];
    $headerToValue['call_depot'] = $row['call_depot'];
    $headerToValue['call_kunde'] = $row['call_kunde'];
    $headerToValue['call_empf'] = $row['call_empf'];
    $headerToValue['call_lock'] = $row['call_lock'];
    $headerToValue['call_tel'] = $row['call_tel'];
    $headerToValue['call_partner'] = $row['call_partner'];
    $headerToValue['call_prio'] = $row['call_prio'];
    $headerToValue['call_bonitaet'] = $row['call_bonitaet'];
    $headerToValue['call_dt_depot'] = $row['call_dt_depot'];
    $headerToValue['call_dt_tour'] = $row['call_dt_tour'];
    $headerToValue['call_durchwahl'] = $row['call_durchwahl'];
    $headerToValue['call_kunden_info'] = $row['call_kunden_info'];
    $headerToValue['call_abs_empf'] = $row['call_abs_empf'];
    $headerToValue['call_sendnr'] = $row['call_sendnr'];
    $headerToValue['call_to_creator'] = $row['call_to_creator'];
    $headerToValue['call_prio_ex'] = $row['call_prio_ex'];
    $values = separateElements($row['call_information']);

    $sql2 = "SELECT mask_def
    FROM cctop2.cc_mask WHERE mask_id = ".$row['call_maskid'];
    $result2 = $conn->query($sql2);
    while ($row2 = $result2->fetch_assoc()) {
        $numbers = extractValues($row2['mask_def']);

        for ($i = 0; $i < count($numbers); $i++) {
            if (!in_array($numbers[$i], $mskIds)) {
                // Add the ID to the array
                array_push($mskIds, $numbers[$i]);
            }
        $sql3 = "SELECT  field_lang FROM cctop2.cc_fields WHERE field_id = ".$numbers[$i];
        $result3 = $conn->query($sql3);
        $row3 = $result3->fetch_assoc();
        if (!in_array($row3['field_lang'], $header)) {
        $header[]=$row3['field_lang'];
        }
        $headerToValue[$row3['field_lang']] = $values[$i];
        }
    }
    $data[] = $headerToValue;
    $headerToValue = array();
}

//createExcelFile($header, $data , 'test.xlsx');
// return $data as JSON to React app

/*/
$response = array(
    'headers' => $header,
    'data' => $data
);
    
header('Content-Type: application/json');

// Encode the response as JSON
$jsonResponse = json_encode($response);

echo $jsonResponse;

echo"<pre>";
print_r($data);
echo"</pre>";
**/
$filename="depot_".$depot."_Month_".$month."_Year_".$year;
createExcelFile($header, $data , $filename.'.xlsx');

// return excel file path
header('Content-Type: application/json');

// Encode the response as JSON
// i want to send the file by email
$from = "test@paketomat.at";
$to = "iead.zaruor@gmail.com";
$cc = "";
$bcc = "";
$replyTo = "";
$subject = "Test Mail";
$bodyHtml = "<h1>Test Mail</h1><p>This is a test mail</p>";
$bodyText = "Test Mail\nThis is a test mail";
$filename = "depot_".$depot."_Month_".$month."_Year_".$year.".xlsx";
$attachment = chunk_split(base64_encode(file_get_contents($filename)));


$wsdl = "http://172.21.233.10/WebServicesSOAP/sendMailServer.php?wsdl";
$dataArgs = array($from, $to, $cc, $bcc, $replyTo, $subject, $bodyHtml, $bodyText, null, $attachment);

$soap = new SoapClient($wsdl, ['encoding' => 'UTF-8']);
$ret = $soap->__call('make_mail', $dataArgs);


echo 'done';




$stmt->close();
$conn->close();

function extractValues($text) {
    $result = [];
    $elements = explode('|', $text);

    foreach ($elements as $element) {
        $parts = explode(';', $element);
        if (count($parts) >= 1) {
            $value = $parts[0];
            $result[] = $value;
        }
    }

    // Remove the first element from the array
    if (count($result) > 0) {
        array_shift($result);
    }

    return $result;
}

function separateElements($text, $separator = '|', $default = '0', $trimWhitespace = true) {
    $elements = explode($separator, $text);
    $result = [];
    
    foreach ($elements as $element) {
        $element = ($trimWhitespace) ? trim($element) : $element;
        $result[] = ($element !== '') ? $element : $default;
    }
    
    return $result;
}
function createExcelFile($headers, $rowData, $filename) {
    $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
    $sheet = $spreadsheet->getActiveSheet();

    // Write headers to the first row of the sheet
    foreach ($headers as $index => $header) {
        $column = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($index + 1);
        $sheet->setCellValue($column . '1', $header);
    }

    // Write rowData to the sheet
    $row = 2; // Start from the second row
    foreach ($rowData as $dataRow) {
        foreach ($dataRow as $key => $value) {
            $headerIndex = array_search($key, $headers);
            if ($headerIndex !== false) {
                $column = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($headerIndex + 1);
                try {
                    $sheet->setCellValue($column . $row, $value);
                } catch (\PhpOffice\PhpSpreadsheet\Exception $e) {
                    // Handle the exception, log or display the error message
                    echo 'Error writing cell ' . $column . $row . ': ' . $e->getMessage();
                }
            }
        }
        $row++;
    }

    // Save the spreadsheet to a file
    $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
    $writer->save($filename);
}




?>
