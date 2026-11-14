<?php
require_once __DIR__ . '/includes/orders.php';
require_customer();

$orderId = (int)get_str('order_id', '0');
$order = q_one('SELECT * FROM orders WHERE order_id = ? AND customer_id = ?', [$orderId, cid()]);
if (!$order) { flash('error', 'Order not found.'); redirect('orders.php'); }
$pay = q_one('SELECT * FROM payment WHERE order_id = ?', [$orderId]);
if ($pay && $pay['status'] === 'Success') redirect('receipt.php?order_id=' . $orderId);
if ($order['status'] === 'Cancelled') { flash('warn', 'This order was cancelled.'); redirect('orders.php'); }

function luhn_ok(string $n): bool
{
    $sum = 0; $alt = false;
    for ($i = strlen($n) - 1; $i >= 0; $i--) {
        $d = (int)$n[$i];
        if ($alt) { $d *= 2; if ($d > 9) $d -= 9; }
        $sum += $d; $alt = !$alt;
    }
    return $sum % 10 === 0;
}

$errors = [];
if (is_post()) {
    csrf_check();
    $method = post('method');
    $ref = '';
    $success = true;

    if ($method === 'UPI') {
        $upi = strtolower(post('upi'));
        if (!preg_match('/^[a-z0-9._-]{2,40}@[a-z]{2,20}$/', $upi)) $errors[] = 'Enter a valid UPI ID, for example name@upi.';
        $ref = 'UPI' . date('ymdHis') . random_int(100, 999);
        if (strpos($upi, 'fail@') === 0) $success = false;   // demo: fail@upi simulates a declined payment
    } elseif ($method === 'Card') {
        $no = preg_replace('/\D/', '', post('card_no'));
        $exp = post('card_exp');
        $cvv = post('card_cvv');
        if (strlen($no) < 13 || strlen($no) > 19 || !luhn_ok($no)) $errors[] = 'Enter a valid card number.';
        if (!preg_match('/^(0[1-9]|1[0-2])\/(\d{2})$/', $exp, $m) || strtotime('20' . $m[2] . '-' . $m[1] . '-01 +1 month') < time()) $errors[] = 'Enter a valid, unexpired card expiry (MM/YY).';
        if (!preg_match('/^\d{3,4}$/', $cvv)) $errors[] = 'Enter the 3 or 4 digit CVV.';
        if (strlen(post('card_name')) < 2) $errors[] = 'Enter the name on the card.';
        // Card details are never stored. Only the last 4 digits go into the transaction reference.
        $ref = 'CARD' . date('ymdHis') . '-' . substr($no, -4);
        if ($no === '4000000000000002') $success = false;     // demo: standard "declined" test card
    } elseif ($method === 'Net Banking') {
        $banks = ['SBI', 'HDFC', 'ICICI', 'Axis', 'PNB', 'Kotak'];
        if (!in_array(post('bank'), $banks, true)) $errors[] = 'Choose your bank.';
        $ref = 'NB' . date('ymdHis') . random_int(100, 999);
    } elseif ($method === 'COD') {
        $ref = 'COD-' . $orderId;
    } else {
        $errors[] = 'Choose a payment method.';
    }

    if (!$errors) {
        [$ok, $status] = record_payment($orderId, cid(), $method, $ref, $success);
        if ($ok) {
            flash('ok', $method === 'COD' ? 'Order confirmed. Please keep cash ready for the delivery agent.' : 'Payment successful.');
            redirect('receipt.php?order_id=' . $orderId);
        }
        $errors[] = $status === 'Failed' ? 'Payment was declined by the bank. Please try another method.' : $status;
    }
}

$pageTitle = 'Payment';
require __DIR__ . '/includes/header.php';
?>
<h1>Payment</h1>
<?php foreach ($errors as $er): ?><div class="alert alert-error"><?= e($er) ?></div><?php endforeach; ?>
<?php if ($pay && $pay['status'] === 'Failed'): ?><div class="alert alert-warn">Your last payment attempt failed. You can try again.</div><?php endif; ?>
<?php if ($order['status'] === 'Awaiting Prescription'): ?>
  <div class="alert alert-info">This order is waiting for prescription verification. You can pay now; delivery starts once the prescription is approved.</div>
<?php endif; ?>
<div class="two-col">
  <form method="post" class="card" autocomplete="off">
    <?= csrf_field() ?>
    <h3>Choose a payment method</h3>
    <div class="pm">
      <?php foreach (['UPI' => 'UPI', 'Card' => 'Debit / Credit card', 'Net Banking' => 'Net banking', 'COD' => 'Cash on delivery'] as $k => $lbl): ?>
        <label><input type="radio" name="method" value="<?= e($k) ?>" <?= (post('method') ?: 'UPI') === $k ? 'checked' : '' ?>><?= e($lbl) ?></label>
      <?php endforeach; ?>
    </div>
    <div class="pm-panel" data-for="UPI">
      <div class="field"><label for="upi">UPI ID</label><input id="upi" type="text" name="upi" placeholder="yourname@upi" value="<?= e(post('upi')) ?>"><div class="hint">Demo gateway: any valid UPI ID succeeds. Use <b>fail@upi</b> to see a declined payment.</div></div>
    </div>
    <div class="pm-panel" data-for="Card">
      <div class="field"><label for="cardNo">Card number</label><input id="cardNo" type="text" name="card_no" inputmode="numeric" placeholder="4111 1111 1111 1111"><div class="hint">Demo: 4111 1111 1111 1111 succeeds. 4000 0000 0000 0002 is declined. Card details are never stored.</div></div>
      <div class="row">
        <div class="field"><label for="cn">Name on card</label><input id="cn" type="text" name="card_name"></div>
        <div class="field"><label for="ce">Expiry (MM/YY)</label><input id="ce" type="text" name="card_exp" placeholder="12/30" maxlength="5"></div>
        <div class="field"><label for="cv">CVV</label><input id="cv" type="password" name="card_cvv" maxlength="4" inputmode="numeric"></div>
      </div>
    </div>
    <div class="pm-panel" data-for="Net Banking">
      <div class="field"><label for="bank">Select bank</label><select id="bank" name="bank"><option value="">Choose...</option>
        <?php foreach (['SBI', 'HDFC', 'ICICI', 'Axis', 'PNB', 'Kotak'] as $b): ?><option><?= e($b) ?></option><?php endforeach; ?></select></div>
    </div>
    <div class="pm-panel" data-for="COD"><div class="alert alert-info">Pay <?= money($order['order_amount']) ?> in cash to the delivery agent. Exact change is appreciated.</div></div>
    <button class="btn btn-block" type="submit">Pay <?= money($order['order_amount']) ?></button>
  </form>
  <div class="card">
    <h3>Order #<?= (int)$order['order_id'] ?></h3>
    <div class="totals">
      <div><span>Subtotal</span><span><?= money($order['subtotal']) ?></span></div>
      <div><span>Delivery</span><span><?= $order['delivery_fee'] > 0 ? money($order['delivery_fee']) : 'FREE' ?></span></div>
      <div class="grand"><span>Amount payable</span><span><?= money($order['order_amount']) ?></span></div>
    </div>
    <p class="small muted mt">Deliver to: <?= e($order['delivery_address']) ?></p>
    <?php if (!$pay): ?><p class="small"><a href="<?= e(url('orders.php')) ?>">Pay later from My Orders</a></p><?php endif; ?>
  </div>
</div>
<?php require __DIR__ . '/includes/footer.php'; ?>
