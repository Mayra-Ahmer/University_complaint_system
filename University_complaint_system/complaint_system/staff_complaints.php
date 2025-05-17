<?php
require_once 'config.php';
require_once 'auth.php';

if (!isStaff()) {
    header("Location: ../login.php");
    exit();
}

// Get staff details
$stmt = $pdo->prepare("SELECT s.* FROM staff s WHERE s.user_id = ?");
$stmt->execute([$_SESSION['user_id']]);
$staff = $stmt->fetch();

// Get search parameters
$search = isset($_GET['search']) ? $_GET['search'] : '';
$status_filter = isset($_GET['status']) ? $_GET['status'] : '';

// Build base query
$query = "
    SELECT c.id, c.description, c.status, c.created_at, 
           cat.name as category, sub.name as subcategory, 
           u.name as student_name, u.email as student_email
    FROM complaints c
    JOIN categories cat ON c.category_id = cat.id
    JOIN subcategories sub ON c.subcategory_id = sub.id
    JOIN users u ON c.user_id = u.id
    WHERE c.category_id IN (
        SELECT category_id FROM staff_categories WHERE staff_id = ?
    )
";

// Add filters if provided
$params = [$staff['id']];
if (!empty($search)) {
    $query .= " AND (c.description LIKE ? OR u.name LIKE ? OR cat.name LIKE ? OR sub.name LIKE ?)";
    $search_term = "%$search%";
    array_push($params, $search_term, $search_term, $search_term, $search_term);
}

if (!empty($status_filter) && in_array($status_filter, ['pending', 'in progress', 'resolved'])) {
    $query .= " AND c.status = ?";
    $params[] = $status_filter;
}

// Add sorting and pagination
$query .= " ORDER BY c.created_at DESC";

// Get all complaints assigned to this staff member
$stmt = $pdo->prepare($query);
$stmt->execute($params);
$complaints = $stmt->fetchAll();

// Get status counts for filter badges
$status_counts = $pdo->prepare("
    SELECT status, COUNT(*) as count 
    FROM complaints 
    WHERE category_id IN (
        SELECT category_id FROM staff_categories WHERE staff_id = ?
    )
    GROUP BY status
");
$status_counts->execute([$staff['id']]);
$status_stats = $status_counts->fetchAll(PDO::FETCH_KEY_PAIR);
$total_complaints = array_sum($status_stats);
?>

<?php include 'header.php'; ?>

<div class="card">
    <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
        <h2 class="mb-0">Assigned Complaints</h2>
        <a href="../submit_complaint.php" class="btn btn-light btn-sm">Submit New Complaint</a>
    </div>
    <div class="card-body">
        <!-- Search and Filter Form -->
        <form method="GET" class="mb-4">
            <div class="row">
                <div class="col-md-6">
                    <div class="input-group mb-3">
                        <input type="text" class="form-control" placeholder="Search complaints..." name="search" value="<?php echo htmlspecialchars($search); ?>">
                        <button class="btn btn-primary" type="submit">Search</button>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="d-flex align-items-center">
                        <span class="me-2">Filter by:</span>
                        <div class="btn-group" role="group">
                            <a href="?status=" class="btn btn-sm btn-outline-secondary <?php echo empty($status_filter) ? 'active' : ''; ?>">
                                All <span class="badge bg-secondary"><?php echo $total_complaints; ?></span>
                            </a>
                            <a href="?status=pending" class="btn btn-sm btn-outline-danger <?php echo $status_filter === 'pending' ? 'active' : ''; ?>">
                                Pending <span class="badge bg-danger"><?php echo $status_stats['pending'] ?? 0; ?></span>
                            </a>
                            <a href="?status=in progress" class="btn btn-sm btn-outline-warning <?php echo $status_filter === 'in progress' ? 'active' : ''; ?>">
                                In Progress <span class="badge bg-warning"><?php echo $status_stats['in progress'] ?? 0; ?></span>
                            </a>
                            <a href="?status=resolved" class="btn btn-sm btn-outline-success <?php echo $status_filter === 'resolved' ? 'active' : ''; ?>">
                                Resolved <span class="badge bg-success"><?php echo $status_stats['resolved'] ?? 0; ?></span>
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </form>

        <?php if (count($complaints) > 0): ?>
            <div class="table-responsive">
                <table class="table table-striped table-hover">
                    <thead class="table-dark">
                        <tr>
                            <th>ID</th>
                            <th>Student</th>
                            <th>Category</th>
                            <th>Subcategory</th>
                            <th>Description</th>
                            <th>Status</th>
                            <th>Date</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($complaints as $complaint): ?>
                            <tr>
                                <td><?php echo $complaint['id']; ?></td>
                                <td>
                                    <?php echo htmlspecialchars($complaint['student_name']); ?>
                                    <br><small class="text-muted"><?php echo htmlspecialchars($complaint['student_email']); ?></small>
                                </td>
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
                                    <div class="btn-group btn-group-sm" role="group">
                                        <a href="../view_complaint.php?id=<?php echo $complaint['id']; ?>" class="btn btn-info" title="View Details">
                                            <i class="bi bi-eye-fill"></i> View
                                        </a>
                                        <?php if ($complaint['status'] !== 'resolved'): ?>
                                            <a href="../view_complaint.php?id=<?php echo $complaint['id']; ?>" class="btn btn-success" title="Mark Resolved">
                                                <i class="bi bi-check-circle-fill"></i>
                                            </a>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <!-- Pagination would go here -->
            <!-- <nav aria-label="Page navigation">
                <ul class="pagination justify-content-center">
                    <li class="page-item disabled">
                        <a class="page-link" href="#" tabindex="-1">Previous</a>
                    </li>
                    <li class="page-item active"><a class="page-link" href="#">1</a></li>
                    <li class="page-item"><a class="page-link" href="#">2</a></li>
                    <li class="page-item"><a class="page-link" href="#">3</a></li>
                    <li class="page-item">
                        <a class="page-link" href="#">Next</a>
                    </li>
                </ul>
            </nav> -->
        <?php else: ?>
            <div class="alert alert-info text-center">
                <i class="bi bi-info-circle-fill me-2"></i>
                No complaints found matching your criteria.
                <?php if (!empty($search) || !empty($status_filter)): ?>
                    <a href="complaints.php" class="alert-link">Clear filters</a>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php include 'footer.php'; ?>