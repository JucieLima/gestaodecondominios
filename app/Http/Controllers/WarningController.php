<?php

namespace App\Http\Controllers;

use App\Models\Unit;
use App\Models\Warning;
use Illuminate\Http\Request;

class WarningController extends Controller
{
    public function getMyWarnings(Request $request)
    {
        $array = ['error' => ''];

        $property = $request->input('property');
        if($property){
            $user = auth()->user()['id'];
            $unit = Unit::where('id', $property)->where('owner', $user)->count();
            if(!unit){
                $array['error'] = 'A unidade informada não pertence a este usuário!';
                return $array;
            }
            $warnings = Warning::where('unit', $property)->orderBy('created_at', 'DESC')->get();
            foreach ($warnings as $key => $value){
                $warnings[$key]['created_at'] =
                    date('d/m/Y à\s h:i:s', strtotime($value['created_at']));
                $photoList = [];
                $photos = explode(',', $value['photos']);
                foreach ($photos as $photo){
                    if(!empty($photo)){
                        $photoList[] = asset('storage/'.$photo);
                    }
                }
                $warnings[$key]['photos'] = $photos;

            }
        }else{
            $array['error'] = 'É necessário informar uma unidade!';
        }
        return $array;
    }
}
