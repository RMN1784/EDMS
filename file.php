<?php
/**
 * Serves a prescription file only to its owner or an administrator.
 * Files live in /uploads (blocked from direct web access) so privacy is enforced here.
 */
require_once __DIR__ . '/includes/functions.php';

$id = (int)get_str('id', '0');
$rx = q_one('SELECT * FROM prescription WHERE prescription_id = ?', [$id]);
if (!$rx || !(is_admin() || (is_customer() && (int)$rx['customer_id'] === cid()))) {
    http_response_code(403);
    exit('Access denied.');
}
$path = __DIR__ . '/uploads/prescriptions/' . basename($rx['file']);
if (!is_file($path)) { http_response_code(404); exit('File not found.'); }

$ext  = strtolower(pathinfo($path, PATHINFO_EXTENSION));
$mime = ['jpg' => 'image/jpeg', 'jpeg' => 'image/jpeg', 'png' => 'image/png', 'pdf' => 'application/pdf'][$ext] ?? 'application/octet-stream';
header('Content-Type: ' . $mime);
header('Content-Length: ' . filesize($path));
header('X-Content-Type-Options: nosniff');
header('Content-Disposition: inline; filename="prescription-' . $id . '.' . $ext . '"');
header('Cache-Control: private, no-store');
readfile($path);
