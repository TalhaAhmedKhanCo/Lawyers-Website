<?php
require_once __DIR__ . '/db.php';

// Create reviews table
$sql = "CREATE TABLE IF NOT EXISTS reviews (
    id INT AUTO_INCREMENT PRIMARY KEY,
    lawyer_id INT NOT NULL,
    customer_id INT NOT NULL,
    rating INT NOT NULL,
    comment TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (lawyer_id) REFERENCES users(id),
    FOREIGN KEY (customer_id) REFERENCES users(id)
)";

if ($conn->query($sql)) {
    echo "Reviews table created successfully.\n";
} else {
    echo "Error creating reviews table: " . $conn->error . "\n";
}
?>
