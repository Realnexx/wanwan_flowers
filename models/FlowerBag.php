<?php
require_once __DIR__.'/Cart.php';

// The session is a compatibility snapshot; cart rows are the source of truth.
class FlowerBag
{
    public static function read(array $products): array
    {
        $items=Cart::get_items(session_id(),$_SESSION['user']['id']??null);
        $bag=[];
        foreach ($items as $id=>$item) if (isset($products[$id])) $bag[(int)$id]=(int)$item['qty'];
        ksort($bag,SORT_NUMERIC);
        return $bag;
    }

    public static function load(array $products): array
    {
        // Import selections made before database storage was enabled once only.
        if (!isset($_SESSION['flower_bag_database'])) {
            $legacy=array_intersect_key($_SESSION['flower_bag']??[],$products);
            RecipeInventory::atomic(function()use($legacy,$products){
                $user=$_SESSION['user']['id']??null;
                Cart::lock_owner(session_id(),$user);
                $saved=self::read($products);
                foreach ($legacy as $id=>$qty) {
                    $qty=max((int)($saved[$id]??0),min(20,max(1,(int)$qty)));
                    if ($qty!==($saved[$id]??0)) Cart::update((int)$id,$qty,session_id(),$user);
                }
            });
            $_SESSION['flower_bag_database']=true;
        }
        return $_SESSION['flower_bag']=self::read($products);
    }

    public static function change(int $id,string $action,int $quantity,array $products): array
    {
        $bag=RecipeInventory::atomic(function()use($id,$action,$quantity,$products){
            $user=$_SESSION['user']['id']??null;
            Cart::lock_owner(session_id(),$user);
            $saved=self::read($products);
            $current=$saved[$id]??0;
            if ($action==='remove') $next=0;
            elseif ($action==='set_quantity') $next=$quantity;
            elseif ($action==='decrease') $next=max(0,$current-1);
            else $next=min(20,$current+$quantity);
            Cart::update($id,$next,session_id(),$user);
            return self::read($products);
        });
        return $_SESSION['flower_bag']=$bag;
    }

    public static function purchased(array $bag,?int $user): void
    {
        foreach ($bag as $id=>$qty) Cart::remove((int)$id,session_id(),$user);
    }
}
