<?php
require_once __DIR__ . '/../includes/auth.php';
require_login();
require_once __DIR__ . '/../includes/db.php';

$alert = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  auth_require_csrf();
  $action = $_POST['action'] ?? '';
  if ($action === 'create_req') {
    $project_id = (int)($_POST['project_id'] ?? 0);
    $main_id = $_POST['main_task_id'] ? (int)$_POST['main_task_id'] : null;
    $sub_id = $_POST['sub_task_id'] ? (int)$_POST['sub_task_id'] : null;
    $ground_id = $_POST['ground_task_id'] ? (int)$_POST['ground_task_id'] : null;
    if ($project_id) {
      db_query('INSERT INTO material_requests(project_id, main_task_id, sub_task_id, ground_task_id, requested_by) VALUES(?,?,?,?,?)','iiiii',[$project_id,$main_id,$sub_id,$ground_id,current_user()['id']]);
      $req_id = db()->insert_id;
      // items
      $names = $_POST['item_name'] ?? [];
      $units = $_POST['item_unit'] ?? [];
      $qtys  = $_POST['item_qty'] ?? [];
      for ($i=0; $i<count($names); $i++) {
        $n = trim($names[$i] ?? '');
        $u = trim($units[$i] ?? '');
        $q = (float)($qtys[$i] ?? 0);
        if ($n && $u && $q > 0) {
          db_query('INSERT INTO material_request_items(request_id, material_name, unit, qty_requested) VALUES(?,?,?,?)','issd',[$req_id,$n,$u,$q]);
        }
      }
      $alert = 'Material request created';
    }
  } elseif ($action === 'approve') {
    $id = (int)($_POST['id'] ?? 0);
    if ($id) {
      // approve all items at requested qty for simplicity
      $items = db_query('SELECT * FROM material_request_items WHERE request_id=?','i',[$id])->get_result()->fetch_all(MYSQLI_ASSOC);
      foreach ($items as $it) {
        db_query('UPDATE material_request_items SET qty_approved=? WHERE id=?','di',[(float)$it['qty_requested'], (int)$it['id']]);
        // stock out
        // find stock row
        $st = db_query('SELECT * FROM stock WHERE material_name=? AND unit=? LIMIT 1','ss',[$it['material_name'],$it['unit']])->get_result()->fetch_assoc();
        if ($st) {
          db_query('UPDATE stock SET quantity = quantity - ? WHERE id=?','di',[(float)$it['qty_requested'], (int)$st['id']]);
          db_query('INSERT INTO stock_log(stock_id, change_type, quantity, ref_type, ref_id, note) VALUES(?,"OUT",?,"REQ",?,?)','iidis',[(int)$st['id'], (float)$it['qty_requested'], $id, 'Material Request']);
        }
      }
      db_query("UPDATE material_requests SET status='fulfilled' WHERE id=?",'i',[$id]);
      $alert = 'Request approved and stock updated';
    }
  } elseif ($action === 'reject') {
    $id = (int)($_POST['id'] ?? 0);
    if ($id) { db_query("UPDATE material_requests SET status='rejected' WHERE id=?",'i',[$id]); $alert = 'Request rejected'; }
  }
}

$projects = db_query('SELECT id,name FROM projects ORDER BY name')->get_result()->fetch_all(MYSQLI_ASSOC);
$mainTasks = db_query('SELECT id,name FROM main_tasks ORDER BY id DESC')->get_result()->fetch_all(MYSQLI_ASSOC);
$subTasks = db_query('SELECT id,name FROM sub_tasks ORDER BY id DESC')->get_result()->fetch_all(MYSQLI_ASSOC);
$groundTasks = db_query('SELECT id,name FROM ground_tasks ORDER BY id DESC')->get_result()->fetch_all(MYSQLI_ASSOC);

$requests = db_query('SELECT mr.*, p.name AS project_name, u.name AS requested_by_name FROM material_requests mr JOIN projects p ON p.id = mr.project_id JOIN users u ON u.id = mr.requested_by ORDER BY mr.id DESC')->get_result()->fetch_all(MYSQLI_ASSOC);

include __DIR__ . '/../includes/header.php';
?>
<meta name="csrf-token" content="<?php echo htmlspecialchars($csrf); ?>">
<div class="d-flex justify-content-between align-items-center mb-2">
  <h4><i class="fa-solid fa-box-open me-1"></i> Material Requests</h4>
  <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modalCreate">Create Request</button>
</div>
<?php if ($alert): ?><div class="alert alert-success"><?php echo htmlspecialchars($alert); ?></div><?php endif; ?>
<div class="card">
  <div class="card-body">
    <table class="table table-striped datatable">
      <thead><tr><th>ID</th><th>Project</th><th>Requested By</th><th>Status</th><th>Created</th><th>Actions</th></tr></thead>
      <tbody>
        <?php foreach ($requests as $r): ?>
          <tr>
            <td><?php echo (int)$r['id']; ?></td>
            <td><?php echo htmlspecialchars($r['project_name']); ?></td>
            <td><?php echo htmlspecialchars($r['requested_by_name']); ?></td>
            <td><span class="badge text-bg-secondary"><?php echo htmlspecialchars($r['status']); ?></span></td>
            <td><?php echo htmlspecialchars($r['created_at']); ?></td>
            <td>
              <button class="btn btn-sm btn-info" data-bs-toggle="collapse" data-bs-target="#items<?php echo (int)$r['id']; ?>">Items</button>
              <?php if ($r['status'] === 'pending'): ?>
                <form method="post" class="d-inline">
                  <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf); ?>">
                  <input type="hidden" name="action" value="approve">
                  <input type="hidden" name="id" value="<?php echo (int)$r['id']; ?>">
                  <button class="btn btn-sm btn-success">Approve & Fulfill</button>
                </form>
                <form method="post" class="d-inline" onsubmit="return confirm('Reject request?');">
                  <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf); ?>">
                  <input type="hidden" name="action" value="reject">
                  <input type="hidden" name="id" value="<?php echo (int)$r['id']; ?>">
                  <button class="btn btn-sm btn-danger">Reject</button>
                </form>
              <?php endif; ?>
            </td>
          </tr>
         <tr class="collapse" id="items<?php echo (int)$r['id']; ?>">
  <td>
    <div class="p-0">
      <table class="table table-sm mb-0">
        <thead><tr><th>Material</th><th>Unit</th><th>Qty Requested</th><th>Qty Approved</th></tr></thead>
        <tbody>
          <?php $items = db_query('SELECT * FROM material_request_items WHERE request_id=?','i',[(int)$r['id']])->get_result()->fetch_all(MYSQLI_ASSOC); ?>
          <?php foreach ($items as $it): ?>
            <tr>
              <td><?php echo htmlspecialchars($it['material_name']); ?></td>
              <td><?php echo htmlspecialchars($it['unit']); ?></td>
              <td><?php echo htmlspecialchars($it['qty_requested']); ?></td>
              <td><?php echo ($it['qty_approved']); ?></td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </td>
  <td></td><td></td><td></td><td></td><td></td>
</tr>

        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>

<div class="modal fade" id="modalCreate" tabindex="-1">
  <div class="modal-dialog modal-lg">
    <form method="post" class="modal-content">
      <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf); ?>">
      <input type="hidden" name="action" value="create_req">
      <div class="modal-header"><h5 class="modal-title">Create Material Request</h5><button class="btn-close" data-bs-dismiss="modal"></button></div>
      <div class="modal-body">
        <div class="row g-2">
          <div class="col-md-3"><label class="form-label">Project</label>
            <select class="form-select" name="project_id" required>
              <option value="">Select</option>
              <?php foreach($projects as $p): ?><option value="<?php echo (int)$p['id']; ?>"><?php echo htmlspecialchars($p['name']); ?></option><?php endforeach; ?>
            </select>
          </div>
          <div class="col-md-3"><label class="form-label">Main Task</label>
            <select class="form-select" name="main_task_id">
              <option value="">None</option>
              <?php foreach($mainTasks as $t): ?><option value="<?php echo (int)$t['id']; ?>"><?php echo htmlspecialchars($t['name']); ?></option><?php endforeach; ?>
            </select>
          </div>
          <div class="col-md-3"><label class="form-label">Sub Task</label>
            <select class="form-select" name="sub_task_id">
              <option value="">None</option>
              <?php foreach($subTasks as $t): ?><option value="<?php echo (int)$t['id']; ?>"><?php echo htmlspecialchars($t['name']); ?></option><?php endforeach; ?>
            </select>
          </div>
          <div class="col-md-3"><label class="form-label">Ground Task</label>
            <select class="form-select" name="ground_task_id">
              <option value="">None</option>
              <?php foreach($groundTasks as $t): ?><option value="<?php echo (int)$t['id']; ?>"><?php echo htmlspecialchars($t['name']); ?></option><?php endforeach; ?>
            </select>
          </div>
        </div>
        <hr>
        <div id="items">
          <div class="row g-2 align-items-end item-row">
            <div class="col-md-5"><label class="form-label">Material</label><input class="form-control" name="item_name[]" required></div>
            <div class="col-md-2"><label class="form-label">Unit</label><input class="form-control" name="item_unit[]" required></div>
            <div class="col-md-3"><label class="form-label">Quantity</label><input type="number" step="0.001" min="0" class="form-control" name="item_qty[]" required></div>
            <div class="col-md-2"><button type="button" class="btn btn-outline-secondary w-100 add-row">Add</button></div>
          </div>
        </div>
      </div>
      <div class="modal-footer"><button class="btn btn-primary">Create</button></div>
    </form>
  </div>
</div>
<script>
    // Turn off DataTables alert popups
    $.fn.dataTable.ext.errMode = 'none';

    $(document).ready(function() {
        $('#DataTables_Table_0').DataTable();
    });
</script>

<script>
    // Disable DataTables error alerts globally
    $.fn.dataTable.ext.errMode = 'none';

    // Initialize all tables with .datatable class
    $(document).ready(function () {
        $('.datatable').DataTable();
    });
</script>

<script>
$(document).on('click', '.add-row', function(){
  const row = $(this).closest('.item-row');
  const clone = row.clone();
  clone.find('input').val('');
  clone.find('.add-row').removeClass('add-row btn-outline-secondary').addClass('remove-row btn-outline-danger').text('Remove');
  $('#items').append(clone);
});
$(document).on('click', '.remove-row', function(){
  $(this).closest('.item-row').remove();
});
</script>
<?php include __DIR__ . '/../includes/footer.php'; ?>
