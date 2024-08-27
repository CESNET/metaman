<?php

namespace App\Console\Commands;

use App\Models\Entity;
use App\Models\Federation;
use App\Traits\DumpFromGit\EntitiesHelp\DeleteFromEntity;
use App\Traits\GitTrait;
use App\Traits\ValidatorTrait;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class PrepareTestData extends Command
{
    use DeleteFromEntity,GitTrait,ValidatorTrait;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:prepare-test-data';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Prepare test data for JMeter testing';

    private function getAllFederationToImport(): array
    {
        $this->initializeGit();

        $cfgfiles = [];
        foreach (Storage::files() as $file) {
            if (preg_match('/^'.config('git.edugain_cfg').'$/', $file)) {
                continue;
            }

            if (preg_match('/\.cfg$/', $file)) {
                $cfgfiles[] = $file;
            }
        }

        $federations = Federation::select('cfgfile')->get()->pluck('cfgfile')->toArray();

        $unknown = [];
        foreach ($cfgfiles as $cfgfile) {
            if (in_array($cfgfile, $federations)) {
                continue;
            }

            $content = Storage::get($cfgfile);
            preg_match('/\[(.*)\]/', $content, $xml_id);
            preg_match('/filters\s*=\s*(.*)/', $content, $filters);
            preg_match('/name\s*=\s*(.*)/', $content, $name);

            $unknown[$cfgfile]['cfgfile'] = $cfgfile;
            $unknown[$cfgfile]['xml_id'] = $xml_id[1];
            $unknown[$cfgfile]['filters'] = $filters[1];
            $unknown[$cfgfile]['name'] = $name[1];
            $unknown[$cfgfile]['cfgfile'] = $cfgfile;
        }

        return $unknown;
    }

    private function importFederations(): void
    {
        $unk = $this->getAllFederationToImport();
        foreach ($unk as $value) {
            $cfgfile = $value['cfgfile'];
            $content = Storage::get($cfgfile);
            preg_match('/\[(.*)\]/', $content, $xml_id);
            preg_match('/filters\s*=\s*(.*)/', $content, $filters);
            preg_match('/name\s*=\s*(.*)/', $content, $xml_name);

            if (empty($names[$cfgfile])) {
                $names[$cfgfile] = preg_replace('/\.cfg$/', '', $cfgfile);
            }

            if (empty($descriptions[$cfgfile])) {
                $descriptions[$cfgfile] = preg_replace('/\.cfg$/', '', $cfgfile);
            }

            DB::transaction(function () use ($cfgfile, $names, $descriptions, $xml_id, $xml_name, $filters) {
                $federation = Federation::create([
                    'name' => $names[$cfgfile],
                    'description' => $descriptions[$cfgfile],
                    'tagfile' => preg_replace('/\.cfg$/', '.tag', $cfgfile),
                    'cfgfile' => $cfgfile,
                    'xml_id' => $xml_id[1],
                    'xml_name' => $xml_name[1],
                    'filters' => $filters[1],
                    'explanation' => 'Imported from Git repository.',
                ]);

                $federation->approved = true;
                $federation->update();
            });
        }
    }

    private function importOneSp(string $metadata, Federation $federation): void
    {

        $result = json_decode($this->validateMetadata($metadata), true);
        $new_entity = json_decode($this->parseMetadata($metadata), true);

        if (array_key_exists('result', $new_entity) && ! is_null($new_entity['result'])) {
            return;
        }

        $existing = Entity::whereEntityid($new_entity['entityid'])->first();
        if ($existing) {
            return;
        }

        switch ($result['code']) {
            case '0':

                DB::transaction(function () use ($new_entity, $federation) {
                    $new_entity = array_merge($new_entity, ['xml_file' => $this->deleteTags($new_entity['metadata'])]);

                    $entity = Entity::create($new_entity);
                    $entity->operators()->attach(1);
                    $entity->federations()->attach($federation, [
                        'explanation' => 'test',
                        'requested_by' => 1,
                        'approved_by' => 1,
                        'approved' => true,
                    ]);
                    $entity->approved = true;
                    $entity->save();

                    return $entity;
                });
                break;

            default:
                break;
        }

    }

    private function createSp()
    {
        $metadataArray[] = '<?xml version="1.0"?>
<EntityDescriptor xmlns:mdui="urn:oasis:names:tc:SAML:metadata:ui" xmlns="urn:oasis:names:tc:SAML:2.0:metadata" entityID="https://adfs.w2lan.cesnet.cz/adfs/services/trust">
  <SPSSODescriptor WantAssertionsSigned="true" protocolSupportEnumeration="urn:oasis:names:tc:SAML:2.0:protocol">
    <Extensions>
      <mdui:UIInfo xmlns:mdui="urn:oasis:names:tc:SAML:metadata:ui">
        <mdui:DisplayName xml:lang="en">ADFS</mdui:DisplayName>
        <mdui:DisplayName xml:lang="cs">ADFS</mdui:DisplayName>
        <mdui:Description xml:lang="en">ADFS (Windows domain w2lan.cesnet.cz)</mdui:Description>
        <mdui:Description xml:lang="cs">ADFS (domena Windows w2lan.cesnet.cz)</mdui:Description>
        <mdui:InformationURL xml:lang="en">https://www.cesnet.cz/?lang=en</mdui:InformationURL>
        <mdui:InformationURL xml:lang="cs">https://www.cesnet.cz/</mdui:InformationURL>
      </mdui:UIInfo>
      <mdrpi:RegistrationInfo xmlns:mdrpi="urn:oasis:names:tc:SAML:2.0:assertion" registrationAuthority="http://www.eduid.cz/" registrationInstant="2024-06-11T10:16:21Z">
        <saml:AttributeValue xmlns:saml="urn:oasis:names:tc:SAML:2.0:assertion" xml:lang="en">http://www.eduid.cz/wiki/_media/en/eduid/policy/policy_eduid_en-1_1.pdf</saml:AttributeValue>
        <saml:AttributeValue xmlns:saml="urn:oasis:names:tc:SAML:2.0:assertion" xml:lang="cs">http://www.eduid.cz/wiki/_media/eduid/policy/policy_eduid_cz-1_1-3.pdf</saml:AttributeValue>
      </mdrpi:RegistrationInfo>
      <mdattr:EntityAttributes xmlns:mdattr="urn:oasis:names:tc:SAML:metadata:attribute">
        <saml:Attribute xmlns:saml="urn:oasis:names:tc:SAML:2.0:assertion" NameFormat="urn:oasis:names:tc:SAML:2.0:attrname-format:uri" Name="http://macedir.org/entity-category">
          <saml:AttributeValue>http://refeds.org/category/research-and-scholarship</saml:AttributeValue>
        </saml:Attribute>
      </mdattr:EntityAttributes>
    </Extensions>
    <KeyDescriptor use="encryption">
      <KeyInfo xmlns="http://www.w3.org/2000/09/xmldsig#">
        <X509Data>
          <X509Certificate>MIIDoTCCAokCFDvaf91QzMDWRgkPAW1zpYXlFjcOMA0GCSqGSIb3DQEBCwUAMIGMMQswCQYDVQQGEwJDWjEOMAwGA1UECAwFUHJhaGExDjAMBgNVBAcMBVByYWhhMQ8wDQYDVQQKDAZDRVNORVQxGzAZBgNVBAsMEkluZm9ybWFjbmkgU3lzdGVteTEvMC0GA1UEAwwmQURGUyBFbmNyeXB0aW9uIC0gYWRmcy53Mmxhbi5jZXNuZXQuY3owHhcNMjEwMTA3MjMyMDA0WhcNMzEwMTA3MjMyMDA0WjCBjDELMAkGA1UEBhMCQ1oxDjAMBgNVBAgMBVByYWhhMQ4wDAYDVQQHDAVQcmFoYTEPMA0GA1UECgwGQ0VTTkVUMRswGQYDVQQLDBJJbmZvcm1hY25pIFN5c3RlbXkxLzAtBgNVBAMMJkFERlMgRW5jcnlwdGlvbiAtIGFkZnMudzJsYW4uY2VzbmV0LmN6MIIBIjANBgkqhkiG9w0BAQEFAAOCAQ8AMIIBCgKCAQEAqcDMWpatLsPCRouNaaNY/aLy92uJ6j9P1+v7/VCT9nY+F8Rr8mCxLw1rf9+MuyxWeYWGgKk1RsnyUc12wIaUHMtbvVf862JtB1hf7xMhasguL/r4NZzY1dalZvKHhHRFHmGUYRsJLVZTjjpqcQgW+5c87Wx5mZzoEALsdsl+B/LR6J7wSA96kxhJFl1u1uwYa4ghs6oIZ5Po4dSqMZeJwPxpfmMir6Jx/SYHnyn9Ib1TVksIkM+69e8McpKBEfkzhhy0s3z3117YjKxfN9AlQ7G78P/dmSN7hDWMLYDqflidF6kb8dxBcV9CTuy2l+X89dacrog6/6ixD/VFcFjwHwIDAQABMA0GCSqGSIb3DQEBCwUAA4IBAQA6k9ss6xi+RlSj6qefzTQv3hkKzHNuri1iyFyQFHMe19j5xJ0J95fm8b7LYitVZHSPLxmjtXzKcFbyf9i4H1GGf6DFekvFSbcYgv49wibMtR6bTsAjgqdfIfnkxhszOoCgqpJp0iHDnCWdIrPh9UlVDJm+Zj9rqmP8ZanUykhaM0Kkqbd608qsyFa6hQAhntF5PpSs0fZxyC4eYXyyw8fVegjM23+hKNzYdBt7atknDdRKmcPdpBWROUQNCPE75U9tKu1LPVTIdSi1gcJZUJvmS2J7WHIj8HpGmZsvQDkMmcnyI1DN9/FMEm3W5RwB3Z4ZZ5m3L1BUn1ZLNpdkgxzy</X509Certificate>
        </X509Data>
      </KeyInfo>
    </KeyDescriptor>
    <KeyDescriptor use="signing">
      <KeyInfo xmlns="http://www.w3.org/2000/09/xmldsig#">
        <X509Data>
          <X509Certificate>MIIDmzCCAoMCFBS+XL2cjCpx8brfX8VX1YMkMKkkMA0GCSqGSIb3DQEBCwUAMIGJMQswCQYDVQQGEwJDWjEOMAwGA1UECAwFUHJhaGExDjAMBgNVBAcMBVByYWhhMQ8wDQYDVQQKDAZDRVNORVQxGzAZBgNVBAsMEkluZm9ybWFjbmkgU3lzdGVteTEsMCoGA1UEAwwjQURGUyBTaWduaW5nIC0gYWRmcy53Mmxhbi5jZXNuZXQuY3owHhcNMjEwMTA3MjMwMjI3WhcNMzEwMTA3MjMwMjI3WjCBiTELMAkGA1UEBhMCQ1oxDjAMBgNVBAgMBVByYWhhMQ4wDAYDVQQHDAVQcmFoYTEPMA0GA1UECgwGQ0VTTkVUMRswGQYDVQQLDBJJbmZvcm1hY25pIFN5c3RlbXkxLDAqBgNVBAMMI0FERlMgU2lnbmluZyAtIGFkZnMudzJsYW4uY2VzbmV0LmN6MIIBIjANBgkqhkiG9w0BAQEFAAOCAQ8AMIIBCgKCAQEAydL7HyIUbuERG+o5Td1t3yStTtyiMhIDmThRK1SPXzhQOjG5rXpA4uF1ba0Ogpnq1Q6/hDB5qIQV1YVdc7UBG20oTglAVjQPPszIaDSk93/v7L7QsqptatPGVxqXdKc9Z/2Yr0KIpCfrlT7jzOvdyWGh/zEdR+rLt68kxYt9SBoh44F2TqJGXrjJC215ruU1nKU/FuXkdyC+F8U8t7GhsyKDgNjpe0hjA2p7hhF71GMq0kKQ4evVVQ7AmNaJw+4N/pQjxomZ9FVjxwF9avp9lZkSmZZOpp+BmPo2kVcYItqwJxK7htqEnTFdpo+5PutRHwl38KvCvvcWnPgrsVU2CwIDAQABMA0GCSqGSIb3DQEBCwUAA4IBAQBnHZHIVRkkWtyBLDBsw84sx9pOxn0nrC0htSlQR2meng8cUwvClJIWR09h2al8s6NW/X6veyT0v49oZg9L7NIZ0nEcX99nSDEiJmgGrNxbUuMvzlpblZXJ7FwVSWJkqmXBsHJJuv3/nun27AR+76xxeJBZI9owXi/+cDriPq0mGmnWrj6olBujMhwY+VXUK6rYPDE9sihxGvSKmLRpLOmh3piN244ZiR3IVBq0h9T1fMN/5WnMhfPPduVLQExUg8az4I0m4qNmMLved9G7uqlf2EarQnZz3YXj1cS+dm6PcFB5Rh9soM0OwZgohIZ96u/m+fz09HiibjKCbLpUkdwe</X509Certificate>
        </X509Data>
      </KeyInfo>
    </KeyDescriptor>
    <SingleLogoutService Binding="urn:oasis:names:tc:SAML:2.0:bindings:HTTP-Redirect" Location="https://adfs.w2lan.cesnet.cz/adfs/ls/"/>
    <SingleLogoutService Binding="urn:oasis:names:tc:SAML:2.0:bindings:HTTP-POST" Location="https://adfs.w2lan.cesnet.cz/adfs/ls/"/>
    <NameIDFormat>urn:oasis:names:tc:SAML:1.1:nameid-format:emailAddress</NameIDFormat>
    <NameIDFormat>urn:oasis:names:tc:SAML:2.0:nameid-format:persistent</NameIDFormat>
    <NameIDFormat>urn:oasis:names:tc:SAML:2.0:nameid-format:transient</NameIDFormat>
    <AssertionConsumerService Binding="urn:oasis:names:tc:SAML:2.0:bindings:HTTP-POST" Location="https://adfs.w2lan.cesnet.cz/adfs/ls/" index="0" isDefault="true"/>
    <AssertionConsumerService Binding="urn:oasis:names:tc:SAML:2.0:bindings:HTTP-Artifact" Location="https://adfs.w2lan.cesnet.cz/adfs/ls/" index="1"/>
    <AssertionConsumerService Binding="urn:oasis:names:tc:SAML:2.0:bindings:HTTP-Redirect" Location="https://adfs.w2lan.cesnet.cz/adfs/ls/" index="2"/>
  </SPSSODescriptor>
  <Organization>
    <OrganizationName xml:lang="en">CESNET, a. l. e.</OrganizationName>
    <OrganizationName xml:lang="cs">CESNET, z. s. p. o.</OrganizationName>
    <OrganizationDisplayName xml:lang="en">CESNET</OrganizationDisplayName>
    <OrganizationDisplayName xml:lang="cs">CESNET</OrganizationDisplayName>
    <OrganizationURL xml:lang="en">https://www.cesnet.cz/?lang=en</OrganizationURL>
    <OrganizationURL xml:lang="cs">https://www.cesnet.cz/</OrganizationURL>
  </Organization>
  <ContactPerson contactType="technical">
    <GivenName>Ji&#x159;&#xED;</GivenName>
    <SurName>Kvarda</SurName>
    <EmailAddress>mailto:jiri.kvarda@cesnet.cz</EmailAddress>
  </ContactPerson>
  <ContactPerson contactType="technical">
    <GivenName>Zbyn&#x11B;k</GivenName>
    <SurName>Brendl</SurName>
    <EmailAddress>mailto:zbynek.brendl@cesnet.cz</EmailAddress>
  </ContactPerson>
</EntityDescriptor>
';
        array_push($metadataArray, '<?xml version="1.0"?>
<EntityDescriptor xmlns="urn:oasis:names:tc:SAML:2.0:metadata" ID="_fff128b6-b099-4d95-b66c-e2e24b48dbb1" entityID="https://adfs-ext.w2lan.cesnet.cz/adfs/services/trust">
  <SPSSODescriptor WantAssertionsSigned="true" protocolSupportEnumeration="urn:oasis:names:tc:SAML:2.0:protocol">
    <Extensions>
      <mdui:UIInfo xmlns:mdui="urn:oasis:names:tc:SAML:metadata:ui">
        <mdui:DisplayName xml:lang="en">ADFS Ext</mdui:DisplayName>
        <mdui:DisplayName xml:lang="cs">ADFS Ext</mdui:DisplayName>
        <mdui:Description xml:lang="en">ADFS External (Windows domain w2lan.cesnet.cz)</mdui:Description>
        <mdui:Description xml:lang="cs">ADFS External (domena Windows w2lan.cesnet.cz)</mdui:Description>
        <mdui:InformationURL xml:lang="en">https://www.cesnet.cz/?lang=en</mdui:InformationURL>
        <mdui:InformationURL xml:lang="cs">https://www.cesnet.cz/</mdui:InformationURL>
      </mdui:UIInfo>
      <mdrpi:RegistrationInfo xmlns:mdrpi="urn:oasis:names:tc:SAML:2.0:assertion" registrationAuthority="http://www.eduid.cz/" registrationInstant="2024-06-11T10:16:21Z">
        <saml:AttributeValue xmlns:saml="urn:oasis:names:tc:SAML:2.0:assertion" xml:lang="en">http://www.eduid.cz/wiki/_media/en/eduid/policy/policy_eduid_en-1_1.pdf</saml:AttributeValue>
        <saml:AttributeValue xmlns:saml="urn:oasis:names:tc:SAML:2.0:assertion" xml:lang="cs">http://www.eduid.cz/wiki/_media/eduid/policy/policy_eduid_cz-1_1-3.pdf</saml:AttributeValue>
      </mdrpi:RegistrationInfo>
    </Extensions>
    <KeyDescriptor use="encryption">
      <KeyInfo xmlns="http://www.w3.org/2000/09/xmldsig#">
        <X509Data>
          <X509Certificate>MIIDqTCCApECFD91f+idEqPwUVXV+sYc9/OZnJNXMA0GCSqGSIb3DQEBCwUAMIGQMQswCQYDVQQGEwJDWjEOMAwGA1UECAwFUHJhaGExDjAMBgNVBAcMBVByYWhhMQ8wDQYDVQQKDAZDRVNORVQxGzAZBgNVBAsMEkluZm9ybWFjbmkgU3lzdGVteTEzMDEGA1UEAwwqQURGUyBFbmNyeXB0aW9uIC0gYWRmcy1leHQudzJsYW4uY2VzbmV0LmN6MB4XDTIxMDIwMTE4MDAxMVoXDTMxMDIwMTE4MDAxMVowgZAxCzAJBgNVBAYTAkNaMQ4wDAYDVQQIDAVQcmFoYTEOMAwGA1UEBwwFUHJhaGExDzANBgNVBAoMBkNFU05FVDEbMBkGA1UECwwSSW5mb3JtYWNuaSBTeXN0ZW15MTMwMQYDVQQDDCpBREZTIEVuY3J5cHRpb24gLSBhZGZzLWV4dC53Mmxhbi5jZXNuZXQuY3owggEiMA0GCSqGSIb3DQEBAQUAA4IBDwAwggEKAoIBAQC12jFAXbP7RrLPAdApE2jrMjHuoTjkx1IjQJCAM3rTweQI1h6UNhiF0o45E3u0XCX2mJpRJDZVKISjzuUy9MvtLuHI+A50VKrk11FqdYjNFlIS4SWx6mHy/8kCHfy4cWgPsy22J2zx5tElJl191pS/AqASzbsACGWQaJ0UvoqwWAxb9PglYI4OLSG4vhp+s8lJFG3GdOz/5jhmk4xsUidI5sw9TyNLY+wPHWfKRNhtQam9oY7HmNMH7RF7AzSjq0wj2a7JHcmqxl9TAXdT9QBfZwQYAbyBhGZAovIrG7eDMG8y2uN3EcmgSt60nnujlFe03FsyI20uXH1HYTkLUsFzAgMBAAEwDQYJKoZIhvcNAQELBQADggEBAElRz1b8M9NANKq9IsnsabDPilNwF3Z1glJ+nOOLJvziXB7Qqa332QGp65RdjjoV0bpOpxnEXsHjGyTwYLVdOTtbwNcjHUD8yq4xFqzu8U6UUYHQtT+WD7mJ6CRd4sF+FcwaQGUVWlDncWwjY27muezzwS9dYOyhptQCj9xtz7sMn/yxijjpuw6/8b/ahOOIEcKga3sE3fllQJIALd31Cgf95PE2kxuum6CKkDT5ediSNUD5yPmDkQddXASuSL1d1ArIX364Gs38slDabCE+NckiBNnS5TVXkUjSsoTnbbLwDZ+upfHYRHu/sGxgFrWkblxbmv7qARmkXU6Rwjh3cOo=</X509Certificate>
        </X509Data>
      </KeyInfo>
    </KeyDescriptor>
    <KeyDescriptor use="signing">
      <KeyInfo xmlns="http://www.w3.org/2000/09/xmldsig#">
        <X509Data>
          <X509Certificate>MIIDozCCAosCFDJ4OjmaAHJRj5T8SkiJMoUUEmxMMA0GCSqGSIb3DQEBCwUAMIGNMQswCQYDVQQGEwJDWjEOMAwGA1UECAwFUHJhaGExDjAMBgNVBAcMBVByYWhhMQ8wDQYDVQQKDAZDRVNORVQxGzAZBgNVBAsMEkluZm9ybWFjbmkgU3lzdGVteTEwMC4GA1UEAwwnQURGUyBTaWduaW5nIC0gYWRmcy1leHQudzJsYW4uY2VzbmV0LmN6MB4XDTIxMDIwMTE3MzcxNVoXDTMxMDIwMTE3MzcxNVowgY0xCzAJBgNVBAYTAkNaMQ4wDAYDVQQIDAVQcmFoYTEOMAwGA1UEBwwFUHJhaGExDzANBgNVBAoMBkNFU05FVDEbMBkGA1UECwwSSW5mb3JtYWNuaSBTeXN0ZW15MTAwLgYDVQQDDCdBREZTIFNpZ25pbmcgLSBhZGZzLWV4dC53Mmxhbi5jZXNuZXQuY3owggEiMA0GCSqGSIb3DQEBAQUAA4IBDwAwggEKAoIBAQCdVOGWRfWBhXQLRV1yyqNjcmQyXvipobvu2ylT2h+Ug08lRXecNFN2hxJi/aHc+kJlkNyR8WmL3Cw76hrpgz8/QcFA+oTl8VSvDyHAwVydwAaWELlUi08ki3EKEyZ0CL8R/8e5Yf2DuSv8fgPSCFe13Ftb3Bfi85xqinijOG0bCjpzbh4kjPdqQCxOI4c7d9VcXyvH3iAxCDeh1QpSyF3MtHCBLs674WC1sEsmUWhbTsoeGixB6MPKRBDotuqaSW0g5X5HFxgAdYnpO3k814DrpD5u0lSpAcdfVtPXrJfVX6FqnQkm9yeXmzFrlD5bBI9jEzPY036vq8Di2A5oQei/AgMBAAEwDQYJKoZIhvcNAQELBQADggEBAI0Yf7AMrz2BmNh86wUdgPhitp1jeStLueteG8P48rfwgOBLhVkdB9O1jbLOWVskZGH/FbiUIxfidp6Gnd9fhOiR5XA7GnJQebcIGLu8NBqd/47QntgYpimFp3Ou4EOY34RzpWbWqj9klr+ll00ESPdgMQGdoFhbCqVYwwSfVCb3LO9gDd9Vl3eJRxyT0iBrdXwQgwnUwgd5hQ2W3hxOWGMxC+LEheDWD1Y7zEch3jLV3qzWWi34VVEKT8ozB3Kx2oZPNy1HN6vGGHEfbvEr+DLwSItaKpG4Z7JP7Y9xkSy5I2mI5NX2d0IGhpBsGDhYHAeYGgkCNhY73NH/IxCGTfM=</X509Certificate>
        </X509Data>
      </KeyInfo>
    </KeyDescriptor>
    <SingleLogoutService Binding="urn:oasis:names:tc:SAML:2.0:bindings:HTTP-Redirect" Location="https://adfs-ext.w2lan.cesnet.cz/adfs/ls/"/>
    <SingleLogoutService Binding="urn:oasis:names:tc:SAML:2.0:bindings:HTTP-POST" Location="https://adfs-ext.w2lan.cesnet.cz/adfs/ls/"/>
    <NameIDFormat>urn:oasis:names:tc:SAML:1.1:nameid-format:emailAddress</NameIDFormat>
    <NameIDFormat>urn:oasis:names:tc:SAML:2.0:nameid-format:persistent</NameIDFormat>
    <NameIDFormat>urn:oasis:names:tc:SAML:2.0:nameid-format:transient</NameIDFormat>
    <AssertionConsumerService Binding="urn:oasis:names:tc:SAML:2.0:bindings:HTTP-POST" Location="https://adfs-ext.w2lan.cesnet.cz/adfs/ls/" index="0" isDefault="true"/>
    <AssertionConsumerService Binding="urn:oasis:names:tc:SAML:2.0:bindings:HTTP-Artifact" Location="https://adfs-ext.w2lan.cesnet.cz/adfs/ls/" index="1"/>
    <AssertionConsumerService Binding="urn:oasis:names:tc:SAML:2.0:bindings:HTTP-Redirect" Location="https://adfs-ext.w2lan.cesnet.cz/adfs/ls/" index="2"/>
  </SPSSODescriptor>
  <Organization>
    <OrganizationName xml:lang="en">CESNET, a. l. e.</OrganizationName>
    <OrganizationName xml:lang="cs">CESNET, z. s. p. o.</OrganizationName>
    <OrganizationDisplayName xml:lang="en">CESNET</OrganizationDisplayName>
    <OrganizationDisplayName xml:lang="cs">CESNET</OrganizationDisplayName>
    <OrganizationURL xml:lang="en">https://www.cesnet.cz/?lang=en</OrganizationURL>
    <OrganizationURL xml:lang="cs">https://www.cesnet.cz/</OrganizationURL>
  </Organization>
  <ContactPerson contactType="technical">
    <GivenName>Ji&#x159;&#xED;</GivenName>
    <SurName>Kvarda</SurName>
    <EmailAddress>mailto:jiri.kvarda@cesnet.cz</EmailAddress>
  </ContactPerson>
  <ContactPerson contactType="technical">
    <GivenName>Zbyn&#x11B;k</GivenName>
    <SurName>Brendl</SurName>
    <EmailAddress>mailto:zbynek.brendl@cesnet.cz</EmailAddress>
  </ContactPerson>
</EntityDescriptor>');
        array_push($metadataArray, '<?xml version="1.0"?>
<md:EntityDescriptor xmlns:md="urn:oasis:names:tc:SAML:2.0:metadata" entityID="https://cesnetdev.westeurope.cloudapp.azure.com/shibboleth/cesnet-internal/sp">
  <md:Extensions xmlns:alg="urn:oasis:names:tc:SAML:metadata:algsupport">
    <alg:DigestMethod Algorithm="http://www.w3.org/2001/04/xmlenc#sha512"/>
    <alg:DigestMethod Algorithm="http://www.w3.org/2001/04/xmldsig-more#sha384"/>
    <alg:DigestMethod Algorithm="http://www.w3.org/2001/04/xmlenc#sha256"/>
    <alg:DigestMethod Algorithm="http://www.w3.org/2001/04/xmldsig-more#sha224"/>
    <alg:DigestMethod Algorithm="http://www.w3.org/2000/09/xmldsig#sha1"/>
    <alg:SigningMethod Algorithm="http://www.w3.org/2001/04/xmldsig-more#ecdsa-sha512"/>
    <alg:SigningMethod Algorithm="http://www.w3.org/2001/04/xmldsig-more#ecdsa-sha384"/>
    <alg:SigningMethod Algorithm="http://www.w3.org/2001/04/xmldsig-more#ecdsa-sha256"/>
    <alg:SigningMethod Algorithm="http://www.w3.org/2001/04/xmldsig-more#ecdsa-sha224"/>
    <alg:SigningMethod Algorithm="http://www.w3.org/2001/04/xmldsig-more#rsa-sha512"/>
    <alg:SigningMethod Algorithm="http://www.w3.org/2001/04/xmldsig-more#rsa-sha384"/>
    <alg:SigningMethod Algorithm="http://www.w3.org/2001/04/xmldsig-more#rsa-sha256"/>
    <alg:SigningMethod Algorithm="http://www.w3.org/2009/xmldsig11#dsa-sha256"/>
    <alg:SigningMethod Algorithm="http://www.w3.org/2001/04/xmldsig-more#ecdsa-sha1"/>
    <alg:SigningMethod Algorithm="http://www.w3.org/2000/09/xmldsig#rsa-sha1"/>
    <alg:SigningMethod Algorithm="http://www.w3.org/2000/09/xmldsig#dsa-sha1"/>
    <mdrpi:RegistrationInfo xmlns:mdrpi="urn:oasis:names:tc:SAML:2.0:assertion" registrationAuthority="http://www.eduid.cz/" registrationInstant="2024-08-19T07:55:12Z">
      <saml:AttributeValue xmlns:saml="urn:oasis:names:tc:SAML:2.0:assertion" xml:lang="en">http://www.eduid.cz/wiki/_media/en/eduid/policy/policy_eduid_en-1_1.pdf</saml:AttributeValue>
      <saml:AttributeValue xmlns:saml="urn:oasis:names:tc:SAML:2.0:assertion" xml:lang="cs">http://www.eduid.cz/wiki/_media/eduid/policy/policy_eduid_cz-1_1-3.pdf</saml:AttributeValue>
    </mdrpi:RegistrationInfo>
  </md:Extensions>
  <md:SPSSODescriptor protocolSupportEnumeration="urn:oasis:names:tc:SAML:2.0:protocol">
    <md:Extensions>
      <mdui:UIInfo xmlns:mdui="urn:oasis:names:tc:SAML:metadata:ui">
        <mdui:DisplayName xml:lang="en">KOMORA dev</mdui:DisplayName>
        <mdui:DisplayName xml:lang="cs">KOMORA dev</mdui:DisplayName>
        <mdui:Description xml:lang="en">KOMORA development</mdui:Description>
        <mdui:Description xml:lang="cs">KOMOTA vyvoj</mdui:Description>
        <mdui:InformationURL xml:lang="en">https://www.cesnet.cz/?lang=en</mdui:InformationURL>
        <mdui:InformationURL xml:lang="cs">https://www.cesnet.cz/</mdui:InformationURL>
      </mdui:UIInfo>
      <init:RequestInitiator xmlns:init="urn:oasis:names:tc:SAML:profiles:SSO:request-init" Binding="urn:oasis:names:tc:SAML:profiles:SSO:request-init" Location="https://cesnetdev.westeurope.cloudapp.azure.com/Shibboleth.sso/Login"/>
    </md:Extensions>
    <md:KeyDescriptor>
      <ds:KeyInfo xmlns:ds="http://www.w3.org/2000/09/xmldsig#">
        <ds:KeyName>k2dev</ds:KeyName>
        <ds:X509Data>
          <ds:X509SubjectName>CN=k2dev</ds:X509SubjectName>
          <ds:X509Certificate>MIIEBTCCAu0CFFfLwyzYVB2UBMSxM9xgNsGgyjU6MA0GCSqGSIb3DQEBCwUAMIG+
MQswCQYDVQQGEwJDWjEOMAwGA1UECAwFUHJhaGExDjAMBgNVBAcMBVByYWhhMQ8w
DQYDVQQKDAZDRVNORVQxGzAZBgNVBAsMEkluZm9ybWFjbmkgU3lzdGVteTEwMC4G
A1UEAwwnY2VzbmV0ZGV2Lndlc3RldXJvcGUuY2xvdWRhcHAuYXp1cmUuY29tMS8w
LQYJKoZIhvcNAQkBFiBqaXJpIGRvdCBrdmFyZGEgYXQgY2VzbmV0IGRvdCBjejAe
Fw0yMTA1MjAxMTAxMjdaFw0zMTA1MjAxMTAxMjdaMIG+MQswCQYDVQQGEwJDWjEO
MAwGA1UECAwFUHJhaGExDjAMBgNVBAcMBVByYWhhMQ8wDQYDVQQKDAZDRVNORVQx
GzAZBgNVBAsMEkluZm9ybWFjbmkgU3lzdGVteTEwMC4GA1UEAwwnY2VzbmV0ZGV2
Lndlc3RldXJvcGUuY2xvdWRhcHAuYXp1cmUuY29tMS8wLQYJKoZIhvcNAQkBFiBq
aXJpIGRvdCBrdmFyZGEgYXQgY2VzbmV0IGRvdCBjejCCASIwDQYJKoZIhvcNAQEB
BQADggEPADCCAQoCggEBANkZ0wCVULkDITICpjcLSuYiAycF9rabe5XWNP+TtUwE
MIyR4e22wb0rUPAHOrsA200a6lQwxMRkSytpd6oRCHhQUXxwX3qx2xMwGu1dyoZf
ajMsyd/AguOYI66ZJiJpPXL/4DuJNSANXFLpKrPNat6MOVjRBODfJ1Pc8Kv4y20Q
GT5fRTk1LMmc2hnckL4dwBSM0GWEIEPWaoNuryk6uwlPpdVXPXgtT8tRgtR3oD+Z
ZRZk6ZJBspDOFgI/k9xOYvFAjNcuPILCn0B0wVlDgC34pyjbdqtm2kk66ZoUloPu
jVrdc8o0jf/4JHs7pOby2sX9NYX7jicKAV+/Ia0swlECAwEAATANBgkqhkiG9w0B
AQsFAAOCAQEAqMeXAYiVZh7Y9O2ciMv4MWxEPlIgwAcStMox3+w20ZK8G5QZmCuU
7akhjoZWBl2ZuSH/CBKmeSe851v4Lb2oWA5zw4Qyzh8gRz7orhILndJeXGPXIS/2
aHegV0LN+DEcnoSmJqQnl9q0YM8KkYsm6p7H0ybDuAWKTwql8H2MN/ErQC0UkPEO
DczTZWtF0u0ztE4ph/5JyLBTwuNVxMRUn1iJcCN49Bp8c34dVuBlnd7Sj9PnfeFR
QtYMoVbDu0fE0JcZ83Mixlj41I3SI7mu6FDtfoevtZAmoKsa/fWmnHJu+z8bBtxi
/SyMgRzPXmhghs97otx9ANzhfRNd+tcZaQ==</ds:X509Certificate>
        </ds:X509Data>
      </ds:KeyInfo>
      <md:EncryptionMethod Algorithm="http://www.w3.org/2009/xmlenc11#aes128-gcm"/>
      <md:EncryptionMethod Algorithm="http://www.w3.org/2009/xmlenc11#aes192-gcm"/>
      <md:EncryptionMethod Algorithm="http://www.w3.org/2009/xmlenc11#aes256-gcm"/>
      <md:EncryptionMethod Algorithm="http://www.w3.org/2001/04/xmlenc#aes128-cbc"/>
      <md:EncryptionMethod Algorithm="http://www.w3.org/2001/04/xmlenc#aes192-cbc"/>
      <md:EncryptionMethod Algorithm="http://www.w3.org/2001/04/xmlenc#aes256-cbc"/>
      <md:EncryptionMethod Algorithm="http://www.w3.org/2001/04/xmlenc#tripledes-cbc"/>
      <md:EncryptionMethod Algorithm="http://www.w3.org/2009/xmlenc11#rsa-oaep"/>
      <md:EncryptionMethod Algorithm="http://www.w3.org/2001/04/xmlenc#rsa-oaep-mgf1p"/>
    </md:KeyDescriptor>
    <md:ArtifactResolutionService Binding="urn:oasis:names:tc:SAML:2.0:bindings:SOAP" Location="https://cesnetdev.westeurope.cloudapp.azure.com/Shibboleth.sso/Artifact/SOAP" index="1"/>
    <md:SingleLogoutService Binding="urn:oasis:names:tc:SAML:2.0:bindings:SOAP" Location="https://cesnetdev.westeurope.cloudapp.azure.com/Shibboleth.sso/SLO/SOAP"/>
    <md:SingleLogoutService Binding="urn:oasis:names:tc:SAML:2.0:bindings:HTTP-Redirect" Location="https://cesnetdev.westeurope.cloudapp.azure.com/Shibboleth.sso/SLO/Redirect"/>
    <md:SingleLogoutService Binding="urn:oasis:names:tc:SAML:2.0:bindings:HTTP-POST" Location="https://cesnetdev.westeurope.cloudapp.azure.com/Shibboleth.sso/SLO/POST"/>
    <md:SingleLogoutService Binding="urn:oasis:names:tc:SAML:2.0:bindings:HTTP-Artifact" Location="https://cesnetdev.westeurope.cloudapp.azure.com/Shibboleth.sso/SLO/Artifact"/>
    <md:AssertionConsumerService Binding="urn:oasis:names:tc:SAML:2.0:bindings:HTTP-POST" Location="https://cesnetdev.westeurope.cloudapp.azure.com/Shibboleth.sso/SAML2/POST" index="1"/>
    <md:AssertionConsumerService Binding="urn:oasis:names:tc:SAML:2.0:bindings:HTTP-POST-SimpleSign" Location="https://cesnetdev.westeurope.cloudapp.azure.com/Shibboleth.sso/SAML2/POST-SimpleSign" index="2"/>
    <md:AssertionConsumerService Binding="urn:oasis:names:tc:SAML:2.0:bindings:HTTP-Artifact" Location="https://cesnetdev.westeurope.cloudapp.azure.com/Shibboleth.sso/SAML2/Artifact" index="3"/>
    <md:AssertionConsumerService Binding="urn:oasis:names:tc:SAML:2.0:bindings:PAOS" Location="https://cesnetdev.westeurope.cloudapp.azure.com/Shibboleth.sso/SAML2/ECP" index="4"/>
  </md:SPSSODescriptor>
  <md:Organization>
    <md:OrganizationName xml:lang="en">CESNET, a.l.e.</md:OrganizationName>
    <md:OrganizationName xml:lang="cs">CESNET, z.s.p.o.</md:OrganizationName>
    <md:OrganizationDisplayName xml:lang="en">CESNET</md:OrganizationDisplayName>
    <md:OrganizationDisplayName xml:lang="cs">CESNET</md:OrganizationDisplayName>
    <md:OrganizationURL xml:lang="en">https://www.cesnet.cz/?lang=en</md:OrganizationURL>
    <md:OrganizationURL xml:lang="cs">https://www.cesnet.cz/</md:OrganizationURL>
  </md:Organization>
  <md:ContactPerson contactType="technical">
    <md:GivenName>Jiri</md:GivenName>
    <md:SurName>Kvarda</md:SurName>
    <md:EmailAddress>mailto:jiri.kvarda@cesnet.cz</md:EmailAddress>
  </md:ContactPerson>
</md:EntityDescriptor>');

        array_push($metadataArray, '<?xml version="1.0" encoding="utf-8"?>
<md:EntityDescriptor xmlns:md="urn:oasis:names:tc:SAML:2.0:metadata" xmlns:mdui="urn:oasis:names:tc:SAML:metadata:ui" entityID="https://clserver.cesnet.cz/sp/shibboleth">
  <md:SPSSODescriptor protocolSupportEnumeration="urn:oasis:names:tc:SAML:2.0:protocol urn:oasis:names:tc:SAML:1.1:protocol urn:oasis:names:tc:SAML:1.0:protocol">
    <md:Extensions>
      <mdui:UIInfo>
        <mdui:DisplayName xml:lang="en">CL server</mdui:DisplayName>
        <mdui:DisplayName xml:lang="cs">CL server</mdui:DisplayName>
        <mdui:Description xml:lang="en">Czech Light server</mdui:Description>
        <mdui:Description xml:lang="cs">Czech Light server</mdui:Description>
        <mdui:InformationURL xml:lang="en">https://clserver.cesnet.cz</mdui:InformationURL>
        <mdui:InformationURL xml:lang="cs">https://clserver.cesnet.cz</mdui:InformationURL>
      </mdui:UIInfo>
      <DiscoveryResponse xmlns="urn:oasis:names:tc:SAML:profiles:SSO:idp-discovery-protocol" Binding="urn:oasis:names:tc:SAML:profiles:SSO:idp-discovery-protocol" Location="https://clserver.cesnet.cz/Shibboleth.sso/DS" index="1"/>
      <mdrpi:RegistrationInfo xmlns:mdrpi="urn:oasis:names:tc:SAML:2.0:assertion" registrationAuthority="http://www.eduid.cz/" registrationInstant="2011-03-09T10:40:22Z">
        <saml:AttributeValue xmlns:saml="urn:oasis:names:tc:SAML:2.0:assertion" xml:lang="en">http://www.eduid.cz/wiki/_media/en/eduid/policy/policy_eduid_en-1_1.pdf</saml:AttributeValue>
        <saml:AttributeValue xmlns:saml="urn:oasis:names:tc:SAML:2.0:assertion" xml:lang="cs">http://www.eduid.cz/wiki/_media/eduid/policy/policy_eduid_cz-1_1-3.pdf</saml:AttributeValue>
      </mdrpi:RegistrationInfo>
    </md:Extensions>
    <md:KeyDescriptor use="signing">
      <ds:KeyInfo xmlns:ds="http://www.w3.org/2000/09/xmldsig#">
        <ds:KeyName>clserver</ds:KeyName>
        <ds:X509Data>
          <ds:X509SubjectName>CN=clserver</ds:X509SubjectName>
          <ds:X509Certificate>MIIC3zCCAcegAwIBAgIJAI9gO5MMJcfqMA0GCSqGSIb3DQEBBQUAMBMxETAPBgNV
BAMTCGNsc2VydmVyMB4XDTA5MDUwNTEyMjY1MVoXDTE5MDUwMzEyMjY1MVowEzER
MA8GA1UEAxMIY2xzZXJ2ZXIwggEiMA0GCSqGSIb3DQEBAQUAA4IBDwAwggEKAoIB
AQDpyVhI7eHvzu5loncBnrDFN5MmFbEgKJ4aQvqRRzTOcpBg1TQgOMeHzfA2QT3q
Dp2tslE2brQBawO6gdahTnRWYA6ykZhqi5jDYmYYZIWhN+uZeTbGDnbEN9KWDebp
qLv4zQ9z2ppPwgq/BxmyBoXWo3bk8OtbIFQjr5Dk2mOiODe5GVzCcBIrfLy+fq5k
ZWgxbXljOXvGAtcXUVTJIjCfDU2odcmeU5kciJVggp/TLti9SPwj1r+zb1rLklul
faPBUXYf+9ucQ/bIaJo9E83W0rabhjzb3NmxTEDTk1Mkko4Sey/znG92HfUFxHo5
7BvP5/vgEEODhnpuFvZ91y6zAgMBAAGjNjA0MBMGA1UdEQQMMAqCCGNsc2VydmVy
MB0GA1UdDgQWBBQJx0i0F+8tUJBdGIplAJSid4OrmzANBgkqhkiG9w0BAQUFAAOC
AQEAcytRkAdmXRFsnrlYiV8KMKthfhI31j09qF9+M+KafCRD+4kU9fL2K4q3U17J
l/GOiSVvVYxuEE6qn9rS9lPeBdyS+TmSktF3ZA9c3ojO/cKJkgUH7qyDMtalaV0h
qpxvtMLbScFWyd6gJWVzPKITQeoC32nHCpE+2QviITeKP7OCTIpvh1ZGSfS0qs33
N7fyT8qj9c4boi/x+OVXpGBuRDIJJRfHvQjPh0ifw6Xgou2+yEV2IQW2jqX5jUTx
asp9mWqogBAT/a/9q19FpoHSde+PwftbVue5KOmkLNZ/nz+1fecGznojJmeT1avB
R6FNakgblF17O54tw1tbA+DJPg==
</ds:X509Certificate>
        </ds:X509Data>
      </ds:KeyInfo>
    </md:KeyDescriptor>
    <md:KeyDescriptor use="encryption">
      <ds:KeyInfo xmlns:ds="http://www.w3.org/2000/09/xmldsig#">
        <ds:KeyName>clserver</ds:KeyName>
        <ds:X509Data>
          <ds:X509SubjectName>CN=clserver</ds:X509SubjectName>
          <ds:X509Certificate>MIIC3zCCAcegAwIBAgIJAI9gO5MMJcfqMA0GCSqGSIb3DQEBBQUAMBMxETAPBgNV
BAMTCGNsc2VydmVyMB4XDTA5MDUwNTEyMjY1MVoXDTE5MDUwMzEyMjY1MVowEzER
MA8GA1UEAxMIY2xzZXJ2ZXIwggEiMA0GCSqGSIb3DQEBAQUAA4IBDwAwggEKAoIB
AQDpyVhI7eHvzu5loncBnrDFN5MmFbEgKJ4aQvqRRzTOcpBg1TQgOMeHzfA2QT3q
Dp2tslE2brQBawO6gdahTnRWYA6ykZhqi5jDYmYYZIWhN+uZeTbGDnbEN9KWDebp
qLv4zQ9z2ppPwgq/BxmyBoXWo3bk8OtbIFQjr5Dk2mOiODe5GVzCcBIrfLy+fq5k
ZWgxbXljOXvGAtcXUVTJIjCfDU2odcmeU5kciJVggp/TLti9SPwj1r+zb1rLklul
faPBUXYf+9ucQ/bIaJo9E83W0rabhjzb3NmxTEDTk1Mkko4Sey/znG92HfUFxHo5
7BvP5/vgEEODhnpuFvZ91y6zAgMBAAGjNjA0MBMGA1UdEQQMMAqCCGNsc2VydmVy
MB0GA1UdDgQWBBQJx0i0F+8tUJBdGIplAJSid4OrmzANBgkqhkiG9w0BAQUFAAOC
AQEAcytRkAdmXRFsnrlYiV8KMKthfhI31j09qF9+M+KafCRD+4kU9fL2K4q3U17J
l/GOiSVvVYxuEE6qn9rS9lPeBdyS+TmSktF3ZA9c3ojO/cKJkgUH7qyDMtalaV0h
qpxvtMLbScFWyd6gJWVzPKITQeoC32nHCpE+2QviITeKP7OCTIpvh1ZGSfS0qs33
N7fyT8qj9c4boi/x+OVXpGBuRDIJJRfHvQjPh0ifw6Xgou2+yEV2IQW2jqX5jUTx
asp9mWqogBAT/a/9q19FpoHSde+PwftbVue5KOmkLNZ/nz+1fecGznojJmeT1avB
R6FNakgblF17O54tw1tbA+DJPg==
</ds:X509Certificate>
        </ds:X509Data>
      </ds:KeyInfo>
    </md:KeyDescriptor>
    <md:SingleLogoutService Binding="urn:oasis:names:tc:SAML:2.0:bindings:SOAP" Location="https://clserver.cesnet.cz/Shibboleth.sso/SLO/SOAP"/>
    <md:SingleLogoutService Binding="urn:oasis:names:tc:SAML:2.0:bindings:HTTP-Redirect" Location="https://clserver.cesnet.cz/Shibboleth.sso/SLO/Redirect"/>
    <md:SingleLogoutService Binding="urn:oasis:names:tc:SAML:2.0:bindings:HTTP-POST" Location="https://clserver.cesnet.cz/Shibboleth.sso/SLO/POST"/>
    <md:SingleLogoutService Binding="urn:oasis:names:tc:SAML:2.0:bindings:HTTP-Artifact" Location="https://clserver.cesnet.cz/Shibboleth.sso/SLO/Artifact"/>
    <md:ManageNameIDService Binding="urn:oasis:names:tc:SAML:2.0:bindings:SOAP" Location="https://clserver.cesnet.cz/Shibboleth.sso/NIM/SOAP"/>
    <md:ManageNameIDService Binding="urn:oasis:names:tc:SAML:2.0:bindings:HTTP-Redirect" Location="https://clserver.cesnet.cz/Shibboleth.sso/NIM/Redirect"/>
    <md:ManageNameIDService Binding="urn:oasis:names:tc:SAML:2.0:bindings:HTTP-POST" Location="https://clserver.cesnet.cz/Shibboleth.sso/NIM/POST"/>
    <md:ManageNameIDService Binding="urn:oasis:names:tc:SAML:2.0:bindings:HTTP-Artifact" Location="https://clserver.cesnet.cz/Shibboleth.sso/NIM/Artifact"/>
    <md:AssertionConsumerService Binding="urn:oasis:names:tc:SAML:2.0:bindings:HTTP-POST" Location="https://clserver.cesnet.cz/Shibboleth.sso/SAML2/POST" index="1"/>
    <md:AssertionConsumerService Binding="urn:oasis:names:tc:SAML:2.0:bindings:HTTP-POST-SimpleSign" Location="https://clserver.cesnet.cz/Shibboleth.sso/SAML2/POST-SimpleSign" index="2"/>
    <md:AssertionConsumerService Binding="urn:oasis:names:tc:SAML:2.0:bindings:HTTP-Artifact" Location="https://clserver.cesnet.cz/Shibboleth.sso/SAML2/Artifact" index="3"/>
    <md:AssertionConsumerService Binding="urn:oasis:names:tc:SAML:2.0:bindings:PAOS" Location="https://clserver.cesnet.cz/Shibboleth.sso/SAML2/ECP" index="4"/>
    <md:AssertionConsumerService Binding="urn:oasis:names:tc:SAML:1.0:profiles:browser-post" Location="https://clserver.cesnet.cz/Shibboleth.sso/SAML/POST" index="5"/>
    <md:AssertionConsumerService Binding="urn:oasis:names:tc:SAML:1.0:profiles:artifact-01" Location="https://clserver.cesnet.cz/Shibboleth.sso/SAML/Artifact" index="6"/>
  </md:SPSSODescriptor>
  <md:Organization>
    <md:OrganizationName xml:lang="en">CESNET, a. l. e.</md:OrganizationName>
    <md:OrganizationName xml:lang="cs">CESNET, z. s. p. o.</md:OrganizationName>
    <md:OrganizationDisplayName xml:lang="en">CESNET</md:OrganizationDisplayName>
    <md:OrganizationDisplayName xml:lang="cs">CESNET</md:OrganizationDisplayName>
    <md:OrganizationURL xml:lang="en">https://www.ces.net/</md:OrganizationURL>
    <md:OrganizationURL xml:lang="cs">https://www.cesnet.cz/</md:OrganizationURL>
  </md:Organization>
  <md:ContactPerson contactType="technical">
    <md:GivenName>Miroslavi</md:GivenName>
    <md:SurName>Hula</md:SurName>
    <md:EmailAddress>mailto:m.hula@cesnet.cz</md:EmailAddress>
  </md:ContactPerson>
</md:EntityDescriptor>');
        array_push($metadataArray, '<?xml version="1.0"?>
<!--
This is example metadata only. Do *NOT* supply it as is without review,
and do *NOT* provide it in real time to your partners.
 -->
<md:EntityDescriptor xmlns:md="urn:oasis:names:tc:SAML:2.0:metadata" ID="_87cc1a5513bcb6336d67c6e2718f999c169bafe5" entityID="https://clserver2.cesnet.cz/shibboleth">
  <md:Extensions xmlns:alg="urn:oasis:names:tc:SAML:metadata:algsupport">
    <alg:DigestMethod Algorithm="http://www.w3.org/2001/04/xmlenc#sha512"/>
    <alg:DigestMethod Algorithm="http://www.w3.org/2001/04/xmldsig-more#sha384"/>
    <alg:DigestMethod Algorithm="http://www.w3.org/2001/04/xmlenc#sha256"/>
    <alg:DigestMethod Algorithm="http://www.w3.org/2001/04/xmldsig-more#sha224"/>
    <alg:DigestMethod Algorithm="http://www.w3.org/2000/09/xmldsig#sha1"/>
    <alg:SigningMethod Algorithm="http://www.w3.org/2001/04/xmldsig-more#ecdsa-sha512"/>
    <alg:SigningMethod Algorithm="http://www.w3.org/2001/04/xmldsig-more#ecdsa-sha384"/>
    <alg:SigningMethod Algorithm="http://www.w3.org/2001/04/xmldsig-more#ecdsa-sha256"/>
    <alg:SigningMethod Algorithm="http://www.w3.org/2001/04/xmldsig-more#ecdsa-sha224"/>
    <alg:SigningMethod Algorithm="http://www.w3.org/2001/04/xmldsig-more#rsa-sha512"/>
    <alg:SigningMethod Algorithm="http://www.w3.org/2001/04/xmldsig-more#rsa-sha384"/>
    <alg:SigningMethod Algorithm="http://www.w3.org/2001/04/xmldsig-more#rsa-sha256"/>
    <alg:SigningMethod Algorithm="http://www.w3.org/2009/xmldsig11#dsa-sha256"/>
    <alg:SigningMethod Algorithm="http://www.w3.org/2001/04/xmldsig-more#ecdsa-sha1"/>
    <alg:SigningMethod Algorithm="http://www.w3.org/2000/09/xmldsig#rsa-sha1"/>
    <alg:SigningMethod Algorithm="http://www.w3.org/2000/09/xmldsig#dsa-sha1"/>
    <mdrpi:RegistrationInfo xmlns:mdrpi="urn:oasis:names:tc:SAML:2.0:assertion" registrationAuthority="http://www.eduid.cz/" registrationInstant="2024-08-19T07:55:12Z">
      <saml:AttributeValue xmlns:saml="urn:oasis:names:tc:SAML:2.0:assertion" xml:lang="en">http://www.eduid.cz/wiki/_media/en/eduid/policy/policy_eduid_en-1_1.pdf</saml:AttributeValue>
      <saml:AttributeValue xmlns:saml="urn:oasis:names:tc:SAML:2.0:assertion" xml:lang="cs">http://www.eduid.cz/wiki/_media/eduid/policy/policy_eduid_cz-1_1-3.pdf</saml:AttributeValue>
    </mdrpi:RegistrationInfo>
  </md:Extensions>
  <md:SPSSODescriptor protocolSupportEnumeration="urn:oasis:names:tc:SAML:2.0:protocol urn:oasis:names:tc:SAML:1.1:protocol urn:oasis:names:tc:SAML:1.0:protocol">
    <md:Extensions>
      <mdui:UIInfo xmlns:mdui="urn:oasis:names:tc:SAML:metadata:ui">
        <mdui:DisplayName xml:lang="cs">Clserver2</mdui:DisplayName>
        <mdui:DisplayName xml:lang="en">Clserver2</mdui:DisplayName>
        <mdui:Description xml:lang="cs">CzechLight server pro evidenci a monitoring.</mdui:Description>
        <mdui:Description xml:lang="en">CzechLight monitoring and evidence server.</mdui:Description>
        <mdui:InformationURL xml:lang="cs">https://clserver2.cesnet.cz/</mdui:InformationURL>
        <mdui:InformationURL xml:lang="en">https://clserver2.cesnet.cz/</mdui:InformationURL>
      </mdui:UIInfo>
      <init:RequestInitiator xmlns:init="urn:oasis:names:tc:SAML:profiles:SSO:request-init" Binding="urn:oasis:names:tc:SAML:profiles:SSO:request-init" Location="https://clserver2.cesnet.cz/Shibboleth.sso/Login"/>
      <idpdisc:DiscoveryResponse xmlns:idpdisc="urn:oasis:names:tc:SAML:profiles:SSO:idp-discovery-protocol" Binding="urn:oasis:names:tc:SAML:profiles:SSO:idp-discovery-protocol" Location="https://clserver2.cesnet.cz/Shibboleth.sso/Login" index="1"/>
    </md:Extensions>
    <md:KeyDescriptor use="signing">
      <ds:KeyInfo xmlns:ds="http://www.w3.org/2000/09/xmldsig#">
        <ds:KeyName>clserver2.cesnet.cz</ds:KeyName>
        <ds:KeyName>https://clserver2.cesnet.cz/shibboleth</ds:KeyName>
        <ds:X509Data>
          <ds:X509SubjectName>CN=clserver2.cesnet.cz</ds:X509SubjectName>
          <ds:X509Certificate>MIIENTCCAp2gAwIBAgIUeyKuEbijM/n0lA/ccgmxuoZ2MvUwDQYJKoZIhvcNAQEL
BQAwHjEcMBoGA1UEAxMTY2xzZXJ2ZXIyLmNlc25ldC5jejAgFw0yMjEyMjIxMjUy
NDdaGA8yMDUyMTIxNDEyNTI0N1owHjEcMBoGA1UEAxMTY2xzZXJ2ZXIyLmNlc25l
dC5jejCCAaIwDQYJKoZIhvcNAQEBBQADggGPADCCAYoCggGBANOOLsy/pKYoDJPv
VXFjRnF1qFZZ7CLaM8pOPgCuu8oyPLKzXxojgysq41Inile/BTgVZ8H+3bJW8C+O
QnM1/lox2aVI/AeRc/4FQXglbwo5Jjf6nzcYrlXaBg66CH2lpxAs/2M80Izc4EK5
QVZqMxBPrhxRyAxlT+XA4ZPv2l9+7rc3NOvDNC7aW6wkVSGViiJLHVM2panIrK83
9bP/93MKUlFMW/90fMebTmB7KiVIlo2A+dUgSvsarF84ZeCPyRPwvjFqk+xfOEQj
aBtKRAnkRw6FMriOTBGXek3Ki0UYyOZJNiFR9orQELOinCreGcJL1mSbB9Syog5z
JyX9EK7uOi1z72h1/4n8OoosbwCOO8I8bc3Yd0Enw6SNM+j6Of3KpSf6Um6DgcJv
XLl1fVDnEraW7BgZNKrRIPkwR2TvgDCCEdgRv+PTcri1mJsoV42OyPMLXa64zaV9
JQOqwTynq4y5qDI/xF4XTBaGJ8MZRksHKsGylZlmDxLQy41SzwIDAQABo2kwZzBG
BgNVHREEPzA9ghNjbHNlcnZlcjIuY2VzbmV0LmN6hiZodHRwczovL2Nsc2VydmVy
Mi5jZXNuZXQuY3ovc2hpYmJvbGV0aDAdBgNVHQ4EFgQULr1sy7EfBCmtkYOIlAjN
ilgHjbowDQYJKoZIhvcNAQELBQADggGBAJ6p/Gy1Udvcwxf8LcthQx51+5hNwmdX
Z/2mCSJe9YRPcTHsmnsoHTGxasLpMROXiMPE6rflzr9cRu9rAPL8op3eJCljCNBg
jnTWEq6Mmm+fWVsDsHWaQz/MjexJeqsEVH/phZNQzmMXvLsqH6+e3dt4HJYXdSDl
X/tMsYqaDoUVptFO7uwMwqnglKwYj2cPxdHe1W29Xh8SiwCjf+MzVnVufws2HQa1
iaKT9nsGBQP3vMhgSEjRUqb3UmgKLxnbCpS1pqq/kLdi/mtf9b2pbISi/vUEMpOo
GVwUmnaVTLJniZouGzNzNisIGGOYcV3usrDa4FAMOmqF4Pik/Ca1/sjhnkdC9Il+
dJz0siAfiOH5fS34tnbHFH1nsc9FdqL88ADFiVGKeoa7URJFlPRWi3RPJ78xZL5L
F+qJfQbqZwWuMvoJSsfvYIZpC73cBpEAUtWAIeuqib0NOjATQLIh9KtU5ouiQJki
TRAJowfvwJta/g0+S725kKG/x79uYsdlQg==
</ds:X509Certificate>
        </ds:X509Data>
      </ds:KeyInfo>
    </md:KeyDescriptor>
    <md:KeyDescriptor use="encryption">
      <ds:KeyInfo xmlns:ds="http://www.w3.org/2000/09/xmldsig#">
        <ds:KeyName>clserver2.cesnet.cz</ds:KeyName>
        <ds:KeyName>https://clserver2.cesnet.cz/shibboleth</ds:KeyName>
        <ds:X509Data>
          <ds:X509SubjectName>CN=clserver2.cesnet.cz</ds:X509SubjectName>
          <ds:X509Certificate>MIIENTCCAp2gAwIBAgIUeyKuEbijM/n0lA/ccgmxuoZ2MvUwDQYJKoZIhvcNAQEL
BQAwHjEcMBoGA1UEAxMTY2xzZXJ2ZXIyLmNlc25ldC5jejAgFw0yMjEyMjIxMjUy
NDdaGA8yMDUyMTIxNDEyNTI0N1owHjEcMBoGA1UEAxMTY2xzZXJ2ZXIyLmNlc25l
dC5jejCCAaIwDQYJKoZIhvcNAQEBBQADggGPADCCAYoCggGBANOOLsy/pKYoDJPv
VXFjRnF1qFZZ7CLaM8pOPgCuu8oyPLKzXxojgysq41Inile/BTgVZ8H+3bJW8C+O
QnM1/lox2aVI/AeRc/4FQXglbwo5Jjf6nzcYrlXaBg66CH2lpxAs/2M80Izc4EK5
QVZqMxBPrhxRyAxlT+XA4ZPv2l9+7rc3NOvDNC7aW6wkVSGViiJLHVM2panIrK83
9bP/93MKUlFMW/90fMebTmB7KiVIlo2A+dUgSvsarF84ZeCPyRPwvjFqk+xfOEQj
aBtKRAnkRw6FMriOTBGXek3Ki0UYyOZJNiFR9orQELOinCreGcJL1mSbB9Syog5z
JyX9EK7uOi1z72h1/4n8OoosbwCOO8I8bc3Yd0Enw6SNM+j6Of3KpSf6Um6DgcJv
XLl1fVDnEraW7BgZNKrRIPkwR2TvgDCCEdgRv+PTcri1mJsoV42OyPMLXa64zaV9
JQOqwTynq4y5qDI/xF4XTBaGJ8MZRksHKsGylZlmDxLQy41SzwIDAQABo2kwZzBG
BgNVHREEPzA9ghNjbHNlcnZlcjIuY2VzbmV0LmN6hiZodHRwczovL2Nsc2VydmVy
Mi5jZXNuZXQuY3ovc2hpYmJvbGV0aDAdBgNVHQ4EFgQULr1sy7EfBCmtkYOIlAjN
ilgHjbowDQYJKoZIhvcNAQELBQADggGBAJ6p/Gy1Udvcwxf8LcthQx51+5hNwmdX
Z/2mCSJe9YRPcTHsmnsoHTGxasLpMROXiMPE6rflzr9cRu9rAPL8op3eJCljCNBg
jnTWEq6Mmm+fWVsDsHWaQz/MjexJeqsEVH/phZNQzmMXvLsqH6+e3dt4HJYXdSDl
X/tMsYqaDoUVptFO7uwMwqnglKwYj2cPxdHe1W29Xh8SiwCjf+MzVnVufws2HQa1
iaKT9nsGBQP3vMhgSEjRUqb3UmgKLxnbCpS1pqq/kLdi/mtf9b2pbISi/vUEMpOo
GVwUmnaVTLJniZouGzNzNisIGGOYcV3usrDa4FAMOmqF4Pik/Ca1/sjhnkdC9Il+
dJz0siAfiOH5fS34tnbHFH1nsc9FdqL88ADFiVGKeoa7URJFlPRWi3RPJ78xZL5L
F+qJfQbqZwWuMvoJSsfvYIZpC73cBpEAUtWAIeuqib0NOjATQLIh9KtU5ouiQJki
TRAJowfvwJta/g0+S725kKG/x79uYsdlQg==
</ds:X509Certificate>
        </ds:X509Data>
      </ds:KeyInfo>
      <md:EncryptionMethod Algorithm="http://www.w3.org/2009/xmlenc11#aes128-gcm"/>
      <md:EncryptionMethod Algorithm="http://www.w3.org/2009/xmlenc11#aes192-gcm"/>
      <md:EncryptionMethod Algorithm="http://www.w3.org/2009/xmlenc11#aes256-gcm"/>
      <md:EncryptionMethod Algorithm="http://www.w3.org/2001/04/xmlenc#aes128-cbc"/>
      <md:EncryptionMethod Algorithm="http://www.w3.org/2001/04/xmlenc#aes192-cbc"/>
      <md:EncryptionMethod Algorithm="http://www.w3.org/2001/04/xmlenc#aes256-cbc"/>
      <md:EncryptionMethod Algorithm="http://www.w3.org/2001/04/xmlenc#tripledes-cbc"/>
      <md:EncryptionMethod Algorithm="http://www.w3.org/2009/xmlenc11#rsa-oaep"/>
      <md:EncryptionMethod Algorithm="http://www.w3.org/2001/04/xmlenc#rsa-oaep-mgf1p"/>
    </md:KeyDescriptor>
    <md:ArtifactResolutionService Binding="urn:oasis:names:tc:SAML:2.0:bindings:SOAP" Location="https://clserver2.cesnet.cz/Shibboleth.sso/Artifact/SOAP" index="1"/>
    <md:SingleLogoutService Binding="urn:oasis:names:tc:SAML:2.0:bindings:SOAP" Location="https://clserver2.cesnet.cz/Shibboleth.sso/SLO/SOAP"/>
    <md:SingleLogoutService Binding="urn:oasis:names:tc:SAML:2.0:bindings:HTTP-Redirect" Location="https://clserver2.cesnet.cz/Shibboleth.sso/SLO/Redirect"/>
    <md:SingleLogoutService Binding="urn:oasis:names:tc:SAML:2.0:bindings:HTTP-POST" Location="https://clserver2.cesnet.cz/Shibboleth.sso/SLO/POST"/>
    <md:SingleLogoutService Binding="urn:oasis:names:tc:SAML:2.0:bindings:HTTP-Artifact" Location="https://clserver2.cesnet.cz/Shibboleth.sso/SLO/Artifact"/>
    <md:AssertionConsumerService Binding="urn:oasis:names:tc:SAML:2.0:bindings:HTTP-POST" Location="https://clserver2.cesnet.cz/Shibboleth.sso/SAML2/POST" index="1"/>
    <md:AssertionConsumerService Binding="urn:oasis:names:tc:SAML:2.0:bindings:HTTP-POST-SimpleSign" Location="https://clserver2.cesnet.cz/Shibboleth.sso/SAML2/POST-SimpleSign" index="2"/>
    <md:AssertionConsumerService Binding="urn:oasis:names:tc:SAML:2.0:bindings:HTTP-Artifact" Location="https://clserver2.cesnet.cz/Shibboleth.sso/SAML2/Artifact" index="3"/>
    <md:AssertionConsumerService Binding="urn:oasis:names:tc:SAML:2.0:bindings:PAOS" Location="https://clserver2.cesnet.cz/Shibboleth.sso/SAML2/ECP" index="4"/>
    <md:AssertionConsumerService Binding="urn:oasis:names:tc:SAML:1.0:profiles:browser-post" Location="https://clserver2.cesnet.cz/Shibboleth.sso/SAML/POST" index="5"/>
    <md:AssertionConsumerService Binding="urn:oasis:names:tc:SAML:1.0:profiles:artifact-01" Location="https://clserver2.cesnet.cz/Shibboleth.sso/SAML/Artifact" index="6"/>
    <md:AttributeConsumingService index="0">
      <md:ServiceName xml:lang="cs">Clserver2</md:ServiceName>
      <md:ServiceName xml:lang="en">Clserver2</md:ServiceName>
      <md:ServiceDescription xml:lang="cs">CzechLight server pro evidenci a monitoring.</md:ServiceDescription>
      <md:ServiceDescription xml:lang="en">CzechLight monitoring and evidence server.</md:ServiceDescription>
      <md:RequestedAttribute FriendlyName="eppn" Name="urn:oid:1.3.6.1.4.1.5923.1.1.1.6" NameFormat="urn:oasis:names:tc:SAML:2.0:attrname-format:uri" isRequired="true"/>
      <md:RequestedAttribute FriendlyName="givenName" Name="urn:oid:2.5.4.42" NameFormat="urn:oasis:names:tc:SAML:2.0:attrname-format:uri" isRequired="true"/>
      <md:RequestedAttribute FriendlyName="mail" Name="urn:oid:0.9.2342.19200300.100.1.3" NameFormat="urn:oasis:names:tc:SAML:2.0:attrname-format:uri" isRequired="true"/>
      <md:RequestedAttribute FriendlyName="sn" Name="urn:oid:2.5.4.4" NameFormat="urn:oasis:names:tc:SAML:2.0:attrname-format:uri" isRequired="true"/>
      <md:RequestedAttribute FriendlyName="affiliation" Name="urn:oid:1.3.6.1.4.1.5923.1.1.1.9" NameFormat="urn:oasis:names:tc:SAML:2.0:attrname-format:uri"/>
    </md:AttributeConsumingService>
  </md:SPSSODescriptor>
  <md:Organization>
    <md:OrganizationName xml:lang="cs">CESNET</md:OrganizationName>
    <md:OrganizationName xml:lang="en">CESNET</md:OrganizationName>
    <md:OrganizationDisplayName xml:lang="cs">CESNET, S&#xED;&#x165; n&#xE1;rodn&#xED;ho v&#xFD;zkumu pro &#x10C;R</md:OrganizationDisplayName>
    <md:OrganizationDisplayName xml:lang="en">CESNET, NREN for Czech republic</md:OrganizationDisplayName>
    <md:OrganizationURL xml:lang="cs">http://www.cesnet.cz/</md:OrganizationURL>
    <md:OrganizationURL xml:lang="en">http://www.ces.net/</md:OrganizationURL>
  </md:Organization>
  <md:ContactPerson contactType="technical">
    <md:GivenName>konferka</md:GivenName>
    <md:SurName>cza-reports</md:SurName>
    <md:EmailAddress>mailto:cla-reports@cesnet.cz</md:EmailAddress>
  </md:ContactPerson>
</md:EntityDescriptor>');

        array_push($metadataArray, '<?xml version="1.0"?>
<md:EntityDescriptor xmlns:md="urn:oasis:names:tc:SAML:2.0:metadata" ID="_e02ef1c32d6970b29583da3a5b12553ec62824e0" entityID="https://agata.suz.cvut.cz/shibboleth">
  <md:Extensions xmlns:alg="urn:oasis:names:tc:SAML:metadata:algsupport">
    <alg:DigestMethod Algorithm="http://www.w3.org/2001/04/xmlenc#sha512"/>
    <alg:DigestMethod Algorithm="http://www.w3.org/2001/04/xmldsig-more#sha384"/>
    <alg:DigestMethod Algorithm="http://www.w3.org/2001/04/xmlenc#sha256"/>
    <alg:DigestMethod Algorithm="http://www.w3.org/2001/04/xmldsig-more#sha224"/>
    <alg:DigestMethod Algorithm="http://www.w3.org/2000/09/xmldsig#sha1"/>
    <alg:SigningMethod Algorithm="http://www.w3.org/2001/04/xmldsig-more#ecdsa-sha512"/>
    <alg:SigningMethod Algorithm="http://www.w3.org/2001/04/xmldsig-more#ecdsa-sha384"/>
    <alg:SigningMethod Algorithm="http://www.w3.org/2001/04/xmldsig-more#ecdsa-sha256"/>
    <alg:SigningMethod Algorithm="http://www.w3.org/2001/04/xmldsig-more#ecdsa-sha224"/>
    <alg:SigningMethod Algorithm="http://www.w3.org/2001/04/xmldsig-more#rsa-sha512"/>
    <alg:SigningMethod Algorithm="http://www.w3.org/2001/04/xmldsig-more#rsa-sha384"/>
    <alg:SigningMethod Algorithm="http://www.w3.org/2001/04/xmldsig-more#rsa-sha256"/>
    <alg:SigningMethod Algorithm="http://www.w3.org/2009/xmldsig11#dsa-sha256"/>
    <alg:SigningMethod Algorithm="http://www.w3.org/2001/04/xmldsig-more#ecdsa-sha1"/>
    <alg:SigningMethod Algorithm="http://www.w3.org/2000/09/xmldsig#rsa-sha1"/>
    <alg:SigningMethod Algorithm="http://www.w3.org/2000/09/xmldsig#dsa-sha1"/>
    <mdrpi:RegistrationInfo xmlns:mdrpi="urn:oasis:names:tc:SAML:2.0:assertion" registrationAuthority="http://www.eduid.cz/" registrationInstant="2013-08-16T09:51:28Z">
      <saml:AttributeValue xmlns:saml="urn:oasis:names:tc:SAML:2.0:assertion" xml:lang="en">http://www.eduid.cz/wiki/_media/en/eduid/policy/policy_eduid_en-1_1.pdf</saml:AttributeValue>
      <saml:AttributeValue xmlns:saml="urn:oasis:names:tc:SAML:2.0:assertion" xml:lang="cs">http://www.eduid.cz/wiki/_media/eduid/policy/policy_eduid_cz-1_1-3.pdf</saml:AttributeValue>
    </mdrpi:RegistrationInfo>
  </md:Extensions>
  <md:SPSSODescriptor protocolSupportEnumeration="urn:oasis:names:tc:SAML:2.0:protocol urn:oasis:names:tc:SAML:1.1:protocol urn:oasis:names:tc:SAML:1.0:protocol">
    <md:Extensions>
      <init:RequestInitiator xmlns:init="urn:oasis:names:tc:SAML:profiles:SSO:request-init" Binding="urn:oasis:names:tc:SAML:profiles:SSO:request-init" Location="https://agata.suz.cvut.cz/Shibboleth.sso/Login"/>
      <idpdisc:DiscoveryResponse xmlns:idpdisc="urn:oasis:names:tc:SAML:profiles:SSO:idp-discovery-protocol" Binding="urn:oasis:names:tc:SAML:profiles:SSO:idp-discovery-protocol" Location="https://agata.suz.cvut.cz/Shibboleth.sso/Login" index="1"/>
      <mdui:UIInfo xmlns:mdui="urn:oasis:names:tc:SAML:metadata:ui">
        <mdui:DisplayName xml:lang="en">Czech Technical University in Prague</mdui:DisplayName>
        <mdui:DisplayName xml:lang="cs">&#x10C;esk&#xE9; vysok&#xE9; u&#x10D;en&#xED; technick&#xE9; v Praze</mdui:DisplayName>
        <mdui:Description xml:lang="en">Canteens of CTU in Prague</mdui:Description>
        <mdui:Description xml:lang="cs">Menzy &#x10C;VUT v Praze</mdui:Description>
        <mdui:InformationURL xml:lang="en">https://www.suz.cvut.cz/en/the-facilities-administration-department-of-the-czech-technical-university-in-prague</mdui:InformationURL>
        <mdui:InformationURL xml:lang="cs">https://www.suz.cvut.cz/sprava-ucelovych-zarizeni-cvut</mdui:InformationURL>
        <mdui:Logo height="40" width="53">https://idp2.civ.cvut.cz/cvutid/logo_cvut_40pix.png</mdui:Logo>
      </mdui:UIInfo>
    </md:Extensions>
    <md:KeyDescriptor>
      <ds:KeyInfo xmlns:ds="http://www.w3.org/2000/09/xmldsig#">
        <ds:KeyName>agata.suz.cvut.cz</ds:KeyName>
        <ds:KeyName>https://agata.suz.cvut.cz/shibboleth</ds:KeyName>
        <ds:X509Data>
          <ds:X509SubjectName>CN=agata.suz.cvut.cz</ds:X509SubjectName>
          <ds:X509Certificate>MIIEKzCCApOgAwIBAgIUW9fhpX3oGKA8iHpystxeRc+EWWswDQYJKoZIhvcNAQEL
BQAwHDEaMBgGA1UEAxMRYWdhdGEuc3V6LmN2dXQuY3owHhcNMjIxMTAzMTMzODQ5
WhcNMzIxMDMxMTMzODQ5WjAcMRowGAYDVQQDExFhZ2F0YS5zdXouY3Z1dC5jejCC
AaIwDQYJKoZIhvcNAQEBBQADggGPADCCAYoCggGBAOKYVEVsWscz3ATAZcb0jrYD
2aIZUzJcUx3G+LNjpBqUdW5sbFN2l9F8DUdSsoXUaRvOav5lOwJw/ob6r9IhZtXm
8epDMw1TyJE5L38Ja+emZ5m1xFHvBvxf4jBzFapQ7USw6PnmK7GQlQPpfU82Ln70
Q+UkhChFyO6gbtXdIwTI1MWTOluEFi7QDo+U2flXfRPRyjqc0TWRcZMWLyMAWpDr
g5Qkw+JrwO1caGKnlpUwUi04iN2Ob5fG/+GfwGAma5lHmB8bThqHxcgGa5pIzGSx
RB6lbv3pOZul1iSFgLnACsVULap5oGoKRy2/g/mH/RIRRhwX6H2Sh+txvOcgEXbR
6uHeDeWkuKbICrmADZUX3qPqNsio39+mxAYA5hrNF1qwzDVpfaZFQVJ7XssMA+jr
yaJ468xew4LkZZ9QkdZQrEAECWlz11F85yVHrDjpvFzFwmbIt1o7GgC6h4D6Mc5b
EZKe0IoOGgchLegKa+xeSshWnS+jDU/+vXU+vljvaQIDAQABo2UwYzBCBgNVHREE
OzA5ghFhZ2F0YS5zdXouY3Z1dC5jeoYkaHR0cHM6Ly9hZ2F0YS5zdXouY3Z1dC5j
ei9zaGliYm9sZXRoMB0GA1UdDgQWBBRwNo4zZprGNBm5zUDrqobfZEgDFTANBgkq
hkiG9w0BAQsFAAOCAYEAZvpexRFXe/KmIVK3jAt4fc202/HWFohyCwQXwynVnJ7Q
i99z6vLu+DgGNCVKZ1uPDMC5Sr20FStv9Bcq9rnSOEKV/w9LgQlCHOzmtq2CAcWk
+S3Bj20fUyfwJ6OSw7M3rKj4kVD3RS/HxHZD97hnw0vQ8NbXPRX4cfAkWmeksFOW
RZO3sNW8ubZNAm4KmXkWQ56rCvIA07pyLuvhLF8/Idaofh3d8VFVvmqGfeDOs6A2
0i5yqxeLf0+MvB/Wof3wWG+PUfR0PGp2fkbvLNkJ9ubZH9ugGh57jXtGzhPTaTv1
kb5SL1gGvAbGTve63ufpgJwfqJkq+Lkd55GHgqdcQDUP0l62pPb4bNjyx5luC17t
uJuwVkydjahNUgWN57ZukdbaKuBWtjAa/gRH94bTPx+7S5/BjWw3VK0v5dPb/+SC
ZRBKdAOmmcZbunWBaY9dCEsZWua9SQY/6ARGVjOEfIkOMALIHuZe1K8lA94X7aE/
nhmFq0B2lWkk0qKcEzdP
</ds:X509Certificate>
        </ds:X509Data>
      </ds:KeyInfo>
      <md:EncryptionMethod Algorithm="http://www.w3.org/2009/xmlenc11#aes128-gcm"/>
      <md:EncryptionMethod Algorithm="http://www.w3.org/2009/xmlenc11#aes192-gcm"/>
      <md:EncryptionMethod Algorithm="http://www.w3.org/2009/xmlenc11#aes256-gcm"/>
      <md:EncryptionMethod Algorithm="http://www.w3.org/2001/04/xmlenc#aes128-cbc"/>
      <md:EncryptionMethod Algorithm="http://www.w3.org/2001/04/xmlenc#aes192-cbc"/>
      <md:EncryptionMethod Algorithm="http://www.w3.org/2001/04/xmlenc#aes256-cbc"/>
      <md:EncryptionMethod Algorithm="http://www.w3.org/2001/04/xmlenc#tripledes-cbc"/>
      <md:EncryptionMethod Algorithm="http://www.w3.org/2009/xmlenc11#rsa-oaep"/>
      <md:EncryptionMethod Algorithm="http://www.w3.org/2001/04/xmlenc#rsa-oaep-mgf1p"/>
    </md:KeyDescriptor>
    <md:ArtifactResolutionService Binding="urn:oasis:names:tc:SAML:2.0:bindings:SOAP" Location="https://agata.suz.cvut.cz/Shibboleth.sso/Artifact/SOAP" index="1"/>
    <md:SingleLogoutService Binding="urn:oasis:names:tc:SAML:2.0:bindings:SOAP" Location="https://agata.suz.cvut.cz/Shibboleth.sso/SLO/SOAP"/>
    <md:SingleLogoutService Binding="urn:oasis:names:tc:SAML:2.0:bindings:HTTP-Redirect" Location="https://agata.suz.cvut.cz/Shibboleth.sso/SLO/Redirect"/>
    <md:SingleLogoutService Binding="urn:oasis:names:tc:SAML:2.0:bindings:HTTP-POST" Location="https://agata.suz.cvut.cz/Shibboleth.sso/SLO/POST"/>
    <md:SingleLogoutService Binding="urn:oasis:names:tc:SAML:2.0:bindings:HTTP-Artifact" Location="https://agata.suz.cvut.cz/Shibboleth.sso/SLO/Artifact"/>
    <md:AssertionConsumerService Binding="urn:oasis:names:tc:SAML:2.0:bindings:HTTP-POST" Location="https://agata.suz.cvut.cz/Shibboleth.sso/SAML2/POST" index="1"/>
    <md:AssertionConsumerService Binding="urn:oasis:names:tc:SAML:2.0:bindings:HTTP-POST-SimpleSign" Location="https://agata.suz.cvut.cz/Shibboleth.sso/SAML2/POST-SimpleSign" index="2"/>
    <md:AssertionConsumerService Binding="urn:oasis:names:tc:SAML:2.0:bindings:HTTP-Artifact" Location="https://agata.suz.cvut.cz/Shibboleth.sso/SAML2/Artifact" index="3"/>
    <md:AssertionConsumerService Binding="urn:oasis:names:tc:SAML:2.0:bindings:PAOS" Location="https://agata.suz.cvut.cz/Shibboleth.sso/SAML2/ECP" index="4"/>
    <md:AssertionConsumerService Binding="urn:oasis:names:tc:SAML:1.0:profiles:browser-post" Location="https://agata.suz.cvut.cz/Shibboleth.sso/SAML/POST" index="5"/>
    <md:AssertionConsumerService Binding="urn:oasis:names:tc:SAML:1.0:profiles:artifact-01" Location="https://agata.suz.cvut.cz/Shibboleth.sso/SAML/Artifact" index="6"/>
  </md:SPSSODescriptor>
  <md:Organization>
    <md:OrganizationName xml:lang="en">CTU</md:OrganizationName>
    <md:OrganizationName xml:lang="cs">&#x10C;VUT</md:OrganizationName>
    <md:OrganizationDisplayName xml:lang="en">Czech Technical University in Prague</md:OrganizationDisplayName>
    <md:OrganizationDisplayName xml:lang="cs">&#x10C;esk&#xE9; vysok&#xE9; u&#x10D;en&#xED; technick&#xE9; v Praze</md:OrganizationDisplayName>
    <md:OrganizationURL xml:lang="en">http://www.cvut.cz/en</md:OrganizationURL>
    <md:OrganizationURL xml:lang="cs">http://www.cvut.cz</md:OrganizationURL>
  </md:Organization>
  <md:ContactPerson contactType="technical">
    <md:GivenName>Tom&#xE1;&#x161;</md:GivenName>
    <md:SurName>Ka&#x148;ovsk&#xFD;</md:SurName>
    <md:EmailAddress>mailto:admin@suz.cvut.cz</md:EmailAddress>
  </md:ContactPerson>
</md:EntityDescriptor>');

        $i = 1;

        foreach ($metadataArray as $metadata) {

            $federation = Federation::findOrFail(($i % 3) + 1);
            $i++;
            $this->importOneSp($metadata, $federation);
        }

    }

    /**
     * Execute the console command.
     */
    public function handle()
    {
        if (! App::environment(['local', 'testing'])) {
            $this->error('This command can only be run in local or testing environments.');
        }
        $this->importFederations();
        $this->createSp();

    }
}
