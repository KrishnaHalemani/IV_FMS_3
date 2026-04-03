<?php
require '../config/db.php';
?>
<!DOCTYPE html>
<html>
<head>
    <title>Create Project</title>
</head>
<body>

<h2>Create Project (Basic)</h2>

<form method="POST" action="projects-store.php">

    <label>Project Name *</label><br>
    <input type="text" name="project_name" required><br><br>

    <label>Project Type *</label><br>
    <select name="project_type" required>
        <option value="">Select</option>
        <option value="personal">Personal</option>
        <option value="team">Team</option>
    </select><br><br>

    <label>Project Status *</label><br>
    <select name="project_status" required>
        <option value="Active">Active</option>
        <option value="Inactive">Inactive</option>
    </select><br><br>

    <label>Customer *</label><br>
    <select name="customer_id" required>
        <option value="">Select Customer</option>
        <?php
        $q = mysqli_query($conn, "SELECT id, customer_name FROM customers");
        while ($c = mysqli_fetch_assoc($q)) {
            echo "<option value='{$c['id']}'>{$c['customer_name']}</option>";
        }
        ?>
    </select><br><br>

    <label>Release Date *</label><br>
    <input type="date" name="release_date" required><br><br>

    <button type="submit">Create Project</button>
</form>

</body>
</html>
