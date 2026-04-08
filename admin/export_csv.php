<?php
require_once __DIR__.'/_guard.php';
require_once __DIR__.'/../includes/helpers.php';
require_admin();

$dataset = $_GET['dataset'] ?? 'rfqs';
$allowedDatasets = ['rfqs','quotes','inquiries','users','products','company_accounts'];
if (!in_array($dataset, $allowedDatasets, true)) {
  http_response_code(400);
  exit('Invalid export dataset');
}

[$dateFrom, $dateTo] = admin_export_range();
$status = trim((string)($_GET['status'] ?? ''));
$q = trim((string)($_GET['q'] ?? ''));

$params = [];
$filters = [];

$applyDateRange = function(string $column) use (&$filters, &$params, $dateFrom, $dateTo): void {
  if ($dateFrom !== '') {
    $filters[] = "$column >= :date_from";
    $params[':date_from'] = $dateFrom . ' 00:00:00';
  }
  if ($dateTo !== '') {
    $filters[] = "$column <= :date_to";
    $params[':date_to'] = $dateTo . ' 23:59:59';
  }
};

$exists = function(string $table) use ($pdo): bool {
  $st = $pdo->prepare("SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = ?");
  $st->execute([$table]);
  return (int)$st->fetchColumn() > 0;
};

switch ($dataset) {
  case 'rfqs':
    if ($status !== '') {
      $filters[] = 'q.status = :status';
      $params[':status'] = $status;
    }
    if ($q !== '') {
      $filters[] = '(q.quote_number LIKE :q OR q.name LIKE :q OR q.company LIKE :q OR q.email LIKE :q OR q.phone LIKE :q)';
      $params[':q'] = '%'.$q.'%';
    }
    $applyDateRange('q.created_at');
    $sql = "SELECT q.quote_number,q.status,q.name,q.company,q.email,q.phone,q.subtotal,q.shipping_fee,q.overhead_charge,q.other_expenses,q.installation_expenses,q.total,q.valid_until,q.sent_at,q.created_at,q.updated_at,
                   (SELECT COUNT(*) FROM quote_items qi WHERE qi.quote_id=q.id) AS item_count
            FROM quotes q";
    if ($filters) $sql .= ' WHERE ' . implode(' AND ', $filters);
    $sql .= ' ORDER BY q.created_at DESC';
    $st = $pdo->prepare($sql);
    $st->execute($params);
    $rows = $st->fetchAll(PDO::FETCH_ASSOC);
    export_csv_download('rfqs-export.csv', ['quote_number','status','name','company','email','phone','item_count','subtotal','shipping_fee','overhead_charge','other_expenses','installation_expenses','total','valid_until','sent_at','created_at','updated_at'], $rows);
    break;

  case 'quotes':
    if ($status !== '') {
      $filters[] = 'q.status = :status';
      $params[':status'] = $status;
    }
    if ($q !== '') {
      $filters[] = '(q.quote_number LIKE :q OR p.name LIKE :q OR p.sku LIKE :q OR q.company LIKE :q OR q.email LIKE :q)';
      $params[':q'] = '%'.$q.'%';
    }
    $applyDateRange('q.created_at');
    $sql = "SELECT q.quote_number,q.status,q.company,q.email,q.created_at,
                   COALESCE(p.name,'Removed Product') AS product_name,
                   COALESCE(p.sku,'') AS product_sku,
                   qi.qty,qi.unit_price,(qi.qty * qi.unit_price) AS line_total
            FROM quote_items qi
            INNER JOIN quotes q ON q.id = qi.quote_id
            LEFT JOIN products p ON p.id = qi.product_id";
    if ($filters) $sql .= ' WHERE ' . implode(' AND ', $filters);
    $sql .= ' ORDER BY q.created_at DESC, q.quote_number DESC';
    $st = $pdo->prepare($sql);
    $st->execute($params);
    $rows = $st->fetchAll(PDO::FETCH_ASSOC);
    export_csv_download('quote-line-items-export.csv', ['quote_number','status','company','email','created_at','product_name','product_sku','qty','unit_price','line_total'], $rows);
    break;

  case 'inquiries':
    if ($status !== '') {
      $filters[] = 'i.status = :status';
      $params[':status'] = $status;
    }
    if ($q !== '') {
      $filters[] = '(i.company LIKE :q OR i.name LIKE :q OR i.email LIKE :q OR i.phone LIKE :q OR i.subject LIKE :q)';
      $params[':q'] = '%'.$q.'%';
    }
    $applyDateRange('i.created_at');
    $sql = "SELECT i.id,i.status,i.company,i.name,i.email,i.phone,i.subject,i.message,COALESCE(p.name,'') AS product_name,i.created_at
            FROM inquiries i
            LEFT JOIN products p ON p.id=i.product_id";
    if ($filters) $sql .= ' WHERE ' . implode(' AND ', $filters);
    $sql .= ' ORDER BY i.created_at DESC';
    $st = $pdo->prepare($sql);
    $st->execute($params);
    $rows = $st->fetchAll(PDO::FETCH_ASSOC);
    export_csv_download('inquiries-export.csv', ['id','status','company','name','email','phone','subject','message','product_name','created_at'], $rows);
    break;

  case 'users':
    if ($status !== '') {
      $filters[] = 'u.role = :status';
      $params[':status'] = $status;
    }
    if ($q !== '') {
      $filters[] = '(u.name LIKE :q OR u.email LIKE :q OR u.phone LIKE :q OR u.company LIKE :q)';
      $params[':q'] = '%'.$q.'%';
    }
    $applyDateRange('u.created_at');
    $sql = "SELECT u.id,u.name,u.email,u.phone,u.company,u.vat_tin,u.default_payment_method,u.role,u.created_at
            FROM users u";
    if ($filters) $sql .= ' WHERE ' . implode(' AND ', $filters);
    $sql .= ' ORDER BY u.created_at DESC';
    $st = $pdo->prepare($sql);
    $st->execute($params);
    $rows = $st->fetchAll(PDO::FETCH_ASSOC);
    export_csv_download('users-export.csv', ['id','name','email','phone','company','vat_tin','default_payment_method','role','created_at'], $rows);
    break;

  case 'products':
    if ($status !== '') {
      if (in_array($status, ['1','0'], true)) {
        $filters[] = 'p.is_active = :status_num';
        $params[':status_num'] = (int)$status;
      } else {
        $filters[] = '(p.brand LIKE :status_text OR c.name LIKE :status_text)';
        $params[':status_text'] = '%'.$status.'%';
      }
    }
    if ($q !== '') {
      $filters[] = '(p.name LIKE :q OR p.sku LIKE :q OR p.brand LIKE :q OR c.name LIKE :q)';
      $params[':q'] = '%'.$q.'%';
    }
    $applyDateRange('p.created_at');
    $sql = "SELECT p.id,p.name,p.slug,p.sku,p.brand,c.name AS category,p.price,p.stock,p.is_active,p.created_at,p.updated_at
            FROM products p
            INNER JOIN categories c ON c.id = p.category_id";
    if ($filters) $sql .= ' WHERE ' . implode(' AND ', $filters);
    $sql .= ' ORDER BY p.updated_at DESC';
    $st = $pdo->prepare($sql);
    $st->execute($params);
    $rows = $st->fetchAll(PDO::FETCH_ASSOC);
    export_csv_download('products-export.csv', ['id','name','slug','sku','brand','category','price','stock','is_active','created_at','updated_at'], $rows);
    break;

  case 'company_accounts':
    if (!$exists('company_accounts')) {
      $rows = [[
        'notice' => 'company_accounts table is not present in this snapshot',
        'dataset' => 'company_accounts',
        'created_at' => date('Y-m-d H:i:s')
      ]];
      export_csv_download('company-accounts-export-placeholder.csv', ['notice','dataset','created_at'], $rows);
    }
    if ($status !== '' && $exists('company_accounts')) {
      $filters[] = 'ca.status = :status';
      $params[':status'] = $status;
    }
    if ($q !== '' && $exists('company_accounts')) {
      $filters[] = '(ca.company_name LIKE :q OR ca.account_code LIKE :q OR ca.email LIKE :q OR ca.phone LIKE :q)';
      $params[':q'] = '%'.$q.'%';
    }
    $applyDateRange('ca.created_at');
    $sql = "SELECT ca.id,ca.account_code,ca.company_name,ca.email,ca.phone,ca.status,ca.created_at
            FROM company_accounts ca";
    if ($filters) $sql .= ' WHERE ' . implode(' AND ', $filters);
    $sql .= ' ORDER BY ca.created_at DESC';
    $st = $pdo->prepare($sql);
    $st->execute($params);
    $rows = $st->fetchAll(PDO::FETCH_ASSOC);
    export_csv_download('company-accounts-export.csv', ['id','account_code','company_name','email','phone','status','created_at'], $rows);
    break;
}
