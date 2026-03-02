<?php

namespace App\Http\Controllers;

use App\Models\WebSetting;
use Illuminate\Http\Request;
use Inertia\Inertia;

class WebSettingController extends Controller
{
    /**
     * Affiche la page d'accueil (Landing).
     */
    public function landing()
    {
        // En supposant que 'Landing copy 4.vue' est votre page d'accueil,
        // renommez-la en 'Landing.vue' dans 'resources/js/Pages/Web/'
        return Inertia::render('Web/Landing');
    }

    /**
     * Affiche la page de contact.
     */
    public function contact()
    {
        return Inertia::render('Web/Contact');
    }

    /**
     * Affiche la page "À propos".
     */
    public function about()
    {
        // Assurez-vous d'avoir un fichier 'About.vue' dans 'resources/js/Pages/Web/'
        return Inertia::render('Web/About');
    }

    /**
     * Affiche la page de l'académie.
     */
    public function academy()
    {
        return Inertia::render('Web/Academy');
    }
}
