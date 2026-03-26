<?php
require_once __DIR__ . '/db.php';
$query = "ALTER TABLE appointment_slots ADD COLUMN IF NOT EXISTS is_full_day TINYINT(1) DEFAULT 0";
if ($conn->query($query) === TRUE) {
    echo "Column is_full_day added successfully.";
} else {
    echo "Error: " . $conn->error;
}
?>
