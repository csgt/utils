<?php
namespace Csgt\Utils\Http\Middleware;

use Closure;
use Csgt\Utils\Menu;
use Csgt\Menu\Menu as MenuC;

class MenuMW
{
    public function handle($request, Closure $next)
    {
        if (auth()->check()) {
            if (!session()->has('menu-collection')) {
                $elAuthMenu = new Menu;
                $elAuthMenu->menuForRole();
            }
            $menu           = new MenuC;
            $menuCollection = session()->get('menu-collection');
            $route          = $request->route()->getName();
            $route          = substr($route, 0, strrpos($route, '.')) . '.index';

            session()->put('menu-selected', $route);
            session()->put('menu', $menu->getMenu($menuCollection));
        }

        return $next($request);
    }
}
