<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Certificat {{ $numero }}</title>
    <style>
        @page {
            margin: 0;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Helvetica', Arial, sans-serif;
            color: #0F172A;
        }

        .page {
            width: 100%;
    height: 555px; /* A4 paysage -50px de marge */
    padding: 50px 80px;
    position: relative;
    background: #fff;
    overflow: hidden;
        }

        /* Bordure décorative */
       .border-decoration {
    position: absolute;
    top: 25px;
    left: 25px;
    right: 25px;
    bottom: 25px;
    border: 3px solid #1B4A8B;
    border-radius: 8px;
}

.border-decoration-inner {
    position: absolute;
    top: 35px;
    left: 35px;
    right: 35px;
    bottom: 35px;
    border: 1px solid #FF8000;
    border-radius: 6px;
}
        .content {
            position: relative;
            z-index: 2;
            text-align: center;
        }

        /* Header */
       .header {
    margin-top: 10px;
    margin-bottom: 20px;
}

.logo {
    max-width: 80px;
    max-height: 80px;
    margin-bottom: 8px;
}

        .org-name {
            font-size: 14px;
            color: #1B4A8B;
            font-weight: bold;
            letter-spacing: 4px;
            text-transform: uppercase;
        }

        /* Titre principal */
       .title {
    font-size: 38px;
    font-weight: 900;
    color: #1B4A8B;
    margin: 15px 0 5px;
    letter-spacing: 2px;
    text-transform: uppercase;
}
        .subtitle {
            font-size: 14px;
            color: #FF8000;
            font-weight: bold;
            letter-spacing: 6px;
            text-transform: uppercase;
            margin-bottom: 30px;
        }

       .divider {
    width: 80px;
    height: 3px;
    background: #FF8000;
    margin: 0 auto 20px;
}

       .intro {
    font-size: 12px;
    color: #475569;
    font-style: italic;
    line-height: 1.5;
    margin: 0 80px 15px;
}

        .name {
    font-size: 30px;
    font-weight: 900;
    color: #0F172A;
    margin: 15px 0 8px;
    text-transform: uppercase;
    letter-spacing: 3px;
}

.name-underline {
    width: 300px;
    height: 2px;
    background: #1B4A8B;
    margin: 0 auto 15px;
}

        .body-text {
            font-size: 14px;
            color: #475569;
            margin-bottom: 15px;
            line-height: 1.5;
        }

       .event-name {
    font-size: 18px;
    font-weight: bold;
    color: #1B4A8B;
    font-style: italic;
    margin: 10px 0;
    padding: 0 60px;
}

        .event-details {
            font-size: 13px;
            color: #64748B;
            margin-top: 10px;
        }

        /* Footer */
        .footer {
    position: absolute;
    bottom: 60px;
    left: 80px;
    right: 80px;
    display: table;
    width: calc(100% - 160px);
}

        .footer-left, .footer-right {
            display: table-cell;
            vertical-align: bottom;
            width: 50%;
        }

        .footer-left {
            text-align: left;
            font-size: 10px;
            color: #94A3B8;
        }

        .footer-right {
            text-align: right;
        }

        .signature-line {
            border-top: 1px solid #1B4A8B;
            width: 200px;
            margin-left: auto;
            padding-top: 5px;
        }

        .signature-text {
            font-size: 10px;
            color: #475569;
            font-weight: bold;
        }

        .footer-info {
            margin-top: 4px;
        }

        .footer-info strong {
            color: #1B4A8B;
        }
    </style>
</head>
<body>

<div class="page">

    <div class="border-decoration"></div>
    <div class="border-decoration-inner"></div>

    <div class="content">

        <!-- ─── HEADER ─── -->
        <div class="header">
            @if($logo_base64)
                <img src="{{ $logo_base64 }}" alt="Logo" class="logo">
            @endif
            <p class="org-name">Moov Africa · {{ $pays }}</p>
        </div>

        <!-- ─── TITRE ─── -->
        <h1 class="title">Certificat</h1>
        <p class="subtitle">de Participation</p>

        <div class="divider"></div>

        <!-- ─── CORPS ─── -->
        <p class="intro">
            La Direction de la Communication Institutionnelle<br>
            et des Relations Publiques de Moov Africa Burkina certifie que
        </p>

        <h2 class="name">{{ $participant['prenom'] }} {{ $participant['nom'] }}</h2>
        <div class="name-underline"></div>

        <p class="body-text">a participé à l'événement suivant :</p>

        <p class="event-name">« {{ $evenement['titre'] }} »</p>

        <p class="event-details">
            organisé le <strong>{{ \Carbon\Carbon::parse($evenement['date_debut'])->locale('fr')->isoFormat('LL') }}</strong>
            @if($evenement['lieu'])
                à <strong>{{ $evenement['lieu'] }}</strong>
            @endif
        </p>

    </div>

    <div class="footer">
        <div class="footer-left">
            <p class="footer-info"><strong>Référence :</strong> {{ $numero }}</p>
            <p class="footer-info"><strong>Délivré le :</strong> {{ $date_generation->locale('fr')->isoFormat('LL') }}</p>
        </div>
        <div class="footer-right">
            <div class="signature-line">
                <p class="signature-text">Direction dCIRP</p>
                <p style="font-size: 9px; color: #94A3B8;">Moov Africa {{ $pays }}</p>
            </div>
        </div>
    </div>

</div>

</body>
</html>