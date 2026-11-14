<?php
require_once __DIR__ . '/includes/orders.php';
$pageTitle = 'Medicines';
require __DIR__ . '/includes/header.php';

$search = get_str('q');
$cat    = (int)get_str('cat', '0');
$sort   = get_str('sort', 'name');
$stock  = get_str('stock') === '1';
$otc    = get_str('otc') === '1';
$page   = max(1, (int)get_str('page', '1'));
$per    = 12;

$where  = ['p.is_active = 1'];
$params = [];
if ($search !== '') {
    $where[]  = '(p.product_name LIKE ? OR p.description LIKE ? OR c.category_name LIKE ?)';
    $like = '%' . str_replace(['%', '_'], ['\\%', '\\_'], $search) . '%';
    array_push($params, $like, $like, $like);
}
if ($cat > 0)  { $where[] = 'p.category_id = ?'; $params[] = $cat; }
if ($stock)    { $where[] = 'p.product_stock > 0'; }
if ($otc)      { $where[] = 'p.requires_prescription = 0'; }
$orderBy = ['name' => 'p.product_name ASC', 'low' => 'p.product_price ASC', 'high' => 'p.product_price DESC'][$sort] ?? 'p.product_name ASC';
$whereSql = implode(' AND ', $where);

$total  = (int)q_val("SELECT COUNT(*) FROM product p JOIN category c ON c.category_id = p.category_id WHERE $whereSql", $params);
$pages  = max(1, (int)ceil($total / $per));
$page   = min($page, $pages);
$offset = ($page - 1) * $per;
$rows   = q_all("SELECT p.*, c.category_name FROM product p JOIN category c ON c.category_id = p.category_id
                  WHERE $whereSql ORDER BY $orderBy LIMIT $per OFFSET $offset", $params);
$cats   = q_all('SELECT c.category_id, c.category_name, COUNT(p.product_id) n FROM category c
                   LEFT JOIN product p ON p.category_id = c.category_id AND p.is_active = 1
                  GROUP BY c.category_id, c.category_name ORDER BY c.category_id');

function qs(array $over = []): string {
    $q = array_merge($_GET, $over);
    foreach ($q as $k => $v) if ($v === '' || $v === null || $v === '0') unset($q[$k]);
    return $q ? '?' . http_build_query($q) : '';
}
?>
<div class="layout-2">
  <aside class="filters card card-flat">
    <h3>Categories</h3>
    <a class="cat-link <?= $cat === 0 ? 'on' : '' ?>" href="<?= e(url('products.php' . qs(['cat' => '', 'page' => '']))) ?>"><span>All medicines</span></a>
    <?php foreach ($cats as $c): ?>
      <a class="cat-link <?= $cat === (int)$c['category_id'] ? 'on' : '' ?>" href="<?= e(url('products.php' . qs(['cat' => $c['category_id'], 'page' => '']))) ?>">
        <span><?= e($c['category_name']) ?></span><span class="muted"><?= (int)$c['n'] ?></span></a>
    <?php endforeach; ?>
    <hr style="border:0;border-top:1px solid var(--line);margin:14px 0">
    <form method="get">
      <?php if ($search !== ''): ?><input type="hidden" name="q" value="<?= e($search) ?>"><?php endif; ?>
      <?php if ($cat): ?><input type="hidden" name="cat" value="<?= $cat ?>"><?php endif; ?>
      <div class="field"><label for="sort">Sort by</label>
        <select id="sort" name="sort" onchange="this.form.submit()">
          <option value="name" <?= $sort === 'name' ? 'selected' : '' ?>>Name A to Z</option>
          <option value="low" <?= $sort === 'low' ? 'selected' : '' ?>>Price: low to high</option>
          <option value="high" <?= $sort === 'high' ? 'selected' : '' ?>>Price: high to low</option>
        </select></div>
      <label class="check"><input type="checkbox" name="stock" value="1" <?= $stock ? 'checked' : '' ?> onchange="this.form.submit()"> In stock only</label>
      <label class="check"><input type="checkbox" name="otc" value="1" <?= $otc ? 'checked' : '' ?> onchange="this.form.submit()"> No prescription needed</label>
    </form>
  </aside>

  <section>
    <div class="section-head" style="margin-top:0">
      <h2><?= $search !== '' ? 'Results for "' . e($search) . '"' : 'All medicines' ?></h2>
      <span class="muted"><?= $total ?> product<?= $total === 1 ? '' : 's' ?></span>
    </div>
    <?php if (!$rows): ?>
      <div class="card empty"><h3>No medicines found</h3><p>Try a different spelling or clear the filters.</p><a class="btn" href="<?= e(url('products.php')) ?>">Show all</a></div>
    <?php else: ?>
      <div class="grid"><?php foreach ($rows as $p): include __DIR__ . '/includes/product_card.php'; endforeach; ?></div>
      <?php if ($pages > 1): ?>
      <nav class="pager" aria-label="Pages">
        <?php for ($i = 1; $i <= $pages; $i++): ?>
          <?php if ($i === $page): ?><span class="cur"><?= $i ?></span>
          <?php else: ?><a href="<?= e(url('products.php' . qs(['page' => $i]))) ?>"><?= $i ?></a><?php endif; ?>
        <?php endfor; ?>
      </nav>
      <?php endif; ?>
    <?php endif; ?>
  </section>
</div>
<?php require __DIR__ . '/includes/footer.php'; ?>
