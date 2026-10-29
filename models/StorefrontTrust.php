<?php
class StorefrontTrust
{
    public static function summary(array $database): array
    {
        $summary = ['rating' => null, 'reviews' => 0, 'completed_orders' => null];
        $db = null;
        try {
            $db = new mysqli($database['host'], $database['user'], $database['password'], $database['name']);
            $db->set_charset('utf8mb4');
            $orders = $db->query("SELECT COUNT(*) AS total FROM online_orders WHERE status = 'completed' AND payment_status = 'paid'");
            if ($orders) $summary['completed_orders'] = (int)$orders->fetch_assoc()['total'];
            $reviews = $db->query("SELECT AVG(r.rating) AS average_rating, COUNT(*) AS total FROM storefront_reviews r INNER JOIN online_orders o ON o.id = r.order_id WHERE r.status = 'approved' AND r.rating BETWEEN 1 AND 5 AND o.status = 'completed' AND o.payment_status = 'paid'");
            if ($reviews) {
                $row = $reviews->fetch_assoc();
                $summary['reviews'] = (int)$row['total'];
                if ($summary['reviews'] > 0) $summary['rating'] = (float)$row['average_rating'];
            }
        } catch (Throwable $error) {
            error_log('Storefront trust metrics unavailable: ' . get_class($error));
        } finally {
            if ($db instanceof mysqli) $db->close();
        }
        return $summary;
    }
}
