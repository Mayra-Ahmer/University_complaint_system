<?php
require_once 'config.php';
require_once 'auth.php';

if (!isStudent()) {
    header("Location: .login.php");
    exit();
}

// Get all complaints for the student
$stmt = $pdo->prepare("
    SELECT c.id, c.description, c.status, c.created_at, cat.name as category, sub.name as subcategory
    FROM complaints c
    JOIN categories cat ON c.category_id = cat.id
    JOIN subcategories sub ON c.subcategory_id = sub.id
    WHERE c.user_id = ?
    ORDER BY c.created_at DESC
");
$stmt->execute([$_SESSION['user_id']]);
$complaints = $stmt->fetchAll();
?>

<?php include 'header.php'; ?>

<div class="card">
    <div class="card-header bg-primary text-white">
        <h2 class="mb-0">My Complaints</h2>
    </div>
    <div class="card-body">
        <?php if (count($complaints) > 0): ?>
            <div class="table-responsive">
                <table class="table table-striped">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Category</th>
                            <th>Subcategory</th>
                            <th>Description</th>
                            <th>Status</th>
                            <th>Date</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($complaints as $complaint): ?>
                            <tr>
                                <td><?php echo $complaint['id']; ?></td>
                                <td><?php echo htmlspecialchars($complaint['category']); ?></td>
                                <td><?php echo htmlspecialchars($complaint['subcategory']); ?></td>
                                <td><?php echo htmlspecialchars(substr($complaint['description'], 0, 50)); ?>...</td>
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
        <?php else: ?>
            <div class="alert alert-info">You haven't submitted any complaints yet.</div>
        <?php endif; ?>
    </div>
</div>

<?php include 'footer.php'; ?>