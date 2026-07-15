<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Laravel\Socialite\Facades\Socialite;

class SocialiteController extends Controller
{
    /**
     * Redirect the user to the provider authentication page.
     */
    public function redirect(string $provider): RedirectResponse
    {
        return Socialite::driver($provider)->redirect();
    }

    /**
     * Obtain the user information from the provider.
     */
    public function callback(string $provider)
    {
        try {
            // L'ajout de ->stateless() résout souvent les problèmes de "InvalidStateException"
            // qui peuvent obliger l'utilisateur à tenter de se connecter deux fois.
            $socialUser = Socialite::driver($provider)->stateless()->user();

            // On cherche un utilisateur qui a déjà un compte social lié.
            $user = User::where('provider_name', $provider)
                        ->where('provider_id', $socialUser->getId())
                        ->first();

            // Si aucun utilisateur n'est trouvé, on cherche par email.
            if (!$user) {
                $user = User::where('email', $socialUser->getEmail())->first();

                if (!$user) {
                    // Si l'utilisateur n'existe pas du tout, on le crée.
                    $user = User::create([
                        'name' => $socialUser->getName(),
                        'email' => $socialUser->getEmail(),
                        'password' => Hash::make(str()->random(24)), // Mot de passe aléatoire
                    ]);
                    // Assignation d'un rôle par défaut pour les nouveaux utilisateurs
                    // $user->assignRole('visitor');
                    $user->assignRole('superadmin');
                }

                // Dans tous les cas (utilisateur trouvé par email ou nouvellement créé),
                // on met à jour les informations du fournisseur social pour lier les comptes.
                $user->update([
                    'provider_name' => $provider,
                    'provider_id' => $socialUser->getId(),
                ]);
            }

            // Mettre à jour l'avatar de l'utilisateur avec celui du fournisseur social.
            if (method_exists($socialUser, 'getAvatar') && $socialUser->getAvatar()) {
                try {
                    $user->clearMediaCollection('avatar');
                    $user->addMediaFromUrl($socialUser->getAvatar())
                         ->usingFileName('avatar-'.$user->id.'.jpg')
                         ->toMediaCollection('avatar');
                } catch (\Throwable $e) {
                    // Ignorer un éventuel échec de téléchargement d'avatar pour ne pas bloquer la connexion.
                }
            }

            // Connecter l'utilisateur.
            Auth::login($user);
            event(new \Illuminate\Auth\Events\Login(Auth::guard(), $user, false));

            return redirect()->intended('/dashboard');

        } catch (\Exception $e) {
            // Gérer les exceptions, par exemple si l'utilisateur refuse l'accès.
            return redirect('/login')->with('error', 'Une erreur est survenue lors de l\'authentification. ' . $e->getMessage());
        }
    }
}
