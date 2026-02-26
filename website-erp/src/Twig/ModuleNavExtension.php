<?php

namespace App\Twig;

use App\Core\CoreAPI;
use App\Core\Security\AuthService;
use App\Repository\UserRepository;
use Symfony\Component\Twig\Attribute\AsTwigExtension;
use Twig\Extension\AbstractExtension;
use Twig\Extension\GlobalsInterface;

#[AsTwigExtension]
class ModuleNavExtension extends AbstractExtension implements GlobalsInterface
{
    public function __construct(
        private CoreAPI $core,
        private AuthService $auth,
        private UserRepository $users,
    ) {}

    public function getGlobals(): array
    {
        $this->core->bootModules();
        $modules = $this->core->modules();
        $user = $this->auth->getCurrentUser();
        $userRoles = $user?->roles ?? [];
        $isAdmin = in_array('ROLE_ADMIN', $userRoles, true);
        $rolesInDb = $this->collectRoles();

        $navModules = [];

        foreach ($modules as $module) {
            if (!$module->enabled) {
                continue;
            }

            $roles = $module->roles ?? [];
            if (count($roles) > 0) {
                if ($isAdmin) {
                    // Admin always has access to enabled modules.
                    $roles = [];
                } elseif ($user === null) {
                    continue;
                } elseif (count(array_intersect($roles, $userRoles)) === 0) {
                    continue;
                }
            }

            $slug = $this->slugify($module->name);
            $navModules[] = [
                'name' => $module->name,
                'slug' => $slug,
                'route' => 'module_' . $slug,
                'roles' => $roles,
                'dependencies' => $module->dependencies ?? [],
            ];
        }

        return [
            'nav_modules' => $navModules,
            'all_modules' => $modules,
            'roles_in_db' => $rolesInDb,
            'is_admin' => $isAdmin,
        ];
    }

    private function slugify(string $name): string
    {
        $slug = preg_replace('/([a-z])([A-Z])/', '$1-$2', $name);
        $slug = strtolower((string) $slug);
        $slug = preg_replace('/[^a-z0-9]+/', '-', (string) $slug);
        return trim((string) $slug, '-');
    }

    /**
     * @return string[]
     */
    private function collectRoles(): array
    {
        $roles = [];
        foreach ($this->users->findAll() as $user) {
            foreach ($user->getRoles() as $role) {
                $roles[$role] = true;
            }
        }

        $roles = array_keys($roles);
        sort($roles);

        return $roles;
    }
}
