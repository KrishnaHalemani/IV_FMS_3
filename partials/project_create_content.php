<?php
$roleGuidance = match ($projectRole) {
    'master' => 'Master can assign project ownership to superadmins, admins, and users. Use this page to create the full operating record from the start.',
    'super' => 'Superadmin can assign project ownership to admins and users within their hierarchy. Keep ownership and milestones clear for delivery control.',
    'admin' => 'Admin can assign project ownership to users they created. Use lean project details so execution starts quickly.',
    default => 'Create a structured project record with clear ownership and milestones.',
};
?>

<style>
    .iv-create-shell { display: grid; gap: 1.5rem; }
    .iv-create-hero, .iv-create-card {
        background: linear-gradient(180deg, #ffffff 0%, #f8fbff 100%);
        border: 1px solid #dce6f4;
        border-radius: 22px;
        box-shadow: 0 24px 60px rgba(18, 38, 63, 0.08);
    }
    .iv-create-hero { padding: 1.75rem; }
    .iv-create-form { display: grid; gap: 1.5rem; }
    .iv-create-card { padding: 1.5rem; }
    .iv-create-card-head { margin-bottom: 1.2rem; }
    .iv-create-card-head h4 { margin-bottom: 0.4rem; font-size: 1.05rem; font-weight: 700; color: #0f172a; }
    .iv-create-grid { display: grid; grid-template-columns: repeat(12, minmax(0, 1fr)); gap: 1rem; }
    .iv-col-12 { grid-column: span 12; }
    .iv-col-8 { grid-column: span 8; }
    .iv-col-6 { grid-column: span 6; }
    .iv-col-4 { grid-column: span 4; }
    .iv-note {
        border-radius: 16px;
        background: #eff6ff;
        color: #1d4ed8;
        border: 1px solid #bfdbfe;
        padding: 0.9rem 1rem;
        font-size: 0.92rem;
    }
    .iv-milestones { display: grid; gap: 1rem; }
    .iv-milestone-item {
        border: 1px solid #e2e8f0;
        border-radius: 16px;
        padding: 1rem;
        background: #fff;
    }
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
    .iv-form-actions {
        display: flex;
        justify-content: space-between;
        gap: 1rem;
        align-items: center;
        flex-wrap: wrap;
    }
    @media (max-width: 991px) {
        .iv-col-8, .iv-col-6, .iv-col-4 { grid-column: span 12; }
    }
</style>

<div class="iv-create-shell">
    <section class="iv-create-hero">
        <div class="d-flex flex-wrap justify-content-between gap-3 align-items-start">
            <div>
                <h3 class="mb-2"><?= htmlspecialchars($projectCreateHeading) ?></h3>
                <p class="mb-0 text-muted"><?= htmlspecialchars($projectCreateSubheading) ?></p>
            </div>
            <div class="d-flex flex-wrap gap-2">
                <a href="<?= htmlspecialchars($projectListUrl) ?>" class="btn btn-light">Back to Projects</a>
            </div>
        </div>
        <div class="iv-note mt-3"><?= htmlspecialchars($roleGuidance) ?></div>
    </section>

    <form class="iv-create-form" id="projectCreateForm" method="post" action="<?= htmlspecialchars($projectCreateAction) ?>" enctype="multipart/form-data">
        <input type="hidden" name="project_type" value="team">
        <input type="hidden" name="project_manage" value="hierarchy">

        <section class="iv-create-card">
            <div class="iv-create-card-head">
                <h4>Core Setup</h4>
                <p class="text-muted mb-0">Keep the project identity, ownership, and operating status crisp.</p>
            </div>

            <div class="iv-create-grid">
                <div class="iv-col-4">
                    <label class="form-label">Project Code</label>
                    <input class="form-control" type="text" name="project_code" value="<?= htmlspecialchars($projectCode) ?>" readonly>
                </div>
                <div class="iv-col-4">
                    <label class="form-label">Priority <span class="text-danger">*</span></label>
                    <select class="form-select" name="project_priority" required>
                        <option value="medium" selected>Medium</option>
                        <option value="low">Low</option>
                        <option value="high">High</option>
                        <option value="critical">Critical</option>
                    </select>
                </div>
                <div class="iv-col-4">
                    <label class="form-label">Status <span class="text-danger">*</span></label>
                    <select class="form-select" name="project_status" required>
                        <option value="draft" selected>Draft</option>
                        <option value="planned">Planned</option>
                        <option value="active">Active</option>
                        <option value="on_hold">On Hold</option>
                        <option value="completed">Completed</option>
                        <option value="cancelled">Cancelled</option>
                    </select>
                </div>
                <div class="iv-col-8">
                    <label class="form-label">Project Name <span class="text-danger">*</span></label>
                    <input class="form-control" type="text" name="project_name" placeholder="Example: ERP rollout for North Region" required>
                </div>
                <div class="iv-col-4">
                    <label class="form-label">Project Owner <span class="text-danger">*</span></label>
                    <select class="form-select" name="assigned_user_id" required>
                        <option value="">Select an owner</option>
                        <?php foreach ($assignableUsers as $assignableUser): ?>
                            <option value="<?= (int) $assignableUser['id'] ?>">
                                <?= htmlspecialchars((string) $assignableUser['username']) ?> (<?= htmlspecialchars(ucfirst((string) $assignableUser['role'])) ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="iv-col-12">
                    <label class="form-label">Project Brief <span class="text-danger">*</span></label>
                    <textarea class="form-control" rows="4" name="description" placeholder="Describe scope, expected outcome, and the first delivery milestone." required></textarea>
                </div>
            </div>
        </section>

        <section class="iv-create-card">
            <div class="iv-create-card-head">
                <h4>Business Context</h4>
                <p class="text-muted mb-0">Link projects with clients and IMS records so reports stay connected.</p>
            </div>

            <div class="iv-create-grid">
                <div class="iv-col-4">
                    <label class="form-label">Client</label>
                    <select class="form-select" name="customer_id">
                        <option value="">Internal or unlinked project</option>
                        <?php foreach ($customers as $customer): ?>
                            <option value="<?= (int) $customer['id'] ?>">
                                <?= htmlspecialchars((string) $customer['customer_name']) ?>
                                <?php if (!empty($customer['company_name'])): ?>
                                    - <?= htmlspecialchars((string) $customer['company_name']) ?>
                                <?php endif; ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="iv-col-4">
                    <label class="form-label">IMS Invoice</label>
                    <select class="form-select" name="related_invoice_id">
                        <option value="">Not linked yet</option>
                        <?php foreach ($invoices as $invoice): ?>
                            <option value="<?= (int) $invoice['id'] ?>">
                                <?= htmlspecialchars((string) $invoice['invoice_number']) ?> - <?= htmlspecialchars((string) ($invoice['to_name'] ?: 'Unnamed client')) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="iv-col-4">
                    <label class="form-label">Billing Type <span class="text-danger">*</span></label>
                    <select class="form-select" name="billing_type" required>
                        <option value="fixed" selected>Fixed Fee</option>
                        <option value="hourly">Hourly</option>
                        <option value="retainer">Retainer</option>
                        <option value="internal">Internal</option>
                    </select>
                </div>
                <div class="iv-col-4">
                    <label class="form-label">Planned Hours <span class="text-danger">*</span></label>
                    <input class="form-control" type="number" name="project_hours" min="1" value="40" required>
                </div>
                <div class="iv-col-4">
                    <label class="form-label">Estimated Budget</label>
                    <input class="form-control" type="number" name="estimated_budget" min="0" step="0.01" placeholder="0.00">
                </div>
                <div class="iv-col-4">
                    <label class="form-label">Start Date <span class="text-danger">*</span></label>
                    <input class="form-control" type="date" name="start_date" value="<?= htmlspecialchars(date('Y-m-d')) ?>" required>
                </div>
                <div class="iv-col-4">
                    <label class="form-label">End Date <span class="text-danger">*</span></label>
                    <input class="form-control" type="date" name="end_date" required>
                </div>
            </div>
        </section>

        <section class="iv-create-card">
            <div class="iv-create-card-head">
                <h4>Delivery Team</h4>
                <p class="text-muted mb-0">Attach employees who will help execute the work even if they are not the project owner.</p>
            </div>

            <div class="iv-create-grid">
                <div class="iv-col-12">
                    <label class="form-label">Team Members</label>
                    <div class="iv-team-picker">
                        <div class="iv-team-chip-list" id="teamMemberChips">
                            <span class="iv-team-empty">No team members selected yet.</span>
                        </div>
                        <select class="form-select" id="teamMemberPicker">
                            <option value="">Add team member</option>
                            <?php foreach ($employees as $employee): ?>
                                <option value="<?= (int) $employee['id'] ?>"><?= htmlspecialchars((string) $employee['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <select class="form-select d-none" id="teamMembersSelect" name="employee_ids[]" multiple size="<?= max(4, min(8, count($employees))) ?>" aria-hidden="true" tabindex="-1">
                        <?php foreach ($employees as $employee): ?>
                            <option value="<?= (int) $employee['id'] ?>"><?= htmlspecialchars((string) $employee['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                    <div class="form-text">Add members from the dropdown. Use the x on a chip to remove them.</div>
                </div>
            </div>
        </section>

        <section class="iv-create-card">
            <div class="iv-create-card-head d-flex justify-content-between gap-3 align-items-start">
                <div>
                    <h4>Milestones</h4>
                    <p class="text-muted mb-0">Add only the checkpoints needed to start execution well.</p>
                </div>
                <button type="button" class="btn btn-light" id="addMilestoneButton">Add Milestone</button>
            </div>

            <div class="iv-milestones" id="milestonesList">
                <div class="iv-milestone-item">
                    <div class="iv-create-grid">
                        <div class="iv-col-6">
                            <label class="form-label">Milestone Title</label>
                            <input class="form-control" type="text" name="milestone_title[]" placeholder="Kickoff completed">
                        </div>
                        <div class="iv-col-6">
                            <label class="form-label">Due Date</label>
                            <input class="form-control" type="date" name="milestone_due_date[]">
                        </div>
                        <div class="iv-col-12">
                            <label class="form-label">Description</label>
                            <textarea class="form-control" rows="3" name="milestone_description[]" placeholder="What must be complete for this checkpoint?"></textarea>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <section class="iv-create-card">
            <div class="iv-form-actions">
                <div class="text-muted">Projects will appear on reports, dashboards, and notifications once created.</div>
                <div class="d-flex gap-2">
                    <a href="<?= htmlspecialchars($projectListUrl) ?>" class="btn btn-light">Cancel</a>
                    <button type="submit" class="btn btn-primary">Create Project</button>
                </div>
            </div>
        </section>
    </form>
</div>

<script>
    (function () {
        const milestonesList = document.getElementById('milestonesList');
        const addMilestoneButton = document.getElementById('addMilestoneButton');
        const endDateInput = document.querySelector('input[name="end_date"]');
        const startDateInput = document.querySelector('input[name="start_date"]');
        const teamMembersSelect = document.getElementById('teamMembersSelect');
        const teamMemberPicker = document.getElementById('teamMemberPicker');
        const teamMemberChips = document.getElementById('teamMemberChips');

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

        if (startDateInput && endDateInput && !endDateInput.value) {
            endDateInput.value = startDateInput.value;
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

        if (addMilestoneButton && milestonesList) {
            addMilestoneButton.addEventListener('click', function () {
                const wrapper = document.createElement('div');
                wrapper.className = 'iv-milestone-item';
                wrapper.innerHTML = `
                    <div class="iv-create-grid">
                        <div class="iv-col-6">
                            <label class="form-label">Milestone Title</label>
                            <input class="form-control" type="text" name="milestone_title[]" placeholder="Client sign-off">
                        </div>
                        <div class="iv-col-6">
                            <label class="form-label">Due Date</label>
                            <input class="form-control" type="date" name="milestone_due_date[]">
                        </div>
                        <div class="iv-col-12">
                            <label class="form-label">Description</label>
                            <textarea class="form-control" rows="3" name="milestone_description[]" placeholder="What must be complete for this checkpoint?"></textarea>
                        </div>
                        <div class="iv-col-12 text-end">
                            <button type="button" class="btn btn-sm btn-outline-danger iv-remove-milestone">Remove</button>
                        </div>
                    </div>
                `;
                milestonesList.appendChild(wrapper);
            });

            milestonesList.addEventListener('click', function (event) {
                const removeButton = event.target.closest('.iv-remove-milestone');
                if (!removeButton) {
                    return;
                }

                const milestone = removeButton.closest('.iv-milestone-item');
                if (milestone) {
                    milestone.remove();
                }
            });
        }
    })();
</script>
