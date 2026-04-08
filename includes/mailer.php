<?php
// Lightweight mail helper using PHP's mail() (no composer required).

require_once __DIR__.'/../config/db.php';
require_once __DIR__.'/helpers.php';

function site_base_url(): string {
  $https = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || (($_SERVER['SERVER_PORT'] ?? '') == 443);
  $scheme = $https ? 'https' : 'http';
  $host = $_SERVER['HTTP_HOST'] ?? '';
  if ($host === '') return '';
  return $scheme . '://' . $host . base_web();
}

function send_mail_basic(string $to, string $subject, string $html, string $fromEmail = ''): bool {
  global $ADMIN_EMAIL;
  $from = $fromEmail ?: $ADMIN_EMAIL;
  $headers = [];
  $headers[] = 'MIME-Version: 1.0';
  $headers[] = 'Content-type: text/html; charset=UTF-8';
  $headers[] = 'From: ' . $from;
  $headers[] = 'Reply-To: ' . $from;
  $headers[] = 'X-Mailer: PHP/' . phpversion();
  return @mail($to, $subject, $html, implode("\r\n", $headers));
}

function admin_emails(PDO $pdo): array {
  try {
    $res = $pdo->query("SELECT email FROM users WHERE role='admin'")->fetchAll();
    $list = [];
    foreach ($res as $r) {
      $e = strtolower(trim((string)($r['email'] ?? '')));
      if ($e && filter_var($e, FILTER_VALIDATE_EMAIL)) $list[] = $e;
    }
    $list = array_values(array_unique($list));
    return $list;
  } catch (Throwable $e) {
    global $ADMIN_EMAIL;
    return [$ADMIN_EMAIL];
  }
}

function notify_admins_rfq(int $quoteId): void {
  global $pdo, $SITE_NAME;

  // Load quote
  $qs = $pdo->prepare("SELECT * FROM quotes WHERE id=:id");
  $qs->execute([':id' => $quoteId]);
  $q = $qs->fetch();
  if (!$q || ($q['status'] ?? '') !== 'submitted') return;

  $its = $pdo->prepare("
    SELECT qi.qty, p.name, p.sku, p.brand
    FROM quote_items qi
    LEFT JOIN products p ON p.id=qi.product_id
    WHERE qi.quote_id=:qid
    ORDER BY qi.id ASC
  ");
  $its->execute([':qid' => $quoteId]);
  $items = $its->fetchAll();

  $base = site_base_url();
  $adminLink = $base ? ($base . '/admin/rfq-view.php?id=' . (int)$quoteId) : '';

  $subject = "[RFQ] {$q['quote_number']} – {$q['name']}";

  $rows = '';
  foreach ($items as $it) {
    $name = e($it['name'] ?: 'Unknown product');
    $qty = (int)$it['qty'];
    $meta = trim(($it['brand'] ?? '') . (!empty($it['sku']) ? ' • ' . $it['sku'] : ''));
    if ($meta) $meta = '<div style="color:#64748b;font-size:12px">' . e($meta) . '</div>';
    $rows .= "<tr><td style='padding:8px;border-bottom:1px solid #e5e7eb'>{$name}{$meta}</td><td style='padding:8px;border-bottom:1px solid #e5e7eb;text-align:center'>{$qty}</td></tr>";
  }

  $notes = nl2br(e($q['notes'] ?: '—'));
  $company = e($q['company'] ?: '');
  $phone = e($q['phone'] ?: '');

  $html = "<!doctype html><html><body style='font-family:Arial,Helvetica,sans-serif;background:#f8fafc;padding:16px'>
  <div style='max-width:780px;margin:0 auto;background:#ffffff;border:1px solid #e5e7eb;border-radius:14px;overflow:hidden'>
    <div style='padding:14px 16px;background:#065f46;color:#fff'>
      <div style='font-size:18px;font-weight:700'>{$SITE_NAME}</div>
      <div style='opacity:.9'>New RFQ submitted: <strong>" . e($q['quote_number']) . "</strong></div>
    </div>
    <div style='padding:16px'>
      <h2 style='margin:0 0 10px 0'>Customer</h2>
      <div style='color:#0f172a'>
        <div><strong>Name:</strong> " . e($q['name']) . "</div>
        " . ($company ? "<div><strong>Company:</strong> {$company}</div>" : '') . "
        <div><strong>Email:</strong> " . e($q['email']) . "</div>
        " . ($phone ? "<div><strong>Phone:</strong> {$phone}</div>" : '') . "
      </div>

      <h2 style='margin:18px 0 10px 0'>Requested Items</h2>
      <table style='width:100%;border-collapse:collapse'>
        <tr>
          <th style='text-align:left;padding:8px;border-bottom:2px solid #e5e7eb'>Product</th>
          <th style='text-align:center;padding:8px;border-bottom:2px solid #e5e7eb'>Qty</th>
        </tr>
        {$rows}
      </table>

      <div style='margin-top:10px;color:#64748b;font-size:12px'>
        Prices are not included in the RFQ. Please prepare the quotation in the admin panel.
      </div>

      <h2 style='margin:18px 0 10px 0'>Notes</h2>
      <div style='color:#0f172a;background:#f1f5f9;border:1px solid #e2e8f0;padding:12px;border-radius:10px'>{$notes}</div>

      " . ($adminLink ? "<div style='margin-top:18px'><a href='" . e($adminLink) . "' style='display:inline-block;background:#22c55e;color:#fff;text-decoration:none;padding:10px 14px;border-radius:999px;font-weight:700'>Open in Admin</a></div>" : '') . "

      <div style='margin-top:18px;color:#64748b;font-size:12px'>This email was sent by the RFQ system.</div>
    </div>
  </div>
  </body></html>";

  $recipients = admin_emails($pdo);
  foreach ($recipients as $to) {
    send_mail_basic($to, $subject, $html);
  }
}


function send_customer_quotation_email(int $quoteId): void {
  global $pdo, $SITE_NAME;

  $qs = $pdo->prepare("SELECT * FROM quotes WHERE id=:id");
  $qs->execute([':id'=>$quoteId]);
  $q = $qs->fetch();
  if(!$q) throw new RuntimeException('Quote not found');

  $its = $pdo->prepare("
    SELECT qi.qty, qi.unit_price, p.name, p.sku, p.brand
    FROM quote_items qi
    LEFT JOIN products p ON p.id=qi.product_id
    WHERE qi.quote_id=:qid
    ORDER BY qi.id ASC
  ");
  $its->execute([':qid'=>$quoteId]);
  $items = $its->fetchAll();

  $base = site_base_url();
  $portalLink = $base ? ($base . '/pages/rfq-view.php?id=' . (int)$quoteId) : '';
  $pdfLink = $base ? ($base . '/pages/quote-pdf.php?id=' . (int)$quoteId) : '';

  $subject = "[Quotation] {$q['quote_number']} – " . ($q['company'] ?: $q['name']);

  $rows = '';
  foreach($items as $it){
    $name = e($it['name'] ?: 'Unknown product');
    $qty = (int)$it['qty'];
    $unit = (float)($it['unit_price'] ?? 0);
    $line = $qty * $unit;
    $meta = trim(($it['brand'] ?? '') . (!empty($it['sku']) ? ' • ' . $it['sku'] : ''));
    if ($meta) $meta = '<div style="color:#64748b;font-size:12px">' . e($meta) . '</div>';
    $rows .= "<tr>
      <td style='padding:10px;border-bottom:1px solid #e5e7eb'>{$name}{$meta}</td>
      <td style='padding:10px;border-bottom:1px solid #e5e7eb;text-align:center'>{$qty}</td>
      <td style='padding:10px;border-bottom:1px solid #e5e7eb;text-align:right'>₱".number_format($unit,2)."</td>
      <td style='padding:10px;border-bottom:1px solid #e5e7eb;text-align:right'>₱".number_format($line,2)."</td>
    </tr>";
  }

  $subtotal = (float)($q['subtotal'] ?? 0);
  $shipping = (float)($q['shipping_fee'] ?? 0);
  $over = (float)($q['overhead_charge'] ?? 0);
  $other = (float)($q['other_expenses'] ?? 0);
  $inst = (float)($q['installation_expenses'] ?? 0);
  $total = (float)($q['total'] ?? ($subtotal + $shipping + $over + $other + $inst));

  $valid = !empty($q['valid_until']) ? e($q['valid_until']) : '—';
  $lead = !empty($q['lead_time']) ? e($q['lead_time']) : '—';
  $warranty = !empty($q['warranty']) ? e($q['warranty']) : '—';
  $pay = !empty($q['payment_terms']) ? nl2br(e($q['payment_terms'])) : '—';

  $to = (string)($q['email'] ?? '');
  if (!filter_var($to, FILTER_VALIDATE_EMAIL)) throw new RuntimeException('Invalid customer email');

  $html = "<!doctype html><html><body style='font-family:Arial,Helvetica,sans-serif;background:#f8fafc;padding:16px'>
  <div style='max-width:860px;margin:0 auto;background:#ffffff;border:1px solid #e5e7eb;border-radius:14px;overflow:hidden'>
    <div style='padding:14px 16px;background:#0f172a;color:#fff'>
      <div style='font-size:18px;font-weight:800'>{$SITE_NAME}</div>
      <div style='opacity:.92'>Official Quotation for RFQ <strong>" . e($q['quote_number']) . "</strong></div>
    </div>

    <div style='padding:16px'>
      <div style='color:#0f172a'>
        <div><strong>Customer:</strong> " . e($q['name']) . "</div>
        " . (!empty($q['company']) ? "<div><strong>Company:</strong> " . e($q['company']) . "</div>" : "") . "
        <div><strong>Email:</strong> " . e($to) . "</div>
      </div>

      <h3 style='margin:18px 0 10px 0'>Items</h3>
      <table style='width:100%;border-collapse:collapse'>
        <tr>
          <th style='text-align:left;padding:10px;border-bottom:2px solid #e5e7eb'>Product</th>
          <th style='text-align:center;padding:10px;border-bottom:2px solid #e5e7eb;width:80px'>Qty</th>
          <th style='text-align:right;padding:10px;border-bottom:2px solid #e5e7eb;width:140px'>Unit</th>
          <th style='text-align:right;padding:10px;border-bottom:2px solid #e5e7eb;width:160px'>Line Total</th>
        </tr>
        {$rows}
      </table>

      <table style='width:100%;border-collapse:collapse;margin-top:14px'>
        <tr><td style='padding:6px 0;text-align:right;color:#334155'>Subtotal</td><td style='padding:6px 0;text-align:right;width:180px'><strong>₱" . number_format($subtotal,2) . "</strong></td></tr>
        <tr><td style='padding:6px 0;text-align:right;color:#334155'>Overhead Charge</td><td style='padding:6px 0;text-align:right'><strong>₱" . number_format($over,2) . "</strong></td></tr>
        <tr><td style='padding:6px 0;text-align:right;color:#334155'>Other Expenses</td><td style='padding:6px 0;text-align:right'><strong>₱" . number_format($other,2) . "</strong></td></tr>
        <tr><td style='padding:6px 0;text-align:right;color:#334155'>Installation Expenses</td><td style='padding:6px 0;text-align:right'><strong>₱" . number_format($inst,2) . "</strong></td></tr>
        <tr><td style='padding:6px 0;text-align:right;color:#334155'>Shipping</td><td style='padding:6px 0;text-align:right'><strong>₱" . number_format($shipping,2) . "</strong></td></tr>
        <tr><td style='padding:10px 0;text-align:right;font-size:16px'>Grand Total</td><td style='padding:10px 0;text-align:right;font-size:16px'><strong>₱" . number_format($total,2) . "</strong></td></tr>
      </table>

      <h3 style='margin:18px 0 10px 0'>Terms</h3>
      <div style='background:#f1f5f9;border:1px solid #e2e8f0;border-radius:10px;padding:12px;color:#0f172a;font-size:13px;line-height:1.6'>
        <div><strong>Valid Until:</strong> {$valid}</div>
        <div><strong>Lead Time:</strong> {$lead}</div>
        <div><strong>Warranty:</strong> {$warranty}</div>
        <div style='margin-top:8px'><strong>Payment Terms:</strong><br>{$pay}</div>
      </div>

      " . ($portalLink ? "<div style='margin-top:16px'>
          <a href='".e($portalLink)."' style='display:inline-block;background:#22c55e;color:#fff;text-decoration:none;padding:10px 14px;border-radius:999px;font-weight:800'>View in Portal</a>
        </div>" : "") . "
      " . ($pdfLink ? "<div style='margin-top:10px;color:#64748b;font-size:12px'>You may also open and print: <a href='".e($pdfLink)."'>Quotation PDF View</a></div>" : "") . "

      <div style='margin-top:18px;color:#64748b;font-size:12px'>Reply to this email for any clarifications. Reference quotation/RFQ #".e($q['quote_number']).".</div>
    </div>
  </div>
  </body></html>";

  send_mail_basic($to, $subject, $html);
}
