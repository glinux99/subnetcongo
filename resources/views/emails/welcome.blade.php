<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bienvenue sur SubnetCongo Academy</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            color: #333;
            margin: 0;
            padding: 0;
            background-color: #f4f4f4;
        }
        .container {
            max-width: 600px;
            margin: 0 auto;
            padding: 20px;
            background-color: #ffffff;
        }
        .header {
            text-align: center;
            padding: 20px 0;
            border-bottom: 2px solid #007bff;
        }
        .header h1 {
            color: #007bff;
            margin: 0;
        }
        .content {
            padding: 30px 0;
        }
        .button {
            display: inline-block;
            padding: 12px 30px;
            background-color: #007bff;
            color: #ffffff;
            text-decoration: none;
            border-radius: 5px;
            margin: 20px 0;
        }
        .footer {
            text-align: center;
            padding: 20px 0;
            font-size: 12px;
            color: #666;
            border-top: 1px solid #ddd;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>SubnetCongo Academy</h1>
        </div>

        <div class="content">
            <h2>Bienvenue {{ $userName }} !</h2>

            <p>Nous sommes ravis de vous accueillir sur SubnetCongo Academy, votre plateforme de formation professionnelle en ligne.</p>

            <p>Votre compte a été créé avec succès. Pour bien démarrer, voici quelques étapes importantes :</p>

            <ol>
                <li><strong>Complétez votre profil</strong> - Ajoutez vos informations personnelles</li>
                <li><strong>Explorez nos formations</strong> - Découvrez nos cours disponibles</li>
                <li><strong>Inscrivez-vous</strong> - Rejoignez votre premier cours</li>
                <li><strong>Commencez à apprendre</strong> - Suivez vos leçons et passez vos examens</li>
            </ol>

            <p>Pour accéder à votre espace personnel :</p>

            <p style="text-align: center;">
                <a href="{{ $dashboardUrl }}" class="button">Accéder à mon espace</a>
            </p>

            <p>Si vous avez des questions, n'hésitez pas à nous contacter. Notre équipe est à votre disposition pour vous accompagner dans votre parcours de formation.</p>

            <p>À bientôt sur SubnetCongo Academy !</p>
        </div>

        <div class="footer">
            <p>Cet email a été envoyé à {{ $userEmail }}</p>
            <p>&copy; {{ date('Y') }} SubnetCongo Academy. Tous droits réservés.</p>
        </div>
    </div>
</body>
</html>
