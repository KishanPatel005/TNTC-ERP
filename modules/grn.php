<?php
require_once __DIR__ . '/../includes/auth.php';
require_login();
require_once __DIR__ . '/../includes/db.php';

$alert='';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  auth_require_csrf();
  $action = $_POST['action'] ?? '';
  if ($action === 'create') {
    $po_id = (int)$_POST['po_id'];
    $grn_number = trim($_POST['grn_number']);
    $received_date = $_POST['received_date'];
    db_query('INSERT INTO grn(po_id,grn_number,received_date,status) VALUES(?,?,?,"posted")','iss',[$po_id,$grn_number,$received_date]);
    $grn_id = db()->insert_id;
    $names = $_POST['item_name'] ?? [];
    $units = $_POST['item_unit'] ?? [];
    $qtys  = $_POST['item_qty'] ?? [];
    for ($i=0; $i<count($names); $i++) {
      $n = trim($names[$i] ?? ''); $u = trim($units[$i] ?? ''); $q = (float)($qtys[$i] ?? 0);
      if ($n && $u && $q>0) {
        db_query('INSERT INTO grn_items(grn_id,material_name,unit,quantity_received) VALUES(?,?,?,?)','issd',[$grn_id,$n,$u,$q]);
        // Stock IN
        $st = db_query('SELECT id FROM stock WHERE material_name=? AND unit=? LIMIT 1','ss',[$n,$u])->get_result()->fetch_assoc();
        if ($st) {
          db_query('UPDATE stock SET quantity = quantity + ? WHERE id=?','di',[$q, (int)$st['id']]);
          db_query('INSERT INTO stock_log(stock_id, change_type, quantity, ref_type, ref_id, note) VALUES(?,"IN",?,"GRN",?,?)','idis',[(int)$st['id'], $q, $grn_id, 'Goods Receipt']);
        } else {
          db_query('INSERT INTO stock(material_name,unit,quantity,low_stock_threshold) VALUES(?,?,?,0)','ssd',[$n,$u,$q]);
          $sid = db()->insert_id;
          db_query('INSERT INTO stock_log(stock_id, change_type, quantity, ref_type, ref_id, note) VALUES(?,"IN",?,"GRN",?,?)','idis',[$sid, $q, $grn_id, 'Goods Receipt']);
        }
      }
    }
    // Update PO status
    db_query("UPDATE purchase_orders SET status='received' WHERE id=?",'i',[$po_id]);
    $alert='GRN created and stock updated';
  }
}
$pos = db_query("SELECT po.id, po.po_number, v.name AS vendor_name FROM purchase_orders po JOIN vendors v ON v.id=po.vendor_id WHERE po.status IN ('issued','partially_received','received') ORDER BY po.id DESC")->get_result()->fetch_all(MYSQLI_ASSOC);
$grns = db_query('SELECT g.*, po.po_number FROM grn g JOIN purchase_orders po ON po.id=g.po_id ORDER BY g.id DESC')->get_result()->fetch_all(MYSQLI_ASSOC);
include __DIR__ . '/../includes/header.php';
?>
<meta name="csrf-token" content="<?php echo htmlspecialchars($csrf); ?>">
<div class="d-flex justify-content-between align-items-center mb-2">
  <h4><i class="fa-solid fa-truck-ramp-box me-1"></i> GRN</h4>
  <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modalAdd">Create GRN</button>
</div>
<?php if ($alert): ?><div class="alert alert-success"><?php echo htmlspecialchars($alert); ?></div><?php endif; ?>
<div class="card"><div class="card-body">
  <table class="table table-striped datatable">
    <thead><tr><th>ID</th><th>GRN Number</th><th>PO Number</th><th>Received</th><th>Status</th></tr></thead>
    <tbody>
      <?php foreach($grns as $g): ?>
        <tr>
          <td><?php echo (int)$g['id']; ?></td>
          <td><?php echo htmlspecialchars($g['grn_number']); ?></td>
          <td><?php echo htmlspecialchars($g['po_number']); ?></td>
          <td><?php echo htmlspecialchars($g['received_date']); ?></td>
          <td><?php echo htmlspecialchars($g['status']); ?></td>
        </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
</div></div>

<div class="modal fade" id="modalAdd" tabindex="-1">
  <div class="modal-dialog modal-lg"><form method="post" class="modal-content">
    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf); ?>">
    <input type="hidden" name="action" value="create">
    <div class="modal-header"><h5 class="modal-title">Create GRN</h5><button class="btn-close" data-bs-dismiss="modal"></button></div>
    <div class="modal-body">
      <div class="row g-2 mb-2">
        <div class="col-md-4"><label class="form-label">PO</label>
          <select class="form-select" name="po_id" required>
            <option value="">Select</option>
            <?php foreach($pos as $p): ?><option value="<?php echo (int)$p['id']; ?>"><?php echo htmlspecialchars($p['po_number'] . ' - ' . $p['vendor_name']); ?></option><?php endforeach; ?>
          </select>
        </div>
        <div class="col-md-4"><label class="form-label">GRN Number</label><input class="form-control" name="grn_number" required></div>
        <div class="col-md-4"><label class="form-label">Received Date</label><input type="date" class="form-control" name="received_date" required></div>
      </div>
      <hr>
      <div id="grnitems">
        <div class="row g-2 align-items-end item-row">
          <div class="col-md-5"><label class="form-label">Material</label><input class="form-control" name="item_name[]" required></div>
          <div class="col-md-2"><label class="form-label">Unit</label><input class="form-control" name="item_unit[]" required></div>
          <div class="col-md-3"><label class="form-label">Qty Received</label><input type="number" step="0.001" class="form-control" name="item_qty[]" required></div>
          <div class="col-md-2"><button type="button" class="btn btn-outline-secondary w-100 add-row">Add</button></div>
        </div>
      </div>
    </div>
    <div class="modal-footer"><button class="btn btn-primary">Create</button></div>
  </form></div>
</div>
<script>
$(document).on('click', '.add-row', function(){
  const row = $(this).closest('.item-row');
  const clone = row.clone();
  clone.find('input').val('');
  clone.find('.add-row').removeClass('add-row btn-outline-secondary').addClass('remove-row btn-outline-danger').text('Remove');
  $('#grnitems').append(clone);
});
$(document).on('click', '.remove-row', function(){ $(this).closest('.item-row').remove(); });
</script>
<?php include __DIR__ . '/../includes/footer.php'; ?>
