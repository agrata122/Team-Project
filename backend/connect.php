<?php
function getDBConnection() {
    $username = "FresGrub"; 
    $password = "agrata@123";  
    $connection_string = "//localhost/xe"; 
    $conn = oci_connect($username, $password, $connection_string);

    if (!$conn) {
        $e = oci_error();
        die ("Connection Error: " . $e['message']);
    }

    return $conn;
}

function create_unique_id() {
    return bin2hex(random_bytes(16));
}
?>
