<?php
// Load authentication before output so redirects work correctly.
require_once __DIR__ . '/auth.php';

// Allow only customers to view this dashboard.
require_role('customer');
require_once __DIR__ . '/header.php';

// Query all appointments made by the logged-in customer.
$customerId = (int)$_SESSION['user']['id'];
$sql = "SELECT a.id, a.notes, a.status, s.slot_date, s.slot_time, u.name AS lawyer_name, lp.specialization, lp.meeting_place
        FROM appointments a
        INNER JOIN appointment_slots s ON s.id = a.slot_id
        INNER JOIN users u ON u.id = a.lawyer_id
        INNER JOIN lawyer_profiles lp ON lp.user_id = u.id
        WHERE a.customer_id = ?
        ORDER BY s.slot_date DESC, s.slot_time DESC";
$stmt = $conn->prepare($sql);
$stmt->bind_param('i', $customerId);
$stmt->execute();
$appointments = $stmt->get_result();
$success = $_GET['success'] ?? '';
$error = $_GET['error'] ?? '';

// Handle customer cancellation
if (isset($_POST['cancel_appointment'])) {
    $appointmentId = (int)$_POST['appointment_id'];
    $customerId = (int)$_SESSION['user']['id'];

    // Update appointment status
    $stmt = $conn->prepare("UPDATE appointments SET status = 'Cancelled' WHERE id = ? AND customer_id = ? AND status IN ('Pending', 'Booked')");
    $stmt->bind_param('ii', $appointmentId, $customerId);
    
    if ($stmt->execute() && $stmt->affected_rows > 0) {
        // Reopen the slot
        $unStmt = $conn->prepare("UPDATE appointment_slots s JOIN appointments a ON s.id = a.slot_id SET s.is_booked = 0 WHERE a.id = ?");
        $unStmt->bind_param('i', $appointmentId);
        $unStmt->execute();
        header('Location: customer_dashboard.php?msg=Appointment cancelled.');
        exit;
    }
}
?>
<div class="d-flex justify-content-between align-items-center mb-4">
    <!-- Show the customer dashboard title and quick action. -->
    <h2 class="mb-0">Customer Dashboard</h2>
    <a href="search.php" class="btn btn-primary">Book New Appointment</a>
</div>

<?php if (isset($_GET['success'])): ?>
    <div class="alert alert-success">Your appointment was booked successfully.</div>
<?php endif; ?>

<div class="card shadow-sm">
    <div class="card-body">
        <h4 class="mb-3">My Appointments</h4>
        <div class="table-responsive">
            <table class="table table-striped align-middle">
                <thead>
                    <tr>
                        <th>Lawyer</th>
                        <th>Specialization</th>
                        <th>Date & Time</th>
                        <th>Meeting Place</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($row = $appointments->fetch_assoc()): ?>
                        <tr>
                            <td>
                                <strong><?php echo e($row['lawyer_name']); ?></strong><br>
                                <small class="text-muted"><?php echo e($row['specialization']); ?></small>
                            </td>
                            <td><?php echo e($row['specialization']); ?></td>
                            <td>
                                <div><?php echo e(date('M j, Y', strtotime($row['slot_date']))); ?></div>
                                <div class="small fw-bold"><?php echo e(date('h:i A', strtotime($row['slot_time']))); ?></div>
                            </td>
                            <td><?php echo e($row['meeting_place']); ?></td>
                            <td>
                                <?php if ($row['status'] == 'Pending'): ?>
                                    <span class="badge bg-warning text-dark">Pending</span>
                                <?php elseif ($row['status'] == 'Booked'): ?>
                                    <span class="badge bg-success">Confirmed</span>
                                <?php elseif ($row['status'] == 'Declined'): ?>
                                    <span class="badge bg-danger">Declined</span>
                                <?php elseif ($row['status'] == 'Cancelled'): ?>
                                    <span class="badge bg-secondary">Cancelled</span>
                                <?php else: ?>
                                    <span class="badge bg-info text-dark"><?php echo e($row['status']); ?></span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if (in_array($row['status'], ['Pending', 'Booked'])): ?>
                                    <form method="post" onsubmit="return confirm('Are you sure you want to cancel this appointment?')">
                                        <input type="hidden" name="cancel_appointment" value="1">
                                        <input type="hidden" name="appointment_id" value="<?php echo (int)$row['id']; ?>">
                                        <button type="submit" class="btn btn-sm btn-outline-danger shadow-sm">Cancel</button>
                                    </form>
                                <?php else: ?>
                                    <span class="text-muted small">No actions</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
<?php
// Render the common footer.
require_once __DIR__ . '/footer.php';
?>
