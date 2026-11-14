<?php
/**
 * One click installer: creates the `pharmacy` database, all 12 tables and the sample data.
 * Open http://localhost/<project-folder>/install.php once. Delete or rename this file afterwards.
 */
require_once __DIR__ . '/config.php';
session_start();
if (empty($_SESSION['inst'])) $_SESSION['inst'] = bin2hex(random_bytes(16));

function split_sql(string $sql): array
{
    $stmts = []; $buf = ''; $inQ = false; $len = strlen($sql);
    for ($i = 0; $i < $len; $i++) {
        $ch = $sql[$i];
        if ($inQ) {
            $buf .= $ch;
            if ($ch === '\\') { $buf .= $sql[++$i] ?? ''; }
            elseif ($ch === "'") { if (($sql[$i + 1] ?? '') === "'") { $buf .= $sql[++$i]; } else { $inQ = false; } }
            continue;
        }
        if ($ch === '-' && ($sql[$i + 1] ?? '') === '-') { while ($i < $len && $sql[$i] !== "\n") $i++; $buf .= "\n"; continue; }
        if ($ch === "'") { $inQ = true; $buf .= $ch; continue; }
        if ($ch === ';') { if (trim($buf) !== '') $stmts[] = trim($buf); $buf = ''; continue; }
        $buf .= $ch;
    }
    if (trim($buf) !== '') $stmts[] = trim($buf);
    return $stmts;
}

$log = []; $ok = null; $exists = false;
try {
    $pdo = new PDO('mysql:host=' . DB_HOST . ';port=' . DB_PORT . ';charset=utf8mb4', DB_USER, DB_PASS, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
    $exists = (bool)$pdo->query("SELECT 1 FROM information_schema.tables WHERE table_schema = '" . str_replace("'", '', DB_NAME) . "' AND table_name = 'login'")->fetchColumn();
} catch (PDOException $e) {
    $log[] = 'Cannot connect to MySQL: ' . $e->getMessage();
    $log[] = 'Start MySQL from the XAMPP control panel and check DB_USER / DB_PASS in config.php.';
    $pdo = null; $ok = false;
}

if ($pdo && $_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!hash_equals($_SESSION['inst'], $_POST['token'] ?? '')) { exit('Invalid request.'); }
    if ($exists && empty($_POST['confirm'])) {
        $log[] = 'Tick the confirmation box to replace the existing database.'; $ok = false;
    } else {
        try {
            $sql = file_get_contents(__DIR__ . '/database/database.sql');
            $sql = str_replace(['CREATE DATABASE IF NOT EXISTS pharmacy', 'USE pharmacy;'], ['CREATE DATABASE IF NOT EXISTS `' . DB_NAME . '`', 'USE `' . DB_NAME . '`;'], $sql);
            $n = 0;
            foreach (split_sql($sql) as $stmt) { $pdo->exec($stmt); $n++; }
            $log[] = "Database '" . DB_NAME . "' created. $n SQL statements executed.";
            $ok = true;
        } catch (Throwable $e) {
            $log[] = 'Installation failed: ' . $e->getMessage(); $ok = false;
        }
    }
}
$base = rtrim(dirname($_SERVER['SCRIPT_NAME']), '/');
?>
<!doctype html><html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
<title>Install | EMDS</title><link rel="stylesheet" href="<?= htmlspecialchars($base) ?>/assets/css/style.css">
<link rel="icon" href="data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 64 64'%3E%3Crect width='64' height='64' rx='14' fill='%23d7263d'/%3E%3Cpath d='M26 12h12v14h14v12H38v14H26V38H12V26h14z' fill='%23fff'/%3E%3C/svg%3E"></head>
<body class="login-bg">
<div class="login-card" style="width:min(560px,94vw)">
  <div class="brand" style="margin-bottom:14px"><span class="brand-mark">+</span><span class="brand-text"><b>EMDS Installer</b><small>Emergency Medicine Delivery System</small></span></div>
  <?php foreach ($log as $l): ?><div class="alert <?= $ok ? 'alert-ok' : 'alert-error' ?>"><?= htmlspecialchars($l) ?></div><?php endforeach; ?>
  <?php if ($ok === true): ?>
    <p>Setup complete.</p>
    <p><a class="btn" href="<?= htmlspecialchars($base) ?>/index.php">Open website</a> <a class="btn btn-ghost" href="<?= htmlspecialchars($base) ?>/admin/login.php">Open admin panel</a></p>
    <div class="alert alert-info small">Admin: <b>admin@emds.in</b> / <b>Admin@123</b><br>Customer: <b>aarav@example.com</b> / <b>Customer@123</b></div>
    <p class="small muted">For safety, delete <b>install.php</b> after setup.</p>
  <?php elseif ($pdo): ?>
    <p>This creates the <b><?= htmlspecialchars(DB_NAME) ?></b> database with 12 tables and sample medicines, customers and orders.</p>
    <form method="post"><input type="hidden" name="token" value="<?= htmlspecialchars($_SESSION['inst']) ?>">
      <?php if ($exists): ?><div class="alert alert-warn">A database with this name already exists. Continuing will erase and recreate it.</div>
      <label class="check" style="margin-bottom:14px"><input type="checkbox" name="confirm" value="1"> Yes, replace the existing data</label><?php endif; ?>
      <button class="btn btn-block" type="submit"><?= $exists ? 'Reset and install' : 'Install now' ?></button></form>
  <?php endif; ?>
</div></body></html>
