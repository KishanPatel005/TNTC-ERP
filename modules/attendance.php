<?php
require_once __DIR__ . '/../includes/auth.php';
require_login();
require_once __DIR__ . '/../includes/db.php';

$alert='';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  auth_require_csrf();
  $date = $_POST['attendance_date'] ?? date('Y-m-d');
  foreach ($_POST['status'] ?? [] as $uid => $st) {
    $uid = (int)$uid;
    $st = in_array($st, ['PRESENT','ABSENT','HALF']) ? $st : 'PRESENT';
    db_query('INSERT INTO attendance(user_id,attendance_date,status) VALUES(?,?,?) ON DUPLICATE KEY UPDATE status=VALUES(status)','iss',[$uid,$date,$st]);
  }
  $alert='Attendance saved';
}

$users = db_query('SELECT u.id,u.name,r.name AS role_name FROM users u JOIN roles r ON r.id=u.role_id WHERE u.status="active" ORDER BY u.name')->get_result()->fetch_all(MYSQLI_ASSOC);
include __DIR__ . '/../includes/header.php';
?>
<meta name="csrf-token" content="<?php echo htmlspecialchars($csrf); ?>">
<h4 class="mb-2"><i class="fa-solid fa-user-check me-1"></i> Attendance</h4>
<?php if ($alert): ?><div class="alert alert-success"><?php echo htmlspecialchars($alert); ?></div><?php endif; ?>
<div class="card"><div class="card-body">
  <form method="post">
    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf); ?>">
    <div class="row g-2 mb-2">
      <div class="col-md-3">
        <label class="form-label">Date</label>
        <input type="date" class="form-control" name="attendance_date" value="<?php echo date('Y-m-d'); ?>">
      </div>
    </div>
    <div class="table-responsive">
      <table class="table table-sm">
        <thead><tr><th>#</th><th>Name</th><th>Role</th><th>Status</th></tr></thead>
        <tbody>
          <?php foreach($users as $u): ?>
          <tr>
            <td><?php echo (int)$u['id']; ?></td>
            <td><?php echo htmlspecialchars($u['name']); ?></td>
            <td><?php echo htmlspecialchars($u['role_name']); ?></td>
            <td>
              <select name="status[<?php echo (int)$u['id']; ?>]" class="form-select form-select-sm" style="max-width:200px;">
                <option value="PRESENT">Present</option>
                <option value="ABSENT">Absent</option>
                <option value="HALF">Half Day</option>
              </select>
            </td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
    <button class="btn btn-primary">Save Attendance</button>
  </form>
</div></div>
<?php include __DIR__ . '/../includes/footer.php'; ?>
