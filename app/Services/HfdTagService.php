<?php

namespace App\Services;

use App\Models\Entity;
use App\Traits\EntitiesXML\TagTrait;
use App\Traits\ValidatorTrait;
use DOMXPath;

class HfdTagService
{
    use TagTrait,ValidatorTrait;

    private string $value = 'http://refeds.org/category/hide-from-discovery';

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
        $attribute = $this->getOrCreateAttribute($xPath, $dom, $entityAttributes, $samlURI);

        $attributeValue = $dom->createElementNS($samlURI, 'saml:AttributeValue', $this->value);
        $attribute->appendChild($attributeValue);

        $dom->normalize();

        return $dom->saveXML();
    }

    public function delete(Entity $entity): void
    {
        $dom = $this->createDOM($entity->xml_file);
        $xPath = $this->createXPath($dom);
        $this->deleteByXpath($xPath);
    }

    public function deleteByXpath(DOMXPath $xPath): void
    {
        $xpathQuery = $this->buildXPathQuery();
        $this->DeleteAllTags($xpathQuery, $xPath);
    }

    private function getRootTag(DOMXPath $xPath): \DOMNode
    {
        $rootTag = $xPath->query("//*[local-name()='EntityDescriptor']")->item(0);
        if (! $rootTag) {
            throw new \RuntimeException('Root tag EntityDescriptor not found');
        }

        return $rootTag;
    }

    private function getOrCreateAttribute(DOMXPath $xPath, \DOMDocument $dom, \DOMNode $entityAttributes, string $samlURI): \DOMNode
    {
        $attribute = $xPath->query('//mdattr:EntityAttributes/saml:Attribute', $entityAttributes);
        if ($attribute->length === 0) {
            $attribute = $dom->createElementNS($samlURI, 'saml:Attribute');
            $attribute->setAttribute('NameFormat', 'urn:oasis:names:tc:SAML:2.0:attrname-format:uri');
            $attribute->setAttribute('Name', 'http://macedir.org/entity-category');
            $entityAttributes->appendChild($attribute);
        } else {
            $attribute = $attribute->item(0);
        }

        return $attribute;
    }

    private function getOrCreateEntityAttributes(DOMXPath $xPath, \DOMDocument $dom, \DOMNode $extensions, string $mdattrURI): \DOMNode
    {
        $entityAttributes = $xPath->query('//mdattr:EntityAttributes');
        if ($entityAttributes->length === 0) {
            $entityAttributes = $dom->createElementNS($mdattrURI, 'mdattr:EntityAttributes');
            $extensions->appendChild($entityAttributes);
        } else {
            $entityAttributes = $entityAttributes->item(0);
        }

        return $entityAttributes;
    }

    private function getOrCreateExtensions(DOMXPath $xPath, \DOMDocument $dom, \DOMNode $rootTag, string $mdURI): \DOMNode
    {
        $extensions = $xPath->query('//md:Extensions');
        if ($extensions->length === 0) {
            $extensions = $dom->createElementNS($mdURI, 'md:Extensions');
            $rootTag->appendChild($extensions);
        } else {
            $extensions = $extensions->item(0);
        }

        return $extensions;
    }

    private function buildXPathQuery(): string
    {
        return "//saml:AttributeValue[text()='$this->value']";
    }
}