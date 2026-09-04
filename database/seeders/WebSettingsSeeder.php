<?php

namespace Database\Seeders;

use App\Models\Menu;
use App\Models\WebSetting;
use Illuminate\Database\Seeder;

class WebSettingsSeeder extends Seeder
{
    public function run(): void
    {
        // ── Identité générale ─────────────────────────────────────
        $general = [
            'site_name'        => 'SubnetCongo',
            'site_tagline'     => 'Centre de formation et d\'excellence numérique en RDC',
            'site_description' => 'SubnetCongo forme les experts de demain en réseaux, cybersécurité, cloud et développement, et déploie des infrastructures connectiques sur l\'ensemble du territoire congolais.',
            'site_logo'        => null,
            'site_favicon'     => null,
            'footer_text'      => '© ' . date('Y') . ' SubnetCongo. Tous droits réservés.',
        ];
        foreach ($general as $k => $v) WebSetting::set($k, $v, 'general', 'text');

        // ── Contact ───────────────────────────────────────────────
        $contact = [
            'contact_email'   => 'contact@subnetcongo.cd',
            'contact_phone'   => '+243 000 000 000',
            'contact_address' => 'Avenue de la Liberation, Commune de la Gombe, Kinshasa, RDC',
        ];
        foreach ($contact as $k => $v) WebSetting::set($k, $v, 'contact', 'text');

        // ── Réseaux sociaux ───────────────────────────────────────
        $social = [
            'social_facebook'  => 'https://facebook.com/subnetcongo',
            'social_twitter'   => 'https://twitter.com/subnetcongo',
            'social_linkedin'  => 'https://linkedin.com/company/subnetcongo',
            'social_youtube'   => 'https://youtube.com/@subnetcongo',
            'social_instagram' => 'https://instagram.com/subnetcongo',
        ];
        foreach ($social as $k => $v) WebSetting::set($k, $v, 'social', 'url');

        // ── Section "About" (utilisée par Landing + page About) ───
        $about = [
            'tagline'         => 'Bâtir une élite numérique congolaise',
            'history'         => 'Depuis sa création, SubnetCongo œuvre à la formation d\'une nouvelle génération d\'ingénieurs et techniciens capable de concevoir, déployer et maintenir des infrastructures numériques de classe mondiale sur l\'ensemble du territoire de la RDC.',
            'services_title'  => 'Connecter, former, transformer',
            'services'        => json_encode([
                [
                    'title' => 'VSAT & Satellite Elite',
                    'icon'  => 'pi-globe',
                    'desc'  => 'L\'accès ultime pour les sites isolés. Forfaits illimités adaptés aux besoins industriels et humanitaires.',
                    'features' => ['Bandes Ku, Ka & C', 'Priorisation du trafic (QoS)', 'Installation en moins de 48h'],
                ],
                [
                    'title' => 'Starlink Business Pro',
                    'icon'  => 'pi-bolt',
                    'desc'  => 'Vitesse révolutionnaire jusqu\'à 350 Mbps. Installateur certifié pour une stabilité maximale.',
                    'features' => ['Kits Haute Performance', 'Support technique local', 'Facturation RDC simplifiée'],
                ],
                [
                    'title' => 'Fibre Noire Dédiée',
                    'icon'  => 'pi-sitemap',
                    'desc'  => 'Connexions symétriques Giga-bit pour les datacenters et entreprises multisites.',
                    'features' => ['Latence < 10ms', 'Monitoring 24/7/365', 'Garantie Temps de Rétablissement'],
                ],
            ], JSON_UNESCAPED_UNICODE),
            'step1_title'     => 'Consultation',
            'step1_desc'      => 'Analyse de vos besoins spécifiques par nos ingénieurs.',
            'step2_title'     => 'Déploiement',
            'step2_desc'      => 'Installation rigoureuse par nos équipes mobiles partout en RDC.',
            'step3_title'     => 'Formation',
            'step3_desc'      => 'Transfert de compétences pour une autonomie totale.',
            'step4_title'     => 'Maintenance',
            'step4_desc'      => 'Suivi proactif et assistance immédiate en cas d\'alerte.',
            'faqs'            => json_encode([
                ['q' => 'Proposez-vous Starlink partout en RDC ?',  'a' => 'Oui, nous couvrons l\'intégralité du territoire national, des zones urbaines aux sites les plus isolés.'],
                ['q' => 'Les formations sont-elles reconnues ?',   'a' => 'Nos formations sont certifiantes et alignées sur les standards internationaux (Cisco, CompTIA, AWS, etc.).'],
                ['q' => 'Comment se passe le paiement ?',          'a' => 'Nous acceptons Orange Money, M-Pesa, Airtel Money, cartes bancaires et virements.'],
                ['q' => 'Puis-je suivre les cours à mon rythme ?','a' => 'Oui, tous nos cours sont accessibles en replay dès leur publication.'],
            ], JSON_UNESCAPED_UNICODE),
        ];
        foreach ($about as $k => $v) {
            $type = in_array($k, ['services', 'faqs']) ? 'json' : 'text';
            WebSetting::set($k, $v, 'about', $type);
        }

        // ── Section Hero (slides pour la page d'accueil) ──────────
        $hero = [
            'slides' => json_encode([
                [
                    'badge'     => 'Leader Connectivité RDC',
                    'title'     => 'Désenclaver le',
                    'highlight' => 'Congo par le Web.',
                    'description' => 'Déploiement stratégique d\'antennes VSAT et solutions Starlink pour les zones minières et forestières reculées. Latence record, productivité sans limites.',
                    'bgImage'   => 'https://images.unsplash.com/photo-1544652478-6653e09f18a2?q=80&w=2070',
                    'mainImage' => 'https://images.unsplash.com/photo-1573166364524-d9dbfd8bbf83?q=80&w=2069',
                    'icon'      => 'pi-map-marker',
                    'cardTitle' => 'Omniprésence Nationale',
                    'cardSub'   => 'Couverture 26 provinces garantie.',
                ],
                [
                    'badge'     => 'Élite Technologique',
                    'title'     => 'Façonner les',
                    'highlight' => 'Experts de Demain.',
                    'description' => 'Plongez dans un écosystème d\'apprentissage de classe mondiale. De la certification Cisco CCNA à l\'Audit de Sécurité, boostez votre carrière internationale.',
                    'bgImage'   => 'https://images.unsplash.com/photo-1550751827-4bd374c3f58b?q=80&w=2070',
                    'mainImage' => 'https://images.unsplash.com/photo-1516321318423-f06f85e504b3?q=80&w=2070',
                    'icon'      => 'pi-book',
                    'cardTitle' => 'Certification Cisco Pro',
                    'cardSub'   => '92% de taux de réussite aux examens.',
                ],
                [
                    'badge'     => 'Souveraineté Numérique',
                    'title'     => 'Bâtir les',
                    'highlight' => 'Routes du Digital.',
                    'description' => 'Maintenance critique, tirage de fibre optique noire et ingénierie de réseaux complexes pour les sièges sociaux et institutions gouvernementales.',
                    'bgImage'   => 'https://images.unsplash.com/photo-1558494949-ef010911182e?q=80&w=2070',
                    'mainImage' => 'https://images.unsplash.com/photo-1551703599-6b3e8379aa8c?q=80&w=2070',
                    'icon'      => 'pi-cog',
                    'cardTitle' => 'Uptime 99.99%',
                    'cardSub'   => 'Monitoring NOC en temps réel.',
                ],
            ], JSON_UNESCAPED_UNICODE),
        ];
        foreach ($hero as $k => $v) WebSetting::set($k, $v, 'hero', 'json');

        // ── Mentions Légales (contenu éditable par l'admin) ────────
        $legal = [
            'legal_page_title'   => 'Mentions Légales',
            'legal_updated_at'   => '4 septembre 2026',
            'legal_intro_html'   => '<p>Conformément aux dispositions de la loi congolaise n° 20/017 du 25 novembre 2020 relative au numérique et aux règlements en vigueur en République Démocratique du Congo.</p>',
            'legal_section_1_html' => '<p>Le site <strong>SubnetCongo</strong> est édité par :</p><ul class="list-none space-y-2 mt-4"><li><strong>Dénomination sociale :</strong> SUBNETCONGO SARL</li><li><strong>Forme juridique :</strong> Société à Responsabilité Limitée</li><li><strong>Capital social :</strong> 10 000 000 CDF (Dix millions de Francs Congolais)</li><li><strong>RCCM :</strong> CD/KIN/RCCM/25-B-00000</li><li><strong>ID.NAT :</strong> 01-9-N00000A</li><li><strong>Numéro d\'impôt :</strong> A123456789B</li><li><strong>Siège social :</strong> Avenue de la Liberation, Commune de la Gombe, Kinshasa, RDC</li><li><strong>Téléphone :</strong> +243 000 000 000</li><li><strong>Email :</strong> contact@subnetcongo.cd</li><li><strong>Site web :</strong> www.subnetcongo.cd</li><li><strong>Directeur de publication :</strong> Monsieur le Gérant de SUBNETCONGO SARL</li></ul>',
            'legal_section_2_html' => '<p>Le site est hébergé par :</p><ul class="list-none space-y-2 mt-4"><li><strong>Hébergeur :</strong> SUBNETCONGO SARL</li><li><strong>Adresse :</strong> Avenue de la Liberation, Commune de la Gombe, Kinshasa, RDC</li><li><strong>Pays :</strong> République Démocratique du Congo</li></ul>',
            'legal_section_3_html' => '<p>L\'ensemble du contenu du site SubnetCongo — incluant, de manière non limitative, les textes, graphiques, logos, icônes, images, clips audio, vidéos, logiciels et leur compilation — est la propriété exclusive de SUBNETCONGO SARL ou de ses partenaires et est protégé par les lois congolaises et internationales relatives à la propriété intellectuelle, notamment le Code de la Propriété Intellectuelle congolais et les conventions internationales dont la RDC est signataire (Convention de Berne, Accord TRIPS).</p><p>Toute reproduction, représentation, modification, publication, adaptation de tout ou partie des éléments du site, quel que soit le moyen ou le procédé utilisé, est interdite sans l\'autorisation écrite préalable de SUBNETCONGO SARL.</p><p>Toute exploitation non autorisée du site ou de l\'un quelconque des éléments qu\'il contient sera considérée comme constitutive d\'une contrefaçon et poursuivie conformément aux dispositions des articles 79 à 91 du Code Pénal Congolais (Loi n° 09/001 du 10 janvier 2009).</p>',
            'legal_section_4_html' => '<p>Conformément à la <strong>Loi n° 20/017 du 25 novembre 2020</strong> portant protection des données à caractère personnel en République Démocratique du Congo, les données personnelles collectées sur ce site font l\'objet d\'un traitement informatique destiné aux besoins de SUBNETCONGO SARL.</p><p>Les données collectées sont nécessaires au fonctionnement des services proposés : création de compte, formation en ligne, suivi pédagogique, facturation, support client et communications relatives à nos services.</p><p>Conformément à la législation en vigueur, vous disposez d\'un droit d\'accès, de rectification, d\'effacement et d\'opposition aux données vous concernant. Pour exercer ces droits, veuillez nous contacter à l\'adresse : <strong>contact@subnetcongo.cd</strong>.</p>',
            'legal_section_5_html' => '<p>L\'utilisation du site SubnetCongo implique l\'acceptation pleine et entière des conditions générales d\'utilisation décrites ci-après. Ces conditions sont susceptibles d\'être modifiées ou complétées à tout moment.</p><p>Le site et ses différents éléments sont fournis « en l\'état ». SUBNETCONGO SARL ne pourra être tenue responsable des dommages directs ou indirects résultant de l\'utilisation du site, notamment la perte de données, les interruptions de service, les virus ou tout autre problème technique.</p><p>L\'utilisateur s\'engage à utiliser le site de manière licite et à respecter les droits de propriété intellectuelle de SUBNETCONGO SARL et de ses partenaires.</p>',
            'legal_section_6_html' => '<p>SUBNETCONGO SARL s\'efforce d\'assurer l\'exactitude et la mise à jour des informations diffusées sur ce site, dont elle se réserve le droit de modifier le contenu à tout moment et sans préavis.</p><p>Les informations contenues sur ce site sont aussi précises que possible et sont actualisées régulièrement. SUBNETCONGO SARL ne pourra être tenue responsable des omissions, des inexactitudes et des carences dans la mise à jour, qu\'elles soient de son fait ou du fait des tiers partenaires qui lui fournissent ces informations.</p><p>Les liens hypertextes mis en place dans le cadre du présent site en direction d\'autres sites présents sur le réseau Internet ne sauraient engager la responsabilité de SUBNETCONGO SARL, notamment en ce qui concerne leur contenu.</p>',
            'legal_section_7_html' => '<p>Le présent site et ses conditions d\'utilisation sont régis par le <strong>droit congolais</strong>, notamment la loi n° 20/017 du 25 novembre 2020 relative au numérique, la loi sur le commerce électronique et le Code Civil congolais.</p><p>Tout litige né de l\'utilisation du site ou de l\'interprétation des présentes mentions légales sera soumis à la <strong>juridiction des tribunaux compétents de la République Démocratique du Congo</strong>, et plus particulièrement aux tribunaux de Kinshasa/Gombe.</p>',
            'legal_section_8_html' => '<p>Pour toute question relative aux présentes mentions légales ou pour toute réclamation, vous pouvez contacter SUBNETCONGO SARL :</p><ul class="list-none space-y-2 mt-4"><li><strong>Email :</strong> contact@subnetcongo.cd</li><li><strong>Téléphone :</strong> +243 000 000 000</li><li><strong>Adresse :</strong> Avenue de la Liberation, Commune de la Gombe, Kinshasa, RDC</li></ul>',
        ];
        foreach ($legal as $k => $v) WebSetting::set($k, $v, 'legal', 'html');

        // ── Politique de Confidentialité (contenu éditable par l'admin) ──
        $privacy = [
            'page_title'   => 'Politique de Confidentialité',
            'updated_at'   => '4 septembre 2026',
            'intro_html'   => '<p>Votre vie privée est essentielle pour SubnetCongo. Découvrez comment nous protégeons vos données personnelles conformément à la législation congolaise.</p><p>Dernière mise à jour : 4 septembre 2026</p>',
            'section_1_html' => '<p><strong>SubnetCongo</strong> (ci-après « nous » ou « SubnetCongo ») est la plateforme de formation en ligne et de services technologiques de <strong>SUBNETCONGO SARL</strong>, société à responsabilité limitée de droit congolais, immatriculée au RCCM de Kinshasa sous le numéro CD/KIN/RCCM/25-B-00000, dont le siège social est situé à Avenue de la Liberation, Commune de la Gombe, Kinshasa, RDC.</p><p>En tant que responsable de traitement, nous nous engageons à protéger la confidentialité de vos données personnelles et à garantir un niveau de sécurité élevé conformément à la <strong>Loi n° 20/017 du 25 novembre 2020</strong> portant protection des données à caractère personnel en République Démocratique du Congo, ainsi qu\'au Règlement (UE) 2016/679 (RGPD) pour les ressortissants européens et à toute réglementation applicable.</p>',
            'section_2_html' => '<p>Nous collectons les catégories de données personnelles suivantes :</p><div class="mt-6 bg-slate-50 rounded-xl p-8 space-y-4"><div><h4 class="font-black text-slate-900 uppercase tracking-wider text-sm">Données d\'identification</h4><p class="text-slate-600 mt-1">Nom, prénom, adresse email, numéro de téléphone, pays, ville, genre, date de naissance.</p></div><div><h4 class="font-black text-slate-900 uppercase tracking-wider text-sm">Données de connexion et navigation</h4><p class="text-slate-600 mt-1">Adresse IP, type de navigateur, système d\'exploitation, pages visitées, durée de visite, cookies et identifiants de session.</p></div><div><h4 class="font-black text-slate-900 uppercase tracking-wider text-sm">Données pédagogiques</h4><p class="text-slate-600 mt-1">Progression dans les cours, résultats aux examens et quiz, certificats obtenus, feedback et évaluations laissés.</p></div><div><h4 class="font-black text-slate-900 uppercase tracking-wider text-sm">Données de paiement</h4><p class="text-slate-600 mt-1">Informations de facturation, historique des transactions, moyens de paiement utilisés (Mobile Money, carte bancaire, etc.). Les données sensibles de carte bancaire ne sont jamais stockées sur nos serveurs — elles sont traitées par nos prestataires de paiement certifiés PCI DSS.</p></div><div><h4 class="font-black text-slate-900 uppercase tracking-wider text-sm">Données de communication</h4><p class="text-slate-600 mt-1">Messages envoyés via le formulaire de contact, tickets de support, correspondances échangées avec notre équipe.</p></div></div>',
            'section_3_html' => '<p>Nous collectons et traitons vos données personnelles pour les finalités suivantes :</p><ul class="list-disc pl-6 space-y-2 mt-4"><li><strong>Exécution du contrat :</strong> création et gestion de votre compte utilisateur, accès aux formations, suivi de votre progression, délivrance de certificats, gestion des inscriptions et paiements.</li><li><strong>Intérêt légitime :</strong> amélioration de nos services, personnalisation de votre expérience, prévention de la fraude, sécurité des systèmes informatiques, analyses statistiques anonymisées.</li><li><strong>Obligation légale :</strong> respect des obligations comptables et fiscales, lutte contre le blanchiment d\'argent, conservation des données requise par la loi congolaise.</li><li><strong>Consentement :</strong> envoi de newsletters et communications marketing (vous pouvez vous désinscrire à tout moment).</li></ul>',
            'section_4_html' => '<p>Vos données personnelles sont conservées pendant des durées strictement nécessaires à la réalisation des finalités pour lesquelles elles ont été collectées :</p><div class="mt-6 bg-slate-50 rounded-xl p-8 space-y-3"><div class="flex justify-between items-center py-2 border-b border-slate-200"><span class="font-semibold text-slate-900">Données de compte actif</span><span class="text-slate-600">Durée de vie du compte + 3 ans (prescription légale)</span></div><div class="flex justify-between items-center py-2 border-b border-slate-200"><span class="font-semibold text-slate-900">Données de paiement / facturation</span><span class="text-slate-600">10 ans (obligation légale fiscale)</span></div><div class="flex justify-between items-center py-2 border-b border-slate-200"><span class="font-semibold text-slate-900">Données pédagogiques (progression, examens)</span><span class="text-slate-600">Durée de vie du compte + 5 ans</span></div><div class="flex justify-between items-center py-2 border-b border-slate-200"><span class="font-semibold text-slate-900">Données de navigation / cookies</span><span class="text-slate-600">13 mois maximum</span></div><div class="flex justify-between items-center py-2"><span class="font-semibold text-slate-900">Logs de connexion et sécurité</span><span class="text-slate-600">12 mois</span></div></div>',
            'section_5_html' => '<p>Vos données personnelles ne sont jamais vendues à des tiers. Elles peuvent être partagées uniquement dans les cas suivants :</p><ul class="list-disc pl-6 space-y-2 mt-4"><li><strong>Prestataires de services :</strong> hébergeurs, prestataires de paiement (Mobile Money, cartes bancaires), services d\'emailing, outils d\'analyse (Google Analytics, etc.) — tous soumis à des obligations de confidentialité contractuelles strictes.</li><li><strong>Autorités légales :</strong> uniquement sur réquisition judiciaire ou demande des autorités compétentes de la RDC, conformément à la loi.</li><li><strong>Instructeurs et formateurs :</strong> vos noms et adresses email peuvent être partagés avec les instructeurs des formations que vous suivez, dans le cadre exclusif de l\'enseignement.</li></ul>',
            'section_6_html' => '<p>Conformément à la <strong>Loi n° 20/017 du 25 novembre 2020</strong> et, le cas échéant, au RGPD, vous disposez des droits suivants sur vos données personnelles :</p><div class="grid grid-cols-1 md:grid-cols-2 gap-4 mt-6"><div class="p-6 bg-slate-50 rounded-xl border border-slate-200"><h4 class="font-black text-slate-900 text-sm uppercase tracking-wider">Droit d\'accès</h4><p class="text-sm text-slate-600 mt-1">Obtenir une copie des données que nous détenons sur vous.</p></div><div class="p-6 bg-slate-50 rounded-xl border border-slate-200"><h4 class="font-black text-slate-900 text-sm uppercase tracking-wider">Droit de rectification</h4><p class="text-sm text-slate-600 mt-1">Corriger toute donnée inexacte ou incomplète.</p></div><div class="p-6 bg-slate-50 rounded-xl border border-slate-200"><h4 class="font-black text-slate-900 text-sm uppercase tracking-wider">Droit à l\'effacement</h4><p class="text-sm text-slate-600 mt-1">Demander la suppression de vos données dans les cas prévus par la loi.</p></div><div class="p-6 bg-slate-50 rounded-xl border border-slate-200"><h4 class="font-black text-slate-900 text-sm uppercase tracking-wider">Droit d\'opposition</h4><p class="text-sm text-slate-600 mt-1">Vous opposer au traitement de vos données pour des raisons légitimes.</p></div><div class="p-6 bg-slate-50 rounded-xl border border-slate-200"><h4 class="font-black text-slate-900 text-sm uppercase tracking-wider">Droit à la portabilité</h4><p class="text-sm text-slate-600 mt-1">Recevoir vos données dans un format structuré et transmissible.</p></div><div class="p-6 bg-slate-50 rounded-xl border border-slate-200"><h4 class="font-black text-slate-900 text-sm uppercase tracking-wider">Droit de retirer votre consentement</h4><p class="text-sm text-slate-600 mt-1">À tout moment, sans affecter la licéité du traitement antérieur.</p></div></div><p class="mt-6">Pour exercer l\'un de ces droits, veuillez nous adresser une demande écrite à : <strong>contact@subnetcongo.cd</strong> ou via notre formulaire de contact. Nous vous répondrons dans un délai maximum de <strong>30 jours</strong>, conformément à la législation en vigueur.</p>',
            'section_7_html' => '<p>Nous utilisons des cookies et technologies similaires pour améliorer votre expérience sur notre site, analyser le trafic et personnaliser le contenu. Vous pouvez configurer votre navigateur pour refuser les cookies, mais certaines fonctionnalités du site pourraient ne pas fonctionner correctement.</p><p>Nous utilisons les types de cookies suivants :</p><ul class="list-disc pl-6 space-y-2 mt-4"><li><strong>Cookies essentiels :</strong> nécessaires au fonctionnement du site (authentification, panier, sécurité).</li><li><strong>Cookies analytiques :</strong> nous permettent de comprendre comment vous utilisez le site pour l\'améliorer.</li><li><strong>Cookies de fonctionnalité :</strong> mémorisent vos préférences (langue, pays, etc.).</li><li><strong>Cookies de marketing :</strong> utilisés pour vous proposer des contenus personnalisés (uniquement avec votre consentement).</li></ul>',
            'section_8_html' => '<p>SUBNETCONGO SARL met en œuvre des mesures de sécurité techniques et organisationnelles appropriées pour protéger vos données personnelles contre tout accès non autorisé, modification, divulgation ou destruction :</p><ul class="list-disc pl-6 space-y-2 mt-4"><li>Chiffrement TLS/SSL sur l\'ensemble des communications</li><li>Hachage et salage des mots de passe (bcrypt)</li><li>Sauvegardes régulières et sécurisées</li><li>Contrôle d\'accès basé sur les rôles (RBAC)</li><li>Surveillance continue de la sécurité (logs, alertes)</li><li>Formation du personnel à la protection des données</li></ul>',
            'section_9_html' => '<p>Vos données sont principalement stockées et traitées en <strong>République Démocratique du Congo</strong> et dans l\'Union Européenne (pour les services d\'hébergement certifiés). Tout transfert de données hors de ces juridictions est soumis à des garanties appropriées (clauses contractuelles types, Privacy Shield pour les États-Unis, etc.) conformément à la législation applicable.</p>',
            'section_10_html' => '<p>Pour toute question relative à la présente politique de confidentialité, pour exercer vos droits ou pour toute réclamation, vous pouvez contacter notre Délégué à la Protection des Données (DPO) :</p><ul class="list-none space-y-2 mt-4"><li><strong>Email :</strong> contact@subnetcongo.cd</li><li><strong>Téléphone :</strong> +243 000 000 000</li><li><strong>Adresse :</strong> Avenue de la Liberation, Commune de la Gombe, Kinshasa, RDC</li></ul><p class="mt-4">Si vous estimez que votre plainte n\'a pas été traitée de manière satisfaisante, vous avez le droit de saisir l\'<strong>Autorité de Protection des Données à caractère Personnel de la RDC</strong> ou toute autre autorité de contrôle compétente.</p>',
        ];
        foreach ($privacy as $k => $v) WebSetting::set($k, $v, 'privacy', 'html');

        // ── Menus par défaut ──────────────────────────────────────
        $menus = [
            [
                'name'     => 'Header principal',
                'location' => 'header',
                'is_active' => true,
                'items'    => [
                    ['label' => 'Accueil',       'url' => '/',            'target' => '_self'],
                    ['label' => 'Académie',      'url' => '/academy',     'target' => '_self'],
                    ['label' => 'Réalisations',  'url' => '/realizations','target' => '_self'],
                    ['label' => 'Blog',          'url' => '/blog',        'target' => '_self'],
                    ['label' => 'À propos',      'url' => '/about',       'target' => '_self'],
                    ['label' => 'Contact',       'url' => '/contact',     'target' => '_self'],
                ],
            ],
            [
                'name'     => 'Footer ressources',
                'location' => 'footer',
                'is_active' => true,
                'items'    => [
                    ['label' => 'Académie',     'url' => '/academy',     'target' => '_self'],
                    ['label' => 'Témoignages',  'url' => '/testimonials','target' => '_self'],
                    ['label' => 'Blog',         'url' => '/blog',        'target' => '_self'],
                ],
            ],
        ];

        foreach ($menus as $m) {
            Menu::updateOrCreate(
                ['name' => $m['name']],
                $m
            );
        }
    }
}
