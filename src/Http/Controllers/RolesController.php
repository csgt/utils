<?php

namespace Csgt\Utils\Http\Controllers;

use App\Models\Module;
use App\Models\Permission;
use App\Models\Role;
use App\Models\RoleModulePermission;
use App\Models\User;
use App\Models\UserRole;
use Csgt\Cancerbero\Cancerbero;
use Csgt\Cancerbero\Models\ModulePermission;
use Csgt\Crud\CrudController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class RolesController extends CrudController
{
    public $path = '/catalogs/roles';

    public $userPath = '/catalogs/users';

    public function setup(Request $request)
    {
        $this->setModel(new Role());
        $this->setTitle('Roles');
        $this->setField(['name' => 'Nombre', 'field' => 'name']);
        $this->setField(['name' => 'Descripción', 'field' => 'description']);
        $this->setField([
            'name' => 'Usuarios',
            'field' => '(SELECT COUNT(*) FROM user_roles WHERE role_id = roles.id) AS users_count',
            'isforeign' => false,
            'class' => 'text-end',
        ]);
        if (! Cancerbero::isGod()) {
            $this->setWhere('id', '<>', Cancerbero::godRole());
        }
        $this->setPermissions(Cancerbero::crudPermissions(substr(str_replace('/', '.', $this->path), 1)));
    }

    public function detail(Request $request, $id)
    {
        $rmpids = [];
        $role = ['name' => null, 'description' => null];
        if ($id !== '0') {
            $role = Role::query()
                ->with('role_module_permissions:id,role_id,module_permission')
                ->findOrFail($id);

            $rmpids = $role->role_module_permissions->map(function ($rmp) {
                return $rmp->module_permission;
            })->toArray();
        }

        $modules = Module::query()
            ->orderBy('name')
            ->get();

        $permissions = Permission::orderBy('name')->get();

        $modules = $modules->map(function ($module) use ($rmpids, $permissions) {
            $module_permissions = ModulePermission::orderBy('name')
                ->get()
                ->map(function ($mp) use ($module, $rmpids, $permissions) {
                    $arr = explode('.', $mp->name);
                    $permission = array_pop($arr);
                    $mp->description = $permissions->where('name', $permission)->first()->description;

                    if ($mp->module == $module->name) {
                        $mp->enabled = in_array($mp->name, $rmpids);

                        return $mp;
                    }

                    return null;
                })
                ->filter()
                ->values();
            $module->modulepermissions = $module_permissions;

            return $module;
        });

        $users = User::query()
            ->select('id', 'name')
            ->where('active', true)
            ->whereIn('id', UserRole::where('role_id', $id)->pluck('user_id'))
            ->get();

        return response()->json([
            'role' => $role,
            'users' => $users,
            'modules' => $modules,
        ]);
    }

    public function create(Request $request)
    {
        return $this->edit($request, 0);
    }

    public function edit(Request $request, $id)
    {
        $breadcrumb = '<ol class="breadcrumb float-sm-end">
            <li class="breadcrumb-item">Catálogos</li>
            <li class="breadcrumb-item"><a href="'.$this->path.'">Roles</a></li>
            <li class="breadcrumb-item active">Rol</li>
        </ol>';

        $params = [
            'id' => $id,
            'path' => $this->path,
            'userpath' => url($this->userPath),
        ];

        return view('component')
            ->withTitle($this->getTitle())
            ->withBreadcrumb($breadcrumb)
            ->with('params', $params)
            ->with('component', 'catalogs-roles-edit');
    }

    public function store(Request $request)
    {
        return $this->update($request, 0);
    }

    public function update(Request $request, $id)
    {
        $rules = [
            'role.name' => 'required',
            'role.description' => 'required',
        ];

        $messages = [
            'role.name.required' => 'El nombre es requerido',
            'role.description.required' => 'La descripción es requerida',
        ];

        $request->validate($rules, $messages);

        DB::transaction(function () use ($request, $id) {

            if ($id !== 0) {
                $roleid = $id;
                $role = Role::findOrFail($roleid);

                // Authrolmodulopermiso::where('roleid', $roleid)->delete();
            } else {
                $role = new Role();
            }

            $role->name = $request->role['name'];
            $role->description = $request->role['description'];
            $role->save();

            RoleModulePermission::where('role_id', $role->id)->delete();
            foreach ($request->modules as $module) {
                foreach ($module['modulepermissions'] as $mp) {
                    if ($mp['enabled']) {
                        $rmp = new RoleModulePermission();
                        $rmp->role_id = $role->id;
                        $rmp->module_permission = $mp['name'];
                        $rmp->save();
                    }
                }
            }
        });

        Cache::flush();

        return response()->json('ok');
    }
}
