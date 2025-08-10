<?php
require_once __DIR__ . '/../includes/auth.php';
require_login();
require_once __DIR__ . '/../includes/db.php';
$u = current_user();
$alert='';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  auth_require_csrf();
  $name = trim($_POST['name'] ?? '');
  $pass = $_POST['password'] ?? '';
  if ($name) {
    if ($pass) {
      $hash = password_hash($pass, PASSWORD_DEFAULT);
      db_query('UPDATE users SET name=?, password_hash=? WHERE id=?','ssi',[$name,$hash,(int)$u['id']]);
    } else {
      db_query('UPDATE users SET name=? WHERE id=?','si',[$name,(int)$u['id']]);
    }
    $_SESSION['user']['name'] = $name;
    $alert='Profile updated';
  }
}
include __DIR__ . '/../includes/header.php';
?>
<meta name="csrf-token" content="<?php echo htmlspecialchars($csrf); ?>">
<h4 class="mb-2"><i class="fa-solid fa-id-badge me-1"></i> Profile</h4>
<?php if ($alert): ?><div class="alert alert-success"><?php echo htmlspecialchars($alert); ?></div><?php endif; ?>
<div class="card"><div class="card-body" style="max-width:600px;">
  <form method="post">
    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf); ?>">
    <div class="mb-2"><label class="form-label">Name</label><input class="form-control" name="name" value="<?php echo htmlspecialchars($u['name']); ?>" required></div>
    <div class="mb-2"><label class="form-label">Email</label><input class="form-control" value="<?php echo htmlspecialchars($u['email']); ?>" disabled></div>
    <div class="mb-2"><label class="form-label">New Password (optional)</label><input type="password" class="form-control" name="password" minlength="6"></div>
    <button class="btn btn-primary">Save</button>
  </form>
</div></div>
<?php include __DIR__ . '/../includes/footer.php'; ?>
