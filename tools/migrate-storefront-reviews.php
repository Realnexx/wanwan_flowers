<?php
if (PHP_SAPI !== 'cli') { http_response_code(403); exit('CLI only'); }
require_once __DIR__ . '/../config/app.php';
$database = $appConfig['database'];
$db = new mysqli($database['host'], $database['user'], $database['password'], $database['name']);
$db->set_charset('utf8mb4');
$db->query("CREATE TABLE IF NOT EXISTS storefront_reviews (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    order_id INT NOT NULL,
    rating TINYINT UNSIGNED NOT NULL,
    status ENUM('pending', 'approved', 'rejected') NOT NULL DEFAULT 'pending',
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY one_review_per_order (order_id),
    INDEX published_ratings (status, rating),
    CONSTRAINT storefront_review_rating CHECK (rating BETWEEN 1 AND 5),
    CONSTRAINT storefront_review_order FOREIGN KEY (order_id) REFERENCES online_orders(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
$db->close();
echo "Storefront review storage ready. No sample ratings inserted.\n";
