<?php
header("Access-Control-Allow-Origin: *"); // Allow requests from all domains (replace * with specific domains if needed)
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");
// Check if the 'file' query parameter is provided in the URL
if (isset($_GET['file'])) {
    $file_path = $_GET['file']; // Get the file path from the URL
    $file_name = basename($file_path); // Extract the file name from the path


?>
    <html>

    <head>
        <title>Download file</title>
    </head>

    <body>
<!-- add back button -->
        <button
            style="background-color: #4CAF50; /* Green */
            border: none;
            color: white;
            padding: 15px 32px;
            text-align: center;
            text-decoration: none;
            display: block;
            font-size: 16px;
            margin: auto;
            cursor: pointer;"
         onclick="goBack()">Go Back</button>
        <script>
            function goBack() {
                window.history.back();
            }
        </script>
    </body>

    </html>
    <script>
        // Redirect to the file download URL in new tab
        
        window.location.href = '<?php echo $file_path; ?>';
    

                


    </script>

<?php
} else {
    // If the 'file' query parameter is not provided, return a 404 error
    http_response_code(404);
    echo 'File not found.';


}
?>