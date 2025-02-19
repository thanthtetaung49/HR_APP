<?php

namespace Database\Seeders;

use App\Models\Allowance;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class AllowanceSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Allowance::create([
            'user_id' => 1,
            'basic_salary' => 500000,
            'technical_allowance' => 50000,
            'living_cost_allowance' => 300000,
            'special_allowance' => 200000
        ]);
    }
}
