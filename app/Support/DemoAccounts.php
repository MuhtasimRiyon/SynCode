<?php

namespace App\Support;

class DemoAccounts
{
    public const PASSWORD = 'SynCode@123';

    public static function users(): array
    {
        return [
            ['name' => 'Demo Admin', 'email' => 'admin@syncode.test', 'role' => 'super_admin', 'label' => 'Super Admin'],
            ['name' => 'Demo Moderator', 'email' => 'moderator@syncode.test', 'role' => 'moderator', 'label' => 'Moderator'],
            ['name' => 'Demo Author', 'email' => 'author@syncode.test', 'role' => 'author', 'label' => 'Author'],
        ];
    }
}
