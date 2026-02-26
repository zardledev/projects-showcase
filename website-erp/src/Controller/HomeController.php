<?php

namespace App\Controller;

use App\Core\CoreAPI;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class HomeController extends AbstractController
{
    #[Route('/', name: 'home')]
    public function index(CoreAPI $core): Response
    {
        $core->bootModules();

        return $this->render('views/home.html.twig', [
            'title' => 'ERP Core',
            'user' => 'Julian',
            'modules' => $core->modules(),
        ]);
    }
}
