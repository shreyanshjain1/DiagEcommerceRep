<?php
require_once __DIR__.'/../config/db.php';
require_once __DIR__.'/../includes/helpers.php';
header('Content-Type: application/json; charset=utf-8');

$q = trim($_GET['q'] ?? '');
if(strlen($q) < 2){ echo json_encode([]); exit; }

$sql = "SELECT id, name, brand, sku, price FROM products
        WHERE MATCH(name, sku, brand, short_desc, long_desc) AGAINST(:q IN NATURAL LANGUAGE MODE)
           OR name LIKE :lq OR brand LIKE :lq OR sku LIKE :lq
        ORDER BY is_active DESC, stock DESC, price DESC
        LIMIT 10";
$st = $pdo->prepare($sql);
$st->execute([':q'=>$q, ':lq'=>'%'.$q.'%']);
$out = [];
while($r = $st->fetch()){
  $out[] = [
    'name'=>$r['name'], 'brand'=>$r['brand'], 'sku'=>$r['sku'], 'price'=>$r['price'],
    'url'=> url('pages/product.php?id='.$r['id'])
  ];
}
echo json_encode($out);
