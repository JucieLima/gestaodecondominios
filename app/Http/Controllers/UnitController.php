<?php

namespace App\Http\Controllers;

use App\Models\Unit;
use App\Models\UnitPerson;
use App\Models\UnitPet;
use App\Models\UnitVehicle;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class UnitController extends Controller
{
    public function getInfo($id)
    {
        $array = ['error' => ''];

        $unit = Unit::find($id);
        if(!$unit){
            $array['error'] = 'Unidade não localizada no sistema!';
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

    public function addPerson($id, Request $request)
    {
        $array = ['error' => ''];

        $unit = Unit::find($id);
        if(!$unit){
            $array['error'] = 'Unidade não localizada no sistema!';
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

    public function addVehicle($id, Request $request)
    {
        $array = ['error' => ''];

        $unit = Unit::find($id);
        if(!$unit){
            $array['error'] = 'Unidade não localizada no sistema!';
            return $array;
        }

        if($unit->owner != auth()->user()['id']){
            $array['error'] = 'Você não tem permissão para adicionar veículos nesta unidade!';
        }

        $validator = Validator::make($request->all(), [
            'title' => 'required',
            'color' => 'required',
            'plate' => 'required',
        ]);

        if($validator->fails()){
            $array['error'] = $validator->errors()->first();
            return $array;
        }

        $vehicle = new UnitVehicle();

        $vehicle->title = $request->input(['title']);
        $vehicle->plate = $request->input(['plate']);
        $vehicle->color = $request->input(['color']);
        $vehicle->unit = $id;

        $vehicle->save();

        return $array;
    }

    public function addPet($id, Request $request)
    {
        $array = ['error' => ''];

        $unit = Unit::find($id);
        if(!$unit){
            $array['error'] = 'Unidade não localizada no sistema!';
            return $array;
        }

        if($unit->owner != auth()->user()['id']){
            $array['error'] = 'Você não tem permissão para adicionar animais nesta unidade!';
        }

        $validator = Validator::make($request->all(), [
            'name' => 'required',
            'race' => 'required',
        ]);

        if($validator->fails()){
            $array['error'] = $validator->errors()->first();
            return $array;
        }

        $pet = new UnitPerson();

        $pet->name = $request->input(['name']);
        $pet->race = $request->input(['race']);
        $pet->unit = $id;

        $pet->save();

        return $array;
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

    public function removeVehicle($id, Request $request)
    {
        $array = ['error' => ''];
        if(!$request->input('id')){
            $array['error'] = 'É preciso informar o id de um veículo!';
            return $array;
        }

        $find = UnitVehicle::where('id', $request->input('id'))->where('unit', $id)->count();
        if(!$find){
            $array['error'] = 'Não foi possível localizar um veículo com os dados que você informou!';
            return $array;
        }

        UnitVehicle::where('id', $request->input('id'))->where('unit', $id)->delete();

        return $array;
    }

    public function removePet($id, Request $request)
    {
        $array = ['error' => ''];
        if(!$request->input('id')){
            $array['error'] = 'É preciso informar o id de um pet!';
            return $array;
        }

        $find = UnitPet::where('id', $request->input('id'))->where('unit', $id)->count();
        if(!$find){
            $array['error'] = 'Não foi possível localizar um pet com os dados que você informou!';
            return $array;
        }

        UnitPet::where('id', $request->input('id'))->where('unit', $id)->delete();

        return $array;
    }

}
