<?php

namespace App\Traits\DumpFromGit\EntitiesHelp;

use App\Facades\RsTag;
use App\Traits\EntitiesXML\TagTrait;
use App\Traits\ValidatorTrait;

trait DeleteFromEntity
{
    use TagTrait, ValidatorTrait;

    private function deleteCategories(\DOMXPath $xPath): void
    {
        $values = config('categories');
        $xpathQueryParts = array_map(function ($value) {
            return "text()='$value'";
        }, $values);

        $xpathQuery = '//saml:AttributeValue['.implode(' or ', $xpathQueryParts).']';
        $this->deleteAllTags($xpathQuery, $xPath);
    }

    private function deleteRegistrationInfo(\DOMXPath $xPath): void
    {
        $xpathQuery = '//mdrpi:RegistrationInfo';
        $tags = $xPath->query($xpathQuery);
        if (! empty($tags)) {
            foreach ($tags as $tag) {
                $this->deleteTag($tag);
            }
        }
    }

    private function deleteFromIdp(\DOMXPath $xPath): void
    {
        $this->deleteCategories($xPath);
    }

    private function deleteFromSP(\DOMXPath $xpath): void
    {
        RsTag::deleteByXpath($xpath);
    }

    private function deleteRepublishRequest(\DOMXPath $xPath): void
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
        if ($this->isIDP($xPath)) {
            $this->deleteFromIdp($xPath);
        }
        // Make  action for SP
        else {
            $this->deleteFromSP($xPath);
        }

        // Make action for Sp and Idp
        $this->deleteRepublishRequest($xPath);
        $this->deleteRegistrationInfo($xPath);

        $dom->normalize();

        return $dom->saveXML();
    }
}
