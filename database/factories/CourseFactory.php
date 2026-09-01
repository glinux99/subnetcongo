<?php

namespace Database\Factories;

use App\Models\Category;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class CourseFactory extends Factory
{
    private array $courseData = [
        [
            'title' => 'Cisco CCNA 200-301 : Le Guide Complet',
            'category' => 'Infrastructure & Réseaux',
            'short' => 'Devenez un expert en routage et commutation Cisco.',
            'tags' => ['Cisco', 'CCNA', 'Networking'],
            'level' => 'beginner'
        ],
        [
            'title' => 'Ethical Hacking : Pentesting Avancé',
            'category' => 'Cybersécurité',
            'short' => 'Apprenez les techniques des hackers pour mieux protéger les entreprises.',
            'tags' => ['Hacking', 'Kali Linux', 'Security'],
            'level' => 'advanced'
        ],
        [
            'title' => 'Starlink for Business : Installation Pro',
            'category' => 'Télécoms & Satellites',
            'short' => 'Déployez l’internet par satellite dans les zones les plus reculées.',
            'tags' => ['Starlink', 'Satellite', 'Telecom'],
            'level' => 'intermediate'
        ],
        [
            'title' => 'Maîtriser Docker & Kubernetes',
            'category' => 'Cloud & Virtualisation',
            'short' => 'Conteneurisez vos applications et gérez leur orchestration à l’échelle.',
            'tags' => ['Docker', 'DevOps', 'Cloud'],
            'level' => 'advanced'
        ],
        [
            'title' => 'Laravel 11 & Vue.js 3 : Masterclass',
            'category' => 'Développement Web & App',
            'short' => 'Bâtissez des applications SaaS robustes et scalables.',
            'tags' => ['PHP', 'Laravel', 'VueJS'],
            'level' => 'intermediate'
        ],
        [
            'title' => 'Administration Linux LPIC-1',
            'category' => 'Systèmes & Open Source',
            'short' => 'Le socle indispensable pour tout administrateur système.',
            'tags' => ['Linux', 'Ubuntu', 'Server'],
            'level' => 'beginner'
        ],
    ];

    public function definition(): array
    {
        $courseInfo = $this->faker->randomElement($this->courseData);
        $title = $courseInfo['title'];

        // On cherche la catégorie exacte définie dans le tableau
        $category = Category::where('name', $courseInfo['category'])->first();

        // Si pour une raison X ou Y elle n'est pas trouvée, 
        // on prend une catégorie au hasard parmi celles créées par le Seeder
        $categoryId = $category 
            ? $category->id 
            : Category::inRandomOrder()->first()?->id;

        return [
            'title' => $title,
            'slug' => Str::slug($title) . '-' . Str::random(5),
            'description' => "## Pourquoi suivre cette formation ? \n\n" . 
                             $this->faker->paragraphs(3, true) . "\n\n" . 
                             "### Prérequis \n- " . $this->faker->sentence(),
            
            'thumbnail_path' => 'lms/thumbnails/course-' . rand(1, 10) . '.jpg',
            'price' => $this->faker->randomElement([0, 99, 199, 299, 499]),
            'promo_price' => function (array $attributes) {
                return $attributes['price'] > 0 ? $attributes['price'] * 0.75 : null;
            },
            'level' => $courseInfo['level'],
            'status' => 'published',
            'is_featured' => $this->faker->boolean(20),
            'short_info' => $courseInfo['short'],
            'tags' => json_encode($courseInfo['tags']),
            
            'seo_metadata' => json_encode([
                'title' => $title,
                'description' => $courseInfo['short'],
            ]),

            'category_id' => $categoryId,
            // On récupère un admin existant au lieu d'en créer un nouveau
            'created_by' => User::role('admin')->inRandomOrder()->first()?->id ?? User::first()?->id,
        ];
    }
}