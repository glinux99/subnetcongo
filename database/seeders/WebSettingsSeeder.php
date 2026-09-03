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
