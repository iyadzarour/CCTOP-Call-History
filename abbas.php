<?php

$data = [
    1 => [
        'NP' => 'Normalpaket',
        'KP' => 'Kleinpaket',
        'RETURN' => 'DPD Return',
        'PARCELLetter' => 'PARCELLetter',
        'POST' => 'Privatpaket',
        'Express' => 'Express',
 
    ],
    2=>[
        'POST' => 'Privatpaket',
        'Express' => 'Express',
        'AM1' => 'Werktag 10:00',
        'AM2' => 'Werktag 12:00',
        'PM2' => 'Werktag 17:00', 
    ],
    3=>[
        'Express' => 'Express',
        'AM1' => 'Werktag 10:00',
    ],
    4=>[
        'Express' => 'Express',
        'AM1' => 'Werktag 10:00',
    ]
];

// Get the first (and only) element of the array
$firstElement = reset($data);
// get theard element
$theardElement = $data[3];



// Extract and print the keys
$keys = array_keys($theardElement);
$keysAsString = implode('<br/>', $keys);

print_r($keysAsString);

?>