<?php

namespace App\Http\Controllers\Api;

use App\Models\Resident;
use App\Models\Unit;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

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
        $array = [];

        $validator = Validator::make($request->all(),[
            'name' => 'required',
            'email' => 'required|email|unique:users,email',
            'cpf' => 'required|unique:users,cpf',
            'password' => 'required|confirmed',
            'password_confirmation' => 'required',
        ]);

        if($validator->fails()){
            $array['error'] = $validator->errors()->first();
            return response()->json($array, 401);
        }

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
            $array['error'] = 'Ocorreu um erro ao tentar obter dados do usuário';
            return response()->json($array, 500);
        }

        $array['user']['token'] = $token;
        $array['user'] = auth()->user();
        $array['user']['properties'] = Unit::select(['id', 'name'])->where('owner', $array['user']['id'])->get();

        return response()->json($array);
    }

    /**
     * @param Request $request
     * @return JsonResponse
     */
    public function login(Request $request): JsonResponse
    {
        $array = [];
        $validator = Validator::make($request->all(), [
            'email' => 'required',
            'password' => 'required'
        ] );

        if($validator->fails()){
            $array['error'] = $validator->errors()->first();
            return response()->json($array, 401);
        }

        $token = auth()->attempt([
            'email' => $request->input('email'),
            'password' => $request->input('password')
        ]);

        if(!$token){
            $array['error'] = 'Erro ao logar, dados não conferem.';
            return response()->json($array, 401);
        }

        $array['token'] = [
            'access_token' => $token,
            'token_type' => 'bearer',
            'expires_in' => (auth()->factory()->getTTL() * 60) + time(),
        ];

        $array['user'] = auth()->user();


        if($array['user']['role'] == 'resident' ){
            $unit = Resident::where('user_id', $array['user']['id'])->with('unit')->get()->toArray();
            $array['unit'] = $unit[0]['unit'];
        }

        return response()->json($array);
    }

    /**
     * @return JsonResponse
     */
    public function validateToken(): JsonResponse
    {
        $user['user'] = auth()->user();
        $user['user']['properties'] = Unit::select(['id', 'name'
                  ])->where('owner', $user['user']['id'])->get();
        return response()->json($user);
    }

    /**
     * @return JsonResponse
     */
    public function logout(): JsonResponse
    {
        auth()->logout();
        return response()->json(['response' => 'Logout realizado com sucesso!']);
    }

}
