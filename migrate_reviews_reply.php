<?php
require_once __DIR__ . '/db.php';

$sql = "ALTER TABLE reviews ADD COLUMN reply TEXT NULL, ADD COLUMN replied_at TIMESTAMP NULL";

if ($conn->query($sql)) {
    echo "Reply columns added to reviews table.\n";
} else {
    echo "Error updating reviews table: " . $conn->error . "\n";
}
?>
