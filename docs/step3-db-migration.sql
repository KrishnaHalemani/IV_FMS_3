ALTER TABLE customers
    ADD COLUMN IF NOT EXISTS franchisee_id INT NULL AFTER status,
    ADD COLUMN IF NOT EXISTS created_by INT NULL AFTER franchisee_id;

ALTER TABLE students
    ADD COLUMN IF NOT EXISTS franchisee_id INT NULL AFTER status,
    ADD COLUMN IF NOT EXISTS created_by INT NULL AFTER franchisee_id;

ALTER TABLE invoices
    ADD COLUMN IF NOT EXISTS franchisee_id INT NULL AFTER discount,
    ADD COLUMN IF NOT EXISTS created_by INT NULL AFTER franchisee_id;

CREATE INDEX IF NOT EXISTS idx_customers_franchisee_id ON customers (franchisee_id);
CREATE INDEX IF NOT EXISTS idx_students_franchisee_id ON students (franchisee_id);
CREATE INDEX IF NOT EXISTS idx_invoices_franchisee_id ON invoices (franchisee_id);

UPDATE customers
SET franchisee_id = 1
WHERE franchisee_id IS NULL;

UPDATE students
SET franchisee_id = 1
WHERE franchisee_id IS NULL;

UPDATE invoices
SET franchisee_id = 1
WHERE franchisee_id IS NULL;
