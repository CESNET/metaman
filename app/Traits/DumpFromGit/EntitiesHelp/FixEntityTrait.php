<?php
namespace App\Traits\DumpFromGit\EntitiesHelp;
use App\Models\Entity;
use App\Traits\ValidatorTrait;
use DOMDocument;
use DOMNode;
use DOMXPath;

trait FixEntityTrait{

    use ValidatorTrait;

    private string $mdURI = 'urn:oasis:names:tc:SAML:2.0:metadata';
    private string $mdattrURI = 'urn:oasis:names:tc:SAML:metadata:attribute';
    private string $samlURI = 'urn:oasis:names:tc:SAML:2.0:assertion';
    private string $mdrpiURI = 'urn:oasis:names:tc:SAML:metadata:rpi';
    private string $mdui = 'urn:oasis:names:tc:SAML:metadata:ui';

    public function fixEntities(): void
    {
        foreach (Entity::select()->get() as $entity)
        {

            $this->mdURI = config('xmlNameSpace.md');
            $this->mdattrURI = config('xmlNameSpace.mdattr');
            $this->samlURI = config('xmlNameSpace.saml');
            $this->mdrpiURI = config('xmlNameSpace.mdrpi');
            $this->mdui = config('xmlNameSpace.mdui');

            $xml_document = $entity->xml_file;


            //$xml_document = $entity->metadata;

            $res = json_decode($this->validateMetadata($xml_document,true),true);
            $res['ent_id'] = $entity->id;
            $errorArray = $res['errorArray'];

            $dom = $this->createDOM($xml_document);
            $xPath = $this->createXPath($dom);
            $UIInfo = $this->CreateUIInfo($dom,$xPath);


            if(array_key_exists('Logo',$errorArray))
            {
                $this->fixLogo($UIInfo,$dom,$xPath);
            }


            $xml_document = $dom->saveXML();
            Entity::whereId($entity->id)->update(['xml_file' => $xml_document]);
            dump($entity->id);

         //   dump($res);
        }
    }

    private function CreateUIInfo(DOMDocument $dom, DOMXPath $xPath ): DOMNode|null
    {
        $rootTag = $xPath->query('//md:Extensions')->item(0);
        $UIInfo = $xPath->query('//mdui:UIInfo');

        if($UIInfo->length === 0)
        {
            $UIInfo = $dom->createElementNS($this->mdui,'mdui:UIInfo');
            $rootTag->appendChild($UIInfo);
        } else
        {
            $UIInfo = $UIInfo->item(0);
        }
        return $UIInfo ;
    }

    private function deleteLogo(object $tag): void
    {
        if ($tag->parentNode) {
            $tag->parentNode->removeChild($tag);
        }
    }
    private function ClearOldLogo(object $logos):void
    {
        foreach ($logos as $logo) {
            $this->deleteLogo($logo);
        }
    }


    private function fixLogo(DOMNode $UIInfo,DOMDocument $dom, DOMXPath $xPath): void
    {
        $logo = $xPath->query('//mdui:Logo');
        $this->ClearOldLogo($logo);

        $logo = $dom->createElementNS($this->mdui,'mdui:Logo','https://www.eduid.cz/images/no_logo_100x100.png');
        $logo->setAttribute('width',100);
        $logo->setAttribute('height',100);
        $UIInfo->appendChild($logo);

    }


}
