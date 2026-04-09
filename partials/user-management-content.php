<?php
$roleDescriptions = [
    'master' => 'Master can review every superadmin, admin, and user account in the system.',
    'super' => 'You can review the admin and user accounts created under your supervision.',
    'admin' => 'You can review the user accounts created by you.',
];
?>
<div class="page-header">
    <div class="page-header-left d-flex align-items-center">
        <div class="page-header-title">
            <h5 class="m-b-10">User Management</h5>
        </div>
        <ul class="breadcrumb">
            <li class="breadcrumb-item"><a href="<?= htmlspecialchars($homePath, ENT_QUOTES, 'UTF-8') ?>">Home</a></li>
            <li class="breadcrumb-item">User Management</li>
        </ul>
    </div>
    <div class="page-header-right ms-auto">
        <a href="<?= htmlspecialchars($registerPath, ENT_QUOTES, 'UTF-8') ?>" class="btn btn-primary">
            <i class="feather-user-plus me-2"></i>
            <span>Create User</span>
        </a>
    </div>
</div>

<div class="main-content">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex flex-column flex-lg-row justify-content-between align-items-lg-center gap-3 mb-4">
                        <div>
                            <h4 class="mb-1"><?= htmlspecialchars(getUserManagementHeading($currentRole), ENT_QUOTES, 'UTF-8') ?></h4>
                            <p class="text-muted mb-0"><?= htmlspecialchars($roleDescriptions[$currentRole] ?? 'Manage visible users.', ENT_QUOTES, 'UTF-8') ?></p>
                        </div>
                        <div class="text-muted small">
                            Total visible users: <strong><?= count($visibleUsers) ?></strong>
                        </div>
                    </div>

                    <div class="table-responsive">
                        <table class="table table-hover" id="userManagementTable">
                            <thead>
                                <tr>
                                    <th>Username</th>
                                    <th>Email</th>
                                    <th>Role</th>
                                    <th>Franchisee</th>
                                    <th>Created By</th>
                                    <th>Created At</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($visibleUsers as $user): ?>
                                    <?php
                                    $creatorLabel = 'System / legacy';
                                    if (!empty($user['creator_name'])) {
                                        $creatorLabel = $user['creator_name'] . ' (' . ucfirst((string) $user['creator_role']) . ')';
                                    }
                                    $franchiseeLabel = 'Not assigned';
                                    if (!empty($user['franchisee_name'])) {
                                        $franchiseeLabel = $user['franchisee_name'];
                                        if (!empty($user['franchisee_code'])) {
                                            $franchiseeLabel .= ' (' . $user['franchisee_code'] . ')';
                                        }
                                    }
                                    ?>
                                    <tr>
                                        <td><?= htmlspecialchars((string) $user['username'], ENT_QUOTES, 'UTF-8') ?></td>
                                        <td><?= htmlspecialchars((string) $user['email'], ENT_QUOTES, 'UTF-8') ?></td>
                                        <td>
                                            <span class="badge bg-soft-primary text-primary"><?= htmlspecialchars(ucfirst((string) $user['role']), ENT_QUOTES, 'UTF-8') ?></span>
                                        </td>
                                        <td><?= htmlspecialchars($franchiseeLabel, ENT_QUOTES, 'UTF-8') ?></td>
                                        <td><?= htmlspecialchars($creatorLabel, ENT_QUOTES, 'UTF-8') ?></td>
                                        <td><?= !empty($user['created_at']) ? htmlspecialchars(date('d M Y, h:i A', strtotime((string) $user['created_at'])), ENT_QUOTES, 'UTF-8') : '-' ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>

                    <?php if ($visibleUsers === []): ?>
                        <div class="alert alert-light border mt-4 mb-0">
                            No users are visible in this scope yet.
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>
