<?php
require_once 'includes/config.php';
require_once 'includes/auth.php';

// Redirect if already logged in
if (isLoggedIn()) {
    if (isAdmin()) {
        redirect(ADMIN_URL);
    } else {
        redirect(BASE_URL . 'dashboard.php');
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login | Auth System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="assets/css/style.css" rel="stylesheet">
</head>
<body>
    <!-- Header -->
    <header class="lto-bg-primary text-white py-3">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-md-6">
                    <h4><i class="bi bi-building"></i> TEST PROJECT | INVENTORY AND TICKETING SYSTEM</h4>
                </div>
            </div>
        </div>
    </header>

    <!-- Main Content -->
    <main class="container my-5">
        <div class="row justify-content-center">
            <div class="col-lg-10">
                            <?php if (isset($_SESSION['error'])): ?>
            <div class="alert alert-danger"><?= $_SESSION['error']; unset($_SESSION['error']); ?></div>
        <?php endif; ?>
                        
        <?php if (isset($_SESSION['success'])): ?>
            <div class="alert alert-success"><?= $_SESSION['success']; unset($_SESSION['success']); ?></div>
        <?php endif; ?>
                <div class="card portal-shadow border-0">
                    <!-- Card Header -->
                    <div class="card-header lto-bg-primary text-white py-3">
                        <h5 class="card-title mb-0"><i class="bi bi-person-circle me-2"></i>USER LOGIN</h5>
                    </div>
                    
                    <!-- Card Body -->
                    <div class="card-body p-0">
                        <div class="row g-0">
                            <!-- Login Section -->
                            <div class="col-md-6 p-4">
                                <ul class="nav nav-tabs mb-4 border-0">
                                    <li class="nav-item">
                                        <button class="nav-link active" data-bs-toggle="tab" data-bs-target="#client-tab">Client Login</button>
                                    </li>
                                </ul>

                                <form id="loginForm" method="POST">
                                    <div class="mb-3">
                                        <label for="username" class="form-label">Username</label>
                                        <div class="input-group">
                                            <span class="input-group-text"><i class="bi bi-person-fill"></i></span>
                                            <input type="text" class="form-control" id="username" name="username" required>
                                        </div>
                                    </div>
                                    <div class="mb-5">
                                        <label for="password" class="form-label">Password</label>
                                        <div class="input-group">
                                            <span class="input-group-text"><i class="bi bi-lock-fill"></i></span>
                                            <input type="password" class="form-control" id="password" name="password" required>
                                        </div>
                                        
                                        <!-- Add this after the password field in the login form -->
                                    </div>

                                        <?php if (isset($_SESSION['show_captcha'])): ?>
                                            <div class="mb-3">
                                                <label for="captcha" class="form-label">CAPTCHA</label>
                                                <div class="d-flex align-items-center gap-3">
                                                    <input type="text" class="form-control" id="captcha" name="captcha" required>
                                                    <img src="includes/captcha.php" alt="CAPTCHA" class="captcha-image" style="cursor: pointer;" onclick="this.src='includes/captcha.php?'+Math.random()">
                                                </div>
                                                <small class="text-muted">Click on the image to refresh</small>
                                            </div>
                                            <?php unset($_SESSION['show_captcha']); ?>
                                        <?php endif; ?>
                                        
                                    <div class="d-grid gap-2">
                                        <button type="submit" name="login" class="btn btn-primary">Login</button>
                                    </div>
                                </form>
                            </div>
                            
                            <!-- Ticket Section -->
                            <div class="col-md-6 bg-light p-4">
                                <div class="h-100 d-flex flex-column">
                                    <h5 class="mb-4"><i class="bi bi-ticket-perforated lto-text-primary me-2"></i>SUBMIT A TICKET</h5>
                                    <p class="mb-3">Need assistance? Submit a support ticket for:</p>
                                    <ul class="mb-4">
                                        <li class="mb-2">Laptop conerns</li>
                                        <li class="mb-2">Issuance of periperals</li>
                                        <li class="mb-2">Internet connectivity</li>
                                        <li>Software / Application concerns</li>
                                    </ul>
                                    
                                    <div class="d-grid mt-auto">
                                        <button class="btn lto-bg-secondary text-white py-2">SUBMIT TICKET</button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </main>


    <!-- Loading Modal -->
    <div class="modal fade" id="loadingModal" tabindex="-1" aria-hidden="true" data-bs-backdrop="static">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content bg-transparent border-0">
                <div class="modal-body text-center">
                    <div class="spinner-border text-primary" role="status" style="width: 3rem; height: 3rem;">
                        <span class="visually-hidden">Loading...</span>
                    </div>
                    <p class="text-white mt-2">Authenticating...</p>
                </div>
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="assets/js/script.js"></script>
</body>
</html>