<?php

namespace Csgt\Utils;

use App\Models\Menu\Menu as MMenu;
use Csgt\Menu\Menu as MenuC;
use Illuminate\Support\Collection;
use Request;

class Menu
{
    protected static $parents = [];

    protected static $menuRoutes = [];

    public static function getParents($aRoute)
    {
        if (! empty(self::$parents[$aRoute])) {
            self::$menuRoutes[] = self::$parents[$aRoute];
            self::getParents(self::$parents[$aRoute]);
        }
    }

    public static function menuForRole()
    {
        self::$parents = [];
        self::$menuRoutes = [];

        $userRoles = auth()->user()->roleIds();

        $menus = MMenu::select('parent_route', 'route')->get();

        foreach ($menus as $menu) {
            self::$parents[$menu->route] = $menu->parent_route;
        }

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

        $permissions = MMenu::query()
            ->whereIn('route', self::$menuRoutes)
            ->orderBy('parent_route')
            ->orderBy('order')
            ->get()
            ->map(function ($menu) {
                return [
                    'name' => $menu->name,
                    'route' => $menu->route,
                    'icon' => $menu->icon,
                    'parent_route' => $menu->parent_route,
                    'has_children' => $menu->has_children,
                ];
            });

        return collect($permissions->unique());
    }

    private static function cachedMenuCollection($cacheKey)
    {
        $items = cache()->get($cacheKey);

        if ($items instanceof \__PHP_Incomplete_Class) {
            cache()->forget($cacheKey);
            $items = null;
        }

        if ($items instanceof Collection) {
            $items = $items->values()->all();
            cache()->forever($cacheKey, $items);
        }

        if ($items !== null && ! is_array($items)) {
            cache()->forget($cacheKey);
            $items = null;
        }

        if ($items === null) {
            $items = self::menuForRole()->values()->all();
            cache()->forever($cacheKey, $items);
        }

        return collect($items);
    }

    public static function menu($cachePrefix = 'menu-collection-')
    {
        $menu = '';

        if (auth()->check()) {
            $id = auth()->id();

            $collection = self::cachedMenuCollection($cachePrefix . $id);

            $route = Request::route()->getName();
            $route = substr($route, 0, strrpos($route, '.')) . '.index';
            session()->put('menu-selected', $route);

            $mc = new MenuC;
            $menu = $mc->getMenu($collection);
        }

        return $menu;
    }

    public static function json()
    {
        $menu = '';

        if (auth()->check()) {
            $id = auth()->id();

            $collection = self::cachedMenuCollection('menu-collection-' . $id);

            $menu = self::children($collection, null);
        }

        $menu = [
            [
                'title' => '',
                'nodes' => $menu,
            ],
            [
                'title' => 'USUARIO',
                'nodes' => [
                    ['id' => 998, 'label' => 'Perfil', 'icon' => 'fa fa-user', 'url' => '/profile', 'children' => []],
                    ['id' => 999, 'label' => 'Cerrar sesión', 'icon' => 'fa fa-sign-out-alt', 'url' => '/logout', 'children' => []],
                ],
            ],
        ];

        return json_encode($menu, JSON_PRETTY_PRINT);
    }

    private static function children($nodes, $parent)
    {
        return $nodes->where('parent_route', $parent)->map(function ($m, $key) use ($nodes) {
            return [
                'id' => $key,
                'label' => $m['name'],
                'icon' => $m['icon'],
                'url' => $m['has_children'] ? null : route($m['route']),
                'children' => self::children($nodes, $m['route'])->values(),
            ];
        });
    }
}