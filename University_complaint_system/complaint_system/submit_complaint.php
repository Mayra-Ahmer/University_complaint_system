<?php
require_once 'config.php';
require_once 'auth.php';
redirectIfNotLoggedIn();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $category_id = $_POST['category_id'];
    $subcategory_id = $_POST['subcategory_id'];
    $description = $_POST['description'];
    
    // Validate input
    if (empty($category_id) || empty($subcategory_id) || empty($description)) {
        $error = "Please fill in all fields";
    } else {
        // Insert complaint
        $stmt = $pdo->prepare("INSERT INTO complaints (user_id, category_id, subcategory_id, description, status) VALUES (?, ?, ?, ?, 'pending')");
        if ($stmt->execute([$_SESSION['user_id'], $category_id, $subcategory_id, $description])) {
            $success = "Complaint submitted successfully!";
        } else {
            $error = "Failed to submit complaint. Please try again.";
        }
    }
}

// Get categories and subcategories
$categories = $pdo->query("SELECT * FROM categories")->fetchAll();
$subcategories = $pdo->query("SELECT * FROM subcategories")->fetchAll();
?>

<?php include 'header.php';
include 'dashboard.php';
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
        
        <form method="POST" action="submit_complaint.php">
            <input type="hidden" name="name" value="<?php echo $name; ?>">
    <input type="hidden" name="email" value="<?php echo $email; ?>">
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