<?php
namespace Csgt\Utils;

use DB;
use Request;
use Csgt\Menu\Menu as MenuC;
use App\Models\Menu\Authmenu as MMenu;

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

        $menus = MMenu::select('padreid', 'menuid')->get();
        //Guardamos un array de parents para solo abrir el dataset una vez
        foreach ($menus as $menu) {
            self::$parents[$menu->menuid] = (int) $menu->padreid;
        }

        //Buscamos todos los permisos (sin parents) y agregamos los parents
        $permissions = MMenu::select('authmenu.menuid', DB::raw('coalesce(authmenu.padreid,0) AS padreid'))
            ->leftJoin('authrolmodulopermisos AS rmp', 'rmp.modulopermisoid', '=', 'authmenu.modulopermisoid')
            ->leftJoin('authmodulopermisos AS mp', 'mp.modulopermisoid', '=', 'rmp.modulopermisoid')
            ->leftJoin('authmodulos AS mo', 'mo.moduloid', '=', 'mp.moduloid')
            ->leftJoin('authpermisos AS p', 'p.permisoid', '=', 'mp.permisoid')
            ->whereIn('rmp.rolid', $userRoles)
            ->get();

        foreach ($permissions as $menu) {
            self::$menuIds[] = $menu->menuid;
            if ($menu->padreid != 0) {
                self::$menuIds[] = $menu->padreid;
                self::getParents($menu->padreid);
            }
        }

        //Ahora que ya tenemos todos los menuids que necesitamos, hacemos de nuevo el select IN
        $permissions = MMenu::select('authmenu.nombre', DB::raw("CONCAT(mo.nombre,'.',p.nombre) AS ruta"),
            'authmenu.padreid', 'authmenu.menuid', 'authmenu.icono')
            ->leftJoin('authrolmodulopermisos AS rmp', 'rmp.modulopermisoid', '=', 'authmenu.modulopermisoid')
            ->leftJoin('authmodulopermisos AS mp', 'mp.modulopermisoid', '=', 'rmp.modulopermisoid')
            ->leftJoin('authmodulos AS mo', 'mo.moduloid', '=', 'mp.moduloid')
            ->leftJoin('authpermisos AS p', 'p.permisoid', '=', 'mp.permisoid')
            ->whereIn('authmenu.menuid', self::$menuIds)
            ->orderBy('authmenu.padreid')
            ->orderBy('authmenu.orden')
            ->get()
            ->map(function ($menu) {
                return [
                    'nombre'  => $menu->nombre,
                    'ruta'    => $menu->ruta,
                    'icono'   => $menu->icono,
                    'padreid' => (int) $menu->padreid,
                    'menuid'  => $menu->menuid,
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
            $menu = $mc->generarMenu($collection);
        }

        return $menu;
    }

}
