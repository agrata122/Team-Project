<?php
// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once '../includes/connect.php';

$conn = getDBConnection();
if (!$conn) {
    die("Database connection failed");
}

// Function to check slot availability
function checkSlotAvailable($conn, $slotDate, $slotTime) {
    $query = "SELECT * FROM collection_slot 
              WHERE slot_date = TO_DATE(:slot_date, 'YYYY-MM-DD') 
              AND TO_CHAR(slot_time, 'HH24:MI') = TO_CHAR(TO_TIMESTAMP(:slot_time, 'HH24:MI'), 'HH24:MI')";
    
    $stmt = oci_parse($conn, $query);
    oci_bind_by_name($stmt, ":slot_date", $slotDate);
    oci_bind_by_name($stmt, ":slot_time", $slotTime);
    oci_execute($stmt);
    
    if ($row = oci_fetch_assoc($stmt)) {
        // Slot exists, check if it's full
        if ($row['TOTAL_ORDER'] >= 20) {
            return false; // Slot is full
        } else {
            return true; // Slot is available
        }
    } else {
        // Slot doesn't exist yet, create it
        $insertQuery = "INSERT INTO collection_slot (slot_date, slot_day, slot_time, total_order)
                        VALUES (TO_DATE(:slot_date, 'YYYY-MM-DD'), 
                                TO_CHAR(TO_DATE(:slot_date, 'YYYY-MM-DD'), 'DAY'),
                                TO_TIMESTAMP(:slot_time, 'HH24:MI'),
                                0)";
        
        $insertStmt = oci_parse($conn, $insertQuery);
        oci_bind_by_name($insertStmt, ":slot_date", $slotDate);
        oci_bind_by_name($insertStmt, ":slot_time", $slotTime);
        
        if (oci_execute($insertStmt)) {
            return true; // Created and available
        } else {
            return false; // Failed to create
        }
    }
}

// Handle AJAX requests for slot availability
if (isset($_POST['action']) && $_POST['action'] == 'check_slot') {
    $selectedDate = $_POST['date'];
    $selectedTime = $_POST['time'];
    
    // Calculate if the selected date is at least 24 hours ahead
    $currentDate = new DateTime();
    $slotDate = new DateTime($selectedDate . ' ' . $selectedTime);
    $interval = $currentDate->diff($slotDate);
    $hoursDiff = $interval->days * 24 + $interval->h;
    
    if ($hoursDiff < 24) {
        echo json_encode(['status' => 'error', 'message' => 'Collection slot must be at least 24 hours after placing the order.']);
        exit;
    }
    
    // Check if slot is available (less than 20 orders)
    $isAvailable = checkSlotAvailable($conn, $selectedDate, $selectedTime);
    
    if ($isAvailable) {
        echo json_encode(['status' => 'success', 'message' => 'Slot is available']);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'That slot is full!']);
    }
    exit;
}

// Get available dates (Wed, Thu, Fri for the next 2 weeks)
$availableDates = [];
$today = new DateTime();
$endDate = clone $today;
$endDate->modify('+14 days'); // 2 weeks ahead

while ($today <= $endDate) {
    $dayOfWeek = $today->format('D');
    if (in_array($dayOfWeek, ['Wed', 'Thu', 'Fri'])) {
        $availableDates[] = $today->format('Y-m-d');
    }
    $today->modify('+1 day');
}

// Available time slots
$timeSlots = [
    '10:00' => '10:00 - 13:00',
    '13:00' => '13:00 - 16:00',
    '16:00' => '16:00 - 19:00'
];

// Save collection slot to session when submitted
if (isset($_POST['action']) && $_POST['action'] == 'save_slot') {
    $selectedDate = $_POST['selected_date'];
    $selectedTime = $_POST['selected_time'];
    
    // Save to session
    $_SESSION['collection_slot'] = [
        'date' => $selectedDate,
        'time' => $selectedTime,
        'formatted_time' => $timeSlots[$selectedTime]
    ];
    
    // Redirect back to cart
    header("Location: shopping_cart.php");
    exit;
}

// Get slot ID for checkout
function getSlotId($conn, $date, $time) {
    $query = "SELECT collection_slot_id FROM collection_slot 
              WHERE slot_date = TO_DATE(:slot_date, 'YYYY-MM-DD') 
              AND TO_CHAR(slot_time, 'HH24:MI') = TO_CHAR(TO_TIMESTAMP(:slot_time, 'HH24:MI'), 'HH24:MI')";
    
    $stmt = oci_parse($conn, $query);
    oci_bind_by_name($stmt, ":slot_date", $date);
    oci_bind_by_name($stmt, ":slot_time", $time);
    oci_execute($stmt);
    
    if ($row = oci_fetch_assoc($stmt)) {
        return $row['COLLECTION_SLOT_ID'];
    }
    
    return null;
}

// Update slot order count
function incrementSlotOrderCount($conn, $slotId) {
    $query = "UPDATE collection_slot 
              SET total_order = total_order + 1 
              WHERE collection_slot_id = :slot_id";
    
    $stmt = oci_parse($conn, $query);
    oci_bind_by_name($stmt, ":slot_id", $slotId);
    return oci_execute($stmt);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Select Collection Slot</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 20px;
            background-color: #f5f5f5;
        }
       
        .container {
            max-width: 600px;
            margin: 80px auto 0 auto; 
            background-color: white;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            padding: 20px;
        }
        
        h1 {
            text-align: center;
            margin-bottom: 20px;
        }
        
        .form-group {
            margin-bottom: 15px;
        }
        
        label {
            display: block;
            margin-bottom: 5px;
            font-weight: bold;
        }
        
        select {
            width: 100%;
            padding: 8px;
            border: 1px solid #ddd;
            border-radius: 4px;
        }
        
        .btn {
            background-color: #d4cfc9;
            color: white;
            padding: 10px 20px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            width: 100%;
            font-size: 16px;
            margin-top: 15px;
        }
        
        .btn:hover {
            background-color: #8dc667;
        }
        
        .slot-message {
            margin-top: 10px;
            padding: 8px;
            border-radius: 4px;
        }
        
        .slot-available {
            background-color: #d4edda;
            color: #155724;
        }
        
        .slot-unavailable {
            background-color: #f8d7da;
            color: #721c24;
        }
    </style>
</head>
<body>
    <?php include 'header.php'; ?>

    <div class="container">
        <h1>Select Collection Slot</h1>
        
        <form id="slot-form" method="post" action="collection_slot.php">
            <input type="hidden" name="action" value="save_slot">
            
            <div class="form-group">
                <label for="date">Collection Day:</label>
                <select id="date" name="selected_date" required>
                    <option value="">-- Select Day --</option>
                    <?php foreach ($availableDates as $date): ?>
                        <?php 
                        $dateObj = new DateTime($date);
                        $dayName = $dateObj->format('l');
                        $formattedDate = $dateObj->format('d M Y');
                        ?>
                        <option value="<?= $date ?>"><?= $dayName ?>, <?= $formattedDate ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div class="form-group">
                <label for="time">Collection Time:</label>
                <select id="time" name="selected_time" required>
                    <option value="">-- Select Time --</option>
                    <?php foreach ($timeSlots as $value => $display): ?>
                        <option value="<?= $value ?>"><?= $display ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div id="slot-message" class="slot-message" style="display: none;"></div>
            
            <button type="submit" class="btn" id="confirm-btn" disabled>Confirm Slot</button>
        </form>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const dateSelect = document.getElementById('date');
            const timeSelect = document.getElementById('time');
            const slotMessage = document.getElementById('slot-message');
            const confirmBtn = document.getElementById('confirm-btn');
            
            function checkSlotAvailability() {
                const selectedDate = dateSelect.value;
                const selectedTime = timeSelect.value;
                
                if (!selectedDate || !selectedTime) return;
                
                // Show loading message
                slotMessage.textContent = 'Checking slot availability...';
                slotMessage.className = 'slot-message';
                slotMessage.style.display = 'block';
                confirmBtn.disabled = true;
                
                // AJAX request to check slot
                const xhr = new XMLHttpRequest();
                xhr.open('POST', 'collection_slot.php', true);
                xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
                xhr.onreadystatechange = function() {
                    if (this.readyState === 4 && this.status === 200) {
                        const response = JSON.parse(this.responseText);
                        
                        if (response.status === 'success') {
                            slotMessage.textContent = 'Slot is available!';
                            slotMessage.className = 'slot-message slot-available';
                            confirmBtn.disabled = false;
                        } else {
                            slotMessage.textContent = response.message;
                            slotMessage.className = 'slot-message slot-unavailable';
                            confirmBtn.disabled = true;
                        }
                    }
                };
                xhr.send('action=check_slot&date=' + selectedDate + '&time=' + selectedTime);
            }
            
            // Check slot availability when date or time changes
            dateSelect.addEventListener('change', checkSlotAvailability);
            timeSelect.addEventListener('change', checkSlotAvailability);
        });
    </script>
</body>
</html>
