<?php

namespace Database\Seeders;

use App\Models\User;
use App\Support\DemoAccounts;
use Illuminate\Database\Seeder;

class DemoUserSeeder extends Seeder
{
    public function run(): void
    {
        if (! config('demo.enabled')) {
            return;
        }

        foreach (DemoAccounts::users() as $account) {
            $user = User::firstOrNew(['email' => $account['email']]);
            $user->name = $account['name'];
            $user->role = $account['role'];
            $user->password = DemoAccounts::PASSWORD;
            $user->email_verified_at = now();
            $user->save();
        }
    }
}
