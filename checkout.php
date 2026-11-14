<?php
require_once __DIR__ . '/includes/orders.php';
require_customer();

$cust  = q_one('SELECT * FROM customer WHERE customer_id = ?', [cid()]);
$items = cart_items(cid());
if (!$items) { flash('warn', 'Your cart is empty.'); redirect('products.php'); }

$subtotal = 0; $needsRx = false;
foreach ($items as $it) { $subtotal += $it['quantity'] * $it['product_price']; if ($it['requires_prescription']) $needsRx = true; }
$fee = delivery_fee_for($subtotal);

$prescriptions = $needsRx ? q_all("SELECT prescription_id, original_name, status, uploaded_at FROM prescription
                                    WHERE customer_id = ? AND status <> 'Rejected' ORDER BY prescription_id DESC", [cid()]) : [];

$errors = [];
$form = ['address' => $cust['address'], 'mobile' => (string)$cust['mobile_no'], 'location_type' => 'Home', 'notes' => '', 'rx' => ''];

if (is_post()) {
    csrf_check();
    foreach (['address', 'mobile', 'location_type', 'notes'] as $k) $form[$k] = post($k);
    $form['rx'] = post('prescription_id');

    if (strlen($form['address']) < 10 || strlen($form['address']) > 255) $errors[] = 'Enter a complete delivery address (10 to 255 characters).';
    if (!valid_mobile($form['mobile'])) $errors[] = 'Enter a valid 10 digit mobile number so the agent can call you.';
    if (!in_array($form['location_type'], ['Home', 'Hospital', 'Other'], true)) $errors[] = 'Choose a delivery location type.';
    if (strlen($form['notes']) > 200) $errors[] = 'Notes can be at most 200 characters.';

    $rxId = null;
    if ($needsRx && !$errors) {
        $hasFile = isset($_FILES['rx_file']) && ($_FILES['rx_file']['error'] ?? 4) !== UPLOAD_ERR_NO_FILE;
        if ($hasFile) {
            $res = store_prescription_upload($_FILES['rx_file']);
            if (is_string($res)) {
                $errors[] = $res;
            } else {
                q("INSERT INTO prescription (customer_id, file, original_name, status, uploaded_at) VALUES (?,?,?,'Pending',?)",
                  [cid(), $res[0], $res[1], now()]);
                $rxId = (int)db()->lastInsertId();
            }
        } elseif ($form['rx'] !== '') {
            $rxId = (int)$form['rx'];
        } else {
            $errors[] = 'Please upload a prescription or choose one you uploaded earlier.';
        }
    }

    if (!$errors) {
        [$ok, $res] = place_order(cid(), $form['address'], $form['mobile'], $form['location_type'], $form['notes'], $rxId);
        if ($ok) {
            flash('ok', 'Order #' . $res . ' placed. Complete the payment to confirm delivery.');
            redirect('payment.php?order_id=' . $res);
        }
        $errors[] = $res;
    }
}

$pageTitle = 'Checkout';
require __DIR__ . '/includes/header.php';
?>
<h1>Checkout</h1>
<?php foreach ($errors as $er): ?><div class="alert alert-error"><?= e($er) ?></div><?php endforeach; ?>
<form method="post" enctype="multipart/form-data" class="two-col">
  <?= csrf_field() ?>
  <div class="card">
    <h3>Where should we deliver?</h3>
    <div class="field"><label>Delivery location</label>
      <select name="location_type">
        <?php foreach (['Home' => 'Home', 'Hospital' => 'Hospital / clinic', 'Other' => 'Other location'] as $k => $lbl): ?>
          <option value="<?= $k ?>" <?= $form['location_type'] === $k ? 'selected' : '' ?>><?= e($lbl) ?></option>
        <?php endforeach; ?></select></div>
    <div class="field"><label for="addr">Full address (include ward / room / landmark)</label><textarea id="addr" name="address" maxlength="255" required><?= e($form['address']) ?></textarea></div>
    <div class="row">
      <div class="field"><label for="mob">Contact mobile</label><input id="mob" type="tel" name="mobile" value="<?= e($form['mobile']) ?>" maxlength="10" required></div>
      <div class="field"><label for="notes">Note for the agent (optional)</label><input id="notes" type="text" name="notes" value="<?= e($form['notes']) ?>" maxlength="200" placeholder="e.g. Gate 2, call on arrival"></div>
    </div>

    <?php if ($needsRx): ?>
    <h3 class="mt">Prescription</h3>
    <div class="alert alert-warn">Your cart has prescription medicines. Upload a clear photo or PDF of the doctor's prescription (JPG, PNG or PDF, up to 5 MB).</div>
    <?php if ($prescriptions): ?>
      <div class="field"><label for="rxsel">Use a prescription you uploaded earlier</label>
        <select id="rxsel" name="prescription_id"><option value="">Choose...</option>
          <?php foreach ($prescriptions as $rx): ?>
            <option value="<?= (int)$rx['prescription_id'] ?>" <?= $form['rx'] == $rx['prescription_id'] ? 'selected' : '' ?>>
              #<?= (int)$rx['prescription_id'] ?> - <?= e($rx['original_name'] ?: 'file') ?> (<?= e($rx['status']) ?>, <?= e(date('d M', strtotime($rx['uploaded_at']))) ?>)</option>
          <?php endforeach; ?></select></div>
      <p class="muted small">or upload a new one:</p>
    <?php endif; ?>
    <div class="field"><label for="rxf">Upload prescription</label><input id="rxf" type="file" name="rx_file" accept=".jpg,.jpeg,.png,.pdf,image/jpeg,image/png,application/pdf"></div>
    <?php endif; ?>
  </div>

  <div class="card">
    <h3>Order summary</h3>
    <table class="t"><tbody>
      <?php foreach ($items as $it): ?>
        <tr><td><?= e($it['product_name']) ?> <?php if ($it['requires_prescription']): ?><span class="rx-tag">Rx</span><?php endif; ?><div class="small muted">Qty <?= (int)$it['quantity'] ?> x <?= money($it['product_price']) ?></div></td>
            <td class="right"><?= money($it['quantity'] * $it['product_price']) ?></td></tr>
      <?php endforeach; ?></tbody></table>
    <div class="totals mt">
      <div><span>Subtotal</span><span><?= money($subtotal) ?></span></div>
      <div><span>Emergency delivery</span><span><?= $fee > 0 ? money($fee) : 'FREE' ?></span></div>
      <div class="grand"><span>Total</span><span><?= money($subtotal + $fee) ?></span></div>
    </div>
    <button class="btn btn-block" type="submit">Place order and pay</button>
    <p class="small muted center mt">Delivery target: <?= (int)DELIVERY_MINUTES ?> minutes</p>
  </div>
</form>
<?php require __DIR__ . '/includes/footer.php'; ?>
