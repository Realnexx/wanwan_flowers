<?php
require_once __DIR__.'/Product.php';
class PublicCatalog {
 public static function visible($p){return $p && !empty($p['show_on_website']) && empty($p['deleted_at']) && strtolower($p['status']??'')==='active' && !empty($p['is_sellable']) && empty($p['is_ingredient']) && ($p['product_type']??'')!=='ingredient';}
 public static function products($category=null,$search=null){return array_values(array_filter(Product::get_all_products($category,$search),[__CLASS__,'visible']));}
 public static function categories(){global $conn;return $conn->query("SELECT DISTINCT c.id,c.name FROM categories c JOIN products p ON p.category_id=c.id WHERE p.show_on_website=1 AND p.status='active' AND p.is_sellable=1 AND p.is_ingredient=0 AND p.product_type<>'ingredient' ORDER BY c.name")->fetch_all(MYSQLI_ASSOC);}
 public static function save($id){require_once __DIR__.'/FlowerCatalog.php';FlowerCatalog::save((int)$id);$visible=empty($_POST['show_on_website'])?0:1;RecipeInventory::query('UPDATE products SET show_on_website=? WHERE id=?','ii',[$visible,$id]);}
}
