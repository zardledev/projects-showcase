<?php

namespace App\Module\Inventory\Controller;

use App\Core\CoreAPI;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class InventoryController extends AbstractController
{
    #[Route('/modules/stock', name: 'module_stock')]
    public function index(CoreAPI $core): Response
    {
        $core->bootModules();
        $module = null;

        foreach ($core->modules() as $item) {
            if ($item->name === 'Stock') {
                $module = $item;
                break;
            }
        }

        return $this->render('module/stock.html.twig', [
            'module' => $module,
        ]);
    }
}
