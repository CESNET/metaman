<?php

namespace Tests\Feature\Http\Controllers;

use App\Jobs\GitAddEntity;
use App\Jobs\GitDeleteEntity;
use App\Jobs\GitUpdateEntity;
use App\Models\Entity;
use App\Models\Federation;
use App\Models\Membership;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Facades\Bus;
use Tests\TestCase;

class EntityControllerTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    /** @test */
    public function an_anonymouse_user_isnt_shown_an_entities_list()
    {
        $this
          ->followingRedirects()
          ->get(route('federations.index'))
          ->assertSeeText('login');

        $this->assertEquals(route('login'), url()->current());
    }

    /** @test */
    public function an_anonymouse_user_isnt_shown_an_entities_details()
    {
        $entity = Entity::factory()->create();

        $this
          ->followingRedirects()
          ->get(route('entities.show', $entity))
          ->assertSeeText('login');

        $this->assertEquals(route('login'), url()->current());
    }

    /** @test */
    public function an_anonymouse_user_isnt_shown_a_form_to_add_a_new_entity()
    {
        $this
          ->followingRedirects()
          ->get(route('entities.create'))
          ->assertSeeText('login');

        $this->assertEquals(route('login'), url()->current());
    }

    /** @test */
    public function an_anonymouse_user_cannot_add_a_new_entity()
    {
        // metadata URL
        $this
          ->followingRedirects()
          ->post(route('entities.store'), [
              'url' => "https://{$this->faker->domainName()}/{$this->faker->unique()->slug(3)}",
              'federation' => Federation::factory()->create()->id,
              'explanation' => $this->faker->catchPhrase(),
          ])
          ->assertSeeText('login');

        $this->assertEquals(route('login'), url()->current());

        // metadata file
    }

    /** @test */
    public function an_anonymouse_user_cannot_see_entities_edit_page()
    {
        $entity = Entity::factory()->create();

        $this
          ->followingRedirects()
          ->get(route('entities.edit', $entity))
          ->assertSeeText('login');

        $this->assertEquals(1, Entity::count());
        $this->assertEquals(route('login'), url()->current());
    }

    /** @test */
    public function an_anonymouse_user_cannot_edit_an_existing_entity()
    {
        $entity = Entity::factory()->create();

        $this
          ->followingRedirects()
          ->patch(route('entities.update', $entity), ['action' => 'update'])
          ->assertSeeText('login');

        $this->assertEquals(1, Entity::count());
        $this->assertEquals(route('login'), url()->current());
    }

    /** @test */
    public function an_anonymouse_user_cannot_change_an_existing_entities_status()
    {
        $entity = Entity::factory()->create();

        $this->assertTrue($entity->active);

        $this
          ->followingRedirects()
          ->patch(route('entities.update', $entity), ['action' => 'status'])
          ->assertSeeText('login');

        $this->assertTrue($entity->active);
        $this->assertEquals(route('login'), url()->current());
    }

    /** @test */
    public function an_anonymouse_user_cannot_change_an_existing_entities_state()
    {
        $entity = Entity::factory()->create();

        $this->assertFalse($entity->trashed());

        $this
          ->followingRedirects()
          ->patch(route('entities.update', $entity), ['action' => 'state'])
          ->assertSeeText('login');

        $this->assertFalse($entity->trashed());
        $this->assertEquals(route('login'), url()->current());
    }

    /** @test */
    public function an_anonymouse_user_cannot_change_an_existing_entities_operators()
    {
        $entity = Entity::factory()->create();
        $entity->operators()->attach(User::factory()->create());
        $this->assertEquals(1, $entity->operators()->count());

        $user = User::factory()->create();

        $this
          ->followingRedirects()
          ->patch(route('entities.update', $entity), [
              'action' => 'add_operators',
              'operators' => [$user->id],
          ])
          ->assertSeeText('login');

        $this->assertEquals(1, $entity->operators()->count());
        $this->assertEquals(route('login'), url()->current());

        $this
          ->followingRedirects()
          ->patch(route('entities.update', $entity), [
              'action' => 'delete_operators',
              'operators' => [User::find(1)->id],
          ])
          ->assertSeeText('login');

        $this->assertEquals(1, $entity->operators()->count());
        $this->assertEquals(route('login'), url()->current());
    }

    /** @test */
    public function an_anonymouse_user_cannot_change_an_existing_entities_federation_membership()
    {
        $entity = Entity::factory()->create();

        $this
          ->followingRedirects()
          ->post(route('entities.join', $entity))
          ->assertSeeText('login');

        $this
          ->followingRedirects()
          ->post(route('entities.leave', $entity))
          ->assertSeeText('login');
    }

    /** @test */
    public function an_anonymouse_user_cannot_purge_an_existing_entity()
    {
        $entity = Entity::factory()->create([
            'active' => false,
            'deleted_at' => now(),
        ]);

        $this
          ->followingRedirects()
          ->delete(route('entities.destroy', $entity))
          ->assertSeeText('login');

        $this->assertEquals(route('login'), url()->current());
    }

    /** @test */
    public function an_anonymouse_user_cannot_reject_a_new_entity_request()
    {
        $user = User::factory()->create();
        $federation = Federation::factory()->create();
        $entity = Entity::factory()->create(['approved' => false]);
        $entity->federations()->attach($federation, [
            'requested_by' => $user->id,
            'explanation' => $this->faker->catchPhrase(),
        ]);
        $membership = Membership::find(1);

        $this
          ->followingRedirects()
          ->delete(route('memberships.destroy', $membership))
          ->assertSeeText('login');
    }

    /** @test */
    public function an_anonymouse_user_cannot_approve_a_new_entity_request()
    {
        $user = User::factory()->create();
        $federation = Federation::factory()->create();
        $entity = Entity::factory()->create(['approved' => false]);
        $entity->federations()->attach($federation, [
            'requested_by' => $user->id,
            'explanation' => $this->faker->catchPhrase(),
        ]);
        $membership = Membership::find(1);

        $this
          ->followingRedirects()
          ->patch(route('memberships.update', $membership))
          ->assertSeeText('login');
    }

    /** @test */
    public function a_user_is_shown_a_entities_list()
    {
        $this->assertEquals(0, Entity::count());

        $user = User::factory()->create();
        $entity = Entity::factory()->create();

        $this
          ->actingAs($user)
          ->get(route('entities.index'))
          ->assertSeeText($entity->name)
          ->assertSeeText($entity->description)
          ->assertSeeText(__('common.active'));

        $this->assertEquals(1, Entity::count());
        $this->assertEquals(route('entities.index'), url()->current());
    }

    /** @test */
    public function a_user_is_shown_a_entities_details()
    {
        $this->assertEquals(0, Entity::count());

        $user = User::factory()->create();
        $entity = Entity::factory()->create();

        $this
          ->actingAs($user)
          ->get(route('entities.show', $entity))
          ->assertSeeText($entity->name)
          ->assertSeeText($entity->description)
          ->assertSeeText($entity->entityid)
          ->assertSeeText($entity->type->name);

        $this->assertEquals(1, Entity::count());
        $this->assertEquals(route('entities.show', $entity), url()->current());
    }

    /** @test */
    public function a_user_is_shown_a_form_to_add_a_new_entity()
    {
        $user = User::factory()->create();

        $this
          ->actingAs($user)
          ->get(route('entities.create'))
          ->assertSeeText(__('entities.add'));

        $this->assertEquals(route('entities.create'), url()->current());
    }

    /** @test */
    public function a_user_can_add_a_new_entity()
    {
        $user = User::factory()->create();
        $federation = Federation::factory()->create();

        $whoami = '<?xml version="1.0" encoding="UTF-8"?>

        <!-- Do not edit manualy! This file is managed by Ansible. -->
        
        <EntityDescriptor xmlns="urn:oasis:names:tc:SAML:2.0:metadata" xmlns:ds="http://www.w3.org/2000/09/xmldsig#" xmlns:shibmd="urn:mace:shibboleth:metadata:1.0" xmlns:xml="http://www.w3.org/XML/1998/namespace" xmlns:mdui="urn:oasis:names:tc:SAML:metadata:ui" xmlns:req-attr="urn:oasis:names:tc:SAML:protocol:ext:req-attr" entityID="https://whoami.cesnet.cz/idp/shibboleth">
        
          <Extensions>
            <mdattr:EntityAttributes xmlns:mdattr="urn:oasis:names:tc:SAML:metadata:attribute">
              <saml:Attribute xmlns:saml="urn:oasis:names:tc:SAML:2.0:assertion" NameFormat="urn:oasis:names:tc:SAML:2.0:attrname-format:uri" Name="http://macedir.org/entity-category-support">
                <!-- Research and Scholarship -->
                <saml:AttributeValue>http://refeds.org/category/research-and-scholarship</saml:AttributeValue>
                <!-- Code of Conduct -->
                <saml:AttributeValue>http://www.geant.net/uri/dataprotection-code-of-conduct/v1</saml:AttributeValue>
              </saml:Attribute>
              <!-- Sirtfi -->
              <saml:Attribute xmlns:saml="urn:oasis:names:tc:SAML:2.0:assertion" NameFormat="urn:oasis:names:tc:SAML:2.0:attrname-format:uri" Name="urn:oasis:names:tc:SAML:attribute:assurance-certification">
                <saml:AttributeValue>https://refeds.org/sirtfi</saml:AttributeValue>
              </saml:Attribute>
            </mdattr:EntityAttributes>
          </Extensions>
        
          <IDPSSODescriptor protocolSupportEnumeration="urn:oasis:names:tc:SAML:2.0:protocol">
            <Extensions>
              <shibmd:Scope regexp="false">cesnet.cz</shibmd:Scope>
              <mdui:UIInfo>
                <mdui:DisplayName xml:lang="en">CESNET</mdui:DisplayName>
                <mdui:DisplayName xml:lang="cs">CESNET</mdui:DisplayName>
                <mdui:Description xml:lang="en">Identity Provider (IdP) for CESNET employees.</mdui:Description>
                <mdui:Description xml:lang="cs">Poskytovatel identity (IdP) pro zaměstnance CESNETu.</mdui:Description>
                <mdui:InformationURL xml:lang="en">https://www.ces.net/</mdui:InformationURL>
                <mdui:InformationURL xml:lang="cs">https://www.cesnet.cz/</mdui:InformationURL>
                <mdui:Logo height="40" width="99">https://whoami.cesnet.cz/idp/images/cesnet-logo-40.png</mdui:Logo>
              </mdui:UIInfo>
            </Extensions>
        
            <KeyDescriptor use="signing">
              <ds:KeyInfo>
                <ds:X509Data>
                  <ds:X509Certificate>MIIEKzCCApOgAwIBAgIUfhycq2ciNJX9gaQvHYRcI7a+J2QwDQYJKoZIhvcNAQEL
        BQAwGzEZMBcGA1UEAwwQd2hvYW1pLmNlc25ldC5jejAeFw0yMjAzMDEwNzQzNTJa
        Fw0zMjAzMDEwNzQzNTJaMBsxGTAXBgNVBAMMEHdob2FtaS5jZXNuZXQuY3owggGi
        MA0GCSqGSIb3DQEBAQUAA4IBjwAwggGKAoIBgQCA+C/U7dOcl2dneqGpFi2AEVwa
        sytgYWzMOWaGRBBVGJoTfIJbvwI/nyerCK+K/CPO3ChJ4DxG6nDH9FQnxvN3jjhf
        JBH6yBzNew3rDDlFLg+iNOW/srAXvDmNrN5/V91orjZ4qlKViNqvtOZrTwVhSDtF
        QSxAPX7Q6b7zqRkPD531eHxVHCjFyriLacihUBEAevOqxO8xdXseeyZzaEQw7KEK
        EEYFIHvH8VAWV/S/vLXj3S8Vam3AE464u87mCIHkED9XfaZAPkGSBe0SwwGdxQnM
        o72G56BrKgBArlKDd/m8zE/fsaP97pBLx9amNA9u0EVhV4W9de2hepJKQowrXoo1
        3LDE/dpbAkSTdlcsrL/g1eN3ziiZ8yOulJaI3zcOmgxqXPlO+wgeYdRiDnSgnQmm
        qxekeZ5LXQfwrroK96ZJaheTUopO17UKDv7lek+c11nPwJiEQkgDqqD2rbwkdmvR
        JUj5jJSyefoIVhtVLGCiCypwKcZJZCDO3y3vsz0CAwEAAaNnMGUwHQYDVR0OBBYE
        FJyrRUYHac3G6UhW8eibyuLAcn9rMEQGA1UdEQQ9MDuCEHdob2FtaS5jZXNuZXQu
        Y3qGJ2h0dHBzOi8vd2hvYW1pLmNlc25ldC5jei9pZHAvc2hpYmJvbGV0aDANBgkq
        hkiG9w0BAQsFAAOCAYEAGYBXQE8GNMhaPuDoWkH0oL2cI4qoneX9NPaUoSwQ+A7O
        qLY+jMm/wUkLpu+Kh61tXMY5NPWMEZLc23nwILegUSzrNGJqMpHnz8Mv1Ab5Alv5
        FyUIr52VGkyNnJpIiq2XCSjIOnmHUkUdymL8nZf8J3IadRZq8jN9BKJy1vaLThlG
        aAsqC2lZiPMMllYhPaOLtIQtBFEyYO97evmVvVNTZgmmk8fuF6P3gM18LRxXRbpQ
        cQ5VS5nL3LrLi/1cATO8Jz+ERv4zaSLd+2UM0Ft899Ucowjzvoq0vmE+pvvlneNl
        aEorT6nbXFeEsxaYuidg1pxWeoBqruRA0I7iD/4YzVgRHIdwBufQK+x1L4Pihp9U
        ySPhVJJXdEdlSt5MnAjBv/Icn/FlGV1u/X37FqIJ7O/Alb5jfwSu97cgwd4pB6/j
        ao2BQ3zYrSsaVU9qtnv0lE5nzG2b3QyPPkIbUR3nV/n7XOoZmGN8k0jX5N0shAfJ
        wc9tCLkBo8qkL9vQaFAu</ds:X509Certificate>
                </ds:X509Data>
              </ds:KeyInfo>
            </KeyDescriptor>
            <KeyDescriptor use="encryption">
              <ds:KeyInfo>
                <ds:X509Data>
                  <ds:X509Certificate>MIIELDCCApSgAwIBAgIVAI6NHbEG2QoGwKTUzRSIXiHOnnw1MA0GCSqGSIb3DQEB
        CwUAMBsxGTAXBgNVBAMMEHdob2FtaS5jZXNuZXQuY3owHhcNMjIwMzAxMDc0MzUw
        WhcNMzIwMzAxMDc0MzUwWjAbMRkwFwYDVQQDDBB3aG9hbWkuY2VzbmV0LmN6MIIB
        ojANBgkqhkiG9w0BAQEFAAOCAY8AMIIBigKCAYEAn5kgxiNoK5y44dK0j8j0jj1f
        uB9mV2GWz2pZba6ytQoIdcDb8udlEcbrjaD41txziJDS7LD/+meZIU4E6B8p0qgn
        5RnMtQq/HfkmSbxplgoZK+ZLqVoIO422wtX8xgSsBsui9boG6LGEYkrF2+oak1OM
        dd20dlSumVcQuRfsRf1JAlI2c08UlinwhoBf4CPMOigDD8qu95sMCaCR920AfLCR
        29R6U2qz5mvps08yFFpkrTErGKLY6SNPHjrbLhW8VU/lqUuVCdnaMnxz9wPWNhAt
        eE9YZLtPlFhqLbqDCloELc/Ekk5/XhVY0vr+B0rBsXmFaolEi9PnGCkIK665YAwk
        cyarO0gyawE3mcX22HBlH1ysHDA4dpXNXtkr4BAiVTIiJAP8T7EUk1z2BDOsDVWC
        8EWvXU6hfnJUabXzpqMMn8b4lCP730nYxuzYlPXmoq+epQVA23blJ5aVA0WUKicV
        EfIx1y3MW1tRTxM2Ze9ELR/+bIv+AHfJt3ox3P83AgMBAAGjZzBlMB0GA1UdDgQW
        BBR49Q5oZ5TRFe96o82mUye8raq17jBEBgNVHREEPTA7ghB3aG9hbWkuY2VzbmV0
        LmN6hidodHRwczovL3dob2FtaS5jZXNuZXQuY3ovaWRwL3NoaWJib2xldGgwDQYJ
        KoZIhvcNAQELBQADggGBADyYeU0OkhTtG75cXza1ah1wlnrXpVOCM2EjC08PmVMH
        LSWIPF7wfzFfirlXonmzh+aM7iwSsm+DmLfEj59mfm0qb/ZrH6rCM8HHa2gpbFk+
        rIRSO3uYvrUvUnK83ZOJ7TQF5HUp4Wz5dzcHh3eQafT2AykclRd5vE3tASBKLDbc
        Lg67ZG8feQePEOT5bEYaLBSDXKVB+5zcMK3YpIInZLgVlw/ukYdkbMoVPH0SLKC2
        kzBv+3qEAtnytl/1uSmZ+YN9sNT5hCgMC53/+L8sABJaclLEvoOCRvUp6xiewrya
        txSk/8c8JLteuzEWor7DXvQxxHco/Uv5nAfFeGWhML5v8RmBq47TdTTWe3PKw3uf
        sC5E47G4vQh7z8a9zDXkkhN0E73Mv8cm4ArzYXJzW3tvYQSwos+Sdq4rQGnYXZ+1
        3AX2GScvmuCyeA8YIKmW3jqzGtWH0iA7Ic6wCnnDZwXtK76x8FMX8cnU2qMC2aVz
        zFAqLcxs7apHvItdRCAPnA==</ds:X509Certificate>
                </ds:X509Data>
              </ds:KeyInfo>
            </KeyDescriptor>
        
            <!--
            <SingleLogoutService Binding="urn:oasis:names:tc:SAML:2.0:bindings:HTTP-POST" Location="https://whoami.cesnet.cz/idp/profile/SAML2/POST/SLO"/>
            <SingleLogoutService Binding="urn:oasis:names:tc:SAML:2.0:bindings:HTTP-POST-SimpleSign" Location="https://whoami.cesnet.cz/idp/profile/SAML2/POST-SimpleSign/SLO"/>
            <SingleLogoutService Binding="urn:oasis:names:tc:SAML:2.0:bindings:HTTP-Redirect" Location="https://whoami.cesnet.cz/idp/profile/SAML2/Redirect/SLO"/>
            -->
        
            <SingleSignOnService Binding="urn:oasis:names:tc:SAML:2.0:bindings:HTTP-Redirect" req-attr:supportsRequestedAttributes="true" Location="https://whoami.cesnet.cz/idp/profile/SAML2/Redirect/SSO"/>
            <SingleSignOnService Binding="urn:oasis:names:tc:SAML:2.0:bindings:HTTP-POST" req-attr:supportsRequestedAttributes="true" Location="https://whoami.cesnet.cz/idp/profile/SAML2/POST/SSO"/>
            <SingleSignOnService Binding="urn:oasis:names:tc:SAML:2.0:bindings:HTTP-POST-SimpleSign" req-attr:supportsRequestedAttributes="true" Location="https://whoami.cesnet.cz/idp/profile/SAML2/POST-SimpleSign/SSO"/>
        
          </IDPSSODescriptor>
        
          <Organization>
            <OrganizationName xml:lang="en">CESNET, a. l. e.</OrganizationName>
            <OrganizationName xml:lang="cs">CESNET, z. s. p. o.</OrganizationName>
            <OrganizationDisplayName xml:lang="en">CESNET</OrganizationDisplayName>
            <OrganizationDisplayName xml:lang="cs">CESNET</OrganizationDisplayName>
            <OrganizationURL xml:lang="en">https://www.ces.net/</OrganizationURL>
            <OrganizationURL xml:lang="cs">https://www.cesnet.cz/</OrganizationURL>
          </Organization>
        
          <ContactPerson contactType="technical">
            <GivenName>Jan</GivenName>
            <SurName>Oppolzer</SurName>
            <EmailAddress>mailto:jan.oppolzer@cesnet.cz</EmailAddress>
          </ContactPerson>
          <ContactPerson contactType="technical">
            <GivenName>Jan</GivenName>
            <SurName>Tomášek</SurName>
            <EmailAddress>mailto:jan.tomasek@cesnet.cz</EmailAddress>
          </ContactPerson>
          <ContactPerson contactType="technical">
            <GivenName>Jan</GivenName>
            <SurName>Chvojka</SurName>
            <EmailAddress>mailto:jan.chvojka@cesnet.cz</EmailAddress>
          </ContactPerson>
          <ContactPerson contactType="other" xmlns:remd="http://refeds.org/metadata" remd:contactType="http://refeds.org/metadata/contactType/security">
            <GivenName>CESNET-CERTS</GivenName>
            <EmailAddress>mailto:abuse@cesnet.cz</EmailAddress>
          </ContactPerson>
        
        </EntityDescriptor>';

        // add an entity using wrong metadata content
        // $this
    //     ->followingRedirects()
    //     ->actingAs($user)
    //     ->post(route('entities.store', [
    //         'metadata' => '',
    //         'federation' => $federation->id,
    //         'explanation' => $this->faker->catchPhrase(),
    //     ]))
    //     ->assertSeeText(__('entities.no_metadata'));

        $this->assertEquals(0, Entity::count());

        // add an entity using corrent metadata content
        $this
          ->followingRedirects()
          ->actingAs($user)
          ->post(route('entities.store', [
              'metadata' => $whoami,
              'federation' => $federation->id,
              'explanation' => $this->faker->catchPhrase(),
          ]))
          ->assertSeeText(__('entities.entity_requested', ['name' => 'https://whoami.cesnet.cz/idp/shibboleth']));

        $this->assertEquals(1, Entity::count());
        $this->assertEquals(route('entities.index'), url()->current());

        // add already existing entity
        $this
          ->followingRedirects()
          ->actingAs($user)
          ->post(route('entities.store', [
              'metadata' => $whoami,
              'federation' => $federation->id,
              'explanation' => $this->faker->catchPhrase(),
          ]))
          ->assertSeeText(__('entities.existing_already'));

        $this->assertEquals(1, Entity::count());
        $this->assertEquals(route('entities.show', Entity::find(1)), url()->current());
    }

    /** @test */
    public function a_user_with_operator_permission_can_see_entities_edit_page()
    {
        $user = User::factory()->create();
        $entity = Entity::factory()->create();
        $user->entities()->attach($entity);

        $this
          ->actingAs($user)
          ->get(route('entities.edit', $entity))
          ->assertSeeText(__('entities.edit', ['name' => $entity->name_en]))
          ->assertSeeText(__('entities.profile'));

        $this->assertEquals(route('entities.edit', $entity), url()->current());
    }

    /** @test */
    public function a_user_with_operator_permission_can_edit_an_existing_entity()
    {
        Bus::fake();

        $user = User::factory()->create();
        $entity = Entity::factory()->create(['entityid' => 'https://whoami.cesnet.cz/idp/shibboleth']);
        $user->entities()->attach($entity);

        $whoami = '<?xml version="1.0" encoding="UTF-8"?>

        <!-- Do not edit manualy! This file is managed by Ansible. -->
        
        <EntityDescriptor xmlns="urn:oasis:names:tc:SAML:2.0:metadata" xmlns:ds="http://www.w3.org/2000/09/xmldsig#" xmlns:shibmd="urn:mace:shibboleth:metadata:1.0" xmlns:xml="http://www.w3.org/XML/1998/namespace" xmlns:mdui="urn:oasis:names:tc:SAML:metadata:ui" xmlns:req-attr="urn:oasis:names:tc:SAML:protocol:ext:req-attr" entityID="https://whoami.cesnet.cz/idp/shibboleth">
        
          <Extensions>
            <mdattr:EntityAttributes xmlns:mdattr="urn:oasis:names:tc:SAML:metadata:attribute">
              <saml:Attribute xmlns:saml="urn:oasis:names:tc:SAML:2.0:assertion" NameFormat="urn:oasis:names:tc:SAML:2.0:attrname-format:uri" Name="http://macedir.org/entity-category-support">
                <!-- Research and Scholarship -->
                <saml:AttributeValue>http://refeds.org/category/research-and-scholarship</saml:AttributeValue>
                <!-- Code of Conduct -->
                <saml:AttributeValue>http://www.geant.net/uri/dataprotection-code-of-conduct/v1</saml:AttributeValue>
              </saml:Attribute>
              <!-- Sirtfi -->
              <saml:Attribute xmlns:saml="urn:oasis:names:tc:SAML:2.0:assertion" NameFormat="urn:oasis:names:tc:SAML:2.0:attrname-format:uri" Name="urn:oasis:names:tc:SAML:attribute:assurance-certification">
                <saml:AttributeValue>https://refeds.org/sirtfi</saml:AttributeValue>
              </saml:Attribute>
            </mdattr:EntityAttributes>
          </Extensions>
        
          <IDPSSODescriptor protocolSupportEnumeration="urn:oasis:names:tc:SAML:2.0:protocol">
            <Extensions>
              <shibmd:Scope regexp="false">cesnet.cz</shibmd:Scope>
              <mdui:UIInfo>
                <mdui:DisplayName xml:lang="en">CESNET</mdui:DisplayName>
                <mdui:DisplayName xml:lang="cs">CESNET</mdui:DisplayName>
                <mdui:Description xml:lang="en">Identity Provider (IdP) for CESNET employees.</mdui:Description>
                <mdui:Description xml:lang="cs">Poskytovatel identity (IdP) pro zaměstnance CESNETu.</mdui:Description>
                <mdui:InformationURL xml:lang="en">https://www.ces.net/</mdui:InformationURL>
                <mdui:InformationURL xml:lang="cs">https://www.cesnet.cz/</mdui:InformationURL>
                <mdui:Logo height="40" width="99">https://whoami.cesnet.cz/idp/images/cesnet-logo-40.png</mdui:Logo>
              </mdui:UIInfo>
            </Extensions>
        
            <KeyDescriptor use="signing">
              <ds:KeyInfo>
                <ds:X509Data>
                  <ds:X509Certificate>MIIEKzCCApOgAwIBAgIUfhycq2ciNJX9gaQvHYRcI7a+J2QwDQYJKoZIhvcNAQEL
        BQAwGzEZMBcGA1UEAwwQd2hvYW1pLmNlc25ldC5jejAeFw0yMjAzMDEwNzQzNTJa
        Fw0zMjAzMDEwNzQzNTJaMBsxGTAXBgNVBAMMEHdob2FtaS5jZXNuZXQuY3owggGi
        MA0GCSqGSIb3DQEBAQUAA4IBjwAwggGKAoIBgQCA+C/U7dOcl2dneqGpFi2AEVwa
        sytgYWzMOWaGRBBVGJoTfIJbvwI/nyerCK+K/CPO3ChJ4DxG6nDH9FQnxvN3jjhf
        JBH6yBzNew3rDDlFLg+iNOW/srAXvDmNrN5/V91orjZ4qlKViNqvtOZrTwVhSDtF
        QSxAPX7Q6b7zqRkPD531eHxVHCjFyriLacihUBEAevOqxO8xdXseeyZzaEQw7KEK
        EEYFIHvH8VAWV/S/vLXj3S8Vam3AE464u87mCIHkED9XfaZAPkGSBe0SwwGdxQnM
        o72G56BrKgBArlKDd/m8zE/fsaP97pBLx9amNA9u0EVhV4W9de2hepJKQowrXoo1
        3LDE/dpbAkSTdlcsrL/g1eN3ziiZ8yOulJaI3zcOmgxqXPlO+wgeYdRiDnSgnQmm
        qxekeZ5LXQfwrroK96ZJaheTUopO17UKDv7lek+c11nPwJiEQkgDqqD2rbwkdmvR
        JUj5jJSyefoIVhtVLGCiCypwKcZJZCDO3y3vsz0CAwEAAaNnMGUwHQYDVR0OBBYE
        FJyrRUYHac3G6UhW8eibyuLAcn9rMEQGA1UdEQQ9MDuCEHdob2FtaS5jZXNuZXQu
        Y3qGJ2h0dHBzOi8vd2hvYW1pLmNlc25ldC5jei9pZHAvc2hpYmJvbGV0aDANBgkq
        hkiG9w0BAQsFAAOCAYEAGYBXQE8GNMhaPuDoWkH0oL2cI4qoneX9NPaUoSwQ+A7O
        qLY+jMm/wUkLpu+Kh61tXMY5NPWMEZLc23nwILegUSzrNGJqMpHnz8Mv1Ab5Alv5
        FyUIr52VGkyNnJpIiq2XCSjIOnmHUkUdymL8nZf8J3IadRZq8jN9BKJy1vaLThlG
        aAsqC2lZiPMMllYhPaOLtIQtBFEyYO97evmVvVNTZgmmk8fuF6P3gM18LRxXRbpQ
        cQ5VS5nL3LrLi/1cATO8Jz+ERv4zaSLd+2UM0Ft899Ucowjzvoq0vmE+pvvlneNl
        aEorT6nbXFeEsxaYuidg1pxWeoBqruRA0I7iD/4YzVgRHIdwBufQK+x1L4Pihp9U
        ySPhVJJXdEdlSt5MnAjBv/Icn/FlGV1u/X37FqIJ7O/Alb5jfwSu97cgwd4pB6/j
        ao2BQ3zYrSsaVU9qtnv0lE5nzG2b3QyPPkIbUR3nV/n7XOoZmGN8k0jX5N0shAfJ
        wc9tCLkBo8qkL9vQaFAu</ds:X509Certificate>
                </ds:X509Data>
              </ds:KeyInfo>
            </KeyDescriptor>
            <KeyDescriptor use="encryption">
              <ds:KeyInfo>
                <ds:X509Data>
                  <ds:X509Certificate>MIIELDCCApSgAwIBAgIVAI6NHbEG2QoGwKTUzRSIXiHOnnw1MA0GCSqGSIb3DQEB
        CwUAMBsxGTAXBgNVBAMMEHdob2FtaS5jZXNuZXQuY3owHhcNMjIwMzAxMDc0MzUw
        WhcNMzIwMzAxMDc0MzUwWjAbMRkwFwYDVQQDDBB3aG9hbWkuY2VzbmV0LmN6MIIB
        ojANBgkqhkiG9w0BAQEFAAOCAY8AMIIBigKCAYEAn5kgxiNoK5y44dK0j8j0jj1f
        uB9mV2GWz2pZba6ytQoIdcDb8udlEcbrjaD41txziJDS7LD/+meZIU4E6B8p0qgn
        5RnMtQq/HfkmSbxplgoZK+ZLqVoIO422wtX8xgSsBsui9boG6LGEYkrF2+oak1OM
        dd20dlSumVcQuRfsRf1JAlI2c08UlinwhoBf4CPMOigDD8qu95sMCaCR920AfLCR
        29R6U2qz5mvps08yFFpkrTErGKLY6SNPHjrbLhW8VU/lqUuVCdnaMnxz9wPWNhAt
        eE9YZLtPlFhqLbqDCloELc/Ekk5/XhVY0vr+B0rBsXmFaolEi9PnGCkIK665YAwk
        cyarO0gyawE3mcX22HBlH1ysHDA4dpXNXtkr4BAiVTIiJAP8T7EUk1z2BDOsDVWC
        8EWvXU6hfnJUabXzpqMMn8b4lCP730nYxuzYlPXmoq+epQVA23blJ5aVA0WUKicV
        EfIx1y3MW1tRTxM2Ze9ELR/+bIv+AHfJt3ox3P83AgMBAAGjZzBlMB0GA1UdDgQW
        BBR49Q5oZ5TRFe96o82mUye8raq17jBEBgNVHREEPTA7ghB3aG9hbWkuY2VzbmV0
        LmN6hidodHRwczovL3dob2FtaS5jZXNuZXQuY3ovaWRwL3NoaWJib2xldGgwDQYJ
        KoZIhvcNAQELBQADggGBADyYeU0OkhTtG75cXza1ah1wlnrXpVOCM2EjC08PmVMH
        LSWIPF7wfzFfirlXonmzh+aM7iwSsm+DmLfEj59mfm0qb/ZrH6rCM8HHa2gpbFk+
        rIRSO3uYvrUvUnK83ZOJ7TQF5HUp4Wz5dzcHh3eQafT2AykclRd5vE3tASBKLDbc
        Lg67ZG8feQePEOT5bEYaLBSDXKVB+5zcMK3YpIInZLgVlw/ukYdkbMoVPH0SLKC2
        kzBv+3qEAtnytl/1uSmZ+YN9sNT5hCgMC53/+L8sABJaclLEvoOCRvUp6xiewrya
        txSk/8c8JLteuzEWor7DXvQxxHco/Uv5nAfFeGWhML5v8RmBq47TdTTWe3PKw3uf
        sC5E47G4vQh7z8a9zDXkkhN0E73Mv8cm4ArzYXJzW3tvYQSwos+Sdq4rQGnYXZ+1
        3AX2GScvmuCyeA8YIKmW3jqzGtWH0iA7Ic6wCnnDZwXtK76x8FMX8cnU2qMC2aVz
        zFAqLcxs7apHvItdRCAPnA==</ds:X509Certificate>
                </ds:X509Data>
              </ds:KeyInfo>
            </KeyDescriptor>
        
            <!--
            <SingleLogoutService Binding="urn:oasis:names:tc:SAML:2.0:bindings:HTTP-POST" Location="https://whoami.cesnet.cz/idp/profile/SAML2/POST/SLO"/>
            <SingleLogoutService Binding="urn:oasis:names:tc:SAML:2.0:bindings:HTTP-POST-SimpleSign" Location="https://whoami.cesnet.cz/idp/profile/SAML2/POST-SimpleSign/SLO"/>
            <SingleLogoutService Binding="urn:oasis:names:tc:SAML:2.0:bindings:HTTP-Redirect" Location="https://whoami.cesnet.cz/idp/profile/SAML2/Redirect/SLO"/>
            -->
        
            <SingleSignOnService Binding="urn:oasis:names:tc:SAML:2.0:bindings:HTTP-Redirect" req-attr:supportsRequestedAttributes="true" Location="https://whoami.cesnet.cz/idp/profile/SAML2/Redirect/SSO"/>
            <SingleSignOnService Binding="urn:oasis:names:tc:SAML:2.0:bindings:HTTP-POST" req-attr:supportsRequestedAttributes="true" Location="https://whoami.cesnet.cz/idp/profile/SAML2/POST/SSO"/>
            <SingleSignOnService Binding="urn:oasis:names:tc:SAML:2.0:bindings:HTTP-POST-SimpleSign" req-attr:supportsRequestedAttributes="true" Location="https://whoami.cesnet.cz/idp/profile/SAML2/POST-SimpleSign/SSO"/>
        
          </IDPSSODescriptor>
        
          <Organization>
            <OrganizationName xml:lang="en">CESNET, a. l. e.</OrganizationName>
            <OrganizationName xml:lang="cs">CESNET, z. s. p. o.</OrganizationName>
            <OrganizationDisplayName xml:lang="en">CESNET</OrganizationDisplayName>
            <OrganizationDisplayName xml:lang="cs">CESNET</OrganizationDisplayName>
            <OrganizationURL xml:lang="en">https://www.ces.net/</OrganizationURL>
            <OrganizationURL xml:lang="cs">https://www.cesnet.cz/</OrganizationURL>
          </Organization>
        
          <ContactPerson contactType="technical">
            <GivenName>Jan</GivenName>
            <SurName>Oppolzer</SurName>
            <EmailAddress>mailto:jan.oppolzer@cesnet.cz</EmailAddress>
          </ContactPerson>
          <ContactPerson contactType="technical">
            <GivenName>Jan</GivenName>
            <SurName>Tomášek</SurName>
            <EmailAddress>mailto:jan.tomasek@cesnet.cz</EmailAddress>
          </ContactPerson>
          <ContactPerson contactType="technical">
            <GivenName>Jan</GivenName>
            <SurName>Chvojka</SurName>
            <EmailAddress>mailto:jan.chvojka@cesnet.cz</EmailAddress>
          </ContactPerson>
          <ContactPerson contactType="other" xmlns:remd="http://refeds.org/metadata" remd:contactType="http://refeds.org/metadata/contactType/security">
            <GivenName>CESNET-CERTS</GivenName>
            <EmailAddress>mailto:abuse@cesnet.cz</EmailAddress>
          </ContactPerson>
        
        </EntityDescriptor>';

        $this
          ->followingRedirects()
          ->actingAs($user)
          ->patch(route('entities.update', $entity), [
              'action' => 'update',
              'metadata' => $whoami,
          ])
          ->assertSeeText(__('entities.entity_updated'));

        $this->assertEquals(route('entities.show', $entity), url()->current());

        Bus::assertDispatched(GitUpdateEntity::class, function ($job) use ($entity) {
            return $job->entity->is($entity);
        });
    }

    /** @test */
    public function a_user_with_operator_permission_can_change_an_existing_entities_status()
    {
        $user = User::factory()->create();
        $entity = Entity::factory()->create();
        $user->entities()->attach($entity);

        $this->assertTrue($entity->active);

        $this
          ->followingRedirects()
          ->actingAs($user)
          ->patch(route('entities.update', $entity), ['action' => 'status'])
          ->assertSeeText(__('entities.inactive', ['name' => $entity->name_en]));

        $entity->refresh();
        $this->assertFalse($entity->active);
        $this->assertEquals(route('entities.show', $entity), url()->current());

        $this
          ->followingRedirects()
          ->actingAs($user)
          ->patch(route('entities.update', $entity), ['action' => 'status'])
          ->assertSeeText(__('entities.active', ['name' => $entity->name_en]));

        $entity->refresh();
        $this->assertTrue($entity->active);
        $this->assertEquals(route('entities.show', $entity), url()->current());
    }

    /** @test */
    public function a_user_with_operator_permission_can_change_an_existing_entities_state()
    {
        Bus::fake();

        $user = User::factory()->create();
        $entity = Entity::factory()->create();
        $user->entities()->attach($entity);

        $this->assertEquals(1, Entity::count());
        $this->assertFalse($entity->trashed());

        $this
          ->followingRedirects()
          ->actingAs($user)
          ->patch(route('entities.update', $entity), ['action' => 'state'])
          ->assertSeeText(__('entities.deleted', ['name' => $entity->name_en]));

        Bus::assertDispatched(GitDeleteEntity::class, function ($job) use ($entity) {
            return $job->entity->is($entity);
        });

        $entity->refresh();
        $this->assertTrue($entity->trashed());
        $this->assertEquals(route('entities.show', $entity), url()->current());

        $this
          ->followingRedirects()
          ->actingAs($user)
          ->patch(route('entities.update', $entity), ['action' => 'state'])
          ->assertSeeText(__('entities.restored', ['name' => $entity->name_en]));

        $entity->refresh();
        $this->assertFalse($entity->trashed());
        $this->assertEquals(route('entities.show', $entity), url()->current());

        Bus::assertDispatched(GitAddEntity::class, function ($job) use ($entity) {
            return $job->entity->is($entity);
        });
    }

    /** @test */
    public function a_user_with_operator_permission_can_change_an_existing_entities_operators()
    {
        $user = User::factory()->create();
        $entity = Entity::factory()->create();
        $user->entities()->attach($entity);
        $new_operator = User::factory()->create();

        $this->assertEquals(1, $entity->operators()->count());

        $this
          ->followingRedirects()
          ->actingAs($user)
          ->patch(route('entities.update', $entity), [
              'action' => 'add_operators',
              'operators' => [$new_operator->id],
          ])
          ->assertSeeText(__('entities.operators_added'));

        $entity->refresh();
        $this->assertEquals(2, $entity->operators()->count());
        $this->assertEquals(route('entities.show', $entity), url()->current());

        $this
          ->followingRedirects()
          ->actingAs($user)
          ->patch(route('entities.update', $entity), [
              'action' => 'delete_operators',
              'operators' => [$new_operator->id],
          ])
          ->assertSeeText(__('entities.operators_deleted'));

        $entity->refresh();
        $this->assertEquals(1, $entity->operators()->count());
        $this->assertEquals(route('entities.show', $entity), url()->current());
    }

    /** @test */
    public function a_user_with_operator_permission_can_change_an_existing_entities_federation_membership()
    {
        $user = User::factory()->create();
        $entity = Entity::factory()->create();
        $user->entities()->attach($entity);
        $federation = Federation::factory()->create();

        $this->assertTrue($entity->active);
        $this->assertTrue($federation->active);
        $this->assertEquals(1, $user->entities()->count());
        $this->assertEquals(0, Membership::whereApproved(false)->count());

        $this
          ->followingRedirects()
          ->actingAs($user)
          ->post(route('entities.join', $entity), [
              'federation' => $federation->id,
              'explanation' => $this->faker->sentence(),
          ])
          ->assertSeeText(__('entities.join_requested', ['name' => $federation->name]));

        $this->assertEquals(1, Membership::whereApproved(false)->count());
    }

    /** @test */
    public function a_user_without_operator_permission_cannot_see_entities_edit_page()
    {
        $user = User::factory()->create();
        $entity = Entity::factory()->create();

        $this
          ->actingAs($user)
          ->get(route('entities.edit', $entity))
          ->assertForbidden();
    }

    /** @test */
    public function a_user_without_operator_permission_cannot_edit_an_existing_entity()
    {
        $entity = Entity::factory()->create(['entityid' => 'https://whoami.cesnet.cz/idp/shibboleth']);

        $this
          ->followingRedirects()
          ->patch(route('entities.update', $entity), [
              'action' => 'update',
              'url' => 'https://whoami.cesnet.cz/idp/shibboleth',
          ])
          ->assertSeeText('login');
    }

    /** @test */
    public function a_user_without_operator_permission_cannot_change_an_existing_entities_status()
    {
        $user = User::factory()->create();
        $entity = Entity::factory()->create();

        $this->assertEquals(1, Entity::count());

        $this
          ->actingAs($user)
          ->patch(route('entities.update', $entity), ['action' => 'status'])
          ->assertForbidden();
    }

    /** @test */
    public function a_user_without_operator_permission_cannot_change_an_existing_entities_state()
    {
        $user = User::factory()->create();
        $entity = Entity::factory()->create();

        $this->assertEquals(1, Entity::count());

        $this
          ->actingAs($user)
          ->patch(route('entities.update', $entity), ['action' => 'state'])
          ->assertForbidden();
    }

    /** @test */
    public function a_user_without_operator_permission_cannot_change_an_existing_entities_operators()
    {
        $user = User::factory()->create();
        $entity = Entity::factory()->create();
        $new_operator = User::factory()->create();

        $this
          ->actingAs($user)
          ->patch(route('entities.update', $entity), [
              'action' => 'add_operators',
              'operators' => [$new_operator->id],
          ])
          ->assertForbidden();

        $entity->refresh();
        $this->assertEquals(0, $entity->operators()->count());

        $this
          ->actingAs($user)
          ->patch(route('entities.update', $entity), [
              'action' => 'delete_operators',
              'operators' => [$new_operator->id],
          ])
          ->assertForbidden();
    }

    /** @test */
    public function a_user_without_operator_permission_cannot_change_an_existing_entities_federation_membership()
    {
        $user = User::factory()->create();
        $entity = Entity::factory()->create();
        $federation = Federation::factory()->create();

        $this->assertTrue($entity->active);
        $this->assertTrue($federation->active);
        $this->assertEquals(0, $user->entities()->count());
        $this->assertEquals(0, Membership::whereApproved(false)->count());

        $this
          ->followingRedirects()
          ->actingAs($user)
          ->post(route('entities.join', $entity), [
              'federation' => $federation->id,
              'explanation' => $this->faker->sentence(),
          ])
          ->assertForbidden();

        $this->assertEquals(0, Membership::whereApproved(false)->count());
    }

    /** @test */
    public function a_user_cannot_purge_an_existing_entity()
    {
        $user = User::factory()->create();
        $entity = Entity::factory()->create([
            'active' => false,
            'deleted_at' => now(),
        ]);

        $this
          ->followingRedirects()
          ->actingAs($user)
          ->delete(route('entities.destroy', $entity))
          ->assertForbidden();
    }

    /** @test */
    public function a_user_cannot_reject_a_new_entity_request()
    {
        $user = User::factory()->create();
        $operator = User::factory()->create();
        $federation = Federation::factory()->create();
        $entity = Entity::factory()->create(['approved' => false]);
        $entity->federations()->attach($federation, [
            'requested_by' => $operator->id,
            'explanation' => $this->faker->catchPhrase(),
        ]);
        $membership = Membership::find(1);

        $this
          ->followingRedirects()
          ->actingAs($user)
          ->delete(route('memberships.destroy', $membership))
          ->assertForbidden();
    }

    /** @test */
    public function a_user_cannot_approve_a_new_entity_request()
    {
        $user = User::factory()->create();
        $operator = User::factory()->create();
        $federation = Federation::factory()->create();
        $entity = Entity::factory()->create(['approved' => false]);
        $operator->entities()->attach($entity);
        $entity->federations()->attach($federation, [
            'requested_by' => $operator->id,
            'explanation' => $this->faker->catchPhrase(),
        ]);
        $membership = Membership::find(1);

        $this
          ->followingRedirects()
          ->actingAs($user)
          ->patch(route('memberships.update', $membership))
          ->assertForbidden();
    }

    /** @test */
    public function an_admin_is_shown_a_entities_list()
    {
        $admin = User::factory()->create(['admin' => true]);
        $entity = Entity::factory()->create();

        $this
          ->actingAs($admin)
          ->get(route('entities.index'))
          ->assertSeeText($entity->name)
          ->assertSeeText($entity->description)
          ->assertSeeText(__('common.active'));

        $this->assertEquals(1, Entity::count());
        $this->assertEquals(route('entities.index'), url()->current());
    }

    /** @test */
    public function an_admin_is_shown_a_entities_details()
    {
        $admin = User::factory()->create(['admin' => true]);
        $entity = Entity::factory()->create();

        $this
          ->actingAs($admin)
          ->get(route('entities.show', $entity))
          ->assertSeeText($entity->name)
          ->assertSeeText($entity->description)
          ->assertSeeText($entity->entityid)
          ->assertSeeText($entity->type->name);

        $this->assertEquals(1, Entity::count());
        $this->assertEquals(route('entities.show', $entity), url()->current());
    }

    /** @test */
    public function an_admin_is_shown_a_form_to_add_a_new_entity()
    {
        $admin = User::factory()->create(['admin' => true]);

        $this
          ->actingAs($admin)
          ->get(route('entities.create'))
          ->assertSeeText(__('entities.add'));

        $this->assertEquals(route('entities.create'), url()->current());
    }

    /** @test */
    public function an_admin_can_add_a_new_entity()
    {
        $admin = User::factory()->create(['admin' => true]);
        $federation = Federation::factory()->create();

        $whoami = '<?xml version="1.0" encoding="UTF-8"?>

        <!-- Do not edit manualy! This file is managed by Ansible. -->
        
        <EntityDescriptor xmlns="urn:oasis:names:tc:SAML:2.0:metadata" xmlns:ds="http://www.w3.org/2000/09/xmldsig#" xmlns:shibmd="urn:mace:shibboleth:metadata:1.0" xmlns:xml="http://www.w3.org/XML/1998/namespace" xmlns:mdui="urn:oasis:names:tc:SAML:metadata:ui" xmlns:req-attr="urn:oasis:names:tc:SAML:protocol:ext:req-attr" entityID="https://whoami.cesnet.cz/idp/shibboleth">
        
          <Extensions>
            <mdattr:EntityAttributes xmlns:mdattr="urn:oasis:names:tc:SAML:metadata:attribute">
              <saml:Attribute xmlns:saml="urn:oasis:names:tc:SAML:2.0:assertion" NameFormat="urn:oasis:names:tc:SAML:2.0:attrname-format:uri" Name="http://macedir.org/entity-category-support">
                <!-- Research and Scholarship -->
                <saml:AttributeValue>http://refeds.org/category/research-and-scholarship</saml:AttributeValue>
                <!-- Code of Conduct -->
                <saml:AttributeValue>http://www.geant.net/uri/dataprotection-code-of-conduct/v1</saml:AttributeValue>
              </saml:Attribute>
              <!-- Sirtfi -->
              <saml:Attribute xmlns:saml="urn:oasis:names:tc:SAML:2.0:assertion" NameFormat="urn:oasis:names:tc:SAML:2.0:attrname-format:uri" Name="urn:oasis:names:tc:SAML:attribute:assurance-certification">
                <saml:AttributeValue>https://refeds.org/sirtfi</saml:AttributeValue>
              </saml:Attribute>
            </mdattr:EntityAttributes>
          </Extensions>
        
          <IDPSSODescriptor protocolSupportEnumeration="urn:oasis:names:tc:SAML:2.0:protocol">
            <Extensions>
              <shibmd:Scope regexp="false">cesnet.cz</shibmd:Scope>
              <mdui:UIInfo>
                <mdui:DisplayName xml:lang="en">CESNET</mdui:DisplayName>
                <mdui:DisplayName xml:lang="cs">CESNET</mdui:DisplayName>
                <mdui:Description xml:lang="en">Identity Provider (IdP) for CESNET employees.</mdui:Description>
                <mdui:Description xml:lang="cs">Poskytovatel identity (IdP) pro zaměstnance CESNETu.</mdui:Description>
                <mdui:InformationURL xml:lang="en">https://www.ces.net/</mdui:InformationURL>
                <mdui:InformationURL xml:lang="cs">https://www.cesnet.cz/</mdui:InformationURL>
                <mdui:Logo height="40" width="99">https://whoami.cesnet.cz/idp/images/cesnet-logo-40.png</mdui:Logo>
              </mdui:UIInfo>
            </Extensions>
        
            <KeyDescriptor use="signing">
              <ds:KeyInfo>
                <ds:X509Data>
                  <ds:X509Certificate>MIIEKzCCApOgAwIBAgIUfhycq2ciNJX9gaQvHYRcI7a+J2QwDQYJKoZIhvcNAQEL
        BQAwGzEZMBcGA1UEAwwQd2hvYW1pLmNlc25ldC5jejAeFw0yMjAzMDEwNzQzNTJa
        Fw0zMjAzMDEwNzQzNTJaMBsxGTAXBgNVBAMMEHdob2FtaS5jZXNuZXQuY3owggGi
        MA0GCSqGSIb3DQEBAQUAA4IBjwAwggGKAoIBgQCA+C/U7dOcl2dneqGpFi2AEVwa
        sytgYWzMOWaGRBBVGJoTfIJbvwI/nyerCK+K/CPO3ChJ4DxG6nDH9FQnxvN3jjhf
        JBH6yBzNew3rDDlFLg+iNOW/srAXvDmNrN5/V91orjZ4qlKViNqvtOZrTwVhSDtF
        QSxAPX7Q6b7zqRkPD531eHxVHCjFyriLacihUBEAevOqxO8xdXseeyZzaEQw7KEK
        EEYFIHvH8VAWV/S/vLXj3S8Vam3AE464u87mCIHkED9XfaZAPkGSBe0SwwGdxQnM
        o72G56BrKgBArlKDd/m8zE/fsaP97pBLx9amNA9u0EVhV4W9de2hepJKQowrXoo1
        3LDE/dpbAkSTdlcsrL/g1eN3ziiZ8yOulJaI3zcOmgxqXPlO+wgeYdRiDnSgnQmm
        qxekeZ5LXQfwrroK96ZJaheTUopO17UKDv7lek+c11nPwJiEQkgDqqD2rbwkdmvR
        JUj5jJSyefoIVhtVLGCiCypwKcZJZCDO3y3vsz0CAwEAAaNnMGUwHQYDVR0OBBYE
        FJyrRUYHac3G6UhW8eibyuLAcn9rMEQGA1UdEQQ9MDuCEHdob2FtaS5jZXNuZXQu
        Y3qGJ2h0dHBzOi8vd2hvYW1pLmNlc25ldC5jei9pZHAvc2hpYmJvbGV0aDANBgkq
        hkiG9w0BAQsFAAOCAYEAGYBXQE8GNMhaPuDoWkH0oL2cI4qoneX9NPaUoSwQ+A7O
        qLY+jMm/wUkLpu+Kh61tXMY5NPWMEZLc23nwILegUSzrNGJqMpHnz8Mv1Ab5Alv5
        FyUIr52VGkyNnJpIiq2XCSjIOnmHUkUdymL8nZf8J3IadRZq8jN9BKJy1vaLThlG
        aAsqC2lZiPMMllYhPaOLtIQtBFEyYO97evmVvVNTZgmmk8fuF6P3gM18LRxXRbpQ
        cQ5VS5nL3LrLi/1cATO8Jz+ERv4zaSLd+2UM0Ft899Ucowjzvoq0vmE+pvvlneNl
        aEorT6nbXFeEsxaYuidg1pxWeoBqruRA0I7iD/4YzVgRHIdwBufQK+x1L4Pihp9U
        ySPhVJJXdEdlSt5MnAjBv/Icn/FlGV1u/X37FqIJ7O/Alb5jfwSu97cgwd4pB6/j
        ao2BQ3zYrSsaVU9qtnv0lE5nzG2b3QyPPkIbUR3nV/n7XOoZmGN8k0jX5N0shAfJ
        wc9tCLkBo8qkL9vQaFAu</ds:X509Certificate>
                </ds:X509Data>
              </ds:KeyInfo>
            </KeyDescriptor>
            <KeyDescriptor use="encryption">
              <ds:KeyInfo>
                <ds:X509Data>
                  <ds:X509Certificate>MIIELDCCApSgAwIBAgIVAI6NHbEG2QoGwKTUzRSIXiHOnnw1MA0GCSqGSIb3DQEB
        CwUAMBsxGTAXBgNVBAMMEHdob2FtaS5jZXNuZXQuY3owHhcNMjIwMzAxMDc0MzUw
        WhcNMzIwMzAxMDc0MzUwWjAbMRkwFwYDVQQDDBB3aG9hbWkuY2VzbmV0LmN6MIIB
        ojANBgkqhkiG9w0BAQEFAAOCAY8AMIIBigKCAYEAn5kgxiNoK5y44dK0j8j0jj1f
        uB9mV2GWz2pZba6ytQoIdcDb8udlEcbrjaD41txziJDS7LD/+meZIU4E6B8p0qgn
        5RnMtQq/HfkmSbxplgoZK+ZLqVoIO422wtX8xgSsBsui9boG6LGEYkrF2+oak1OM
        dd20dlSumVcQuRfsRf1JAlI2c08UlinwhoBf4CPMOigDD8qu95sMCaCR920AfLCR
        29R6U2qz5mvps08yFFpkrTErGKLY6SNPHjrbLhW8VU/lqUuVCdnaMnxz9wPWNhAt
        eE9YZLtPlFhqLbqDCloELc/Ekk5/XhVY0vr+B0rBsXmFaolEi9PnGCkIK665YAwk
        cyarO0gyawE3mcX22HBlH1ysHDA4dpXNXtkr4BAiVTIiJAP8T7EUk1z2BDOsDVWC
        8EWvXU6hfnJUabXzpqMMn8b4lCP730nYxuzYlPXmoq+epQVA23blJ5aVA0WUKicV
        EfIx1y3MW1tRTxM2Ze9ELR/+bIv+AHfJt3ox3P83AgMBAAGjZzBlMB0GA1UdDgQW
        BBR49Q5oZ5TRFe96o82mUye8raq17jBEBgNVHREEPTA7ghB3aG9hbWkuY2VzbmV0
        LmN6hidodHRwczovL3dob2FtaS5jZXNuZXQuY3ovaWRwL3NoaWJib2xldGgwDQYJ
        KoZIhvcNAQELBQADggGBADyYeU0OkhTtG75cXza1ah1wlnrXpVOCM2EjC08PmVMH
        LSWIPF7wfzFfirlXonmzh+aM7iwSsm+DmLfEj59mfm0qb/ZrH6rCM8HHa2gpbFk+
        rIRSO3uYvrUvUnK83ZOJ7TQF5HUp4Wz5dzcHh3eQafT2AykclRd5vE3tASBKLDbc
        Lg67ZG8feQePEOT5bEYaLBSDXKVB+5zcMK3YpIInZLgVlw/ukYdkbMoVPH0SLKC2
        kzBv+3qEAtnytl/1uSmZ+YN9sNT5hCgMC53/+L8sABJaclLEvoOCRvUp6xiewrya
        txSk/8c8JLteuzEWor7DXvQxxHco/Uv5nAfFeGWhML5v8RmBq47TdTTWe3PKw3uf
        sC5E47G4vQh7z8a9zDXkkhN0E73Mv8cm4ArzYXJzW3tvYQSwos+Sdq4rQGnYXZ+1
        3AX2GScvmuCyeA8YIKmW3jqzGtWH0iA7Ic6wCnnDZwXtK76x8FMX8cnU2qMC2aVz
        zFAqLcxs7apHvItdRCAPnA==</ds:X509Certificate>
                </ds:X509Data>
              </ds:KeyInfo>
            </KeyDescriptor>
        
            <!--
            <SingleLogoutService Binding="urn:oasis:names:tc:SAML:2.0:bindings:HTTP-POST" Location="https://whoami.cesnet.cz/idp/profile/SAML2/POST/SLO"/>
            <SingleLogoutService Binding="urn:oasis:names:tc:SAML:2.0:bindings:HTTP-POST-SimpleSign" Location="https://whoami.cesnet.cz/idp/profile/SAML2/POST-SimpleSign/SLO"/>
            <SingleLogoutService Binding="urn:oasis:names:tc:SAML:2.0:bindings:HTTP-Redirect" Location="https://whoami.cesnet.cz/idp/profile/SAML2/Redirect/SLO"/>
            -->
        
            <SingleSignOnService Binding="urn:oasis:names:tc:SAML:2.0:bindings:HTTP-Redirect" req-attr:supportsRequestedAttributes="true" Location="https://whoami.cesnet.cz/idp/profile/SAML2/Redirect/SSO"/>
            <SingleSignOnService Binding="urn:oasis:names:tc:SAML:2.0:bindings:HTTP-POST" req-attr:supportsRequestedAttributes="true" Location="https://whoami.cesnet.cz/idp/profile/SAML2/POST/SSO"/>
            <SingleSignOnService Binding="urn:oasis:names:tc:SAML:2.0:bindings:HTTP-POST-SimpleSign" req-attr:supportsRequestedAttributes="true" Location="https://whoami.cesnet.cz/idp/profile/SAML2/POST-SimpleSign/SSO"/>
        
          </IDPSSODescriptor>
        
          <Organization>
            <OrganizationName xml:lang="en">CESNET, a. l. e.</OrganizationName>
            <OrganizationName xml:lang="cs">CESNET, z. s. p. o.</OrganizationName>
            <OrganizationDisplayName xml:lang="en">CESNET</OrganizationDisplayName>
            <OrganizationDisplayName xml:lang="cs">CESNET</OrganizationDisplayName>
            <OrganizationURL xml:lang="en">https://www.ces.net/</OrganizationURL>
            <OrganizationURL xml:lang="cs">https://www.cesnet.cz/</OrganizationURL>
          </Organization>
        
          <ContactPerson contactType="technical">
            <GivenName>Jan</GivenName>
            <SurName>Oppolzer</SurName>
            <EmailAddress>mailto:jan.oppolzer@cesnet.cz</EmailAddress>
          </ContactPerson>
          <ContactPerson contactType="technical">
            <GivenName>Jan</GivenName>
            <SurName>Tomášek</SurName>
            <EmailAddress>mailto:jan.tomasek@cesnet.cz</EmailAddress>
          </ContactPerson>
          <ContactPerson contactType="technical">
            <GivenName>Jan</GivenName>
            <SurName>Chvojka</SurName>
            <EmailAddress>mailto:jan.chvojka@cesnet.cz</EmailAddress>
          </ContactPerson>
          <ContactPerson contactType="other" xmlns:remd="http://refeds.org/metadata" remd:contactType="http://refeds.org/metadata/contactType/security">
            <GivenName>CESNET-CERTS</GivenName>
            <EmailAddress>mailto:abuse@cesnet.cz</EmailAddress>
          </ContactPerson>
        
        </EntityDescriptor>';

        // add an entity using wrong metadata content
        // add an entity using correct metadata content
        $this
          ->followingRedirects()
          ->actingAs($admin)
          ->post(route('entities.store', [
              'metadata' => $whoami,
              'federation' => $federation->id,
              'explanation' => $this->faker->catchPhrase(),
          ]))
          ->assertSeeText(__('entities.entity_requested', ['name' => 'https://whoami.cesnet.cz/idp/shibboleth']));

        $this->assertEquals(1, Entity::count());
        $this->assertEquals(route('entities.index'), url()->current());

        // add already existing entity
        $this
          ->followingRedirects()
          ->actingAs($admin)
          ->post(route('entities.store', [
              'metadata' => $whoami,
              'federation' => $federation->id,
              'explanation' => $this->faker->catchPhrase(),
          ]))
          ->assertSeeText(__('entities.existing_already'));

        $this->assertEquals(1, Entity::count());
        $this->assertEquals(route('entities.show', Entity::find(1)), url()->current());
    }

    /** @test */
    public function an_admin_can_see_entities_edit_page()
    {
        $admin = User::factory()->create(['admin' => true]);
        $entity = Entity::factory()->create();

        $this
          ->actingAs($admin)
          ->get(route('entities.edit', $entity))
          ->assertSeeText(__('entities.edit', ['name' => $entity->name_en]))
          ->assertSeeText(__('entities.profile'));

        $this->assertEquals(1, Entity::count());
        $this->assertEquals(route('entities.edit', $entity), url()->current());
    }

    /** @test */
    public function an_admin_can_edit_an_existing_entity()
    {
        Bus::fake();

        $admin = User::factory()->create(['admin' => true]);
        $entity = Entity::factory()->create(['entityid' => 'https://whoami.cesnet.cz/idp/shibboleth']);

        $whoami = '<?xml version="1.0" encoding="UTF-8"?>

        <!-- Do not edit manualy! This file is managed by Ansible. -->
        
        <EntityDescriptor xmlns="urn:oasis:names:tc:SAML:2.0:metadata" xmlns:ds="http://www.w3.org/2000/09/xmldsig#" xmlns:shibmd="urn:mace:shibboleth:metadata:1.0" xmlns:xml="http://www.w3.org/XML/1998/namespace" xmlns:mdui="urn:oasis:names:tc:SAML:metadata:ui" xmlns:req-attr="urn:oasis:names:tc:SAML:protocol:ext:req-attr" entityID="https://whoami.cesnet.cz/idp/shibboleth">
        
          <Extensions>
            <mdattr:EntityAttributes xmlns:mdattr="urn:oasis:names:tc:SAML:metadata:attribute">
              <saml:Attribute xmlns:saml="urn:oasis:names:tc:SAML:2.0:assertion" NameFormat="urn:oasis:names:tc:SAML:2.0:attrname-format:uri" Name="http://macedir.org/entity-category-support">
                <!-- Research and Scholarship -->
                <saml:AttributeValue>http://refeds.org/category/research-and-scholarship</saml:AttributeValue>
                <!-- Code of Conduct -->
                <saml:AttributeValue>http://www.geant.net/uri/dataprotection-code-of-conduct/v1</saml:AttributeValue>
              </saml:Attribute>
              <!-- Sirtfi -->
              <saml:Attribute xmlns:saml="urn:oasis:names:tc:SAML:2.0:assertion" NameFormat="urn:oasis:names:tc:SAML:2.0:attrname-format:uri" Name="urn:oasis:names:tc:SAML:attribute:assurance-certification">
                <saml:AttributeValue>https://refeds.org/sirtfi</saml:AttributeValue>
              </saml:Attribute>
            </mdattr:EntityAttributes>
          </Extensions>
        
          <IDPSSODescriptor protocolSupportEnumeration="urn:oasis:names:tc:SAML:2.0:protocol">
            <Extensions>
              <shibmd:Scope regexp="false">cesnet.cz</shibmd:Scope>
              <mdui:UIInfo>
                <mdui:DisplayName xml:lang="en">CESNET</mdui:DisplayName>
                <mdui:DisplayName xml:lang="cs">CESNET</mdui:DisplayName>
                <mdui:Description xml:lang="en">Identity Provider (IdP) for CESNET employees.</mdui:Description>
                <mdui:Description xml:lang="cs">Poskytovatel identity (IdP) pro zaměstnance CESNETu.</mdui:Description>
                <mdui:InformationURL xml:lang="en">https://www.ces.net/</mdui:InformationURL>
                <mdui:InformationURL xml:lang="cs">https://www.cesnet.cz/</mdui:InformationURL>
                <mdui:Logo height="40" width="99">https://whoami.cesnet.cz/idp/images/cesnet-logo-40.png</mdui:Logo>
              </mdui:UIInfo>
            </Extensions>
        
            <KeyDescriptor use="signing">
              <ds:KeyInfo>
                <ds:X509Data>
                  <ds:X509Certificate>MIIEKzCCApOgAwIBAgIUfhycq2ciNJX9gaQvHYRcI7a+J2QwDQYJKoZIhvcNAQEL
        BQAwGzEZMBcGA1UEAwwQd2hvYW1pLmNlc25ldC5jejAeFw0yMjAzMDEwNzQzNTJa
        Fw0zMjAzMDEwNzQzNTJaMBsxGTAXBgNVBAMMEHdob2FtaS5jZXNuZXQuY3owggGi
        MA0GCSqGSIb3DQEBAQUAA4IBjwAwggGKAoIBgQCA+C/U7dOcl2dneqGpFi2AEVwa
        sytgYWzMOWaGRBBVGJoTfIJbvwI/nyerCK+K/CPO3ChJ4DxG6nDH9FQnxvN3jjhf
        JBH6yBzNew3rDDlFLg+iNOW/srAXvDmNrN5/V91orjZ4qlKViNqvtOZrTwVhSDtF
        QSxAPX7Q6b7zqRkPD531eHxVHCjFyriLacihUBEAevOqxO8xdXseeyZzaEQw7KEK
        EEYFIHvH8VAWV/S/vLXj3S8Vam3AE464u87mCIHkED9XfaZAPkGSBe0SwwGdxQnM
        o72G56BrKgBArlKDd/m8zE/fsaP97pBLx9amNA9u0EVhV4W9de2hepJKQowrXoo1
        3LDE/dpbAkSTdlcsrL/g1eN3ziiZ8yOulJaI3zcOmgxqXPlO+wgeYdRiDnSgnQmm
        qxekeZ5LXQfwrroK96ZJaheTUopO17UKDv7lek+c11nPwJiEQkgDqqD2rbwkdmvR
        JUj5jJSyefoIVhtVLGCiCypwKcZJZCDO3y3vsz0CAwEAAaNnMGUwHQYDVR0OBBYE
        FJyrRUYHac3G6UhW8eibyuLAcn9rMEQGA1UdEQQ9MDuCEHdob2FtaS5jZXNuZXQu
        Y3qGJ2h0dHBzOi8vd2hvYW1pLmNlc25ldC5jei9pZHAvc2hpYmJvbGV0aDANBgkq
        hkiG9w0BAQsFAAOCAYEAGYBXQE8GNMhaPuDoWkH0oL2cI4qoneX9NPaUoSwQ+A7O
        qLY+jMm/wUkLpu+Kh61tXMY5NPWMEZLc23nwILegUSzrNGJqMpHnz8Mv1Ab5Alv5
        FyUIr52VGkyNnJpIiq2XCSjIOnmHUkUdymL8nZf8J3IadRZq8jN9BKJy1vaLThlG
        aAsqC2lZiPMMllYhPaOLtIQtBFEyYO97evmVvVNTZgmmk8fuF6P3gM18LRxXRbpQ
        cQ5VS5nL3LrLi/1cATO8Jz+ERv4zaSLd+2UM0Ft899Ucowjzvoq0vmE+pvvlneNl
        aEorT6nbXFeEsxaYuidg1pxWeoBqruRA0I7iD/4YzVgRHIdwBufQK+x1L4Pihp9U
        ySPhVJJXdEdlSt5MnAjBv/Icn/FlGV1u/X37FqIJ7O/Alb5jfwSu97cgwd4pB6/j
        ao2BQ3zYrSsaVU9qtnv0lE5nzG2b3QyPPkIbUR3nV/n7XOoZmGN8k0jX5N0shAfJ
        wc9tCLkBo8qkL9vQaFAu</ds:X509Certificate>
                </ds:X509Data>
              </ds:KeyInfo>
            </KeyDescriptor>
            <KeyDescriptor use="encryption">
              <ds:KeyInfo>
                <ds:X509Data>
                  <ds:X509Certificate>MIIELDCCApSgAwIBAgIVAI6NHbEG2QoGwKTUzRSIXiHOnnw1MA0GCSqGSIb3DQEB
        CwUAMBsxGTAXBgNVBAMMEHdob2FtaS5jZXNuZXQuY3owHhcNMjIwMzAxMDc0MzUw
        WhcNMzIwMzAxMDc0MzUwWjAbMRkwFwYDVQQDDBB3aG9hbWkuY2VzbmV0LmN6MIIB
        ojANBgkqhkiG9w0BAQEFAAOCAY8AMIIBigKCAYEAn5kgxiNoK5y44dK0j8j0jj1f
        uB9mV2GWz2pZba6ytQoIdcDb8udlEcbrjaD41txziJDS7LD/+meZIU4E6B8p0qgn
        5RnMtQq/HfkmSbxplgoZK+ZLqVoIO422wtX8xgSsBsui9boG6LGEYkrF2+oak1OM
        dd20dlSumVcQuRfsRf1JAlI2c08UlinwhoBf4CPMOigDD8qu95sMCaCR920AfLCR
        29R6U2qz5mvps08yFFpkrTErGKLY6SNPHjrbLhW8VU/lqUuVCdnaMnxz9wPWNhAt
        eE9YZLtPlFhqLbqDCloELc/Ekk5/XhVY0vr+B0rBsXmFaolEi9PnGCkIK665YAwk
        cyarO0gyawE3mcX22HBlH1ysHDA4dpXNXtkr4BAiVTIiJAP8T7EUk1z2BDOsDVWC
        8EWvXU6hfnJUabXzpqMMn8b4lCP730nYxuzYlPXmoq+epQVA23blJ5aVA0WUKicV
        EfIx1y3MW1tRTxM2Ze9ELR/+bIv+AHfJt3ox3P83AgMBAAGjZzBlMB0GA1UdDgQW
        BBR49Q5oZ5TRFe96o82mUye8raq17jBEBgNVHREEPTA7ghB3aG9hbWkuY2VzbmV0
        LmN6hidodHRwczovL3dob2FtaS5jZXNuZXQuY3ovaWRwL3NoaWJib2xldGgwDQYJ
        KoZIhvcNAQELBQADggGBADyYeU0OkhTtG75cXza1ah1wlnrXpVOCM2EjC08PmVMH
        LSWIPF7wfzFfirlXonmzh+aM7iwSsm+DmLfEj59mfm0qb/ZrH6rCM8HHa2gpbFk+
        rIRSO3uYvrUvUnK83ZOJ7TQF5HUp4Wz5dzcHh3eQafT2AykclRd5vE3tASBKLDbc
        Lg67ZG8feQePEOT5bEYaLBSDXKVB+5zcMK3YpIInZLgVlw/ukYdkbMoVPH0SLKC2
        kzBv+3qEAtnytl/1uSmZ+YN9sNT5hCgMC53/+L8sABJaclLEvoOCRvUp6xiewrya
        txSk/8c8JLteuzEWor7DXvQxxHco/Uv5nAfFeGWhML5v8RmBq47TdTTWe3PKw3uf
        sC5E47G4vQh7z8a9zDXkkhN0E73Mv8cm4ArzYXJzW3tvYQSwos+Sdq4rQGnYXZ+1
        3AX2GScvmuCyeA8YIKmW3jqzGtWH0iA7Ic6wCnnDZwXtK76x8FMX8cnU2qMC2aVz
        zFAqLcxs7apHvItdRCAPnA==</ds:X509Certificate>
                </ds:X509Data>
              </ds:KeyInfo>
            </KeyDescriptor>
        
            <!--
            <SingleLogoutService Binding="urn:oasis:names:tc:SAML:2.0:bindings:HTTP-POST" Location="https://whoami.cesnet.cz/idp/profile/SAML2/POST/SLO"/>
            <SingleLogoutService Binding="urn:oasis:names:tc:SAML:2.0:bindings:HTTP-POST-SimpleSign" Location="https://whoami.cesnet.cz/idp/profile/SAML2/POST-SimpleSign/SLO"/>
            <SingleLogoutService Binding="urn:oasis:names:tc:SAML:2.0:bindings:HTTP-Redirect" Location="https://whoami.cesnet.cz/idp/profile/SAML2/Redirect/SLO"/>
            -->
        
            <SingleSignOnService Binding="urn:oasis:names:tc:SAML:2.0:bindings:HTTP-Redirect" req-attr:supportsRequestedAttributes="true" Location="https://whoami.cesnet.cz/idp/profile/SAML2/Redirect/SSO"/>
            <SingleSignOnService Binding="urn:oasis:names:tc:SAML:2.0:bindings:HTTP-POST" req-attr:supportsRequestedAttributes="true" Location="https://whoami.cesnet.cz/idp/profile/SAML2/POST/SSO"/>
            <SingleSignOnService Binding="urn:oasis:names:tc:SAML:2.0:bindings:HTTP-POST-SimpleSign" req-attr:supportsRequestedAttributes="true" Location="https://whoami.cesnet.cz/idp/profile/SAML2/POST-SimpleSign/SSO"/>
        
          </IDPSSODescriptor>
        
          <Organization>
            <OrganizationName xml:lang="en">CESNET, a. l. e.</OrganizationName>
            <OrganizationName xml:lang="cs">CESNET, z. s. p. o.</OrganizationName>
            <OrganizationDisplayName xml:lang="en">CESNET</OrganizationDisplayName>
            <OrganizationDisplayName xml:lang="cs">CESNET</OrganizationDisplayName>
            <OrganizationURL xml:lang="en">https://www.ces.net/</OrganizationURL>
            <OrganizationURL xml:lang="cs">https://www.cesnet.cz/</OrganizationURL>
          </Organization>
        
          <ContactPerson contactType="technical">
            <GivenName>Jan</GivenName>
            <SurName>Oppolzer</SurName>
            <EmailAddress>mailto:jan.oppolzer@cesnet.cz</EmailAddress>
          </ContactPerson>
          <ContactPerson contactType="technical">
            <GivenName>Jan</GivenName>
            <SurName>Tomášek</SurName>
            <EmailAddress>mailto:jan.tomasek@cesnet.cz</EmailAddress>
          </ContactPerson>
          <ContactPerson contactType="technical">
            <GivenName>Jan</GivenName>
            <SurName>Chvojka</SurName>
            <EmailAddress>mailto:jan.chvojka@cesnet.cz</EmailAddress>
          </ContactPerson>
          <ContactPerson contactType="other" xmlns:remd="http://refeds.org/metadata" remd:contactType="http://refeds.org/metadata/contactType/security">
            <GivenName>CESNET-CERTS</GivenName>
            <EmailAddress>mailto:abuse@cesnet.cz</EmailAddress>
          </ContactPerson>
        
        </EntityDescriptor>';

        $this
          ->followingRedirects()
          ->actingAs($admin)
          ->patch(route('entities.update', $entity), [
              'action' => 'update',
              'metadata' => $whoami,
          ])
          ->assertSeeText(__('entities.entity_updated'));

        $this->assertEquals(route('entities.show', $entity), url()->current());
        Bus::assertDispatched(GitUpdateEntity::class, function ($job) use ($entity) {
            return $job->entity->is($entity);
        });
    }

    /** @test */
    public function an_admin_can_change_an_existing_entities_status()
    {
        $admin = User::factory()->create(['admin' => true]);
        $entity = Entity::factory()->create();

        $this->assertEquals(1, Entity::count());
        $this->assertTrue($entity->active);

        $this
          ->followingRedirects()
          ->actingAs($admin)
          ->patch(route('entities.update', $entity), ['action' => 'status'])
          ->assertSeeText(__('entities.inactive', ['name' => $entity->name_en]));

        $entity->refresh();
        $this->assertFalse($entity->active);
        $this->assertEquals(route('entities.show', $entity), url()->current());

        $this
          ->followingRedirects()
          ->actingAs($admin)
          ->patch(route('entities.update', $entity), ['action' => 'status'])
          ->assertSeeText(__('entities.active', ['name' => $entity->name_en]));

        $entity->refresh();
        $this->assertTrue($entity->active);
        $this->assertEquals(route('entities.show', $entity), url()->current());
    }

    /** @test */
    public function an_admin_can_change_an_existing_entities_state()
    {
        Bus::fake();

        $admin = User::factory()->create(['admin' => true]);
        $entity = Entity::factory()->create();

        $this->assertEquals(1, Entity::count());
        $this->assertFalse($entity->trashed());

        $this
          ->followingRedirects()
          ->actingAs($admin)
          ->patch(route('entities.update', $entity), ['action' => 'state'])
          ->assertSeeText(__('entities.deleted', ['name' => $entity->name_en]));

        Bus::assertDispatched(GitDeleteEntity::class, function ($job) use ($entity) {
            return $job->entity->is($entity);
        });

        $entity->refresh();
        $this->assertTrue($entity->trashed());
        $this->assertEquals(route('entities.show', $entity), url()->current());

        $this
          ->followingRedirects()
          ->actingAs($admin)
          ->patch(route('entities.update', $entity), ['action' => 'state'])
          ->assertSeeText(__('entities.restored', ['name' => $entity->name_en]));

        $entity->refresh();
        $this->assertFalse($entity->trashed());
        $this->assertEquals(route('entities.show', $entity), url()->current());

        Bus::assertDispatched(GitAddEntity::class, function ($job) use ($entity) {
            return $job->entity->is($entity);
        });
    }

    /** @test */
    public function an_admin_can_change_an_existing_entities_operators()
    {
        $admin = User::factory()->create(['admin' => true]);
        $entity = Entity::factory()->create();
        $new_operator = User::factory()->create();

        $this->assertEquals(1, Entity::count());
        $this->assertEquals(2, User::count());
        $this->assertEquals(0, $entity->operators()->count());

        $this
          ->followingRedirects()
          ->actingAs($admin)
          ->patch(route('entities.update', $entity), ['action' => 'add_operators'])
          ->assertSeeText(__('entities.add_empty_operators'));

        $entity->refresh();
        $this->assertEquals(0, $entity->operators()->count());
        $this->assertEquals(route('entities.operators', $entity), url()->current());

        $this
          ->followingRedirects()
          ->actingAs($admin)
          ->patch(route('entities.update', $entity), [
              'action' => 'add_operators',
              'operators' => [$new_operator->id],
          ])
          ->assertSeeText(__('entities.operators_added'));

        $entity->refresh();
        $this->assertEquals(1, $entity->operators()->count());
        $this->assertEquals(route('entities.show', $entity), url()->current());

        $this
          ->followingRedirects()
          ->actingAs($admin)
          ->patch(route('entities.update', $entity), ['action' => 'delete_operators'])
          ->assertSeeText(__('entities.delete_empty_operators'));

        $entity->refresh();
        $this->assertEquals(1, $entity->operators()->count());
        $this->assertEquals(route('entities.operators', $entity), url()->current());

        $this
          ->followingRedirects()
          ->actingAs($admin)
          ->patch(route('entities.update', $entity), [
              'action' => 'delete_operators',
              'operators' => [$new_operator->id],
          ])
          ->assertSeeText(__('entities.operators_deleted'));

        $entity->refresh();
        $this->assertEquals(0, $entity->operators()->count());
        $this->assertEquals(route('entities.show', $entity), url()->current());
    }

    /** @test */
    public function an_admin_can_change_an_existing_entities_federation_membership()
    {
        $this->withoutExceptionHandling();

        $admin = User::factory()->create(['admin' => true]);
        $entity = Entity::factory()->create();
        $federation = Federation::factory()->create();

        $this->assertTrue($entity->active);
        $this->assertTrue($federation->active);
        $this->assertEquals(0, Membership::whereApproved(false)->count());

        $this
          ->followingRedirects()
          ->actingAs($admin)
          ->post(route('entities.join', $entity), [
              'federation' => $federation->id,
              'explanation' => $this->faker->sentence(),
          ])
          ->assertSeeText(__('entities.join_requested', ['name' => $federation->name]));

        $this->assertEquals(1, Membership::whereApproved(false)->count());
    }

    /** @test */
    public function an_admin_can_purge_an_existing_entity()
    {
        $admin = User::factory()->create(['admin' => true]);
        $entity = Entity::factory()->create([
            'active' => false,
            'deleted_at' => now(),
        ]);
        $name = $entity->name_en;

        $this
          ->followingRedirects()
          ->actingAs($admin)
          ->delete(route('entities.destroy', $entity))
          ->assertSeeText(__('entities.destroyed', ['name' => $name]));
    }

    /** @test */
    public function an_admin_can_reject_a_new_entity_request()
    {
        $admin = User::factory()->create(['admin' => true]);
        $federation = Federation::factory()->create();
        $entity = Entity::factory()->create(['approved' => false]);
        $entity->federations()->attach($federation, [
            'requested_by' => $admin->id,
            'explanation' => $this->faker->catchPhrase(),
        ]);
        $membership = Membership::find(1);

        $this
          ->followingRedirects()
          ->actingAs($admin)
          ->delete(route('memberships.destroy', $membership))
          ->assertSeeText(__('federations.membership_rejected', ['entity' => $entity->name_en]));
    }

    /** @test */
    public function an_admin_can_approve_a_new_entity_request()
    {
        Bus::fake();

        $admin = User::factory()->create(['admin' => true]);
        $federation = Federation::factory()->create();
        $entity = Entity::factory()->create(['approved' => false]);
        $entity->federations()->attach($federation, [
            'requested_by' => $admin->id,
            'explanation' => $this->faker->catchPhrase(),
        ]);
        $membership = Membership::find(1);

        $this
          ->followingRedirects()
          ->actingAs($admin)
          ->patch(route('memberships.update', $membership))
          ->assertSeeText(__('federations.membership_accepted', ['entity' => $entity->entityid]));
    }

    /** @test */
    public function not_even_an_admin_can_run_update_function_without_definig_action()
    {
        $admin = User::factory()->create(['admin' => true]);
        $entity = Entity::factory()->create();

        $this
          ->followingRedirects()
          ->actingAs($admin)
          ->put(route('entities.update', $entity));

        $this->assertEquals(route('home'), url()->current());
    }

    /** @test */
    public function ask_rs_isnt_shown_for_sp_entities_not_in_rs_federation()
    {
        $user = User::factory()->create();
        $entity = Entity::factory()->create(['type' => 'sp']);
        $user->entities()->attach($entity);

        $this
          ->actingAs($user)
          ->get(route('entities.show', $entity))
          ->assertDontSeeText(__('entities.ask_rs'));

        $this
          ->followingRedirects()
          ->actingAs($user)
          ->post(route('entities.rs', $entity))
          ->assertStatus(403)
          ->assertSeeText(__('entities.rs_only_for_eduidcz_members'));
    }

    /** @test */
    public function ask_rs_is_shown_for_sp_entities_in_rs_federation()
    {
        $user = User::factory()->create();
        $entity = Entity::factory()->create(['type' => 'sp', 'rs' => false]);
        $user->entities()->attach($entity);
        $federation = Federation::factory()->create(['xml_name' => config('git.rs_federation')]);
        $federation->entities()->attach($entity, [
            'requested_by' => $user->id,
            'approved_by' => $user->id,
            'approved' => true,
            'explanation' => 'Test',
        ]);

        $this
          ->actingAs($user)
          ->get(route('entities.show', $entity))
          ->assertSeeText(__('entities.ask_rs'));

        $this
          ->followingRedirects()
          ->actingAs($user)
          ->post(route('entities.rs', $entity))
          ->assertStatus(200)
          ->assertSeeText(__('entities.rs_asked'));
    }
}
