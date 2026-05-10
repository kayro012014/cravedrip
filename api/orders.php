<?php
require_once __DIR__ . '/helpers.php';
api_require_auth();
require_once __DIR__ . '/config.php';
set_security_headers();

$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {
    $date = $_GET['date'] ?? date('Y-m-d');
    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
        $date = date('Y-m-d');
    }

    $stmt = $pdo->prepare("
        SELECT o.id, o.order_number, o.subtotal, o.discount, o.vat_amount, o.total,
               o.payment_method, o.cash_received, o.change_amount, o.created_at,
               GROUP_CONCAT(
                   CONCAT(oi.item_name, IF(oi.quantity > 1, CONCAT(' x', oi.quantity), ''))
                   ORDER BY oi.id SEPARATOR ', '
               ) AS items_summary
        FROM orders o
        LEFT JOIN order_items oi ON oi.order_id = o.id
        WHERE DATE(o.created_at) = ?
        GROUP BY o.id
        ORDER BY o.created_at DESC
    ");
    $stmt->execute([$date]);
    $rows = $stmt->fetchAll();

    foreach ($rows as &$o) {
        $o['id']         = (int)$o['id'];
        $o['subtotal']   = (float)$o['subtotal'];
        $o['discount']   = (float)$o['discount'];
        $o['vat_amount'] = (float)$o['vat_amount'];
        $o['total']      = (float)$o['total'];
        $o['time']       = date('H:i', strtotime($o['created_at']));
        $o['payment']    = ucfirst($o['payment_method']);
        $o['items']      = $o['items_summary']
            ? array_map('trim', explode(', ', $o['items_summary']))
            : [];
        unset($o['items_summary'], $o['payment_method']);
    }
    api_json(['ok' => true, 'orders' => $rows]);
}

if ($method === 'POST') {
    api_require_csrf();

    /* Accept JSON body (fetch with Content-Type: application/json) */
    $raw   = file_get_contents('php://input');
    $input = $raw ? json_decode($raw, true) : $_POST;
    if (!$input) api_json(['ok' => false, 'error' => 'Invalid request body.'], 400);

    $cartItems     = $input['items']          ?? [];
    $subtotal      = (float)($input['subtotal']       ?? 0);
    $discount      = (float)($input['discount']        ?? 0);
    $total         = (float)($input['total']            ?? 0);
    $paymentMethod = strtolower(trim($input['payment_method'] ?? ''));
    $cashReceived  = isset($input['cash_received'])  ? (float)$input['cash_received']  : null;
    $changeAmount  = isset($input['change_amount'])  ? (float)$input['change_amount']  : null;

    $allowed_payments = ['cash', 'gcash', 'card'];
    if (!in_array($paymentMethod, $allowed_payments, true)) api_json(['ok' => false, 'error' => 'Invalid payment method.'], 422);
    if (!is_array($cartItems) || empty($cartItems))         api_json(['ok' => false, 'error' => 'Order has no items.'], 422);
    if ($total <= 0)                                         api_json(['ok' => false, 'error' => 'Invalid order total.'], 422);

    $vatAmount = round($total / 1.12 * 0.12, 4);
    $userId    = (int)$_SESSION['user_id'];

    try {
        $pdo->beginTransaction();

        /* Generate a unique order number: ORD-YYYYMMDD-NNN */
        $countToday  = (int)$pdo->query(
            "SELECT COUNT(*) FROM orders WHERE DATE(created_at) = CURDATE()"
        )->fetchColumn();
        $orderNumber = 'ORD-' . date('Ymd') . '-' . str_pad($countToday + 1, 3, '0', STR_PAD_LEFT);

        $stmt = $pdo->prepare(
            "INSERT INTO orders (order_number, subtotal, discount, vat_amount, total,
                                 payment_method, cash_received, change_amount, created_by)
             VALUES (?,?,?,?,?,?,?,?,?)"
        );
        $stmt->execute([
            $orderNumber, $subtotal, $discount, $vatAmount,
            $total, $paymentMethod, $cashReceived, $changeAmount, $userId,
        ]);
        $orderId = (int)$pdo->lastInsertId();

        $itemStmt = $pdo->prepare(
            "INSERT INTO order_items (order_id, menu_item_id, item_name, item_price, quantity, line_total)
             VALUES (?,?,?,?,?,?)"
        );
        foreach ($cartItems as $item) {
            $menuItemId = isset($item['id']) && $item['id'] ? (int)$item['id'] : null;
            $name       = mb_substr(trim($item['name'] ?? ''), 0, 150);
            $price      = (float)($item['price'] ?? 0);
            $qty        = max(1, (int)($item['qty'] ?? 1));
            if (!$name || $price <= 0) continue;
            $itemStmt->execute([$orderId, $menuItemId, $name, $price, $qty, $price * $qty]);
        }

        $pdo->commit();
        api_json(['ok' => true, 'order_id' => $orderId, 'order_number' => $orderNumber]);

    } catch (PDOException $e) {
        $pdo->rollBack();
        api_json(['ok' => false, 'error' => 'Failed to save order.'], 500);
    }
}

api_json(['ok' => false, 'error' => 'Method not allowed'], 405);
