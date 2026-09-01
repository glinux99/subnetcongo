<?php

namespace Database\Seeders;

use App\Models\Category;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class CategorySeeder extends Seeder
{
    public function run(): void
    {
        $categories = [
            [
                'name' => 'Infrastructure & Réseaux',
                'icon' => 'pi-sitemap',
                'description' => 'Conception, configuration et maintenance des architectures réseaux Cisco, MikroTik et Ubiquiti.'
            ],
            [
                'name' => 'Cybersécurité',
                'icon' => 'pi-shield',
                'description' => 'Protection des systèmes, ethical hacking, audit de sécurité et défense des infrastructures.'
            ],
            [
                'name' => 'Télécoms & Satellites',
                'icon' => 'pi-globe',
                'description' => 'Déploiement de la fibre optique (FTTH), technologies VSAT et solutions Starlink Business.'
            ],
            [
                'name' => 'Cloud & Virtualisation',
                'icon' => 'pi-cloud',
                'description' => 'Administration de serveurs Cloud AWS, Azure et environnements virtualisés Proxmox/VMware.'
            ],
            [
                'name' => 'Développement Web & App',
                'icon' => 'pi-code',
                'description' => 'Création d’applications modernes avec Laravel, Vue.js, Inertia et architectures API.'
            ],
            [
                'name' => 'Systèmes & Open Source',
                'icon' => 'pi-desktop',
                'description' => 'Maîtrise de l’administration Linux (LPI), Windows Server et gestion de parcs informatiques.'
            ],
        ];

        foreach ($categories as $cat) {
            Category::create([
                'name' => $cat['name'],
                'slug' => Str::slug($cat['name']),
                'icon' => $cat['icon'],
                'description' => $cat['description'],
                'is_active' => true,
            ]);
        }
    }
}