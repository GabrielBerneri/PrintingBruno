<?php
/**
 * Customer addresses
 * GET /api/customer/addresses.php
 * POST /api/customer/addresses.php
 * PUT /api/customer/addresses.php?id=X
 * DELETE /api/customer/addresses.php?id=X
 */

require_once __DIR__ . '/../db.php';
require_once __DIR__ . '/../customer_auth.php';

$db = getDB();
$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
$session = pbCustomerRequireAuth($db, $method !== 'GET');
$customerId = (int)$session['customer_id'];

if ($method === 'GET') {
    jsonResponse(['addresses' => pbCustomerListAddresses($db, $customerId)]);
}

if ($method === 'POST') {
    $address = pbCustomerSaveAddress($db, $customerId, null, getJsonBody());
    jsonResponse(['success' => true, 'address' => $address], 201);
}

if ($method === 'PUT') {
    $payload = getJsonBody();
    $addressId = (int)($_GET['id'] ?? ($payload['id'] ?? 0));
    if ($addressId <= 0) {
        jsonResponse(['error' => 'Address ID required'], 400);
    }
    $address = pbCustomerSaveAddress($db, $customerId, $addressId, $payload);
    jsonResponse(['success' => true, 'address' => $address]);
}

if ($method === 'DELETE') {
    $addressId = (int)($_GET['id'] ?? 0);
    if ($addressId <= 0) {
        jsonResponse(['error' => 'Address ID required'], 400);
    }

    $stmt = $db->prepare('UPDATE customer_addresses SET active = 0, is_default = 0, updated_at = CURRENT_TIMESTAMP WHERE id = ? AND customer_id = ?');
    $stmt->execute([$addressId, $customerId]);
    if ($stmt->rowCount() === 0) {
        jsonResponse(['error' => 'Dirección no encontrada.'], 404);
    }

    pbCustomerEnsureDefaultAddress($db, $customerId);
    jsonResponse(['success' => true]);
}

jsonResponse(['error' => 'Method not allowed'], 405);

function pbCustomerListAddresses(PDO $db, int $customerId): array {
    $stmt = $db->prepare('SELECT * FROM customer_addresses WHERE customer_id = ? AND active = 1 ORDER BY is_default DESC, created_at DESC');
    $stmt->execute([$customerId]);
    $addresses = [];
    foreach ($stmt->fetchAll() as $row) {
        $row['id'] = (int)$row['id'];
        $row['customer_id'] = (int)$row['customer_id'];
        $row['is_default'] = (int)$row['is_default'];
        $row['active'] = (int)$row['active'];
        $addresses[] = $row;
    }
    return $addresses;
}

function pbCustomerEnsureDefaultAddress(PDO $db, int $customerId): void {
    $stmt = $db->prepare('SELECT id FROM customer_addresses WHERE customer_id = ? AND active = 1 AND is_default = 1 LIMIT 1');
    $stmt->execute([$customerId]);
    if ($stmt->fetchColumn()) {
        return;
    }

    $stmt = $db->prepare('SELECT id FROM customer_addresses WHERE customer_id = ? AND active = 1 ORDER BY created_at ASC LIMIT 1');
    $stmt->execute([$customerId]);
    $fallbackId = (int)$stmt->fetchColumn();
    if ($fallbackId > 0) {
        $db->prepare('UPDATE customer_addresses SET is_default = 1 WHERE id = ?')->execute([$fallbackId]);
    }
}

function pbCustomerSaveAddress(PDO $db, int $customerId, ?int $addressId, array $payload): array {
    $label = trim((string)($payload['label'] ?? ''));
    $recipientName = trim((string)($payload['recipient_name'] ?? ''));
    $phone = trim((string)($payload['phone'] ?? ''));
    $street = trim((string)($payload['street'] ?? ''));
    $city = trim((string)($payload['city'] ?? ''));
    $province = trim((string)($payload['province'] ?? ''));
    $postalCode = trim((string)($payload['postal_code'] ?? ''));
    $notes = trim((string)($payload['notes'] ?? ''));
    $isDefault = array_key_exists('is_default', $payload) ? (int)((bool)$payload['is_default']) : 0;

    if ($street === '' || $city === '' || $province === '' || $postalCode === '') {
        jsonResponse(['error' => 'Calle, ciudad, provincia y código postal son obligatorios.'], 400);
    }

    $db->beginTransaction();
    try {
        if ($isDefault === 1) {
            $db->prepare('UPDATE customer_addresses SET is_default = 0 WHERE customer_id = ?')->execute([$customerId]);
        }

        if ($addressId === null) {
            $stmt = $db->prepare("
                INSERT INTO customer_addresses (customer_id, label, recipient_name, phone, street, city, province, postal_code, notes, is_default, active)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 1)
            ");
            $stmt->execute([
                $customerId,
                $label !== '' ? $label : null,
                $recipientName !== '' ? $recipientName : null,
                $phone !== '' ? $phone : null,
                $street,
                $city,
                $province,
                $postalCode,
                $notes !== '' ? $notes : null,
                $isDefault,
            ]);
            $addressId = (int)$db->lastInsertId();
        } else {
            $stmt = $db->prepare("
                UPDATE customer_addresses
                SET label = ?, recipient_name = ?, phone = ?, street = ?, city = ?, province = ?, postal_code = ?, notes = ?, is_default = ?, updated_at = CURRENT_TIMESTAMP
                WHERE id = ? AND customer_id = ?
            ");
            $stmt->execute([
                $label !== '' ? $label : null,
                $recipientName !== '' ? $recipientName : null,
                $phone !== '' ? $phone : null,
                $street,
                $city,
                $province,
                $postalCode,
                $notes !== '' ? $notes : null,
                $isDefault,
                $addressId,
                $customerId,
            ]);
            if ($stmt->rowCount() === 0) {
                throw new RuntimeException('Dirección no encontrada.');
            }
        }

        pbCustomerEnsureDefaultAddress($db, $customerId);
        $db->commit();
    } catch (Throwable $e) {
        if ($db->inTransaction()) {
            $db->rollBack();
        }
        if ($e instanceof RuntimeException) {
            jsonResponse(['error' => $e->getMessage()], 404);
        }
        throw $e;
    }

    $stmt = $db->prepare('SELECT * FROM customer_addresses WHERE id = ? LIMIT 1');
    $stmt->execute([$addressId]);
    $address = $stmt->fetch();
    if (!$address) {
        throw new RuntimeException('No se pudo cargar la dirección guardada.');
    }

    $address['id'] = (int)$address['id'];
    $address['customer_id'] = (int)$address['customer_id'];
    $address['is_default'] = (int)$address['is_default'];
    $address['active'] = (int)$address['active'];
    return $address;
}
