<?php
require_once __DIR__.'/header.php';
require_once __DIR__.'/../config/csrf.php';
require_once __DIR__.'/../includes/settings.php';

$id = (int)($_GET['id'] ?? 0);
$st = $pdo->prepare("SELECT * FROM quotes WHERE id=:id");
$st->execute([':id'=>$id]);
$q = $st->fetch();
if(!$q){ echo '<div class="alert error">RFQ not found.</div>'; require_once __DIR__.'/footer.php'; exit; }

$items = $pdo->prepare("
  SELECT qi.id AS item_id, qi.qty, qi.unit_price, p.name, p.sku, p.brand
  FROM quote_items qi
  LEFT JOIN products p ON p.id=qi.product_id
  WHERE qi.quote_id=:qid
  ORDER BY qi.id ASC
");
$items->execute([':qid'=>$id]);
$list = $items->fetchAll();

$whatsapp = (string)setting('contact_whatsapp','09453462354');
$msg = "RFQ {$q['quote_number']}\nCustomer: {$q['name']}";
if (!empty($q['company'])) $msg .= "\nCompany: {$q['company']}";
$msg .= "\nEmail: {$q['email']}";
if (!empty($q['phone'])) $msg .= "\nPhone: {$q['phone']}";
$msg .= "\n\nItems:";
foreach($list as $it){
  $nm = $it['name'] ?: 'Unknown product';
  $msg .= "\n- {$it['qty']} x {$nm}";
}

$computedSubtotal = 0.0;
foreach ($list as $it) {
  $computedSubtotal += ((int)$it['qty']) * ((float)$it['unit_price']);
}
$ship  = (float)($q['shipping_fee'] ?? 0);
$over  = (float)($q['overhead_charge'] ?? 0);
$other = (float)($q['other_expenses'] ?? 0);
$inst  = (float)($q['installation_expenses'] ?? 0);
$computedTotal = $computedSubtotal + $ship + $over + $other + $inst;
?>

<style>
  .rfq-layout{display:grid;grid-template-columns:1.15fr .85fr;gap:16px}
  @media(max-width:1024px){.rfq-layout{grid-template-columns:1fr}}

  .rfq-card h3{margin:0}
  .rfq-card .sub{color:var(--admin-muted);margin-top:6px;line-height:1.55}

  /* Unit price inputs should be clearly editable */
.money-input{
  width:100%;
  min-width:170px;
  max-width:260px;
  box-sizing:border-box;
  display:block;
  margin-left:auto;
  text-align:right;
  font-variant-numeric:tabular-nums;
}

  .money-input:focus{
    outline:3px solid rgba(34,197,94,.18);
    border-color:#22c55e;
  }

  .sum-row td{background:#fafafa}
  .sum-row td strong{white-space:nowrap}

  /* Right panel: make it not cramped */
  .admin-top-actions{
    display:grid;
    grid-template-columns:1fr;
    gap:10px;
    margin-top:14px;
  }
  .admin-top-actions .btn{width:100%;justify-content:center}

  .admin-form{margin-top:14px}
  .admin-form .row2{
    display:grid;
    grid-template-columns:1fr 1fr;
    gap:12px;
    margin-top:12px;
  }
  .admin-form .field{
    display:flex;
    flex-direction:column;
    gap:6px;
    min-width:0;
  }
  .admin-form .field label{
    margin:0;
    font-weight:900;
    color:#0f172a;
  }
  .admin-form .field input,
  .admin-form .field select,
  .admin-form textarea{
    width:100%;
    padding:10px 12px;
    border:1px solid var(--admin-border);
    border-radius:12px;
  }
  .admin-form textarea{resize:vertical}

  .admin-form .full{
    margin-top:12px;
    display:flex;
    flex-direction:column;
    gap:6px;
  }
  .admin-form .full label{font-weight:900;color:#0f172a}

  @media(max-width:900px){
    .admin-form .row2{grid-template-columns:1fr}
  }

  .rfq-actions{
    display:flex;
    gap:10px;
    flex-wrap:wrap;
    margin-top:14px;
  }
  .rfq-actions .btn{white-space:nowrap}

  /* Make right panel padding feel better */
  .rfq-card{padding:22px !important}
  @media(max-width:640px){.rfq-card{padding:16px !important}}
</style>

<h1>RFQ – <?php echo e($q['quote_number']); ?></h1>
<?php if(isset($_GET['saved'])): ?><div class="alert success">Saved.</div><?php endif; ?>
<?php if(isset($_GET['sent']) && $_GET['sent']==='1'): ?><div class="alert success">Quotation sent to customer.</div><?php endif; ?>
<?php if(isset($_GET['sent']) && $_GET['sent']==='0'): ?><div class="alert error">Saved but failed to send email (check mail settings).</div><?php endif; ?>

<form class="rfq-layout" action="<?php echo url('admin_actions/rfq_update.php'); ?>" method="post">
  <?php csrf_field(); ?>
  <input type="hidden" name="id" value="<?php echo (int)$q['id']; ?>">

  <div class="card p rfq-card">
    <h3>Customer details</h3>
    <div class="mt16">
      <div><strong>Name:</strong> <?php echo e($q['name']); ?></div>
      <?php if($q['company']): ?><div><strong>Company:</strong> <?php echo e($q['company']); ?></div><?php endif; ?>
      <div><strong>Email:</strong> <?php echo e($q['email']); ?></div>
      <?php if($q['phone']): ?><div><strong>Phone:</strong> <?php echo e($q['phone']); ?></div><?php endif; ?>
      <div class="mt16"><strong>Customer Notes:</strong><br><?php echo nl2br(e($q['notes'] ?: '—')); ?></div>
    </div>

    <h3 class="mt16">Items & Pricing</h3>
    <div class="sub">Enter unit prices (PHP) to compute totals. You can keep this RFQ price-less if you want (leave unit price as 0.00).</div>

    <table class="table mt16">
      <tr>
        <th>Product</th>
        <th style="width:90px">Qty</th>
        <th style="width:210px">Unit (PHP)</th>
        <th style="width:160px">Line</th>
      </tr>

      <?php foreach($list as $it): ?>
        <?php
          $qty = (int)$it['qty'];
          $unit = (float)$it['unit_price'];
          $line = $qty * $unit;
        ?>
        <tr>
          <td>
            <?php echo e($it['name'] ?: 'Unknown product'); ?>
            <div class="muted"><?php echo e($it['brand'] ?: ''); ?><?php if($it['sku']): ?> • <?php echo e($it['sku']); ?><?php endif; ?></div>
          </td>
          <td><?php echo $qty; ?></td>
          <td>
            <input class="money-input" type="number" step="0.01" min="0"
                   name="item_price[<?php echo (int)$it['item_id']; ?>]"
                   value="<?php echo e(number_format($unit,2,'.','')); ?>">
          </td>
          <td>₱<?php echo number_format($line,2); ?></td>
        </tr>
      <?php endforeach; ?>

      <tr class="sum-row">
        <td colspan="3" style="text-align:right"><strong>Subtotal</strong></td>
        <td><strong>₱<?php echo number_format($computedSubtotal,2); ?></strong></td>
      </tr>
      <tr class="sum-row">
        <td colspan="3" style="text-align:right"><strong>Shipping</strong></td>
        <td><input class="money-input" type="number" step="0.01" min="0" name="shipping_fee" value="<?php echo e(number_format($ship,2,'.','')); ?>"></td>
      </tr>
      <tr class="sum-row">
        <td colspan="3" style="text-align:right"><strong>Overhead Charge</strong></td>
        <td><input class="money-input" type="number" step="0.01" min="0" name="overhead_charge" value="<?php echo e(number_format($over,2,'.','')); ?>"></td>
      </tr>
      <tr class="sum-row">
        <td colspan="3" style="text-align:right"><strong>Other Expenses</strong></td>
        <td><input class="money-input" type="number" step="0.01" min="0" name="other_expenses" value="<?php echo e(number_format($other,2,'.','')); ?>"></td>
      </tr>
      <tr class="sum-row">
        <td colspan="3" style="text-align:right"><strong>Installation Expenses</strong></td>
        <td><input class="money-input" type="number" step="0.01" min="0" name="installation_expenses" value="<?php echo e(number_format($inst,2,'.','')); ?>"></td>
      </tr>
      <tr class="sum-row">
        <td colspan="3" style="text-align:right"><strong>Total</strong></td>
        <td><strong>₱<?php echo number_format($computedTotal,2); ?></strong></td>
      </tr>
    </table>
  </div>

  <div class="card p rfq-card">
    <h3>Admin actions</h3>

    <div class="admin-top-actions">
      <a class="btn secondary" target="_blank" rel="noopener" href="<?php echo e(wa_link($whatsapp, $msg)); ?>">WhatsApp follow-up</a>
      <a class="btn secondary" href="mailto:<?php echo e($q['email']); ?>?subject=<?php echo rawurlencode('Your RFQ ' . $q['quote_number']); ?>">Email customer</a>
    </div>

    <div class="admin-form">
      <div class="row2">
        <div class="field">
          <label for="status">Status</label>
          <select id="status" name="status">
            <?php
              $opts = ['draft','submitted','quoted','closed'];
              foreach($opts as $o){
                $sel = ($q['status']===$o) ? 'selected' : '';
                echo '<option value="'.e($o).'" '.$sel.'>'.e(ucfirst($o)).'</option>';
              }
            ?>
          </select>
        </div>

        <div class="field">
          <label for="valid_until">Valid Until</label>
          <input id="valid_until" type="date" name="valid_until" value="<?php echo e($q['valid_until'] ?? ''); ?>">
        </div>
      </div>

      <div class="row2">
        <div class="field">
          <label for="lead_time">Lead Time</label>
          <input id="lead_time" type="text" name="lead_time" placeholder="e.g., 2–4 weeks after PO" value="<?php echo e($q['lead_time'] ?? ''); ?>">
        </div>

        <div class="field">
          <label for="warranty">Warranty</label>
          <input id="warranty" type="text" name="warranty" placeholder="e.g., 1 year parts & service" value="<?php echo e($q['warranty'] ?? ''); ?>">
        </div>
      </div>

      <div class="full">
        <label for="payment_terms">Payment Terms</label>
        <textarea id="payment_terms" name="payment_terms" rows="4" placeholder="e.g., 50% downpayment, 50% upon delivery"><?php echo e($q['payment_terms'] ?? ''); ?></textarea>
      </div>

      <div class="full">
        <label for="admin_notes">Admin Notes (internal)</label>
        <textarea id="admin_notes" name="admin_notes" rows="6" placeholder="Add internal notes, pricing references, follow-up status, etc..."><?php echo e($q['admin_notes'] ?? ''); ?></textarea>
      </div>
    </div>

    <div class="rfq-actions">
      <button class="btn" type="submit">Save</button>
      <button class="btn secondary" formaction="<?php echo url('admin_actions/quote_send.php'); ?>"
              type="submit" onclick="return confirm('Send formal quotation to the customer now?');">
        Send Formal Quotation
      </button>
      <a class="btn ghost" href="<?php echo url('admin/rfqs.php'); ?>">Back</a>
    </div>

    <div class="mt16 muted" style="font-size:12px;line-height:1.55">
      Tip: Set status to <strong>Quoted</strong> once you have sent the formal quotation to the customer.
    </div>
  </div>
</form>

<?php require_once __DIR__.'/footer.php'; ?>
