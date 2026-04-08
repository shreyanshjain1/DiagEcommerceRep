<?php
// public_html/ecom/includes/seo_head.php
declare(strict_types=1);

/**
 * Expected variables (optional; can be set per page before including):
 *   $SEO_TITLE
 *   $SEO_DESC
 *   $SEO_CANONICAL  (recommended)
 *   $SEO_OG_IMAGE   (optional)
 *   $SEO_EXTRA_SCHEMA_JSON (optional JSON string for Product schema, etc)
 */

$SITE = $SITE_NAME ?? 'Pharmastar Diagnostics';

// Base URLs
$scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
$host   = $_SERVER['HTTP_HOST'] ?? 'pharmastar.org';
$base   = $scheme . '://' . $host;

// Defaults
$SEO_TITLE = $SEO_TITLE ?? ($SITE . ' | Medical Diagnostics & Laboratory Solutions Philippines');
$SEO_DESC  = $SEO_DESC  ?? ('Pharmastar Diagnostics — a division of Pharmastar Int\'l Trading Corp. Browse diagnostic machines, laboratory analyzers, reagents, and consumables in the Philippines. Request a quotation (RFQ) online.');

// Canonical: strongly recommended to set explicitly per page
if (!isset($SEO_CANONICAL) || trim((string)$SEO_CANONICAL) === '') {
  $SEO_CANONICAL = $base . ($_SERVER['REQUEST_URI'] ?? '/ecom/index.php');
}

// OG image (logo fallback)
if (!isset($SEO_OG_IMAGE) || trim((string)$SEO_OG_IMAGE) === '') {
  // If your logo path differs, change this
  $SEO_OG_IMAGE = $base . '/ecom/assets/img/logo.png';
}

$titleSafe = htmlspecialchars((string)$SEO_TITLE, ENT_QUOTES | ENT_HTML5, 'UTF-8');
$descSafe  = htmlspecialchars((string)$SEO_DESC, ENT_QUOTES | ENT_HTML5, 'UTF-8');
$canonSafe = htmlspecialchars((string)$SEO_CANONICAL, ENT_QUOTES | ENT_HTML5, 'UTF-8');
$ogImgSafe = htmlspecialchars((string)$SEO_OG_IMAGE, ENT_QUOTES | ENT_HTML5, 'UTF-8');

// Public contacts (keep consistent with your settings.php)
$contactHotline = function_exists('setting') ? (string)setting('contact_hotline', '+639691229230') : '+639691229230';
$contactEmail   = function_exists('setting') ? (string)setting('contact_email', ($ADMIN_EMAIL ?? 'info@pharmastar.org')) : ($ADMIN_EMAIL ?? 'info@pharmastar.org');
$contactWhatsApp= function_exists('setting') ? (string)setting('contact_whatsapp', '+639453462354') : '+639453462354';

// Page URL for schema
$pageUrl = (string)$SEO_CANONICAL;

// --- Core meta ---
?>
<title><?php echo $titleSafe; ?></title>
<meta name="description" content="<?php echo $descSafe; ?>">
<link rel="canonical" href="<?php echo $canonSafe; ?>">

<meta name="robots" content="index,follow,max-image-preview:large,max-snippet:-1,max-video-preview:-1">

<!-- Open Graph -->
<meta property="og:type" content="website">
<meta property="og:site_name" content="<?php echo htmlspecialchars((string)$SITE, ENT_QUOTES | ENT_HTML5, 'UTF-8'); ?>">
<meta property="og:title" content="<?php echo $titleSafe; ?>">
<meta property="og:description" content="<?php echo $descSafe; ?>">
<meta property="og:url" content="<?php echo $canonSafe; ?>">
<meta property="og:image" content="<?php echo $ogImgSafe; ?>">

<!-- Twitter -->
<meta name="twitter:card" content="summary_large_image">
<meta name="twitter:title" content="<?php echo $titleSafe; ?>">
<meta name="twitter:description" content="<?php echo $descSafe; ?>">
<meta name="twitter:image" content="<?php echo $ogImgSafe; ?>">

<?php
// --- Schema graph ---
$schema = [
  "@context" => "https://schema.org",
  "@graph" => [
    [
      "@type" => "Organization",
      "@id" => $base . "/#organization",
      "name" => "Pharmastar Diagnostics",
      "url" => $base . "/ecom/index.php",
      "description" => "Pharmastar Diagnostics — a division of Pharmastar Int'l Trading Corp. Platform for browsing diagnostics and submitting RFQ/quotation requests online in the Philippines.",
      "logo" => [
        "@type" => "ImageObject",
        "url" => $SEO_OG_IMAGE
      ],
      "parentOrganization" => [
        "@type" => "Organization",
        "name" => "Pharmastar Int'l Trading Corp",
        "url"  => "https://www.pharmastar.com.ph/"
      ],
      "contactPoint" => [
        [
          "@type" => "ContactPoint",
          "contactType" => "sales",
          "telephone" => $contactHotline,
          "email" => $contactEmail,
          "areaServed" => "PH"
        ]
      ],
      "sameAs" => [
        // Add your FB/LinkedIn links here later if you want
      ]
    ],
    [
      "@type" => "WebSite",
      "@id" => $base . "/#website",
      "url" => $base . "/ecom/index.php",
      "name" => "Pharmastar Diagnostics",
      "publisher" => ["@id" => $base . "/#organization"],
      "inLanguage" => "en",
      "potentialAction" => [
        [
          "@type" => "SearchAction",
          "target" => $base . "/ecom/pages/products.php?q={search_term_string}",
          "query-input" => "required name=search_term_string"
        ]
      ]
    ],
    [
      "@type" => "WebPage",
      "@id" => $pageUrl . "#webpage",
      "url" => $pageUrl,
      "name" => (string)$SEO_TITLE,
      "description" => (string)$SEO_DESC,
      "isPartOf" => ["@id" => $base . "/#website"],
      "about" => ["@id" => $base . "/#organization"]
    ]
  ]
];
?>
<script type="application/ld+json">
<?php
echo json_encode($schema, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
?>
</script>

<?php if (!empty($SEO_EXTRA_SCHEMA_JSON)): ?>
<script type="application/ld+json">
<?php echo $SEO_EXTRA_SCHEMA_JSON; ?>
</script>
<?php endif; ?>
