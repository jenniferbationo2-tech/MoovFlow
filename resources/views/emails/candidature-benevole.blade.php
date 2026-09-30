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
                                Bonjour {{ $user?->prenom ?? 'cher bénévole' }},
                            </p>

                            {{-- ════════════ TYPE : soumise ════════════ --}}
                            @if ($type === 'soumise')
                                <h2 style="margin:0 0 20px;color:#0F172A;font-size:24px;font-weight:800;">
                                    Votre candidature a bien été reçue
                                </h2>
                                <p style="margin:0 0 16px;color:#475569;font-size:15px;line-height:1.6;">
                                    Nous avons bien reçu votre candidature pour le poste bénévole
                                    <strong>{{ $poste?->nom_poste }}</strong>
                                    @if($evenement) sur <strong>{{ $evenement->titre }}</strong> @endif.
                                </p>
                                <p style="margin:0 0 16px;color:#475569;font-size:15px;line-height:1.6;">
                                    L'équipe organisatrice va l'examiner et vous recevrez une réponse par email.
                                </p>

                            {{-- ════════════ TYPE : acceptee ════════════ --}}
                            @elseif ($type === 'acceptee')
                                <h2 style="margin:0 0 20px;color:#0F172A;font-size:24px;font-weight:800;">
                                    Félicitations, votre candidature est acceptée !
                                </h2>
                                <p style="margin:0 0 16px;color:#475569;font-size:15px;line-height:1.6;">
                                    Vous êtes retenu(e) comme bénévole pour le poste
                                    <strong>{{ $poste?->nom_poste }}</strong>
                                    @if($evenement) sur <strong>{{ $evenement->titre }}</strong> @endif.
                                </p>

                                <table role="presentation" cellspacing="0" cellpadding="0" border="0" width="100%"
                                       style="background-color:#ECFDF5;border:2px solid #10B981;border-radius:12px;margin:24px 0;">
                                    <tr>
                                        <td style="padding:20px;">
                                            @if($evenement?->date_debut)
                                                <p style="margin:0 0 8px;color:#065F46;font-size:14px;">
                                                    <strong>Date :</strong> {{ $evenement->date_debut->format('d F Y à H:i') }}
                                                </p>
                                            @endif
                                            @if($evenement?->lieu)
                                                <p style="margin:0;color:#065F46;font-size:14px;">
                                                    <strong>Lieu :</strong> {{ $evenement->lieu->nom }}
                                                </p>
                                            @endif
                                        </td>
                                    </tr>
                                </table>

                                <p style="margin:0;color:#475569;font-size:15px;line-height:1.6;">
                                    Merci pour votre engagement auprès de Moov Africa Burkina !
                                </p>

                            {{-- ════════════ TYPE : refusee ════════════ --}}
                            @elseif ($type === 'refusee')
                                <h2 style="margin:0 0 20px;color:#0F172A;font-size:24px;font-weight:800;">
                                    Réponse à votre candidature bénévole
                                </h2>
                                <p style="margin:0 0 16px;color:#475569;font-size:15px;line-height:1.6;">
                                    Nous vous remercions pour votre candidature au poste
                                    <strong>{{ $poste?->nom_poste }}</strong>.
                                </p>
                                <p style="margin:0 0 16px;color:#475569;font-size:15px;line-height:1.6;">
                                    Malheureusement, nous ne pourrons pas la retenir cette fois-ci.
                                </p>

                                @if ($candidature->motif_refus)
                                    <table role="presentation" cellspacing="0" cellpadding="0" border="0" width="100%"
                                           style="background-color:#FEF2F2;border-left:4px solid #DC2626;border-radius:6px;margin:20px 0;">
                                        <tr>
                                            <td style="padding:16px 20px;">
                                                <p style="margin:0 0 6px;color:#991B1B;font-size:11px;text-transform:uppercase;letter-spacing:1px;font-weight:700;">
                                                    Motif de la décision
                                                </p>
                                                <p style="margin:0;color:#7F1D1D;font-size:14px;line-height:1.5;">
                                                    {{ $candidature->motif_refus }}
                                                </p>
                                            </td>
                                        </tr>
                                    </table>
                                @endif

                                <p style="margin:16px 0 0;color:#475569;font-size:15px;line-height:1.6;">
                                    N'hésitez pas à candidater pour d'autres postes bénévoles sur MoovFlow !
                                </p>
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
