@php
    // Phrases d'accueil variées par rôle pour éviter un message identique à chaque inscription
    $messages = [
        'student' => [
            'greetings' => [
                'Bienvenue sur SubnetCongo Academy !',
                'Votre aventure commence ici !',
                'Félicitations, votre compte est prêt !',
                'Bienvenue dans la communauté SubnetCongo !',
            ],
            'intro' => 'Nous sommes ravis de vous accueillir sur SubnetCongo Academy, votre plateforme de formation professionnelle. Vous faites désormais partie de notre communauté d\'apprenants.',
            'steps' => [
                ['title' => 'Complétez votre profil', 'desc' => 'Renseignez vos informations pour personnaliser votre expérience.'],
                ['title' => 'Explorez le catalogue', 'desc' => 'Parcourez nos formations et choisissez votre premier cours.'],
                ['title' => 'Inscrivez-vous à un cours', 'desc' => 'Accédez aux contenus vidéo, documents et exercices.'],
                ['title' => 'Suivez votre progression', 'desc' => 'Validez vos modules et passez vos examens pour obtenir vos certificats.'],
            ],
            'cta' => 'Accéder à mon espace',
            'closing' => 'Nous vous souhaitons une excellente formation. À très vite sur SubnetCongo Academy !',
        ],
        'instructor' => [
            'greetings' => [
                'Bienvenue dans l\'espace instructeur !',
                'Votre espace de création est prêt !',
                'Félicitations, vous rejoignez nos formateurs !',
                'Bienvenue parmi nos experts pédagogiques !',
            ],
            'intro' => 'Nous sommes honorés de vous accueillir dans l\'espace instructeur de SubnetCongo Academy. Votre expertise va enrichir notre catalogue de formations.',
            'steps' => [
                ['title' => 'Complétez votre profil formateur', 'desc' => 'Ajoutez votre biographie et vos qualifications.'],
                ['title' => 'Créez votre premier cours', 'desc' => 'Structurez vos modules, leçons et ressources.'],
                ['title' => 'Publiez vos contenus', 'desc' => 'Rendez vos formations visibles dans le catalogue.'],
                ['title' => 'Interagissez avec vos apprenants', 'desc' => 'Répondez aux questions et suivez les progrès de vos élèves.'],
            ],
            'cta' => 'Ouvrir mon espace instructeur',
            'closing' => 'Votre contribution est précieuse pour nos apprenants. Bienvenue dans l\'aventure SubnetCongo !',
        ],
        'admin' => [
            'greetings' => [
                'Bienvenue dans l\'équipe SubnetCongo Academy !',
                'Votre accès administrateur est activé !',
                'Félicitations, vous avez rejoint l\'administration !',
                'Bienvenue dans le pilotage de la plateforme !',
            ],
            'intro' => 'Nous vous souhaitons la bienvenue au sein de l\'équipe d\'administration de SubnetCongo Academy. Votre rôle est essentiel au bon fonctionnement de la plateforme.',
            'steps' => [
                ['title' => 'Découvrez le tableau de bord', 'desc' => 'Consultez les indicateurs clés et l\'activité de la plateforme.'],
                ['title' => 'Gérez les utilisateurs', 'desc' => 'Administrez les comptes, rôles et permissions.'],
                ['title' => 'Supervisez les inscriptions', 'desc' => 'Validez les demandes et suivez les parcours.'],
                ['title' => 'Configurez la plateforme', 'desc' => 'Ajustez les paramètres, templates et intégrations.'],
            ],
            'cta' => 'Accéder à l\'administration',
            'closing' => 'Merci de faire partie de l\'équipe. Ensemble, faisons grandir SubnetCongo Academy !',
        ],
        'staff' => [
            'greetings' => [
                'Bienvenue dans l\'équipe SubnetCongo Academy !',
                'Votre accès équipe est activé !',
                'Félicitations, vous avez rejoint le staff !',
                'Bienvenue au sein de nos équipes !',
            ],
            'intro' => 'Nous vous souhaitons la bienvenue dans l\'équipe SubnetCongo Academy. Votre rôle contribuent directement à la qualité du service rendu à nos apprenants.',
            'steps' => [
                ['title' => 'Découvrez votre espace de travail', 'desc' => 'Familiarisez-vous avec les outils à votre disposition.'],
                ['title' => 'Consultez vos responsabilités', 'desc' => 'Identifiez les missions liées à votre fonction.'],
                ['title' => 'Collaborez avec l\'équipe', 'desc' => 'Échangez avec les autres membres via les outils internes.'],
                ['title' => 'Restez informé', 'desc' => 'Consultez régulièrement le tableau de bord et les notifications.'],
            ],
            'cta' => 'Accéder à mon espace',
            'closing' => 'Merci pour votre engagement. L\'équipe SubnetCongo Academy compte sur vous !',
        ],
    ];

    $m = $messages[$roleKey] ?? $messages['student'];
    $greeting = $m['greetings'][array_rand($m['greetings'])];
@endphp
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bienvenue sur SubnetCongo Academy</title>
</head>
<body style="margin:0;padding:0;background-color:#eef2f7;font-family:Segoe UI,Roboto,Helvetica,Arial,sans-serif;">
    <table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%" style="background-color:#eef2f7;">
        <tr>
            <td style="padding:24px 12px;">
                <table role="presentation" cellpadding="0" cellspacing="0" border="0" width="720" align="center" style="width:100%;max-width:720px;margin:0 auto;border-radius:18px;overflow:hidden;box-shadow:0 18px 40px -12px rgba(15,23,42,.18);">

                    <!-- En-tête -->
                    <tr>
                        <td style="background:linear-gradient(135deg,#003399 0%,#0a4fd6 60%,#1e64ff 100%);padding:34px 40px;text-align:center;">
                            <div style="display:inline-block;background:#EE1C25;color:#ffffff;font-size:11px;font-weight:800;letter-spacing:2px;text-transform:uppercase;padding:5px 14px;border-radius:999px;margin-bottom:14px;box-shadow:0 4px 14px -4px rgba(238,28,37,.6);">SubnetCongo Academy</div>
                            <h1 style="margin:0;color:#ffffff;font-size:24px;line-height:1.25;font-weight:800;letter-spacing:-.2px;">Formation &amp; Certification</h1>
                            <p style="margin:8px 0 0;color:#cfe0ff;font-size:13px;letter-spacing:.3px;">Apprenez. Pratiquez. Certifiez-vous.</p>
                        </td>
                    </tr>

                    <!-- Corps -->
                    <tr>
                        <td style="background:#ffffff;padding:36px 40px;font-size:16px;line-height:1.75;color:#334155;">
                            <h2 style="margin-top:0;color:#0f172a;font-size:26px;font-weight:800;letter-spacing:-.3px;">{{ $greeting }}</h2>
                            <p>Bonjour <strong>{{ $userName }}</strong>,</p>
                            <p>{{ $m['intro'] }}</p>

                            <table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%" style="margin:24px 0;">
                                @foreach($m['steps'] as $step)
                                    <tr>
                                        <td style="padding:10px 12px;background:#f8fafc;border:1px solid #eef2f7;border-radius:10px;">
                                            <strong style="color:#003399;font-size:15px;">{{ $step['title'] }}</strong>
                                            <span style="display:block;color:#64748b;font-size:13px;margin-top:2px;">{{ $step['desc'] }}</span>
                                        </td>
                                    </tr>
                                    @if(!$loop->last)<tr><td style="height:8px;"></td></tr>@endif
                                @endforeach
                            </table>

                            <table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%" style="margin:28px 0 8px;">
                                <tr>
                                    <td align="center">
                                        <a href="{{ $dashboardUrl }}" style="display:inline-block;background:#003399;color:#ffffff;text-decoration:none;font-weight:800;font-size:15px;letter-spacing:.3px;padding:16px 40px;border-radius:12px;box-shadow:0 10px 24px -10px rgba(0,51,153,.55);">
                                            {{ $m['cta'] }}&nbsp;&nbsp;→
                                        </a>
                                    </td>
                                </tr>
                            </table>

                            <p style="margin-top:24px;">{{ $m['closing'] }}</p>
                            <p style="margin-bottom:0;">L'équipe SubnetCongo Academy</p>
                        </td>
                    </tr>

                    <!-- Pied de page -->
                    <tr>
                        <td style="background:#0f172a;padding:26px 40px;text-align:center;">
                            <p style="margin:0 0 8px;color:#cbd5e1;font-size:13px;font-weight:700;letter-spacing:.4px;">SubnetCongo Academy — Plateforme e-learning</p>
                            <p style="margin:0;color:#64748b;font-size:11px;line-height:1.6;">
                                Cet email a été envoyé à {{ $userEmail }}.<br>
                                Pour toute question : <a href="mailto:academy@subnetcongo.net" style="color:#60a5fa;text-decoration:none;font-weight:600;">academy@subnetcongo.net</a>
                            </p>
                            <p style="margin:12px 0 0;color:#475569;font-size:10px;letter-spacing:.5px;text-transform:uppercase;">© {{ date('Y') }} SubnetCongo Sarl · RD Congo</p>
                        </td>
                    </tr>

                </table>
            </td>
        </tr>
    </table>
</body>
</html>
