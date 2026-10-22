<?php

namespace Csgt\Utils\Tests\Fixtures;

use Illuminate\Foundation\Auth\User as Authenticatable;

class User extends Authenticatable
{
    protected $table = 'users';

    public $timestamps = false;

    protected $guarded = [];

    /**
     * The contract Csgt\Utils\Menu relies on: whatever roles this user holds.
     */
    public function roleIds(): array
    {
        return json_decode($this->roles ?? '[]', true);
    }
}
