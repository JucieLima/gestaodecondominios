<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

use App\Models\User;
use App\Models\Unit;

class AuthController extends Controller
{
    public function unauthorized()
    {
        return response()->json([
            'error' => 'Não autorizado',
        ],401);
    }

    public function register(Request $request)
    {
        $array = [ 'error' => '' ];


        $validator = Validator::make($request->all(),[
            'name' => 'required',
            'email' => 'required|email|unique:users,email',
            'cpf' => 'required|unique:users,cpf',
            'password' => 'required|confirmed',
            'password_confirmation' => 'required',
        ]);

        if(!$validator->fails()){

            $user = new User();
            $user->name = $request->input('name');
            $user->email = $request->input('email');
            $user->cpf = $request->input('cpf');
            $user->password = password_hash($request->input('password'), PASSWORD_DEFAULT);
            $user->role = $request->input('role') != NULL ? $request->input('role')  : 'user';
            $user->status = $request->input('status') != NULL ? (int) $request->input('status')  : 0;

            $user->save();

            $token = auth()->attempt([
                'cpf' => $user->cpf,
                'password' => $request->input('password')
            ]);

            if(!$token){
                $array['error'] = 'Ocorreu um erro ao tentar efetuar registro do usuário';
            }else{
                $array['token'] = $token;
                $array['user'] = auth()->user();
                $array['user']['properties'] = Unit::select(['id', 'name'])->where('owner', $array['user']['id'])->get();
            }

        }else{
            $array['error'] = $validator->errors()->first();
        }

        return $array;
    }

    public function login(Request $request)
    {
        $array = ['error' => ''];
        $validator = Validator::make($request->all(), [
            'cpf' => 'required',
            'password' => 'required'
        ] );

        if(!$validator->fails()){
            $token = auth()->attempt([
                'cpf' => $request->input('cpf'),
                'password' => $request->input('password')
            ]);

            if(!$token){
                $array['error'] = 'Ero ao logar, dados não conferem!';
            }else{
                $array['token'] = $token;
                $array['user'] = auth()->user();
                $array['user']['properties'] = Unit::select(['id', 'name'])->where('owner', $array['user']['id'])->get();
            }

        }else{
            $array['error'] = $validator->errors()->first();
        }

        return $array;
    }
}
