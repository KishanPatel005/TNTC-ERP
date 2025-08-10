-- Construction ERP Management System - SQL Schema and Sample Data
-- MySQL InnoDB with Foreign Keys and Indexes
-- Import this into a database named `construction_erp` (create it if not exists)

SET NAMES utf8mb4;
SET time_zone = "+00:00";
SET foreign_key_checks = 0;
SET sql_mode = 'STRICT_ALL_TABLES,ERROR_FOR_DIVISION_BY_ZERO,NO_ENGINE_SUBSTITUTION';

START TRANSACTION;

-- ROLES
CREATE TABLE IF NOT EXISTS roles (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(50) NOT NULL UNIQUE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- USERS
CREATE TABLE IF NOT EXISTS users (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(120) NOT NULL,
  email VARCHAR(160) NOT NULL UNIQUE,
  password_hash VARCHAR(255) NOT NULL,
  role_id INT NOT NULL,
  status ENUM('active','inactive') NOT NULL DEFAULT 'active',
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (role_id) REFERENCES roles(id)
    ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
CREATE INDEX idx_users_role ON users(role_id);

-- PROJECTS
CREATE TABLE IF NOT EXISTS projects (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(200) NOT NULL,
  description TEXT,
  start_date DATE,
  end_date DATE,
  status ENUM('planned','in_progress','on_hold','completed','cancelled') NOT NULL DEFAULT 'planned',
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
CREATE INDEX idx_projects_status ON projects(status);

-- TASKS HIERARCHY: main -> sub -> ground
CREATE TABLE IF NOT EXISTS main_tasks (
  id INT AUTO_INCREMENT PRIMARY KEY,
  project_id INT NOT NULL,
  name VARCHAR(200) NOT NULL,
  deadline DATE,
  status ENUM('pending','in_progress','completed','blocked') NOT NULL DEFAULT 'pending',
  assigned_to INT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (project_id) REFERENCES projects(id)
    ON UPDATE CASCADE ON DELETE CASCADE,
  FOREIGN KEY (assigned_to) REFERENCES users(id)
    ON UPDATE CASCADE ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
CREATE INDEX idx_main_tasks_project ON main_tasks(project_id);
CREATE INDEX idx_main_tasks_assigned ON main_tasks(assigned_to);

CREATE TABLE IF NOT EXISTS sub_tasks (
  id INT AUTO_INCREMENT PRIMARY KEY,
  main_task_id INT NOT NULL,
  name VARCHAR(200) NOT NULL,
  deadline DATE,
  status ENUM('pending','in_progress','completed','blocked') NOT NULL DEFAULT 'pending',
  assigned_to INT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (main_task_id) REFERENCES main_tasks(id)
    ON UPDATE CASCADE ON DELETE CASCADE,
  FOREIGN KEY (assigned_to) REFERENCES users(id)
    ON UPDATE CASCADE ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
CREATE INDEX idx_sub_tasks_main ON sub_tasks(main_task_id);
CREATE INDEX idx_sub_tasks_assigned ON sub_tasks(assigned_to);

CREATE TABLE IF NOT EXISTS ground_tasks (
  id INT AUTO_INCREMENT PRIMARY KEY,
  sub_task_id INT NOT NULL,
  name VARCHAR(200) NOT NULL,
  deadline DATE,
  status ENUM('pending','in_progress','completed','blocked') NOT NULL DEFAULT 'pending',
  assigned_to INT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (sub_task_id) REFERENCES sub_tasks(id)
    ON UPDATE CASCADE ON DELETE CASCADE,
  FOREIGN KEY (assigned_to) REFERENCES users(id)
    ON UPDATE CASCADE ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
CREATE INDEX idx_ground_tasks_sub ON ground_tasks(sub_task_id);
CREATE INDEX idx_ground_tasks_assigned ON ground_tasks(assigned_to);

-- STOCK & MATERIALS
CREATE TABLE IF NOT EXISTS stock (
  id INT AUTO_INCREMENT PRIMARY KEY,
  material_name VARCHAR(200) NOT NULL,
  unit VARCHAR(50) NOT NULL,
  quantity DECIMAL(15,3) NOT NULL DEFAULT 0,
  low_stock_threshold DECIMAL(15,3) NOT NULL DEFAULT 0,
  UNIQUE KEY uniq_material (material_name, unit)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS material_requests (
  id INT AUTO_INCREMENT PRIMARY KEY,
  project_id INT NOT NULL,
  main_task_id INT NULL,
  sub_task_id INT NULL,
  ground_task_id INT NULL,
  requested_by INT NOT NULL,
  status ENUM('pending','approved','rejected','fulfilled') NOT NULL DEFAULT 'pending',
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (project_id) REFERENCES projects(id)
    ON UPDATE CASCADE ON DELETE CASCADE,
  FOREIGN KEY (main_task_id) REFERENCES main_tasks(id)
    ON UPDATE CASCADE ON DELETE SET NULL,
  FOREIGN KEY (sub_task_id) REFERENCES sub_tasks(id)
    ON UPDATE CASCADE ON DELETE SET NULL,
  FOREIGN KEY (ground_task_id) REFERENCES ground_tasks(id)
    ON UPDATE CASCADE ON DELETE SET NULL,
  FOREIGN KEY (requested_by) REFERENCES users(id)
    ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
CREATE INDEX idx_material_requests_project ON material_requests(project_id);
CREATE INDEX idx_material_requests_status ON material_requests(status);

CREATE TABLE IF NOT EXISTS material_request_items (
  id INT AUTO_INCREMENT PRIMARY KEY,
  request_id INT NOT NULL,
  material_name VARCHAR(200) NOT NULL,
  unit VARCHAR(50) NOT NULL,
  qty_requested DECIMAL(15,3) NOT NULL,
  qty_approved DECIMAL(15,3) NULL,
  FOREIGN KEY (request_id) REFERENCES material_requests(id)
    ON UPDATE CASCADE ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
CREATE INDEX idx_mri_request ON material_request_items(request_id);

CREATE TABLE IF NOT EXISTS stock_log (
  id INT AUTO_INCREMENT PRIMARY KEY,
  stock_id INT NOT NULL,
  change_type ENUM('IN','OUT','ADJUST') NOT NULL,
  quantity DECIMAL(15,3) NOT NULL,
  ref_type ENUM('PO','GRN','REQ','MANUAL') NOT NULL,
  ref_id INT NULL,
  note VARCHAR(255) NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (stock_id) REFERENCES stock(id)
    ON UPDATE CASCADE ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
CREATE INDEX idx_stock_log_stock ON stock_log(stock_id);

-- VENDORS, POs, GRNs
CREATE TABLE IF NOT EXISTS vendors (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(200) NOT NULL,
  contact_person VARCHAR(150),
  phone VARCHAR(50),
  email VARCHAR(160),
  address TEXT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS purchase_orders (
  id INT AUTO_INCREMENT PRIMARY KEY,
  vendor_id INT NOT NULL,
  po_number VARCHAR(50) NOT NULL UNIQUE,
  po_date DATE NOT NULL,
  status ENUM('draft','issued','partially_received','received','cancelled') NOT NULL DEFAULT 'draft',
  total_amount DECIMAL(15,2) NOT NULL DEFAULT 0,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (vendor_id) REFERENCES vendors(id)
    ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
CREATE INDEX idx_po_vendor ON purchase_orders(vendor_id);
CREATE INDEX idx_po_status ON purchase_orders(status);

CREATE TABLE IF NOT EXISTS purchase_order_items (
  id INT AUTO_INCREMENT PRIMARY KEY,
  po_id INT NOT NULL,
  material_name VARCHAR(200) NOT NULL,
  unit VARCHAR(50) NOT NULL,
  quantity DECIMAL(15,3) NOT NULL,
  unit_price DECIMAL(15,2) NOT NULL,
  FOREIGN KEY (po_id) REFERENCES purchase_orders(id)
    ON UPDATE CASCADE ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
CREATE INDEX idx_poi_po ON purchase_order_items(po_id);

CREATE TABLE IF NOT EXISTS grn (
  id INT AUTO_INCREMENT PRIMARY KEY,
  po_id INT NOT NULL,
  grn_number VARCHAR(50) NOT NULL UNIQUE,
  received_date DATE NOT NULL,
  status ENUM('draft','posted') NOT NULL DEFAULT 'draft',
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (po_id) REFERENCES purchase_orders(id)
    ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
CREATE INDEX idx_grn_po ON grn(po_id);

CREATE TABLE IF NOT EXISTS grn_items (
  id INT AUTO_INCREMENT PRIMARY KEY,
  grn_id INT NOT NULL,
  material_name VARCHAR(200) NOT NULL,
  unit VARCHAR(50) NOT NULL,
  quantity_received DECIMAL(15,3) NOT NULL,
  FOREIGN KEY (grn_id) REFERENCES grn(id)
    ON UPDATE CASCADE ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
CREATE INDEX idx_grn_items_grn ON grn_items(grn_id);

-- ATTENDANCE & SALARY
CREATE TABLE IF NOT EXISTS attendance (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NOT NULL,
  attendance_date DATE NOT NULL,
  status ENUM('PRESENT','ABSENT','HALF') NOT NULL DEFAULT 'PRESENT',
  remarks VARCHAR(255) NULL,
  UNIQUE KEY uniq_attendance (user_id, attendance_date),
  FOREIGN KEY (user_id) REFERENCES users(id)
    ON UPDATE CASCADE ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
CREATE INDEX idx_attendance_user ON attendance(user_id);

CREATE TABLE IF NOT EXISTS salary (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NOT NULL,
  month YEAR(4) NOT NULL,
  month_num TINYINT NOT NULL CHECK (month_num BETWEEN 1 AND 12),
  base_salary DECIMAL(15,2) NOT NULL DEFAULT 0,
  days_present INT NOT NULL DEFAULT 0,
  days_absent INT NOT NULL DEFAULT 0,
  amount DECIMAL(15,2) NOT NULL DEFAULT 0,
  paid_date DATE NULL,
  status ENUM('pending','paid') NOT NULL DEFAULT 'pending',
  UNIQUE KEY uniq_salary_user_month (user_id, month, month_num),
  FOREIGN KEY (user_id) REFERENCES users(id)
    ON UPDATE CASCADE ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
CREATE INDEX idx_salary_user ON salary(user_id);

-- REPORTS table is optional as most reports will be generated dynamically.
-- Creating a simple export log table.
CREATE TABLE IF NOT EXISTS export_logs (
  id INT AUTO_INCREMENT PRIMARY KEY,
  report_name VARCHAR(150) NOT NULL,
  file_name VARCHAR(200) NOT NULL,
  requested_by INT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (requested_by) REFERENCES users(id)
    ON UPDATE CASCADE ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Seed base roles
INSERT INTO roles (name) VALUES
  ('Admin'), ('Manager'), ('Staff')
ON DUPLICATE KEY UPDATE name = VALUES(name);

-- Optional seed data (projects, vendors, stock)
INSERT INTO projects (name, description, start_date, end_date, status) VALUES
  ('Residential Complex A', 'Multi-tower residential project', '2025-01-01', '2026-12-31', 'in_progress'),
  ('Highway Expansion B', 'Expansion of 50km highway', '2025-03-01', '2027-06-30', 'planned')
ON DUPLICATE KEY UPDATE description = VALUES(description), status = VALUES(status);

INSERT INTO vendors (name, contact_person, phone, email, address) VALUES
  ('BuildSupplies Co.', 'John Vendor', '+1-202-555-0147', 'sales@buildsupplies.example', 'Industrial Area, City'),
  ('SteelMakers Ltd.', 'Mary Steel', '+1-202-555-0199', 'contact@steelmakers.example', 'Steel Park, City')
ON DUPLICATE KEY UPDATE contact_person = VALUES(contact_person), phone = VALUES(phone);

INSERT INTO stock (material_name, unit, quantity, low_stock_threshold) VALUES
  ('Cement', 'bag', 500, 100),
  ('Sand', 'ton', 50, 10),
  ('Steel Rod 12mm', 'piece', 1000, 200),
  ('Bricks', 'piece', 20000, 5000)
ON DUPLICATE KEY UPDATE quantity = VALUES(quantity), low_stock_threshold = VALUES(low_stock_threshold);

-- NOTE ON ADMIN USER:
-- For security, the initial Admin user is not auto-created here because MySQL cannot generate bcrypt hashes natively.
-- After importing this schema, visit the application login page; it will guide you to create the first Admin user if none exists.

COMMIT;

SET foreign_key_checks = 1;