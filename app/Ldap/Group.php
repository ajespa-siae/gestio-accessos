<?php

namespace App\Ldap;

use LdapRecord\Models\Model;

class Group extends Model
{
    /**
     * The object classes of the LDAP model.
     */
    public static array $objectClasses = [
        'top',
        'group',
    ];

    /**
     * Get the group name.
     */
    public function getName(): ?string
    {
        return $this->getFirstAttribute('cn');
    }

    /**
     * Get the group description.
     */
    public function getDescription(): ?string
    {
        return $this->getFirstAttribute('description');
    }

    /**
     * Get all members of this group.
     */
    public function getMembers(): array
    {
        return $this->getAttribute('member') ?: [];
    }

    /**
     * Check if a user is a member of this group.
     */
    public function hasMember(string $userDn): bool
    {
        $members = $this->getMembers();
        
        foreach ($members as $member) {
            if (strcasecmp($member, $userDn) === 0) {
                return true;
            }
        }
        
        return false;
    }
}
