<?php

namespace Modules\Auth\Console;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rules\Password;
use Modules\Auth\Models\Admin;

class CreateAdminCommand extends Command
{
    protected $signature = 'yaqot:create-admin';

    protected $description = 'Create a dashboard administrator account';

    public function handle(): int
    {
        $data = [
            'name' => trim((string) $this->ask('Name')),
            'email' => strtolower(trim((string) $this->ask('Email'))),
            'password' => (string) $this->secret('Password (min. 10 characters, upper and lower case, a number and a symbol)'),
            'password_confirmation' => (string) $this->secret('Confirm password'),
        ];

        $validator = Validator::make($data, [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:admins,email'],
            'password' => ['required', 'confirmed', Password::defaults()],
        ]);

        if ($validator->fails()) {
            foreach ($validator->errors()->all() as $error) {
                $this->error($error);
            }

            return self::FAILURE;
        }

        $admin = new Admin($validator->safe()->only(['name', 'email', 'password']));
        $admin->is_active = true;
        $admin->save();

        $this->info("Admin [{$admin->email}] created.");

        return self::SUCCESS;
    }
}
