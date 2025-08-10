<?php
require_once __DIR__ . '/../includes/auth.php';
require_login();
require_once __DIR__ . '/../includes/db.php';

$alert='';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  auth_require_csrf();
  $action = $_POST['action'] ?? '';
  if ($action === 'add') {
    db_query('INSERT INTO vendors(name,contact_person,phone,email,address) VALUES(?,?,?,?,?)','sssss',[
      trim($_POST['name']), trim($_POST['contact_person']), trim($_POST['phone']), trim($_POST['email']), trim($_POST['address'])
    ]); $alert='Vendor added';
  } elseif ($action === 'update') {
    db_query('UPDATE vendors SET name=?, contact_person=?, phone=?, email=?, address=? WHERE id=?','sssssi',[
      trim($_POST['name']), trim($_POST['contact_person']), trim($_POST['phone']), trim($_POST['email']), trim($_POST['address']), (int)$_POST['id']
    ]); $alert='Vendor updated';
  } elseif ($action === 'delete') {
    db_query('DELETE FROM vendors WHERE id=?','i',[(int)$_POST['id']]); $alert='Vendor deleted';
  }
}
$rows = db_query('SELECT * FROM vendors ORDER BY id DESC')->get_result()->fetch_all(MYSQLI_ASSOC);
include __DIR__ . '/../includes/header.php';
?>
<meta name="csrf-token" content="<?php echo htmlspecialchars($csrf); ?>">
<div class="d-flex justify-content-between align-items-center mb-2">
  <h4><i class="fa-solid fa-truck-field me-1"></i> Vendors</h4>
  <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modalAdd">Add Vendor</button>
</div>
<?php if ($alert): ?><div class="alert alert-success"><?php echo htmlspecialchars($alert); ?></div><?php endif; ?>
<div class="card"><div class="card-body">
  <table class="table table-striped datatable">
    <thead><tr><th>ID</th><th>Name</th><th>Contact</th><th>Phone</th><th>Email</th><th>Address</th><th>Actions</th></tr></thead>
    <tbody>
      <?php foreach($rows as $r): ?>
        <tr>
          <td><?php echo (int)$r['id']; ?></td>
          <td><?php echo htmlspecialchars($r['name']); ?></td>
          <td><?php echo htmlspecialchars($r['contact_person']); ?></td>
          <td><?php echo htmlspecialchars($r['phone']); ?></td>
          <td><?php echo htmlspecialchars($r['email']); ?></td>
          <td><?php echo nl2br(htmlspecialchars($r['address'])); ?></td>
          <td>
            <button class="btn btn-sm btn-warning" data-bs-toggle="modal" data-bs-target="#modalEdit<?php echo (int)$r['id']; ?>">Edit</button>
            <form method="post" class="d-inline" onsubmit="return confirm('Delete vendor?');">
              <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf); ?>">
              <input type="hidden" name="action" value="delete">
              <input type="hidden" name="id" value="<?php echo (int)$r['id']; ?>">
              <button class="btn btn-sm btn-danger">Delete</button>
            </form>
          </td>
        </tr>
        <div class="modal fade" id="modalEdit<?php echo (int)$r['id']; ?>" tabindex="-1"><div class="modal-dialog"><form method="post" class="modal-content">
          <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf); ?>">
          <input type="hidden" name="action" value="update">
          <input type="hidden" name="id" value="<?php echo (int)$r['id']; ?>">
          <div class="modal-header"><h5 class="modal-title">Edit Vendor</h5><button class="btn-close" data-bs-dismiss="modal"></button></div>
          <div class="modal-body">
            <div class="mb-2"><label class="form-label">Name</label><input class="form-control" name="name" value="<?php echo htmlspecialchars($r['name']); ?>" required></div>
            <div class="mb-2"><label class="form-label">Contact</label><input class="form-control" name="contact_person" value="<?php echo htmlspecialchars($r['contact_person']); ?>"></div>
            <div class="mb-2"><label class="form-label">Phone</label><input class="form-control" name="phone" value="<?php echo htmlspecialchars($r['phone']); ?>"></div>
            <div class="mb-2"><label class="form-label">Email</label><input type="email" class="form-control" name="email" value="<?php echo htmlspecialchars($r['email']); ?>"></div>
            <div class="mb-2"><label class="form-label">Address</label><textarea class="form-control" name="address"><?php echo htmlspecialchars($r['address']); ?></textarea></div>
          </div>
          <div class="modal-footer"><button class="btn btn-primary">Save</button></div>
        </form></div></div>
      <?php endforeach; ?>
    </tbody>
  </table>
</div></div>

<div class="modal fade" id="modalAdd" tabindex="-1"><div class="modal-dialog"><form method="post" class="modal-content">
  <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf); ?>">
  <input type="hidden" name="action" value="add">
  <div class="modal-header"><h5 class="modal-title">Add Vendor</h5><button class="btn-close" data-bs-dismiss="modal"></button></div>
  <div class="modal-body">
    <div class="mb-2"><label class="form-label">Name</label><input class="form-control" name="name" required></div>
    <div class="mb-2"><label class="form-label">Contact</label><input class="form-control" name="contact_person"></div>
    <div class="mb-2"><label class="form-label">Phone</label><input class="form-control" name="phone"></div>
    <div class="mb-2"><label class="form-label">Email</label><input type="email" class="form-control" name="email"></div>
    <div class="mb-2"><label class="form-label">Address</label><textarea class="form-control" name="address"></textarea></div>
  </div>
  <div class="modal-footer"><button class="btn btn-primary">Save</button></div>
</form></div></div>
<?php include __DIR__ . '/../includes/footer.php'; ?>
