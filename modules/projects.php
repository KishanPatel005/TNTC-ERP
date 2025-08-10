<?php
require_once __DIR__ . '/../includes/auth.php';
require_login();
require_once __DIR__ . '/../includes/db.php';

$alert = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  auth_require_csrf();
  $action = $_POST['action'] ?? '';
  if ($action === 'add') {
    $name = trim($_POST['name'] ?? '');
    $desc = trim($_POST['description'] ?? '');
    $sd = $_POST['start_date'] ?? null;
    $ed = $_POST['end_date'] ?? null;
    $status = $_POST['status'] ?? 'planned';
    if ($name) {
      db_query('INSERT INTO projects(name,description,start_date,end_date,status) VALUES(?,?,?,?,?)','sssss',[$name,$desc,$sd,$ed,$status]);
      $alert = 'Project added';
    }
  } elseif ($action === 'update') {
    $id = (int)($_POST['id'] ?? 0);
    $name = trim($_POST['name'] ?? '');
    $desc = trim($_POST['description'] ?? '');
    $sd = $_POST['start_date'] ?? null;
    $ed = $_POST['end_date'] ?? null;
    $status = $_POST['status'] ?? 'planned';
    if ($id && $name) {
      db_query('UPDATE projects SET name=?, description=?, start_date=?, end_date=?, status=? WHERE id=?','sssssi',[$name,$desc,$sd,$ed,$status,$id]);
      $alert = 'Project updated';
    }
  } elseif ($action === 'delete') {
    $id = (int)($_POST['id'] ?? 0);
    if ($id) {
      db_query('DELETE FROM projects WHERE id=?','i',[$id]);
      $alert = 'Project deleted';
    }
  }
}

$res = db_query('SELECT * FROM projects ORDER BY id DESC');
$projects = $res->get_result()->fetch_all(MYSQLI_ASSOC);
include __DIR__ . '/../includes/header.php';
?>
<meta name="csrf-token" content="<?php echo htmlspecialchars($csrf); ?>">
<div class="d-flex justify-content-between align-items-center mb-2">
  <h4><i class="fa-solid fa-diagram-project me-1"></i> Projects</h4>
  <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modalAdd">Add Project</button>
</div>
<?php if ($alert): ?><div class="alert alert-success"><?php echo htmlspecialchars($alert); ?></div><?php endif; ?>
<div class="card">
  <div class="card-body">
    <div class="table-responsive">
      <table class="table table-striped datatable">
        <thead><tr><th>ID</th><th>Name</th><th>Dates</th><th>Status</th><th>Actions</th></tr></thead>
        <tbody>
        <?php foreach ($projects as $p): ?>
          <tr>
            <td><?php echo (int)$p['id']; ?></td>
            <td><?php echo htmlspecialchars($p['name']); ?></td>
            <td><?php echo htmlspecialchars($p['start_date'] . ' - ' . $p['end_date']); ?></td>
            <td><span class="badge text-bg-secondary"><?php echo htmlspecialchars($p['status']); ?></span></td>
            <td>
              <button class="btn btn-sm btn-warning" data-bs-toggle="modal" data-bs-target="#modalEdit<?php echo (int)$p['id']; ?>">Edit</button>
              <form method="post" class="d-inline" onsubmit="return confirm('Delete project?');">
                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf); ?>">
                <input type="hidden" name="action" value="delete">
                <input type="hidden" name="id" value="<?php echo (int)$p['id']; ?>">
                <button class="btn btn-sm btn-danger">Delete</button>
              </form>
            </td>
          </tr>
          <div class="modal fade" id="modalEdit<?php echo (int)$p['id']; ?>" tabindex="-1">
            <div class="modal-dialog">
              <form method="post" class="modal-content">
                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf); ?>">
                <input type="hidden" name="action" value="update">
                <input type="hidden" name="id" value="<?php echo (int)$p['id']; ?>">
                <div class="modal-header"><h5 class="modal-title">Edit Project</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
                <div class="modal-body">
                  <div class="mb-2"><label class="form-label">Name</label><input class="form-control" name="name" value="<?php echo htmlspecialchars($p['name']); ?>" required></div>
                  <div class="mb-2"><label class="form-label">Description</label><textarea class="form-control" name="description"><?php echo htmlspecialchars($p['description']); ?></textarea></div>
                  <div class="row g-2 mb-2">
                    <div class="col"><label class="form-label">Start</label><input type="date" class="form-control" name="start_date" value="<?php echo htmlspecialchars($p['start_date']); ?>"></div>
                    <div class="col"><label class="form-label">End</label><input type="date" class="form-control" name="end_date" value="<?php echo htmlspecialchars($p['end_date']); ?>"></div>
                  </div>
                  <div class="mb-2"><label class="form-label">Status</label>
                    <select class="form-select" name="status">
                      <?php foreach(['planned','in_progress','on_hold','completed','cancelled'] as $s): ?>
                        <option value="<?php echo $s; ?>" <?php if($p['status']===$s) echo 'selected';?>><?php echo ucfirst(str_replace('_',' ',$s)); ?></option>
                      <?php endforeach; ?>
                    </select>
                  </div>
                </div>
                <div class="modal-footer"><button class="btn btn-primary">Save</button></div>
              </form>
            </div>
          </div>
        <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>

<div class="modal fade" id="modalAdd" tabindex="-1">
  <div class="modal-dialog">
    <form method="post" class="modal-content">
      <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf); ?>">
      <input type="hidden" name="action" value="add">
      <div class="modal-header"><h5 class="modal-title">Add Project</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
      <div class="modal-body">
        <div class="mb-2"><label class="form-label">Name</label><input class="form-control" name="name" required></div>
        <div class="mb-2"><label class="form-label">Description</label><textarea class="form-control" name="description"></textarea></div>
        <div class="row g-2 mb-2">
          <div class="col"><label class="form-label">Start</label><input type="date" class="form-control" name="start_date"></div>
          <div class="col"><label class="form-label">End</label><input type="date" class="form-control" name="end_date"></div>
        </div>
        <div class="mb-2"><label class="form-label">Status</label>
          <select class="form-select" name="status">
            <option value="planned">Planned</option>
            <option value="in_progress">In Progress</option>
            <option value="on_hold">On Hold</option>
            <option value="completed">Completed</option>
            <option value="cancelled">Cancelled</option>
          </select>
        </div>
      </div>
      <div class="modal-footer"><button class="btn btn-primary">Add</button></div>
    </form>
  </div>
</div>
<?php include __DIR__ . '/../includes/footer.php'; ?>
