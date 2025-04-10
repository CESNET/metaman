<?php

namespace Tests\Feature\Http\Services;

use App\Models\Entity;
use App\Services\RsTagService;
use App\Traits\ValidatorTrait;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Facades\Log;
use Tests\TestCase;

class RsTagServiceTest extends TestCase
{
    use RefreshDatabase, ValidatorTrait,WithFaker;

    public function test_rs_tag_service_create_shoud_create_entity()
    {
        $entity = Entity::factory()->create([
            'type' => 'idp',
            'entityid' => 'https://example.org/idp',
            'file' => 'example.org/idp.xml',
            'xml_file' => <<<'XML'
<md:EntityDescriptor xmlns:md="urn:oasis:names:tc:SAML:2.0:metadata" entityID="">
    <md:IDPSSODescriptor protocolSupportEnumeration="">
        <md:Extensions/>
    </md:IDPSSODescriptor>
</md:EntityDescriptor>
XML,
            'name_en' => 'Example Identity Provider',
            'name_cs' => 'Příklad poskytovatele identit',
            'description_en' => 'This is a sample identity provider used for testing.',
            'description_cs' => 'Toto je testovací poskytovatel identit.',
            'edugain' => true,
            'hfd' => false,
            'rs' => true,
            'cocov1' => true,
            'sirtfi' => false,
            'metadata' => '<metadata>Generated for testing</metadata>',
        ]);

        $service = new RsTagService;
        $expected = <<<'XML'
<?xml version="1.0"?>
<md:EntityDescriptor xmlns:md="urn:oasis:names:tc:SAML:2.0:metadata" entityID="">
  <md:IDPSSODescriptor protocolSupportEnumeration="">
    <md:Extensions>
      <mdattr:EntityAttributes xmlns:mdattr="urn:oasis:names:tc:SAML:metadata:attribute">
        <saml:Attribute xmlns:saml="urn:oasis:names:tc:SAML:2.0:assertion" NameFormat="urn:oasis:names:tc:SAML:2.0:attrname-format:uri" Name="http://macedir.org/entity-category">
          <saml:AttributeValue>http://refeds.org/category/research-and-scholarship</saml:AttributeValue>
        </saml:Attribute>
      </mdattr:EntityAttributes>
    </md:Extensions>
  </md:IDPSSODescriptor>
</md:EntityDescriptor>

XML;
        $this->assertEquals($expected, $service->create($entity));

    }

    public function test_rs_tag_service_create_shoud_delete_entity()
    {
        $entity = Entity::factory()->create([
            'type' => 'idp',
            'entityid' => 'https://example.org/idp',
            'file' => 'example.org/idp.xml',
            'xml_file' => <<<'XML'
<md:EntityDescriptor xmlns:md="urn:oasis:names:tc:SAML:2.0:metadata" entityID="">
    <md:IDPSSODescriptor protocolSupportEnumeration="">
        <md:Extensions/>
    </md:IDPSSODescriptor>
</md:EntityDescriptor>
XML,
            'name_en' => 'Example Identity Provider',
            'name_cs' => 'Příklad poskytovatele identit',
            'description_en' => 'This is a sample identity provider used for testing.',
            'description_cs' => 'Toto je testovací poskytovatel identit.',
            'edugain' => true,
            'hfd' => false,
            'rs' => true,
            'cocov1' => true,
            'sirtfi' => false,
            'metadata' => '<metadata>Generated for testing</metadata>',
        ]);

        $service = new RsTagService;
        $expected = <<<'XML'
<?xml version="1.0"?>
<md:EntityDescriptor xmlns:md="urn:oasis:names:tc:SAML:2.0:metadata" entityID="">
  <md:IDPSSODescriptor protocolSupportEnumeration="">
    <md:Extensions/>
  </md:IDPSSODescriptor>
</md:EntityDescriptor>

XML;
        $this->assertEquals($expected, $service->delete($entity));
    }

    public function test_rs_tag_service_update_should_create_tag_when_rs_true_and_tag_missing()
    {
        $entity = Entity::factory()->create([
            'rs' => true,
            'type' => 'idp',
            'xml_file' => <<<'XML'
<md:EntityDescriptor xmlns:md="urn:oasis:names:tc:SAML:2.0:metadata" entityID="">
  <md:IDPSSODescriptor protocolSupportEnumeration="">
    <md:Extensions/>
  </md:IDPSSODescriptor>
</md:EntityDescriptor>
XML
        ]);

        $service = new RsTagService;
        $result = $service->update($entity);

        $this->assertStringContainsString('saml:AttributeValue', $result);
        $this->assertStringContainsString('http://refeds.org/category/research-and-scholarship', $result);
    }

    public function test_rs_tag_service_update_should_delete_tag_when_rs_false_and_tag_present()
    {
        $entity = Entity::factory()->create([
            'rs' => false,
            'type' => 'idp',
            'xml_file' => <<<'XML'
<md:EntityDescriptor xmlns:md="urn:oasis:names:tc:SAML:2.0:metadata"
                     xmlns:saml="urn:oasis:names:tc:SAML:2.0:assertion" entityID="">
  <md:IDPSSODescriptor protocolSupportEnumeration="">
    <md:Extensions>
      <mdattr:EntityAttributes xmlns:mdattr="urn:oasis:names:tc:SAML:metadata:attribute">
        <saml:Attribute Name="urn:some:attr">
          <saml:AttributeValue>https://refeds.org/category/research-and-scholarship</saml:AttributeValue>
        </saml:Attribute>
      </mdattr:EntityAttributes>
    </md:Extensions>
  </md:IDPSSODescriptor>
</md:EntityDescriptor>
XML
        ]);

        $service = new RsTagService;
        $result = $service->update($entity);

        $this->assertStringNotContainsString('http://refeds.org/category/research-and-scholarship', $result);
    }

    public function test_rs_tag_service_update_should_delete_tag_when_rs_false_and_tag_is_present()
    {
        $entity = Entity::factory()->create([
            'rs' => false,
            'type' => 'idp',
            'xml_file' => <<<'XML'
<?xml version="1.0"?>
<md:EntityDescriptor xmlns:md="urn:oasis:names:tc:SAML:2.0:metadata" entityID="">
  <md:IDPSSODescriptor protocolSupportEnumeration="">
    <md:Extensions>
      <mdattr:EntityAttributes xmlns:mdattr="urn:oasis:names:tc:SAML:metadata:attribute">
        <saml:Attribute xmlns:saml="urn:oasis:names:tc:SAML:2.0:assertion" NameFormat="urn:oasis:names:tc:SAML:2.0:attrname-format:uri" Name="http://macedir.org/entity-category">
          <saml:AttributeValue>http://refeds.org/category/research-and-scholarship</saml:AttributeValue>
        </saml:Attribute>
      </mdattr:EntityAttributes>
    </md:Extensions>
  </md:IDPSSODescriptor>
</md:EntityDescriptor>

XML
        ]);

        $service = new RsTagService;
        $result = $service->update($entity);

        $this->assertStringNotContainsString('http://refeds.org/category/research-and-scholarship', $result);
    }

    public function test_get_or_create_attribute_returns_existing_attribute()
    {

        $entity = Entity::factory()->create([
            'rs' => false,
            'type' => 'idp',
            'xml_file' => <<<'XML'
<md:EntityDescriptor xmlns:md="urn:oasis:names:tc:SAML:2.0:metadata"
                     xmlns:saml="urn:oasis:names:tc:SAML:2.0:assertion"
                     xmlns:mdattr="urn:oasis:names:tc:SAML:metadata:attribute" entityID="">
  <md:IDPSSODescriptor protocolSupportEnumeration="">
    <md:Extensions>
      <mdattr:EntityAttributes>
        <saml:Attribute Name="http://macedir.org/entity-category-support" />
      </mdattr:EntityAttributes>
    </md:Extensions>
  </md:IDPSSODescriptor>
</md:EntityDescriptor>

XML
        ]);

        $service = new RsTagService;
        $result = $service->create($entity);
        $expected = <<<'XML'
<?xml version="1.0"?>
<md:EntityDescriptor xmlns:md="urn:oasis:names:tc:SAML:2.0:metadata" xmlns:saml="urn:oasis:names:tc:SAML:2.0:assertion" xmlns:mdattr="urn:oasis:names:tc:SAML:metadata:attribute" entityID="">
  <md:IDPSSODescriptor protocolSupportEnumeration="">
    <md:Extensions>
      <mdattr:EntityAttributes>
        <saml:Attribute Name="http://macedir.org/entity-category-support">
          <saml:AttributeValue>http://refeds.org/category/research-and-scholarship</saml:AttributeValue>
        </saml:Attribute>
      </mdattr:EntityAttributes>
    </md:Extensions>
  </md:IDPSSODescriptor>
</md:EntityDescriptor>

XML;
        $this->assertEquals($expected, $result);

    }

    public function test_update_throws_and_logs_exception_with_broken_xml()
    {
        $brokenXml = '<xml><unclosed-tag>';

        $entity = Entity::factory()->make([
            'rs' => true,
            'xml_file' => $brokenXml,
        ]);

        $service = new RsTagService;

        // Ожидаем лог
        Log::shouldReceive('critical')
            ->once()
            ->withArgs(function ($message) use ($entity) {
                return str_contains($message, "Exception occurred in {$entity->id}");
            });

        $this->expectException(\Exception::class);
        $service->update($entity);
    }
}
