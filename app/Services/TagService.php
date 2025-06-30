<?php

namespace App\Services;

use App\Models\Entity;
use App\Traits\EntitiesXML\TagTrait;
use DOMNode;
use DOMXPath;
use RuntimeException;

abstract class TagService
{
    use TagTrait;

    abstract public function create(Entity $entity);

    abstract public function delete(Entity $entity);

    protected function buildXPathQuery(string $value): string
    {
        return "//saml:AttributeValue[text()='$value']";
    }

    protected function getRootTag(DOMXPath $xPath): DOMNode
    {
        $rootTag = $xPath->query("//*[local-name()='EntityDescriptor']")->item(0);
        throw_unless($rootTag, new RuntimeException(('Root tag EntityDescriptor not found.')));

        return $rootTag;
    }

    protected function getOrCreateExtensions(DOMXPath $xPath, \DOMDocument $dom, \DOMNode $rootTag, string $mdURI): \DOMNode
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

    protected function getOrCreateEntityAttributes(\DOMXPath $xPath, \DOMDocument $dom, \DOMNode $extensions, string $mdattrURI): \DOMNode
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

    protected function hasTagInDocument(string $xml_document, string $value): bool
    {
        $xpathQuery = $this->buildXPathQuery($value);

        return $this->hasXpathQueryInDocument($xml_document, $xpathQuery);
    }
}
