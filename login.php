<?php
require_once __DIR__ . '/includes/orders.php';
if (is_customer()) redirect('index.php');

$error = '';
$email = '';
if (is_post()) {
    csrf_check();
    $email = strtolower(post('email'));
    $pw    = $_POST['password'] ?? '';

    if (login_throttled()) {
        $error = 'Too many failed attempts. Please wait 5 minutes and try again.';
    } else {
        $row = q_one("SELECT l.username, l.password, l.role, l.is_active, c.customer_id, c.first_name
                        FROM login l LEFT JOIN customer c ON c.email = l.username WHERE l.username = ?", [$email]);
        if ($row && password_verify($pw, $row['password'])) {
            if ($row['role'] !== 'customer') {
                $error = 'This is an administrator account. Please use the admin login page.';
            } elseif (!$row['is_active']) {
                $error = 'Your account has been disabled. Please contact the pharmacy.';
            } else {
                login_ok();
                $_SESSION['role']  = 'customer';
                $_SESSION['cid']   = (int)$row['customer_id'];
                $_SESSION['name']  = $row['first_name'];
                $_SESSION['email'] = $row['username'];
                q('UPDATE login SET last_login = ? WHERE username = ?', [now(), $row['username']]);
                $dest = $_SESSION['after_login'] ?? '';
                unset($_SESSION['after_login']);
                // only allow redirects back into this site
                redirect($dest && $dest[0] === '/' && strpos($dest, '//') !== 0 ? $dest : 'index.php');
            }
        } else {
            login_failed();
            $error = 'Incorrect email or password.';
        }
    }
}

$pageTitle = 'Login';
require __DIR__ . '/includes/header.php';
?>
<div class="card auth-box">
  <h2>Welcome back</h2>
  <p class="muted">Log in to order medicines and track deliveries.</p>
  <?php if ($error): ?><div class="alert alert-error"><?= e($error) ?></div><?php endif; ?>
  <form method="post">
    <?= csrf_field() ?>
    <div class="field"><label for="em">Email</label><input id="em" type="email" name="email" value="<?= e($email) ?>" required autofocus></div>
    <div class="field"><label for="pw">Password</label><input id="pw" type="password" name="password" required></div>
    <button class="btn btn-block" type="submit">Log in</button>
  </form>
  <p class="center small mt">New here? <a href="<?= e(url('register.php')) ?>">Create an account</a> &middot; <a href="<?= e(url('admin/login.php')) ?>">Admin login</a></p>
  <div class="alert alert-info small" style="margin:14px 0 0">Demo customer: <b>aarav@example.com</b> / <b>Customer@123</b></div>
</div>
<?php require __DIR__ . '/includes/footer.php'; ?>
