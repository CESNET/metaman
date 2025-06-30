<?php

namespace App\Ldap;

use LdapRecord\Models\Model;

class CesnetOrganization extends Model
{
    /**
     * The object classes of the LDAP model.
     *
     * @var array
     */
    public static array $objectClasses = [
        'top',
        'dcObject',
        'cesnetOrganization',
    ];

    /**
     * @codeCoverageIgnore
     */
    protected function getCreatableRdnAttribute(): string
    {
        return 'dc';
    }
}
