<?php
require_once 'config.php';
require_once 'auth.php';

if (!isAdmin()) {
    header("Location: ../login.php");
    exit();
}

// Get all users with their roles
$users = $pdo->query("
    SELECT u.id, u.name, u.email, u.role, 
           s.designation as staff_designation,
           st.enrollment_no, st.semester
    FROM users u
    LEFT JOIN staff s ON u.id = s.user_id
    LEFT JOIN students st ON u.id = st.user_id
    ORDER BY u.role, u.name
")->fetchAll();

// Process form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['delete_user'])) {
        $user_id = $_POST['user_id'];
        
        try {
            $pdo->beginTransaction();
            
            // Delete from users table (cascades to staff/students)
            $stmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
            $stmt->execute([$user_id]);
            
            $pdo->commit();
            $_SESSION['success'] = "User deleted successfully!";
        } catch (Exception $e) {
            $pdo->rollBack();
            $_SESSION['error'] = "Failed to delete user: " . $e->getMessage();
        }
        
        header("Location: users.php");
        exit();
    }
    
    if (isset($_POST['change_role'])) {
        $user_id = $_POST['user_id'];
        $new_role = $_POST['new_role'];
        
        try {
            $pdo->beginTransaction();
            
            // Update user role
            $stmt = $pdo->prepare("UPDATE users SET role = ? WHERE id = ?");
            $stmt->execute([$new_role, $user_id]);
            
            // If changing to staff, ensure record exists in staff table
            if ($new_role === 'staff') {
                $stmt = $pdo->prepare("INSERT IGNORE INTO staff (user_id, designation) VALUES (?, 'Staff Member')");
                $stmt->execute([$user_id]);
                
                // Remove from students table if exists
                $pdo->prepare("DELETE FROM students WHERE user_id = ?")->execute([$user_id]);
            }
            
            // If changing to student, ensure record exists in students table
            if ($new_role === 'student') {
                $enrollment_no = 'EN' . date('Y') . str_pad($user_id, 4, '0', STR_PAD_LEFT);
                $stmt = $pdo->prepare("INSERT IGNORE INTO students (user_id, enrollment_no, semester) VALUES (?, ?, 1)");
                $stmt->execute([$user_id, $enrollment_no]);
                
                // Remove from staff table if exists
                $pdo->prepare("DELETE FROM staff WHERE user_id = ?")->execute([$user_id]);
            }
            
            $pdo->commit();
            $_SESSION['success'] = "User role updated successfully!";
        } catch (Exception $e) {
            $pdo->rollBack();
            $_SESSION['error'] = "Failed to update user role: " . $e->getMessage();
        }
        
        header("Location: users.php");
        exit();
    }
}
?>

<?php include 'header.php'; ?>

<div class="card">
    <div class="card-header bg-primary text-white">
        <h2 class="mb-0">Manage Users</h2>
    </div>
    <div class="card-body">
        <?php if (isset($_SESSION['success'])): ?>
            <div class="alert alert-success"><?php echo $_SESSION['success']; unset($_SESSION['success']); ?></div>
        <?php endif; ?>
        
        <?php if (isset($_SESSION['error'])): ?>
            <div class="alert alert-danger"><?php echo $_SESSION['error']; unset($_SESSION['error']); ?></div>
        <?php endif; ?>
        
        <div class="table-responsive">
            <table class="table table-striped">
                <thead>
                    <tr>
                        <th>Name</th>
                        <th>Email</th>
                        <th>Role</th>
                        <th>Details</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($users as $user): ?>
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
                                <?php if ($user['role'] === 'staff'): ?>
                                    <?php echo htmlspecialchars($user['staff_designation']); ?>
                                <?php elseif ($user['role'] === 'student'): ?>
                                    Enrl: <?php echo htmlspecialchars($user['enrollment_no']); ?>, Sem: <?php echo htmlspecialchars($user['semester']); ?>
                                <?php endif; ?>
                            </td>
                            <td>
                                <div class="dropdown">
                                    <button class="btn btn-sm btn-secondary dropdown-toggle" type="button" data-bs-toggle="dropdown">
                                        Actions
                                    </button>
                                    <ul class="dropdown-menu">
                                        <li>
                                            <form method="POST" class="dropdown-item">
                                                <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                                                <select name="new_role" class="form-select mb-2" onchange="this.form.submit()">
                                                    <option value="">Change Role</option>
                                                    <option value="student" <?php echo $user['role'] === 'student' ? 'selected' : ''; ?>>Student</option>
                                                    <option value="staff" <?php echo $user['role'] === 'staff' ? 'selected' : ''; ?>>Staff</option>
                                                    <option value="admin" <?php echo $user['role'] === 'admin' ? 'selected' : ''; ?>>Admin</option>
                                                </select>
                                                <input type="hidden" name="change_role" value="1">
                                            </form>
                                        </li>
                                        <li>
                                            <form method="POST" class="dropdown-item" onsubmit="return confirm('Are you sure you want to delete this user?');">
                                                <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                                                <button type="submit" name="delete_user" class="btn btn-sm btn-danger w-100">Delete</button>
                                            </form>
                                        </li>
                                    </ul>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php include 'footer.php'; ?>