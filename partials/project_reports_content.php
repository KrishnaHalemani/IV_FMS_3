<?php
if (!isset($reportData, $reportRole, $reportBasePath, $reportProjectsPath, $reportCreatePath)) {
    throw new RuntimeException('Project report view requires report context.');
}

$roleDescriptions = [
    'master' => 'Enterprise-level project reporting across delivery health, ownership, budgets, and upcoming milestone pressure.',
    'super' => 'Track the project portfolio created by your hierarchy and spot delays, ownership gaps, and upcoming work.',
    'admin' => 'See project execution clearly with quick filters, risk visibility, and recent delivery movement.',
];

$summary = $reportData['summary'];
$statusBreakdown = $reportData['status_breakdown'];
$priorityBreakdown = $reportData['priority_breakdown'];
$projects = $reportData['projects'];
$riskProjects = $reportData['risk_projects'];
$upcomingMilestones = $reportData['upcoming_milestones'];
$filters = $reportData['filters'];
$statusTotal = max(1, array_sum(array_map(static fn($row) => (int) $row['total'], $statusBreakdown)));
$priorityTotal = max(1, array_sum(array_map(static fn($row) => (int) $row['total'], $priorityBreakdown)));
?>

<style>
    .iv-report-hero,
    .iv-report-card,
    .iv-report-panel {
        border: 1px solid rgba(15, 23, 42, 0.08);
        border-radius: 20px;
        box-shadow: 0 18px 50px rgba(15, 23, 42, 0.06);
        background: #fff;
    }

    .iv-report-hero {
        background: linear-gradient(135deg, #082f49 0%, #0f766e 50%, #22c55e 100%);
        color: #fff;
        overflow: hidden;
        position: relative;
    }

    .iv-report-hero:before {
        content: "";
        position: absolute;
        width: 220px;
        height: 220px;
        border-radius: 999px;
        background: rgba(255, 255, 255, 0.14);
        top: -80px;
        right: -50px;
    }

    .iv-report-card .card-body,
    .iv-report-panel {
        padding: 1.35rem;
    }

    .iv-report-kpi {
        border-radius: 16px;
        padding: 14px 16px;
        background: rgba(255, 255, 255, 0.12);
        border: 1px solid rgba(255, 255, 255, 0.18);
        min-height: 100%;
    }

    .iv-report-track {
        width: 100%;
        height: 8px;
        background: #e2e8f0;
        border-radius: 999px;
        overflow: hidden;
    }

    .iv-report-fill {
        height: 100%;
        background: linear-gradient(90deg, #10b981, #14b8a6);
        border-radius: 999px;
    }

    .iv-report-pill {
        display: inline-flex;
        align-items: center;
        border-radius: 999px;
        padding: 6px 12px;
        font-size: 11px;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.04em;
    }

    .iv-report-pill.priority-high,
    .iv-report-pill.priority-critical {
        background: rgba(239, 68, 68, 0.12);
        color: #dc2626;
    }

    .iv-report-pill.priority-medium {
        background: rgba(245, 158, 11, 0.14);
        color: #d97706;
    }

    .iv-report-pill.priority-low,
    .iv-report-pill.priority-unspecified {
        background: rgba(37, 99, 235, 0.1);
        color: #2563eb;
    }

    .iv-report-row + .iv-report-row,
    .iv-risk-item + .iv-risk-item,
    .iv-milestone-item + .iv-milestone-item {
        margin-top: 12px;
    }

    .iv-risk-item,
    .iv-milestone-item {
        border: 1px solid rgba(15, 23, 42, 0.08);
        border-radius: 16px;
        padding: 14px 16px;
    }
</style>

<div class="main-content">
    <div class="card iv-report-hero mb-4">
        <div class="card-body p-4 p-md-5 position-relative">
            <div class="row align-items-center g-4">
                <div class="col-lg-7">
                    <span class="badge bg-white text-success fw-semibold mb-3">Project Reporting</span>
                    <h2 class="mb-2 text-white">Delivery Intelligence</h2>
                    <p class="mb-4 text-white text-opacity-75"><?= htmlspecialchars($roleDescriptions[$reportRole] ?? 'Project performance and risk visibility for your workspace.') ?></p>
                    <div class="d-flex flex-wrap gap-2">
                        <a href="<?= htmlspecialchars($reportProjectsPath) ?>" class="btn btn-light">Open Projects</a>
                        <a href="<?= htmlspecialchars($reportCreatePath) ?>" class="btn btn-outline-light">Create Project</a>
                    </div>
                </div>
                <div class="col-lg-5">
                    <div class="row g-3">
                        <div class="col-6">
                            <div class="iv-report-kpi">
                                <div class="fs-12 text-uppercase text-white text-opacity-75 mb-2">Total Projects</div>
                                <div class="fs-2 fw-bold"><?= number_format($summary['total']) ?></div>
                                <div class="fs-12 text-white text-opacity-75 mt-1"><?= number_format($summary['active']) ?> active now</div>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="iv-report-kpi">
                                <div class="fs-12 text-uppercase text-white text-opacity-75 mb-2">Tracked Budget</div>
                                <div class="fs-2 fw-bold">Rs <?= number_format($summary['budget'], 0) ?></div>
                                <div class="fs-12 text-white text-opacity-75 mt-1">Portfolio estimate</div>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="iv-report-kpi">
                                <div class="fs-12 text-uppercase text-white text-opacity-75 mb-2">Logged Hours</div>
                                <div class="fs-2 fw-bold"><?= number_format($summary['hours']) ?>H</div>
                                <div class="fs-12 text-white text-opacity-75 mt-1">Planned project hours</div>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="iv-report-kpi">
                                <div class="fs-12 text-uppercase text-white text-opacity-75 mb-2">Risk Count</div>
                                <div class="fs-2 fw-bold"><?= number_format($summary['delayed'] + $summary['without_owner']) ?></div>
                                <div class="fs-12 text-white text-opacity-75 mt-1">Delays plus ownership gaps</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="iv-report-panel mb-4">
        <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-3">
            <div>
                <h5 class="mb-1">Filter Portfolio</h5>
                <p class="text-muted fs-12 mb-0">Use live filters to focus the report on a specific status or priority.</p>
            </div>
            <a href="<?= htmlspecialchars($reportBasePath) ?>" class="btn btn-light">Reset Filters</a>
        </div>
        <form method="get" action="" class="row g-3">
            <div class="col-md-4">
                <label class="form-label">Status</label>
                <select name="status" class="form-control">
                    <option value="">All statuses</option>
                    <?php foreach ($filters['available_statuses'] as $statusOption): ?>
                        <option value="<?= htmlspecialchars((string) $statusOption) ?>" <?= $filters['status'] === (string) $statusOption ? 'selected' : '' ?>>
                            <?= htmlspecialchars(ucfirst((string) $statusOption)) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-4">
                <label class="form-label">Priority</label>
                <select name="priority" class="form-control">
                    <option value="">All priorities</option>
                    <?php foreach ($filters['available_priorities'] as $priorityOption): ?>
                        <option value="<?= htmlspecialchars((string) $priorityOption) ?>" <?= $filters['priority'] === (string) $priorityOption ? 'selected' : '' ?>>
                            <?= htmlspecialchars(ucfirst((string) $priorityOption)) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-4 d-flex align-items-end">
                <button type="submit" class="btn btn-primary w-100">Apply Filters</button>
            </div>
        </form>
    </div>

    <div class="row g-4 mb-4">
        <div class="col-xxl-3 col-md-6">
            <div class="card iv-report-card">
                <div class="card-body">
                    <div class="fs-12 text-uppercase text-muted mb-2">Active Projects</div>
                    <div class="fs-2 fw-bold text-dark"><?= number_format($summary['active']) ?></div>
                    <div class="text-muted fs-12 mt-2">Currently moving through delivery</div>
                </div>
            </div>
        </div>
        <div class="col-xxl-3 col-md-6">
            <div class="card iv-report-card">
                <div class="card-body">
                    <div class="fs-12 text-uppercase text-muted mb-2">Completed</div>
                    <div class="fs-2 fw-bold text-dark"><?= number_format($summary['completed']) ?></div>
                    <div class="text-muted fs-12 mt-2">Closed successfully in this scope</div>
                </div>
            </div>
        </div>
        <div class="col-xxl-3 col-md-6">
            <div class="card iv-report-card">
                <div class="card-body">
                    <div class="fs-12 text-uppercase text-muted mb-2">Delayed</div>
                    <div class="fs-2 fw-bold text-danger"><?= number_format($summary['delayed']) ?></div>
                    <div class="text-muted fs-12 mt-2">Past due and still open</div>
                </div>
            </div>
        </div>
        <div class="col-xxl-3 col-md-6">
            <div class="card iv-report-card">
                <div class="card-body">
                    <div class="fs-12 text-uppercase text-muted mb-2">Without Owner</div>
                    <div class="fs-2 fw-bold text-warning"><?= number_format($summary['without_owner']) ?></div>
                    <div class="text-muted fs-12 mt-2">Projects that need accountability</div>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-4 mb-4">
        <div class="col-xxl-6">
            <div class="iv-report-panel">
                <div class="d-flex align-items-center justify-content-between mb-3">
                    <div>
                        <h5 class="mb-1">Status Distribution</h5>
                        <p class="text-muted fs-12 mb-0">Where the current portfolio stands by delivery stage.</p>
                    </div>
                    <span class="badge bg-soft-success text-success"><?= number_format($summary['total']) ?> projects</span>
                </div>
                <?php if ($statusBreakdown !== []): ?>
                    <?php foreach ($statusBreakdown as $item): ?>
                        <?php $count = (int) $item['total']; ?>
                        <?php $percent = round(($count / $statusTotal) * 100); ?>
                        <div class="iv-report-row">
                            <div class="d-flex justify-content-between fs-12 mb-2">
                                <span class="fw-semibold text-capitalize"><?= htmlspecialchars((string) $item['label']) ?></span>
                                <span><?= number_format($count) ?> - <?= $percent ?>%</span>
                            </div>
                            <div class="iv-report-track">
                                <div class="iv-report-fill" style="width: <?= $percent ?>%"></div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="text-muted fs-13">No status data is available yet.</div>
                <?php endif; ?>
            </div>
        </div>
        <div class="col-xxl-6">
            <div class="iv-report-panel">
                <div class="d-flex align-items-center justify-content-between mb-3">
                    <div>
                        <h5 class="mb-1">Priority Mix</h5>
                        <p class="text-muted fs-12 mb-0">A quick view of delivery urgency across the scoped projects.</p>
                    </div>
                    <span class="badge bg-soft-primary text-primary"><?= number_format(count($priorityBreakdown)) ?> levels</span>
                </div>
                <?php if ($priorityBreakdown !== []): ?>
                    <?php foreach ($priorityBreakdown as $item): ?>
                        <?php $count = (int) $item['total']; ?>
                        <?php $percent = round(($count / $priorityTotal) * 100); ?>
                        <div class="iv-report-row">
                            <div class="d-flex justify-content-between fs-12 mb-2">
                                <span class="fw-semibold text-capitalize"><?= htmlspecialchars((string) $item['label']) ?></span>
                                <span><?= number_format($count) ?> - <?= $percent ?>%</span>
                            </div>
                            <div class="iv-report-track">
                                <div class="iv-report-fill" style="width: <?= $percent ?>%"></div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="text-muted fs-13">No priority data is available yet.</div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <div class="row g-4 mb-4">
        <div class="col-xxl-8">
            <div class="iv-report-panel">
                <div class="d-flex align-items-center justify-content-between mb-3">
                    <div>
                        <h5 class="mb-1">Project Portfolio</h5>
                        <p class="text-muted fs-12 mb-0">Recent projects with ownership, client, budget, and milestone context.</p>
                    </div>
                    <a href="<?= htmlspecialchars($reportProjectsPath) ?>" class="btn btn-light">Open Full List</a>
                </div>
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead>
                            <tr>
                                <th>Project</th>
                                <th>Client</th>
                                <th>Owner</th>
                                <th>Priority</th>
                                <th>Status</th>
                                <th class="text-end">Budget</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ($projects !== []): ?>
                                <?php foreach ($projects as $project): ?>
                                    <?php $priorityClass = strtolower((string) ($project['project_priority'] ?: 'unspecified')); ?>
                                    <tr>
                                        <td>
                                            <div class="fw-semibold text-dark"><?= htmlspecialchars((string) $project['project_name']) ?></div>
                                            <div class="fs-12 text-muted">
                                                <?= htmlspecialchars((string) ($project['project_code'] ?: 'No code')) ?>
                                                - <?= (int) $project['milestone_count'] ?> milestones
                                            </div>
                                        </td>
                                        <td>
                                            <div><?= htmlspecialchars((string) ($project['customer_name'] ?: 'Internal Project')) ?></div>
                                            <div class="fs-12 text-muted"><?= htmlspecialchars((string) ($project['invoice_number'] ?: 'No IMS link')) ?></div>
                                        </td>
                                        <td>
                                            <div><?= htmlspecialchars((string) ($project['owner_name'] ?: 'Unassigned')) ?></div>
                                            <div class="fs-12 text-muted">Created by <?= htmlspecialchars((string) ($project['creator_name'] ?: 'System')) ?></div>
                                        </td>
                                        <td><span class="iv-report-pill priority-<?= htmlspecialchars($priorityClass) ?>"><?= htmlspecialchars((string) ($project['project_priority'] ?: 'unspecified')) ?></span></td>
                                        <td><?= htmlspecialchars((string) ($project['project_status'] ?: 'unspecified')) ?></td>
                                        <td class="text-end fw-semibold">Rs <?= number_format((float) ($project['estimated_budget'] ?? 0), 0) ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="6" class="text-center py-4 text-muted">No projects match the selected filters.</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        <div class="col-xxl-4">
            <div class="iv-report-panel mb-4">
                <div class="d-flex align-items-center justify-content-between mb-3">
                    <div>
                        <h5 class="mb-1">Risk Queue</h5>
                        <p class="text-muted fs-12 mb-0">Projects needing immediate follow-up.</p>
                    </div>
                    <span class="badge bg-soft-danger text-danger"><?= number_format(count($riskProjects)) ?> items</span>
                </div>
                <?php if ($riskProjects !== []): ?>
                    <?php foreach ($riskProjects as $risk): ?>
                        <div class="iv-risk-item">
                            <div class="fw-semibold text-dark"><?= htmlspecialchars((string) $risk['project_name']) ?></div>
                            <div class="fs-12 text-muted mt-1"><?= htmlspecialchars((string) ($risk['owner_name'] ?: 'Unassigned owner')) ?></div>
                            <div class="fs-12 mt-2">
                                <span class="text-danger"><?= htmlspecialchars((string) ($risk['project_status'] ?: 'No status')) ?></span>
                                <?php if (!empty($risk['end_date'])): ?>
                                    <span class="text-muted">- due <?= date('d M Y', strtotime((string) $risk['end_date'])) ?></span>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="text-muted fs-13">No immediate project risks are visible right now.</div>
                <?php endif; ?>
            </div>

            <div class="iv-report-panel">
                <div class="d-flex align-items-center justify-content-between mb-3">
                    <div>
                        <h5 class="mb-1">Upcoming Milestones</h5>
                        <p class="text-muted fs-12 mb-0">Next due work items across the visible portfolio.</p>
                    </div>
                    <span class="badge bg-soft-info text-info"><?= number_format(count($upcomingMilestones)) ?> upcoming</span>
                </div>
                <?php if ($upcomingMilestones !== []): ?>
                    <?php foreach ($upcomingMilestones as $milestone): ?>
                        <div class="iv-milestone-item">
                            <div class="fw-semibold text-dark"><?= htmlspecialchars((string) $milestone['title']) ?></div>
                            <div class="fs-12 text-muted mt-1"><?= htmlspecialchars((string) $milestone['project_name']) ?></div>
                            <div class="fs-12 text-primary mt-2"><?= date('d M Y', strtotime((string) $milestone['due_date'])) ?></div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="text-muted fs-13">No upcoming milestones are scheduled yet.</div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>
