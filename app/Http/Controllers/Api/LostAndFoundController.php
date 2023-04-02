<?php

namespace App\Http\Controllers\Api;

use App\Models\LostAndFound;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class LostAndFoundController extends Controller
{
    /**
     * @return JsonResponse
     */
    public function getAll(): JsonResponse
    {
        $array = [];

        $lost = LostAndFound::where('status', 'LOST')
            ->orderBy('updated_at', 'DESC')
            ->limit(100)
            ->get();
        $recovered = LostAndFound::where('status', "<>", 'LOST')
            ->orderBy('updated_at', 'DESC')
            ->limit(100)
            ->get();

        foreach ($lost as $key => $value) {
            $lost[$key]['photo'] = asset('storage/' . $value['photo']);
            $lost[$key]['when'] = date('d/m/Y H:i', strtotime($value['created_at']));
        }

        foreach ($recovered as $key => $value) {
            $recovered[$key]['photo'] = asset('storage/' . $value['photo']);
            $recovered[$key]['when'] = date('d/m/Y H:i', strtotime($value['created_at']));
        }

        $array['lost'] = $lost;
        $array['recovered'] = $recovered;

        return response()->json($array);

    }

    /**
     * @param Request $request
     * @return JsonResponse
     */
    public function insert(Request $request): JsonResponse
    {
        $array = [];

        $validator = Validator::make($request->all(), [
           'title' => 'required',
           'where' => 'required',
           'author' => 'required',
           'description' => 'required|min:20',
           'photo' => 'required|file|mimes:png,jpg',
        ]);

        if($validator->fails()) {
            $array['error'] = $validator->errors()->first();
            return response()->json($array, 400);
        }

        $author = User::find($request->input("author"));

        if(!$author){
            $array['error'] = "Usuário inválido.";
            return response()->json($array, 404);
        }

        $file = $request->file('photo')->store('public');
        $file = explode('/', $file);

        $photo = $file[1];

        $lost = new LostAndFound();
        $lost->status = 'LOST';
        $lost->photo = $photo;
        $lost->title = $request->input('title');
        $lost->where = $request->input('where');
        $lost->author = $request->input('author');
        $lost->description = $request->input('description');

        $lost->save();

        $array['result'] = "Objeto cadastrado com sucesso!";

        return response()->json($array);
    }

    /**
     * @param $id
     * @param Request $request
     * @return JsonResponse
     */
    public function update($id, Request $request): JsonResponse
    {
        $array = [];

        $item = LostAndFound::find($id);

        if(!$item){
            $array['error'] = 'Item não localizado no sistema!';
            return response()->json($array, 404);
        }

        $validator = Validator::make($request->all(), [
            'title' => 'required',
            'where' => 'required',
            'author' => 'required',
            'description' => 'required|min:20',
            'photo' => 'file|mimes:png,jpg',
        ]);

        if($validator->fails()) {
            $array['error'] = $validator->errors()->first();
            return response()->json($array, 400);
        }

        if($request->input('author') != auth()->user()['id']){
            $array['error'] = 'Somente o autor poderá realizar edições.';
            return response()->json($array, 400);
        }

        $status = $request->input(['status']);
        if(!($status && in_array($status, ['LOST', 'RECOVERED', 'CLAIMED']))){
            $array['error'] = 'Status inválido!';
            return response()->json($array, 400);
        }

        if($request->file('photo')){
            Storage::disk('public')->delete($item->photo);
            $image = $request->file('photo')->store('images', 'public');
            $item->photo = $image;
        }

        $item->status = $status;
        $item->title = $request->input('title');
        $item->where = $request->input('where');
        $item->description = $request->input('description');

        $item->save();

        $array['result'] = "Objeto cadastrado com sucesso!";

        return response()->json($array);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return JsonResponse
     */
    public function destroy(int $id): JsonResponse
    {
        $response = [];

        $object = LostAndFound::find($id);
        if(!$object){
            $response['error'] = "Não foi possível localizar o objeto no banco de dados.";
            return response()->json($response, 404);
        }

        if($object->author != auth()->user()['id']){
            $array['error'] = 'Somente o autor poderá realizar excluir este objeto.';
            return response()->json($array, 400);
        }

        Storage::disk('public')->delete($object->photo);
        $object->delete();

        $response['result'] = "Objeto excluída com sucesso.";
        return response()->json($response);
    }

    /**
     * @param int $id
     * @return JsonResponse
     */
    public function claim(int $id): JsonResponse
    {
        $response = [];

        $object = LostAndFound::find($id);
        if(!$object){
            $response['error'] = "Não foi possível localizar o objeto no banco de dados.";
            return response()->json($response, 404);
        }

        $object->author = auth()->user()['id'];
        $object->status = "CLAIMED";

        $object->save();

        $response['result'] = "Objeto reivindicado com sucesso.";
        return response()->json($response);
    }
}
