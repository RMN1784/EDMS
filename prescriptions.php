<?php
require_once __DIR__ . '/includes/orders.php';
require_customer();

if (is_post() && post('action') === 'upload') {
    csrf_check();
    $res = store_prescription_upload($_FILES['rx_file'] ?? []);
    if (is_string($res)) {
        flash('error', $res);
    } else {
        q("INSERT INTO prescription (customer_id, file, original_name, status, uploaded_at) VALUES (?,?,?,'Pending',?)", [cid(), $res[0], $res[1], now()]);
        flash('ok', 'Prescription uploaded. A pharmacist will verify it shortly.');
    }
    redirect('prescriptions.php');
}

$pageTitle = 'My prescriptions';
require __DIR__ . '/includes/header.php';
$list = q_all('SELECT * FROM prescription WHERE customer_id = ? ORDER BY prescription_id DESC', [cid()]);
?>
<h1>My prescriptions</h1>
<div class="two-col">
  <div>
    <?php if (!$list): ?><div class="card empty"><h3>No prescriptions uploaded</h3><p>Upload one here or during checkout.</p></div><?php else: ?>
    <div class="table-wrap"><table class="t"><thead><tr><th>#</th><th>File</th><th>Uploaded</th><th>Status</th><th>Pharmacist remarks</th></tr></thead><tbody>
      <?php foreach ($list as $r): ?>
        <tr><td>#<?= (int)$r['prescription_id'] ?></td>
            <td><a href="<?= e(url('file.php?id=' . $r['prescription_id'])) ?>" target="_blank" rel="noopener"><?= e($r['original_name'] ?: $r['file']) ?></a></td>
            <td class="nowrap"><?= e(fmt_dt($r['uploaded_at'])) ?></td><td><?= badge($r['status']) ?></td><td><?= e($r['remarks'] ?: '-') ?></td></tr>
      <?php endforeach; ?></tbody></table></div><?php endif; ?>
  </div>
  <form method="post" enctype="multipart/form-data" class="card">
    <?= csrf_field() ?><input type="hidden" name="action" value="upload">
    <h3>Upload a prescription</h3>
    <div class="field"><label for="f">Photo or PDF</label><input id="f" type="file" name="rx_file" accept=".jpg,.jpeg,.png,.pdf" required><div class="hint">JPG, PNG or PDF. Maximum 5 MB. Make sure the doctor's name, date and medicines are clearly readable.</div></div>
    <button class="btn btn-block" type="submit">Upload</button>
  </form>
</div>
<?php require __DIR__ . '/includes/footer.php'; ?>
