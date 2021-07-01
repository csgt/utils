<?php
namespace Csgt\Utils;

use Request;
use Csgt\Menu\Menu as MenuC;
use App\Models\Menu\Menu as MMenu;

class Menu
{
    protected static $parents    = [];
    protected static $menuRoutes = [0];

    public static function getParents($aRoute)
    {
        if (self::$parents[$aRoute]) {
            self::$menuRoutes[] = self::$parents[$aRoute];
            self::getParents(self::$parents[$aRoute]);
        }
    }

    public static function menuForRole()
    {
        $userRoles = auth()->user()->roleIds();

        $menus = MMenu::select('parent_route', 'route')->get();
        //Guardamos un array de parents para solo abrir el dataset una vez
        foreach ($menus as $menu) {
            self::$parents[$menu->route] = $menu->parent_route;
        }

        //Buscamos todos los permisos (sin parents) y agregamos los parents
        $permissions = MMenu::select('menus.route', 'menus.parent_route', 'menus.has_children')
            ->leftJoin('role_module_permissions AS rmp', 'rmp.module_permission', '=', 'menus.route')
            ->whereIn('rmp.role_id', $userRoles)
            ->get();

        foreach ($permissions as $menu) {
            self::$menuRoutes[] = $menu->route;
            if ($menu->parent_route) {
                self::$menuRoutes[] = $menu->parent_route;
                self::getParents($menu->parent_route);
            }
        }

        //Ahora que ya tenemos todos los menuRoutes que necesitamos, hacemos de nuevo el select IN
        $permissions = MMenu::query()
            ->whereIn('route', self::$menuRoutes)
            ->orderBy('parent_route')
            ->orderBy('order')
            ->get()
            ->map(function ($menu) {
                return [
                    'name'         => $menu->name,
                    'route'        => $menu->route,
                    'icon'         => $menu->icon,
                    'parent_route' => $menu->parent_route,
                    'has_children' => $menu->has_children,
                ];
            });

        return collect($permissions->unique());
    }

    public static function menu()
    {
        $menu = '';
        if (auth()->check()) {
            $id = auth()->id();

            $collection = cache()->rememberForever('menu-collection-' . $id, function () {
                return self::menuForRole();
            });

            $route = Request::route()->getName();
            $route = substr($route, 0, strrpos($route, '.')) . '.index';
            session()->put('menu-selected', $route);

            $mc   = new MenuC;
            $menu = $mc->getMenu($collection);
        }

        return $menu;
    }

}
