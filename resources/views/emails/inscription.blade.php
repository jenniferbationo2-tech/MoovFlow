<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>MoovFlow</title>
</head>
<body style="margin:0;padding:0;font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Helvetica,Arial,sans-serif;background-color:#F4F6FA;">

    <table role="presentation" cellspacing="0" cellpadding="0" border="0" width="100%" style="background-color:#F4F6FA;padding:30px 0;">
        <tr>
            <td align="center">

                <!-- ────────── CONTAINER ────────── -->
                <table role="presentation" cellspacing="0" cellpadding="0" border="0" width="600"
                       style="background-color:#ffffff;border-radius:16px;overflow:hidden;box-shadow:0 4px 20px rgba(0,0,0,0.08);">

                    <!-- ── EN-TÊTE BLEU MOOV ── -->
                    <tr>
                        <td style="background:linear-gradient(135deg,#1B4A8B 0%,#0F3265 100%);padding:30px 40px;text-align:center;">
                            <h1 style="margin:0;color:#ffffff;font-size:24px;font-weight:800;letter-spacing:-0.5px;">
                                Moov<span style="color:#FF8000;">Flow</span>
                            </h1>
                            <p style="margin:6px 0 0;color:rgba(255,255,255,0.7);font-size:11px;text-transform:uppercase;letter-spacing:2px;">
                                Plateforme officielle Moov Africa Burkina · dCIRP
                            </p>
                        </td>
                    </tr>

                    <!-- ── CORPS DU MESSAGE ── -->
                    <tr>
                        <td style="padding:40px;">

                            <p style="margin:0 0 8px;color:#64748B;font-size:12px;text-transform:uppercase;letter-spacing:1.5px;font-weight:600;">
                                Bonjour {{ $user?->prenom ?? 'cher participant' }},
                            </p>

                            {{-- ════════════ TYPE : preinscription_recue ════════════ --}}
                            @if ($type === 'preinscription_recue')
                                <h2 style="margin:0 0 20px;color:#0F172A;font-size:24px;font-weight:800;">
                                    Votre pré-inscription est bien reçue 
                                </h2>
                                <p style="margin:0 0 16px;color:#475569;font-size:15px;line-height:1.6;">
                                    Nous avons bien reçu votre pré-inscription pour <strong>{{ $evenement?->titre }}</strong>.
                                </p>
                                <p style="margin:0 0 16px;color:#475569;font-size:15px;line-height:1.6;">
                                    Notre équipe va examiner votre demande dans les prochains jours. Vous recevrez
                                    une notification dès que nous aurons étudié votre dossier.
                                </p>

                            {{-- ════════════ TYPE : preselectionne ════════════ --}}
                            @elseif ($type === 'preselectionne')
                                <h2 style="margin:0 0 20px;color:#0F172A;font-size:24px;font-weight:800;">
                                     Félicitations, vous êtes présélectionné(e) !
                                </h2>
                                <p style="margin:0 0 16px;color:#475569;font-size:15px;line-height:1.6;">
                                    Votre pré-inscription pour <strong>{{ $evenement?->titre }}</strong> a retenu notre attention !
                                </p>
                                <p style="margin:0 0 20px;color:#475569;font-size:15px;line-height:1.6;">
                                    Pour finaliser votre inscription, merci de compléter votre dossier complet en cliquant sur le bouton ci-dessous :
                                </p>

                                @if (!empty($donnees['lien_dossier']))
                                    <table role="presentation" cellspacing="0" cellpadding="0" border="0" width="100%">
                                        <tr>
                                            <td align="center" style="padding:20px 0;">
                                                <a href="{{ $donnees['lien_dossier'] }}"
                                                   style="background-color:#FF8000;color:#ffffff;padding:14px 32px;border-radius:10px;text-decoration:none;font-weight:700;font-size:14px;display:inline-block;text-transform:uppercase;letter-spacing:0.5px;">
                                                    Compléter mon dossier →
                                                </a>
                                            </td>
                                        </tr>
                                    </table>
                                @endif

                                <p style="margin:16px 0 0;color:#94A3B8;font-size:13px;line-height:1.5;">
                                     Ce lien expire dans 7 jours. N'attendez pas pour finaliser !
                                </p>

                            {{-- ════════════ TYPE : dossier_recu ════════════ --}}
                            @elseif ($type === 'dossier_recu')
                                <h2 style="margin:0 0 20px;color:#0F172A;font-size:24px;font-weight:800;">
                                     Votre dossier est bien reçu
                                </h2>
                                <p style="margin:0 0 16px;color:#475569;font-size:15px;line-height:1.6;">
                                    Merci d'avoir complété votre dossier pour <strong>{{ $evenement?->titre }}</strong>.
                                </p>
                                <p style="margin:0 0 16px;color:#475569;font-size:15px;line-height:1.6;">
                                    Notre équipe va maintenant procéder à l'analyse approfondie de votre candidature.
                                    Vous recevrez une réponse définitive sous quelques jours.
                                </p>

                            {{-- ════════════ TYPE : accepte ════════════ --}}
                            @elseif ($type === 'accepte')
                                <h2 style="margin:0 0 20px;color:#0F172A;font-size:24px;font-weight:800;">
                                     Félicitations, vous êtes accepté(e) !
                                </h2>
                                <p style="margin:0 0 16px;color:#475569;font-size:15px;line-height:1.6;">
                                    Nous sommes ravis de vous compter parmi les participants de
                                    <strong>{{ $evenement?->titre }}</strong>.
                                </p>

                                {{-- Encart QR Code --}}
                                <table role="presentation" cellspacing="0" cellpadding="0" border="0" width="100%"
                                       style="background-color:#ECFDF5;border:2px solid #10B981;border-radius:12px;margin:24px 0;">
                                    <tr>
                                        <td style="padding:20px;text-align:center;">
                                            <p style="margin:0 0 8px;color:#065F46;font-size:11px;text-transform:uppercase;letter-spacing:1.5px;font-weight:700;">
                                                Votre badge d'accès
                                            </p>
                                            <p style="margin:0 0 12px;color:#10B981;font-family:monospace;font-size:24px;font-weight:800;letter-spacing:2px;">
                                                {{ $inscription->qr_code ?? '—' }}
                                            </p>
                                            <p style="margin:0;color:#065F46;font-size:13px;">
                                                Présentez ce code à l'entrée de l'événement
                                            </p>
                                        </td>
                                    </tr>
                                </table>

                                <p style="margin:0 0 8px;color:#475569;font-size:15px;line-height:1.6;">
                                    <strong> Date :</strong>
                                    {{ $evenement?->date_debut?->format('d F Y à H:i') ?? '—' }}
                                </p>
                                <p style="margin:0 0 8px;color:#475569;font-size:15px;line-height:1.6;">
                                    <strong> Lieu :</strong>
                                    {{ $evenement?->lieu?->nom ?? '—' }}
                                </p>

                            {{-- ════════════ TYPE : refuse ════════════ --}}
                            @elseif ($type === 'refuse')
                                <h2 style="margin:0 0 20px;color:#0F172A;font-size:24px;font-weight:800;">
                                    Réponse à votre candidature
                                </h2>
                                <p style="margin:0 0 16px;color:#475569;font-size:15px;line-height:1.6;">
                                    Nous vous remercions pour l'intérêt que vous avez porté à <strong>{{ $evenement?->titre }}</strong>.
                                </p>
                                <p style="margin:0 0 16px;color:#475569;font-size:15px;line-height:1.6;">
                                    Malheureusement, nous ne pourrons pas retenir votre candidature cette fois-ci.
                                </p>

                                @if (!empty($donnees['motif']))
                                    <table role="presentation" cellspacing="0" cellpadding="0" border="0" width="100%"
                                           style="background-color:#FEF2F2;border-left:4px solid #DC2626;border-radius:6px;margin:20px 0;">
                                        <tr>
                                            <td style="padding:16px 20px;">
                                                <p style="margin:0 0 6px;color:#991B1B;font-size:11px;text-transform:uppercase;letter-spacing:1px;font-weight:700;">
                                                    Motif de la décision
                                                </p>
                                                <p style="margin:0;color:#7F1D1D;font-size:14px;line-height:1.5;">
                                                    {{ $donnees['motif'] }}
                                                </p>
                                            </td>
                                        </tr>
                                    </table>
                                @endif

                                <p style="margin:16px 0 0;color:#475569;font-size:15px;line-height:1.6;">
                                    N'hésitez pas à postuler aux prochains événements MoovFlow !
                                </p>

                            {{-- ════════════ TYPE : rappel_veille ════════════ --}}
                            @elseif ($type === 'rappel_veille')
                                <h2 style="margin:0 0 20px;color:#0F172A;font-size:24px;font-weight:800;">
                                     Votre événement, c'est demain !
                                </h2>
                                <p style="margin:0 0 16px;color:#475569;font-size:15px;line-height:1.6;">
                                    Petit rappel : vous êtes attendu(e) demain pour <strong>{{ $evenement?->titre }}</strong>.
                                </p>
                                <p style="margin:0 0 8px;color:#475569;font-size:15px;line-height:1.6;">
                                    <strong>Date :</strong> {{ $evenement?->date_debut?->format('d F Y à H:i') ?? '—' }}
                                </p>
                                <p style="margin:0 0 16px;color:#475569;font-size:15px;line-height:1.6;">
                                     <strong>Lieu :</strong> {{ $evenement?->lieu?->nom ?? '—' }}
                                </p>
                                <p style="margin:16px 0 0;color:#475569;font-size:15px;line-height:1.6;">
                                    N'oubliez pas votre badge : <strong>{{ $inscription->qr_code }}</strong>
                                </p>

                            {{-- ════════════ TYPE : remerciement ════════════ --}}
                            @elseif ($type === 'remerciement')
                                <h2 style="margin:0 0 20px;color:#0F172A;font-size:24px;font-weight:800;">
                                     Merci pour votre participation !
                                </h2>
                                <p style="margin:0 0 16px;color:#475569;font-size:15px;line-height:1.6;">
                                    Merci d'avoir participé à <strong>{{ $evenement?->titre }}</strong>.
                                    Nous espérons que cette expérience vous a été utile.
                                </p>
                                <p style="margin:0 0 16px;color:#475569;font-size:15px;line-height:1.6;">
                                    Votre avis nous intéresse beaucoup. Nous vous invitons à répondre à notre questionnaire
                                    de satisfaction.
                                </p>

                            {{-- ════════════ TYPE : rappel_dossier ════════════ --}}
                            @elseif ($type === 'rappel_dossier')
                                <h2 style="margin:0 0 20px;color:#0F172A;font-size:24px;font-weight:800;">
                                    Pensez à compléter votre dossier
                                </h2>
                                <p style="margin:0 0 16px;color:#475569;font-size:15px;line-height:1.6;">
                                    Nous n'avons pas encore reçu votre dossier complet pour <strong>{{ $evenement?->titre }}</strong>.
                                </p>
                                <p style="margin:0 0 16px;color:#475569;font-size:15px;line-height:1.6;">
                                    N'attendez pas la dernière minute, complétez-le dès maintenant pour finaliser votre inscription :
                                </p>

                                @if (!empty($donnees['lien_dossier']))
                                    <table role="presentation" cellspacing="0" cellpadding="0" border="0" width="100%">
                                        <tr>
                                            <td align="center" style="padding:20px 0;">
                                                <a href="{{ $donnees['lien_dossier'] }}"
                                                   style="background-color:#FF8000;color:#ffffff;padding:14px 32px;border-radius:10px;text-decoration:none;font-weight:700;font-size:14px;display:inline-block;">
                                                    Compléter mon dossier →
                                                </a>
                                            </td>
                                        </tr>
                                    </table>
                                @endif
                            @endif

                            {{-- ────── Signature ────── --}}
                            <p style="margin:32px 0 0;padding-top:24px;border-top:1px solid #E2E8F0;color:#94A3B8;font-size:13px;line-height:1.6;">
                                Cordialement,<br>
                                <strong style="color:#475569;">L'équipe dCIRP — Moov Africa Burkina</strong>
                            </p>

                        </td>
                    </tr>

                    <!-- ── FOOTER ── -->
                    <tr>
                        <td style="background-color:#0F172A;padding:24px 40px;text-align:center;">
                            <p style="margin:0;color:rgba(255,255,255,0.5);font-size:11px;line-height:1.5;">
                                © 2026 Moov Africa Burkina · Tous droits réservés<br>
                                Cet email est généré automatiquement. Merci de ne pas y répondre.
                            </p>
                        </td>
                    </tr>
                </table>

            </td>
        </tr>
    </table>
</body>
</html>