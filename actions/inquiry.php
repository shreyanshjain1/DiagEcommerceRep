<?php
require_once __DIR__.'/../config/db.php';
require_once __DIR__.'/../includes/helpers.php';
require_once __DIR__.'/../config/csrf.php';

ensure_post();
csrf_validate();

$action = $_POST['action'] ?? '';
if ($action !== 'send') { http_response_code(400); die('Bad request'); }

/* Spam honeypot */
if (!empty($_POST['website'])) { die('OK'); }

$name    = trim($_POST['name'] ?? '');
$email   = filter_var($_POST['email'] ?? '', FILTER_VALIDATE_EMAIL);
$phone   = trim($_POST['phone'] ?? '');
$company = trim($_POST['company'] ?? '');
$product_id = (int)($_POST['product_id'] ?? 0);
$subject = trim($_POST['subject'] ?? '');
$message = trim($_POST['message'] ?? '');

if (!$name || !$email || !$subject || !$message) {
  header('Location: '.url('pages/inquiry.php?err='.rawurlencode('Please fill in all required fields.')));
  exit;
}

/* Save to DB */
$ins = $pdo->prepare("INSERT INTO inquiries(created_at,status,company,name,email,phone,subject,message,product_id)
                      VALUES (NOW(),'New',:c,:n,:e,:p,:s,:m,:pid)");
$ins->execute([
  ':c'=>$company ?: null, ':n'=>$name, ':e'=>$email, ':p'=>$phone ?: null, ':s'=>$subject, ':m'=>$message,
  ':pid'=>$product_id > 0 ? $product_id : null,
]);

/* Email admin */
$site = $SITE_NAME ?? 'Pharmastar';
$subj = "Inquiry: {$subject}";
$body = "New Inquiry from {$site}\n\n".
        "Company: ".($company ?: '—')."\n".
        "Name: {$name}\n".
        "Email: {$email}\n".
        "Phone: ".($phone ?: '—')."\n\n".
        "Message:\n{$message}\n\n".
        "----\nSent at: ".date('Y-m-d H:i:s');

$headers = [];
$headers[] = "From: {$site} <no-reply@localhost>";
$headers[] = "Reply-To: {$name} <{$email}>";
$headers[] = "Content-Type: text/plain; charset=UTF-8";

@mail($ADMIN_EMAIL, $subj, $body, implode("\r\n", $headers));

header('Location: '.url('pages/inquiry.php?sent=1'));
exit;
