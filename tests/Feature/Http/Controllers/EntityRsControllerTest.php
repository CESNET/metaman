<?php

namespace Tests\Feature\Http\Controllers;

use App\Models\Entity;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Facades\Bus;
use Tests\TestCase;

class EntityRsControllerTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    public function test_admin_can_change_entity_rs()
    {
        Bus::fake();

        $xml_document = <<<'XML'
<?xml version="1.0" encoding="utf-8"?>
<md:EntityDescriptor xmlns:md="urn:oasis:names:tc:SAML:2.0:metadata" ID="test_sp_metadata" entityID="https://test-sp.example.com/saml/metadata">
  <md:SPSSODescriptor AuthnRequestsSigned="true" WantAssertionsSigned="true" protocolSupportEnumeration="urn:oasis:names:tc:SAML:2.0:protocol">
    <md:Extensions>
      <mdui:UIInfo xmlns:mdui="urn:oasis:names:tc:SAML:metadata:ui">
        <mdui:DisplayName xml:lang="en">Test Service Provider</mdui:DisplayName>
        <mdui:DisplayName xml:lang="cs">Test Service Provider</mdui:DisplayName>
        <mdui:Description xml:lang="en">This is a test service provider for SAML integration.</mdui:Description>
        <mdui:Description xml:lang="cs">This is a test service provider for SAML integration.</mdui:Description>
        <mdui:InformationURL xml:lang="en">https://test-sp.example.com/info</mdui:InformationURL>
        <mdui:InformationURL xml:lang="cs">https://test-sp.example.com/info</mdui:InformationURL>
      </mdui:UIInfo>
      <mdrpi:RegistrationInfo xmlns:mdrpi="urn:oasis:names:tc:SAML:2.0:assertion" registrationAuthority="https://test-reg-authority.example.com/" registrationInstant="2025-04-08T00:00:00Z">
        <!-- Default test registration information -->
      </mdrpi:RegistrationInfo>
    </md:Extensions>
    <md:KeyDescriptor use="signing">
      <ds:KeyInfo xmlns:ds="http://www.w3.org/2000/09/xmldsig#">
        <ds:X509Data>
          <ds:X509Certificate>TESTCERTIFICATE_FOR_SIGNING</ds:X509Certificate>
        </ds:X509Data>
      </ds:KeyInfo>
    </md:KeyDescriptor>
    <md:KeyDescriptor use="encryption">
      <ds:KeyInfo xmlns:ds="http://www.w3.org/2000/09/xmldsig#">
        <ds:X509Data>
          <ds:X509Certificate>TESTCERTIFICATE_FOR_ENCRYPTION</ds:X509Certificate>
        </ds:X509Data>
      </ds:KeyInfo>
    </md:KeyDescriptor>
    <md:SingleLogoutService Binding="urn:oasis:names:tc:SAML:2.0:bindings:HTTP-POST" Location="https://test-sp.example.com/saml/SingleLogout"/>
    <md:SingleLogoutService Binding="urn:oasis:names:tc:SAML:2.0:bindings:HTTP-Redirect" Location="https://test-sp.example.com/saml/SingleLogout"/>
    <md:NameIDFormat>urn:oasis:names:tc:SAML:1.1:nameid-format:emailAddress</md:NameIDFormat>
    <md:NameIDFormat>urn:oasis:names:tc:SAML:2.0:nameid-format:transient</md:NameIDFormat>
    <md:NameIDFormat>urn:oasis:names:tc:SAML:2.0:nameid-format:persistent</md:NameIDFormat>
    <md:NameIDFormat>urn:oasis:names:tc:SAML:1.1:nameid-format:unspecified</md:NameIDFormat>
    <md:NameIDFormat>urn:oasis:names:tc:SAML:1.1:nameid-format:X509SubjectName</md:NameIDFormat>
    <md:AssertionConsumerService Binding="urn:oasis:names:tc:SAML:2.0:bindings:HTTP-POST" Location="https://test-sp.example.com/saml/SSO" index="0" isDefault="true"/>
    <md:AssertionConsumerService Binding="urn:oasis:names:tc:SAML:2.0:bindings:HTTP-Artifact" Location="https://test-sp.example.com/saml/SSO" index="1"/>
  </md:SPSSODescriptor>
  <md:Organization>
    <md:OrganizationName xml:lang="en">Test Organization</md:OrganizationName>
    <md:OrganizationName xml:lang="cs">Test Organization</md:OrganizationName>
    <md:OrganizationDisplayName xml:lang="en">Test Organization</md:OrganizationDisplayName>
    <md:OrganizationDisplayName xml:lang="cs">Test Organization</md:OrganizationDisplayName>
    <md:OrganizationURL xml:lang="en">https://test-organization.example.com</md:OrganizationURL>
    <md:OrganizationURL xml:lang="cs">https://test-organization.example.com</md:OrganizationURL>
  </md:Organization>
  <md:ContactPerson contactType="technical">
    <md:GivenName>Test</md:GivenName>
    <md:SurName>User</md:SurName>
    <md:EmailAddress>mailto:test@example.com</md:EmailAddress>
  </md:ContactPerson>
</md:EntityDescriptor>

XML;

        $admin = User::factory()->create(['admin' => true]);
        $entity = Entity::factory()->create([
            'rs' => false,
            'type' => 'sp',
            'xml_file' => $xml_document,
            'metadata' => $xml_document,
        ]);
        $this->followingRedirects()
            ->actingAs($admin)
            ->patch(route('entities.rs.state', $entity), [
                'action' => 'update',
                'rs' => false,
                'type' => 'sp',
                'xml_file' => $xml_document,
                'metadata' => $xml_document,
            ])->assertStatus(200);
        $entity->refresh();
        $this->assertTrue($entity->rs);
        $this->assertStringContainsString('http://refeds.org/category/research-and-scholarship', $entity->xml_file);

    }
}
