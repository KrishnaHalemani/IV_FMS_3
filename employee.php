<?php
require_once __DIR__ . '/config/db.php';

/* DELETE EMPLOYEE */
if (isset($_GET['delete_id'])) {
    $id = (int) $_GET['delete_id'];

    $stmt = $conn->prepare("DELETE FROM employees WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();

    header("Location: employee.php");
    exit;
}

/* FETCH EMPLOYEES */
$result = $conn->query("
    SELECT 
        e.*,
        GROUP_CONCAT(
            CONCAT(p.id, '::', p.project_name) 
            ORDER BY p.created_at DESC 
            SEPARATOR '||'
        ) AS assigned_projects
    FROM employees e
    LEFT JOIN project_employees pe ON e.id = pe.employee_id
    LEFT JOIN projects p ON pe.project_id = p.id
    GROUP BY e.id
    ORDER BY e.id DESC
");
?>

<!DOCTYPE html>
<html lang="zxx">

<head>
    <meta charset="utf-8">
    <title>IV || Employees</title>

    <link rel="shortcut icon" href="assets/images/favicon.ico">
    <link rel="stylesheet" href="assets/css/bootstrap.min.css">
    <link rel="stylesheet" href="assets/vendors/css/vendors.min.css">
    <link rel="stylesheet" href="assets/vendors/css/dataTables.bs5.min.css">
    <link rel="stylesheet" href="assets/css/theme.min.css">
    <style>
        .project-dropdown {
            position: relative;
            display: inline-block;
        }

        .project-header {
            background: #e9f2ff;
            color: #0d6efd;
            padding: 4px 8px;
            border-radius: 4px;
            cursor: pointer;
            font-size: 13px;
        }

        .project-list {
            display: none;
            position: absolute;
            background: #fff;
            border: 1px solid #ddd;
            list-style: none;
            padding: 5px 0;
            margin-top: 4px;
            min-width: 180px;
            z-index: 9999;
            border-radius: 4px;
        }

        .project-list li {
            padding: 6px 12px;
        }

        .project-list li a {
            text-decoration: none;
            color: #333;
            display: block;
        }

        .project-list li:hover {
            background: #f1f1f1;
        }
    </style>

</head>

<body>

    <?php include 'sidebar.php'; ?>

    <main class="nxl-container">
        <div class="nxl-content">

            <div class="page-header">
                <div class="page-header-left">
                    <h5 class="m-b-10">Employees</h5>
                </div>
                <div class="page-header-right">
                    <a href="employee-create.php" class="btn btn-primary">
                        + Add Employee
                    </a>
                </div>
            </div>

            <div class="main-content">
                <div class="row">
                    <div class="col-lg-12">

                        <div class="card stretch stretch-full">
                            <div class="card-body p-3">
                                <?php if (isset($_GET['created'])): ?>
                                    <div class="alert alert-success alert-dismissible fade show">
                                        Employee created successfully!
                                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                                    </div>
                                <?php endif; ?>
                                <div class="table-responsive">

                                    <table class="table table-hover" id="employeeList">

                                        <thead>
                                            <tr>
                                                <th></th>
                                                <th>Name</th>
                                                <th>Email</th>
                                                <th>Phone</th>
                                                <th>Date</th>
                                                <th>Projects</th>
                                                <th>Delete</th>
                                            </tr>
                                        </thead>

                                        <tbody>
                                            <?php if ($result && $result->num_rows > 0): ?>
                                                <?php while ($row = $result->fetch_assoc()): ?>
                                                    <tr>
                                                        <td><input type="checkbox"></td>

                                                        <td><?= htmlspecialchars($row['name']) ?></td>

                                                        <td><?= htmlspecialchars($row['email']) ?></td>

                                                        <td><?= htmlspecialchars($row['phone'] ?? '') ?></td>

                                                        <td><?= date('d M Y', strtotime($row['created_at'])) ?></td>


                                                        <td>
                                                            <?php
                                                            if (!empty($row['assigned_projects'])) {

                                                                $projects = explode('||', $row['assigned_projects']);
                                                                $latestProject = explode('::', $projects[0]);
                                                                $latestName = $latestProject[1];

                                                                $employeeId = $row['id'];
                                                            ?>

                                                                <div class="project-dropdown">
                                                                    <div class="project-header" onclick="toggleProjectList(<?= $employeeId ?>)">
                                                                        <?= htmlspecialchars($latestName) ?> ▼
                                                                    </div>

                                                                    <ul class="project-list" id="project-list-<?= $employeeId ?>">
                                                                        <?php foreach ($projects as $project):
                                                                            list($projectId, $projectName) = explode('::', $project);
                                                                        ?>
                                                                            <li>
                                                                                <a href="projects-view.php?id=<?= $projectId ?>">
                                                                                    <?= htmlspecialchars($projectName) ?>
                                                                                </a>
                                                                            </li>
                                                                        <?php endforeach; ?>
                                                                    </ul>
                                                                </div>

                                                            <?php
                                                            } else {
                                                                echo '<span class="text-muted">Not Assigned</span>';
                                                            }
                                                            ?>
                                                        </td>



                                                        <td class="text-end">

                                                            <a href="employee-edit.php?id=<?= $row['id'] ?>"
                                                                class="btn btn-sm btn-warning">
                                                                <i class="feather-edit"></i>
                                                            </a>

                                                            <a href="?delete_id=<?= $row['id'] ?>"
                                                                onclick="return confirm('Delete this employee?')"
                                                                class="btn btn-sm btn-danger">
                                                                <i class="feather-trash-2"></i>
                                                            </a>

                                                        </td>
                                                    </tr>
                                                <?php endwhile; ?>
                                            <?php endif; ?>
                                        </tbody>


                                    </table>

                                </div>
                            </div>
                        </div>

                    </div>
                </div>
            </div>

        </div>

        <?php include 'footer.php'; ?>
    </main>

    <script src="assets/vendors/js/vendors.min.js"></script>
    <script src="assets/vendors/js/dataTables.min.js"></script>
    <script src="assets/vendors/js/dataTables.bs5.min.js"></script>
    <script src="assets/js/bootstrap.bundle.min.js"></script>


    <script>
        $(document).ready(function() {
            $('#employeeList').DataTable({
                destroy: true,
                autoWidth: false
            });
        });
    </script>
    <script>
        function toggleProjectList(id) {

            var list = document.getElementById("project-list-" + id);

            if (!list) return;

            var isOpen = list.style.display === "block";

            // close all first
            document.querySelectorAll(".project-list").forEach(function(el) {
                el.style.display = "none";
            });

            list.style.display = isOpen ? "none" : "block";
        }

        // close when clicking outside
        document.addEventListener("click", function(e) {
            if (!e.target.closest(".project-dropdown")) {
                document.querySelectorAll(".project-list").forEach(function(el) {
                    el.style.display = "none";
                });
            }
        });
    </script>


</body>

</html>