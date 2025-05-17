<?php
function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

function isStudent() {
    return isLoggedIn() && $_SESSION['role'] === 'student';
}

function isStaff() {
    return isLoggedIn() && $_SESSION['role'] === 'staff';
}

function isAdmin() {
    return isLoggedIn() && $_SESSION['role'] === 'admin';
}

function redirectIfNotLoggedIn() {
    if (!isLoggedIn()) {
        header("Location: login.php");
        exit();
    }
}

function redirectBasedOnRole() {
    if (isLoggedIn()) {
        if (isAdmin()) {
            header("Location: admin_dashboard.php");
        } elseif (isStaff()) {
            header("Location: staff_dashboard.php");
        } elseif (isStudent()) {
            header("Location: dashboard.php");
        }
        exit();
    }
}
?>