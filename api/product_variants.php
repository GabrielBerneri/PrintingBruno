<?php
/**
 * Product variant helpers.
 */

function pbHasTable(PDO $db, string $table): bool {
    static $cache = [];

    $key = strtolower($table);
    if (array_key_exists($key, $cache)) {
        return $cache[$key];
    }

    try {
        $stmt = $db->prepare('SELECT COUNT(*) FROM information_schema.TABLES WHERE TABLE_SCHEMA = ? AND TABLE_NAME = ?');
        $stmt->execute([DB_NAME, $table]);
        return $cache[$key] = (bool)$stmt->fetchColumn();
    } catch (Throwable $e) {
        return $cache[$key] = false;
    }
}

function pbHasColumn(PDO $db, string $table, string $column): bool {
    static $cache = [];

    $key = strtolower($table . '.' . $column);
    if (array_key_exists($key, $cache)) {
        return $cache[$key];
    }

    try {
        $stmt = $db->prepare('SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = ? AND TABLE_NAME = ? AND COLUMN_NAME = ?');
        $stmt->execute([DB_NAME, $table, $column]);
        return $cache[$key] = (bool)$stmt->fetchColumn();
    } catch (Throwable $e) {
        return $cache[$key] = false;
    }
}

function pbHasProductVariantsTable(PDO $db): bool {
    return pbHasTable($db, 'product_variants');
}

function pbHasProductColorsTable(PDO $db): bool {
    return pbHasTable($db, 'product_colors');
}

function pbLegacyColorPalette(): array {
    return [
        'rojo' => '#d83b3b',
        'blanco' => '#f4f4f1',
        'negro' => '#1a1a1a',
        'gris' => '#8c8f96',
        'azul' => '#2f63d8',
        'verde' => '#2f9d63',
        'dorado' => '#c9a227',
        'celeste' => '#6bbef0',
        'rosa' => '#ef7ca8',
    ];
}

function pbLegacyColorHex(?string $name): ?string {
    $normalized = strtolower(trim((string)$name));
    return pbLegacyColorPalette()[$normalized] ?? null;
}

function pbNormalizeColorHex($value): ?string {
    $value = strtolower(trim((string)$value));
    if ($value === '') {
        return null;
    }

    if ($value[0] !== '#') {
        $value = '#' . $value;
    }

    return preg_match('/^#[0-9a-f]{6}$/', $value) === 1 ? $value : null;
}

function pbColorSlug(string $value): string {
    $slug = strtolower(trim($value));
    $slug = preg_replace('/[^a-z0-9]+/', '-', $slug);
    return trim((string)$slug, '-');
}

function pbListProductColors(PDO $db, bool $includeInactive = false): array {
    if (!pbHasProductColorsTable($db)) {
        return [];
    }

    $sql = 'SELECT * FROM product_colors';
    if (!$includeInactive) {
        $sql .= ' WHERE active = 1';
    }
    $sql .= ' ORDER BY active DESC, sort_order ASC, name ASC';

    $stmt = $db->query($sql);
    $colors = [];
    foreach ($stmt->fetchAll() as $color) {
        $color['id'] = (int)$color['id'];
        $color['active'] = (int)$color['active'];
        $color['sort_order'] = (int)$color['sort_order'];
        $color['hex_primary'] = pbNormalizeColorHex($color['hex_primary']) ?? '#ffffff';
        $color['hex_secondary'] = pbNormalizeColorHex($color['hex_secondary'] ?? null);
        $colors[] = $color;
    }

    return $colors;
}

function pbProductColorMapByIds(PDO $db, array $ids): array {
    if (!pbHasProductColorsTable($db)) {
        return [];
    }

    $ids = array_values(array_unique(array_filter(array_map('intval', $ids), static function ($id) {
        return $id > 0;
    })));
    if (empty($ids)) {
        return [];
    }

    $placeholders = implode(',', array_fill(0, count($ids), '?'));
    $stmt = $db->prepare("SELECT * FROM product_colors WHERE id IN ($placeholders)");
    $stmt->execute($ids);

    $mapped = [];
    foreach ($stmt->fetchAll() as $color) {
        $color['id'] = (int)$color['id'];
        $color['active'] = (int)$color['active'];
        $color['sort_order'] = (int)$color['sort_order'];
        $color['hex_primary'] = pbNormalizeColorHex($color['hex_primary']) ?? '#ffffff';
        $color['hex_secondary'] = pbNormalizeColorHex($color['hex_secondary'] ?? null);
        $mapped[(int)$color['id']] = $color;
    }

    return $mapped;
}

function pbResolveVariantColor(PDO $db, ?int $colorId, ?string $legacyName, ?string $legacyHex = null): array {
    $resolved = [
        'id' => $colorId ?: null,
        'name' => trim((string)$legacyName),
        'hex_primary' => pbNormalizeColorHex($legacyHex) ?? pbLegacyColorHex($legacyName),
        'hex_secondary' => null,
        'active' => 1,
    ];

    if (!$colorId || !pbHasProductColorsTable($db)) {
        return $resolved;
    }

    $colorMap = pbProductColorMapByIds($db, [$colorId]);
    if (!isset($colorMap[$colorId])) {
        throw new InvalidArgumentException('El color seleccionado no existe.');
    }

    $color = $colorMap[$colorId];
    if ((int)$color['active'] !== 1) {
        throw new InvalidArgumentException('No podés usar un color inactivo en una variante.');
    }

    return [
        'id' => (int)$color['id'],
        'name' => trim((string)$color['name']),
        'hex_primary' => $color['hex_primary'],
        'hex_secondary' => $color['hex_secondary'],
        'active' => (int)$color['active'],
    ];
}

function pbNormalizeImageUrls($value, int $max = 10): array {
    $urls = [];

    if (is_array($value)) {
        $urls = $value;
    } elseif (is_string($value)) {
        $trimmed = trim($value);
        if ($trimmed !== '') {
            if ($trimmed[0] === '[') {
                $decoded = json_decode($trimmed, true);
                if (is_array($decoded)) {
                    $urls = $decoded;
                } else {
                    $urls = [$trimmed];
                }
            } elseif (strpos($trimmed, ',') !== false) {
                $urls = array_map('trim', explode(',', $trimmed));
            } else {
                $urls = preg_split('/\r\n|\r|\n/', $trimmed) ?: [$trimmed];
            }
        }
    }

    $urls = array_values(array_filter(array_map(static function ($url) {
        return is_string($url) ? trim($url) : '';
    }, $urls), static function ($url) {
        return $url !== '';
    }));

    $urls = array_values(array_unique($urls));
    if (count($urls) > $max) {
        $urls = array_slice($urls, 0, $max);
    }

    return $urls;
}

function pbDecodeImageUrls(?string $imageUrl, $imageUrlsColumn): array {
    $urls = [];

    if (is_string($imageUrlsColumn) && trim($imageUrlsColumn) !== '') {
        $decoded = json_decode($imageUrlsColumn, true);
        if (is_array($decoded)) {
            $urls = pbNormalizeImageUrls($decoded);
        }
    }

    if (empty($urls) && is_string($imageUrl) && trim($imageUrl) !== '') {
        $urls = pbNormalizeImageUrls($imageUrl);
    }

    return $urls;
}

function pbBuildVariantLabel(?string $primaryColor, ?string $secondaryColor, ?string $explicitLabel = null, ?string $fallback = null): string {
    $explicitLabel = trim((string)$explicitLabel);
    if ($explicitLabel !== '') {
        return $explicitLabel;
    }

    $primaryColor = trim((string)$primaryColor);
    $secondaryColor = trim((string)$secondaryColor);

    if ($primaryColor !== '' && $secondaryColor !== '') {
        return $primaryColor . ' + ' . $secondaryColor;
    }
    if ($primaryColor !== '') {
        return $primaryColor;
    }
    if ($secondaryColor !== '') {
        return $secondaryColor;
    }

    $fallback = trim((string)$fallback);
    return $fallback !== '' ? $fallback : 'Base';
}

function pbBuildFallbackVariant(array $productFallback = []): array {
    $imageUrls = pbNormalizeImageUrls($productFallback['image_urls'] ?? ($productFallback['image_url'] ?? []));
    $stock = max(0, (int)($productFallback['stock'] ?? 0));

    return [
        'id' => isset($productFallback['variant_id']) ? (int)$productFallback['variant_id'] : null,
        'label' => pbBuildVariantLabel(
            $productFallback['primary_color'] ?? null,
            $productFallback['secondary_color'] ?? null,
            $productFallback['label'] ?? null,
            'Base'
        ),
        'primary_color_id' => isset($productFallback['primary_color_id']) ? (int)$productFallback['primary_color_id'] : null,
        'secondary_color_id' => isset($productFallback['secondary_color_id']) ? (int)$productFallback['secondary_color_id'] : null,
        'primary_color' => trim((string)($productFallback['primary_color'] ?? '')),
        'secondary_color' => trim((string)($productFallback['secondary_color'] ?? '')),
        'primary_color_name' => trim((string)($productFallback['primary_color_name'] ?? ($productFallback['primary_color'] ?? ''))),
        'secondary_color_name' => trim((string)($productFallback['secondary_color_name'] ?? ($productFallback['secondary_color'] ?? ''))),
        'primary_color_hex' => pbNormalizeColorHex($productFallback['primary_color_hex'] ?? null) ?? pbLegacyColorHex($productFallback['primary_color'] ?? null),
        'secondary_color_hex' => pbNormalizeColorHex($productFallback['secondary_color_hex'] ?? null) ?? pbLegacyColorHex($productFallback['secondary_color'] ?? null),
        'primary_color_hex_secondary' => pbNormalizeColorHex($productFallback['primary_color_hex_secondary'] ?? null),
        'secondary_color_hex_secondary' => pbNormalizeColorHex($productFallback['secondary_color_hex_secondary'] ?? null),
        'sku' => trim((string)($productFallback['sku'] ?? '')),
        'price' => array_key_exists('price', $productFallback) && $productFallback['price'] !== '' && $productFallback['price'] !== null
            ? (float)$productFallback['price']
            : null,
        'stock' => $stock,
        'active' => array_key_exists('active', $productFallback) ? (int)$productFallback['active'] : 1,
        'sort_order' => max(0, (int)($productFallback['sort_order'] ?? 0)),
        'image_urls' => $imageUrls,
        'image_url' => $imageUrls[0] ?? '',
    ];
}

function pbNormalizeVariantsInput($value, array $productFallback = []): array {
    $variants = [];

    if (is_array($value)) {
        foreach (array_values($value) as $index => $rawVariant) {
            if (!is_array($rawVariant)) {
                continue;
            }

            $imageUrls = pbNormalizeImageUrls($rawVariant['image_urls'] ?? ($rawVariant['image_url'] ?? []));
            $price = $rawVariant['price'] ?? null;
            if ($price === '' || $price === null) {
                $price = null;
            } else {
                $price = (float)$price;
            }

            $primaryColorId   = isset($rawVariant['primary_color_id']) && $rawVariant['primary_color_id'] !== '' && $rawVariant['primary_color_id'] !== null
                ? (int)$rawVariant['primary_color_id'] : null;
            $secondaryColorId = isset($rawVariant['secondary_color_id']) && $rawVariant['secondary_color_id'] !== '' && $rawVariant['secondary_color_id'] !== null
                ? (int)$rawVariant['secondary_color_id'] : null;

            $variants[] = [
                'id'                 => isset($rawVariant['id']) && $rawVariant['id'] !== '' ? (int)$rawVariant['id'] : null,
                'label'              => pbBuildVariantLabel(
                    $rawVariant['primary_color'] ?? null,
                    $rawVariant['secondary_color'] ?? null,
                    $rawVariant['label'] ?? null,
                    'Variante ' . ($index + 1)
                ),
                'primary_color'      => trim((string)($rawVariant['primary_color'] ?? '')),
                'secondary_color'    => trim((string)($rawVariant['secondary_color'] ?? '')),
                'primary_color_name' => trim((string)($rawVariant['primary_color_name'] ?? ($rawVariant['primary_color'] ?? ''))),
                'secondary_color_name' => trim((string)($rawVariant['secondary_color_name'] ?? ($rawVariant['secondary_color'] ?? ''))),
                'primary_color_id'   => $primaryColorId,
                'secondary_color_id' => $secondaryColorId,
                'primary_color_hex'  => pbNormalizeColorHex($rawVariant['primary_color_hex'] ?? null) ?? pbLegacyColorHex($rawVariant['primary_color'] ?? null),
                'secondary_color_hex' => pbNormalizeColorHex($rawVariant['secondary_color_hex'] ?? null) ?? pbLegacyColorHex($rawVariant['secondary_color'] ?? null),
                'primary_color_hex_secondary' => pbNormalizeColorHex($rawVariant['primary_color_hex_secondary'] ?? null),
                'secondary_color_hex_secondary' => pbNormalizeColorHex($rawVariant['secondary_color_hex_secondary'] ?? null),
                'sku'                => trim((string)($rawVariant['sku'] ?? '')),
                'price'              => $price,
                'stock'              => max(0, (int)($rawVariant['stock'] ?? 0)),
                'active'             => array_key_exists('active', $rawVariant) ? (int)((bool)$rawVariant['active']) : 1,
                'sort_order'         => max(0, (int)($rawVariant['sort_order'] ?? $index)),
                'image_urls'         => $imageUrls,
                'image_url'          => $imageUrls[0] ?? '',
            ];
        }
    }

    if (empty($variants)) {
        $variants[] = pbBuildFallbackVariant($productFallback);
    }

    return array_values($variants);
}

function pbGetReservedVariantQuantities(PDO $db, array $variantIds, ?int $excludeOrderId = null): array {
    $variantIds = array_values(array_unique(array_filter(array_map('intval', $variantIds), static function ($id) {
        return $id > 0;
    })));

    if (empty($variantIds) || !pbHasColumn($db, 'stock_reservations', 'variant_id')) {
        return [];
    }

    $placeholders = implode(',', array_fill(0, count($variantIds), '?'));
    $sql = "
        SELECT variant_id, COALESCE(SUM(quantity), 0) AS reserved_qty
        FROM stock_reservations
        WHERE status = ?
          AND variant_id IN ($placeholders)
    ";
    $params = array_merge([PB_RESERVATION_ACTIVE], $variantIds);
    if ($excludeOrderId !== null) {
        $sql .= " AND order_id != ?";
        $params[] = $excludeOrderId;
    }
    $sql .= " GROUP BY variant_id";

    $stmt = $db->prepare($sql);
    $stmt->execute($params);

    $reserved = [];
    foreach ($stmt->fetchAll() as $row) {
        $reserved[(int)$row['variant_id']] = (int)$row['reserved_qty'];
    }

    return $reserved;
}

function pbFetchProductVariantsByProductIds(PDO $db, array $productIds, bool $includeInactive = false, ?int $excludeOrderId = null): array {
    if (!pbHasProductVariantsTable($db)) {
        return [];
    }

    $productIds = array_values(array_unique(array_filter(array_map('intval', $productIds), static function ($id) {
        return $id > 0;
    })));

    if (empty($productIds)) {
        return [];
    }

    $placeholders = implode(',', array_fill(0, count($productIds), '?'));

    $sql = "
        SELECT *
        FROM product_variants
        WHERE product_id IN ($placeholders)";
    if (!$includeInactive) {
        $sql .= ' AND active = 1';
    }
    $sql .= ' ORDER BY sort_order ASC, id ASC';

    $stmt = $db->prepare($sql);
    $stmt->execute($productIds);
    $variants = $stmt->fetchAll();
    if (empty($variants)) {
        return [];
    }

    $reservedByVariant = pbGetReservedVariantQuantities($db, array_column($variants, 'id'), $excludeOrderId);
    $colorIds = [];
    foreach ($variants as $variant) {
        if (!empty($variant['primary_color_id'])) {
            $colorIds[] = (int)$variant['primary_color_id'];
        }
        if (!empty($variant['secondary_color_id'])) {
            $colorIds[] = (int)$variant['secondary_color_id'];
        }
    }
    $colorMap = pbProductColorMapByIds($db, $colorIds);

    $colorMapByName = [];
    if (pbHasProductColorsTable($db)) {
        foreach (pbListProductColors($db) as $c) {
            $colorMapByName[strtolower(trim((string)($c['name'] ?? '')))] = $c;
        }
    }

    $mapped = [];

    foreach ($variants as $variant) {
        $variantId = (int)$variant['id'];
        $productId = (int)$variant['product_id'];
        $stock     = (int)$variant['stock'];
        $available = max(0, $stock - (int)($reservedByVariant[$variantId] ?? 0));
        $imageUrls = pbDecodeImageUrls($variant['image_url'] ?? '', $variant['image_urls'] ?? null);

        $primaryColorId = isset($variant['primary_color_id']) && $variant['primary_color_id'] !== null ? (int)$variant['primary_color_id'] : null;
        $secondaryColorId = isset($variant['secondary_color_id']) && $variant['secondary_color_id'] !== null ? (int)$variant['secondary_color_id'] : null;
        $primaryRecord = ($primaryColorId !== null && isset($colorMap[$primaryColorId]))
            ? $colorMap[$primaryColorId]
            : ($colorMapByName[strtolower(trim((string)($variant['primary_color'] ?? '')))] ?? null);
        $secondaryRecord = ($secondaryColorId !== null && isset($colorMap[$secondaryColorId]))
            ? $colorMap[$secondaryColorId]
            : ($colorMapByName[strtolower(trim((string)($variant['secondary_color'] ?? '')))] ?? null);
        $primaryColorName = trim((string)($primaryRecord['name'] ?? ($variant['primary_color'] ?? '')));
        $secondaryColorName = trim((string)($secondaryRecord['name'] ?? ($variant['secondary_color'] ?? '')));

        $normalized = [
            'id'                   => $variantId,
            'product_id'           => $productId,
            'label'                => pbBuildVariantLabel(
                $primaryColorName,
                $secondaryColorName,
                $variant['label'] ?? null,
                'Base'
            ),
            'primary_color'        => $primaryColorName,
            'secondary_color'      => $secondaryColorName,
            'primary_color_name'   => $primaryColorName,
            'secondary_color_name' => $secondaryColorName,
            'primary_color_id'     => $primaryColorId,
            'secondary_color_id'   => $secondaryColorId,
            'primary_color_hex'    => $primaryRecord['hex_primary'] ?? pbLegacyColorHex($variant['primary_color'] ?? null),
            'secondary_color_hex'  => $secondaryRecord['hex_primary'] ?? pbLegacyColorHex($variant['secondary_color'] ?? null),
            'primary_color_hex_secondary' => $primaryRecord['hex_secondary'] ?? null,
            'secondary_color_hex_secondary' => $secondaryRecord['hex_secondary'] ?? null,
            'sku'                  => trim((string)($variant['sku'] ?? '')),
            'price'                => $variant['price'] !== null ? (float)$variant['price'] : null,
            'stock'                => $stock,
            'available_stock'      => $available,
            'active'               => (int)($variant['active'] ?? 1),
            'sort_order'           => (int)($variant['sort_order'] ?? 0),
            'image_url'            => $imageUrls[0] ?? '',
            'image_urls'           => $imageUrls,
        ];

        $mapped[$productId][] = $normalized;
    }

    return $mapped;
}

function pbAttachVariantsToProducts(PDO $db, array &$products, bool $includeInactive = false, ?int $excludeOrderId = null): void {
    if (empty($products) || !pbHasProductVariantsTable($db)) {
        foreach ($products as &$product) {
            $product['variants'] = [];
            $product['variant_count'] = 0;
            $product['default_variant_id'] = null;
            $product['price_from'] = (float)($product['price'] ?? 0);
            $product['price_to'] = (float)($product['price'] ?? 0);
        }
        unset($product);
        return;
    }

    $variantsByProduct = pbFetchProductVariantsByProductIds($db, array_column($products, 'id'), $includeInactive, $excludeOrderId);

    foreach ($products as &$product) {
        $productId = (int)($product['id'] ?? 0);
        $variants = $variantsByProduct[$productId] ?? [];
        $activeVariants = array_values(array_filter($variants, static function (array $variant) {
            return (int)($variant['active'] ?? 0) === 1;
        }));
        $defaultVariant = $activeVariants[0] ?? ($variants[0] ?? null);

        $pricePoints = [];
        foreach ($variants as &$variant) {
            $variant['final_price'] = $variant['price'] !== null
                ? (float)$variant['price']
                : (float)($product['price'] ?? 0);
            $pricePoints[] = $variant['final_price'];
        }
        unset($variant);

        if (!empty($activeVariants)) {
            $product['available_stock'] = array_sum(array_map(static function (array $variant) {
                return (int)($variant['available_stock'] ?? 0);
            }, $activeVariants));
        } else {
            $product['available_stock'] = max(0, (int)($product['available_stock'] ?? $product['stock'] ?? 0));
        }

        if ((empty($product['image_urls']) || !is_array($product['image_urls'])) && $defaultVariant && !empty($defaultVariant['image_urls'])) {
            $product['image_urls'] = $defaultVariant['image_urls'];
            $product['image_url'] = $defaultVariant['image_url'];
        }

        $product['variants'] = $includeInactive ? $variants : $activeVariants;
        $product['variant_count'] = count($product['variants']);
        $product['default_variant_id'] = $defaultVariant['id'] ?? null;
        $product['price_from'] = !empty($pricePoints) ? (float)min($pricePoints) : (float)($product['price'] ?? 0);
        $product['price_to'] = !empty($pricePoints) ? (float)max($pricePoints) : (float)($product['price'] ?? 0);
    }
    unset($product);
}

function pbSyncProductStock(PDO $db, int $productId): void {
    if ($productId <= 0 || !pbHasProductVariantsTable($db)) {
        return;
    }

    $stmt = $db->prepare('SELECT COALESCE(SUM(stock), 0) FROM product_variants WHERE product_id = ? AND active = 1');
    $stmt->execute([$productId]);
    $stock = (int)$stmt->fetchColumn();

    $db->prepare('UPDATE products SET stock = ?, updated_at = CURRENT_TIMESTAMP WHERE id = ?')->execute([$stock, $productId]);
}

function pbSaveProductVariants(PDO $db, int $productId, array $variants, array $productFallback = []): void {
    if ($productId <= 0 || !pbHasProductVariantsTable($db)) {
        return;
    }

    $normalizedVariants = pbNormalizeVariantsInput($variants, $productFallback);
    $existingStmt = $db->prepare('SELECT id FROM product_variants WHERE product_id = ?');
    $existingStmt->execute([$productId]);
    $existingIds = array_map('intval', $existingStmt->fetchAll(PDO::FETCH_COLUMN) ?: []);

    $hasPrimaryColorId   = pbHasColumn($db, 'product_variants', 'primary_color_id');
    $hasSecondaryColorId = pbHasColumn($db, 'product_variants', 'secondary_color_id');

    if ($hasPrimaryColorId && $hasSecondaryColorId) {
        $upsertStmt = $db->prepare("
            INSERT INTO product_variants (
                id, product_id, label, primary_color, secondary_color,
                primary_color_id, secondary_color_id,
                sku, price, stock, image_url, image_urls, active, sort_order
            ) VALUES (
                ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?
            )
            ON DUPLICATE KEY UPDATE
                product_id = VALUES(product_id),
                label = VALUES(label),
                primary_color = VALUES(primary_color),
                secondary_color = VALUES(secondary_color),
                primary_color_id = VALUES(primary_color_id),
                secondary_color_id = VALUES(secondary_color_id),
                sku = VALUES(sku),
                price = VALUES(price),
                stock = VALUES(stock),
                image_url = VALUES(image_url),
                image_urls = VALUES(image_urls),
                active = VALUES(active),
                sort_order = VALUES(sort_order),
                updated_at = CURRENT_TIMESTAMP
        ");
    } else {
        $upsertStmt = $db->prepare("
            INSERT INTO product_variants (
                id, product_id, label, primary_color, secondary_color, sku, price, stock, image_url, image_urls, active, sort_order
            ) VALUES (
                ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?
            )
            ON DUPLICATE KEY UPDATE
                product_id = VALUES(product_id),
                label = VALUES(label),
                primary_color = VALUES(primary_color),
                secondary_color = VALUES(secondary_color),
                sku = VALUES(sku),
                price = VALUES(price),
                stock = VALUES(stock),
                image_url = VALUES(image_url),
                image_urls = VALUES(image_urls),
                active = VALUES(active),
                sort_order = VALUES(sort_order),
                updated_at = CURRENT_TIMESTAMP
        ");
    }

    $keptIds = [];
    foreach ($normalizedVariants as $index => $variant) {
        $imageUrls = pbNormalizeImageUrls($variant['image_urls'] ?? ($variant['image_url'] ?? []));
        $variantId = !empty($variant['id']) ? (int)$variant['id'] : null;
        if ($variantId !== null) {
            $keptIds[] = $variantId;
        }

        $resolvedPrimaryColor = pbResolveVariantColor(
            $db,
            $variant['primary_color_id'] ?? null,
            $variant['primary_color_name'] ?? ($variant['primary_color'] ?? null),
            $variant['primary_color_hex'] ?? null
        );
        $resolvedSecondaryColor = pbResolveVariantColor(
            $db,
            $variant['secondary_color_id'] ?? null,
            $variant['secondary_color_name'] ?? ($variant['secondary_color'] ?? null),
            $variant['secondary_color_hex'] ?? null
        );
        $label      = pbBuildVariantLabel(
            $resolvedPrimaryColor['name'] ?? null,
            $resolvedSecondaryColor['name'] ?? null,
            $variant['label'] ?? null,
            'Variante ' . ($index + 1)
        );
        $primaryColorText   = trim((string)($resolvedPrimaryColor['name'] ?? ''));
        $secondaryColorText = trim((string)($resolvedSecondaryColor['name'] ?? ''));
        $imageUrlsJson      = !empty($imageUrls) ? json_encode($imageUrls, JSON_UNESCAPED_SLASHES) : null;
        $price              = $variant['price'] !== null && $variant['price'] !== '' ? (float)$variant['price'] : null;
        $stock              = max(0, (int)($variant['stock'] ?? 0));
        $active             = array_key_exists('active', $variant) ? (int)((bool)$variant['active']) : 1;
        $sortOrder          = max(0, (int)($variant['sort_order'] ?? $index));

        if ($hasPrimaryColorId && $hasSecondaryColorId) {
            $upsertStmt->execute([
                $variantId, $productId, $label,
                $primaryColorText, $secondaryColorText,
                $resolvedPrimaryColor['id'], $resolvedSecondaryColor['id'],
                trim((string)($variant['sku'] ?? '')),
                $price, $stock, $imageUrls[0] ?? '', $imageUrlsJson, $active, $sortOrder,
            ]);
        } else {
            $upsertStmt->execute([
                $variantId, $productId, $label,
                $primaryColorText, $secondaryColorText,
                trim((string)($variant['sku'] ?? '')),
                $price, $stock, $imageUrls[0] ?? '', $imageUrlsJson, $active, $sortOrder,
            ]);
        }

        if ($variantId === null) {
            $keptIds[] = (int)$db->lastInsertId();
        }
    }

    $idsToDisable = array_values(array_diff($existingIds, array_filter($keptIds)));
    if (!empty($idsToDisable)) {
        $placeholders = implode(',', array_fill(0, count($idsToDisable), '?'));
        $params = array_merge([$productId], $idsToDisable);
        $stmt = $db->prepare("UPDATE product_variants SET active = 0, updated_at = CURRENT_TIMESTAMP WHERE product_id = ? AND id IN ($placeholders)");
        $stmt->execute($params);
    }

    pbSyncProductStock($db, $productId);
}

function pbIsDefaultVariantLabel(?string $label): bool {
    $label = strtolower(trim((string)$label));
    return in_array($label, ['', 'base', 'única', 'unica'], true);
}
