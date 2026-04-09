<?php
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/config/current_user.php';

/* DELETE EMPLOYEE */
if (isset($_GET['delete_id'])) {
    $id = (int) $_GET['delete_id'];
    $sessionRole = (string) ($_SESSION['role'] ?? '');
    $franchiseeId = iv_current_session_franchisee_id();

    $lookupSql = "
        SELECT id, user_id, franchisee_id
        FROM employees
        WHERE id = ?
    ";
    if ($sessionRole !== 'master' && $franchiseeId !== null) {
        $lookupSql .= " AND franchisee_id = " . (int) $franchiseeId;
    }
    $lookupSql .= " LIMIT 1";

    $lookup = $conn->prepare($lookupSql);
    $lookup->bind_param("i", $id);
    $lookup->execute();
    $employeeToDelete = $lookup->get_result()->fetch_assoc();
    $lookup->close();

    if ($employeeToDelete) {
        $linkedUserId = !empty($employeeToDelete['user_id']) ? (int) $employeeToDelete['user_id'] : null;
        $currentUserId = (int) ($_SESSION['user_id'] ?? 0);

        if ($linkedUserId !== null && $linkedUserId === $currentUserId) {
            header("Location: employee.php?delete_error=self");
            exit;
        }

        $conn->begin_transaction();

        try {
            $projectEmployeeDelete = $conn->prepare("DELETE FROM project_employees WHERE employee_id = ?");
            $projectEmployeeDelete->bind_param("i", $id);
            if (!$projectEmployeeDelete->execute()) {
                throw new RuntimeException('Unable to remove project assignments for this employee.');
            }
            $projectEmployeeDelete->close();

            if ($linkedUserId !== null) {
                $unassignProjects = $conn->prepare("UPDATE projects SET assigned_user_id = NULL WHERE assigned_user_id = ?");
                $unassignProjects->bind_param("i", $linkedUserId);
                if (!$unassignProjects->execute()) {
                    throw new RuntimeException('Unable to unassign projects from the linked login account.');
                }
                $unassignProjects->close();
            }

            $deleteEmployee = $conn->prepare("DELETE FROM employees WHERE id = ?");
            $deleteEmployee->bind_param("i", $id);
            if (!$deleteEmployee->execute()) {
                throw new RuntimeException('Unable to delete the employee record.');
            }
            $deleteEmployee->close();

            if ($linkedUserId !== null) {
                $deleteUser = $conn->prepare("DELETE FROM users WHERE id = ?");
                $deleteUser->bind_param("i", $linkedUserId);
                if (!$deleteUser->execute()) {
                    throw new RuntimeException('Unable to delete the linked login account.');
                }
                $deleteUser->close();
            }

            $conn->commit();
            header("Location: employee.php?deleted=1");
            exit;
        } catch (Throwable $e) {
            $conn->rollback();
            header("Location: employee.php?delete_error=failed");
            exit;
        }
    }

    header("Location: employee.php?delete_error=missing");
    exit;
}

/* FETCH EMPLOYEES */
$employeeWhere = '1=1';
$franchiseeId = iv_current_session_franchisee_id();
if ((string) ($_SESSION['role'] ?? '') !== 'master' && $franchiseeId !== null) {
    $employeeWhere = 'e.franchisee_id = ' . (int) $franchiseeId;
}

$result = $conn->query("
    SELECT 
        e.*,
        u.username AS linked_username,
        u.role AS linked_role,
        COALESCE(f.franchisee_name, 'Not Assigned') AS franchisee_name,
        GROUP_CONCAT(
            CONCAT(p.id, '::', p.project_name) 
            ORDER BY p.created_at DESC 
            SEPARATOR '||'
        ) AS assigned_projects
    FROM employees e
    LEFT JOIN users u ON e.user_id = u.id
    LEFT JOIN franchisees f ON e.franchisee_id = f.id
    LEFT JOIN project_employees pe ON e.id = pe.employee_id
    LEFT JOIN projects p ON pe.project_id = p.id
    WHERE {$employeeWhere}
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
                                <?php if (isset($_GET['updated'])): ?>
                                    <div class="alert alert-success alert-dismissible fade show">
                                        Employee updated successfully!
                                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                                    </div>
                                <?php endif; ?>
                                <?php if (isset($_GET['deleted'])): ?>
                                    <div class="alert alert-success alert-dismissible fade show">
                                        Employee deleted successfully!
                                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                                    </div>
                                <?php endif; ?>
                                <?php if (isset($_GET['delete_error'])): ?>
                                    <div class="alert alert-danger alert-dismissible fade show">
                                        <?php
                                        $deleteError = (string) $_GET['delete_error'];
                                        echo match ($deleteError) {
                                            'self' => 'You cannot delete the employee record linked to your own logged-in account.',
                                            'missing' => 'Employee not found or you do not have permission to delete it.',
                                            default => 'Unable to delete this employee right now. Please try again.',
                                        };
                                        ?>
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
                                                <th>Franchisee</th>
                                                <th>Login Access</th>
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

                                                        <td><?= htmlspecialchars($row['franchisee_name']) ?></td>

                                                        <td>
                                                            <?php if (!empty($row['linked_username'])): ?>
                                                                <span class="badge bg-soft-success text-success">
                                                                    <?= htmlspecialchars($row['linked_username']) ?> (<?= htmlspecialchars(ucfirst((string) $row['linked_role'])) ?>)
                                                                </span>
                                                            <?php else: ?>
                                                                <span class="text-muted">No login</span>
                                                            <?php endif; ?>
                                                        </td>

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
