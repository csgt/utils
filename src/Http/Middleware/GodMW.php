<?php
namespace Csgt\Utils\Http\Middleware;

use Route;
use Closure;
use Cancerbero;

class GodMW
{
    public function handle($request, Closure $next)
    {
        $cancerbero = new Cancerbero;

        if (!$cancerbero->isGod()) {
            return view('csgtcancerbero::error')->with('mensaje', $request->error . ' (' . Route::currentRouteName() . ')');
        }

        return $next($request);
    }
}
