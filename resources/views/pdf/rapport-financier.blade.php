<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Rapport financier - {{ $evenement->titre }}</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; color: #0f172a; margin: 28px; font-size: 14px; }
        h1 { color: #0066b3; margin-bottom: 6px; }
        .muted { color: #64748b; margin-bottom: 20px; }
        .grid { width: 100%; border-collapse: separate; border-spacing: 12px; margin-bottom: 24px; }
        .grid td { border: 1px solid #dbe3ef; border-radius: 12px; padding: 14px; vertical-align: top; }
        .table { width: 100%; border-collapse: collapse; }
        .table th, .table td { border: 1px solid #dbe3ef; padding: 10px; text-align: left; }
        .table th { background: #eff6ff; }
        .danger { color: #dc2626; font-weight: bold; }
    </style>
</head>
<body>
    <h1>Rapport financier</h1>
    <p class="muted">{{ $evenement->titre }} · édité le {{ now()->format('d/m/Y H:i') }}</p>

    <table class="grid">
        <tr>
            <td><strong>Budget prévisionnel</strong><br>{{ number_format((float) $stats['budget_previsionnel'], 0, ',', ' ') }} XOF</td>
            <td><strong>Recettes</strong><br>{{ number_format((float) $stats['recettes_total'], 0, ',', ' ') }} XOF</td>
            <td><strong>Dépenses</strong><br>{{ number_format((float) $stats['depenses'], 0, ',', ' ') }} XOF</td>
            <td><strong>Solde</strong><br>{{ number_format((float) $stats['solde'], 0, ',', ' ') }} XOF</td>
        </tr>
    </table>

    @if($stats['depassement'])
        <p class="danger">Alerte : le budget prévisionnel est dépassé.</p>
    @endif

    <table class="table">
        <thead>
            <tr>
                <th>Libellé</th>
                <th>Type</th>
                <th>Montant</th>
            </tr>
        </thead>
        <tbody>
            @forelse($stats['budget']['lignes'] as $ligne)
                <tr>
                    <td>{{ $ligne['libelle'] }}</td>
                    <td>{{ ucfirst($ligne['type']) }}</td>
                    <td>{{ number_format((float) $ligne['montant'], 0, ',', ' ') }} XOF</td>
                </tr>
            @empty
                <tr>
                    <td colspan="3">Aucune ligne budgétaire.</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</body>
</html>