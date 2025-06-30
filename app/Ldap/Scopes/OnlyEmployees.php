<?php

namespace App\Ldap\Scopes;

use LdapRecord\Models\Model;
use LdapRecord\Models\Scope;
use LdapRecord\Query\Model\Builder;

class OnlyEmployees implements Scope
{
    /**
     * Apply the scope to the given query.
     */
    public function apply(Builder $builder, Model $model): void
    {
        // Only get users with an employee ID
        $builder->where('employeeid', '>=', '1');
        
        // Exclude computer accounts
        $builder->where('objectclass', '=', 'user')
                ->where('objectclass', '!=', 'computer');
    }
}