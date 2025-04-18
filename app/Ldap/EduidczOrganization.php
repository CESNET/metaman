<?php

namespace App\Ldap;

use LdapRecord\Models\Model;

class EduidczOrganization extends Model
{
    protected ?string $connection = 'eduidczorganizations';

    /**
     * The object classes of the LDAP model.
     *
     * @var array
     */
    public static array $objectClasses = [
        'top',
        'eduidczorganization',
    ];

    protected function getCreatableRdnAttribute(): string
    {
        return 'dc';
    }
}
