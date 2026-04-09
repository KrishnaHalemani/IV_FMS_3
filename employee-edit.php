<?php
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/config/user_management.php';
require_once __DIR__ . '/config/current_user.php';

$id = (int) ($_GET['id'] ?? 0);
$sessionRole = (string) ($_SESSION['role'] ?? '');
$lockedFranchiseeId = $sessionRole !== 'master' ? iv_current_session_franchisee_id() : null;
$lockedFranchisee = null;

$franchisees = [];
$franchiseeSql = "SELECT id, franchisee_name, franchisee_code FROM franchisees";
if ($lockedFranchiseeId !== null) {
    $franchiseeSql .= " WHERE id = " . (int) $lockedFranchiseeId;
}
$franchiseeSql .= " ORDER BY franchisee_name";
$franchiseeResult = $conn->query($franchiseeSql);
while ($franchiseeResult && $row = $franchiseeResult->fetch_assoc()) {
    $franchisees[] = $row;
    if ($lockedFranchiseeId !== null && (int) $row['id'] === $lockedFranchiseeId) {
        $lockedFranchisee = $row;
    }
}

$stmt = $conn->prepare("
    SELECT e.*, u.username AS linked_username, u.role AS linked_role, u.email AS linked_email
    FROM employees e
    LEFT JOIN users u ON u.id = e.user_id
    WHERE e.id = ?
");
$stmt->bind_param("i", $id);
$stmt->execute();
$data = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$data) {
    die("Employee not found");
}

$currentRole = $data['role'] ?? '';
$currentFranchiseeId = $data['franchisee_id'] ?? null;
$allowedSystemRoles = isset($_SESSION['role']) ? getCreatableRoles((string) $_SESSION['role']) : [];
$jobRoleOptions = ['Developer', 'Designer', 'Manager', 'QA'];
if ($currentRole !== '' && !in_array($currentRole, $jobRoleOptions, true) && !in_array(strtolower($currentRole), ['super', 'admin', 'user'], true)) {
    array_unshift($jobRoleOptions, $currentRole);
}
$error = '';

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $name = trim((string) ($_POST['name'] ?? ''));
    $email = trim((string) ($_POST['email'] ?? ''));
    $phone = trim((string) ($_POST['phone'] ?? ''));
    $role = trim((string) ($_POST['role'] ?? ''));
    if ($lockedFranchiseeId !== null) {
        $franchisee_id = $lockedFranchiseeId;
    } else {
        $franchisee_id = ($_POST['franchisee_id'] ?? '') !== '' ? (int) $_POST['franchisee_id'] : null;
    }

    $create_login_account = isset($_POST['create_login_account']) && empty($data['user_id']);
    $login_username = trim((string) ($_POST['login_username'] ?? ''));
    $login_role = trim((string) ($_POST['login_role'] ?? 'user'));
    $login_password = (string) ($_POST['login_password'] ?? '');
    $login_confirm_password = (string) ($_POST['login_confirm_password'] ?? '');

    if ($name === '' || $email === '' || $role === '') {
        $error = "Please fill all required fields.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Please enter a valid employee email address.";
    }

    if ($error === '' && $franchisee_id !== null) {
        $franchiseeCheck = $conn->prepare("SELECT id FROM franchisees WHERE id = ? LIMIT 1");
        $franchiseeCheck->bind_param("i", $franchisee_id);
        $franchiseeCheck->execute();
        $franchiseeCheck->store_result();
        if ($franchiseeCheck->num_rows === 0) {
            $error = "Selected franchisee does not exist.";
        }
        $franchiseeCheck->close();
    }

    if ($error === '' && $create_login_account) {
        if ($login_username === '') {
            $error = "Username is required when creating a login account.";
        } elseif ($login_password !== $login_confirm_password) {
            $error = "Login password and confirm password must match.";
        } elseif (!in_array($login_role, $allowedSystemRoles, true)) {
            $error = "Selected system role is not allowed.";
        }
    }

    if ($error === '') {
        $conn->begin_transaction();

        try {
            $linkedUserId = !empty($data['user_id']) ? (int) $data['user_id'] : null;

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

            if ($linkedUserId !== null) {
                $userCheck = $conn->prepare("SELECT id FROM users WHERE email = ? AND id <> ? LIMIT 1");
                $userCheck->bind_param("si", $email, $linkedUserId);
                $userCheck->execute();
                $conflictingUser = $userCheck->get_result()->fetch_assoc();
                $userCheck->close();

                if ($conflictingUser) {
                    throw new RuntimeException("That email is already used by another login account.");
                }

                $syncUser = $conn->prepare("UPDATE users SET email = ? WHERE id = ?");
                $syncUser->bind_param("si", $email, $linkedUserId);
                if (!$syncUser->execute()) {
                    throw new RuntimeException("Unable to sync the linked login account.");
                }
                $syncUser->close();
            }

            $update = $conn->prepare("
                UPDATE employees
                SET name = ?, email = ?, phone = ?, role = ?, franchisee_id = ?, user_id = ?
                WHERE id = ?
            ");
            $update->bind_param("ssssiii", $name, $email, $phone, $role, $franchisee_id, $linkedUserId, $id);

            if (!$update->execute()) {
                throw new RuntimeException("Unable to update employee right now.");
            }

            $update->close();
            $conn->commit();

            if ((int) ($_SESSION['user_id'] ?? 0) === (int) $linkedUserId) {
                iv_refresh_session_user_context($conn, $linkedUserId);
            }

            header("Location: employee.php?updated=1");
            exit;
        } catch (Throwable $e) {
            $conn->rollback();
            $error = $e->getMessage();
        }
    }

    $data['name'] = $name;
    $data['email'] = $email;
    $data['phone'] = $phone;
    $data['role'] = $role;
    $data['franchisee_id'] = $franchisee_id;
    $currentRole = $role;
    $currentFranchiseeId = $franchisee_id;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Edit Employee</title>

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
            <h5 class="m-b-10">Edit Employee</h5>
        </div>
    </div>

    <div class="main-content">
        <div class="row justify-content-center">

            <div class="col-lg-6">

                <div class="card">
                    <div class="card-body">
                        <?php if ($error !== ''): ?>
                            <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
                        <?php endif; ?>

                        <form method="POST">
                            <div class="mb-3">
                                <label class="form-label">Name</label>
                                <input type="text" name="name" class="form-control" value="<?= htmlspecialchars((string) $data['name']) ?>" required>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Email</label>
                                <input type="email" name="email" class="form-control" value="<?= htmlspecialchars((string) $data['email']) ?>" required>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Phone</label>
                                <input type="text" name="phone" class="form-control" value="<?= htmlspecialchars((string) ($data['phone'] ?? '')) ?>">
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Job Role</label>
                                <select name="role" class="form-control" required>
                                    <option value="">Select Role</option>
                                    <?php foreach ($jobRoleOptions as $jobRoleOption): ?>
                                        <option value="<?= htmlspecialchars($jobRoleOption) ?>" <?= $currentRole === $jobRoleOption ? 'selected' : '' ?>>
                                            <?= htmlspecialchars($jobRoleOption) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <div class="mb-4">
                                <label class="form-label">Franchisee</label>
                                <?php if ($lockedFranchiseeId !== null && $lockedFranchisee !== null): ?>
                                    <input type="hidden" name="franchisee_id" value="<?= (int) $lockedFranchisee['id'] ?>">
                                    <input type="text" class="form-control" value="<?= htmlspecialchars((string) $lockedFranchisee['franchisee_name']) ?> (<?= htmlspecialchars((string) $lockedFranchisee['franchisee_code']) ?>)" readonly>
                                    <div class="form-text">This account can only manage employees inside its own franchise.</div>
                                <?php else: ?>
                                    <select name="franchisee_id" class="form-control">
                                        <option value="">Not assigned</option>
                                        <?php foreach ($franchisees as $franchisee): ?>
                                            <option value="<?= (int) $franchisee['id'] ?>" <?= (int) $currentFranchiseeId === (int) $franchisee['id'] ? 'selected' : '' ?>>
                                                <?= htmlspecialchars((string) $franchisee['franchisee_name']) ?> (<?= htmlspecialchars((string) $franchisee['franchisee_code']) ?>)
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                <?php endif; ?>
                            </div>

                            <div class="border rounded p-3 mb-4 bg-light">
                                <div class="fw-semibold mb-2">Login Account</div>
                                <?php if (!empty($data['user_id'])): ?>
                                    <div class="text-muted">Linked as <strong><?= htmlspecialchars((string) $data['linked_username']) ?></strong> (<?= htmlspecialchars(ucfirst((string) $data['linked_role'])) ?>)</div>
                                    <div class="small text-muted mt-1"><?= htmlspecialchars((string) $data['linked_email']) ?></div>
                                <?php elseif ($allowedSystemRoles !== []): ?>
                                    <div class="form-check mb-3">
                                        <input class="form-check-input" type="checkbox" name="create_login_account" id="createLoginAccount">
                                        <label class="form-check-label" for="createLoginAccount">Create login account for this employee</label>
                                    </div>

                                    <div id="loginAccountFields" style="display:none;">
                                        <div class="mb-3">
                                            <label class="form-label">Username *</label>
                                            <input type="text" name="login_username" class="form-control" placeholder="Username for login">
                                        </div>

                                        <div class="mb-3">
                                            <label class="form-label">System Role *</label>
                                            <select name="login_role" class="form-control">
                                                <?php foreach ($allowedSystemRoles as $systemRole): ?>
                                                    <option value="<?= htmlspecialchars($systemRole) ?>"><?= htmlspecialchars(ucfirst($systemRole)) ?></option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>

                                        <div class="mb-3">
                                            <label class="form-label">Password *</label>
                                            <input type="password" name="login_password" class="form-control" placeholder="Set login password">
                                        </div>

                                        <div class="mb-0">
                                            <label class="form-label">Confirm Password *</label>
                                            <input type="password" name="login_confirm_password" class="form-control" placeholder="Confirm login password">
                                        </div>
                                    </div>
                                <?php else: ?>
                                    <div class="text-muted">You do not have permission to create login accounts.</div>
                                <?php endif; ?>
                            </div>

                            <div class="d-grid gap-2">
                                <button type="submit" class="btn btn-primary">Update Employee</button>
                                <a href="employee.php" class="btn btn-secondary">Cancel</a>
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
