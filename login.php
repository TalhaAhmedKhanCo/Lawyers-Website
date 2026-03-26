<?php
// Load dependencies for authentication and layout.
require_once __DIR__ . '/header.php';

// Prepare variables to show feedback to the user.
$error = '';

// Process login when the form is submitted.
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    // Load the user by email using a prepared statement.
    $stmt = $conn->prepare('SELECT id, name, email, password, role FROM users WHERE email = ?');
    $stmt->bind_param('s', $email);
    $stmt->execute();
    $user = $stmt->get_result()->fetch_assoc();

    // Verify the password hash and create the session on success.
    if ($user && password_verify($password, $user['password'])) {
        $_SESSION['user'] = [
            'id' => $user['id'],
            'name' => $user['name'],
            'email' => $user['email'],
            'role' => $user['role']
        ];

        // Redirect the user to the correct dashboard based on role.
        if ($user['role'] === 'admin') {
            header('Location: admin_dashboard.php');
        } elseif ($user['role'] === 'lawyer') {
            header('Location: lawyer_dashboard.php');
        } else {
            header('Location: customer_dashboard.php');
        }
        exit;
    } else {
        $error = 'Invalid email or password.';
    }
}
?>
<div class="row justify-content-center py-5 animate-up">
    <div class="col-lg-5 col-md-8">
        <div class="card p-4 border-0 shadow-lg">
            <div class="card-body">
                <div class="text-center mb-5">
                    <h2 class="fw-bold">Welcome Back</h2>
                    <p class="text-muted">Enter your credentials to access your LegalEase account.</p>
                </div>
                <!-- Display a validation message if login fails. -->
                <?php if ($error): ?><div class="alert alert-danger px-3 py-2 small border-0 rounded-3"><?php echo e($error); ?></div><?php endif; ?>
                <form method="post">
                    <div class="mb-4">
                        <label class="form-label fw-semibold small text-uppercase">Email Address</label>
                        <input type="email" name="email" class="form-control p-3 bg-light border-0 rounded-3" placeholder="name@example.com" required>
                    </div>
                    <div class="mb-4">
                        <div class="d-flex justify-content-between">
                            <label class="form-label fw-semibold small text-uppercase">Password</label>
                            <a href="#" class="small text-decoration-none">Forgot Password?</a>
                        </div>
                        <input type="password" name="password" class="form-control p-3 bg-light border-0 rounded-3" placeholder="••••••••" required>
                    </div>
                    <button type="submit" class="btn btn-primary w-100 py-3 mt-2 shadow-sm">Sign In</button>
                    <div class="text-center mt-4">
                        <p class="text-muted small">Don't have an account? <a href="register.php" class="fw-bold text-decoration-none text-primary">Create one here</a></p>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
<?php
// Render the common footer.
require_once __DIR__ . '/footer.php';
?>
