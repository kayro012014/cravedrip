<?php
/*
 * CraveDrip — One-time database setup
 * Visit: http://localhost:8888/cravedrip/admin/pages/setup.php
 * DELETE this file after running it.
 */

$host = '127.0.0.1';
$port = 8889;
$user = 'root';
$pass = 'root';

$steps = [];
$ok    = true;

try {
    $pdo = new PDO(
        "mysql:host=$host;port=$port;charset=utf8mb4",
        $user,
        $pass,
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );

    /* ── Database ── */
    $pdo->exec("CREATE DATABASE IF NOT EXISTS cravedrip CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    $steps[] = ['ok', 'Database <strong>cravedrip</strong> ready.'];
    $pdo->exec("USE cravedrip");

    /* ── users ── */
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS users (
            id            INT AUTO_INCREMENT PRIMARY KEY,
            username      VARCHAR(60)  UNIQUE NOT NULL,
            password_hash VARCHAR(255) NOT NULL,
            name          VARCHAR(100) NOT NULL,
            role          VARCHAR(30)  NOT NULL DEFAULT 'admin',
            created_at    TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )
    ");
    $steps[] = ['ok', 'Table <strong>users</strong> ready.'];

    /* ── login_attempts (rate limiting) ── */
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS login_attempts (
            id           INT AUTO_INCREMENT PRIMARY KEY,
            ip           VARCHAR(45) NOT NULL,
            attempted_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_ip_time (ip, attempted_at)
        )
    ");
    $steps[] = ['ok', 'Table <strong>login_attempts</strong> ready.'];

    /* ── menu_items ── */
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS menu_items (
            id         INT AUTO_INCREMENT PRIMARY KEY,
            name       VARCHAR(150) NOT NULL,
            category   VARCHAR(50)  NOT NULL,
            price      DECIMAL(10,2) NOT NULL,
            img        VARCHAR(255),
            is_active  TINYINT(1) NOT NULL DEFAULT 1,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        )
    ");
    $steps[] = ['ok', 'Table <strong>menu_items</strong> ready.'];

    /* ── inventory_items ── */
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS inventory_items (
            id            INT AUTO_INCREMENT PRIMARY KEY,
            name          VARCHAR(150)  NOT NULL,
            category      VARCHAR(50)   NOT NULL,
            unit          VARCHAR(20)   NOT NULL,
            stock         DECIMAL(12,3) NOT NULL DEFAULT 0,
            reorder_level DECIMAL(12,3) NOT NULL DEFAULT 0,
            cost_price    DECIMAL(10,2) NOT NULL DEFAULT 0,
            sell_price    DECIMAL(10,2) NULL,
            created_at    TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at    TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        )
    ");
    $steps[] = ['ok', 'Table <strong>inventory_items</strong> ready.'];

    /* ── orders ── */
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS orders (
            id             INT AUTO_INCREMENT PRIMARY KEY,
            order_number   VARCHAR(25)   NOT NULL UNIQUE,
            subtotal       DECIMAL(10,2) NOT NULL,
            discount       DECIMAL(10,2) NOT NULL DEFAULT 0,
            vat_amount     DECIMAL(10,4) NOT NULL DEFAULT 0,
            total          DECIMAL(10,2) NOT NULL,
            payment_method VARCHAR(20)   NOT NULL,
            cash_received  DECIMAL(10,2) NULL,
            change_amount  DECIMAL(10,2) NULL,
            created_by     INT           NOT NULL,
            created_at     TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_date (created_at),
            FOREIGN KEY (created_by) REFERENCES users(id)
        )
    ");
    $steps[] = ['ok', 'Table <strong>orders</strong> ready.'];

    /* ── order_items ── */
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS order_items (
            id           INT AUTO_INCREMENT PRIMARY KEY,
            order_id     INT           NOT NULL,
            menu_item_id INT           NULL,
            item_name    VARCHAR(150)  NOT NULL,
            item_price   DECIMAL(10,2) NOT NULL,
            quantity     INT           NOT NULL DEFAULT 1,
            line_total   DECIMAL(10,2) NOT NULL,
            FOREIGN KEY (order_id)     REFERENCES orders(id) ON DELETE CASCADE,
            FOREIGN KEY (menu_item_id) REFERENCES menu_items(id) ON DELETE SET NULL
        )
    ");
    $steps[] = ['ok', 'Table <strong>order_items</strong> ready.'];

    /* ── Seed: admin user ── */
    $count = $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
    if ($count == 0) {
        $hash = password_hash('cravedrip2024', PASSWORD_BCRYPT);
        $pdo->prepare("INSERT INTO users (username, password_hash, name, role) VALUES (?,?,?,?)")
            ->execute(['admin', $hash, 'Jude Christian', 'admin']);
        $steps[] = ['ok', 'Admin account created — <code>admin / cravedrip2024</code>'];
    } else {
        $steps[] = ['info', 'Admin user already exists — skipped.'];
    }

    /* ── Seed: menu items ── */
    $menuCount = $pdo->query("SELECT COUNT(*) FROM menu_items")->fetchColumn();
    if ($menuCount == 0) {
        $menuStmt = $pdo->prepare(
            "INSERT INTO menu_items (name, category, price, img) VALUES (?,?,?,?)"
        );
        $menu = [
            ['Classic Espresso',  'coffee', 85,  '/cravedrip/assets/images/espresso.jpg'],
            ['Vanilla Cappuccino','coffee', 120, '/cravedrip/assets/images/cappuccino.jpg'],
            ['Caramel Macchiato', 'coffee', 150, '/cravedrip/assets/images/caramel-macchiato.jpg'],
            ['Americano',         'coffee', 95,  '/cravedrip/assets/images/espresso.jpg'],
            ['Caffe Latte',       'coffee', 130, '/cravedrip/assets/images/cappuccino.jpg'],
            ['Iced Mocha Frappe', 'cold',   165, '/cravedrip/assets/images/iced-mocha.jpg'],
            ['Cold Brew',         'cold',   145, '/cravedrip/assets/images/iced-mocha.jpg'],
            ['Mango Smoothie',    'cold',   130, '/cravedrip/assets/images/mango-smoothie.jpg'],
            ['Matcha Latte',      'tea',    140, '/cravedrip/assets/images/matcha-latte.jpg'],
            ['Earl Grey Tea',     'tea',    95,  '/cravedrip/assets/images/matcha-latte.jpg'],
            ['Butter Croissant',  'pastry', 75,  '/cravedrip/assets/images/croissant.jpg'],
            ['Chocolate Cake',    'pastry', 110, '/cravedrip/assets/images/chocolate-cake.jpg'],
            ['Blueberry Muffin',  'pastry', 85,  '/cravedrip/assets/images/croissant.jpg'],
            ['Cheese Danish',     'pastry', 90,  '/cravedrip/assets/images/croissant.jpg'],
        ];
        foreach ($menu as $row) $menuStmt->execute($row);
        $steps[] = ['ok', count($menu) . ' menu items seeded.'];
    } else {
        $steps[] = ['info', 'Menu items already exist — skipped.'];
    }

    /* ── Seed: inventory items ── */
    $invCount = $pdo->query("SELECT COUNT(*) FROM inventory_items")->fetchColumn();
    if ($invCount == 0) {
        $invStmt = $pdo->prepare(
            "INSERT INTO inventory_items (name, category, unit, stock, reorder_level, cost_price, sell_price)
             VALUES (?,?,?,?,?,?,?)"
        );
        $inv = [
            ['Arabica Espresso Beans','ingredients','kg',  12.5,5,   420,  null],
            ['Robusta Beans',         'ingredients','kg',  8,   5,   280,  null],
            ['Fresh Milk',            'ingredients','L',   18,  10,  75,   null],
            ['Oat Milk',              'ingredients','L',   4,   5,   145,  null],
            ['Heavy Cream',           'ingredients','L',   3,   4,   210,  null],
            ['Vanilla Syrup',         'ingredients','L',   2.5, 2,   320,  null],
            ['Caramel Syrup',         'ingredients','L',   1.5, 2,   320,  null],
            ['Chocolate Powder',      'ingredients','kg',  3.2, 2,   380,  null],
            ['Matcha Powder',         'ingredients','kg',  0.8, 1,   1200, null],
            ['Sugar (White)',         'ingredients','kg',  15,  5,   65,   null],
            ['Mango Puree',           'ingredients','L',   5,   3,   180,  null],
            ['Ice',                   'ingredients','kg',  0,   10,  15,   null],
            ['Paper Cups 8oz',        'supplies',   'pcs', 320, 100, 3,    null],
            ['Paper Cups 12oz',       'supplies',   'pcs', 450, 100, 4,    null],
            ['Paper Cups 16oz',       'supplies',   'pcs', 180, 100, 5,    null],
            ['Plastic Straws',        'supplies',   'pcs', 600, 200, 1,    null],
            ['Coffee Filters',        'supplies',   'pcs', 200, 50,  2,    null],
            ['Butter Croissants',     'baked',      'pcs', 24,  10,  35,   75],
            ['Chocolate Cake Slice',  'baked',      'pcs', 12,  5,   55,   110],
            ['Blueberry Muffins',     'baked',      'pcs', 8,   8,   40,   85],
        ];
        foreach ($inv as $row) $invStmt->execute($row);
        $steps[] = ['ok', count($inv) . ' inventory items seeded.'];
    } else {
        $steps[] = ['info', 'Inventory items already exist — skipped.'];
    }

} catch (PDOException $e) {
    $steps[] = ['err', 'Error: ' . htmlspecialchars($e->getMessage())];
    $ok = false;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Setup — CraveDrip</title>
<link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;600;700&family=Playfair+Display:wght@700;900&display=swap" rel="stylesheet">
<style>
  *{margin:0;padding:0;box-sizing:border-box}
  body{font-family:'DM Sans',sans-serif;background:#1a0f08;min-height:100vh;display:flex;align-items:center;justify-content:center;padding:2rem}
  .card{background:#fff;border-radius:16px;padding:2.5rem;max-width:560px;width:100%}
  .logo{font-family:'Playfair Display',serif;font-size:1.8rem;font-weight:900;color:#3d2817}
  .logo span{color:#b8763a}
  h2{font-size:1rem;color:#3d2817;margin:0.25rem 0 1.75rem;font-weight:600}
  .step{display:flex;gap:0.75rem;align-items:flex-start;padding:0.7rem 1rem;border-radius:8px;margin-bottom:0.5rem;font-size:0.875rem}
  .step.ok   {background:rgba(39,174,96,0.08);color:#1e7e40}
  .step.info {background:rgba(41,128,185,0.08);color:#1a609c}
  .step.err  {background:rgba(231,76,60,0.08);color:#b52d1f}
  .actions{margin-top:1.75rem;display:flex;gap:0.75rem;flex-wrap:wrap}
  a.btn{display:inline-block;padding:0.65rem 1.4rem;border-radius:8px;text-decoration:none;font-weight:600;font-size:0.875rem;transition:.2s}
  .btn-primary{background:#b8763a;color:#fff}
  .btn-primary:hover{background:#9e5e2a}
  .btn-ghost{border:1.5px solid #e0d5c5;color:#c4a484}
  .btn-ghost:hover{border-color:#b8763a;color:#b8763a}
  .warn{margin-top:1.5rem;padding:0.85rem 1rem;background:rgba(230,126,34,0.08);border-left:3px solid #e67e22;border-radius:0 8px 8px 0;font-size:0.8rem;color:#8a5a10;line-height:1.6}
  code{background:#f5efe6;padding:0.1rem 0.4rem;border-radius:4px;font-size:0.85em}
</style>
</head>
<body>
<div class="card">
  <div class="logo">Crave<span>Drip</span></div>
  <h2>Database Setup</h2>

  <?php foreach ($steps as [$type, $msg]): ?>
  <div class="step <?= $type ?>">
    <span><?= $type === 'ok' ? '✓' : ($type === 'err' ? '✗' : 'i') ?></span>
    <span><?= $msg ?></span>
  </div>
  <?php endforeach; ?>

  <div class="actions">
    <?php if ($ok): ?>
      <a href="login.php" class="btn btn-primary">Go to Login →</a>
    <?php endif; ?>
    <a href="../../" class="btn btn-ghost">Back to Site</a>
  </div>

  <div class="warn">
    <strong>Security:</strong> Delete <code>admin/pages/setup.php</code> immediately after setup is complete.
  </div>
</div>
</body>
</html>
