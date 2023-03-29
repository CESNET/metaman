<?php

namespace Database\Factories;

use App\Models\Entity;
use Illuminate\Database\Eloquent\Factories\Factory;

class EntityFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Entity::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        $entityid = "https://{$this->faker->domainName()}/{$this->faker->unique()->slug(3)}";

        return [
            'type' => $type = random_int(0, 1) ? 'idp' : 'sp',
            'entityid' => $entityid,
            'file' => urlencode(preg_replace('#https://#', '', $entityid)).'.xml',
            'name_en' => $this->faker->catchPhrase(),
            'name_cs' => $this->faker->catchPhrase(),
            'description_en' => $this->faker->text(255),
            'description_cs' => $this->faker->text(255),
            'edugain' => random_int(0, 1) ? true : false,
            'hfd' => ($type === 'idp' && random_int(0, 1)) ? true : false,
            'rs' => random_int(0, 1) ? true : false,
            'cocov1' => random_int(0, 1) ? true : false,
            'sirtfi' => random_int(0, 1) ? true : false,
            'approved' => true,
            'metadata' => $this->faker->randomHtml(5, 5),
        ];
    }
}
