<?php

namespace Database\Seeders;

use App\Models\User;
use App\Enums\RoleEnum;
use Spatie\Permission\Models\Role;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $user = User::firstOrCreate(
            [
                'email' => 'user@local.dev'
            ],
            [
                'name' => 'User',
                'password' => bcrypt('user123')
            ]);

        $author = User::firstOrCreate(
            [
                'email' => 'author@local.dev'
            ],
            [
                'name' => 'First Author',
                'password' => bcrypt('author123')
            ]);

        $secondAuthor = User::firstOrCreate(
            [
                'email' => 'author2@local.dev'
            ],
            [
                'name' => 'Second Author',
                'password' => bcrypt('author123')
            ]);

        $thirdAuthor = User::firstOrCreate(
            [
                'email' => 'author3@local.dev'
            ],
            [
                'name' => 'Third Author',
                'password' => bcrypt('author123')
            ]);

        $admin = User::firstOrCreate(
            [
                'email' => 'admin@local.dev',
            ],
            [
                'name' => 'Admin',
                'password' => bcrypt('admin123')
            ]);

        $admin->assignRole(RoleEnum::ADMIN->value);
        $author->assignRole(RoleEnum::AUTHOR->value);
        $secondAuthor->assignRole(RoleEnum::AUTHOR->value);
        $thirdAuthor->assignRole(RoleEnum::AUTHOR->value);
    }
}
