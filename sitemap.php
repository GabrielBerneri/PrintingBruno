<?php
/**
 * PrintingBruno - Sitemap XML dinámico
 * Accesible como /sitemap.xml via rewrite en .htaccess
 */

require_once __DIR__ . '/api/db.php';

header('Content-Type: application/xml; charset=utf-8');
header('X-Robots-Tag: noindex');

$base = 'https://www.printingbruno.com';

$staticPages = [
    ['loc' => $base . '/',              'priority' => '1.0', 'changefreq' => 'weekly'],
    ['loc' => $base . '/catalogo.html', 'priority' => '0.9', 'changefreq' => 'daily'],
    ['loc' => $base . '/nosotros.html', 'priority' => '0.5', 'changefreq' => 'monthly'],
    ['loc' => $base . '/contacto.html', 'priority' => '0.6', 'changefreq' => 'monthly'],
];

$products = [];
try {
    $db = getDB();
    $stmt = $db->query("
        SELECT id, name, updated_at, created_at
        FROM products
        WHERE active = 1
        ORDER BY updated_at DESC
    ");
    $products = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    // Si falla la DB, el sitemap igual sirve con las páginas estáticas
}

function xmlEsc(string $s): string {
    return htmlspecialchars($s, ENT_XML1 | ENT_QUOTES, 'UTF-8');
}

function slugify(string $text): string {
    $text = mb_strtolower($text, 'UTF-8');
    $text = str_replace(['á','é','í','ó','ú','ü','ñ'], ['a','e','i','o','u','u','n'], $text);
    $text = preg_replace('/[^a-z0-9]+/', '-', $text);
    return trim($text, '-');
}

echo '<?xml version="1.0" encoding="UTF-8"?>';
?>
<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">
<?php foreach ($staticPages as $page): ?>
  <url>
    <loc><?= xmlEsc($page['loc']) ?></loc>
    <priority><?= $page['priority'] ?></priority>
    <changefreq><?= $page['changefreq'] ?></changefreq>
  </url>
<?php endforeach; ?>
<?php foreach ($products as $p): ?>
  <url>
    <loc><?= xmlEsc($base . '/producto.html?id=' . (int)$p['id']) ?></loc>
    <lastmod><?= date('Y-m-d', strtotime($p['updated_at'] ?? $p['created_at'] ?? 'now')) ?></lastmod>
    <priority>0.8</priority>
    <changefreq>weekly</changefreq>
  </url>
<?php endforeach; ?>
</urlset>
