<?php
require_once __DIR__.'/../config/db.php';
require_once __DIR__.'/../includes/helpers.php';
header('Content-Type: application/json; charset=utf-8');

$q = trim((string)($_GET['q'] ?? ''));
if (mb_strlen($q) < 2) {
    echo json_encode([]);
    exit;
}

/**
 * Run a LIKE-based fallback query when FULLTEXT is unavailable or unsupported.
 */
function searchSuggestFallback(PDO $pdo, string $q): array
{
    $like = '%'.$q.'%';

    $sql = "SELECT id, name, brand, sku, price,
                   CASE
                     WHEN name = :exact THEN 100
                     WHEN sku = :exact THEN 95
                     WHEN brand = :exact THEN 90
                     WHEN name LIKE :prefix THEN 80
                     WHEN sku LIKE :prefix THEN 75
                     WHEN brand LIKE :prefix THEN 70
                     WHEN name LIKE :like THEN 60
                     WHEN sku LIKE :like THEN 55
                     WHEN brand LIKE :like THEN 50
                     WHEN short_desc LIKE :like THEN 20
                     WHEN long_desc LIKE :like THEN 10
                     ELSE 0
                   END AS relevance
            FROM products
            WHERE is_active = 1
              AND (
                name LIKE :like
                OR brand LIKE :like
                OR sku LIKE :like
                OR short_desc LIKE :like
                OR long_desc LIKE :like
              )
            ORDER BY relevance DESC, stock DESC, price DESC, name ASC
            LIMIT 10";

    $st = $pdo->prepare($sql);
    $st->execute([
        ':exact' => $q,
        ':prefix' => $q.'%',
        ':like' => $like,
    ]);

    return $st->fetchAll(PDO::FETCH_ASSOC) ?: [];
}

try {
    $sql = "SELECT id, name, brand, sku, price,
                   (
                     MATCH(name, sku, brand, short_desc, long_desc) AGAINST(:q IN NATURAL LANGUAGE MODE)
                   ) AS relevance
            FROM products
            WHERE is_active = 1
              AND (
                MATCH(name, sku, brand, short_desc, long_desc) AGAINST(:q IN NATURAL LANGUAGE MODE)
                OR name LIKE :lq
                OR brand LIKE :lq
                OR sku LIKE :lq
              )
            ORDER BY relevance DESC, stock DESC, price DESC, name ASC
            LIMIT 10";

    $st = $pdo->prepare($sql);
    $st->execute([
        ':q' => $q,
        ':lq' => '%'.$q.'%',
    ]);

    $rows = $st->fetchAll(PDO::FETCH_ASSOC) ?: [];
} catch (Throwable $e) {
    $rows = searchSuggestFallback($pdo, $q);
}

$out = [];
foreach ($rows as $r) {
    $out[] = [
        'name' => $r['name'],
        'brand' => $r['brand'],
        'sku' => $r['sku'],
        'price' => $r['price'],
        'url' => url('pages/product.php?id='.$r['id']),
    ];
}

echo json_encode($out);
