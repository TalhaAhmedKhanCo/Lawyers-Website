<?php
require_once __DIR__ . '/db.php';

$queries = [
    "ALTER TABLE lawyer_profiles ADD COLUMN IF NOT EXISTS work_days VARCHAR(100) DEFAULT 'Mon,Tue,Wed,Thu,Fri'",
    "ALTER TABLE lawyer_profiles ADD COLUMN IF NOT EXISTS work_start_time TIME DEFAULT '09:00:00'",
    "ALTER TABLE lawyer_profiles ADD COLUMN IF NOT EXISTS work_end_time TIME DEFAULT '17:00:00'"
];

foreach ($queries as $query) {
    if ($conn->query($query) === TRUE) {
        echo "Query executed successfully: $query\n";
    } else {
        echo "Error executing query: " . $conn->error . "\n";
    }
}
?>
