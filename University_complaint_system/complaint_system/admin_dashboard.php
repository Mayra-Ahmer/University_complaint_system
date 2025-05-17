<?php
require_once 'config.php';
require_once 'auth.php';

if (!isAdmin()) {
    header("Location: ../login.php");
    exit();
}

// Get total counts
$usersCount = $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
$studentsCount = $pdo->query("SELECT COUNT(*) FROM students")->fetchColumn();
$staffCount = $pdo->query("SELECT COUNT(*) FROM staff")->fetchColumn();
$complaintsCount = $pdo->query("SELECT COUNT(*) FROM complaints")->fetchColumn();

// Get complaints count by status
$complaints = $pdo->query("
    SELECT status, COUNT(*) as count 
    FROM complaints 
    GROUP BY status
")->fetchAll(PDO::FETCH_KEY_PAIR);

// Get recent complaints
$recentComplaints = $pdo->query("
    SELECT c.id, c.description, c.status, c.created_at, 
           cat.name as category, sub.name as subcategory, 
           u.name as student_name
    FROM complaints c
    JOIN categories cat ON c.category_id = cat.id
    JOIN subcategories sub ON c.subcategory_id = sub.id
    JOIN users u ON c.user_id = u.id
    ORDER BY c.created_at DESC
    LIMIT 5
")->fetchAll();

// Get recent users
$recentUsers = $pdo->query("
    SELECT u.id, u.name, u.email, u.role, 
           s.enrollment_no, st.designation
    FROM users u
    LEFT JOIN students s ON u.id = s.user_id
    LEFT JOIN staff st ON u.id = st.user_id
    ORDER BY u.id DESC
    LIMIT 5
")->fetchAll();
?>

<?php include 'header.php'; ?>

<div class="row mb-4">
    <div class="col-md-3">
        <div class="card text-white bg-primary mb-3">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h5 class="card-title">Total Users</h5>
                        <p class="card-text display-6"><?php echo $usersCount; ?></p>
                    </div>
                    <i class="bi bi-people-fill fs-1"></i>
                </div>
            </div>
            <div class="card-footer bg-transparent">
                <a href="users.php" class="text-white stretched-link">View All Users</a>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card text-white bg-success mb-3">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h5 class="card-title">Students</h5>
                        <p class="card-text display-6"><?php echo $studentsCount; ?></p>
                    </div>
                    <i class="bi bi-mortarboard-fill fs-1"></i>
                </div>
            </div>
            <div class="card-footer bg-transparent">
                <a href="users.php?role=student" class="text-white stretched-link">View Students</a>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card text-white bg-warning mb-3">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h5 class="card-title">Staff Members</h5>
                        <p class="card-text display-6"><?php echo $staffCount; ?></p>
                    </div>
                    <i class="bi bi-person-badge-fill fs-1"></i>
                </div>
            </div>
            <div class="card-footer bg-transparent">
                <a href="users.php?role=staff" class="text-white stretched-link">View Staff</a>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card text-white bg-info mb-3">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h5 class="card-title">Total Complaints</h5>
                        <p class="card-text display-6"><?php echo $complaintsCount; ?></p>
                    </div>
                    <i class="bi bi-clipboard2-pulse-fill fs-1"></i>
                </div>
            </div>
            <div class="card-footer bg-transparent">
                <a href="complaints.php" class="text-white stretched-link">View Complaints</a>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-md-6">
        <div class="card mb-4">
            <div class="card-header bg-primary text-white">
                <h5 class="mb-0">Complaints Overview</h5>
            </div>
            <div class="card-body">
                <div class="row text-center">
                    <div class="col-md-4">
                        <div class="card border-success mb-3">
                            <div class="card-body text-success">
                                <h5>Resolved</h5>
                                <p class="display-5"><?php echo $complaints['resolved'] ?? 0; ?></p>
                                <small><?php echo $complaintsCount > 0 ? round(($complaints['resolved'] ?? 0) / $complaintsCount * 100, 1) : 0; ?>%</small>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card border-warning mb-3">
                            <div class="card-body text-warning">
                                <h5>In Progress</h5>
                                <p class="display-5"><?php echo $complaints['in progress'] ?? 0; ?></p>
                                <small><?php echo $complaintsCount > 0 ? round(($complaints['in progress'] ?? 0) / $complaintsCount * 100, 1) : 0; ?>%</small>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card border-danger mb-3">
                            <div class="card-body text-danger">
                                <h5>Pending</h5>
                                <p class="display-5"><?php echo $complaints['pending'] ?? 0; ?></p>
                                <small><?php echo $complaintsCount > 0 ? round(($complaints['pending'] ?? 0) / $complaintsCount * 100, 1) : 0; ?>%</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="card mb-4">
            <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
                <h5 class="mb-0">Recent Complaints</h5>
                <a href="complaints.php" class="btn btn-light btn-sm">View All</a>
            </div>
            <div class="card-body">
                <?php if (count($recentComplaints) > 0): ?>
                    <div class="list-group">
                        <?php foreach ($recentComplaints as $complaint): ?>
                            <a href="../view_complaint.php?id=<?php echo $complaint['id']; ?>" class="list-group-item list-group-item-action">
                                <div class="d-flex w-100 justify-content-between">
                                    <h6 class="mb-1"><?php echo htmlspecialchars($complaint['category']); ?>: <?php echo htmlspecialchars($complaint['subcategory']); ?></h6>
                                    <small class="text-<?php 
                                        echo $complaint['status'] === 'resolved' ? 'success' : 
                                             ($complaint['status'] === 'in progress' ? 'warning' : 'danger'); 
                                    ?>">
                                        <?php echo ucfirst($complaint['status']); ?>
                                    </small>
                                </div>
                                <p class="mb-1"><?php echo htmlspecialchars(substr($complaint['description'], 0, 100)); ?>...</p>
                                <small class="text-muted">
                                    Submitted by <?php echo htmlspecialchars($complaint['student_name']); ?> on <?php echo date('M d, Y', strtotime($complaint['created_at'])); ?>
                                </small>
                            </a>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <div class="alert alert-info mb-0">No recent complaints found.</div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <div class="col-md-6">
        <div class="card mb-4">
            <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
                <h5 class="mb-0">Recent Users</h5>
                <a href="users.php" class="btn btn-light btn-sm">View All</a>
            </div>
            <div class="card-body">
                <?php if (count($recentUsers) > 0): ?>
                    <div class="table-responsive">
                        <table class="table table-sm">
                            <thead>
                                <tr>
                                    <th>Name</th>
                                    <th>Email</th>
                                    <th>Role</th>
                                    <th>Details</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($recentUsers as $user): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($user['name']); ?></td>
                                        <td><?php echo htmlspecialchars($user['email']); ?></td>
                                        <td>
                                            <span class="badge bg-<?php 
                                                echo $user['role'] === 'admin' ? 'danger' : 
                                                     ($user['role'] === 'staff' ? 'warning' : 'success'); 
                                            ?>">
                                                <?php echo ucfirst($user['role']); ?>
                                            </span>
                                        </td>
                                        <td>
                                            <?php if ($user['role'] === 'student' && isset($user['enrollment_no'])): ?>
                                                <?php echo htmlspecialchars($user['enrollment_no']); ?>
                                            <?php elseif ($user['role'] === 'staff' && isset($user['designation'])): ?>
                                                <?php echo htmlspecialchars($user['designation']); ?>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <div class="alert alert-info mb-0">No recent users found.</div>
                <?php endif; ?>
            </div>
        </div>

        <div class="card">
            <div class="card-header bg-primary text-white">
                <h5 class="mb-0">Quick Actions</h5>
            </div>
            <div class="card-body">
                <div class="row g-2">
                    <div class="col-md-6">
                        <a href="users.php" class="btn btn-outline-primary w-100">
                            <i class="bi bi-people-fill me-2"></i> Manage Users
                        </a>
                    </div>
                    <div class="col-md-6">
                        <a href="complaints.php" class="btn btn-outline-primary w-100">
                            <i class="bi bi-clipboard2-pulse-fill me-2"></i> Manage Complaints
                        </a>
                    </div>
                    <div class="col-md-6">
                        <a href="staff_categories.php" class="btn btn-outline-primary w-100">
                            <i class="bi bi-person-lines-fill me-2"></i> Staff Assignments
                        </a>
                    </div>
                    <div class="col-md-6">
                        <a href="categories.php" class="btn btn-outline-primary w-100">
                            <i class="bi bi-tags-fill me-2"></i> Manage Categories
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include 'footer.php'; ?>