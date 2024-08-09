<?php

namespace App\Services;

use App\Models\Entity;
use DOMNode;
use DOMXPath;

abstract class TagService
{
    abstract public function create(Entity $entity);

    abstract public function delete(Entity $entity);

    abstract public function update(Entity $entity);

    protected function buildXPathQuery(string $value): string
    {
        return "//saml:AttributeValue[text()='$value']";
    }

    protected function getRootTag(DOMXPath $xPath): DOMNode
    {
        $rootTag = $xPath->query("//*[local-name()='EntityDescriptor']")->item(0);
        if (! $rootTag) {
            throw new \RuntimeException('Root tag EntityDescriptor not found');
        }

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
}
