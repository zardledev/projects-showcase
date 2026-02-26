<?php

namespace App\Controller;

use App\Core\CoreAPI;
use App\Core\Module\ModuleConfigManager;
use App\Repository\UserRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Csrf\CsrfToken;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;
use Symfony\Component\Process\Process;

class AdminModuleController extends AbstractController
{
    #[Route('/admin/modules', name: 'admin_modules', methods: ['GET'])]
    public function index(CoreAPI $core, UserRepository $users): Response
    {
        $core->bootModules();

        return $this->render('admin/modules.html.twig', [
            'modules' => $core->modules(),
            'roles' => $this->collectRoles($users),
        ]);
    }

    #[Route('/admin/modules/toggle', name: 'admin_module_toggle', methods: ['POST'])]
    public function toggle(
        Request $request,
        ModuleConfigManager $config,
        CsrfTokenManagerInterface $csrf,
    ): RedirectResponse {
        $token = new CsrfToken('module_toggle', (string) $request->request->get('_token'));
        if (!$csrf->isTokenValid($token)) {
            $this->addFlash('error', 'Token invalide.');
            return $this->redirectToRoute('admin_modules');
        }

        $name = (string) $request->request->get('name');
        $enabled = (bool) $request->request->get('enabled');
        if ($name === '') {
            $this->addFlash('error', 'Module invalide.');
            return $this->redirectToRoute('admin_modules');
        }

        $config->setEnabled($name, $enabled);
        if (!$this->refreshCache()) {
            $this->addFlash('warning', 'Cache non regeneree. Le changement peut ne pas etre visible immediatement.');
        }
        $this->addFlash('success', $enabled ? 'Module active.' : 'Module desactive.');

        return $this->redirectToRoute('admin_modules');
    }

    /**
     * @return string[]
     */
    private function collectRoles(UserRepository $users): array
    {
        $roles = [];
        foreach ($users->findAll() as $user) {
            foreach ($user->getRoles() as $role) {
                $roles[$role] = true;
            }
        }

        $roles = array_keys($roles);
        sort($roles);

        return $roles;
    }

    private function refreshCache(): bool
    {
        $projectDir = (string) $this->getParameter('kernel.project_dir');
        $console = $projectDir . DIRECTORY_SEPARATOR . 'bin' . DIRECTORY_SEPARATOR . 'console';
        if (!is_file($console)) {
            return false;
        }

        $clear = new Process([PHP_BINARY, $console, 'cache:clear', '--no-warmup', '--no-interaction']);
        $clear->setTimeout(300);
        $clear->run();
        if (!$clear->isSuccessful()) {
            return false;
        }

        $warmup = new Process([PHP_BINARY, $console, 'cache:warmup', '--no-interaction']);
        $warmup->setTimeout(300);
        $warmup->run();
        return $warmup->isSuccessful();
    }
}



