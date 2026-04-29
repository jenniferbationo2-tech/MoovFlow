<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Rapport RSE - {{ $evenement->titre }}</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; color: #0f172a; margin: 28px; font-size: 14px; }
        h1 { color: #0066b3; margin-bottom: 6px; }
        .muted { color: #64748b; margin-bottom: 20px; }
        .section { border: 1px solid #dbe3ef; border-radius: 12px; padding: 16px; margin-bottom: 16px; }
        .score { font-size: 26px; font-weight: bold; color: #00a651; }
        ul { padding-left: 18px; }
    </style>
</head>
<body>
    <h1>Rapport RSE</h1>
    <p class="muted">{{ $evenement->titre }} · édité le {{ now()->format('d/m/Y H:i') }}</p>

    <div class="section">
        <strong>Score global RSE</strong>
        <div class="score">{{ number_format((float) $stats['score_global'], 1, ',', ' ') }}%</div>
    </div>

    <div class="section">
        <strong>Impact social</strong>
        <ul>
            <li>Bénéficiaires directs : {{ $stats['social']['nb_beneficiaires_directs'] }}</li>
            <li>Bénéficiaires indirects : {{ $stats['social']['nb_beneficiaires_indirects'] }}</li>
            <li>Associations soutenues : {{ $stats['social']['nb_associations'] }}</li>
            <li>Projets accompagnés : {{ $stats['social']['nb_projets'] }}</li>
            <li>Taux femmes bénéficiaires : {{ number_format((float) $stats['social']['taux_femmes'], 1, ',', ' ') }}%</li>
        </ul>
    </div>

    <div class="section">
        <strong>Impact environnemental</strong>
        <p>Score environnemental : {{ number_format((float) $stats['environmental']['score_environnemental'], 1, ',', ' ') }}%</p>
    </div>

    <div class="section">
        <strong>Impact économique</strong>
        <ul>
            <li>Montants collectés : {{ number_format((float) $stats['economic']['montants_collectes'], 0, ',', ' ') }} XOF</li>
            <li>Retombées partenaires : {{ number_format((float) $stats['economic']['retombees_partenaires'], 0, ',', ' ') }} XOF</li>
            <li>Emplois créés : {{ $stats['economic']['nb_emplois_crees'] }}</li>
        </ul>
    </div>
</body>
</html>