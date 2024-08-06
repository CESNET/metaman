<?php

namespace App\Traits\EntitiesXML;

trait TagTrait
{
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
