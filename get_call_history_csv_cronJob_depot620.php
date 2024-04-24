<?php

// Set the timezone to match your application
date_default_timezone_set('Europe/Vienna'); // Replace 'Europe/Vienna' with your timezone

// Get the current date
$currentDate = new DateTime();

// Modify the date to get the previous month
$previousMonth = $currentDate->modify('-1 month')->format('m');
// If the previous month is December, decrement the current year
if ($previousMonth == '12') {
    $currentYear = $currentDate->modify('-1 year')->format('Y');
} else {
    $currentYear = $currentDate->format('Y');
}
// Get the current year
$currentYear = $currentDate->format('Y');

// API endpoint with dynamic month and year
$apiUrl = "http://192.168.61.32/testApp/callhistory/jsonCallHistoryToCsv.php/?depot=22&month={$previousMonth}&year={$currentYear}";

// Make the API request
$response = file_get_contents($apiUrl);

// Check if the request was successful
if ($response === false) {
    // Handle error
    echo 'Error accessing the API.';
} else {
    // Process the API response (you can echo it or save it to a file, etc.)
    echo $response;
}

?>
