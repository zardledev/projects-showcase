<?php
namespace App\Core\Event;

class OrderConfirmedEvent
{
    public function __construct(public readonly int $orderId) {}
}
