<?php

namespace Database\Seeders;

use App\Models\ManagementRank;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class ManagementRankSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        ManagementRank::create([
            'name' => 'Front line',
            'rank' => json_encode([1, 2])
        ]);

        ManagementRank::create([
            'name' => 'Supervisor level',
            'rank' => json_encode([3, 4])
        ]);


        ManagementRank::create([
            'name' => 'Middle Management',
            'rank' => json_encode([5, 6])
        ]);

        ManagementRank::create([
            'name' => 'Top Management',
            'rank' => json_encode([7])
        ]);
    }
}
