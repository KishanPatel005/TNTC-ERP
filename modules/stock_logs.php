<?php
require_once __DIR__ . '/../includes/auth.php';
require_login();
require_once __DIR__ . '/../includes/db.php';
$rows = db_query('SELECT sl.*, s.material_name, s.unit FROM stock_log sl JOIN stock s ON s.id = sl.stock_id ORDER BY sl.id DESC LIMIT 1000')->get_result()->fetch_all(MYSQLI_ASSOC);
include __DIR__ . '/../includes/header.php';
?>
<h4 class="mb-2"><i class="fa-solid fa-clock-rotate-left me-1"></i> Stock Logs</h4>
<div class="card"><div class="card-body">
  <table class="table table-striped datatable">
    <thead><tr><th>ID</th><th>Material</th><th>Unit</th><th>Type</th><th>Qty</th><th>Ref</th><th>Note</th><th>Date</th></tr></thead>
    <tbody>
      <?php foreach($rows as $r): ?>
        <tr>
          <td><?php echo (int)$r['id']; ?></td>
          <td><?php echo htmlspecialchars($r['material_name']); ?></td>
          <td><?php echo htmlspecialchars($r['unit']); ?></td>
          <td><?php echo htmlspecialchars($r['change_type']); ?></td>
          <td><?php echo htmlspecialchars($r['quantity']); ?></td>
          <td><?php echo htmlspecialchars($r['ref_type'] . '#' . $r['ref_id']); ?></td>
          <td><?php echo htmlspecialchars($r['note']); ?></td>
          <td><?php echo htmlspecialchars($r['created_at']); ?></td>
        </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
</div></div>
<?php include __DIR__ . '/../includes/footer.php'; ?>
