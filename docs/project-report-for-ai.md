# Infinite Vision FMS: Project Report for AI Handoff

## Overview

This repository is a PHP + MySQL franchise and operations management system running in XAMPP. It uses server-rendered PHP pages, Bootstrap-based UI assets, and a shared MySQL database defined largely in `db.sql`.

The application appears to support multiple roles:

- `master`
- `super`
- `admin`
- `user`

Core business areas currently present in the codebase:

- authentication and role-based access
- projects
- franchisees
- employees
- customers/clients/students
- invoices and payment listing
- reports
- notifications

This is not a framework-based application. Most pages are direct PHP entry points that query the database, render HTML, and include shared layout files such as `header.php`, `sidebar.php`, and `footer.php`.

## Runtime Stack

- PHP application served from XAMPP
- MySQL database
- Bootstrap / vendor JS / DataTables on the frontend
- mostly page-per-feature architecture

Working directory:

- `c:\xampp\htdocs\FMS_new\FMS_IV-main`

## High-Level File Structure

- `config/`: DB connection, roles, feature helpers
- `partials/`: reusable UI blocks for projects/reports
- `admin/`, `super/`, `user/`: role-specific copies/variants of pages
- root PHP files: master/general pages
- `assets/`: CSS, JS, images, vendor frontend files
- `docs/`: migration scripts and documentation assets
- `db.sql`: main schema bootstrap and later schema alterations

## Important Application Areas

### 1. Franchisees

Main files:

- `franchisees.php`
- `franchisee-create.php`
- `franchisee-view.php`
- `franchisee-delete.php`
- `config/franchisees.php`

What it does:

- only `master` can access franchisee management through `iv_require_master_session()`
- franchisees are stored in the `franchisees` table
- projects and employees can be assigned to a franchisee through `franchisee_id`

Current franchise list behavior:

- shows code, name, owner, contact, project count, status, creator
- computes `project_count` via `COUNT(p.id)`
- computes `total_budget` via `SUM(p.estimated_budget)`
- labels that value as `Budget`

Important limitation:

- franchise pages currently show project budget, not actual revenue

### 2. Projects

Main files:

- `projects.php`
- `projects-create.php`
- `projects-store.php`
- `projects-view.php`
- `partials/project_create_content.php`

What it does:

- supports creating and assigning operational projects
- includes links to customer, franchisee, assigned user, and invoice candidates
- uses `estimated_budget` as the main money-related field on projects

Observed project-related DB fields in `projects`:

- `project_name`
- `description`
- `customer_name`
- `start_date`
- `end_date`
- `assigned_user_id`
- `project_type`
- `project_manage`
- `project_code`
- `project_priority`
- `project_hours`
- `estimated_budget`
- `billing_type`
- `project_status`
- `release_date`
- `completion_date`
- `customer_id`
- `created_by`
- `related_invoice_id`
- `franchisee_id`

### 3. Invoices and Payment

Main files:

- `invoice-create.php`
- `save-invoice.php`
- `invoice-view.php`
- `invoice-pdf.php`
- `invoice-delete.php`
- `payment.php`

What it does:

- invoice creation accepts issue date, billing section, party details, GST number, and line items
- `save-invoice.php` calculates:
  - subtotal
  - 18% tax
  - grand total
- data is stored in:
  - `invoices`
  - `invoice_items`
- `payment.php` is effectively an invoice listing page, not a true payment ledger

Important limitation:

- invoices are not currently linked to a franchisee when saved
- invoices are not clearly linked back to a project during save
- therefore franchise revenue cannot be derived reliably from invoices yet

### 4. Roles and Access Control

Relevant files:

- `config/roles.php`
- `config/franchisees.php`
- `config/user_management.php`

Observed pattern:

- session-based role checks
- direct redirects or `403` responses
- role-specific folders often duplicate similar pages instead of using a unified controller pattern

## Key Database Tables

Based on `db.sql`, these tables matter most for current business flow:

- `users`
- `projects`
- `clients`
- `customers`
- `employees`
- `project_employees`
- `project_targets`
- `project_files`
- `students`
- `invoices`
- `invoice_items`
- `notifications`
- `franchisees`

## Important Relationships

- `users.created_by -> users.id`
- `projects.assigned_user_id -> users.id`
- `projects.franchisee_id -> franchisees.id`
- `employees.franchisee_id -> franchisees.id`
- `employees.user_id -> users.id`
- `invoice_items.invoice_id -> invoices.id`

Potential but underused relationship:

- `projects.related_invoice_id`

This field exists in schema, but the current invoice save flow does not appear to populate it.

## Current Revenue Logic

### What exists today

The franchise listing uses:

- `COUNT(projects.id)` as project count
- `SUM(projects.estimated_budget)` as budget total

This means the UI currently shows projected value, not booked revenue.

### What is missing

To support real franchise revenue, the system needs one of these:

1. direct invoice-to-franchisee relation
2. invoice-to-project relation, then project-to-franchisee traversal

### Recommended revenue model

Best practical option:

- store `project_id` on each invoice, or
- store `franchisee_id` on each invoice

Then calculate:

- projected revenue: `SUM(projects.estimated_budget)`
- actual revenue: `SUM(invoices.grand_total)` for invoices tied to that franchise

## Important Product Gap

There is a mismatch between franchise reporting and invoicing:

- franchise pages summarize project budgets
- invoice/payment pages summarize invoices globally
- there is no robust linkage between the two

As a result, if someone asks "How much revenue did this franchise make?", the current system cannot answer accurately.

## Suggested Next Steps for Another AI

If the next task is to implement franchise revenue, follow this order:

1. inspect invoice creation and add linkage to either `project_id` or `franchisee_id`
2. update schema if needed with a migration
3. backfill invoice linkage where possible
4. update `payment.php` and invoice views to show linked franchise/project
5. update `franchisees.php` to display:
   - project count
   - projected budget
   - actual revenue
6. update `franchisee-view.php` to show a franchise-level revenue summary and optionally franchise invoices

## Implementation Risks

- duplicated pages under `admin/`, `super/`, and `user/` may need parallel updates
- schema evolution is happening directly in `db.sql`, so production-safe migrations may be missing
- naming is inconsistent across some areas:
  - clients vs customers
  - payment page vs invoice listing behavior
  - `status` vs `project_status`
- some pages are tightly coupled to direct SQL in the page itself

## Useful Entry Points for Future Work

- `db.sql`
- `config/franchisees.php`
- `franchisees.php`
- `franchisee-view.php`
- `projects-create.php`
- `projects-store.php`
- `invoice-create.php`
- `save-invoice.php`
- `payment.php`

## Short Summary

This project is a multi-role PHP/MySQL business management app with franchise, project, and invoice features. Franchise reporting currently uses project budget as a proxy for value, but true franchise revenue is not implemented because invoices are not properly bound to franchisees or projects during save. The clean next step is to bind invoices to projects or franchisees and compute revenue from invoice totals.
