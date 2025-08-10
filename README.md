# Construction ERP Management System (PHP + MySQL)

This is a fully functional Construction Project Management & Inventory ERP built in pure PHP (procedural) and MySQL.

Features
- User Authentication (Admin, Manager, Staff)
- Dashboard with quick stats and charts
- Project and hierarchical Tasks (Main -> Sub -> Ground)
- Material Requests and Stock Management (IN/OUT/ADJUST)
- Purchase Orders (PO) and Goods Receipt Note (GRN)
- Attendance and Salary calculation
- CSV Exports for reports

Technology Stack
- PHP 8+ (no framework)
- MySQL (InnoDB, FKs, indexes)
- Bootstrap 5, jQuery, DataTables, SweetAlert2, Chart.js via CDN

Directory Structure
- /assets (css, js)
- /includes (db.php, auth.php, header.php, footer.php)
- /modules (functional pages)
- config.php, sql_dump.sql, index.php, login.php, logout.php

Installation (XAMPP on Windows)
1) Copy the project folder to: C:\xampp\htdocs\TNTC
2) Start Apache and MySQL in XAMPP.
3) Create database:
   - Open http://localhost/phpmyadmin
   - Create a database named: construction_erp
4) Import schema and seed data:
   - In phpMyAdmin, select the construction_erp database
   - Go to Import and upload the file: sql_dump.sql (located in this folder)
5) Configure database credentials:
   - Edit config.php and set DB_HOST, DB_USER, DB_PASS as needed
6) Open the application:
   - Go to http://localhost/TNTC/login.php
   - On first launch, if no users exist, the app will prompt to create the first Admin user
7) Login and use the system.

Role-based Access
- Admin, Manager, and Staff roles are seeded in the roles table.
- All authenticated users can access the system; fine-grained restrictions can be applied using require_role(["Admin","Manager"]) inside module pages if needed.

Security
- Prepared statements for all DB writes, and for reads where user input is involved
- CSRF tokens in all forms
- Sessions configured with HttpOnly and SameSite

Notes
- Charts on the dashboard use current DB values
- Exports are available under Modules > Reports
- Stock logs track IN/OUT/ADJUST movements

Troubleshooting
- If you see DB connection errors: verify config.php credentials and that MySQL is running
- For 500 errors, check Apache error log: C:\xampp\apache\logs\error.log

License
- For internal use and testing.
