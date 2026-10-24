<?php
session_start();

if (!isset($_SESSION['user'])) {
    header('Location: login.php');
    exit;
}

if (strtolower($_SESSION['role'] ?? 'resident') !== 'admin') {
    header('Location: resident_dashboard.php');
    exit;
}

require 'db.php';

$username = $_SESSION['user'];
$notice = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action']) && $_POST['action'] === 'update_request') {
        $requestId = (int)($_POST['request_id'] ?? 0);
        $requestType = trim($_POST['request_type'] ?? '');
        $fullName = trim($_POST['full_name'] ?? '');
        $address = trim($_POST['address'] ?? '');
        $contactNumber = trim($_POST['contact_number'] ?? '');
        $purpose = trim($_POST['purpose'] ?? '');
        $status = trim($_POST['status'] ?? 'pending');

        if ($requestId <= 0 || $requestType === '') {
            $error = 'Please provide a valid request and service type.';
        } else {
            try {
                $pdo = getDb();
                $stmt = $pdo->prepare('UPDATE requests SET request_type = ?, full_name = ?, address = ?, contact_number = ?, purpose = ?, status = ? WHERE id = ?');
                $stmt->execute([
                    $requestType,
                    $fullName,
                    $address,
                    $contactNumber,
                    $purpose,
                    in_array($status, ['pending', 'approved', 'rejected', 'completed'], true) ? $status : 'pending',
                    $requestId
                ]);
                $notice = 'Request updated successfully.';
            } catch (Exception $e) {
                $error = 'Unable to update request.';
            }
        }
    }

    if (isset($_POST['action']) && $_POST['action'] === 'delete_request') {
        $requestId = (int)($_POST['request_id'] ?? 0);

        if ($requestId <= 0) {
            $error = 'Invalid request selected.';
        } else {
            try {
                $pdo = getDb();
                $stmt = $pdo->prepare('DELETE FROM requests WHERE id = ?');
                $stmt->execute([$requestId]);
                $notice = 'Completed request was deleted successfully.';
            } catch (Exception $e) {
                $error = 'Unable to delete request.';
            }
        }
    }
}

try {
    $pdo = getDb();
    $stmt = $pdo->query('SELECT r.id, r.request_type, r.full_name, r.address, r.contact_number, r.purpose, r.status, r.created_at, u.username, u.email
        FROM requests r
        INNER JOIN users u ON u.id = r.user_id
        ORDER BY r.created_at DESC');
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
    <title>Barangay Admin Dashboard</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="dashboard">
        <div class="topbar">
            <div class="brand"><span class="brand-dot"></span> Barangay Admin Console</div>
            <a class="logout" href="logout.php">Logout</a>
        </div>

        <div class="hero">
            <h1>Welcome, Admin <?php echo htmlspecialchars($username); ?>!</h1>
            <p>
                You have administrative access to barangay records, certificate requests, and resident services.
            </p>
        </div>

        <div class="grid">
            <div class="card">
                <h3>Resident Requests</h3>
                <strong><?php echo count($requests); ?></strong>
                <span>Total Requests</span>
            </div>

            <div class="card">
                <h3>Approved Certificates</h3>
                <strong><?php echo count(array_filter($requests, fn($row) => strtolower($row['status']) === 'approved' || strtolower($row['status']) === 'completed')); ?></strong>
                <span>Verified</span>
            </div>

            <div class="card">
                <h3>System Status</h3>
                <strong>Admin Mode</strong>
                <span>Active</span>
            </div>
        </div>

        <div class="request-box">
            <div class="request-header">
                <h2>Request Management</h2>
            </div>

            <?php if ($notice): ?>
                <div class="notice success"><?php echo htmlspecialchars($notice); ?></div>
            <?php endif; ?>

            <?php if ($error): ?>
                <div class="notice error"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>

            <?php if (empty($requests)): ?>
                <p class="empty-state">No resident requests yet.</p>
            <?php else: ?>
                <div class="admin-table-wrap">
                    <table class="request-table admin-table">
                        <thead>
                            <tr>
                                <th>Resident</th>
                                <th>Applicant Info</th>
                                <th>Request</th>
                                <th>Purpose</th>
                                <th>Status</th>
                                <th>Date</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($requests as $request): ?>
                                <tr>
                                    <td>
                                        <strong><?php echo htmlspecialchars($request['username']); ?></strong><br>
                                        <small><?php echo htmlspecialchars($request['email']); ?></small>
                                    </td>
                                    <td>
                                        <strong><?php echo htmlspecialchars($request['full_name'] ?? 'N/A'); ?></strong><br>
                                        <small><?php echo htmlspecialchars($request['address'] ?? 'N/A'); ?></small><br>
                                        <small><?php echo htmlspecialchars($request['contact_number'] ?? 'N/A'); ?></small>
                                    </td>
                                    <td>
                                        <form method="POST" class="inline-form">
                                            <input type="hidden" name="action" value="update_request">
                                            <input type="hidden" name="request_id" value="<?php echo (int)$request['id']; ?>">
                                            <input type="text" name="request_type" value="<?php echo htmlspecialchars($request['request_type']); ?>" required>
                                            <input type="text" name="full_name" value="<?php echo htmlspecialchars($request['full_name'] ?? ''); ?>" placeholder="Full name" required>
                                            <textarea name="address" rows="2" placeholder="Address"><?php echo htmlspecialchars($request['address'] ?? ''); ?></textarea>
                                            <input type="text" name="contact_number" value="<?php echo htmlspecialchars($request['contact_number'] ?? ''); ?>" placeholder="Contact number" required>
                                    </td>
                                    <td>
                                            <textarea name="purpose" rows="3"><?php echo htmlspecialchars($request['purpose'] ?? ''); ?></textarea>
                                    </td>
                                    <td>
                                            <select name="status">
                                                <option value="pending" <?php echo strtolower($request['status']) === 'pending' ? 'selected' : ''; ?>>Pending</option>
                                                <option value="approved" <?php echo strtolower($request['status']) === 'approved' ? 'selected' : ''; ?>>Approved</option>
                                                <option value="rejected" <?php echo strtolower($request['status']) === 'rejected' ? 'selected' : ''; ?>>Rejected</option>
                                                <option value="completed" <?php echo strtolower($request['status']) === 'completed' ? 'selected' : ''; ?>>Completed</option>
                                            </select>
                                    </td>
                                    <td><?php echo htmlspecialchars(date('M d, Y', strtotime($request['created_at']))); ?></td>
                                    <td class="action-cell">
                                            <button type="submit" class="table-action update">Save</button>
                                        </form>
                                        <form method="POST" class="inline-form delete-form" onsubmit="return confirm('Delete this request after completion?');">
                                            <input type="hidden" name="action" value="delete_request">
                                            <input type="hidden" name="request_id" value="<?php echo (int)$request['id']; ?>">
                                            <button type="submit" class="table-action delete">Delete</button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>
