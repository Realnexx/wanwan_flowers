<?php
if (PHP_SAPI !== 'cli') { http_response_code(403); exit('CLI only'); }
require __DIR__.'/../config/app.php';
$d=$appConfig['database'];$db=new mysqli($d['host'],$d['user'],$d['password'],$d['name']);$db->set_charset('utf8mb4');
foreach (['fulfillment_type'=>"VARCHAR(16) NOT NULL DEFAULT 'pickup'",'payment_choice'=>"VARCHAR(16) NOT NULL DEFAULT 'counter'",'delivery_details'=>'LONGTEXT NULL','delivery_fee'=>"DECIMAL(10,2) NOT NULL DEFAULT 0",'gateway_fee_estimate'=>"DECIMAL(10,2) NOT NULL DEFAULT 0"] as $column=>$definition) {
    if (!$db->query("SHOW COLUMNS FROM online_orders LIKE '$column'")->num_rows) $db->query("ALTER TABLE online_orders ADD COLUMN $column $definition");
}
echo "Flower checkout fields ready.\n";
