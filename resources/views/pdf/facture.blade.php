<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Facture {{ $facture->numero_facture }}</title>
    <style @nonce>
        body {
            font-family: DejaVu Sans, sans-serif;
            color: #1e293b;
            font-size: 14px;
            margin: 0;
            padding: 32px;
        }

        .header {
            display: table;
            width: 100%;
            margin-bottom: 28px;
        }

        .brand,
        .meta {
            display: table-cell;
            vertical-align: top;
        }

        .meta {
            text-align: right;
        }

        .logo {
            display: inline-block;
            padding: 10px 16px;
            background: #0066b3;
            color: #ffffff;
            font-weight: bold;
            font-size: 20px;
            border-radius: 10px;
        }

        h1 {
            font-size: 24px;
            margin: 18px 0 8px;
        }

        .subtitle {
            color: #64748b;
            margin: 0;
        }

        .card {
            border: 1px solid #dbe3ef;
            border-radius: 12px;
            padding: 18px;
            margin-top: 18px;
        }

        .grid {
            width: 100%;
            border-collapse: collapse;
            margin-top: 18px;
        }

        .grid th,
        .grid td {
            border: 1px solid #dbe3ef;
            padding: 12px;
            text-align: left;
        }

        .grid th {
            background: #eff6ff;
            color: #0f172a;
        }

        .total {
            text-align: right;
            margin-top: 18px;
            font-size: 18px;
            font-weight: bold;
            color: #0066b3;
        }

        .footer {
            margin-top: 36px;
            color: #64748b;
            font-size: 12px;
        }
    </style>
</head>
<body>
    <div class="header">
        <div class="brand">
<div class="logo">MoovFlow</div>
            <h1>Facture</h1>
            <p class="subtitle">Confirmation de paiement evenementiel</p>
        </div>
        <div class="meta">
            <p><strong>Numero :</strong> {{ $facture->numero_facture }}</p>
            <p><strong>Date :</strong> {{ optional($facture->created_at)->format('d/m/Y H:i') }}</p>
            <p><strong>Reference :</strong> {{ $paiement->reference_transaction ?? 'N/A' }}</p>
        </div>
    </div>

    <div class="card">
        <strong>Participant</strong>
        <p>{{ $participant->name }}</p>
        <p>{{ $participant->email }}</p>
        <p>{{ $participant->telephone ?? 'Telephone non renseigne' }}</p>
    </div>

    <div class="card">
        <strong>Evenement</strong>
        <p>{{ $evenement->titre }}</p>
        <p>
            @if($evenement->date_debut)
                Debut : {{ $evenement->date_debut->format('d/m/Y H:i') }}
            @endif
            @if($evenement->date_fin)
                - Fin : {{ $evenement->date_fin->format('d/m/Y H:i') }}
            @endif
        </p>
    </div>

    <table class="grid">
        <thead>
            <tr>
                <th>Designation</th>
                <th>Tarif</th>
                <th>Mode</th>
                <th>Montant</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td>Inscription evenement</td>
                <td>{{ $tarif?->nom ?? 'Gratuit' }}</td>
                <td>{{ strtoupper(str_replace('_', ' ', $paiement->mode)) }}</td>
                <td>{{ number_format((float) $paiement->montant, 0, ',', ' ') }} XOF</td>
            </tr>
        </tbody>
    </table>

    <div class="total">
        Total : {{ number_format((float) $paiement->montant, 0, ',', ' ') }} XOF
    </div>

    <div class="footer">
Merci d avoir choisi MoovFlow. Cette facture est generee automatiquement.
    </div>
</body>
</html>