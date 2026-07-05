<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Présentation - {{ $evenement->titre }}</title>
    <style @nonce>
        body { margin: 0; font-family: Arial, sans-serif; background: #f8fafc; color: #0f172a; }
        .slide { min-height: 100vh; padding: 56px; box-sizing: border-box; page-break-after: always; }
        .hero { background: linear-gradient(135deg, #0066b3, #00a651); color: white; }
        .cards { display: grid; grid-template-columns: repeat(2, 1fr); gap: 20px; margin-top: 24px; }
        .card { background: white; color: #0f172a; border-radius: 18px; padding: 24px; box-shadow: 0 12px 32px rgba(15, 23, 42, 0.08); }
        h1 { margin-top: 0; font-size: 42px; }
        h2 { font-size: 28px; }
        .metric { font-size: 30px; font-weight: bold; margin-top: 10px; color: #0066b3; }
    </style>
</head>
<body>
    <section class="slide hero">
        <h1>{{ $evenement->titre }}</h1>
        <p>Présentation synthétique générée automatiquement pour le comité de pilotage.</p>
    </section>

    <section class="slide">
        <h2>Participation</h2>
        <div class="cards">
            <div class="card"><strong>Inscrits</strong><div class="metric">{{ $stats['participation']['inscrits'] }}</div></div>
            <div class="card"><strong>Taux de présence</strong><div class="metric">{{ number_format((float) $stats['participation']['taux_presence'], 1, ',', ' ') }}%</div></div>
            <div class="card"><strong>Présents</strong><div class="metric">{{ $stats['participation']['presents'] }}</div></div>
            <div class="card"><strong>Absents</strong><div class="metric">{{ $stats['participation']['absents'] }}</div></div>
        </div>
    </section>

    <section class="slide">
        <h2>Finance</h2>
        <div class="cards">
            <div class="card"><strong>Budget prévisionnel</strong><div class="metric">{{ number_format((float) $stats['financier']['budget_previsionnel'], 0, ',', ' ') }} XOF</div></div>
            <div class="card"><strong>Recettes</strong><div class="metric">{{ number_format((float) $stats['financier']['recettes_total'], 0, ',', ' ') }} XOF</div></div>
            <div class="card"><strong>Dépenses</strong><div class="metric">{{ number_format((float) $stats['financier']['depenses'], 0, ',', ' ') }} XOF</div></div>
            <div class="card"><strong>Solde</strong><div class="metric">{{ number_format((float) $stats['financier']['solde'], 0, ',', ' ') }} XOF</div></div>
        </div>
    </section>

    <section class="slide">
        <h2>RSE</h2>
        <div class="cards">
            <div class="card"><strong>Score global</strong><div class="metric">{{ number_format((float) $stats['rse']['score_global'], 1, ',', ' ') }}%</div></div>
            <div class="card"><strong>Bénéficiaires directs</strong><div class="metric">{{ $stats['rse']['social']['nb_beneficiaires_directs'] }}</div></div>
            <div class="card"><strong>Score environnemental</strong><div class="metric">{{ number_format((float) $stats['rse']['environmental']['score_environnemental'], 1, ',', ' ') }}%</div></div>
            <div class="card"><strong>Emplois créés</strong><div class="metric">{{ $stats['rse']['economic']['nb_emplois_crees'] }}</div></div>
        </div>
    </section>
</body>
</html>