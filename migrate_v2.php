<?php
require_once __DIR__ . '/db.php';

$queries = [
    "ALTER TABLE appointment_slots ADD COLUMN IF NOT EXISTS is_full_day TINYINT(1) DEFAULT 0",
    "ALTER TABLE appointments MODIFY COLUMN status ENUM('Pending', 'Booked', 'Completed', 'Cancelled', 'Declined') DEFAULT 'Pending'"
];

foreach ($queries as $query) {
    if ($conn->query($query) === TRUE) {
        echo "Executed: $query\n";
    } else {
        echo "Error: " . $conn->error . "\n";
    }
}
?>
