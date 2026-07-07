<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>Certificat - {{ $certificate->certificate_code ?? 'Preview' }}</title>

    @php
        // Définir la variable $template à partir de l'objet $certificate
        $template = $certificate->template;

        /**
         * =========================================================================
         * 1. PARSING SÉCURISÉ DU TEMPLATE (QUANTUM ENGINE)
         * =========================================================================
         */

        if (!function_exists('safeColor')) {
            function safeColor($color, $default = '#000000') {
                if (empty($color)) return $default;
                return strpos($color, '#') === 0 ? $color : '#' . $color;
            }
        }

        // Configuration Globale du document
        $orientation = $template->orientation ?? 'landscape';
        $canvasWidth  = $orientation === 'landscape' ? 1120 : 792;
        $canvasHeight = $orientation === 'landscape' ? 792 : 1120;

        $bgColor     = safeColor($template->background_color ?? '#ffffff');
        $borderWidth = isset($template->border_width) ? (int)$template->border_width : 0;
        $borderColor = safeColor($template->border_color ?? '#dc2626');

        /**
         * DÉCODAGE DOUBLE DU JSON (Correction de l'échappement BDD)
         */
        $elements = [];
        $rawElements = $template->config ?? '[]';

        if (is_string($rawElements)) {
            $elements = json_decode($rawElements, true);
            // Si Laravel a échappé le JSON (la chaîne commence par "[{"), on refait un décode
            if (is_string($elements)) {
                $elements = json_decode($elements, true);
            }
        } else if (is_array($rawElements)) {
            $elements = $rawElements;
        }

        if (!is_array($elements)) {
            $elements = [];
        }

        /**
         * =========================================================================
         * 2. SYSTÈME DE VARIABLES DYNAMIQUES (Remplacement de {{...}})
         * =========================================================================
         */
        $metadata = [];
        if (isset($certificate->metadata)) {
            if (is_string($certificate->metadata)) {
                $metadata = json_decode($certificate->metadata, true) ?? [];
            } elseif (is_array($certificate->metadata)) {
                $metadata = $certificate->metadata;
            }
        }

        $vars = [
            '{{NOM_APPRENANT}}'  => $metadata['student_name_at_issue'] ?? ($certificate->user->name ?? 'Nom de l\'Apprenant'),
            '{{NOM_FORMATION}}'  => $metadata['course_title_at_issue'] ?? ($certificate->course->title ?? 'Titre de la Formation'),
            '{{CERTIFICATE_ID}}' => $certificate->certificate_code ?? 'XXXX-XXXX-XXXX',
            '{{DATE}}'           => isset($certificate->issued_at) ? \Carbon\Carbon::parse($certificate->issued_at)->format('d/m/Y') : date('d/m/Y'),
            '{{MENTION}}'        => $metadata['mention'] ?? 'Excellence'
        ];

        function parseVariables($text, $vars) {
            if(empty($text)) return '';
            return str_replace(array_keys($vars), array_values($vars), $text);
        }
    @endphp

    <style>
        /**
         * =========================================================================
         * 3. CSS DOMPDF (MAPPÉ SUR LES PIXELS DU FRONTEND)
         * =========================================================================
         */
        @page {
            size: {{ $canvasWidth }}px {{ $canvasHeight }}px;
            margin: 0;
        }

        body {
            margin: 0;
            padding: 0;
            width: {{ $canvasWidth }}px;
            height: {{ $canvasHeight }}px;
            background-color: {{ $bgColor }};
            position: relative;
            overflow: hidden;
            font-family: 'Helvetica', 'Arial', sans-serif;
        }

        /* Image de Fond */
        .bg-image {
            position: absolute;
            top: 0; left: 0;
            width: {{ $canvasWidth }}px;
            height: {{ $canvasHeight }}px;
            z-index: 1;
            opacity: {{ isset($template->bg_opacity) ? ($template->bg_opacity / 100) : 1 }};
        }

        /* Bordure master */
        .master-border {
            position: absolute;
            top: 30px; left: 30px;
            width: {{ $canvasWidth - 60 - ($borderWidth * 2) }}px;
            height: {{ $canvasHeight - 60 - ($borderWidth * 2) }}px;
            border: {{ $borderWidth }}px solid {{ $borderColor }};
            z-index: 5;
            box-sizing: border-box;
        }

        /* Conteneur Absolu (Drag & Drop) */
        .quantum-element {
            position: absolute;
            box-sizing: border-box;
        }

        .quantum-element img {
            width: 100%;
            height: 100%;
        }
    </style>
</head>
<body>

    <!-- 1. IMAGE DE FOND GLOBALE -->
    @if(isset($background_image) && !empty($background_image))
        <img src="{{ $background_image }}" class="bg-image" alt="Background">
    @endif

    <!-- 2. BORDURE DÉCORATIVE -->
    @if($borderWidth > 0)
        <div class="master-border"></div>
    @endif

    <!-- ALERTE SI LE DÉCODAGE JSON ÉCHOUE -->
    @if(empty($elements))
        <div style="position: absolute; top: 45%; left: 0; width: 100%; text-align: center; color: red; font-size: 24px; font-weight: bold; z-index: 999;">
            MODELE VIERGE OU ERREUR DE DONNÉES.<br>
            <span style="font-size: 16px; color: black;">Assurez-vous d'avoir sauvegardé les éléments dans le Studio.</span>
        </div>
    @endif

    <!-- 3. BOUCLE DE RENDU DU STUDIO (LE COEUR DU SYSTÈME) -->
    @foreach($elements as $index => $el)
        @php
            // Gestion des coordonnées et des tailles (on gère le "null" de la BDD)
            $x = isset($el['x']) ? (float)$el['x'] : 0;
            $y = isset($el['y']) ? (float)$el['y'] : 0;

            $w = (isset($el['width']) && $el['width'] !== null) ? $el['width'] . 'px' : '100%';
            $h = (isset($el['height']) && $el['height'] !== null) ? $el['height'] . 'px' : 'auto';

            $opacity = isset($el['opacity']) ? ($el['opacity'] / 100) : 1;
            $zIndex  = 10 + $index;

            /**
             * LOGIQUE DES IMAGES (Logo, Signatures, Images Custom)
             * Le front peut envoyer l'image directement en Base64 dans "content"
             */
            $imgContent = null;
            if (in_array($el['type'], ['logo', 'signature', 'image'])) {
                // 1. On cherche d'abord si l'image est dans le JSON (Glissée via le Studio)
                if (!empty($el['content']) && strpos($el['content'], 'data:image') === 0) {
                    $imgContent = $el['content'];
                }
                // 2. Sinon, on utilise l'upload classique du backend comme solution de secours
                elseif ($el['type'] === 'logo' && !empty($logo)) {
                    $imgContent = $logo;
                }
                elseif ($el['type'] === 'signature' && !empty($signature)) {
                    $imgContent = $signature;
                }
            }
        @endphp

        <!-- Le conteneur du calque -->
        <div class="quantum-element" style="left: {{ $x }}px; top: {{ $y }}px; width: {{ $w }}; height: {{ $h }}; z-index: {{ $zIndex }}; opacity: {{ $opacity }};">

            <!-- ========= RENDU : TEXTE ========= -->
            @if($el['type'] === 'text')
                @php
                    $style = $el['style'] ?? [];
                    $fs    = $style['fontSize'] ?? 16;
                    $color = safeColor($style['color'] ?? '#000000');
                    $ff    = $style['fontFamily'] ?? 'Helvetica';
                    $fw    = $style['fontWeight'] ?? 'normal';
                    $fst   = $style['fontStyle'] ?? 'normal';
                    $align = $style['textAlign'] ?? 'left';
                    $ls    = $style['letterSpacing'] ?? 0;
                    $lh    = $style['lineHeight'] ?? 1.2;
                    $decor = $style['textDecoration'] ?? 'none';

                    // On remplace les {{VARIABLES}}
                    $content = parseVariables($el['content'] ?? '', $vars);
                @endphp
                <div style="font-size: {{ $fs }}px; color: {{ $color }}; font-family: '{{ $ff }}', sans-serif; font-weight: {{ $fw }}; font-style: {{ $fst }}; text-align: {{ $align }}; letter-spacing: {{ $ls }}px; text-decoration: {{ $decor }}; line-height: {{ $lh }};">
                    {!! nl2br(e($content)) !!}
                </div>

            <!-- ========= RENDU : IMAGES, LOGO, SIGNATURE ========= -->
            @elseif(in_array($el['type'], ['logo', 'signature', 'image']))
                @if(!empty($imgContent))
                    <img src="{{ $imgContent }}">
                @endif

            <!-- ========= RENDU : QR CODE ========= -->
            @elseif($el['type'] === 'qr')
                @if(isset($qr_code) && !empty($qr_code))
                    <div style="text-align: center;">
                        <div style="background-color: #ffffff; padding: 5px; box-sizing: border-box; display: inline-block; width: 100%;">
                            <img src="{{ $qr_code }}" style="width: 100%; height: auto; display: block;">
                        </div>
                        <div style="font-family: 'Helvetica', sans-serif; font-size: 8px; color: #333; margin-top: 4px; letter-spacing: 0.5px;">
                            ID: {{ $certificate->certificate_code }}
                        </div>
                    </div>
                @endif

            @endif

        </div>
    @endforeach

</body>
</html>
