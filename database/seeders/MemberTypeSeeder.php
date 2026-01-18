<?php

namespace Database\Seeders;

use App\Models\MemberType;
use Illuminate\Database\Seeder;

class MemberTypeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $memberTypes = [
            // Executive Positions
            'President',
            'Senior Vice President',
            'Vice President',

            // Secretary Positions
            'Secretary General',
            'Senior Joint Secretary General',
            'Joint Secretary General',

            // Treasurer Positions
            'Treasurer',
            'Co-Treasurer',

            // Organizing & Office Positions
            'Organising Secretary',
            'Office Secretary',

            // Department Secretaries
            'Cultural & Publication Secretary',
            'Publicity, Information & Communication Secretary',
            'Education, Technology & Research Secretary',
            'Sports Secretary',
            'Social Welfare Secretary',
            'Legal Affairs Secretary',
            'International Affairs Secretary',
            'Women Affairs Secretary',
            'Health &Treatment Affairs Secretary',
            'Career & Employment Affairs Secretary',

            // Associate Department Secretaries
            'Associate Organising Secretary',
            'Associate Office Secretary',
            'Associate Cultural & Publication Secretary',
            'Associate Publicity, Information & Communication Secretary',
            'Associate Education, Technology & Research Secretary',
            'Associate Sports Secretary',
            'Associate Social Welfare Secretary',
            'Associate Legal Affairs Secretary',
            'Associate International Affairs Secretary',
            'Associate Women Affairs Secretary',
            'Associate Health & Treatment Affairs Secretary',
            'Associate Career & Employment Affairs Secretary',
        ];

        foreach ($memberTypes as $name) {
            MemberType::firstOrCreate(
                ['name' => $name],
                ['description' => null]
            );
        }
    }
}
