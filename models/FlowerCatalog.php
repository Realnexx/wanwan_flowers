<?php
class FlowerCatalog
{
    public const BADGES = ['', 'Best Seller', 'Editor’s Pick', 'Popular Gift', 'Signature Lush', 'Seasonal', 'Sweet Gesture', 'Limited Stem', 'Serene'];

    public static function products(mysqli $db): array
    {
        $rows = $db->query("SELECT p.*, c.name AS category_name FROM products p LEFT JOIN categories c ON c.id=p.category_id WHERE p.show_on_website=1 AND p.status='active' AND p.is_sellable=1 AND COALESCE(p.is_ingredient,0)=0 AND COALESCE(p.product_type,'simple')<>'ingredient' ORDER BY p.is_featured DESC,p.id ASC")->fetch_all(MYSQLI_ASSOC);
        $products = [];
        foreach ($rows as $row) {
            $products[(int)$row['id']] = [
                'name' => $row['name'], 'price' => (float)$row['price'],
                'type' => trim($row['website_occasion'] ?? '') ?: ($row['category_name'] ?? 'Flowers'),
                'badge' => $row['website_badge'] ?? '',
                'stems' => $row['website_stems'] ?? '',
                'image' => $row['image_url'] ?? '',
                'note' => $row['description'] ?? '',
            ];
        }
        return $products;
    }

    public static function image(string $image): string
    {
        // Product images may be uploaded local paths or HTTPS URLs, never script URLs.
        if (!preg_match('~^https://~i', $image) && !preg_match('~^(?:assets/|/)[A-Za-z0-9_./% -]+$~', $image)) {
            $image = 'assets/img/flower-placeholder.svg';
        }
        return htmlspecialchars($image, ENT_QUOTES, 'UTF-8');
    }

    public static function save(int $id): void
    {
        global $conn;
        $badge = $_POST['website_badge'] ?? '';
        $occasion = $_POST['website_occasion'] ?? '';
        $stems = $_POST['website_stems'] ?? '';
        if (!is_string($badge) || !in_array($badge, self::BADGES, true)) throw new DomainException('Choose a valid website badge.');
        if (!is_string($occasion) || mb_strlen($occasion)>80 || !is_string($stems) || mb_strlen($stems)>255) throw new DomainException('Website occasion or stem details are too long.');
        $stmt = $conn->prepare('UPDATE products SET website_badge=?,website_occasion=?,website_stems=? WHERE id=?');
        $occasion = trim($occasion); $stems = trim($stems);
        $stmt->bind_param('sssi', $badge, $occasion, $stems, $id);
        $stmt->execute();
    }
}
