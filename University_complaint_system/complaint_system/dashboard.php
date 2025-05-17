<?php
require_once 'config.php';
require_once 'auth.php';

if (!isStudent()) {
    header("Location: login.php");
    exit();
}

// Get student details
$stmt = $pdo->prepare("SELECT s.*, u.name, u.email FROM students s JOIN users u ON s.user_id = u.id WHERE s.user_id = ?");
$stmt->execute([$_SESSION['user_id']]);
$student = $stmt->fetch();

// Get complaints count by status
$complaints = $pdo->prepare("
    SELECT status, COUNT(*) as count 
    FROM complaints 
    WHERE user_id = ? 
    GROUP BY status
");
$complaints->execute([$_SESSION['user_id']]);
$complaintStats = $complaints->fetchAll(PDO::FETCH_KEY_PAIR);
?>

<?php include 'header.php'; ?>

<div class="row">
    <div class="col-md-4">
        <div class="card mb-4">
            <div class="card-header bg-primary text-white">
                Student Information
            </div>
            <div class="card-body">
                <p><strong>Name:</strong> <?php echo htmlspecialchars($student['name']); ?></p>
                <p><strong>Email:</strong> <?php echo htmlspecialchars($student['email']); ?></p>
                <p><strong>Enrollment No:</strong> <?php echo htmlspecialchars($student['enrollment_no']); ?></p>
                <p><strong>Semester:</strong> <?php echo htmlspecialchars($student['semester']); ?></p>
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
                    SELECT c.id, c.description, c.status, c.created_at, cat.name as category, sub.name as subcategory
                    FROM complaints c
                    JOIN categories cat ON c.category_id = cat.id
                    JOIN subcategories sub ON c.subcategory_id = sub.id
                    WHERE c.user_id = ?
                    ORDER BY c.created_at DESC
                    LIMIT 5
                ");
                $recentComplaints->execute([$_SESSION['user_id']]);
                $complaints = $recentComplaints->fetchAll();
                
                if (count($complaints) > 0): ?>
                    <div class="table-responsive">
                        <table class="table table-striped">
                            <thead>
                                <tr>
                                    <th>ID</th>
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
                        <a href="complaints.php" class="btn btn-primary">View All Complaints</a>
                    </div>
                <?php else: ?>
                    <p>No complaints submitted yet.</p>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $category_id = $_POST['category_id'];
    $subcategory_id = $_POST['subcategory_id'];
    $description = $_POST['description'];
    $name = $_POST['name'];
    $email=$_POST['email'];
    // print_r($name);die;

    // Validate input
    if (empty($category_id) || empty($subcategory_id) || empty($description)) {
        $error = "Please fill in all fields";
    } else {
        
        // Insert complaint
        $stmt = $pdo->prepare("INSERT INTO complaints (`name`,email,user_id, category_id, subcategory_id, `description`, `status`) VALUES (?,?,?, ?, ?, ?, 'pending')");
        if ($stmt->execute([$name,$email,$_SESSION['user_id'], $category_id, $subcategory_id, $description])) {
            $success = "Complaint submitted successfully!";
        } else {
            $error = "Failed to submit complaint. Please try again.";
        }
    }
}

// Get categories and subcategories
$categories = $pdo->query("SELECT * FROM categories")->fetchAll();
$subcategories = $pdo->query("SELECT * FROM subcategories")->fetchAll();
include 'header.php';
?>

<div class="row justify-content-center">
    <div class="col-md-8">
        <h2 class="mb-4">Submit a Complaint</h2>
        
        <?php if (isset($error)): ?>
            <div class="alert alert-danger"><?php echo $error; ?></div>
        <?php endif; ?>
        
        <?php if (isset($success)): ?>
            <div class="alert alert-success"><?php echo $success; ?></div>
        <?php endif; ?>
        
        <form method="POST" action="">
        <input type="hidden" name="name" value="<?php echo $student['name']; ?>">
        <input type="hidden" name="email" value="<?php echo $student['email']; ?>">
            <div class="mb-3">
                <label for="category_id" class="form-label">Category</label>
                <select class="form-select" id="category_id" name="category_id" required>
                    <option value="">Select a category</option>
                    <?php foreach ($categories as $category): ?>
                        <option value="<?php echo $category['id']; ?>"><?php echo htmlspecialchars($category['name']); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div class="mb-3">
                <label for="subcategory_id" class="form-label">Subcategory</label>
                <select class="form-select" id="subcategory_id" name="subcategory_id" required>
                    <option value="">Select a subcategory</option>
                    <?php foreach ($subcategories as $subcategory): ?>
                        <option value="<?php echo $subcategory['id']; ?>" data-category="<?php echo $subcategory['category_id']; ?>">
                            <?php echo htmlspecialchars($subcategory['name']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div class="mb-3">
                <label for="description" class="form-label">Description</label>
                <textarea class="form-control" id="description" name="description" rows="5" required></textarea>
            </div>
            
            <button type="submit" class="btn btn-primary">Submit Complaint</button>
        </form>
    </div>
</div>

<script>

// Filter subcategories based on selected category
document.getElementById('category_id').addEventListener('change', function() {
    const categoryId = this.value;
    const subcategorySelect = document.getElementById('subcategory_id');
    
    // Reset subcategory selection
    subcategorySelect.selectedIndex = 0;
    
    // Show/hide subcategories based on selected category
    for (let i = 0; i < subcategorySelect.options.length; i++) {
        const option = subcategorySelect.options[i];
        if (option.value === '') continue;
        
        if (option.dataset.category === categoryId) {
            option.style.display = '';
        } else {
            option.style.display = 'none';
        }
    }
});
</script>

<?php include 'footer.php'; ?>