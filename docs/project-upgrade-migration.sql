ALTER TABLE projects
ADD COLUMN project_code VARCHAR(50) NULL AFTER project_manage,
ADD COLUMN project_priority VARCHAR(20) NULL AFTER project_code,
ADD COLUMN estimated_budget DECIMAL(12,2) NULL AFTER project_hours,
ADD COLUMN related_invoice_id INT NULL AFTER customer_id;
