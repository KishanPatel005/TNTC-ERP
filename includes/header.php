<?php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/auth.php';
$u = current_user();
$csrf = auth_csrf_token();

// Compute project root URL so that links work from both / and /modules/*
function app_root_url_from_script(): string {
    $https = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || (isset($_SERVER['SERVER_PORT']) && $_SERVER['SERVER_PORT'] == 443);
    $scheme = $https ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
    $dir = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '')), '/');
    if (substr($dir, -8) === '/modules') { // remove /modules when inside modules
        $dir = substr($dir, 0, -8);
    }
    return rtrim("{$scheme}://{$host}{$dir}", '/');
}
$ROOT_URL = app_root_url_from_script();

function nav_active(array $endPaths): string {
    $cur = $_SERVER['SCRIPT_NAME'] ?? '';
    foreach ($endPaths as $p) {
        if (str_ends_with($cur, '/' . $p) || str_ends_with($cur, $p)) return 'active';
    }
    return '';
}
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <meta name="csrf-token" content="<?php echo htmlspecialchars($csrf); ?>">
  <title><?php echo APP_NAME; ?></title>
  <base href="<?php echo htmlspecialchars($ROOT_URL); ?>/">
  <!-- Bootstrap 5 -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <!-- Bootstrap Icons -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
  <!-- Font Awesome 6 -->
  <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" rel="stylesheet">
  <!-- DataTables Bootstrap 5 -->
  <link href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css" rel="stylesheet">
  <!-- App custom CSS -->
  <link href="assets/css/app.css" rel="stylesheet">
  <style>
    /* Minimal NiceAdmin-like layout */
    body { background-color: #f6f9ff; }
    .header { height: 60px; z-index: 1030; }
    .header .logo { font-weight: 600; font-size: 1.15rem; letter-spacing: .3px; }
    .header .logo span { color: #012970; }
    .toggle-sidebar-btn { font-size: 1.3rem; cursor: pointer; color: #012970; }
    .sidebar { position: fixed; top: 60px; left: 0; bottom: 0; width: 260px; background: #fff; border-right: 1px solid #e1e5ef; padding: .5rem 0; overflow-y: auto; }
    .sidebar .nav-link { color: #012970; padding: .55rem 1rem; border-left: 3px solid transparent; }
    .sidebar .nav-link.active, .sidebar .nav-link:hover { background: #f0f4ff; border-left-color: #4154f1; color: #4154f1; }
    .main { margin-top: 60px; margin-left: 0; min-height: calc(100vh - 60px); }
    @media (min-width: 992px) { .main { margin-left: 260px; } }
    /* Mobile sidebar */
    @media (max-width: 991.98px) {
      .sidebar { transform: translateX(-100%); transition: transform .2s; }
      body.sidebar-open .sidebar { transform: translateX(0); }
    }
  </style>
</head>
<body>
  <!-- Header -->
  <header id="header" class="header fixed-top d-flex align-items-center bg-white border-bottom shadow-sm">
    <div class="container-fluid d-flex align-items-center justify-content-between">
      <div class="d-flex align-items-center gap-2">
<?php
$currentPage = basename($_SERVER['SCRIPT_NAME']);

if ($u) {
    if ($currentPage === 'index.php' || $currentPage === 'index') {
        // Button version
        echo '<button id="sidebarTogglee" class="toggle-sidebar-btn" style="background:none;border:none;cursor:pointer;">
                <i class="bi bi-list"></i>
              </button>';
    } else {
        // Icon-only version
        echo '<i class="bi bi-list toggle-sidebar-btn" id="sidebarToggle"></i>';
    }
}
?>

<script>
(function(){
    const body = document.body;
    const toggle = document.getElementById('sidebarTogglee');
    if (toggle) {
        toggle.addEventListener('click', () => {
            body.classList.toggle('sidebar-open');
        });
    }
})();
</script>

<style>
  .sidebar {
    /* position: fixed; or absolute depending on layout */
    /* top: 0; */
    /* left: 0; */
    z-index: 9999; /* make sure it's above all other elements */
}

</style>
        <a href="index.php" class="text-decoration-none logo"><span>TNTC</span></a>
      </div>
      <div class="d-flex align-items-center">
        <?php if ($u): ?>
          <div class="dropdown">
            <a href="#" class="d-flex align-items-center text-decoration-none" data-bs-toggle="dropdown" aria-expanded="false">
              <span class="me-2 text-secondary"><?php echo htmlspecialchars($u['name']); ?></span>
              <img src="https://ui-avatars.com/api/?name=<?php echo urlencode($u['name']); ?>&background=4154f1&color=fff&size=32" class="rounded-circle" alt="User">
            </a>
            <ul class="dropdown-menu dropdown-menu-end shadow-sm">
              <li><a class="dropdown-item" href="modules/profile.php"><i class="bi bi-person me-2"></i>Profile</a></li>
              <li><hr class="dropdown-divider"></li>
              <li><a class="dropdown-item" href="logout.php" onclick="return confirm('Logout?');"><i class="bi bi-box-arrow-right me-2"></i>Logout</a></li>
            </ul>
          </div>
        <?php else: ?>
          <a class="btn btn-outline-primary btn-sm" href="login.php">Login</a>
        <?php endif; ?>
      </div>
    </div>
  </header>

  <?php if ($u): ?>
  <!-- Sidebar -->
  <aside id="sidebar" class="sidebar">
    <ul class="nav flex-column">
      <li class="nav-item"><a class="nav-link <?php echo nav_active(['index.php']); ?>" href="index.php"><i class="bi bi-speedometer2 me-2"></i>Dashboard</a></li>
      <li class="px-3 text-uppercase small text-muted mt-2 mb-1">Projects</li>
      <li class="nav-item"><a class="nav-link <?php echo nav_active(['modules/projects.php']); ?>" href="modules/projects.php"><i class="bi bi-diagram-3 me-2"></i>Projects</a></li>
      <li class="nav-item"><a class="nav-link <?php echo nav_active(['modules/tasks.php']); ?>" href="modules/tasks.php"><i class="bi bi-list-check me-2"></i>Tasks</a></li>
      <li class="px-3 text-uppercase small text-muted mt-2 mb-1">Materials</li>
      <li class="nav-item"><a class="nav-link <?php echo nav_active(['modules/material_requests.php']); ?>" href="modules/material_requests.php"><i class="bi bi-box-seam me-2"></i>Material Requests</a></li>
      <li class="nav-item"><a class="nav-link <?php echo nav_active(['modules/stock.php']); ?>" href="modules/stock.php"><i class="bi bi-boxes me-2"></i>Stock</a></li>
      <li class="nav-item"><a class="nav-link <?php echo nav_active(['modules/stock_logs.php']); ?>" href="modules/stock_logs.php"><i class="bi bi-clock-history me-2"></i>Stock Logs</a></li>
      <li class="px-3 text-uppercase small text-muted mt-2 mb-1">PO & GRN</li>
      <li class="nav-item"><a class="nav-link <?php echo nav_active(['modules/vendors.php']); ?>" href="modules/vendors.php"><i class="bi bi-truck me-2"></i>Vendors</a></li>
      <li class="nav-item"><a class="nav-link <?php echo nav_active(['modules/purchase_orders.php']); ?>" href="modules/purchase_orders.php"><i class="bi bi-receipt me-2"></i>Purchase Orders</a></li>
      <li class="nav-item"><a class="nav-link <?php echo nav_active(['modules/grn.php']); ?>" href="modules/grn.php"><i class="bi bi-bag-check me-2"></i>GRN</a></li>
      <li class="px-3 text-uppercase small text-muted mt-2 mb-1">HR</li>
      <li class="nav-item"><a class="nav-link <?php echo nav_active(['modules/attendance.php']); ?>" href="modules/attendance.php"><i class="bi bi-people me-2"></i>Attendance</a></li>
      <li class="nav-item"><a class="nav-link <?php echo nav_active(['modules/salary.php']); ?>" href="modules/salary.php"><i class="bi bi-cash-coin me-2"></i>Salary</a></li>
      <li class="px-3 text-uppercase small text-muted mt-2 mb-1">Reports</li>
      <li class="nav-item"><a class="nav-link <?php echo nav_active(['modules/reports.php']); ?>" href="modules/reports.php"><i class="bi bi-graph-up-arrow me-2"></i>Reports</a></li>
    </ul>
  </aside>
  <?php endif; ?>

  <!-- Main content wrapper -->
  <main id="main" class="main">
    <div class="container-fluid pt-3">
<style>
/* 1) Make table container scroll horizontally on small screens */
.table-responsive, .card .card-body {
  overflow-x: auto;
  -webkit-overflow-scrolling: touch;
}

/* 2) Ensure table can be wider than viewport so it scrolls */
.table-responsive table, .card .card-body table {
  width: 100%;
  min-width: 720px; /* adjust to your number of columns */
  table-layout: auto;
}

/* 3) Prevent cells from wrapping (keeps columns stable) */
.table-responsive th,
.table-responsive td,
.card .card-body th,
.card .card-body td {
  white-space: nowrap;
  word-break: normal;
}

/* 4) Stack action buttons on narrow screens */
@media (max-width: 576px) {
  .btn-stack-sm .btn {
    display: block;
    width: 100%;
    margin-bottom: 6px;
  }
}

/* 5) Sidebar z-index fix (adjust selector to match your sidebar/offcanvas) */
.sidebar, .offcanvas {
  /* z-index: 2000 !important; */
}

/* 6) Ensure tables / DataTables wrapper sit below the sidebar */
.dataTables_wrapper { position: relative; z-index: 1; }
</style>
