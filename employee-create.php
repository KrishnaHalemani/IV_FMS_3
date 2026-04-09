<?php
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/config/user_management.php';
require_once __DIR__ . '/config/current_user.php';

$error = "";
$success = "";

$name = $email = $phone = $role = $status = "";
$franchisee_id = "";
$create_login_account = false;
$login_username = "";
$login_role = "user";
$jobRoleOptions = ['Developer', 'Designer', 'Manager', 'QA'];
$sessionRole = (string) ($_SESSION['role'] ?? '');
$lockedFranchiseeId = $sessionRole !== 'master' ? iv_current_session_franchisee_id() : null;
$lockedFranchisee = null;

$franchisees = [];
$franchiseeSql = "SELECT id, franchisee_name, franchisee_code FROM franchisees WHERE status = 'Active'";
if ($lockedFranchiseeId !== null) {
    $franchiseeSql .= " AND id = " . (int) $lockedFranchiseeId;
}
$franchiseeSql .= " ORDER BY franchisee_name";
$franchiseeResult = $conn->query($franchiseeSql);
while ($franchiseeResult && $row = $franchiseeResult->fetch_assoc()) {
    $franchisees[] = $row;
    if ($lockedFranchiseeId !== null && (int) $row['id'] === $lockedFranchiseeId) {
        $lockedFranchisee = $row;
    }
}

if ($lockedFranchiseeId !== null) {
    $franchisee_id = (string) $lockedFranchiseeId;
}

$allowedSystemRoles = isset($_SESSION['role']) ? getCreatableRoles((string) $_SESSION['role']) : [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim((string) ($_POST['name'] ?? ''));
    $email = trim((string) ($_POST['email'] ?? ''));
    $phone = trim((string) ($_POST['phone'] ?? ''));
    $role = trim((string) ($_POST['role'] ?? ''));
    $status = trim((string) ($_POST['status'] ?? 'Active'));
    if ($lockedFranchiseeId !== null) {
        $franchisee_id = (string) $lockedFranchiseeId;
    } else {
        $franchisee_id = ($_POST['franchisee_id'] ?? '') !== '' ? (string) (int) $_POST['franchisee_id'] : '';
    }

    $create_login_account = isset($_POST['create_login_account']);
    $login_username = trim((string) ($_POST['login_username'] ?? ''));
    $login_role = trim((string) ($_POST['login_role'] ?? 'user'));
    $login_password = (string) ($_POST['login_password'] ?? '');
    $login_confirm_password = (string) ($_POST['login_confirm_password'] ?? '');

    if ($name === '' || $email === '' || $role === '') {
        $error = "Please fill all required fields.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Please enter a valid employee email address.";
    } else {
        $check = $conn->prepare("SELECT id FROM employees WHERE email = ? LIMIT 1");
        $check->bind_param("s", $email);
        $check->execute();
        $check->store_result();

        if ($franchisee_id !== '') {
            $franchiseeIdValue = (int) $franchisee_id;
            $franchiseeCheck = $conn->prepare("SELECT id FROM franchisees WHERE id = ? LIMIT 1");
            $franchiseeCheck->bind_param("i", $franchiseeIdValue);
            $franchiseeCheck->execute();
            $franchiseeCheck->store_result();
            if ($franchiseeCheck->num_rows === 0) {
                $error = "Selected franchisee does not exist.";
            }
            $franchiseeCheck->close();
        }

        if ($error === "" && $create_login_account) {
            if ($login_username === '') {
                $error = "Username is required when creating a login account.";
            } elseif ($login_password !== $login_confirm_password) {
                $error = "Login password and confirm password must match.";
            } elseif (!in_array($login_role, $allowedSystemRoles, true)) {
                $error = "Selected system role is not allowed.";
            }
        }

        if ($error !== "") {
            // Validation message prepared above.
        } elseif ($check->num_rows > 0) {
            $error = "Employee with this email already exists.";
        } else {
            $franchiseeIdValue = $franchisee_id === '' ? null : (int) $franchisee_id;
            $linkedUserId = null;

            $conn->begin_transaction();

            try {
                if ($create_login_account) {
                    $account = createManagedUserAccount(
                        $conn,
                        (int) ($_SESSION['user_id'] ?? 0),
                        (string) ($_SESSION['role'] ?? ''),
                        $email,
                        $login_username,
                        $login_password,
                        $login_role
                    );

                    if (!$account['ok']) {
                        throw new RuntimeException((string) $account['error']);
                    }

                    $linkedUserId = (int) $account['user_id'];
                }

                $stmt = $conn->prepare("
                    INSERT INTO employees (name, email, phone, role, status, franchisee_id, user_id)
                    VALUES (?, ?, ?, ?, ?, ?, ?)
                ");
                $stmt->bind_param("sssssii", $name, $email, $phone, $role, $status, $franchiseeIdValue, $linkedUserId);

                if (!$stmt->execute()) {
                    throw new RuntimeException("Something went wrong. Please try again.");
                }

                $stmt->close();
                $conn->commit();
                header("Location: employee.php?created=1");
                exit;
            } catch (Throwable $e) {
                $conn->rollback();
                $error = $e->getMessage();
            }
        }

        $check->close();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Create Employee</title>

    <link rel="shortcut icon" href="assets/images/favicon.ico">
    <link rel="stylesheet" href="assets/css/bootstrap.min.css">
    <link rel="stylesheet" href="assets/vendors/css/vendors.min.css">
    <link rel="stylesheet" href="assets/css/theme.min.css">
</head>

<body>

<?php include 'sidebar.php'; ?>

<main class="nxl-container">
<div class="nxl-content">

<div class="page-header">
    <div class="page-header-left">
        <h5 class="m-b-10">Create Employee</h5>
    </div>
</div>

<div class="main-content">
<div class="row">
<div class="col-lg-8">

<div class="card">
<div class="card-body">

<?php if ($error): ?>
<div class="alert alert-danger alert-dismissible fade show">
    <?= htmlspecialchars($error) ?>
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
<?php endif; ?>

<form method="POST">

    <div class="mb-3">
        <label class="form-label">Name *</label>
        <input type="text" name="name" class="form-control" value="<?= htmlspecialchars($name) ?>" required>
    </div>

    <div class="mb-3">
        <label class="form-label">Email *</label>
        <input type="email" name="email" class="form-control" value="<?= htmlspecialchars($email) ?>" required>
    </div>

    <div class="mb-3">
        <label class="form-label">Phone</label>
        <input type="text" name="phone" class="form-control" value="<?= htmlspecialchars($phone) ?>">
    </div>

    <div class="mb-3">
        <label class="form-label">Job Role *</label>
        <select name="role" class="form-control" required>
            <option value="">Select Role</option>
            <?php foreach ($jobRoleOptions as $jobRoleOption): ?>
                <option value="<?= htmlspecialchars($jobRoleOption) ?>" <?= $role === $jobRoleOption ? 'selected' : '' ?>>
                    <?= htmlspecialchars($jobRoleOption) ?>
                </option>
            <?php endforeach; ?>
        </select>
    </div>

    <div class="mb-4">
        <label class="form-label">Status</label>
        <select name="status" class="form-control">
            <option value="Active" <?= $status == "Active" ? 'selected' : '' ?>>Active</option>
            <option value="Inactive" <?= $status == "Inactive" ? 'selected' : '' ?>>Inactive</option>
        </select>
    </div>

    <div class="mb-4">
        <label class="form-label">Franchisee</label>
        <?php if ($lockedFranchiseeId !== null && $lockedFranchisee !== null): ?>
            <input type="hidden" name="franchisee_id" value="<?= (int) $lockedFranchisee['id'] ?>">
            <input type="text" class="form-control" value="<?= htmlspecialchars((string) $lockedFranchisee['franchisee_name']) ?> (<?= htmlspecialchars((string) $lockedFranchisee['franchisee_code']) ?>)" readonly>
            <div class="form-text">Employees created from this account are assigned to your franchise automatically.</div>
        <?php else: ?>
            <select name="franchisee_id" class="form-control">
                <option value="">Not assigned</option>
                <?php foreach ($franchisees as $franchisee): ?>
                    <option value="<?= (int) $franchisee['id'] ?>" <?= $franchisee_id === (string) $franchisee['id'] ? 'selected' : '' ?>>
                        <?= htmlspecialchars((string) $franchisee['franchisee_name']) ?> (<?= htmlspecialchars((string) $franchisee['franchisee_code']) ?>)
                    </option>
                <?php endforeach; ?>
            </select>
        <?php endif; ?>
    </div>

    <?php if ($allowedSystemRoles !== []): ?>
    <div class="form-check mb-3">
        <input class="form-check-input" type="checkbox" name="create_login_account" id="createLoginAccount" <?= $create_login_account ? 'checked' : '' ?>>
        <label class="form-check-label" for="createLoginAccount">Create login account for this employee</label>
    </div>

    <div id="loginAccountFields" style="<?= $create_login_account ? '' : 'display:none;' ?>">
        <div class="mb-3">
            <label class="form-label">Username *</label>
            <input type="text" name="login_username" class="form-control" value="<?= htmlspecialchars($login_username) ?>" placeholder="Username for login">
        </div>

        <div class="mb-3">
            <label class="form-label">System Role *</label>
            <select name="login_role" class="form-control">
                <?php foreach ($allowedSystemRoles as $systemRole): ?>
                    <option value="<?= htmlspecialchars($systemRole) ?>" <?= $login_role === $systemRole ? 'selected' : '' ?>><?= htmlspecialchars(ucfirst($systemRole)) ?></option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="mb-3">
            <label class="form-label">Password *</label>
            <input type="password" name="login_password" class="form-control" placeholder="Set login password">
        </div>

        <div class="mb-4">
            <label class="form-label">Confirm Password *</label>
            <input type="password" name="login_confirm_password" class="form-control" placeholder="Confirm login password">
        </div>
    </div>
    <?php endif; ?>

    <div class="d-grid">
        <button type="submit" class="btn btn-primary">
            <i class="feather-user-plus me-2"></i> Create Employee
        </button>
    </div>

</form>

</div>
</div>

</div>
</div>
</div>

</div>

<?php include 'footer.php'; ?>
</main>

<script src="assets/vendors/js/vendors.min.js"></script>
<script src="assets/js/bootstrap.bundle.min.js"></script>
<script>
    (function () {
        const checkbox = document.getElementById('createLoginAccount');
        const fields = document.getElementById('loginAccountFields');
        if (!checkbox || !fields) {
            return;
        }

        checkbox.addEventListener('change', function () {
            fields.style.display = this.checked ? '' : 'none';
        });
    })();
</script>

</body>
</html>
