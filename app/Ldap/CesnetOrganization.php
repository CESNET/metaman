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
    public static $objectClasses = [
        'top',
        'dcObject',
        'cesnetOrganization',
    ];

    protected function getCreatableRdnAttribute()
    {
        return 'dc';
    }
}
