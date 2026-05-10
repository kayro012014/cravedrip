<?php
require_once __DIR__ . '/helpers.php';
api_require_auth();
require_once __DIR__ . '/config.php';
set_security_headers();

$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {
    $items = $pdo->query(
        "SELECT id, name, category, price, img, is_active
         FROM menu_items WHERE is_active = 1
         ORDER BY category, name"
    )->fetchAll();

    foreach ($items as &$i) {
        $i['id']    = (int)$i['id'];
        $i['price'] = (float)$i['price'];
        $i['is_active'] = (bool)$i['is_active'];
    }
    api_json(['ok' => true, 'items' => $items]);
}

if ($method === 'POST') {
    api_require_csrf();
    $action = v_str('action');

    $allowed_cats = ['coffee', 'cold', 'tea', 'pastry'];

    if ($action === 'add') {
        $name     = v_str('name', 150);
        $category = v_str('category', 50);
        $price    = v_float('price');
        $img      = v_str('img', 255);

        if (!$name)                                      api_json(['ok' => false, 'error' => 'Name is required.'], 422);
        if (!in_array($category, $allowed_cats, true))   api_json(['ok' => false, 'error' => 'Invalid category.'], 422);
        if ($price <= 0)                                 api_json(['ok' => false, 'error' => 'Price must be greater than zero.'], 422);

        $stmt = $pdo->prepare("INSERT INTO menu_items (name, category, price, img) VALUES (?,?,?,?)");
        $stmt->execute([$name, $category, $price, $img ?: null]);
        api_json(['ok' => true, 'id' => (int)$pdo->lastInsertId()]);
    }

    if ($action === 'update') {
        $id       = v_int('id', 1);
        $name     = v_str('name', 150);
        $category = v_str('category', 50);
        $price    = v_float('price');
        $img      = v_str('img', 255);

        if (!$name)                                      api_json(['ok' => false, 'error' => 'Name is required.'], 422);
        if (!in_array($category, $allowed_cats, true))   api_json(['ok' => false, 'error' => 'Invalid category.'], 422);
        if ($price <= 0)                                 api_json(['ok' => false, 'error' => 'Price must be greater than zero.'], 422);

        $stmt = $pdo->prepare("UPDATE menu_items SET name=?,category=?,price=?,img=? WHERE id=?");
        $stmt->execute([$name, $category, $price, $img ?: null, $id]);
        api_json(['ok' => true]);
    }

    if ($action === 'delete') {
        $id = v_int('id', 1);
        $pdo->prepare("UPDATE menu_items SET is_active=0 WHERE id=?")->execute([$id]);
        api_json(['ok' => true]);
    }

    api_json(['ok' => false, 'error' => 'Unknown action.'], 422);
}

api_json(['ok' => false, 'error' => 'Method not allowed'], 405);
