<?php
session_start();

if (!isset($_SESSION['user'])) {
    header('Location: login.php');
    exit;
}

if (strtolower($_SESSION['role'] ?? 'resident') === 'admin') {
    header('Location: admin_dashboard.php');
    exit;
}

require 'db.php';

$services = [
    'Barangay Clearance',
    'Residency Certificate',
    'Certificate of Indigency',
    'Barangay ID Application',
    'Business Permit Assistance',
    'Certificate of Good Moral Character'
];

$username = $_SESSION['user'];
$error = '';
$success = $_SESSION['request_success'] ?? '';
unset($_SESSION['request_success']);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $requestType = trim($_POST['request_type'] ?? '');
    $fullName = trim($_POST['full_name'] ?? '');
    $address = trim($_POST['address'] ?? '');
    $contactNumber = trim($_POST['contact_number'] ?? '');
    $purpose = trim($_POST['purpose'] ?? '');

    if ($requestType === '') {
        $error = 'Please select the certificate or service you want to apply for.';
    } elseif ($fullName === '' || $address === '' || $contactNumber === '') {
        $error = 'Please provide your full name, address, and contact number.';
    } else {
        try {
            $pdo = getDb();
            $stmt = $pdo->prepare('INSERT INTO requests (user_id, request_type, full_name, address, contact_number, purpose, status) VALUES (?, ?, ?, ?, ?, ?, ?)');
            $stmt->execute([
                $_SESSION['user_id'],
                $requestType,
                $fullName,
                $address,
                $contactNumber,
                $purpose !== '' ? $purpose : null,
                'pending'
            ]);

            $_SESSION['request_success'] = 'Your application for ' . $requestType . ' has been submitted successfully.';
            header('Location: resident_dashboard.php');
            exit;
        } catch (Exception $e) {
            $error = 'Something went wrong while submitting your request. Please try again.';
        }
    }
}

try {
    $pdo = getDb();
    $stmt = $pdo->prepare('SELECT request_type, full_name, address, contact_number, purpose, status, created_at FROM requests WHERE user_id = ? ORDER BY created_at DESC LIMIT 10');
    $stmt->execute([$_SESSION['user_id']]);
    $requests = $stmt->fetchAll();
} catch (Exception $e) {
    $requests = [];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Barangay Resident Dashboard</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="dashboard">
        <div class="topbar">
            <div class="brand"><span class="brand-dot"></span> Barangay Resident Portal</div>
            <a class="logout" href="logout.php">Logout</a>
        </div>

        <div class="hero">
            <h1>Welcome, <?php echo htmlspecialchars($username); ?>!</h1>
            <p>
                Choose a barangay service and fill out the resident request details for processing.
            </p>
        </div>

        <div class="grid">
            <div class="card">
                <h3>Certificates</h3>
                <strong><?php echo count($requests); ?></strong>
                <span>Submitted</span>
            </div>

            <div class="card">
                <h3>Services</h3>
                <strong><?php echo count($services); ?></strong>
                <span>Available</span>
            </div>

            <div class="card">
                <h3>Status</h3>
                <strong>Resident</strong>
                <span>Access Ready</span>
            </div>
        </div>

        <div class="request-box">
            <div class="request-header">
                <h2>Apply for a Barangay Service</h2>
            </div>

            <?php if ($success): ?>
                <div class="notice success"><?php echo htmlspecialchars($success); ?></div>
            <?php endif; ?>

            <?php if ($error): ?>
                <div class="notice error"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>

            <div class="service-grid">
                <?php foreach ($services as $service): ?>
                    <button type="button" class="service-option" data-service="<?php echo htmlspecialchars($service, ENT_QUOTES); ?>">
                        <span><?php echo htmlspecialchars($service); ?></span>
                        <small>Apply now</small>
                    </button>
                <?php endforeach; ?>
            </div>
        </div>

        <div class="request-box">
            <div class="request-header">
                <h2>My Applications</h2>
            </div>

            <?php if (empty($requests)): ?>
                <p class="empty-state">No applications yet. Select a service above to begin.</p>
            <?php else: ?>
                <table class="request-table">
                    <thead>
                        <tr>
                            <th>Request</th>
                            <th>Applicant</th>
                            <th>Address</th>
                            <th>Contact</th>
                            <th>Purpose</th>
                            <th>Status</th>
                            <th>Date</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($requests as $request): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($request['request_type']); ?></td>
                                <td><?php echo htmlspecialchars($request['full_name'] ?? '—'); ?></td>
                                <td><?php echo htmlspecialchars($request['address'] ?? '—'); ?></td>
                                <td><?php echo htmlspecialchars($request['contact_number'] ?? '—'); ?></td>
                                <td><?php echo htmlspecialchars($request['purpose'] ?? '—'); ?></td>
                                <td><span class="status-badge status-<?php echo strtolower(htmlspecialchars($request['status'])); ?>"><?php echo htmlspecialchars($request['status']); ?></span></td>
                                <td><?php echo htmlspecialchars(date('M d, Y', strtotime($request['created_at']))); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
    </div>

    <div class="modal" id="requestModal" aria-hidden="true">
        <div class="modal-backdrop" data-close="modal"></div>
        <div class="modal-content">
            <div class="modal-header">
                <h3 id="modalTitle">Barangay Service Request</h3>
                <button type="button" class="close-btn" aria-label="Close form">×</button>
            </div>

            <form method="POST" class="request-form modal-form">
                <input type="hidden" name="request_type" id="request_type" value="">

                <label for="full_name">Full name</label>
                <input id="full_name" name="full_name" type="text" placeholder="Enter your full name" required>

                <label for="address">Complete address</label>
                <textarea id="address" name="address" rows="3" placeholder="House number, street, barangay, city" required></textarea>

                <label for="contact_number">Contact number</label>
                <input id="contact_number" name="contact_number" type="text" placeholder="09xx-xxx-xxxx" required>

                <label for="purpose">Purpose or notes</label>
                <textarea id="purpose" name="purpose" rows="4" placeholder="Tell us why you are applying for this service..."></textarea>

                <button type="submit">Submit Application</button>
            </form>
        </div>
    </div>

    <script>
        const requestModal = document.getElementById('requestModal');
        const modalTitle = document.getElementById('modalTitle');
        const requestTypeInput = document.getElementById('request_type');

        document.querySelectorAll('.service-option').forEach((button) => {
            button.addEventListener('click', () => {
                const service = button.dataset.service;
                modalTitle.textContent = service;
                requestTypeInput.value = service;
                requestModal.classList.add('show');
                requestModal.setAttribute('aria-hidden', 'false');
            });
        });

        document.querySelectorAll('[data-close="modal"]').forEach((element) => {
            element.addEventListener('click', () => {
                requestModal.classList.remove('show');
                requestModal.setAttribute('aria-hidden', 'true');
            });
        });

        const closeButton = document.querySelector('.close-btn');
        if (closeButton) {
            closeButton.addEventListener('click', () => {
                requestModal.classList.remove('show');
                requestModal.setAttribute('aria-hidden', 'true');
            });
        }

        document.addEventListener('keydown', (event) => {
            if (event.key === 'Escape') {
                requestModal.classList.remove('show');
                requestModal.setAttribute('aria-hidden', 'true');
            }
        });
    </script>
</body>
</html>
