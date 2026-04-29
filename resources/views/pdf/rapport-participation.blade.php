<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Rapport participation - {{ $evenement->titre }}</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; color: #0f172a; margin: 28px; font-size: 14px; }
        h1 { color: #0066b3; margin-bottom: 6px; }
        .muted { color: #64748b; margin-bottom: 20px; }
        .stats { width: 100%; border-collapse: separate; border-spacing: 12px; margin-bottom: 24px; }
        .stats td { border: 1px solid #dbe3ef; border-radius: 12px; padding: 14px; vertical-align: top; }
        .table { width: 100%; border-collapse: collapse; }
        .table th, .table td { border: 1px solid #dbe3ef; padding: 10px; text-align: left; }
        .table th { background: #eff6ff; }
    </style>
</head>
<body>
    <h1>Rapport de participation</h1>
    <p class="muted">{{ $evenement->titre }} · édité le {{ now()->format('d/m/Y H:i') }}</p>

    <table class="stats">
        <tr>
            <td><strong>Inscrits</strong><br>{{ $stats['inscrits'] }}</td>
            <td><strong>Présents</strong><br>{{ $stats['presents'] }}</td>
            <td><strong>Absents</strong><br>{{ $stats['absents'] }}</td>
            <td><strong>Taux de présence</strong><br>{{ number_format((float) $stats['taux_presence'], 1, ',', ' ') }}%</td>
        </tr>
    </table>

    <table class="table">
        <thead>
            <tr>
                <th>Nom</th>
                <th>Email</th>
                <th>Téléphone</th>
                <th>Inscription</th>
                <th>Présence</th>
            </tr>
        </thead>
        <tbody>
            @forelse($stats['participants'] as $participant)
                <tr>
                    <td>{{ $participant['nom'] }}</td>
                    <td>{{ $participant['email'] }}</td>
                    <td>{{ $participant['telephone'] }}</td>
                    <td>{{ ucfirst($participant['statut_inscription']) }}</td>
                    <td>{{ $participant['presence'] === 'present' ? 'Présent' : 'Absent' }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="5">Aucun participant disponible.</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</body>
</html>