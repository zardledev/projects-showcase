<?php

namespace App\Controller;

use App\Core\CoreAPI;
use App\Core\Event\OrderConfirmedEvent;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class OrderController extends AbstractController
{
    #[Route('/order/confirm/{id}')]
    public function confirm(int $id, CoreAPI $core): Response
    {
        $core->emit(new OrderConfirmedEvent($id));

        return new Response('Commande confirmee');
    }
}
