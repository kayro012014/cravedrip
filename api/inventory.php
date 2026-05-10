<?php
require_once __DIR__ . '/helpers.php';
api_require_auth();
require_once __DIR__ . '/config.php';
set_security_headers();

$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {
    $rows = $pdo->query(
        "SELECT id, name, category, unit, stock,
                reorder_level AS reorderLevel,
                cost_price    AS costPrice,
                sell_price    AS sellPrice
         FROM inventory_items
         ORDER BY category, name"
    )->fetchAll();

    foreach ($rows as &$i) {
        $i['id']           = (int)$i['id'];
        $i['stock']        = (float)$i['stock'];
        $i['reorderLevel'] = (float)$i['reorderLevel'];
        $i['costPrice']    = (float)$i['costPrice'];
        $i['sellPrice']    = $i['sellPrice'] !== null ? (float)$i['sellPrice'] : null;
    }
    api_json(['ok' => true, 'items' => $rows]);
}

if ($method === 'POST') {
    api_require_csrf();
    $action = v_str('action');

    $allowed_cats  = ['ingredients', 'supplies', 'baked'];
    $allowed_units = ['kg', 'g', 'L', 'ml', 'pcs', 'pack'];

    if ($action === 'add' || $action === 'update') {
        $name      = v_str('name', 150);
        $category  = v_str('category', 50);
        $unit      = v_str('unit', 20);
        $stock     = (float)($_POST['stock']      ?? 0);
        $reorder   = (float)($_POST['reorder']    ?? 0);
        $costPrice = (float)($_POST['cost_price'] ?? 0);
        $sellPrice = (isset($_POST['sell_price']) && $_POST['sell_price'] !== '')
                     ? (float)$_POST['sell_price'] : null;

        if (!$name)                                         api_json(['ok' => false, 'error' => 'Name is required.'], 422);
        if (!in_array($category, $allowed_cats, true))      api_json(['ok' => false, 'error' => 'Invalid category.'], 422);
        if (!in_array($unit, $allowed_units, true))         api_json(['ok' => false, 'error' => 'Invalid unit.'], 422);
        if ($stock < 0)                                     api_json(['ok' => false, 'error' => 'Stock cannot be negative.'], 422);
        if ($costPrice < 0)                                 api_json(['ok' => false, 'error' => 'Cost price cannot be negative.'], 422);

        if ($action === 'add') {
            $stmt = $pdo->prepare(
                "INSERT INTO inventory_items (name, category, unit, stock, reorder_level, cost_price, sell_price)
                 VALUES (?,?,?,?,?,?,?)"
            );
            $stmt->execute([$name, $category, $unit, $stock, $reorder, $costPrice, $sellPrice]);
            api_json(['ok' => true, 'id' => (int)$pdo->lastInsertId()]);
        } else {
            $id   = v_int('id', 1);
            $stmt = $pdo->prepare(
                "UPDATE inventory_items
                 SET name=?,category=?,unit=?,stock=?,reorder_level=?,cost_price=?,sell_price=?
                 WHERE id=?"
            );
            $stmt->execute([$name, $category, $unit, $stock, $reorder, $costPrice, $sellPrice, $id]);
            api_json(['ok' => true]);
        }
    }

    if ($action === 'adjust') {
        $id     = v_int('id', 1);
        $type   = v_str('type', 10);
        $qty    = (float)($_POST['qty'] ?? 0);

        if (!in_array($type, ['add', 'deduct'], true)) api_json(['ok' => false, 'error' => 'Invalid adjustment type.'], 422);
        if ($qty <= 0)                                  api_json(['ok' => false, 'error' => 'Quantity must be positive.'], 422);

        $stmt = $pdo->prepare("SELECT stock FROM inventory_items WHERE id = ?");
        $stmt->execute([$id]);
        $item = $stmt->fetch();
        if (!$item) api_json(['ok' => false, 'error' => 'Item not found.'], 404);

        $current  = (float)$item['stock'];
        $newStock = $type === 'add' ? $current + $qty : $current - $qty;

        if ($newStock < 0) api_json(['ok' => false, 'error' => 'Cannot deduct more than current stock.'], 422);

        $newStock = round($newStock, 3);
        $pdo->prepare("UPDATE inventory_items SET stock=? WHERE id=?")->execute([$newStock, $id]);
        api_json(['ok' => true, 'stock' => $newStock]);
    }

    if ($action === 'delete') {
        $id = v_int('id', 1);
        $pdo->prepare("DELETE FROM inventory_items WHERE id=?")->execute([$id]);
        api_json(['ok' => true]);
    }

    api_json(['ok' => false, 'error' => 'Unknown action.'], 422);
}

api_json(['ok' => false, 'error' => 'Method not allowed'], 405);
