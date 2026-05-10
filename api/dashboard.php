<?php
require_once __DIR__ . '/helpers.php';
api_require_auth();
require_once __DIR__ . '/config.php';
set_security_headers();

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    api_json(['ok' => false, 'error' => 'Method not allowed'], 405);
}

/* Today's revenue / order count / average */
$stats = $pdo->query(
    "SELECT
        COALESCE(SUM(total), 0)   AS revenue,
        COUNT(*)                  AS orders,
        COALESCE(AVG(total), 0)   AS avg_order
     FROM orders
     WHERE DATE(created_at) = CURDATE()"
)->fetch();

/* Last 10 orders today with item summary */
$recentStmt = $pdo->query(
    "SELECT o.id, o.order_number, o.total, o.payment_method, o.created_at,
            GROUP_CONCAT(
                CONCAT(oi.item_name, IF(oi.quantity > 1, CONCAT(' x', oi.quantity), ''))
                ORDER BY oi.id SEPARATOR ', '
            ) AS items_summary
     FROM orders o
     LEFT JOIN order_items oi ON oi.order_id = o.id
     WHERE DATE(o.created_at) = CURDATE()
     GROUP BY o.id
     ORDER BY o.created_at DESC
     LIMIT 10"
);
$recent = $recentStmt->fetchAll();

foreach ($recent as &$o) {
    $o['id']      = (int)$o['id'];
    $o['total']   = (float)$o['total'];
    $o['time']    = date('H:i', strtotime($o['created_at']));
    $o['payment'] = ucfirst($o['payment_method']);
    $o['items']   = $o['items_summary']
        ? array_map('trim', explode(', ', $o['items_summary']))
        : [];
    unset($o['items_summary'], $o['payment_method']);
}

/* Stock alerts from inventory */
$invRows = $pdo->query(
    "SELECT name, unit, stock, reorder_level
     FROM inventory_items"
)->fetchAll();

$low = [];
$out = [];
foreach ($invRows as $i) {
    $stock   = (float)$i['stock'];
    $reorder = (float)$i['reorder_level'];
    $entry   = ['name' => $i['name'], 'unit' => $i['unit'], 'stock' => $stock, 'reorderLevel' => $reorder];
    if ($stock <= 0) {
        $out[] = $entry;
    } elseif ($stock <= $reorder) {
        $low[] = $entry;
    }
}

api_json([
    'ok'      => true,
    'revenue' => round((float)$stats['revenue'], 2),
    'orders'  => (int)$stats['orders'],
    'avg'     => round((float)$stats['avg_order'], 2),
    'alerts'  => count($low) + count($out),
    'recent'  => $recent,
    'low'     => $low,
    'out'     => $out,
]);
