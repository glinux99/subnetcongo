<?php

namespace Database\Seeders;

use App\Models\LandingSlide;
use App\Models\LandingStat;
use App\Models\LandingTechnology;
use Illuminate\Database\Seeder;

class LandingContentSeeder extends Seeder
{
    public function run(): void
    {
        // ── SLIDES ─────────────────────────────────────────────
        // Cleanup legacy test slides
        LandingSlide::whereNotIn('badge', [
            'Leader Connectivité RDC',
            'Élite Technologique',
            'Souveraineté Numérique',
        ])->delete();

        $slides = [
            [
                'badge'      => 'Leader Connectivité RDC',
                'title'      => 'Désenclaver le',
                'highlight'  => 'Congo par le Web.',
                'description'=> 'Déploiement stratégique d\'antennes VSAT et solutions Starlink pour les zones minières et forestières reculées. Latence record, productivité sans limites.',
                'bg_image'   => 'https://images.unsplash.com/photo-1544652478-6653e09f18a2?q=80&w=2070',
                'main_image' => 'https://images.unsplash.com/photo-1573166364524-d9dbfd8bbf83?q=80&w=2069',
                'icon'       => 'pi-map-marker',
                'card_title' => 'Omniprésence Nationale',
                'card_sub'   => 'Couverture 26 provinces garantie.',
                'cta_label'  => 'Découvrir nos solutions',
                'cta_url'    => '/academy',
                'position'   => 1,
                'is_active'  => true,
            ],
            [
                'badge'      => 'Élite Technologique',
                'title'      => 'Façonner les',
                'highlight'  => 'Experts de Demain.',
                'description'=> 'Plongez dans un écosystème d\'apprentissage de classe mondiale. De la certification Cisco CCNA à l\'Audit de Sécurité, boostez votre carrière internationale.',
                'bg_image'   => 'https://images.unsplash.com/photo-1550751827-4bd374c3f58b?q=80&w=2070',
                'main_image' => 'https://images.unsplash.com/photo-1516321318423-f06f85e504b3?q=80&w=2070',
                'icon'       => 'pi-book',
                'card_title' => 'Certification Cisco Pro',
                'card_sub'   => '92% de taux de réussite aux examens.',
                'cta_label'  => 'Voir les cours',
                'cta_url'    => '/academy',
                'position'   => 2,
                'is_active'  => true,
            ],
            [
                'badge'      => 'Souveraineté Numérique',
                'title'      => 'Bâtir les',
                'highlight'  => 'Routes du Digital.',
                'description'=> 'Maintenance critique, tirage de fibre optique noire et ingénierie de réseaux complexes pour les sièges sociaux et institutions gouvernementales.',
                'bg_image'   => 'https://images.unsplash.com/photo-1558494949-ef010911182e?q=80&w=2070',
                'main_image' => 'https://images.unsplash.com/photo-1551703599-6b3e8379aa8c?q=80&w=2070',
                'icon'       => 'pi-cog',
                'card_title' => 'Uptime 99.99%',
                'card_sub'   => 'Monitoring NOC en temps réel.',
                'cta_label'  => 'Nous contacter',
                'cta_url'    => '/contact',
                'position'   => 3,
                'is_active'  => true,
            ],
        ];
        foreach ($slides as $s) LandingSlide::updateOrCreate(['badge' => $s['badge']], $s);

        // ── TECHNOLOGIES ───────────────────────────────────────
        $techs = [
            ['name' => 'Cisco Networking',   'slug' => 'cisco',      'icon' => 'pi-server',         'color' => '#0EA5E9', 'category' => 'Réseau',         'description' => 'CCNA, CCNP, routage & switching.', 'position' => 1],
            ['name' => 'Cybersécurité',      'slug' => 'security',   'icon' => 'pi-shield',         'color' => '#EF4444', 'category' => 'Sécurité',       'description' => 'Pentesting, audit, SOC.',         'position' => 2],
            ['name' => 'Cloud AWS',          'slug' => 'aws',        'icon' => 'pi-cloud',          'color' => '#F59E0B', 'category' => 'Cloud',          'description' => 'Architectures AWS certifiantes.', 'position' => 3],
            ['name' => 'Linux & DevOps',     'slug' => 'linux',      'icon' => 'pi-desktop',        'color' => '#10B981', 'category' => 'Systèmes',       'description' => 'Administration, Ansible, Docker.', 'position' => 4],
            ['name' => 'VSAT & Satellite',   'slug' => 'vsat',       'icon' => 'pi-compass',        'color' => '#8B5CF6', 'category' => 'Télécoms',       'description' => 'Haut débit pour sites isolés.',  'position' => 5],
            ['name' => 'Fibre Optique',      'slug' => 'fiber',      'icon' => 'pi-share-alt',      'color' => '#003399', 'category' => 'Télécoms',       'description' => 'Tirage, soudure, certification.',  'position' => 6],
            ['name' => 'Python & IA',        'slug' => 'python',     'icon' => 'pi-code',           'color' => '#3B82F6', 'category' => 'Développement',  'description' => 'Scripting, ML, automatisation.',   'position' => 7],
            ['name' => 'Starlink Business',  'slug' => 'starlink',   'icon' => 'pi-bolt',           'color' => '#EE1C25', 'category' => 'Connectivité',   'description' => 'Installation et exploitation.',   'position' => 8],
        ];
        foreach ($techs as $t) LandingTechnology::updateOrCreate(['slug' => $t['slug']], $t);

        // ── STATS ──────────────────────────────────────────────
        $stats = [
            ['label' => 'Apprenants formés',     'value' => '3 500+', 'icon' => 'pi-users',         'color' => '#003399', 'position' => 1],
            ['label' => 'Cours & Modules',       'value' => '120+',   'icon' => 'pi-book',          'color' => '#EE1C25', 'position' => 2],
            ['label' => 'Experts Certifiés',     'value' => '25+',    'icon' => 'pi-verified',      'color' => '#10B981', 'position' => 3],
            ['label' => 'Taux de réussite',      'value' => '98%',    'icon' => 'pi-chart-line',    'color' => '#F59E0B', 'position' => 4],
            ['label' => 'Sites VSAT actifs',     'value' => '850+',   'icon' => 'pi-globe',         'color' => '#8B5CF6', 'position' => 5],
            ['label' => 'Support Technique',     'value' => '24/7',   'icon' => 'pi-headphones',    'color' => '#0EA5E9', 'position' => 6],
        ];
        foreach ($stats as $s) LandingStat::updateOrCreate(['label' => $s['label']], $s);
    }
}
