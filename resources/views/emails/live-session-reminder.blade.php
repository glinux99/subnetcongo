<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <title>Rappel : votre session live commence bientôt</title>
</head>
<body style="margin:0;padding:0;background:#f1f5f9;font-family:Arial,Helvetica,sans-serif;color:#1e293b;">
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background:#f1f5f9;padding:24px 0;">
        <tr>
            <td align="center">
                <table role="presentation" width="600" cellpadding="0" cellspacing="0" style="max-width:600px;width:100%;background:#ffffff;border-radius:14px;overflow:hidden;">
                    <tr>
                        <td style="background:#dc2626;padding:22px 28px;">
                            <span style="color:#ffffff;font-size:18px;font-weight:bold;">SubnetCongo Academy</span>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding:28px;">
                            <h1 style="margin:0 0 16px;font-size:20px;color:#0f172a;">Rappel : la session commence dans {{ $minutesBefore }} minute(s)</h1>

                            <p style="margin:0 0 12px;font-size:14px;line-height:1.6;">
                                Bonjour{{ $recipientName ? ' ' . $recipientName : '' }},
                            </p>

                            <p style="margin:0 0 16px;font-size:14px;line-height:1.6;">
                                La session <strong>« {{ $sessionTitle }} »</strong> animée par <strong>{{ $instructorName }}</strong>
                                @if ($courseTitle)
                                    dans le cadre du cours <strong>{{ $courseTitle }}</strong>
                                @endif
                                va commencer dans <strong>{{ $minutesBefore }} minute(s)</strong>.
                            </p>

                            <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background:#f8fafc;border:1px solid #e2e8f0;border-radius:10px;margin:0 0 22px;">
                                <tr>
                                    <td style="padding:14px 16px;font-size:13px;color:#334155;">
                                        <strong>Date et heure :</strong> {{ $startAtLabel }}
                                    </td>
                                </tr>
                            </table>

                            <p style="margin:0 0 22px;">
                                <a href="{{ $joinUrl }}"
                                   style="display:inline-block;background:#dc2626;color:#ffffff;text-decoration:none;font-size:14px;font-weight:bold;padding:12px 22px;border-radius:10px;">
                                    Rejoindre la session
                                </a>
                            </p>

                            <p style="margin:0 0 8px;font-size:12px;color:#64748b;line-height:1.6;">
                                Ce lien est personnel et sécurisé. Connectez-vous avec votre compte pour y accéder.
                            </p>
                            <p style="margin:0;font-size:12px;color:#94a3b8;word-break:break-all;">
                                {{ $joinUrl }}
                            </p>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding:16px 28px;background:#f8fafc;border-top:1px solid #e2e8f0;">
                            <p style="margin:0;font-size:11px;color:#94a3b8;">
                                Cet e-mail a été envoyé automatiquement par SubnetCongo Academy. Si vous n'êtes pas à l'origine de cette invitation, vous pouvez l'ignorer.
                            </p>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
