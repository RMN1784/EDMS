<?php
require_once __DIR__ . '/includes/orders.php';
require_customer();

$orderId = (int)get_str('order_id', '0');
$order = q_one('SELECT * FROM orders WHERE order_id = ? AND customer_id = ?', [$orderId, cid()]);
if (!$order) { flash('error', 'Order not found.'); redirect('orders.php'); }
$delivery = q_one('SELECT * FROM delivery WHERE order_id = ?', [$orderId]);
$items = q_all('SELECT d.quantity, d.unit_price, p.product_name FROM order_details d JOIN product p ON p.product_id = d.product_id WHERE d.order_id = ?', [$orderId]);
$pay = q_one('SELECT * FROM payment WHERE order_id = ?', [$orderId]);
$snap = tracking_snapshot($order, $delivery);

$pageTitle = 'Track order #' . $orderId;
$extraScripts = ['assets/js/track.js'];
require __DIR__ . '/includes/header.php';
?>
<p class="small"><a href="<?= e(url('orders.php')) ?>">My orders</a> / Order #<?= $orderId ?></p>
<h1>Track order #<?= $orderId ?> <span id="statusText" class="badge badge-info" style="font-size:.9rem;vertical-align:middle"><?= e($order['status']) ?></span></h1>

<?php if ($order['status'] === 'Awaiting Prescription'): ?>
  <div class="alert alert-warn">A pharmacist is verifying your prescription. Your <?= (int)DELIVERY_MINUTES ?> minute timer starts as soon as it is approved.</div>
<?php endif; ?>
<?php if (!$pay && $order['status'] !== 'Cancelled'): ?>
  <div class="alert alert-info">Payment pending. <a href="<?= e(url('payment.php?order_id=' . $orderId)) ?>">Pay now</a> or choose cash on delivery.</div>
<?php endif; ?>
<?php if ($order['status'] === 'Cancelled' && $order['cancel_reason']): ?>
  <div class="alert alert-error">Cancelled: <?= e($order['cancel_reason']) ?></div>
<?php endif; ?>

<div id="trackRoot" data-api="<?= e(url('api/track.php?order_id=' . $orderId)) ?>" data-state="<?= e(json_encode($snap)) ?>">
  <div class="eta-box" id="etaBox">
    <div><small id="clockLabel">Estimated time to delivery</small><div class="clock" id="clock">--:--</div></div>
    <div class="right"><small>Order total</small><b style="font-size:1.3rem"><?= money($order['order_amount']) ?></b></div>
  </div>
  <div class="two-col">
    <div class="card">
      <h3>Live map</h3>
      <canvas id="map"></canvas>
      <div class="agent-card" id="agentBox" style="display:none">
        <div class="avatar" id="agentAv">A</div>
        <div><b id="agentName"></b><div class="small muted">Delivery agent &middot; <span id="agentPhone"></span></div></div>
      </div>
      <p class="small muted mt">Delivering to (<?= e($order['location_type']) ?>): <?= e($order['delivery_address']) ?></p>
    </div>
    <div>
      <div class="card">
        <h3>Delivery status</h3>
        <ol class="timeline">
          <li id="st-placed"><span class="pt"></span><b>Order placed</b><small id="tm-placed"></small></li>
          <li id="st-confirmed"><span class="pt"></span><b>Confirmed by pharmacy</b><small id="tm-confirmed"></small></li>
          <li id="st-packed"><span class="pt"></span><b>Medicines packed</b><small id="tm-packed"></small></li>
          <li id="st-dispatched"><span class="pt"></span><b>Out for delivery</b><small id="tm-dispatched"></small></li>
          <li id="st-delivered"><span class="pt"></span><b>Delivered</b><small id="tm-delivered"></small></li>
        </ol>
      </div>
      <div class="card mt">
        <h3>Items</h3>
        <?php foreach ($items as $it): ?><div style="display:flex;justify-content:space-between;padding:4px 0"><span><?= e($it['product_name']) ?> x <?= (int)$it['quantity'] ?></span><span><?= money($it['quantity'] * $it['unit_price']) ?></span></div><?php endforeach; ?>
        <?php if ($pay): ?><p class="mt small"><a href="<?= e(url('receipt.php?order_id=' . $orderId)) ?>">View receipt</a></p><?php endif; ?>
      </div>
    </div>
  </div>
</div>
<?php require __DIR__ . '/includes/footer.php'; ?>
