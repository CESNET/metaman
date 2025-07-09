<?php

namespace Tests\Feature\Http\Controllers;

use App\Ldap\CesnetOrganization;
use App\Ldap\EduidczOrganization;
use LdapRecord\Laravel\Testing\DirectoryEmulator;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class EntityidControllerTest extends TestCase
{
    // response

    #[Test]
    public function missing_company_id_returns_bad_request_response(): void
    {
        $this
            ->get(route('api:entityid'))
            ->assertBadRequest();
    }

    #[Test]
    public function organization_not_found_returns_not_found_response(): void
    {
        DirectoryEmulator::setup();

        $this
            ->get(route('api:entityid', ['ico' => '123']))
            ->assertNotFound();

        DirectoryEmulator::tearDown();
    }

    #[Test]
    public function organization_without_an_idp_returns_not_found_response(): void
    {
        $ico = '123456789';

        DirectoryEmulator::setup();
        DirectoryEmulator::setup('eduidczorganizations');

        CesnetOrganization::create([
            'dc' => 'Example',
            'ico' => $ico,
        ]);

        $this->assertCount(1, CesnetOrganization::all());

        $this
            ->get(route('api:entityid', ['ico' => $ico]))
            ->assertNotFound();

        DirectoryEmulator::tearDown();
    }

    #[Test]
    public function organization_with_an_idp_returns_its_entityid(): void
    {
        $ico = '1234567890';
        $dc = 'Example';
        $entityid = 'https://idp.example.org/idp/shibboleth';
        $scope = 'example.org';

        DirectoryEmulator::setup();
        DirectoryEmulator::setup('eduidczorganizations');

        $o = CesnetOrganization::create([
            'ico' => $ico,
            'dc' => $dc,
        ]);

        $this->assertCount(1, CesnetOrganization::all());

        EduidczOrganization::create([
            'dc' => now()->timestamp,
            'oPointer' => $o->getDn(),
            'entityIDofIdP' => $entityid,
            'eduIDczScope' => $scope,
        ]);

        $this->assertCount(1, EduidczOrganization::all());

        $this
            ->get(route('api:entityid', ['ico' => $ico]))
            ->assertOk()
            ->assertJson([
                'entityID' => $entityid,
            ]);

        DirectoryEmulator::tearDown();
    }
}
