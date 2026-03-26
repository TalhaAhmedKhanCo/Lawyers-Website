<?php
require_once __DIR__ . '/header.php';

$message_sent = false;
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $first_name = trim($_POST['first_name'] ?? '');
    $last_name = trim($_POST['last_name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $subject = trim($_POST['subject'] ?? 'General Inquiry');
    $message = trim($_POST['message'] ?? '');

    if (empty($first_name) || empty($last_name) || empty($email) || empty($message)) {
        $error = "Please fill in all required fields.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Invalid email address format.";
    } else {
        // Save to database
        $stmt = $conn->prepare("INSERT INTO contact_submissions (first_name, last_name, email, subject, message) VALUES (?, ?, ?, ?, ?)");
        $stmt->bind_param("sssss", $first_name, $last_name, $email, $subject, $message);
        
        if ($stmt->execute()) {
            $message_sent = true;
            
            // Send email notification (Note: mail() needs a configured SMTP server)
            $to = "admin@legalease.com"; // Change to user's real email if needed
            $email_subject = "New Contact Form Submission: $subject";
            $email_body = "You have received a new message from your website contact form.\n\n".
                          "Name: $first_name $last_name\n".
                          "Email: $email\n".
                          "Subject: $subject\n".
                          "Message:\n$message";
            $headers = "From: webmaster@legalease.com\r\n";
            $headers .= "Reply-To: $email\r\n";
            
            @mail($to, $email_subject, $email_body, $headers);
        } else {
            $error = "Something went wrong. Please try again later.";
        }
    }
}
?>

<div class="row g-5 py-5">
    <div class="col-lg-5 animate-up">
        <h6 class="text-primary fw-bold text-uppercase mb-3">Contact Us</h6>
        <h1 class="display-4 mb-4">We're Here to Help You Navigate the Law</h1>
        <p class="text-muted mb-5 lead">Have questions about our platform or need assistance with your appointments? Reach out to our dedicated support team through the form or our contact details.</p>
        
        <div class="d-flex mb-4 p-4 bg-white rounded-3 shadow-sm">
            <div class="text-primary me-4">
                <svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect width="20" height="16" x="2" y="4" rx="2"></rect><path d="m22 7-8.97 5.7a1.94 1.94 0 0 1-2.06 0L2 7"></path></svg>
            </div>
            <div>
                <h5 class="mb-1">Email Us</h5>
                <p class="text-muted mb-0">support@legalease.com</p>
            </div>
        </div>

        <div class="d-flex mb-4 p-4 bg-white rounded-3 shadow-sm">
            <div class="text-primary me-4">
                <svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z"></path></svg>
            </div>
            <div>
                <h5 class="mb-1">Call Support</h5>
                <p class="text-muted mb-0">+1 (800) LEGAL-HELP</p>
            </div>
        </div>
    </div>
    
    <div class="col-lg-7 animate-up" style="animation-delay: 0.2s;">
        <div class="card p-5 border-0 shadow-lg">
            <h3 class="mb-4">Send a Message</h3>

            <?php if ($message_sent): ?>
                <div class="alert alert-success border-0 rounded-3 mb-4">
                    <h5 class="alert-heading">Message Sent!</h5>
                    <p class="mb-0">Thank you for reaching out. We have received your message and will get back to you shortly.</p>
                </div>
            <?php endif; ?>

            <?php if ($error): ?>
                <div class="alert alert-danger border-0 rounded-3 mb-4">
                    <?php echo e($error); ?>
                </div>
            <?php endif; ?>

            <form action="contact.php" method="POST">
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label">First Name</label>
                        <input type="text" name="first_name" class="form-control p-3 bg-light border-0 rounded-3" placeholder="John" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Last Name</label>
                        <input type="text" name="last_name" class="form-control p-3 bg-light border-0 rounded-3" placeholder="Doe" required>
                    </div>
                    <div class="col-12">
                        <label class="form-label">Email Address</label>
                        <input type="email" name="email" class="form-control p-3 bg-light border-0 rounded-3" placeholder="name@example.com" required>
                    </div>
                    <div class="col-12">
                        <label class="form-label">Subject</label>
                        <select name="subject" class="form-select p-3 bg-light border-0 rounded-3">
                            <option value="General Inquiry">General Inquiry</option>
                            <option value="Account Support">Account Support</option>
                            <option value="Legal Consultation">Legal Consultation</option>
                        </select>
                    </div>
                    <div class="col-12">
                        <label class="form-label">Message</label>
                        <textarea name="message" class="form-control p-3 bg-light border-0 rounded-3" rows="5" placeholder="Tell us how we can help..." required></textarea>
                    </div>
                    <div class="col-12">
                        <button type="submit" class="btn btn-primary btn-lg px-5 mt-2">Submit Request</button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>

<?php
require_once __DIR__ . '/footer.php';
?>
