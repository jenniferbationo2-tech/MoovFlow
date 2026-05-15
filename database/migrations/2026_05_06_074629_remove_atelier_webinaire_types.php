<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        // Vérifier qu'aucun événement n'utilise Atelier/Webinaire
        $count = DB::table('evenements')
            ->whereIn('type_evenement_id', function ($q) {
                $q->select('id')
                  ->from('types_evenement')
                  ->whereIn('code', ['ATELIER', 'WEBINAIRE']);
            })
            ->count();

        if ($count > 0) {
            // Si des événements existent, les passer en CONF par défaut
            $confId = DB::table('types_evenement')->where('code', 'CONF')->value('id');

            if ($confId) {
                DB::table('evenements')
                    ->whereIn('type_evenement_id', function ($q) {
                        $q->select('id')
                          ->from('types_evenement')
                          ->whereIn('code', ['ATELIER', 'WEBINAIRE']);
                    })
                    ->update(['type_evenement_id' => $confId]);
            }
        }

        // Supprimer les 2 types
        DB::table('types_evenement')
          ->whereIn('code', ['ATELIER', 'WEBINAIRE'])
          ->delete();
    }

    public function down(): void
    {
        DB::table('types_evenement')->insert([
            ['nom' => 'Atelier',   'code' => 'ATELIER',   'created_at' => now(), 'updated_at' => now()],
            ['nom' => 'Webinaire', 'code' => 'WEBINAIRE', 'created_at' => now(), 'updated_at' => now()],
        ]);
    }
};