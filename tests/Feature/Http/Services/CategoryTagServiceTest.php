<?php

namespace Tests\Feature\Http\Services;

use App\Models\Category;
use App\Models\Entity;
use App\Services\CategoryTagService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class CategoryTagServiceTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    public function test_service_can_create_xml_entity()
    {

        $xml_document = <<<'XML'
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
XML;

        $category = Category::factory()->create([
            'name' => 'avcr',
        ]);
        $entity = Entity::factory()->create([
            'xml_file' => $xml_document,
            'rs' => false,
            'type' => 'idp',

        ]);
        $entity->category()->associate($category);

        $service = new CategoryTagService;
        $expected = <<<'XML'
<?xml version="1.0"?>
<md:EntityDescriptor xmlns:md="urn:oasis:names:tc:SAML:2.0:metadata" xmlns:saml="urn:oasis:names:tc:SAML:2.0:assertion" xmlns:mdattr="urn:oasis:names:tc:SAML:metadata:attribute" entityID="">
  <md:IDPSSODescriptor protocolSupportEnumeration="">
    <md:Extensions>
      <mdattr:EntityAttributes>
        <saml:Attribute Name="http://macedir.org/entity-category-support">
          <saml:AttributeValue>http://eduid.cz/uri/idp-group/avcr</saml:AttributeValue>
        </saml:Attribute>
      </mdattr:EntityAttributes>
    </md:Extensions>
  </md:IDPSSODescriptor>
</md:EntityDescriptor>

XML;
        $this->assertEquals($expected, $service->create($entity));
    }

    public function test_service_can_delete_xml_entity()
    {

        $xml_document = <<<'XML'
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
XML;

        $category = Category::factory()->create([
            'name' => 'avcr',
        ]);
        $entity = Entity::factory()->create([
            'xml_file' => $xml_document,
            'rs' => false,
            'type' => 'idp',

        ]);
        $entity->category()->associate($category);

        $service = new CategoryTagService;
        $expected = <<<'XML'
<?xml version="1.0"?>
<md:EntityDescriptor xmlns:md="urn:oasis:names:tc:SAML:2.0:metadata" xmlns:saml="urn:oasis:names:tc:SAML:2.0:assertion" xmlns:mdattr="urn:oasis:names:tc:SAML:metadata:attribute" entityID="">
  <md:IDPSSODescriptor protocolSupportEnumeration="">
    <md:Extensions>
      <mdattr:EntityAttributes>
        <saml:Attribute Name="http://macedir.org/entity-category-support"/>
      </mdattr:EntityAttributes>
    </md:Extensions>
  </md:IDPSSODescriptor>
</md:EntityDescriptor>
XML;
        $this->assertXmlStringEqualsXmlString($expected, $service->delete($entity));
    }

    public function test_create_service_should_return_false_where_entity_without_category()
    {

        $xml_document = <<<'XML'
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
XML;
        $entity = Entity::factory()->create([
            'xml_file' => $xml_document,
            'rs' => false,
            'type' => 'idp',

        ]);

        $service = new CategoryTagService;
        $this->assertFalse($service->create($entity));
    }

    public function test_delete_service_should_return_false_where_entity_without_category()
    {

        $xml_document = <<<'XML'
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
XML;
        $entity = Entity::factory()->create([
            'xml_file' => $xml_document,
            'rs' => false,
            'type' => 'idp',

        ]);

        $service = new CategoryTagService;
        $this->assertFalse($service->delete($entity));
    }

    /**
      public function test_create_service_should_return_false_where_category_not_in_config()
      {

          $xml_document = <<<'XML'
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
XML;
          $entity = Entity::factory()->create([
              'xml_file' => $xml_document,
              'rs' => false,
              'type' => 'idp',

          ]);
          $category = Category::factory()->create([
              'name' => 'testCatka',
          ]);
          $entity->category()->associate($category);
          $service = new CategoryTagService;

          $this->assertFalse($service->create($entity));

      }
     */

    /**
    public function test_delete_service_should_return_false_where_category_not_in_config()
    {

      $xml_document = <<<'XML'
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
XML;
      $entity = Entity::factory()->create([
        'xml_file' => $xml_document,
        'rs' => false,
        'type' => 'idp',

      ]);
      $category = Category::factory()->create([
        'name' => 'testCatka',
      ]);
      $entity->category()->associate($category);
      $service = new CategoryTagService;

      $this->assertFalse($service->delete($entity));
    }
     */
    public function test_service_can_create_attribute_and_create_xml_entity()
    {

        $xml_document = <<<'XML'
<md:EntityDescriptor xmlns:md="urn:oasis:names:tc:SAML:2.0:metadata"
                     xmlns:saml="urn:oasis:names:tc:SAML:2.0:assertion"
                     xmlns:mdattr="urn:oasis:names:tc:SAML:metadata:attribute" entityID="">
  <md:IDPSSODescriptor protocolSupportEnumeration="">
    <md:Extensions>
      <mdattr:EntityAttributes>
      </mdattr:EntityAttributes>
    </md:Extensions>
  </md:IDPSSODescriptor>
</md:EntityDescriptor>
XML;

        $category = Category::factory()->create([
            'name' => 'avcr',
        ]);
        $entity = Entity::factory()->create([
            'xml_file' => $xml_document,
            'rs' => false,
            'type' => 'idp',

        ]);
        $entity->category()->associate($category);

        $service = new CategoryTagService;
        $expected = <<<'XML'
<?xml version="1.0"?>
<md:EntityDescriptor xmlns:md="urn:oasis:names:tc:SAML:2.0:metadata" xmlns:saml="urn:oasis:names:tc:SAML:2.0:assertion" xmlns:mdattr="urn:oasis:names:tc:SAML:metadata:attribute" entityID="">
  <md:IDPSSODescriptor protocolSupportEnumeration="">
    <md:Extensions>
      <mdattr:EntityAttributes>
      <saml:Attribute Name="http://macedir.org/entity-category" NameFormat="urn:oasis:names:tc:SAML:2.0:attrname-format:uri"><saml:AttributeValue>http://eduid.cz/uri/idp-group/avcr</saml:AttributeValue></saml:Attribute></mdattr:EntityAttributes>
    </md:Extensions>
  </md:IDPSSODescriptor>
</md:EntityDescriptor>
XML;

        $this->assertXmlStringEqualsXmlString($expected, $service->create($entity));
    }

    public function test_service_can_create_extensions()
    {

        $xml_document = <<<'XML'
<md:EntityDescriptor xmlns:md="urn:oasis:names:tc:SAML:2.0:metadata" entityID="">
  <md:IDPSSODescriptor protocolSupportEnumeration="">
  </md:IDPSSODescriptor>
</md:EntityDescriptor>
XML;

        $category = Category::factory()->create([
            'name' => 'avcr',
        ]);
        $entity = Entity::factory()->create([
            'xml_file' => $xml_document,
            'rs' => false,
            'type' => 'idp',

        ]);
        $entity->category()->associate($category);

        $service = new CategoryTagService;
        $expected = <<<'XML'
<?xml version="1.0"?>
<md:EntityDescriptor xmlns:md="urn:oasis:names:tc:SAML:2.0:metadata" entityID="">
  <md:IDPSSODescriptor protocolSupportEnumeration="">
  </md:IDPSSODescriptor>
  <md:Extensions>
    <mdattr:EntityAttributes xmlns:mdattr="urn:oasis:names:tc:SAML:metadata:attribute">
      <saml:Attribute xmlns:saml="urn:oasis:names:tc:SAML:2.0:assertion" Name="http://macedir.org/entity-category" NameFormat="urn:oasis:names:tc:SAML:2.0:attrname-format:uri">
        <saml:AttributeValue>http://eduid.cz/uri/idp-group/avcr</saml:AttributeValue>
      </saml:Attribute>
    </mdattr:EntityAttributes>
  </md:Extensions>
</md:EntityDescriptor>

XML;
        $this->assertXmlStringEqualsXmlString($expected, $service->create($entity));
    }
}
