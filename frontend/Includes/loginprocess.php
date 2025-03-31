<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}


require_once "verify_otp.php";

$email = $_POST['email'] ?? '';

echo ($email);

?>
