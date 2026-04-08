<?php
// public_html/ecom/sitemap.php
declare(strict_types=1);

header('Content-Type: application/xml; charset=UTF-8');

$BASE = 'https://pharmastar.org/ecom/'; // keep trailing slash

// Helper: escape XML safely
function x(string $s): string {
  return htmlspecialchars($s, ENT_QUOTES | ENT_XML1, 'UTF-8');
}

// Helper: iso8601 lastmod
function iso_lastmod(int $ts): string {
  if ($ts <= 0) $ts = time();
  return gmdate('Y-m-d\TH:i:s\Z', $ts);
}

// Helper: output a <url> node
function out_url(string $loc, string $lastmod, string $changefreq, string $priority): void {
  echo "  <url>\n";
  echo "    <loc>" . x($loc) . "</loc>\n";
  echo "    <lastmod>" . x($lastmod) . "</lastmod>\n";
  echo "    <changefreq>" . x($changefreq) . "</changefreq>\n";
  echo "    <priority>" . x($priority) . "</priority>\n";
  echo "  </url>\n";
}

// 1) Important “static” pages you want Google to show as sitelinks
$static = [
  'index.php'          => ['changefreq' => 'daily',  'priority' => '1.0'],
  'pages/products.php' => ['changefreq' => 'daily',  'priority' => '0.9'],
  'pages/inquiry.php'  => ['changefreq' => 'weekly', 'priority' => '0.8'],
  'pages/cart.php'     => ['changefreq' => 'weekly', 'priority' => '0.7'],
  'pages/compare.php'  => ['changefreq' => 'weekly', 'priority' => '0.6'],
  'pages/wishlist.php' => ['changefreq' => 'weekly', 'priority' => '0.6'],

  // Add these if you created them
  'pages/about.php'    => ['changefreq' => 'monthly','priority' => '0.6'],
  'pages/contact.php'  => ['changefreq' => 'monthly','priority' => '0.6'],
];

// Output XML header
echo "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n";
echo "<urlset xmlns=\"http://www.sitemaps.org/schemas/sitemap/0.9\">\n";

// Add static pages first
foreach ($static as $path => $meta) {
  $filePath = __DIR__ . '/' . ltrim($path, '/');
  $ts = @filemtime($filePath);
  out_url(
    $BASE . ltrim($path, '/'),
    iso_lastmod((int)($ts ?: time())),
    $meta['changefreq'],
    $meta['priority']
  );
}

// 2) Add dynamic product + category links from DB (safe: sitemap still works if DB fails)
try {
  require_once __DIR__ . '/config/db.php'; // provides $pdo
} catch (Throwable $e) {
  // ignore
}

if (isset($pdo)) {
  // Products (IMPORTANT: correct URL is product.php?id=ID)
  try {
    $stmt = $pdo->query("SELECT id, updated_at, created_at FROM products WHERE is_active=1 ORDER BY id DESC");
    while ($r = $stmt->fetch(PDO::FETCH_ASSOC)) {
      $id = (int)($r['id'] ?? 0);
      if ($id <= 0) continue;

      $ts = 0;
      if (!empty($r['updated_at'])) $ts = (int)strtotime((string)$r['updated_at']);
      if ($ts <= 0 && !empty($r['created_at'])) $ts = (int)strtotime((string)$r['created_at']);
      if ($ts <= 0) $ts = time();

      out_url(
        $BASE . "pages/product.php?id=" . $id,
        iso_lastmod($ts),
        'weekly',
        '0.8'
      );
    }
  } catch (Throwable $e) {
    // ignore
  }

  // Categories -> your site uses products.php?category=ID
  try {
    $stmt = $pdo->query("SELECT id, updated_at, created_at FROM categories ORDER BY id DESC");
    while ($r = $stmt->fetch(PDO::FETCH_ASSOC)) {
      $id = (int)($r['id'] ?? 0);
      if ($id <= 0) continue;

      $ts = 0;
      if (!empty($r['updated_at'])) $ts = (int)strtotime((string)$r['updated_at']);
      if ($ts <= 0 && !empty($r['created_at'])) $ts = (int)strtotime((string)$r['created_at']);
      if ($ts <= 0) $ts = time();

      out_url(
        $BASE . "pages/products.php?category=" . $id,
        iso_lastmod($ts),
        'weekly',
        '0.7'
      );
    }
  } catch (Throwable $e) {
    // ignore
  }

  // Brand pages (optional) -> if you added /pages/brand.php?b=BrandName
  try {
    $stmt = $pdo->query("SELECT DISTINCT brand FROM products WHERE is_active=1 AND brand IS NOT NULL AND brand<>'' ORDER BY brand");
    while ($r = $stmt->fetch(PDO::FETCH_ASSOC)) {
      $brand = trim((string)($r['brand'] ?? ''));
      if ($brand === '') continue;

      out_url(
        $BASE . "pages/brand.php?b=" . rawurlencode($brand),
        iso_lastmod(time()),
        'weekly',
        '0.5'
      );
    }
  } catch (Throwable $e) {
    // ignore
  }
}

echo "</urlset>\n";
