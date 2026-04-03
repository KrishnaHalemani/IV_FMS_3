<?php
$projectSummary = $projectsData['summary'] ?? [];
$projectRows = $projectsData['rows'] ?? [];
$projectMilestones = $projectsData['upcoming_milestones'] ?? [];
$projectStatusBreakdown = $projectsData['status_breakdown'] ?? [];
$projectFilters = $projectsData['filters'] ?? [];
?>

<style>
    .iv-projects-shell { display: grid; gap: 1.5rem; }
    .iv-projects-hero, .iv-projects-card {
        background: linear-gradient(180deg, #ffffff 0%, #f8fbff 100%);
        border: 1px solid #dce6f4;
        border-radius: 22px;
        box-shadow: 0 24px 60px rgba(18, 38, 63, 0.08);
    }
    .iv-projects-hero { padding: 1.75rem; display: grid; gap: 1rem; }
    .iv-projects-kpis {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
        gap: 1rem;
    }
    .iv-projects-kpi {
        padding: 1rem 1.1rem;
        border-radius: 18px;
        background: #0f172a;
        color: #f8fafc;
        min-height: 120px;
    }
    .iv-projects-kpi:nth-child(2) { background: #123c69; }
    .iv-projects-kpi:nth-child(3) { background: #14532d; }
    .iv-projects-kpi:nth-child(4) { background: #7c2d12; }
    .iv-projects-kpi-label {
        font-size: 0.75rem;
        text-transform: uppercase;
        letter-spacing: 0.08em;
        opacity: 0.75;
    }
    .iv-projects-kpi-value { font-size: 2rem; font-weight: 700; line-height: 1.1; margin-top: 0.75rem; }
    .iv-projects-kpi-meta { font-size: 0.9rem; margin-top: 0.65rem; opacity: 0.82; }
    .iv-projects-grid {
        display: grid;
        grid-template-columns: minmax(0, 2.3fr) minmax(280px, 1fr);
        gap: 1.5rem;
    }
    .iv-projects-card { padding: 1.5rem; }
    .iv-projects-card-header {
        display: flex;
        justify-content: space-between;
        gap: 1rem;
        align-items: flex-start;
        margin-bottom: 1.25rem;
    }
    .iv-projects-card-title { margin: 0; font-size: 1.1rem; font-weight: 700; color: #0f172a; }
    .iv-projects-card-copy { margin: 0.35rem 0 0; color: #52607a; }
    .iv-projects-filter-grid {
        display: grid;
        grid-template-columns: 1.4fr repeat(2, minmax(160px, 0.8fr)) auto;
        gap: 1rem;
        align-items: end;
    }
    .iv-projects-stat-list, .iv-projects-milestone-list { display: grid; gap: 0.85rem; }
    .iv-projects-stat-item, .iv-projects-milestone-item {
        border: 1px solid #e2e8f0;
        border-radius: 16px;
        padding: 0.95rem 1rem;
        background: #fff;
    }
    .iv-projects-table-wrap { overflow-x: auto; }
    .iv-projects-table th {
        font-size: 0.74rem;
        text-transform: uppercase;
        letter-spacing: 0.08em;
        color: #64748b;
        border-bottom-color: #dce6f4;
    }
    .iv-projects-table td { vertical-align: middle; border-bottom-color: #edf2f7; }
    .iv-projects-project-name { font-weight: 700; color: #0f172a; }
    .iv-projects-project-code { color: #64748b; font-size: 0.82rem; }
    .iv-projects-chip-row { display: flex; gap: 0.5rem; flex-wrap: wrap; }
    .iv-projects-pill {
        display: inline-flex;
        align-items: center;
        padding: 0.34rem 0.65rem;
        border-radius: 999px;
        font-size: 0.76rem;
        font-weight: 700;
    }
    .iv-projects-empty { text-align: center; padding: 2.5rem 1rem; color: #64748b; }
    @media (max-width: 1199px) { .iv-projects-grid { grid-template-columns: 1fr; } }
    @media (max-width: 767px) {
        .iv-projects-filter-grid { grid-template-columns: 1fr; }
        .iv-projects-card, .iv-projects-hero { padding: 1.1rem; }
    }
</style>

<div class="iv-projects-shell">
    <section class="iv-projects-hero">
        <div class="d-flex flex-wrap justify-content-between gap-3 align-items-start">
            <div>
                <h3 class="mb-2"><?= htmlspecialchars($projectsPageHeading) ?></h3>
                <p class="mb-0 text-muted"><?= htmlspecialchars($projectsPageSubheading) ?></p>
            </div>
            <div class="d-flex flex-wrap gap-2">
                <a href="<?= htmlspecialchars($projectsReportUrl) ?>" class="btn btn-light">Open Reports</a>
                <a href="<?= htmlspecialchars($projectsCreateUrl) ?>" class="btn btn-primary">Create Project</a>
            </div>
        </div>

        <div class="iv-projects-kpis">
            <div class="iv-projects-kpi">
                <div class="iv-projects-kpi-label">Total Projects</div>
                <div class="iv-projects-kpi-value"><?= (int) ($projectSummary['total_projects'] ?? 0) ?></div>
                <div class="iv-projects-kpi-meta"><?= (int) ($projectSummary['planned_hours'] ?? 0) ?> planned hours</div>
            </div>
            <div class="iv-projects-kpi">
                <div class="iv-projects-kpi-label">Active Portfolio</div>
                <div class="iv-projects-kpi-value"><?= (int) ($projectSummary['active_projects'] ?? 0) ?></div>
                <div class="iv-projects-kpi-meta"><?= number_format((float) ($projectSummary['portfolio_budget'] ?? 0), 2) ?> budget value</div>
            </div>
            <div class="iv-projects-kpi">
                <div class="iv-projects-kpi-label">Completed</div>
                <div class="iv-projects-kpi-value"><?= (int) ($projectSummary['completed_projects'] ?? 0) ?></div>
                <div class="iv-projects-kpi-meta">Delivery visibility by role scope</div>
            </div>
            <div class="iv-projects-kpi">
                <div class="iv-projects-kpi-label">Needs Attention</div>
                <div class="iv-projects-kpi-value"><?= (int) (($projectSummary['delayed_projects'] ?? 0) + ($projectSummary['unowned_projects'] ?? 0)) ?></div>
                <div class="iv-projects-kpi-meta"><?= (int) ($projectSummary['delayed_projects'] ?? 0) ?> delayed and <?= (int) ($projectSummary['unowned_projects'] ?? 0) ?> unowned</div>
            </div>
        </div>
    </section>

    <div class="iv-projects-grid">
        <section class="iv-projects-card">
            <div class="iv-projects-card-header">
                <div>
                    <h4 class="iv-projects-card-title">Project Workspace</h4>
                    <p class="iv-projects-card-copy">Filter the portfolio, review assignments, and jump into delivery work quickly.</p>
                </div>
            </div>

            <form method="get" class="iv-projects-filter-grid mb-4">
                <div>
                    <label class="form-label" for="project-search">Search</label>
                    <input id="project-search" class="form-control" type="search" name="search" value="<?= htmlspecialchars((string) ($projectFilters['search'] ?? '')) ?>" placeholder="Project, code, client, owner">
                </div>
                <div>
                    <label class="form-label" for="project-status-filter">Status</label>
                    <select id="project-status-filter" class="form-select" name="status">
                        <?php foreach (($projectFilters['available_statuses'] ?? []) as $statusOption): ?>
                            <option value="<?= htmlspecialchars($statusOption) ?>" <?= (($projectFilters['status'] ?? 'all') === $statusOption) ? 'selected' : '' ?>>
                                <?= htmlspecialchars($statusOption === 'all' ? 'All statuses' : iv_project_label_from_key($statusOption)) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label class="form-label" for="project-priority-filter">Priority</label>
                    <select id="project-priority-filter" class="form-select" name="priority">
                        <?php foreach (($projectFilters['available_priorities'] ?? []) as $priorityOption): ?>
                            <option value="<?= htmlspecialchars($priorityOption) ?>" <?= (($projectFilters['priority'] ?? 'all') === $priorityOption) ? 'selected' : '' ?>>
                                <?= htmlspecialchars($priorityOption === 'all' ? 'All priorities' : iv_project_label_from_key($priorityOption)) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="d-flex gap-2">
                    <button type="submit" class="btn btn-primary w-100">Apply</button>
                    <a href="<?= htmlspecialchars($projectsIndexUrl) ?>" class="btn btn-light w-100">Reset</a>
                </div>
            </form>

            <div class="iv-projects-table-wrap">
                <table class="table iv-projects-table align-middle">
                    <thead>
                        <tr>
                            <th>Project</th>
                            <th>Client</th>
                            <th>Owner</th>
                            <th>Timeline</th>
                            <th>Delivery</th>
                            <th>Budget</th>
                            <th class="text-end">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($projectRows === []): ?>
                            <tr><td colspan="7" class="iv-projects-empty">No projects match the current filters.</td></tr>
                        <?php endif; ?>

                        <?php foreach ($projectRows as $projectRow): ?>
                            <?php
                            $statusKey = strtolower(str_replace([' ', '-'], '_', (string) ($projectRow['project_status'] ?? '')));
                            $priorityKey = strtolower(str_replace([' ', '-'], '_', (string) ($projectRow['project_priority'] ?? '')));
                            ?>
                            <tr>
                                <td>
                                    <div class="iv-projects-project-name"><?= htmlspecialchars((string) $projectRow['project_name']) ?></div>
                                    <div class="iv-projects-project-code"><?= htmlspecialchars((string) ($projectRow['project_code'] ?: 'No code')) ?></div>
                                    <div class="iv-projects-chip-row mt-2">
                                        <span class="iv-projects-pill bg-soft-<?= iv_project_badge_tone($priorityKey, 'priority') ?> text-<?= iv_project_badge_tone($priorityKey, 'priority') ?>"><?= htmlspecialchars(iv_project_label_from_key($priorityKey)) ?></span>
                                        <span class="iv-projects-pill bg-soft-<?= iv_project_badge_tone($statusKey) ?> text-<?= iv_project_badge_tone($statusKey) ?>"><?= htmlspecialchars(iv_project_label_from_key($statusKey)) ?></span>
                                        <?php if ((int) ($projectRow['is_delayed'] ?? 0) === 1): ?>
                                            <span class="iv-projects-pill bg-soft-danger text-danger">Delayed</span>
                                        <?php endif; ?>
                                    </div>
                                </td>
                                <td>
                                    <div class="fw-semibold text-dark"><?= htmlspecialchars((string) $projectRow['client_name']) ?></div>
                                    <div class="text-muted small">Invoice: <?= htmlspecialchars((string) $projectRow['invoice_number']) ?></div>
                                </td>
                                <td>
                                    <div class="fw-semibold text-dark"><?= htmlspecialchars((string) $projectRow['owner_name']) ?></div>
                                    <div class="text-muted small">Created by <?= htmlspecialchars((string) $projectRow['creator_name']) ?></div>
                                </td>
                                <td>
                                    <div class="fw-semibold text-dark"><?= htmlspecialchars((string) ($projectRow['start_date'] ?: '-')) ?></div>
                                    <div class="text-muted small">Due <?= htmlspecialchars((string) ($projectRow['end_date'] ?: '-')) ?></div>
                                </td>
                                <td>
                                    <div class="fw-semibold text-dark"><?= (int) ($projectRow['milestone_count'] ?? 0) ?> milestones</div>
                                    <div class="text-muted small"><?= (int) ($projectRow['project_hours'] ?? 0) ?> planned hrs</div>
                                </td>
                                <td>
                                    <div class="fw-semibold text-dark"><?= number_format((float) ($projectRow['estimated_budget'] ?? 0), 2) ?></div>
                                    <div class="text-muted small"><?= htmlspecialchars(ucfirst((string) ($projectRow['owner_role'] ?? ''))) ?></div>
                                </td>
                                <td class="text-end">
                                    <div class="d-flex justify-content-end gap-2">
                                        <a href="<?= htmlspecialchars($projectsViewUrlPrefix . (int) $projectRow['id']) ?>" class="btn btn-sm btn-light">Open</a>
                                        <form method="post" action="<?= htmlspecialchars($projectsDeleteUrl) ?>" onsubmit="return confirm('Delete this project?');">
                                            <input type="hidden" name="id" value="<?= (int) $projectRow['id'] ?>">
                                            <button type="submit" class="btn btn-sm btn-outline-danger">Delete</button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </section>

        <aside class="d-grid gap-4">
            <section class="iv-projects-card">
                <div class="iv-projects-card-header">
                    <div>
                        <h4 class="iv-projects-card-title">Portfolio Signals</h4>
                        <p class="iv-projects-card-copy">Quick read on where projects are clustering right now.</p>
                    </div>
                </div>
                <div class="iv-projects-stat-list">
                    <?php foreach ($projectStatusBreakdown as $breakdown): ?>
                        <div class="iv-projects-stat-item">
                            <div class="d-flex justify-content-between gap-3">
                                <strong><?= htmlspecialchars(iv_project_label_from_key((string) $breakdown['key'])) ?></strong>
                                <span class="text-muted"><?= (int) $breakdown['total'] ?></span>
                            </div>
                            <div class="progress mt-3 ht-6">
                                <div class="progress-bar bg-<?= iv_project_badge_tone((string) $breakdown['key']) ?>" style="width: <?= (int) (($projectSummary['total_projects'] ?? 0) > 0 ? round(((int) $breakdown['total'] / (int) $projectSummary['total_projects']) * 100) : 0) ?>%"></div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                    <?php if ($projectStatusBreakdown === []): ?>
                        <div class="iv-projects-stat-item text-muted">Status insights will appear as projects are created.</div>
                    <?php endif; ?>
                </div>
            </section>

            <section class="iv-projects-card">
                <div class="iv-projects-card-header">
                    <div>
                        <h4 class="iv-projects-card-title">Upcoming Milestones</h4>
                        <p class="iv-projects-card-copy">Delivery deadlines coming up next in your role scope.</p>
                    </div>
                </div>
                <div class="iv-projects-milestone-list">
                    <?php foreach ($projectMilestones as $milestone): ?>
                        <div class="iv-projects-milestone-item">
                            <div class="fw-semibold text-dark"><?= htmlspecialchars((string) $milestone['title']) ?></div>
                            <div class="text-muted small"><?= htmlspecialchars((string) $milestone['project_name']) ?></div>
                            <div class="small mt-2 text-primary">Due <?= htmlspecialchars((string) $milestone['due_date']) ?></div>
                        </div>
                    <?php endforeach; ?>
                    <?php if ($projectMilestones === []): ?>
                        <div class="iv-projects-milestone-item text-muted">No upcoming milestones yet.</div>
                    <?php endif; ?>
                </div>
            </section>
        </aside>
    </div>
</div>
