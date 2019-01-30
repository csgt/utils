<?php
namespace Csgt\Utils;

use DB;
use Auth;
use Session;
use App\Models\Menu\Menu as MMenu;

class Menu
{
    protected $parents = [];
    protected $menuIds = [0];

    public function parents($aMenuId)
    {
        if ($this->parents[$aMenuId] != 0) {
            $this->menuIds[] = $this->parents[$aMenuId];
            $this->parents($this->parents[$aMenuId]);
        }
    }

    public function menuForRole()
    {

        $userRoles = Auth::user()->roleIds();

        $menus = MMenu::select('parent_id', 'id')->get();
        //Guardamos un array de parents para solo abrir el dataset una vez
        foreach ($menus as $menu) {
            $this->parents[$menu->id] = (int) $menu->parent_id;
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
            $this->menuIds[] = $menu->id;
            if ($menu->parent_id != 0) {
                $this->menuIds[] = $menu->parent_id;
                $this->parents($menu->parent_id);
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
            ->whereIn('menus.id', $this->menuIds)
            ->whereIn('rmp.role_id', $userRoles)
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

        Session::put('menu-collection', collect($permissions));
    }

}
