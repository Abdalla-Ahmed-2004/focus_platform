<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;

class CreateAdminCommand extends Command
{
    protected $signature = 'make:admin';
    protected $description = 'Create a secure Super Admin user interactively';

    public function handle()
    {
        $email = $this->ask('Enter Admin Email');
        $password = $this->secret('Enter Admin Password');

        if (User::where('email', $email)->exists()) {
            $this->error('A user with this email already exists!');
            return;
        }

        $user =  User::create([
            'name' => 'Super Admin',
            'email' => $email,
            'password' => Hash::make($password),

        ]);
        $user->assignRole('admin');



        $this->info('Admin account successfully created!');
    }
}
