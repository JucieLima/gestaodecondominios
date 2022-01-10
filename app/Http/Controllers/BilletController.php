<?php

namespace App\Http\Controllers;

use App\Models\Billet;
use App\Models\Unit;
use Illuminate\Http\Request;

class BilletController extends Controller
{
    public function getAll(Request $request)
    {
        $array = ['error' => ''];
        $property = $request->input('property');
        if($property){
            $user = auth()->user()['id'];
            $unit = Unit::where('id', $property)->where('owner', $user)->count();
            if(!$unit){
                $array['error'] = 'A unidade requisitada não pertence a este usuário!';
                $array['user'] = $user;
                return $array;
            }
            $billets = Billet::where('unit', $property)->get();
            foreach($billets as $key => $value){
                $billets[$key]['file'] = asset('storage/'.$value['file_url']);
            }
            $array['list'] = $billets;
        }else{
            $array['error'] = 'Para gerar boletos é necessário informar uma unidade!';
        }
        return $array;
    }
}
