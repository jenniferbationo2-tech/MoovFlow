<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        $typesAttendus = [
            ['code' => 'BARA_MOUSSO', 'nom' => 'Bara Mousso'],
            ['code' => 'CONF',        'nom' => 'Conférence'],
            ['code' => 'SPORT',       'nom' => 'Compétition sportive'],
            ['code' => 'CHALLENGE',   'nom' => 'Challenge Innovation'],
            ['code' => 'FORMATION',   'nom' => 'Formation numérique'],
            ['code' => 'HACK',        'nom' => 'Hackathon'],
            ['code' => 'SALON',       'nom' => 'Salon'],
        ];

        foreach ($typesAttendus as $type) {
            $exists = DB::table('types_evenement')->where('code', $type['code'])->exists();
            if (!$exists) {
                DB::table('types_evenement')->insert(array_merge($type, [
                    'created_at' => now(),
                    'updated_at' => now(),
                ]));
            }
        }
    }

    public function down(): void
    {
        // Pas de rollback (potentiellement destructif)
    }
};