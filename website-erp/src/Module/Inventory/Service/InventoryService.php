<?php

namespace App\Module\Inventory\Service;

class InventoryService
{
    public function reserveStock(int $orderId): void
    {
        dump("Stock reserve pour commande $orderId");
    }
}
