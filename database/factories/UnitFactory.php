<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use phpDocumentor\Reflection\Types\Boolean;

class UnitFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        $name = $this->makeName();
        return [
            'name' => $name,
            'owner' => 1,
        ];
    }

    private function makeName(){
        do{
            $bloco = ['A', 'B', 'C', 'D', 'E', 'F'];
            $andar = ['1', '2', '3', '4', '5', '6'];
            $apart = ['01', '02', '03', '04', '05', '06', '07', '08'];
            $name = $bloco[\rand(0,5)].' '.$andar[\rand(0,5)].$apart[\rand(0,7)];
            $make = $this->checkName($name);
        }while($make == false);

        return $name;
    }

    private function checkName(String $name){
        $make = false;
        $file = fopen("names.txt", "w+");
        $json = fgets($file);

        if(!empty($json)){
            $names = json_decode($json, true, 1, JSON_OBJECT_AS_ARRAY);
            if(!in_array($name, $names)){
                $names[] = $name;
                $make = true;
            }
        }else{
            $make = true;
            $names[] = $name;
        }
        $write = json_encode($names);
        fputs($file, $write);

        fclose($file);
        return $make;
    }
}
