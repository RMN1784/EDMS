<?php
require_once __DIR__ . '/includes/orders.php';

$ajax = ($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '') === 'fetch';

function cart_reply(bool $ok, string $msg, bool $ajax, string $redirectTo = 'cart.php'): void
{
    if ($ajax) {
        json_out(['ok' => $ok, 'message' => $msg, 'count' => cart_count()], 200);
    }
    flash($ok ? 'ok' : 'error', $msg);
    redirect($redirectTo);
}

if (is_post()) {
    csrf_check();
    if (!is_customer()) {
        if ($ajax) json_out(['ok' => false, 'message' => 'Please log in to add medicines to your cart.', 'login' => url('login.php')]);
        require_customer();
    }
    $action = post('action');
    $pid    = (int)post('product_id', '0');
    $qty    = (int)post('quantity', '1');
    $back   = $_SERVER['HTTP_REFERER'] ?? '';

    if ($action === 'add') {
        $p = q_one('SELECT product_id, product_name, product_stock FROM product WHERE product_id = ? AND is_active = 1', [$pid]);
        if (!$p) cart_reply(false, 'Medicine not found.', $ajax);
        if ($qty < 1 || $qty > 20) cart_reply(false, 'Quantity must be between 1 and 20.', $ajax);
        $inCart = (int)q_val('SELECT quantity FROM cart WHERE customer_id = ? AND product_id = ?', [cid(), $pid]);
        if ($inCart + $qty > $p['product_stock']) {
            cart_reply(false, 'Only ' . $p['product_stock'] . ' unit(s) of ' . $p['product_name'] . ' available' . ($inCart ? ' (you already have ' . $inCart . ' in the cart).' : '.'), $ajax);
        }
        q('INSERT INTO cart (customer_id, product_id, quantity) VALUES (?,?,?)
           ON DUPLICATE KEY UPDATE quantity = quantity + VALUES(quantity)', [cid(), $pid, $qty]);
        cart_reply(true, $p['product_name'] . ' added to cart.', $ajax);
    }

    if ($action === 'update') {
        $p = q_one('SELECT product_stock, product_name FROM product WHERE product_id = ?', [$pid]);
        if ($p && $qty >= 1 && $qty <= 20 && $qty <= $p['product_stock']) {
            q('UPDATE cart SET quantity = ? WHERE customer_id = ? AND product_id = ?', [$qty, cid(), $pid]);
            flash('ok', 'Cart updated.');
        } else {
            flash('error', 'That quantity is not available.');
        }
        redirect('cart.php');
    }

    if ($action === 'remove') {
        q('DELETE FROM cart WHERE customer_id = ? AND product_id = ?', [cid(), $pid]);
        flash('ok', 'Item removed.');
        redirect('cart.php');
    }
    redirect('cart.php');
}

$pageTitle = 'Your cart';
require __DIR__ . '/includes/header.php';

if (!is_customer()): ?>
  <div class="card empty"><h2>Your cart is waiting</h2><p>Log in to add medicines and place an emergency order.</p>
    <a class="btn" href="<?= e(url('login.php')) ?>">Log in</a> <a class="btn btn-ghost" href="<?= e(url('register.php')) ?>">Register</a></div>
<?php require __DIR__ . '/includes/footer.php'; exit; endif;

$items = cart_items(cid());
$subtotal = 0; $needsRx = false;
foreach ($items as $it) { $subtotal += $it['quantity'] * $it['product_price']; if ($it['requires_prescription']) $needsRx = true; }
$fee = $items ? delivery_fee_for($subtotal) : 0;
?>
<h1>Your cart</h1>
<?php if (!$items): ?>
  <div class="card empty"><h3>Your cart is empty</h3><p>Search for the medicines you need.</p><a class="btn" href="<?= e(url('products.php')) ?>">Browse medicines</a></div>
<?php else: ?>
<div class="two-col">
  <div>
    <div class="table-wrap"><table class="t">
      <thead><tr><th>Medicine</th><th>Price</th><th>Quantity</th><th class="right">Total</th><th></th></tr></thead>
      <tbody>
      <?php foreach ($items as $it): $bad = !$it['is_active'] || $it['quantity'] > $it['product_stock']; ?>
        <tr>
          <td><a href="<?= e(url('product.php?id=' . $it['product_id'])) ?>"><b><?= e($it['product_name']) ?></b></a>
            <?php if ($it['requires_prescription']): ?> <span class="rx-tag">Rx</span><?php endif; ?>
            <div class="small muted"><?= e($it['category_name']) ?></div>
            <?php if ($bad): ?><div class="stock-out">Only <?= (int)$it['product_stock'] ?> in stock. Please reduce the quantity.</div><?php endif; ?></td>
          <td><?= money($it['product_price']) ?></td>
          <td><form method="post" class="inline-form"><?= csrf_field() ?><input type="hidden" name="action" value="update"><input type="hidden" name="product_id" value="<?= (int)$it['product_id'] ?>">
              <input class="qty" type="number" name="quantity" value="<?= (int)$it['quantity'] ?>" min="1" max="20"><button class="btn btn-small btn-ghost" type="submit">Update</button></form></td>
          <td class="right"><b><?= money($it['quantity'] * $it['product_price']) ?></b></td>
          <td><form method="post"><?= csrf_field() ?><input type="hidden" name="action" value="remove"><input type="hidden" name="product_id" value="<?= (int)$it['product_id'] ?>"><button class="btn btn-small btn-danger" type="submit">Remove</button></form></td>
        </tr>
      <?php endforeach; ?>
      </tbody></table></div>
    <?php if ($needsRx): ?>
      <div class="alert alert-warn mt">Your cart has prescription medicines (marked Rx). You will upload or select a prescription at checkout. Delivery starts after a pharmacist verifies it.</div>
    <?php endif; ?>
  </div>
  <div class="card">
    <h3>Order summary</h3>
    <div class="totals">
      <div><span>Subtotal</span><span><?= money($subtotal) ?></span></div>
      <div><span>Emergency delivery</span><span><?= $fee > 0 ? money($fee) : 'FREE' ?></span></div>
      <div class="grand"><span>Total</span><span><?= money($subtotal + $fee) ?></span></div>
    </div>
    <?php if ($fee > 0): ?><p class="small muted">Add <?= money(FREE_DELIVERY_ABOVE - $subtotal) ?> more for free delivery.</p><?php endif; ?>
    <a class="btn btn-block" href="<?= e(url('checkout.php')) ?>">Proceed to checkout</a>
    <a class="btn btn-block btn-ghost mt" style="margin-top:10px" href="<?= e(url('products.php')) ?>">Continue shopping</a>
  </div>
</div>
<?php endif; ?>
<?php require __DIR__ . '/includes/footer.php'; ?>
