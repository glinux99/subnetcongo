<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>Certificat - {{ $certificate->certificate_code }}</title>

    @php
        /**
         * 1. PARSING ET NETTOYAGE DES DONNÉES
         */
        if (!function_exists('safeColor')) {
            function safeColor($color, $default = '#000000') {
                if (empty($color)) return $default;
                return strpos($color, '#') === 0 ? $color : '#' . $color;
            }
        }

        // Configuration du Template (Styles globaux)
        $bgColor     = safeColor($template->background_color ?? '#ffffff');
        $bgOpacity   = ($template->bg_opacity ?? 100) / 100;
        $borderColor = safeColor($template->border_color ?? '#c5a059');
        $textColor   = safeColor($template->text_color ?? '#1e293b');
        $borderWidth = $template->border_width ?? 20;
        $orientation = $template->orientation ?? 'landscape';

        // Polices
        $primaryFont = $template->primary_font ?? 'Helvetica';
        $accentFont  = $template->accent_font ?? 'Times-Bold';

        // Extraction de la configuration des éléments (JSON decodé en array)
        // On fusionne avec des valeurs par défaut au cas où
        $elements = $config ?? [];

        $title    = $elements['title']    ?? ['text' => 'CERTIFICAT', 'size' => 40, 'top' => 25, 'letter_spacing' => 0, 'align' => 'center'];
        $subtitle = $elements['subtitle'] ?? ['text' => 'Décerné à', 'size' => 18, 'top' => 40, 'letter_spacing' => 0];
        $body     = $elements['body']     ?? ['text' => 'Pour la réussite de', 'size' => 16, 'top' => 65, 'letter_spacing' => 0];

        // Données dynamiques
        $studentName = $certificate->metadata['student_name_at_issue'] ?? ($user->name ?? 'Étudiant');
        $courseTitle = $certificate->metadata['course_title_at_issue'] ?? ($course->title ?? 'Formation');
    @endphp

    <style>
        /**
         * 2. CONFIGURATION DE LA PAGE
         */
        @page {
            margin: 0;
            size: a4 {{ $orientation }};
        }

        body {
            margin: 0;
            padding: 0;
            font-family: '{{ $primaryFont }}', sans-serif;
            background-color: #FFFFFF; /* Fond de base pour DomPDF */
        }

        /* Calque de couleur de fond avec opacité */
        .bg-color-layer {
            position: absolute;
            top: 0; left: 0; width: 100%; height: 100%;
            background-color: {{ $bgColor }};
            opacity: {{ $bgOpacity }};
            z-index: -10;
        }

        /* Image de fond (Filigrane) */
        .background-image-layer {
            position: absolute;
            top: 0; left: 0; width: 100%; height: 100%;
            z-index: -20;
            opacity: {{ ($template->background_opacity ?? 20) / 100 }};
        }
        .background-image-layer img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .certificate-wrapper {
            position: relative;
            width: 100%;
            height: 100%;
            box-sizing: border-box;
        }

        /* 3. BORDURES */
        .main-border {
            position: absolute;
            top: 30px; left: 30px; right: 30px; bottom: 30px;
            border: {{ $borderWidth }}px solid {{ $borderColor }};
            z-index: 100;
            pointer-events: none;
        }

        /* 4. LOGO */
        .logo-box {
            position: absolute;
            top: {{ $template->logo_top ?? 10 }}%;
            width: 100%;
            text-align: center;
            z-index: 50;
        }
        .logo-box img {
            width: {{ $template->logo_scale ?? 15 }}%;
            max-width: 300px;
        }

        /* 5. TEXTES DYNAMIQUES (Positions basées sur le designer) */
        .text-container {
            position: absolute;
            width: 100%;
            text-align: {{ $title['align'] ?? 'center' }};
            left: 0;
            z-index: 60;
            padding: 0 10%;
            box-sizing: border-box;
        }

        .title-style {
            font-family: '{{ $accentFont }}', serif;
            font-size: {{ $title['size'] }}px;
            color: {{ safeColor($title['color'] ?? $textColor) }};
            letter-spacing: {{ $title['letter_spacing'] }}px;
            text-transform: uppercase;
        }

        .subtitle-style {
            font-size: {{ $subtitle['size'] }}px;
            color: {{ safeColor($subtitle['color'] ?? $textColor) }};
            letter-spacing: {{ $subtitle['letter_spacing'] ?? 0 }}px;
            font-weight: bold;
        }

        .body-style {
            font-size: {{ $body['size'] }}px;
            color: {{ safeColor($body['color'] ?? $textColor) }};
            line-height: 1.6;
        }

        .recipient-name {
            position: absolute;
            top: 48%;
            width: 100%;
            text-align: center;
            font-size: 54px;
            font-weight: 900;
            font-family: '{{ $accentFont }}', serif;
            text-transform: uppercase;
            color: {{ $textColor }};
            z-index: 70;
        }

        .course-highlight {
            margin-top: 15px;
            font-weight: 900;
            color: {{ $borderColor }};
            font-style: italic;
            font-size: {{ $body['size'] + 4 }}px;
        }

        /* 6. FOOTER (Signatures, QR, Sceau) */
        .footer-section {
            position: absolute;
            bottom: 60px;
            left: 80px;
            right: 80px;
            height: 140px;
            z-index: 80;
        }

        .footer-column {
            float: left;
            width: 33.33%;
            text-align: center;
        }

        .signature-wrap {
            height: 80px;
            vertical-align: bottom;
            border-bottom: 2px solid {{ $textColor }};
            margin-bottom: 10px;
        }

        .signature-img {
            max-height: 70px;
            max-width: 200px;
        }

        .qr-wrap img {
            width: 90px;
            height: 90px;
            border: 3px solid {{ $borderColor }};
            background-color: white;
            padding: 3px;
        }

        .seal-wrap {
            width: 100px;
            height: 100px;
            margin: 0 auto;
            border: 5px double {{ $borderColor }};
            border-radius: 50%;
            background: white;
            line-height: 90px;
        }

        .label-footer {
            font-size: 10px;
            font-weight: bold;
            text-transform: uppercase;
            color: {{ $textColor }};
            letter-spacing: 1px;
        }

        .clear { clear: both; }
    </style>
</head>
<body>

    <div class="certificate-wrapper">

        <!-- CALQUES DE FOND -->
        @if(isset($background_image) && $background_image)
            <div class="background-image-layer">
                <img src="{{ $background_image }}">
            </div>
        @endif
        <div class="bg-color-layer"></div>

        <!-- BORDURE -->
        <div class="main-border"></div>

        <!-- LOGO -->
        @if($template->show_logo && isset($logo))
            <div class="logo-box">
                <img src="{{ $logo }}">
            </div>
        @endif

        <!-- TITRE -->
        <div class="text-container" style="top: {{ $title['top'] }}%;">
            <div class="title-style">{{ $title['text'] }}</div>
        </div>

        <!-- SOUS-TITRE -->
        <div class="text-container" style="top: {{ $subtitle['top'] }}%;">
            <div class="subtitle-style">{{ $subtitle['text'] }}</div>
        </div>

        <!-- NOM DE L'ÉTUDIANT (POSITION FIXE CENTRALE) -->
        <div class="recipient-name">
            <span style="border-bottom: 4px solid {{ $borderColor }}; padding-bottom: 5px;">
                {{ $studentName }}
            </span>
        </div>

        <!-- CORPS DU TEXTE -->
        <div class="text-container" style="top: {{ $body['top'] }}%;">
            <div class="body-style">
                {{ $body['text'] }}
                <div class="course-highlight">{{ $courseTitle }}</div>
            </div>
        </div>

        <!-- PIED DE PAGE -->
        <div class="footer-section">

            <!-- Signature -->
            <div class="footer-column">
                @if($template->show_signature)
                    <div class="signature-wrap">
                        @if(isset($signature))
                            <img src="{{ $signature }}" class="signature-img">
                        @endif
                    </div>
                    <div class="label-footer">Directeur de l'Académie</div>
                @endif
            </div>

            <!-- Sceau Central -->
            <div class="footer-column">
                <div class="seal-wrap">
                   <span style="font-size: 40px; color: {{ $borderColor }}">★</span>
                </div>
                <div class="label-footer" style="margin-top: 10px;">Sceau d'Excellence</div>
            </div>

            <!-- QR Code -->
            <div class="footer-column">
                @if($template->show_qr && isset($qr_code))
                    <div class="qr-wrap">
                        <img src="{{ $qr_code }}">
                    </div>
                    <div class="label-footer" style="font-size: 8px; margin-top: 5px;">Vérification : {{ $certificate->certificate_code }}</div>
                @endif
            </div>

            <div class="clear"></div>
        </div>

    </div>

</body>
</html>
