<?php
session_start();
require_once 'config/database.php';

// Check if user is logged in and is a hospital
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'hospital') {
    header('Location: login.html');
    exit;
}

$pdo = getConnection();

// Get hospital information
$stmt = $pdo->prepare("
    SELECT h.*, u.username, u.email 
    FROM hospitals h 
    JOIN users u ON h.user_id = u.user_id 
    WHERE h.user_id = ?
");
$stmt->execute([$_SESSION['user_id']]);
$hospital = $stmt->fetch();

// Get blood group statistics
$stmt = $pdo->prepare("
    SELECT blood_group, SUM(available_count) as count
    FROM hospital_inventory_counts
    WHERE hospital_id = ?
    GROUP BY blood_group
");
$stmt->execute([$hospital['hospital_id']]);
$bloodGroupStats = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);

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
    <title>Hospital Dashboard - Blood Bank Management System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css" rel="stylesheet">
    <link href="style.css" rel="stylesheet">
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary">
        <div class="container">
            <a class="navbar-brand" href="#"><?php echo htmlspecialchars($hospital['name']); ?></a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item">
                        <a class="nav-link active" href="hospital_dashboard.php">Dashboard</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="hospital/blood_units.php">Blood Units</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="hospital/requests.php">Blood Requests</a>
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
                            <i class="bi bi-person-circle"></i> <?php echo htmlspecialchars($hospital['name']); ?>
                        </button>
                        <ul class="dropdown-menu dropdown-menu-end">
                            <li><a class="dropdown-item" href="hospital/profile.php">Profile</a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item" href="auth/logout.php">Logout</a></li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </nav>

    <div class="container mt-4">
        <!-- Blood Group Statistics -->
        <div class="row mb-4">
            <div class="col-md-12">
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">Blood Inventory Status</h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <?php
                            $bloodGroups = ['A+', 'A-', 'B+', 'B-', 'AB+', 'AB-', 'O+', 'O-'];
                            foreach ($bloodGroups as $group):
                                $count = $bloodGroupStats[$group] ?? 0;
                            ?>
                                <div class="col-md-3 mb-3">
                                    <div class="card bg-light">
                                        <div class="card-body text-center">
                                            <h3 class="mb-0"><?php echo $group; ?></h3>
                                            <p class="mb-0"><?php echo $count; ?> units available</p>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Add Blood Unit Form -->
        <div class="row mb-4">
            <div class="col-md-12">
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">Add Blood Unit</h5>
                    </div>
                    <div class="card-body">
                        <form id="addBloodUnitForm" onsubmit="return addBloodUnit(event)">
                            <div class="row">
                                <div class="col-md-3 mb-3">
                                    <label for="blood_group" class="form-label">Blood Group</label>
                                    <select class="form-select" id="blood_group" name="blood_group" required>
                                        <option value="">Select Blood Group</option>
                                        <?php foreach ($bloodGroups as $group): ?>
                                            <option value="<?php echo $group; ?>"><?php echo $group; ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="col-md-3 mb-3">
                                    <label for="type" class="form-label">Type</label>
                                    <select class="form-select" id="type" name="type" required>
                                        <option value="">Select Type</option>
                                        <option value="whole">Whole Blood</option>
                                        <option value="plasma">Plasma</option>
                                        <option value="platelets">Platelets</option>
                                    </select>
                                </div>
                                <div class="col-md-3 mb-3">
                                    <label for="donor_id" class="form-label">Donor ID (Optional)</label>
                                    <input type="text" class="form-control" id="donor_id" name="donor_id">
                                </div>
                                <div class="col-md-3 mb-3">
                                    <label for="expiry_date" class="form-label">Expiry Date</label>
                                    <input type="date" class="form-control" id="expiry_date" name="expiry_date" required>
                                </div>
                            </div>
                            <button type="submit" class="btn btn-primary">Add Blood Unit</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>

        <!-- Blood Units Table -->
        <div class="row">
            <div class="col-md-12">
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">Recent Blood Units</h5>
                        <div class="btn-group">
                            <button type="button" class="btn btn-primary" onclick="filterBloodUnits('all')">All</button>
                            <button type="button" class="btn btn-outline-primary" onclick="filterBloodUnits('available')">Available</button>
                            <button type="button" class="btn btn-outline-primary" onclick="filterBloodUnits('reserved')">Reserved</button>
                            <button type="button" class="btn btn-outline-primary" onclick="filterBloodUnits('used')">Used</button>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Unit ID</th>
                                        <th>Blood Group</th>
                                        <th>Type</th>
                                        <th>Donor</th>
                                        <th>Collection Date</th>
                                        <th>Expiry Date</th>
                                        <th>Status</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($bloodUnits as $unit): ?>
                                        <tr class="blood-unit-row" data-status="<?php echo $unit['status']; ?>">
                                            <td><?php echo htmlspecialchars($unit['unit_id']); ?></td>
                                            <td><?php echo htmlspecialchars($unit['blood_group']); ?></td>
                                            <td><?php echo htmlspecialchars($unit['type']); ?></td>
                                            <td><?php echo htmlspecialchars($unit['donor_name'] ?? 'N/A'); ?></td>
                                            <td><?php echo date('M d, Y', strtotime($unit['collection_date'])); ?></td>
                                            <td><?php echo date('M d, Y', strtotime($unit['expiry_date'])); ?></td>
                                            <td>
                                                <span class="badge bg-<?php 
                                                    echo $unit['status'] === 'available' ? 'success' : 
                                                        ($unit['status'] === 'reserved' ? 'warning' : 'danger'); 
                                                ?>">
                                                    <?php echo ucfirst($unit['status']); ?>
                                                </span>
                                            </td>
                                            <td>
                                                <button class="btn btn-sm btn-info" onclick="viewUnitDetails(<?php echo $unit['unit_id']; ?>)">
                                                    <i class="bi bi-eye"></i> Details
                                                </button>
                                                <?php if ($unit['status'] === 'available'): ?>
                                                    <button class="btn btn-sm btn-warning" onclick="markAsReserved(<?php echo $unit['unit_id']; ?>)">
                                                        <i class="bi bi-bookmark"></i> Reserve
                                                    </button>
                                                <?php endif; ?>
                                                <?php if ($unit['status'] === 'reserved'): ?>
                                                    <button class="btn btn-sm btn-success" onclick="markAsUsed(<?php echo $unit['unit_id']; ?>)">
                                                        <i class="bi bi-check-lg"></i> Mark Used
                                                    </button>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function filterBloodUnits(status) {
            const rows = document.querySelectorAll('.blood-unit-row');
            rows.forEach(row => {
                if (status === 'all' || row.dataset.status === status) {
                    row.style.display = '';
                } else {
                    row.style.display = 'none';
                }
            });
        }

        function addBloodUnit(event) {
            event.preventDefault();
            
            const formData = {
                blood_group: document.getElementById('blood_group').value,
                type: document.getElementById('type').value,
                donor_id: document.getElementById('donor_id').value || null,
                expiry_date: document.getElementById('expiry_date').value
            };

            fetch('hospital/add_blood_unit.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify(formData)
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('Blood unit added successfully');
                    location.reload();
                } else {
                    alert(data.error || 'Failed to add blood unit');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('An error occurred while adding the blood unit');
            });

            return false;
        }

        function viewUnitDetails(unitId) {
            window.location.href = `hospital/blood_unit_details.php?unit_id=${unitId}`;
        }

        function markAsReserved(unitId) {
            if (confirm('Are you sure you want to mark this blood unit as reserved?')) {
                fetch('hospital/update_blood_unit_status.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({ 
                        unit_id: unitId,
                        status: 'reserved'
                    })
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        alert('Blood unit marked as reserved successfully');
                        location.reload();
                    } else {
                        alert(data.error || 'Failed to update blood unit status');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('An error occurred while updating blood unit status');
                });
            }
        }

        function markAsUsed(unitId) {
            if (confirm('Are you sure you want to mark this blood unit as used?')) {
                fetch('hospital/update_blood_unit_status.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({ 
                        unit_id: unitId,
                        status: 'used'
                    })
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        alert('Blood unit marked as used successfully');
                        location.reload();
                    } else {
                        alert(data.error || 'Failed to update blood unit status');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('An error occurred while updating blood unit status');
                });
            }
        }
    </script>
</body>
</html> 