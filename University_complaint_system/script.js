document.getElementById('loginForm').addEventListener('submit', function (e) {
    e.preventDefault();

    const name = document.getElementById('name').value.trim();
    const department = document.getElementById('department').value.trim();
    const idNumber = document.getElementById('idNumber').value.trim();
    const role = document.getElementById('role').value;

    if (!name || !department || !idNumber || !role) {
        alert("All fields are required!");
        return;
    }

    // For now just show input (will send to PHP backend later)
    alert(`Welcome ${name}! Role: ${role}`);

    // Later: send data using fetch() or form action to PHP script
});
