<?php

namespace App\Module\Crm\Controller;

use App\Core\CoreAPI;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class CrmController extends AbstractController
{
    #[Route('/modules/crm', name: 'module_crm')]
    public function index(CoreAPI $core): Response
    {
        $core->bootModules();
        $module = null;

        foreach ($core->modules() as $item) {
            if ($item->name === 'CRM') {
                $module = $item;
                break;
            }
        }

        return $this->render('module/crm.html.twig', [
            'module' => $module,
        ]);
    }
}
