<?php
// Load authentication and database helpers.
require_once __DIR__ . '/auth.php';

// Allow only logged-in customers to create bookings.
require_role('customer');

// Process only POST requests for appointment booking.
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $lawyerId = (int)($_POST['lawyer_id'] ?? 0);
    $slotData = $_POST['slot_data'] ?? ''; // Format: "YYYY-MM-DD|HH:MM"
    $notes = trim($_POST['notes'] ?? '');
    $customerId = (int)$_SESSION['user']['id'];

    if ($slotData !== '' && $lawyerId > 0) {
        $parts = explode('|', $slotData);
        $date = $parts[0];
        $time = $parts[1];

        // Fetch lawyer profile to verify schedule
        $lawyer = get_lawyer_by_id($conn, $lawyerId);
        
        // Final validation: check it's a work day and the slot is available
        if (!$lawyer || !is_work_day($lawyer, $date) || !is_slot_available($conn, $lawyerId, $date, $time)) {
            header('Location: lawyer_profile.php?id=' . $lawyerId . '&error=This time slot is no longer available.');
            exit;
        }

        // Start a database transaction so booking stays consistent.
        $conn->begin_transaction();

        try {
            // 1. Create the slot record on the fly (marking it as booked)
            $insertSlot = $conn->prepare('INSERT INTO appointment_slots (lawyer_id, slot_date, slot_time, is_booked) VALUES (?, ?, ?, 1)');
            $insertSlot->bind_param('iss', $lawyerId, $date, $time);
            $insertSlot->execute();
            $newSlotId = $insertSlot->insert_id;

            // 2. Create the appointment record (starts as Pending for lawyer approval).
            $insertAppointment = $conn->prepare('INSERT INTO appointments (customer_id, lawyer_id, slot_id, notes, status) VALUES (?, ?, ?, ?, ?)');
            $status = 'Pending';
            $insertAppointment->bind_param('iiiss', $customerId, $lawyerId, $newSlotId, $notes, $status);
            $insertAppointment->execute();

            $conn->commit();
            header('Location: customer_dashboard.php?success=Appointment booked successfully!');
            exit;
        } catch (Exception $exception) {
            // Roll back the transaction if anything goes wrong.
            $conn->rollback();
            header('Location: lawyer_profile.php?id=' . $lawyerId . '&error=Could not complete booking. Please try again.');
            exit;
        }
    }
}

// Redirect to search if the request is invalid.
header('Location: search.php');
exit;
?>
