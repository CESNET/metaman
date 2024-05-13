<?php
namespace App\Traits\DumpFromGit\EntitiesHelp;
use App\Models\Category;
use App\Models\Entity;
use App\Traits\ValidatorTrait;
use Illuminate\Support\Facades\Storage;

trait UpdateEntity
{
    private string $mdURI = 'urn:oasis:names:tc:SAML:2.0:metadata';
    private string $mdattrURI = 'urn:oasis:names:tc:SAML:metadata:attribute';
    private string $samlURI = 'urn:oasis:names:tc:SAML:2.0:assertion';
    private string $mdrpiURI = 'urn:oasis:names:tc:SAML:metadata:rpi';

    use ValidatorTrait;


    private function updateXmlCategories(string $xml_document, int $category_id ) : string
    {
        $dom = $this->createDOM($xml_document);
        $xPath = $this->createXPath($dom);

        $rootTag = $xPath->query("//*[local-name()='EntityDescriptor']")->item(0);
        $entityExtensions = $xPath->query('//md:Extensions');
        if($entityExtensions->length === 0)
        {
            $namespaceURI = $dom->documentElement->lookupNamespaceURI('md');
            $entityExtensions = $dom->createElementNS($this->mdURI, 'md:Extensions');
            $rootTag->insertBefore($entityExtensions,$rootTag->firstChild);
        }else {
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

        $categoryXml = Category::whereId($category_id)->first()->xml_value;

        $attributeValue = $dom->createElementNS($this->samlURI, 'saml:AttributeValue', $categoryXml);
        $attribute->appendChild($attributeValue);

        return $dom->saveXML();
    }

    private function updateResearchAndScholarship( string $xml_document,bool $isIdp) : string
    {
        $dom = $this->createDOM($xml_document);
        $xPath = $this->createXPath($dom);

        $rootTag = $xPath->query("//*[local-name()='EntityDescriptor']")->item(0);

        $extensions = $xPath->query('//md:Extensions');
        if($extensions->length === 0)
        {
            $extensions = $dom->createElementNS($this->mdURI, 'md:Extensions');
            $rootTag->appendChild($extensions);
        }
        else
        {
            $extensions = $extensions->item(0);
        }

        $entityAttributes = $xPath->query('//mdattr:EntityAttributes');
        if ($entityAttributes->length === 0) {
            $entityAttributes = $dom->createElementNS( $this->mdattrURI,'mdattr:EntityAttributes');
            $extensions->appendChild($entityAttributes);
        } else {
            $entityAttributes = $entityAttributes->item(0);
        }


        $attribute = $xPath->query('//mdattr:EntityAttributes/saml:Attribute', $entityAttributes);
        if ($attribute->length === 0) {
            $attribute = $dom->createElementNS($this->samlURI, 'saml:Attribute');
            $attribute->setAttribute('NameFormat', 'urn:oasis:names:tc:SAML:2.0:attrname-format:uri');

            if($isIdp)
                $attribute->setAttribute('Name', 'http://macedir.org/entity-category-support');
            else
                $attribute->setAttribute('Name', 'http://macedir.org/entity-category');

            $entityAttributes->appendChild($attribute);
        } else {
            $attribute = $attribute->item(0);
        }

        $attributeValue = $dom->createElementNS($this->samlURI, 'saml:AttributeValue', 'http://refeds.org/category/research-and-scholarship');
        $attribute->appendChild($attributeValue);


        $dom->normalize();
        return $dom->saveXML();

    }

    /**
     * @throws Exception if  exist more or less then 2 part something gone wrong
     */
    private function splitDocument():array
    {
        $document = Storage::get(config('git.reginfo'));
        $lines = explode("\n", $document);
        $splitDocument = [];

        foreach ($lines as $line) {
            if(empty(ltrim($line))) {
                continue;
            }
            $parts = preg_split('/\s+/', $line, 2);
            if(count($parts) != 2) {
                throw new Exception('no 2 part');
            } else
            {
                $splitDocument[$parts[0]] = $parts[1];
            }
        }
        return $splitDocument;
    }

    private function updateRegistrationInfo(string $xml_document, string $entityId,array $timestampDocumentArray ) : string
    {
        $dom = $this->createDOM($xml_document);
        $xPath = $this->createXPath($dom);
        $rootTag = $xPath->query("//*[local-name()='EntityDescriptor']")->item(0);

        $entityExtensions = $xPath->query('//md:Extensions');
        if ($entityExtensions->length === 0) {
            $entityExtensions = $dom->createElementNS( $this->mdURI,'md:Extensions');
            $rootTag->insertBefore($entityExtensions,$rootTag->firstChild);
        } else {
            $entityExtensions = $entityExtensions->item(0);
        }
        $info = $xPath->query('//mdrpi:RegistrationInfo', $entityExtensions);
        if ($info->length === 0) {

            $info = $dom->createElementNS($this->samlURI, 'mdrpi:RegistrationInfo');

            $info->setAttribute('registrationAuthority', config('registrationInfo.registrationAuthority'));


            if(empty($timestampDocumentArray[$entityId])) {
                $info->setAttribute('registrationInstant', gmdate('Y-m-d\TH:i:s\Z'));
            } else {
                $info->setAttribute('registrationInstant',$timestampDocumentArray[$entityId]);
            }


            $entityExtensions->appendChild($info);
        } else {
            $info = $info->item(0);
        }

        //For English
        $registrationPolicyEN = $dom->createElementNS($this->samlURI, 'saml:AttributeValue',config('registrationInfo.en'));
        $registrationPolicyEN->setAttributeNS('http://www.w3.org/XML/1998/namespace', 'xml:lang', 'en');
        $info->appendChild($registrationPolicyEN);
        // For Czech
        $registrationPolicyCZ = $dom->createElementNS($this->samlURI, 'saml:AttributeValue',config('registrationInfo.cs'));
        $registrationPolicyCZ->setAttributeNS('http://www.w3.org/XML/1998/namespace', 'xml:lang', 'cs');
        $info->appendChild($registrationPolicyCZ);

        $dom->normalize();
        return $dom->saveXML();
    }




    public function updateEntitiesXml() : void
    {
        $this->mdURI = config('xmlNameSpace.md');
        $this->mdattrURI = config('xmlNameSpace.mdattr');
        $this->samlURI = config('xmlNameSpace.saml');
        $this->mdrpiURI = config('xmlNameSpace.mdrpi');

        $timestampDocumentArray = $this->splitDocument();
        // dump($timestampDocumentArray);


        foreach (Entity::select()->get() as $entity)
        {
            if(empty($entity->xml_file))
                continue;

            $xml_document = $entity->xml_file;
            $isIdp = false;
            if($entity->type == "idp")
                $isIdp = true;


            if($entity->rs)
            {
                $xml_document = $this->updateResearchAndScholarship($xml_document,$isIdp);
            }
            if(!empty($entity->category_id))
            {
                $xml_document = $this->updateXmlCategories($xml_document,$entity->category_id);
            }

            $xml_document = $this->updateRegistrationInfo($xml_document,$entity->entityid,$timestampDocumentArray);


            Entity::whereId($entity->id)->update(['xml_file' => $xml_document]);
        }
    }


}
