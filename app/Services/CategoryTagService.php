<?php

namespace App\Services;

use App\Models\Category;
use App\Models\Entity;
use App\Traits\EntitiesXML\TagTrait;
use App\Traits\HandlesJobsFailuresTrait;
use App\Traits\ValidatorTrait;
use DOMXPath;
use Mockery\Exception;

class CategoryTagService extends TagService
{
    use HandlesJobsFailuresTrait;
    use TagTrait,ValidatorTrait;

    public function create(Entity $entity): false|string
    {
        $mdURI = config('xmlNameSpace.md');
        $mdattrURI = config('xmlNameSpace.mdattr');
        $samlURI = config('xmlNameSpace.saml');

        $category = self::hasCategoryInDatabase($entity);
        if (! $category) {
            return false;
        }
        $attributeValue = $this->hasAtributeValueInConfig($category);
        if (! $attributeValue) {
            return false;
        }
        $xml_document = $entity->xml_file;

        $dom = $this->createDOM($xml_document);
        $xPath = $this->createXPath($dom);
        $rootTag = $this->getRootTag($xPath);
        $extensions = $this->getOrCreateExtensions($xPath, $dom, $rootTag, $mdURI);
        $entityAttributes = $this->getOrCreateEntityAttributes($xPath, $dom, $extensions, $mdattrURI);
        $attribute = $this->getOrCreateAttribute($xPath, $dom, $entityAttributes, $samlURI);

        $attributeValue = $dom->createElementNS($samlURI, 'saml:AttributeValue', $attributeValue);
        $attribute->appendChild($attributeValue);

        $dom->normalize();

        return $dom->saveXML();
    }

    public function delete(Entity $entity): false|string
    {
        $category = self::hasCategoryInDatabase($entity);
        if (! $category) {
            return false;
        }
        $attributeValue = $this->hasAtributeValueInConfig($category);
        if (! $attributeValue) {
            return false;
        }

        $dom = $this->createDOM($entity->xml_file);
        $xPath = $this->createXPath($dom);
        $xpathQuery = $this->buildXPathQuery($attributeValue);
        $this->deleteAllTags($xpathQuery, $xPath);
        $dom->normalize();

        return $dom->saveXML();

    }

    private static function hasCategoryInDatabase(Entity $entity): false|Category
    {
        $category = $entity->category;
        if (is_null($category)) {
            return false;
        } else {
            return $category;
        }
    }

    private function hasAtributeValueInConfig(Category $category): false|string
    {
        try {
            $attributeValue = config("categories.$category->name");
            if (is_null($attributeValue)) {
                throw new Exception('No category attribute in config please update');
            } else {
                return $attributeValue;
            }
        } catch (Exception $exception) {
            $this->failed($exception);
        }

        return false;
    }

    protected function getOrCreateAttribute(DOMXPath $xPath, \DOMDocument $dom, \DOMNode $entityAttributes, string $samlURI): \DOMNode|bool|\DOMElement|null
    {
        $attribute = $xPath->query('//mdattr:EntityAttributes/saml:Attribute', $entityAttributes);
        if ($attribute->length === 0) {
            $attribute = $dom->createElementNS($samlURI, 'saml:Attribute');
            $attribute->setAttribute('Name', 'http://macedir.org/entity-category');
            $attribute->setAttribute('NameFormat', 'urn:oasis:names:tc:SAML:2.0:attrname-format:uri');
            $entityAttributes->appendChild($attribute);
        } else {
            $attribute = $attribute->item(0);
        }

        return $attribute;
    }
}
