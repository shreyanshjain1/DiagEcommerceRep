<?php
require_once __DIR__.'/../config/db.php';
require_once __DIR__.'/../includes/helpers.php';
require_once __DIR__.'/../includes/settings.php';
require_once __DIR__.'/../config/csrf.php';

require_admin();
ensure_post();
csrf_validate();

$contactEmail = trim((string)($_POST['contact_email'] ?? ''));
$hotline = trim((string)($_POST['contact_hotline'] ?? ''));
$whatsapp = trim((string)($_POST['contact_whatsapp'] ?? ''));

if (!$contactEmail || !$hotline || !$whatsapp) {
  die('Missing fields');
}

$ok = setting_set([
  'contact_email' => $contactEmail,
  'contact_hotline' => $hotline,
  'contact_whatsapp' => $whatsapp,
]);

header('Location: '.url('admin/settings.php?'.($ok?'ok=1':'err=1')));
exit;
