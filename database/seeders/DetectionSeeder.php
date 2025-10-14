<?php

namespace Database\Seeders;

use App\Models\Detection;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DetectionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Detection::create([
            'user_id' => 1,
            'other_detection' => 30000
        ]);
    }
}
