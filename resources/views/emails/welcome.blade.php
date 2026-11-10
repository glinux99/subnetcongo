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
<body style="margin:0;padding:0;background-color:#f1f5f9;font-family:Arial,Helvetica,sans-serif;">
    <div style="max-width:620px;margin:0 auto;padding:24px 12px;">

        <!-- En-tête -->
        <div style="background:linear-gradient(135deg,#003399 0%,#0a4fd6 100%);border-radius:14px 14px 0 0;padding:26px 30px;text-align:center;">
            <div style="display:inline-block;background:#EE1C25;color:#ffffff;font-size:11px;font-weight:700;letter-spacing:2px;text-transform:uppercase;padding:4px 12px;border-radius:20px;margin-bottom:10px;">SubnetCongo Academy</div>
            <h1 style="margin:0;color:#ffffff;font-size:20px;font-weight:700;">Formation &amp; Certification</h1>
        </div>

        <!-- Corps -->
        <div style="background:#ffffff;padding:34px 30px;font-size:15px;line-height:1.7;color:#334155;">
            <h2 style="margin-top:0;color:#0f172a;font-size:22px;">{{ $greeting }}</h2>
            <p>Bonjour <strong>{{ $userName }}</strong>,</p>
            <p>{{ $m['intro'] }}</p>

            <p style="font-weight:700;color:#0f172a;margin-bottom:10px;">Pour bien démarrer :</p>
            <ol style="padding-left:20px;margin:0 0 8px;">
                @foreach($m['steps'] as $step)
                    <li style="margin-bottom:10px;">
                        <strong style="color:#003399;">{{ $step['title'] }}</strong><br>
                        <span style="color:#64748b;font-size:14px;">{{ $step['desc'] }}</span>
                    </li>
                @endforeach
            </ol>

            <div style="text-align:center;margin:28px 0 8px;">
                <a href="{{ $dashboardUrl }}" style="display:inline-block;background:#003399;color:#ffffff;text-decoration:none;font-weight:700;font-size:14px;padding:13px 32px;border-radius:10px;letter-spacing:0.3px;">
                    {{ $m['cta'] }} →
                </a>
            </div>

            <p style="margin-top:24px;">{{ $m['closing'] }}</p>
            <p style="margin-bottom:0;">L'équipe SubnetCongo Academy</p>
        </div>

        <!-- Pied de page -->
        <div style="background:#0f172a;border-radius:0 0 14px 14px;padding:22px 30px;text-align:center;">
            <p style="margin:0 0 6px;color:#94a3b8;font-size:12px;">
                SubnetCongo Academy — Plateforme e-learning
            </p>
            <p style="margin:0;color:#64748b;font-size:11px;">
                Cet email a été envoyé à {{ $userEmail }}. Pour toute question :
                <a href="mailto:academy@subnetcongo.net" style="color:#60a5fa;text-decoration:none;">academy@subnetcongo.net</a>
            </p>
        </div>

    </div>
</body>
</html>
