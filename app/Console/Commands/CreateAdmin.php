<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rules\Password;

class CreateAdmin extends Command
{
    protected $signature = 'syncode:admin {--name= : Administrator display name} {--email= : Administrator email}';

    protected $description = 'Create a real Super Admin using a hidden password prompt';

    public function handle(): int
    {
        $data = [
            'name' => $this->option('name') ?: $this->ask('Administrator name'),
            'email' => strtolower(trim($this->option('email') ?: $this->ask('Administrator email'))),
            'password' => $this->secret('Password (minimum 12 characters)'),
            'password_confirmation' => $this->secret('Confirm password'),
        ];
        $validator = Validator::make($data, [
            'name' => ['required', 'string', 'max:80'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'confirmed', Password::min(12)->mixedCase()->numbers()->symbols()],
        ]);
        if ($validator->fails()) {
            foreach ($validator->errors()->all() as $error) {
                $this->error($error);
            }

            return self::FAILURE;
        }
        $user = new User;
        $user->forceFill(collect($data)->only(['name', 'email', 'password'])->all() + ['role' => 'super_admin', 'suspended' => false])->save();
        $this->info('Super Admin created. Sign in with the email and password you supplied.');

        return self::SUCCESS;
    }
}
