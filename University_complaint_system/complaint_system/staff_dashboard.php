<?php
require_once 'config.php';
require_once 'auth.php';

if (!isStaff()) {
    header("Location: ../login.php");
    exit();
}

// Get staff details
$stmt = $pdo->prepare("SELECT s.*, u.name, u.email FROM staff s JOIN users u ON s.user_id = u.id WHERE s.user_id = ?");
$stmt->execute([$_SESSION['user_id']]);
$staff = $stmt->fetch();

// Get complaints count by status for staff to handle
$complaints = $pdo->prepare("
    SELECT status, COUNT(*) as count 
    FROM complaints 
    WHERE category_id IN (
        SELECT category_id FROM staff_categories WHERE staff_id = ?
    )
    GROUP BY status
");
$complaints->execute([$staff['id']]);
$complaintStats = $complaints->fetchAll(PDO::FETCH_KEY_PAIR);
?>

<?php include 'header.php'; ?>

<div class="row">
    <div class="col-md-4">
        <div class="card mb-4">
            <div class="card-header bg-primary text-white">
                Staff Information
            </div>
            <div class="card-body">
                <p><strong>Name:</strong> <?php echo htmlspecialchars($staff['name']); ?></p>
                <p><strong>Email:</strong> <?php echo htmlspecialchars($staff['email']); ?></p>
                <p><strong>Designation:</strong> <?php echo htmlspecialchars($staff['designation']); ?></p>
            </div>
        </div>
    </div>
    
    <div class="col-md-8">
        <div class="card mb-4">
            <div class="card-header bg-primary text-white">
                Complaint Statistics
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-4 text-center">
                        <div class="card bg-success text-white mb-3">
                            <div class="card-body">
                                <h5 class="card-title">Resolved</h5>
                                <p class="card-text display-4"><?php echo $complaintStats['resolved'] ?? 0; ?></p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4 text-center">
                        <div class="card bg-warning text-dark mb-3">
                            <div class="card-body">
                                <h5 class="card-title">In Progress</h5>
                                <p class="card-text display-4"><?php echo $complaintStats['in progress'] ?? 0; ?></p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4 text-center">
                        <div class="card bg-danger text-white mb-3">
                            <div class="card-body">
                                <h5 class="card-title">Pending</h5>
                                <p class="card-text display-4"><?php echo $complaintStats['pending'] ?? 0; ?></p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="card">
            <div class="card-header bg-primary text-white">
                Recent Complaints
            </div>
            <div class="card-body">
                <?php
                $recentComplaints = $pdo->prepare("
                    SELECT c.id, c.description, c.status, c.created_at, cat.name as category, sub.name as subcategory, u.name as student_name
                    FROM complaints c
                    JOIN categories cat ON c.category_id = cat.id
                    JOIN subcategories sub ON c.subcategory_id = sub.id
                    JOIN users u ON c.user_id = u.id
                    WHERE c.category_id IN (
                        SELECT category_id FROM staff_categories WHERE staff_id = ?
                    )
                    ORDER BY c.created_at DESC
                    LIMIT 5
                ");
                $recentComplaints->execute([$staff['id']]);
                $complaints = $recentComplaints->fetchAll();
                
                if (count($complaints) > 0): ?>
                    <div class="table-responsive">
                        <table class="table table-striped">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Student</th>
                                    <th>Category</th>
                                    <th>Subcategory</th>
                                    <th>Status</th>
                                    <th>Date</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($complaints as $complaint): ?>
                                    <tr>
                                        <td><?php echo $complaint['id']; ?></td>
                                        <td><?php echo htmlspecialchars($complaint['student_name']); ?></td>
                                        <td><?php echo htmlspecialchars($complaint['category']); ?></td>
                                        <td><?php echo htmlspecialchars($complaint['subcategory']); ?></td>
                                        <td>
                                            <span class="badge bg-<?php 
                                                echo $complaint['status'] === 'resolved' ? 'success' : 
                                                     ($complaint['status'] === 'in progress' ? 'warning' : 'danger'); 
                                            ?>">
                                                <?php echo ucfirst($complaint['status']); ?>
                                            </span>
                                        </td>
                                        <td><?php echo date('M d, Y', strtotime($complaint['created_at'])); ?></td>
                                        <td>
                                            <a href="../view_complaint.php?id=<?php echo $complaint['id']; ?>" class="btn btn-sm btn-info">View</a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    <div class="text-end">
                        <a href="../staff/complaints.php" class="btn btn-primary">View All Complaints</a>
                    </div>
                <?php else: ?>
                    <p>No complaints assigned to you yet.</p>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php include 'footer.php'; ?>