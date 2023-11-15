<?php

namespace App\Traits;

use Illuminate\Http\Request;

trait ValidatorTrait
{
    public string $code = '';

    public string $message = '';

    public string $error = '';

    // //////////////////////////////////////////////////
    // Helper functions
    // //////////////////////////////////////////////////
    public function getMetadata(Request $request): string
    {
        if ($request->hasFile('file')) {
            try {
                return file_get_contents($request->file);
            } catch (\Throwable $t) {
                return false;
            }
        }

        if ($request->input('metadata')) {
            return $request->input('metadata');
        }
    }

    public function libxml_display_errors(): string
    {
        $errors = libxml_get_errors();
        $result = null;
        foreach ($errors as $error) {
            $result .= trim($error->message).' ';
        }

        return $result;
        libxml_clear_errors();
    }

    public function createDOM(string $metadata): object
    {
        libxml_use_internal_errors(true);

        $dom = new \DOMDocument();
        $dom->loadXML($metadata);

        $result = null;
        foreach (libxml_get_errors() as $error) {
            $result .= "Error on line {$error->line}: ".trim($error->message).'. ';
        }
        if (! is_null($result)) {
            $this->error = $result;
        }

        if (
            ! $dom->schemaValidate(dirname(__DIR__).'/../xsd/saml-schema-metadata-2.0.xsd') or
            ! $dom->schemaValidate(dirname(__DIR__).'/../xsd/sstc-saml-metadata-ui-v1.0.xsd')
        ) {
            $this->error = 'This metadata is not valid against XML schema. '.$this->libxml_display_errors();
        }

        return $dom;
    }

    public function createXPath(object $dom): object
    {
        $xpath = new \DOMXPath($dom);

        $xpath->registerNameSpace('md', 'urn:oasis:names:tc:SAML:2.0:metadata');
        $xpath->registerNameSpace('ds', 'http://www.w3.org/2000/09/xmldsig#');
        $xpath->registerNameSpace('shibmd', 'urn:mace:shibboleth:metadata:1.0');
        $xpath->registerNameSpace('mdui', 'urn:oasis:names:tc:SAML:metadata:ui');
        $xpath->registerNameSpace('eduidmd', 'http://eduid.cz/schema/metadata/1.0');
        $xpath->registerNameSpace('init', 'urn:oasis:names:tc:SAML:profiles:SSO:request-init');
        $xpath->registerNameSpace('idpdisc', 'urn:oasis:names:tc:SAML:profiles:SSO:idp-discovery-protocol');
        $xpath->registerNameSpace('mdattr', 'urn:oasis:names:tc:SAML:metadata:attribute');
        $xpath->registerNameSpace('saml', 'urn:oasis:names:tc:SAML:2.0:assertion');
        $xpath->registerNameSpace('remd', 'http://refeds.org/metadata');

        return $xpath;
    }

    public function isIDP(object $xpath): bool
    {
        if (($xpath->query('/md:EntityDescriptor/md:IDPSSODescriptor')->length === 1) &&
            ($xpath->query('/md:EntityDescriptor/md:SPSSODescriptor')->length === 0)
        ) {
            return true;
        }

        return false;
    }

    // //////////////////////////////////////////////////
    // SAML parser
    // //////////////////////////////////////////////////
    public function getEntityType(object $xpath): string
    {
        if ($this->isIDP($xpath)) {
            return 'idp';
        } else {
            return 'sp';
        }
    }

    public function getEntityId(object $xpath): string
    {
        return $xpath->query('/md:EntityDescriptor')->item(0)->getAttribute('entityID');
    }

    public function getEntityScope(object $xpath): ?string
    {
        if (! $this->isIDP($xpath)) {
            return null;
        }

        return $xpath->query('/md:EntityDescriptor/md:IDPSSODescriptor/md:Extensions/shibmd:Scope')->item(0)->nodeValue ?? null;
    }

    public function getEntityFile(object $xpath): string
    {
        return urlencode(preg_replace('#^https://#', '', $this->getEntityId($xpath))).'.xml';
    }

    public function getEntityNameEn(object $xpath): ?string
    {
        return $xpath->query('//md:Extensions/mdui:UIInfo/mdui:DisplayName[@xml:lang="en"]')->item(0)->nodeValue ?? null;
    }

    public function getEntityNameCs(object $xpath): ?string
    {
        return $xpath->query('//md:Extensions/mdui:UIInfo/mdui:DisplayName[@xml:lang="cs"]')->item(0)->nodeValue ?? null;
    }

    public function getEntityDescriptionEn(object $xpath): ?string
    {
        return $xpath->query('//md:Extensions/mdui:UIInfo/mdui:Description[@xml:lang="en"]')->item(0)->nodeValue ?? null;
    }

    public function getEntityDescriptionCs(object $xpath): ?string
    {
        return $xpath->query('//md:Extensions/mdui:UIInfo/mdui:Description[@xml:lang="cs"]')->item(0)->nodeValue ?? null;
    }

    public function getEntityRS(object $xpath): bool
    {
        if ($this->isIDP($xpath)) {
            $values = $xpath->query('/md:EntityDescriptor/md:Extensions/mdattr:EntityAttributes/saml:Attribute[@Name="http://macedir.org/entity-category-support"]/saml:AttributeValue');
            foreach ($values as $value) {
                if (strcmp($value->nodeValue, 'http://refeds.org/category/research-and-scholarship') === 0) {
                    return true;
                }
            }
        }

        return false;
    }

    public function getEntityCocoV1(object $xpath): bool
    {
        if ($this->isIDP($xpath)) {
            $values = $xpath->query('/md:EntityDescriptor/md:Extensions/mdattr:EntityAttributes/saml:Attribute[@Name="http://macedir.org/entity-category-support"]/saml:AttributeValue');
            foreach ($values as $value) {
                if (strcmp($value->nodeValue, 'http://www.geant.net/uri/dataprotection-code-of-conduct/v1') === 0) {
                    return true;
                }
            }
        } else {
            $values = $xpath->query('/md:EntityDescriptor/md:Extensions/mdattr:EntityAttributes/saml:Attribute[@Name="http://macedir.org/entity-category"]/saml:AttributeValue');
            foreach ($values as $value) {
                if (strcmp($value->nodeValue, 'http://www.geant.net/uri/dataprotection-code-of-conduct/v1') === 0) {
                    return true;
                }
            }
        }

        return false;
    }

    public function getEntitySirtfi(object $xpath): bool
    {
        $values = $xpath->query('/md:EntityDescriptor/md:Extensions/mdattr:EntityAttributes/saml:Attribute[@Name="urn:oasis:names:tc:SAML:attribute:assurance-certification"]/saml:AttributeValue');
        foreach ($values as $value) {
            if (strcmp($value->nodeValue, 'https://refeds.org/sirtfi') === 0) {
                return true;
            }
        }

        return false;
    }

    public function parseMetadata(string $metadata): string
    {
        $dom = $this->createDOM($metadata);
        $xpath = $this->createXPath($dom);

        if ($this->checkForEntityDescriptorElement($xpath)) {
            return json_encode([
                'type' => $this->getEntityType($xpath),
                'scope' => $this->getEntityScope($xpath),
                'entityid' => $this->getEntityId($xpath),
                'file' => $this->getEntityFile($xpath),
                'name_en' => $this->getEntityNameEn($xpath),
                'name_cs' => $this->getEntityNameCs($xpath),
                'description_en' => $this->getEntityDescriptionEn($xpath),
                'description_cs' => $this->getEntityDescriptionCs($xpath),
                'rs' => $this->getEntityRS($xpath),
                'cocov1' => $this->getEntityCocoV1($xpath),
                'sirtfi' => $this->getEntitySirtfi($xpath),
                'metadata' => $metadata,
            ], JSON_FORCE_OBJECT);
        }

        return json_encode([
            'result' => 'Error',
            'error' => 'This is no metadata.',
        ], JSON_FORCE_OBJECT);
    }

    // //////////////////////////////////////////////////
    // SAML validator
    // //////////////////////////////////////////////////
    public function checkDependencies(): void
    {
        if (! extension_loaded('exif')) {
            throw new \Exception('Exif support not available.');
        }

        if (! extension_loaded('gd')) {
            throw new \Exception('GD support not available.');
        }
    }

    public function checkForEntityDescriptorElement($xpath): bool
    {
        if ($xpath->query('/md:EntityDescriptor')->length === 1) {
            return true;
        }

        return false;
    }

    public function checkHTTPS(object $xpath): void
    {
        $URL = [];

        if ($this->isIDP($xpath)) {
            $SSODescriptor = 'md:IDPSSODescriptor';
        } else {
            $SSODescriptor = 'md:SPSSODescriptor';
        }

        // /md:EntityDescriptor[@entityID]
        $URL['entityID'] = $xpath->query('/md:EntityDescriptor')->item(0)->getAttribute('entityID');

        // /md:EntityDescriptor/$SSODescriptor/md:Extensions/mdui:UIInfo/mdui:Logo
        $Logo = $xpath->query('/md:EntityDescriptor/'.$SSODescriptor.'/md:Extensions/mdui:UIInfo/mdui:Logo');
        for ($i = 0; $i < $Logo->length; $i++) {
            $URL['Logo'.($i + 1)] = $Logo->item($i)->nodeValue;
        }

        // /md:EntityDescriptor/$SSODescriptor/md:ArtifactResolutionService
        $ArtifactResolutionService = $xpath->query('/md:EntityDescriptor/'.$SSODescriptor.'/md:ArtifactResolutionService');
        for ($i = 0; $i < $ArtifactResolutionService->length; $i++) {
            $URL['ArtifactResolutionService'.($i + 1)] = $ArtifactResolutionService->item($i)->getAttribute('Location');
        }

        // /md:EntityDescriptor/$SSODescriptor/md:SingleLogoutService
        $SingleLogoutService = $xpath->query('/md:EntityDescriptor/'.$SSODescriptor.'/md:SingleLogoutService');
        for ($i = 0; $i < $SingleLogoutService->length; $i++) {
            $URL['SingleLogoutService'.($i + 1)] = $SingleLogoutService->item($i)->getAttribute('Location');
        }

        // /md:EntityDescriptor/$SSODescriptor/md:SingleSignOnService
        $SingleSignOnService = $xpath->query('/md:EntityDescriptor/'.$SSODescriptor.'/md:SingleSignOnService');
        for ($i = 0; $i < $SingleSignOnService->length; $i++) {
            $URL['SingleSignOnService'.($i + 1)] = $SingleSignOnService->item($i)->getAttribute('Location');
        }

        // /md:EntityDescriptor/md:AttributeAuthorityDescriptor/md:AttributeService
        $AttributeService = $xpath->query('/md:EntityDescriptor/md:AttributeAuthorityDescriptor/md:AttributeService');
        for ($i = 0; $i < $AttributeService->length; $i++) {
            $URL['AttributeService'.($i + 1)] = $AttributeService->item($i)->getAttribute('Location');
        }

        // /md:EntityDescriptor/$SSODescriptor/md:Extensions/init:RequestInitiator
        $RequestInitiator = $xpath->query('/md:EntityDescriptor/'.$SSODescriptor.'/md:Extensions/init:RequestInitiator');
        for ($i = 0; $i < $RequestInitiator->length; $i++) {
            $URL['RequestInitiator'.($i + 1)] = $RequestInitiator->item($i)->getAttribute('Location');
        }

        // /md:EntityDescriptor/$SSODescriptor/md:Extensions/idpdisc:DiscoveryResponse
        $DiscoveryResponse = $xpath->query('/md:EntityDescriptor/'.$SSODescriptor.'/md:Extensions/idpdisc:DiscoveryResponse');
        for ($i = 0; $i < $DiscoveryResponse->length; $i++) {
            $URL['DiscoveryResponse'.($i + 1)] = $DiscoveryResponse->item($i)->getAttribute('Location');
        }

        // /md:EntityDescriptor/$SSODescriptor/md:AssertionConsumerService
        $AssertionConsumerService = $xpath->query('/md:EntityDescriptor/'.$SSODescriptor.'/md:AssertionConsumerService');
        for ($i = 0; $i < $AssertionConsumerService->length; $i++) {
            $URL['AssertionConsumerService'.($i + 1)] = $AssertionConsumerService->item($i)->getAttribute('Location');
        }

        foreach ($URL as $key => $value) {
            if (! preg_match('/^https\:\/\//', $value)) {
                $this->error .= "HTTPS missing in $key. ";
            }
        }
    }

    public function checkRepublishRequest(object $xpath): void
    {
        if ($this->isIDP($xpath)) {
            $SSODescriptor = 'IDPSSODescriptor';
        } else {
            $SSODescriptor = 'SPSSODescriptor';
        }

        $RepublishRequestIDP = $xpath->query('/md:EntityDescriptor/md:IDPSSODescriptor/md:Extensions/eduidmd:RepublishRequest');
        $RepublishRequestSP = $xpath->query('/md:EntityDescriptor/md:SPSSODescriptor/md:Extensions/eduidmd:RepublishRequest');
        $RepublishRequest = $xpath->query('/md:EntityDescriptor/md:Extensions/eduidmd:RepublishRequest');
        $RepublishTarget = $xpath->query('/md:EntityDescriptor/md:Extensions/eduidmd:RepublishRequest/eduidmd:RepublishTarget');

        if (($RepublishRequestIDP->length > 0) or ($RepublishRequestSP->length > 0)) {
            $this->error .= 'RepublishRequest element placed incorrectly in /EntityDescriptor/'.$SSODescriptor.'/Extensions, but it has to be in /EntityDescriptor/Extensions. ';
        }

        if ($RepublishRequest->length > 0) {
            if ($RepublishTarget->length > 0) {
                if (strcmp('http://edugain.org/', $RepublishTarget->item(0)->nodeValue) !== 0) {
                    $this->error .= '/EntityDescriptor/Extensions/RepublishRequest/RepublishTarget misconfigured.';
                }
            } else {
                $this->error .= '/EntityDescriptor/Extensions/RepublishRequest/RepublishTarget missing.';
            }
        }
    }

    public function checkURLaddress(object $element): string
    {
        foreach ($element as $e) {
            @$file = file_get_contents(trim($e->nodeValue));

            if (@$http_response_header === null) {
                return $e->nodeValue.' from '.$e->parentNode->nodeName.'/'.$e->nodeName.'[@xml:lang="'.$e->getAttribute('xml:lang').'"] could not be read, check www.ssllabs.com for possible SSL errors. ';
            } elseif (preg_match('/403|404|500/', $http_response_header[0])) {
                return $e->nodeValue.' from '.$e->parentNode->nodeName.'/'.$e->nodeName.'[@xml:lang="'.$e->getAttribute('xml:lang').'"] could not be read due to '.$http_response_header[0].' return code. ';
            } elseif (! $file) {
                return $e->nodeValue.' from '.$e->parentNode->nodeName.'/'.$e->nodeName.'[@xml:lang="'.$e->getAttribute('xml:lang').'"] failed to load. ';
            } else {
                return '';
            }
        }
    }

    public function checkUIInfo(object $xpath): void
    {
        if ($this->isIDP($xpath)) {
            $SSODescriptor = 'IDPSSODescriptor';
        } else {
            $SSODescriptor = 'SPSSODescriptor';
        }

        $DisplayName_CS = $xpath->query('/md:EntityDescriptor/md:'.$SSODescriptor.'/md:Extensions/mdui:UIInfo/mdui:DisplayName[@xml:lang="cs"]');
        $DisplayName_EN = $xpath->query('/md:EntityDescriptor/md:'.$SSODescriptor.'/md:Extensions/mdui:UIInfo/mdui:DisplayName[@xml:lang="en"]');
        $Description_CS = $xpath->query('/md:EntityDescriptor/md:'.$SSODescriptor.'/md:Extensions/mdui:UIInfo/mdui:Description[@xml:lang="cs"]');
        $Description_EN = $xpath->query('/md:EntityDescriptor/md:'.$SSODescriptor.'/md:Extensions/mdui:UIInfo/mdui:Description[@xml:lang="en"]');
        $InformationURL_CS = $xpath->query('/md:EntityDescriptor/md:'.$SSODescriptor.'/md:Extensions/mdui:UIInfo/mdui:InformationURL[@xml:lang="cs"]');
        $InformationURL_EN = $xpath->query('/md:EntityDescriptor/md:'.$SSODescriptor.'/md:Extensions/mdui:UIInfo/mdui:InformationURL[@xml:lang="en"]');
        $Logo = $xpath->query('/md:EntityDescriptor/md:'.$SSODescriptor.'/md:Extensions/mdui:UIInfo/mdui:Logo');
        $PrivacyStatementURL_CS = $xpath->query('/md:EntityDescriptor/md:'.$SSODescriptor.'/md:Extensions/mdui:UIInfo/mdui:PrivacyStatementURL[@xml:lang="cs"]');
        $PrivacyStatementURL_EN = $xpath->query('/md:EntityDescriptor/md:'.$SSODescriptor.'/md:Extensions/mdui:UIInfo/mdui:PrivacyStatementURL[@xml:lang="en"]');

        if ($DisplayName_CS->length !== 1) {
            $this->error .= $SSODescriptor.'/UIInfo/DisplayName[@xml:lang="cs"] missing. ';
        }

        if ($DisplayName_EN->length !== 1) {
            $this->error .= $SSODescriptor.'/UIInfo/DisplayName[@xml:lang="en"] missing. ';
        }

        if ($Description_CS->length !== 1) {
            $this->error .= $SSODescriptor.'/UIInfo/Description[@xml:lang="cs"] missing. ';
        }

        if ($Description_EN->length !== 1) {
            $this->error .= $SSODescriptor.'/UIInfo/Description[@xml:lang="en"] missing. ';
        }

        if ($InformationURL_CS->length !== 1) {
            $this->error .= $SSODescriptor.'/UIInfo/InformationURL[@xml:lang="cs"] missing. ';
        }

        if ($InformationURL_EN->length !== 1) {
            $this->error .= $SSODescriptor.'/UIInfo/InformationURL[@xml:lang="en"] missing. ';
        }

        if ($this->isIDP($xpath)) {
            if ($Logo->length < 1) {
                $this->error .= $SSODescriptor.'/UIInfo/Logo missing. ';
            } else {
                foreach ($Logo as $logo) {
                    @$file = file_get_contents($logo->nodeValue);
                    if (! $file) {
                        $this->error .= $SSODescriptor.'/UIInfo/Logo '.$logo->nodeValue.' could not be read. ';
                    } else {
                        if (exif_imagetype($logo->nodeValue)) {
                            $imagesize = getimagesize($logo->nodeValue);
                            $img_width = $imagesize[0];
                            $img_height = $imagesize[1];
                            $md_width = $logo->getAttribute('width');
                            $md_height = $logo->getAttribute('height');

                            if ($img_width != $md_width) {
                                $this->error .= $SSODescriptor.'/UIInfo/Logo[@width="'.$md_width.'"] does not match the width ('.$img_width.'px) of the image '.$logo->nodeValue.'. ';
                            }

                            if ($img_height != $md_height) {
                                $this->error .= $SSODescriptor.'/UIInfo/Logo[@height="'.$md_height.'"] does not match the height ('.$img_height.'px) of the image '.$logo->nodeValue.'. ';
                            }
                        }

                        if (! exif_imagetype($logo->nodeValue)) {
                            $doc = new \DOMDocument();
                            $doc->load($logo->nodeValue);
                            if (strcmp($doc->documentElement->nodeName, 'svg') !== 0) {
                                $this->error .= $SSODescriptor.'/UIInfo/Logo '.$logo->nodeValue.' is not an image. ';
                            }
                        }
                    }
                }
            }
        }

        if ($PrivacyStatementURL_CS->length > 0) {
            $r = $this->checkURLaddress($PrivacyStatementURL_CS);
            if ($r) {
                $this->error .= $r;
            }
        }

        if ($PrivacyStatementURL_EN->length > 0) {
            $r = $this->checkURLaddress($PrivacyStatementURL_EN);
            if ($r) {
                $this->error .= $r;
            }
        }
    }

    public function checkCertificates(object $xpath): void
    {
        $certificates = $xpath->query('//ds:X509Certificate');

        if ($certificates->length === 0) {
            $this->error .= 'No certificate found. ';

            return;
        }

        $i = 0;
        foreach ($certificates as $cert) {
            $X509Certificate = "-----BEGIN CERTIFICATE-----\n".trim($cert->nodeValue)."\n-----END CERTIFICATE-----";
            $cert_info = openssl_x509_parse($X509Certificate, true);

            if (is_array($cert_info)) {
                $cert_validTo = date('Y-m-d', $cert_info['validTo_time_t']);
                $cert_validFor = floor((strtotime($cert_validTo) - time()) / (60 * 60 * 24));
                $pub_key = openssl_pkey_get_details(openssl_pkey_get_public($X509Certificate));
            } else {
                $this->error .= 'The certificate #'.($i + 1).' is invalid. ';
            }

            // This is here to skip every other certificate in order to
            // allow proper certificate rollover, i.e. to change
            // expired/old certificate.
            if ($i % 2 === 0) {
                $i++;

                continue;
            }

            $CRT_VALIDITY = 30;
            if ($cert_validFor < $CRT_VALIDITY) {
                $this->error .= 'The certificate(s) must be valid at least for '.$CRT_VALIDITY.' days, yours certificate #'.($i + 1).' is valid for '.$cert_validFor.' days. ';
            }

            $CRT_KEY_SIZE_RSA = 2048;
            if (array_key_exists('rsa', $pub_key) && $pub_key['bits'] < $CRT_KEY_SIZE_RSA) {
                $this->error .= 'The RSA public key(s) must be at least '.$CRT_KEY_SIZE_RSA.' bits, yours RSA public key for certificate #'.($i + 1).' is '.$pub_key['bits'].' bits. ';
            }

            $CRT_KEY_SIZE_EC = 384;
            if (array_key_exists('ec', $pub_key) && $pub_key['bits'] < $CRT_KEY_SIZE_EC) {
                $this->error .= 'The EC public key(s) must be at least '.$CRT_KEY_SIZE_EC.' bits, yours EC public key for certificate #'.($i + 1).' is '.$pub_key['bits'].' bits. ';
            }

            $i++;
        }
    }

    public function checkScope(object $xpath): void
    {
        if (! $this->isIDP($xpath)) {
            return;
        }

        $IDPSSOScope = $xpath->query('/md:EntityDescriptor/md:IDPSSODescriptor/md:Extensions/shibmd:Scope');
        $AAScope = $xpath->query('/md:EntityDescriptor/md:AttributeAuthorityDescriptor/md:Extensions/shibmd:Scope');

        if ($IDPSSOScope->length !== 1) {
            $this->message .= 'WARNING: Precisely 1 EntityDescriptor/IDPSSODescriptor/Extensions/Scope is HIGHLY recommended. ';
        }

        if ($AAScope->length > 1) {
            $this->error .= 'Either 0 or 1 EntityDescriptor/AttributeAuthorityDescriptor/Extensions/Scope allowed. ';
        }
    }

    public function checkScopeRegexp(object $xpath): void
    {
        $Scopes = $xpath->query('//shibmd:Scope[@regexp]');

        foreach ($Scopes as $s) {
            if (strcmp($s->getAttribute('regexp'), 'false') !== 0) {
                $this->error .= 'All Scope[@regexp] must be "false". ';
            }
        }
    }

    public function checkScopeValue(object $xpath): void
    {
        $entityID = $xpath->query('/md:EntityDescriptor')->item(0)->getAttribute('entityID');
        $Scope = $xpath->query('//shibmd:Scope[@regexp]');

        $pattern = '/https:\/\/([a-z0-9_\-\.]+)\/.*/';
        $replacement = '$1';
        $hostname = preg_replace($pattern, $replacement, $entityID);

        foreach ($Scope as $s) {
            if (preg_match("/$s->nodeValue/", $hostname) !== 1) {
                $this->message .= 'All Scope elements should be a lowercase substring of EntityDescriptor[@entityID]. ';
            }
        }
    }

    public function checkAttributeAuthorityDescriptor(object $xpath): void
    {
        $SAML2binding = 'urn:oasis:names:tc:SAML:2.0:bindings:SOAP';
        $SAML2protocol = 'urn:oasis:names:tc:SAML:2.0:protocol';

        $AttributeAuthorityDescriptor = $xpath->query('/md:EntityDescriptor/md:AttributeAuthorityDescriptor');

        if ($AttributeAuthorityDescriptor->length > 0) {
            $AttributeService = $xpath->query('/md:EntityDescriptor/md:AttributeAuthorityDescriptor/md:AttributeService');
            $protocols = $AttributeAuthorityDescriptor->item(0)->getAttribute('protocolSupportEnumeration');

            foreach ($AttributeService as $as) {
                if (strcmp($as->getAttribute('Binding'), $SAML2binding) === 0) {
                    if (! preg_match("/$SAML2protocol/", $protocols)) {
                        $this->error .= 'SAML 2.0 binding requires SAML 2.0 token in EntityDescriptor/AttributeAuthorityDescriptor[@protocolSupportEnumeration]. ';
                    }
                }
            }

            if (preg_match("/$SAML2protocol/", $protocols)) {
                $SAML2binding_found = null;

                foreach ($AttributeService as $as) {
                    if (strcmp($SAML2binding, $as->getAttribute('Binding')) === 0) {
                        $SAML2binding_found = true;
                    }
                }

                if (! $SAML2binding_found) {
                    $this->error .= 'SAML 2.0 token in EntityDescriptor/AttributeAuthorityDescriptor[@protocolSupportEnumeration] requires SAML 2.0 binding. ';
                }
            }
        }
    }

    public function checkOrganization(object $xpath): void
    {
        $Organization = $xpath->query('/md:EntityDescriptor/md:Organization');

        if ($Organization->length === 0) {
            $this->error .= 'Organization element missing. ';
        } else {
            $OrganizationName_CS = $xpath->query('/md:EntityDescriptor/md:Organization/md:OrganizationName[@xml:lang="cs"]');
            $OrganizationName_EN = $xpath->query('/md:EntityDescriptor/md:Organization/md:OrganizationName[@xml:lang="en"]');
            $OrganizationDisplayName_CS = $xpath->query('/md:EntityDescriptor/md:Organization/md:OrganizationDisplayName[@xml:lang="cs"]');
            $OrganizationDisplayName_EN = $xpath->query('/md:EntityDescriptor/md:Organization/md:OrganizationDisplayName[@xml:lang="en"]');
            $OrganizationURL_CS = $xpath->query('/md:EntityDescriptor/md:Organization/md:OrganizationURL[@xml:lang="cs"]');
            $OrganizationURL_EN = $xpath->query('/md:EntityDescriptor/md:Organization/md:OrganizationURL[@xml:lang="en"]');

            if ($OrganizationName_CS->length === 0) {
                $this->error .= 'Organization/OrganizationName[@xml:lang="cs"] missing. ';
            }

            if ($OrganizationName_EN->length === 0) {
                $this->error .= 'Organization/OrganizationName[@xml:lang="en"] missing. ';
            }

            if ($OrganizationDisplayName_CS->length === 0) {
                $this->error .= 'Organization/OrganizationDisplayName[@xml:lang="cs"] missing. ';
            }

            if ($OrganizationDisplayName_EN->length === 0) {
                $this->error .= 'Organization/OrganizationDisplayName[@xml:lang="en"] missing ';
            }

            if ($OrganizationURL_CS->length === 0) {
                $this->error .= 'Organization/OrganizationURL[@xml:lang="cs"] missing. ';
            }

            if ($OrganizationURL_EN->length === 0) {
                $this->error .= 'Organization/OrganizationURL[@xml:lang="cs"] missing. ';
            }
        }
    }

    public function checkContactPerson(object $xpath): void
    {
        $ContactPerson = $xpath->query('/md:EntityDescriptor/md:ContactPerson');
        $ContactPersonTechnical = $xpath->query('/md:EntityDescriptor/md:ContactPerson[@contactType="technical"]');

        foreach ($ContactPerson as $c) {
            @$email = $c->getElementsByTagNameNS('urn:oasis:names:tc:SAML:2.0:metadata', 'EmailAddress')->item(0)->nodeValue;

            if (! preg_match('/^mailto\:/', $email)) {
                $this->error .= 'ContactPerson/EmailAddress does not contain "mailto:" scheme. ';
            }
        }

        if ($ContactPersonTechnical->length < 1) {
            $this->error .= 'ContactPerson[@contactType="technical"] undefined. ';
        } else {
            foreach ($ContactPersonTechnical as $c) {
                @$givenName = $c->getElementsByTagNameNS('urn:oasis:names:tc:SAML:2.0:metadata', 'GivenName')->item(0)->nodeValue;
                @$sn = $c->getElementsByTagNameNS('urn:oasis:names:tc:SAML:2.0:metadata', 'SurName')->item(0)->nodeValue;
                @$email = $c->getElementsByTagNameNS('urn:oasis:names:tc:SAML:2.0:metadata', 'EmailAddress')->item(0)->nodeValue;

                if (empty($givenName)) {
                    $this->error .= 'ContactPerson[@contactType="technical"]/GivenName missing. ';
                }

                if (empty($sn)) {
                    $this->error .= 'ContactPerson[@contactType="technical"]/SurName missing. ';
                }

                if (empty($email)) {
                    $this->error .= 'ContactPerson[@contactType="technical"]/EmailAddress missing. ';
                }
            }
        }
    }

    public function checkEC(object $xpath): void
    {
        if ($this->isIDP($xpath)) {
            /*
             * R&S + CoCo (IdP)
             */
            $entity_category_support = $xpath->query('/md:EntityDescriptor/md:Extensions/mdattr:EntityAttributes/saml:Attribute[@Name="http://macedir.org/entity-category-support"]/saml:AttributeValue');

            foreach ($entity_category_support as $category) {
                if (strcmp($category->nodeValue, 'http://refeds.org/category/research-and-scholarship') === 0) {
                    $this->message .= 'R&S application. ';
                }

                if (strcmp($category->nodeValue, 'http://www.geant.net/uri/dataprotection-code-of-conduct/v1') === 0) {
                    $this->message .= 'CoCo v1 application. ';
                }
            }
        } else {
            /*
             * R&S + CoCo (SP)
             */
            $entity_category = $xpath->query('/md:EntityDescriptor/md:Extensions/mdattr:EntityAttributes/saml:Attribute[@Name="http://macedir.org/entity-category"]/saml:AttributeValue');

            foreach ($entity_category as $category) {
                if (strcmp($category->nodeValue, 'http://refeds.org/category/research-and-scholarship') === 0) {
                    $this->message .= 'R&S application. ';
                }

                if (strcmp($category->nodeValue, 'http://www.geant.net/uri/dataprotection-code-of-conduct/v1') === 0) {
                    $this->message .= 'CoCo v1 application. ';

                    $PrivacyStatementURL_CS = $xpath->query('/md:EntityDescriptor/md:SPSSODescriptor/md:Extensions/mdui:UIInfo/mdui:PrivacyStatementURL[@xml:lang="cs"]');
                    $PrivacyStatementURL_EN = $xpath->query('/md:EntityDescriptor/md:SPSSODescriptor/md:Extensions/mdui:UIInfo/mdui:PrivacyStatementURL[@xml:lang="en"]');

                    if ($PrivacyStatementURL_CS->length === 0) {
                        $this->message .= 'UIInfo/PrivacyStatementURL[@xml:lang="cs"] missing, but that is OK for foreign services. ';
                    }

                    if ($PrivacyStatementURL_EN->length === 0) {
                        $this->error .= 'UIInfo/PrivacyStatementURL[@xml:lang="en"] missing. ';
                    }
                }
            }
        }

        $assurance_certification = $xpath->query('/md:EntityDescriptor/md:Extensions/mdattr:EntityAttributes/saml:Attribute[@Name="urn:oasis:names:tc:SAML:attribute:assurance-certification"]/saml:AttributeValue');

        foreach ($assurance_certification as $category) {
            /*
             * Sirtfi
             */
            if (strcmp($category->nodeValue, 'https://refeds.org/sirtfi') === 0) {
                $sirtfi_contact = $xpath->query('/md:EntityDescriptor/md:ContactPerson[@remd:contactType="http://refeds.org/metadata/contactType/security"]');

                if ($sirtfi_contact->length === 0) {
                    $this->error .= 'Sirtfi category claimed, but Sirtfi contact not defined. ';
                } elseif ($sirtfi_contact->length === 1) {
                    @$name = $sirtfi_contact->item(0)->getElementsByTagNameNS('urn:oasis:names:tc:SAML:2.0:metadata', 'GivenName')->item(0)->nodeValue;
                    @$email = $sirtfi_contact->item(0)->getElementsByTagNameNS('urn:oasis:names:tc:SAML:2.0:metadata', 'EmailAddress')->item(0)->nodeValue;

                    if (empty($name)) {
                        $this->error .= 'Sirtfi contact is missing GivenName element. ';
                    }

                    if (empty($email)) {
                        $this->error .= 'Sirtfi contact is missing EmailAddress element. ';
                    }

                    if (! empty($name) && ! empty($email)) {
                        $this->message .= 'Sirtfi application ('.preg_replace('/mailto:/', '', $email).'). ';
                    }
                } else {
                    $this->error .= 'Multiple Sirtfi contact definition is prohibited. ';
                }
            }
        }
    }

    public function checkOneEntityAttributesElementPerExtensions(object $xpath): void
    {
        if (($count = $xpath->query('/md:EntityDescriptor/md:Extensions/mdattr:EntityAttributes')->length) > 1) {
            $this->error .= "Multiple (in your case: {$count}) EntityAttributes elements in EntityDescriptor/Extensions forbidden. ";
        }
    }

    public function checkServiceProviderRequestedAttributeNameValueDuplicity(object $xpath): void
    {
        if ($this->isIDP($xpath)) {
            return;
        }

        $values = [];
        foreach ($xpath->query('/md:EntityDescriptor/md:SPSSODescriptor/md:AttributeConsumingService/md:RequestedAttribute') as $attribute) {
            $values[] = $attribute->getAttribute('Name');
        }

        if ($duplicates = array_diff_key($values, array_unique($values))) {
            $this->error .= 'Duplicated RequestedAttribute element definitions: '.implode(', ', $duplicates);
        }
    }

    public function generateResult(): void
    {
        if (empty($this->error)) {
            $this->code = 0;
        } else {
            $this->code = 1;
        }
    }

    public function validateMetadata(string $metadata): string
    {
        $this->checkDependencies();

        $dom = $this->createDOM($metadata);
        $xpath = $this->createXPath($dom);

        if ($this->checkForEntityDescriptorElement($xpath)) {
            $this->checkHTTPS($xpath);
            $this->checkRepublishRequest($xpath);
            $this->checkUIInfo($xpath);
            $this->checkCertificates($xpath);
            $this->checkScope($xpath);
            $this->checkScopeRegexp($xpath);
            $this->checkScopeValue($xpath);
            $this->checkAttributeAuthorityDescriptor($xpath);
            $this->checkOrganization($xpath);
            $this->checkContactPerson($xpath);
            $this->checkEC($xpath);
            $this->checkOneEntityAttributesElementPerExtensions($xpath);
            $this->checkServiceProviderRequestedAttributeNameValueDuplicity($xpath);

            $this->generateResult();
        }

        return json_encode([
            'code' => $this->code,
            'message' => $this->message,
            'error' => $this->error,
        ], JSON_FORCE_OBJECT);
    }
}
