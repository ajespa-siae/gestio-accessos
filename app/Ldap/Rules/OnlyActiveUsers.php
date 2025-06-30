<?php

namespace App\Ldap\Rules;

use LdapRecord\Models\Model;

class OnlyActiveUsers
{
    /**
     * Check if the user is active.
     */
    public function passes(Model $user): bool
    {
        $userAccountControl = $user->getFirstAttribute('useraccountcontrol');
        
        if (!$userAccountControl) {
            return false;
        }

        // 0x0002 = ACCOUNTDISABLE
        $isDisabled = $userAccountControl & 2;
        
        return !$isDisabled;
    }
}