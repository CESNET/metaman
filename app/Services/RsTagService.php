<?php

namespace App\Services;

use App\Models\Entity;
use App\Traits\EntitiesXML\TagTrait;
use App\Traits\ValidatorTrait;
use DOMXPath;

class RsTagService extends TagService
{
    use TagTrait,ValidatorTrait;

    private string $value = 'http://refeds.org/category/research-and-scholarship';

    public function create(Entity $entity): false|string
    {
        $mdURI = config('xmlNameSpace.md');
        $mdattrURI = config('xmlNameSpace.mdattr');
        $samlURI = config('xmlNameSpace.saml');

        $xml_document = $entity->xml_file;
        $isIdp = $entity->type == 'idp';

        $dom = $this->createDOM($xml_document);
        $xPath = $this->createXPath($dom);

        $rootTag = $this->getRootTag($xPath);
        $extensions = $this->getOrCreateExtensions($xPath, $dom, $rootTag, $mdURI);
        $entityAttributes = $this->getOrCreateEntityAttributes($xPath, $dom, $extensions, $mdattrURI);
        $attribute = $this->getOrCreateAttribute($xPath, $dom, $entityAttributes, $samlURI, $isIdp);

        $attributeValue = $dom->createElementNS($samlURI, 'saml:AttributeValue', $this->value);
        $attribute->appendChild($attributeValue);

        $dom->normalize();

        return $dom->saveXML();
    }

    public function delete(Entity $entity): false|string
    {

        $dom = $this->createDOM($entity->xml_file);
        $xPath = $this->createXPath($dom);
        $this->deleteByXpath($xPath);
        $dom->normalize();

        return $dom->saveXML();
    }

    public function deleteByXpath(DOMXPath $xPath): void
    {
        $xpathQuery = $this->buildXPathQuery($this->value);
        $this->DeleteAllTags($xpathQuery, $xPath);
    }

    public function update(Entity $entity): false|string
    {
        if ($entity->rs) {

            if (! $this->hasTagInDocument($entity->xml_file, $this->value)) {
                return $this->create($entity);
            }

        } else {
            if ($this->hasTagInDocument($entity->xml_file, $this->value)) {
                return $this->delete($entity);
            }
        }

        return false;
    }

    protected function getOrCreateAttribute(\DOMXPath $xPath, \DOMDocument $dom, \DOMNode $entityAttributes, string $samlURI, bool $isIdp): \DOMNode
    {
        $attribute = $xPath->query('//mdattr:EntityAttributes/saml:Attribute', $entityAttributes);
        if ($attribute->length === 0) {
            $attribute = $dom->createElementNS($samlURI, 'saml:Attribute');
            $attribute->setAttribute('NameFormat', 'urn:oasis:names:tc:SAML:2.0:attrname-format:uri');
            $attribute->setAttribute('Name', $isIdp ? 'http://macedir.org/entity-category-support' : 'http://macedir.org/entity-category');
            $entityAttributes->appendChild($attribute);
        } else {
            $attribute = $attribute->item(0);
        }

        return $attribute;
    }
}
