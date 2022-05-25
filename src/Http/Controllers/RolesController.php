<?php
namespace Csgt\Utils\Http\Controllers;

use DB;
use Cache;
use Crypt;
use Cancerbero;
use App\Models\Auth\Role;
use App\Models\Auth\Module;
use Illuminate\Http\Request;
use Csgt\Crud\CrudController;
use App\Models\Auth\Permission;
use App\Models\Auth\ModulePermission;
use App\Models\Auth\RoleModulePermission;

class RolesController extends CrudController
{
    public $path = '/catalogs/roles';

    public function __construct()
    {
        $this->setModel(new Role);
        $this->setTitle('Roles');
        $this->setField(['name' => 'Nombre', 'field' => 'name']);
        $this->setField(['name' => 'Descripción', 'field' => 'description']);
        $this->middleware(function ($request, $next) {
            if (!Cancerbero::isGod()) {
                $this->setWhere('id', '<>', Cancerbero::godRole());
            }

            return $next($request);
        });
        $this->setPermissions("\Cancerbero::crudPermissions", substr(str_replace('/', '.', $this->path), 1));
    }

    public function detail(Request $request, $id)
    {
        $rmpids = [];
        $role   = ['name' => null, 'description' => null, 'role_module_permissions' => []];

        if ($id !== '0') {
            $id   = Crypt::decrypt($id);
            $role = Role::with('role_module_permissions:id,role_id,module_permission')
                ->findOrFail($id);

            $rmpids = $role->role_module_permissions->map(function ($rmp) {
                return $rmp->module_permission;
            })->toArray();
        }

        $permissions = Permission::orderBy('name')->get();
        $modules     = Module::orderBy('name')->get();

        $modulepermissions = ModulePermission::query()
            ->orderBy('name')
            ->get()
            ->map(function ($mp) use ($modules, $permissions, $rmpids) {
                $mp->m = $modules->first(function ($m) use ($mp) {
                    return $m->name == $mp->module;
                });

                $mp->p = $permissions->first(function ($p) use ($mp) {
                    return $p->name == $mp->permission;
                });

                $mp->enabled = in_array($mp->name, $rmpids);

                return $mp;
            })
            ->sortBy('m.description')
            ->groupBy('m.description');

        return response()->json([
            'role'              => $role,
            'modulepermissions' => $modulepermissions,
        ]);
    }

    public function create(Request $request)
    {
        return $this->edit($request, 0);
    }

    public function edit(Request $request, $id)
    {
        $breadcrumb = '<ol class="breadcrumb float-sm-right">
            <li class="breadcrumb-item">Catálogos</li>
            <li class="breadcrumb-item"><a href="' . $this->path . '">Roles</a></li>
            <li class="breadcrumb-item active">Rol</li>
        </ol>';

        $params = [
            'id'   => $id,
            'path' => $this->path,
        ];

        return view('component')
            ->withTitle($this->getTitle())
            ->withBreadcrumb($breadcrumb)
            ->with('params', $params)
            ->with('props', $params)
            ->with('component', 'catalogs-roles-edit');
    }

    public function store(Request $request)
    {
        return $this->update($request, 0);
    }

    public function update(Request $request, $id)
    {
        $rules = [
            'role.name'        => 'required',
            'role.description' => 'required',
        ];

        $messages = [
            'role.name.required'        => 'El nombre es requerido',
            'role.description.required' => 'La descripción es requerida',
        ];

        $request->validate($rules, $messages);

        DB::transaction(function () use ($request, $id) {

            if ($id !== 0) {
                $roleid = Crypt::decrypt($id);
                $role   = Role::findOrFail($roleid);

            } else {
                $role = new Role;
            }

            $role->name        = $request->role['name'];
            $role->description = $request->role['description'];
            $role->save();

            RoleModulePermission::where('role_id', $role->id)->delete();

            foreach ($request->modulepermissions as $modules) {
                foreach ($modules as $mp) {
                    if ($mp['enabled']) {
                        $rmp                    = new RoleModulePermission;
                        $rmp->role_id           = $role->id;
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
