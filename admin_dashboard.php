<?php
// Load authentication before output so redirects work correctly.
require_once __DIR__ . '/auth.php';

// Restrict access to the administrator only.
require_role('admin');
require_once __DIR__ . '/header.php';

// Query users for overview reporting.
$users = $conn->query('SELECT id, name, email, role, created_at FROM users ORDER BY created_at DESC');

// Query lawyer profiles to help the admin review listings.
$lawyers = $conn->query("SELECT u.id, u.name, u.email, lp.specialization, lp.location, lp.meeting_place
                         FROM users u
                         INNER JOIN lawyer_profiles lp ON lp.user_id = u.id
                         WHERE u.role = 'lawyer'
                         ORDER BY u.name ASC");

// Query all appointments for centralized management visibility.
$appointments = $conn->query("SELECT a.id, a.status, s.slot_date, s.slot_time, c.name AS customer_name, l.name AS lawyer_name
                              FROM appointments a
                              INNER JOIN appointment_slots s ON s.id = a.slot_id
                              INNER JOIN users c ON c.id = a.customer_id
                              INNER JOIN users l ON l.id = a.lawyer_id
                              ORDER BY s.slot_date DESC, s.slot_time DESC");

// Handle review deletion by Admin
if (isset($_POST['delete_review'])) {
    $reviewId = (int)$_POST['review_id'];
    $stmt = $conn->prepare("DELETE FROM reviews WHERE id = ?");
    $stmt->bind_param('i', $reviewId);
    $stmt->execute();
    header("Location: admin_dashboard.php?msg=Review deleted.");
    exit;
}

// Query all reviews for platform moderation
$all_reviews = $conn->query("SELECT r.*, c.name AS customer_name, l.name AS lawyer_name 
                             FROM reviews r
                             INNER JOIN users c ON c.id = r.customer_id
                             INNER JOIN users l ON l.id = r.lawyer_id
                             ORDER BY r.created_at DESC");
?>
<h2 class="mb-4">Admin Dashboard</h2>

<div class="row g-4">
    <div class="col-12">
        <div class="card shadow-sm">
            <div class="card-body">
                <!-- Table for all registered users. -->
                <h4 class="mb-3">All Users</h4>
                <div class="table-responsive">
                    <table class="table table-striped align-middle">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Name</th>
                                <th>Email</th>
                                <th>Role</th>
                                <th>Created</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($user = $users->fetch_assoc()): ?>
                                <tr>
                                    <td><?php echo (int)$user['id']; ?></td>
                                    <td><?php echo e($user['name']); ?></td>
                                    <td><?php echo e($user['email']); ?></td>
                                    <td><?php echo e($user['role']); ?></td>
                                    <td><?php echo e($user['created_at']); ?></td>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <div class="col-12">
        <div class="card shadow-sm">
            <div class="card-body">
                <!-- Table for lawyer profile records. -->
                <h4 class="mb-3">Lawyer Listings</h4>
                <div class="table-responsive">
                    <table class="table table-striped align-middle">
                        <thead>
                            <tr>
                                <th>Name</th>
                                <th>Email</th>
                                <th>Specialization</th>
                                <th>Location</th>
                                <th>Meeting Place</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($lawyer = $lawyers->fetch_assoc()): ?>
                                <tr>
                                    <td><?php echo e($lawyer['name']); ?></td>
                                    <td><?php echo e($lawyer['email']); ?></td>
                                    <td><?php echo e($lawyer['specialization']); ?></td>
                                    <td><?php echo e($lawyer['location']); ?></td>
                                    <td><?php echo e($lawyer['meeting_place']); ?></td>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <div class="col-12">
        <div class="card shadow-sm">
            <div class="card-body">
                <!-- Table for all platform appointments. -->
                <h4 class="mb-3">Appointments</h4>
                <div class="table-responsive">
                    <table class="table table-striped align-middle">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Customer</th>
                                <th>Lawyer</th>
                                <th>Date</th>
                                <th>Time</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($appointment = $appointments->fetch_assoc()): ?>
                                <tr>
                                    <td><?php echo (int)$appointment['id']; ?></td>
                                    <td><?php echo e($appointment['customer_name']); ?></td>
                                    <td><?php echo e($appointment['lawyer_name']); ?></td>
                                    <td><?php echo e($appointment['slot_date']); ?></td>
                                    <td><?php echo e(substr($appointment['slot_time'], 0, 5)); ?></td>
                                    <td><?php echo e($appointment['status']); ?></td>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
    <div class="col-12">
        <div class="card shadow-sm border-0">
            <div class="card-body p-4">
                <h4 class="mb-4 d-flex align-items-center">
                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="me-2 text-primary"><path d="M21 15a2 2 0 0 1-2 2H7l4-4-4-4h12a2 2 0 0 1 2 2z"></path></svg>
                    Platform Reviews
                </h4>
                <div class="table-responsive">
                    <table class="table table-hover align-middle">
                        <thead class="bg-light">
                            <tr>
                                <th>Lawyer</th>
                                <th>Customer</th>
                                <th>Rating</th>
                                <th>Comment</th>
                                <th>Date</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($rev = $all_reviews->fetch_assoc()): ?>
                                <tr>
                                    <td><strong><?php echo e($rev['lawyer_name']); ?></strong></td>
                                    <td><?php echo e($rev['customer_name']); ?></td>
                                    <td>
                                        <div class="text-warning small">
                                            <?php for($i=1; $i<=5; $i++) echo $i <= $rev['rating'] ? '★' : '☆'; ?>
                                        </div>
                                    </td>
                                    <td style="max-width: 300px;"><small class="text-muted"><?php echo e($rev['comment']); ?></small></td>
                                    <td><?php echo date('M j, Y', strtotime($rev['created_at'])); ?></td>
                                    <td>
                                        <form method="post" onsubmit="return confirm('Delete this review permanently?')">
                                            <input type="hidden" name="delete_review" value="1">
                                            <input type="hidden" name="review_id" value="<?php echo (int)$rev['id']; ?>">
                                            <button class="btn btn-sm btn-outline-danger">Delete</button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                            <?php if ($all_reviews->num_rows === 0): ?>
                                <tr>
                                    <td colspan="6" class="text-center py-4 text-muted small italic">No reviews found on the platform.</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
<?php
// Render the common footer.
require_once __DIR__ . '/footer.php';
?>
