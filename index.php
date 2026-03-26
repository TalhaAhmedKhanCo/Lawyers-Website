<?php
// Load the shared page header.
require_once __DIR__ . '/header.php';
?>
<div class="hero-section text-white text-center animate-up">
    <div class="container py-5">
        <h1 class="display-3 mb-4">Find the Right Legal Expert <span style="color: var(--primary-color);">Instantly</span></h1>
        <p class="lead mb-5 mx-auto opacity-75" style="max-width: 800px;">Connect with specialized lawyers, book consultations, and manage your legal appointments through our seamless digital portal.</p>
        <div class="d-flex justify-content-center gap-3">
            <a href="search.php" class="btn btn-primary btn-lg px-5">Find a Lawyer</a>
            <a href="services.php" class="btn btn-outline-light btn-lg px-5">Our Services</a>
        </div>
    </div>
</div>

<div class="row g-4 py-5">
    <div class="col-md-4 animate-up" style="animation-delay: 0.1s;">
        <div class="card h-100 p-4">
            <div class="card-body">
                <div class="mb-4 text-primary">
                    <svg xmlns="http://www.w3.org/2000/svg" width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"></path><circle cx="9" cy="7" r="4"></circle><path d="M22 21v-2a4 4 0 0 0-3-3.87"></path><path d="M16 3.13a4 4 0 0 1 0 7.75"></path></svg>
                </div>
                <h4 class="card-title mb-3">For Clients</h4>
                <p class="text-muted">Browse verified legal professionals, read authentic reviews, and book instant appointments without the hassle of phone tags.</p>
            </div>
        </div>
    </div>
    <div class="col-md-4 animate-up" style="animation-delay: 0.2s;">
        <div class="card h-100 p-4 border-primary" style="border: 1px solid rgba(99, 102, 241, 0.2) !important;">
            <div class="card-body">
                <div class="mb-4 text-primary">
                    <svg xmlns="http://www.w3.org/2000/svg" width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14.7 6.3a1 1 0 0 0 0 1.4l1.6 1.6a1 1 0 0 0 1.4 0l3.77-3.77a6 6 0 0 1-7.94 7.94l-6.91 6.91a2.12 2.12 0 0 1-3-3l6.91-6.91a6 6 0 0 1 7.94-7.94l-3.76 3.76z"></path></svg>
                </div>
                <h4 class="card-title mb-3">For Lawyers</h4>
                <p class="text-muted">Grow your practice with automated scheduling, profile management, and a streamlined dashboard for appointment tracking.</p>
            </div>
        </div>
    </div>
    <div class="col-md-4 animate-up" style="animation-delay: 0.3s;">
        <div class="card h-100 p-4">
            <div class="card-body">
                <div class="mb-4 text-primary">
                    <svg xmlns="http://www.w3.org/2000/svg" width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect width="18" height="11" x="3" y="11" rx="2" ry="2"></rect><path d="M7 11V7a5 5 0 0 1 10 0v4"></path></svg>
                </div>
                <h4 class="card-title mb-3">Secure & Private</h4>
                <p class="text-muted">Your data and appointment details are protected with industry-standard encryption, ensuring complete confidentiality.</p>
            </div>
        </div>
    </div>
</div>
<?php
// Load the shared footer.
require_once __DIR__ . '/footer.php';
?>
