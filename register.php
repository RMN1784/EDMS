<?php
require_once __DIR__ . '/includes/orders.php';
if (is_customer()) redirect('index.php');

$errors = [];
$v = ['first_name' => '', 'last_name' => '', 'email' => '', 'mobile' => '', 'address' => ''];

if (is_post()) {
    csrf_check();
    foreach ($v as $k => $_) $v[$k] = post($k);
    $v['email'] = strtolower($v['email']);
    $pw = $_POST['password'] ?? '';
    $pw2 = $_POST['confirm'] ?? '';

    if (!valid_name($v['first_name'])) $errors[] = 'Enter a valid first name (letters only, up to 30 characters).';
    if (!valid_name($v['last_name']))  $errors[] = 'Enter a valid last name (letters only, up to 30 characters).';
    if (!valid_email($v['email']))     $errors[] = 'Enter a valid email address.';
    if (!valid_mobile($v['mobile']))   $errors[] = 'Enter a valid 10 digit Indian mobile number.';
    if (strlen($v['address']) < 10 || strlen($v['address']) > 255) $errors[] = 'Enter your full address (10 to 255 characters).';
    if ($err = valid_password($pw))    $errors[] = $err;
    if ($pw !== $pw2)                  $errors[] = 'Passwords do not match.';

    if (!$errors && q_val('SELECT 1 FROM login WHERE username = ?', [$v['email']])) {
        $errors[] = 'An account with this email already exists. Please log in.';
    }

    if (!$errors) {
        $pdo = db();
        try {
            $pdo->beginTransaction();
            q('INSERT INTO login (username, password, role, created_at) VALUES (?,?,?,?)', [$v['email'], password_hash($pw, PASSWORD_DEFAULT), 'customer', now()]);
            q('INSERT INTO customer (first_name, last_name, email, address, mobile_no, created_at) VALUES (?,?,?,?,?,?)',
              [$v['first_name'], $v['last_name'], $v['email'], $v['address'], $v['mobile'], now()]);
            $pdo->commit();
            flash('ok', 'Registration successful. Please log in.');
            redirect('login.php');
        } catch (PDOException $ex) {
            if ($pdo->inTransaction()) $pdo->rollBack();
            $errors[] = 'Could not create the account. Please try again.';
        }
    }
}

$pageTitle = 'Register';
require __DIR__ . '/includes/header.php';
?>
<div class="card auth-box" style="max-width:600px">
  <h2>Create your account</h2>
  <p class="muted">Register once and order medicines in seconds, any time of the day.</p>
  <?php foreach ($errors as $er): ?><div class="alert alert-error"><?= e($er) ?></div><?php endforeach; ?>
  <form method="post" novalidate>
    <?= csrf_field() ?>
    <div class="row">
      <div class="field"><label for="fn">First name</label><input id="fn" type="text" name="first_name" value="<?= e($v['first_name']) ?>" maxlength="30" required></div>
      <div class="field"><label for="ln">Last name</label><input id="ln" type="text" name="last_name" value="<?= e($v['last_name']) ?>" maxlength="30" required></div>
    </div>
    <div class="row">
      <div class="field"><label for="em">Email</label><input id="em" type="email" name="email" value="<?= e($v['email']) ?>" maxlength="100" required></div>
      <div class="field"><label for="mb">Mobile number</label><input id="mb" type="tel" name="mobile" value="<?= e($v['mobile']) ?>" maxlength="10" pattern="[6-9][0-9]{9}" required></div>
    </div>
    <div class="field"><label for="ad">Delivery address</label><textarea id="ad" name="address" maxlength="255" required><?= e($v['address']) ?></textarea></div>
    <div class="row">
      <div class="field"><label for="pw">Password</label><input id="pw" type="password" name="password" minlength="8" required><div class="hint">At least 8 characters with letters and numbers.</div></div>
      <div class="field"><label for="cf">Confirm password</label><input id="cf" type="password" name="confirm" minlength="8" required></div>
    </div>
    <button class="btn btn-block" type="submit">Create account</button>
  </form>
  <p class="center small mt">Already registered? <a href="<?= e(url('login.php')) ?>">Log in</a></p>
</div>
<?php require __DIR__ . '/includes/footer.php'; ?>
