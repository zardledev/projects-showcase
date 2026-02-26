<?php

namespace App\Module\Finance\Controller;

use App\Core\CoreAPI;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class FinanceController extends AbstractController
{
    #[Route('/modules/finance', name: 'module_finance')]
    public function index(CoreAPI $core): Response
    {
        $core->bootModules();
        $module = null;

        foreach ($core->modules() as $item) {
            if ($item->name === 'Finance') {
                $module = $item;
                break;
            }
        }

        return $this->render('module/finance.html.twig', [
            'module' => $module,
        ]);
    }
}
