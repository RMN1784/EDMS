<?php
require_once __DIR__ . '/includes/orders.php';
require_customer();

if (is_post() && post('action') === 'cancel') {
    csrf_check();
    $oid = (int)post('order_id', '0');
    $o = q_one('SELECT status FROM orders WHERE order_id = ? AND customer_id = ?', [$oid, cid()]);
    if (!$o) {
        flash('error', 'Order not found.');
    } elseif (!customer_can_cancel($o['status'])) {
        flash('error', 'This order can no longer be cancelled because it is already ' . strtolower($o['status']) . '.');
    } elseif (cancel_order($oid, 'Cancelled by customer')) {
        flash('ok', 'Order #' . $oid . ' cancelled. Any payment made will be refunded.');
    } else {
        flash('error', 'Could not cancel the order.');
    }
    redirect('orders.php');
}

$pageTitle = 'My orders';
require __DIR__ . '/includes/header.php';

$orders = q_all('SELECT o.*, p.status AS pay_status, p.payment_type,
                        (SELECT COUNT(*) FROM order_details d WHERE d.order_id = o.order_id) AS n_items
                   FROM orders o LEFT JOIN payment p ON p.order_id = o.order_id
                  WHERE o.customer_id = ? ORDER BY o.order_id DESC', [cid()]);
?>
<h1>My orders</h1>
<?php if (!$orders): ?>
  <div class="card empty"><h3>No orders yet</h3><p>Your orders and live delivery status will appear here.</p><a class="btn" href="<?= e(url('products.php')) ?>">Order medicines</a></div>
<?php else: ?>
<div class="table-wrap"><table class="t">
  <thead><tr><th>Order</th><th>Placed</th><th>Items</th><th>Amount</th><th>Status</th><th>Payment</th><th></th></tr></thead><tbody>
  <?php foreach ($orders as $o): ?>
    <tr>
      <td><b>#<?= (int)$o['order_id'] ?></b></td>
      <td class="nowrap"><?= e(fmt_dt($o['order_date'])) ?></td>
      <td><?= (int)$o['n_items'] ?></td>
      <td><?= money($o['order_amount']) ?></td>
      <td><?= badge($o['status']) ?></td>
      <td><?= $o['pay_status'] ? badge($o['pay_status']) . ' <span class="small muted">' . e($o['payment_type']) . '</span>' : '<span class="badge badge-warn">Unpaid</span>' ?></td>
      <td class="nowrap">
        <a class="btn btn-small" href="<?= e(url('track.php?order_id=' . $o['order_id'])) ?>">Track</a>
        <?php if (!$o['pay_status'] && $o['status'] !== 'Cancelled'): ?><a class="btn btn-small btn-teal" href="<?= e(url('payment.php?order_id=' . $o['order_id'])) ?>">Pay</a>
        <?php elseif ($o['pay_status']): ?><a class="btn btn-small btn-ghost" href="<?= e(url('receipt.php?order_id=' . $o['order_id'])) ?>">Receipt</a><?php endif; ?>
        <?php if (customer_can_cancel($o['status'])): ?>
          <form method="post" style="display:inline" data-confirm="Cancel order #<?= (int)$o['order_id'] ?>?"><?= csrf_field() ?><input type="hidden" name="action" value="cancel"><input type="hidden" name="order_id" value="<?= (int)$o['order_id'] ?>"><button class="btn btn-small btn-danger" type="submit">Cancel</button></form>
        <?php endif; ?>
      </td>
    </tr>
  <?php endforeach; ?></tbody></table></div>
<?php endif; ?>
<?php require __DIR__ . '/includes/footer.php'; ?>
