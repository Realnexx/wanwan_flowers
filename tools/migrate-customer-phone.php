<?php
if (PHP_SAPI!=='cli') { http_response_code(403);exit; }
require __DIR__.'/../config/db.php';
if (!$conn->query("SHOW COLUMNS FROM users LIKE 'phone'")->num_rows) $conn->query('ALTER TABLE users ADD COLUMN phone VARCHAR(40) NULL');
echo "Customer phone field ready.\n";
