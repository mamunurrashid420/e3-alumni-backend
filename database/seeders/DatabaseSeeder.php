<?php

namespace Database\Seeders;

use App\Models\User;
use App\PrimaryMemberType;
use App\UserRole;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            MemberTypeSeeder::class,
        ]);

        // Create super admin
        User::create([
            'name' => 'Super Admin',
            'email' => 'admin@jssalumni.org',
            'password' => Hash::make('jsSalumni@2026admin'),
            'role' => UserRole::SuperAdmin,
            'primary_member_type' => null,
            'secondary_member_type_id' => null,
        ]);

        // Create member user
        User::create([
            'name' => 'Test Member',
            'email' => 'member@example.com',
            'password' => Hash::make('password'),
            'role' => UserRole::Member,
            'primary_member_type' => PrimaryMemberType::General,
            'secondary_member_type_id' => null,
            'member_id' => 'G-1900-0000',
        ]);
    }
}
