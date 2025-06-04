<?php
session_start();
require_once 'config/database.php';

// Check if user is logged in and is a recipient
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'recipient') {
    header('Location: login.html');
    exit;
}

try {
    $pdo = getConnection();
    
    // Get recipient information
    $stmt = $pdo->prepare("
        SELECT r.*, u.email, u.username
        FROM recipients r
        JOIN users u ON r.user_id = u.user_id
        WHERE r.user_id = ?
    ");
    $stmt->execute([$_SESSION['user_id']]);
    $recipient = $stmt->fetch(PDO::FETCH_ASSOC);

    // Get active blood requests
    $stmt = $pdo->prepare("
        SELECT 
            br.request_id,
            br.blood_group,
            br.units_required,
            br.component_type,
            br.required_date,
            br.status,
            br.priority,
            h.name as hospital_name
        FROM blood_requests br
        JOIN hospitals h ON br.hospital_id = h.hospital_id
        WHERE br.recipient_id = ?
        AND br.status IN ('pending', 'approved')
        ORDER BY 
            CASE br.priority
                WHEN 'emergency' THEN 1
                WHEN 'urgent' THEN 2
                ELSE 3
            END,
            br.required_date ASC
    ");
    $stmt->execute([$recipient['recipient_id']]);
    $activeRequests = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Get unread notifications
    $stmt = $pdo->prepare("
        SELECT COUNT(*) as unread_count
        FROM notifications
        WHERE user_id = ? AND is_read = 0
    ");
    $stmt->execute([$_SESSION['user_id']]);
    $unreadNotifications = $stmt->fetch(PDO::FETCH_ASSOC)['unread_count'];

} catch (PDOException $e) {
    error_log("Recipient dashboard error: " . $e->getMessage());
    $error = "An error occurred while loading the dashboard.";
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Recipient Dashboard - Blood Bank Management System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css">
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary">
        <div class="container">
            <a class="navbar-brand" href="#">Blood Bank Management System</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item">
                        <a class="nav-link active" href="recipient_dashboard.php">Dashboard</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="recipient/requests.php">Blood Requests</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="recipient/history.php">Request History</a>
                    </li>
                </ul>
                <div class="d-flex align-items-center">
                    <a href="recipient/notifications.php" class="btn btn-light position-relative me-2">
                        <i class="bi bi-bell"></i>
                        <?php if ($unreadNotifications > 0): ?>
                            <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger">
                                <?php echo $unreadNotifications; ?>
                            </span>
                        <?php endif; ?>
                    </a>
                    <a href="auth/logout.php" class="btn btn-light">Logout</a>
                </div>
            </div>
        </div>
    </nav>

    <div class="container mt-4">
        <?php if (isset($error)): ?>
            <div class="alert alert-danger"><?php echo $error; ?></div>
        <?php endif; ?>

        <!-- Recipient Information -->
        <div class="row mb-4">
            <div class="col-md-6">
                <div class="card">
                    <div class="card-body">
                        <h5 class="card-title">Your Information</h5>
                        <div class="row">
                            <div class="col-md-6">
                                <p><strong>Name:</strong> <?php echo htmlspecialchars($recipient['name']); ?></p>
                                <p><strong>Blood Group:</strong> <?php echo htmlspecialchars($recipient['blood_group']); ?></p>
                                <p><strong>Contact:</strong> <?php echo htmlspecialchars($recipient['contact']); ?></p>
                            </div>
                            <div class="col-md-6">
                                <p><strong>City:</strong> <?php echo htmlspecialchars($recipient['city']); ?></p>
                                <p><strong>Email:</strong> <?php echo htmlspecialchars($recipient['email']); ?></p>
                                <p><strong>Status:</strong> 
                                    <span class="badge bg-<?php echo $recipient['status'] === 'active' ? 'success' : 'danger'; ?>">
                                        <?php echo ucfirst($recipient['status']); ?>
                                    </span>
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="card">
                    <div class="card-body">
                        <h5 class="card-title">Quick Actions</h5>
                        <div class="d-grid gap-2">
                            <a href="recipient/requests.php?action=new" class="btn btn-primary">
                                <i class="bi bi-plus-circle"></i> New Blood Request
                            </a>
                            <a href="recipient/emergency.php" class="btn btn-danger">
                                <i class="bi bi-exclamation-triangle"></i> Emergency Request
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Active Blood Requests -->
        <div class="card mb-4">
            <div class="card-body">
                <h5 class="card-title">Active Blood Requests</h5>
                <?php if (empty($activeRequests)): ?>
                    <p>No active blood requests.</p>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Blood Group</th>
                                    <th>Type</th>
                                    <th>Units</th>
                                    <th>Required Date</th>
                                    <th>Hospital</th>
                                    <th>Priority</th>
                                    <th>Status</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($activeRequests as $request): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($request['blood_group']); ?></td>
                                        <td><?php echo htmlspecialchars($request['component_type']); ?></td>
                                        <td><?php echo htmlspecialchars($request['units_required']); ?></td>
                                        <td><?php echo date('M d, Y', strtotime($request['required_date'])); ?></td>
                                        <td><?php echo htmlspecialchars($request['hospital_name']); ?></td>
                                        <td>
                                            <span class="badge bg-<?php 
                                                echo $request['priority'] === 'emergency' ? 'danger' : 
                                                    ($request['priority'] === 'urgent' ? 'warning' : 'info'); 
                                            ?>">
                                                <?php echo ucfirst($request['priority']); ?>
                                            </span>
                                        </td>
                                        <td>
                                            <span class="badge bg-<?php 
                                                echo $request['status'] === 'approved' ? 'success' : 'warning'; 
                                            ?>">
                                                <?php echo ucfirst($request['status']); ?>
                                            </span>
                                        </td>
                                        <td>
                                            <?php if ($request['status'] === 'pending'): ?>
                                                <button onclick="cancelRequest(<?php echo $request['request_id']; ?>)" 
                                                        class="btn btn-sm btn-danger">
                                                    Cancel
                                                </button>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function cancelRequest(requestId) {
            if (confirm('Are you sure you want to cancel this blood request?')) {
                fetch('recipient/cancel_request.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({
                        request_id: requestId
                    })
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        alert('Blood request cancelled successfully!');
                        window.location.reload();
                    } else {
                        alert(data.error || 'Failed to cancel blood request');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('An error occurred while cancelling the request');
                });
            }
        }
    </script>
</body>
</html> 