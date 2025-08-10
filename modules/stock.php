<?php
require_once __DIR__ . '/../includes/auth.php';
require_login();
require_once __DIR__ . '/../includes/db.php';

$alert = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  auth_require_csrf();
  $action = $_POST['action'] ?? '';
  if ($action === 'add') {
    $name = trim($_POST['material_name'] ?? '');
    $unit = trim($_POST['unit'] ?? '');
    $qty  = (float)($_POST['quantity'] ?? 0);
    $low  = (float)($_POST['low_stock_threshold'] ?? 0);
    if ($name && $unit) {
      // upsert by unique(material_name,unit)
      $stmt = db_query('SELECT id FROM stock WHERE material_name=? AND unit=? LIMIT 1','ss',[$name,$unit]);
      $row = db_fetch_one($stmt);
      if ($row) {
        db_query('UPDATE stock SET quantity = quantity + ?, low_stock_threshold = ? WHERE id=?','ddi',[$qty, $low, (int)$row['id']]);
      } else {
        db_query('INSERT INTO stock(material_name,unit,quantity,low_stock_threshold) VALUES(?,?,?,?)','ssdd',[$name,$unit,$qty,$low]);
      }
      $alert = 'Stock saved';
    }
  } elseif ($action === 'adjust') {
    $id = (int)($_POST['id'] ?? 0);
    $qty = (float)($_POST['quantity'] ?? 0);
    if ($id) {
      db_query('UPDATE stock SET quantity = ? WHERE id=?','di',[$qty,$id]);
      db_query('INSERT INTO stock_log(stock_id, change_type, quantity, ref_type, note) VALUES(?,"ADJUST",?,"MANUAL",?)','ids',[ $id, $qty, 'Manual adjust' ]);
      $alert = 'Stock adjusted';
    }
  }
}

$rows = db_query('SELECT * FROM stock ORDER BY material_name')->get_result()->fetch_all(MYSQLI_ASSOC);
include __DIR__ . '/../includes/header.php';
?>
<meta name="csrf-token" content="<?php echo htmlspecialchars($csrf); ?>">
<div class="d-flex justify-content-between align-items-center mb-2">
  <h4><i class="fa-solid fa-boxes-stacked me-1"></i> Stock</h4>
  <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modalAdd">Add/Update</button>
</div>
<?php if ($alert): ?><div class="alert alert-success"><?php echo htmlspecialchars($alert); ?></div><?php endif; ?>
<div class="card">
  <div class="card-body">
    <table class="table table-striped datatable">
      <thead><tr><th>ID</th><th>Material</th><th>Unit</th><th>Qty</th><th>Low Threshold</th><th>Status</th><th>Actions</th></tr></thead>
<tbody>
<?php foreach($rows as $r): 
  $low = (float)$r['low_stock_threshold']; 
  $q = (float)$r['quantity']; 
  $danger = $low > 0 && $q <= $low; 
?>
  <tr class="<?php echo $danger ? 'table-warning' : ''; ?>">
    <td><?php echo (int)$r['id']; ?></td>
    <td><?php echo htmlspecialchars($r['material_name']); ?></td>
    <td><?php echo htmlspecialchars($r['unit']); ?></td>
    <td><?php echo number_format($q, 3); ?></td>
    <td><?php echo number_format($low, 3); ?></td>
    <td><?php echo $danger ? '<span class="badge text-bg-danger">Low</span>' : '<span class="badge text-bg-success">OK</span>'; ?></td>
    <td>
      <button class="btn btn-sm btn-outline-secondary" data-bs-toggle="modal" data-bs-target="#modalAdj<?php echo (int)$r['id']; ?>">Adjust</button>
    </td>
  </tr>
<?php endforeach; ?>
</tbody>
</table>

    </table>
    <?php foreach($rows as $r): ?>
<div class="modal fade" id="modalAdj<?php echo (int)$r['id']; ?>" tabindex="-1">
  <div class="modal-dialog">
    <form method="post" class="modal-content">
      <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf); ?>">
      <input type="hidden" name="action" value="adjust">
      <input type="hidden" name="id" value="<?php echo (int)$r['id']; ?>">
      <div class="modal-header">
        <h5 class="modal-title">Adjust Stock - <?php echo htmlspecialchars($r['material_name']); ?></h5>
        <button class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <div class="mb-2">
          <label class="form-label">New Quantity</label>
          <input type="number" step="0.001" name="quantity" class="form-control" value="<?php echo htmlspecialchars($r['quantity']); ?>" required>
        </div>
      </div>
      <div class="modal-footer">
        <button class="btn btn-primary">Save</button>
      </div>
    </form>
  </div>
</div>
<?php endforeach; ?>

  </div>
</div>

<div class="modal fade" id="modalAdd" tabindex="-1"><div class="modal-dialog"><form method="post" class="modal-content">
  <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf); ?>">
  <input type="hidden" name="action" value="add">
  <div class="modal-header"><h5 class="modal-title">Add/Update Stock</h5><button class="btn-close" data-bs-dismiss="modal"></button></div>
  <div class="modal-body">
    <div class="mb-2"><label class="form-label">Material</label><input class="form-control" name="material_name" required></div>
    <div class="mb-2"><label class="form-label">Unit</label><input class="form-control" name="unit" required></div>
    <div class="mb-2"><label class="form-label">Quantity (add)</label><input type="number" step="0.001" class="form-control" name="quantity" value="0" required></div>
    <div class="mb-2"><label class="form-label">Low Stock Threshold</label><input type="number" step="0.001" class="form-control" name="low_stock_threshold" value="0" required></div>
  </div>
  <div class="modal-footer"><button class="btn btn-primary">Save</button></div>
</form></div></div>
<?php include __DIR__ . '/../includes/footer.php'; ?>
