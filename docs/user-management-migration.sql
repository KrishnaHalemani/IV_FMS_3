ALTER TABLE users
ADD COLUMN created_by INT NULL AFTER role;

ALTER TABLE users
ADD CONSTRAINT fk_users_created_by
FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL;
