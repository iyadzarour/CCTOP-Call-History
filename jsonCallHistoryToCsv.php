<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
set_time_limit(1080000000); // Set the maximum execution time to 3 hours (10800 seconds)
require 'vendor/autoload.php'; // Include the Composer autoloader
ini_set('memory_limit', '102400M');

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

// Define a function to create a database connection
function createDatabaseConnection() {
    $servername = "192.168.61.108";
    $username = "iyad";
    $password = "iyad";
    $dbname = "cctop2";

    $conn = new mysqli($servername, $username, $password, $dbname);
    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
    }
    $conn->set_charset("utf8");
    return $conn;
}

// Define a function to get call category by ID
function getCallKat($id, $conn) {
    $sql = "SELECT kat_name FROM cctop2.cc_kat WHERE kat_id = ? LIMIT 1";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    $stmt->close();
    return $row['kat_name'];
}
// Define a function to get call depot name by ID
function getDepot($depot, $conn) {
    $sql = "SELECT depot_name FROM cctop2.cc_depot WHERE depot_id = ? LIMIT 1";
    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        // Handle the SQL statement preparation error here
        return null;
    }
    $stmt->bind_param("i", $depot);
    if (!$stmt->execute()) {
        // Handle the execution error here
        $stmt->close();
        return null;
    }
    $result = $stmt->get_result();
    if (!$result) {
        // Handle the result retrieval error here
        $stmt->close();
        return null;
    }
    $row = $result->fetch_assoc();
    $stmt->close();
    if (!$row) {
        // Handle the case where no row was found for the given depot ID
        return null;
    }
    return $row['depot_name'];
}

// Define a function to create Excel files
function createExcelFiles($headers, $rowData, $baseFilename) {
    $maxLength = 32767;

    $chunkSize = 100000;
    $numChunks = ceil(count($rowData) / $chunkSize);

    for ($chunkIndex = 0; $chunkIndex < $numChunks; $chunkIndex++) {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        // Write headers to the first row of the sheet
        foreach ($headers as $index => $header) {
            $column = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($index + 1);
            $sheet->setCellValue($column . '1', $header);
        }

        // Write rowData to the sheet
        $startIndex = $chunkIndex * $chunkSize;
        $endIndex = min(($chunkIndex + 1) * $chunkSize, count($rowData));

        $row = 2; // Start from the second row
        for ($i = $startIndex; $i < $endIndex; $i++) {
            $dataRow = $rowData[$i];
            foreach ($dataRow as $key => $value) {
                $headerIndex = array_search($key, $headers);
                if ($headerIndex !== false) {
                    $column = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($headerIndex + 1);
                    try {
                        if ($value === null) {
                            $truncatedString = '0';
                        } else {
                            if (mb_strlen($value, 'UTF-8') > $maxLength) {
                                $truncatedString = mb_substr($value, 0, $maxLength, 'UTF-8');
                            } else {
                                $truncatedString = $value;
                            }
                            // Sanitize the truncatedString to remove unwanted characters
                            $truncatedString = sanitizeString($truncatedString);
                        }
                        $sheet->setCellValue($column . $row, $truncatedString);
                    } catch (\PhpOffice\PhpSpreadsheet\Exception $e) {
                        // Handle the exception by printing the problematic cell content
                        echo "Error in cell ($column$row): " . $e->getMessage() . PHP_EOL;
                    }
                }
            }
            $row++;
        }

        $filename = $baseFilename;

        // Save the spreadsheet to a file
        $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
        try {
            $writer->save($filename);
        } catch (\PhpOffice\PhpSpreadsheet\Writer\Exception $e) {
            // Handle the exception, log or display the error message
            echo 'Error saving Excel file: ' . $e->getMessage() . PHP_EOL;
        }
    }
}

// Define a function to sanitize a string
function sanitizeString($string) {
    // Define a regular expression pattern to match unwanted characters
    $unwantedPattern = '/[,\r\n\t]/';

    // Use preg_replace to replace unwanted characters with a space
    $sanitizedString = preg_replace($unwantedPattern, ' ', $string);

    // Remove German characters
    $germanCharacters = array(
        // Newline (Line Break)
        "\r",   // Carriage Return
        "\t",   // Tab
        ",",    // Comma
        "\"",   // Quotation Marks
        "=",    // Equals Sign
        "+",    // Plus Sign
        "-",    // Minus Sign
        "*",    // Asterisk
        "/",    // Slash
        "\\",   // Backslash
        "?",    // Question Mark
        "[",    // Left Square Bracket
        "]",    // Right Square Bracket
        "{",    // Left Curly Bracket
        "}",    // Right Curly Bracket
        "<",    // Less Than
        ">",    // Greater Than
        "&",    // Ampersand
    );

    $sanitizedString = str_replace($germanCharacters, '', $sanitizedString);

    return $sanitizedString;
}

// Database configuration
$conn = createDatabaseConnection();

header("Access-Control-Allow-Origin: *"); // Allow requests from all domains (replace * with specific domains if needed)
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");

// Get the parameters from the URL
$depot = isset($_GET['depot']) ? $_GET['depot'] : null;
$month = isset($_GET['month']) ? $_GET['month'] : null;
$year = isset($_GET['year']) ? $_GET['year'] : null;
$daysInMonth = cal_days_in_month(CAL_GREGORIAN, $month, $year);

$sql = "SELECT call_id, call_kat, call_maskid, call_user, call_user_del, call_information, call_status,
call_start, call_phone, call_save, call_done, call_depot, call_kunde, call_empf, call_lock, call_tel,
call_partner, call_prio, call_bonitaet, call_dt_depot, call_dt_tour, call_durchwahl, call_kunden_info,
call_abs_empf, call_sendnr, call_to_creator, call_prio_ex
FROM cctop2.cc_call_archiv
WHERE 1=1 ";

// Create an array to store the parameter bindings
$bindings = array();

// Add conditions for the required parameters
if ($depot !== null && !empty($depot)) {
    $sql .= " AND call_depot = ? ";
    $bindings[] = $depot;
}

if (($month !== null) && ($year !== null)) {
    // change the date format to match the database format
// Modify the SQL query based on the number of days in the month
$sql .= " AND call_save BETWEEN ? AND ? ";
$bindings[] = $year . '-' . $month . '-01';
$bindings[] = $year . '-' . $month . '-' . $daysInMonth;
}

$header = [];
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
    $headerToValue['call_kat'] = getCallKat($row['call_kat'], $conn);
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
    FROM cctop2.cc_mask WHERE mask_id = ?";
    $stmt2 = $conn->prepare($sql2);
    $stmt2->bind_param("i", $row['call_maskid']);
    $stmt2->execute();
    $result2 = $stmt2->get_result();
    while ($row2 = $result2->fetch_assoc()) {
        $numbers = extractValues($row2['mask_def']);

        for ($i = 0; $i < count($numbers); $i++) {
            if (!in_array($numbers[$i], $mskIds)) {
                // Add the ID to the array
                array_push($mskIds, $numbers[$i]);
            }
            $sql3 = "SELECT  field_lang FROM cctop2.cc_fields WHERE field_id = ?";
            $stmt3 = $conn->prepare($sql3);
            $stmt3->bind_param("i", $numbers[$i]);
            $stmt3->execute();
            $result3 = $stmt3->get_result();
            $row3 = $result3->fetch_assoc();
            if (!in_array($row3['field_lang'], $header)) {
                $header[] = $row3['field_lang'];
            }
            $headerToValue[$row3['field_lang']] = $values[$i];
        }
    }
    $data[] = $headerToValue;
    $headerToValue = array();
}
$stmt->close();
// Unset variables to release memory
unset($result);
unset($mskIds);
unset($headerToValue);
unset($stmt);
unset($sql);
unset($sql2);
unset($sql3);

// Garbage collection is usually handled by PHP; manual collection may not be necessary.
// gc_collect_cycles();

$filename = "depot_" . getDepot($depot,$conn) . "_Monat_" . $month . "_Jahr_" . $year. '.xlsx' ;
createExcelFiles($header, $data, $filename);
/*
$from = "test@paketomat.at";
$to = "johannes.uzsoki@dpd.at";
$cc = "reinhard.francan@gwp.dpd.at";
$bcc = "";
$replyTo = "";
$subject = "This is a callhistory test mail";
$bodyHtml = "<h1>Test Mail</h1><p>.$filename</p>
<a href='http://192.168.61.32/testApp/callhistory/getfile.php/?file=http://192.168.61.32/testApp/callhistory/".$filename."'>Download Excel file</a>
";
$bodyText = "Test Mail\nThis is a callhistory test mail";

$wsdl = "http://172.21.233.10/WebServicesSOAP/sendMailServer.php?wsdl";
$dataArgs = array($from, $to, $cc, $bcc, $replyTo, $subject, $bodyHtml, $bodyText, null, null);

$soap = new SoapClient($wsdl, ['encoding' => 'UTF-8']);
$ret = $soap->__call('make_mail', $dataArgs);
// Encode the response as JSON
*/
header('Content-Type: application/json');

// Close the prepared statement


// send path to react as json response


$jsonResponse = json_encode($data);
$conn->close();

//echo $jsonResponse;
echo json_encode("http://192.168.61.32/testApp/callhistory/getfile.php/?file=http://192.168.61.32/testApp/callhistory/".$filename);





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
?>
