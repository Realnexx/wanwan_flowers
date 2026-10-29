<?php
if(PHP_SAPI!=='cli'){http_response_code(403);exit;}
require __DIR__.'/../config/app.php';$d=$appConfig['database'];$db=new mysqli($d['host'],$d['user'],$d['password'],$d['name']);
foreach(['psgc_region','psgc_province','psgc_city','psgc_barangay'] as $column) if(!$db->query("SHOW COLUMNS FROM users LIKE '$column'")->num_rows) $db->query("ALTER TABLE users ADD COLUMN $column VARCHAR(10) NOT NULL DEFAULT ''");
echo "PSGC profile fields ready.\n";
