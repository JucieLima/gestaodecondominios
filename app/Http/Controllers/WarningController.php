<?php

namespace App\Http\Controllers;

use App\Models\Unit;
use App\Models\Warning;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

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

    public function addWarningFile(Request $request)
    {
        $array = ['error' => ''];

        $validator = Validator::make($request->all(),[
           'photo' => 'required|file|mimes:jpg,png'
        ]);

        if($validator->fails()){
            $array['error'] = $validator->errors()->first();
            return $array;
        }

        $file = $request->file('photo')->store('public');
        $array['photo'] = asset(Storage::url($file));

        return $array;

    }

    public function setWarning(Request $request)
    {
        $array = ['error' => ''];

        $validator = Validator::make($request->all(), [
            'title' => 'required',
            'property' => 'required',
        ]);

        if($validator->fails()){
            $array['error'] = $validator->errors()->first();
            return $array;
        }

        $input_photos = $request->input('photos');

        $warning = new Warning();
        $warning->unit = $request->input(['property']);
        $warning->title = $request->input(['title']);
        $warning->body = $request->input(['body']);

        if($input_photos && is_array($input_photos)){
            $photos = [];
            foreach ($input_photos as $item){
                $uri = explode('/', $item);
                $photos[] = end($uri);
            }

            $warning->photos = implode(',', $photos);
        }

        $warning->save();

        return $array;
    }
}
