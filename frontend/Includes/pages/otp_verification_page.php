<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
} 

$error = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (!isset($_POST['otp']) || !is_array($_POST['otp'])) {
        $error = "Invalid OTP format.";
    } else {
        $entered_otp = implode("", $_POST['otp']);

        if (isset($_SESSION['otp']) && isset($_SESSION['otp_expiry'])) {
            if (time() > $_SESSION['otp_expiry']) {
                $error = "OTP has expired. Please request a new one.";
                unset($_SESSION['otp']);
                unset($_SESSION['otp_expiry']);
            } elseif ($entered_otp === $_SESSION['otp']) {
                $_SESSION['otp_verified'] = true;

                // Redirecting to `registerprocess.php` with verification flag
                echo "<form id='redirect-form' action='../registerprocess.php' method='post'>";
                echo "<input type='hidden' name='otp_verified' value='true'>";
                echo "</form>";
                echo "<script>document.getElementById('redirect-form').submit();</script>";
                exit();
            } else {
                $error = "Invalid OTP. Please try again.";
            }
        } else {
            $error = "No OTP found. Please request a new one.";
        }
    }
}
?>


<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>OTP Verification</title>
    <link rel="stylesheet" href="../../assets/CSS/otp_verification.css">
</head>
<body>
    <div class="otp-container">
        <img src="../../assets/Images/otp.png" alt="OTP Image" class="otp-image">
        <h2>Verify Your Account</h2>
        <p>Enter the OTP sent to your email.</p>

        <?php if ($error): ?>
            <p class="error"><?php echo $error; ?></p>
        <?php endif; ?>

        <form method="POST" action="otp_verification_page.php">
            <div class="otp-inputs">
                <input type="text" name="otp[]" maxlength="1" required>
                <input type="text" name="otp[]" maxlength="1" required>
                <input type="text" name="otp[]" maxlength="1" required>
                <input type="text" name="otp[]" maxlength="1" required>
                <input type="text" name="otp[]" maxlength="1" required>
                <input type="text" name="otp[]" maxlength="1" required>
            </div>
            <button type="submit">Verify</button>
        </form>

        <p class="resend">Didn't receive OTP? <a href="resend_otp.php">Resend OTP</a></p>
    </div>

    <script>
        const inputs = document.querySelectorAll(".otp-inputs input");

        inputs.forEach((input, index) => {
            input.addEventListener("input", (e) => {
                if (e.target.value.length === 1 && index < inputs.length - 1) {
                    inputs[index + 1].focus();
                }
            });

            input.addEventListener("keydown", (e) => {
                if (e.key === "Backspace" && index > 0 && !e.target.value) {
                    inputs[index - 1].focus();
                }
            });
        });
    </script>
</body>
</html>
