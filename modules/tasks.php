<?php
require_once __DIR__ . '/../includes/auth.php';
require_login();
require_once __DIR__ . '/../includes/db.php';

$alert = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  auth_require_csrf();
  $action = $_POST['action'] ?? '';
  if ($action === 'add_main') {
    $project_id = (int)($_POST['project_id'] ?? 0);
    $name = trim($_POST['name'] ?? '');
    $deadline = $_POST['deadline'] ?? null;
    if ($project_id && $name) db_query('INSERT INTO main_tasks(project_id,name,deadline) VALUES(?,?,?)', 'iss', [$project_id, $name, $deadline]);
    $alert = 'Main task added';
  } elseif ($action === 'add_sub') {
    $main_id = (int)($_POST['main_task_id'] ?? 0);
    $name = trim($_POST['name'] ?? '');
    $deadline = $_POST['deadline'] ?? null;
    if ($main_id && $name) db_query('INSERT INTO sub_tasks(main_task_id,name,deadline) VALUES(?,?,?)', 'iss', [$main_id, $name, $deadline]);
    $alert = 'Sub task added';
  } elseif ($action === 'add_ground') {
    $sub_id = (int)($_POST['sub_task_id'] ?? 0);
    $name = trim($_POST['name'] ?? '');
    $deadline = $_POST['deadline'] ?? null;
    if ($sub_id && $name) db_query('INSERT INTO ground_tasks(sub_task_id,name,deadline) VALUES(?,?,?)', 'iss', [$sub_id, $name, $deadline]);
    $alert = 'Ground task added';
  } elseif ($action === 'update_status') {
    $level = $_POST['level'] ?? 'main';
    $id = (int)($_POST['id'] ?? 0);
    $status = $_POST['status'] ?? 'pending';
    $table = $level === 'sub' ? 'sub_tasks' : ($level === 'ground' ? 'ground_tasks' : 'main_tasks');
    if ($id) db_query("UPDATE {$table} SET status=? WHERE id=?", 'si', [$status, $id]);
    $alert = 'Status updated';
  }
}

$projects = db_query('SELECT id, name FROM projects ORDER BY name')->get_result()->fetch_all(MYSQLI_ASSOC);
$mainTasks = db_query('SELECT mt.*, p.name AS project_name FROM main_tasks mt JOIN projects p ON p.id = mt.project_id ORDER BY mt.id DESC')->get_result()->fetch_all(MYSQLI_ASSOC);
$subTasks = db_query('SELECT st.*, mt.name AS main_name FROM sub_tasks st JOIN main_tasks mt ON mt.id = st.main_task_id ORDER BY st.id DESC')->get_result()->fetch_all(MYSQLI_ASSOC);
$groundTasks = db_query('SELECT gt.*, st.name AS sub_name FROM ground_tasks gt JOIN sub_tasks st ON st.id = gt.sub_task_id ORDER BY gt.id DESC')->get_result()->fetch_all(MYSQLI_ASSOC);

include __DIR__ . '/../includes/header.php';
?>
<meta name="csrf-token" content="<?php echo htmlspecialchars($csrf); ?>">
<h4 class="mb-2"><i class="fa-solid fa-list-check me-1"></i> Tasks</h4>
<?php if ($alert): ?>
  <div class="alert alert-success"><?php echo htmlspecialchars($alert); ?></div>
<?php endif; ?>

<!-- Tabs -->
<ul class="nav nav-tabs" id="taskTabs" role="tablist">
  <li class="nav-item" role="presentation">
    <button class="nav-link active" id="main-tab" data-bs-toggle="tab" data-bs-target="#mainTasks" type="button" role="tab">Main Tasks</button>
  </li>
  <li class="nav-item" role="presentation">
    <button class="nav-link" id="sub-tab" data-bs-toggle="tab" data-bs-target="#subTasks" type="button" role="tab">Sub Tasks</button>
  </li>
  <li class="nav-item" role="presentation">
    <button class="nav-link" id="ground-tab" data-bs-toggle="tab" data-bs-target="#groundTasks" type="button" role="tab">Ground Tasks</button>
  </li>
</ul>

<div class="tab-content mt-3">
  <!-- Main Tasks Tab -->
  <div class="tab-pane fade show active" id="mainTasks" role="tabpanel" aria-labelledby="main-tab">
    <div class="card">
      <div class="card-header d-flex justify-content-between">
        <span>Main Tasks</span>
        <button class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#modalAddMain">Add</button>
      </div>
   <div class="table-responsive">
  <table class="table table-bordered table-striped table-hover align-middle">
          <thead>
            <tr><th>ID</th><th>Project</th><th>Name</th><th>Status</th><th>Deadline</th><th>Update</th></tr>
          </thead>
          <tbody>
            <?php foreach ($mainTasks as $t): ?>
              <tr>
                <td><?= (int)$t['id']; ?></td>
                <td><?= htmlspecialchars($t['project_name']); ?></td>
                <td><?= htmlspecialchars($t['name']); ?></td>
                <td><?= htmlspecialchars($t['status']); ?></td>
                <td><?= htmlspecialchars($t['deadline']); ?></td>
                <td>
                  <form method="post" class="d-flex gap-1">
                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf); ?>">
                    <input type="hidden" name="action" value="update_status">
                    <input type="hidden" name="level" value="main">
                    <input type="hidden" name="id" value="<?= (int)$t['id']; ?>">
                    <select name="status" class="form-select form-select-sm">
                      <?php foreach(['pending','in_progress','completed','blocked'] as $s): ?>
                        <option value="<?= $s; ?>" <?= $t['status']===$s?'selected':''; ?>><?= $s; ?></option>
                      <?php endforeach; ?>
                    </select>
                    <button class="btn btn-sm btn-outline-primary">Go</button>
                  </form>
                </td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>

  <!-- Sub Tasks Tab -->
  <div class="tab-pane fade" id="subTasks" role="tabpanel" aria-labelledby="sub-tab">
    <div class="card">
      <div class="card-header d-flex justify-content-between">
        <span>Sub Tasks</span>
        <button class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#modalAddSub">Add</button>
      </div>
   <div class="table-responsive">
  <table class="table table-bordered table-striped table-hover align-middle">
          <thead>
            <tr><th>ID</th><th>Main</th><th>Name</th><th>Status</th><th>Deadline</th><th>Update</th></tr>
          </thead>
          <tbody>
            <?php foreach ($subTasks as $t): ?>
              <tr>
                <td><?= (int)$t['id']; ?></td>
                <td><?= htmlspecialchars($t['main_name']); ?></td>
                <td><?= htmlspecialchars($t['name']); ?></td>
                <td><?= htmlspecialchars($t['status']); ?></td>
                <td><?= htmlspecialchars($t['deadline']); ?></td>
                <td>
                  <form method="post" class="d-flex gap-1">
                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf); ?>">
                    <input type="hidden" name="action" value="update_status">
                    <input type="hidden" name="level" value="sub">
                    <input type="hidden" name="id" value="<?= (int)$t['id']; ?>">
                    <select name="status" class="form-select form-select-sm">
                      <?php foreach(['pending','in_progress','completed','blocked'] as $s): ?>
                        <option value="<?= $s; ?>" <?= $t['status']===$s?'selected':''; ?>><?= $s; ?></option>
                      <?php endforeach; ?>
                    </select>
                    <button class="btn btn-sm btn-outline-primary">Go</button>
                  </form>
                </td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>

  <!-- Ground Tasks Tab -->
  <div class="tab-pane fade" id="groundTasks" role="tabpanel" aria-labelledby="ground-tab">
    <div class="card">
      <div class="card-header d-flex justify-content-between">
        <span>Ground Tasks</span>
        <button class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#modalAddGround">Add</button>
      </div>
   <div class="table-responsive">
  <table class="table table-bordered table-striped table-hover align-middle">
          <thead>
            <tr><th>ID</th><th>Sub</th><th>Name</th><th>Status</th><th>Deadline</th><th>Update</th></tr>
          </thead>
          <tbody>
            <?php foreach ($groundTasks as $t): ?>
              <tr>
                <td><?= (int)$t['id']; ?></td>
                <td><?= htmlspecialchars($t['sub_name']); ?></td>
                <td><?= htmlspecialchars($t['name']); ?></td>
                <td><?= htmlspecialchars($t['status']); ?></td>
                <td><?= htmlspecialchars($t['deadline']); ?></td>
                <td>
                  <form method="post" class="d-flex gap-1">
                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf); ?>">
                    <input type="hidden" name="action" value="update_status">
                    <input type="hidden" name="level" value="ground">
                    <input type="hidden" name="id" value="<?= (int)$t['id']; ?>">
                    <select name="status" class="form-select form-select-sm">
                      <?php foreach(['pending','in_progress','completed','blocked'] as $s): ?>
                        <option value="<?= $s; ?>" <?= $t['status']===$s?'selected':''; ?>><?= $s; ?></option>
                      <?php endforeach; ?>
                    </select>
                    <button class="btn btn-sm btn-outline-primary">Go</button>
                  </form>
                </td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</div>


<!-- Modals -->
<div class="modal fade" id="modalAddMain" tabindex="-1">
  <div class="modal-dialog">
    <form method="post" class="modal-content">
      <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf); ?>">
      <input type="hidden" name="action" value="add_main">
      <div class="modal-header">
        <h5 class="modal-title">Add Main Task</h5><button class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <div class="mb-2"><label class="form-label">Project</label>
          <select class="form-select" name="project_id" required>
            <option value="">Select</option>
            <?php foreach ($projects as $p): ?><option value="<?php echo (int)$p['id']; ?>"><?php echo htmlspecialchars($p['name']); ?></option><?php endforeach; ?>
          </select>
        </div>
        <div class="mb-2"><label class="form-label">Name</label><input class="form-control" name="name" required></div>
        <div class="mb-2"><label class="form-label">Deadline</label><input type="date" class="form-control" name="deadline"></div>
      </div>
      <div class="modal-footer"><button class="btn btn-primary">Add</button></div>
    </form>
  </div>
</div>

<div class="modal fade" id="modalAddSub" tabindex="-1">
  <div class="modal-dialog">
    <form method="post" class="modal-content">
      <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf); ?>">
      <input type="hidden" name="action" value="add_sub">
      <div class="modal-header">
        <h5 class="modal-title">Add Sub Task</h5><button class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <div class="mb-2"><label class="form-label">Main Task</label>
          <select class="form-select" name="main_task_id" required>
            <option value="">Select</option>
            <?php foreach ($mainTasks as $t): ?><option value="<?php echo (int)$t['id']; ?>"><?php echo htmlspecialchars($t['name']); ?> (<?php echo htmlspecialchars($t['project_name']); ?>)</option><?php endforeach; ?>
          </select>
        </div>
        <div class="mb-2"><label class="form-label">Name</label><input class="form-control" name="name" required></div>
        <div class="mb-2"><label class="form-label">Deadline</label><input type="date" class="form-control" name="deadline"></div>
      </div>
      <div class="modal-footer"><button class="btn btn-primary">Add</button></div>
    </form>
  </div>
</div>

<div class="modal fade" id="modalAddGround" tabindex="-1">
  <div class="modal-dialog">
    <form method="post" class="modal-content">
      <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf); ?>">
      <input type="hidden" name="action" value="add_ground">
      <div class="modal-header">
        <h5 class="modal-title">Add Ground Task</h5><button class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <div class="mb-2"><label class="form-label">Sub Task</label>
          <select class="form-select" name="sub_task_id" required>
            <option value="">Select</option>
            <?php foreach ($subTasks as $t): ?><option value="<?php echo (int)$t['id']; ?>"><?php echo htmlspecialchars($t['name']); ?> (Main: <?php echo htmlspecialchars($t['main_name']); ?>)</option><?php endforeach; ?>
          </select>
        </div>
        <div class="mb-2"><label class="form-label">Name</label><input class="form-control" name="name" required></div>
        <div class="mb-2"><label class="form-label">Deadline</label><input type="date" class="form-control" name="deadline"></div>
      </div>
      <div class="modal-footer"><button class="btn btn-primary">Add</button></div>
    </form>
  </div>
</div>
<?php include __DIR__ . '/../includes/footer.php'; ?>