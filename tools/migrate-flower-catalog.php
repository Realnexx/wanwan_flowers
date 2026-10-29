<?php
if (PHP_SAPI !== 'cli') { http_response_code(403); exit('CLI only'); }
require_once __DIR__ . '/../config/app.php';
$config = $appConfig['database'];
$db = new mysqli($config['host'],$config['user'],$config['password'],$config['name']);
$db->set_charset('utf8mb4');
foreach (['website_badge'=>'VARCHAR(40)', 'website_occasion'=>'VARCHAR(80)', 'website_stems'=>'VARCHAR(255)'] as $column=>$type) {
    if (!$db->query("SHOW COLUMNS FROM products LIKE '$column'")->num_rows) $db->query("ALTER TABLE products ADD COLUMN $column $type NOT NULL DEFAULT ''");
}
$db->begin_transaction();
try {
    // Seed only an empty public catalog; never overwrite existing products or choices.
    $published = (int)$db->query('SELECT COUNT(*) n FROM products WHERE show_on_website=1')->fetch_assoc()['n'];
    $seeded = (int)$db->query("SELECT COUNT(*) n FROM products WHERE sku LIKE 'WAN-FLOWER-%'")->fetch_assoc()['n'];
    $inserted = 0;
    if (!$published && !$seeded) {
        $db->query("INSERT IGNORE INTO categories(name,description) VALUES('Flowers','Hand-arranged flower bouquets')");
        $category = (int)$db->query("SELECT id FROM categories WHERE name='Flowers'")->fetch_assoc()['id'];
        $stmt = $db->prepare("INSERT INTO products(category_id,name,sku,description,price,image_url,status,show_on_website,is_sellable,is_ingredient,track_stock,inventory_type,website_badge,website_occasion,website_stems) VALUES(?,?,?,?,?,?,'active',1,1,0,0,'non-stock',?,?,?)");
        foreach (require __DIR__ . '/flower-seed-data.php' as $id=>$flower) {
            $sku = 'WAN-FLOWER-' . str_pad((string)$id,3,'0',STR_PAD_LEFT);
            $image = 'https://images.unsplash.com/'.$flower['image'].'?auto=format&fit=crop&w=1200&q=85';
            $stmt->bind_param('isssdssss',$category,$flower['name'],$sku,$flower['note'],$flower['price'],$image,$flower['badge'],$flower['type'],$flower['stems']);
            $stmt->execute(); $inserted++;
        }
    }
    $db->commit();
    echo "Flower catalog ready. Seeded $inserted products. Existing products preserved.\n";
} catch (Throwable $error) { $db->rollback(); throw $error; }
