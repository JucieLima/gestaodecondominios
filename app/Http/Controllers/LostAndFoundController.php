<?php

namespace App\Http\Controllers;

use App\Models\LostAndFound;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class LostAndFoundController extends Controller
{
    public function getAll()
    {
        $array = ['error' => ''];

        $lost = LostAndFound::where('status', 'LOST')
            ->orderBy('created_at', 'DESC')
            ->limit(100)
            ->get();
        $recovered = LostAndFound::where('status', 'RECOVERED')
            ->orderBy('created_at', 'DESC')
            ->limit(100)
            ->get();

        foreach ($lost as $key => $value) {
            $lost[$key]['photo'] = asset('storage/' . $value['photo']);
        }

        foreach ($recovered as $key => $value) {
            $recovered[$key]['photo'] = asset('storage/' . $value['photo']);
        }

        $array['lost'] = $lost;
        $array['recovered'] = $recovered;

        return $array;

    }

    public function insert(Request $request)
    {
        $array = ['error' => ''];

        $validator = Validator::make($request->all(), [
           'title' => 'required',
           'where' => 'required',
           'photo' => 'required|file|mimes:png,jpg',
        ]);

        if($validator->fails()) {
            $array['error'] = $validator->errors()->first();
            return $array;
        }

        $file = $request->file('photo')->store('public');
        $file = explode('/', $file);

        $photo = $file[1];

        $lost = new LostAndFound();
        $lost->status = 'LOST';
        $lost->photo = $photo;
        $lost->title = $request->input('title');
        $lost->where = $request->input('where');

        $lost->save();

        return $array;
    }

    public function update($id, Request $request)
    {
        $array = ['error'];

        $status = $request->input(['status']);
        if(!($status && in_array($status, ['LOST', 'RECOVERED']))){
            $array['error'] = 'Status inválido!';
            return $array;
        }

        $item = LostAndFound::find($id);

        if(!$item){
            $array['error'] = 'Item não foi localizado no sistema!';
            return $array;
        }

        $item->status = $status;
        $item->save();

        return $array;
    }
}
