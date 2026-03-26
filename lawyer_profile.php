<?php
// Load shared dependencies and layout.
require_once __DIR__ . '/header.php';

// Get the lawyer ID from the URL query string.
$lawyerId = (int)($_GET['id'] ?? 0);
$lawyer = get_lawyer_by_id($conn, $lawyerId);

// Stop execution if the lawyer does not exist.
if (!$lawyer) {
    echo '<div class="alert alert-danger">Lawyer not found.</div>';
    require_once __DIR__ . '/footer.php';
    exit;
}

// We will generate slots dynamically for the next 7 days based on recurring schedule
$available_days = [];
for ($i = 0; $i < 7; $i++) {
    $date = date('Y-m-d', strtotime("+$i days"));
    
    // Only proceed if it's a working day and NOT a full day leave/block
    if (!is_work_day($lawyer, $date) || is_day_blocked($conn, $lawyerId, $date)) continue;

    $available_days[$date] = [];
    foreach (get_lawyer_slots($lawyer) as $time) {
        if (is_slot_available($conn, $lawyerId, $date, $time)) {
            $available_days[$date][] = $time;
        }
    }
}

$success = $_GET['success'] ?? '';
$error = $_GET['error'] ?? '';

// Handle review submission
if (isset($_POST['submit_review']) && is_logged_in() && has_role('customer')) {
    $rating = (int)($_POST['rating'] ?? 0);
    $comment = trim($_POST['comment'] ?? '');
    $customerId = $_SESSION['user']['id'];
    
    if ($rating >= 1 && $rating <= 5) {
        $stmt = $conn->prepare("INSERT INTO reviews (lawyer_id, customer_id, rating, comment) VALUES (?, ?, ?, ?)");
        $stmt->bind_param('iiis', $lawyerId, $customerId, $rating, $comment);
        $stmt->execute();
        header("Location: lawyer_profile.php?id=$lawyerId&success=Thank you for your review!");
        exit;
    } else {
        $error = "Please provide a valid rating between 1 and 5.";
    }
}

// Fetch reviews for this lawyer
$reviewStmt = $conn->prepare("SELECT r.*, u.name as customer_name FROM reviews r JOIN users u ON u.id = r.customer_id WHERE r.lawyer_id = ? ORDER BY r.created_at DESC");
$reviewStmt->bind_param('i', $lawyerId);
$reviewStmt->execute();
$reviewsList = $reviewStmt->get_result();
?>
<div class="row g-4 pb-5 animate-up">
    <?php if ($success): ?><div class="col-12"><div class="alert alert-success mt-4"><?php echo e($success); ?></div></div><?php endif; ?>
    <?php if ($error): ?><div class="col-12"><div class="alert alert-danger mt-4"><?php echo e($error); ?></div></div><?php endif; ?>
    <div class="col-lg-4">
        <div class="card border-0 shadow-lg overflow-hidden h-100">
            <!-- Show profile photo and summary details. -->
            <img src="<?php echo e($lawyer['photo_url'] ?: 'https://images.unsplash.com/photo-1589829545856-d10d557cf95f?q=80&w=800&auto=format&fit=crop'); ?>" class="card-img-top" style="height: 350px; object-fit: cover;" alt="Lawyer Photo">
            <div class="card-body p-4">
                <h2 class="fw-bold mb-1"><?php echo e($lawyer['name']); ?></h2>
                
                <div class="d-flex align-items-center mb-3">
                    <div class="text-warning fs-5 me-2">
                        <?php 
                        $avg = (float)$lawyer['avg_rating'];
                        for($i=1; $i<=5; $i++) echo $i <= round($avg) ? '★' : '☆';
                        ?>
                    </div>
                    <span class="fw-bold"><?php echo ($avg > 0) ? number_format($avg, 1) : 'New'; ?></span>
                    <span class="text-muted small ms-1">(<?php echo $lawyer['review_count']; ?>)</span>
                </div>

                <span class="badge bg-primary px-3 py-2 rounded-pill mb-4"><?php echo e($lawyer['specialization']); ?></span>
                
                <div class="mb-3 d-flex align-items-center text-muted">
                    <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="me-2"><path d="M20 10c0 6-8 12-8 12s-8-6-8-12a8 8 0 0 1 16 0Z"/><circle cx="12" cy="10" r="3"/></svg>
                    <span><?php echo e($lawyer['location']); ?></span>
                </div>
                <div class="mb-3 d-flex align-items-center text-muted">
                    <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="me-2"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z"></path></svg>
                    <span><?php echo e($lawyer['email']); ?></span>
                </div>
                <div class="mb-3 d-flex align-items-center text-muted">
                    <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="me-2"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"></path><circle cx="12" cy="10" r="3"></circle></svg>
                    <span><?php echo e($lawyer['meeting_place']); ?></span>
                </div>

                <hr class="my-4 opacity-50">
                
                <div class="row text-center">
                    <div class="col-6 border-end">
                        <small class="text-muted d-block text-uppercase fw-bold">Experience</small>
                        <span class="fs-5 fw-bold text-primary"><?php echo e($lawyer['experience_years']); ?>+ Yrs</span>
                    </div>
                    <div class="col-6">
                        <small class="text-muted d-block text-uppercase fw-bold">Reg No</small>
                        <span class="fs-6 fw-bold text-primary"><?php echo e($lawyer['bar_registration_no']); ?></span>
                    </div>
                </div>
            </div>
            <div class="card-footer bg-primary text-white text-center p-3 border-0">
                <span class="small opacity-75">Consultation Fee</span>
                <div class="fs-4 fw-bold">PKR <?php echo e((int)$lawyer['consultation_fee']); ?></div>
            </div>
        </div>
    </div>
    
    <div class="col-lg-8">
        <div class="card border-0 shadow-lg mb-4">
            <div class="card-body p-4">
                <h4 class="fw-bold mb-3 d-flex align-items-center">
                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="me-2 text-primary"><path d="M19 21v-2a4 4 0 0 0-4-4H9a4 4 0 0 0-4 4v2"></path><circle cx="12" cy="7" r="4"></circle></svg>
                    Professional Biography
                </h4>
                <p class="text-muted lead mb-0" style="font-size: 1rem; line-height: 1.8;"><?php echo nl2br(e($lawyer['bio'])); ?></p>
            </div>
        </div>

        <div class="card border-0 shadow-lg">
            <div class="card-body p-4">
                <h4 class="fw-bold mb-4 d-flex align-items-center">
                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="me-2 text-primary"><rect width="18" height="18" x="3" y="4" rx="2" ry="2"></rect><line x1="16" x2="16" y1="2" y2="6"></line><line x1="8" x2="8" y1="2" y2="6"></line><line x1="3" x2="21" y1="10" y2="10"></line></svg>
                    Book an Appointment
                </h4>
                
                <?php if (!is_logged_in()): ?>
                    <div class="alert alert-soft-primary border-0 rounded-3 mb-0" style="background-color: rgba(99, 102, 241, 0.1); color: var(--primary-color);">
                        <div class="d-flex align-items-center">
                            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="me-2"><circle cx="12" cy="12" r="10"></circle><line x1="12" x2="12" y1="16" y2="12"></line><line x1="12" x2="12.01" y1="8" y2="8"></line></svg>
                            <span>You must be <a href="login.php" class="fw-bold text-decoration-none">logged in</a> to book an appointment.</span>
                        </div>
                    </div>
                <?php elseif (!has_role('customer')): ?>
                    <div class="alert alert-soft-warning border-0 rounded-3 mb-0" style="background-color: rgba(255, 193, 7, 0.1); color: #856404;">
                        <div class="d-flex align-items-center">
                            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="me-2"><path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"></path><line x1="12" y1="9" x2="12" y2="13"></line><line x1="12" y1="17" x2="12.01" y2="17"></line></svg>
                            <span>Only <strong>Customers</strong> can book appointments. You are logged in as a <strong><?php echo ucfirst($_SESSION['user']['role']); ?></strong>.</span>
                        </div>
                    </div>
                <?php endif; ?>

                <?php 
                $has_slots = false;
                foreach($available_days as $day_slots) if(!empty($day_slots)) $has_slots = true;
                
                if ($has_slots): ?>
                    <form method="post" action="book_appointment.php" class="mt-2">
                        <input type="hidden" name="lawyer_id" value="<?php echo (int)$lawyer['id']; ?>">
                        <div class="mb-4">
                            <label class="form-label fw-semibold small text-uppercase">Select Available Slot</label>
                            <select name="slot_data" class="form-select p-3 bg-light border-0 rounded-3" required <?php echo (!is_logged_in() || !has_role('customer')) ? 'disabled' : ''; ?>>
                                <option value="">Choose a date and time...</option>
                                <?php foreach ($available_days as $date => $times): ?>
                                    <optgroup label="<?php echo date('D, M j, Y', strtotime($date)); ?>">
                                        <?php foreach ($times as $time): ?>
                                            <option value="<?php echo $date; ?>|<?php echo $time; ?>">
                                                <?php echo date('h:i A', strtotime($time)); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </optgroup>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="mb-4">
                            <label class="form-label fw-semibold small text-uppercase">Case Overview / Notes</label>
                            <textarea name="notes" class="form-control p-3 bg-light border-0 rounded-3" rows="4" placeholder="Briefly describe your legal needs..." <?php echo (!is_logged_in() || !has_role('customer')) ? 'disabled' : ''; ?>></textarea>
                        </div>
                        <button type="submit" class="btn btn-primary btn-lg px-5 shadow-sm" <?php echo (!is_logged_in() || !has_role('customer')) ? 'disabled' : ''; ?>>Reserve Slot Now</button>
                    </form>
                <?php else: ?>
                    <div class="text-center py-4 bg-light rounded-4">
                        <svg xmlns="http://www.w3.org/2000/svg" width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="text-muted mb-3 opacity-25"><rect width="18" height="18" x="3" y="4" rx="2" ry="2"></rect><line x1="16" x2="16" y1="2" y2="6"></line><line x1="8" x2="8" y1="2" y2="6"></line><line x1="3" x2="21" y1="10" y2="10"></line></svg>
                        <p class="text-muted mb-0">No available slots at the moment. Please check back later.</p>
                    </div>
                <?php endif; ?>
        <div class="card border-0 shadow-lg mt-4">
            <div class="card-body p-4">
                <h4 class="fw-bold mb-4 d-flex align-items-center">
                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="me-2 text-primary"><path d="M21 15a2 2 0 0 1-2 2H7l4-4-4-4h12a2 2 0 0 1 2 2z"></path></svg>
                    Client Reviews
                </h4>

                <?php if (is_logged_in() && has_role('customer')): ?>
                    <form method="post" class="mb-5 p-3 bg-light rounded-4">
                        <input type="hidden" name="submit_review" value="1">
                        <label class="form-label fw-bold small text-uppercase mb-2">Leave a Rating</label>
                        <div class="mb-3">
                            <select name="rating" class="form-select border-0 shadow-sm" required>
                                <option value="5">★★★★★ (Excellent)</option>
                                <option value="4">★★★★☆ (Very Good)</option>
                                <option value="3">★★★☆☆ (Good)</option>
                                <option value="2">★★☆☆☆ (Fair)</option>
                                <option value="1">★☆☆☆☆ (Poor)</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <textarea name="comment" class="form-control border-0 shadow-sm" rows="3" placeholder="Share your experience with this lawyer..." required></textarea>
                        </div>
                        <button type="submit" class="btn btn-primary shadow-sm px-4">Post My Review</button>
                    </form>
                <?php endif; ?>

                <div class="reviews-list">
                    <?php if ($reviewsList->num_rows > 0): ?>
                        <?php while($rev = $reviewsList->fetch_assoc()): ?>
                            <div class="review-item mb-4 pb-4 border-bottom last-child-no-border">
                                <div class="d-flex justify-content-between align-items-start mb-2">
                                    <div>
                                        <h6 class="fw-bold mb-0"><?php echo e($rev['customer_name']); ?></h6>
                                        <div class="text-warning small">
                                            <?php for($i=1; $i<=5; $i++) echo $i <= $rev['rating'] ? '★' : '☆'; ?>
                                        </div>
                                    </div>
                                    <span class="text-muted small"><?php echo date('M j, Y', strtotime($rev['created_at'])); ?></span>
                                </div>
                                <p class="text-muted mb-3"><?php echo nl2br(e($rev['comment'])); ?></p>
                                
                                <?php if (!empty($rev['reply'])): ?>
                                    <div class="ms-4 p-3 bg-light rounded-3 border-start border-primary border-4">
                                        <div class="d-flex justify-content-between align-items-center mb-1">
                                            <span class="fw-bold text-primary small">Lawyer's Response</span>
                                            <span class="text-muted small"><?php echo date('M j, Y', strtotime($rev['replied_at'])); ?></span>
                                        </div>
                                        <p class="text-muted small mb-0"><?php echo nl2br(e($rev['reply'])); ?></p>
                                    </div>
                                <?php endif; ?>
                            </div>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <div class="text-center py-4 opacity-50">
                            <p class="mb-0 italic">No reviews yet. Be the first to share your feedback!</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>
<?php
// Render the common footer.
require_once __DIR__ . '/footer.php';
?>
