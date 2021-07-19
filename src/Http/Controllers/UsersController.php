<?php
namespace Csgt\Utils\Http\Controllers;

use DB;
use Hash;
use Cache;
use Crypt;
use App\Models\Auth\Role;
use App\Models\Auth\User;
use Illuminate\Http\Request;
use App\Models\Auth\UserRole;
use Csgt\Crud\CrudController;
use Csgt\Cancerbero\Cancerbero;

class UsersController extends CrudController
{
    public $path = '/catalogs/users';

    public function __construct()
    {
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

        $this->middleware(function ($request, $next) {
            if (!Cancerbero::isGod()) {
                $ids = UserRole::where('role_id', Cancerbero::godRole())->pluck('user_id');
                $this->setWhere('id', '<>', $ids);
            }

            return $next($request);
        });

        $this->setPermissions("\Cancerbero::crudPermissions", substr(str_replace('/', '.', $this->path), 1));
    }

    public function detail(Request $request, $id)
    {
        $roleIds = [];
        $user    = [
            'name'     => null,
            'email'    => null,
            'active'   => true,
            'role_ids' => [],
        ];

        if ($id !== '0') {
            $id   = Crypt::decrypt($id);
            $user = User::with('roles')->findOrFail($id);

            $roleIds = $user->roles->map(function ($role) {
                return $role->id;
            })->toArray();

            $user->role_ids = $roleIds;
        }

        $roles = Role::orderBy('name');
        if (!Cancerbero::isGod()) {
            $roles->where('id', '<>', Cancerbero::godRole());
        }
        $roles = $roles->get();

        return response()->json([
            'user'           => $user,
            'roles'          => $roles,
            'changePassword' => false,
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
            <li class="breadcrumb-item"><a href="' . $this->path . '">Usuarios</a></li>
            <li class="breadcrumb-item active">Usuario</li>
        </ol>';

        $params = [
            'id'   => $id,
            'path' => $this->path,
        ];

        return view('component')
            ->withTitle($this->getTitle())
            ->withBreadcrumb($breadcrumb)
            ->with('params', $params)
            ->with('component', 'catalogs-users-edit');
    }

    public function store(Request $request)
    {
        return $this->update($request, 0);
    }

    public function update(Request $request, $id)
    {
        $savePass = ($id === 0 || $request->changePassword);
        $rules    = [
            'user.name'     => 'required',
            'user.email'    => 'required|unique:users,email' . ($id !== 0 ? ',' . Crypt::decrypt($id) : ''),
            'user.role_ids' => 'required|min:1',
        ];

        $messages = [
            'user.name.required'     => 'El nombre es requerido',
            'user.email.required'    => 'El email es requerido',
            'user.role_ids.required' => 'Al menos debe seleccionar un rol',
            'user.role_ids.min'      => 'Al menos debe seleccionar un rol',
        ];

        if ($savePass) {
            $rules['user.password']              = 'required|confirmed';
            $messages['user.password.required']  = 'La password es requerida';
            $messages['user.password.confirmed'] = 'Las passwords no concuerdan';
        }

        $request->validate($rules, $messages);

        DB::transaction(function () use ($request, $id, $savePass) {

            if ($id !== 0) {
                $userId = Crypt::decrypt($id);
                $user   = User::findOrFail($userId);

            } else {
                $user = new User;
            }

            $user->name   = $request->user['name'];
            $user->email  = $request->user['email'];
            $user->active = $request->user['active'];
            if ($savePass) {
                $user->password = Hash::make($request->user['password']);
            }
            $user->save();

            UserRole::where('user_id', $user->id)->delete();

            foreach ($request->user['role_ids'] as $roleId) {
                $ur          = new UserRole;
                $ur->role_id = $roleId;
                $ur->user_id = $user->id;
                $ur->save();
            }
        });
        Cache::flush();

        return response()->json('ok');
    }
}
