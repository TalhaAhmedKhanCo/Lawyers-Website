<?php
// Include the database connection for authentication-related operations.
require_once __DIR__ . '/db.php';

// Check whether any user is currently logged in.
function is_logged_in() {
    return isset($_SESSION['user']);
}

// Check whether the logged-in user has the expected role.
function has_role($role) {
    return is_logged_in() && $_SESSION['user']['role'] === $role;
}

// Redirect guests to the login page when authentication is required.
function require_login() {
    if (!is_logged_in()) {
        header('Location: login.php');
        exit;
    }
}

// Redirect users who do not have the expected role.
function require_role($role) {
    require_login();

    if (!has_role($role)) {
        header('Location: index.php');
        exit;
    }
}

// Escape output before printing into HTML to prevent XSS.
function e($value) {
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

// Fetch a lawyer profile record with its linked user data.
function get_lawyer_by_id($conn, $lawyerId) {
    $sql = "SELECT lp.*, u.id, u.name, u.email,
                   (SELECT AVG(rating) FROM reviews r WHERE r.lawyer_id = u.id) as avg_rating,
                   (SELECT COUNT(*) FROM reviews r WHERE r.lawyer_id = u.id) as review_count
            FROM users u
            INNER JOIN lawyer_profiles lp ON lp.user_id = u.id
            WHERE u.id = ? AND u.role = 'lawyer'";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param('i', $lawyerId);
    $stmt->execute();
    $result = $stmt->get_result();
    return $result->fetch_assoc();
}

/**
 * Returns an array of available time slots based on the lawyer's custom schedule.
 * Example for lawyer working 09:00 to 17:00.
 */
function get_lawyer_slots($lawyer) {
    $startStop = [
        (int)substr($lawyer['work_start_time'] ?? '09:00:00', 0, 2),
        (int)substr($lawyer['work_end_time'] ?? '17:00:00', 0, 2)
    ];

    $slots = [];
    for ($h = $startStop[0]; $h < $startStop[1]; $h++) {
        $slots[] = str_pad($h, 2, '0', STR_PAD_LEFT) . ':00';
    }
    return $slots;
}

/**
 * Checks if a specific date is a working day for the lawyer.
 */
function is_work_day($lawyer, $date) {
    $dayOfWeek = date('D', strtotime($date)); // e.g., Mon, Tue
    $workDays = explode(',', $lawyer['work_days'] ?? 'Mon,Tue,Wed,Thu,Fri');
    return in_array($dayOfWeek, $workDays);
}

/**
 * Checks if a specific date/time is available for a lawyer.
 * A slot is NOT available if:
 * 1. It is already booked (is_booked = 1)
 * 2. It is manually blocked as "Leave" or "Unavailable" (is_booked = 0)
 * 3. The entire date is blocked (is_full_day = 1)
 */
function is_slot_available($conn, $lawyerId, $date, $time) {
    // 1. Check if the whole day is blocked
    $stmt = $conn->prepare("SELECT id FROM appointment_slots WHERE lawyer_id = ? AND slot_date = ? AND is_full_day = 1");
    $stmt->bind_param('is', $lawyerId, $date);
    $stmt->execute();
    if ($stmt->get_result()->num_rows > 0) return false;

    // 2. Check for manual block or existing booking for this hour
    $stmt = $conn->prepare("SELECT id FROM appointment_slots WHERE lawyer_id = ? AND slot_date = ? AND slot_time LIKE ?");
    $timePattern = $time . '%';
    $stmt->bind_param('iss', $lawyerId, $date, $timePattern);
    $stmt->execute();
    return $stmt->get_result()->num_rows === 0;
}

/**
 * Checks if an entire day is marked as Leave.
 */
function is_day_blocked($conn, $lawyerId, $date) {
    $stmt = $conn->prepare("SELECT id FROM appointment_slots WHERE lawyer_id = ? AND slot_date = ? AND is_full_day = 1");
    $stmt->bind_param('is', $lawyerId, $date);
    $stmt->execute();
    return $stmt->get_result()->num_rows > 0;
}
?>
