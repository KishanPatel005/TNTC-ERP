<?php
require_once __DIR__ . '/includes/auth.php';
require_login();
require_once __DIR__ . '/includes/db.php';

include __DIR__ . '/includes/header.php';
// Dashboard stats
$stats = [
  'projects_active' => 0,
  'tasks_pending' => 0,
  'stock_items' => 0,
  'salary_due' => 0.00,
];

$projects = db_query("SELECT COUNT(*) AS c FROM projects WHERE status IN ('planned','in_progress','on_hold')");
$stats['projects_active'] = $projects->get_result()->fetch_assoc()['c'] ?? 0;

$tasks = db_query("SELECT (
  (SELECT COUNT(*) FROM main_tasks WHERE status IN ('pending','in_progress')) +
  (SELECT COUNT(*) FROM sub_tasks WHERE status IN ('pending','in_progress')) +
  (SELECT COUNT(*) FROM ground_tasks WHERE status IN ('pending','in_progress'))
) AS c");
$stats['tasks_pending'] = $tasks->get_result()->fetch_assoc()['c'] ?? 0;

$stock = db_query("SELECT COUNT(*) AS c FROM stock");
$stats['stock_items'] = $stock->get_result()->fetch_assoc()['c'] ?? 0;

$salary = db_query("SELECT SUM(amount) AS s FROM salary WHERE status = 'pending'");
$stats['salary_due'] = (float)($salary->get_result()->fetch_assoc()['s'] ?? 0);

?>
<meta name="csrf-token" content="<?php echo htmlspecialchars($csrf); ?>">
<div class="row g-3">
  <div class="col-md-3">
    <div class="card text-bg-primary">
      <div class="card-body">
        <div class="d-flex justify-content-between align-items-center">
          <div>
            <div class="fs-5">Active Projects</div>
            <div class="display-6"><?php echo (int)$stats['projects_active']; ?></div>
          </div>
          <i class="fa-solid fa-diagram-project fa-3x opacity-75"></i>
        </div>
      </div>
    </div>
  </div>
  <div class="col-md-3">
    <div class="card text-bg-warning">
      <div class="card-body">
        <div class="d-flex justify-content-between align-items-center">
          <div>
            <div class="fs-5">Tasks Pending</div>
            <div class="display-6"><?php echo (int)$stats['tasks_pending']; ?></div>
          </div>
          <i class="fa-solid fa-list-check fa-3x opacity-75"></i>
        </div>
      </div>
    </div>
  </div>
  <div class="col-md-3">
    <div class="card text-bg-success">
      <div class="card-body">
        <div class="d-flex justify-content-between align-items-center">
          <div>
            <div class="fs-5">Stock Items</div>
            <div class="display-6"><?php echo (int)$stats['stock_items']; ?></div>
          </div>
          <i class="fa-solid fa-boxes-stacked fa-3x opacity-75"></i>
        </div>
      </div>
    </div>
  </div>
  <div class="col-md-3">
    <div class="card text-bg-danger">
      <div class="card-body">
        <div class="d-flex justify-content-between align-items-center">
          <div>
            <div class="fs-5">Salary Due</div>
            <div class="display-6">$<?php echo number_format($stats['salary_due'], 2); ?></div>
          </div>
          <i class="fa-solid fa-money-bill-wave fa-3x opacity-75"></i>
        </div>
      </div>
    </div>
  </div>
</div>

<div class="row g-3 mt-1">
  <div class="col-lg-6">
    <div class="card">
      <div class="card-header"><i class="fa-solid fa-chart-column me-1"></i> Material Usage</div>
      <div class="card-body">
        <canvas id="chartMaterials" height="120"></canvas>
      </div>
    </div>
  </div>
  <div class="col-lg-6">
    <div class="card">
      <div class="card-header"><i class="fa-solid fa-chart-line me-1"></i> Task Progress</div>
      <div class="card-body">
        <canvas id="chartTasks" height="120"></canvas>
      </div>
    </div>
  </div>
</div>

<script>
const ctxM = document.getElementById('chartMaterials');
const chartM = new Chart(ctxM, {
  type: 'bar',
  data: {
    labels: ['Cement','Sand','Steel','Bricks'],
    datasets: [{
      label: 'Available Stock',
      data: [<?php
        $rs = db_query("SELECT material_name, quantity FROM stock ORDER BY material_name LIMIT 4");
        $rows = $rs->get_result()->fetch_all(MYSQLI_ASSOC);
        $vals = array_map(fn($r)=> (float)$r['quantity'], $rows);
        echo implode(',', $vals ?: [0,0,0,0]);
      ?>],
      backgroundColor: 'rgba(54, 162, 235, 0.5)'
    }]
  },
  options: { responsive: true, maintainAspectRatio: false }
});

const ctxT = document.getElementById('chartTasks');
const chartT = new Chart(ctxT, {
  type: 'doughnut',
  data: {
    labels: ['Pending','In Progress','Completed','Blocked'],
    datasets: [{
      label: 'Tasks',
      data: [<?php
        $pending = db_query("SELECT (
          (SELECT COUNT(*) FROM main_tasks WHERE status='pending') +
          (SELECT COUNT(*) FROM sub_tasks WHERE status='pending') +
          (SELECT COUNT(*) FROM ground_tasks WHERE status='pending')
        ) AS c");
        $inprog = db_query("SELECT (
          (SELECT COUNT(*) FROM main_tasks WHERE status='in_progress') +
          (SELECT COUNT(*) FROM sub_tasks WHERE status='in_progress') +
          (SELECT COUNT(*) FROM ground_tasks WHERE status='in_progress')
        ) AS c");
        $completed = db_query("SELECT (
          (SELECT COUNT(*) FROM main_tasks WHERE status='completed') +
          (SELECT COUNT(*) FROM sub_tasks WHERE status='completed') +
          (SELECT COUNT(*) FROM ground_tasks WHERE status='completed')
        ) AS c");
        $blocked = db_query("SELECT (
          (SELECT COUNT(*) FROM main_tasks WHERE status='blocked') +
          (SELECT COUNT(*) FROM sub_tasks WHERE status='blocked') +
          (SELECT COUNT(*) FROM ground_tasks WHERE status='blocked')
        ) AS c");
        $p = $pending->get_result()->fetch_assoc()['c'] ?? 0;
        $ip= $inprog->get_result()->fetch_assoc()['c'] ?? 0;
        $co= $completed->get_result()->fetch_assoc()['c'] ?? 0;
        $bl= $blocked->get_result()->fetch_assoc()['c'] ?? 0;
        echo implode(',', [(int)$p,(int)$ip,(int)$co,(int)$bl]);
      ?>],
      backgroundColor: [
        'rgba(255, 205, 86, 0.7)',
        'rgba(54, 162, 235, 0.7)',
        'rgba(75, 192, 192, 0.7)',
        'rgba(255, 99, 132, 0.7)'
      ]
    }]
  },
  options: { responsive: true, maintainAspectRatio: false }
});
</script>
<?php include __DIR__ . '/includes/footer.php'; ?>
