<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Liste des participants - {{ $evenement->titre }}</title>
    <style @nonce>
        @page {
            margin: 80px 30px 60px 30px;
        }

        body {
            font-family: 'DejaVu Sans', sans-serif;
            font-size: 11px;
            color: #1e293b;
            margin: 0;
            padding: 0;
        }

        /* HEADER */
        .header {
            position: fixed;
            top: -60px;
            left: 0;
            right: 0;
            border-bottom: 3px solid #1B4A8B;
            padding-bottom: 10px;
        }
        .header-table {
            width: 100%;
            border: 0;
        }
        .header-table td {
            vertical-align: top;
            padding: 0;
        }
        .header-logo {
            color: #FF8000;
            font-size: 20px;
            font-weight: bold;
            letter-spacing: -1px;
        }
        .header-subtitle {
            color: #64748b;
            font-size: 9px;
            margin-top: 2px;
        }
        .header-right {
            text-align: right;
        }
        .header-right .title {
            color: #1B4A8B;
            font-size: 14px;
            font-weight: bold;
        }
        .header-right .subtitle {
            color: #64748b;
            font-size: 9px;
            margin-top: 2px;
        }

        /* FOOTER */
        .footer {
            position: fixed;
            bottom: -40px;
            left: 0;
            right: 0;
            border-top: 1px solid #e2e8f0;
            padding-top: 8px;
            font-size: 9px;
            color: #94a3b8;
        }
        .footer-table {
            width: 100%;
            border: 0;
        }
        .footer-table td {
            padding: 0;
        }
        .footer .right {
            text-align: right;
        }

        /* CONTENT */
        .event-info {
            background: #f8fafc;
            border-left: 4px solid #1B4A8B;
            padding: 15px;
            margin-bottom: 20px;
        }
        .event-info h1 {
            color: #1B4A8B;
            font-size: 16px;
            margin: 0 0 6px 0;
        }
        .event-info .meta {
            color: #475569;
            font-size: 10px;
        }
        .event-info .meta span {
            margin-right: 15px;
        }
        .event-info .badge {
            display: inline-block;
            background: #1B4A8B;
            color: white;
            padding: 3px 8px;
            border-radius: 3px;
            font-size: 9px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-top: 5px;
        }

        /* TABLE */
        table.participants {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
        }
        table.participants thead {
            background: #1B4A8B;
            color: white;
        }
        table.participants thead th {
            padding: 10px 8px;
            text-align: left;
            font-size: 10px;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        table.participants tbody tr {
            border-bottom: 1px solid #e2e8f0;
        }
        table.participants tbody tr:nth-child(even) {
            background: #f8fafc;
        }
        table.participants tbody td {
            padding: 8px;
            font-size: 10px;
        }
        table.participants .num {
            color: #94a3b8;
            font-weight: bold;
            width: 30px;
        }
        table.participants .ref {
            font-family: 'Courier New', monospace;
            color: #1B4A8B;
            font-weight: bold;
            width: 100px;
        }
        table.participants .name {
            font-weight: bold;
        }
        table.participants .email {
            color: #64748b;
            font-size: 9px;
        }
        table.participants .statut {
            text-align: center;
            width: 80px;
        }
        .badge-statut {
            padding: 2px 6px;
            border-radius: 3px;
            font-size: 8px;
            font-weight: bold;
            text-transform: uppercase;
        }
        .statut-confirmee, .statut-acceptee {
            background: #d1fae5;
            color: #065f46;
        }
        .statut-present {
            background: #a7f3d0;
            color: #064e3b;
        }
        .statut-preinscrit {
            background: #fef3c7;
            color: #92400e;
        }
        .statut-refusee, .statut-annulee {
            background: #fee2e2;
            color: #991b1b;
        }

        /* SUMMARY */
        .summary {
            margin-top: 15px;
            text-align: right;
            font-size: 11px;
            color: #475569;
        }
        .summary strong {
            color: #1B4A8B;
            font-size: 14px;
        }

        /* EMPTY STATE */
        .empty {
            text-align: center;
            padding: 30px;
            color: #94a3b8;
            font-style: italic;
        }
    </style>
</head>
<body>

    <!-- HEADER (fixe) -->
    <div class="header">
        <table class="header-table">
            <tr>
                <td>
                    <div class="header-logo">MOOV AFRICA</div>
                    <div class="header-subtitle">Burkina Faso · dCIRP</div>
                </td>
                <td class="header-right">
                    <div class="title">Liste des participants</div>
                    <div class="subtitle">Document officiel</div>
                </td>
            </tr>
        </table>
    </div>

    <!-- FOOTER (fixe) -->
    <div class="footer">
        <table class="footer-table">
            <tr>
                <td>
                    Généré le {{ $dateGeneration }}
                </td>
                <td class="right">
                    Moov Africa Burkina · dCIRP · MoovFlow
                </td>
            </tr>
        </table>
    </div>

    <!-- CONTENT -->
    <div class="event-info">
        <h1>{{ $evenement->titre }}</h1>
        <div class="meta">
            <span><strong>📅</strong> {{ $evenement->date_debut?->format('d/m/Y') }}</span>
            @if($evenement->lieu)
                <span><strong>📍</strong> {{ $evenement->lieu->nom }}</span>
            @endif
            @if($evenement->typeEvenement)
                <span><strong>Type :</strong> {{ $evenement->typeEvenement->nom }}</span>
            @endif
        </div>
        <span class="badge">{{ count($participants) }} participants</span>
    </div>

    @if(count($participants) > 0)
        <table class="participants">
            <thead>
                <tr>
                    <th class="num">N°</th>
                    <th>Référence QR</th>
                    <th>Nom complet</th>
                    <th>Contact</th>
                    <th class="statut">Statut</th>
                </tr>
            </thead>
            <tbody>
                @foreach($participants as $index => $p)
                    <tr>
                        <td class="num">{{ $index + 1 }}</td>
                        <td class="ref">{{ $p['qr_code'] ?? '—' }}</td>
                        <td>
                            <div class="name">{{ strtoupper($p['nom'] ?? '') }} {{ $p['prenom'] ?? '' }}</div>
                            <div class="email">{{ $p['email'] ?? '' }}</div>
                        </td>
                        <td>
                            @if(!empty($p['telephone']))
                                {{ $p['telephone'] }}
                            @else
                                <em style="color: #cbd5e1;">Non renseigné</em>
                            @endif
                        </td>
                        <td class="statut">
                            <span class="badge-statut statut-{{ $p['statut'] }}">
                                {{ $p['statut_label'] }}
                            </span>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>

        <div class="summary">
            Total : <strong>{{ count($participants) }}</strong> participant{{ count($participants) > 1 ? 's' : '' }}
        </div>
    @else
        <div class="empty">
            <p>Aucun participant inscrit pour cet événement.</p>
        </div>
    @endif

</body>
</html>