<?php
require_once 'config.php';
require_once 'auth.php';
redirectIfNotLoggedIn();

if (!isset($_GET['id'])) {
    header("Location: " . (isAdmin() ? 'admin/dashboard.php' : (isStaff() ? 'staff/dashboard.php' : 'student/dashboard.php')));
    exit();
}

$complaint_id = $_GET['id'];

// Get complaint details
$stmt = $pdo->prepare("
    SELECT c.*, cat.name as category, sub.name as subcategory, 
           u.name as user_name, u.email as user_email,
           s.enrollment_no as student_enrollment, s.semester as student_semester
    FROM complaints c
    JOIN categories cat ON c.category_id = cat.id
    JOIN subcategories sub ON c.subcategory_id = sub.id
    JOIN users u ON c.user_id = u.id
    LEFT JOIN students s ON c.user_id = s.user_id
    WHERE c.id = ?
");
$stmt->execute([$complaint_id]);
$complaint = $stmt->fetch();

if (!$complaint) {
    header("Location: " . (isAdmin() ? 'admin/dashboard.php' : (isStaff() ? 'staff/dashboard.php' : 'student/dashboard.php')));
    exit();
}

// Check if staff member is authorized to view this complaint
if (isStaff()) {
    $stmt = $pdo->prepare("
        SELECT 1 FROM staff_categories 
        WHERE staff_id = (SELECT id FROM staff WHERE user_id = ?)
        AND category_id = ?
    ");
    $stmt->execute([$_SESSION['user_id'], $complaint['category_id']]);
    if (!$stmt->fetch()) {
        header("Location: staff/dashboard.php");
        exit();
    }
}

// Check if student is viewing their own complaint
if (isStudent() && $complaint['user_id'] != $_SESSION['user_id']) {
    header("Location: student/dashboard.php");
    exit();
}

// Get comments for this complaint
$comments = $pdo->prepare("
    SELECT cm.*, u.name as author_name, u.role as author_role
    FROM complaint_comments cm
    JOIN users u ON cm.user_id = u.id
    WHERE cm.complaint_id = ?
    ORDER BY cm.created_at ASC
");
$comments->execute([$complaint_id]);
$comments = $comments->fetchAll();

// Process form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['add_comment'])) {
        $comment = $_POST['comment'];
        
        if (!empty($comment)) {
            $stmt = $pdo->prepare("
                INSERT INTO complaint_comments (complaint_id, user_id, comment)
                VALUES (?, ?, ?)
            ");
            $stmt->execute([$complaint_id, $_SESSION['user_id'], $comment]);
            header("Location: view_complaint.php?id=$complaint_id");
            exit();
        }
    }
    
    if (isset($_POST['update_status']) && (isStaff() || isAdmin())) {
        $status = $_POST['status'];
        $stmt = $pdo->prepare("UPDATE complaints SET status = ? WHERE id = ?");
        $stmt->execute([$status, $complaint_id]);
        header("Location: view_complaint.php?id=$complaint_id");
        exit();
    }
}
?>

<?php include 'header.php'; ?>

<div class="card mb-4">
    <div class="card-header bg-primary text-white">
        Complaint Details #<?php echo $complaint['id']; ?>
    </div>
    <div class="card-body">
        <div class="row mb-4">
            <div class="col-md-6">
                <h5>Complaint Information</h5>
                <p><strong>Category:</strong> <?php echo htmlspecialchars($complaint['category']); ?></p>
                <p><strong>Subcategory:</strong> <?php echo htmlspecialchars($complaint['subcategory']); ?></p>
                <p><strong>Status:</strong> 
                    <span class="badge bg-<?php 
                        echo $complaint['status'] === 'resolved' ? 'success' : 
                             ($complaint['status'] === 'in progress' ? 'warning' : 'danger'); 
                    ?>">
                        <?php echo ucfirst($complaint['status']); ?>
                    </span>
                </p>
                <p><strong>Submitted:</strong> <?php echo date('M d, Y h:i A', strtotime($complaint['created_at'])); ?></p>
            </div>
            <div class="col-md-6">
                <h5>Student Information</h5>
                <p><strong>Name:</strong> <?php echo htmlspecialchars($complaint['user_name']); ?></p>
                <p><strong>Email:</strong> <?php echo htmlspecialchars($complaint['user_email']); ?></p>
                <?php if (isset($complaint['student_enrollment'])): ?>
                    <p><strong>Enrollment No:</strong> <?php echo htmlspecialchars($complaint['student_enrollment']); ?></p>
                    <p><strong>Semester:</strong> <?php echo htmlspecialchars($complaint['student_semester']); ?></p>
                <?php endif; ?>
            </div>
        </div>
        
        <div class="mb-4">
            <h5>Description</h5>
            <div class="border p-3 bg-light">
                <?php echo nl2br(htmlspecialchars($complaint['description'])); ?>
            </div>
        </div>
        
        <?php if (isStaff() || isAdmin()): ?>
        <div class="mb-4">
            <h5>Update Status</h5>
            <form method="POST" class="row g-3">
                <div class="col-md-8">
                    <select name="status" class="form-select">
                        <option value="pending" <?php echo $complaint['status'] === 'pending' ? 'selected' : ''; ?>>Pending</option>
                        <option value="in progress" <?php echo $complaint['status'] === 'in progress' ? 'selected' : ''; ?>>In Progress</option>
                        <option value="resolved" <?php echo $complaint['status'] === 'resolved' ? 'selected' : ''; ?>>Resolved</option>
                    </select>
                </div>
                <div class="col-md-4">
                    <button type="submit" name="update_status" class="btn btn-primary w-100">Update Status</button>
                </div>
            </form>
        </div>
        <?php endif; ?>
    </div>
</div>

<div class="card">
    <div class="card-header bg-primary text-white">
        Comments
    </div>
    <div class="card-body">
        <?php if (count($comments) > 0): ?>
            <div class="mb-4">
                <?php foreach ($comments as $comment): ?>
                    <div class="mb-3 p-3 border rounded <?php echo $comment['author_role'] === 'staff' || $comment['author_role'] === 'admin' ? 'bg-light' : ''; ?>">
                        <div class="d-flex justify-content-between mb-2">
                            <strong><?php echo htmlspecialchars($comment['author_name']); ?></strong>
                            <small class="text-muted"><?php echo date('M d, Y h:i A', strtotime($comment['created_at'])); ?></small>
                        </div>
                        <p><?php echo nl2br(htmlspecialchars($comment['comment'])); ?></p>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <p>No comments yet.</p>
        <?php endif; ?>
        
        <form method="POST">
            <div class="mb-3">
                <label for="comment" class="form-label">Add Comment</label>
                <textarea class="form-control" id="comment" name="comment" rows="3" required></textarea>
            </div>
            <button type="submit" name="add_comment" class="btn btn-primary">Submit Comment</button>
        </form>
    </div>
</div>

<?php include 'footer.php'; ?>