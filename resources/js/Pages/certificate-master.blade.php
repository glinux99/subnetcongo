<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>Certificat - {{ $certificate->certificate_code }}</title>
    <style>
        /*
         * ======================================================================================
         * SUBNETCONGO LMS - MASTER PDF TEMPLATE ENGINE v12.0.0
         * ======================================================================================
         * Ce template Blade est le moteur de rendu pour tous les certificats PDF.
         * Il utilise les variables injectées par CertificateController@download
         * pour construire dynamiquement le certificat avec les styles, polices,
         * et contenus définis dans le Visual Designer.
         *
         * NOTE POUR DOMPDF:
         * - Les polices personnalisées (Inter, Playfair Display, etc.) doivent être
         *   installées et accessibles par DomPDF sur le serveur. 'DejaVu Sans' est une police
         *   de secours fiable pour les caractères spéciaux.
         * - Préférez les styles en ligne ou dans une balise <style>.
         * - Les images (QR, signature) sont injectées en Base64 pour éviter les
         *   problèmes de chemins relatifs.
         */
        @page {
            margin: 0;
            size: a4 {{ $template->orientation ?? 'landscape' }};
        }
        body {
            font-family: '{{ $template->primary_font ?? 'DejaVu Sans' }}', sans-serif;
            margin: 0;
            padding: 0;
            background-color: #{{ $template->background_color }};
            color: #{{ $template->text_color }};
            -webkit-font-smoothing: antialiased;
            -moz-osx-font-smoothing: grayscale;
        }

        .certificate-container {
            position: relative;
            width: 100%;
            height: 100%;
            overflow: hidden;
        }

        .certificate-border {
            position: absolute;
            top: 25px;
            left: 25px;
            right: 25px;
            bottom: 25px;
            border: {{ $template->border_width }}px solid #{{ $template->border_color }};
        }

        .text-element {
            position: absolute;
            width: 100%;
            text-align: center;
            padding: 0 50px;
            box-sizing: border-box;
            word-wrap: break-word;
        }

        .recipient-name {
            font-family: '{{ $template->accent_font ?? $template->primary_font }}', serif;
            font-weight: 900;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            display: inline-block;
            padding-bottom: 8px;
            border-bottom: 2px solid #{{ $template->border_color }};
        }

        .course-name {
            font-weight: 900;
            font-style: italic;
            color: #{{ $template->border_color }};
        }

        .footer {
            position: absolute;
            bottom: 45px;
            left: 50px;
            right: 50px;
            height: 100px;
        }
        .footer-item {
            position: absolute;
            bottom: 0;
            width: 33.33%;
            text-align: center;
        }
        .footer-left { left: 0; }
        .footer-center { left: 33.33%; }
        .footer-right { right: 0; }

        .signature-image { max-height: 50px; margin-bottom: 8px; }
        .qr-code-image { width: 80px; height: 80px; }
        .footer-label { font-size: 9px; text-transform: uppercase; letter-spacing: 0.1em; font-weight: 700; }

    </style>
</head>
<body>
    <div class="certificate-container">
        <div class="certificate-border"></div>

        @if(isset($config['title']))
            <div class="text-element" style="top: {{ $config['title']['top'] }}%; font-size: {{ $config['title']['size'] }}px; font-family: '{{ $template->accent_font ?? $template->primary_font }}', serif; font-weight: 900; text-transform: uppercase; letter-spacing: 0.05em;">
                {{ $config['title']['text'] }}
            </div>
        @endif

        @if(isset($config['subtitle']))
            <div class="text-element" style="top: {{ $config['subtitle']['top'] }}%; font-size: {{ $config['subtitle']['size'] }}px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.3em; opacity: 0.7;">
                {{ $config['subtitle']['text'] }}
            </div>
        @endif

        <div class="text-element" style="top: 45%;">
            <h2 class="recipient-name" style="font-size: 36px; font-family: '{{ $template->accent_font ?? $template->primary_font }}', serif;">
                {{ $certificate->metadata['student_name_at_issue'] ?? $user->name }}
            </h2>
        </div>

        @if(isset($config['body']))
            <div class="text-element" style="top: {{ $config['body']['top'] }}%; font-size: {{ $config['body']['size'] }}px; line-height: 1.6; opacity: 0.9;">
                {{ $config['body']['text'] }}
                <p class="course-name" style="margin-top: 16px; font-size: {{ $config['body']['size'] + 2 }}px;">
                    {{ $certificate->metadata['course_title_at_issue'] ?? $course->title }}
                </p>
            </div>
        @endif

        <div class="footer">
            @if($template->show_signature && $signature)
                <div class="footer-item footer-left">
                    <img src="{{ $signature }}" class="signature-image" alt="Signature">
                    <div style="width: 150px; height: 1px; background-color: #{{ $template->text_color }}; margin: 0 auto 8px auto; opacity: 0.5;"></div>
                    <div class="footer-label">Signataire Autorisé</div>
                </div>
            @endif

            <div class="footer-item footer-center">
                {{-- Pour un logo, décommentez et ajustez le chemin : <img src="{{ public_path('images/logo.png') }}" style="max-height: 60px; margin-bottom: 8px;" alt="Logo"> --}}
                <div class="footer-label" style="margin-top: 70px;">SubnetCongo E-Learning</div>
            </div>

            @if($template->show_qr && $qr_code)
                <div class="footer-item footer-right">
                    <img src="{{ $qr_code }}" class="qr-code-image" alt="QR Code">
                    <div class="footer-label" style="font-size: 8px; margin-top: 4px;">Vérifier : {{ $certificate->certificate_code }}</div>
                </div>
            @endif
        </div>
    </div>
</body>
</html>
