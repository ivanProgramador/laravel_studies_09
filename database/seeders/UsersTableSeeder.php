<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\User;
use Illuminate\Support\Carbon;

class UsersTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
         for($index = 1;$index <= 3; $index++){
         
         User::create([
            
             'user_name'=>"user$index",
             'email'=>"user$index@gmail.com",
             'password'=>bcrypt('Aa123456'),
             'email_verified_at'=>Carbon::now(),
             'active'=>true
            ]);
    }
}
}
