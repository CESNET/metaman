<?php
namespace App\Traits\DumpFromGit\EntitiesHelp;
use App\Traits\ValidatorTrait;

trait DeleteFromEntity
{
    use ValidatorTrait;


    private function hasChildElements(object $parent): bool
    {
        foreach ($parent->childNodes as $child) {
            if ($child->nodeType === XML_ELEMENT_NODE) {
                return true;
            }
        }

        return false;
    }

    private function deleteTag(object $tag): void
    {
        if ($tag->parentNode) {
            $tag->parentNode->removeChild($tag);
        }
    }

    private function deleteNoChilledTag(object $tag): void
    {
        if (!$this->hasChildElements($tag)) {
            $this->deleteTag($tag);
        }
    }

    private function DeleteAllTags(string $xpathQuery, \DOMXPath $xPath ) : void
    {
        $tags = $xPath->query($xpathQuery);

        foreach ($tags as $tag) {
            $parent = $tag->parentNode;
            $grandParent = $parent->parentNode;
            $this->deleteTag($tag);

            $this->deleteNoChilledTag($parent);
            $this->deleteNoChilledTag($grandParent);
        }
    }


    private function deleteCategories(\DOMXPath $xPath) :void
    {
        $values = config('categories');
        $xpathQueryParts = array_map(function ($value) {
            return "text()='$value'";
        }, $values);

        $xpathQuery = '//saml:AttributeValue[' . implode(' or ', $xpathQueryParts) . ']';
        $this->DeleteAllTags($xpathQuery,$xPath);
    }

    private function deleteResearchAndScholarship(\DOMXPath $xPath) : void
    {
        $value = "http://refeds.org/category/research-and-scholarship" ;
        $xpathQueryParts = "text()='$value'";

        $xpathQuery = '//saml:AttributeValue[' . $xpathQueryParts . ']';
        $this->DeleteAllTags($xpathQuery,$xPath);
    }
    private function deleteRegistrationInfo(\DOMXPath $xPath) : void
    {
        $xpathQuery = '//mdrpi:RegistrationInfo';
        $tags = $xPath->query($xpathQuery);
        if(!empty($tags))
        {
            foreach ($tags as $tag) {
                $this->deleteTag($tag);
            }
        }

    }


    private function deleteFromIdp( \DOMXPath $xPath ) : void
    {
        $this->deleteCategories($xPath);
    }

    private function deleteFromSP( \DOMXPath $xpath ) : void
    {
        $this->deleteResearchAndScholarship($xpath);

    }

    private function deleteRepublishRequest(\DOMXPath $xPath) : void
    {

        $xpathQuery = '//eduidmd:RepublishRequest';

        $tags = $xPath->query($xpathQuery);

        foreach ($tags as $tag) {
            $this->deleteTag($tag);
        }

    }


    private function deleteTags(string $metadata): string
    {
        $dom = $this->createDOM($metadata);
        $xPath = $this->createXPath($dom);

        // Make action for IDP
        if($this->isIDP($xPath))
        {
            $this->deleteFromIdp($xPath);
        }
        // Make  action for SP
        else
        {
            $this->deleteFromSP($xPath);
        }


        // Make action for Sp and Idp
        $this->deleteRepublishRequest($xPath);
        $this->deleteRegistrationInfo($xPath);


        $dom->normalize();
        return $dom->saveXML();
    }



}
