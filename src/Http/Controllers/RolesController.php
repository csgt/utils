<?php
namespace Csgt\Utils\Http\Controllers;

use App\Models\Auth\Module;
use App\Models\Auth\Role;
use Cancerbero;
use Crypt;
use Csgt\Crud\CrudController;
use Illuminate\Http\Request;

class RolesController extends CrudController {
	public function __construct() {
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
		$this->setPermissions("\Cancerbero::crudPermissions", 'catalogos.roles');
	}

	public function detail(Request $request, $id) {
		$rmpids = [];

		if ($id != 0) {
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
			'role' => $role,
			'modules' => $modules,
		]);
	}

	public function create(Request $request) {
		return $this->edit($request, 0);
	}

	public function edit(Request $request, $id) {
		$breadcrumb = '<ol class="breadcrumb float-sm-right">
            <li class="breadcrumb-item">Catálogos</li>
            <li class="breadcrumb-item"><a href="/catalogos/roles">Roles</a></li>
            <li class="breadcrumb-item active">Rol</li>
        </ol>';

		return view('component')
			->withTitle($this->getTitle())
			->withBreadcrumb($breadcrumb)
			->with('component', 'catalogs-roles-edit');

		// if ($id !== 0) {
		//     $id = Crypt::decrypt($id);
		// }

		// $roleName = 'Nuevo';

		// // $modulePermissions = Module::with(['permissions',
		// //     'role_module_permission' => function ($query) use ($id) {
		// //         return $query->where('role_id', $id);
		// //     },
		// // ])
		// //     ->orderBy('name')
		// //     ->get();

		// $role = Role::find($id);

		// if ($id !== 0) {
		//     $roleName = $role->name;
		// }
		// $breadcrumb = '<ol class="breadcrumb float-sm-right">
		//     <li class="breadcrumb-item">Catálogos</li>
		//     <li class="breadcrumb-item"><a href="/catalogos/roles">Roles</a></li>
		//     <li class="breadcrumb-item active">' . $roleName . '</li>
		// </ol>';

		// return view('catalogs.roles.edit')
		//     ->withData($role)
		//     ->withTitle($this->getTitle())
		//     ->withBreadcrumb($breadcrumb)
		//     ->withTemplate($this->getLayout())
		//     ->withId(($id == 0 ? 0 : Crypt::encrypt($id)));
		// ->withModulePermission($modulePermissions);
	}

	public function store(Request $request) {
		return $this->update($request, 0);
	}

	public function update(Request $request, $id) {
		if ($id !== 0) {
			$rolid = Crypt::decrypt($request->id);
			$rol = Authrol::find($rolid);
			$rol->nombre = $request->nombre;
			$rol->descripcion = $request->descripcion;
			$rol->save();
			Authrolmodulopermiso::where('rolid', $rolid)->delete();
		} else {
			$rol = new Authrol;
			$rol->nombre = $request->nombre;
			$rol->descripcion = $request->descripcion;
			$rol->save();
			$rolid = $rol->rolid;
		}

		$modulopermisos = $request->modulopermisos;

		if ($modulopermisos) {
			foreach ($modulopermisos as $modulopermiso) {
				$authmodulopermiso = new Authrolmodulopermiso;
				$authmodulopermiso->rolid = $rolid;
				$authmodulopermiso->modulopermisoid = $modulopermiso;
				$authmodulopermiso->save();
			}
		}

		return redirect()->route('catalogos.roles.index')
			->with('flashMessage', config('cancerbero::mensajerolmodulopermisoexitoso'))
			->with('flashType', 'success');
	}
}
