<?php
namespace App\Module\Inventory\EventSubscriber;

use App\Core\Event\OrderConfirmedEvent;
use App\Module\Inventory\Service\InventoryService;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class OrderConfirmedSubscriber implements EventSubscriberInterface
{
    public function __construct(private InventoryService $inventoryService) {}

    public static function getSubscribedEvents(): array
    {
        return [
            OrderConfirmedEvent::class => 'onOrderConfirmed',
        ];
    }

    public function onOrderConfirmed(OrderConfirmedEvent $event): void
    {
        $this->inventoryService->reserveStock($event->orderId);
    }
}
