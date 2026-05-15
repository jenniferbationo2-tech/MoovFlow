<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Rapport Impact RSE - Moov Africa Burkina</title>
    <style>
        @page { margin: 1.5cm; }
        body {
            font-family: DejaVu Sans, sans-serif;
            color: #1E293B;
            font-size: 11pt;
            line-height: 1.4;
        }
        .header {
            border-bottom: 3px solid #1B4A8B;
            padding-bottom: 15px;
            margin-bottom: 25px;
        }
        .header h1 {
            margin: 0;
            color: #1B4A8B;
            font-size: 22pt;
        }
        .header .subtitle {
            color: #FF8000;
            font-size: 10pt;
            text-transform: uppercase;
            letter-spacing: 2px;
            margin-top: 5px;
        }
        .header .meta {
            color: #64748B;
            font-size: 9pt;
            margin-top: 8px;
        }
        h2 {
            color: #1B4A8B;
            font-size: 14pt;
            border-bottom: 2px solid #E2E8F0;
            padding-bottom: 5px;
            margin-top: 25px;
            margin-bottom: 15px;
        }
        .kpi-grid {
            display: table;
            width: 100%;
            margin-bottom: 20px;
        }
        .kpi-row { display: table-row; }
        .kpi-card {
            display: table-cell;
            width: 25%;
            padding: 12px;
            border: 1px solid #E2E8F0;
            border-radius: 4px;
            vertical-align: top;
            text-align: center;
        }
        .kpi-label {
            font-size: 8pt;
            color: #64748B;
            text-transform: uppercase;
            letter-spacing: 1px;
        }
        .kpi-value {
            font-size: 24pt;
            font-weight: bold;
            color: #1B4A8B;
            margin: 5px 0;
        }
        .kpi-sub {
            font-size: 8pt;
            color: #94A3B8;
        }
        table.events {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
        }
        table.events th {
            background-color: #1B4A8B;
            color: white;
            padding: 8px;
            text-align: left;
            font-size: 9pt;
            text-transform: uppercase;
        }
        table.events td {
            padding: 8px;
            border-bottom: 1px solid #E2E8F0;
            font-size: 10pt;
        }
        table.events tr:nth-child(even) td {
            background-color: #F8FAFC;
        }
        .footer {
            position: fixed;
            bottom: 0;
            left: 0;
            right: 0;
            text-align: center;
            font-size: 8pt;
            color: #94A3B8;
            border-top: 1px solid #E2E8F0;
            padding-top: 8px;
        }
        .badge {
            display: inline-block;
            padding: 2px 8px;
            border-radius: 3px;
            font-size: 8pt;
            font-weight: bold;
        }
        .text-orange { color: #FF8000; }
        .text-blue { color: #1B4A8B; }
    </style>
</head>
<body>

    <!-- ── EN-TÊTE ── -->
    <div class="header">
        <h1>Rapport d'Impact Social</h1>
        <div class="subtitle">Moov Africa Burkina · dCIRP · MoovEvents</div>
        <div class="meta">
            Généré le {{ $date_generation }}
            @if($genere_par)
                par {{ $genere_par->prenom }} {{ $genere_par->nom }}
            @endif
        </div>
    </div>

    <!-- ── KPIs PRINCIPAUX ── -->
    <h2>Indicateurs clés</h2>
    <div class="kpi-grid">
        <div class="kpi-row">
            <div class="kpi-card">
                <div class="kpi-label">Bénéficiaires directs</div>
                <div class="kpi-value">{{ number_format($kpis['beneficiaires_directs'], 0, ',', ' ') }}</div>
                <div class="kpi-sub">Personnes touchées</div>
            </div>
            <div class="kpi-card">
                <div class="kpi-label">Femmes bénéficiaires</div>
                <div class="kpi-value">{{ $kpis['taux_femmes'] }}%</div>
                <div class="kpi-sub">{{ number_format($kpis['femmes'], 0, ',', ' ') }} personnes</div>
            </div>
            <div class="kpi-card">
                <div class="kpi-label">Associations</div>
                <div class="kpi-value">{{ $kpis['associations'] }}</div>
                <div class="kpi-sub">soutenues</div>
            </div>
            <div class="kpi-card">
                <div class="kpi-label">Emplois créés</div>
                <div class="kpi-value">{{ $kpis['emplois'] }}</div>
                <div class="kpi-sub">Impact économique</div>
            </div>
        </div>
    </div>

    <!-- ── PERFORMANCE PAR TYPE ── -->
    <h2>Performance par type d'événement</h2>
    <table class="events">
        <thead>
            <tr>
                <th>Type d'événement</th>
                <th style="text-align: center;">Nb événements</th>
                <th style="text-align: center;">Nb inscriptions</th>
            </tr>
        </thead>
        <tbody>
            @foreach($performanceTypes as $pt)
                <tr>
                    <td><strong>{{ $pt->type_nom }}</strong></td>
                    <td style="text-align: center;">{{ $pt->nb_evenements }}</td>
                    <td style="text-align: center;">{{ $pt->nb_inscriptions }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <!-- ── DÉTAIL DES ÉVÉNEMENTS ── -->
    <h2>Détail des événements à impact</h2>
    <table class="events">
        <thead>
            <tr>
                <th>Événement</th>
                <th>Type</th>
                <th>Date</th>
                <th style="text-align: center;">Bénéficiaires</th>
            </tr>
        </thead>
        <tbody>
            @foreach($evenements as $ev)
                @php $rse = $ev->objectifsRse->first(); @endphp
                <tr>
                    <td><strong>{{ $ev->titre }}</strong></td>
                    <td>{{ $ev->typeEvenement?->nom ?? '—' }}</td>
                    <td>{{ optional($ev->date_debut)?->format('d/m/Y') ?? '—' }}</td>
                    <td style="text-align: center;">
                        <strong class="text-blue">{{ $rse?->nb_beneficiaires_directs ?? 0 }}</strong>
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <!-- ── FOOTER ── -->
    <div class="footer">
        Moov Africa Burkina · Plateforme MoovFlow · Document confidentiel
    </div>

</body>
</html>