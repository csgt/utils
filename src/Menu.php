<?php
namespace Csgt\Utils;

use DB;
use Auth;
use Session;
use App\Models\Menu as MMenu;

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
        $permissions = MMenu::select('menu.id', DB::raw('coalesce(menu.parent_id,0) AS parent_id'))
            ->leftJoin('role_module_permissions AS rmp', 'rmp.module_permission_id', '=', 'menu.module_permission_id')
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
        $permissions = MMenu::select('menu.name', DB::raw("CONCAT(mo.name,'.',p.name) AS route"),
            'menu.parent_id', 'menu.id', 'menu.icon')
            ->leftJoin('role_module_permissions AS rmp', 'rmp.module_permission_id', '=', 'menu.module_permission_id')
            ->leftJoin('module_permissions AS mp', 'mp.id', '=', 'rmp.module_permission_id')
            ->leftJoin('modules AS mo', 'mo.id', '=', 'mp.module_id')
            ->leftJoin('permissions AS p', 'p.id', '=', 'mp.permission_id')
            ->whereIn('menu.id', $this->menuIds)
            ->orderBy('menu.parent_id')
            ->orderBy('menu.order')
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
