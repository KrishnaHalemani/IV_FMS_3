# Franchise Management System (FMS)

This project is a role-based Franchise Management System built with PHP and MySQL for managing franchise operations, staff, projects, customers, students, and invoices.

It is designed around one core business idea:

- `master` owns and oversees franchises
- `super` manages a franchise at a senior level
- `admin` handles daily branch operations
- `user` works on assigned projects inside the franchise

## What This FMS Does

This system helps a franchise business run its branch operations in a structured way.

Main business areas:

- franchise ownership and branch structure
- employee and login management
- customer and student management
- invoice and payment record management
- project creation, assignment, and tracking
- role-based dashboards and workspace access
- notification and reporting support

## Core Features

### 1. Franchise Management

The `master` role can create and manage franchise records.

Relevant files:

- [franchisees.php](/c:/xampp/htdocs/FMS_new/FMS_IV-main/franchisees.php)
- [franchisee-create.php](/c:/xampp/htdocs/FMS_new/FMS_IV-main/franchisee-create.php)
- [franchisee-view.php](/c:/xampp/htdocs/FMS_new/FMS_IV-main/franchisee-view.php)

Use case:

- company owner creates a Mumbai franchise
- later creates a Delhi franchise
- each branch then gets its own staff and branch data

### 2. Role-Based User Management

The app supports multiple levels of users with controlled creation rules.

Relevant files:

- [user-management.php](/c:/xampp/htdocs/FMS_new/FMS_IV-main/user-management.php)
- [config/user_management.php](/c:/xampp/htdocs/FMS_new/FMS_IV-main/config/user_management.php)
- [config/franchise_binding.php](/c:/xampp/htdocs/FMS_new/FMS_IV-main/config/franchise_binding.php)

Important rules:

- non-master users must belong to a franchise
- login accounts should be tied to employee records
- managers can create lower-level roles only within allowed hierarchy

### 3. Employee Management

Managers can create and maintain employee records for their franchise.

Relevant files:

- [employee.php](/c:/xampp/htdocs/FMS_new/FMS_IV-main/employee.php)
- [employee-create.php](/c:/xampp/htdocs/FMS_new/FMS_IV-main/employee-create.php)
- [employee-edit.php](/c:/xampp/htdocs/FMS_new/FMS_IV-main/employee-edit.php)

Use case:

- branch admin adds a counselor, designer, or operator
- employee may also receive a system login depending on role

### 4. Customer Management

Each franchise can manage its own customer records.

Relevant files:

- [customers.php](/c:/xampp/htdocs/FMS_new/FMS_IV-main/customers.php)
- [customers-create.php](/c:/xampp/htdocs/FMS_new/FMS_IV-main/customers-create.php)
- [save-customer.php](/c:/xampp/htdocs/FMS_new/FMS_IV-main/save-customer.php)
- [config/business_scope.php](/c:/xampp/htdocs/FMS_new/FMS_IV-main/config/business_scope.php)

Current behavior:

- `master` can see all customers
- `super` and `admin` see only their franchise customers
- `user` cannot create or manage customers

### 5. Student Management

The system also supports student records, useful for education, admissions, visa, coaching, or service-oriented franchises.

Relevant files:

- [students.php](/c:/xampp/htdocs/FMS_new/FMS_IV-main/students.php)
- [students-create.php](/c:/xampp/htdocs/FMS_new/FMS_IV-main/students-create.php)
- [students-view.php](/c:/xampp/htdocs/FMS_new/FMS_IV-main/students-view.php)
- [save-student.php](/c:/xampp/htdocs/FMS_new/FMS_IV-main/save-student.php)

### 6. Invoice and Payment Management

Franchise managers can create and manage invoice records within their own branch scope.

Relevant files:

- [payment.php](/c:/xampp/htdocs/FMS_new/FMS_IV-main/payment.php)
- [invoice-create.php](/c:/xampp/htdocs/FMS_new/FMS_IV-main/invoice-create.php)
- [invoice-view.php](/c:/xampp/htdocs/FMS_new/FMS_IV-main/invoice-view.php)
- [invoice-pdf.php](/c:/xampp/htdocs/FMS_new/FMS_IV-main/invoice-pdf.php)
- [save-invoice.php](/c:/xampp/htdocs/FMS_new/FMS_IV-main/save-invoice.php)

Current behavior:

- invoice records are scoped by `franchisee_id`
- `master` can see all invoices
- `super` and `admin` can manage invoices in their franchise
- `user` cannot create, delete, or manage invoices

### 7. Project Management

Projects are the main operational workspace of the system.

Relevant files:

- [projects.php](/c:/xampp/htdocs/FMS_new/FMS_IV-main/projects.php)
- [projects-create.php](/c:/xampp/htdocs/FMS_new/FMS_IV-main/projects-create.php)
- [projects-store.php](/c:/xampp/htdocs/FMS_new/FMS_IV-main/projects-store.php)
- [projects-view.php](/c:/xampp/htdocs/FMS_new/FMS_IV-main/projects-view.php)
- [config/project_access.php](/c:/xampp/htdocs/FMS_new/FMS_IV-main/config/project_access.php)

Project capabilities include:

- create projects
- assign projects to staff
- view project status and timelines
- scope project visibility by role and franchise
- restrict worker access to assigned project workspace

### 8. Worker Workspace

The `user` role now works as a dedicated worker/project workspace.

Relevant files:

- [user/projects.php](/c:/xampp/htdocs/FMS_new/FMS_IV-main/user/projects.php)
- [user/projects-view.php](/c:/xampp/htdocs/FMS_new/FMS_IV-main/user/projects-view.php)
- [user/sidebar.php](/c:/xampp/htdocs/FMS_new/FMS_IV-main/user/sidebar.php)

Current behavior:

- `user` can access project workspace only
- `user` cannot create customers
- `user` cannot create invoices
- `user` cannot register users
- `user` cannot edit project assignment, dates, or status through worker routes

### 9. Dashboards and Reports

The system provides role-aware dashboard metrics and project reporting.

Relevant files:

- [index.php](/c:/xampp/htdocs/FMS_new/FMS_IV-main/index.php)
- [partials/dashboard_helpers.php](/c:/xampp/htdocs/FMS_new/FMS_IV-main/partials/dashboard_helpers.php)
- [reports-project.php](/c:/xampp/htdocs/FMS_new/FMS_IV-main/reports-project.php)
- [partials/project_reports_helpers.php](/c:/xampp/htdocs/FMS_new/FMS_IV-main/partials/project_reports_helpers.php)

### 10. Notifications

The project module includes notification support for assignment and status-related events.

Relevant files:

- [notifications.php](/c:/xampp/htdocs/FMS_new/FMS_IV-main/notifications.php)
- [config/notifications.php](/c:/xampp/htdocs/FMS_new/FMS_IV-main/config/notifications.php)

## How The System Works

### Login Flow

Relevant files:

- [login.php](/c:/xampp/htdocs/FMS_new/FMS_IV-main/login.php)
- [dashboard.php](/c:/xampp/htdocs/FMS_new/FMS_IV-main/dashboard.php)
- [config/current_user.php](/c:/xampp/htdocs/FMS_new/FMS_IV-main/config/current_user.php)

Flow:

1. User logs in.
2. Session is created with `user_id` and `role`.
3. Franchise context is refreshed from employee/franchise mapping.
4. Non-master users without valid franchise binding are blocked.
5. User is routed to the correct workspace:
   - `master`, `super`, `admin` -> root app
   - `user` -> worker project workspace

### Franchise Data Scope

Relevant files:

- [config/business_scope.php](/c:/xampp/htdocs/FMS_new/FMS_IV-main/config/business_scope.php)

Scope rules:

- `master` can access all branch data
- `super` and `admin` are limited to their franchise
- `user` is limited to worker project workspace

This applies to:

- customers
- students
- invoices
- project-linked selections and access

### Unified App Structure

The system now follows this structure:

- root app for `master`, `super`, and `admin`
- `user/` app for worker-only access

The old manager app folders are now compatibility layers:

- [config/legacy_app_redirect.php](/c:/xampp/htdocs/FMS_new/FMS_IV-main/config/legacy_app_redirect.php)

This avoids maintaining separate business logic in `admin/` and `super/`.

## Practical Example

Example: Education or service franchise chain

1. `master` creates franchise `Mumbai Branch`
2. `master` or higher manager creates a `super` for Mumbai
3. `super` adds branch employees:
   - `admin`
   - `user` staff members
4. `admin` creates a customer
5. `admin` creates a student record
6. `admin` creates an invoice for the customer
7. `admin` creates a project and assigns a worker
8. assigned `user` logs in and sees only project workspace
9. `master` can still monitor all franchise activity at top level

## Security and Access Improvements Already Applied

The current system includes these stabilization changes:

- direct write endpoints are protected by session and role checks
- public standalone registration flow is no longer used for unsafe account creation
- non-master users must be franchise-bound
- customers, students, and invoices are franchise-scoped
- worker role is restricted to project-only workspace
- `admin/` and `super/` business pages now redirect to the unified root app

Key files:

- [config/access_control.php](/c:/xampp/htdocs/FMS_new/FMS_IV-main/config/access_control.php)
- [config/franchise_binding.php](/c:/xampp/htdocs/FMS_new/FMS_IV-main/config/franchise_binding.php)
- [config/business_scope.php](/c:/xampp/htdocs/FMS_new/FMS_IV-main/config/business_scope.php)
- [config/project_access.php](/c:/xampp/htdocs/FMS_new/FMS_IV-main/config/project_access.php)

## Database Notes

The database schema is included in:

- [db.sql](/c:/xampp/htdocs/FMS_new/FMS_IV-main/db.sql)

Business tables currently include:

- `franchisees`
- `employees`
- `users`
- `customers`
- `students`
- `invoices`
- `invoice_items`
- `projects`
- `notifications`
- `activity_logs`

Important business columns:

- `franchisee_id`
- `created_by`
- role fields in user records

## Current Limitations

This system is now much safer and more consistent, but a few practical improvements are still recommended:

- full UI testing by role in browser
- stronger audit logging for key CRUD actions
- better validation and user feedback on forms
- cleanup of legacy duplicate assets and unused compatibility files
- formal database migrations instead of manual SQL patches

## Recommended User Journey By Role

### Master

- manage franchises
- view all branch performance
- oversee staff and projects
- maintain top-level control

### Super

- manage one franchise
- manage branch staff and user access
- manage clients, students, invoices, and projects inside that franchise

### Admin

- run day-to-day branch operations
- create customer, student, invoice, and project records
- assign and monitor work inside the franchise

### User

- open assigned project workspace
- review project details
- work only within operational project scope

## Quick Summary

This FMS is a franchise-oriented business operations platform with:

- franchise ownership control
- branch-scoped staff management
- branch-scoped CRM and invoice handling
- project assignment and tracking
- worker-only project workspace
- role-based access and dashboard flow

