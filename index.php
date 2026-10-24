<?php
session_start();

if (isset($_SESSION['user'])) {
    header('Location: dashboard.php');
    exit;
}

$services = [
    'Barangay Clearance',
    'Residency Certificate',
    'Certificate of Indigency',
    'Barangay ID Application',
    'Business Permit Assistance',
    'Certificate of Good Moral Character'
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Barangay Digital Portal</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="portal">
        <div class="logo">
            <img src="barangay-logo.svg" alt="Barangay logo" class="logo-mark" />
            <div class="logo-copy">
                <span class="logo-title">Barangay Connect</span>
                <small>Barangay Services Portal</small>
            </div>
        </div>

        <div class="content">
            <div class="hero">
                <h1>Barangay services, powered for the future.</h1>
                <p>
                    Secure digital access for resident registration, certificate requests, citizen updates,
                    and community services from one unified barangay portal.
                </p>

                <div class="badge-row">
                    <span class="badge">Resident Access</span>
                    <span class="badge">Certificate Request</span>
                    <span class="badge">Community Updates</span>
                </div>

                <div class="actions">
                    <a class="button primary" href="login.php">Sign In</a>
                    <a class="button secondary" href="register.php">Create Account</a>
                </div>
            </div>

            <div class="panel">
                <h3>Services</h3>
                <ul class="service-list">
                    <?php foreach ($services as $service): ?>
                        <li><span><?php echo htmlspecialchars($service); ?></span><span>Live</span></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        </div>
    </div>
</body>
</html>
