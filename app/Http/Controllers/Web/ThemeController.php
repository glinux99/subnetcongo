<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\WebSetting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redirect;
use Inertia\Inertia;

class ThemeController extends Controller
{
    public function index()
    {
        $identity = WebSetting::group('general') + WebSetting::group('contact') + WebSetting::group('social');
        return Inertia::render('Web/Theme', ['identity' => $identity]);
    }

    public function save(Request $request)
    {
        $data = $request->validate([
            'site_name'        => 'nullable|string|max:255',
            'site_tagline'     => 'nullable|string|max:255',
            'site_description' => 'nullable|string|max:2000',
            'contact_email'    => 'nullable|email',
            'contact_phone'    => 'nullable|string|max:50',
            'contact_address'  => 'nullable|string|max:500',
            'social_facebook'  => 'nullable|string|max:500',
            'social_twitter'   => 'nullable|string|max:500',
            'social_linkedin'  => 'nullable|string|max:500',
            'social_youtube'   => 'nullable|string|max:500',
            'social_instagram' => 'nullable|string|max:500',
        ]);

        foreach ($data as $key => $value) {
            $group = in_array($key, ['contact_email', 'contact_phone', 'contact_address']) ? 'contact'
                   : (str_starts_with($key, 'social_') ? 'social' : 'general');
            WebSetting::set($key, $value ?? '', $group, 'text');
        }

        return Redirect::route('web.theme')->with('success', 'Identité du site mise à jour.');
    }
}
