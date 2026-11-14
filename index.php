<?php
require_once __DIR__ . '/includes/orders.php';
$pageTitle = 'Medicines delivered in ' . DELIVERY_MINUTES . ' minutes';
require __DIR__ . '/includes/header.php';

$cats = q_all('SELECT c.category_id, c.category_name, COUNT(p.product_id) AS n
                 FROM category c LEFT JOIN product p ON p.category_id = c.category_id AND p.is_active = 1
                GROUP BY c.category_id, c.category_name ORDER BY c.category_id');
$essentials = q_all('SELECT p.*, c.category_name FROM product p JOIN category c ON c.category_id = p.category_id
                      WHERE p.is_active = 1 AND p.product_stock > 0 AND p.product_id IN (1,6,15,30,17,39,52,53)
                      ORDER BY FIELD(p.product_id,1,6,30,15,17,39,52,53)');
?>
<section class="hero">
  <div>
    <h1>Emergency medicines at your door in <em><?= (int)DELIVERY_MINUTES ?> minutes</em></h1>
    <p>Fever at 2 AM, an asthma attack, a heart patient who ran out of tablets. Order from anywhere, track your delivery live and get medicines at home, in hospital or wherever you are.</p>
    <form class="hero-search" action="<?= e(url('products.php')) ?>" method="get">
      <input type="search" name="q" placeholder="Search for a medicine or health product" aria-label="Search medicines" required>
      <button type="submit">Find medicine</button>
    </form>
    <div class="hero-chips">
      <a class="chip" href="<?= e(url('products.php?q=paracetamol')) ?>">Paracetamol</a>
      <a class="chip" href="<?= e(url('products.php?cat=4')) ?>">Asthma</a>
      <a class="chip" href="<?= e(url('products.php?cat=2')) ?>">Cardiac</a>
      <a class="chip" href="<?= e(url('products.php?q=ORS')) ?>">ORS</a>
      <a class="chip" href="<?= e(url('products.php?cat=8')) ?>">First aid</a>
    </div>
  </div>
  <div class="timer-card">
    <div class="timer-ring"><div><span><?= (int)DELIVERY_MINUTES ?></span></div></div>
    <b>Minutes. Door to door.</b>
    <small>Clock starts when your order is confirmed. Prescription medicines start after quick verification.</small>
  </div>
</section>

<div class="section-head"><h2>How it works</h2></div>
<div class="steps">
  <div class="step"><span class="n">1</span><h3>Search and add</h3><p class="muted small">Find medicines by name or category and add them to your cart.</p></div>
  <div class="step"><span class="n">2</span><h3>Upload prescription</h3><p class="muted small">Needed only for regulated medicines. A pharmacist verifies it in minutes.</p></div>
  <div class="step"><span class="n">3</span><h3>Pay securely</h3><p class="muted small">UPI, card, net banking or cash on delivery.</p></div>
  <div class="step"><span class="n">4</span><h3>Track live</h3><p class="muted small">Watch your delivery agent and a countdown to arrival.</p></div>
</div>

<div class="section-head"><h2>Shop by category</h2><a href="<?= e(url('products.php')) ?>">View all medicines</a></div>
<div class="cat-grid">
<?php foreach ($cats as $c): ?>
  <a class="cat-tile" style="--c:<?= e(category_color((int)$c['category_id'])) ?>" href="<?= e(url('products.php?cat=' . $c['category_id'])) ?>">
    <?= e($c['category_name']) ?><small><?= (int)$c['n'] ?> products</small>
  </a>
<?php endforeach; ?>
</div>

<div class="section-head"><h2>Emergency essentials</h2><a href="<?= e(url('products.php')) ?>">See more</a></div>
<div class="grid">
<?php foreach ($essentials as $p): include __DIR__ . '/includes/product_card.php'; endforeach; ?>
</div>
<?php require __DIR__ . '/includes/footer.php'; ?>
