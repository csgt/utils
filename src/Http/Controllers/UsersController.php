<?php
namespace Csgt\Utils\Http\Controllers;

use App\Models\Auth\User;
use App\Models\Cancerbero\Authrol;
use App\Models\Cancerbero\Authusuariorol;
use Cancerbero;
use Crypt;
use Csgt\Crud\CrudController;
use Illuminate\Http\Request;

class UsersController extends CrudController {
	public function __construct() {
		$this->setModel(new User);
		$this->setTitle('Usuarios');
		$this->setBreadCrumb([
			['url' => '', 'title' => 'Catálogos', 'icon' => 'fa fa-book'],
			['url' => '', 'title' => 'Usuarios', 'icon' => 'fa fa-user'],
		]);

		$this->setField(['name' => 'Nombre', 'field' => 'name']);
		$this->setField(['name' => 'Email', 'field' => 'email']);
		$this->setField(['name' => 'Creado', 'field' => 'created_at', 'type' => 'datetime']);
		$this->setField(['name' => 'Activo', 'field' => 'active', 'type' => 'bool']);
		$this->setPermisions("\Cancerbero::crudPermissions", 'catalogs.users');
	}

	public function edit(Request $request, $id) {
		$data = [];
		$usuarioroles = [];
		$nombreUsuario = "Nuevo";

		if ($id !== 0) {
			$usuarioid = Crypt::decrypt($id);
			$data = User::find($usuarioid);
			$usuarioroles = Authusuariorol::where('usuarioid', $usuarioid)
				->pluck('rolid')->toArray();
			$nombreUsuario = $data->nombre;
		}

		$roles = Authrol::orderBy('nombre');

		if (!Cancerbero::isGod()) {
			$roles->where('rolid', '<>', config('csgtcancerbero.rolbackdoor'));
		}
		$roles = $roles->get();

		$breadcrumb = '<ol class="breadcrumb">
			<li>Catálogos</li>
			<li><a href="/catalogos/usuarios">Usuarios</a></li>
			<li class="active">' . $nombreUsuario . '</li>
		</ol>';

		return view('catalogos.usuarios.edit')
			->with('templateincludes', ['selectize', 'formvalidation'])
			->with('template', config('csgtcomponents.template', 'layouts.app'))
			->with('breadcrumb', $breadcrumb)
			->with('roles', $roles)
			->with('data', $data)
			->with('usuarioroles', $usuarioroles)
			->with('id', $id);
	}

	public function create(Request $request) {
		return $this->edit($request, 0);
	}

	public function update(Request $request, $id) {
		if ($id !== 0) {
			$usuarioid = Crypt::decrypt($id);
			$usuario = User::find($usuarioid);
		} else {
			$usuario = new User;
		}

		$usuario->nombre = $request->nombre;
		$usuario->email = $request->email;
		$pass = $request->password;

		if ($pass) {
			$usuario->password = bcrypt($pass);
		}

		$usuario->activo = ($request->activo ? 1 : 0);

		//Ahora validamos si la password debe ser cambiada
		if (config('csgtlogin.vencimiento.habilitado')) {
			if (Input::has('vencimiento')) {
				$usuario->{config('csgtlogin.vencimiento.campo')} = date_create();
			}
		}

		$usuario->save();

		$roles = $request->rolid;
		if (!$roles) {
			$roles = [];
		}

		//Borramos todos los roles actuales
		Authusuariorol::where('usuarioid', $usuario->usuarioid)->delete();

		foreach ($roles as $rol) {
			$ur = new Authusuariorol;
			$ur->rolid = Crypt::decrypt($rol);
			$ur->usuarioid = $usuario->usuarioid;
			$ur->save();
		}

		return redirect()->route('catalogos.usuarios.index');
	}

	public function store(Request $request) {
		return $this->update($request, 0);
	}

/*
public function destroy($aId) {

try{
if (Crud::getSoftDelete()){
$query = DB::table('authusuarios')
->where('usuarioid', Crypt::decrypt($aId))
->update(array('deleted_at'=>date_create(), config('csgtlogin.password.campo') =>''));
}
else
$query = DB::table('authusuarios')
->where('usuarioid', Crypt::decrypt($aId))
->delete();

Session::flash('message', 'Registro borrado exitosamente');
Session::flash('type', 'warning');

} catch (\Exception $e) {
Session::flash('message', 'Error al borrar campo. Revisar datos relacionados.');
Session::flash('type', 'danger');
}

return Redirect::to('/usuarios');
}
 */
}
