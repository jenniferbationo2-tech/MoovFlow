<?php

namespace Database\Seeders;

use App\Models\Evenement;
use App\Models\Lieu;
use App\Models\Tarif;
use App\Models\TypeEvenement;
use App\Models\User;
use Illuminate\Database\Seeder;

class TestDataSeeder extends Seeder
{
    public function run(): void
    {
        // 1️⃣ — Lieux
        $lieux = [
            ['nom' => 'Palais des Sports Ouaga 2000', 'adresse' => 'Ouagadougou'],
            ['nom' => 'Hôtel Laïco', 'adresse' => 'Ouagadougou'],
            ['nom' => 'Stade du 4 Août', 'adresse' => 'Ouagadougou'],
            ['nom' => 'Parc SIAO', 'adresse' => 'Ouagadougou'],
        ];

        foreach ($lieux as $l) {
            Lieu::firstOrCreate(['nom' => $l['nom']], $l);
        }

        // 2️⃣ — Utilisateurs de test
        $admin = User::firstOrCreate(['email' => 'admin@moov.bf'], [
            'name'      => 'Admin Moov',
            'nom'       => 'ADMIN',
            'prenom'    => 'Moov',
            'password'  => bcrypt('password'),
            'is_active' => true,
        ]);
        $admin->assignRole('admin');

        $orga = User::firstOrCreate(['email' => 'orga@moov.bf'], [
            'name'      => 'Aminata Kaboré',
            'nom'       => 'KABORE',
            'prenom'    => 'Aminata',
            'telephone' => '70000001',
            'password'  => bcrypt('password'),
            'is_active' => true,
        ]);
        $orga->assignRole('organisateur');

        $resp = User::firstOrCreate(['email' => 'resp@moov.bf'], [
            'name'      => 'Fatimata Ouedraogo',
            'nom'       => 'OUEDRAOGO',
            'prenom'    => 'Fatimata',
            'password'  => bcrypt('password'),
            'is_active' => true,
        ]);
        $resp->assignRole('responsable_dcirp');

        $part = User::firstOrCreate(['email' => 'participant@test.bf'], [
            'name'      => 'Issouf Somé',
            'nom'       => 'SOME',
            'prenom'    => 'Issouf',
            'telephone' => '70000002',
            'password'  => bcrypt('password'),
            'is_active' => true,
        ]);
        $part->assignRole('participant');

        // 3️⃣ — Événements (un par type)
        $types = TypeEvenement::all()->keyBy('code');

        // Conférence
        if ($types->has('CONF')) {
            $ev = Evenement::firstOrCreate(
                ['titre' => 'Conférence RSE 2025 — Numérique en Afrique'],
                [
                    'description'       => 'Conférence sur l\'impact social du numérique.',
                    'type_evenement_id' => $types['CONF']->id,
                    'date_debut'        => now()->addDays(15),
                    'date_fin'          => now()->addDays(15)->addHours(4),
                    'lieu_id'           => Lieu::where('nom', 'Hôtel Laïco')->first()->id,
                    'statut'            => 'publie',
                    'budget_prev'       => 3000000,
                    'created_by'        => $orga->id,
                ]
            );
            Tarif::firstOrCreate(
                ['evenement_id' => $ev->id, 'nom' => 'Standard'],
                ['montant' => 0]
            );
        }

        // Hackathon
        if ($types->has('HACK')) {
            $ev = Evenement::firstOrCreate(
                ['titre' => 'Hackathon Moov 2025 — 48h pour Innover'],
                [
                    'description'       => '48h de développement intensif. Thème : fintech rurale.',
                    'type_evenement_id' => $types['HACK']->id,
                    'date_debut'        => now()->addDays(30),
                    'date_fin'          => now()->addDays(32),
                    'lieu_id'           => Lieu::first()->id,
                    'statut'            => 'publie',
                    'budget_prev'       => 6000000,
                    'created_by'        => $orga->id,
                ]
            );
            Tarif::firstOrCreate(
                ['evenement_id' => $ev->id, 'nom' => 'Inscription équipe'],
                ['montant' => 0]
            );
        }

        // Sport (Holiday Foot)
        if ($types->has('SPORT')) {
            $ev = Evenement::firstOrCreate(
                ['titre' => 'Moov Holiday Foot 2025'],
                [
                    'description'       => 'Tournoi inter-équipes, 16 équipes, 5 jours.',
                    'type_evenement_id' => $types['SPORT']->id,
                    'date_debut'        => now()->addDays(45),
                    'date_fin'          => now()->addDays(50),
                    'lieu_id'           => Lieu::where('nom', 'Stade du 4 Août')->first()->id,
                    'statut'            => 'publie',
                    'budget_prev'       => 8000000,
                    'created_by'        => $orga->id,
                ]
            );
            Tarif::firstOrCreate(
                ['evenement_id' => $ev->id, 'nom' => 'Inscription équipe'],
                ['montant' => 50000]
            );
        }

        // Salon (Moov participe au SIAO)
        if ($types->has('SALON')) {
            Evenement::firstOrCreate(
                ['titre' => 'SIAO 2025 — Stand Moov Africa'],
                [
                    'description'          => 'Moov Africa participe au Salon International de l\'Artisanat de Ouagadougou.',
                    'type_evenement_id'    => $types['SALON']->id,
                    'date_debut'           => now()->addDays(7),
                    'date_fin'             => now()->addDays(14),
                    'lieu_id'              => Lieu::where('nom', 'Parc SIAO')->first()->id,
                    'statut'               => 'publie',
                    'budget_prev'          => 3500000,
                    'created_by'           => $orga->id,
                    'nom_salon_hote'       => 'SIAO 2025 — 18ème édition',
                    'organisateur_externe' => 'Ministère du Commerce',
                    'lieu_stand'           => 'Hall B — Stand 42',
                    'superficie_stand'     => 36,
                    'objectifs_stand'      => 'Promouvoir Moov Money, collecter 300 prospects.',
                    'objectif_prospects'   => 300,
                ]
            );
        }

        // Formation
        if ($types->has('FORMATION')) {
            $ev = Evenement::firstOrCreate(
                ['titre' => 'Formation IA & Data — Niveau Débutant'],
                [
                    'description'       => 'Formation pratique sur l\'Intelligence Artificielle et l\'analyse de données.',
                    'type_evenement_id' => $types['FORMATION']->id,
                    'date_debut'        => now()->addDays(10),
                    'date_fin'          => now()->addDays(11),
                    'lieu_id'           => Lieu::first()->id,
                    'statut'            => 'publie',
                    'budget_prev'       => 2500000,
                    'created_by'        => $orga->id,
                ]
            );
            Tarif::firstOrCreate(
                ['evenement_id' => $ev->id, 'nom' => 'Tarif standard'],
                ['montant' => 15000]
            );
        }

        echo "\n✅ Données de test créées avec succès !\n";
        echo "📧 Comptes test :\n";
        echo "  - admin@moov.bf       → Admin       (mdp: password)\n";
        echo "  - resp@moov.bf        → Responsable (mdp: password)\n";
        echo "  - orga@moov.bf        → Organisateur (mdp: password)\n";
        echo "  - participant@test.bf → Participant (mdp: password)\n\n";
    }
}