<?php
namespace Csgt\Utils\Http\Controllers;

use Auth;
use Hash;
use App\Models\Auth\User;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

class ProfileController extends Controller
{
    public $path = '/profile';

    public function index(Request $request)
    {
        return view('component')
            ->withTitle('Perfil')
            ->with('component', 'profile');
    }

    public function detail(Request $request, $id)
    {
        $user = User::findOrFail(Auth::id());

        return response()->json([
            'user'           => $user,
            'changePassword' => false,
        ]);
    }

    public function store(Request $request)
    {
        $savePass = $request->changePassword;
        $rules    = [
            'user.name' => 'required',
        ];

        $messages = [
            'user.name.required' => 'El nombre es requerido',
        ];

        if ($savePass) {
            $rules['user.password']              = 'required|confirmed';
            $messages['user.password.required']  = 'La password es requerida';
            $messages['user.password.confirmed'] = 'Las passwords no concuerdan';
        }

        $request->validate($rules, $messages);

        $user       = User::findOrFail(Auth::id());
        $user->name = $request->user['name'];
        if ($savePass) {
            $user->password = Hash::make($request->user['password']);
        }
        $user->save();

        return response()->json('ok');
    }
}
