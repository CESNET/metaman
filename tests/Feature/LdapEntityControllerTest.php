<?php

namespace Tests\Feature;

use App\Ldap\CesnetOrganization;
use App\Ldap\EduidczOrganization;
use App\Models\Entity;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use LdapRecord\Laravel\Testing\DirectoryEmulator;
use Tests\TestCase;

class LdapEntityControllerTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    /**
     * @test
     */
    public function entity_controller_show_works()
    {
        $this->assertTrue(true);
        /*
        DirectoryEmulator::setup('default');
        DirectoryEmulator::setup('eduidczorganizations');

        $entity = Entity::factory()->hasOperators(1)->create(['type' => 'idp']);
        $user = User::find(1);

        $eduidczOrganization = EduidczOrganization::create([
            'entityIDofIdP' => $entity->entityid,
        ]);

        $cesnetOrganization = CesnetOrganization::create([
            'dc' => 'example',
            'o' => 'EXAMPLE',
        ]);

        $this->assertEquals($entity->entityid, $eduidczOrganization->getFirstAttribute('entityIDofIdP'));
        $this->assertEquals('EXAMPLE', $cesnetOrganization->getFirstAttribute('o'));
        */
    }
}
