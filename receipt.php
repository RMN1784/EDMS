<?php
require_once __DIR__ . '/includes/orders.php';
require_customer();

$orderId = (int)get_str('order_id', '0');
$order = q_one('SELECT o.*, c.first_name, c.last_name, c.email FROM orders o JOIN customer c ON c.customer_id = o.customer_id
                 WHERE o.order_id = ? AND o.customer_id = ?', [$orderId, cid()]);
if (!$order) { flash('error', 'Receipt not found.'); redirect('orders.php'); }
$pay = q_one('SELECT * FROM payment WHERE order_id = ?', [$orderId]);
if (!$pay) redirect('payment.php?order_id=' . $orderId);
$items = q_all('SELECT d.quantity, d.unit_price, p.product_name FROM order_details d JOIN product p ON p.product_id = d.product_id WHERE d.order_id = ?', [$orderId]);

$pageTitle = 'Receipt #' . $orderId;
require __DIR__ . '/includes/header.php';
?>
<div class="receipt">
  <div class="receipt-head">
    <div><h2 style="margin:0"><?= e(STORE_NAME) ?></h2><div class="small muted"><?= e(STORE_ADDRESS) ?> &middot; <?= e(STORE_PHONE) ?></div></div>
    <div class="right"><b>Receipt</b><div class="small muted">Order #<?= (int)$order['order_id'] ?><br><?= e(fmt_dt($order['order_date'])) ?></div></div>
  </div>
  <div class="row small">
    <div><b>Billed to</b><br><?= e($order['first_name'] . ' ' . $order['last_name']) ?><br><?= e($order['email']) ?><br>Mobile: <?= e($order['contact_mobile']) ?></div>
    <div><b>Delivery address (<?= e($order['location_type']) ?>)</b><br><?= e($order['delivery_address']) ?></div>
    <div><b>Payment</b><br>Payment ID: <?= (int)$pay['payment_id'] ?><br>Method: <?= e($pay['payment_type']) ?><br>Reference: <?= e($pay['transaction_ref']) ?><br>Status: <?= badge($pay['status']) ?></div>
  </div>
  <table class="t mt"><thead><tr><th>Medicine</th><th>Qty</th><th class="right">Price</th><th class="right">Amount</th></tr></thead><tbody>
    <?php foreach ($items as $it): ?><tr><td><?= e($it['product_name']) ?></td><td><?= (int)$it['quantity'] ?></td><td class="right"><?= money($it['unit_price']) ?></td><td class="right"><?= money($it['quantity'] * $it['unit_price']) ?></td></tr><?php endforeach; ?>
  </tbody></table>
  <div class="totals mt">
    <div><span>Subtotal</span><span><?= money($order['subtotal']) ?></span></div>
    <div><span>Delivery</span><span><?= $order['delivery_fee'] > 0 ? money($order['delivery_fee']) : 'FREE' ?></span></div>
    <div class="grand"><span>Total</span><span><?= money($order['order_amount']) ?></span></div>
  </div>
  <p class="center mt"><?php if ($pay['status'] === 'Success'): ?><span class="paid-stamp">PAID</span><?php elseif ($pay['status'] === 'Pending'): ?><span class="badge badge-warn">Cash to be collected on delivery</span><?php endif; ?></p>
  <p class="small muted center">This is a computer generated receipt. Medicines are dispensed as per prescription where applicable.</p>
  <div class="center no-print mt">
    <a class="btn" href="<?= e(url('track.php?order_id=' . $orderId)) ?>">Track delivery</a>
    <button class="btn btn-ghost" onclick="window.print()">Print receipt</button>
    <a class="btn btn-ghost" href="<?= e(url('orders.php')) ?>">My orders</a>
  </div>
</div>
<?php require __DIR__ . '/includes/footer.php'; ?>
