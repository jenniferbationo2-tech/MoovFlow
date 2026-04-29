<?php

namespace Database\Seeders;

use App\Models\TypeEvenement;
use Illuminate\Database\Seeder;

class TypeEvenementSeeder extends Seeder
{
    public function run(): void
    {
        $types = [
            ['nom' => 'Conférence', 'code' => 'CONF'],
            ['nom' => 'Hackathon', 'code' => 'HACK'],
            ['nom' => 'Atelier', 'code' => 'ATELIER'],
            ['nom' => 'Formation', 'code' => 'FORMATION'],
            ['nom' => 'Salon', 'code' => 'SALON'],
            ['nom' => 'Compétition sportive', 'code' => 'SPORT'],
            ['nom' => 'Webinaire', 'code' => 'WEBINAIRE'],
        ];

        foreach ($types as $type) {
            TypeEvenement::updateOrCreate(
                ['code' => $type['code']],
                ['nom' => $type['nom']]
            );
        }
    }
}