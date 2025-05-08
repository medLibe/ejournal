<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        User::create([
            'name'  => 'Gerry',
            'username'  => 'ArsNova',
            'password'  => Hash::make('arang123'),
        ]);

        User::create([
            'name'  => 'Joy',
            'username'  => 'joyful',
            'password'  => Hash::make('joyful123'),
        ]);
    }
}
