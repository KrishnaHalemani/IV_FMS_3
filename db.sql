CREATE DATABASE FMS_DB;
USE FMS_DB;

CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    email VARCHAR(255) UNIQUE NOT NULL,
    username VARCHAR(50) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    role ENUM('master','super','admin','user') NOT NULL,
    created_by INT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

ALTER TABLE users
ADD CONSTRAINT fk_users_created_by
FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL;

INSERT INTO users (email,username, password, role) VALUES
('master@gmail.com', 'master', 'master123', 'master'),
('superadmin@gmail.com', 'superadmin', 'super123', 'super'),
('admin@gmail.com', 'admin', 'admin123', 'admin'),
('user@gmail.com', 'user', 'user123', 'user');


CREATE TABLE projects (
    id INT AUTO_INCREMENT PRIMARY KEY,
    project_name VARCHAR(255) NOT NULL,
    description TEXT,
    customer_name VARCHAR(255),
    start_date DATE,
    end_date DATE,
    status ENUM('Not Started','In Progress','On Hold','Finished','Declined') DEFAULT 'Not Started',
    assigned_user_id INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (assigned_user_id) REFERENCES users(id)
);

-- before runnning the code below make sure to have user and project table of above code in your database.

ALTER TABLE projects
ADD COLUMN project_type VARCHAR(20),
ADD COLUMN project_manage VARCHAR(20),
ADD COLUMN project_code VARCHAR(50),
ADD COLUMN project_priority VARCHAR(20),
ADD COLUMN project_hours INT,
ADD COLUMN estimated_budget DECIMAL(12,2),
ADD COLUMN billing_type VARCHAR(50),
ADD COLUMN project_status VARCHAR(30),
ADD COLUMN release_date DATE,
ADD COLUMN completion_date DATE,
ADD COLUMN client_id INT;

CREATE TABLE clients (
    id INT AUTO_INCREMENT PRIMARY KEY,
    client_name VARCHAR(255) NOT NULL,
    email VARCHAR(255),
    phone VARCHAR(20),
    company_name VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE employees (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    email VARCHAR(255),
    role VARCHAR(50),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE project_employees (
    id INT AUTO_INCREMENT PRIMARY KEY,
    project_id INT,
    employee_id INT,
    FOREIGN KEY (project_id) REFERENCES projects(id) ON DELETE CASCADE,
    FOREIGN KEY (employee_id) REFERENCES employees(id) ON DELETE CASCADE
);
CREATE TABLE project_targets (
    id INT AUTO_INCREMENT PRIMARY KEY,
    project_id INT,
    title VARCHAR(255),
    description TEXT,
    due_date DATE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (project_id) REFERENCES projects(id) ON DELETE CASCADE
);
CREATE TABLE project_files (
    id INT AUTO_INCREMENT PRIMARY KEY,
    project_id INT,
    file_name VARCHAR(255),
    file_path VARCHAR(255),
    uploaded_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (project_id) REFERENCES projects(id) ON DELETE CASCADE
);



CREATE TABLE customers (
    id INT AUTO_INCREMENT PRIMARY KEY,
    customer_name VARCHAR(255) NOT NULL,
    email VARCHAR(255),
    phone VARCHAR(20),
    company_name VARCHAR(255),
    address TEXT,
    about TEXT,
    dob DATE,
    status ENUM('Active','Inactive') DEFAULT 'Active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE students (
    id INT AUTO_INCREMENT PRIMARY KEY,
    student_name VARCHAR(255) NOT NULL,
    email VARCHAR(255),
    phone VARCHAR(20),
    course VARCHAR(255),
    address TEXT,
    about TEXT,
    dob DATE,
    status ENUM('Active','Inactive') DEFAULT 'Active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);




-- before runnning the code below make sure to have  client table of above code in your database.

ALTER TABLE projects



ADD COLUMN created_by INT;


CREATE TABLE invoices (
    id INT AUTO_INCREMENT PRIMARY KEY,

    issue_date DATE,
    invoice_label VARCHAR(255),
    invoice_number VARCHAR(100),
    invoice_product VARCHAR(255),

    from_name VARCHAR(255),
    from_email VARCHAR(255),
    from_phone VARCHAR(50),
    from_address TEXT,

    to_name VARCHAR(255),
    to_email VARCHAR(255),
    to_phone VARCHAR(50),
    to_address TEXT,

    invoice_note TEXT,

    sub_total DECIMAL(10,2),
    tax_percent DECIMAL(5,2),
    tax_amount DECIMAL(10,2),
    grand_total DECIMAL(10,2),

    currency VARCHAR(10),
    discount DECIMAL(10,2),

    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE invoice_items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    invoice_id INT NOT NULL,
    description VARCHAR(255) NOT NULL,
    qty DECIMAL(10,2) NOT NULL,
    rate DECIMAL(10,2) NOT NULL,
    amount DECIMAL(10,2) NOT NULL,
    sort_order INT NOT NULL DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_invoice_items_invoice FOREIGN KEY (invoice_id) REFERENCES invoices(id) ON DELETE CASCADE
);

-- please run the query for the student file 



ALTER TABLE projects DROP COLUMN status;
ALTER TABLE projects
CHANGE client_id customer_id INT(11);

ALTER TABLE projects
ADD COLUMN related_invoice_id INT NULL AFTER customer_id;

CREATE TABLE IF NOT EXISTS students (
    id INT AUTO_INCREMENT PRIMARY KEY,
    student_name VARCHAR(255) NOT NULL,
    email VARCHAR(255),
    phone VARCHAR(20),
    course VARCHAR(255),
    address TEXT,
    about TEXT,
    dob DATE,
    status ENUM('Active','Inactive') DEFAULT 'Active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS notifications (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    type VARCHAR(60) NOT NULL,
    title VARCHAR(255) NOT NULL,
    message TEXT NOT NULL,
    link VARCHAR(255) NULL,
    is_read TINYINT(1) NOT NULL DEFAULT 0,
    created_by INT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    read_at TIMESTAMP NULL DEFAULT NULL,
    INDEX idx_notifications_user_read (user_id, is_read),
    INDEX idx_notifications_created_at (created_at),
    CONSTRAINT fk_notifications_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    CONSTRAINT fk_notifications_created_by FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL
);
