<?php
namespace Csgt\Utils;

use DB;
use Cache;
use Request;
use Csgt\Menu\Menu as MenuC;
use App\Models\Menu\Menu as MMenu;

class Menu
{
    protected static $parents = [];
    protected static $menuIds = [0];

    public static function getParents($aMenuId)
    {
        if (self::$parents[$aMenuId] != 0) {
            self::$menuIds[] = self::$parents[$aMenuId];
            self::getParents(self::$parents[$aMenuId]);
        }
    }

    public static function menuForRole()
    {

        $userRoles = auth()->user()->roleIds();

        $menus = MMenu::select('parent_id', 'id')->get();
        //Guardamos un array de parents para solo abrir el dataset una vez
        foreach ($menus as $menu) {
            self::$parents[$menu->id] = (int) $menu->parent_id;
        }

        //Buscamos todos los permisos (sin parents) y agregamos los parents
        $permissions = MMenu::select('menus.id', DB::raw('coalesce(menus.parent_id,0) AS parent_id'))
            ->leftJoin('role_module_permissions AS rmp', 'rmp.module_permission_id', '=', 'menus.module_permission_id')
            ->leftJoin('module_permissions AS mp', 'mp.id', '=', 'rmp.module_permission_id')
            ->leftJoin('modules AS mo', 'mo.id', '=', 'mp.module_id')
            ->leftJoin('permissions AS p', 'p.id', '=', 'mp.permission_id')
            ->whereIn('rmp.role_id', $userRoles)
            ->get();

        foreach ($permissions as $menu) {
            self::$menuIds[] = $menu->id;
            if ($menu->parent_id != 0) {
                self::$menuIds[] = $menu->parent_id;
                self::getParents($menu->parent_id);
            }
        }

        //Ahora que ya tenemos todos los menuids que necesitamos, hacemos de nuevo el select IN
        $arr         = [];
        $permissions = MMenu::select('menus.name', DB::raw("CONCAT(mo.name,'.',p.name) AS route"),
            'menus.parent_id', 'menus.id', 'menus.icon')
            ->leftJoin('role_module_permissions AS rmp', 'rmp.module_permission_id', '=', 'menus.module_permission_id')
            ->leftJoin('module_permissions AS mp', 'mp.id', '=', 'rmp.module_permission_id')
            ->leftJoin('modules AS mo', 'mo.id', '=', 'mp.module_id')
            ->leftJoin('permissions AS p', 'p.id', '=', 'mp.permission_id')
            ->whereIn('menus.id', self::$menuIds)
            ->orderBy('menus.parent_id')
            ->orderBy('menus.order')
            ->get()
            ->map(function ($menu) {
                return [
                    'name'      => $menu->name,
                    'route'     => $menu->route,
                    'icon'      => $menu->icon,
                    'parent_id' => (int) $menu->parent_id,
                    'id'        => $menu->id,
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
