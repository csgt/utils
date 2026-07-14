<?php
namespace Csgt\Utils\Http\Controllers;


use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class ProfileController extends Controller
{
    public function index(Request $request)
    {
        return view('component', [
            'title'     => trans('Profile'),
            'component' => 'profile',
        ]);
    }

    public function detail()
    {
        $user = auth()->user();
        return response()->json($user);
    }

        public function store(Request $request)
    {
        $rules = [
            'user.name'     => 'required|max:255',
            'user.email'    => [
                'required',
                'max:255',
                'email',
                Rule::unique('users', 'email')->ignore($request->user['id']),
            ],
        ];

        if ($request->changePassword) {
            $rules['user.password']              = 'required|min:6';
            $rules['user.password_confirmation'] = 'required_with:user.password|same:user.password|min:6';
        }

        $request->validate($rules);

        $dataRequest                = $request->user;
        if ($request->changePassword) {
            $dataRequest['password'] = Hash::make($request->user['password']);
        }

        $user = auth()->user();

        $user->fill($dataRequest);
        $user->save();

        return response()->json('ok');
    }
}
