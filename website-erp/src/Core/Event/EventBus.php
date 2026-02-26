<?php

namespace App\Core\Event;

use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

class EventBus
{
    public function __construct(private EventDispatcherInterface $dispatcher) {}

    public function dispatch(object $event): void
    {
        $this->dispatcher->dispatch($event);
    }
}
