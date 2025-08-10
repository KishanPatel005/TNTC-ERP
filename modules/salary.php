<?php
require_once __DIR__ . '/../includes/auth.php';
require_login();
require_once __DIR__ . '/../includes/db.php';

$alert='';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  auth_require_csrf();
  $action = $_POST['action'] ?? '';
  if ($action === 'calculate') {
    $year = (int)$_POST['year'];
    $month = (int)$_POST['month'];
    $start = sprintf('%04d-%02d-01', $year, $month);
    $end = date('Y-m-t', strtotime($start));
    // For demo, assume base salary stored in users table? Not present; add fixed base or skip.
    // We'll use a flat daily rate of 50 for Staff, 80 for Manager, 120 for Admin as example.
    $users = db_query('SELECT u.id,u.name,r.name AS role_name FROM users u JOIN roles r ON r.id=u.role_id WHERE u.status="active"')->get_result()->fetch_all(MYSQLI_ASSOC);
    foreach ($users as $u) {
      $rate = $u['role_name']==='Admin'?120:($u['role_name']==='Manager'?80:50);
      $att = db_query('SELECT status, COUNT(*) c FROM attendance WHERE user_id=? AND attendance_date BETWEEN ? AND ? GROUP BY status','iss',[ (int)$u['id'], $start, $end ])->get_result()->fetch_all(MYSQLI_ASSOC);
      $present=0;$absent=0;$half=0;foreach($att as $a){if($a['status']==='PRESENT')$present=$a['c'];elseif($a['status']==='ABSENT')$absent=$a['c'];elseif($a['status']==='HALF')$half=$a['c'];}
      $days_present = $present + 0.5*$half;
      $amount = $days_present * $rate;
      db_query('INSERT INTO salary(user_id,month,month_num,base_salary,days_present,days_absent,amount,status) VALUES(?,?,?,?,?,?,?,"pending") ON DUPLICATE KEY UPDATE base_salary=VALUES(base_salary), days_present=VALUES(days_present), days_absent=VALUES(days_absent), amount=VALUES(amount)','iiididdi',[ (int)$u['id'], $year, $month, $rate*30, (int)$days_present, (int)$absent, (float)$amount ]);
    }
    $alert='Salary calculated for period';
  } elseif ($action === 'mark_paid') {
    $id = (int)$_POST['id'];
    db_query("UPDATE salary SET status='paid', paid_date=CURDATE() WHERE id=?",'i',[$id]);
    $alert='Marked as paid';
  }
}
$rows = db_query('SELECT s.*, u.name FROM salary s JOIN users u ON u.id=s.user_id ORDER BY s.id DESC')->get_result()->fetch_all(MYSQLI_ASSOC);
include __DIR__ . '/../includes/header.php';
?>
<meta name="csrf-token" content="<?php echo htmlspecialchars($csrf); ?>">
<div class="d-flex justify-content-between align-items-center mb-2">
  <h4><i class="fa-solid fa-sack-dollar me-1"></i> Salary</h4>
  <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modalCalc">Calculate</button>
</div>
<?php if ($alert): ?><div class="alert alert-success"><?php echo htmlspecialchars($alert); ?></div><?php endif; ?>
<div class="card"><div class="card-body">
  <table class="table table-striped datatable">
    <thead><tr><th>ID</th><th>Name</th><th>Month</th><th>Base</th><th>Present</th><th>Absent</th><th>Amount</th><th>Status</th><th>Actions</th></tr></thead>
    <tbody>
      <?php foreach($rows as $r): ?>
      <tr>
        <td><?php echo (int)$r['id']; ?></td>
        <td><?php echo htmlspecialchars($r['name']); ?></td>
        <td><?php echo htmlspecialchars($r['month'] . '-' . sprintf('%02d',(int)$r['month_num'])); ?></td>
        <td><?php echo number_format((float)$r['base_salary'],2); ?></td>
        <td><?php echo (int)$r['days_present']; ?></td>
        <td><?php echo (int)$r['days_absent']; ?></td>
        <td><?php echo number_format((float)$r['amount'],2); ?></td>
        <td><?php echo htmlspecialchars($r['status']); ?></td>
        <td>
          <?php if ($r['status'] === 'pending'): ?>
          <form method="post" class="d-inline">
            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf); ?>">
            <input type="hidden" name="action" value="mark_paid">
            <input type="hidden" name="id" value="<?php echo (int)$r['id']; ?>">
            <button class="btn btn-sm btn-success">Mark Paid</button>
          </form>
          <?php else: ?>
          <span class="badge text-bg-success">Paid</span>
          <?php endif; ?>
        </td>
      </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
</div></div>

<div class="modal fade" id="modalCalc" tabindex="-1"><div class="modal-dialog"><form method="post" class="modal-content">
  <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf); ?>">
  <input type="hidden" name="action" value="calculate">
  <div class="modal-header"><h5 class="modal-title">Calculate Salary</h5><button class="btn-close" data-bs-dismiss="modal"></button></div>
  <div class="modal-body">
    <div class="row g-2">
      <div class="col"><label class="form-label">Year</label><input type="number" class="form-control" name="year" value="<?php echo date('Y'); ?>" required></div>
      <div class="col"><label class="form-label">Month</label><input type="number" class="form-control" name="month" min="1" max="12" value="<?php echo date('n'); ?>" required></div>
    </div>
  </div>
  <div class="modal-footer"><button class="btn btn-primary">Run</button></div>
</form></div></div>
<?php include __DIR__ . '/../includes/footer.php'; ?>
