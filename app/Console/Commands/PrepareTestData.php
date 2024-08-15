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

    private function importOneSp(): void
    {
        $metadata = '<?xml version="1.0"?>
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
                $federation = Federation::findOrFail(1);
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

    /**
     * Execute the console command.
     */
    public function handle()
    {
        if (! App::environment(['local', 'testing'])) {
            $this->error('This command can only be run in local or testing environments.');
        }
        $this->importFederations();
        $this->importOneSp();

    }
}
