<?php
// Load authentication before output so redirects work correctly.
require_once __DIR__ . '/auth.php';

// Allow only lawyers to access this page.
require_role('lawyer');
require_once __DIR__ . '/header.php';

$lawyerId = (int)$_SESSION['user']['id'];
$success = '';
$error = '';

// Update the lawyer profile when the profile form is submitted.
if (isset($_POST['update_profile'])) {
    $specialization = trim($_POST['specialization'] ?? '');
    $location = trim($_POST['location'] ?? '');
    $bio = trim($_POST['bio'] ?? '');
    $meetingPlace = trim($_POST['meeting_place'] ?? '');
    $experienceYears = (int)($_POST['experience_years'] ?? 0);
    $consultationFee = (float)($_POST['consultation_fee'] ?? 0);
    $barRegistrationNo = trim($_POST['bar_registration_no'] ?? '');

    // Recurring Schedule fields
    $workDays = isset($_POST['work_days']) ? implode(',', $_POST['work_days']) : 'Mon,Tue,Wed,Thu,Fri';
    $workStart = $_POST['work_start_time'] ?? '09:00';
    $workEnd = $_POST['work_end_time'] ?? '17:00';

    $photoUrl = trim($_POST['photo_url'] ?? '');

    // Handle Photo Upload if a file was provided
    if (isset($_FILES['photo_file']) && $_FILES['photo_file']['error'] === UPLOAD_ERR_OK) {
        $fileTmpPath = $_FILES['photo_file']['tmp_name'];
        $fileName = $_FILES['photo_file']['name'];
        $fileNameCmps = explode(".", $fileName);
        $fileExtension = strtolower(end($fileNameCmps));

        $allowedfileExtensions = array('jpg', 'gif', 'png', 'jpeg', 'webp');
        if (in_array($fileExtension, $allowedfileExtensions)) {
            $uploadFileDir = 'uploads/';
            $newFileName = time() . '_' . bin2hex(random_bytes(8)) . '.' . $fileExtension;
            $dest_path = $uploadFileDir . $newFileName;

            if (move_uploaded_file($fileTmpPath, $dest_path)) {
                $photoUrl = $dest_path;
            }
        }
    }

    $stmt = $conn->prepare('UPDATE lawyer_profiles SET specialization = ?, location = ?, bio = ?, meeting_place = ?, experience_years = ?, consultation_fee = ?, bar_registration_no = ?, photo_url = ?, work_days = ?, work_start_time = ?, work_end_time = ? WHERE user_id = ?');
    $stmt->bind_param('ssssidsssssi', $specialization, $location, $bio, $meetingPlace, $experienceYears, $consultationFee, $barRegistrationNo, $photoUrl, $workDays, $workStart, $workEnd, $lawyerId);
    $stmt->execute();
    $success = 'Profile and work schedule updated successfully.';
}

// DELETE manual block / leave
if (isset($_GET['delete_leave'])) {
    $slotId = (int)$_GET['delete_leave'];
    $stmt = $conn->prepare('DELETE FROM appointment_slots WHERE id = ? AND lawyer_id = ? AND is_booked = 0');
    $stmt->bind_param('ii', $slotId, $lawyerId);
    $stmt->execute();
    $success = 'Leave/Block removed. You are now available at that time.';
}

// Add a specific Leave or Block
if (isset($_POST['add_leave'])) {
    $slotDate = $_POST['slot_date'] ?? '';
    $slotTime = $_POST['slot_time'] ?? '';
    $isFullDay = isset($_POST['is_full_day']) ? 1 : 0;

    if ($slotDate !== '') {
        $stmt = $conn->prepare('INSERT INTO appointment_slots (lawyer_id, slot_date, slot_time, is_booked, is_full_day) VALUES (?, ?, ?, 0, ?)');
        $stmt->bind_param('issi', $lawyerId, $slotDate, $slotTime, $isFullDay);
        $stmt->execute();
        $success = $isFullDay ? 'Full day marked as unavailable.' : 'Specific time marked as unavailable.';
    } else {
        $error = 'Please provide a date.';
    }
}

// Handle Appointment Status Changes (Approve/Decline/Reschedule)
if (isset($_POST['update_appointment'])) {
    $appointmentId = (int)$_POST['appointment_id'];
    $action = $_POST['action'] ?? '';
    $newTime = $_POST['new_time'] ?? '';

    if ($action === 'Approve') {
        $stmt = $conn->prepare("UPDATE appointments SET status = 'Booked' WHERE id = ? AND lawyer_id = ?");
        $stmt->bind_param('ii', $appointmentId, $lawyerId);
        $stmt->execute();
        $success = 'Appointment approved.';
    } elseif ($action === 'Decline') {
        $stmt = $conn->prepare("UPDATE appointments SET status = 'Declined' WHERE id = ? AND lawyer_id = ?");
        $stmt->bind_param('ii', $appointmentId, $lawyerId);
        $stmt->execute();
        // Also unbook the slot if it was created dynamically
        $unStmt = $conn->prepare("UPDATE appointment_slots s JOIN appointments a ON s.id = a.slot_id SET s.is_booked = 0 WHERE a.id = ?");
        $unStmt->bind_param('i', $appointmentId);
        $unStmt->execute();
        $success = 'Appointment declined.';
    } elseif ($action === 'Reschedule') {
        if ($newTime !== '') {
            $stmt = $conn->prepare("UPDATE appointment_slots s JOIN appointments a ON s.id = a.slot_id SET s.slot_time = ?, s.is_booked = 1 WHERE a.id = ? AND a.lawyer_id = ?");
            $stmt->bind_param('sii', $newTime, $appointmentId, $lawyerId);
            $stmt->execute();
            
            // Also update status to 'Booked' so it's confirmed
            $stStmt = $conn->prepare("UPDATE appointments SET status = 'Booked' WHERE id = ?");
            $stStmt->bind_param('i', $appointmentId);
            $stStmt->execute();
            
            $success = 'Appointment rescheduled and confirmed.';
        }
    } elseif ($action === 'Complete') {
        $stmt = $conn->prepare("UPDATE appointments SET status = 'Completed' WHERE id = ? AND lawyer_id = ?");
        $stmt->bind_param('ii', $appointmentId, $lawyerId);
        $stmt->execute();
        $success = 'Appointment marked as completed.';
    } elseif ($action === 'Cancel_Booked') {
        $stmt = $conn->prepare("UPDATE appointments SET status = 'Cancelled' WHERE id = ? AND lawyer_id = ?");
        $stmt->bind_param('ii', $appointmentId, $lawyerId);
        if($stmt->execute()) {
            // Reopen the slot
            $unStmt = $conn->prepare("UPDATE appointment_slots s JOIN appointments a ON s.id = a.slot_id SET s.is_booked = 0 WHERE a.id = ?");
            $unStmt->bind_param('i', $appointmentId);
            $unStmt->execute();
            $success = 'Appointment cancelled and time slot reopened.';
        }
    }
}

// Load the current lawyer profile for editing.
$profileStmt = $conn->prepare('SELECT * FROM lawyer_profiles WHERE user_id = ?');
$profileStmt->bind_param('i', $lawyerId);
$profileStmt->execute();
$profile = $profileStmt->get_result()->fetch_assoc();

// Load appointment slots created by the lawyer.
$slotListStmt = $conn->prepare('SELECT * FROM appointment_slots WHERE lawyer_id = ? ORDER BY slot_date DESC, slot_time DESC');
$slotListStmt->bind_param('i', $lawyerId);
$slotListStmt->execute();
$slotList = $slotListStmt->get_result();

// Load booked appointments for the lawyer.
$appointmentSql = "SELECT a.id, a.notes, a.status, s.slot_date, s.slot_time, u.name AS customer_name, u.email AS customer_email
                   FROM appointments a
                   INNER JOIN appointment_slots s ON s.id = a.slot_id
                   INNER JOIN users u ON u.id = a.customer_id
                   WHERE a.lawyer_id = ?
                   ORDER BY s.slot_date DESC, s.slot_time DESC";
$appointmentStmt = $conn->prepare($appointmentSql);
$appointmentStmt->bind_param('i', $lawyerId);
$appointmentStmt->execute();
$appointments = $appointmentStmt->get_result();

// NEW: Query reviews for this specific lawyer
$lawyer_reviews = $conn->prepare("SELECT r.*, u.name AS customer_name 
                                  FROM reviews r
                                  INNER JOIN users u ON u.id = r.customer_id
                                  WHERE r.lawyer_id = ?
                                  ORDER BY r.created_at DESC");
$lawyer_reviews->bind_param('i', $lawyerId);
$lawyer_reviews->execute();
$myReviews = $lawyer_reviews->get_result();

// Handle lawyer reply to review
if (isset($_POST['submit_reply'])) {
    $reviewId = (int)$_POST['review_id'];
    $reply = trim($_POST['reply_text'] ?? '');
    
    // Ensure the review belongs to this lawyer
    $stmt = $conn->prepare("UPDATE reviews SET reply = ?, replied_at = NOW() WHERE id = ? AND lawyer_id = ?");
    $stmt->bind_param('sii', $reply, $reviewId, $lawyerId);
    $stmt->execute();
    $success = 'Your response to the review has been posted.';
}
?>
<div class="d-flex justify-content-between align-items-center mb-4">
    <!-- Show the lawyer dashboard heading. -->
    <h2 class="mb-0">Lawyer Dashboard</h2>
</div>

<?php if ($success): ?><div class="alert alert-success"><?php echo e($success); ?></div><?php endif; ?>
<?php if ($error): ?><div class="alert alert-danger"><?php echo e($error); ?></div><?php endif; ?>

<div class="row g-4">
    <div class="col-lg-6">
        <div class="card shadow-sm h-100">
            <div class="card-body">
                <!-- Form to maintain the lawyer's public profile information. -->
                <h4 class="mb-3">Manage Profile</h4>
                <form method="post" enctype="multipart/form-data">
                    <input type="hidden" name="update_profile" value="1">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Specialization</label>
                            <input type="text" name="specialization" class="form-control" value="<?php echo e($profile['specialization'] ?? ''); ?>">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Location</label>
                            <input type="text" name="location" class="form-control" value="<?php echo e($profile['location'] ?? ''); ?>">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Meeting Place</label>
                            <input type="text" name="meeting_place" class="form-control" value="<?php echo e($profile['meeting_place'] ?? ''); ?>">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Experience</label>
                            <input type="number" name="experience_years" class="form-control" value="<?php echo e($profile['experience_years'] ?? 0); ?>">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Fee</label>
                            <input type="number" step="0.01" name="consultation_fee" class="form-control" value="<?php echo e($profile['consultation_fee'] ?? 0); ?>">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Bar Registration No</label>
                            <input type="text" name="bar_registration_no" class="form-control" value="<?php echo e($profile['bar_registration_no'] ?? ''); ?>">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Profile Photo</label>
                            <input type="file" name="photo_file" class="form-control" accept="image/*">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">OR Photo URL</label>
                            <input type="url" name="photo_url" class="form-control" value="<?php echo e($profile['photo_url'] ?? ''); ?>">
                        </div>
                        <div class="col-12">
                            <label class="form-label">Bio</label>
                            <textarea name="bio" class="form-control" rows="3"><?php echo e($profile['bio'] ?? ''); ?></textarea>
                        </div>
                        <div class="col-12 mt-3">
                            <h5 class="border-bottom pb-2">Working Schedule</h5>
                            <p class="text-muted small">Standard Recurring Availability</p>
                            <div class="row g-2">
                                <div class="col-md-12">
                                    <label class="form-label d-block">Work Days</label>
                                    <?php 
                                    $currentDays = explode(',', $profile['work_days'] ?? 'Mon,Tue,Wed,Thu,Fri');
                                    $days = ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'];
                                    foreach($days as $d): ?>
                                        <div class="form-check form-check-inline">
                                            <input class="form-check-input" type="checkbox" name="work_days[]" value="<?= $d ?>" <?= in_array($d, $currentDays) ? 'checked' : '' ?>>
                                            <label class="form-check-label"><?= $d ?></label>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Start Time</label>
                                    <input type="time" name="work_start_time" class="form-control" value="<?= substr($profile['work_start_time'] ?? '09:00', 0, 5) ?>">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">End Time</label>
                                    <input type="time" name="work_end_time" class="form-control" value="<?= substr($profile['work_end_time'] ?? '17:00', 0, 5) ?>">
                                </div>
                            </div>
                        </div>
                    </div>
                    <button type="submit" class="btn btn-primary mt-4 w-100">Save Profile & Schedule</button>
                </form>
            </div>
        </div>
    </div>

    <div class="col-lg-6">
        <div class="card shadow-sm mb-4">
            <div class="card-body">
                <!-- Manage Absences/Leaves -->
                <h4 class="mb-3">Take a Leave / Block Time</h4>
                <p class="text-muted small">Mark specific times as **Unavailable** (overrides your normal schedule).</p>
                <form method="post" class="row g-3">
                    <input type="hidden" name="add_leave" value="1">
                    <div class="col-md-5">
                        <label class="form-label">Date</label>
                        <input type="date" name="slot_date" class="form-control" required>
                    </div>
                    <div class="col-md-5">
                        <label class="form-label">Time (Ignore if Full Day)</label>
                         <select name="slot_time" class="form-select" id="leaveTimeSelect">
                            <?php foreach(get_lawyer_slots($profile) as $h): ?>
                                <option value="<?= $h ?>"><?= $h ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-2 d-flex align-items-end">
                        <div class="form-check mb-2">
                            <input class="form-check-input" type="checkbox" name="is_full_day" id="isFullDay" onchange="document.getElementById('leaveTimeSelect').disabled = this.checked">
                            <label class="form-check-label" for="isFullDay">Full Day</label>
                        </div>
                    </div>
                    <div class="col-12">
                        <button type="submit" class="btn btn-danger">Mark Leave</button>
                    </div>
                </form>
            </div>
        </div>

        <div class="card shadow-sm">
            <div class="card-body">
                <!-- Display all slots so the lawyer can track availability. -->
                <h4 class="mb-3">Leaves & Exceptions</h4>
                <div class="table-responsive">
                    <table class="table table-striped align-middle">
                        <thead>
                            <tr>
                                <th>Date</th>
                                <th>Time / Duration</th>
                                <th>Type</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($slot = $slotList->fetch_assoc()): ?>
                                <tr>
                                    <td><?php echo e($slot['slot_date']); ?></td>
                                    <td>
                                        <?php if ($slot['is_full_day']): ?>
                                            Full Day
                                        <?php else: ?>
                                            <?php echo e(substr($slot['slot_time'], 0, 5)); ?>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if ($slot['is_booked']): ?>
                                            <span class="badge bg-primary">Customer Booking</span>
                                        <?php elseif ($slot['is_full_day']): ?>
                                            <span class="badge bg-dark">Full Day Leave</span>
                                        <?php else: ?>
                                            <span class="badge bg-danger">Hourly Leave</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if (!$slot['is_booked']): ?>
                                            <a href="?delete_leave=<?= $slot['id'] ?>" class="btn btn-sm btn-outline-danger" onclick="return confirm('Remove this block/leave?')">Remove</a>
                                        <?php else: ?>
                                            <span class="text-muted small">Cannot remove booking here</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="card shadow-sm mt-4">
    <div class="card-body">
        <!-- List appointments booked by customers. -->
        <h4 class="mb-3">Recent Bookings & Requests</h4>
        <div class="table-responsive">
            <table class="table table-striped align-middle">
                <thead>
                    <tr>
                        <th>Customer</th>
                        <th>Date & Time</th>
                        <th>Status</th>
                        <th>Notes</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($apt = $appointments->fetch_assoc()): ?>
                        <tr>
                            <td>
                                <strong><?php echo e($apt['customer_name']); ?></strong><br>
                                <small class="text-muted"><?php echo e($apt['customer_email']); ?></small>
                            </td>
                            <td>
                                <div><?php echo date('M j, Y', strtotime($apt['slot_date'])); ?></div>
                                <div class="small fw-bold"><?php echo date('h:i A', strtotime($apt['slot_time'])); ?></div>
                            </td>
                            <td>
                                <?php if ($apt['status'] == 'Pending'): ?>
                                    <span class="badge bg-warning text-dark">Pending Approval</span>
                                <?php elseif ($apt['status'] == 'Booked'): ?>
                                    <span class="badge bg-success">Approved</span>
                                <?php elseif ($apt['status'] == 'Declined'): ?>
                                    <span class="badge bg-danger">Declined</span>
                                <?php else: ?>
                                    <span class="badge bg-secondary"><?php echo $apt['status']; ?></span>
                                <?php endif; ?>
                            </td>
                            <td class="small"><?php echo e($apt['notes']); ?></td>
                            <td>
                                <?php if ($apt['status'] == 'Pending'): ?>
                                    <div class="d-flex gap-1">
                                        <form method="post" class="d-inline">
                                            <input type="hidden" name="update_appointment" value="1">
                                            <input type="hidden" name="appointment_id" value="<?php echo $apt['id']; ?>">
                                            <input type="hidden" name="action" value="Approve">
                                            <button class="btn btn-sm btn-success" title="Approve">✔</button>
                                        </form>
                                        <form method="post" class="d-inline">
                                            <input type="hidden" name="update_appointment" value="1">
                                            <input type="hidden" name="appointment_id" value="<?php echo $apt['id']; ?>">
                                            <input type="hidden" name="action" value="Decline">
                                            <button class="btn btn-sm btn-danger" title="Decline">✖</button>
                                        </form>
                                        <button class="btn btn-sm btn-outline-primary" onclick="showReschedule(<?php echo $apt['id']; ?>, '<?php echo substr($apt['slot_time'], 0, 5); ?>')" title="Reschedule">🕒</button>
                                    </div>
                                <?php elseif ($apt['status'] == 'Booked'): ?>
                                    <div class="d-flex gap-1">
                                        <form method="post" class="d-inline" onsubmit="return confirm('Mark this case as completed?')">
                                            <input type="hidden" name="update_appointment" value="1">
                                            <input type="hidden" name="appointment_id" value="<?php echo $apt['id']; ?>">
                                            <input type="hidden" name="action" value="Complete">
                                            <button class="btn btn-sm btn-outline-success">Done</button>
                                        </form>
                                        <form method="post" class="d-inline" onsubmit="return confirm('Are you sure you want to cancel this booking? The slot will be reopened.')">
                                            <input type="hidden" name="update_appointment" value="1">
                                            <input type="hidden" name="appointment_id" value="<?php echo $apt['id']; ?>">
                                            <input type="hidden" name="action" value="Cancel_Booked">
                                            <button class="btn btn-sm btn-outline-danger">Cancel</button>
                                        </form>
                                    </div>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>
    <!-- NEW: Reviews Section for Lawyer -->
    <div class="col-12 mt-4">
        <div class="card shadow-sm border-0">
            <div class="card-body p-4">
                <h4 class="mb-4 d-flex align-items-center">
                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="me-2 text-primary"><path d="M21 15a2 2 0 0 1-2 2H7l4-4-4-4h12a2 2 0 0 1 2 2z"></path></svg>
                    Client Feedback
                </h4>
                <div class="table-responsive">
                    <table class="table table-hover align-middle">
                        <thead class="bg-light">
                            <tr>
                                <th>Client Name</th>
                                <th>Rating</th>
                                <th>Comment</th>
                                <th>Date Received</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($rev = $myReviews->fetch_assoc()): ?>
                                <tr>
                                    <td><strong><?php echo e($rev['customer_name']); ?></strong></td>
                                    <td>
                                        <div class="text-warning small fw-bold">
                                            <?php for($i=1; $i<=5; $i++) echo $i <= $rev['rating'] ? '★' : '☆'; ?>
                                        </div>
                                    </td>
                                    <td>
                                        <div class="mb-2 text-dark"><?php echo e($rev['comment']); ?></div>
                                        <?php if ($rev['reply']): ?>
                                            <div class="bg-light p-2 rounded-2 small border-start border-primary border-4">
                                                <span class="fw-bold text-primary">Your Reply:</span><br>
                                                <?php echo e($rev['reply']); ?>
                                            </div>
                                        <?php endif; ?>
                                    </td>
                                    <td><span class="small text-muted"><?php echo date('M j, Y', strtotime($rev['created_at'])); ?></span></td>
                                    <td>
                                        <button class="btn btn-sm btn-outline-primary" onclick="showReplyModal(<?php echo (int)$rev['id']; ?>, '<?php echo e(addslashes($rev['reply'])); ?>')">
                                            <?php echo $rev['reply'] ? 'Edit Reply' : 'Post Reply'; ?>
                                        </button>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                            <?php if ($myReviews->num_rows === 0): ?>
                                <tr>
                                    <td colspan="4" class="text-center py-4 text-muted small italic">No reviews received yet.</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

    </div>
</div>

<!-- Reply to Review Modal -->
<div id="replyModal" class="position-fixed top-0 start-0 w-100 h-100 bg-dark bg-opacity-50 d-none" style="z-index: 1050;">
    <div class="position-absolute top-50 start-50 translate-middle bg-white p-4 rounded-3 shadow" style="width: 500px; max-width: 90%;">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h5 class="mb-0">Reply to Review</h5>
            <button type="button" class="btn-close" onclick="hideReplyModal()"></button>
        </div>
        <form method="post">
            <input type="hidden" name="submit_reply" value="1">
            <input type="hidden" name="review_id" id="replyReviewId">
            <div class="mb-3">
                <label class="form-label fw-bold small text-uppercase">Your Message</label>
                <textarea name="reply_text" id="replyTextarea" class="form-control" rows="5" placeholder="Write your professional response here..." required></textarea>
                <small class="text-muted text-wrap">This reply will be visible to everyone on your public profile.</small>
            </div>
            <div class="d-flex justify-content-end gap-2">
                <button type="button" class="btn btn-light" onclick="hideReplyModal()">Cancel</button>
                <button type="submit" class="btn btn-primary">Post Reply</button>
            </div>
        </form>
    </div>
</div>

<!-- Reschedule Modal -->
<div id="rescheduleModal" class="position-fixed top-0 start-0 w-100 h-100 bg-dark bg-opacity-50 d-none" style="z-index: 1050;">
    <div class="position-absolute top-50 start-50 translate-middle bg-white p-4 rounded-3 shadow" style="width: 400px; max-width: 90%;">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h5 class="mb-0">Reschedule Appointment</h5>
            <button type="button" class="btn-close" onclick="hideReschedule()"></button>
        </div>
        <form method="post">
            <input type="hidden" name="update_appointment" value="1">
            <input type="hidden" name="action" value="Reschedule">
            <input type="hidden" name="appointment_id" id="resAptId">
            <div class="mb-3">
                <label class="form-label">Select New Time</label>
                <select name="new_time" class="form-select" id="resTimeSelect">
                    <?php foreach(get_lawyer_slots($profile) as $h): ?>
                        <option value="<?php echo $h; ?>"><?php echo $h; ?></option>
                    <?php endforeach; ?>
                </select>
                <small class="text-muted">This will update the appointment time for the customer.</small>
            </div>
            <div class="d-flex justify-content-end gap-2">
                <button type="button" class="btn btn-light" onclick="hideReschedule()">Cancel</button>
                <button type="submit" class="btn btn-primary">Update Time</button>
            </div>
        </form>
    </div>
</div>

<script>
function showReschedule(id, time) {
    document.getElementById('resAptId').value = id;
    document.getElementById('resTimeSelect').value = time;
    document.getElementById('rescheduleModal').classList.remove('d-none');
}
function hideReschedule() {
    document.getElementById('rescheduleModal').classList.add('d-none');
}

function showReplyModal(id, currentReply) {
    document.getElementById('replyReviewId').value = id;
    document.getElementById('replyTextarea').value = currentReply;
    document.getElementById('replyModal').classList.remove('d-none');
}

function hideReplyModal() {
    document.getElementById('replyModal').classList.add('d-none');
}

// Close modal on escape key
document.addEventListener('keydown', (e) => {
    if (e.key === 'Escape') {
        hideReschedule();
        hideReplyModal();
    }
});
</script>
<?php
// Render the common footer.
require_once __DIR__ . '/footer.php';
?>
