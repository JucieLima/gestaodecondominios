<?php

namespace App\Http\Controllers\Api;

use App\Models\Unit;
use App\Models\UnitPerson;
use App\Models\UnitPet;
use App\Models\UnitVehicle;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class UnitController extends Controller
{
    public function getInfo($id): array
    {
        $array = ['error' => ''];

        $unit = Unit::find($id);
        if(!$unit){
            $array['error'] = $this->unitNotFound();
            return $array;
        }

        if($unit->owner != auth()->user()['id']){
            $array['error'] = 'Você não tem perissão para acessar esta unidade!';
        }

        $array['unit'] = $unit->name;
        $array['people'] = UnitPerson::where('unit', $id)->get();
        $array['vehicles'] = UnitVehicle::where('unit', $id)->get();
        $array['pets'] = UnitPet::where('unit', $id)->get();

        return $array;
    }

    public function addPerson($id, Request $request): array
    {
        $array = ['error' => ''];

        $unit = Unit::find($id);
        if(!$unit){
            $array['error'] = $this->unitNotFound();
            return $array;
        }

        if($unit->owner != auth()->user()['id']){
            $array['error'] = 'Você não tem permissão para adicionar pessoas nesta unidade!';
        }

        $validator = Validator::make($request->all(), [
            'name' => 'required',
            'birthdate' => 'required|date'
        ]);

        if($validator->fails()){
            $array['error'] = $validator->errors()->first();
            return $array;
        }

        $person = new UnitPerson();

        $person->name = $request->input(['name']);
        $person->birthdate = $request->input(['birthdate']);
        $person->unit = $id;

        $person->save();

        return $array;
    }

    /**
     * @param $id
     * @return JsonResponse
     */
    public function vehicles($id): JsonResponse
    {
        $array = [];
        $unit = Unit::find($id);
        if(!$unit){
            $array['error'] = "A unidade informada não foi localizada no banco de dados";
            return response()->json($array, 404);
        }

        $vehicles = UnitVehicle::where("unit", $id)->get();
        $array['vehicles'] = $vehicles;
        return response()->json($array);
    }

    /**
     * @param $id
     * @param Request $request
     * @return JsonResponse
     */
    public function addVehicle($id, Request $request): JsonResponse
    {
        $array = [];

        $unit = Unit::find($id);
        if(!$unit){
            $array['error'] = $this->unitNotFound();
            return response()->json($array, 404);
        }

        $validator = Validator::make($request->all(), [
            'title' => 'required',
            'color' => 'required',
            'plate' => 'required',
        ]);

        if($validator->fails()){
            $array['error'] = $validator->errors()->first();
            return response()->json($array, 400);
        }

        $vehicle = new UnitVehicle();

        $vehicle->title = $request->input(['title']);
        $vehicle->plate = strtoupper($request->input(['plate']));
        $vehicle->color = $request->input(['color']);
        $vehicle->unit = $id;
        $vehicle->save();

        $array['result'] = "Veículo cadastrado com sucesso.";

        return response()->json($array);
    }

    /**
     * @param int $id
     * @param Request $request
     * @return JsonResponse
     */
    public function updateVehicle(int $id, Request  $request): JsonResponse
    {
        $array = [];

        $unit = Unit::find($id);
        if(!$unit){
            $array['error'] = $this->unitNotFound();
            return response()->json($array, 404);
        }

        $validator = Validator::make($request->all(), [
            'title' => 'required',
            'color' => 'required',
            'plate' => 'required',
        ]);

        if($validator->fails()){
            $array['error'] = $validator->errors()->first();
            return response()->json($array, 400);
        }

        $vehicle = UnitVehicle::find($request->input('id'));

        $vehicle->title = $request->input(['title']);
        $vehicle->plate = strtoupper($request->input(['plate']));
        $vehicle->color = $request->input(['color']);
        $vehicle->save();

        $array['result'] = "Veículo atualizado com sucesso.";

        return response()->json($array);
    }

    /**
     * @param $id
     * @return JsonResponse
     */
    public function pets($id): JsonResponse
    {
        $array = [];
        $unit = Unit::find($id);
        if(!$unit){
            $array['error'] = "A unidade informada não foi localizada no banco de dados";
            return response()->json($array, 404);
        }

        $pets = UnitPet::where("unit", $id)->get();
        $array['pets'] = $pets;
        return response()->json($array);
    }

    /**
     * @param $id
     * @param Request $request
     * @return JsonResponse
     */
    public function addPet($id, Request $request): JsonResponse
    {
        $array = ['error' => ''];

        $unit = Unit::find($id);
        if(!$unit){
            $array['error'] = $this->unitNotFound();
            return response()->json($array, 404);
        }

        if($unit->owner != auth()->user()['id']){
            $array['error'] = 'Você não tem permissão para adicionar animais nesta unidade!';
        }

        $validator = Validator::make($request->all(), [
            'name' => 'required',
            'breed' => 'required',
            'species' => 'required',
            'photo' => 'required|file|mimes:png,jpg',
            'description' => 'required|min:20',
        ]);

        if($validator->fails()){
            $array['error'] = $validator->errors()->first();
            return response()->json($array, 400);
        }

        $pet = new UnitPet();

        $pet->name = $request->input(['name']);
        $pet->breed = $request->input(['breed']);
        $pet->species = $request->input(['species']);
        $pet->description = $request->input(['description']);
        $pet->photo = $request->file('photo')->store('images', 'public');
        $pet->unit = $id;

        $pet->save();
        $array['pet'] = json_encode($pet->toArray());
        return response()->json($array);
    }

    /**
     * @param int $id
     * @param Request $request
     * @return JsonResponse
     */
    public function updatePet(int $id, Request  $request): JsonResponse
    {
        $array = [];

        $unit = Unit::find($id);
        if(!$unit){
            $array['error'] = $this->unitNotFound();
            return response()->json($array, 404);
        }

        $validator = Validator::make($request->all(), [
            'id' => 'required',
            'name' => 'required',
            'breed' => 'required',
            'species' => 'required',
            'description' => 'required|min:20',
        ]);

        if($validator->fails()){
            $array['error'] = $validator->errors()->first();
            return response()->json($array, 400);
        }

        $pet = UnitPet::find($request->input('id'));

        if($request->file('photo')){
            Storage::disk('public')->delete($pet->photo);
            $image = $request->file('photo')->store("images",'public');
            $pet->photo = $image;
        }

        $pet->name = $request->input(['name']);
        $pet->breed = $request->input(['breed']);
        $pet->species = $request->input(['species']);
        $pet->description = $request->input(['description']);
        $pet->update();

        $array['result'] = "Animal atualizado com sucesso.";
        $array['pet'] = json_encode($pet->toArray());
        return response()->json($array);
    }

    public function removePerson($id, Request $request)
    {
        $array = ['error' => ''];
        if(!$request->input('id')){
            $array['error'] = 'É preciso informar o id de uma pessoa!';
            return $array;
        }

        $find = UnitPerson::where('id', $request->input('id'))->where('unit', $id)->count();
        if(!$find){
            $array['error'] = 'Não foi possível localizar uma pessoa com os dados que você informou!';
            return $array;
        }

        UnitPerson::where('id', $request->input('id'))->where('unit', $id)->delete();

        return $array;
    }

    /**
     * @param $id
     * @param Request $request
     * @return JsonResponse
     */
    public function removeVehicle($id, Request $request): JsonResponse
    {
        $array = [];
        if(!$request->input('id')){
            $array['error'] = 'É preciso informar o id de um veículo!';
            return response()->json($array, 400);
        }

        $find = UnitVehicle::where('id', $request->input('id'))->where('unit', $id)->count();
        if(!$find){
            $array['error'] = 'Não foi possível localizar um veículo com os dados que você informou!';
            $array['unit_id'] = $id;
            $array['vehicle_id'] = $request->input('id');
            return response()->json($array, 404);
        }

        UnitVehicle::where('id', $request->input('id'))->where('unit', $id)->delete();
        $array['result'] = 'Veículo excluído com sucesso!';
        return response()->json($array);
    }

    public function removePet($id, Request $request): JsonResponse
    {
        $array = ['error' => ''];
        if(!$request->input('id')){
            $array['error'] = 'É preciso informar o id de um pet!';
            return response()->json($array, 400);
        }

        $find = UnitPet::where('id', $request->input('id'))->where('unit', $id)->count();
        if(!$find){
            $array['error'] = 'Não foi possível localizar um pet com os dados que você informou!';
            return response()->json($array, 404);
        }

        UnitPet::where('id', $request->input('id'))->where('unit', $id)->delete();

        return response()->json($array);
    }

    /**
     * @return string
     */
    private function unitNotFound(): string
    {
        return 'Unidade não localizada no sistema!';
    }

}
