<?php
require_once __DIR__ . '/../includes/auth.php';
require_login();
require_once __DIR__ . '/../includes/db.php';

$alert='';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  auth_require_csrf();
  $action = $_POST['action'] ?? '';
  if ($action === 'add') {
    $vendor_id = (int)$_POST['vendor_id'];
    $po_number = trim($_POST['po_number']);
    $po_date = $_POST['po_date'];
    db_query('INSERT INTO purchase_orders(vendor_id,po_number,po_date,status,total_amount) VALUES(?,?,?,?,0)','isss',[$vendor_id,$po_number,$po_date,'issued']);
    $po_id = db()->insert_id;
    $names = $_POST['item_name'] ?? [];
    $units = $_POST['item_unit'] ?? [];
    $qtys  = $_POST['item_qty'] ?? [];
    $prices= $_POST['item_price'] ?? [];
    $total = 0;
    for ($i=0; $i<count($names); $i++) {
      $n = trim($names[$i] ?? ''); $u = trim($units[$i] ?? ''); $q = (float)($qtys[$i] ?? 0); $pr = (float)($prices[$i] ?? 0);
      if ($n && $u && $q>0 && $pr>=0) { db_query('INSERT INTO purchase_order_items(po_id,material_name,unit,quantity,unit_price) VALUES(?,?,?,?,?)','issdd',[$po_id,$n,$u,$q,$pr]); $total += $q*$pr; }
    }
    db_query('UPDATE purchase_orders SET total_amount=? WHERE id=?','di',[$total,$po_id]);
    $alert='PO created';
  } elseif ($action === 'delete') {
    $id = (int)$_POST['id'];
    db_query('DELETE FROM purchase_orders WHERE id=?','i',[$id]);
    $alert='PO deleted';
  }
}
$vendors = db_query('SELECT id,name FROM vendors ORDER BY name')->get_result()->fetch_all(MYSQLI_ASSOC);
$pos = db_query('SELECT po.*, v.name AS vendor_name FROM purchase_orders po JOIN vendors v ON v.id=po.vendor_id ORDER BY po.id DESC')->get_result()->fetch_all(MYSQLI_ASSOC);
include __DIR__ . '/../includes/header.php';
?>
<meta name="csrf-token" content="<?php echo htmlspecialchars($csrf); ?>">
<div class="d-flex justify-content-between align-items-center mb-2">
  <h4><i class="fa-solid fa-file-invoice-dollar me-1"></i> Purchase Orders</h4>
  <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modalAdd">Create PO</button>
</div>
<?php if ($alert): ?><div class="alert alert-success"><?php echo htmlspecialchars($alert); ?></div><?php endif; ?>
<div class="card"><div class="card-body">
  <table class="table table-striped datatable">
    <thead><tr><th>ID</th><th>PO Number</th><th>Date</th><th>Vendor</th><th>Status</th><th>Total</th><th>Actions</th></tr></thead>
    <tbody>
    <?php foreach($pos as $po): ?>
      <tr>
        <td><?php echo (int)$po['id']; ?></td>
        <td><?php echo htmlspecialchars($po['po_number']); ?></td>
        <td><?php echo htmlspecialchars($po['po_date']); ?></td>
        <td><?php echo htmlspecialchars($po['vendor_name']); ?></td>
        <td><?php echo htmlspecialchars($po['status']); ?></td>
        <td><?php echo number_format((float)$po['total_amount'],2); ?></td>
        <td>
          <form method="post" class="d-inline" onsubmit="return confirm('Delete PO?');">
            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf); ?>">
            <input type="hidden" name="action" value="delete">
            <input type="hidden" name="id" value="<?php echo (int)$po['id']; ?>">
            <button class="btn btn-sm btn-danger">Delete</button>
          </form>
        </td>
      </tr>
    <?php endforeach; ?>
    </tbody>
  </table>
</div></div>

<div class="modal fade" id="modalAdd" tabindex="-1">
  <div class="modal-dialog modal-lg"><form method="post" class="modal-content">
    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf); ?>">
    <input type="hidden" name="action" value="add">
    <div class="modal-header"><h5 class="modal-title">Create Purchase Order</h5><button class="btn-close" data-bs-dismiss="modal"></button></div>
    <div class="modal-body">
      <div class="row g-2 mb-2">
        <div class="col-md-4"><label class="form-label">Vendor</label>
          <select class="form-select" name="vendor_id" required>
            <option value="">Select</option>
            <?php foreach($vendors as $v): ?><option value="<?php echo (int)$v['id']; ?>"><?php echo htmlspecialchars($v['name']); ?></option><?php endforeach; ?>
          </select>
        </div>
        <div class="col-md-4"><label class="form-label">PO Number</label><input class="form-control" name="po_number" required></div>
        <div class="col-md-4"><label class="form-label">PO Date</label><input type="date" class="form-control" name="po_date" required></div>
      </div>
      <hr>
      <div id="poitems">
        <div class="row g-2 align-items-end item-row">
          <div class="col-md-4"><label class="form-label">Material</label><input class="form-control" name="item_name[]" required></div>
          <div class="col-md-2"><label class="form-label">Unit</label><input class="form-control" name="item_unit[]" required></div>
          <div class="col-md-2"><label class="form-label">Qty</label><input type="number" step="0.001" class="form-control" name="item_qty[]" required></div>
          <div class="col-md-2"><label class="form-label">Unit Price</label><input type="number" step="0.01" class="form-control" name="item_price[]" required></div>
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
  $('#poitems').append(clone);
});
$(document).on('click', '.remove-row', function(){ $(this).closest('.item-row').remove(); });
</script>
<?php include __DIR__ . '/../includes/footer.php'; ?>
