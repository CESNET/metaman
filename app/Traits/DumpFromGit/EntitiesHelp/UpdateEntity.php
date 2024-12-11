<?php

namespace App\Traits\DumpFromGit\EntitiesHelp;

use App\Facades\RsTag;
use App\Models\Category;
use App\Models\Entity;
use App\Traits\EntitiesXML\TagTrait;
use App\Traits\ValidatorTrait;
use DOMDocument;
use DOMElement;
use Exception;
use Illuminate\Support\Facades\Storage;

trait UpdateEntity
{
    private string $mdURI = 'urn:oasis:names:tc:SAML:2.0:metadata';

    private string $mdattrURI = 'urn:oasis:names:tc:SAML:metadata:attribute';

    private string $samlURI = 'urn:oasis:names:tc:SAML:2.0:assertion';

    private string $mdrpiURI = 'urn:oasis:names:tc:SAML:metadata:rpi';

    use TagTrait,ValidatorTrait;

    private function prepareXmlStructure(DOMDocument $dom): \DOMNode|bool|DOMElement|\DOMNameSpaceNode|null
    {
        $xPath = $this->createXPath($dom);
        $rootTag = $xPath->query("//*[local-name()='EntityDescriptor']")->item(0);

        $entityExtensions = $xPath->query('//md:Extensions');
        if ($entityExtensions->length === 0) {
            $dom->documentElement->lookupNamespaceURI('md');
            $entityExtensions = $dom->createElementNS($this->mdURI, 'md:Extensions');
            $rootTag->insertBefore($entityExtensions, $rootTag->firstChild);
        } else {
            $entityExtensions = $entityExtensions->item(0);
        }

        $entityAttributes = $xPath->query('//mdattr:EntityAttributes');
        if ($entityAttributes->length === 0) {
            $entityAttributes = $dom->createElementNS($this->mdattrURI, 'mdattr:EntityAttributes');
            $entityExtensions->appendChild($entityAttributes);
        } else {
            $entityAttributes = $entityAttributes->item(0);
        }

        $attribute = $xPath->query('//mdattr:EntityAttributes/saml:Attribute', $entityAttributes);
        if ($attribute->length === 0) {
            $attribute = $dom->createElementNS($this->samlURI, 'saml:Attribute');
            $attribute->setAttribute('Name', 'http://macedir.org/entity-category');
            $attribute->setAttribute('NameFormat', 'urn:oasis:names:tc:SAML:2.0:attrname-format:uri');
            $entityAttributes->appendChild($attribute);
        } else {
            $attribute = $attribute->item(0);
        }

        return $attribute;
    }

    /**
     * @throws \DOMException
     * @throws \Exception
     */
    private function updateXmlCategories(string $xml_document, int $category_id): string
    {
        $dom = $this->createDOM($xml_document);
        $attribute = $this->prepareXmlStructure($dom);

        $categoryXml = Category::whereId($category_id)->first()->xml_value;

        if (empty($categoryXml)) {
            throw new \Exception("Category with ID $category_id has no XML value.");
        }

        $attributeValue = $dom->createElementNS($this->samlURI, 'saml:AttributeValue', $categoryXml);
        $attribute->appendChild($attributeValue);

        return $dom->saveXML();
    }

    /**
     * @throws \DOMException
     */
    public function updateXmlGroups(string $xml_document, array $groupLink): string
    {
        $dom = $this->createDOM($xml_document);
        $xPath = $this->createXPath($dom);
        $attribute = $this->prepareXmlStructure($dom);

        foreach ($groupLink as $link) {
            $query = "//saml:AttributeValue[text()='$link']";
            $existing = $xPath->query($query);
            if ($existing->length === 0) {
                $attributeValue = $dom->createElementNS($this->samlURI, 'saml:AttributeValue', $link);
                $attribute->appendChild($attributeValue);
            }
        }

        return $dom->saveXML();
    }

    public function deleteXmlGroups(string $xml_document, array $groupLink): string
    {
        $dom = $this->createDOM($xml_document);
        $xPath = $this->createXPath($dom);

        $xPath->registerNamespace('saml', $this->samlURI);

        foreach ($groupLink as $link) {
            $query = "//saml:AttributeValue[text()='$link']";

            $nodes = $xPath->query($query);

            foreach ($nodes as $node) {
                $this->deleteTag($node);
            }
        }

        return $dom->saveXML();
    }

    /**
     * @throws Exception if  exist more or less then 2 part something gone wrong
     */
    private function splitDocument(): array
    {
        $document = Storage::get(config('git.reginfo'));
        $lines = explode("\n", $document);
        $splitDocument = [];

        foreach ($lines as $line) {
            if (empty(ltrim($line))) {
                continue;
            }
            $parts = preg_split('/\s+/', $line, 2);
            if (count($parts) != 2) {
                throw new Exception('no 2 part');
            } else {
                $splitDocument[$parts[0]] = $parts[1];
            }
        }

        return $splitDocument;
    }

    private function updateRegistrationInfo(string $xml_document, string $entityId, array $timestampDocumentArray): string
    {
        $dom = $this->createDOM($xml_document);
        $xPath = $this->createXPath($dom);
        $rootTag = $xPath->query("//*[local-name()='EntityDescriptor']")->item(0);

        $entityExtensions = $xPath->query('//md:Extensions');
        if ($entityExtensions->length === 0) {
            $entityExtensions = $dom->createElementNS($this->mdURI, 'md:Extensions');
            $rootTag->insertBefore($entityExtensions, $rootTag->firstChild);
        } else {
            $entityExtensions = $entityExtensions->item(0);
        }
        $info = $xPath->query('//mdrpi:RegistrationInfo', $entityExtensions);
        if ($info->length === 0) {

            $info = $dom->createElementNS($this->samlURI, 'mdrpi:RegistrationInfo');

            $info->setAttribute('registrationAuthority', config('registrationInfo.registrationAuthority'));

            if (empty($timestampDocumentArray[$entityId])) {
                $info->setAttribute('registrationInstant', gmdate('Y-m-d\TH:i:s\Z'));
            } else {
                $info->setAttribute('registrationInstant', $timestampDocumentArray[$entityId]);
            }

            $entityExtensions->appendChild($info);
        } else {
            $info = $info->item(0);
        }

        //For English
        $registrationPolicyEN = $dom->createElementNS($this->samlURI, 'saml:AttributeValue', config('registrationInfo.en'));
        $registrationPolicyEN->setAttributeNS('http://www.w3.org/XML/1998/namespace', 'xml:lang', 'en');
        $info->appendChild($registrationPolicyEN);
        // For Czech
        $registrationPolicyCZ = $dom->createElementNS($this->samlURI, 'saml:AttributeValue', config('registrationInfo.cs'));
        $registrationPolicyCZ->setAttributeNS('http://www.w3.org/XML/1998/namespace', 'xml:lang', 'cs');
        $info->appendChild($registrationPolicyCZ);

        $dom->normalize();

        return $dom->saveXML();
    }

    /**
     * @param  array  $timestampDocumentArray  for add registration time from git file
     * @return void update entity in db and return
     *
     * @throws \DOMException
     */
    public function updateEntityXml($entity, array $timestampDocumentArray = []): void
    {
        if (empty($entity->xml_file)) {
            return;
        }

        $xml_document = $entity->xml_file;
        RsTag::update($entity);
        if (! empty($entity->category_id)) {
            $xml_document = $this->updateXmlCategories($xml_document, $entity->category_id);
        }
        $groupLink = $entity->groups()->pluck('xml_value')->toArray();

        if (! empty($groupLink)) {
            $xml_document = $this->updateXmlGroups($xml_document, $groupLink);
        }

        $xml_document = $this->updateRegistrationInfo($xml_document, $entity->entityid, $timestampDocumentArray);

        Entity::whereId($entity->id)->update(['xml_file' => $xml_document]);

    }

    public function updateEntitiesXml(): void
    {
        $this->mdURI = config('xmlNameSpace.md');
        $this->mdattrURI = config('xmlNameSpace.mdattr');
        $this->samlURI = config('xmlNameSpace.saml');
        $this->mdrpiURI = config('xmlNameSpace.mdrpi');

        $timestampDocumentArray = $this->splitDocument();
        // dump($timestampDocumentArray);

        foreach (Entity::select()->get() as $entity) {
            $this->updateEntityXml($entity, $timestampDocumentArray);
        }
    }
}
