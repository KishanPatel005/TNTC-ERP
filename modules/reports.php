<?php
require_once __DIR__ . '/../includes/auth.php';
require_login();
require_once __DIR__ . '/../includes/db.php';

function export_csv(string $filename, array $headers, array $rows): void {
  header('Content-Type: text/csv');
  header('Content-Disposition: attachment; filename="' . $filename . '"');
  $out = fopen('php://output', 'w');
  fputcsv($out, $headers);
  foreach ($rows as $r) { fputcsv($out, $r); }
  fclose($out);
  exit;
}

if (isset($_GET['export']) && $_GET['export'] === 'tasks') {
  $rows = [];
  $res = db_query("SELECT 'Main' as level, id, name, status, deadline FROM main_tasks UNION ALL SELECT 'Sub', id, name, status, deadline FROM sub_tasks UNION ALL SELECT 'Ground', id, name, status, deadline FROM ground_tasks");
  $data = $res->get_result()->fetch_all(MYSQLI_ASSOC);
  foreach ($data as $d) { $rows[] = [$d['level'],$d['id'],$d['name'],$d['status'],$d['deadline']]; }
  export_csv('tasks.csv', ['Level','ID','Name','Status','Deadline'], $rows);
}
if (isset($_GET['export']) && $_GET['export'] === 'stock') {
  $data = db_query('SELECT material_name, unit, quantity, low_stock_threshold FROM stock ORDER BY material_name')->get_result()->fetch_all(MYSQLI_ASSOC);
  $rows = array_map(fn($r)=>[$r['material_name'],$r['unit'],$r['quantity'],$r['low_stock_threshold']], $data);
  export_csv('stock.csv', ['Material','Unit','Quantity','Low Threshold'], $rows);
}
if (isset($_GET['export']) && $_GET['export'] === 'salary') {
  $data = db_query('SELECT u.name, s.month, s.month_num, s.base_salary, s.days_present, s.days_absent, s.amount, s.status FROM salary s JOIN users u ON u.id=s.user_id ORDER BY s.id DESC')->get_result()->fetch_all(MYSQLI_ASSOC);
  $rows = array_map(fn($r)=>[$r['name'],$r['month'],$r['month_num'],$r['base_salary'],$r['days_present'],$r['days_absent'],$r['amount'],$r['status']], $data);
  export_csv('salary.csv', ['Name','Year','Month','Base','Present','Absent','Amount','Status'], $rows);
}

include __DIR__ . '/../includes/header.php';
?>
<h4 class="mb-3"><i class="fa-solid fa-chart-line me-1"></i> Reports</h4>
<div class="row g-3">
  <div class="col-md-4">
    <div class="card h-100">
      <div class="card-body d-flex flex-column">
        <h5>Task Progress</h5>
        <p class="text-muted">Export status of all tasks</p>
        <a class="btn btn-outline-primary mt-auto" href="?export=tasks"><i class="fa-solid fa-file-csv me-1"></i> Export CSV</a>
      </div>
    </div>
  </div>
  <div class="col-md-4">
    <div class="card h-100">
      <div class="card-body d-flex flex-column">
        <h5>Stock Usage</h5>
        <p class="text-muted">Export current stock levels</p>
        <a class="btn btn-outline-primary mt-auto" href="?export=stock"><i class="fa-solid fa-file-csv me-1"></i> Export CSV</a>
      </div>
    </div>
  </div>
  <div class="col-md-4">
    <div class="card h-100">
      <div class="card-body d-flex flex-column">
        <h5>Salary Summary</h5>
        <p class="text-muted">Export salary calculations</p>
        <a class="btn btn-outline-primary mt-auto" href="?export=salary"><i class="fa-solid fa-file-csv me-1"></i> Export CSV</a>
      </div>
    </div>
  </div>
</div>
<?php include __DIR__ . '/../includes/footer.php'; ?>
