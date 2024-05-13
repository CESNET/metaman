<?php

namespace Database\Factories;

use App\Models\Category;
use Illuminate\Database\Eloquent\Factories\Factory;

class CategoryFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Category::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        $name = substr($this->faker->unique()->company(), 0, 32);

        return [
            'name' => $name,
            'description' => $this->faker->catchPhrase(),
            'tagfile' => generateFederationID($name).'.tag',
            'xml_value' => $this->faker->unique()->url()
        ];
    }
}
