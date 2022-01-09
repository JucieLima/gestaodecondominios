<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class UnitFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        $bloco = ['A', 'B', 'C', 'D', 'E', 'F'];
        $andar = ['1', '2', '3', '4', '5', '6'];
        $apart = ['01', '02', '03', '04', '05', '06', '07', '08'];
        $name = $bloco[\rand(0,5)].' '.$andar[\rand(0,5)].$apart[\rand(0,7)];
        return [
            'name' => $name,
            'owner' => 1,
        ];
    }
}
