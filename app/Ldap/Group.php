<?php

namespace App\Ldap;

use LdapRecord\Models\Model;

class Group extends Model
{
    /**
     * The object classes of the LDAP model.
     */
    protected static array $objectClasses = [
        'top',
        'group',
    ];

    /**
     * The LDAP connection to use.
     */
    protected ?string $connection = 'default';

    /**
     * Get members of this group
     */
    public function members()
    {
        return $this->hasMany(User::class, 'memberof');
    }

    /**
     * Get the group name
     */
    public function getName(): ?string
    {
        return $this->getFirstAttribute('cn');
    }

    /**
     * Get the group description
     */
    public function getDescription(): ?string
    {
        return $this->getFirstAttribute('description');
    }
}