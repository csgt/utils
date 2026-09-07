<?php

namespace App\Models\Menu;

use Illuminate\Database\Eloquent\Model;

/**
 * Csgt\Utils\Menu reaches for App\Models\Menu\Menu, which every consuming
 * application publishes with make:csgtutils. The tests provide their own so the
 * package can exercise the menu without a host application.
 */
class Menu extends Model
{
    protected $table = 'menus';

    public $timestamps = false;

    protected $guarded = [];
}
