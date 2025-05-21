<?php
    
    session_start();
    include('../header.php');
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Contact Us</title>
    <link rel="stylesheet" href="../../assets/CSS/contactus.css">
</head>
<body>
<div class="center-container">
    <h1>Contact Us</h1>
    <p>Any questions or remarks? Just write us a message!</p>
</div>
<div class="contact-section">
    <div class="contact-container">
        <!-- Left Side - Contact Information (Green Part) -->
        <div class="contact-information">
            <div class="info-content">
                <h3>Contact Information</h3>
                <p>Say something to start a chat with us!</p>
                <br><br><br><p>📞+977 0987654321</p>
                <p>✉️demo@gmail.com</p>
                <p>📍The British College, Thapathali,<br> &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</nbsp>Kathmandu, Nepal</p><br><br>
                <br> <br><br>
              
            </div>
        </div>
        
        <!-- Right Side - Contact Form (White Box) -->
        <div class="form-container">
            <form id="contactForm" method="POST">
                <div class="form-group">
                    <div class="half-width">
                        <label>First Name</label>
                        <input type="text" class="form-control" name="first_name" required>
                    </div>
                    <div class="half-width">
                        <label>Last Name</label>
                        <input type="text" class="form-control" name="last_name" required>
                    </div>
                </div>
                <div class="form-group">
                    <div class="half-width">
                        <label>Email</label>
                        <input type="email" class="form-control" name="email" required>
                    </div>
                    <div class="half-width">
                        <label>Phone Number</label>
                        <input type="text" class="form-control" name="phone" required>
                    </div>
                </div>
                <label>Select Subject:</label>
                <div class="radio-group">
                    <input type="radio" name="subject" value="General Inquiry" checked> General Inquiry
                    <input type="radio" name="subject" value="Product Return"> Product Return
                    <input type="radio" name="subject" value="Complain"> Complain
                    <input type="radio" name="subject" value="Delivery Mishap"> Delivery Mishap
                </div><br>
                <div class="form-group">
                    <label>Message</label>
                    <textarea class="form-control" name="message" placeholder="Write a message..."></textarea>
                </div>
                <div class="submit-wrapper">
                    <button type="submit" class="btn-submit">Send Message</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script src="../../assets/JS/contactus.js"></script>

<?php
    include('../footer.php');
?>
</body>
</html>