<?php

namespace Tests\Feature\Traits;

use App\Traits\ValidatorTrait;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ValidatorTraitTest extends TestCase
{
    use ValidatorTrait;

    #[Test]
    public function test_returns_file_content_when_file_is_present()
    {
        $file = UploadedFile::fake()->createWithContent('test.txt', 'Hello, file!');
        $request = Request::create('/', 'POST', [], [], ['file' => $file]);

        $this->assertEquals('Hello, file!', $this->getMetadata($request));
    }

    #[Test]
    public function test_returns_false_when_file_throws_exception()
    {

        $mockRequest = \Mockery::mock(Request::class);
        $mockRequest->shouldReceive('hasFile')->with('file')->andReturn(true);
        $mockRequest->file = '/this/path/does/not/exist';
        $this->assertEmpty($this->getMetadata($mockRequest));
    }

    #[Test]
    public function test_returns_empty_line_when_file_and_metadata_is_empty()
    {
        $mockRequest = \Mockery::mock(Request::class);
        $mockRequest->shouldReceive('hasFile')->with('file')->andReturn(false);
        $mockRequest->shouldReceive('input')->with('metadata')->andReturn('');
        $this->assertEmpty($this->getMetadata($mockRequest));

    }

    #[Test]
    public function is_id_p_returns_true_where_xm_l_has_id_p_description()
    {
        $xml = <<<'XML'
        <md:EntityDescriptor xmlns:md="urn:oasis:names:tc:SAML:2.0:metadata" entityID="">
        <md:IDPSSODescriptor  protocolSupportEnumeration=""/>
        </md:EntityDescriptor>
        XML;

        $dom = $this->createDOM($xml);
        $xpath = $this->createXPath($dom);
        $result = $this->isIDP($xpath);
        $this->assertTrue($result);
    }

    #[Test]
    public function is_id_p_returns_false_where_xm_l_has_s_p_description()
    {
        $xml = <<<'XML'
<md:EntityDescriptor xmlns:md="urn:oasis:names:tc:SAML:2.0:metadata" entityID="">
    <md:IDPSSODescriptor  protocolSupportEnumeration=""/>
    <md:SPSSODescriptor  protocolSupportEnumeration=""/>
</md:EntityDescriptor>
XML;
        $dom = $this->createDOM($xml);
        $xpath = $this->createXPath($dom);
        $result = $this->isIDP($xpath);
        $this->assertFalse($result);

    }

    #[Test]
    public function get_entity_type_should_return_sp_where_is_id_p_is_false()
    {
        $xml = <<<'XML'
<md:EntityDescriptor xmlns:md="urn:oasis:names:tc:SAML:2.0:metadata" entityID="">
    <md:IDPSSODescriptor  protocolSupportEnumeration=""/>
    <md:SPSSODescriptor  protocolSupportEnumeration=""/>
</md:EntityDescriptor>
XML;
        $dom = $this->createDOM($xml);
        $xpath = $this->createXPath($dom);
        $result = $this->getEntityType($xpath);
        $this->assertEquals('sp', $result);
    }

    #[Test]
    public function get_entity_scope_should_return_null_where_is_id_p_is_false()
    {
        $xml = <<<'XML'
<md:EntityDescriptor xmlns:md="urn:oasis:names:tc:SAML:2.0:metadata" entityID="">
    <md:IDPSSODescriptor  protocolSupportEnumeration=""/>
    <md:SPSSODescriptor  protocolSupportEnumeration=""/>
</md:EntityDescriptor>
XML;
        $dom = $this->createDOM($xml);
        $xpath = $this->createXPath($dom);
        $result = $this->getEntityScope($xpath);
        $this->assertNull($result);
    }

    #[Test]
    public function get_entity_r_s_should_return_false_where_is_id_p_is_false()
    {
        $xml = <<<'XML'
<md:EntityDescriptor xmlns:md="urn:oasis:names:tc:SAML:2.0:metadata" entityID="">
    <md:IDPSSODescriptor  protocolSupportEnumeration=""/>
    <md:SPSSODescriptor  protocolSupportEnumeration=""/>
</md:EntityDescriptor>
XML;
        $dom = $this->createDOM($xml);
        $xpath = $this->createXPath($dom);
        $result = $this->getEntityRS($xpath);
        $this->assertFalse($result);
    }

    #[Test]
    public function test_get_entity_sirtfi_returns_false_when_sirtfi_not_present()
    {
        $xml = <<<'XML'
<md:EntityDescriptor xmlns:md="urn:oasis:names:tc:SAML:2.0:metadata"
                     xmlns:mdattr="urn:oasis:names:tc:SAML:metadata:attribute"
                     xmlns:saml="urn:oasis:names:tc:SAML:2.0:assertion" entityID="">
    <md:Extensions>
        <mdattr:EntityAttributes>
            <saml:Attribute Name="urn:oasis:names:tc:SAML:attribute:assurance-certification">
                <saml:AttributeValue>https://example.org/other-certification</saml:AttributeValue>
            </saml:Attribute>
        </mdattr:EntityAttributes>
    </md:Extensions>
</md:EntityDescriptor>
XML;

        $dom = $this->createDOM($xml);
        $xpath = $this->createXPath($dom);
        $result = $this->getEntitySirtfi($xpath);
        $this->assertFalse($result);
    }

    #[Test]
    public function test_get_entity_coco_v1_returns_false_when_sp_and_no_matching_attribute()
    {
        $xml = <<<'XML'
<md:EntityDescriptor xmlns:md="urn:oasis:names:tc:SAML:2.0:metadata"
                     xmlns:mdattr="urn:oasis:names:tc:SAML:metadata:attribute"
                     xmlns:saml="urn:oasis:names:tc:SAML:2.0:assertion" entityID="">
    <md:SPSSODescriptor protocolSupportEnumeration=""/>
    <md:Extensions>
        <mdattr:EntityAttributes>
            <saml:Attribute Name="http://macedir.org/entity-category">
                <saml:AttributeValue>http://example.com/other-category</saml:AttributeValue>
            </saml:Attribute>
        </mdattr:EntityAttributes>
    </md:Extensions>
</md:EntityDescriptor>
XML;

        $dom = $this->createDOM($xml);
        $xpath = $this->createXPath($dom);
        $result = $this->getEntityCocoV1($xpath);
        $this->assertFalse($result);
    }

    #[Test]
    public function test_get_entity_coco_v1_returns_true_when_sp_with_correct_entity_category()
    {
        $xml = <<<'XML'
<md:EntityDescriptor xmlns:md="urn:oasis:names:tc:SAML:2.0:metadata"
                     xmlns:mdattr="urn:oasis:names:tc:SAML:metadata:attribute"
                     xmlns:saml="urn:oasis:names:tc:SAML:2.0:assertion" entityID="">
    <md:SPSSODescriptor protocolSupportEnumeration=""/>
    <md:Extensions>
        <mdattr:EntityAttributes>
            <saml:Attribute Name="http://macedir.org/entity-category">
                <saml:AttributeValue>http://www.geant.net/uri/dataprotection-code-of-conduct/v1</saml:AttributeValue>
            </saml:Attribute>
        </mdattr:EntityAttributes>
    </md:Extensions>
</md:EntityDescriptor>
XML;

        $dom = $this->createDOM($xml);
        $xpath = $this->createXPath($dom);
        $result = $this->getEntityCocoV1($xpath);
        $this->assertTrue($result);
    }

    public function test_error_when_binding_exists_but_no_protocol()
    {
        $xml = <<<'XML'
<md:EntityDescriptor xmlns:md="urn:oasis:names:tc:SAML:2.0:metadata">
  <md:AttributeAuthorityDescriptor>
    <md:AttributeService Binding="urn:oasis:names:tc:SAML:2.0:bindings:SOAP" />
  </md:AttributeAuthorityDescriptor>
</md:EntityDescriptor>
XML;

        $dom = $this->createDOM($xml);
        $xpath = $this->createXPath($dom);
        $this->checkAttributeAuthorityDescriptor($xpath);

        $this->assertStringContainsString('requires SAML 2.0 token', $this->error);
    }

    public function test_error_when_protocol_exists_but_no_binding()
    {
        $xml = <<<'XML'
<md:EntityDescriptor xmlns:md="urn:oasis:names:tc:SAML:2.0:metadata">
  <md:AttributeAuthorityDescriptor protocolSupportEnumeration="urn:oasis:names:tc:SAML:2.0:protocol">
    <md:AttributeService Binding="urn:oasis:names:tc:SAML:1.1:bindings:SOAP" />
  </md:AttributeAuthorityDescriptor>
</md:EntityDescriptor>
XML;

        $dom = $this->createDOM($xml);
        $xpath = $this->createXPath($dom);
        $this->checkAttributeAuthorityDescriptor($xpath);

        $this->assertStringContainsString('requires SAML 2.0 binding', $this->error);
    }

    public function test_no_error_when_binding_and_protocol_match()
    {
        $xml = <<<'XML'
<md:EntityDescriptor xmlns:md="urn:oasis:names:tc:SAML:2.0:metadata">
  <md:AttributeAuthorityDescriptor protocolSupportEnumeration="urn:oasis:names:tc:SAML:2.0:protocol">
    <md:AttributeService Binding="urn:oasis:names:tc:SAML:2.0:bindings:SOAP" />
  </md:AttributeAuthorityDescriptor>
</md:EntityDescriptor>
XML;

        $dom = $this->createDOM($xml);
        $xpath = $this->createXPath($dom);
        $this->checkAttributeAuthorityDescriptor($xpath);

        $this->assertSame(
            'This metadata is not valid against XML schema. Element \'{urn:oasis:names:tc:SAML:2.0:metadata}EntityDescriptor\': The attribute \'entityID\' is required but missing. Element \'{urn:oasis:names:tc:SAML:2.0:metadata}AttributeService\': The attribute \'Location\' is required but missing. ',
            $this->error);
    }

    public function test_no_error_when_attribute_authority_descriptor_missing()
    {
        $xml = <<<'XML'
<md:EntityDescriptor xmlns:md="urn:oasis:names:tc:SAML:2.0:metadata">
</md:EntityDescriptor>
XML;

        $dom = $this->createDOM($xml);
        $xpath = $this->createXPath($dom);
        $this->checkAttributeAuthorityDescriptor($xpath);

        var_dump($this->error);

        $this->assertNotNull($this->error);
    }
}
