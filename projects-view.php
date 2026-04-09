<?php
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/config/user_management.php';
require_once __DIR__ . '/config/project_access.php';

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

if (!isset($_GET['id'])) {
    die('Project ID missing');
}

$project_id = (int) $_GET['id'];
$currentUserId = (int) ($_SESSION['user_id'] ?? 0);
$currentSessionRole = (string) ($_SESSION['role'] ?? '');
$canChangeProjectOwner = $currentSessionRole === 'master';
$canManageWorkingTeam = in_array($currentSessionRole, ['master', 'super', 'admin'], true);
$flash = null;
$error = null;
$billingTypeOptions = [
    'fixed' => 'Fixed Rate',
    'task_hours' => 'Tasks Hours',
    'project_hours' => 'Project Hours'
];

if (!isset($_SESSION['user_id'], $_SESSION['role'])) {
    header('Location: login.php');
    exit;
}

if (!iv_user_can_access_project($conn, $project_id, $currentUserId, $currentSessionRole)) {
    http_response_code(403);
    exit('Forbidden');
}

$assignableUsers = fetchAssignableUsersIndexed($conn, $currentUserId, $currentSessionRole);
$eligibleUserIds = array_keys($assignableUsers);
if ($currentUserId > 0 && !in_array($currentUserId, $eligibleUserIds, true)) {
    $eligibleUserIds[] = $currentUserId;
}
$eligibleUserIds = array_values(array_unique(array_map('intval', $eligibleUserIds)));

$eligibleEmployees = [];
if ($eligibleUserIds !== []) {
    $eligibleEmployeeResult = $conn->query("
        SELECT id, name, email, role, user_id
        FROM employees
        WHERE user_id IN (" . implode(',', $eligibleUserIds) . ")
        ORDER BY name ASC
    ");
    while ($eligibleEmployeeResult && $row = $eligibleEmployeeResult->fetch_assoc()) {
        $eligibleEmployees[] = $row;
    }
}

$eligibleEmployeesById = [];
foreach ($eligibleEmployees as $eligibleEmployee) {
    $eligibleEmployeesById[(int) $eligibleEmployee['id']] = $eligibleEmployee;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_project'])) {
    $project_name = trim($_POST['project_name'] ?? '');
    $customer_id = ($_POST['customer_id'] ?? '') !== '' ? (int) $_POST['customer_id'] : null;
    $related_invoice_id = ($_POST['related_invoice_id'] ?? '') !== '' ? (int) $_POST['related_invoice_id'] : null;
    $project_type = trim($_POST['project_type'] ?? '');
    $billing_type = trim($_POST['billing_type'] ?? '');
    $project_status = trim($_POST['project_status'] ?? '');
    $project_hours = (int) ($_POST['project_hours'] ?? 0);
    $start_date = $_POST['start_date'] ?? null;
    $end_date = $_POST['end_date'] ?? null;
    $description = trim($_POST['description'] ?? '');
    $progress = (int) ($_POST['progress'] ?? 0);
    $assigned_user_id = ($_POST['assigned_user_id'] ?? '') !== '' ? (int) $_POST['assigned_user_id'] : null;
    $franchisee_id = ($_POST['franchisee_id'] ?? '') !== '' ? (int) $_POST['franchisee_id'] : null;
    $team_member_ids = array_values(array_unique(array_map('intval', (array) ($_POST['employee_ids'] ?? []))));

    $allowedType = ['personal', 'team'];
    $allowedStatus = ['Not Started', 'In Progress', 'On Hold', 'Finished', 'Declined'];
    $customer_name = 'Internal Project';

    if ($project_name === '') {
        $error = 'Project name is required.';
    } elseif (!in_array(strtolower($project_type), $allowedType, true)) {
        $error = 'Invalid project type.';
    } elseif (!array_key_exists($billing_type, $billingTypeOptions)) {
        $error = 'Invalid billing type.';
    } elseif (!in_array($project_status, $allowedStatus, true)) {
        $error = 'Invalid project status.';
    } elseif ($progress < 0 || $progress > 100) {
        $error = 'Progress must be between 0 and 100.';
    } elseif ($start_date && $end_date && $start_date > $end_date) {
        $error = 'Start date cannot be after end date.';
    } else {
        if ($customer_id !== null) {
            $customerCheck = $conn->prepare("SELECT customer_name FROM customers WHERE id = ? LIMIT 1");
            $customerCheck->bind_param("i", $customer_id);
            $customerCheck->execute();
            $customerRow = $customerCheck->get_result()->fetch_assoc();
            $customerCheck->close();

            if (!$customerRow) {
                $error = 'Invalid client selected.';
            } else {
                $customer_name = (string) $customerRow['customer_name'];
            }
        }

        if ($error === null && $related_invoice_id !== null) {
            $invoiceCheck = $conn->prepare("SELECT to_name FROM invoices WHERE id = ? LIMIT 1");
            $invoiceCheck->bind_param("i", $related_invoice_id);
            $invoiceCheck->execute();
            $invoiceRow = $invoiceCheck->get_result()->fetch_assoc();
            $invoiceCheck->close();

            if (!$invoiceRow) {
                $error = 'Invalid invoice selected.';
            } elseif ($customer_id === null && !empty($invoiceRow['to_name'])) {
                $customer_name = (string) $invoiceRow['to_name'];
            }
        }

        if ($franchisee_id !== null) {
            $franchiseeCheck = $conn->prepare("SELECT id FROM franchisees WHERE id = ? LIMIT 1");
            $franchiseeCheck->bind_param("i", $franchisee_id);
            $franchiseeCheck->execute();
            $franchiseeExists = $franchiseeCheck->get_result()->fetch_assoc();
            $franchiseeCheck->close();

            if (!$franchiseeExists) {
                $error = 'Invalid franchisee selected.';
            }
        }
    }

    if (!$canChangeProjectOwner) {
        $assigned_user_id = isset($project['assigned_user_id']) ? (int) $project['assigned_user_id'] : null;
    }

    if (!$canManageWorkingTeam) {
        $team_member_ids = [];
        $existingTeamStmt = $conn->prepare("SELECT employee_id FROM project_employees WHERE project_id = ?");
        $existingTeamStmt->bind_param("i", $project_id);
        $existingTeamStmt->execute();
        $existingTeamResult = $existingTeamStmt->get_result();
        while ($existingTeamResult && $row = $existingTeamResult->fetch_assoc()) {
            $team_member_ids[] = (int) $row['employee_id'];
        }
        $existingTeamStmt->close();
    } else {
        foreach ($team_member_ids as $teamMemberId) {
            if (!isset($eligibleEmployeesById[$teamMemberId])) {
                $error = 'Invalid team member selected.';
                break;
            }
        }
    }

    if ($error === null) {
        $conn->begin_transaction();

        try {
            $sql = "UPDATE projects
                    SET project_name = ?, customer_name = ?, customer_id = ?, related_invoice_id = ?, project_type = ?, billing_type = ?,
                        project_status = ?, project_hours = ?, start_date = ?, end_date = ?,
                        description = ?, progress = ?, assigned_user_id = ?, franchisee_id = ?
                    WHERE id = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param(
                "ssiisssisssiiii",
                $project_name,
                $customer_name,
                $customer_id,
                $related_invoice_id,
                $project_type,
                $billing_type,
                $project_status,
                $project_hours,
                $start_date,
                $end_date,
                $description,
                $progress,
                $assigned_user_id,
                $franchisee_id,
                $project_id
            );
            $stmt->execute();

            if ($stmt->error) {
                throw new RuntimeException('Failed to update project.');
            }
            $stmt->close();

            $deleteTeamStmt = $conn->prepare("DELETE FROM project_employees WHERE project_id = ?");
            $deleteTeamStmt->bind_param("i", $project_id);
            if (!$deleteTeamStmt->execute()) {
                throw new RuntimeException('Failed to reset project team.');
            }
            $deleteTeamStmt->close();

            if ($team_member_ids !== []) {
                $insertTeamStmt = $conn->prepare("INSERT INTO project_employees (project_id, employee_id) VALUES (?, ?)");
                foreach ($team_member_ids as $teamMemberId) {
                    $insertTeamStmt->bind_param("ii", $project_id, $teamMemberId);
                    if (!$insertTeamStmt->execute()) {
                        throw new RuntimeException('Failed to save project team.');
                    }
                }
                $insertTeamStmt->close();
            }

            $conn->commit();
            header("Location: projects-view.php?id={$project_id}&updated=1");
            exit;
        } catch (Throwable $e) {
            $conn->rollback();
            $error = $e->getMessage();
        }
    }
}

if (isset($_GET['updated']) && $_GET['updated'] === '1') {
    $flash = 'Project updated successfully.';
}

$projectStmt = $conn->prepare("
    SELECT
        p.*,
        u.username AS assigned_employee_name,
        f.franchisee_name,
        c.customer_name AS linked_customer_name,
        i.invoice_number AS linked_invoice_number
    FROM projects p
    LEFT JOIN users u ON p.assigned_user_id = u.id
    LEFT JOIN franchisees f ON p.franchisee_id = f.id
    LEFT JOIN customers c ON p.customer_id = c.id
    LEFT JOIN invoices i ON p.related_invoice_id = i.id
    WHERE p.id = ?
");
$projectStmt->bind_param("i", $project_id);
$projectStmt->execute();
$projectResult = $projectStmt->get_result();

if ($projectResult->num_rows === 0) {
    die('Project not found');
}

$project = $projectResult->fetch_assoc();
$currentBillingType = (string) ($project['billing_type'] ?? '');
if ($currentBillingType === 'fixed_rate') {
    $currentBillingType = 'fixed';
}
if (!array_key_exists($currentBillingType, $billingTypeOptions)) {
    $currentBillingType = 'project_hours';
}

$employees = [];
$employeeResult = $conn->query("SELECT id, username AS name FROM users ORDER BY username");
if ($employeeResult) {
    while ($row = $employeeResult->fetch_assoc()) {
        $employees[] = $row;
    }
}

$customers = [];
$customerResult = $conn->query("SELECT id, customer_name, company_name FROM customers ORDER BY customer_name");
if ($customerResult) {
    while ($row = $customerResult->fetch_assoc()) {
        $customers[] = $row;
    }
}

$invoices = [];
$invoiceResult = $conn->query("SELECT id, invoice_number, to_name FROM invoices ORDER BY id DESC");
if ($invoiceResult) {
    while ($row = $invoiceResult->fetch_assoc()) {
        $invoices[] = $row;
    }
}

$franchisees = [];
$franchiseeResult = $conn->query("SELECT id, franchisee_name, franchisee_code FROM franchisees ORDER BY franchisee_name");
if ($franchiseeResult) {
    while ($row = $franchiseeResult->fetch_assoc()) {
        $franchisees[] = $row;
    }
}

$workingTeam = [];
$workingTeamStmt = $conn->prepare("
    SELECT e.id, e.name, e.email, e.role, u.username AS linked_username
    FROM project_employees pe
    INNER JOIN employees e ON e.id = pe.employee_id
    LEFT JOIN users u ON u.id = e.user_id
    WHERE pe.project_id = ?
    ORDER BY e.name ASC
");
$workingTeamStmt->bind_param("i", $project_id);
$workingTeamStmt->execute();
$workingTeamResult = $workingTeamStmt->get_result();
while ($workingTeamResult && $row = $workingTeamResult->fetch_assoc()) {
    $workingTeam[] = $row;
}
$workingTeamStmt->close();
$selectedTeamMemberIds = array_map(
    static fn(array $row): int => (int) $row['id'],
    $workingTeam
);
$assignedOwnerEmployeeId = null;
if (!empty($project['assigned_user_id'])) {
    $assignedOwnerEmployeeStmt = $conn->prepare("SELECT id FROM employees WHERE user_id = ? LIMIT 1");
    $assignedOwnerEmployeeStmt->bind_param("i", $project['assigned_user_id']);
    $assignedOwnerEmployeeStmt->execute();
    $assignedOwnerEmployeeRow = $assignedOwnerEmployeeStmt->get_result()->fetch_assoc();
    $assignedOwnerEmployeeStmt->close();
    if ($assignedOwnerEmployeeRow) {
        $assignedOwnerEmployeeId = (int) $assignedOwnerEmployeeRow['id'];
    }
}

$statusClassMap = [
    'Not Started' => 'secondary',
    'In Progress' => 'primary',
    'On Hold' => 'warning',
    'Finished' => 'success',
    'Declined' => 'danger'
];
$statusClass = $statusClassMap[$project['project_status']] ?? 'secondary';
$progressValue = isset($project['progress']) ? (int) $project['progress'] : 0;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Project View</title>
    <link rel="shortcut icon" href="assets/images/favicon.ico">
    <link rel="stylesheet" href="assets/css/bootstrap.min.css">
    <link rel="stylesheet" href="assets/css/theme.min.css">
    <style>
        .iv-team-picker {
            border: 1px solid #dce6f4;
            border-radius: 16px;
            background: #fff;
            padding: 1rem;
            display: grid;
            gap: 0.9rem;
        }
        .iv-team-chip-list {
            min-height: 52px;
            border: 1px dashed #cbd5e1;
            border-radius: 14px;
            padding: 0.8rem;
            display: flex;
            flex-wrap: wrap;
            gap: 0.65rem;
            align-items: center;
        }
        .iv-team-chip {
            display: inline-flex;
            align-items: center;
            gap: 0.45rem;
            border-radius: 999px;
            background: #e0ecff;
            color: #123c69;
            padding: 0.4rem 0.75rem;
            font-weight: 600;
        }
        .iv-team-chip-remove {
            border: 0;
            background: transparent;
            color: inherit;
            font-size: 1rem;
            line-height: 1;
            padding: 0;
            cursor: pointer;
        }
        .iv-team-empty {
            color: #64748b;
            font-size: 0.92rem;
        }
    </style>
</head>
<body>
<?php include 'sidebar.php'; ?>

<main class="nxl-container">
<div class="nxl-content">

<div class="page-header d-flex justify-content-between align-items-center mb-4">
    <div>
        <h5><?= htmlspecialchars($project['project_name']) ?></h5>
        <small class="text-muted">
            <?= $project['start_date'] ? date('d M Y', strtotime($project['start_date'])) : '-' ?>
            to
            <?= $project['end_date'] ? date('d M Y', strtotime($project['end_date'])) : '-' ?>
        </small>
    </div>
    <div class="d-flex gap-2">
        <a href="projects-create.php" class="btn btn-primary">
            <i class="feather-plus me-1"></i> Create Project
        </a>
        <a href="project-pdf.php?id=<?= $project_id ?>" class="btn btn-outline-dark">
            <i class="feather-download me-1"></i> Download PDF
        </a>
    </div>
</div>

<?php if ($flash): ?>
    <div class="alert alert-success"><?= htmlspecialchars($flash) ?></div>
<?php endif; ?>
<?php if ($error): ?>
    <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
<?php endif; ?>

<form method="POST">
<input type="hidden" name="save_project" value="1">

<div class="card mb-4">
<div class="card-body">
<div class="mb-4">
    <img src="assets/images/Logo_IV.png" style="max-height:60px" alt="Logo">
    <h5 class="mt-2 mb-0">Infinite Vision</h5>
    <small class="text-muted">Project Report</small>
</div>

<div class="row mb-4">
    <div class="col-md-6">
        <label class="form-label">Project Name</label>
        <input type="text" name="project_name" class="form-control mb-3" value="<?= htmlspecialchars($project['project_name']) ?>" required>

        <label class="form-label">Client</label>
        <select name="customer_id" class="form-select mb-3">
            <option value="">Internal or unlinked project</option>
            <?php foreach ($customers as $customer): ?>
                <option value="<?= (int) $customer['id'] ?>" <?= (int) ($project['customer_id'] ?? 0) === (int) $customer['id'] ? 'selected' : '' ?>>
                    <?= htmlspecialchars((string) $customer['customer_name']) ?><?php if (!empty($customer['company_name'])): ?> - <?= htmlspecialchars((string) $customer['company_name']) ?><?php endif; ?>
                </option>
            <?php endforeach; ?>
        </select>

        <div class="mb-3">
            <strong>Current Client:</strong>
            <span class="badge bg-light text-dark border">
                <?= !empty($project['linked_customer_name']) ? htmlspecialchars((string) $project['linked_customer_name']) : htmlspecialchars((string) ($project['customer_name'] ?: 'Internal Project')) ?>
            </span>
        </div>

        <label class="form-label">IMS Invoice</label>
        <select name="related_invoice_id" class="form-select mb-3">
            <option value="">Not linked yet</option>
            <?php foreach ($invoices as $invoice): ?>
                <option value="<?= (int) $invoice['id'] ?>" <?= (int) ($project['related_invoice_id'] ?? 0) === (int) $invoice['id'] ? 'selected' : '' ?>>
                    <?= htmlspecialchars((string) ($invoice['invoice_number'] ?: 'No invoice number')) ?> - <?= htmlspecialchars((string) ($invoice['to_name'] ?: 'Unnamed client')) ?>
                </option>
            <?php endforeach; ?>
        </select>

        <div class="mb-3">
            <strong>Current Invoice:</strong>
            <span class="badge bg-light text-dark border">
                <?= !empty($project['linked_invoice_number']) ? htmlspecialchars((string) $project['linked_invoice_number']) : 'Not linked' ?>
            </span>
        </div>

        <label class="form-label">Project Type</label>
        <select name="project_type" class="form-select mb-3" required>
            <option value="personal" <?= strtolower((string)$project['project_type']) === 'personal' ? 'selected' : '' ?>>Personal</option>
            <option value="team" <?= strtolower((string)$project['project_type']) === 'team' ? 'selected' : '' ?>>Team</option>
        </select>

        <label class="form-label">Billing Type</label>
        <select name="billing_type" class="form-select mb-3" required>
            <?php foreach ($billingTypeOptions as $value => $label): ?>
                <option value="<?= $value ?>" <?= $currentBillingType === $value ? 'selected' : '' ?>>
                    <?= htmlspecialchars($label) ?>
                </option>
            <?php endforeach; ?>
        </select>
    </div>

    <div class="col-md-6">
        <label class="form-label">Status</label>
        <select name="project_status" class="form-select mb-3" required>
            <?php foreach (['Not Started', 'In Progress', 'On Hold', 'Finished', 'Declined'] as $status): ?>
                <option value="<?= $status ?>" <?= $project['project_status'] === $status ? 'selected' : '' ?>><?= $status ?></option>
            <?php endforeach; ?>
        </select>

        <label class="form-label">Project Hours</label>
        <input type="number" name="project_hours" min="0" class="form-control mb-3" value="<?= (int)$project['project_hours'] ?>">

        <label class="form-label">Assigned To</label>
        <?php if ($canChangeProjectOwner): ?>
            <select name="assigned_user_id" class="form-select mb-3">
                <option value="">Unassigned</option>
                <?php foreach ($employees as $employee): ?>
                    <option value="<?= (int)$employee['id'] ?>" <?= (int)$project['assigned_user_id'] === (int)$employee['id'] ? 'selected' : '' ?>>
                        <?= htmlspecialchars($employee['name']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        <?php else: ?>
            <input type="hidden" name="assigned_user_id" value="<?= htmlspecialchars((string) ($project['assigned_user_id'] ?? '')) ?>">
            <input type="text" class="form-control mb-3" value="<?= $project['assigned_employee_name'] ? htmlspecialchars($project['assigned_employee_name']) : 'Unassigned' ?>" readonly>
            <div class="form-text">Only master can change the project owner.</div>
        <?php endif; ?>

        <div class="mb-3">
            <strong>Currently Assigned:</strong>
            <span class="badge bg-light text-dark border">
                <?= $project['assigned_employee_name'] ? htmlspecialchars($project['assigned_employee_name']) : 'Unassigned' ?>
            </span>
        </div>

        <label class="form-label">Franchisee</label>
        <select name="franchisee_id" class="form-select mb-3">
            <option value="">No franchisee</option>
            <?php foreach ($franchisees as $franchisee): ?>
                <option value="<?= (int)$franchisee['id'] ?>" <?= (int) ($project['franchisee_id'] ?? 0) === (int) $franchisee['id'] ? 'selected' : '' ?>>
                    <?= htmlspecialchars((string) $franchisee['franchisee_name']) ?> (<?= htmlspecialchars((string) $franchisee['franchisee_code']) ?>)
                </option>
            <?php endforeach; ?>
        </select>

        <div class="mb-3">
            <strong>Current Franchisee:</strong>
            <span class="badge bg-light text-dark border">
                <?= !empty($project['franchisee_name']) ? htmlspecialchars((string) $project['franchisee_name']) : 'Not assigned' ?>
            </span>
        </div>
    </div>
</div>

<div class="row mb-3">
    <div class="col-md-6">
        <label class="form-label">Start Date</label>
        <input type="date" name="start_date" class="form-control" value="<?= htmlspecialchars((string)$project['start_date']) ?>">
    </div>
    <div class="col-md-6">
        <label class="form-label">End Date</label>
        <input type="date" name="end_date" class="form-control" value="<?= htmlspecialchars((string)$project['end_date']) ?>">
    </div>
</div>

<div class="mb-2 d-flex justify-content-between align-items-center">
    <span>Project Progress</span>
    <span id="progressValue"><?= $progressValue ?>%</span>
</div>
<input type="range" class="form-range mb-3" min="0" max="100" step="1" name="progress" id="progressSlider" value="<?= $progressValue ?>">

<div class="progress mb-4" style="height:6px;">
    <div id="progressBar" class="progress-bar bg-primary" style="width:<?= $progressValue ?>%"></div>
</div>

<div class="mb-3">
    <strong>Status:</strong>
    <span class="badge bg-<?= $statusClass ?>"><?= htmlspecialchars($project['project_status']) ?></span>
</div>

<button type="submit" class="btn btn-primary">Save Changes</button>
</div>
</div>

<div class="card">
<div class="card-body">
    <h6 class="fw-bold mb-2">Working Team</h6>
    <p class="text-muted mb-3">Employees and users assigned to work on this project.</p>
    <?php if ($canManageWorkingTeam): ?>
        <div class="iv-team-picker mb-4">
            <?php if ($assignedOwnerEmployeeId !== null): ?>
                <div class="form-text">Project owner and working team are separate. You can remove the owner from the working team without changing ownership.</div>
            <?php endif; ?>
            <div class="iv-team-chip-list" id="teamMemberChips">
                <?php if ($workingTeam === []): ?>
                    <span class="iv-team-empty">No team members selected yet.</span>
                <?php else: ?>
                    <?php foreach ($workingTeam as $teamMember): ?>
                        <span class="iv-team-chip">
                            <span><?= htmlspecialchars((string) $teamMember['name']) ?></span>
                            <button type="button" class="iv-team-chip-remove" data-employee-id="<?= (int) $teamMember['id'] ?>" aria-label="Remove <?= htmlspecialchars((string) $teamMember['name']) ?>">&times;</button>
                        </span>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
            <select class="form-select" id="teamMemberPicker">
                <option value="">Add team member</option>
                <?php foreach ($eligibleEmployees as $eligibleEmployee): ?>
                    <option value="<?= (int) $eligibleEmployee['id'] ?>">
                        <?= htmlspecialchars((string) $eligibleEmployee['name']) ?><?php if (!empty($eligibleEmployee['email'])): ?> - <?= htmlspecialchars((string) $eligibleEmployee['email']) ?><?php endif; ?>
                    </option>
                <?php endforeach; ?>
            </select>
            <select class="form-select d-none" id="teamMembersSelect" name="employee_ids[]" multiple size="<?= max(4, min(8, max(1, count($eligibleEmployees)))) ?>" aria-hidden="true" tabindex="-1">
                <?php foreach ($eligibleEmployees as $eligibleEmployee): ?>
                    <option value="<?= (int) $eligibleEmployee['id'] ?>" <?= in_array((int) $eligibleEmployee['id'], $selectedTeamMemberIds, true) ? 'selected' : '' ?>>
                        <?= htmlspecialchars((string) $eligibleEmployee['name']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
            <?php if ($eligibleEmployees === []): ?>
                <div class="form-text">No eligible employee profiles found for your current hierarchy yet.</div>
            <?php endif; ?>
        </div>
    <?php else: ?>
        <?php if ($workingTeam === []): ?>
            <div class="text-muted mb-4">No team members assigned yet.</div>
        <?php else: ?>
            <div class="row g-3 mb-4">
                <?php foreach ($workingTeam as $teamMember): ?>
                    <div class="col-md-6 col-xl-4">
                        <div class="border rounded-3 p-3 h-100 bg-light-subtle">
                            <div class="fw-semibold text-dark"><?= htmlspecialchars((string) $teamMember['name']) ?></div>
                            <div class="small text-muted"><?= htmlspecialchars((string) ($teamMember['email'] ?: 'No email')) ?></div>
                            <div class="mt-2 d-flex flex-wrap gap-2">
                                <span class="badge bg-light text-dark border"><?= htmlspecialchars((string) ($teamMember['role'] ?: 'No job role')) ?></span>
                                <?php if (!empty($teamMember['linked_username'])): ?>
                                    <span class="badge bg-soft-success text-success"><?= htmlspecialchars((string) $teamMember['linked_username']) ?></span>
                                <?php else: ?>
                                    <span class="badge bg-light text-muted border">No login</span>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    <?php endif; ?>

    <h6 class="fw-bold">Project Description</h6>
    <textarea class="form-control" name="description" rows="6"><?= htmlspecialchars((string)$project['description']) ?></textarea>
</div>
</div>

</form>

</div>
</main>

<script src="assets/vendors/js/vendors.min.js"></script>
<script src="assets/js/common-init.min.js"></script>
<script src="assets/js/theme-customizer-init.min.js"></script>
<script>
const slider = document.getElementById('progressSlider');
const progressBar = document.getElementById('progressBar');
const progressValue = document.getElementById('progressValue');
const teamMembersSelect = document.getElementById('teamMembersSelect');
const teamMemberPicker = document.getElementById('teamMemberPicker');
const teamMemberChips = document.getElementById('teamMemberChips');

if (slider && progressBar && progressValue) {
    slider.addEventListener('input', function () {
        const value = Number(this.value) || 0;
        progressBar.style.width = value + '%';
        progressValue.textContent = value + '%';
    });
}

function renderTeamMemberChips() {
    if (!teamMembersSelect || !teamMemberChips) {
        return;
    }

    const selectedOptions = Array.from(teamMembersSelect.options).filter(function (option) {
        return option.selected;
    });

    if (selectedOptions.length === 0) {
        teamMemberChips.innerHTML = '<span class="iv-team-empty">No team members selected yet.</span>';
        return;
    }

    teamMemberChips.innerHTML = selectedOptions.map(function (option) {
        return '<span class="iv-team-chip">' +
            '<span>' + option.text.replace(/</g, '&lt;').replace(/>/g, '&gt;') + '</span>' +
            '<button type="button" class="iv-team-chip-remove" data-employee-id="' + option.value + '" aria-label="Remove ' + option.text.replace(/"/g, '&quot;') + '">&times;</button>' +
        '</span>';
    }).join('');
}

if (teamMembersSelect && teamMemberPicker && teamMemberChips) {
    teamMemberPicker.addEventListener('change', function () {
        const selectedValue = this.value;
        if (!selectedValue) {
            return;
        }

        Array.from(teamMembersSelect.options).forEach(function (option) {
            if (option.value === selectedValue) {
                option.selected = true;
            }
        });

        this.value = '';
        renderTeamMemberChips();
    });

    teamMemberChips.addEventListener('click', function (event) {
        const removeButton = event.target.closest('.iv-team-chip-remove');
        if (!removeButton) {
            return;
        }

        const employeeId = removeButton.getAttribute('data-employee-id');
        Array.from(teamMembersSelect.options).forEach(function (option) {
            if (option.value === employeeId) {
                option.selected = false;
            }
        });

        renderTeamMemberChips();
    });

    renderTeamMemberChips();
}
</script>
</body>
</html>
