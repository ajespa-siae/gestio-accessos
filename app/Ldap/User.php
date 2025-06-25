<?php

namespace App\Ldap;

use LdapRecord\Models\Model;
use LdapRecord\Models\Concerns\HasPassword;
use LdapRecord\Models\Concerns\CanAuthenticate;

class User extends Model
{
    use HasPassword, CanAuthenticate;

    /**
     * The object classes of the LDAP model.
     */
    protected static array $objectClasses = [
        'top',
        'person',
        'organizationalperson',
        'user',
    ];

    /**
     * The LDAP connection to use.
     */
    protected ?string $connection = 'default';

    /**
     * Get the username from LDAP
     */
    public function getUsername(): ?string
    {
        return $this->getFirstAttribute('samaccountname');
    }

    /**
     * Get the employee ID from LDAP
     */
    public function getEmployeeId(): ?string
    {
        return $this->getFirstAttribute('employeeid');
    }

    /**
     * Get the display name from LDAP
     */
    public function getDisplayName(): string
    {
        return $this->getFirstAttribute('cn') 
            ?: $this->getFirstAttribute('displayname') 
            ?: 'Unknown User';
    }

    /**
     * Get the email address from LDAP
     */
    public function getEmailAddress(): ?string
    {
        return $this->getFirstAttribute('mail');
    }

    /**
     * Get the department from LDAP
     */
    public function getDepartment(): ?string
    {
        return $this->getFirstAttribute('department');
    }

    /**
     * Get the title/position from LDAP
     */
    public function getTitle(): ?string
    {
        return $this->getFirstAttribute('title');
    }

    /**
     * Get user groups
     */
    public function groups()
    {
        return $this->hasMany(Group::class, 'member');
    }

    /**
     * Check if user is member of a group
     */
    public function isMemberOf(string $groupName): bool
    {
        return $this->groups()
            ->where('cn', $groupName)
            ->exists();
    }

    /**
     * Get all group names
     */
    public function getGroupNames(): array
    {
        return $this->groups()
            ->get()
            ->map(fn($group) => $group->getFirstAttribute('cn'))
            ->filter()
            ->values()
            ->toArray();
    }
}