<?php
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/db.php';

if (current_user()) { header('Location: index.php'); exit; }

$error = '';

// If no users, allow bootstrap of first Admin
$users_count = db_query('SELECT COUNT(*) AS c FROM users');
$c = $users_count->get_result()->fetch_assoc()['c'] ?? 0;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    auth_require_csrf();
    if (isset($_POST['action']) && $_POST['action'] === 'register_admin' && $c == 0) {
        $name = trim($_POST['name'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $pass = $_POST['password'] ?? '';
        if (!$name || !filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($pass) < 6) {
            $error = 'Enter valid name, email and password (min 6 chars).';
        } else {
            // Ensure roles exist and get Admin role id
            $roleStmt = db_query('SELECT id FROM roles WHERE name = "Admin" LIMIT 1');
            $rid = $roleStmt->get_result()->fetch_assoc()['id'] ?? null;
            if (!$rid) {
                // Seed base roles if missing
                db_query('INSERT INTO roles (name) VALUES ("Admin"),("Manager"),("Staff") ON DUPLICATE KEY UPDATE name=VALUES(name)');
                $roleStmt = db_query('SELECT id FROM roles WHERE name = "Admin" LIMIT 1');
                $rid = $roleStmt->get_result()->fetch_assoc()['id'] ?? null;
            }
            if (!$rid) {
                $error = 'Unable to create Admin role. Please check DB permissions.';
            } else {
                $hash = password_hash($pass, PASSWORD_DEFAULT);
                $stmt = db_query('INSERT INTO users(name,email,password_hash,role_id) VALUES(?,?,?,?)','sssi',[$name,$email,$hash,$rid]);
                if ($stmt) {
                    attempt_login($email, $pass);
                    header('Location: index.php');
                    exit;
                } else { $error = 'Failed to create user.'; }
            }
        }
    } else {
        $email = trim($_POST['email'] ?? '');
        $pass = $_POST['password'] ?? '';
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error = 'Invalid email';
        } elseif (!attempt_login($email, $pass)) {
            $error = 'Invalid credentials';
        } else {
            header('Location: index.php');
            exit;
        }
    }
}

include __DIR__ . '/includes/header.php';
?>
<meta name="csrf-token" content="<?php echo htmlspecialchars($csrf); ?>">
<div class="row justify-content-center mt-5">
  <div class="col-md-5">
    <div class="card">
      <div class="card-header"><i class="fa-solid fa-right-to-bracket me-1"></i> Login</div>
      <div class="card-body">
        <?php if ($error): ?><div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div><?php endif; ?>
        <?php if ($c == 0): ?>
        <div class="alert alert-info">No users found. Create the first Admin account.</div>
        <form method="post" class="needs-validation" novalidate>
          <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf); ?>">
          <input type="hidden" name="action" value="register_admin">
          <div class="mb-2">
            <label class="form-label">Name</label>
            <input class="form-control" name="name" required>
          </div>
          <div class="mb-2">
            <label class="form-label">Email</label>
            <input type="email" class="form-control" name="email" required>
          </div>
          <div class="mb-2">
            <label class="form-label">Password</label>
            <input type="password" class="form-control" name="password" minlength="6" required>
          </div>
          <button class="btn btn-primary w-100">Create Admin & Login</button>
        </form>
        <?php else: ?>
        <form method="post" class="needs-validation" novalidate>
          <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf); ?>">
          <div class="mb-2">
            <label class="form-label">Email</label>
            <input type="email" class="form-control" name="email" required>
          </div>
          <div class="mb-2">
            <label class="form-label">Password</label>
            <input type="password" class="form-control" name="password" required>
          </div>
          <button class="btn btn-primary w-100">Login</button>
        </form>
        <?php endif; ?>
      </div>
    </div>
  </div>
</div>
<script>
(() => {
  'use strict'
  const forms = document.querySelectorAll('.needs-validation')
  Array.from(forms).forEach(form => {
    form.addEventListener('submit', event => {
      if (!form.checkValidity()) {
        event.preventDefault()
        event.stopPropagation()
      }
      form.classList.add('was-validated')
    }, false)
  })
})()
</script>
<?php include __DIR__ . '/includes/footer.php'; ?>
