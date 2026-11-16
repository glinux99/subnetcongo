<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">
        <script>try { if (localStorage.getItem('snc-dark-mode') === '1') document.documentElement.classList.add('app-dark'); } catch (e) {}</script>

        <!-- SEO Dynamique -->
        <title inertia>{{ config('app.name', 'Subnet Congo') }} | Solutions Internet & Infrastructures IT en RDC</title>
        <meta name="description" content="Subnet Congo Sarl : Fournisseur d'accès Internet (Fibre, VSAT, Radio), maintenance réseau, cybersécurité et formation certifiée en République Démocratique du Congo.">
        <meta name="keywords" content="Internet RDC, VSAT Congo, Fibre optique, Maintenance Informatique, Formation CCNA, Subnet Congo, Télécoms Goma, Kinshasa IT">
        {{-- Développé par Daniel KIKIMBA (glinux99) — infos@danielkikimba.site — https://danielkikimba.site --}}
        <meta name="author" content="Daniel KIKIMBA">
        <meta name="robots" content="index, follow">
        <link rel="canonical" href="{{ url()->current() }}">

        <!-- Favicons -->
        <link rel="icon" type="image/png" href="https://subnetcongo.net/assets/media/logos/logo.png">
        <link rel="apple-touch-icon" href="https://subnetcongo.net/assets/media/logos/logo.png">

        <!-- Open Graph / Facebook (Pour un partage professionnel) -->
        <meta property="og:type" content="website">
        <meta property="og:url" content="{{ url()->current() }}">
        <meta property="og:title" content="Subnet Congo - Connecter la RDC au Monde">
        <meta property="og:description" content="Solutions de connectivité haut débit, audit réseau et formations professionnelles pour entreprises et institutions.">
        <meta property="og:image" content="https://subnetcongo.net/assets/media/logos/logo.png">
        <meta property="og:site_name" content="Subnet Congo Sarl">

        <!-- Twitter -->
        <meta name="twitter:card" content="summary_large_image">
        <meta name="twitter:title" content="Subnet Congo - Leader en Télécoms & IT">
        <meta name="twitter:description" content="Expertise en réseaux, fibre optique et maintenance IT en RDC.">
        <meta name="twitter:image" content="https://subnetcongo.net/assets/media/logos/logo.png">

        <!-- Polices -->
        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
        <link href="https://fonts.cdnfonts.com/css/lato" rel="stylesheet">

        <!-- Données Structurées JSON-LD (Organization + LocalBusiness + WebSite) -->
        <script type="application/ld+json">
        {
            "@context": "https://schema.org",
            "@type": ["Organization", "LocalBusiness"],
            "name": "Subnet Congo Sarl",
            "legalName": "SubNet Congo Sarl",
            "url": "https://subnetcongo.net",
            "logo": "https://subnetcongo.net/assets/media/logos/logo.png",
            "image": "https://subnetcongo.net/assets/media/logos/logo.png",
            "telephone": "+243 973 359 648",
            "email": "academy@subnetcongo.net",
            "address": {
                "@type": "PostalAddress",
                "streetAddress": "Av. du Touriste 001, Quartier Les Volcans",
                "addressLocality": "Goma",
                "addressRegion": "Nord-Kivu",
                "addressCountry": "CD"
            },
            "areaServed": [
                { "@type": "Country", "name": "République Démocratique du Congo" },
                { "@type": "City", "name": "Goma" },
                { "@type": "City", "name": "Kinshasa" }
            ],
            "contactPoint": [
                {
                    "@type": "ContactPoint",
                    "telephone": "+243 973 359 648",
                    "email": "academy@subnetcongo.net",
                    "contactType": "customer service",
                    "availableLanguage": ["French", "English"]
                },
                {
                    "@type": "ContactPoint",
                    "contactType": "sales",
                    "availableLanguage": ["French", "English"]
                }
            ],
            "sameAs": [
                "https://facebook.com/subnetcongo",
                "https://twitter.com/subnetcongo",
                "https://linkedin.com/company/subnet-congo"
            ],
            "description": "Fournisseur d'accès Internet (Fibre, VSAT, Radio), maintenance réseau, cybersécurité et formation certifiée en République Démocratique du Congo."
        }
        </script>
        <script type="application/ld+json">
        {
            "@context": "https://schema.org",
            "@type": "WebSite",
            "name": "Subnet Congo",
            "url": "https://subnetcongo.net",
            "potentialAction": {
                "@type": "SearchAction",
                "target": "https://subnetcongo.net/marketplace?search={search_term_string}",
                "query-input": "required name=search_term_string"
            }
        }
        </script>

        <!-- Scripts & Styles -->
        @routes
        @vite(['resources/js/app.js', "resources/js/Pages/{$page['component']}.vue"])
        @inertiaHead

        <style>
            /* Optimisation du chargement */
            body { margin: 0; font-family: 'Lato', sans-serif; }
            [v-cloak] { display: none; }
        </style>
    </head>
    <body class="font-sans antialiased bg-gray-50">
        @inertia
    </body>
</html>
