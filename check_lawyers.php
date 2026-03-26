<?php
require_once __DIR__ . '/db.php';
$res = $conn->query("SELECT u.id, u.name, u.role, lp.user_id FROM users u LEFT JOIN lawyer_profiles lp ON u.id = lp.user_id WHERE u.role = 'lawyer'");
while ($row = $res->fetch_assoc()) {
    print_r($row);
}
?>
