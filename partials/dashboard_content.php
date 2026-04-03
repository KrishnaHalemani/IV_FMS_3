<?php
if (!isset($dashboardMetrics, $dashboardRole)) {
    throw new RuntimeException('Dashboard view requires metrics and role context.');
}

$roleTitles = [
    'master' => 'Enterprise Dashboard',
    'super' => 'Superadmin Operations',
    'admin' => 'Admin Workbench',
];

$roleDescriptions = [
    'master' => 'Complete visibility across users, projects, linked clients, IMS records, and delivery risks.',
    'super' => 'Monitor the admins and users under you, keep projects moving, and act on delivery risks early.',
    'admin' => 'Track your team, active delivery workload, milestones, and recent operational activity in one place.',
];

$quickActions = [
    'master' => [
        ['label' => 'Create Project', 'href' => 'projects-create.php', 'icon' => 'feather-briefcase'],
        ['label' => 'Manage Users', 'href' => 'user-management.php', 'icon' => 'feather-users'],
        ['label' => 'Add Client', 'href' => 'customers-create.php', 'icon' => 'feather-user-plus'],
    ],
    'super' => [
        ['label' => 'Create Project', 'href' => 'projects-create.php', 'icon' => 'feather-briefcase'],
        ['label' => 'Manage Users', 'href' => 'user-management.php', 'icon' => 'feather-users'],
        ['label' => 'Add Client', 'href' => 'customers-create.php', 'icon' => 'feather-user-plus'],
    ],
    'admin' => [
        ['label' => 'Create Project', 'href' => 'projects-create.php', 'icon' => 'feather-briefcase'],
        ['label' => 'Manage Users', 'href' => 'user-management.php', 'icon' => 'feather-users'],
        ['label' => 'Add Client', 'href' => 'customers-create.php', 'icon' => 'feather-user-plus'],
    ],
];

$statusTotal = 0;
foreach ($dashboardMetrics['status_breakdown'] as $statusItem) {
    $statusTotal += (int) ($statusItem['total'] ?? 0);
}

$title = $roleTitles[$dashboardRole] ?? 'Dashboard';
$description = $roleDescriptions[$dashboardRole] ?? 'Operational visibility across your workspace.';
$actions = $quickActions[$dashboardRole] ?? [];
$teamSegments = $dashboardMetrics['team']['segments'] ?? [];
$activityRows = $dashboardMetrics['recent_activity'] ?? [];
$milestoneRows = $dashboardMetrics['upcoming_milestones'] ?? [];
$alertRows = $dashboardMetrics['alerts'] ?? [];
?>

<style>
    .iv-dashboard-hero,
    .iv-dashboard-card,
    .iv-dashboard-panel {
        border: 1px solid rgba(15, 23, 42, 0.08);
        border-radius: 20px;
        box-shadow: 0 18px 50px rgba(15, 23, 42, 0.06);
    }

    .iv-dashboard-hero {
        background: linear-gradient(135deg, #0f172a 0%, #1d4ed8 55%, #38bdf8 100%);
        color: #fff;
        overflow: hidden;
        position: relative;
    }

    .iv-dashboard-hero:before,
    .iv-dashboard-hero:after {
        content: "";
        position: absolute;
        border-radius: 999px;
        opacity: 0.18;
        background: #fff;
    }

    .iv-dashboard-hero:before {
        width: 220px;
        height: 220px;
        top: -80px;
        right: -60px;
    }

    .iv-dashboard-hero:after {
        width: 140px;
        height: 140px;
        bottom: -40px;
        right: 140px;
    }

    .iv-dashboard-hero .btn {
        border-radius: 999px;
    }

    .iv-dashboard-stat {
        background: rgba(255, 255, 255, 0.12);
        border: 1px solid rgba(255, 255, 255, 0.18);
        border-radius: 16px;
        padding: 14px 16px;
        min-height: 100%;
    }

    .iv-dashboard-card {
        background: #fff;
        height: 100%;
    }

    .iv-dashboard-card .card-body {
        padding: 1.35rem;
    }

    .iv-kpi-icon {
        width: 52px;
        height: 52px;
        border-radius: 16px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        background: rgba(59, 130, 246, 0.12);
        color: #2563eb;
        font-size: 1.15rem;
    }

    .iv-dashboard-panel {
        background: #fff;
        padding: 1.35rem;
        height: 100%;
    }

    .iv-segment-bar {
        width: 100%;
        height: 10px;
        border-radius: 999px;
        background: #e5e7eb;
        overflow: hidden;
        display: flex;
    }

    .iv-segment-piece {
        height: 100%;
    }

    .iv-alert-item,
    .iv-activity-item,
    .iv-milestone-item {
        border: 1px solid rgba(15, 23, 42, 0.08);
        border-radius: 16px;
        padding: 14px 16px;
        background: #fff;
    }

    .iv-status-row + .iv-status-row,
    .iv-alert-item + .iv-alert-item,
    .iv-activity-item + .iv-activity-item,
    .iv-milestone-item + .iv-milestone-item {
        margin-top: 12px;
    }

    .iv-status-track {
        width: 100%;
        height: 8px;
        border-radius: 999px;
        background: #e2e8f0;
        overflow: hidden;
    }

    .iv-status-fill {
        height: 100%;
        border-radius: 999px;
        background: linear-gradient(90deg, #2563eb, #38bdf8);
    }

    .iv-timeline-dot {
        width: 10px;
        height: 10px;
        border-radius: 50%;
        background: #2563eb;
        flex: 0 0 auto;
        margin-top: 7px;
    }

    @media (max-width: 767.98px) {
        .iv-dashboard-hero .d-flex {
            gap: 0.75rem;
        }
    }
</style>

<div class="main-content">
    <div class="card iv-dashboard-hero mb-4">
        <div class="card-body p-4 p-md-5 position-relative">
            <div class="row align-items-center g-4">
                <div class="col-lg-7">
                    <span class="badge bg-white text-primary fw-semibold mb-3"><?= htmlspecialchars($dashboardMetrics['role_label']) ?></span>
                    <h2 class="mb-2 text-white"><?= htmlspecialchars($title) ?></h2>
                    <p class="mb-4 text-white text-opacity-75"><?= htmlspecialchars($description) ?></p>
                    <div class="d-flex flex-wrap gap-2">
                        <?php foreach ($actions as $action): ?>
                            <a href="<?= htmlspecialchars($action['href']) ?>" class="btn btn-light">
                                <i class="<?= htmlspecialchars($action['icon']) ?> me-2"></i><?= htmlspecialchars($action['label']) ?>
                            </a>
                        <?php endforeach; ?>
                    </div>
                </div>
                <div class="col-lg-5">
                    <div class="row g-3">
                        <div class="col-6">
                            <div class="iv-dashboard-stat">
                                <div class="fs-12 text-uppercase text-white text-opacity-75 mb-2">Projects in Scope</div>
                                <div class="fs-2 fw-bold"><?= number_format($dashboardMetrics['projects']['total']) ?></div>
                                <div class="fs-12 text-white text-opacity-75 mt-1"><?= number_format($dashboardMetrics['projects']['active']) ?> active right now</div>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="iv-dashboard-stat">
                                <div class="fs-12 text-uppercase text-white text-opacity-75 mb-2">Portfolio Value</div>
                                <div class="fs-2 fw-bold">Rs <?= number_format($dashboardMetrics['projects']['portfolio_value'], 0) ?></div>
                                <div class="fs-12 text-white text-opacity-75 mt-1">Estimated budget tracked</div>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="iv-dashboard-stat">
                                <div class="fs-12 text-uppercase text-white text-opacity-75 mb-2">Linked IMS Value</div>
                                <div class="fs-2 fw-bold">Rs <?= number_format($dashboardMetrics['ims']['total_collected'], 0) ?></div>
                                <div class="fs-12 text-white text-opacity-75 mt-1"><?= number_format($dashboardMetrics['ims']['total_invoices']) ?> invoices in scope</div>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="iv-dashboard-stat">
                                <div class="fs-12 text-uppercase text-white text-opacity-75 mb-2">Team in Scope</div>
                                <div class="fs-2 fw-bold"><?= number_format($dashboardMetrics['team']['total']) ?></div>
                                <div class="fs-12 text-white text-opacity-75 mt-1"><?= count($teamSegments) > 0 ? htmlspecialchars($teamSegments[0]['label']) . ' and below' : 'Assigned hierarchy' ?></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-4 mb-4">
        <div class="col-xxl-3 col-md-6">
            <div class="card iv-dashboard-card">
                <div class="card-body">
                    <div class="d-flex align-items-center justify-content-between mb-3">
                        <div class="iv-kpi-icon"><i class="feather-users"></i></div>
                        <span class="badge bg-soft-primary text-primary">Hierarchy</span>
                    </div>
                    <div class="fs-3 fw-bold text-dark"><?= number_format($dashboardMetrics['team']['total']) ?></div>
                    <div class="text-muted mb-3">Users inside your operating scope</div>
                    <?php foreach ($teamSegments as $segment): ?>
                        <div class="d-flex align-items-center justify-content-between fs-12 mb-2">
                            <span><?= htmlspecialchars($segment['label']) ?></span>
                            <span class="fw-semibold"><?= number_format($segment['count']) ?></span>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
        <div class="col-xxl-3 col-md-6">
            <div class="card iv-dashboard-card">
                <div class="card-body">
                    <div class="d-flex align-items-center justify-content-between mb-3">
                        <div class="iv-kpi-icon"><i class="feather-briefcase"></i></div>
                        <span class="badge bg-soft-success text-success">Delivery</span>
                    </div>
                    <div class="fs-3 fw-bold text-dark"><?= number_format($dashboardMetrics['projects']['active']) ?></div>
                    <div class="text-muted mb-3">Active projects currently running</div>
                    <div class="d-flex justify-content-between fs-12">
                        <span>Completed</span>
                        <span class="fw-semibold"><?= number_format($dashboardMetrics['projects']['completed']) ?></span>
                    </div>
                    <div class="d-flex justify-content-between fs-12 mt-2">
                        <span>Delayed</span>
                        <span class="fw-semibold text-danger"><?= number_format($dashboardMetrics['projects']['delayed']) ?></span>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xxl-3 col-md-6">
            <div class="card iv-dashboard-card">
                <div class="card-body">
                    <div class="d-flex align-items-center justify-content-between mb-3">
                        <div class="iv-kpi-icon"><i class="feather-user-check"></i></div>
                        <span class="badge bg-soft-info text-info">Coverage</span>
                    </div>
                    <div class="fs-3 fw-bold text-dark"><?= number_format($dashboardMetrics['clients']['total']) ?></div>
                    <div class="text-muted mb-3">Clients linked to projects in scope</div>
                    <div class="d-flex justify-content-between fs-12">
                        <span>Without owner</span>
                        <span class="fw-semibold"><?= number_format($dashboardMetrics['projects']['without_owner']) ?></span>
                    </div>
                    <div class="d-flex justify-content-between fs-12 mt-2">
                        <span>Without milestones</span>
                        <span class="fw-semibold"><?= number_format($dashboardMetrics['projects']['without_milestones']) ?></span>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xxl-3 col-md-6">
            <div class="card iv-dashboard-card">
                <div class="card-body">
                    <div class="d-flex align-items-center justify-content-between mb-3">
                        <div class="iv-kpi-icon"><i class="feather-dollar-sign"></i></div>
                        <span class="badge bg-soft-warning text-warning">IMS</span>
                    </div>
                    <div class="fs-3 fw-bold text-dark">Rs <?= number_format($dashboardMetrics['ims']['total_collected'], 0) ?></div>
                    <div class="text-muted mb-3">Invoice value linked with your projects</div>
                    <div class="d-flex justify-content-between fs-12">
                        <span>Invoices in scope</span>
                        <span class="fw-semibold"><?= number_format($dashboardMetrics['ims']['total_invoices']) ?></span>
                    </div>
                    <div class="d-flex justify-content-between fs-12 mt-2">
                        <span>Total projects</span>
                        <span class="fw-semibold"><?= number_format($dashboardMetrics['projects']['total']) ?></span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-4 mb-4">
        <div class="col-xxl-6">
            <div class="iv-dashboard-panel">
                <div class="d-flex align-items-center justify-content-between mb-3">
                    <div>
                        <h5 class="mb-1">Project Status Mix</h5>
                        <p class="text-muted fs-12 mb-0">A quick chart of where your portfolio currently stands.</p>
                    </div>
                    <span class="badge bg-soft-dark text-dark"><?= number_format($statusTotal) ?> tracked</span>
                </div>
                <?php if ($dashboardMetrics['status_breakdown'] !== []): ?>
                    <?php foreach ($dashboardMetrics['status_breakdown'] as $statusItem): ?>
                        <?php $count = (int) ($statusItem['total'] ?? 0); ?>
                        <?php $percent = $statusTotal > 0 ? round(($count / $statusTotal) * 100) : 0; ?>
                        <div class="iv-status-row">
                            <div class="d-flex align-items-center justify-content-between fs-12 mb-2">
                                <span class="fw-semibold text-capitalize"><?= htmlspecialchars((string) $statusItem['status_label']) ?></span>
                                <span><?= number_format($count) ?> projects • <?= $percent ?>%</span>
                            </div>
                            <div class="iv-status-track">
                                <div class="iv-status-fill" style="width: <?= $percent ?>%"></div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="text-muted fs-13">No project status data is available yet.</div>
                <?php endif; ?>
            </div>
        </div>
        <div class="col-xxl-6">
            <div class="iv-dashboard-panel">
                <div class="d-flex align-items-center justify-content-between mb-3">
                    <div>
                        <h5 class="mb-1">Team Composition</h5>
                        <p class="text-muted fs-12 mb-0">Role coverage inside your current reporting scope.</p>
                    </div>
                    <span class="badge bg-soft-primary text-primary"><?= number_format($dashboardMetrics['team']['total']) ?> total</span>
                </div>
                <?php
                $teamTotal = max(1, (int) $dashboardMetrics['team']['total']);
                $toneMap = [
                    'primary' => '#2563eb',
                    'success' => '#16a34a',
                    'warning' => '#d97706',
                    'info' => '#0891b2',
                ];
                ?>
                <?php if ($teamSegments !== []): ?>
                    <div class="iv-segment-bar mb-4">
                        <?php foreach ($teamSegments as $segment): ?>
                            <?php $width = max(8, (int) round(($segment['count'] / $teamTotal) * 100)); ?>
                            <div class="iv-segment-piece" style="width: <?= $width ?>%; background: <?= $toneMap[$segment['tone']] ?? '#64748b' ?>"></div>
                        <?php endforeach; ?>
                    </div>
                    <?php foreach ($teamSegments as $segment): ?>
                        <div class="d-flex align-items-center justify-content-between py-2 border-bottom border-gray-200">
                            <div class="d-flex align-items-center gap-2">
                                <span class="rounded-circle d-inline-block" style="width:10px; height:10px; background: <?= $toneMap[$segment['tone']] ?? '#64748b' ?>"></span>
                                <span class="fw-semibold"><?= htmlspecialchars($segment['label']) ?></span>
                            </div>
                            <span class="text-muted"><?= number_format($segment['count']) ?></span>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="text-muted fs-13">No subordinate team members are available for this dashboard.</div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <div class="row g-4 mb-4">
        <div class="col-xxl-4">
            <div class="iv-dashboard-panel">
                <div class="d-flex align-items-center justify-content-between mb-3">
                    <div>
                        <h5 class="mb-1">Alerts</h5>
                        <p class="text-muted fs-12 mb-0">Risks and hygiene issues that need attention.</p>
                    </div>
                    <span class="badge bg-soft-danger text-danger"><?= number_format(count($alertRows)) ?> open</span>
                </div>
                <?php if ($alertRows !== []): ?>
                    <?php foreach ($alertRows as $alert): ?>
                        <div class="iv-alert-item">
                            <div class="d-flex align-items-start justify-content-between gap-3">
                                <div>
                                    <div class="fw-semibold text-dark"><?= htmlspecialchars($alert['title']) ?></div>
                                    <div class="text-muted fs-12 mt-1">Review and resolve this from the linked modules.</div>
                                </div>
                                <span class="badge bg-soft-<?= htmlspecialchars($alert['tone']) ?> text-<?= htmlspecialchars($alert['tone']) ?>"><?= number_format((int) $alert['value']) ?></span>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="iv-alert-item">
                        <div class="fw-semibold text-dark">No major alerts</div>
                        <div class="text-muted fs-12 mt-1">Your current project hygiene checks look healthy.</div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
        <div class="col-xxl-4">
            <div class="iv-dashboard-panel">
                <div class="d-flex align-items-center justify-content-between mb-3">
                    <div>
                        <h5 class="mb-1">Upcoming Milestones</h5>
                        <p class="text-muted fs-12 mb-0">Next due items across the visible project portfolio.</p>
                    </div>
                    <span class="badge bg-soft-info text-info"><?= number_format(count($milestoneRows)) ?> upcoming</span>
                </div>
                <?php if ($milestoneRows !== []): ?>
                    <?php foreach ($milestoneRows as $milestone): ?>
                        <div class="iv-milestone-item">
                            <div class="d-flex gap-3">
                                <span class="iv-timeline-dot"></span>
                                <div>
                                    <div class="fw-semibold text-dark"><?= htmlspecialchars((string) $milestone['title']) ?></div>
                                    <div class="text-muted fs-12"><?= htmlspecialchars((string) $milestone['project_name']) ?></div>
                                    <div class="fs-12 mt-1 text-primary"><?= date('d M Y', strtotime((string) $milestone['due_date'])) ?></div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="iv-milestone-item">
                        <div class="fw-semibold text-dark">No upcoming milestones</div>
                        <div class="text-muted fs-12 mt-1">Add project milestones to improve planning visibility.</div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
        <div class="col-xxl-4">
            <div class="iv-dashboard-panel">
                <div class="d-flex align-items-center justify-content-between mb-3">
                    <div>
                        <h5 class="mb-1">Recent Activity</h5>
                        <p class="text-muted fs-12 mb-0">Latest login and system actions in your scope.</p>
                    </div>
                    <span class="badge bg-soft-success text-success"><?= number_format(count($activityRows)) ?> events</span>
                </div>
                <?php if ($activityRows !== []): ?>
                    <?php foreach ($activityRows as $activity): ?>
                        <div class="iv-activity-item">
                            <div class="d-flex justify-content-between gap-3">
                                <div>
                                    <div class="fw-semibold text-dark text-capitalize"><?= htmlspecialchars((string) $activity['action']) ?></div>
                                    <div class="text-muted fs-12"><?= htmlspecialchars((string) $activity['user_email']) ?> • <?= htmlspecialchars((string) $activity['user_role']) ?></div>
                                    <?php if (!empty($activity['details'])): ?>
                                        <div class="fs-12 mt-1"><?= htmlspecialchars((string) $activity['details']) ?></div>
                                    <?php endif; ?>
                                </div>
                                <div class="fs-12 text-muted text-end"><?= date('d M, h:i A', strtotime((string) $activity['created_at'])) ?></div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="iv-activity-item">
                        <div class="fw-semibold text-dark">No recent activity yet</div>
                        <div class="text-muted fs-12 mt-1">Activity will appear here once users log in and work inside the system.</div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>
