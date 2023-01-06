<?php

namespace App\Ldap;

use LdapRecord\Models\Model;

class EduidczOrganization extends Model
{
    protected $connection = 'eduidczorganizations';

    /**
     * The object classes of the LDAP model.
     *
     * @var array
     */
    public static $objectClasses = [
        'top',
        'eduidczorganization',
    ];

    protected function getCreatableRdnAttribute()
    {
        return 'dc';
    }
}
