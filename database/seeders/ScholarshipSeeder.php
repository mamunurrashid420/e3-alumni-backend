<?php

namespace Database\Seeders;

use App\Models\Scholarship;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class ScholarshipSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $scholarships = [
            [
                'title' => 'Scholarships for Students (Classes 6–9, SSC)',
                'description' => 'Scholarship for current students of Jahapur Secondary School from classes VI to IX and SSC level.',
                'category' => 'Students (Classes 6–9, SSC)',
                'is_active' => true,
                'sort_order' => 1,
            ],
            [
                'title' => 'JSSAA Scholarships (Upazila/Zila level)',
                'description' => 'Scholarship for current students of other schools (Inter Union, Upazilla & Zilla) from classes VI to X.',
                'category' => 'Upazila/Zila level',
                'is_active' => true,
                'sort_order' => 2,
            ],
            [
                'title' => 'JSSAA Scholarships for Ex-Students',
                'description' => 'Scholarship support for ex-students of Jahapur Secondary School who are currently pursuing further education.',
                'category' => 'Ex-Students',
                'is_active' => true,
                'sort_order' => 3,
            ],
            [
                'title' => "JSSAA Scholarships for Ex-Students' Children",
                'description' => 'Educational support for children of registered alumni members of Jahapur Secondary School.',
                'category' => "Ex-Students' Children",
                'is_active' => true,
                'sort_order' => 4,
            ],
        ];

        foreach ($scholarships as $data) {
            Scholarship::create($data);
        }
    }
}
