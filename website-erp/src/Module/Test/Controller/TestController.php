<?php

namespace App\Module\Test\Controller;

use App\Core\CoreAPI;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class TestController extends AbstractController
{
    #[Route('/modules/test', name: 'module_test')]
    public function index(CoreAPI $core): Response
    {
        $core->bootModules();
        $module = null;

        foreach ($core->modules() as $item) {
            if ($item->name === 'Test') {
                $module = $item;
                break;
            }
        }

        return $this->render('module/test.html.twig', [
            'module' => $module,
        ]);
    }
}