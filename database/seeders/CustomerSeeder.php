<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Customer;
use Illuminate\Support\Facades\Hash;

class CustomerSeeder extends Seeder
{
    public function run()
    {
        Customer::create([
            'code' => 'CUST001',
            'name' => 'Nugi Darmawan',
            'email' => 'nugi@gmail.com',
            'phone' => '08123456789',
            'address' => 'Jl. Jakarta No. 22',
            'is_active' => true,
        ]);
    }
}