<?php
/**
 * Order shipping address snapshot helpers.
 */

require_once __DIR__ . '/product_variants.php';
require_once __DIR__ . '/customer_auth.php';

function pbHasOrderShippingAddressesTable(PDO $db): bool {
    return pbHasTable($db, 'order_shipping_addresses');
}

function pbHydrateOrderShippingAddress(?array $row): ?array {
    if (!$row) {
        return null;
    }

    return [
        'id' => isset($row['id']) ? (int)$row['id'] : null,
        'order_id' => isset($row['order_id']) ? (int)$row['order_id'] : null,
        'customer_id' => isset($row['customer_id']) ? (int)$row['customer_id'] : null,
        'customer_address_id' => isset($row['customer_address_id']) && $row['customer_address_id'] !== null ? (int)$row['customer_address_id'] : null,
        'label' => $row['label'] ?? null,
        'recipient_name' => $row['recipient_name'] ?? null,
        'phone' => $row['phone'] ?? null,
        'street' => $row['street'] ?? null,
        'city' => $row['city'] ?? null,
        'province' => $row['province'] ?? null,
        'postal_code' => $row['postal_code'] ?? null,
        'notes' => $row['notes'] ?? null,
        'source' => $row['source'] ?? 'checkout_form',
        'created_at' => $row['created_at'] ?? null,
        'updated_at' => $row['updated_at'] ?? null,
    ];
}

function pbGetOrderShippingAddress(PDO $db, int $orderId): ?array {
    if ($orderId <= 0 || !pbHasOrderShippingAddressesTable($db)) {
        return null;
    }

    $stmt = $db->prepare('SELECT * FROM order_shipping_addresses WHERE order_id = ? LIMIT 1');
    $stmt->execute([$orderId]);
    return pbHydrateOrderShippingAddress($stmt->fetch() ?: null);
}

function pbFindCustomerShippingAddress(PDO $db, int $customerId, ?int $addressId = null): ?array {
    if ($customerId <= 0 || !pbHasTable($db, 'customer_addresses')) {
        return null;
    }

    if ($addressId !== null && $addressId > 0) {
        $stmt = $db->prepare('SELECT * FROM customer_addresses WHERE id = ? AND customer_id = ? AND active = 1 LIMIT 1');
        $stmt->execute([$addressId, $customerId]);
        $row = $stmt->fetch();
        if ($row) {
            return $row;
        }
    }

    $stmt = $db->prepare('SELECT * FROM customer_addresses WHERE customer_id = ? AND active = 1 ORDER BY is_default DESC, created_at ASC LIMIT 1');
    $stmt->execute([$customerId]);
    return $stmt->fetch() ?: null;
}

function pbResolveOrderShippingSnapshot(PDO $db, ?int $customerId, array $customer, ?array $payload): ?array {
    if (!pbHasOrderShippingAddressesTable($db)) {
        return null;
    }

    $payload = is_array($payload) ? $payload : [];
    $requestedAddressId = (int)($payload['customer_address_id'] ?? $payload['id'] ?? 0);
    $hasSavedAddressPreference = array_key_exists('use_saved_address', $payload);
    $shouldUseSavedAddress = !$hasSavedAddressPreference || !empty($payload['use_saved_address']);
    $selectedAddress = ($customerId !== null && $customerId > 0 && $shouldUseSavedAddress)
        ? pbFindCustomerShippingAddress($db, $customerId, $requestedAddressId > 0 ? $requestedAddressId : null)
        : null;

    $recipientName = trim((string)($payload['recipient_name'] ?? ''));
    $label = trim((string)($payload['label'] ?? ''));
    $phone = trim((string)($payload['phone'] ?? ''));
    $street = trim((string)($payload['street'] ?? ''));
    $city = trim((string)($payload['city'] ?? ''));
    $province = trim((string)($payload['province'] ?? ''));
    $postalCode = trim((string)($payload['postal_code'] ?? ''));
    $notes = trim((string)($payload['notes'] ?? ''));

    $hasExplicitInput = $recipientName !== ''
        || $label !== ''
        || $phone !== ''
        || $street !== ''
        || $city !== ''
        || $province !== ''
        || $postalCode !== ''
        || $notes !== '';

    if (!$hasExplicitInput && !$selectedAddress) {
        return null;
    }

    if (!$hasExplicitInput && $selectedAddress) {
        return [
            'customer_id' => $customerId,
            'customer_address_id' => (int)$selectedAddress['id'],
            'label' => $selectedAddress['label'] ?? null,
            'recipient_name' => $selectedAddress['recipient_name'] ?? null,
            'phone' => $selectedAddress['phone'] ?? null,
            'street' => $selectedAddress['street'] ?? null,
            'city' => $selectedAddress['city'] ?? null,
            'province' => $selectedAddress['province'] ?? null,
            'postal_code' => $selectedAddress['postal_code'] ?? null,
            'notes' => $selectedAddress['notes'] ?? null,
            'source' => 'customer_address',
        ];
    }

    $street = $street !== '' ? $street : trim((string)($selectedAddress['street'] ?? ''));
    $city = $city !== '' ? $city : trim((string)($selectedAddress['city'] ?? ''));
    $province = $province !== '' ? $province : trim((string)($selectedAddress['province'] ?? ''));
    $postalCode = $postalCode !== '' ? $postalCode : trim((string)($selectedAddress['postal_code'] ?? ''));

    if ($street === '' || $city === '' || $province === '' || $postalCode === '') {
        throw new InvalidArgumentException('Completá calle, ciudad, provincia y código postal para la entrega.');
    }

    $fallbackName = trim((string)($customer['full_name'] ?? pbCustomerFormatFullName($customer)));
    $fallbackPhone = trim((string)($customer['phone'] ?? ''));

    return [
        'customer_id' => $customerId,
        'customer_address_id' => $selectedAddress ? (int)$selectedAddress['id'] : null,
        'label' => $label !== '' ? $label : ($selectedAddress['label'] ?? null),
        'recipient_name' => $recipientName !== '' ? $recipientName : (($selectedAddress['recipient_name'] ?? '') !== '' ? $selectedAddress['recipient_name'] : ($fallbackName !== '' ? $fallbackName : null)),
        'phone' => $phone !== '' ? $phone : (($selectedAddress['phone'] ?? '') !== '' ? $selectedAddress['phone'] : ($fallbackPhone !== '' ? $fallbackPhone : null)),
        'street' => $street,
        'city' => $city,
        'province' => $province,
        'postal_code' => $postalCode,
        'notes' => $notes !== '' ? $notes : ($selectedAddress['notes'] ?? null),
        'source' => $selectedAddress ? 'customer_address' : 'checkout_form',
    ];
}

function pbSaveOrderShippingAddress(PDO $db, int $orderId, ?array $snapshot): ?array {
    if ($orderId <= 0 || !$snapshot || !pbHasOrderShippingAddressesTable($db)) {
        return null;
    }

    $stmt = $db->prepare("
        INSERT INTO order_shipping_addresses (
            order_id, customer_id, customer_address_id, label, recipient_name, phone, street, city, province, postal_code, notes, source
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ON DUPLICATE KEY UPDATE
            customer_id = VALUES(customer_id),
            customer_address_id = VALUES(customer_address_id),
            label = VALUES(label),
            recipient_name = VALUES(recipient_name),
            phone = VALUES(phone),
            street = VALUES(street),
            city = VALUES(city),
            province = VALUES(province),
            postal_code = VALUES(postal_code),
            notes = VALUES(notes),
            source = VALUES(source),
            updated_at = CURRENT_TIMESTAMP
    ");
    $stmt->execute([
        $orderId,
        $snapshot['customer_id'] ?? null,
        $snapshot['customer_address_id'] ?? null,
        $snapshot['label'] ?? null,
        $snapshot['recipient_name'] ?? null,
        $snapshot['phone'] ?? null,
        $snapshot['street'] ?? null,
        $snapshot['city'] ?? null,
        $snapshot['province'] ?? null,
        $snapshot['postal_code'] ?? null,
        $snapshot['notes'] ?? null,
        $snapshot['source'] ?? 'checkout_form',
    ]);

    return pbGetOrderShippingAddress($db, $orderId);
}
