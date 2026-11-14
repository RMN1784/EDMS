<?php
require_once __DIR__ . '/includes/orders.php';
$id = (int)get_str('id', '0');
$p = q_one('SELECT p.*, c.category_name FROM product p JOIN category c ON c.category_id = p.category_id WHERE p.product_id = ? AND p.is_active = 1', [$id]);
if (!$p) { flash('error', 'Medicine not found.'); redirect('products.php'); }

// Feedback submission
if (is_post() && post('action') === 'feedback') {
    csrf_check();
    require_customer();
    $comment = mb_substr(post('comments'), 0, 100);
    $rating  = (int)post('rating', '5');
    if ($comment === '' || $rating < 1 || $rating > 5) {
        flash('error', 'Please write a short comment and choose a rating.');
    } else {
        q('INSERT INTO feedback (customer_id, product_id, comments, rating, created_at) VALUES (?,?,?,?,?)', [cid(), $id, $comment, $rating, now()]);
        flash('ok', 'Thank you for your feedback.');
    }
    redirect('product.php?id=' . $id . '#reviews');
}

$pageTitle = $p['product_name'];
require __DIR__ . '/includes/header.php';

$reviews = q_all('SELECT f.*, c.first_name,
                         EXISTS(SELECT 1 FROM orders o JOIN order_details d ON d.order_id = o.order_id
                                 WHERE o.customer_id = f.customer_id AND d.product_id = f.product_id AND o.status = \'Delivered\') AS verified
                    FROM feedback f JOIN customer c ON c.customer_id = f.customer_id
                   WHERE f.product_id = ? ORDER BY f.feedback_id DESC LIMIT 20', [$id]);
$avg = $reviews ? array_sum(array_column($reviews, 'rating')) / count($reviews) : 0;
$col = category_color((int)$p['category_id']);
?>
<p class="small"><a href="<?= e(url('products.php')) ?>">Medicines</a> / <a href="<?= e(url('products.php?cat=' . $p['category_id'])) ?>"><?= e($p['category_name']) ?></a></p>
<div class="two-col">
  <div class="card">
    <div class="pcard-img" style="--tint:<?= e($col) ?>1a;height:180px;border-radius:12px;margin-bottom:16px"><?= pill_svg($col) ?></div>
    <h1 style="font-size:1.6rem"><?= e($p['product_name']) ?> <?php if ($p['requires_prescription']): ?><span class="rx-tag">Rx</span><?php endif; ?></h1>
    <p class="muted"><?= e($p['description']) ?></p>
    <table class="t" style="margin:10px 0"><tbody>
      <tr><td>Category</td><td><?= e($p['category_name']) ?></td></tr>
      <tr><td>Manufacturer</td><td><?= e($p['manufacturer'] ?: '-') ?></td></tr>
      <tr><td>Expiry date</td><td><?= $p['expiry_date'] ? e(date('M Y', strtotime($p['expiry_date']))) : 'Not applicable' ?></td></tr>
      <tr><td>Prescription</td><td><?= $p['requires_prescription'] ? 'Required. Upload at checkout.' : 'Not required' ?></td></tr>
      <tr><td>Rating</td><td><?= $reviews ? number_format($avg, 1) . ' / 5 (' . count($reviews) . ' reviews)' : 'No reviews yet' ?></td></tr>
    </tbody></table>
    <p class="small muted">Always follow your doctor's advice and the label instructions. This website does not give medical advice.</p>
  </div>
  <div class="card">
    <div class="price" style="font-size:2rem"><?= money($p['product_price']) ?></div>
    <p class="<?= $p['product_stock'] > 10 ? 'stock-ok' : ($p['product_stock'] > 0 ? 'stock-low' : 'stock-out') ?>">
      <?= $p['product_stock'] > 10 ? 'In stock' : ($p['product_stock'] > 0 ? 'Only ' . (int)$p['product_stock'] . ' left' : 'Out of stock') ?></p>
    <form class="js-add-cart" action="<?= e(url('cart.php')) ?>" method="post">
      <?= csrf_field() ?>
      <input type="hidden" name="action" value="add"><input type="hidden" name="product_id" value="<?= (int)$p['product_id'] ?>">
      <div class="field"><label for="q">Quantity</label>
        <input id="q" type="number" name="quantity" value="1" min="1" max="<?= max(1, min(20, (int)$p['product_stock'])) ?>" <?= $p['product_stock'] <= 0 ? 'disabled' : '' ?>></div>
      <button class="btn btn-block" type="submit" <?= $p['product_stock'] <= 0 ? 'disabled' : '' ?>>Add to cart</button>
    </form>
    <p class="small muted mt">Delivery in about <?= (int)DELIVERY_MINUTES ?> minutes. Free delivery above <?= money(FREE_DELIVERY_ABOVE) ?>.</p>
  </div>
</div>

<div class="card mt" id="reviews">
  <h2>Customer feedback</h2>
  <?php foreach ($reviews as $r): ?>
    <div style="border-top:1px solid var(--line);padding:10px 0">
      <b><?= e($r['first_name']) ?></b> <span style="color:#f59e0b"><?= str_repeat('★', (int)$r['rating']) . str_repeat('☆', 5 - (int)$r['rating']) ?></span>
      <?php if ($r['verified']): ?><span class="badge badge-ok">Verified purchase</span><?php endif; ?>
      <div><?= e($r['comments']) ?></div>
      <div class="small muted"><?= e(fmt_dt($r['created_at'])) ?></div>
    </div>
  <?php endforeach; ?>
  <?php if (!$reviews): ?><p class="muted">Be the first to share your experience.</p><?php endif; ?>

  <?php if (is_customer()): ?>
  <form method="post" class="mt" style="border-top:1px solid var(--line);padding-top:16px;max-width:520px">
    <?= csrf_field() ?><input type="hidden" name="action" value="feedback">
    <div class="field"><label for="rating">Rating</label>
      <select id="rating" name="rating"><?php for ($i = 5; $i >= 1; $i--): ?><option value="<?= $i ?>"><?= $i ?> star<?= $i > 1 ? 's' : '' ?></option><?php endfor; ?></select></div>
    <div class="field"><label for="comments">Your comment (max 100 characters)</label><input id="comments" type="text" name="comments" maxlength="100" required></div>
    <button class="btn btn-dark" type="submit">Submit feedback</button>
  </form>
  <?php else: ?><p class="small"><a href="<?= e(url('login.php')) ?>">Log in</a> to leave feedback.</p><?php endif; ?>
</div>
<?php require __DIR__ . '/includes/footer.php'; ?>
