-- Construction ERP - Dummy Data (Safe to run after schema import)
-- Notes:
-- 1) This file intentionally DOES NOT create any users;
--    on first application launch, create the first Admin in the UI using the credentials provided in needed_details.txt.
-- 2) Run this once to seed sample projects, tasks, vendors, stock, POs, and GRNs.

SET NAMES utf8mb4;
SET time_zone = "+00:00";
SET foreign_key_checks = 0;
SET sql_mode = 'STRICT_ALL_TABLES,ERROR_FOR_DIVISION_BY_ZERO,NO_ENGINE_SUBSTITUTION';

START TRANSACTION;

-- Projects
INSERT INTO projects (name, description, start_date, end_date, status) VALUES
  ('Residential Complex A - Phase 2', 'Phase 2 of multi-tower residential project', '2025-07-01', '2026-12-31', 'in_progress'),
  ('Highway Expansion B - Segment 1', 'Segment 1 (km 0-10) of highway expansion', '2025-08-15', '2026-08-14', 'planned'),
  ('Office Tower C', 'Premium grade-A office tower', '2025-09-01', '2027-03-31', 'planned');

-- Main tasks for Residential Complex A - Phase 2
INSERT INTO main_tasks (project_id, name, deadline, status, assigned_to)
SELECT p.id, 'Foundation', '2025-09-30', 'in_progress', NULL
FROM projects p WHERE p.name = 'Residential Complex A - Phase 2' LIMIT 1;
INSERT INTO main_tasks (project_id, name, deadline, status, assigned_to)
SELECT p.id, 'Structure', '2025-12-31', 'pending', NULL
FROM projects p WHERE p.name = 'Residential Complex A - Phase 2' LIMIT 1;
INSERT INTO main_tasks (project_id, name, deadline, status, assigned_to)
SELECT p.id, 'Finishing', '2026-05-31', 'pending', NULL
FROM projects p WHERE p.name = 'Residential Complex A - Phase 2' LIMIT 1;

-- Sub tasks
INSERT INTO sub_tasks (main_task_id, name, deadline, status, assigned_to)
SELECT mt.id, 'Excavation', '2025-08-15', 'in_progress', NULL
FROM main_tasks mt JOIN projects p ON p.id = mt.project_id
WHERE p.name = 'Residential Complex A - Phase 2' AND mt.name = 'Foundation' LIMIT 1;
INSERT INTO sub_tasks (main_task_id, name, deadline, status, assigned_to)
SELECT mt.id, 'Rebar & Formwork', '2025-09-10', 'pending', NULL
FROM main_tasks mt JOIN projects p ON p.id = mt.project_id
WHERE p.name = 'Residential Complex A - Phase 2' AND mt.name = 'Foundation' LIMIT 1;

-- Ground tasks
INSERT INTO ground_tasks (sub_task_id, name, deadline, status, assigned_to)
SELECT st.id, 'Trenching', '2025-08-01', 'in_progress', NULL
FROM sub_tasks st JOIN main_tasks mt ON mt.id = st.main_task_id JOIN projects p ON p.id = mt.project_id
WHERE p.name = 'Residential Complex A - Phase 2' AND mt.name = 'Foundation' AND st.name = 'Excavation' LIMIT 1;
INSERT INTO ground_tasks (sub_task_id, name, deadline, status, assigned_to)
SELECT st.id, 'Soil Disposal', '2025-08-05', 'pending', NULL
FROM sub_tasks st JOIN main_tasks mt ON mt.id = st.main_task_id JOIN projects p ON p.id = mt.project_id
WHERE p.name = 'Residential Complex A - Phase 2' AND mt.name = 'Foundation' AND st.name = 'Excavation' LIMIT 1;

-- Vendors
INSERT INTO vendors (name, contact_person, phone, email, address) VALUES
  ('ConcreteMixers Inc.', 'Sam Supplier', '+1-202-555-0101', 'orders@concretemixers.example', '45 Industrial Ave'),
  ('MegaSteel Traders', 'Priya Steel', '+1-202-555-0115', 'sales@megasteel.example', 'Plot 12 Steel Park');

-- Stock (initial quantities)
INSERT INTO stock (material_name, unit, quantity, low_stock_threshold) VALUES
  ('Cement', 'bag', 200, 100),
  ('Sand', 'ton', 80, 20),
  ('Steel Rod 12mm', 'piece', 500, 200),
  ('Bricks', 'piece', 30000, 5000);

-- Purchase Orders
INSERT INTO purchase_orders (vendor_id, po_number, po_date, status, total_amount)
SELECT v.id, 'PO-2025-001', '2025-07-15', 'issued', 0
FROM vendors v WHERE v.name = 'ConcreteMixers Inc.' LIMIT 1;

INSERT INTO purchase_orders (vendor_id, po_number, po_date, status, total_amount)
SELECT v.id, 'PO-2025-002', '2025-07-20', 'issued', 0
FROM vendors v WHERE v.name = 'MegaSteel Traders' LIMIT 1;

-- Purchase Order Items for PO-2025-001
INSERT INTO purchase_order_items (po_id, material_name, unit, quantity, unit_price)
SELECT po.id, 'Cement', 'bag', 300, 6.50 FROM purchase_orders po WHERE po.po_number = 'PO-2025-001' LIMIT 1;
INSERT INTO purchase_order_items (po_id, material_name, unit, quantity, unit_price)
SELECT po.id, 'Sand', 'ton', 100, 20.00 FROM purchase_orders po WHERE po.po_number = 'PO-2025-001' LIMIT 1;

-- Purchase Order Items for PO-2025-002
INSERT INTO purchase_order_items (po_id, material_name, unit, quantity, unit_price)
SELECT po.id, 'Steel Rod 12mm', 'piece', 800, 2.40 FROM purchase_orders po WHERE po.po_number = 'PO-2025-002' LIMIT 1;

-- Update PO totals
UPDATE purchase_orders po
LEFT JOIN (
  SELECT po_id, SUM(quantity * unit_price) AS t FROM purchase_order_items GROUP BY po_id
) x ON x.po_id = po.id
SET po.total_amount = IFNULL(x.t,0)
WHERE po.po_number IN ('PO-2025-001','PO-2025-002');

-- GRN for PO-2025-001 (partial receipt)
INSERT INTO grn (po_id, grn_number, received_date, status)
SELECT po.id, 'GRN-2025-001', '2025-07-18', 'posted' FROM purchase_orders po WHERE po.po_number='PO-2025-001' LIMIT 1;

-- GRN Items
INSERT INTO grn_items (grn_id, material_name, unit, quantity_received)
SELECT g.id, 'Cement', 'bag', 200 FROM grn g WHERE g.grn_number='GRN-2025-001' LIMIT 1;
INSERT INTO grn_items (grn_id, material_name, unit, quantity_received)
SELECT g.id, 'Sand', 'ton', 40 FROM grn g WHERE g.grn_number='GRN-2025-001' LIMIT 1;

-- Reflect GRN into stock quantities
UPDATE stock s SET s.quantity = s.quantity + 200 WHERE s.material_name='Cement' AND s.unit='bag';
UPDATE stock s SET s.quantity = s.quantity + 40 WHERE s.material_name='Sand' AND s.unit='ton';

-- Stock logs for GRN
INSERT INTO stock_log (stock_id, change_type, quantity, ref_type, ref_id, note)
SELECT s.id, 'IN', 200, 'GRN', g.id, 'Dummy data GRN receipt - Cement'
FROM stock s, grn g
WHERE s.material_name='Cement' AND s.unit='bag' AND g.grn_number='GRN-2025-001' LIMIT 1;

INSERT INTO stock_log (stock_id, change_type, quantity, ref_type, ref_id, note)
SELECT s.id, 'IN', 40, 'GRN', g.id, 'Dummy data GRN receipt - Sand'
FROM stock s, grn g
WHERE s.material_name='Sand' AND s.unit='ton' AND g.grn_number='GRN-2025-001' LIMIT 1;

COMMIT;

SET foreign_key_checks = 1;