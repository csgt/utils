<?php

namespace Csgt\Utils\Tests\Feature;

use Csgt\Utils\Menu;
use Csgt\Utils\Tests\Fixtures\User;
use Csgt\Utils\Tests\TestCase;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Menu collects the routes a user may see into static properties. Anything that
 * keeps the class alive between requests — Octane, RoadRunner, a queue worker —
 * reuses those properties, so they have to be scoped to a single build. This
 * regression already shipped once: it was fixed on 8.x in v8.0.12 and the fix
 * was never carried over to 9.x or 10.x.
 */
class MenuTest extends TestCase
{
    protected function defineEnvironment($app)
    {
        parent::defineEnvironment($app);

        $app['config']->set('database.default', 'testing');
        $app['config']->set('auth.providers.users.model', User::class);
    }

    protected function setUp(): void
    {
        parent::setUp();

        Schema::create('users', function (Blueprint $table) {
            $table->increments('id');
            $table->string('email');
            $table->string('password')->nullable();
            $table->text('roles')->nullable();
        });

        Schema::create('menus', function (Blueprint $table) {
            $table->increments('id');
            $table->string('name');
            $table->string('route')->nullable();
            $table->string('parent_route')->nullable();
            $table->boolean('has_children')->default(false);
            $table->string('icon')->nullable();
            $table->integer('order')->default(0);
        });

        Schema::create('role_module_permissions', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('role_id');
            $table->string('module_permission');
        });

        $this->seedMenu();
    }

    public function test_a_user_only_sees_the_routes_their_roles_grant(): void
    {
        $this->actingAs($this->userWithRoutes(['admin.catalogs.users.index']));

        $this->assertSame(
            ['admin.catalogs', 'admin.catalogs.users.index'],
            Menu::menuForRole()->pluck('route')->sort()->values()->all()
        );
    }

    public function test_a_user_with_no_roles_sees_nothing(): void
    {
        $this->actingAs($this->userWithRoutes([]));

        $this->assertSame([], Menu::menuForRole()->pluck('route')->all());
    }

    public function test_one_users_routes_do_not_leak_into_the_next_users_menu(): void
    {
        $admin = $this->userWithRoutes(['admin.catalogs.users.index', 'admin.reports.sales.index']);
        $stranger = $this->userWithRoutes([]);

        $this->actingAs($admin);
        $this->assertNotEmpty(Menu::menuForRole(), 'the admin should see what they were granted');

        // Same process, second user. Without the reset the admin's routes are
        // still in the static properties and the stranger inherits the lot.
        $this->actingAs($stranger);

        $this->assertSame([], Menu::menuForRole()->pluck('route')->all());
    }

    public function test_a_narrower_role_does_not_inherit_the_previous_users_parents(): void
    {
        $this->actingAs($this->userWithRoutes(['admin.catalogs.users.index']));
        Menu::menuForRole();

        $this->actingAs($this->userWithRoutes(['admin.reports.sales.index']));

        $this->assertSame(
            ['admin.reports', 'admin.reports.sales.index'],
            Menu::menuForRole()->pluck('route')->sort()->values()->all()
        );
    }

    private function seedMenu(): void
    {
        $rows = [
            ['name' => 'Catalogs', 'route' => 'admin.catalogs', 'parent_route' => null, 'has_children' => true],
            ['name' => 'Users', 'route' => 'admin.catalogs.users.index', 'parent_route' => 'admin.catalogs'],
            ['name' => 'Roles', 'route' => 'admin.catalogs.roles.index', 'parent_route' => 'admin.catalogs'],
            ['name' => 'Reports', 'route' => 'admin.reports', 'parent_route' => null, 'has_children' => true],
            ['name' => 'Sales', 'route' => 'admin.reports.sales.index', 'parent_route' => 'admin.reports'],
        ];

        foreach ($rows as $order => $row) {
            \App\Models\Menu\Menu::create($row + ['order' => $order, 'has_children' => $row['has_children'] ?? false]);
        }
    }

    /**
     * Every user gets a role of their own, so granting routes to one cannot
     * widen what another already sees.
     */
    private function userWithRoutes(array $routes): User
    {
        static $nextRole = 1;

        $roleId = $nextRole++;

        foreach ($routes as $route) {
            \DB::table('role_module_permissions')->insert([
                'role_id' => $roleId,
                'module_permission' => $route,
            ]);
        }

        return User::create([
            'email' => 'user'.$roleId.'@example.test',
            'roles' => json_encode([$roleId]),
        ]);
    }
}
