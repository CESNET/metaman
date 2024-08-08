<?php

namespace App\Traits\EntitiesXML;

use App\Traits\ValidatorTrait;

trait TagTrait
{
    use ValidatorTrait;

    public function hasChildElements(object $parent): bool
    {
        if (! $parent instanceof \DOMNode) {
            throw new \InvalidArgumentException('Argument must be an instance of \DOMNode');
        }

        foreach ($parent->childNodes as $child) {
            if ($child->nodeType === XML_ELEMENT_NODE) {
                return true;
            }
        }

        return false;
    }

    // find attribute in XML document
    public function hasXpathQueryInDocument(string $xml_document, string $xpathQuery): bool
    {
        try {
            $dom = $this->createDOM($xml_document);
            $xPath = $this->createXPath($dom);
            $nodes = $xPath->query($xpathQuery);

            if ($nodes === false) {
                throw new \RuntimeException('Error executing XPath query');
            }

            return $nodes->length > 0;
        } catch (\Exception $e) {
            throw new \RuntimeException('An error occurred while checking for the tag: '.$e->getMessage());
        }
    }

    private function deleteTag(object $tag): void
    {
        if (! $tag instanceof \DOMNode) {
            throw new \InvalidArgumentException('Argument must be an instance of \DOMNode');
        }

        $tag->parentNode?->removeChild($tag);
    }

    private function deleteNoChilledTag(object $tag): void
    {
        if (! $tag instanceof \DOMNode) {
            throw new \InvalidArgumentException('Argument must be an instance of \DOMNode');
        }

        if (! $this->hasChildElements($tag)) {
            $this->deleteTag($tag);
        }
    }

    private function deleteAllTags(string $xpathQuery, \DOMXPath $xPath): void
    {
        if (empty($xpathQuery)) {
            throw new \InvalidArgumentException('XPath query string cannot be empty');
        }

        $tags = $xPath->query($xpathQuery);

        if ($tags === false) {
            throw new \RuntimeException('Error executing XPath query');
        }

        foreach ($tags as $tag) {
            if (! $tag instanceof \DOMNode) {
                throw new \UnexpectedValueException('XPath query result must be an instance of \DOMNode');
            }

            $parent = $tag->parentNode;
            $grandParent = $parent ? $parent->parentNode : null;
            $this->deleteTag($tag);

            if ($parent) {
                $this->deleteNoChilledTag($parent);
            }

            if ($grandParent) {
                $this->deleteNoChilledTag($grandParent);
            }
        }
    }
}
