<?php

header("Access-Control-Allow-Origin: *"); // Allow requests from all domains (replace * with specific domains if needed)
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");

// Define the path to the folder where Excel files are stored
$fileDir = './';

// Function to get a list of Excel files in the folder
function getExcelFilesList($dir) {
    $excelFiles = array();

    if (is_dir($dir)) {
        $files = scandir($dir);
        foreach ($files as $file) {
            if (is_file($dir . $file) && pathinfo($file, PATHINFO_EXTENSION) === 'xlsx') {
                $excelFiles[] = $file;
            }
        }
    }

    return $excelFiles;
}
// Check if the filename parameter exists
if (isset($_GET['filename'])) {
    // Get the filename from the URL parameter
    $filename = $_GET['filename'];
    
    // Define the path to the folder where Excel files are stored

    // Check if the file exists in the folder
    if (file_exists($fileDir . $filename)) {
        // Set the appropriate headers for the file download
        header('Content-Type: application/octet-stream');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Content-Length: ' . filesize($fileDir . $filename));

        // Read and output the file to the client
        readfile($fileDir . $filename);
    } else {
        // If the file does not exist, return a 404 error
        http_response_code(404);
        echo 'File not found.';
    }
} else {
    // If the filename parameter is not provided, return a list of Excel files
    $excelFilesList = getExcelFilesList($fileDir);
    echo json_encode($excelFilesList);
}
?>
