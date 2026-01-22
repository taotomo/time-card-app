<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use App\Models\User;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // 管理者ユーザー
        User::create([
            'name' => '管理者',
            'email' => 'admin@example.com',
            'password' => Hash::make('password123'),
        ]);

        // テストユーザー
        User::create([
            'name' => '山田 太郎',
            'email' => 'yamada@example.com',
            'password' => Hash::make('password123'),
        ]);

        User::create([
            'name' => '西 怜音',
            'email' => 'nishi@example.com',
            'password' => Hash::make('password123'),
        ]);

        User::create([
            'name' => '堀田 一世',
            'email' => 'hotta@example.com',
            'password' => Hash::make('password123'),
        ]);

        User::create([
            'name' => '山本 晃吾',
            'email' => 'yamamoto@example.com',
            'password' => Hash::make('password123'),
        ]);

        User::create([
            'name' => '秋田 麻美',
            'email' => 'akita@example.com',
            'password' => Hash::make('password123'),
        ]);

        User::create([
            'name' => '中垣 綾美',
            'email' => 'nakagaki@example.com',
            'password' => Hash::make('password123'),
        ]);
    }
}
