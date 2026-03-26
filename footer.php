</main>
<?php
// Handle newsletter subscription
$newsletter_status = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['subscribe_newsletter'])) {
    $email = trim($_POST['newsletter_email'] ?? '');
    if (filter_var($email, FILTER_VALIDATE_EMAIL)) {
        // Use IGNORE to avoid errors on duplicate emails
        $stmt = $conn->prepare("INSERT IGNORE INTO newsletter_subscribers (email) VALUES (?)");
        $stmt->bind_param("s", $email);
        if ($stmt->execute()) {
            $newsletter_status = 'success';
            
            // Send email notification to Admin
            $to = "admin@legalease.com";
            $subject = "New Newsletter Subscriber";
            $message = "You have a new subscriber: $email";
            $headers = "From: webmaster@legalease.com";
            @mail($to, $subject, $message, $headers);
            
            // Send confirmation to Subscriber
            $subject_user = "Welcome to LegalEase Newsletter";
            $message_user = "Thank you for subscribing to LegalEase. You will now receive our latest updates and legal insights!";
            @mail($email, $subject_user, $message_user, $headers);
        }
    } else {
        $newsletter_status = 'error';
    }
}
?>
</main>
    <footer class="footer mt-auto py-5 bg-white border-top shadow-sm animate-up">
        <div class="container">
            <div class="row g-5">
                <div class="col-lg-4 mb-4 mb-lg-0">
                    <a class="navbar-brand d-flex align-items-center mb-4 text-primary fs-3" href="index.php">
                        <svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="me-2"><path d="m3 9 9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"></path><polyline points="9 22 9 12 15 12 15 22"></polyline></svg>
                        LegalEase
                    </a>
                    <p class="text-muted pe-lg-5 mb-4">Empowering individuals with direct access to specialized legal professionals, simplified scheduling, and transparent consultations.</p>
                    <div class="d-flex gap-3">
                        <a href="#" class="text-primary"><svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M18 2h-3a5 5 0 0 0-5 5v3H7v4h3v8h4v-8h3l1-4h-4V7a1 1 0 0 1 1-1h3z"></path></svg></a>
                        <a href="#" class="text-primary"><svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect width="20" height="20" x="2" y="2" rx="5" ry="5"></rect><path d="M16 11.37A4 4 0 1 1 12.63 8 4 4 0 0 1 16 11.37z"></path><line x1="17.5" x2="17.51" y1="6.5" y2="6.5"></line></svg></a>
                        <a href="#" class="text-primary"><svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 4s-.7 2.1-2 3.4c1.6 10-9.4 17.3-18 11.6 2.2.1 4.4-.6 6-2C3 15.5.5 9.6 3 5c2.2 2.6 5.6 4.1 9 4-.9-4.2 4-6.6 7-3.8 1.1 0 3-1.2 3-1.2z"></path></svg></a>
                    </div>
                </div>
                <div class="col-6 col-lg-2">
                    <h5 class="mb-4">Quick Links</h5>
                    <ul class="list-unstyled">
                        <li class="mb-2"><a href="index.php" class="text-muted text-decoration-none hover-primary">Home</a></li>
                        <li class="mb-2"><a href="services.php" class="text-muted text-decoration-none hover-primary">Services</a></li>
                        <li class="mb-2"><a href="search.php" class="text-muted text-decoration-none hover-primary">Find Lawyers</a></li>
                        <li class="mb-2"><a href="about.php" class="text-muted text-decoration-none hover-primary">About Us</a></li>
                    </ul>
                </div>
                <div class="col-6 col-lg-2">
                    <h5 class="mb-4">Resources</h5>
                    <ul class="list-unstyled">
                        <li class="mb-2"><a href="#" class="text-muted text-decoration-none hover-primary">FAQ</a></li>
                        <li class="mb-2"><a href="#" class="text-muted text-decoration-none hover-primary">Privacy Policy</a></li>
                        <li class="mb-2"><a href="#" class="text-muted text-decoration-none hover-primary">Terms of Use</a></li>
                        <li class="mb-2"><a href="contact.php" class="text-muted text-decoration-none hover-primary">Support</a></li>
                    </ul>
                </div>
                <div class="col-lg-4">
                    <h5 class="mb-4">Subscribe to Newsletter</h5>
                    <p class="text-muted mb-4">Stay informed about legal updates and new platform features.</p>
                    <form method="POST" action="">
                        <div class="input-group mb-3">
                            <input type="email" name="newsletter_email" class="form-control bg-light border-0 p-3" placeholder="Enter your email" required>
                            <button type="submit" name="subscribe_newsletter" class="btn btn-primary px-4">Join</button>
                        </div>
                    </form>
                    <?php if ($newsletter_status === 'success'): ?>
                        <p class="text-success small mb-0 animate-up">✓ Subscription successful! Check your email.</p>
                    <?php elseif ($newsletter_status === 'error'): ?>
                        <p class="text-danger small mb-0">⚠ Please enter a valid email address.</p>
                    <?php endif; ?>
                </div>
            </div>
            <div class="border-top mt-5 pt-4 text-center">
                <p class="text-muted mb-0 small">&copy; <?= date('Y') ?> LegalEase Appointment Portal. All rights reserved.</p>
            </div>
        </div>
    </footer>
    <!-- Load Bootstrap JavaScript bundle for UI components. -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <!-- Toggle role-specific fields on the registration page. -->
    <script>
        // This helper shows extra lawyer fields only when the lawyer role is selected.
        function toggleLawyerFields() {
            const roleElement = document.getElementById('role');
            const sectionElement = document.getElementById('lawyer-fields');

            if (!roleElement || !sectionElement) {
                return;
            }

            sectionElement.style.display = roleElement.value === 'lawyer' ? 'block' : 'none';
        }

        // Attach the event listener after the page has loaded.
        document.addEventListener('DOMContentLoaded', function () {
            const roleElement = document.getElementById('role');
            if (roleElement) {
                roleElement.addEventListener('change', toggleLawyerFields);
                toggleLawyerFields();
            }
        });
    </script>
</body>
</html>
