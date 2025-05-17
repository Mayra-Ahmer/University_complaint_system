<?php
require_once 'config.php';
require_once 'auth.php';

if (!isAdmin()) {
    header("Location: ../login.php");
    exit();
}

// Get all staff members
$staffMembers = $pdo->query("
    SELECT s.id, u.name, u.email, s.designation
    FROM staff s
    JOIN users u ON s.user_id = u.id
")->fetchAll();

// Get all categories
$categories = $pdo->query("SELECT * FROM categories")->fetchAll();

// Get staff categories assignments
$staffCategories = $pdo->query("
    SELECT staff_id, category_id 
    FROM staff_categories
")->fetchAll(PDO::FETCH_GROUP | PDO::FETCH_COLUMN);

// Process form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_assignments'])) {
    // Delete all existing assignments
    $pdo->query("TRUNCATE TABLE staff_categories");
    
    // Insert new assignments
    if (isset($_POST['assignments'])) {
        $stmt = $pdo->prepare("INSERT INTO staff_categories (staff_id, category_id) VALUES (?, ?)");
        foreach ($_POST['assignments'] as $staff_id => $category_ids) {
            foreach ($category_ids as $category_id) {
                $stmt->execute([$staff_id, $category_id]);
            }
        }
    }
    
    $_SESSION['success'] = "Staff category assignments updated successfully!";
    header("Location: staff_categories.php");
    exit();
}
?>

<?php include 'header.php'; ?>

<div class="card">
    <div class="card-header bg-primary text-white">
        <h2 class="mb-0">Staff Category Assignments</h2>
    </div>
    <div class="card-body">
        <?php if (isset($_SESSION['success'])): ?>
            <div class="alert alert-success"><?php echo $_SESSION['success']; unset($_SESSION['success']); ?></div>
        <?php endif; ?>
        
        <form method="POST">
            <div class="table-responsive">
                <table class="table table-striped">
                    <thead>
                        <tr>
                            <th>Staff Member</th>
                            <th>Designation</th>
                            <th>Assigned Categories</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($staffMembers as $staff): ?>
                            <tr>
                                <td>
                                    <?php echo htmlspecialchars($staff['name']); ?>
                                    <br><small class="text-muted"><?php echo htmlspecialchars($staff['email']); ?></small>
                                </td>
                                <td><?php echo htmlspecialchars($staff['designation']); ?></td>
                                <td>
                                    <?php foreach ($categories as $category): ?>
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" 
                                                name="assignments[<?php echo $staff['id']; ?>][]" 
                                                value="<?php echo $category['id']; ?>"
                                                <?php echo isset($staffCategories[$staff['id']]) && in_array($category['id'], $staffCategories[$staff['id']]) ? 'checked' : ''; ?>>
                                            <label class="form-check-label">
                                                <?php echo htmlspecialchars($category['name']); ?>
                                            </label>
                                        </div>
                                    <?php endforeach; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <div class="text-end mt-3">
                <button type="submit" name="update_assignments" class="btn btn-primary">Save Assignments</button>
            </div>
        </form>
    </div>
</div>

<?php include 'footer.php'; ?>