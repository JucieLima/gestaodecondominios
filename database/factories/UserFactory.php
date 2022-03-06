<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class UserFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()    {
        return [
            'name' => $this->faker->name(),
            'email' => $this->faker->unique()->safeEmail(),
            'password' => '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', // password
            'cpf' => $this->fakeCpf(),
            'role' => 'user',
            'status' => 1
        ];
    }

    private function fakeCpf()
    {
        $num1 = $this->aleatorio();
        $num2 = $this->aleatorio();
        $num3 = $this->aleatorio();
        return "$num1.$num2.$num3-{$this->dig($num1.$num2.$num3)}";
    }

    private function dig($str)
    {
        $dig = '';
        $cpf = str_split($str);
        for ($t = 8; $t < 10; $t++) {
            for ($d = 0, $c = 0; $c < $t; $c++) {
                $d += $cpf[$c] * (($t + 1) - $c);
            }
            $d = ((10 * $d) % 11) % 10;
            $dig .= $d;
        }
        return $dig;
    }

    private function aleatorio()
    {
        return str_pad(rand(0, 999), 3, 0, STR_PAD_LEFT);
    }
}
