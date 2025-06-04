<?php
session_start();
require_once 'config/database.php';

// Check if user is logged in and is an admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: login.html');
    exit;
}

$pdo = getConnection();

// Get admin information
$stmt = $pdo->prepare("SELECT * FROM users WHERE user_id = ?");
$stmt->execute([$_SESSION['user_id']]);
$admin = $stmt->fetch();

// Get pending registrations
$stmt = $pdo->prepare("
    SELECT u.*, 
           CASE 
               WHEN u.role = 'hospital' THEN h.name 
               WHEN u.role = 'donor' THEN d.name 
               WHEN u.role = 'recipient' THEN r.name 
           END as full_name,
           CASE 
               WHEN u.role = 'hospital' THEN h.license_number 
               ELSE NULL 
           END as license_number
    FROM users u
    LEFT JOIN hospitals h ON u.user_id = h.user_id
    LEFT JOIN donors d ON u.user_id = d.user_id
    LEFT JOIN recipients r ON u.user_id = r.user_id
    WHERE u.status = 'pending'
    ORDER BY u.created_at DESC
");
$stmt->execute();
$pendingRegistrations = $stmt->fetchAll();

// Get unread notifications
$stmt = $pdo->prepare("
    SELECT COUNT(*) as count 
    FROM notifications 
    WHERE user_id = ? AND is_read = 0
");
$stmt->execute([$_SESSION['user_id']]);
$unreadNotifications = $stmt->fetch()['count'];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - Blood Bank Management System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css" rel="stylesheet">
    <link href="style.css" rel="stylesheet">
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary">
        <div class="container">
            <a class="navbar-brand" href="#">Blood Bank Admin</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item">
                        <a class="nav-link active" href="admin_dashboard.php">Dashboard</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="admin/users.php">Users</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="admin/hospitals.php">Hospitals</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="admin/blood_units.php">Blood Units</a>
                    </li>
                </ul>
                <div class="d-flex align-items-center">
                    <a href="notifications.php" class="btn btn-light position-relative me-3">
                        <i class="bi bi-bell"></i>
                        <?php if ($unreadNotifications > 0): ?>
                            <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger">
                                <?php echo $unreadNotifications; ?>
                            </span>
                        <?php endif; ?>
                    </a>
                    <div class="dropdown">
                        <button class="btn btn-light dropdown-toggle" type="button" data-bs-toggle="dropdown">
                            <i class="bi bi-person-circle"></i> Admin
                        </button>
                        <ul class="dropdown-menu dropdown-menu-end">
                            <li><a class="dropdown-item" href="admin/profile.php">Profile</a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item" href="auth/logout.php">Logout</a></li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </nav>

    <div class="container mt-4">
        <div class="row">
            <div class="col-md-12">
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">Pending Registrations</h5>
                        <span class="badge bg-primary"><?php echo count($pendingRegistrations); ?> pending</span>
                    </div>
                    <div class="card-body">
                        <?php if (empty($pendingRegistrations)): ?>
                            <p class="text-center text-muted">No pending registrations</p>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead>
                                        <tr>
                                            <th>Name</th>
                                            <th>Username</th>
                                            <th>Email</th>
                                            <th>Role</th>
                                            <th>Registration Date</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($pendingRegistrations as $registration): ?>
                                            <tr>
                                                <td><?php echo htmlspecialchars($registration['full_name']); ?></td>
                                                <td><?php echo htmlspecialchars($registration['username']); ?></td>
                                                <td><?php echo htmlspecialchars($registration['email']); ?></td>
                                                <td>
                                                    <span class="badge bg-<?php 
                                                        echo $registration['role'] === 'hospital' ? 'primary' : 
                                                            ($registration['role'] === 'donor' ? 'success' : 'info'); 
                                                    ?>">
                                                        <?php echo ucfirst($registration['role']); ?>
                                                    </span>
                                                </td>
                                                <td><?php echo date('M d, Y', strtotime($registration['created_at'])); ?></td>
                                                <td>
                                                    <button class="btn btn-sm btn-success" onclick="approveRegistration(<?php echo $registration['user_id']; ?>)">
                                                        <i class="bi bi-check-lg"></i> Approve
                                                    </button>
                                                    <button class="btn btn-sm btn-danger" onclick="rejectRegistration(<?php echo $registration['user_id']; ?>)">
                                                        <i class="bi bi-x-lg"></i> Reject
                                                    </button>
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
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function approveRegistration(userId) {
            console.log("Approve button clicked for user ID:", userId);
            if (confirm('Are you sure you want to approve this registration?')) {
                fetch('admin/approve_registration.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({ user_id: userId })
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        alert('Registration approved successfully');
                        location.reload();
                    } else {
                        alert(data.error || 'Failed to approve registration');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('An error occurred while approving the registration');
                });
            }
        }

        function rejectRegistration(userId) {
            if (confirm('Are you sure you want to reject this registration?')) {
                fetch('admin/reject_registration.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({ user_id: userId })
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        alert('Registration rejected successfully');
                        location.reload();
                    } else {
                        alert(data.error || 'Failed to reject registration');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('An error occurred while rejecting the registration');
                });
            }
        }
    </script>
</body>
</html> 