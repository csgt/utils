<?php
namespace Csgt\Utils\Http\Controllers;

use DB;
use Crypt;
use Cancerbero;
use App\Models\Auth\Role;
use App\Models\Auth\Module;
use Illuminate\Http\Request;
use Csgt\Crud\CrudController;
use App\Models\Auth\RoleModulePermission;

class RolesController extends CrudController
{
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
        $this->setPermissions("\Cancerbero::crudPermissions", 'catalogs.roles');
    }

    public function detail(Request $request, $id)
    {
        $rmpids = [];
        $role   = ['name' => null, 'description' => null];

        if ($id !== '0') {
            $id   = Crypt::decrypt($id);
            $role = Role::with('role_module_permissions:id,role_id,module_permission_id')
                ->findOrFail($id);

            $rmpids = $role->role_module_permissions->map(function ($rmp) {
                return $rmp->module_permission_id;
            })->toArray();
        }

        $modules = Module::query()
            ->with([
                'modulepermissions',
                'modulepermissions.permission' => function ($query) {
                    $query->orderBy('name');
                },
            ])
            ->orderBy('name')
            ->get();

        $modules = $modules->map(function ($module) use ($rmpids) {
            $mp = $module->modulepermissions->map(function ($mp) use ($rmpids) {
                $mp->enabled = in_array($mp->id, $rmpids);

                return $mp;
            });
            $module->modulepermissions = $mp;

            return $module;
        });

        return response()->json([
            'role'    => $role,
            'modules' => $modules,
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
            <li class="breadcrumb-item"><a href="/catalogos/roles">Roles</a></li>
            <li class="breadcrumb-item active">Rol</li>
        </ol>';

        $params = ['id' => $id];

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

                //Authrolmodulopermiso::where('roleid', $roleid)->delete();
            } else {
                $role = new Role;
            }

            $role->name        = $request->role['name'];
            $role->description = $request->role['description'];
            $role->save();

            RoleModulePermission::where('role_id', $role->id)->delete();

            foreach ($request->modules as $module) {
                foreach ($module['modulepermissions'] as $mp) {
                    if ($mp['enabled']) {
                        $rmp                       = new RoleModulePermission;
                        $rmp->role_id              = $role->id;
                        $rmp->module_permission_id = $mp['id'];
                        $rmp->save();
                    }
                }
            }
        });

        return response()->json('ok');
    }
}
