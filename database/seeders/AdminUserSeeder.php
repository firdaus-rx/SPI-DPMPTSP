<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AdminUserSeeder extends Seeder
{
    public function run(): void
    {
        $users = [
            [
                'name' => 'Administrator DPMPTSP',
                'email' => config('auth.admin_email', env('ADMIN_EMAIL', 'admin@dpmptsp.pidie.go.id')),
                'password' => config('auth.admin_password', env('ADMIN_PASSWORD', 'password')),
            ],
            // Tambah akun lain di sini bila perlu — tanpa register UI.
            // ['name' => 'Operator', 'email' => 'operator@dpmptsp.pidie.go.id', 'password' => 'password'],
        ];

        foreach ($users as $data) {
            // User::casts password => 'hashed' → cukup assign plain, model akan hash otomatis (Hash::isHashed dicegah double-hash)
            User::updateOrCreate(
                ['email' => $data['email']],
                [
                    'name' => $data['name'],
                    'password' => $data['password'],
                    'email_verified_at' => now(),
                ]
            );
        }
    }
}
